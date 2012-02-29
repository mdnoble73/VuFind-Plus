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
require_once 'sys/eContent/EContentRecord.php';
require_once 'sys/Pager.php';
require_once("PHPExcel.php");

class EContentCollection extends Admin
{
	function launch()
	{
		global $interface;

		$interface->setTemplate('econtentCollection.tpl');
		$interface->setPageTitle('eContent Collection Report');
		
		$endDate = (isset($_REQUEST['endDate']) && strlen($_REQUEST['endDate']) > 0) ? DateTime::createFromFormat('m/d/Y', $_REQUEST['endDate']) : new DateTime();
		$interface->assign('endDate', $endDate->format('m/d/Y'));
		
		if (isset($_REQUEST['startDate']) && strlen($_REQUEST['startDate']) > 0){
			$startDate = DateTime::createFromFormat('m/d/Y', $_REQUEST['startDate']);
		} else{
			$startDate = new DateTime($endDate->format('m/d/Y') . " - 1 years");
		}
		
		$interface->assign('startDate', $startDate->format('m/d/Y'));
		
		//Load source filter
		$sourceFilter = array();
		$sources = $this->loadEContentSources();
		$interface->assign('sourceFilter', $sources);
		$selectedSource = isset($_REQUEST['source']) ? $_REQUEST['source'] : $sources[0];
		$interface->assign('source', $selectedSource);
		$interface->assign('showNumItems', strcasecmp($selectedSource, 'OverDrive') != 0);

		//Set the end date to the end of the day
		$endDate->setTime(24, 0, 0);
		$startDate->setTime(0, 0, 0);
		
		//Setup paging for use in the query
		$currentPage = 1;
		$startRecord = 1;
		$itemsPerPage = (isset($_REQUEST['itemsPerPage']) && is_numeric($_REQUEST['itemsPerPage']) ) ? $_REQUEST['itemsPerPage'] : 50;

		if (isset($_GET['page']) && is_numeric($_GET['page'])) {
			$currentPage = $_GET['page'];
			// 1st record is easy, work out the start of this page
			$startRecord = (($currentPage - 1) * $itemsPerPage) + 1;
		}
		if (isset($_REQUEST['exportToExcel'])) {
			$itemsPerPage = -1;
		}
		
		$collectionDetails = $this->loadCollectionDetails($selectedSource, $startDate, $endDate, $itemsPerPage, $startRecord);
		$interface->assign('collectionDetails', $collectionDetails['records']);
		
		//////////Paging Array
		$summary = array(
      	'page' => $currentPage,  
				'perPage' => $itemsPerPage, 
				'resultTotal' => $collectionDetails['resultTotal'],
				'startRecord' => $startRecord,
        'endRecord'=> $startRecord + count($collectionDetails['records']) - 1, 
		);

		$interface->assign('recordCount', $collectionDetails['resultTotal']);
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
		
		//Export to Excel
		if (isset($_REQUEST['exportToExcel'])) {
			$this->exportToExcel($selectedSource, $collectionDetails['records']);
		}else{
			$this->getItemsPerPageList();
		}
		
		$interface->display('layout.tpl');
	}

	function loadEContentSources(){
		$sources = array();
		$econtentRecord = new EContentRecord();
		$econtentRecord->query("SELECT DISTINCT source FROM econtent_record ORDER BY source");
		while ($econtentRecord->fetch()){
			$sources[] =  $econtentRecord->source;
		}
		return $sources;
	}

	function loadCollectionDetails($source, $startDate, $endDate, $itemsPerPage, $startRecord){
		$collectionDetails = array();
		$collectionDetails['records'] = array();
		$econtentRecord = new EContentRecord();
		$econtentRecord->source = $source;
		$econtentRecord->status = 'active';
		$econtentRecord->whereAdd("date_added >= {$startDate->getTimestamp()}");
		$econtentRecord->whereAdd("date_added < {$endDate->getTimestamp()}");
		$econtentRecord->orderBy('title');
		$econtentRecordCount = clone $econtentRecord;
		if ($itemsPerPage > 0){
			$econtentRecord->limit($startRecord, $itemsPerPage);
		}
		
		$econtentRecord->find();
		while ($econtentRecord->fetch()){
			$collectionDetails['records'][] = clone $econtentRecord;
		}
		
		//Get the total number of available results
		$econtentRecordCount->find();
		$numTotalRecords = $econtentRecordCount->N;
		$collectionDetails['resultTotal'] = $numTotalRecords;
		$collectionDetails['perPage'] = $itemsPerPage;
		
		return $collectionDetails;
	}

	function exportToExcel($selectedSource, $collectionDetails){
		//May ned more time to exort all records
		set_time_limit(600);
		//PHPEXCEL
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator("VuFind")
			->setLastModifiedBy("VuFind")
			->setTitle("Office 2007 XLSX Document")
			->setSubject("Office 2007 XLSX Document")
			->setDescription("Office 2007 XLSX, generated using PHP.")
			->setKeywords("office 2007 openxml php")
			->setCategory("Itemless eContent Report");

		// Add some data
		$activeSheet = $objPHPExcel->setActiveSheetIndex(0);
		$activeSheet
			->setCellValue('A1', 'eContent Collection Details')
			->setCellValue('A2', 'Source: ' . $selectedSource)
			->setCellValue('A3', 'ID')
			->setCellValue('B3', 'Title')
			->setCellValue('C3', 'Author')
			->setCellValue('D3', 'ISBN')
			->setCellValue('E3', 'Publisher')
			->setCellValue('F3', 'Source')
			->setCellValue('G3', 'Date Added');

		$isOverDrive = strcasecmp($selectedSource, 'OverDrive') == 0;
		if (!$isOverDrive){
			$activeSheet->setCellValue('H3', 'Num Items');
		}
		$a=4;
		//Loop Through The Report Data
		foreach ($collectionDetails as $record) {
				
			$activeSheet
				->setCellValue('A'.$a, $record->id)
				->setCellValue('B'.$a, $record->title)
				->setCellValue('C'.$a, $record->author . ($record->subTitle ? ": " . $record->subTitle : '' ))
				->setCellValue('D'.$a, $record->getISBN())
				->setCellValue('E'.$a, $record->publisher)
				->setCellValue('F'.$a, $record->source)
				->setCellValue('G'.$a, date('m/d/Y', $record->date_added));
			if (!$isOverDrive){
				$activeSheet->setCellValue('H'.$a, $record->getNumItems());
			}
			$a++;
		}
		$activeSheet->getColumnDimension('A')->setAutoSize(true);
		$activeSheet->getColumnDimension('B')->setAutoSize(true);
		$activeSheet->getColumnDimension('C')->setAutoSize(true);
		$activeSheet->getColumnDimension('D')->setAutoSize(true);
		$activeSheet->getColumnDimension('E')->setAutoSize(true);
		$activeSheet->getColumnDimension('F')->setAutoSize(true);
		$activeSheet->getColumnDimension('G')->setAutoSize(true);
			
		// Rename sheet
		$activeSheet->setTitle('eContent Collction');

		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename=EContentCollectionDetailsReport.xls');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
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
	
	function getAllowableRoles(){
		return array('cataloging', 'epubAdmin');
	}

}