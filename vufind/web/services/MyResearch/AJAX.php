<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
 *
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/MyResearch/lib/Suggestions.php';

require_once ROOT_DIR . '/services/MyResearch/lib/User_resource.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';

class AJAX extends Action {

	function MyResearch_AJAX()
	{
	}

	function launch()
	{
		$method = $_GET['method'];
		if (in_array($method, array('GetSuggestions', 'GetListTitles', 'getOverDriveSummary', 'AddList', 'GetPreferredBranches', 'clearUserRating', 'requestPinReset', 'getCreateListForm'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if (in_array($method, array('LoginForm', 'getBulkAddToListForm', 'getPinUpdateForm', 'getCitationFormatsForm', 'getPinResetForm'))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else{
			header ('Content-type: text/xml');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$xml = '<?xml version="1.0" encoding="UTF-8"?' . ">\n" .
								 "<AJAXResponse>\n";
			if (is_callable(array($this, $_GET['method']))) {
				$xml .= $this->$_GET['method']();
			} else {
				$xml .= '<Error>Invalid Method</Error>';
			}
			$xml .= '</AJAXResponse>';

			echo $xml;
		}
	}

	function clearUserRating(){
		global $user;
		$source = $_REQUEST['source'];
		$recordId = $_REQUEST['recordId'];
		$result = array('result' => false);
		if ($source == 'VuFind'){
			require_once ROOT_DIR . '/Drivers/marmot_inc/UserRating.php';
			$resource = new Resource();
			$resource->record_id = $recordId;
			$resource->source = 'VuFind';
			if ($resource->find(true)){
				$rating = new UserRating();
				$rating->userid = $user->id;
				$rating->resourceid = $resource->id;
				if ($rating->find(true)){
					if ($rating->delete()){
						$result = array('result' => true, 'message' => 'deleted user rating for resource ' . $rating->resourceid);
					}
				}
			}
		}else{
			require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
			$econtentRating = new EContentRating();
			$econtentRating->userId = $user->id;
			$econtentRating->recordId = $recordId;
			if ($econtentRating->find(true)){
				if ($econtentRating->delete()){
					$result = array('result' => true);
				}
			}
		}
		return json_encode($result);
	}

	// Create new list
	function AddList()
	{
		global $user;
		$return = array();
		if ($user) {
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
			$title = isset($_REQUEST['title']) ? $_REQUEST['title'] : '';
			if (strlen(trim($title)) == 0){
				$return['result'] = "false";
				$return['message'] = "You must provide a title for the list";
			}else{
				$list = new UserList();
				$list->title = $_REQUEST['title'];
				$list->user_id = $user->id;
				//Check to see if there is already a list with this id
				$existingList = false;
				if ($list->find(true)){
					$existingList = true;
				}
				$list->description = $_REQUEST['desc'];
				$list->public = $_REQUEST['public'];
				if ($existingList){
					$list->update();
				}else{
					$list->insert();
				}

				if (isset($_REQUEST['recordId'])){
					$recordToAdd = $_REQUEST['recordId'];
					require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
					//Check to see if the user has already added the title to the list.
					$userListEntry = new UserListEntry();
					$userListEntry->listId = $list->id;
					$userListEntry->groupedWorkPermanentId = $recordToAdd;
					if (!$userListEntry->find(true)){
						$userListEntry->dateAdded = time();
						$userListEntry->insert();
					}
				}

				$return['result'] = 'true';
				$return['newId'] = $list->id;
				if ($existingList){
					$return['message'] = "Updated list {$title} successfully" ;
				}else{
					$return['message'] = "Created list {$title} successfully" ;
				}
			}
		} else {
			$return['result'] = "false";
			$return['message'] = "You must be logged in to create a list";
		}

		return json_encode($return);
	}

	function getCreateListForm(){
		global $interface;

		$id = $_REQUEST['recordId'];
		$interface->assign('recordId', $id);

		$results = array(
				'title' => 'Create new List',
				'modalBody' => $interface->fetch("MyResearch/list-form.tpl"),
				'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.Account.addList(\"{$id}\"); return false;'>Create List</span>"
		);
		return json_encode($results);
	}

	/**
	 * Get a list of preferred hold pickup branches for a user.
	 *
	 * @return string XML representing the pickup branches.
	 */
	function GetPreferredBranches()
	{
		require_once ROOT_DIR . '/Drivers/marmot_inc/Location.php';
		global $configArray;
		global $user;

		try {
			$catalog = new CatalogConnection($configArray['Catalog']['driver']);
		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}

		$username = $_REQUEST['username'];
		$password = $_REQUEST['barcode'];

		//Get the list of pickup branch locations for display in the user interface.
		$patron = $catalog->patronLogin($username, $password);
		if ($patron == null){
			$result = array(
				'PickupLocations' => array(),
				'loginFailed' => true
			);
		}else{
			$patronProfile = $catalog->getMyProfile($patron);

			$location = new Location();
			$locationList = $location->getPickupBranches($patronProfile, $patronProfile['homeLocationId']);
			$pickupLocations = array();
			foreach ($locationList as $curLocation){
				$pickupLocations[] = array(
					'id' => $curLocation->locationId,
					'displayName' => $curLocation->displayName,
					'selected' => $curLocation->selected,
				);
			}
			require_once ROOT_DIR . '/Drivers/marmot_inc/PType.php';
			$maxHolds = -1;
			//Determine if we should show a warning
			$ptype = new PType();
			$ptype->pType = $patronProfile['ptype'];
			if ($ptype->find(true)){
				$maxHolds = $ptype->maxHolds;
			}
			$currentHolds = $patronProfile['numHolds'];
			$holdCount = $_REQUEST['holdCount'];
			$showOverHoldLimit = false;
			if ($maxHolds != -1 && ($currentHolds + $holdCount > $maxHolds)){
				$showOverHoldLimit = true;
			}

			//Also determine if the hold can be cancelled.
			global $librarySingleton;
			$patronHomeBranch = $librarySingleton->getPatronHomeLibrary();
			$showHoldCancelDate = 0;
			if ($patronHomeBranch != null){
				$showHoldCancelDate = $patronHomeBranch->showHoldCancelDate;
			}
			$result = array(
				'PickupLocations' => $pickupLocations,
				'loginFailed' => false,
				'AllowHoldCancellation' => $showHoldCancelDate,
				'showOverHoldLimit' => $showOverHoldLimit,
				'maxHolds' => $maxHolds,
				'currentHolds' => $currentHolds
			);
		}
		return json_encode($result);
	}

	function GetSuggestions(){
		global $interface;
		global $library;
		global $configArray;

		//Make sure to initialize solr
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		//Get suggestions for the user
		$suggestions = Suggestions::getSuggestions();
		$interface->assign('suggestions', $suggestions);
		if (isset($library)){
			$interface->assign('showRatings', $library->showRatings);
		}else{
			$interface->assign('showRatings', 1);
		}

		//return suggestions as json for display in the title scroller
		$titles = array();
		foreach ($suggestions as $suggestion){
			$titles[] = array(
					'id' => $suggestion['titleInfo']['id'],
					'image' => $configArray['Site']['coverUrl'] . "/bookcover.php?id=". $suggestion['titleInfo']['id'] . "&issn=" . $suggestion['titleInfo']['issn'] . "&isn=" . $suggestion['titleInfo']['isbn10'] . "&size=medium&upc=" . $suggestion['titleInfo']['upc'] . "&category=" . $suggestion['titleInfo']['format_category'][0],
					'title' => $suggestion['titleInfo']['title'],
					'author' => $suggestion['titleInfo']['author'],
					'basedOn' => $suggestion['basedOn']
			);
		}

		foreach ($titles as $key => $rawData){
			$formattedTitle = "<div id=\"scrollerTitleSuggestion{$key}\" class=\"scrollerTitle\">" .
					'<a href="' . $configArray['Site']['path'] . "/Record/" . $rawData['id'] . '" id="descriptionTrigger' . $rawData['id'] . '">' .
					"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
					"</a></div>" .
					"<div id='descriptionPlaceholder{$rawData['id']}' style='display:none'></div>";
			$rawData['formattedTitle'] = $formattedTitle;
			$titles[$key] = $rawData;
		}

		$return = array('titles' => $titles, 'currentIndex' => 0);
		return json_encode($return);
		//return $interface->fetch('MyResearch/ajax-suggestionsList.tpl');
	}

	function GetListTitles(){
		global $memCache;
		global $configArray;
		global $timer;

		$listId = $_REQUEST['listId'];
		$_REQUEST['id'] = 'list:' . $listId;
		$listName = strip_tags(isset($_GET['scrollerName']) ? $_GET['scrollerName'] : 'List' . $listId);
		$scrollerName = isset($_GET['scrollerName']) ? strip_tags($_GET['scrollerName']) : $listName;

		//Determine the caching parameters
		require_once(ROOT_DIR . '/services/API/ListAPI.php');
		$listAPI = new ListAPI();
		$cacheInfo = $listAPI->getCacheInfoForList();

		$listData = $memCache->get($cacheInfo['cacheName']);

		if (!$listData || isset($_REQUEST['reload']) || (isset($listData['titles']) && count($listData['titles'] == 0))){
			global $interface;

			$titles = $listAPI->getListTitles();
			$timer->logTime("getListTitles");
			$addStrandsTracking = false;
			if ($titles['success'] == true){
				if (isset($titles['strands'])){
					$addStrandsTracking = true;
					$strandsInfo = $titles['strands'];
				}
				$titles = $titles['titles'];
				if (is_array($titles)){
					foreach ($titles as $key => $rawData){

						$interface->assign('title', $rawData['title']);
						$interface->assign('description', $rawData['description'].'w00t!');
						$interface->assign('length', $rawData['length']);
						$interface->assign('publisher', $rawData['publisher']);
						$descriptionInfo = $interface->fetch('Record/ajax-description-popup.tpl') ;

						$formattedTitle = "<div id=\"scrollerTitle{$scrollerName}{$key}\" class=\"scrollerTitle\">";
						$shortId = $rawData['id'];
						if (preg_match('/econtentRecord\d+/i', $rawData['id'])){
							$recordId = substr($rawData['id'], 14);
							$formattedTitle .= '<a href="' . $configArray['Site']['path'] . "/EcontentRecord/" . $recordId . ($addStrandsTracking ? "?strandsReqId={$strandsInfo['reqId']}&strandsTpl={$strandsInfo['tpl']}" : '') . '" id="descriptionTrigger' . $rawData['id'] . '">';
						}else{
							$shortId = str_replace('.b', 'b', $shortId);
							$formattedTitle .= '<a href="' . $configArray['Site']['path'] . "/Record/" . $rawData['id'] . ($addStrandsTracking ? "?strandsReqId={$strandsInfo['reqId']}&strandsTpl={$strandsInfo['tpl']}" : '') . '" id="descriptionTrigger' . $shortId . '">';
						}
						$formattedTitle .= "<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
								"</a></div>" .
								"<div id='descriptionPlaceholder{$shortId}' style='display:none' class='loaded'>" .
									$descriptionInfo .
								"</div>";
						$rawData['formattedTitle'] = $formattedTitle;
						$titles[$key] = $rawData;
					}
				}
				$currentIndex = count($titles) > 5 ? floor(count($titles) / 2) : 0;

				$return = array('titles' => $titles, 'currentIndex' => $currentIndex);
				$listData = json_encode($return);
			}else{
				$return = array('titles' => array(), 'currentIndex' =>0);
				$listData = json_encode($return);
			}

			$memCache->set($cacheInfo['cacheName'], $listData, 0, $cacheInfo['cacheLength']);

		}

		return $listData;
	}

	function getOverDriveSummary(){
		global $user;
		if ($user){
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$overDriveDriver = OverDriveDriverFactory::getDriver();
			$summary = $overDriveDriver->getOverDriveSummary($user);
			return json_encode($summary);
		}else{
			return array('error' => 'There is no user currently logged in.');
		}
	}

	function LoginForm(){
		global $interface;
		return $interface->fetch('MyResearch/ajax-login.tpl');
	}

	function getBulkAddToListForm(){
		global $interface;
		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['listId']));
		$interface->assign('popupTitle', 'Add titles to list');
		$pageContent = $interface->fetch('MyResearch/bulkAddToListPopup.tpl');
		$interface->assign('popupContent', $pageContent);
		echo $interface->fetch('popup-wrapper.tpl');
	}

	function getPinUpdateForm(){
		global $interface;
		$interface->assign('popupTitle', 'Modify PIN number');
		$pageContent = $interface->fetch('MyResearch/modifyPinPopup.tpl');
		$interface->assign('popupContent', $pageContent);
		return $interface->fetch('popup-wrapper.tpl');
	}

	function getPinResetForm(){
		global $interface;
		$interface->assign('popupTitle', 'Reset PIN Request');
		$pageContent = $interface->fetch('MyResearch/resetPinPopup.tpl');
		$interface->assign('popupContent', $pageContent);
		return $interface->fetch('popup-wrapper.tpl');
	}

	function requestPinReset(){
		global $configArray;

		try {
			/** @var DriverInterface|MillenniumDriver|Nashville|Marmot|Sierra|Horizon $catalog */
			$catalog = new CatalogConnection($configArray['Catalog']['driver']);

			$barcode = $_REQUEST['barcode'];

			//Get the list of pickup branch locations for display in the user interface.
			$result = $catalog->requestPinReset($barcode);
			return json_encode($result);

		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}
	}

	function getCitationFormatsForm(){
		global $interface;
		$interface->assign('popupTitle', 'Please select a citation format');
		$interface->assign('listId', $_REQUEST['listId']);
		$citationFormats = CitationBuilder::getCitationFormats();
		$interface->assign('citationFormats', $citationFormats);
		$pageContent = $interface->fetch('MyResearch/getCitationFormatPopup.tpl');
		$interface->assign('popupContent', $pageContent);
		return $interface->fetch('popup-wrapper.tpl');
	}
}
