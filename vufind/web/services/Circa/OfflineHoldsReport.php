<?php
/**
 * A report of holds that have been placed offline with their status.
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/26/13
 * Time: 10:39 AM
 */
require_once ROOT_DIR . '/services/Admin/Admin.php';
class Circa_OfflineHoldsReport extends Admin_Admin{
	public function launch(){
		global $interface;

		if (isset($_REQUEST['startDate'])){
			$startDate = new DateTime($_REQUEST['startDate']);
		}else{
			$startDate = new DateTime();
			date_sub($startDate, new DateInterval('P1M'));
		}
		if (isset($_REQUEST['endDate'])){
			$endDate = new DateTime($_REQUEST['endDate']);
		}else{
			$endDate = new DateTime();
		}

		$interface->assign('startDate', $startDate->getTimestamp());
		$interface->assign('endDate', $endDate->getTimestamp());


		$offlineHolds = array();
		$offlineHoldsObj = new OfflineHold();
		$offlineHoldsObj->whereAdd("timeEntered >= " . $startDate->getTimestamp() . " AND timeEntered <= " . $endDate->getTimestamp());
		$offlineHoldsObj->find();
		while ($offlineHoldsObj->fetch()){
			$offlineHold = array();
			$resource = new Resource();
			$resource->source = 'VuFind';
			$resource->record_id = $offlineHoldsObj->bibId;
			if ($resource->find(true)){
				$offlineHold['title'] = $resource->title;
			}
			$offlineHold['patronBarcode'] = $offlineHoldsObj->patronBarcode;
			$offlineHold['bibId'] = $offlineHoldsObj->bibId;
			$offlineHold['timeEntered'] = $offlineHoldsObj->timeEntered;
			$offlineHold['status'] = $offlineHoldsObj->status;
			$offlineHold['notes'] = $offlineHoldsObj->notes;
			$offlineHolds[] = $offlineHold;
		}

		$interface->setPageTitle('Offline Holds Report');
		$interface->assign('offlineHolds', $offlineHolds);
		$interface->setTemplate('offlineHoldsReport.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles() {
		return array('opacAdmin', 'libraryAdmin');
	}
}