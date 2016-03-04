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

		if ($this->archiveObject->getDatastream('BANNER') != null) {
			$interface->assign('main_image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/BANNER/view");
		}

		$interface->assign('showExploreMore', true);

		// Display Page
		$this->display('exhibit.tpl');
	}

	function loadExploreMoreContent(){
		global $interface;
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->setDebugging(false, false);
		$searchObject->clearHiddenFilters();
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->clearFilters();
		$searchObject->addFilter("RELS_EXT_isMemberOfCollection_uri_ms:\"info:fedora/{$this->pid}\"");

		$relatedImages = array();
		$response = $searchObject->processSearch(true, false);
		if ($response && $response['response']['numFound'] > 0) {
			foreach ($response['response']['docs'] as $objectInCollection){
				/** @var IslandoraDriver $firstObjectDriver */
				$firstObjectDriver = RecordDriverFactory::initRecordDriver($objectInCollection);
				$relatedImages[] = array(
						'title' => $firstObjectDriver->getTitle(),
						'description' => "Update me",
						'thumbnail' => $firstObjectDriver->getBookcoverUrl('medium'),
						'link' => $firstObjectDriver->getRecordUrl(),
				);
			}

		}

		$interface->assign('relatedImages', $relatedImages);
	}
}