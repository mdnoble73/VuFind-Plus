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

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Proxy_Request.php';

global $configArray;

class Record_AJAX extends Action {

	function launch() {
		global $timer;
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");

		// Methods intend to return JSON data
		if (in_array($method, array('getPlaceHoldForm', 'getBookMaterialForm', 'placeHold', 'reloadCover', 'bookMaterial'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->json_utf8_encode($this->$method());
		}else if (in_array($method, array('GetGoDeeperData', 'getPurchaseOptions', 'getBookingCalendar'))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if ($method == 'downloadMarc'){
			echo $this->$method();
		}else{
			header ('Content-type: text/xml');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

			$xmlResponse = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
			$xmlResponse .= "<AJAXResponse>\n";
			if (is_callable(array($this, $_GET['method']))) {
				$xmlResponse .= $this->$_GET['method']();
			} else {
				$xmlResponse .= '<Error>Invalid Method</Error>';
			}
			$xmlResponse .= '</AJAXResponse>';

			echo $xmlResponse;
		}
	}

	function downloadMarc(){
		$id = $_REQUEST['id'];
		$marcData = MarcLoader::loadMarcRecordByILSId($id);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header("Content-Disposition: attachment; filename={$id}.mrc");
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');

		header('Content-Length: ' . strlen($marcData->toRaw()));
		ob_clean();
		flush();
		echo($marcData->toRaw());
	}

	function getPurchaseOptions(){
		global $interface;
		if (isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
			$interface->assign('id', $id);
			$marcRecord = MarcLoader::loadMarcRecordByILSId($id);
			if ($marcRecord){
				$linkFields = $marcRecord->getFields('856') ;
				$purchaseLinks = array();
				if ($linkFields){
					$field856Index = 0;
					/** @var File_MARC_Data_Field[] $linkFields */
					foreach ($linkFields as $marcField){
						$field856Index++;
						//Get the link
						if ($marcField->getSubfield('u')){
							$link = $marcField->getSubfield('u')->getData();
							if ($marcField->getSubfield('3')){
								$linkText = $marcField->getSubfield('3')->getData();
							}elseif ($marcField->getSubfield('y')){
								$linkText = $marcField->getSubfield('y')->getData();
							}elseif ($marcField->getSubfield('z')){
								$linkText = $marcField->getSubfield('z')->getData();
							}else{
								$linkText = $link;
							}
							//Process some links differently so we can either hide them
							//or show them in different areas of the catalog.
							if (preg_match('/purchase|buy/i', $linkText) ||
							preg_match('/barnesandnoble|tatteredcover|amazon\.com/i', $link)){
								if (preg_match('/barnesandnoble/i', $link)){
									$purchaseLinks[] = array(
		        		  	  'link' => $link,
	                    'linkText' => 'Buy from Barnes & Noble',
		        		  		'storeName' => 'Barnes & Noble',
											'image' => '/images/barnes_and_noble.png',
											'field856Index' => $field856Index,
									);
								}else if (preg_match('/tatteredcover/i', $link)){
									$purchaseLinks[] = array(
	                    'link' => $link,
	                    'linkText' => 'Buy from Tattered Cover',
		        		  		'storeName' => 'Tattered Cover',
											'image' => '/images/tattered_cover.png',
											'field856Index' => $field856Index,
									);
								}else if (preg_match('/amazon\.com/i', $link)){
									$purchaseLinks[] = array(
	                    'link' => $link,
	                    'linkText' => 'Buy from Amazon',
	                  	'storeName' => 'Amazon',
											'image' => '/images/amazon.png',
											'field856Index' => $field856Index,
									);
								}else if (preg_match('/smashwords\.com/i', $link)){
									$purchaseLinks[] = array(
	                    'link' => $link,
	                    'linkText' => 'Buy from Smashwords',
	                  	'storeName' => 'Smashwords',
											'image' => '/images/smashwords.png',
											'field856Index' => $field856Index,
									);
								}else{
									$purchaseLinks[] = array(
	                    'link' => $link,
	                    'linkText' => $linkText,
	                  	'storeName' => 'Smashwords',
											'image' => '',
											'field856Index' => $field856Index,
									);
								}
							}
						}
					}
				} //End checking for purchase information in the marc record


				if (count($purchaseLinks) > 0){
					$interface->assign('purchaseLinks', $purchaseLinks);
				}else{
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($id);
					if ($recordDriver->isValid()){
						$title = $recordDriver->getTitle();
						$author = $recordDriver->getAuthor();

						require_once ROOT_DIR . '/services/Record/Purchase.php';
						$purchaseLinks = Record_Purchase::getStoresForTitle($title, $author);

						if (count($purchaseLinks) > 0){
							$interface->assign('purchaseLinks', $purchaseLinks);
						}else{
							$interface->assign('errors', array("Sorry we couldn't find any stores that offer this title."));
						}
					}else{
						$interface->assign('errors', array("Sorry we couldn't find a resource for that id."));
					}
				}
			}else{
				$errors = array("Could not load marc information for that id.");
				$interface->assign('errors', $errors);
			}
		}else{
			$errors = array("You must provide the id of the title to be purchased. ");
			$interface->assign('errors', $errors);
		}

		echo $interface->fetch('Record/ajax-purchase-options.tpl');
	}

	function IsLoggedIn()
	{
		return "<result>" .
		(UserAccount::isLoggedIn() ? "True" : "False") . "</result>";
	}

	function GetGoDeeperData(){
		require_once(ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php');
		$dataType = $_REQUEST['dataType'];
		$upc = $_REQUEST['upc'];
		$isbn = $_REQUEST['isbn'];

		$formattedData = GoDeeperData::getHtmlData($dataType, 'Record', $isbn, $upc);
		return $formattedData;

	}

	function GetHoldingsInfo(){
		require_once 'Holdings.php';
		global $interface;
		global $configArray;
		global $library;
		global $timer;
		$timer->logTime("Starting GetHoldingsInfo");
		if ($configArray['Catalog']['offline']){
			$interface->assign('offline', true);
		}else{
			$interface->assign('offline', false);
		}
		$fullId = $_REQUEST['id'];
		$recordInfo = explode(':', $fullId);
		$recordType = $recordInfo[0];
		$ilsId = $recordInfo[1];
		$interface->assign('id', $ilsId);
		$interface->assign('recordType', $recordType);

		$showCopiesLineInHoldingsSummary = true;
		$showCheckInGrid = true;
		if ($library && $library->showCopiesLineInHoldingsSummary == 0){
			$showCopiesLineInHoldingsSummary = false;
		}
		$interface->assign('showCopiesLineInHoldingsSummary', $showCopiesLineInHoldingsSummary);
		if ($library && $library->showCheckInGrid == 0){
			$showCheckInGrid = false;
		}
		$interface->assign('showCheckInGrid', $showCheckInGrid);

		try {
			$catalog = CatalogFactory::getCatalogConnectionInstance();;
			$timer->logTime("Connected to catalog");
		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
			return null;
		}

		$hasLastCheckinData = false;
		$holdingData = new stdClass();
		// Get Holdings Data
		if ($catalog->status) {
			$result = $catalog->getHolding($fullId);
			$timer->logTime("Loaded Holding Data from catalog");
			if (PEAR_Singleton::isError($result)) {
				PEAR_Singleton::raiseError($result);
			}
			if (count($result)) {
				$holdings = array();
				$issueSummaries = array();
				foreach ($result as $copy) {
					if (isset($copy['type']) && $copy['type'] == 'issueSummary'){
						$issueSummaries = $result;
						break;
					}else{
						$hasLastCheckinData = (isset($copy['lastCheckinDate']) && $copy['lastCheckinDate'] != null) || $hasLastCheckinData; // if $hasLastCheckinData was true keep that value even when first check is false.
						// flag for at least 1 lastCheckinDate

						$key = $copy['location'];
						$key = preg_replace('~\W~', '_', $key);
						$holdings[$key][] = $copy;
					}
				}
				if (isset($issueSummaries) && count($issueSummaries) > 0){
					$interface->assign('issueSummaries', $issueSummaries);
					$holdingData->issueSummaries = $issueSummaries;
				}else{
					$interface->assign('hasLastCheckinData', $hasLastCheckinData);
					$interface->assign('holdings', $holdings);
					$holdingData->holdings = $holdings;
				}
			}else{
				$interface->assign('holdings', array());
				$holdingData->holdings = array();
			}

			//Holdings summary
			$result = $catalog->getStatusSummary($fullId, false);
			if (PEAR_Singleton::isError($result)) {
				PEAR_Singleton::raiseError($result);
			}
			$holdingData->holdingsSummary = $result;
			$interface->assign('holdingsSummary', $result);
			$timer->logTime("Loaded holdings summary");
			$interface->assign('formattedHoldingsSummary', $interface->fetch('Record/holdingsSummary.tpl'));
		}

		return $interface->fetch('Record/ajax-holdings.tpl');
	}

	function GetProspectorInfo(){
		require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
		global $configArray;
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		/** @var SearchObject_Solr $db */
		$db = new $class($url);

		// Retrieve Full record from Solr
		if (!($record = $db->getRecord($id))) {
			PEAR_Singleton::raiseError(new PEAR_Error('Record Does Not Exist'));
		}

		$prospector = new Prospector();
		//Check to see if the record exists within Prospector so we can get the prospector Id
		$prospectorDetails = $prospector->getProspectorDetailsForLocalRecord($record);
		$interface->assign('prospectorDetails', $prospectorDetails);

		$searchTerms = array(
			array(
				'lookfor' => $record['title'],
				'index' => 'Title'
			),
		);
		if (isset($record['author'])){
			$searchTerms[] = array(
				'lookfor' => $record['author'],
				'index' => 'Author'
			);
		}
		$prospectorResults = $prospector->getTopSearchResults($searchTerms, 10, $prospectorDetails);
		$interface->assign('prospectorResults', $prospectorResults);
		return $interface->fetch('Record/ajax-prospector.tpl');
	}

	function GetReviewInfo(){
		require_once 'Reviews.php';
		$isbn = $_REQUEST['isbn'];
		$id = $_REQUEST['id'];
		$enrichmentData = Record_Reviews::loadReviews($id, $isbn);
		global $interface;
		$interface->assign('id', $id);
		$interface->assign('enrichment', $enrichmentData);
		return $interface->fetch('Record/ajax-reviews.tpl');
	}

	function getPlaceHoldForm(){
		global $interface;
		global $user;
		if ($user){
			$id = $_REQUEST['id'];
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);

			//Get information to show a warning if the user does not have sufficient holds
			require_once ROOT_DIR . '/Drivers/marmot_inc/PType.php';
			$maxHolds = -1;
			//Determine if we should show a warning
			$ptype = new PType();
			$ptype->pType = $user->patronType;
			if ($ptype->find(true)){
				$maxHolds = $ptype->maxHolds;
			}
			$currentHolds = $user->numHoldsIls;
			if ($maxHolds != -1 && ($currentHolds + 1 > $maxHolds)){
				$interface->assign('showOverHoldLimit', true);
				$interface->assign('maxHolds', $maxHolds);
				$interface->assign('currentHolds', $currentHolds);
			}

			//Check to see if the user has linked users that we can place holds for as well
			//If there are linked users, we will add pickup locations for them as well
			$locations = $user->getValidPickupBranches($recordSource);

			$interface->assign('pickupLocations', $locations);

			global $library;
			$interface->assign('showHoldCancelDate', $library->showHoldCancelDate);
			$interface->assign('defaultNotNeededAfterDays', $library->defaultNotNeededAfterDays);
			$interface->assign('showDetailedHoldNoticeInformation', $library->showDetailedHoldNoticeInformation);
			$interface->assign('treatPrintNoticesAsPhoneNotices', $library->treatPrintNoticesAsPhoneNotices);

			require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			$marcRecord = new MarcRecord($id);
			$title = $marcRecord->getTitle();
			$interface->assign('id', $marcRecord->getId());
			if (count($locations) == 0){
				$results = array(
					'title' => 'Unable to place hold',
					'modalBody' => '<p>Sorry, no copies of this title are available to your account.</p>',
					'modalButtons' => ""
				);
			}else{
				$results = array(
					'title' => 'Place Hold on ' . $title,
					'modalBody' => $interface->fetch("Record/hold-popup.tpl"),
					'modalButtons' => "<input type='submit' name='submit' id='requestTitleButton' value='Submit Hold Request' class='btn btn-primary' onclick='return VuFind.Record.submitHoldForm();'>"
				);
			}

		}else{
			$results = array(
					'title' => 'Please login',
					'modalBody' => "You must be logged in.  Please close this dialog and login before placing your hold.",
					'modalButtons' => ""
			);
		}
		return $results;
	}

	function getBookMaterialForm($errorMessage = null){
		global $interface;
		global $user;
		if ($user){
			$id = $_REQUEST['id'];

			require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			$marcRecord = new MarcRecord($id);
			$title = $marcRecord->getTitle();
			$interface->assign('id', $id);
			if ($errorMessage) $interface->assign('errorMessage', $errorMessage);
			$results = array(
					'title' => 'Schedule ' . $title,
					'modalBody' => $interface->fetch("Record/book-materials-form.tpl"),
					'modalButtons' => '<button class="btn btn-primary" onclick="$(\'#bookMaterialForm\').submit()">Schedule Item</button>'
			    // Clicking invokes submit event, which allows the validator to act before calling the ajax handler
			);
		}else{
			$results = array(
					'title' => 'Please login',
					'modalBody' => "You must be logged in.  Please close this dialog and login before scheduling this item.",
					'modalButtons' => ""
			);
		}
		return $results;
	}

	function getBookingCalendar(){
		$recordId = $_REQUEST['id'];
		if (strpos($recordId, ':') !== false) list(,$recordId) = explode(':', $recordId, 2); // remove any prefix from the recordId
		if (!empty($recordId)) {
			global $user;
			$catalog = $user->getCatalogDriver();
//			$catalog = CatalogFactory::getCatalogConnectionInstance();
			return $catalog->getBookingCalendar($recordId);
		}
	}

	function bookMaterial(){
		if (!empty($_REQUEST['id'])){
			$recordId = $_REQUEST['id'];
			if (strpos($recordId, ':') !== false) list(,$recordId) = explode(':', $recordId, 2); // remove any prefix from the recordId
		}
		if (empty($recordId)) {
			return array('success' => false, 'message' => 'Item ID is required.');
		}
		if (isset($_REQUEST['startDate'])) {
			$startDate = $_REQUEST['startDate'];
		} else {
			return array('success' => false, 'message' => 'Start Date is required.');
		}

		$startTime = empty($_REQUEST['startTime']) ? null : $_REQUEST['startTime'];
		$endDate   = empty($_REQUEST['endDate'])   ? null : $_REQUEST['endDate'];
		$endTime   = empty($_REQUEST['endTime'])   ? null : $_REQUEST['endTime'];

		global $user;
		if ($user) { // The user is already logged in
//			$catalog = CatalogFactory::getCatalogConnectionInstance();
			return $user->bookMaterial($recordId, $startDate, $startTime, $endDate, $endTime);
			if (!empty($return['retry'])) {
				return $this->getBookMaterialForm($return['message']); // send back error message with form to try again
			} else { // otherwise return output to user's browser
				if ($return['success'] == true) {
					$return['message'] = '<div class="alert alert-success">' . $return['message'] . '</div>';
				}
					// wrap a success message in a success alert
				return $return;
			}

		} else {
			return array('success' => false, 'message' => 'User not logged in.');
		}
	}

	function json_utf8_encode($result) { // TODO: add to other ajax.php or make part of a ajax base class
		try {
			require_once ROOT_DIR . '/sys/Utils/ArrayUtils.php';
			$utf8EncodedValue = ArrayUtils::utf8EncodeArray($result);
			$output           = json_encode($utf8EncodedValue);
			$error            = json_last_error();
			if ($error != JSON_ERROR_NONE || $output === FALSE) {
				if (function_exists('json_last_error_msg')) {
					$output = json_encode(array('error' => 'error_encoding_data', 'message' => json_last_error_msg()));
				} else {
					$output = json_encode(array('error' => 'error_encoding_data', 'message' => json_last_error()));
				}
				global $configArray;
				if ($configArray['System']['debug']) {
					print_r($utf8EncodedValue);
				}
			}
		}
		catch (Exception $e){
			$output = json_encode(array('error'=>'error_encoding_data', 'message' => $e));
			global $logger;
			$logger->log("Error encoding json data $e", PEAR_LOG_ERR);
		}
		return $output;
	}

	function placeHold(){
		global $interface;
		global $analytics;
		$analytics->enableTracking();
		$recordId = $_REQUEST['id'];
		if (strpos($recordId, ':') > 0){
			list($source, $shortId) = explode(':', $recordId);
		}else{
			$shortId = $recordId;
		}

		global $user;
		if ($user){
			//The user is already logged in

			if (!empty($_REQUEST['campus'])) {
			 //Check to see what account we should be placing a hold for
				//Rather than asking the user for this explicitly, we do it based on the pickup location
				$campus   = $_REQUEST['campus'];
				$location = new Location();
				/** @var Location[] $userPickupLocations */
				$userPickupLocations = $location->getPickupBranches($user);
				$patron              = null;
				foreach ($userPickupLocations as $tmpLocation) {
					if ($tmpLocation->code == $campus) {
						$patron = $user;
						break;
					}
				}
				if ($patron == null) {
					//Check linked users
					$linkedUsers = $user->getLinkedUsers();
					foreach ($linkedUsers as $tmpUser) {
						$location = new Location();
						/** @var Location[] $userPickupLocations */
						$userPickupLocations = $location->getPickupBranches($tmpUser);
						foreach ($userPickupLocations as $tmpLocation) {
							if ($tmpLocation->code == $campus) {
								$patron = $tmpUser;
								break;
							}
						}
						if ($patron != null) {
							break;
						}
					}
				}

				if ($patron == null) {
					$results = array(
						'success' => false,
						'message' => 'You must select a valid user to place the hold for.',
						'title' => 'Select valid user',
					);
				} else {
					if (isset($_REQUEST['selectedItem'])) {
						$return = $patron->placeItemHold($shortId, $_REQUEST['selectedItem'], $campus);
					} else {
						$return = $patron->placeHold($shortId, $campus);
					}

					if (isset($return['items'])) {
						$interface->assign('campus', $campus);
						$items = $return['items'];
						$interface->assign('items', $items);
						$interface->assign('message', $return['message']);
						$interface->assign('id', $shortId);
						$interface->assign('patronId', $patron->id);

						global $library;
						$interface->assign('showDetailedHoldNoticeInformation', $library->showDetailedHoldNoticeInformation);
						$interface->assign('treatPrintNoticesAsPhoneNotices', $library->treatPrintNoticesAsPhoneNotices);

						//Need to place item level holds.
						$results = array(
							'success' => true,
							'needsItemLevelHold' => true,
							'message' => $interface->fetch('Record/item-hold-popup.tpl'),
							'title' => $return['title'],
						);
					} else { // Completed Hold Attempt
						$interface->assign('message', $return['message']);
						$interface->assign('success', $return['success']);

						//Get library based on patron home library since that is what controls their notifications rather than the active interface.
						//$library = Library::getPatronHomeLibrary();
						global $library;
						$canUpdateContactInfo = $library->allowProfileUpdates == 1;
						// set update permission based on active library's settings. Or allow by default.
						$canChangeNoticePreference = $library->showNoticeTypeInProfile == 1;
						// when user preference isn't set, they will be shown a link to account profile. this link isn't needed if the user can not change notification preference.
						$interface->assign('canUpdate', $canUpdateContactInfo);
						$interface->assign('canChangeNoticePreference', $canChangeNoticePreference);

						$interface->assign('showDetailedHoldNoticeInformation', $library->showDetailedHoldNoticeInformation);
						$interface->assign('treatPrintNoticesAsPhoneNotices', $library->treatPrintNoticesAsPhoneNotices);

						$results = array(
							'success' => $return['success'],
							'message' => $interface->fetch('Record/hold-success-popup.tpl'),
							'title' => $return['title'],
						);
						if (isset($_REQUEST['autologout'])) {
							UserAccount::softLogout();
							$results['autologout'] = true;
						}
					}
				}
			} else {
				$results = array(
					'success' => false,
					'message' => 'No pick-up location is set.  Please choose a Location for the title to be picked up at.',
				);
			}

			if (isset($_REQUEST['autologout'])){
				UserAccount::softLogout();
				$results['autologout'] = true;
			}
		} else {
			$results = array(
				'success' => false,
				'message' => 'You must be logged in to place a hold.  Please close this dialog and login.',
				'title' => 'Please login',
			);
		}
		return $results;
	}

	function reloadCover(){
		require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
		$id = $_REQUEST['id'];
		$recordDriver = new MarcRecord($id);

		//Reload small cover
		$smallCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('small')) . '&reload';
		file_get_contents($smallCoverUrl);

		//Reload medium cover
		$mediumCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('medium')) . '&reload';
		file_get_contents($mediumCoverUrl);

		//Reload large cover
		$largeCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('large')) . '&reload';
		file_get_contents($largeCoverUrl);

		//Also reload covers for the grouped work
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkDriver = new GroupedWorkDriver($recordDriver->getGroupedWorkId());
		global $configArray;
		//Reload small cover
		$smallCoverUrl = $configArray['Site']['coverUrl'] . str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('small')) . '&reload';
		file_get_contents($smallCoverUrl);

		//Reload medium cover
		$mediumCoverUrl = $configArray['Site']['coverUrl'] . str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('medium')) . '&reload';
		file_get_contents($mediumCoverUrl);

		//Reload large cover
		$largeCoverUrl = $configArray['Site']['coverUrl'] . str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('large')) . '&reload';
		file_get_contents($largeCoverUrl);

		return array('success' => true, 'message' => 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.');
	}

}
