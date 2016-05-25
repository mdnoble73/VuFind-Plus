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
							'description' => "Update me",
							'image' => $firstObjectDriver->getBookcoverUrl('medium'),
							'dateCreated' => $firstObjectDriver->getDateCreated(),
							'link' => $firstObjectDriver->getRecordUrl(),
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
}
