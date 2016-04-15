<?php

/**
 * Contains functionality to load content related to a search or to another object
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/20/2016
 * Time: 8:06 PM
 */
class ExploreMore {
	private $relatedCollections;

	/**
	 * @param string $activeSection
	 * @param IndexRecord $recordDriver
	 */
	function loadExploreMoreSidebar($activeSection, $recordDriver){
		global $interface;
		$exploreMoreSectionsToShow = array();

		$relatedPikaContent = array();
		if ($activeSection == 'archive'){
			/** @var IslandoraDriver $archiveDriver */
			$archiveDriver = $recordDriver;
			$this->relatedCollections = $archiveDriver->getRelatedCollections();
			if (count($this->relatedCollections) > 0){
				$exploreMoreSectionsToShow['relatedCollections'] = array(
						'title' => 'Related Collections',
						'format' => 'list',
						'values' => $this->relatedCollections
				);
			}

			//Find content from the catalog that is directly related to the object or collection based on linked data
			$relatedPikaContent = $archiveDriver->getRelatedPikaContent();
			if (count($relatedPikaContent) > 0){
				$exploreMoreSectionsToShow['linkedCatalogRecords'] = array(
						'title' => 'From the Catalog',
						'format' => 'scroller',
						'values' => $relatedPikaContent
				);
			}

			//Find other entities
		}

		//Get subjects that can be used for searching other systems
		$subjects = $recordDriver->getAllSubjectHeadings();
		$subjectsForSearching = array();
		$quotedSubjectsForSearching = array();
		foreach ($subjects as $subject){
			if (is_array($subject)){
				$searchSubject =  implode(" ", $subject);
			}else{
				$searchSubject = $subject;
			}
			$searchSubject = preg_replace('/\(.*?\)/',"", $searchSubject);
			$searchSubject = trim(preg_replace('/[\/|:.,"]/',"", $searchSubject));
			$subjectsForSearching[] = $searchSubject;
			$quotedSubjectsForSearching[] = '"' . $searchSubject . '"';
		}

		$subjectsForSearching = array_slice($subjectsForSearching, 0, 5);
		$searchTerm = implode(' or ', $subjectsForSearching);
		$quotedSearchTerm = implode(' OR ', $quotedSubjectsForSearching);

		//Get objects from the archive based on search subjects
		if ($activeSection != 'archive'){
			foreach ($subjectsForSearching as $curSubject){
				$exactEntityMatches = $this->loadExactEntityMatches(array(), $curSubject);
				if (count($exactEntityMatches) > 0){
					$exploreMoreSectionsToShow['exactEntityMatches'] = array(
							'title' => 'Related People, Places &amp; Events',
							'format' => 'list',
							'values' => usort($exactEntityMatches, 'ExploreMore::sortRelatedEntities')
					);
				}
			}
		}

		//Always load ebsco even if we are already in that section
		$ebscoMatches = $this->loadEbscoOptions('', array(), $searchTerm);
		if (count($ebscoMatches) > 0){
			$interface->assign('relatedArticles', $ebscoMatches);
		}

		//Load related content from the archive

		if ($activeSection == 'archive'){
			/** @var IslandoraDriver $archiveDriver */
			$archiveDriver = $recordDriver;
			$relatedArchiveEntities = $this->getRelatedArchiveEntities($archiveDriver);
			if (count($relatedArchiveEntities) > 0){
				if (isset($relatedArchiveEntities['people'])){
					usort($relatedArchiveEntities['people'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedPeople'] = array(
							'title' => 'Associated People',
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['people']
					);
				}
				if (isset($relatedArchiveEntities['places'])){
					usort($relatedArchiveEntities['places'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedPlaces'] = array(
							'title' => 'Associated Places',
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['places']
					);
				}
				if (isset($relatedArchiveEntities['organizations'])){
					usort($relatedArchiveEntities['organizations'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedOrganizations'] = array(
							'title' => 'Associated Organizations',
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['organizations']
					);
				}
				if (isset($relatedArchiveEntities['events'])){
					usort($relatedArchiveEntities['events'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedEvents'] = array(
							'title' => 'Associated Events',
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['events']
					);
				}
			}
		}

		$relatedArchiveContent = $this->getRelatedArchiveObjects($quotedSearchTerm);
		if (count($relatedArchiveContent) > 0){
			$exploreMoreSectionsToShow['relatedArchiveData'] = array(
					'title' => 'From the Archive',
					'format' => 'subsections',
					'values' => $relatedArchiveContent
			);
		}

		if ($activeSection != 'catalog'){
			$relatedWorks = $this->getRelatedWorks($quotedSubjectsForSearching, $relatedPikaContent);
			if ($relatedWorks['numFound'] > 0){
				$exploreMoreSectionsToShow['relatedCatalog'] = array(
						'title' => 'More From the Catalog',
						'format' => 'scrollerWithLink',
						'values' => $relatedWorks['values'],
						'link' => $relatedWorks['link'],
						'numFound' => $relatedWorks['numFound'],
				);
			}
		}

		if ($activeSection == 'archive'){
			/** @var IslandoraDriver $archiveDriver */
			$archiveDriver = $recordDriver;
			if ($archiveDriver->isEntity()){
				require_once ROOT_DIR . '/sys/SearchObject/DPLA.php';
				$dpla = new DPLA();
				//Check to see if we get any results from DPLA for this entity
				$dplaResults = $dpla->getDPLAResults('"' . $archiveDriver->getTitle() . '"');
				if (count($dplaResults)){
					$exploreMoreSectionsToShow['relatedCatalog'] = array(
							'title' => 'Digital Public Library of America',
							'format' => 'scrollerWithLink',
							'values' => $dplaResults,
							'link' => 'http://dp.la/search?q=' . urlencode('"' . $archiveDriver->getTitle() . '"'),
							'openInNewWindow' => true,
					);
				}
			}
		}

		$interface->assign('exploreMoreSections', $exploreMoreSectionsToShow);
	}

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

		$exploreMoreOptions = $this->loadEbscoOptions($activeSection, $exploreMoreOptions, $searchTerm);

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
									if ($marmotExtension->count() > 0) {
										/** @var SimpleXMLElement $marmotLocal */
										$marmotLocal = $marmotExtension->marmotLocal;
										if ($marmotLocal->count() > 0) {
											/** @var SimpleXMLElement $pikaOptions */
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
									'image' => $fedoraUtils->getObjectImageUrl($archiveObject, 'small'),
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
										'image' => $firstObjectDriver->getBookcoverUrl('medium'),
										'link' => $firstObjectDriver->getRecordUrl(),
									);
								} else {
									$exploreMoreOptions[] = array(
										'title' => "{$contentType}s ({$numMatches})",
										'description' => "{$contentType}s related to {$searchObject2->getQuery()}",
										'image' => $firstObjectDriver->getBookcoverUrl('medium'),
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
								'image' => $fedoraUtils->getObjectImageUrl($archiveObject, 'small', 'personCModel'),
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
								'image' => $fedoraUtils->getObjectImageUrl($archiveObject, 'small', 'placeCModel'),
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
								'image' => $fedoraUtils->getObjectImageUrl($archiveObject, 'small', 'eventCModel'),
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
	 * @param string $searchTerm
	 * @return array
	 */
	protected function loadExactEntityMatches($exploreMoreOptions, $searchTerm) {
		global $library;
		global $configArray;
		if ($library->enableArchive) {
			if (isset($configArray['Islandora']) && isset($configArray['Islandora']['solrUrl']) && $searchTerm) {
				/** @var SearchObject_Islandora $searchObject */
				$searchObject = SearchObjectFactory::initSearchObject('Islandora');
				$searchObject->init();
				$searchObject->setDebugging(false, false);

				//First look specifically for
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
								'label' => $entityDriver->getTitle(),
								'image' => $entityDriver->getBookcoverUrl('medium'),
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
									'image' => $configArray['Site']['path'] . '/interface/themes/responsive/images/library_symbol.png',
									'link' => $searchObjectSolr->renderSearchUrl(),
									'usageCount' => 1
							);
						} else {
							//Add a link to the actual title
							$exploreMoreOptions[] = array(
									'title' => $driver->getTitle(),
									'description' => $driver->getTitle(),
									'image' => $driver->getBookcoverUrl('small'),
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

	/**
	 * @param $activeSection
	 * @param $searchTerm
	 * @param $exploreMoreOptions
	 * @return array
	 */
	public function loadEbscoOptions($activeSection, $exploreMoreOptions, $searchTerm) {
		global $library;
		global $configArray;
		if ($library->edsApiProfile && $activeSection != 'ebsco') {
			//Load EDS options
			require_once ROOT_DIR . '/sys/Ebsco/EDS_API.php';
			$edsApi = EDS_API::getInstance();
			if ($edsApi->authenticate()) {
				//Find related titles
				$edsResults = $edsApi->getSearchResults($searchTerm);
				if ($edsResults) {
					$numMatches = $edsResults->Statistics->TotalHits;
					if ($numMatches > 0) {
						//Check results based on common facets
						foreach ($edsResults->AvailableFacets->AvailableFacet as $facetInfo) {
							if ($facetInfo->Id == 'SourceType') {
								foreach ($facetInfo->AvailableFacetValues->AvailableFacetValue as $facetValue) {
									$facetValueStr = (string)$facetValue->Value;
									if (in_array($facetValueStr, array('Magazines', 'News', 'Academic Journals', 'Primary Source Documents'))) {
										$numFacetMatches = (int)$facetValue->Count;
										$iconName = 'ebsco_' . str_replace(' ', '_', strtolower($facetValueStr));
										$exploreMoreOptions[] = array(
												'title' => "$facetValueStr ({$numFacetMatches})",
												'description' => "{$facetValueStr} in EBSCO related to {$searchTerm}",
												'image' => $configArray['Site']['path'] . "/interface/themes/responsive/images/{$iconName}.png",
												'link' => '/EBSCO/Results?lookfor=' . urlencode($searchTerm) . '&filter[]=' . $facetInfo->Id . ':' . $facetValueStr,
										);
									}

								}
							}
						}

						$exploreMoreOptions[] = array(
								'title' => "All EBSCO Results ({$numMatches})",
								'description' => "All Results in EBSCO related to {$searchTerm}",
								'image' => $configArray['Site']['path'] . '/interface/themes/responsive/images/ebsco_eds.png',
								'link' => '/EBSCO/Results?lookfor=' . urlencode($searchTerm)
						);
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	function loadExploreMoreContent(){
		require_once ROOT_DIR . '/sys/ArchiveSubject.php';
		$archiveSubjects = new ArchiveSubject();
		$subjectsToIgnore = array();
		$subjectsToRestrict = array();
		if ($archiveSubjects->find(true)){
			$subjectsToIgnore = array_flip(explode("\r\n", strtolower($archiveSubjects->subjectsToIgnore)));
			$subjectsToRestrict = array_flip(explode("\r\n", strtolower($archiveSubjects->subjectsToRestrict)));
		}
		$this->getRelatedCollections();
		$relatedSubjects = array();
		$numSubjectsAdded = 0;
		if (strlen($this->archiveObject->label) > 0) {
			$relatedSubjects[$this->archiveObject->label] = '"' . $this->archiveObject->label . '"';
		}
		for ($i = 0; $i < 2; $i++){
			foreach ($this->formattedSubjects as $subject) {
				$lowerSubject = strtolower($subject['label']);
				if (!array_key_exists($lowerSubject, $subjectsToIgnore)) {
					if ($i == 0){
						//First pass, just add primary subjects
						if (!array_key_exists($lowerSubject, $subjectsToRestrict)) {
							$relatedSubjects[$lowerSubject] = '"' . $subject['label'] . '"';
						}
					}else{
						//Second pass, add restricted subjects, but only if we don't have 5 subjects already
						if (array_key_exists($lowerSubject, $subjectsToRestrict) && count($relatedSubjects) <= 5) {
							$relatedSubjects[$lowerSubject] = '"' . $subject['label'] . '"';
						}
					}
				}
			}
		}
		$relatedSubjects = array_slice($relatedSubjects, 0, 5);
		foreach ($this->relatedPeople as $person) {
			$label = (string)$person['label'];
			$relatedSubjects[$label] = '"' . $label . '"';
			$numSubjectsAdded++;
		}
		$relatedSubjects = array_slice($relatedSubjects, 0, 8);

		$exploreMore = new ExploreMore();

		$exploreMore->loadEbscoOptions('archive', array(), implode($relatedSubjects, " or "));
		$searchTerm = implode(" OR ", $relatedSubjects);
		$exploreMore->getRelatedArchiveObjects($searchTerm);
	}

	public function getRelatedArchiveObjects($searchTerm) {
		$relatedArchiveContent = array();

		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->setDebugging(false, false);

		//Get a list of objects in the archive related to this search
		$searchObject->setSearchTerms(array(
				'lookfor' => $searchTerm,
				'index' => 'IslandoraKeyword'
		));
		$searchObject->clearHiddenFilters();
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->clearFilters();
		$searchObject->addFacet('RELS_EXT_hasModel_uri_s', 'Format');

		$response = $searchObject->processSearch(true, false);
		if ($response && $response['response']['numFound'] > 0) {
			//Using the facets, look for related entities
			foreach ($response['facet_counts']['facet_fields']['RELS_EXT_hasModel_uri_s'] as $relatedContentType) {
				if ($relatedContentType[0] != 'info:fedora/islandora:collectionCModel' &&
						$relatedContentType[0] != 'info:fedora/islandora:personCModel' &&
						$relatedContentType[0] != 'info:fedora/islandora:placeCModel' &&
						$relatedContentType[0] != 'info:fedora/islandora:organizationCModel' &&
						$relatedContentType[0] != 'info:fedora/islandora:eventCModel'
				) {

					/** @var SearchObject_Islandora $searchObject2 */
					$searchObject2 = SearchObjectFactory::initSearchObject('Islandora');
					$searchObject2->init();
					$searchObject2->setDebugging(false, false);
					$searchObject2->setSearchTerms(array(
							'lookfor' => $searchTerm,
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
							$relatedArchiveContent[] = array(
									'title' => $firstObjectDriver->getTitle(),
									'description' => $firstObjectDriver->getTitle(),
									'image' => $firstObjectDriver->getBookcoverUrl('medium'),
									'link' => $firstObjectDriver->getRecordUrl(),
							);
						} else {
							$relatedArchiveContent[] = array(
									'title' => "{$contentType}s ({$numMatches})",
									'description' => "{$contentType}s related to this",
									'image' => $firstObjectDriver->getBookcoverUrl('medium'),
									'link' => $searchObject2->renderSearchUrl(),
							);
						}
					}
				}
			}
		}
		return $relatedArchiveContent;
	}

	/**
	 * Load entities that are related to this entity but that are not directly related.
	 * I.e. we want to see
	 *
	 * @param IslandoraDriver $archiveDriver
	 * @return array
	 */
	public function getRelatedArchiveEntities($archiveDriver){
		$directlyRelatedPeople = $archiveDriver->getRelatedPeople();
		$directlyRelatedPlaces = $archiveDriver->getRelatedPlaces();
		$directlyRelatedOrganizations = $archiveDriver->getRelatedOrganizations();
		$directlyRelatedEvents = $archiveDriver->getRelatedEvents();

		$relatedPeople = array();
		$relatedPlaces = array();
		$relatedOrganizations = array();
		$relatedEvents = array();
		$relatedObjects = $archiveDriver->getDirectlyLinkedArchiveObjects();

		foreach ($relatedObjects['objects'] as $object){
			/** @var IslandoraDriver $objectDriver */
			$objectDriver = $object['driver'];

			$peopleRelatedToObject = $objectDriver->getRelatedPeople();
			foreach($peopleRelatedToObject as $entity){
				if ($entity['pid'] != $archiveDriver->getUniqueID() && !array_key_exists($entity['pid'], $directlyRelatedPeople)){
					$relatedPeople = $this->addAssociatedEntity($entity, $relatedPeople, $objectDriver);
				}
			}

			$placesRelatedToObject = $objectDriver->getRelatedPlaces();
			foreach($placesRelatedToObject as $entity){
				if ($entity['pid'] != $archiveDriver->getUniqueID() && !array_key_exists($entity['pid'], $directlyRelatedPlaces)){
					$relatedPlaces = $this->addAssociatedEntity($entity, $relatedPlaces, $objectDriver);
				}
			}

			$organizationsRelatedToObject = $objectDriver->getRelatedOrganizations();
			foreach($organizationsRelatedToObject as $entity){
				if ($entity['pid'] != $archiveDriver->getUniqueID() && !array_key_exists($entity['pid'], $directlyRelatedOrganizations)){
					$relatedOrganizations = $this->addAssociatedEntity($entity, $relatedOrganizations, $objectDriver);
				}
			}

			$eventsRelatedToObject = $objectDriver->getRelatedEvents();
			foreach($eventsRelatedToObject as $entity){
				if ($entity['pid'] != $archiveDriver->getUniqueID() && !array_key_exists($entity['pid'], $directlyRelatedEvents)){
					$relatedEvents = $this->addAssociatedEntity($entity, $relatedEvents, $objectDriver);
				}
			}
		}

		$relatedEntities = array();
		if (count($relatedPeople) > 0){
			$relatedEntities['people'] = $relatedPeople;
		}
		if (count($relatedPlaces) > 0){
			$relatedEntities['places'] = $relatedPlaces;
		}
		if (count($relatedOrganizations) > 0){
			$relatedEntities['organizations'] = $relatedOrganizations;
		}
		if (count($relatedEvents) > 0){
			$relatedEntities['events'] = $relatedEvents;
		}
		return $relatedEntities;
	}

	/**
	 * @param string[] $relatedSubjects
	 * @param array    $directlyRelatedRecords
	 *
	 * @return array
	 */
	public function getRelatedWorks($relatedSubjects, $directlyRelatedRecords) {
		//Load related catalog content
		$searchTerm = implode(" OR ", $relatedSubjects);

		$similarTitles = array(
				'numFound' => 0,
				'link' => '',
				'values' => array()
		);

		if (strlen($searchTerm) > 0) {
			//Blacklist any records that we have specific links to
			$recordsToAvoid = '';
			foreach ($directlyRelatedRecords as $record){
				if (strlen($recordsToAvoid) > 0){
					$recordsToAvoid .= ' OR ';
				}
				$recordsToAvoid .= $record['id'];
			}
			if (strlen($recordsToAvoid) > 0){
				$searchTerm .= " AND NOT id:($recordsToAvoid)";
			}

			/** @var SearchObject_Solr $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init('local', $searchTerm);
			$searchObject->setSearchTerms(array(
					'lookfor' => $searchTerm,
					'index' => 'Keyword'
			));
			$searchObject->addFilter('literary_form_full:Non Fiction');
			$searchObject->addFilter('target_audience:(Adult OR Unknown)');

			$searchObject->setPage(1);
			$searchObject->setLimit(5);
			$results = $searchObject->processSearch(true, false);

			if ($results && isset($results['response'])) {
				$similarTitles = array(
						'numFound' => $results['response']['numFound'],
						'link' => $searchObject->renderSearchUrl(),
						'topHits' => array()
				);
				foreach ($results['response']['docs'] as $doc) {
					/** @var GroupedWorkDriver $driver */
					$driver = RecordDriverFactory::initRecordDriver($doc);
					$similarTitle = array(
							'label' => $driver->getTitle(),
							'link' => $driver->getLinkUrl(),
							'image' => $driver->getBookcoverUrl('medium')
					);
					$similarTitles['values'][] = $similarTitle;
				}
			}
		}
		return $similarTitles;
	}

	private function addAssociatedEntity($entity, $relatedEntities, $objectDriver) {
		if (!isset($relatedEntities[$entity['pid']])){
			$relatedEntities[$entity['pid']] = $entity;
			if (!isset($relatedEntities[$entity['pid']]['linkingReason'])) {
				$relatedEntities[$entity['pid']]['linkingReason'] = "Both link to: ";
			}
		}

		if (strpos($relatedEntities[$entity['pid']]['linkingReason'], "\r\n - " . $objectDriver->getTitle()) === false){
			$relatedEntities[$entity['pid']]['linkingReason'] .= "\r\n - " . $objectDriver->getTitle();
		}

		return $relatedEntities;
	}

	static function sortRelatedEntities($a, $b){
		return strcasecmp($a["label"], $b["label"]);
	}
}

