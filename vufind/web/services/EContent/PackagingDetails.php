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
		
		$packagingDetails = new PackagingDetailsEntry();
		$packagingDetails->whereAdd('created >= ' . $startDate->getTimestamp() . ' AND created < ' . $endDate->getTimestamp());
		if ($distributorRestriction) {
			$packagingDetails->whereAdd($distributorRestriction);
		}
		if ($statusRestriction) {
			$packagingDetails->whereAdd($statusRestriction);
		}

		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])){
			$packagingDetails->find();
			$records = array();
			while ($packagingDetails->fetch()) {
				$records[] = clone $packagingDetails;
			}
			$this->exportToExcel($records);
		}
		
		// Number of row per page
		$perPage = 20;
		
		$datagrid =& new Structures_DataGrid($perPage);
		$datagrid->setDefaultSort(array('filename' => 'ASC'));
		$datagrid->bind($packagingDetails);
		$datagrid->addColumn(new Structures_DataGrid_Column('Filename', 'filename', 'filename',  null, null, array($this, 'printFileNameAsLinkToDetails')));
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
	
	function printFileNameAsLinkToDetails($params, $args = array()) {
		extract($params);
		return '<a href="#" onclick="popupDetails(' . $record['id'] . ');return false;">'
			. $record['filename'] . '</a>';
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
	
	function exportToExcel($records){
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
		->setCategory("Packaging Details Report");
	
		// Add some data
		$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', 'Packaging Details')
		->setCellValue('A3', 'ID')
		->setCellValue('B3', 'File Name')
		->setCellValue('C3', 'Distributor ID')
		->setCellValue('D3', 'Copies')
		->setCellValue('E3', 'Previous ACS ID')
		->setCellValue('F3', 'Created')
		->setCellValue('G3', 'Last Update')
		->setCellValue('H3', 'Packaging Start Time')
		->setCellValue('I3', 'Packaging End Time')
		->setCellValue('J3', 'ACS Error')
		->setCellValue('K3', 'ACS ID')
		->setCellValue('L3', 'Status');
	
		$a=4;
		//Loop Through The Report Data
		foreach ($records as $record) {
	
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A'.$a, $record->id)
			->setCellValue('B'.$a, $record->filename)
			->setCellValue('C'.$a, $record->distributorId)
			->setCellValue('D'.$a, $record->copies)
			->setCellValue('E'.$a, $record->previousAcsId)
			->setCellValue('F'.$a, date('m/d/Y  H:i:s', $record->created))
			->setCellValue('G'.$a, $record->lastUpdate ? date('m/d/Y  H:i:s', $record->lastUpdate) : '')
			->setCellValue('H'.$a, $record->packagingStartTime ? date('m/d/Y H:i:s', $record->packagingStartTime) : '')
			->setCellValue('I'.$a, $record->packagingEndTime ? date('m/d/Y H:i:s', $record->packagingEndTime) : '')
			->setCellValue('J'.$a, $record->acsError)
			->setCellValue('K'.$a, $record->acsId)
			->setCellValue('L'.$a, $record->status);
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
			
		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('Packaging Details Report');
	
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
	
		// Redirect output to a client�s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename=PackagingDetailsReport.xls');
		header('Cache-Control: max-age=0');
	
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}