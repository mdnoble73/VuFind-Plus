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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'services/MyResearch/MyResearch.php';
require_once 'sys/Pager.php';

class CheckedOut extends MyResearch{
	function launch(){
		global $configArray;
		global $interface;
		global $user;
		global $timer;
		$logger = new Logger();

		// Get My Transactions
		$oneOrMoreRenewableItems = false;
		if ($this->catalog->status) {
			if ($user->cat_username) {
				$patron = $this->catalog->patronLogin($user->cat_username, $user->cat_password);
				$timer->logTime("Logged in patron to get checked out items.");
				if (PEAR::isError($patron))
				PEAR::raiseError($patron);

				$patronResult = $this->catalog->getMyProfile($patron);
				if (!PEAR::isError($patronResult)) {
					$interface->assign('profile', $patronResult);
				}
				$timer->logTime("Got patron profile to get checked out items.");

				$libraryHoursMessage = Location::getLibraryHoursMessage($patronResult['homeLocationId']);
				$interface->assign('libraryHoursMessage', $libraryHoursMessage);

				// Define sorting options
				$sortOptions = array('title'   => 'Title',
                             'author'  => 'Author',
                             'dueDate' => 'Due Date',
				                     'format'  => 'Format',
				                     'renewed'  => 'Times Renewed',
				                     'holdQueueLength'  => 'Wish List',
				);
				$interface->assign('sortOptions', $sortOptions);
				$selectedSortOption = isset($_REQUEST['accountSort']) ? $_REQUEST['accountSort'] : 'dueDate';
				$interface->assign('defaultSortOption', $selectedSortOption);
				$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;

				$recordsPerPage = isset($_REQUEST['pagesize']) && (is_numeric($_REQUEST['pagesize'])) ? $_REQUEST['pagesize'] : 25;
				$interface->assign('recordsPerPage', $recordsPerPage);
				if (isset($_GET['exportToExcel'])) {
					$recordsPerPage = -1;
					$page = 1;
				}

				$result = $this->catalog->getMyTransactions($patron, $page, $recordsPerPage, $selectedSortOption);
				$timer->logTime("Loaded transactions from catalog.");
				if (!PEAR::isError($result)) {

					$link = $_SERVER['REQUEST_URI'];
					if (preg_match('/[&?]page=/', $link)){
						$link = preg_replace("/page=\\d+/", "page=%d", $link);
					}else if (strpos($link, "?") > 0){
						$link .= "&page=%d";
					}else{
						$link .= "?page=%d";
					}
					if ($recordsPerPage != '-1'){
						$options = array('totalItems' => $result['numTransactions'],
					                 'fileName'   => $link,
					                 'perPage'    => $recordsPerPage,
					                 'append'    => false,
						);
						$pager = new VuFindPager($options);
						$interface->assign('pageLinks', $pager->getLinks());
					}

					$transList = array();
					foreach ($result['transactions'] as $i => $data) {
						$itemBarcode = isset($data['barcode']) ? $data['barcode'] : null;
						$itemId = isset($data['itemid']) ? $data['itemid'] : null;
						if ($itemBarcode != null && isset($_SESSION['renew_message'][$itemBarcode])){
							$renewMessage = $_SESSION['renew_message'][$itemBarcode]['message'];
							$renewResult = $_SESSION['renew_message'][$itemBarcode]['result'];
							$data['renewMessage'] = $renewMessage;
							$data['renewResult']  = $renewResult;
							$result['transactions'][$i] = $data;
							unset($_SESSION['renew_message'][$itemBarcode]);
							//$logger->log("Found renewal message in session for $itemBarcode", PEAR_LOG_INFO);
						}else if ($itemId != null && isset($_SESSION['renew_message'][$itemId])){
							$renewMessage = $_SESSION['renew_message'][$itemId]['message'];
							$renewResult = $_SESSION['renew_message'][$itemId]['result'];
							$data['renewMessage'] = $renewMessage;
							$data['renewResult']  = $renewResult;
							$result['transactions'][$i] = $data;
							unset($_SESSION['renew_message'][$itemId]);
							//$logger->log("Found renewal message in session for $itemBarcode", PEAR_LOG_INFO);
						}else{
							$renewMessage = null;
							$renewResult = null;
						}

					}
					$interface->assign('transList', $result['transactions']);
					unset($_SESSION['renew_message']);
				}
			}
		}

		//Determine which columns to show
		$ils = $configArray['Catalog']['ils'];
		$showOut = ($ils == 'Horizon');
		$showRenewed = ($ils == 'Horizon' || $ils == 'Millennium');
		$showWaitList = ($ils == 'Horizon');

		$interface->assign('showOut', $showOut);
		$interface->assign('showRenewed', $showRenewed);
		$interface->assign('showWaitList', $showWaitList);

		if (isset($_GET['exportToExcel'])) {
			$this->exportToExcel($result['transactions'], $showOut, $showRenewed, $showWaitList);
		}

		$interface->setTemplate('checkedout.tpl');
		$interface->setPageTitle('Checked Out Items');
		$interface->display('layout.tpl');
	}

	public function exportToExcel($checkedOutItems, $showOut, $showRenewed, $showWaitList) {
		//PHPEXCEL
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator("VuFind Plus")
		->setLastModifiedBy("VuFind Plus")
		->setTitle("Office 2007 XLSX Document")
		->setSubject("Office 2007 XLSX Document")
		->setDescription("Office 2007 XLSX, generated using PHP.")
		->setKeywords("office 2007 openxml php")
		->setCategory("Checked Out Items");

		$activeSheet = $objPHPExcel->setActiveSheetIndex(0);
		$curRow = 1;
		$curCol = 0;
		$activeSheet->setCellValueByColumnAndRow($curCol, $curRow, 'Checked Out Items');
		$curRow = 3;
		$curCol = 0;
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Title');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Author');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Format');
		if ($showOut){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Out');
		}
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Due');
		if ($showRenewed){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Renewed');
		}
		if ($showWaitList){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Wait List');
		}


		$a=4;
		//Loop Through The Report Data
		foreach ($checkedOutItems as $row) {
			$titleCell = preg_replace("/(\/|:)$/", "", $row['title']);
			if (isset ($row['title2'])){
				$titleCell .= preg_replace("/(\/|:)$/", "", $row['title2']);
			}

			if (isset ($row['author'])){
				if (is_array($row['author'])){
					$authorCell = implode(', ', $row['author']);
				}else{
					$authorCell = $row['author'];
				}
				$authorCell = str_replace('&nbsp;', ' ', $authorCell);
			}else{
				$authorCell = '';
			}
			if (isset($row['format'])){
				if (is_array($row['format'])){
					$formatString = implode(', ', $row['format']);
				}else{
					$formatString = $row['format'];
				}
			}else{
				$formatString ='';
			}
			$activeSheet = $objPHPExcel->setActiveSheetIndex(0);
			$curCol = 0;
			$activeSheet->setCellValueByColumnAndRow($curCol++, $a, $titleCell);
			$activeSheet->setCellValueByColumnAndRow($curCol++, $a, $authorCell);
			$activeSheet->setCellValueByColumnAndRow($curCol++, $a, $formatString);
			if ($showOut){
				$activeSheet->setCellValueByColumnAndRow($curCol++, $a, date('M d, Y', $row['checkoutdate']));
			}
			$activeSheet->setCellValueByColumnAndRow($curCol++, $a, date('M d, Y', $row['duedate']));
			if ($showRenewed){
				$activeSheet->setCellValueByColumnAndRow($curCol++, $a, $row['renewCount']);
			}
			if ($showWaitList){
				$activeSheet->setCellValueByColumnAndRow($curCol++, $a, $row['holdQueueLength']);
			}

			$a++;
		}
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);

		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('Checked Out');

		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="CheckedOutItems.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;

	}
}