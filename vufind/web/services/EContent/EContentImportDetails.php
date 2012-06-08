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
require_once 'sys/eContent/EContentImportDetailsEntry.php';
require_once 'sys/Pager.php';
require_once 'Structures/DataGrid.php';
class EContentImportDetails extends Admin
{
	function launch()
	{
		global $configArray;
		global $interface;
		global $user;
		
		// Publisher Filter
		$allPublishers = $this->getAllPublishers();
		$interface->assign('publisherFilter', $allPublishers);
		$selectedPublisherFilter = null;
		if (isset($_REQUEST['publisherFilter'])){
			$selectedPublisherFilter = $_REQUEST['publisherFilter'];
		}else{
			$selectedPublisherFilter = array();
		}
		$interface->assign('selectedPublisherFilter', $selectedPublisherFilter);
		$publishers = empty($selectedPublisherFilter) ? $allPublishers : $selectedPublisherFilter;
		$interface->assign('publishers', $publishers);
		
		// Status Filter
		$allStatuses = $this->getStatuses();
		$interface->assign('statusFilter', $allStatuses);
		$selectedStatusFilter = null;
		if (isset($_REQUEST['statusFilter'])){
			$selectedStatusFilter = $_REQUEST['statusFilter'];
		}else{
			$selectedStatusFilter = array();
		}
		$interface->assign('selectedStatusFilter', $selectedStatusFilter);
		$statuses = empty($selectedStatusFilter) ? $allStatuses : $selectedStatusFilter;
		$interface->assign('statuses', $statuses);
		
		// Date range filter (default to 1 hour ago)
		$startDate = new DateTime();
		$startDate->modify("-1 hour");
		if (isset($_REQUEST['startDate']) && strlen($_REQUEST['startDate']) > 0) { 
			$startDate = DateTime::createFromFormat('m/d/Y', $_REQUEST['startDate']);
			$startDate->setTime(0, 0, 0);
		}
		$interface->assign('startDate', $startDate->format('m/d/Y'));
		
		$endDate = (isset($_REQUEST['endDate']) && strlen($_REQUEST['endDate']) > 0) ? DateTime::createFromFormat('m/d/Y', $_REQUEST['endDate']) : new DateTime();
		$interface->assign('endDate', $endDate->format('m/d/Y'));
		
		//Set the end date to the end of the day
		$endDate->setTime(24, 0, 0);
		
		// create a SQL clause to filter by selected publishers
		$publisherRestriction = null;
		if (isset($_REQUEST['publisherFilter'])){
			$publishersToShow = array();
			foreach ($_REQUEST['publisherFilter'] as $item){
				$publishersToShow[] = "'" . mysql_escape_string(strip_tags($item)) . "'";
			}
			if (!empty($publishersToShow)) {
				$publisherRestriction = "publisher IN (" . implode(",", $publishersToShow) . ") ";
			}
		}
		
		// create a SQL clause to filter by selected statuses
		$statusRestriction = null;
		if (isset($_REQUEST['statusFilter'])){
			$statusesToShow = array();
			foreach ($_REQUEST['statusFilter'] as $item){
				$statusesToShow[] = "'" . mysql_escape_string(strip_tags($item)) . "'";
			}
			if (!empty($statusesToShow)) {
				$statusRestriction = "status IN (" . implode(",", $statusesToShow) . ") ";
			}
		}
		
		// Packaging ID filter
		$packagingIdsToShow = array();
		$packagingIdsRestriction = null;
		if (isset($_REQUEST['packagingIds'])){
			$packagingIds = explode(',', $_REQUEST['packagingIds']);
			foreach ($packagingIds as $id) {
				if (is_numeric($id)) {
					$packagingIdsToShow[] = mysql_escape_string(strip_tags($id));
				}
			}
			if (!empty($packagingIdsToShow)) {
				$packagingIdsRestriction = "packagingId IN (" . implode(",", $packagingIdsToShow) . ") ";
			}
			$interface->assign('packagingIds', implode(",", $packagingIdsToShow));
		}
		
		$importDetails = new EContentImportDetailsEntry();
		$importDetails->whereAdd('dateFound >= ' . $startDate->getTimestamp() . ' AND dateFound < ' . $endDate->getTimestamp());
		if ($publisherRestriction) {
			$importDetails->whereAdd($publisherRestriction);
		}
		if ($statusRestriction) {
			$importDetails->whereAdd($statusRestriction);
		}
		if ($packagingIdsRestriction) {
			$importDetails->whereAdd($packagingIdsRestriction);
		}
		
		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])){
			$importDetails->find();
			$records = array();
			while ($importDetails->fetch()) {
				$records[] = clone $importDetails;
			}
			$this->exportToExcel($records);
		}

		// Number of row per page
		$perPage = 20;
		
		$datagrid =& new Structures_DataGrid($perPage);
		$datagrid->setDefaultSort(array('filename' => 'ASC'));
		$datagrid->bind($importDetails);
		$datagrid->addColumn(new Structures_DataGrid_Column('File Name', 'filename', 'filename',  null, null, array($this, 'printFileNameAsLinkToDetails')));
		$datagrid->addColumn(new Structures_DataGrid_Column('Publisher', 'publisher', 'publisher'));
		$datagrid->addColumn(new Structures_DataGrid_Column('Date Found', 'dateFound', 'dateFound', null, null, array($this, 'printDateFound')));
		$datagrid->addColumn(new Structures_DataGrid_Column('Packaging ID', 'packagingId', 'packagingId'));
		$datagrid->addColumn(new Structures_DataGrid_Column('Status', 'status', 'status'));
		$datagrid->addColumn(new Structures_DataGrid_Column('Details', 'details', 'details',  null, null, array($this, 'printLinkToDetails')));
		$interface->assign('importDetailsTable', $datagrid->getOutput());
		
		// create pager
		$params = array();
		if (isset($_REQUEST['startDate']) && strlen($_REQUEST['startDate']) > 0) {
			$params['startDate'] = $startDate->format('m/d/Y');
		}
		if (isset($_REQUEST['endDate']) && strlen($_REQUEST['endDate']) > 0) {
			$params['endDate'] = $endDate->format('m/d/Y');	
		}
		if (!empty($selectedPublisherFilter)) {
			$params['publisherFilter'] = $selectedPublisherFilter;
		}
		if (!empty($selectedStatusFilter)) {
			$params['statusFilter'] = $selectedStatusFilter;
		}
		if (!empty($packagingIdsToShow)) {
			$params['packagingIds'] = implode(',', $packagingIdsToShow);
		}
		$options = array('totalItems' => $datagrid->getRecordCount(),
			'fileName' => $configArray['Site']['path'] . '/EContent/EContentImportDetails?' . http_build_query($params) . '&page=%d',
			'perPage' => $perPage
		);
		$pager = new VuFindPager($options);
		$interface->assign('pageLinks', $pager->getLinks());
		$interface->setTemplate('eContentImportDetails.tpl');
		$interface->setPageTitle('eContent Import Details');
		$interface->display('layout.tpl');
	}

	function printDateFound($params, $args = array()) {
		extract($params);
		return date('m/d/Y', $record['dateFound']);
	}
	
	function printFileNameAsLinkToDetails($params, $args = array()) {
		extract($params);
		return '<a href="#" onclick="popupDetails(' . $record['id'] . ');return false;">'
		. $record['filename'] . '</a>';
	}
	
	function printLinkToDetails($params, $args = array()) {
		extract($params);
		return '<a href="#" onclick="popupDetails(' . $record['id'] . ');return false;">Details</a>';
	}
	
	function getAllPublishers() {
		$importDetails = new EContentImportDetailsEntry();
		$importDetails->query('SELECT DISTINCT publisher FROM ' . $importDetails->__table . ' ORDER BY publisher');
		$publishers = array();
		while ($importDetails->fetch()){
			$publishers[] = $importDetails->publisher;
		}
		return $publishers;
	}
	
	function getStatuses() {
		$importDetails = new EContentImportDetailsEntry();
		$importDetails->query('SELECT DISTINCT status FROM ' . $importDetails->__table . ' ORDER BY status');
		$statuses = array();
		while ($importDetails->fetch()){
			$statuses[] = $importDetails->status;
		}
		return $statuses;
	}
	
	function exportToExcel($records){
		global $configArray;
		//PHPEXCEL
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator($configArray['Site']['title'])
			->setLastModifiedBy($configArray['Site']['title'])
			->setTitle("Office 2007 XLSX Document")
			->setSubject("Office 2007 XLSX Document")
			->setDescription("Office 2007 XLSX, generated using PHP.")
			->setKeywords("office 2007 openxml php")
			->setCategory("eContent Import Details Report");

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'eContent Import Details')
			->setCellValue('A3', 'ID')
			->setCellValue('B3', 'File Name')
			->setCellValue('C3', 'Library File Name')
			->setCellValue('D3', 'Publisher')
			->setCellValue('E3', 'Distributor ID')
			->setCellValue('F3', 'Copies')
			->setCellValue('G3', 'Date Found')
			->setCellValue('H3', 'eContent Record ID')
			->setCellValue('I3', 'eContent Item ID')
			->setCellValue('J3', 'Date Sent to Packaging')
			->setCellValue('K3', 'Packaging ID')
			->setCellValue('L3', 'ACS Error')
			->setCellValue('M3', 'ACS ID')
			->setCellValue('N3', 'Status');

		$a=4;
		//Loop Through The Report Data
		foreach ($records as $record) {
				
			$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$a, $record->id)
				->setCellValue('B'.$a, $record->filename)
				->setCellValue('C'.$a, $record->libraryFilename)
				->setCellValue('D'.$a, $record->publisher)
				->setCellValue('E'.$a, $record->distributorId)
				->setCellValue('F'.$a, $record->copies)
				->setCellValue('G'.$a, date('m/d/Y H:i:s', $record->dateFound))
				->setCellValue('H'.$a, $record->econtentRecordId)
				->setCellValue('I'.$a, $record->econtentItemId)
				->setCellValue('J'.$a, $record->dateSentToPackaging ? date('m/d/Y  H:i:s', $record->dateSentToPackaging) : '')
				->setCellValue('K'.$a, $record->packagingId)
				->setCellValue('L'.$a, $record->acsError)
				->setCellValue('M'.$a, $record->acsId)
				->setCellValue('N'.$a, $record->status);
			$a++;
		}
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);
			
		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('eContent Import Details Report');

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		// Redirect output to a client�s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename=eContentImportDetailsReport.xls');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}