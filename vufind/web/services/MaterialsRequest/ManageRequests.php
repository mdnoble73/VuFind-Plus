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
require_once('sys/MaterialsRequestStatus.php');

class ManageRequests extends Admin {

	function launch()
	{
		global $configArray;
		global $interface;
		global $user;
		
		//Load status information 
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->orderBy('isDefault DESC, isOpen DESC, description ASC');
		$materialsRequestStatus->find();
		$allStatuses = array();
		$availableStatuses = array();
		$defaultStatusesToShow = array();
		while ($materialsRequestStatus->fetch()){
			$availableStatuses[$materialsRequestStatus->id] = $materialsRequestStatus->description;
			$allStatuses[$materialsRequestStatus->id] = clone $materialsRequestStatus;
			if ($materialsRequestStatus->isOpen == 1 || $materialsRequestStatus->isDefault == 1){
				$defaultStatusesToShow[] = $materialsRequestStatus->id;
			}
		}
		$interface->assign('availableStatuses', $availableStatuses);
		
		if (isset($_REQUEST['statusFilter'])){
			$statusesToShow = $_REQUEST['statusFilter'];
			$_SESSION['materialsRequestStatusFilter'] = $statusesToShow;
		}elseif (isset($_SESSION['materialsRequestStatusFilter'])){
			$statusesToShow = $_SESSION['materialsRequestStatusFilter'];
		}else{
			$statusesToShow = $defaultStatusesToShow;
		}
		$interface->assign('statusFilter', $statusesToShow);

		//Process status change if needed
		if (isset($_REQUEST['updateStatus']) && isset($_REQUEST['select'])){
			//Look for which titles should be modified
			$selectedRequests = $_REQUEST['select'];
			$statusToSet = $_REQUEST['newStatus'];
			require_once 'sys/Mailer.php';
			$mail = new VuFindMailer();
			foreach ($selectedRequests as $requestId => $selected){
				$materialRequest = new MaterialsRequest();
				$materialRequest->id = $requestId;
				if ($materialRequest->find(true)){
					$materialRequest->status = $statusToSet;
					$materialRequest->dateUpdated = time();
					$materialRequest->update();
					
					if ($allStatuses[$statusToSet]->sendEmailToPatron == 1 && $materialRequest->email){
						$body = '*****This is an auto-generated email response. Please do not reply.*****';
						$body .= "\r\n" . $allStatuses[$statusToSet]->emailTemplate;
						
						//Replace tags with appropriate values 
						$materialsRequestUser = new User();
						$materialsRequestUser->id = $materialRequest->createdBy;
						$materialsRequestUser->find(true);
						foreach ($materialsRequestUser as $fieldName => $fieldValue){
							if (!is_array($fieldValue)){
								$body = str_replace('{' . $fieldName . '}', $fieldValue, $body);
							}
						}
						foreach ($materialRequest as $fieldName => $fieldValue){
							if (!is_array($fieldValue)){
								$body = str_replace('{' . $fieldName . '}', $fieldValue, $body);
							}
						}
						$materialsRequestUser->find(true);
						$mail->send($materialRequest->email, $configArray['Site']['email'], "Your Materials Request Update", $body, $configArray['Site']['email']);
					}
				}
			}
		}

		
		
		$availableFormats = MaterialsRequest::getFormats();
		$interface->assign('availableFormats', $availableFormats);
		$defaultFormatsToShow = array_keys($availableFormats);
		if (isset($_REQUEST['formatFilter'])){
			$formatsToShow = $_REQUEST['formatFilter'];
			$_SESSION['materialsRequestFormatFilter'] = $formatsToShow;
		}elseif (isset($_SESSION['materialsRequestFormatFilter'])){
			$formatsToShow = $_SESSION['materialsRequestFormatFilter'];
		}else{
			$formatsToShow = $defaultFormatsToShow;
		}
		$interface->assign('formatFilter', $formatsToShow);
		
		//Get a list of all materials requests for the user
		$allRequests = array();
		if ($user){
			
			$materialsRequests = new MaterialsRequest();
			$materialsRequests->joinAdd(new Location(), "LEFT");
			$materialsRequests->joinAdd(new MaterialsRequestStatus());
			$materialsRequests->joinAdd(new User());
			$materialsRequests->selectAdd();
			$materialsRequests->selectAdd('materials_request.*, description as statusLabel, location.displayName as location, firstname, lastname, ' . $configArray['Catalog']['barcodeProperty'] . ' as barcode');

			if (count($availableStatuses) > count($statusesToShow)){
				$statusSql = "";
				foreach ($statusesToShow as $status){
					if (strlen($statusSql) > 0) $statusSql .= ",";
					$statusSql .= "'" . mysql_escape_string($status) . "'";
				}
				$materialsRequests->whereAdd("status in ($statusSql)");
			}
			
			if (count($availableFormats) > count($formatsToShow)){
				//At least one format is disabled
				$formatSql = "";
				foreach ($formatsToShow as $format){
					if (strlen($formatSql) > 0) $formatSql .= ",";
					$formatSql .= "'" . mysql_escape_string($format) . "'";
				}
				$materialsRequests->whereAdd("format in ($formatSql)");
			}

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
		global $configArray;
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
		
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'ID');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Title');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Season');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Magazine');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Author');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Format');
		if ($configArray['MaterialsRequest']['showEbookFormatField'] || $configArray['MaterialsRequest']['showEaudioFormatField']){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Sub Format');
		}
		if ($configArray['MaterialsRequest']['showBookTypeField']){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Type');
		}
		if ($configArray['MaterialsRequest']['showAgeField']){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Age Level');
		}
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'ISBN');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'UPC');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'ISSN');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'OCLC Number');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Publisher');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Publication Year');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Abridged');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'How did you hear about this?');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Comments');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Name');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Barcode');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Email');
		
		if ($configArray['MaterialsRequest']['showPlaceHoldField']){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Hold');
		}
		if ($configArray['MaterialsRequest']['showIllField']){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'ILL');
		}
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Status');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Date Created');

		$numCols = $curCol;
		//Loop Through The Report Data
		foreach ($allRequests as $request) {
			if (array_key_exists($request->id, $selectedRequestIds)){
				$curRow++;
				$curCol = 0;
				
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->id);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->title);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->season);
				$magazineInfo = '';
				if ($request->magazineTitle){
					$magazineInfo .= $request->magazineTitle . ' ';
				}
				if ($request->magazineDate){
					$magazineInfo .= $request->magazineDate . ' ';
				}
				if ($request->magazineVolume){
					$magazineInfo .= 'volume ' . $request->magazineVolume . ' ';
				}
				if ($request->magazineNumber){
					$magazineInfo .= 'number ' . $request->magazineNumber . ' ';
				}
				if ($request->magazinePageNumbers){
					$magazineInfo .= 'p. ' . $request->magazinePageNumbers . ' ';
				}
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, trim($magazineInfo));
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->author);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->format);
				if ($configArray['MaterialsRequest']['showEbookFormatField'] || $configArray['MaterialsRequest']['showEaudioFormatField']){
					$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->subFormat);
				}
				if ($configArray['MaterialsRequest']['showBookTypeField']){
					$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->bookType);
				}
				if ($configArray['MaterialsRequest']['showAgeField']){
					$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->ageLevel);
				}
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->isbn);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->upc);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->issn);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->oclcNumber);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->publisher);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->publicationYear);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->abridged == 0 ? 'Unabridged' : ($request->abridged == 1 ? 'Abridged' : 'Not Applicable'));
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->about);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->comments);
				$requestUser = new User();
				$requestUser->id = $request->createdBy;
				$requestUser->find(true);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $requestUser->lastname . ', ' . $requestUser->firstname);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $requestUser->$configArray['Catalog']['barcodeProperty']);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $requestUser->email);
				if ($configArray['MaterialsRequest']['showPlaceHoldField']){
					if ($request->placeHoldWhenAvailable == 1){
						$value = 'Yes ' . $request->holdPickupLocation;
						if ($request->bookmobileStop){
							$value .= ' ' . $request->bookmobileStop;
						}
					}else{
						$value = 'No';
					}
					$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $value);
				}
				if ($configArray['MaterialsRequest']['showIllField']){
					if ($request->illItem == 1){
						$value = 'Yes';
					}else{
						$value = 'No';
					}
					$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $value);
				}
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, translate($request->status));
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, date('m/d/Y', $request->dateCreated));
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
