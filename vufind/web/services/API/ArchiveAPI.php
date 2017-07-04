<?php
require_once ROOT_DIR . '/Action.php';
/**
 * APIs related to Digital Archive functionality
 * User: mnoble
 * Date: 6/29/2017
 * Time: 11:00 AM
 */

class API_ArchiveAPI extends Action {
	function launch(){
		$method = $_REQUEST['method'];

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if (is_callable(array($this, $method))) {
			$output = json_encode(array('result'=>$this->$method()));
		} else {
			$output = json_encode(array('error'=>"invalid_method '$method'"));
		}
		echo $output;
	}

	/**
	 * Returns a feed of content to be sent by DPLA after being processed by the state library.  May not return
	 * a full number of results due to filtering at the collection level.
	 *
	 * Future libraries may require different information.
	 */
	function getDPLAFeed(){
		$curPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$pageSize = isset($_REQUEST['pageSize']) ? $_REQUEST['pageSize'] : 100;
		$changesSince = isset($_REQUEST['changesSince']) ? $_REQUEST['changesSince'] : null;
		$namespace = isset($_REQUEST['namespace']) ? $_REQUEST['namespace'] : null;

		//Query for collections that should not be exported to DPLA
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->setPrimarySearch(false);
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
		$searchObject->addHiddenFilter('RELS_EXT_hasModel_uri_ms', '"info:fedora/islandora:collectionCModel"');
		$searchObject->addHiddenFilter('mods_extension_marmotLocal_pikaOptions_dpla_s', "yes");
		$searchCollectionsResult = $searchObject->processSearch(true, false);
		$collectionsToInclude = array();
		$ancestors = "";

		foreach ($searchCollectionsResult['response']['docs'] as $doc){
			$collectionsToInclude[] = $doc['PID'];
			if (strlen($ancestors) > 0){
				$ancestors .= ' OR ';
			}
			$ancestors .= 'ancestors_ms:"' . $doc['PID'] . '"';
		}


		//Query Solr for the records to export
		// Initialise from the current search globals
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->setPrimarySearch(true);
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
		$searchObject->addFilter("mods_extension_marmotLocal_pikaOptions_dpla_s:yes OR (!mods_extension_marmotLocal_pikaOptions_dpla_s:no AND ($ancestors))");
		$searchObject->addHiddenFilter('!PID', "person*");
		$searchObject->addHiddenFilter('!PID', "event*");
		$searchObject->addHiddenFilter('!PID', "organization*");
		$searchObject->addHiddenFilter('!PID', "place*");
		$searchObject->addHiddenFilter('!RELS_EXT_hasModel_uri_ms', '"info:fedora/islandora:collectionCModel"');
		$searchObject->addHiddenFilter('!RELS_EXT_hasModel_uri_ms', '"info:fedora/islandora:pageCModel"');
		if ($namespace != null){
			$searchObject->addHiddenFilter('namespace_ms', $namespace);
		}

		//Filter to only see DPLA records
		if ($changesSince != null){
			$searchObject->addHiddenFilter('fgs_lastModifiedDate_dt', "[$changesSince TO *]");
		}
		$searchObject->addFieldsToReturn(array(
				'mods_accessCondition_marmot_rightsStatementOrg_t',
				'mods_accessCondition_rightsHolder_entityTitle_ms',
				'mods_extension_marmotLocal_hasCreator_entityTitle_ms',
				'mods_physicalDescription_extent_s',
				'mods_extension_marmotLocal_pikaOptions_dpla_s',
		));
		$searchObject->setPage($curPage);
		$searchObject->setLimit($pageSize);
		$searchObject->clearFacets();
		$searchObject->addFacet('namespace_ms');
		$searchObject->setSort("fgs_lastModifiedDate_dt asc");

		$searchResult = $searchObject->processSearch(true, false);
		$dplaDocs = array();

		foreach ($searchResult['response']['docs'] as $doc){
			/** @var IslandoraDriver $record */
			$record = RecordDriverFactory::initRecordDriver($doc);
			$contributingLibrary = $record->getContributingLibrary();
			//$exportToDPLA = isset($doc['mods_extension_marmotLocal_pikaOptions_dpla_s']) ? $doc['mods_extension_marmotLocal_pikaOptions_dpla_s'] : 'collection';

			//Get the owning library
			$dplaDoc = array();
			$dplaDoc['dataProvider'] = $contributingLibrary['libraryName'];
			$dplaDoc['isShownAt'] = $contributingLibrary['baseUrl'] . $record->getLinkUrl();
			if (isset($doc['mods_accessCondition_marmot_rightsStatementOrg_t'])){
				$dplaDoc['rights'] = $doc['mods_accessCondition_marmot_rightsStatementOrg_t'];
			}else{
				$dplaDoc['rights'] = 'http://rightsstatements.org/page/CNE/1.0/?language=en';
			}

			$dplaDoc['title'] = $record->getTitle();

			$language = $record->getModsValue('languageTerm');
			if (strlen($language)){
				$dplaDoc['language'] = $language;
			}

			$dplaDoc['preview'] = $record->getBookcoverUrl('small');
			$dplaDoc['dateCreated'] = $record->getDateCreated();
			$relatedPlaces = $record->getRelatedPlaces();
			$dplaRelatedPlaces = array();
			foreach ($relatedPlaces as $relatedPlace){
				$dplaRelatedPlaces[] = $relatedPlace['label'];
			}
			if (count($dplaRelatedPlaces)){
				$dplaDoc['place'] = $dplaRelatedPlaces;
			}
			$subjects = $record->getAllSubjectHeadings();
			$dplaDoc['subject'] = array_keys($subjects);
			if (isset($doc['mods_extension_marmotLocal_hasCreator_entityTitle_ms'])){
				$dplaDoc['creator'] = $doc['mods_extension_marmotLocal_hasCreator_entityTitle_ms'];
			}
			$dplaDoc['description'] = $record->getDescription();
			$dplaDoc['format'] = $this->mapFormat($record->getFormat());
			$publishers = array();
			$relatedPeople = $record->getRelatedPeople();
			foreach ($relatedPeople as $relatedPerson){
				if ($relatedPerson['role'] = 'publisher'){
					$publishers[] = $relatedPerson['label'];
				}
			}
			$relatedOrganizations = $record->getRelatedOrganizations();
			foreach ($relatedOrganizations as $relatedOrganization){
				if ($relatedOrganization['role'] = 'publisher'){
					$publishers[] = $relatedOrganization['label'];
				}
			}
			if (count($publishers) > 0){
				$dplaDoc['publisher'] = $publishers;
			}
			$dplaDoc['type'] = $record->getFormat();
			$subTitle = $record->getSubTitle();
			if (strlen($subTitle) > 0){
				$dplaDoc['alternativeTitle'] = $record->getSubTitle();
			}

			if (isset($doc['mods_physicalDescription_extent_s'])){
				$dplaDoc['extent'] = $doc['mods_physicalDescription_extent_s'];
			}
			$dplaDoc['identifier'] = $record->getUniqueID();
			$relatedCollections = $record->getRelatedCollections();
			$dplaRelations = array();
			foreach ($relatedCollections as $relatedCollection){
				$dplaRelations[] = $relatedCollection['label'];
			}
			$dplaDoc['relation'] = $dplaRelations;
			if (isset($doc['mods_accessCondition_rightsHolder_entityTitle_ms'])){
				$dplaDoc['rightsHolder'] = $doc['mods_accessCondition_rightsHolder_entityTitle_ms'];
			}
			$dplaDoc['includeInDPLA'] = isset($doc['mods_extension_marmotLocal_pikaOptions_dpla_s']) ? $doc['mods_extension_marmotLocal_pikaOptions_dpla_s'] : 'default';
			$dplaDocs[] = $dplaDoc;
		}

		$recordsByLibrary = array();
		if (isset($searchResult['facet_counts'])){
			$namespaceFacet = $searchResult['facet_counts']['facet_fields']['namespace_ms'];
			foreach($namespaceFacet as $facetInfo){
				$recordsByLibrary[$facetInfo[0]] = $facetInfo[1];
			}
		}

		$summary = $searchObject->getResultSummary();
		$results = array(
				'numResults' => $summary['resultTotal'],
				'numPages' => ceil($summary['resultTotal'] / $pageSize),
				'recordsByLibrary' => $recordsByLibrary,
				'includedCollections' => $collectionsToInclude,
				'docs' => $dplaDocs,
		);

		return $results;
	}

	private $formatMap = array(
			"Academic Paper" => "Text",
			"Art" => "Image",
			"Article" => "Text",
			"Book" => "Text",
			"Document" => "Text",
			"Image" => "Still Image",
			"Magazine" => "Text",
			"Music Recording" => "Sound",
			"Newspaper" => "Text",
			"Page" => "Text",
			"Postcard" => "Still Image",
			"Video" => "Moving Image",
			"Voice Recording" => "Sound",
	);
	private function mapFormat($format){
		if (array_key_exists($format, $this->formatMap)){
			return $this->formatMap[$format];
		}else{
			return "Unknown";
		}
	}
}