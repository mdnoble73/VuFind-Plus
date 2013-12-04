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
class GroupedRecord_Home extends Action{


	function launch() {
		global $interface;
		global $timer;
		global $logger;
		global $configArray;

		$id = $_REQUEST['id'];

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		/** @var Solr $db */
		$db = new $class($url);
		$db->disableScoping();

		// Retrieve Full Marc Record
		if (!($record = $db->getRecord($id))) {
			$logger->log("Did not find a record for id {$id} in solr." , PEAR_LOG_DEBUG);
			$interface->setTemplate('invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}
		$db->enableScoping();

		$recordDriver = RecordDriverFactory::initRecordDriver($record);
		$interface->assign('recordDriver', $recordDriver);
		$timer->logTime('Initialized the Record Driver');


		$interface->setTemplate('full-record.tpl');

		// Display Page
		$interface->display('layout.tpl');
	}
}