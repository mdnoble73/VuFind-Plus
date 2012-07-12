<?php
/**
 *
 * Copyright (C) Andrew Nagy 2009
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
require_once 'services/MyResearch/lib/User.php';
require_once 'services/MyResearch/lib/Search.php';
require_once 'Drivers/marmot_inc/Prospector.php';

require_once 'sys/SolrStats.php';
require_once 'sys/Pager.php';

class Results extends Action {

	private $solrStats = false;
	private $query;

	function launch() {
		global $interface;
		global $configArray;
		global $timer;
		global $user;

		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';

		// Include Search Engine Class
		require_once 'sys/' . $configArray['Index']['engine'] . '.php';
		$timer->logTime('Include search engine');

		//Check to see if the year has been set and if so, convert to a filter and resend.
		$dateFilters = array('publishDate');
		foreach ($dateFilters as $dateFilter){
			if (isset($_REQUEST[$dateFilter . 'yearfrom']) || isset($_REQUEST[$dateFilter . 'yearto'])){
				$queryParams = $_GET;
				$yearFrom = preg_match('/^\d{2,4}$/', $_REQUEST[$dateFilter . 'yearfrom']) ? $_REQUEST[$dateFilter . 'yearfrom'] : '*';
				$yearTo = preg_match('/^\d{2,4}$/', $_REQUEST[$dateFilter . 'yearto']) ? $_REQUEST[$dateFilter . 'yearto'] : '*';
				if (strlen($yearFrom) == 2){
					$yearFrom = '19' . $yearFrom;
				}else if (strlen($yearFrom) == 3){
					$yearFrom = '0' . $yearFrom;
				}
				if (strlen($yearTo) == 2){
					$yearTo = '19' . $yearTo;
				}else if (strlen($yearFrom) == 3){
					$yearTo = '0' . $yearTo;
				}
				if ($yearTo != '*' && $yearFrom != '*' && $yearTo < $yearFrom){
					$tmpYear = $yearTo;
					$yearTo = $yearFrom;
					$yearFrom = $tmpYear;
				}
				unset($queryParams['module']);
				unset($queryParams['action']);
				unset($queryParams[$dateFilter . 'yearfrom']);
				unset($queryParams[$dateFilter . 'yearto']);
				if (!isset($queryParams['sort'])){
					$queryParams['sort'] = 'year';
				}
				$queryParamStrings = array();
				foreach($queryParams as $paramName => $queryValue){
					if (is_array($queryValue)){
						foreach ($queryValue as $arrayValue){
							if (strlen($arrayValue) > 0){
								$queryParamStrings[] = $paramName . '[]=' . $arrayValue;
							}
						}
					}else{
						if (strlen($queryValue)){
							$queryParamStrings[] = $paramName . '=' . $queryValue;
						}
					}
				}
				if ($yearFrom != '*' || $yearTo != '*'){
					$queryParamStrings[] = "&filter[]=$dateFilter:[$yearFrom+TO+$yearTo]";
				}
				$queryParamString = join('&', $queryParamStrings);
				header("Location: {$configArray['Site']['path']}/Search/Results?$queryParamString");
				exit;
			}
		}
		
		$rangeFilters = array('lexile_score', 'accelerated_reader_reading_level', 'accelerated_reader_point_value');
		foreach ($rangeFilters as $filter){
			if (isset($_REQUEST[$filter . 'from']) || isset($_REQUEST[$filter . 'to'])){
				$queryParams = $_GET;
				$from = preg_match('/^\d*(\.\d*)?$/', $_REQUEST[$filter . 'from']) ? $_REQUEST[$filter . 'from'] : '*';
				$to = preg_match('/^\d*(\.\d*)?$/', $_REQUEST[$filter . 'to']) ? $_REQUEST[$filter . 'to'] : '*';
				
				if ($to != '*' && $from != '*' && $to < $from){
					$tmpFilter = $to;
					$to = $from;
					$from = $tmpFilter;
				}
				unset($queryParams['module']);
				unset($queryParams['action']);
				unset($queryParams[$filter . 'from']);
				unset($queryParams[$filter . 'to']);
				$queryParamStrings = array();
				foreach($queryParams as $paramName => $queryValue){
					if (is_array($queryValue)){
						foreach ($queryValue as $arrayValue){
							if (strlen($arrayValue) > 0){
								$queryParamStrings[] = $paramName . '[]=' . $arrayValue;
							}
						}
					}else{
						if (strlen($queryValue)){
							$queryParamStrings[] = $paramName . '=' . $queryValue;
						}
					}
				}
				if ($yearFrom != '*' || $yearTo != '*'){
					$queryParamStrings[] = "&filter[]=$filter:[$from+TO+$to]";
				}
				$queryParamString = join('&', $queryParamStrings);
				header("Location: {$configArray['Site']['path']}/Search/Results?$queryParamString");
				exit;
			}
		}

		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$timer->logTime("Init Search Object");

		// Build RSS Feed for Results (if requested)
		if ($searchObject->getView() == 'rss') {
			// Throw the XML to screen
			echo $searchObject->buildRSS();
			// And we're done
			exit();
		}else if ($searchObject->getView() == 'excel'){
			// Throw the Excel spreadsheet to screen for download
			echo $searchObject->buildExcel();
			// And we're done
			exit();
		}

		// TODO : Investigate this... do we still need
		// If user wants to print record show directly print-dialog box
		if (isset($_GET['print'])) {
			$interface->assign('print', true);
		}

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed
		$interface->setPageTitle('Search Results');
		$interface->assign('sortList',   $searchObject->getSortList());
		$interface->assign('rssLink',    $searchObject->getRSSUrl());
		$interface->assign('excelLink',  $searchObject->getExcelUrl());

		$timer->logTime('Setup Search');
		
		// Process Search
		$result = $searchObject->processSearch(true, true);
		if (PEAR::isError($result)) {
			PEAR::raiseError($result->getMessage());
		}
		$timer->logTime('Process Search');

		// Some more variables
		//   Those we can construct AFTER the search is executed, but we need
		//   no matter whether there were any results
		$interface->assign('qtime',               round($searchObject->getQuerySpeed(), 2));
		$interface->assign('spellingSuggestions', $searchObject->getSpellingSuggestions());
		$interface->assign('lookfor',             $searchObject->displayQuery());
		$interface->assign('searchType',          $searchObject->getSearchType());
		// Will assign null for an advanced search
		$interface->assign('searchIndex',         $searchObject->getSearchIndex());

		// We'll need recommendations no matter how many results we found:
		$interface->assign('topRecommendations',
		$searchObject->getRecommendationsTemplates('top'));
		$interface->assign('sideRecommendations',
		$searchObject->getRecommendationsTemplates('side'));

		// 'Finish' the search... complete timers and log search history.
		$searchObject->close();
		$interface->assign('time', round($searchObject->getTotalSpeed(), 2));
		// Show the save/unsave code on screen
		// The ID won't exist until after the search has been put in the search history
		//    so this needs to occur after the close() on the searchObject
		$interface->assign('showSaved',   true);
		$interface->assign('savedSearch', $searchObject->isSavedSearch());
		$interface->assign('searchId',    $searchObject->getSearchId());
		$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$interface->assign('page', $currentPage);

		//Enable and disable functionality based on library settings
		//This must be done before we process each result
		global $library;
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		$showHoldButton = 1;
		$showHoldButtonInSearchResults = 1;
		if (isset($library) && $location != null){
			$interface->assign('showFavorites', $library->showFavorites);
			$interface->assign('showComments', $library->showComments);
			$showHoldButton = (($location->showHoldButton == 1) && ($library->showHoldButton == 1)) ? 1 : 0;
			$showHoldButtonInSearchResults = (($location->showHoldButton == 1) && ($library->showHoldButtonInSearchResults == 1)) ? 1 : 0;
		}else if ($location != null){
			$interface->assign('showFavorites', 1);
			$showHoldButton = $location->showHoldButton;
		}else if (isset($library)){
			$interface->assign('showFavorites', $library->showFavorites);
			$showHoldButton = $library->showHoldButton;
			$showHoldButtonInSearchResults = $library->showHoldButtonInSearchResults;
			$interface->assign('showComments', $library->showComments);
		}else{
			$interface->assign('showFavorites', 1);
			$interface->assign('showComments', 1);
		}
		if ($showHoldButton == 0){
			$showHoldButtonInSearchResults = 0;
		}
		$interface->assign('showHoldButton', $showHoldButtonInSearchResults);
		$interface->assign('page_body_style', 'sidebar_left');

		$enableProspectorIntegration = isset($configArray['Content']['Prospector']) ? $configArray['Content']['Prospector'] : false;
		$showRatings = 1;
		if (isset($library)){
			$enableProspectorIntegration = ($library->enablePospectorIntegration == 1);
			$showRatings = $library->showRatings;
		}
		$interface->assign('showRatings', $showRatings);

		$numProspectorTitlesToLoad = 0;
		if ($searchObject->getResultTotal() < 1) {
			
			//Var for the IDCLREADER TEMPLATE
			$interface->assign('ButtonBack',true);
			$interface->assign('ButtonHome',true);
			$interface->assign('MobileTitle','No Results Found');
			
			// No record found
			$interface->setTemplate('list-none.tpl');
			$interface->assign('recordCount', 0);

			// Was the empty result set due to an error?
			$error = $searchObject->getIndexError();
			if ($error !== false) {
				// If it's a parse error or the user specified an invalid field, we
				// should display an appropriate message:
				if (stristr($error, 'org.apache.lucene.queryParser.ParseException') ||
				preg_match('/^undefined field/', $error)) {
					$interface->assign('parseError', true);

					// Unexpected error -- let's treat this as a fatal condition.
				} else {
					PEAR::raiseError(new PEAR_Error('Unable to process query<br />' .
                        'Solr Returned: ' . $error));
				}
			}

			$numProspectorTitlesToLoad = 10;
			$timer->logTime('no hits processing');

		} else if ($searchObject->getResultTotal() == 1){
			//Redirect to the home page for the record
			$recordSet = $searchObject->getResultRecordSet();
			$record = reset($recordSet);
			if ($record['recordtype'] == 'list'){
				$listId = substr($record['id'], 4);
				header("Location: " . $interface->getUrl() . "/MyResearch/MyList/{$listId}");
				exit();
			}elseif ($record['recordtype'] == 'econtentRecord'){
				$shortId = str_replace('econtentRecord', '', $record['id']);
				header("Location: " . $interface->getUrl() . "/EcontentRecord/$shortId/Home");
				exit();
			}else{
				header("Location: " . $interface->getUrl() . "/Record/{$record['id']}/Home");
				exit();
			}
			
		} else {
			$timer->logTime('save search');

			// If the "jumpto" parameter is set, jump to the specified result index:
			$this->processJumpto($result);

			// Assign interface variables
			$summary = $searchObject->getResultSummary();
			$interface->assign('recordCount', $summary['resultTotal']);
			$interface->assign('recordStart', $summary['startRecord']);
			$interface->assign('recordEnd',   $summary['endRecord']);

			$facetSet = $searchObject->getFacetList();
			$interface->assign('facetSet',       $facetSet);

			//Check to see if a format category is already set
			$categorySelected = false;
			if (isset($facetSet['top'])){
				foreach ($facetSet['top'] as $title=>$cluster){
					if ($cluster['label'] == 'Category'){
						foreach ($cluster['list'] as $thisFacet){
							if ($thisFacet['isApplied']){
								$categorySelected = true;
							}
						}
					}
				}
			}
			$interface->assign('categorySelected', $categorySelected);
			$timer->logTime('load selected category');

			// Big one - our results
			$recordSet = $searchObject->getResultRecordHTML();
			$interface->assign('recordSet', $recordSet);
			$timer->logTime('load result records');

			// Setup Display
			$interface->assign('sitepath', $configArray['Site']['path']);
			$interface->assign('subpage', 'Search/list-list.tpl');
			$interface->setTemplate('list.tpl');
			
			//Var for the IDCLREADER TEMPLATE
			$interface->assign('ButtonBack',true);
			$interface->assign('ButtonHome',true);
			$interface->assign('MobileTitle','Search Results');
			

			// Process Paging
			$link = $searchObject->renderLinkPageTemplate();
			$options = array('totalItems' => $summary['resultTotal'],
                             'fileName'   => $link,
                             'perPage'    => $summary['perPage']);
			$pager = new VuFindPager($options);
			$interface->assign('pageLinks', $pager->getLinks());
			if ($pager->isLastPage()){
				$numProspectorTitlesToLoad = $summary['perPage'] - $pager->getNumRecordsOnPage();
				if ($numProspectorTitlesToLoad < 5){
					$numProspectorTitlesToLoad = 5;
				}
			}
			$timer->logTime('finish hits processing');
		}

		if ($numProspectorTitlesToLoad > 0 && $enableProspectorIntegration){
			$interface->assign('prospectorNumTitlesToLoad', $numProspectorTitlesToLoad);
			$interface->assign('prospectorSavedSearchId', $searchObject->getSearchId());
		}else{
			$interface->assign('prospectorNumTitlesToLoad', 0);
		}
		
		//Determine whether or not materials request functionality should be enabled
		$interface->assign('enableMaterialsRequest', MaterialsRequest::enableMaterialsRequest());

		if ($configArray['Statistics']['enabled'] && isset( $_GET['lookfor'])) {
			require_once('Drivers/marmot_inc/SearchStat.php');
			$searchStat = new SearchStat();
			$searchStat->saveSearch( strip_tags($_GET['lookfor']),  strip_tags(isset($_GET['type']) ? $_GET['type'] : (isset($_GET['basicType']) ? $_GET['basicType'] : 'Keyword')), $searchObject->getResultTotal());
		}

		// Save the ID of this search to the session so we can return to it easily:
		$_SESSION['lastSearchId'] = $searchObject->getSearchId();

		// Save the URL of this search to the session so we can return to it easily:
		$_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();
		
		// Done, display the page
		$interface->display('layout.tpl');
	} // End launch()

	/**
	 * Process the "jumpto" parameter.
	 *
	 * @access  private
	 * @param   array       $result         Solr result returned by SearchObject
	 */
	private function processJumpto($result)
	{
		if (isset($_REQUEST['jumpto']) && is_numeric($_REQUEST['jumpto'])) {
			$i = intval($_REQUEST['jumpto'] - 1);
			if (isset($result['response']['docs'][$i])) {
				$record = RecordDriverFactory::initRecordDriver($result['response']['docs'][$i]);
				$jumpUrl = '../Record/' . urlencode($record->getUniqueID());
				header('Location: ' . $jumpUrl);
				die();
			}
		}
	}
}