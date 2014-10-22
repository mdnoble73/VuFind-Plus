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
		if (in_array($method, array('getPlaceHoldForm', 'placeHold', 'reloadCover'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if (in_array($method, array('GetGoDeeperData', 'getPurchaseOptions'))){
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
		require_once ROOT_DIR . '/services/MyResearch/lib/User.php';

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
		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

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
			$catalog = new CatalogConnection($configArray['Catalog']['driver']);
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

		$holdingData = new stdClass();
		// Get Holdings Data
		if ($catalog->status) {
			$result = $catalog->getHolding($id);
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
						$key = $copy['location'];
						$key = preg_replace('~\W~', '_', $key);
						$holdings[$key][] = $copy;
					}
				}
				if (isset($issueSummaries) && count($issueSummaries) > 0){
					$interface->assign('issueSummaries', $issueSummaries);
					$holdingData->issueSummaries = $issueSummaries;
				}else{
					$interface->assign('holdings', $holdings);
					$holdingData->holdings = $holdings;
				}
			}else{
				$interface->assign('holdings', array());
				$holdingData->holdings = array();
			}

			// Get Acquisitions Data
			$result = $catalog->getPurchaseHistory($id);
			if (PEAR_Singleton::isError($result)) {
				PEAR_Singleton::raiseError($result);
			}
			$interface->assign('history', $result);
			$holdingData->history = $result;
			$timer->logTime("Loaded purchase history");

			//Holdings summary
			$result = $catalog->getStatusSummary($id, false);
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
		global $configArray;
		if ($user){
			$id = $_REQUEST['id'];
			$catalog = new CatalogConnection($configArray['Catalog']['driver']);
			$profile = $catalog->getMyProfile($user);
			$interface->assign('profile', $profile);

			//Get information to show a warning if the user does not have sufficient holds
			require_once ROOT_DIR . '/Drivers/marmot_inc/PType.php';
			$maxHolds = -1;
			//Determine if we should show a warning
			$ptype = new PType();
			$ptype->pType = $user->patronType;
			if ($ptype->find(true)){
				$maxHolds = $ptype->maxHolds;
			}
			$currentHolds = $profile['numHolds'];
			if ($maxHolds != -1 && ($currentHolds + 1 > $maxHolds)){
				$interface->assign('showOverHoldLimit', true);
				$interface->assign('maxHolds', $maxHolds);
				$interface->assign('currentHolds', $currentHolds);
			}

			global $locationSingleton;
			//Get the list of pickup branch locations for display in the user interface.
			$locations = $locationSingleton->getPickupBranches($profile, $profile['homeLocationId']);
			$interface->assign('pickupLocations', $locations);

			require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			$marcRecord = new MarcRecord($id);
			$interface->assign('id', $id);
			$title = $marcRecord->getTitle();
			$interface->assign('title', $title);
			$results = array(
					'title' => 'Place Hold on ' . $title,
					'modalBody' => $interface->fetch("Record/hold-popup.tpl"),
					'modalButtons' => "<input type='submit' name='submit' id='requestTitleButton' value='Submit Hold Request' class='btn btn-primary' onclick='return VuFind.Record.submitHoldForm();'/>"
			);
		}else{
			$results = array(
					'title' => 'Please login',
					'modalBody' => "You must be logged in.  Please close this dialog and login before placing your hold.",
					'modalButtons' => ""
			);
		}

		return json_encode($results);
	}

	function placeHold(){
		global $user;
		global $configArray;
		global $interface;
		$recordId = $_REQUEST['id'];
		if ($user){
			//The user is already logged in
			$barcodeProperty = $configArray['Catalog']['barcodeProperty'];
			$catalog = new CatalogConnection($configArray['Catalog']['driver']);
			if (isset($_REQUEST['selectedItem'])){
				$return = $catalog->placeItemHold($recordId, $_REQUEST['selectedItem'], $user->$barcodeProperty, '', '');
			}else{
				$return = $catalog->placeHold($recordId, $user->$barcodeProperty, '', '');
			}

			if (isset($return['items'])){
				$campus = $_REQUEST['campus'];
				$interface->assign('campus', $campus);
				$interface->assign('items', $return['items']);
				$interface->assign('id', $recordId);
				//Need to place item level holds.
				$results = array(
						'success' => true,
						'needsItemLevelHold' => true,
						'message' => $interface->fetch('Record/item-hold-popup.tpl'),
						'title' => $return['title'],
				);
			}else{
				$message = $return['message'];
				$results = array(
						'success' => $return['result'],
						'message' => $message,
						'title' => $return['title'],
				);
				if (isset($_REQUEST['autologout'])){
					UserAccount::softLogout();
					$results['autologout'] = true;
				}
			}
		} else {
			$results = array(
				'success' => false,
				'message' => 'You must be logged in to place a hold.  Please close this dialog and login.',
				'title' => 'Please login',
			);
			if (isset($_REQUEST['autologout'])){
				UserAccount::softLogout();
				$results['autologout'] = true;
			}
		}
		return json_encode($results);
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

		return json_encode(array('success' => true, 'message' => 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.'));
	}
}
