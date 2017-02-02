<?php

/**
 * Record Driver for display of LargeImages from Islandora
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/9/2015
 * Time: 1:47 PM
 */
require_once ROOT_DIR . '/RecordDrivers/IslandoraDriver.php';
class CollectionDriver extends IslandoraDriver {

	public function getViewAction() {
		return "Exhibit";
	}

	private $anonymousMasterDownload = null;
	private $verifiedMasterDownload = null;
	private $anonymousLcDownload = null;
	private $verifiedLcDownload = null;
	public function canAnonymousDownloadMaster() {
		$this->loadDownloadRestrictions();
		return $this->anonymousMasterDownload;
	}
	public function canVerifiedDownloadMaster() {
		$this->loadDownloadRestrictions();
		return $this->verifiedMasterDownload;
	}

	public function canAnonymousDownloadLC() {
		$this->loadDownloadRestrictions();
		return $this->anonymousLcDownload;
	}
	public function canVerifiedDownloadLC() {
		$this->loadDownloadRestrictions();
		return $this->verifiedLcDownload;
	}

	public function loadDownloadRestrictions(){
		if ($this->anonymousMasterDownload != null){
			return;
		}
		$this->anonymousMasterDownload = $this->getModsValue('anonymousMasterDownload', 'marmot') != 'no';
		$this->verifiedMasterDownload = $this->getModsValue('verifiedMasterDownload', 'marmot') != 'no';
		$this->anonymousLcDownload = $this->getModsValue('anonymousLcDownload', 'marmot') != 'no';
		$this->verifiedLcDownload = $this->getModsValue('verifiedLcDownload', 'marmot') != 'no';
	}

	public function getFormat(){
		return 'Collection';
	}

	public function getNextPrevLinks($currentCollectionItemPID){
		global $interface;

		$collectionChildren = $this->getChildren();
		$currentCollectionItemIndex = array_search($currentCollectionItemPID, $collectionChildren);
		if ($currentCollectionItemIndex !== false) {
			$interface->assign('collectionPid', $this->pid);// TODO: used?
			$interface->assign('page', 1); // Value ignored for collections at this time

			// Previous Collection Item
			if ($currentCollectionItemIndex > 0) {
				$previousIndex = $currentCollectionItemIndex - 1;
				$fedoraUtils = FedoraUtils::getInstance();
				$previousCollectionItemPid = $collectionChildren[$previousIndex];
				/** @var IslandoraDriver $previousRecord */
				$previousRecord = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($previousCollectionItemPid));
				if (!empty($previousRecord)) {
					$interface->assign('previousIndex', $previousIndex);
					$interface->assign('previousType', 'Archive');
					$interface->assign('previousUrl', $previousRecord->getLinkUrl());
					$interface->assign('previousTitle', $previousRecord->getTitle());
//					$interface->assign('previousCollectionItemPid', $previousCollectionItemPid);
				}
			}

			// Next Collection Item
			$nextIndex = $currentCollectionItemIndex + 1;
			if ($nextIndex < count($collectionChildren) ) {
				if (!isset($fedoraUtils)) { $fedoraUtils = FedoraUtils::getInstance(); }
				$nextCollectionItemPid = $collectionChildren[$nextIndex];
				$nextRecord = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($nextCollectionItemPid));
				if (!empty($nextRecord)) {
						$interface->assign('nextIndex', $nextIndex);
						$interface->assign('nextType', 'Archive');
						$interface->assign('nextUrl', $nextRecord->getLinkUrl());
						$interface->assign('nextTitle', $nextRecord->getTitle());
						$interface->assign('nextPage', 1); // Value ignored for collections at this time
//						$interface->assign('nextCollectionItemPid', $nextCollectionItemPid);
				}
			}

		}
	}

	//
//	public function getPreviousNextLinks($pid)
//	{
//		$pikaCollectionDisplay = empty($_REQUEST['style']) ? $this->getModsValue('pikaCollectionDisplay', 'marmot') : $_REQUEST['style'];
//		$displayType           = $pikaCollectionDisplay ? $pikaCollectionDisplay : 'basic';
//
//		//Check the MODS for the collection to see if it has information about ordering
//		$sortingInfo = $this->getModsValues('collectionOrder', 'marmot');
//		if (count($sortingInfo) > 0 && $sortingInfo != array('')) {
//
//			//TODO: I think the each will break this at the end of the array
//			//			$found = $done = false;
////			$currentPid = null;
////			do {
////				if (!empty($currentPid)) {
////					$previousPid = $currentPid;
////				}
////				$curSortSection = each($sortingInfo)[1];
////				$currentPid = $this->getModsValue('objectPid', 'marmot', $curSortSection);
////				$curSortSection = each($sortingInfo)[1];
////				$nextPid = $this->getModsValue('objectPid', 'marmot', $curSortSection);
////				if ($currentPid == $pid) {
////				 $found = true;
////				}
////			} while (!$found && !$done);
//
//		}
//
//
//		/** @var SearchObject_Islandora $searchObject */
//		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
//		$searchObject->init();
//		$searchObject->setDebugging(false, false);
//		$searchObject->clearHiddenFilters();
//		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
//		$searchObject->clearFilters();
//		$searchObject->addFilter("RELS_EXT_isMemberOfCollection_uri_ms:\"info:fedora/{$this->pid}\"");
//		$searchObject->clearFacets();
//		if ($displayType == 'map' || $displayType == 'custom') {
//			$searchObject->addFacet('mods_extension_marmotLocal_relatedEntity_place_entityPid_ms');
//			$searchObject->addFacet('mods_extension_marmotLocal_relatedPlace_entityPlace_entityPid_ms');
//			$searchObject->addFacet('mods_extension_marmotLocal_militaryService_militaryRecord_relatedPlace_entityPlace_entityPid_ms');
//			$searchObject->addFacet('mods_extension_marmotLocal_describedEntity_entityPid_ms');
//			$searchObject->addFacet('mods_extension_marmotLocal_picturedEntity_entityPid_ms');
//			$searchObject->setFacetLimit(250);
//		}
//
//		$searchObject->setLimit(24);
//
//		$searchObject->setSort('fgs_label_s');
//
//		$newOrder = array();
//		$response = $searchObject->processSearch(true, false);
//		foreach ($response['response']['docs'] as $objectInCollection) {
//			/** @var IslandoraDriver $firstObjectDriver */
//			$firstObjectDriver                                = RecordDriverFactory::initRecordDriver($objectInCollection);
//			$relatedImages[$firstObjectDriver->getUniqueID()] = array(
//				'pid' => $firstObjectDriver->getUniqueID(),
//				'title' => $firstObjectDriver->getTitle(),
//				'description' => $firstObjectDriver->getDescription(),
//				'image' => $firstObjectDriver->getBookcoverUrl('medium'),
//				'link' => $firstObjectDriver->getRecordUrl() . "?returnTo=" . urlencode($this->pid),
//				//							'link' => $firstObjectDriver->getRecordUrl(),
//				//TODO: exhibit navigation
//			);
//
//		}
//	}
}