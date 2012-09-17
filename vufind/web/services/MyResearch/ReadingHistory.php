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

class ReadingHistory extends MyResearch
{
	function launch()
	{
		global $configArray;
		global $interface;
		global $user;

		global $library;
		if (isset($library)){
			$interface->assign('showRatings', $library->showRatings);
		}else{
			$interface->assign('showRatings', 1);
		}

		// Get My Transactions
		if ($this->catalog->status) {
			if ($user->cat_username) {
				$patron = $this->catalog->patronLogin($user->cat_username, $user->cat_password);
				if (PEAR::isError($patron))
				PEAR::raiseError($patron);

				$patronResult = $this->catalog->getMyProfile($patron);
				if (!PEAR::isError($patronResult)) {
					$interface->assign('profile', $patronResult);
				}

				//Check to see if there is an action to perform.
				if (isset($_REQUEST['readingHistoryAction']) && strlen($_REQUEST['readingHistoryAction']) > 0 && $_REQUEST['readingHistoryAction'] != 'exportToExcel'){
					//Perform the requested action
					$selectedTitles = isset($_REQUEST['selected']) ? $_REQUEST['selected'] : array();
					$readingHistoryAction = $_REQUEST['readingHistoryAction'];
					$this->catalog->doReadingHistoryAction($patron, $readingHistoryAction, $selectedTitles);
					//redirect back to ourself without the action.
					header("Location: {$configArray['Site']['url']}/MyResearch/ReadingHistory");
					die();
				}

				// Define sorting options
				if (strcasecmp($configArray['Catalog']['ils'], 'Millennium') == 0){
					$sortOptions = array('title' => 'Title',
					                     'author' => 'Author',
					                     'checkedOut' => 'Checkout Date',
					                     'format' => 'Format',
					);
				}else{
					$sortOptions = array('title' => 'Title',
					                     'author' => 'Author',
					                     'checkedOut' => 'Checkout Date',
					                     'returned' => 'Return Date',
					                     'format' => 'Format',
					);
				}
				$interface->assign('sortOptions', $sortOptions);
				$selectedSortOption = isset($_REQUEST['accountSort']) ? $_REQUEST['accountSort'] : 'returned';
				$interface->assign('defaultSortOption', $selectedSortOption);
				$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;

				$recordsPerPage = isset($_REQUEST['pagesize']) && (is_numeric($_REQUEST['pagesize'])) ? $_REQUEST['pagesize'] : 25;
				$interface->assign('recordsPerPage', $recordsPerPage);
				if (isset($_REQUEST['readingHistoryAction']) && $_REQUEST['readingHistoryAction'] == 'exportToExcel'){
					$recordsPerPage = -1;
					$page = 1;
				}

				$result = $this->catalog->getReadingHistory($patron, $page, $recordsPerPage, $selectedSortOption);

				$link = $_SERVER['REQUEST_URI'];
				if (preg_match('/[&?]page=/', $link)){
					$link = preg_replace("/page=\\d+/", "page=%d", $link);
				}else if (strpos($link, "?") > 0){
					$link .= "&page=%d";
				}else{
					$link .= "?page=%d";
				}
				if ($recordsPerPage != '-1'){
					$options = array('totalItems' => $result['numTitles'],
					                 'fileName'   => $link,
					                 'perPage'    => $recordsPerPage,
					                 'append'    => false,
					                 );
					$pager = new VuFindPager($options);
					$interface->assign('pageLinks', $pager->getLinks());
				}
				if (!PEAR::isError($result)) {
					$interface->assign('historyActive', $result['historyActive']);
					$interface->assign('transList', $result['titles']);
					if (isset($_REQUEST['readingHistoryAction']) && $_REQUEST['readingHistoryAction'] == 'exportToExcel'){
						$this->exportToExcel($result['titles']);
					}
				}
			}
		}


		$interface->setTemplate('readingHistory.tpl');
		$interface->setPageTitle('Reading History');
		$interface->display('layout.tpl');
	}

	public function exportToExcel($readingHistory) {
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
		->setCellValue('A1', 'Reading History')
		->setCellValue('A3', 'Title')
		->setCellValue('B3', 'Author')
		->setCellValue('C3', 'Format')
		->setCellValue('D3', 'From')
		->setCellValue('E3', 'To');

		$a=4;
		//Loop Through The Report Data
		foreach ($readingHistory as $row) {

			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A'.$a, $row['title'])
			->setCellValue('B'.$a, $row['author'])
			->setCellValue('C'.$a, $row['format'])
			->setCellValue('D'.$a, $row['checkout'])
			->setCellValue('E'.$a, $row['lastCheckout']);

			$a++;
		}
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);

		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('Reading History');

		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="ReadingHistory.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;

	}
}