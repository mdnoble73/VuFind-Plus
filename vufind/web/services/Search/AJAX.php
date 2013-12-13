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

class AJAX extends Action {

	function launch()
	{
		global $analytics;
		$analytics->disableTracking();
		$method = $_REQUEST['method'];
		if (in_array($method, array('GetAutoSuggestList', 'SysListTitles', 'GetListTitles', 'GetStatusSummaries', 'GetSeriesInfo'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$this->$method();
		}elseif (in_array($method, array('getOtherEditions'))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$this->$method();
		}else{
			header('Content-type: text/xml');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
			echo "<AJAXResponse>\n";
			if (is_callable(array($this, $method))) {
				$this->$method();
			} else {
				echo '<Error>Invalid Method</Error>';
			}
			echo '</AJAXResponse>';
		}
	}

	function IsLoggedIn()
	{
		require_once ROOT_DIR . '/services/MyResearch/lib/User.php';

		echo "<result>" .
		(UserAccount::isLoggedIn() ? "True" : "False") . "</result>";
	}

	/**
	 * Get Item Statuses
	 *
	 * This is responsible for printing the holdings information for a
	 * collection of records in XML format.
	 *
	 * @access	public
	 * @author	Chris Delis <cedelis@uillinois.edu>
	 */
	function GetItemStatuses()
	{
		global $configArray;

		require_once ROOT_DIR . '/CatalogConnection.php';

		// Try to find a copy that is available
		$catalog = new CatalogConnection($configArray['Catalog']['driver']);

		$result = $catalog->getStatuses($_GET['id']);

		// In order to detect IDs missing from the status response, create an
		// array with a key for every requested ID.	We will clear keys as we
		// encounter IDs in the response -- anything left will be problems that
		// need special handling.
		$missingIds = array_flip($_GET['id']);

		// Loop through all the status information that came back
		foreach ($result as $record) {
			// If we encountered errors, skip those problem records.
			if (PEAR_Singleton::isError($record)) {
				continue;
			}
			$available = false;
			$location = '';
			$recordId = '';
			$reserve = '';
			$callnumber = '';
			if (count($record)) {
				foreach ($record as $info) {
					if ($recordId == '') $recordId = $info['id'];
					if ($reserve == '') $reserve = $info['reserve'];
					if ($callnumber == '') $callnumber = $info['callnumber'];
					// Find an available copy
					if ($info['availability']) {
						$available = true;
					}

					// Has multiple locations?
					if ($location != 'Multiple Locations') {
						if ($location != '') {
							if ($info['location'] != $location) {
								$location = 'Multiple Locations';
							} else {
								$location = htmlspecialchars($info['location']);
							}
						} else {
							$location = htmlspecialchars($info['location']);
						}
					}
				}

				// The current ID is not missing -- remove it from the missing list.
				unset($missingIds[$recordId]);

				echo ' <item id="' . htmlspecialchars($recordId) . '">';
				if ($available) {
					echo '	<availability>true</availability>';
				} else {
					echo '	<availability>false</availability>';
				}
				echo '	<location>' . htmlspecialchars($location) . '</location>';
				echo '	<reserve>' . htmlspecialchars($reserve) . '</reserve>';
				echo '	<callnumber>' . htmlspecialchars($callnumber) . '</callnumber>';
				echo ' </item>';
			}
		}

		// If any IDs were missing, send back appropriate dummy data
		foreach($missingIds as $missingId => $junk) {
			echo ' <item id="' . htmlspecialchars($missingId) . '">';
			echo '	 <availability>false</availability>';
			echo '	 <location>Unknown</location>';
			echo '	 <reserve>N</reserve>';
			echo '	 <callnumber></callnumber>';
			echo ' </item>';
		}
	}

	/**
	 * Get Item Statuses
	 *
	 * This is responsible for getting holding summary information for a list of
	 * records from the database.
	 *
	 * @access	public
	 * @author	Mark Noble <mnoble@turningleaftech.com>
	 */
	function GetStatusSummaries()
	{
		global $configArray;
		global $interface;
		global $timer;
		global $library;

		$showOtherEditionsPopup = false;
		if ($configArray['Content']['showOtherEditionsPopup']){
			if ($library){
				$showOtherEditionsPopup = ($library->showOtherEditionsPopup == 1);
			}else{
				$showOtherEditionsPopup = true;
			}
		}
		$interface->assign('showOtherEditionsPopup', $showOtherEditionsPopup);
		$showCopiesLineInHoldingsSummary = true;
		if ($library && $library->showCopiesLineInHoldingsSummary == 0){
			$showCopiesLineInHoldingsSummary = false;
		}
		$interface->assign('showCopiesLineInHoldingsSummary', $showCopiesLineInHoldingsSummary);

		require_once ROOT_DIR . '/CatalogConnection.php';

		// Try to find a copy that is available
		/** @var $catalog CatalogConnection */
		$catalog = new CatalogConnection($configArray['Catalog']['driver']);
		$timer->logTime("Initialized Catalog Connection");

		$summaries = $catalog->getStatusSummaries($_GET['id'], true);
		$timer->logTime("Retrieved status summaries");

		$result = array();
		$result['items'] = array();

		if ($configArray['Catalog']['offline']){
			$interface->assign('offline', true);
		}else{
			$interface->assign('offline', false);
		}

		// Loop through all the status information that came back
		foreach ($summaries as $id => $record) {
			// If we encountered errors, skip those problem records.
			if (PEAR_Singleton::isError($record)) {
				continue;
			}
			$itemResults = $record;
			$interface->assign('id', $id);
			$interface->assign('holdingsSummary', $record);

			$formattedHoldingsSummary = $interface->fetch('Record/holdingsSummary.tpl');
			$itemResults['formattedHoldingsSummary'] = $formattedHoldingsSummary;
			$result['items'][] = $itemResults;

		}
		echo json_encode($result);
		$timer->logTime("Formatted results");
	}

	/**
	 * Get Item Statuses
	 *
	 * This is responsible for getting holding summary information for a list of
	 * records from the database.
	 *
	 * @access	public
	 * @author	Mark Noble <mnoble@turningleaftech.com>
	 */
	function GetEContentStatusSummaries()
	{
		global $interface;
		global $timer;

		require_once (ROOT_DIR . '/Drivers/EContentDriver.php');
		$driver = new EContentDriver();
		//Load status summaries
		$result = $driver->getStatusSummaries($_GET['id']);
		$timer->logTime("Retrieved status summaries");

		// Loop through all the status information that came back
		foreach ($result as $id => $record) {
			// If we encountered errors, skip those problem records.
			if (PEAR_Singleton::isError($record)) {
				continue;
			}

			$interface->assign('id', $id);
			$interface->assign('holdingsSummary', $record);

			$formattedHoldingsSummary = $interface->fetch('EcontentRecord/holdingsSummary.tpl');

			echo ' <item id="' . htmlspecialchars($record['recordId']) . '">';
			echo '	<status>' . htmlspecialchars($record['status']) . '</status>';
			echo '	<class>' . htmlspecialchars($record['class']) . '</class>';
			echo '	<showplacehold>' . ($record['showPlaceHold'] ? '1' : '0') . '</showplacehold>';
			echo '	<showcheckout>' . ($record['showCheckout'] ? '1' : '0') . '</showcheckout>';
			echo '	<showaccessonline>' . ($record['showAccessOnline'] ? '1' : '0') . '</showaccessonline>';
			if (isset($record['accessOnlineUrl'])){
				echo '	<accessonlineurl>' . htmlspecialchars($record['accessOnlineUrl']) . '</accessonlineurl>';
				echo '	<accessonlinetext>' . htmlspecialchars($record['accessOnlineText']) . '</accessonlinetext>';
			}
			echo '	<showaddtowishlist>' . ($record['showAddToWishlist'] ? '1' : '0') . '</showaddtowishlist>';
			echo '	<availablecopies>' . htmlspecialchars($record['showAccessOnline']) . '</availablecopies>';
			echo '	<numcopies>' . htmlspecialchars($record['totalCopies']) . '</numcopies>';
			echo '	<holdQueueLength>' . (isset($record['holdQueueLength']) ? htmlspecialchars($record['holdQueueLength']) : '') . '</holdQueueLength>';
			echo '	<isDownloadable>1</isDownloadable>';
			echo '	<formattedHoldingsSummary>' . htmlspecialchars($formattedHoldingsSummary) . '</formattedHoldingsSummary>';
			echo ' </item>';

		}
		$timer->logTime("Formatted results");
	}

	// Email Search Results
	function SendEmail()
	{
		require_once ROOT_DIR . '/services/Search/Email.php';

		$emailService = new Search_Email();
		$result = $emailService->sendEmail($_GET['url'], $_GET['to'], $_GET['from'], $_GET['message']);

		if (PEAR_Singleton::isError($result)) {
			echo '<result>Error</result>';
			echo '<details>' . htmlspecialchars(translate($result->getMessage())) . '</details>';
		} else {
			echo '<result>Done</result>';
		}
	}

	function GetSaveStatus()
	{
		require_once ROOT_DIR . '/services/MyResearch/lib/User.php';
		require_once ROOT_DIR . '/services/MyResearch/lib/Resource.php';

		// check if user is logged in
		if (!($user = UserAccount::isLoggedIn())) {
			echo "<result>Unauthorized</result>";
			return;
		}

		// Check if resource is saved to favorites
		$resource = new Resource();
		$resource->record_id = $_GET['id'];
		if ($resource->find(true)) {
			if ($user->hasResource($resource)) {
				echo '<result>Saved</result>';
			} else {
				echo '<result>Not Saved</result>';
			}
		} else {
			echo '<result>Not Saved</result>';
		}
	}

	/**
	 * Get Save Statuses
	 *
	 * This is responsible for printing the save status for a collection of
	 * records in XML format.
	 *
	 * @access	public
	 * @author	Chris Delis <cedelis@uillinois.edu>
	 */
	function GetSaveStatuses()
	{
		require_once ROOT_DIR . '/services/MyResearch/lib/User.php';
		require_once ROOT_DIR . '/services/MyResearch/lib/Resource.php';
		global $configArray;

		// check if user is logged in
		if (!($user = UserAccount::isLoggedIn())) {
			echo "<result>Unauthorized</result>";
			return;
		}

		for ($i=0; ; $i++) {
			if (! isset($_GET['id' . $i])) break;
			$id = $_GET['id' . $i];
			echo '<item id="' . htmlspecialchars($id) . '">';

			// Check if resource is saved to favorites
			$resource = new Resource();
			$resource->record_id = $id;
			if ($resource->find(true)) {
				$data = $user->getSavedData($id, $resource->source);
				if ($data) {
					echo '<result>';
					// Convert the resource list into JSON so it's easily readable
					// by the calling Javascript code.	Note that we have to entity
					// encode it so it can embed cleanly inside our XML response.
					$json = array();
					foreach ($data as $list) {
						$listData = new User_list();
						$listData->id = $list->list_id;
						$link = '';
						if ($listData->find(true)){
							if ($listData->user_id == $user->id || $listData->public){
								$link = $configArray['Site']['path'] . '/MyResearch/MyList/' . $listData->id;
							}
						}
						$json[] = array('id' => $list->id, 'title' => $list->list_title, 'link' => $link);
					}
					echo htmlspecialchars(json_encode($json));
					echo '</result>';
				} else {
					echo '<result>False</result>';
				}
			} else {
				echo '<result>False</result>';
			}

			echo '</item>';
		}
	}

	function GetSavedData()
	{
		require_once ROOT_DIR . '/services/MyResearch/lib/User.php';
		require_once ROOT_DIR . '/services/MyResearch/lib/Resource.php';

		// check if user is logged in
		if ((!$user = UserAccount::isLoggedIn())) {
			echo "<result>Unauthorized</result>";
			return;
		}

		echo "<result>\n";

		$saved = $user->getSavedData($_GET['id']);
		if ($saved->notes) {
			echo "	<Notes>$saved->notes</Notes>\n";
		}

		$myTagList = $user->getTags($_GET['id']);
		if (count($myTagList)) {
			foreach ($myTagList as $tag) {
				echo "	<Tag>" . $tag->tag . "</Tag>\n";
			}
		}

		echo '</result>';
	}



	function GetAutoSuggestList(){
		require_once ROOT_DIR . '/services/Search/lib/SearchSuggestions.php';
		global $timer;
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$searchTerm = isset($_REQUEST['searchTerm']) ? $_REQUEST['searchTerm'] : $_REQUEST['q'];
		$searchType = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
		$cacheKey = 'auto_suggest_list_' . urlencode($searchType) . '_' . urlencode($searchTerm);
		$searchSuggestions = $memCache->get($cacheKey);
		if ($searchSuggestions == false || isset($_REQUEST['reload'])){
			$suggestions = new SearchSuggestions();
			$commonSearches = $suggestions->getAllSuggestions($searchTerm, $searchType);
			$commonSearchTerms = array();
			foreach ($commonSearches as $searchTerm){
				if (is_array($searchTerm)){
					$commonSearchTerms[] = $searchTerm['phrase'];
				}else{
					$commonSearchTerms[] = $searchTerm;
				}
			}
			$searchSuggestions = json_encode($commonSearchTerms);
			$memCache->set($cacheKey, $searchSuggestions, 0, $configArray['Caching']['search_suggestions'] );
			$timer->logTime("Loaded search suggestions $cacheKey");
		}
		echo $searchSuggestions;
	}

	function getProspectorResults(){
		$prospectorNumTitlesToLoad = $_GET['prospectorNumTitlesToLoad'];
		$prospectorSavedSearchId = $_GET['prospectorSavedSearchId'];

		require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
		global $configArray;
		global $interface;
		global $timer;
		global $library;
		if (isset($library)){
			$interface->assign('showProspectorTitlesAsTab', $library->showProspectorTitlesAsTab);
		}else{
			$interface->assign('showProspectorTitlesAsTab', 0);
		}

		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$db = new $class($url);
		$searchObject = $searchObject->restoreSavedSearch($prospectorSavedSearchId, false);

		//Load results from Prospector
		$prospector = new Prospector();
		$prospectorResults = $prospector->getTopSearchResults($searchObject->getSearchTerms(), $prospectorNumTitlesToLoad);
		$interface->assign('prospectorResults', $prospectorResults);
		$prospectorLink = $prospector->getSearchLink($searchObject->getSearchTerms());
		$interface->assign('prospectorLink', $prospectorLink);
		$timer->logTime('load Prospector titles');
		echo $interface->fetch('Search/ajax-prospector.tpl');
	}

	/**
	 * For historical purposes.	Make sure the old API wll still work.
	 */
	function SysListTitles(){
		if (!isset($_GET['id'])){
			$_GET['id'] = $_GET['name'];
		}
		return $this->GetListTitles();
	}

	/**
	 * @return string JSON encoded data representing the list infromation
	 */
	function GetListTitles(){
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $timer;

		$listName = strip_tags(isset($_GET['scrollerName']) ? $_GET['scrollerName'] : 'List' . $_GET['id']);
		$scrollerName = $listName;

		//Determine the caching parameters
		require_once(ROOT_DIR . '/services/API/ListAPI.php');
		$listAPI = new ListAPI();
		$cacheInfo = $listAPI->getCacheInfoForList();

		$listData = $memCache->get($cacheInfo['cacheName']);
		if (!$listData || isset($_REQUEST['reload']) || (isset($listData['titles']) && count($listData['titles']) == 0)){
			global $interface;

			$titles = $listAPI->getListTitles();
			$timer->logTime("getListTitles");
			$addStrandsTracking = false;
			$strandsInfo = null;
			if ($titles['success'] == true){
				if (isset($titles['strands'])){
					$addStrandsTracking = true;
					$strandsInfo = $titles['strands'];
				}
				$titles = $titles['titles'];
				if (is_array($titles)){
					foreach ($titles as $key => $rawData){

						$interface->assign('description', $rawData['description']);
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
						$imageUrl = $rawData['small_image'];
						if ($_REQUEST['coverSize'] == 'medium'){
							$imageUrl = $rawData['image'];
						}
						$formattedTitle .= "<img src=\"{$imageUrl}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/></a>";
						if (isset($_REQUEST['showRatings']) && $_REQUEST['showRatings']){
							$interface->assign('shortId', $rawData['shortId']);
							$interface->assign('id', $rawData['id']);
							$interface->assign('ratingData', $rawData['ratingData']);
							$interface->assign('showNotInterested', false);
							$formattedTitle .= $interface->fetch('Record/title-rating.tpl');
						}
						$formattedTitle .= "</div>" .
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
		echo $listData;
	}

	function getOtherEditions(){
		global $interface;
		global $analytics;
		$id = $_REQUEST['id'];
		$isEContent = $_REQUEST['isEContent'];

		if ($isEContent == 'true'){
			require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';
			$econtentRecord = new EContentRecord();
			$econtentRecord->id = $id;
			if ($econtentRecord->find(true)){
				$otherEditions = OtherEditionHandler::getEditions($econtentRecord->id, $econtentRecord->getIsbn(), $econtentRecord->getIssn(), 10);
			}else{
				$error = "Sorry we couldn't find that record in the catalog.";
			}
		}else{
			$resource = new Resource();
			$resource->record_id = $id;
			$resource->source = 'VuFind';
			$solrId = $id;
			if ($resource->find(true)){
				$otherEditions = OtherEditionHandler::getEditions($solrId, $resource->isbn , null, 10);
			}else{
				$error = "Sorry we couldn't find that record in the catalog.";
			}
		}

		if (isset($otherEditions)){
			//Get resource for each edition
			$editionResources = array();
			if (is_array($otherEditions)){
				foreach ($otherEditions as $edition){
					/** @var Resource $editionResource */
					$editionResource = new Resource();
					if (preg_match('/econtentRecord(\d+)/', $edition['id'], $matches)){
						$editionResource->source = 'eContent';
						$editionResource->record_id = trim($matches[1]);
					}else{
						$editionResource->record_id = $edition['id'];
						$editionResource->source = 'VuFind';
					}

					if ($editionResource->find(true)){
						$editionResources[] = $editionResource;
					}else{
						$logger= new Logger();
						$logger->log("Could not find resource {$editionResource->source} {$editionResource->record_id} - {$edition['id']}", PEAR_LOG_DEBUG);
					}
				}
				$analytics->addEvent('Enrichment', 'Other Editions', count($otherEditions));
			}else{
				$analytics->addEvent('Enrichment', 'Other Editions Error');
			}
			$interface->assign('otherEditions', $editionResources);
			$interface->assign('popupTitle', 'Other Editions');
			$interface->assign('popupTemplate', 'Resource/otherEditions.tpl');
			echo $interface->fetch('popup-wrapper.tpl');
		}elseif (isset($error)){
			$analytics->addEvent('Enrichment', 'Other Editions Error', $error);
			echo $error;
		}else{
			echo("There are no other editions for this title currently in the catalog.");
			$analytics->addEvent('Enrichment', 'Other Editions', 0, 'No Other ISBNs');
		}
	}

	function GetSeriesInfo(){
		require_once ROOT_DIR . '/sys/NovelistFactory.php';
		$novelist = NovelistFactory::getNovelist();
		if (isset($_REQUEST['isbn'])){
			$isbns = $_REQUEST['isbn'];
		}else{
			$isbns = array();
		}

		$seriesInfo = array();
		foreach ($isbns as $isbn){
			$enrichment = $novelist->loadEnrichment($isbn);
			if (isset($enrichment['seriesTitle'])){
				$seriesInfo[$isbn] = "<a href='/Search/Results?sort=year&lookfor=series:" . urlencode($enrichment['seriesTitle']) . "'>{$enrichment['seriesTitle']}</a>" ;
				if (isset($enrichment['volumeLabel']) && strlen($enrichment['volumeLabel']) > 0){
					$seriesInfo[$isbn] .=  ', ' . $enrichment['volumeLabel'];
				}
			}else{
				$seriesInfo[$isbn] = "N/A";
			}
		}
		echo json_encode(array(
			'success' => true,
			'series' => $seriesInfo
		));
	}
}

function ar2xml($ar)
{
	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->formatOutput = true;
	foreach ($ar as $facet => $value) {
		$element = $doc->createElement($facet);
		foreach ($value as $term => $cnt) {
			$child = $doc->createElement('term', $term);
			$child->setAttribute('count', $cnt);
			$element->appendChild($child);
		}
		$doc->appendChild($element);
	}

	return strstr($doc->saveXML(), "\n");
}