<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/2/13
 * Time: 3:52 PM
 */

class GroupedRecord_AJAX {
	function launch() {
		global $timer;
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");
		if (in_array($method, array('getRelatedRecords'))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else{
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}
	}

	function getRelatedRecords(){
		global $interface;
		global $configArray;
		if (isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init();
			// Setup Search Engine Connection
			$class = $configArray['Index']['engine'];
			$url = $configArray['Index']['url'];
			/** @var SearchObject_Solr $db */
			$db = new $class($url);
			if ($configArray['System']['debugSolr']) {
				$db->debug = true;
			}

			// Retrieve Full record from Solr
			if (!($record = $db->getRecord($id))) {
				PEAR_Singleton::raiseError(new PEAR_Error('Record Does Not Exist'));
			}

			$recordDriver = RecordDriverFactory::initRecordDriver($record);
			$interface->assign('relatedRecords', $recordDriver->getRelatedRecords());
			return $interface->fetch('GroupedRecord/relatedRecordPopup.tpl');
		}else{
			return "Unable to load related records";
		}
	}
} 