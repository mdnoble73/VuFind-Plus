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
					$link = preg_replace("/[&?]page=\d+/", "", $link);
					if ($recordsPerPage != '-1'){
						$options = array('totalItems' => $result['numTransactions'],
					                 'fileName'   => $link,
					                 'perPage'    => $recordsPerPage,
					                 'append'    => true,
						);
						$pager = new VuFindPager($options);
						$interface->assign('pageLinks', $pager->getLinks());
					}

					$transList = array();
					foreach ($result['transactions'] as $i => $data) {

						/*if ($record == null){
							echo("No record found for {$data['id']}<br/>");
							}*/
						$itemBarcode = $data['barcode'];
						if (isset($_SESSION['renewResult'][$itemBarcode])){
							$renewMessage = $_SESSION['renewResult'][$itemBarcode]['message'];
							$renewResult = $_SESSION['renewResult'][$itemBarcode]['result'];
							$data['renewMessage'] = $renewMessage;
							$data['renewResult']  = $renewResult;
							$result['transactions'][$i] = $result;
							unset($_SESSION['renewResult'][$itemBarcode]);
						}else{
							$renewMessage = null;
							$renewResult = null;
						}

						/*//Make sure that we don't get duplicate sort keys by appending the xnum.
						 $sortKey .= '-' . $i;
						 $id = $data['id'];
						 $downloadLink = null;
						 $readOnlineLink = null;
						 $returnEPubLink = null;
						 $transList[$sortKey] = array(
						 'recordId'=> $id,
						 'shortId'   => $id,
						 'isbn'      => $data['isbn'],
						 'upc'      => $data['upc'],
						 'author'    => $data['author'],
						 'title'     => $title,
						 'format_category'    => $data['format_category'],
						 'format'    => $data['format'],
						 'checkoutdate'   => $data['checkoutdate'],
						 'duedate'   => $data['duedate'],
						 'canrenew'  => isset($data['canrenew']) ? $data['canrenew'] : true ,
						 'itemindex' => isset($data['itemindex']) ? $data['itemindex'] : $i,
						 'itemid'    => $data['barcode'],
						 'renewMessage' => $renewMessage,
						 'renewResult'  => $renewResult,
						 'renewCount'=> $data['renewCount'],
						 'overdue' => $data['overdue'],
						 'daysUntilDue' => $data['daysUntilDue'],
						 'hasEpub'     => isset($data['hasEpub']) ? $data['hasEpub'] : false,
						 'links'       => $data['links'],
						 'holdQueueLength' => $data['holdQueueLength'],
						 );*/
					}
					//ksort($transList);
					$interface->assign('transList', $result['transactions']);
				}
			}
		}

		if (isset($_GET['exportToExcel'])) {
			$this->exportToExcel($result['transactions']);
		}

		$homeLibrary = Library::getPatronHomeLibrary();
		$patronCanRenew = true;
		if (isset($homeLibrary) && !is_null($homeLibrary)){
			$patronCanRenew = $homeLibrary->canRenew == 0;
		}
		$interface->assign('patronCanRenew', $patronCanRenew);

		$interface->setTemplate('checkedout.tpl');
		$interface->setPageTitle('Checked Out Items');
		$interface->display('layout.tpl');
	}

	public function exportToExcel($checkedOutItems) {
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
		->setCategory("Checked Out Items");

		$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', 'Checked Out Items')
		->setCellValue('A3', 'Title')
		->setCellValue('B3', 'Author')
		->setCellValue('C3', 'Format')
		->setCellValue('D3', 'Out')
		->setCellValue('E3', 'Due')
		->setCellValue('F3', 'Renewed')
		->setCellValue('G3', 'Wait List');


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
			$formatString = implode(', ', $row['format']);
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A'.$a, $titleCell)
			->setCellValue('B'.$a, $authorCell)
			->setCellValue('C'.$a, $formatString)
			->setCellValue('D'.$a, date('M d, Y', strtotime($row['checkoutdate'])))
			->setCellValue('E'.$a, date('M d, Y', strtotime($row['duedate'])))
			->setCellValue('F'.$a, $row['renewCount'])
			->setCellValue('G'.$a, $row['holdQueueLength']);

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