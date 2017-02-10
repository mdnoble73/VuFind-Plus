<?php

/**
 * A home page for the archive displaying all available projects as well as links to content by
 * content type
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/27/2016
 * Time: 8:26 AM
 */
require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
class Archive_Home extends Action{

	function launch() {
		global $interface;
		global $timer;
		global $library;

		//Get a list of all available projects
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->setDebugging(false, false);
		$searchObject->clearHiddenFilters();
		$searchObject->addHiddenFilter('fedora_datastreams_ms', 'MODS');
		$searchObject->addHiddenFilter('RELS_EXT_hasModel_uri_s', '*collectionCModel');
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_includeInPika_ms', "no");
		$searchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
		$searchObject->addHiddenFilter('mods_extension_marmotLocal_pikaOptions_showOnPikaArchiveHomepage_ms', "yes");
		$searchObject->addHiddenFilter('RELS_EXT_isMemberOfCollection_uri_ms', "info\\:fedora/{$library->archiveNamespace}\\:*");
		$searchObject->setLimit(50);
		$searchObject->setSort('fgs_label_s');
		$timer->logTime('Setup Search');

		$response = $searchObject->processSearch(true, true);
		if (PEAR_Singleton::isError($response)) {
			PEAR_Singleton::raiseError($response->getMessage());
		}
		$timer->logTime('Process Search for collections');

		$relatedProjects = array();
		if ($response && isset($response['response'])){
			//Get information about each project
			if ($searchObject->getResultTotal() > 0){
				$summary = $searchObject->getResultSummary();
				$interface->assign('recordCount', $summary['resultTotal']);
				$interface->assign('recordStart', $summary['startRecord']);
				$interface->assign('recordEnd',   $summary['endRecord']);

				foreach ($response['response']['docs'] as $objectInCollection){
					$firstObjectDriver = RecordDriverFactory::initRecordDriver($objectInCollection);
					$relatedProjects[] = array(
						'title' => $firstObjectDriver->getTitle(),
						'description' => $firstObjectDriver->getDescription(),
						'image' => $firstObjectDriver->getBookcoverUrl('small'),
						'dateCreated' => $firstObjectDriver->getDateCreated(),
						'link' => $firstObjectDriver->getRecordUrl(),
						'pid' => $firstObjectDriver->getUniqueID()
					);
					$timer->logTime('Loaded related object');
				}
			}
		}
		$interface->assign('relatedProjects', $relatedProjects);

		//Get a list of content types and count the number of objects per content type
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->setDebugging(false, false);
		$searchObject->clearHiddenFilters();
		$searchObject->addHiddenFilter('fedora_datastreams_ms', 'MODS');
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_includeInPika_ms', "no");
		$searchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
		$searchObject->addFacet('mods_genre_s');
		$searchObject->setLimit(1);
		$searchObject->setSort('fgs_label_s');

		if ($library->hideAllCollectionsFromOtherLibraries){
			$searchObject->addHiddenFilter('PID', $library->archiveNamespace . '*');
		}
		$timer->logTime('Setup Search');

		$response = $searchObject->processSearch(true, true);
		if (PEAR_Singleton::isError($response)) {
			PEAR_Singleton::raiseError($response->getMessage());
		}
		$timer->logTime('Process Search for related content types');

		$relatedContentTypes = array();
		if ($response && isset($response['response'])){
			foreach ($response['facet_counts']['facet_fields']['mods_genre_s'] as $genre) {
				/** @var SearchObject_Islandora $searchObject2 */
				$searchObject2 = SearchObjectFactory::initSearchObject('Islandora');
				$searchObject2->init();
				$searchObject2->setDebugging(false, false);
				$searchObject2->clearHiddenFilters();
				$searchObject2->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
				$searchObject2->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_includeInPika_ms', "no");
				$searchObject2->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
				$searchObject2->clearFilters();
				$searchObject2->addFilter("mods_genre_s:{$genre[0]}");
				$response2 = $searchObject2->processSearch(true, false);
				if ($response2 && $response2['response']['numFound'] > 0) {
					$firstObject = reset($response2['response']['docs']);
					/** @var IslandoraDriver $firstObjectDriver */
					$firstObjectDriver = RecordDriverFactory::initRecordDriver($firstObject);
					$numMatches = $response2['response']['numFound'];
					$contentType = ucwords($genre[0]);
					if ($numMatches == 1) {
						$relatedContentTypes[] = array(
								'title' => "{$contentType} ({$numMatches})",
								'description' => "{$contentType} related to this",
								'image' => $firstObjectDriver->getBookcoverUrl('medium'),
								'link' => $firstObjectDriver->getRecordUrl(),
						);
					} else {
						$relatedContentTypes[] = array(
								'title' => "{$contentType}s ({$numMatches})",
								'description' => "{$contentType}s related to this",
								'image' => $firstObjectDriver->getBookcoverUrl('medium'),
								'link' => $searchObject2->renderSearchUrl(),
						);
					}
				}
			}

		}
		$interface->assign('showExploreMore', false);
		$interface->assign('relatedContentTypes', $relatedContentTypes);
		$this->endExhibitContext();

		parent::display('home.tpl', $library->displayName . ' Digital Collection');
	}

	protected function endExhibitContext()
	{
		$_SESSION['ExhibitContext']  = null;
		$_SESSION['exhibitSearchId'] = null;
		$_SESSION['placePid']        = null;
		$_SESSION['dateFilter']      = null;
	}

}