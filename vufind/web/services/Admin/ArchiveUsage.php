<?php
/**
 * Display a report of usage based on namespace for each library connected to the archive
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/4/2017
 * Time: 8:19 AM
 */
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/ArchiveSubject.php';
class Admin_ArchiveUsage extends Admin_Admin{

	function launch() {
		global $interface;

		$archiveLibraries = new Library();
		$archiveLibraries->whereAdd("archiveNamespace != ''");
		$archiveLibraries->orderBy('displayName');
		$archiveLibraries->find();

		$usageByNamespace = array();

		while ($archiveLibraries->fetch()){
			/** @var SearchObject_Islandora $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Islandora');
			$searchObject->init();
			$searchObject->setDebugging(false, false);
			$searchObject->setLimit(250);
			$searchObject->clearFilters();
			$searchObject->clearHiddenFilters();

			$searchObject->setBasicQuery($archiveLibraries->archiveNamespace, 'namespace_s');
			$searchObject->addFieldsToReturn(array('fedora_datastream_latest_OBJ_SIZE_ms'));
			$searchObject->setApplyStandardFilters(false);

			$usageByNamespace[$archiveLibraries->ilsCode] = array(
					'displayName' => $archiveLibraries->displayName,
					'numObjects' => 0,
					'driveSpace' => 0
			);

			$response = $searchObject->processSearch(true, false);
			if ($response && $response['response']['numFound'] > 0) {
				$numProcessed = 0;
				$usageByNamespace[$archiveLibraries->ilsCode]['numObjects'] = $response['response']['numFound'];
				while ($numProcessed < $response['response']['numFound']){
					foreach ($response['response']['docs'] as $doc){
						if (isset ($doc['fedora_datastream_latest_OBJ_SIZE_ms'])){
							$usageByNamespace[$archiveLibraries->ilsCode]['driveSpace'] += $doc['fedora_datastream_latest_OBJ_SIZE_ms'][0];
						}
						$numProcessed++;
					}
					if ($numProcessed < $response['response']['numFound']){
						$searchObject->setPage($searchObject->getPage() + 1);
						$response = $searchObject->processSearch(true, false);
					}
				}

			}

			$diskSpace = $usageByNamespace[$archiveLibraries->ilsCode]['driveSpace'];
			if ($diskSpace > 100000000){
				$usageByNamespace[$archiveLibraries->ilsCode]['driveSpace'] = ceil($usageByNamespace[$archiveLibraries->ilsCode]['driveSpace'] * 0.000000001) . ' GB';
			}else{
				$usageByNamespace[$archiveLibraries->ilsCode]['driveSpace'] = ceil($usageByNamespace[$archiveLibraries->ilsCode]['driveSpace'] * 0.000001) . ' MB';
			}
		}

		$interface->assign('usageByNamespace', $usageByNamespace);

		$this->display('archiveUsage.tpl', 'Archive Usage By Library');
	}

	function getAllowableRoles() {
		return array('opacAdmin', 'archives');
	}
}