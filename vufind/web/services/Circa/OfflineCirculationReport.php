<?php
/**
 * A report of check ins and check outs that have been placed offline with their status.
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/26/13
 * Time: 10:39 AM
 */
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR .'/sys/OfflineCirculationEntry.php';
class Circa_OfflineCirculationReport extends Admin_Admin{
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
		$typesToInclude = isset($_REQUEST['typesToInclude']) ? $_REQUEST['typesToInclude'] : 'everything';

		$interface->assign('startDate', $startDate->getTimestamp());
		$interface->assign('endDate', $endDate->getTimestamp());
		$interface->assign('typesToInclude', $typesToInclude);


		$offlineCirculationEntries = array();
		$offlineCirculationEntryObj = new OfflineCirculationEntry();
		$offlineCirculationEntryObj->whereAdd("timeEntered >= " . $startDate->getTimestamp() . " AND timeEntered <= " . $endDate->getTimestamp());
		if ($typesToInclude == 'checkouts'){
			$offlineCirculationEntryObj->type = 'Check Out';
		}else if ($typesToInclude == 'checkins'){
			$offlineCirculationEntryObj->type = 'Check In';
		}
		$offlineCirculationEntryObj->find();
		while ($offlineCirculationEntryObj->fetch()){
			$offlineCirculationEntries[] = clone $offlineCirculationEntryObj;
		}

		$interface->setPageTitle('Offline Circulation Report');
		$interface->assign('offlineCirculation', $offlineCirculationEntries);
		$interface->setTemplate('offlineCirculationReport.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles() {
		return array('opacAdmin', 'libraryAdmin');
	}
}