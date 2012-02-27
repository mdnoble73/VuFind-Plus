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

require_once 'services/Record/UserComments.php';
require_once 'sys/eContent/EContentRecord.php';
require_once 'RecordDrivers/EcontentRecordDriver.php';
require_once 'sys/SolrStats.php';

class Home extends Action{
	private $db;
	private $id;
	private $isbn;
	private $issn;

	function launch(){
		global $interface;
		global $timer;
		global $configArray;
		global $user;

		//Enable and disable functionality based on library settings
		global $library;
		global $locationSingleton;

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$this->db->debug = true;
		}

		$location = $locationSingleton->getActiveLocation();
		if (isset($library)){
			$interface->assign('showTextThis', $library->showTextThis);
			$interface->assign('showEmailThis', $library->showEmailThis);
			$interface->assign('showFavorites', $library->showFavorites);
			$interface->assign('linkToAmazon', $library->linkToAmazon);
			$interface->assign('enablePospectorIntegration', $library->enablePospectorIntegration);
			if ($location != null){
				$interface->assign('showAmazonReviews', (($location->showAmazonReviews == 1) && ($library->showAmazonReviews == 1)) ? 1 : 0);
				$interface->assign('showStandardReviews', (($location->showStandardReviews == 1) && ($library->showStandardReviews == 1)) ? 1 : 0);
				$interface->assign('showHoldButton', (($location->showHoldButton == 1) && ($library->showHoldButton == 1)) ? 1 : 0);
			}else{
				$interface->assign('showAmazonReviews', $library->showAmazonReviews);
				$interface->assign('showStandardReviews', $library->showStandardReviews);
				$interface->assign('showHoldButton', $library->showHoldButton);
			}
			$interface->assign('showTagging', $library->showTagging);
			$interface->assign('showRatings', $library->showRatings);
			$interface->assign('showComments', $library->showComments);
			$interface->assign('tabbedDetails', $library->tabbedDetails);
		}else{
			$interface->assign('showTextThis', 1);
			$interface->assign('showEmailThis', 1);
			$interface->assign('showFavorites', 1);
			$interface->assign('linkToAmazon', 1);
			$interface->assign('enablePospectorIntegration', 0);
			if ($location != null){
				$interface->assign('showAmazonReviews', $location->showAmazonReviews);
				$interface->assign('showStandardReviews', $location->showStandardReviews);
				$interface->assign('showHoldButton', $location->showHoldButton);
			}else{
				$interface->assign('showAmazonReviews', 1);
				$interface->assign('showStandardReviews', 1);
				$interface->assign('showHoldButton', 1);
			}
			$interface->assign('showTagging', 1);
			$interface->assign('showRatings', 1);
			$interface->assign('showComments', 1);
			$interface->assign('tabbedDetails', 1);
		}
		$timer->logTime('Configure UI for library and location');

		UserComments::loadEContentComments();
		$timer->logTime('Loaded Comments');

		$eContentRecord = new EContentRecord();
		$this->id = strip_tags($_REQUEST['id']);
		$eContentRecord->id = $this->id;
		if (!$eContentRecord->find(true)){
			//TODO: display record not found error
		}else{
			$this->isbn = $eContentRecord->getIsbn();
			if (is_array($this->isbn)){
				if (count($this->isbn) > 0){
					$this->isbn = $this->isbn[0];
				}else{
					$this->isbn = "";
				}
			}
			$this->issn = $eContentRecord->getPropertyArray('issn');
			if (is_array($this->issn)){
				if (count($this->issn) > 0){
					$this->issn = $this->issn[0];
				}else{
					$this->issn = "";
				}
			}
			$interface->assign('additionalAuthorsList', $eContentRecord->getPropertyArray('author2'));
			$interface->assign('subjectList', $eContentRecord->getPropertyArray('subject'));
			$interface->assign('lccnList', $eContentRecord->getPropertyArray('lccn'));
			$interface->assign('isbnList', $eContentRecord->getPropertyArray('isbn'));
			$interface->assign('isbn', $eContentRecord->getIsbn());
			$interface->assign('isbn10', $eContentRecord->getIsbn10());
			$interface->assign('issnList', $eContentRecord->getPropertyArray('issn'));
			$interface->assign('upcList', $eContentRecord->getPropertyArray('upc'));
			$interface->assign('seriesList', $eContentRecord->getPropertyArray('series'));
			$interface->assign('topicList', $eContentRecord->getPropertyArray('topic'));
			$interface->assign('genreList', $eContentRecord->getPropertyArray('genre'));
			$interface->assign('regionList', $eContentRecord->getPropertyArray('region'));
			$interface->assign('eraList', $eContentRecord->getPropertyArray('era'));

			$interface->assign('eContentRecord', $eContentRecord);
			$interface->assign('id', $eContentRecord->id);

			require_once('sys/eContent/EContentRating.php');
			$eContentRating = new EContentRating();
			$eContentRating->recordId = $eContentRecord->id;
			$interface->assign('ratingData', $eContentRating->getRatingData($user, false));

			//Load purchase urls
			if (isset($eContentRecord->purchaseUrl)){
				$purchaseLinks = array();
				if (preg_match('/barnesandnoble/i', $eContentRecord->purchaseUrl)){
					$purchaseLinks[] = array(
	        		  	  'link' => $eContentRecord->purchaseUrl,
                    'linkText' => 'Buy from Barnes & Noble',
	        		  		'storeName' => 'Barnes & Noble',
										'eContent' => true,
					);
				}else if (preg_match('/tatteredcover/i', $eContentRecord->purchaseUrl)){
					$purchaseLinks[] = array(
                    'link' => $eContentRecord->purchaseUrl,
                    'linkText' => 'Buy from Tattered Cover',
	        		  		'storeName' => 'Tattered Cover',
										'eContent' => true,
					);
				}else if (preg_match('/amazon\.com/i', $eContentRecord->purchaseUrl)){
					$purchaseLinks[] = array(
                    'link' => $eContentRecord->purchaseUrl,
                    'linkText' => 'Buy from Amazon',
                  	'storeName' => 'Amazon',
										'eContent' => true,
					);
				}else if (preg_match('/smashwords\.com/i', $eContentRecord->purchaseUrl)){
					$purchaseLinks[] = array(
                    'link' => $eContentRecord->purchaseUrl,
                    'linkText' => 'Buy from Smashwords',
                  	'storeName' => 'Smashwords', 
										'eContent' => true,
					);
				}
				$interface->assign('purchaseLinks', $purchaseLinks);
			}

			//Determine the cover to use
			$bookCoverUrl = $configArray['Site']['coverUrl'] . "/bookcover.php?id={$eContentRecord->id}&amp;econtent=true&amp;isn={$eContentRecord->getIsbn()}&amp;size=large&amp;upc={$eContentRecord->getUpc()}&amp;category=" . urlencode($eContentRecord->format_category()) . "&amp;format=" . urlencode($eContentRecord->getFirstFormat());
			$interface->assign('bookCoverUrl', $bookCoverUrl);

			if (isset($_REQUEST['detail'])){
				$detail = strip_tags($_REQUEST['detail']);
				$interface->assign('defaultDetailsTab', $detail);
			}

			// Find Similar Records
			$similar = $this->db->getMoreLikeThis('econtentRecord' . $eContentRecord->id);
			$timer->logTime('Got More Like This');

			// Find Other Editions
			$editions = $this->getEditions();
			if (!PEAR::isError($editions)) {
				$interface->assign('editions', $editions);
			}
			$timer->logTime('Got Other editions');
				
			//Load the citations
			$this->loadCitation($eContentRecord);
				
			// Retrieve User Search History
			$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ?
			$_SESSION['lastSearchURL'] : false);
			$this->getNextPrevLinks();

			// Retrieve tags associated with the record
			$limit = 5;
			$resource = new Resource();
			$resource->record_id = $_GET['id'];
			$resource->source = 'eContent';
			$tags = array();
			if ($tags = $resource->getTags()) {
				array_slice($tags, 0, $limit);
			}
			$interface->assign('tagList', $tags);
			$timer->logTime('Got tag list');

			//Build the actual view
			$interface->setTemplate('view.tpl');

			$interface->setPageTitle($eContentRecord->title);

			// Display Page
			$interface->display('layout.tpl');

		}
	}


	/**
	 * Load information from the review provider and update the interface with the data.
	 *
	 * @return array       Returns array with review data, otherwise a
	 *                      PEAR_Error.
	 */
	function loadEnrichment(){
		global $interface;
		global $configArray;

		// Fetch from provider
		if (isset($configArray['Content']['enrichment'])) {
			$providers = explode(',', $configArray['Content']['enrichment']);
			foreach ($providers as $provider) {
				$provider = explode(':', trim($provider));
				$func = strtolower($provider[0]);
				$enrichment[$func] = $this->$func();

				// If the current provider had no valid reviews, store nothing:
				if (empty($enrichment[$func]) || PEAR::isError($enrichment[$func])) {
					unset($enrichment[$func]);
				}
			}
		}

		if ($enrichment) {
			$interface->assign('enrichment', $enrichment);
		}

		return $enrichment;
	}

	function loadCitation($eContentRecord)
	{
		global $interface;

		//instantiate the record driver
		$recordDriver = new EcontentRecordDriver();
		$recordDriver->setDataObject($eContentRecord);

		$citationCount = 0;
		$formats = $recordDriver->getCitationFormats();
		foreach($formats as $current) {
			$interface->assign(strtolower($current), $recordDriver->getCitation($current));
			$citationCount++;
		}
		$interface->assign('citationCount', $citationCount);
	}

	function getEditions(){
		if ($this->isbn) {
			return $this->getXISBN($this->isbn);
		} else if (isset($this->record['issn'])) {
			return $this->getXISSN($this->record['issn']);
		}

		return null;
	}

	private function getXISBN($isbn){
		global $configArray;

		// Build URL
		$url = 'http://xisbn.worldcat.org/webservices/xid/isbn/' . urlencode(is_array($isbn) ? $isbn[0] : $isbn) .
               '?method=getEditions&format=csv';
		if (isset($configArray['WorldCat']['id'])) {
			$url .= '&ai=' . $configArray['WorldCat']['id'];
		}

		// Print Debug code
		/*if ($configArray['System']['debug']) {
		echo "<pre>XISBN: $url</pre>";
		}*/

		// Fetch results
		if ($fp = @fopen($url, "r")) {
			$query = '';
			while (($data = fgetcsv($fp, 1000, ",")) !== FALSE) {
				// If we got an error message, don't treat it as an ISBN!
				if ($data[0] == 'overlimit') {
					continue;
				}
				if ($query != '') {
					$query .= ' OR isbn:' . $data[0];
				} else {
					$query = 'isbn:' . $data[0];
				}
			}
		}

		if (isset($query) && ($query != '')) {
			// Filter out current record
			$query .= ' NOT id:' . $this->id;

			$result = $this->db->search($query, null, null, 0, 10);
			if (!PEAR::isError($result)) {
				if (isset($result['response']['docs']) && !empty($result['response']['docs'])) {
					return $result['response']['docs'];
				} else {
					return null;
				}
			} else {
				return $result;
			}
		} else {
			return null;
		}
	}

	private function getXISSN($issn){
		global $configArray;

		// Build URL
		$url = 'http://xissn.worldcat.org/webservices/xid/issn/' . urlencode(is_array($issn) ? $issn[0] : $issn) .
		//'?method=getEditions&format=csv';
               '?method=getEditions&format=xml';
		if (isset($configArray['WorldCat']['id'])) {
			$url .= '&ai=' . $configArray['WorldCat']['id'];
		}

		// Print Debug code
		if ($configArray['System']['debug']) {
			echo "<pre>XISSN: $url</pre>";
		}

		// Fetch results
		$query = '';
		$data = @file_get_contents($url);
		if (empty($data)) {
			return null;
		}
		$unxml = new XML_Unserializer();
		$unxml->unserialize($data);
		$data = $unxml->getUnserializedData($data);
		if (!empty($data) && isset($data['group']['issn'])) {
			if (is_array($data['group']['issn'])) {
				foreach ($data['group']['issn'] as $issn) {
					if ($query != '') {
						$query .= ' OR issn:' . $issn;
					} else {
						$query = 'issn:' . $issn;
					}
				}
			} else {
				$query = 'issn:' . $data['group']['issn'];
			}
		}

		if ($query) {
			// Filter out current record
			$query .= ' NOT id:' . $this->id;

			$result = $this->db->search($query, null, null, 0, 10);
			if (!PEAR::isError($result)) {
				if (isset($result['response']['docs']) && !empty($result['response']['docs'])) {
					return $result['response']['docs'];
				} else {
					return null;
				}
			} else {
				return $result;
			}
		} else {
			return null;
		}
	}

	function getNextPrevLinks(){
		global $interface;
		global $timer;
		//Setup next and previous links based on the search results.
		if (isset($_REQUEST['searchId'])){
			//rerun the search
			$s = new SearchEntry();
			$s->id = $_REQUEST['searchId'];
			$interface->assign('searchId', $_REQUEST['searchId']);
			$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			$interface->assign('page', $currentPage);

			$s->find();
			if ($s->N > 0){
				$s->fetch();
				$minSO = unserialize($s->search_object);
				$searchObject = SearchObjectFactory::deminify($minSO);
				$searchObject->setPage($currentPage);
				//Run the search
				$result = $searchObject->processSearch(true, false, false);

				//Check to see if we need to run a search for the next or previous page
				$currentResultIndex = $_REQUEST['recordIndex'] - 1;
				$recordsPerPage = $searchObject->getLimit();

				if (($currentResultIndex) % $recordsPerPage == 0 && $currentResultIndex > 0){
					//Need to run a search for the previous page
					$interface->assign('previousPage', $currentPage - 1);
					$previousSearchObject = clone $searchObject;
					$previousSearchObject->setPage($currentPage - 1);
					$previousSearchObject->processSearch(true, false, false);
					$previousResults = $previousSearchObject->getResultRecordSet();
				}else if (($currentResultIndex + 1) % $recordsPerPage == 0 && ($currentResultIndex + 1) < $searchObject->getResultTotal()){
					//Need to run a search for the next page
					$nextSearchObject = clone $searchObject;
					$interface->assign('nextPage', $currentPage + 1);
					$nextSearchObject->setPage($currentPage + 1);
					$nextSearchObject->processSearch(true, false, false);
					$nextResults = $nextSearchObject->getResultRecordSet();
				}

				if (PEAR::isError($result)) {
					//If we get an error excuting the search, just eat it for now.
				}else{
					if ($searchObject->getResultTotal() < 1) {
						//No results found
					}else{
						$recordSet = $searchObject->getResultRecordSet();
						//Record set is 0 based, but we are passed a 1 based index
						if ($currentResultIndex > 0){
							if (isset($previousResults)){
								$previousRecord = $previousResults[count($previousResults) -1];
							}else{
								$previousRecord = $recordSet[$currentResultIndex - 1 - (($currentPage -1) * $recordsPerPage)];
							}
							
							//Convert back to 1 based index
							$interface->assign('previousIndex', $currentResultIndex - 1 + 1);
							$interface->assign('previousTitle', $previousRecord['title']);
							if (strpos($previousRecord['id'], 'econtentRecord') === 0){
								$interface->assign('previousType', 'EContentRecord');
								$interface->assign('previousId', str_replace('econtentRecord', '', $previousRecord['id']));
							}else{
								$interface->assign('previousType', 'Record');
								$interface->assign('previousId', $previousRecord['id']);
							}
						}
						if ($currentResultIndex + 1 < $searchObject->getResultTotal()){

							if (isset($nextResults)){
								$nextRecord = $nextResults[0];
							}else{
								$nextRecord = $recordSet[$currentResultIndex + 1 - (($currentPage -1) * $recordsPerPage)];
							}
							//Convert back to 1 based index
							$interface->assign('nextIndex', $currentResultIndex + 1 + 1);
							$interface->assign('nextTitle', $nextRecord['title']);
							if (strpos($nextRecord['id'], 'econtentRecord') === 0){
								$interface->assign('nextType', 'EContentRecord');
								$interface->assign('nextId', str_replace('econtentRecord', '', $nextRecord['id']));
							}else{
								$interface->assign('nextType', 'Record');
								$interface->assign('nextId', $nextRecord['id']);
							}
						}

					}
				}
			}
			$timer->logTime('Got next/previous links');
		}
	}
}