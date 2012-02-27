<?php
/**
 *
 * Copyright (C) Villanova University 2010.
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
require_once 'services/Admin/Admin.php';
require_once 'sys/eContent/EContentItem.php';
require_once 'sys/eContent/EContentRecord.php';
require_once 'sys/eContent/EContentHistoryEntry.php';
require_once 'sys/Pager.php';
require_once("PHPExcel.php");

class EContentUsage extends Admin
{
	function launch()
	{
		global $interface;

		$interface->setTemplate('econtentUsage.tpl');
		$interface->setPageTitle('eContent Usage Summary');

		$today = time();
		//Grab the Selected Date Start
		if (isset($_REQUEST['dateFilterStart'])){
			$selectedDateStart = $_REQUEST['dateFilterStart'];
		} else {
			$selectedDateStart = strtotime('-30 days');
			$selectedDateStart = date('m/d/Y', $selectedDateStart);
		}
		$interface->assign('selectedDateStart', $selectedDateStart);

		//Grab the Selected End Date
		if (isset($_REQUEST['dateFilterEnd'])){
			$selectedDateEnd = $_REQUEST['dateFilterEnd'];
		} else {
			$selectedDateEnd = strtotime('now');
			$selectedDateEnd = date('m/d/Y', $selectedDateEnd);
		}
		$interface->assign('selectedDateEnd', $selectedDateEnd);

		//Source Filter
		$interface->assign('resultsSourceFilter', $this->getSourceFilter());
		$selectedSourceFilter = null;
		if (isset($_REQUEST['sourceFilter'])){
			$selectedSourceFilter = array();
			$selectedSourceFilter = $_REQUEST['sourceFilter'];
		}
		$interface->assign('selectedSourceFilter', $selectedSourceFilter);

		//Access Type Filter
		$interface->assign('resultsAccessTypeFilter', $this->getAccessTypeFilter());
		$selectedAccessTypeFilter = null;
		if (isset($_REQUEST['accessTypeFilter'])){
			$selectedAccessTypeFilter = array();
			$selectedAccessTypeFilter = $_REQUEST['accessTypeFilter'];
		}
		$interface->assign('selectedAccessTypeFilter', $selectedAccessTypeFilter);

		//Min/Max Filter
		$minFilter = "";
		$maxFilter = "";
		if (isset($_REQUEST['minPageViewsFilter']) && is_numeric ($_REQUEST['minPageViewsFilter'])){
			$minFilter = $_REQUEST['minPageViewsFilter'];
		} else {
			$minFilter = "";
		}
		$interface->assign('minFilter', $minFilter);
		if (isset($_REQUEST['maxPageViewsFilter']) && is_numeric ($_REQUEST['maxPageViewsFilter'])){
			$maxFilter = $_REQUEST['maxPageViewsFilter'];
		} else {
			$maxFilter = "";
		}
		$interface->assign('maxFilter', $maxFilter);

		$usageSummary = $this->loadUsageSummary($selectedDateStart, $selectedDateEnd, $selectedSourceFilter, $selectedAccessTypeFilter, $minFilter, $maxFilter, $interface);
		$interface->assign('usageSummary', $usageSummary);

		if (isset($_REQUEST['exportToExcel'])) {
			$this->exportUsageSummaryToExcel($usageSummary);
			die();
		}else{
			$this->getItemsPerPageList();
		}

		$interface->display('layout.tpl');
	}

	function loadUsageSummary($selectedDateStart, $selectedDateEnd, $selectedSourceFilter, $selectedAccessTypeFilter, $minFilter, $maxFilter, $interface ){
		$usageSummary = array();
		$columns = array(
			'title' => 'Title',
			'source' => 'Source',
			'readOnline' => 'Read Online',
			'download' => 'Downloaded',
			'numViews' => 'Total Usage',
			'placeHold' => 'Hold Placed',
			'checkedOut' => 'Checked Out',
			'checkedIn' => 'Returned Early',
			'numUsers' => 'Total Users'
		);
		$usageSummary['columns'] = $columns;
		$epubHistory = new EContentHistoryEntry();

		//Setup paging for use in the query
		$currentPage = 1;
		$startRecord = 1;
		$itemsPerPage = (isset($_REQUEST['itemsPerPage']) && is_numeric($_REQUEST['itemsPerPage']) ) ? $_REQUEST['itemsPerPage'] : 50;

		if (isset($_GET['page']) && is_numeric($_GET['page'])) {
			$currentPage = $_GET['page'];
			// 1st record is easy, work out the start of this page
			$startRecord = (($currentPage - 1) * $itemsPerPage) + 1;
		}
		
		$startDateSqlFormatted = date('Y-m-d', strtotime($selectedDateStart));
		$endDateSqlFormatted = date('Y-m-d', strtotime($selectedDateEnd));

		//Create the base query
		$baseQuery = "SELECT econtent_record.id, " .
				"econtent_record.title, ".
				"econtent_record.source, " . 
				"COUNT(DISTINCT userId) as numUsers, " .
				"COUNT(DISTINCT IF (action = 'Checked Out', userid, NULL)) as checkedOut, " .
				"COUNT(DISTINCT IF (action = 'Checked In', userid, NULL)) as checkedIn, " . 
				"COUNT(DISTINCT IF (action = 'Read Online', userid, NULL)) as readOnline, " .
				"COUNT(DISTINCT IF (action = 'Place Hold', userid, NULL)) as placeHold, " .
				"COUNT(DISTINCT IF (action = 'Download', userid, NULL)) as download, ".
				"COUNT(DISTINCT IF (action = 'Read Online' OR action = 'Read Online', userid, NULL)) as numViews " .
				"FROM `econtent_history` ".
				"INNER join econtent_record on econtent_record.id = econtent_history.recordId ";
		$baseQuery .= "WHERE (DATE_FORMAT(econtent_history.openDate, '%Y-%m-%d')) BETWEEN '". $startDateSqlFormatted . "' AND '". $endDateSqlFormatted . "' ";
		if (count($selectedSourceFilter) > 0) {
			$sourceEntries = "";
			foreach ($selectedSourceFilter as $curSource){
				if (strlen($sourceEntries) > 0){
					$sourceEntries .= ', ';
				}
				$sourceEntries .= "'" . mysql_escape_string($curSource) . "'";
			}
			$baseQuery .= "AND econtent_record.source IN (". $sourceEntries . ") ";
		}
		if (count($selectedAccessTypeFilter) > 0) {
			$accessTypes = join("','", $selectedAccessTypeFilter);
			$accessTypes = "";
			foreach ($selectedAccessTypeFilter as $curAccessType){
				if (strlen($accessTypes) > 0){
					$accessTypes .= ', ';
				}
				$accessTypes .= "'" . mysql_escape_string($curAccessType) . "'";
			}
			$baseQuery .= "AND econtent_record.accessType IN (". $accessTypes . ") ";
		}
		
		$baseQuery .= "GROUP BY econtent_record.id ".
				"ORDER BY title, econtent_record.id ASC ";
		
		
	
		$countQuery = "SELECT COUNT(id) as totalResults FROM (" . $baseQuery . ") baseQuery ";
		$usageQuery = "SELECT * FROM (" . $baseQuery . ") baseQuery ";
		
		//Add max / min filters as needed since they depend on the base query
		if (($minFilter != "")&&($maxFilter != "")) {
			$countQuery .= "WHERE numViews >= ". $minFilter . " AND numViews <= ". $maxFilter . " ";
			$usageQuery .= "WHERE numViews >= ". $minFilter . " AND numViews <= ". $maxFilter . " ";
		}elseif ($minFilter != ""){
			$countQuery .= "WHERE numViews >= ". $minFilter . " ";
			$usageQuery .= "WHERE numViews >= ". $minFilter . " ";
		}elseif ($maxFilter != ""){
			$countQuery .= "WHERE numViews <= ". $maxFilter . " ";
			$usageQuery .= "WHERE numViews <= ". $maxFilter . " ";
		}
		if (!isset($_REQUEST['exportToExcel'])) {
			$usageQuery .= "LIMIT ".($startRecord -1).", ".$itemsPerPage ." ";
		}

		$epubHistory->query($usageQuery);
		$usageSummary['data'] = array();
		while ($epubHistory->fetch()){
			$resourceInfo  = array();
			$resourceInfo['title'] = $epubHistory->title;
			$resourceInfo['source'] = $epubHistory->source;
			$resourceInfo['record_id'] = $epubHistory->recordId;
			$resourceInfo['checkedOut'] = $epubHistory->checkedOut;
			$resourceInfo['checkedIn'] = $epubHistory->checkedIn;
			$resourceInfo['readOnline'] = $epubHistory->readOnline;
			$resourceInfo['placeHold'] = $epubHistory->placeHold;
			$resourceInfo['download'] = $epubHistory->download;
			$resourceInfo['numViews'] = $epubHistory->numViews;
			$resourceInfo['numUsers'] = $epubHistory->numUsers;
			
			$usageSummary['data'][] = $resourceInfo;
		}
		
		//Load total number of results
		$epubHistory->query($countQuery);
		if ($epubHistory->fetch()){
			$totalResultCount = $epubHistory->totalResults;
		}else{
			$totalResultCount = 0;
		}

		//////////Paging Array
		$summary = array(
      	'page' => $currentPage,  
				'perPage' => $itemsPerPage, 
				'resultTotal' => $totalResultCount,
				'startRecord' => $startRecord,
        'endRecord'=> $startRecord + count($usageSummary['data']) - 1, 
		);

		$interface->assign('recordCount', $summary['resultTotal']);
		$interface->assign('recordStart', $summary['startRecord']);
		$interface->assign('recordEnd',   $summary['endRecord']);

		// Process Paging using VuFind Pager object
		if (strrpos($_SERVER["REQUEST_URI"], "page=")) {
			//replace the page variable with a new one
			$link = str_replace(("page=".$currentPage),"page=%d",$_SERVER["REQUEST_URI"]);
		}
		else {
			if (strrpos($_SERVER["REQUEST_URI"], "?")) {
				$link = $_SERVER["REQUEST_URI"]."&page=%d";
			}
			else {
				$link = $_SERVER["REQUEST_URI"]."?page=%d";
			}
		}
		$options = array(	'totalItems' => $summary['resultTotal'],
												'fileName'   => $link,
												'perPage'    => $summary['perPage']);
		$pager = new VuFindPager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		return $usageSummary;
	}

	function exportUsageSummaryToExcel($usageSummary){

		//PHPEXCEL
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator("DCL")
		->setLastModifiedBy("DCL")
		->setTitle("Office 2007 XLSX Document")
		->setSubject("Office 2007 XLSX Document")
		->setDescription("Office 2007 XLSX, generated using PHP.")
		->setKeywords("office 2007 openxml php")
		->setCategory("eContent Usage");

		//Set the sheet header
		$objPHPExcel->setActiveSheetIndex(0);
		$sheet = $objPHPExcel->getActiveSheet();
		$sheet->setTitle('eContent Usage Report');

		$curRow = 1;
		$curCol = 0;
		// Add some data
		foreach ($usageSummary['columns'] as $column){
			$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $column);
		}

		$curRow++;
		//Loop Through The Report Data
		foreach ($usageSummary['data'] as $usageSummaryRow) {
			$curCol = 0;
			foreach ($usageSummary['columns'] as $field => $column){
				$sheet->setCellValueByColumnAndRow($curCol++, $curRow, $usageSummaryRow[$field]);
			}
			$curRow++;
		}
		//Autosize all columns
		$columnID = 'A';
		$lastColumn = $sheet->getHighestColumn();
		do {
		   $sheet->getColumnDimension($columnID)->setAutoSize(true);
		   $columnID++;
		} while ($columnID != $lastColumn); 
		
		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="eUsageSummaryReport.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;

	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}

	function getSourceFilter(){
		//Populate the Source Filter
		$econtentRecord = new EContentRecord();
		$querySourceFilter = "SELECT DISTINCT source FROM econtent_record WHERE source IS NOT NULL AND source <> '' ".
			"ORDER BY source ASC";
		$econtentRecord->query($querySourceFilter);

		$resultsSourceFilter = array();
		$i=0;
		while ($econtentRecord->fetch()) {
			$tmp = array(
		    'SourceValue' => $econtentRecord->source
			);
			$resultsSourceFilter[$i++] = $tmp;
		}
			
		return $resultsSourceFilter;
	}

	function getAccessTypeFilter(){
		//Populate the Source Filter
		$resultsAccessTypeFilter = array(
			'free' => 'Free Usage',
			'singleUse' => 'Single Usage',
			'acs' => 'Adobe Content Server Protected',
			
		);
		return $resultsAccessTypeFilter;
	}

	public function getItemsPerPageList() {
		global $interface;
		//loop through the
		$itemsPerPageList = array();
		$itemsPerPageList["10"] = array(
					      'amount'  => 10,          
					      'selected' => isset($_REQUEST['itemsPerPage']) && $_REQUEST['itemsPerPage'] == 10 );
		$itemsPerPageList["50"] = array(
					      'amount'  => 50,          
					      'selected' => (!isset($_REQUEST['itemsPerPage']) || $_REQUEST['itemsPerPage'] == 50) );
		$itemsPerPageList["100"] = array(
					      'amount'  => 100,
      					'selected' => isset($_REQUEST['itemsPerPage']) && $_REQUEST['itemsPerPage'] == 100 );

		$interface->assign('itemsPerPageList', $itemsPerPageList);
		return $itemsPerPageList;
	}

}