<?php
/**
 *
 * Copyright (C) Anythink Libraries 2012.
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
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Anythink Libraries 2012.
 *
 */

require_once 'Action.php';
require_once('services/Admin/Admin.php');
require_once('sys/MaterialsRequest.php');

class ManageRequests extends Admin {

	function launch()
	{
		global $configArray;
		global $interface;
		global $user;

		//Process status change if needed
		if (isset($_REQUEST['updateStatus']) && isset($_REQUEST['select'])){
			//Look for which titles should be modified
			$selectedRequests = $_REQUEST['select'];
			$statusToSet = $_REQUEST['newStatus'];
			foreach ($selectedRequests as $requestId => $selected){
				$materialRequest = new MaterialsRequest();
				$materialRequest->id = $requestId;
				if ($materialRequest->find(true)){
					$materialRequest->status = $statusToSet;
					$materialRequest->dateUpdated = time();
					$materialRequest->update();
				}
			}
		}

		$defaultStatusesToShow = array('pending', 'referredToILL', 'ILLplaced', 'notEnoughInfo');
		if (isset($_REQUEST['statusFilter'])){
			$statusesToShow = $_REQUEST['statusFilter'];
		}else{
			$statusesToShow = $defaultStatusesToShow;
		}
		$interface->assign('statusFilter', $statusesToShow);
		//Get a list of all materials requests for the user
		$allRequests = array();
		if ($user){
			$materialsRequests = new MaterialsRequest();
				
			$statusSql = "";
			foreach ($statusesToShow as $status){
				if (strlen($statusSql) > 0) $statusSql .= ",";
				$statusSql .= "'" . mysql_escape_string($status) . "'";
			}
			$materialsRequests->whereAdd("status in ($statusSql)");
				
			//Add filtering by date as needed
			if (isset($_REQUEST['startDate']) && strlen($_REQUEST['startDate']) > 0){
				$startDate = strtotime($_REQUEST['startDate']);
				$materialsRequests->whereAdd("dateCreated >= $startDate");
				$interface->assign('startDate', $_REQUEST['startDate']);
			}
			if (isset($_REQUEST['endDate']) && strlen($_REQUEST['endDate']) > 0){
				$endDate = strtotime($_REQUEST['endDate']);
				$materialsRequests->whereAdd("dateCreated <= $endDate");
				$interface->assign('endDate', $_REQUEST['endDate']);
			}
				
			$materialsRequests->find();
			while ($materialsRequests->fetch()){
				$allRequests[] = clone $materialsRequests;
			}
		}else{
			$interface->assign('error', "You must be logged in to manage requests.");
		}
		$interface->assign('allRequests', $allRequests);

		if (isset($_REQUEST['exportSelected'])){
			$this->exportToExcel($_REQUEST['select'], $allRequests);
		}else{
			$interface->setTemplate('manageRequests.tpl');
			$interface->setPageTitle('Manage Materials Requests');
			$interface->display('layout.tpl');
		}
	}
	
	function exportToExcel($selectedRequestIds, $allRequests){
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
		$activeSheet->setCellValueByColumnAndRow(0, 1, 'Materials Requests');
		
		//Define table headers
		$curRow = 3;
		$curCol = 0;
		$activeSheet
			->setCellValueByColumnAndRow($curCol++, $curRow, 'ID')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'Title')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'Author')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'Format')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'Age Level')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'ISBN / UPC')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'OCLC Number')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'Publisher')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'Publication Year')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'Article Info')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'Abridged')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'How did you hear about this?')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'Comments')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'Status')
			->setCellValueByColumnAndRow($curCol++, $curRow, 'Date Created');

		$numCols = $curCol;
		//Loop Through The Report Data
		foreach ($allRequests as $request) {
			if (array_key_exists($request->id, $selectedRequestIds)){
				$curRow++;
				$curCol = 0;
				
				$activeSheet
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->id)
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->title)
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->author)
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->format)
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->ageLevel)
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->isbn_upc)
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->oclcNumber)
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->publisher)
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->publicationYear)
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->articleInfo)
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->abridged == 0 ? 'Unabridged' : ($request->abridged == 1 ? 'Abridged' : 'Not Applicable'))
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->about)
					->setCellValueByColumnAndRow($curCol++, $curRow, $request->comments)
					->setCellValueByColumnAndRow($curCol++, $curRow, translate($request->status))
					->setCellValueByColumnAndRow($curCol++, $curRow, date('m/d/Y', $request->dateCreated));
			}
		}
		
		for ($i = 0; $i < $numCols; $i++){
			$activeSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
		}
			
		// Rename sheet
		$activeSheet->setTitle('Materials Requests');

		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename=MaterialsRequests.xls');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}

	function getAllowableRoles(){
		return array('cataloging');
	}
}
