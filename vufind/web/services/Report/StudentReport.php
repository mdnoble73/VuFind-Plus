<?php
/**
 * Displays Student Reports Created by cron
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/19/14
 * Time: 2:28 PM
 */

require_once(ROOT_DIR . '/services/Admin/Admin.php');
class Report_StudentReport extends Admin_Admin {
	function launch(){
		global $interface;
		global $configArray;
		global $user;

		//Get a list of all reports the user has access to
		$reportDir = $configArray['Site']['reportPath'];

		$allowableLocationCodes = "";
		if ($user->hasRole('opacAdmin')){
			$allowableLocationCodes = '.*';
		}elseif ($user->hasRole('libraryAdmin')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$allowableLocationCodes = trim($homeLibrary->ilsCode) . '.*';
		}elseif ($user->hasRole('locationReports')){
			$homeLocation = Location::getUserHomeLocation();
			$allowableLocationCodes = trim($homeLocation->code) . '.*';
		}
		$availableReports = array();
		$dh  = opendir($reportDir);
		while (false !== ($filename = readdir($dh))) {
			if (is_file($reportDir . '/' . $filename)){
				if (preg_match('/(\w+)_school_report\.csv/i', $filename, $matches)){
					$locationCode = $matches[1];
					if (preg_match("/$allowableLocationCodes/", $locationCode)){
						$availableReports[$locationCode] = $filename;
					}
				}
			}
		}
		ksort($availableReports);
		$interface->assign('availableReports', $availableReports);

		$selectedReport = isset($_REQUEST['selectedReport']) ? $availableReports[$_REQUEST['selectedReport']] : reset($availableReports);
		$interface->assign('selectedReport', $selectedReport);
		$showOverdueOnly = isset($_REQUEST['showOverdueOnly']) ? $_REQUEST['showOverdueOnly'] == 'overdue': true;
		$interface->assign('showOverdueOnly', $showOverdueOnly);
		$now = time();
		if ($selectedReport){
			$fhnd = fopen($reportDir . '/' . $selectedReport, "r");
			if ($fhnd){
				$fileData = array();
				while (($data = fgetcsv($fhnd)) !== FALSE){
					$okToInclude = true;
					if ($showOverdueOnly){
						$dueDate = $data[12];
						$dueTime = strtotime($dueDate);
						if ($dueTime >= $now){
							$okToInclude = false;
						}
					}
					if ($okToInclude){
						$fileData[] = $data;
					}
				}
				$interface->assign('reportData', $fileData);
			}
		}

		if (isset($_REQUEST['download'])){
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename=' . $selectedReport);
			header('Content-Length:' . filesize($reportDir . '/' . $selectedReport));
			readfile($reportDir . '/' . $selectedReport);
			exit;
		}

		$interface->setPageTitle('Student Report');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('studentReport.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'locationReports');
	}
} 