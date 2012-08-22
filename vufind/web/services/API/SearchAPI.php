<?php
/**
 *
 * Copyright (C) Douglas County Libraries 2011.
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
require_once 'sys/SolrStats.php';
require_once 'sys/Pager.php';

class SearchAPI extends Action {

	function launch()
	{
		//header('Content-type: application/json');
		header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if (is_callable(array($this, $_GET['method']))) {
			if (in_array($_GET['method'] , array('getSearchBar', 'getHomePageWidget', 'getListWidget'))){
				$output = $this->$_GET['method']();
			}else{
				$output = json_encode(array('result'=>$this->$_GET['method']()));
			}
		} else {
			$output = json_encode(array('error'=>'invalid_method'));
		}

		echo $output;
	}

	/**
	 * Do a basic search and return results as a JSON array
	 */
	function search()
	{
		global $interface;
		global $configArray;
		global $timer;

		// Include Search Engine Class
		require_once 'sys/' . $configArray['Index']['engine'] . '.php';
		$timer->logTime('Include search engine');

		//setup the results array.
		$jsonResults = array();

		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed
		$interface->setPageTitle('Search Results');
		$interface->assign('sortList',   $searchObject->getSortList());
		$interface->assign('rssLink',    $searchObject->getRSSUrl());

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if (PEAR::isError($result)) {
			PEAR::raiseError($result->getMessage());
		}
		$timer->logTime('Process Search');

		// 'Finish' the search... complete timers and log search history.
		$searchObject->close();

		if ($searchObject->getResultTotal() < 1) {
			// No record found
			$interface->setTemplate('list-none.tpl');
			$jsonResults['recordCount'] = 0;

			// Was the empty result set due to an error?
			$error = $searchObject->getIndexError();
			if ($error !== false) {
				// If it's a parse error or the user specified an invalid field, we
				// should display an appropriate message:
				if (stristr($error, 'org.apache.lucene.queryParser.ParseException') ||
				preg_match('/^undefined field/', $error)) {
					$jsonResults['parseError'] = true;

					// Unexpected error -- let's treat this as a fatal condition.
				} else {
					PEAR::raiseError(new PEAR_Error('Unable to process query<br />' .
                        'Solr Returned: ' . $error));
				}
			}

			$timer->logTime('no hits processing');

		} else {
			$timer->logTime('save search');

			// Assign interface variables
			$summary = $searchObject->getResultSummary();
			$jsonResults['recordCount'] = $summary['resultTotal'];
			$jsonResults['recordStart'] = $summary['startRecord'];
			$jsonResults['recordEnd'] =   $summary['endRecord'];

			// Big one - our results
			$recordSet = $searchObject->getResultRecordSet();
			//Remove fields as needed to improve the display.
			foreach($recordSet as $recordKey => $record){
				unset($record['auth_author']);
				unset($record['auth_authorStr']);
				unset($record['callnumber-first-code']);
				unset($record['spelling']);
				unset($record['callnumber-first']);
				unset($record['title_auth']);
				unset($record['callnumber-subject']);
				unset($record['author-letter']);
				unset($record['marc_error']);
				unset($record['title_fullStr']);
				unset($record['shortId']);
				$recordSet[$recordKey] = $record;
			}
			$jsonResults['recordSet'] = $recordSet;
			$timer->logTime('load result records');

			$facetSet = $searchObject->getFacetList();
			$jsonResults['facetSet'] =       $facetSet;

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
			$jsonResults['categorySelected'] = $categorySelected;
			$timer->logTime('load selected category');

			// Process Paging
			$link = $searchObject->renderLinkPageTemplate();
			$options = array('totalItems' => $summary['resultTotal'],
                             'fileName'   => $link,
                             'perPage'    => $summary['perPage']);
			$pager = new VuFindPager($options);
			$jsonResults['paging'] = array(
            	'currentPage' => $pager->pager->_currentPage,
            	'totalPages' => $pager->pager->_totalPages,
            	'totalItems' => $pager->pager->_totalItems,
            	'itemsPerPage' => $pager->pager->_perPage,
			);
			$interface->assign('pageLinks', $pager->getLinks());
			$timer->logTime('finish hits processing');
		}

		// Report additional information after the results
		$jsonResults['query_time'] = 		  round($searchObject->getQuerySpeed(), 2);
		$jsonResults['spellingSuggestions'] = $searchObject->getSpellingSuggestions();
		$jsonResults['lookfor'] =             $searchObject->displayQuery();
		$jsonResults['searchType'] =          $searchObject->getSearchType();
		// Will assign null for an advanced search
		$jsonResults['searchIndex'] =         $searchObject->getSearchIndex();
		$jsonResults['time'] = round($searchObject->getTotalSpeed(), 2);
		// Show the save/unsave code on screen
		// The ID won't exist until after the search has been put in the search history
		//    so this needs to occur after the close() on the searchObject
		$jsonResults['showSaved'] =   true;
		$jsonResults['savedSearch'] = $searchObject->isSavedSearch();
		$jsonResults['searchId'] =    $searchObject->getSearchId();
		$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$jsonResults['page'] = $currentPage;


		if ($configArray['Statistics']['enabled'] && isset( $_GET['lookfor'])) {
			require_once('Drivers/marmot_inc/SearchStat.php');
			$searchStat = new SearchStat();
			$searchStat->saveSearch( strip_tags($_GET['lookfor']), strip_tags($_GET['type']), $searchObject->getResultTotal());
		}

		// Save the ID of this search to the session so we can return to it easily:
		$_SESSION['lastSearchId'] = $searchObject->getSearchId();

		// Save the URL of this search to the session so we can return to it easily:
		$_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();

		// Return the results for display to the user.
		return $jsonResults;
	}

	function getSearchBar(){
		global $interface;
		return $interface->fetch('API/searchbar.tpl');
	}

	function getHomePageWidget(){
		global $interface;
		$interface->caching = 1;
		return $interface->fetch('API/homePageWidget.tpl');
	}

	function getListWidget(){
		global $interface;
		global $user;
		if (isset($_REQUEST['username']) && isset($_REQUEST['password'])){
			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];
			$user = UserAccount::validateAccount($username, $password);
			$interface->assign('user', $user);
		}
		//Load the widget configuration
		require_once 'sys/ListWidget.php';
		require_once 'sys/ListWidgetList.php';
		require_once 'sys/ListWidgetListsLinks.php';
		$widget = new ListWidget();
		$id = $_REQUEST['id'];

		$widget->id = $id;
		if ($widget->find(true)){
			$interface->assign('widget', $widget);
			//return the widget
			return $interface->fetch('API/listWidget.tpl');
		}
	}

	/**
	 * Retreive the top 20 search terms by popularity from the search_stats table
	 * Enter description here ...
	 */
	function getTopSearches(){
		require_once('Drivers/marmot_inc/SearchStat.php');
		$numSearchesToReturn = isset($_REQUEST['numResults']) ? $_REQUEST['numResults'] : 20;
		$searchStats = new SearchStat();
		$searchStats->query("SELECT phrase, sum(numSearches) as numTotalSearches FROM `search_stats` where phrase != '' group by phrase order by numTotalSearches DESC LIMIT $numSearchesToReturn");
		$searches = array();
		while ($searchStats->fetch()){
			$searches[] = $searchStats->phrase;
		}
		return $searches;
	}

	function getRecordIdForTitle(){
		$title = strip_tags($_REQUEST['title']);
		$_REQUEST['lookfor'] = $title;
		$_REQUEST['type'] = 'Keyword';

		global $interface;
		global $configArray;
		global $timer;

		// Include Search Engine Class
		require_once 'sys/' . $configArray['Index']['engine'] . '.php';
		$timer->logTime('Include search engine');

		//setup the results array.
		$jsonResults = array();

		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed
		$interface->setPageTitle('Search Results');
		$interface->assign('sortList',   $searchObject->getSortList());
		$interface->assign('rssLink',    $searchObject->getRSSUrl());

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if (PEAR::isError($result)) {
			PEAR::raiseError($result->getMessage());
		}

		if ($searchObject->getResultTotal() < 1){
			return "";
		}else{
			//Return the first result
			$recordSet = $searchObject->getResultRecordSet();
			foreach($recordSet as $recordKey => $record){
				return $record['id'];
			}
		}
	}
	
	function getTitleInfoForISBN(){
		$isbn = str_replace('-', '', strip_tags($_REQUEST['isbn']));
		$_REQUEST['lookfor'] = $isbn;
		$_REQUEST['type'] = 'ISN';

		global $interface;
		global $configArray;
		global $timer;

		// Include Search Engine Class
		require_once 'sys/' . $configArray['Index']['engine'] . '.php';
		$timer->logTime('Include search engine');

		//setup the results array.
		$jsonResults = array();

		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed
		$interface->setPageTitle('Search Results');
		$interface->assign('sortList',   $searchObject->getSortList());
		$interface->assign('rssLink',    $searchObject->getRSSUrl());

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if (PEAR::isError($result)) {
			PEAR::raiseError($result->getMessage());
		}

		if ($searchObject->getResultTotal() >= 1){
			//Return the first result
			$recordSet = $searchObject->getResultRecordSet();
			foreach($recordSet as $recordKey => $record){
				$jsonResults[] = array(
					'id' => $record['id'], 
					'title'=> $record['title'], 
					'author' => isset($record['author']) ? $record['author'] : (isset($record['author2']) ? $record['author2'] : ''),
					'format' => isset($record['format']) ? $record['format'] : '',
					'format_category' => isset($record['format_category']) ? $record['format_category'] : '',
				);
			}
		}
		return $jsonResults;
	}
}