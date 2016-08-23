<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/Action.php';

class Archive_AJAX extends Action {


	function launch() {
		global $timer;
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");
		//JSON Responses
		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		echo json_encode($this->$method());
	}

	function getRelatedObjectsForMappedCollection(){
		if (isset($_REQUEST['collectionId']) && isset($_REQUEST['placeId'])){
			global $interface;
			global $timer;
			require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
			$fedoraUtils = FedoraUtils::getInstance();
			$pid = urldecode($_REQUEST['collectionId']);
			$interface->assign('exhibitPid', $pid);
			if (isset($_REQUEST['reloadHeader'])){
				$interface->assign('reloadHeader', $_REQUEST['reloadHeader']);
			}else{
				$interface->assign('reloadHeader', '1');
			}

			$placeId = urldecode($_REQUEST['placeId']);
			/** @var FedoraObject $placeObject */
			$placeObject = $fedoraUtils->getObject($placeId);
			$interface->assign('placePid', $placeId);
			$interface->assign('label', $placeObject->label);

			$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			$interface->assign('page', $page);

			$sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'title';
			$interface->assign('sort', $sort);

			/** @var SearchObject_Islandora $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Islandora');
			$searchObject->init();
			$searchObject->setDebugging(false, false);
			$searchObject->clearHiddenFilters();
			$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
			$searchObject->clearFilters();
			$searchObject->addFilter("RELS_EXT_isMemberOfCollection_uri_ms:\"info:fedora/{$pid}\"");
			$searchObject->setBasicQuery("mods_extension_marmotLocal_relatedEntity_place_entityPid_ms:\"{$placeId}\" OR " .
					"mods_extension_marmotLocal_relatedPlace_entityPlace_entityPid_ms:\"{$placeId}\" OR " .
					"mods_extension_marmotLocal_militaryService_militaryRecord_relatedPlace_entityPlace_entityPid_ms:\"{$placeId}\" OR " .
					"mods_extension_marmotLocal_describedEntity_entityPid_ms:\"{$placeId}\" OR " .
					"mods_extension_marmotLocal_picturedEntity_entityPid_ms:\"{$placeId}\""
			);
			//Add filtering based on date filters
			if (isset($_REQUEST['dateFilter'])){
				$filter = '';
				foreach($_REQUEST['dateFilter'] as $date){
					if (strlen($filter) > 0){
						$filter .= ' OR ';
					}
					if ($date == ''){
						$filter .= "mods_originInfo_dateCreated_dt:[* TO *]";
					}elseif ($date == 'before1880'){
						$filter .= "mods_originInfo_dateCreated_dt:[* TO 1879-12-31T23:59:59Z]";
					}else{
						$startYear = substr($date, 0, 4);
						$endYear = $startYear + 9;
						$filter .= "mods_originInfo_dateCreated_dt:[$date TO $endYear-12-31T23:59:59Z]";
					}

				}
				$searchObject->addFilter($filter);
			}
			$searchObject->clearFacets();
			$searchObject->addFacet('mods_originInfo_dateCreated_dt', 'Date Created');
			$searchObject->addFacetOptions(array(
					'facet.range' => 'mods_originInfo_dateCreated_dt',
					'f.mods_originInfo_dateCreated_dt.facet.missing' => 'true',
					'f.mods_originInfo_dateCreated_dt.facet.range.start' => '1880-01-01T00:00:00Z',
					'f.mods_originInfo_dateCreated_dt.facet.range.end' => 'NOW/YEAR',
					'f.mods_originInfo_dateCreated_dt.facet.range.hardend' => 'true',
					'f.mods_originInfo_dateCreated_dt.facet.range.gap' => '+10YEAR',
					'f.mods_originInfo_dateCreated_dt.facet.range.other' => 'all',
			));
			if ($sort == 'title') {
				$searchObject->setSort('fgs_label_s');
			}elseif ($sort == 'newest') {
				$searchObject->setSort('mods_originInfo_dateCreated_dt desc,fgs_label_s asc');
			}elseif ($sort == 'oldest') {
				$searchObject->setSort('mods_originInfo_dateCreated_dt asc,fgs_label_s asc');
			}

			$searchObject->setLimit(24);

			$relatedObjects = array();
			$response = $searchObject->processSearch(true, false, true);
			if ($response && isset($response['error'])){
				$interface->assign('solrError', $response['error']['msg']);
				$interface->assign('solrLink', $searchObject->getFullSearchUrl());
			}
			if ($response && isset($response['response']) && $response['response']['numFound'] > 0) {
				$summary = $searchObject->getResultSummary();
				$interface->assign('recordCount', $summary['resultTotal']);
				$interface->assign('recordStart', $summary['startRecord']);
				$interface->assign('recordEnd',   $summary['endRecord']);

				foreach ($response['response']['docs'] as $objectInCollection){
					/** @var IslandoraDriver $firstObjectDriver */
					$firstObjectDriver = RecordDriverFactory::initRecordDriver($objectInCollection);
					$relatedObjects[] = array(
							'title' => $firstObjectDriver->getTitle(),
							'description' => $firstObjectDriver->getDescription(),
							'image' => $firstObjectDriver->getBookcoverUrl('medium'),
							'dateCreated' => $firstObjectDriver->getDateCreated(),
							'link' => $firstObjectDriver->getRecordUrl(),
							'pid' => $firstObjectDriver->getUniqueID()
					);
					$timer->logTime('Loaded related object');
				}
				if (count($response['facet_counts']['facet_ranges']) > 0){
					$dateFacetInfo = array();
					$dateCreatedInfo = $response['facet_counts']['facet_ranges']['mods_originInfo_dateCreated_dt'];
					if ($dateCreatedInfo['before'] > 0){
						$dateFacetInfo[] = array(
								'label' => 'Before 1880',
								'count' => $dateCreatedInfo['before'],
								'value' => 'before1880'
						);
					}
					foreach($dateCreatedInfo['counts'] as $facetInfo){
						$dateFacetInfo[] = array(
								'label' => substr($facetInfo[0], 0,4) . '\'s',
								'count' => $facetInfo[1],
								'value' => $facetInfo[0]
						);
					}
					if (isset($response['facet_counts']['facet_fields'])){
						foreach($response['facet_counts']['facet_fields']['mods_originInfo_dateCreated_dt'] as $facetInfo){
							if ($facetInfo[0] == null){
								$dateFacetInfo[] = array(
										'label' => 'Unknown',
										'count' => $facetInfo[1],
										'value' => $facetInfo[0]
								);
							}
						}
					}
					$interface->assign('dateFacetInfo', $dateFacetInfo);
				}
			}

			$interface->assign('relatedObjects', $relatedObjects);
			return array(
					'success' => true,
					'relatedObjects' => $interface->fetch('Archive/relatedObjects.tpl')
			);
		}else{
			return array(
					'success' => false,
					'message' => 'You must supply the collection and place to load data for'
			);
		}
	}

	function getExploreMoreContent(){
		if (!isset($_REQUEST['id'])){
			return array(
					'success' => false,
					'message' => 'You must supply the id to load explore more content for'
			);
		}
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();
		$pid = urldecode($_REQUEST['id']);
		$interface->assign('pid', $pid);
		$archiveObject = $fedoraUtils->getObject($pid);
		$recordDriver = RecordDriverFactory::initRecordDriver($archiveObject);
		$interface->assign('recordDriver', $recordDriver);

		require_once ROOT_DIR . '/sys/ExploreMore.php';
		$exploreMore = new ExploreMore();
		$exploreMore->loadExploreMoreSidebar('archive', $recordDriver);


		$relatedSubjects = $recordDriver->getAllSubjectHeadings();

		$ebscoMatches = $exploreMore->loadEbscoOptions('archive', array(), implode($relatedSubjects, " or "));
		if (count($ebscoMatches) > 0){
			$interface->assign('relatedArticles', $ebscoMatches);
		}

		return array(
				'success' => true,
				'exploreMore' => $interface->fetch('explore-more-sidebar.tpl')
		);
	}

	public function getObjectInfo(){
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$pid = urldecode($_REQUEST['id']);
		$interface->assign('pid', $pid);
		$archiveObject = $fedoraUtils->getObject($pid);
		$recordDriver = RecordDriverFactory::initRecordDriver($archiveObject);
		$interface->assign('recordDriver', $recordDriver);

		$url =  $recordDriver->getLinkUrl();
		$interface->assign('url', $url);
		$interface->assign('description', $recordDriver->getDescription());
		$interface->assign('image', $recordDriver->getBookcoverUrl('medium'));

		return array(
				'title' => "<a href='$url'>{$recordDriver->getTitle()}</a>",
				'modalBody' => $interface->fetch('Archive/archivePopup.tpl'),
				'modalButtons' => "<a href='$url'><button class='modal-buttons btn btn-primary'>More Info</button></a>"
		);
	}

	public function getTranscript(){
		global $configArray;
		$objectUrl = $configArray['Islandora']['objectUrl'];
		$transcriptIdentifier = urldecode($_REQUEST['transcriptId']);
		if (strlen($transcriptIdentifier) == 0){
			return array(
					'success' => true,
					'transcript' => "There is no transcription available for this page.",
			);
		}else{
			$transcriptUrl = $objectUrl . '/' . $transcriptIdentifier;
			$transcript = file_get_contents($transcriptUrl);

			if ($transcript){
				return array(
						'success' => true,
						'transcript' => $transcript,
				);
			}else{
				return array(
						'success' => false,
				);
			}
		}
	}

	public function getAdditionalRelatedObjects(){
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$pid = $_REQUEST['id'];
		$interface->assign('pid', $pid);
		$archiveObject = $fedoraUtils->getObject($pid);
		/** @var IslandoraDriver $recordDriver */
		$recordDriver = RecordDriverFactory::initRecordDriver($archiveObject);
		$interface->assign('recordDriver', $recordDriver);
		$directlyRelatedObjects = $recordDriver->getDirectlyRelatedArchiveObjects();

		$interface->assign('directlyRelatedObjects', $directlyRelatedObjects);

		return array(
				'success' => true,
				'additionalObjects' => $interface->fetch('Archive/additionalRelatedObjects.tpl')
		);
	}

	function getSaveToListForm(){
		global $interface;
		global $user;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';

		//Get a list of all lists for the user
		$containingLists = array();
		$nonContainingLists = array();

		$userLists = new UserList();
		$userLists->user_id = $user->id;
		$userLists->deleted = 0;
		$userLists->find();
		while ($userLists->fetch()){
			//Check to see if the user has already added the title to the list.
			$userListEntry = new UserListEntry();
			$userListEntry->listId = $userLists->id;
			$userListEntry->groupedWorkPermanentId = $id;
			if ($userListEntry->find(true)){
				$containingLists[] = array(
					'id' => $userLists->id,
					'title' => $userLists->title
				);
			}else{
				$nonContainingLists[] = array(
					'id' => $userLists->id,
					'title' => $userLists->title
				);
			}
		}

		$interface->assign('containingLists', $containingLists);
		$interface->assign('nonContainingLists', $nonContainingLists);

		$results = array(
			'title' => 'Add To List',
			'modalBody' => $interface->fetch("GroupedWork/save.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='VuFind.Archive.saveToList(\"{$id}\"); return false;'>Save To List</button>"
		);
		return $results;
	}

	function saveToList(){
		$result = array();

		global $user;
		if ($user === false) {
			$result['success'] = false;
			$result['message'] = 'Please login before adding a title to list.';
		}else{
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
			$result['success'] = true;
			$id = urldecode($_REQUEST['id']);
			$listId = $_REQUEST['listId'];
			$notes = $_REQUEST['notes'];

			//Check to see if we need to create a list
			$userList = new UserList();
			$listOk = true;
			if (empty($listId)){
				$userList->title = "My Favorites";
				$userList->user_id = $user->id;
				$userList->public = 0;
				$userList->description = '';
				$userList->insert();
			}else{
				$userList->id = $listId;
				if (!$userList->find(true)){
					$result['success'] = false;
					$result['message'] = 'Sorry, we could not find that list in the system.';
					$listOk = false;
				}
			}

			if ($listOk){
				$userListEntry = new UserListEntry();
				$userListEntry->listId = $userList->id;
				$userListEntry->groupedWorkPermanentId = $id;

				$existingEntry = false;
				if ($userListEntry->find(true)){
					$existingEntry = true;
				}
				$userListEntry->notes = $notes;
				$userListEntry->dateAdded = time();
				if ($existingEntry){
					$userListEntry->update();
				}else{
					$userListEntry->insert();
				}
			}

			$result['success'] = true;
			$result['message'] = 'This title was saved to your list successfully.';
		}

		return $result;
	}
}
