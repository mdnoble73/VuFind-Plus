<?php
/**
 * Description goes here
 *
 * @category Pika
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
			$interface->setTemplate('../Record/invalidRecord.tpl');
			$this->display('../Record/invalidRecord.tpl', 'Error');
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

		$interface->assign('moreDetailsOptions', $recordDriver->getMoreDetailsOptions());
		$exploreMoreInfo = $recordDriver->getExploreMoreInfo();
		$interface->assign('exploreMoreInfo', $exploreMoreInfo);

		$interface->assign('metadataTemplate', 'GroupedWork/metadata.tpl');

		$interface->assign('semanticData', json_encode($recordDriver->getSemanticData()));

		// Display Page

//		global $configArray;
//		if ($configArray['Catalog']['showExploreMoreForFullRecords']) {
//			$interface->assign('showExploreMore', true);
//		}
		// above is done in $recordDriver->getExploreMoreInfo() plb 2-25-2016

		$this->display('full-record.tpl', $recordDriver->getTitle());
	}


}