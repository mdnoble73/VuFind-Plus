<?php

/**
 * Displays Information about Digital Repository (Islandora) Entity
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 3/22/2016
 * Time: 11:15 AM
 */
require_once ROOT_DIR . '/services/Archive/Object.php';
abstract class Archive_Entity extends Archive_Object {
	function loadRelatedContentForEntity(){
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/Solr.php';

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/Solr.php';

		// Initialise from the current search globals
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		//$searchObject->addHiddenFilter('-RELS_EXT_hasModel_uri_s', '*collectionCModel');
		$searchObject->setSearchTerms(array(
			'lookfor' => '"' . $this->pid . '"',
			'index' => 'IslandoraPeopleById'
		));
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");

		$searchObject->clearHiddenFilters();
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->clearFilters();
		$searchObject->addFacet('RELS_EXT_hasModel_uri_s', 'Format');

		$response = $searchObject->processSearch(true, false);
		if ($response && $response['response']['numFound'] > 0) {
			$directlyRelatedObjects = array(
					'numFound' => $response['response']['numFound'],
					'objects' => array(),
			);
			$directlyRelatedObjects['numFound'] = $response['response']['numFound'];
			foreach ($response['response']['docs'] as $doc) {
				$entityDriver = RecordDriverFactory::initRecordDriver($doc);
				$directlyRelatedObjects['objects'][] = array(
						'title' => $entityDriver->getTitle(),
						'description' => $entityDriver->getTitle(),
						'thumbnail' => $entityDriver->getBookcoverUrl('medium'),
						'link' => $entityDriver->getRecordUrl(),
				);
			}

			global $interface;
			$interface->assign('directlyRelatedObjects', $directlyRelatedObjects);
		}
	}
}