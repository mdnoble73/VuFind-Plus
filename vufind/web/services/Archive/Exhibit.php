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
		global $timer;

		$fedoraUtils = FedoraUtils::getInstance();

		$this->loadArchiveObjectData();
		$timer->logTime('Loaded Archive Object Data');
		//$this->loadExploreMoreContent();
		$timer->logTime('Loaded Explore More Content');

		if (isset($_REQUEST['style'])){
			$pikaCollectionDisplay = $_REQUEST['style'];
		}else{
			$pikaCollectionDisplay = $this->recordDriver->getModsValue('pikaCollectionDisplay', 'marmot');
		}

		$repositoryLink = $configArray['Islandora']['repositoryUrl'] . '/islandora/object/' . $this->recordDriver->getUniqueID();
		$interface->assign('repositoryLink', $repositoryLink);

		$description = html_entity_decode($this->recordDriver->getDescription());
		$description = str_replace("\r\n", '<br/>', $description);
		$description = str_replace("&#xD;", '<br/>', $description);
		if (strlen($description)){
			$interface->assign('description', $description);
		}

		$displayType = 'basic';
		if ($pikaCollectionDisplay == 'map'){
			$displayType = 'map';
			$mapZoom = $this->recordDriver->getModsValue('mapZoomLevel', 'marmot');
			$interface->assign('mapZoom', $mapZoom);
		}elseif ($pikaCollectionDisplay == 'custom'){
			$displayType = 'custom';
			//Load the options to show
			$collectionOptionsRaw = $this->recordDriver->getModsValue('collectionOptions', 'marmot');
			$collectionOptions = explode("\r\n", html_entity_decode($collectionOptionsRaw));
		}elseif ($pikaCollectionDisplay == 'timeline'){
			$displayType = 'timeline';
		}
		$interface->assign('displayType', $displayType);
		$this->loadRelatedObjects($displayType);
		$timer->logTime('Loaded Related Objects');

		if ($this->archiveObject->getDatastream('BANNER') != null) {
			$interface->assign('main_image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/BANNER/view");
		}

		if ($this->archiveObject->getDatastream('TN') != null) {
			$interface->assign('thumbnail', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/TN/view");
		}

		//Get a list of sub collections to for searching
		$subCollections = $this->recordDriver->getSubCollections();
		$interface->assign('subCollections', $subCollections);

		$interface->assign('showExploreMore', true);

		$imageMapPID = $this->recordDriver->getModsValue('imageMapPID', 'marmot');
		if ($imageMapPID != null && strlen($imageMapPID) > 0){
			$interface->assign('hasImageMap', true);
			$interface->assign('imageMapPID', $imageMapPID);

			/** @var FedoraObject $imageMapObject */
			$imageMapObject = $fedoraUtils->getObject($imageMapPID);
			$imageMapDriver = RecordDriverFactory::initRecordDriver($imageMapObject);
			$imageMapImage = $imageMapDriver->getBookcoverUrl('large');
			$imageMapMap = $imageMapObject->getDatastream('MAP')->content;

			//Substitute the imageMap for the source in the map
			$imageMapMap = preg_replace('/src="(.*?)"/', "src=\"{$imageMapImage}\"", $imageMapMap);
			$interface->assign('imageMap', $imageMapMap);
		}

		// Determine what type of page to show
		if ($displayType == 'basic'){
			$this->display('exhibit.tpl');
		} else if ($displayType == 'timeline'){
			$this->display('timelineExhibit.tpl');
		} else if ($displayType == 'map'){
			//Get a list of related places for the object by searching solr to find all objects
			$this->recordDriver->getRelatedPlaces();
			$this->display('mapExhibit.tpl');
		} else if ($displayType == 'custom'){
			$collectionTemplates = array();
			foreach ($collectionOptions as $option){
				if ($option == 'searchCollection'){
					$collectionTemplates[] = $interface->fetch('Archive/searchComponent.tpl');
				}else if ($option == 'googleMap'){
					$mapZoom = $this->recordDriver->getModsValue('mapZoomLevel', 'marmot');
					$interface->assign('mapZoom', $mapZoom);
					$this->recordDriver->getRelatedPlaces();
					$collectionTemplates[] = $interface->fetch('Archive/browseByMapComponent.tpl');
				}else if (strpos($option, 'browseCollectionByTitle') === 0 ){
					$collectionToLoadFromPID = str_replace('browseCollectionByTitle|', '', $option);
					$collectionToLoadFromObject = $fedoraUtils->getObject($collectionToLoadFromPID);
					$collectionDriver = RecordDriverFactory::initRecordDriver($collectionToLoadFromObject);

					$collectionObjects = $collectionDriver->getChildren();
					$collectionTitles = array();
					foreach ($collectionObjects as $childPid){
						$childObject = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($childPid));
						$collectionTitles[] = array(
								'title' => $childObject->getTitle(),
								'link' => $childObject->getRecordUrl()
						);
					}


					$browseCollectionTitlesData = array(
						'title' => $collectionToLoadFromObject->label,
						'collectionTitles' => $collectionTitles,
					);
					$interface->assignAppendToExisting('browseCollectionTitlesData', $browseCollectionTitlesData);
					$collectionTemplates[] = $interface->fetch('Archive/browseCollectionTitles.tpl');
				}else if ($option == 'randomImage' ){
					$randomImagePid = $this->recordDriver->getRandomObject();
					if ($randomImagePid != null){
						$randomObject = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($randomImagePid));
						$randomObjectInfo = array(
								'label' => $randomObject->getTitle(),
								'link' => $randomObject->getRecordUrl(),
								'image' => $randomObject->getBookcoverUrl('medium')
						);
						$interface->assign('randomObject', $randomObjectInfo);
						$collectionTemplates[] = $interface->fetch('Archive/randomImageComponent.tpl');
					}

				}

			}
			$interface->assign('collectionTemplates', $collectionTemplates);

			$this->display('customExhibit.tpl');
		}
	}

	function loadRelatedObjects($displayType){
		global $interface;
		global $timer;
		$fedoraUtils = FedoraUtils::getInstance();
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->setDebugging(false, false);
		$searchObject->clearHiddenFilters();
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->clearFilters();
		$searchObject->addFilter("RELS_EXT_isMemberOfCollection_uri_ms:\"info:fedora/{$this->pid}\"");
		$searchObject->clearFacets();
		if ($displayType == 'map' || $displayType == 'custom'){
			$searchObject->addFacet('mods_extension_marmotLocal_relatedEntity_place_entityPid_ms');
			$searchObject->addFacet('mods_extension_marmotLocal_relatedPlace_entityPlace_entityPid_ms');
			$searchObject->addFacet('mods_extension_marmotLocal_militaryService_militaryRecord_relatedPlace_entityPlace_entityPid_ms');
			$searchObject->addFacet('mods_extension_marmotLocal_describedEntity_entityPid_ms');
			$searchObject->addFacet('mods_extension_marmotLocal_picturedEntity_entityPid_ms');
			$searchObject->setFacetLimit(250);
		}

		$searchObject->setLimit(24);

		$searchObject->setSort('fgs_label_s');
		$interface->assign('showThumbnailsSorted', true);

		$relatedImages = array();
		$mappedPlaces = array();
		$unmappedPlaces = array();
		$response = $searchObject->processSearch(true, false);
		$timer->logTime('Did initial search for related objects');
		if ($response && $response['response']['numFound'] > 0) {
			if ($displayType == 'map' || $displayType == 'custom') {
				$minLat = null;
				$minLong = null;
				$maxLat = null;
				$maxLong = null;
				$geometricMeanLat = 0;
				$geometricMeanLong = 0;
				$numPoints = 0;
				foreach ($response['facet_counts']['facet_fields'] as $facetField) {
					foreach ($facetField as $facetInfo) {
						if (substr($facetInfo[0], 0, 5) == 'place'){
							$mappedPlace = array(
									'pid' => $facetInfo[0],
									'count' => $facetInfo[1]
							);
							$cache = new IslandoraObjectCache();
							$cache->pid = $facetInfo[0];
							$updateCache = true;
							if ($cache->find(true)){
								if ($cache->hasLatLong != null){
									$updateCache = false;
								}
							}
							if ($updateCache){
								/** @var PlaceDriver $placeEntityDriver */
								$placeEntityDriver = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($mappedPlace['pid']));
								$mappedPlace['label'] = $placeEntityDriver->getTitle();
								$mappedPlace['url'] = $placeEntityDriver->getRecordUrl();
								if ($placeEntityDriver instanceof PlaceDriver){
									$geoData = $placeEntityDriver->getGeoData();
								}else{
									//echo("Warning {$placeEntityDriver->getTitle()} ({$placeEntityDriver->getUniqueID()}) was not a place");
									continue;
								}

								if ($geoData){
									$mappedPlace['latitude'] = $geoData['latitude'];
									$mappedPlace['longitude'] = $geoData['longitude'];
								}
								$cache = new IslandoraObjectCache();
								$cache->pid = $facetInfo[0];
								//Should always find the cache now since it gets built when creating the record driver
								if ($cache->find(true)){
									if ($geoData) {
										$cache->latitude = $mappedPlace['latitude'];
										$cache->longitude = $mappedPlace['longitude'];
										$cache->hasLatLong = 1;
									}else{
										$cache->latitude = null;
										$cache->longitude = null;
										$cache->hasLatLong = 0;
									}
									$cache->lastUpdate = time();
									$cache->update();
								}
								$timer->logTime('Loaded information about related place');
							}else {
								$mappedPlace['label'] = $cache->title;
								$mappedPlace['url'] = '/Archive/' . $cache->pid . '/Place';
								if ($cache->hasLatLong){
									$mappedPlace['latitude'] = $cache->latitude;
									$mappedPlace['longitude'] = $cache->longitude;
								}
								$timer->logTime('Loaded information about related place from cache');
							}

							if (isset($mappedPlace['latitude']) && isset($mappedPlace['longitude'])) {
								$geometricMeanLat += $mappedPlace['latitude'] * $mappedPlace['count'];
								$geometricMeanLong += $mappedPlace['longitude'] * $mappedPlace['count'];
								$numPoints += $mappedPlace['count'];

								if ($minLat == null || $mappedPlace['latitude'] < $minLat) {
									$minLat = $mappedPlace['latitude'];
								}
								if ($maxLat == null || $mappedPlace['latitude'] > $maxLat) {
									$maxLat = $mappedPlace['latitude'];
								}
								if ($minLong == null || $mappedPlace['longitude'] < $minLong) {
									$minLong = $mappedPlace['longitude'];
								}
								if ($maxLong == null || $mappedPlace['longitude'] > $maxLong) {
									$maxLong = $mappedPlace['longitude'];
								}

								$mappedPlaces[] = $mappedPlace;
								if (count($mappedPlaces) == 1){
									$interface->assign('selectedPlace', $mappedPlace['pid']);
								}
							}else{
								$unmappedPlaces[] = $mappedPlace;
							}
						}
					}
				}
				$interface->assign('minLat', $minLat);
				$interface->assign('maxLat', $maxLat);
				$interface->assign('minLong', $minLong);
				$interface->assign('maxLong', $maxLong);
				$interface->assign('mapCenterLat', $geometricMeanLat / $numPoints);
				$interface->assign('mapCenterLong', $geometricMeanLong / $numPoints);

				if (isset($_REQUEST['placePid'])){
					$interface->assign('selectedPlace', urldecode($_REQUEST['placePid']));
				}
				$interface->assign('mappedPlaces', $mappedPlaces);
				$interface->assign('unmappedPlaces', $unmappedPlaces);
			}else{
				//Load related objects
				$allObjectsAreCollections = true;
				foreach ($response['response']['docs'] as $objectInCollection){
					/** @var IslandoraDriver $firstObjectDriver */
					$firstObjectDriver = RecordDriverFactory::initRecordDriver($objectInCollection);
					$relatedImages[$firstObjectDriver->getUniqueID()] = array(
							'pid' => $firstObjectDriver->getUniqueID(),
							'title' => $firstObjectDriver->getTitle(),
							'description' => $firstObjectDriver->getDescription(),
							'image' => $firstObjectDriver->getBookcoverUrl('medium'),
							'link' => $firstObjectDriver->getRecordUrl(),
					);
					if (!($firstObjectDriver instanceof CollectionDriver)){
						$allObjectsAreCollections = false;
					}
					$timer->logTime('Loaded related object');
				}
				$interface->assign('showWidgetView', $allObjectsAreCollections);
				$summary = $searchObject->getResultSummary();
				$interface->assign('recordCount', $summary['resultTotal']);
				$interface->assign('recordStart', $summary['startRecord']);
				$interface->assign('recordEnd',   $summary['endRecord']);

				//Check the MODS for the collection to see if it has information about ordering
				$sortingInfo = $this->recordDriver->getModsValues('collectionOrder', 'marmot');
				if (count($sortingInfo) > 0){
					$sortedImages = array();
					foreach ($sortingInfo as $curSortSection){
						$pid = $this->recordDriver->getModsValue('objectPid', 'marmot', $curSortSection);
						if (array_key_exists($pid, $relatedImages)){
							$sortedImages[] = $relatedImages[$pid];
							unset($relatedImages[$pid]);
						}
					}
					//Add any images that weren't specifically sorted
					$relatedImages = $sortedImages + $relatedImages;
				}
				$interface->assign('relatedImages', $relatedImages);
			}
		}
	}
}