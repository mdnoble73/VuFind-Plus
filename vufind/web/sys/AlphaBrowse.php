<?php
/**
 * Processes Alphabetic Browse requests to return results based on
 *
 *
 * @author Mark Noble
 *
 */
class AlphaBrowse{
	public function getBrowseResults($browseType, $lookFor, $relativePage = 0, $resultsPerPage = 20){
		global $configArray;
		global $timer;

		//Normalize the search term
		$lookFor = strtolower($lookFor);

		$results = array();
		//Query to find the first record that matches the
		if (!in_array($browseType, array('title', 'author', 'callnumber', 'subject'))){
			return array(
				'success' => false,
				'message' => "The browse type $browseType is not a valid browse type."
			);
		}
		$browseTable = $browseType . '_browse';
		$browseField = 'sortValue';
		mysql_select_db($configArray['Database']['database_vufind_dbname']);

		global $librarySingleton;
		global $locationSingleton;
		$searchLibrary = Library::getSearchLibrary();

		$libaryToBrowse = -1;
		$scope = 0;
		if (isset($searchLibrary) && $searchLibrary->defaultLibraryFacet){
			$libaryToBrowse = $searchLibrary->libraryId;
			$scope = 1;
		}

		//Get the count of the rows in the database

		if ($scope == 0){
			$scopingFilter = "scope = $scope and scopeId= -1";
		}elseif ($scope = 1){
			$scopingFilter = "scope = 1 and scopeId=$libaryToBrowse";
		}
		//$query = "SELECT count({$browseTable}.id) as numRows FROM {$browseTable} inner join {$browseTable}_scoped_results on {$browseTable}.id = browseValueId WHERE $scopingFilter";
		$query = "SELECT * FROM {$browseTable}_metadata WHERE $scopingFilter";
		$result = mysql_query($query);
		if ($result == FALSE){
			return array(
				'success' => false,
				'message' => "Sorry, unable to browse $browseType right now, please try again later."
			);
		}
		$timer->logTime("Loaded metadata");
		$metaData = mysql_fetch_assoc($result);
		//echo("NumRows = {$metaData['numResults']}");

		//Cleanup our look for value
		$lookFor = strtolower($lookFor);
		$lookFor = preg_replace('/\W/', ' ', $lookFor);
		$lookFor = preg_replace("/^(a|an|the|el|la)\\s/", '', $lookFor);
		$lookFor = preg_replace('/\s{2,}/', ' ', $lookFor);
		return $this->loadBrowseItems($lookFor, $browseType, $browseTable, $scopingFilter, $relativePage, $resultsPerPage, $metaData);

	}

	function loadBrowseItems($lookFor, $browseType, $browseTable, $scopingFilter, $relativePage, $resultsPerPage, $metaData){
		//Now that we have the id to start with, get the actual records
		global $timer;

		$termRank = null;
		$term = $lookFor;
		$termRankQuery = "SELECT MIN(alphaRank) as termRank FROM {$browseTable} WHERE sortValue >= '$term'";
		//echo($termRankQuery . "<br />");
		$termRankResult = mysql_query($termRankQuery);
		if ($termRankResult){
			$termRankInfo = mysql_fetch_assoc($termRankResult);
			$termRank = $termRankInfo['termRank'];
		}
		$term = substr($term, 0, strlen($term) -1);

		if ($termRank == null){
			$termRank = 0;
		}
		$timer->logTime("Loaded position of alpha browse search term");

		if ($relativePage >= 0){
			//$query = "SELECT {$browseTable}.*, count({$browseTable}_scoped_results.record) as numResults, GROUP_CONCAT({$browseTable}_scoped_results.record) as relatedRecords FROM {$browseTable} inner join {$browseTable}_scoped_results on {$browseTable}.id = browseValueId WHERE $scopingFilter and {$browseTable}.alphaRank >= $termRank GROUP BY id ORDER BY alphaRank LIMIT " . ($relativePage * $resultsPerPage) . ", $resultsPerPage";
			$query = "SELECT DISTINCT {$browseTable}.* FROM {$browseTable} inner join {$browseTable}_scoped_results on {$browseTable}.id = browseValueId WHERE $scopingFilter and {$browseTable}.alphaRank >= $termRank and {$browseTable}.alphaRank < " . ($termRank + ($relativePage + 1) * $resultsPerPage * 50) . " ORDER BY alphaRank LIMIT " . ($relativePage * $resultsPerPage) . ", $resultsPerPage";
		}else{
			//$query = "SELECT {$browseTable}.*, count({$browseTable}_scoped_results.record) as numResults, GROUP_CONCAT({$browseTable}_scoped_results.record) as relatedRecords FROM {$browseTable} inner join {$browseTable}_scoped_results on {$browseTable}.id = browseValueId WHERE $scopingFilter and {$browseTable}.alphaRank < $termRank GROUP BY id ORDER BY alphaRank DESC LIMIT " . (-$relativePage * $resultsPerPage) . ", $resultsPerPage";
			$query = "SELECT DISTINCT {$browseTable}.* FROM {$browseTable} inner join {$browseTable}_scoped_results on {$browseTable}.id = browseValueId WHERE $scopingFilter and {$browseTable}.alphaRank < $termRank  and {$browseTable}.alphaRank > " . ($termRank - -$relativePage * $resultsPerPage * 50) . " ORDER BY alphaRank DESC LIMIT " . (-$relativePage * $resultsPerPage) . ", $resultsPerPage";
		}
		//echo $query . "<br />";
		$result = mysql_query($query);
		$timer->logTime("Queried for alpha browse results");
		$browseResults = array();
		$row = 0;
		while ($browseResult = mysql_fetch_assoc($result)){
			$rowDetailsQuery = "SELECT record from {$browseTable}_scoped_results where browseValueId = {$browseResult['id']} and $scopingFilter";
			$rowDetailsResult = mysql_query($rowDetailsQuery);
			$numResults = mysql_num_rows($rowDetailsResult);
			$browseResult['numResults'] = $numResults;
			$searchLink = '';
			if ($numResults > 0 && $numResults <= 20){
				$recordsToFind = '';
				while ($curRecord = mysql_fetch_assoc($rowDetailsResult)){
					if (strlen($recordsToFind) > 0){
						$recordsToFind .= " OR ";
					}
					$recordsToFind .= "id:" . $curRecord['record'];
				}
				$searchLink = "/Search/Results?basicType=Keyword&amp;lookfor=" . urlencode($recordsToFind);

			}else{
				if ($browseResult['numResults'] > 0){
					if ($browseType=="author"){
						$searchLink = "/Author/Home?sort=title&amp;author=" . urlencode($browseResult['value']);
					}else if ($browseType=="callnumber"){
						$searchLink = "/Search/Results?basicType=AllFields&amp;lookfor=&quot;" . urlencode($browseResult['value']) . "&quot;";
					}else{
						$searchLink = "/Search/Results?basicType=" . ucfirst($browseType) . "&amp;lookfor=&quot;" . urlencode($browseResult['value']) . "&quot;";
					}
				}
			}
			$browseResult['searchLink'] = $searchLink;
			if ($relativePage < 0){
				$browseResults[--$row] = $browseResult;
			}else{
				$browseResults[$row++] = $browseResult;
			}
		}
		if ($relativePage < 0){
			ksort($browseResults);
		}
		$timer->logTime("Processed alpha browse results");
		return array(
			'success' => true,
			'items' => $browseResults,
			'totalCount' => $metaData['numResults'],
			'showNext' => $termRank <= $metaData['maxAlphaRank'],
			'showPrev' => $termRank > $metaData['minAlphaRank'],
		);
	}
}