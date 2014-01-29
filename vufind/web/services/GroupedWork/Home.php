<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 11/27/13
 * Time: 12:14 PM
 */
require_once ROOT_DIR  . '/Action.php';
class GroupedWork_Home extends Action{


	function launch() {
		global $interface;
		global $timer;
		global $logger;


		$id = $_REQUEST['id'];

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);
		if (!$recordDriver->isValid){
			$logger->log("Did not find a record for id {$id} in solr." , PEAR_LOG_DEBUG);
			$interface->setTemplate('invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}
		$interface->assign('recordDriver', $recordDriver);
		$timer->logTime('Initialized the Record Driver');

		// Retrieve User Search History
		$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ? $_SESSION['lastSearchURL'] : false);

		//Get Next/Previous Links
		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$searchObject->getNextPrevLinks();

		$interface->assign('sidebar', 'GroupedWork/full-record-sidebar.tpl');
		$interface->setTemplate('full-record.tpl');

		// Display Page
		$interface->display('layout.tpl');
	}
}