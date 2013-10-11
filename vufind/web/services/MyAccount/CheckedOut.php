<?php
/**
 * Shows all titles that are checked out to a user (combines all sources)
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/10/13
 * Time: 1:10 PM
 */

require_once ROOT_DIR . '/services/MyResearch/MyResearch.php';
class MyAccount_CheckedOut extends MyResearch{
	function launch(){
		global $configArray;
		global $interface;
		global $user;
		global $timer;

		$allCheckedOut = array();
		if ($configArray['Catalog']['offline']){
			$interface->assign('offline', true);
		}else{
			$interface->assign('offline', false);

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

			// Get My Transactions
			if ($this->catalog->status) {
				if ($user->cat_username) {
					$patron = $this->catalog->patronLogin($user->cat_username, $user->cat_password);
					$timer->logTime("Logged in patron to get checked out items.");
					if (PEAR_Singleton::isError($patron))
						PEAR_Singleton::raiseError($patron);

					$patronResult = $this->catalog->getMyProfile($patron);
					if (!PEAR_Singleton::isError($patronResult)) {
						$interface->assign('profile', $patronResult);
					}
					$timer->logTime("Got patron profile to get checked out items.");

					$libraryHoursMessage = Location::getLibraryHoursMessage($patronResult['homeLocationId']);
					$interface->assign('libraryHoursMessage', $libraryHoursMessage);

					//Get checked out titles from the ILS
					$catalogTransactions = $this->catalog->getMyTransactions($page, $recordsPerPage, $selectedSortOption);
					$timer->logTime("Loaded transactions from catalog.");

					//Get checked out titles from OverDrive
					$overDriveDriver = OverDriveDriverFactory::getDriver();
					$overDriveCheckedOutItems = $overDriveDriver->getOverDriveCheckedOutItems($user);

					//Get a list of eContent that has been checked out
					require_once ROOT_DIR . '/Drivers/EContentDriver.php';
					$driver = new EContentDriver();
					$eContentCheckedOut = $driver->getMyTransactions($user);

					$allCheckedOut = array_merge($catalogTransactions['transactions'], $overDriveCheckedOutItems['items'], $eContentCheckedOut['transactions']);
					if (!PEAR_Singleton::isError($catalogTransactions)) {

						$interface->assign('showNotInterested', false);
						foreach ($allCheckedOut as $i => $data) {
							//Get Rating
							$resource = new Resource();
							if ($data['checkoutSource'] == 'ILS'){
								$resource->source = 'VuFind';
							}else{
								$resource->source = 'eContent';
							}

							if (isset($data['id'])){
								$resource->record_id = $data['id'];
								if ($resource->find(true)){
									$data['ratingData'] = $resource->getRatingData($user);
								}
							}
							$allCheckedOut[$i] = $data;

							$itemBarcode = isset($data['barcode']) ? $data['barcode'] : null;
							$itemId = isset($data['itemid']) ? $data['itemid'] : null;
							if ($itemBarcode != null && isset($_SESSION['renew_message'][$itemBarcode])){
								$renewMessage = $_SESSION['renew_message'][$itemBarcode]['message'];
								$renewResult = $_SESSION['renew_message'][$itemBarcode]['result'];
								$data['renewMessage'] = $renewMessage;
								$data['renewResult']  = $renewResult;
								$allCheckedOut[$i] = $data;
								unset($_SESSION['renew_message'][$itemBarcode]);
								//$logger->log("Found renewal message in session for $itemBarcode", PEAR_LOG_INFO);
							}else if ($itemId != null && isset($_SESSION['renew_message'][$itemId])){
								$renewMessage = $_SESSION['renew_message'][$itemId]['message'];
								$renewResult = $_SESSION['renew_message'][$itemId]['result'];
								$data['renewMessage'] = $renewMessage;
								$data['renewResult']  = $renewResult;
								$allCheckedOut[$i] = $data;
								unset($_SESSION['renew_message'][$itemId]);
								//$logger->log("Found renewal message in session for $itemBarcode", PEAR_LOG_INFO);
							}else{
								$renewMessage = null;
								$renewResult = null;
							}

						}
						$interface->assign('transList', $allCheckedOut);
						unset($_SESSION['renew_message']);
					}
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

		if (isset($_GET['exportToExcel']) && isset($result)) {
			$this->exportToExcel($allCheckedOut, $showOut, $showRenewed, $showWaitList);
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

		// Redirect output to a client's web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="CheckedOutItems.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;

	}
}