<?php
/**
 * Displays Information about Digital Repository (Islandora) Exhibit
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/7/2015
 * Time: 7:55 AM
 */

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Exhibit extends Archive_Object{
	function launch(){
		global $interface;
		global $configArray;

		$this->loadArchiveObjectData();
		$this->loadExploreMoreContent();

		$this->loadRelatedObjects();

		if ($this->archiveObject->getDatastream('BANNER') != null) {
			$interface->assign('main_image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/BANNER/view");
		}

		if ($this->archiveObject->getDatastream('TN') != null) {
			$interface->assign('thumbnail', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/TN/view");
		}

		$interface->assign('showExploreMore', true);

		$displayType = 'basic';
		if ($this->pid == 'evld:localHistoryArchive'){
			$displayType = 'timeline';
		}
		// Determine what type of page to show
		if ($displayType == 'basic'){
			$this->display('exhibit.tpl');
		} else if ($displayType == 'timeline'){
			$this->display('timeline.tpl');
		}
	}

	function loadRelatedObjects(){
		global $interface;
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->setDebugging(false, false);
		$searchObject->clearHiddenFilters();
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->clearFilters();
		$searchObject->addFilter("RELS_EXT_isMemberOfCollection_uri_ms:\"info:fedora/{$this->pid}\"");
		$searchObject->setLimit(48);

		$relatedImages = array();
		$response = $searchObject->processSearch(true, false);
		if ($response && $response['response']['numFound'] > 0) {
			foreach ($response['response']['docs'] as $objectInCollection){
				/** @var IslandoraDriver $firstObjectDriver */
				$firstObjectDriver = RecordDriverFactory::initRecordDriver($objectInCollection);
				$relatedImages[] = array(
						'title' => $firstObjectDriver->getTitle(),
						'description' => "Update me",
						'image' => $firstObjectDriver->getBookcoverUrl('medium'),
						'link' => $firstObjectDriver->getRecordUrl(),
				);
			}

		}

		$interface->assign('relatedImages', $relatedImages);
	}
}