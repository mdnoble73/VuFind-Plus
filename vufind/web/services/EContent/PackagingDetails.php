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
require_once 'sys/eContent/PackagingDetailsEntry.php';
require_once 'sys/Pager.php';
require_once 'Structures/DataGrid.php';
class PackagingDetails extends Admin
{
	function launch()
	{
		global $configArray;
		global $interface;
		global $user;
		
		// Distributor Filter
		$allDistributors = $this->getAllDistributors();
		$interface->assign('distributorFilter', $allDistributors);
		$selectedDistributorFilter = null;
		if (isset($_REQUEST['distributorFilter'])){
			$selectedDistributorFilter = $_REQUEST['distributorFilter'];
		}
		$interface->assign('selectedDistributorFilter', $selectedDistributorFilter);
		$distributors = empty($selectedDistributorFilter) ? $allDistributors : $selectedDistributorFilter;
		$interface->assign('distributors', $distributors);
				
		// Status Filter
		$allStatuses = $this->getStatuses();
		$interface->assign('statusFilter', $allStatuses);
		$selectedStatusFilter = null;
		if (isset($_REQUEST['statusFilter'])){
			$selectedStatusFilter = $_REQUEST['statusFilter'];
		} else {
			// default to error status
			$selectedStatusFilter = array('acsError');
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
		
		// create a SQL clause to filter by selected distributors
		$distributorRestriction = null;
		if (isset($_REQUEST['distributorFilter'])){
			$distributorsToShow = array();
			foreach ($_REQUEST['distributorFilter'] as $id) {
				$distributorsToShow[] = "'" . mysql_escape_string(strip_tags($id)) . "'";
			}
			if (!empty($distributorsToShow)) {
				$distributorRestriction = "distributorId IN (" . implode(",", $distributorsToShow) . ") ";
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
		
		// Number of row per page
		$perPage = 20;
		
		$datagrid =& new Structures_DataGrid($perPage);
		$datagrid->setDefaultSort(array('filename' => 'ASC'));
		$packagingDetails = new PackagingDetailsEntry();
		$packagingDetails->whereAdd('created >= ' . $startDate->getTimestamp() . ' AND created < ' . $endDate->getTimestamp());
		if ($distributorRestriction) {
			$packagingDetails->whereAdd($distributorRestriction);
		}
		if ($statusRestriction) {
			$packagingDetails->whereAdd($statusRestriction);
		}
		$datagrid->bind($packagingDetails);
		$datagrid->addColumn(new Structures_DataGrid_Column('Filename', 'filename', 'filename'));
		$datagrid->addColumn(new Structures_DataGrid_Column('Distributor', 'distributorId', 'distributorId'));
		$datagrid->addColumn(new Structures_DataGrid_Column('Created', 'created', 'created',  null, null, array($this, 'printCreatedDate')));
		$datagrid->addColumn(new Structures_DataGrid_Column('Packaging Start', 'packagingStartTime', 'packagingStartTime'));
		$datagrid->addColumn(new Structures_DataGrid_Column('Packaging End', 'packagingEndTime', 'packagingEndTime'));
		$datagrid->addColumn(new Structures_DataGrid_Column('Status', 'status', 'status'));
		$interface->assign('packagingDetailsTable', $datagrid->getOutput());
		
		// create pager
		$params = array();
		if (isset($_REQUEST['startDate']) && strlen($_REQUEST['startDate']) > 0) {
			$params['startDate'] = $startDate->format('m/d/Y');
		}
		if (isset($_REQUEST['endDate']) && strlen($_REQUEST['endDate']) > 0) {
			$params['endDate'] = $endDate->format('m/d/Y');	
		}
		if (!empty($selectedDistributorFilter)) {
			$params['distributorFilter'] = $selectedDistributorFilter;
		}
		if (!empty($selectedStatusFilter)) {
			$params['statusFilter'] = $selectedStatusFilter;
		}
		$options = array('totalItems' => $datagrid->getRecordCount(),
			'fileName' => $configArray['Site']['path'] . '/EContent/PackagingDetails?' . http_build_query($params) . '&page=%d',
			'perPage' => $perPage
		);
		$pager = new VuFindPager($options);
		$interface->assign('pageLinks', $pager->getLinks());
		
		$interface->setTemplate('packagingDetails.tpl');
		$interface->setPageTitle('Packaging Details');
		$interface->display('layout.tpl');
	}

	function printCreatedDate($params, $args = array()) {
		extract($params);
		return date('m/d/Y', $record['created']);
	}
	function getAllDistributors() {
		$packagingDetails = new PackagingDetailsEntry();
		$packagingDetails->query('SELECT DISTINCT distributorId FROM ' . $packagingDetails->__table . ' ORDER BY distributorId');
		$distributors = array();
		while ($packagingDetails->fetch()){
			$distributors[$packagingDetails->distributorId] = $packagingDetails->distributorId;
		}
		return $distributors;
	}
	
	function getStatuses() {
		$packagingDetails = new PackagingDetailsEntry();
		$packagingDetails->query('SELECT DISTINCT status FROM ' . $packagingDetails->__table . ' ORDER BY status');
		$statuses = array();
		while ($packagingDetails->fetch()){
			$statuses[] = $packagingDetails->status;
		}
		return $statuses;
	}
	
	function exportToExcel($itemlessRecords){
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
			->setCategory("Archived eContent Report");

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'Archived eContent')
			->setCellValue('A3', 'ID')
			->setCellValue('B3', 'Title')
			->setCellValue('C3', 'Author')
			->setCellValue('D3', 'ISBN')
			->setCellValue('E3', 'ILS Id')
			->setCellValue('F3', 'Source')
			->setCellValue('G3', 'Date Archived');

		$a=4;
		//Loop Through The Report Data
		foreach ($itemlessRecords as $itemlessRecord) {
				
			$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$a, $itemlessRecord->id)
				->setCellValue('B'.$a, $itemlessRecord->title)
				->setCellValue('C'.$a, $itemlessRecord->author)
				->setCellValue('D'.$a, $itemlessRecord->isbn)
				->setCellValue('E'.$a, $itemlessRecord->ilsId)
				->setCellValue('F'.$a, $itemlessRecord->source)
				->setCellValue('G'.$a, date('m/d/Y', $itemlessRecord->date_updated));
			$a++;
		}
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
			
		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('Archived eContent');

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		// Redirect output to a client�s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename=ArchivedEContentReport.xls');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}