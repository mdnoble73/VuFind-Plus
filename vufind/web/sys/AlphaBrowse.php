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
			$scopingFilter = "scope = $scope"; 
		}elseif ($scope = 1){
			$scopingFilter = "scope = 1 and scopeId=$libaryToBrowse";
		}
		$query = "SELECT count({$browseTable}.id) as numRows FROM {$browseTable} inner join {$browseTable}_scoped_results on {$browseTable}.id = browseValueId WHERE $scopingFilter";
		//$query = "SELECT COUNT(id) as numRows from $browseTable where libraryId = '$libaryToBrowse'";
		$result = mysql_query($query);
		if ($result == FALSE){
			return array(
				'success' => false,
				'message' => "Sorry, unable to browse $browseType right now, please try again later."
			);
		}
		$numRowsRS = mysql_fetch_assoc($result);
		$numRows = $numRowsRS['numRows'];
		
		$foundMatch = false;
		$exactMatch = true;
		while (!$foundMatch && strlen($lookFor) > 0){
			//If we didn't find a match, try the original value as well
			$query = "SELECT MIN({$browseTable}.id) as startRow FROM {$browseTable} inner join {$browseTable}_scoped_results on {$browseTable}.id = browseValueId WHERE value LIKE '$lookFor%' and $scopingFilter ";
			//echo ($query);
			$result = mysql_query($query);
			if ($result && mysql_num_rows ($result) > 0 ){
				$startRowRS = mysql_fetch_assoc($result);
				$startRow = $startRowRS['startRow'];
				if ($startRow == null){
					$exactMatch = false;
				}else{
					$foundMatch = true;
					return $this->loadBrowseItems($lookFor, $browseType, $browseTable, $scopingFilter, $startRow, $exactMatch, $relativePage, $resultsPerPage, $numRows);
				}
			}
			
			//We didn't get an exact match, try to find the right location in the list
			//based on fuzzy logic.  
			//To get the next sarch term, increment the new last character by one character.
			$lookFor = substr($lookFor, 0, strlen($lookFor) - 1);
			//To make sure we don't go backwards to far in the list, increment the 
			$lastChar = substr($lookFor, -1);
			$lookFor = substr($lookFor, 0, strlen($lookFor) - 1);
			if ($lastChar != 'z'){
				$lookFor .= $lastChar++;
			}
		}
		//Didn't find a match, just start at the beginning of the table
		return $this->loadBrowseItems('', $browseType, $browseTable, $scopingFilter, 0, false, $relativePage, $resultsPerPage, $numRows);
	}
	
	function loadBrowseItems($lookFor, $browseType, $browseTable, $scopingFilter, $startRow, $exactMatch, $relativePage, $resultsPerPage, $numRows){
		$selectedIndex = $startRow;
		if (!$exactMatch) {
			$startRow -= 2;
		}
		$startRow = $startRow + $relativePage * $resultsPerPage;
		if ($startRow < 0){
			$startRow = 0;
		}
		$endRow = $startRow + $resultsPerPage;
		if ($endRow > $numRows){
			$endRow = $numRows;
		}
		
		
		//Now that we have the id to start with, get the actual records
		if ($relativePage >= 0){
			$query = "SELECT * FROM {$browseTable} inner join {$browseTable}_scoped_results on {$browseTable}.id = browseValueId WHERE {$browseTable}.sortValue >= '$lookFor' and $scopingFilter ORDER BY sortValue LIMIT " . ($relativePage * $resultsPerPage) . ", $resultsPerPage";
		}else{
			$numTotalPreviousEntriesQuery = "SELECT count({$browseTable}.id) as numRows FROM {$browseTable} inner join {$browseTable}_scoped_results on {$browseTable}.id = browseValueId WHERE {$browseTable}.sortValue < '$lookFor' and $scopingFilter ORDER BY sortValue";
			$numTotalPreviousEntriesResult = mysql_query($numTotalPreviousEntriesQuery);
			$numTotalPreviousEntries = mysql_fetch_assoc($numTotalPreviousEntriesResult);
			$numPreviousRows = $numTotalPreviousEntries['numRows'];
			//echo("Previous Rows: $numPreviousRows<br/>");
			
			$query = "SELECT * FROM {$browseTable} inner join {$browseTable}_scoped_results on {$browseTable}.id = browseValueId WHERE {$browseTable}.sortValue < '$lookFor' and $scopingFilter ORDER BY sortValue LIMIT " . ($numPreviousRows + ($relativePage * $resultsPerPage)) . ", $resultsPerPage";
		}
		//echo $query;
		$result = mysql_query($query);
		$browseResults = array();
		while ($browseResult = mysql_fetch_assoc($result)){
			$searchLink = '';
			if (strlen($browseResult['relatedRecords']) > 0 ){
				if ($browseResult['numResults'] == 1){
					$recordToFind = $browseResult['relatedRecords'];
					if (substr($recordToFind, 0, 14) == 'econtentRecord'){
						$searchLink = "/EcontentRecord/" . substr($recordToFind, 14) . "/Home";
					}else{
						$searchLink = "/Record/" . $recordToFind . "/Home";
					}
				}else{
					$recordsToFind = "id:" . preg_replace("/,/", " OR id:", $browseResult['relatedRecords']);
					$searchLink = "/Search/Results?basicType=Keyword&amp;lookfor=" . urlencode($recordsToFind);
				}
			}else{
				if ($browseResult['numResults'] > 0){
					if ($browseType=="author"){
						$searchLink = "/Author/Home?author=" . urlencode($browseResult['value']);
					}else if ($browseType=="callnumber"){
						$searchLink = "/Search/Results?basicType=AllFields&amp;lookfor=&quot;" . urlencode($browseResult['value']) . "&quot;";
					}else{
						$searchLink = "/Search/Results?basicType=" . ucfirst($browseType) . "&amp;lookfor=&quot;" . urlencode($browseResult['value']) . "&quot;";
					}
				}
			}
			$browseResult['searchLink'] = $searchLink;
			$browseResults[] = $browseResult;
		}
		return array(
			'success' => true,
			'items' => $browseResults,
			'totalCount' => $numRows,
			'selectedIndex' => $selectedIndex,
			'startRow' => $startRow,
		);
	}
}