<?php
/**
 * Shows all titles that are on hold for a user (combines all sources)
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/10/13
 * Time: 1:11 PM
 */

require_once ROOT_DIR . '/services/MyResearch/MyResearch.php';
class MyAccount_Holds extends MyResearch{
	function launch()
	{
		global $configArray;
		global $interface;
		global $user;

		if (isset($_REQUEST['multiAction'])){
			$multiAction = $_REQUEST['multiAction'];
			$locationId = isset($_REQUEST['location']) ? $_REQUEST['location'] : null;
			$cancelId = array();
			$freeze = '';
			$type = 'update';
			if ($multiAction == 'cancelSelected'){
				$type = 'cancel';
				$freeze = '';
			}elseif ($multiAction == 'freezeSelected'){
				$type = 'update';
				$freeze = 'on';

			}elseif ($multiAction == 'thawSelected'){
				$type = 'update';
				$freeze = 'off';

			}elseif ($multiAction == 'updateSelected'){
				$type = 'update';
				$freeze = '';
			}
			$result = $this->catalog->driver->updateHoldDetailed($user->password, $type, '', null, $cancelId, $locationId, $freeze);
			$interface->assign('holdResult', $result);

			//Redirect back here without the extra parameters.
			$redirectUrl = $configArray['Site']['path'] . '/MyResearch/Holds?accountSort=' . ($selectedSortOption = isset($_REQUEST['accountSort']) ? $_REQUEST['accountSort'] : 'title');
			if (isset($_REQUEST['section'])){
				$redirectUrl .= "&section=" . $_REQUEST['section'];
			}
			header("Location: " . $redirectUrl);
			die();
		}

		$interface->assign('allowFreezeHolds', true);

		$ils = $configArray['Catalog']['ils'];
		$showPosition = ($ils == 'Horizon');
		$showExpireTime = ($ils == 'Horizon');
		// Define sorting options
		$sortOptions = array('title' => 'Title',
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

		$profile = $this->catalog->getMyProfile($user);

		$libraryHoursMessage = Location::getLibraryHoursMessage($profile['homeLocationId']);
		$interface->assign('libraryHoursMessage', $libraryHoursMessage);

		$allowChangeLocation = ($ils == 'Millennium' || $ils == 'Sierra');
		$interface->assign('allowChangeLocation', $allowChangeLocation);
		$showPlacedColumn = ($ils == 'Horizon');
		$interface->assign('showPlacedColumn', $showPlacedColumn);
		$showDateWhenSuspending = ($ils == 'Horizon');
		$interface->assign('showDateWhenSuspending', $showDateWhenSuspending);

		$interface->assign('showPosition', $showPosition);
		$interface->assign('showNotInterested', false);

		// Get My Transactions
		if ($configArray['Catalog']['offline']){
			$interface->assign('offline', true);
		}else{
			$patron = null;
			if ($this->catalog->status) {
				if ($user->cat_username) {
					$patron = $this->catalog->patronLogin($user->cat_username, $user->cat_password);
					$patronResult = $this->catalog->getMyProfile($patron);
					if (!PEAR_Singleton::isError($patronResult)) {
						$interface->assign('profile', $patronResult);
					}

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

					//Get Holds from the ILS
					$ilsHolds = $this->catalog->getMyHolds($patron, $page, $recordsPerPage, $selectedSortOption);
					if (PEAR_Singleton::isError($ilsHolds)) {
						$ilsHolds = array();
					}

					//Get holds from OverDrive
					$overDriveDriver = OverDriveDriverFactory::getDriver();
					$overDriveHolds = $overDriveDriver->getOverDriveHolds($user);

					//Get a list of eContent that has been checked out
					require_once ROOT_DIR . '/Drivers/EContentDriver.php';
					$driver = new EContentDriver();
					$eContentHolds = $driver->getMyHolds($user);


					$allHolds = array_merge_recursive($ilsHolds, $overDriveHolds, $eContentHolds);

					$location = new Location();
					$pickupBranches = $location->getPickupBranches($patronResult, null);
					$locationList = array();
					foreach ($pickupBranches as $curLocation) {
						$locationList[$curLocation->locationId] = $curLocation->displayName;
					}
					$interface->assign('pickupLocations', $locationList);

					//Make sure available holds come before unavailable
					$interface->assign('recordList', $allHolds['holds']);

					//make call to export function
					if ((isset($_GET['exportToExcelAvailable'])) || (isset($_GET['exportToExcelUnavailable']))){
						if (isset($_GET['exportToExcelAvailable'])) {
							$exportType = "available";
						}
						else {
							$exportType = "unavailable";
						}
						$this->exportToExcel($allHolds, $exportType, $showDateWhenSuspending, $showPosition, $showExpireTime);
					}
				}
			}
			$interface->assign('patron',$patron);
		}

		//Load holds that have been entered offline
		if ($user){
			require_once ROOT_DIR . '/sys/OfflineHold.php';
			$twoDaysAgo = time() - 48 * 60 * 60;
			$twoWeeksAgo = time() - 14 * 24 * 60 * 60;
			$offlineHoldsObj = new OfflineHold();
			$offlineHoldsObj->patronId = $user->id;
			$offlineHoldsObj->whereAdd("status = 'Not Processed' OR (status = 'Hold Placed' AND timeEntered >= $twoDaysAgo)  OR (status = 'Hold Failed' AND timeEntered >= $twoWeeksAgo)");
			$offlineHolds = array();
			if ($offlineHoldsObj->find()){
				while ($offlineHoldsObj->fetch()){
					//Load the title
					$offlineHold = array();
					$resource = new Resource();
					$resource->source = 'VuFind';
					$resource->record_id = $offlineHoldsObj->bibId;
					if ($resource->find(true)){
						$offlineHold['title'] = $resource->title;
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

		$interface->setPageTitle('My Holds');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('holds.tpl');

		//print_r($patron);
		$interface->display('layout.tpl');
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
				->setCellValue('D'.$a, isset($row['createTime']) ? date('M d, Y', $row['createTime']) : '')
				->setCellValue('E'.$a, $row['location'])
				->setCellValue('F'.$a, isset($row['availableTime']) ? date('M d, Y', strtotime($row['availableTime'])) : 'Now')
				->setCellValue('G'.$a, date('M d, Y', $row['expire']));
			} else {
				$statusCell = $row['status'];
				if ($row['frozen'] && $showDateWhenSuspending){
					$statusCell .= " until " . date('M d, Y', strtotime($row['reactivateTime']));
				}
				$objPHPExcel->getActiveSheet()
				->setCellValue('A'.$a, $titleCell)
				->setCellValue('B'.$a, $authorCell)
				->setCellValue('C'.$a, $formatString)
				->setCellValue('D'.$a, isset($row['createTime']) ? date('M d, Y', $row['createTime']) : '')
				->setCellValue('E'.$a, $row['location']);
				if ($showPosition){
					$objPHPExcel->getActiveSheet()
					->setCellValue('F'.$a, $row['position'])
					->setCellValue('G'.$a, $statusCell);
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