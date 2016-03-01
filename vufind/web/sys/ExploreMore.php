<?php

/**
 * Description goes here
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/20/2016
 * Time: 8:06 PM
 */
class ExploreMore {
	function loadExploreMoreBar($activeSection){
		if (isset($_REQUEST['page']) && $_REQUEST['page'] > 1){
			return;
		}
		//Get data from the repository
		global $interface;
		global $configArray;
		global $library;
		$exploreMoreOptions = array();

		$searchTerm = $_REQUEST['lookfor'];
		if (!$searchTerm){
			if (isset($_REQUEST['filter'])){
				foreach ($_REQUEST['filter'] as $filter){
					$filterVals = explode(':', $filter);
					$searchTerm = str_replace('"', '', $filterVals[1]);
					break;
				}
			}
		}

		//Check the archive to see if we match an entity.  Always do this since we may not get the record high in the search results.
		$exploreMoreOptions = $this->loadExactEntityMatches($exploreMoreOptions, $searchTerm);

		$exploreMoreOptions = $this->loadCatalogOptions($activeSection, $exploreMoreOptions, $searchTerm);

		if ($library->edsApiProfile && $activeSection != 'ebsco'){
			//Load EDS options
			require_once ROOT_DIR . '/sys/Ebsco/EDS_API.php';
			$edsApi = EDS_API::getInstance();
			if ($edsApi->authenticate()){
				//Find related titles
				$edsResults = $edsApi->getSearchResults($searchTerm);
				if ($edsResults){
					$numMatches = $edsResults->Statistics->TotalHits;
					if ($numMatches > 0){
						//Check results based on common facets
						foreach ($edsResults->AvailableFacets->AvailableFacet as $facetInfo){
							if ($facetInfo->Id == 'SourceType'){
								foreach ($facetInfo->AvailableFacetValues->AvailableFacetValue as $facetValue){
									$facetValueStr = (string)$facetValue->Value;
									if (in_array($facetValueStr, array('Magazines', 'News', 'Academic Journals', 'Primary Source Documents'))){
										$numFacetMatches = (int)$facetValue->Count;
										$iconName = 'ebsco_' .  str_replace(' ', '_', strtolower($facetValueStr));
										$exploreMoreOptions[] = array(
												'title' => "$facetValueStr ({$numFacetMatches})",
												'description' => "{$facetValueStr} in EBSCO related to {$searchTerm}",
												'thumbnail' => $configArray['Site']['path'] . "/interface/themes/responsive/images/{$iconName}.png",
												'link' => '/EBSCO/Results?lookfor=' . urlencode($searchTerm) . '&filter[]=' . $facetInfo->Id . ':' . $facetValueStr,
										);
									}

								}
							}
						}

						$exploreMoreOptions[] = array(
							'title' => "All EBSCO Results ({$numMatches})",
							'description' => "All Results in EBSCO related to {$searchTerm}",
							'thumbnail' => $configArray['Site']['path'] . '/interface/themes/responsive/images/ebsco_eds.png',
							'link' => '/EBSCO/Results?lookfor=' . urlencode($searchTerm)
						);
					}
				}
			}
		}

		if ($library->enableArchive && $activeSection != 'archive'){
			if (isset($configArray['Islandora']) && isset($configArray['Islandora']['solrUrl']) && !empty($_GET['lookfor']) && !is_array($_GET['lookfor'])) {
				require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
				$fedoraUtils = FedoraUtils::getInstance();

				/** @var SearchObject_Islandora $searchObject */
				$searchObject = SearchObjectFactory::initSearchObject('Islandora');
				$searchObject->init();
				$searchObject->setDebugging(false, false);

				//Get a list of objects in the archive related to this search
				$searchObject->setSearchTerms(array(
					'lookfor' => $_REQUEST['lookfor'],
					'index' => 'IslandoraKeyword'
				));
				$searchObject->clearHiddenFilters();
				$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
				$searchObject->clearFilters();
				$searchObject->addFacet('RELS_EXT_hasModel_uri_s', 'Format');
				$searchObject->addFacet('RELS_EXT_isMemberOfCollection_uri_ms', 'Collection');
				$searchObject->addFacet('mods_extension_marmotLocal_relatedEntity_person_entityPid_ms', 'People');
				$searchObject->addFacet('mods_extension_marmotLocal_relatedEntity_place_entityPid_ms', 'Places');
				$searchObject->addFacet('mods_extension_marmotLocal_relatedEntity_event_entityPid_ms', 'Events');

				$response = $searchObject->processSearch(true, false);
				if ($response && $response['response']['numFound'] > 0) {
					//Using the facets, look for related entities
					foreach ($response['facet_counts']['facet_fields']['RELS_EXT_isMemberOfCollection_uri_ms'] as $collectionInfo) {
						$archiveObject = $fedoraUtils->getObject($collectionInfo[0]);
						if ($archiveObject != null) {
							//Check the mods data to see if it should be suppressed in Pika
							$okToAdd = true;
							$mods = FedoraUtils::getInstance()->getModsData($archiveObject);
							if ($mods != null){
								if (count($mods->extension) > 0){
									/** @var SimpleXMLElement $marmotExtension */
									$marmotExtension = $mods->extension->children('http://marmot.org/local_mods_extension');
									if (count($marmotExtension) > 0) {
										$marmotLocal = $marmotExtension->marmotLocal;
										if ($marmotLocal->count() > 0) {
											$pikaOptions = $marmotLocal->pikaOptions;
											if ($pikaOptions->count() > 0) {
												$okToAdd = $pikaOptions->includeInPika != 'no';
											}
										}
									}
								}
							}else{
								//If we don't get mods, exclude from the display
								$okToAdd = false;
							}

							if ($okToAdd){
								$exploreMoreOptions[] = array(
									'title' => $archiveObject->label,
									'description' => $archiveObject->label,
									'thumbnail' => $fedoraUtils->getObjectImageUrl($archiveObject, 'small'),
									'link' => $configArray['Site']['path'] . "/Archive/{$archiveObject->id}/Exhibit",
									'usageCount' => $collectionInfo[1]
								);
							}
						}
					}

					foreach ($response['facet_counts']['facet_fields']['RELS_EXT_hasModel_uri_s'] as $relatedContentType) {
						if ($relatedContentType[0] != 'info:fedora/islandora:collectionCModel' &&
							$relatedContentType[0] != 'info:fedora/islandora:personCModel' &&
							$relatedContentType[0] != 'info:fedora/islandora:placeCModel' &&
							$relatedContentType[0] != 'info:fedora/islandora:eventCModel'
						) {

							/** @var SearchObject_Islandora $searchObject2 */
							$searchObject2 = SearchObjectFactory::initSearchObject('Islandora');
							$searchObject2->init();
							$searchObject2->setDebugging(false, false);
							$searchObject2->setSearchTerms(array(
								'lookfor' => $_REQUEST['lookfor'],
								'index' => 'IslandoraKeyword'
							));
							$searchObject2->clearHiddenFilters();
							$searchObject2->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
							$searchObject2->clearFilters();
							$searchObject2->addFilter("RELS_EXT_hasModel_uri_s:{$relatedContentType[0]}");
							$response2 = $searchObject2->processSearch(true, false);
							if ($response2 && $response2['response']['numFound'] > 0) {
								$firstObject = reset($response2['response']['docs']);
								/** @var IslandoraDriver $firstObjectDriver */
								$firstObjectDriver = RecordDriverFactory::initRecordDriver($firstObject);
								$numMatches = $response2['response']['numFound'];
								$contentType = translate($relatedContentType[0]);
								if ($numMatches == 1) {
									$exploreMoreOptions[] = array(
										'title' => "{$contentType}s ({$numMatches})",
										'description' => "{$contentType}s related to {$searchObject2->getQuery()}",
										'thumbnail' => $firstObjectDriver->getBookcoverUrl('medium'),
										'link' => $firstObjectDriver->getRecordUrl(),
									);
								} else {
									$exploreMoreOptions[] = array(
										'title' => "{$contentType}s ({$numMatches})",
										'description' => "{$contentType}s related to {$searchObject2->getQuery()}",
										'thumbnail' => $firstObjectDriver->getBookcoverUrl('medium'),
										'link' => $searchObject2->renderSearchUrl(),
									);
								}
							}
						}
					}

					if (isset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_person_entityPid_ms'])) {
						$personInfo = reset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_person_entityPid_ms']);
						$numPeople = count($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_person_entityPid_ms']);
						if ($numPeople == 100) {
							$numPeople = '100+';
						}
						$archiveObject = $fedoraUtils->getObject($personInfo[0]);
						$searchObject->clearFilters();
						$searchObject->addFilter('RELS_EXT_hasModel_uri_s:info:fedora/islandora:personCModel');
						if ($archiveObject != null) {
							$exploreMoreOptions[] = array(
								'title' => "People (" . $numPeople . ")",
								'description' => "People related to {$searchObject->getQuery()}",
								'thumbnail' => $fedoraUtils->getObjectImageUrl($archiveObject, 'small', 'personCModel'),
								'link' => '/Archive/RelatedEntities?lookfor=' . urlencode($_REQUEST['lookfor']) . '&entityType=person',
								'usageCount' => $numPeople
							);
						}
					}
					if (isset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_place_entityPid_ms'])) {
						$placeInfo = reset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_place_entityPid_ms']);
						$numPlaces = count($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_place_entityPid_ms']);
						if ($numPlaces == 100) {
							$numPlaces = '100+';
						}
						$archiveObject = $fedoraUtils->getObject($placeInfo[0]);
						$searchObject->clearFilters();
						$searchObject->addFilter('RELS_EXT_hasModel_uri_s:info:fedora/islandora:placeCModel');
						if ($archiveObject != null) {
							$exploreMoreOptions[] = array(
								'title' => "Places (" . $numPlaces . ")",
								'description' => "Places related to {$searchObject->getQuery()}",
								'thumbnail' => $fedoraUtils->getObjectImageUrl($archiveObject, 'small', 'placeCModel'),
								'link' => '/Archive/RelatedEntities?lookfor=' . urlencode($_REQUEST['lookfor']) . '&entityType=place',
								'usageCount' => $numPlaces
							);
						}
					}
					if (isset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_event_entityPid_ms'])) {
						$eventInfo = reset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_event_entityPid_ms']);
						$numEvents = count($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_event_entityPid_ms']);
						if ($numEvents == 100) {
							$numEvents = '100+';
						}
						$archiveObject = $fedoraUtils->getObject($eventInfo[0]);
						$searchObject->clearFilters();
						$searchObject->addFilter('RELS_EXT_hasModel_uri_s:info:fedora/islandora:eventCModel');
						if ($archiveObject != null) {
							$exploreMoreOptions[] = array(
								'title' => "Events (" . $numEvents . ")",
								'description' => "Places related to {$searchObject->getQuery()}",
								'thumbnail' => $fedoraUtils->getObjectImageUrl($archiveObject, 'small', 'eventCModel'),
								'link' => '/Archive/RelatedEntities?lookfor=' . urlencode($_REQUEST['lookfor']) . '&entityType=event',
								'usageCount' => $numEvents
							);
						}
					}
				}
			}

		} else {
			global $logger;
			$logger->log('Islandora Search Failed.', PEAR_LOG_WARNING);
		}

		$interface->assign('exploreMoreOptions', $exploreMoreOptions);
	}

	/**
	 * @param $exploreMoreOptions
	 * @return array
	 */
	protected function loadExactEntityMatches($exploreMoreOptions, $searchTerm) {
		global $library;
		global $configArray;
		if ($library->enableArchive) {
			if (isset($configArray['Islandora']) && isset($configArray['Islandora']['solrUrl']) && !empty($_GET['lookfor']) && !is_array($_GET['lookfor'])) {
				/** @var SearchObject_Islandora $searchObject */
				$searchObject = SearchObjectFactory::initSearchObject('Islandora');
				$searchObject->init();
				$searchObject->setDebugging(false, false);

				//First look specifically for (We cou
				$searchObject->setSearchTerms(array(
						'lookfor' => $searchTerm,
						'index' => 'IslandoraTitle'
				));
				$searchObject->clearHiddenFilters();
				$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
				//First search for people, places, and things
				$searchObject->addHiddenFilter('RELS_EXT_hasModel_uri_s', "(*placeCModel OR *personCModel OR *eventCModel)");
				$response = $searchObject->processSearch(true, false);
				if ($response && $response['response']['numFound'] > 0) {
					//Check the docs to see if we have a match for a person, place, or event
					$numProcessed = 0;
					foreach ($response['response']['docs'] as $doc) {
						$entityDriver = RecordDriverFactory::initRecordDriver($doc);
						$exploreMoreOptions[] = array(
								'title' => $entityDriver->getTitle(),
								'description' => $entityDriver->getTitle(),
								'thumbnail' => $entityDriver->getBookcoverUrl('medium'),
								'link' => $entityDriver->getRecordUrl(),
						);
						$numProcessed++;
						if ($numProcessed >= 3) {
							break;
						}
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	/**
	 * @param $activeSection
	 * @param $exploreMoreOptions
	 * @param $searchTerm
	 * @return array
	 */
	protected function loadCatalogOptions($activeSection, $exploreMoreOptions, $searchTerm) {
		global $configArray;
		if ($activeSection != 'catalog') {
			if (strlen($searchTerm) > 0) {
				/** @var SearchObject_Solr $searchObject */
				$searchObjectSolr = SearchObjectFactory::initSearchObject();
				$searchObjectSolr->init('local');
				$searchObjectSolr->setSearchTerms(array(
						'lookfor' => $searchTerm,
						'index' => 'Keyword'
				));
				$searchObjectSolr->clearHiddenFilters();
				$searchObjectSolr->clearFilters();
				$searchObjectSolr->addFilter('literary_form_full:Non Fiction');
				$searchObjectSolr->addFilter('target_audience:Adult');
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit(5);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					foreach ($results['response']['docs'] as $doc) {
						/** @var GroupedWorkDriver $driver */
						$driver = RecordDriverFactory::initRecordDriver($doc);
						$numCatalogResults = $results['response']['numFound'];
						if ($numCatalogResultsAdded == 4 && $numCatalogResults > 5) {
							//Add a link to remaining catalog results
							$exploreMoreOptions[] = array(
									'title' => "Catalog Results ($numCatalogResults)",
									'description' => "Catalog Results ($numCatalogResults)",
									'thumbnail' => $configArray['Site']['path'] . '/interface/themes/responsive/images/library_symbol.png',
									'link' => $searchObjectSolr->renderSearchUrl(),
									'usageCount' => 1
							);
						} else {
							//Add a link to the actual title
							$exploreMoreOptions[] = array(
									'title' => $driver->getTitle(),
									'description' => $driver->getTitle(),
									'thumbnail' => $driver->getBookcoverUrl('small'),
									'link' => $driver->getLinkUrl(),
									'usageCount' => 1
							);
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}
}