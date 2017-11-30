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

class Union_AJAX extends Action {

	function launch()
	{
		global $analytics;
		$analytics->disableTracking();
		$method = $_REQUEST['method'];
		header ('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if (is_callable(array($this, $method))) {
			try{
				$result = $this->$method();
				require_once ROOT_DIR . '/sys/Utils/ArrayUtils.php';
				$utf8EncodedValue = ArrayUtils::utf8EncodeArray($result);
				$output = json_encode($utf8EncodedValue);
				$error = json_last_error();
				if ($error != JSON_ERROR_NONE || $output === FALSE){
					if (function_exists('json_last_error_msg')){
						$output = json_encode(array('error'=>'error_encoding_data', 'message' => json_last_error_msg()));
					}else{
						$output = json_encode(array('error'=>'error_encoding_data', 'message' => json_last_error()));
					}
					global $configArray;
					if ($configArray['System']['debug']){
						print_r($utf8EncodedValue);
					}
				}
			}catch (Exception $e){
				$output = json_encode(array('error'=>'error_encoding_data', 'message' => $e));
				global $logger;
				$logger->log("Error encoding json data $e", PEAR_LOG_ERR);
			}

		} else {
			$output = json_encode(array('error'=>'invalid_method'));
		}
		echo $output;
	}

	function getCombinedResults()
	{
		$source = $_REQUEST['source'];
		$numberOfResults = $_REQUEST['numberOfResults'];
		$sectionId = $_REQUEST['id'];
		list($className, $id) = explode(':', $sectionId);
		$sectionObject = null;
		if ($className == 'LibraryCombinedResultSection'){
			$sectionObject = new LibraryCombinedResultSection();
			$sectionObject->id = $id;
			$sectionObject->find(true);
		}elseif ($className == 'LocationCombinedResultSection'){
			$sectionObject = new LocationCombinedResultSection();
			$sectionObject->id = $id;
			$sectionObject->find(true);
		}else{
			return array(
					'success' => false,
					'error' => 'Invalid section id pased in'
			);
		}
		$searchTerm = $_REQUEST['searchTerm'];
		$searchType = $_REQUEST['searchType'];
		$showCovers = $_REQUEST['showCovers'];
		$this->setShowCovers();

		if ($source == 'eds') {
			$results = $this->getResultsFromEDS($searchTerm, $numberOfResults);
		}elseif ($source == 'pika') {
			$results = $this->getResultsFromPika($searchTerm, $numberOfResults, $searchType);
		}elseif ($source == 'archive'){
			$results = $this->getResultsFromArchive($numberOfResults, $searchType, $searchTerm);

		}elseif ($source == 'dpla'){
			$results = $this->getResultsFromDPLA($searchTerm, $numberOfResults);
		}elseif ($source == 'prospector'){
			$results = $this->getResultsFromProspector($searchType, $searchTerm, $numberOfResults);
		}else{
			$results = "<div>Showing $numberOfResults for $source.  Show covers? $showCovers</div>";
		}
		$results .= "<div><a href='" . $sectionObject->getResultsLink($searchTerm, $searchType) . "'>Full Results</a></div>";


		return array(
				'success' => true,
				'results' => $results
		);
	}

	/**
	 * @param $searchTerm
	 * @param $numberOfResults
	 * @param $searchType
	 * @return string
	 */
	private function getResultsFromPika($searchTerm, $numberOfResults, $searchType)
	{
		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init('local', $searchTerm);
		$searchObject->setLimit($numberOfResults);
		$searchObject->setSearchTerms(array(
				'index' => $searchType,
				'lookfor' => $searchTerm
		));
		$result = $searchObject->processSearch(true, false);
		$summary = $searchObject->getResultSummary();
		$records = $searchObject->getResultRecordHTML();
		if ($summary['resultTotal'] < $numberOfResults) {
			$results = "<div>Showing {$summary['resultTotal']} records</div>";
		} else {
			$results = "<div>Showing $numberOfResults of {$summary['resultTotal']} results</div>";
		}

		foreach ($records as $record) {
			$results .= $record;
		}
		return $results;
	}

	/**
	 * @param $searchTerm
	 * @param $numberOfResults
	 * @return string
	 */
	private function getResultsFromEDS($searchTerm, $numberOfResults)
	{
		require_once ROOT_DIR . '/sys/Ebsco/EDS_API.php';
		$edsApi = EDS_API::getInstance();
		$searchResults = $edsApi->getSearchResults($searchTerm);
		$summary = $edsApi->getResultSummary();
		$records = $edsApi->getResultRecordHTML();
		if ($summary['resultTotal'] < $numberOfResults) {
			$results = "<div>Showing {$summary['resultTotal']} records</div>";
		} else {
			$results = "<div>Showing $numberOfResults of {$summary['resultTotal']} results</div>";
		}

		$numAdded = 0;
		foreach ($records as $record) {
			$numAdded++;
			$results .= $record;
			if ($numAdded >= $numberOfResults) {
				break;
			}
		}
		return $results;
	}

	/**
	 * @param $numberOfResults
	 * @param $searchType
	 * @param $searchTerm
	 * @return string
	 */
	private function getResultsFromArchive($numberOfResults, $searchType, $searchTerm)
	{
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
		$searchObject->setLimit($numberOfResults);
		if ($searchType == 'Title') {
			$searchType = 'IslandoraTitle';
		} elseif ($searchType == 'Subject') {
			$searchType = 'IslandoraSubject';
		} else {
			$searchType = 'IslandoraKeyword';
		}
		$searchObject->setSearchTerms(array(
				'index' => $searchType,
				'lookfor' => $searchTerm
		));
		$result = $searchObject->processSearch(true, false);
		$summary = $searchObject->getResultSummary();
		$records = $searchObject->getResultRecordHTML();
		if ($summary['resultTotal'] < $numberOfResults) {
			$results = "<div>Showing {$summary['resultTotal']} records</div>";
		} else {
			$results = "<div>Showing $numberOfResults of {$summary['resultTotal']} results</div>";
		}

		foreach ($records as $record) {
			$results .= $record;
		}
		return $results;
	}

	/**
	 * @param $searchTerm
	 * @param $numberOfResults
	 * @return string
	 */
	private function getResultsFromDPLA($searchTerm, $numberOfResults)
	{
		require_once ROOT_DIR . '/sys/SearchObject/DPLA.php';
		$dpla = new DPLA();
		$dplaResults = $dpla->getDPLAResults($searchTerm, $numberOfResults);
		$results = $dpla->formatResults($dplaResults, false);
		return $results;
	}

	/**
	 * @param $searchType
	 * @param $searchTerm
	 * @param $numberOfResults
	 * @return string
	 */
	private function getResultsFromProspector($searchType, $searchTerm, $numberOfResults)
	{
		require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
		$prospector = new Prospector();
		$searchTerms = array(array(
				'index' => $searchType,
				'lookfor' => $searchTerm
		));
		$records = $prospector->getTopSearchResults($searchTerms, $numberOfResults);
		global $interface;
		$interface->assign('prospectorResults', $records);
		$results = $interface->fetch('GroupedWork/ajax-prospector.tpl');
		return $results;
	}
}
