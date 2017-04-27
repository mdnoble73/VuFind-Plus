<?php
/**
 * Shows all titles that are on hold for a user (combines all sources)
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/10/13
 * Time: 1:11 PM
 */

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
class MyAccount_Holds extends MyAccount{
	function launch()
	{
		global $configArray,
		       $interface,
		       $library,
		       $user;

		//Check to see if any user accounts are allowed to freeze holds
		$interface->assign('allowFreezeHolds', true);

		$ils = $configArray['Catalog']['ils'];
		$showPosition = ($ils == 'Horizon' || $ils == 'Koha');
		$showExpireTime = ($ils == 'Horizon');
		$suspendRequiresReactivationDate = ($ils == 'Horizon' || $ils == 'CarlX');
		$interface->assign('suspendRequiresReactivationDate', $suspendRequiresReactivationDate);
		$canChangePickupLocation = ($ils != 'Koha');
		$interface->assign('canChangePickupLocation', $canChangePickupLocation);
		// Define sorting options
		$sortOptions = array(
			'title' => 'Title',
			'author' => 'Author',
			'format' => 'Format',
			'placed' => 'Date Placed',
			'location' => 'Pickup Location',
			'status' => 'Status',
		);

		if ($showPosition){
			$sortOptions['position'] = 'Position';
		}
		$interface->assign('sortOptions', $sortOptions);
		$selectedSortOption = isset($_REQUEST['accountSort']) ? $_REQUEST['accountSort'] : 'title';
		$interface->assign('defaultSortOption', $selectedSortOption);

		if ($library->showLibraryHoursNoticeOnAccountPages) {
			$libraryHoursMessage = Location::getLibraryHoursMessage($user->homeLocationId);
			$interface->assign('libraryHoursMessage', $libraryHoursMessage);
		}

		$allowChangeLocation = ($ils == 'Millennium' || $ils == 'Sierra');
		$interface->assign('allowChangeLocation', $allowChangeLocation);
		//$showPlacedColumn = ($ils == 'Horizon');
		//Horizon Web Services does not include data placed anymore
		//TODO: ShowPlacedColumn is never displayed on My Holds page
//		$showPlacedColumn = true;
		$showPlacedColumn = false;
		$interface->assign('showPlacedColumn', $showPlacedColumn);
		$showDateWhenSuspending = ($ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony');
		$interface->assign('showDateWhenSuspending', $showDateWhenSuspending);

		$interface->assign('showPosition', $showPosition);
		$interface->assign('showNotInterested', false);

		// Get My Transactions
		if ($configArray['Catalog']['offline']){
			$interface->assign('offline', true);
		}else{
			if ($user) {
				$interface->assign('sortOptions', $sortOptions);
				$selectedSortOption = isset($_REQUEST['accountSort']) ? $_REQUEST['accountSort'] : 'dueDate';
				$interface->assign('defaultSortOption', $selectedSortOption);

				$recordsPerPage = isset($_REQUEST['pagesize']) && (is_numeric($_REQUEST['pagesize'])) ? $_REQUEST['pagesize'] : 25;
				$interface->assign('recordsPerPage', $recordsPerPage);

				//Get Holds from the ILS
				$allHolds = $user->getMyHolds();

				//Make sure available holds come before unavailable
				$interface->assign('recordList', $allHolds);

				//make call to export function
				if ((isset($_GET['exportToExcelAvailable'])) || (isset($_GET['exportToExcelUnavailable']))) {
					if (isset($_GET['exportToExcelAvailable'])) {
						$exportType = "available";
					} else {
						$exportType = "unavailable";
					}
					$this->exportToExcel($allHolds, $exportType, $showDateWhenSuspending, $showPosition, $showExpireTime);
				}
			}
		}

		//Load holds that have been entered offline
		if ($user){
			require_once ROOT_DIR . '/sys/OfflineHold.php';
			$twoDaysAgo = time() - 48 * 60 * 60;
			$twoWeeksAgo = time() - 14 * 24 * 60 * 60;
			$offlineHoldsObj = new OfflineHold();
			$offlineHoldsObj->patronId = $user->id;
			$offlineHoldsObj->whereAdd("status = 'Not Processed' OR (status = 'Hold Placed' AND timeEntered >= $twoDaysAgo) OR (status = 'Hold Failed' AND timeEntered >= $twoWeeksAgo)");
			// mysql has these functions as well: "status = 'Not Processed' OR (status = 'Hold Placed' AND timeEntered >= DATE_SUB(NOW(), INTERVAL 2 DAYS)) OR (status = 'Hold Failed' AND timeEntered >= DATE_SUB(NOW(), INTERVAL 2 WEEKS))");
			$offlineHolds = array();
			if ($offlineHoldsObj->find()){
				while ($offlineHoldsObj->fetch()){
					//Load the title
					$offlineHold = array();
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($offlineHoldsObj->bibId);
					if ($recordDriver->isValid()){
						$offlineHold['title'] = $recordDriver->getTitle();
					}
					$offlineHold['bibId'] = $offlineHoldsObj->bibId;
					$offlineHold['timeEntered'] = $offlineHoldsObj->timeEntered;
					$offlineHold['status'] = $offlineHoldsObj->status;
					$offlineHold['notes'] = $offlineHoldsObj->notes;
					$offlineHolds[] = $offlineHold;
				}
			}
			$interface->assign('offlineHolds', $offlineHolds);
		}

		global $library;
		if (!$library->showDetailedHoldNoticeInformation){
			$notification_method = '';
		}else{
			$notification_method = ($user->noticePreferenceLabel != 'Unknown') ? $user->noticePreferenceLabel : '';
			if ($notification_method == 'Mail' && $library->treatPrintNoticesAsPhoneNotices){
				$notification_method = 'Telephone';
			}
		}
		$interface->assign('notification_method', strtolower($notification_method));

		//print_r($patron);

		// Present to the user
		$this->display('holds.tpl', 'My Holds');
	}

	public function exportToExcel($result, $exportType, $showDateWhenSuspending, $showPosition, $showExpireTime) {
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
		->setCategory("Holds");

		if ($exportType == "available") {
			// Add some data
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'Holds - '.ucfirst($exportType))
			->setCellValue('A3', 'Title')
			->setCellValue('B3', 'Author')
			->setCellValue('C3', 'Format')
			->setCellValue('D3', 'Placed')
			->setCellValue('E3', 'Pickup')
			->setCellValue('F3', 'Available')
			->setCellValue('G3', 'Expires');
		} else {
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'Holds - '.ucfirst($exportType))
			->setCellValue('A3', 'Title')
			->setCellValue('B3', 'Author')
			->setCellValue('C3', 'Format')
			->setCellValue('D3', 'Placed')
			->setCellValue('E3', 'Pickup');

			if ($showPosition){
				$objPHPExcel->getActiveSheet()->setCellValue('F3', 'Position')
				->setCellValue('G3', 'Status');
				if ($showExpireTime){
					$objPHPExcel->getActiveSheet()->setCellValue('H3', 'Expires');
				}
			}else{
				$objPHPExcel->getActiveSheet()
				->setCellValue('F3', 'Status');
				if ($showExpireTime){
					$objPHPExcel->getActiveSheet()->setCellValue('G3', 'Expires');
				}
			}
		}


		$a=4;
		//Loop Through The Report Data
		foreach ($result[$exportType] as $row) {

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
				$formatString = '';
			}

			if ($exportType == "available") {
				$objPHPExcel->getActiveSheet()
				->setCellValue('A'.$a, $titleCell)
				->setCellValue('B'.$a, $authorCell)
				->setCellValue('C'.$a, $formatString)
//				->setCellValue('D'.$a, isset($row['createTime']) ? date('M d, Y', $row['createTime']) : '')
				->setCellValue('D'.$a, isset($row['create']) ? date('M d, Y', $row['create']) : '')
				->setCellValue('E'.$a, $row['location'])
				->setCellValue('F'.$a, isset($row['availableTime']) ? date('M d, Y', strtotime($row['availableTime'])) : 'Now')
				->setCellValue('G'.$a, date('M d, Y', $row['expire'])); //prefer expireTime because it is a timestamp
			} else {
				if (isset($row['status'])){
					$statusCell = $row['status'];
				}else{
					$statusCell = '';
				}

				if (isset($row['frozen']) && $row['frozen'] && $showDateWhenSuspending){
					$statusCell .= " until " . date('M d, Y', strtotime($row['reactivateTime']));
				}
				$objPHPExcel->getActiveSheet()
				->setCellValue('A'.$a, $titleCell)
				->setCellValue('B'.$a, $authorCell)
				->setCellValue('C'.$a, $formatString)
				->setCellValue('D'.$a, isset($row['createTime']) ? date('M d, Y', $row['createTime']) : '');
				if (isset($row['location'])){
					$objPHPExcel->getActiveSheet()->setCellValue('E'.$a, $row['location']);
				}else{
					$objPHPExcel->getActiveSheet()->setCellValue('E'.$a, '');
				}

				if ($showPosition){
					if (isset($row['position'])){
						$objPHPExcel->getActiveSheet()->setCellValue('F'.$a, $row['position']);
					}else{
						$objPHPExcel->getActiveSheet()->setCellValue('F'.$a, '');
					}

					$objPHPExcel->getActiveSheet()->setCellValue('G'.$a, $statusCell);
					if ($showExpireTime){
						$objPHPExcel->getActiveSheet()->setCellValue('H'.$a, date('M d, Y', $row['expireTime']));
					}
				}else{
					$objPHPExcel->getActiveSheet()->setCellValue('F'.$a, $statusCell);
					if ($showExpireTime){
						$objPHPExcel->getActiveSheet()->setCellValue('G'.$a, date('M d, Y', $row['expireTime']));
					}
				}
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
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);


		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('Holds');

		// Redirect output to a client's web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="Holds.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;

	}
}