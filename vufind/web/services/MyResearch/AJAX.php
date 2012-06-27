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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';
require_once 'services/MyResearch/lib/Suggestions.php';

require_once 'services/MyResearch/lib/User_resource.php';
require_once 'services/MyResearch/lib/User_list.php';

class AJAX extends Action {

	function AJAX()
	{
	}

	function launch()
	{
		$method = $_GET['method'];
		if (in_array($method, array('GetSuggestions', 'GetListTitles', 'getOverDriveSummary', 'AddList'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if (in_array($method, array('LoginForm', 'getBulkAddToListForm', 'getPinUpdateForm'))){
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

	// Create new list
	function AddList()
	{
		require_once 'services/MyResearch/ListEdit.php';
		$return = array();
		if (UserAccount::isLoggedIn()) {
			$listService = new ListEdit();
			$result = $listService->addList();
			if (!PEAR::isError($result)) {
				$return['result'] = 'Done';
				$return['newId'] = $result;
			} else {
				$error = $result->getMessage();
				if (empty($error)) {
					$error = 'Error';
				}
				$return['result'] = translate($error);
			}
		} else {
			$return['result'] = "Unauthorized";
		}

		return json_encode($return);
	}

	/**
	 * Get a list of preferred hold pickup branches for a user.
	 *
	 * @return string XML representing the pickup branches.
	 */
	function GetPreferredBranches()
	{
		require_once 'Drivers/marmot_inc/Location.php';
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
			$output = "<result>\n" .
                      "  <PickupLocations>\n";
			$output .= "  </PickupLocations>\n" .
                       '</result>';
		}else{

			$output = "<result>\n" .
                      "  <PickupLocations>\n";

			$patronProfile = $catalog->getMyProfile($patron);

			$location = new Location();
			$locationList = $location->getPickupBranches($patronProfile, $patronProfile['homeLocationId']);

			foreach ($locationList as $location){
				$output .= "<Location id='{$location->code}' selected='{$location->selected}'>$location->displayName</Location>";
			}

			$output .= "  </PickupLocations>\n";
			//Also determine if the hold can be cancelled.
			global $librarySingleton;
			$patronHomeBranch = $librarySingleton->getPatronHomeLibrary();
			$showHoldCancelDate = 0;
			if ($patronHomeBranch != null){
				$showHoldCancelDate = $patronHomeBranch->showHoldCancelDate;
			}
			$output .= "  <AllowHoldCancellation>{$showHoldCancelDate}</AllowHoldCancellation>\n";
			$output .= '</result>';
			
		}
		return $output;
	}

	function GetSuggestions(){
		global $interface;
		global $library;

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
	    		'image' => $configArray['Site']['coverUrl'] . "/bookcover.php?id=". $suggestion['titleInfo']['id'] . "&isn=" . $suggestion['titleInfo']['isbn10'] . "&size=medium&upc=" . $suggestion['titleInfo']['upc'] . "&category=" . $suggestion['titleInfo']['format_category'][0],
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
		require_once('RecordDrivers/MarcRecord.php');
		global $interface;
		global $configArray;
		global $library;

		//Make sure to initialize solr
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$db->debug = true;
		}

		$listId = $_REQUEST['listId'];

		//Get the actual titles for the list
		$list = User_list::staticGet('id', $listId);
		$listTitles = $list->getResources();

		$titles = array();
		foreach ($listTitles as $title){
			if ($title->source == 'VuFind'){
				$upc = $title->upc;
				$formatCategory = $title->format_category;
			
				$titles[] = array(
		    		'id' => $title->record_id,
		    		'image' => $configArray['Site']['coverUrl'] . "/bookcover.php?id=" . $title->record_id . "&isn=" . $title->isbn . "&size=small&upc=" . $upc . "&category=" . $formatCategory,
		    		'title' => $title->title,
		    		'author' => $title->author,
				    'source' => 'VuFind',
				    'link' => $configArray['Site']['path'] . "/Record/" . $title->record_id,
				);
			}else{
				require_once('sys/eContent/EContentRecord.php');
				$record = new EContentRecord();
				$record->id = $title->record_id;
				if ($record->find(true)){
					$titles[] = array(
						'id' => $record->id,
						'image' => $configArray['Site']['coverUrl'] . "/bookcover.php?id=" . $record->id . "&isn=" . $record->getIsbn() . "&size=small&upc=" . $record->upc . "&category=EMedia",
						'title' => $record->title,
						'author' => $record->author,
						'source' => 'eContent',
						'link' => $configArray['Site']['path'] . "/EcontentRecord/" . $record->id,
					);
				}
			}
		}

		foreach ($titles as $key => $rawData){
			$formattedTitle = "<div id=\"scrollerTitleList{$listId}{$key}\" class=\"scrollerTitle\">" .
    			'<a href="' . $rawData['link'] . '" id="descriptionTrigger' . $rawData['id'] . '">' . 
    			"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" . 
    			"</a></div>" . 
    			"<div id='descriptionPlaceholder{$rawData['id']}' style='display:none'></div>";
			$rawData['formattedTitle'] = $formattedTitle;
			$titles[$key] = $rawData;
		}
		 
		$return = array('titles' => $titles, 'currentIndex' => 0);
		return json_encode($return);
	}
	
	function getOverDriveSummary(){
		global $user;
		if ($user){
			require_once 'Drivers/OverDriveDriver.php';
			$overDriveDriver = new OverDriveDriver();
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
		global $user; 
		global $interface;
		$interface->assign('popupTitle', 'Modify PIN number');
		$pageContent = $interface->fetch('MyResearch/modifyPinPopup.tpl');
		$interface->assign('popupContent', $pageContent);
		return $interface->fetch('popup-wrapper.tpl');
	}
}