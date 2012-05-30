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

require_once 'Action.php';

require_once 'File/MARC.php';

require_once 'sys/Language.php';

require_once 'services/MyResearch/lib/User.php';
require_once 'services/MyResearch/lib/Resource.php';
require_once 'services/MyResearch/lib/Resource_tags.php';
require_once 'services/MyResearch/lib/Tags.php';
require_once 'RecordDrivers/Factory.php';

class Record extends Action
{
	public $id;

	/**
	 * marc record in File_Marc object
	 */
	protected $recordDriver;
	public $marcRecord;

	public $record;

	public $isbn;
	public $upc;

	public $cacheId;

	public $db;

	public $description;

	function __construct($subAction = false, $record_id = null)
	{
		global $interface;
		global $configArray;
		global $library;
		global $timer;

		$interface->assign('page_body_style', 'sidebar_left');
		$interface->assign('libraryThingUrl', $configArray['LibraryThing']['url']);
		
		//Load basic information needed in subclasses
		if ($record_id == null || !isset($record_id)){
			$this->id = $_GET['id'];
		}else{
			$this->id = $record_id;
		}
		if ($configArray['Catalog']['ils'] == 'Millennium'){
			$interface->assign('classicId', substr($this->id, 1, strlen($this->id) -2));
			$interface->assign('classicUrl', $configArray['Catalog']['url']);
		}
		 
		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$this->db->debug = true;
		}

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			PEAR::raiseError(new PEAR_Error("Record {$this->id} Does Not Exist"));
		}
		$this->record = $record;
		$interface->assign('record', $record);
		$this->recordDriver = RecordDriverFactory::initRecordDriver($record);
		$timer->logTime('Initialized the Record Driver');
		
		$interface->assign('coreMetadata', $this->recordDriver->getCoreMetadata());
		
		// Process MARC Data
		require_once 'sys/MarcLoader.php';
		$marcRecord = MarcLoader::loadMarcRecordFromRecord($record);
		if ($marcRecord) {
			$this->marcRecord = $marcRecord;
			$interface->assign('marc', $marcRecord);
		} else {
			$interface->assign('error', 'Cannot Process MARC Record');
		}
		$timer->logTime('Processed the marc record');

		//Load information for display in the template rather than processing specific fields in the template
		$marcField = $marcRecord->getField('245');
		$recordTitle = $this->getSubfieldData($marcField, 'a');
		$interface->assign('recordTitle', $recordTitle);
		$recordTitleSubtitle = trim($this->concatenateSubfieldData($marcField, array('a', 'b', 'h', 'n', 'p')));
		$recordTitleSubtitle = preg_replace('~\s+[\/:]$~', '', $recordTitleSubtitle);
		$interface->assign('recordTitleSubtitle', $recordTitleSubtitle);
		$recordTitleWithAuth = trim($this->concatenateSubfieldData($marcField, array('a', 'b', 'h', 'n', 'p', 'c')));
		$interface->assign('recordTitleWithAuth', $recordTitleWithAuth);

		$marcField = $marcRecord->getField('100');
		if ($marcField){
			$mainAuthor = $this->concatenateSubfieldData($marcField, array('a', 'b', 'c', 'd'));
			$interface->assign('mainAuthor', $mainAuthor);
		}

		$marcField = $marcRecord->getField('110');
		if ($marcField){
			$corporateAuthor = $this->getSubfieldData($marcField, 'a');
			$interface->assign('corporateAuthor', $corporateAuthor);
		}

		$marcFields = $marcRecord->getFields('700');
		if ($marcFields){
			$contributors = array();
			foreach ($marcFields as $marcField){
				$contributors[] = $this->concatenateSubfieldData($marcField, array('a', 'b', 'c', 'd'));
			}
			$interface->assign('contributors', $contributors);
		}

		$marcFields = $marcRecord->getFields('260');
		if ($marcFields){
			$published = array();
			foreach ($marcFields as $marcField){
				$published[] = $this->concatenateSubfieldData($marcField, array('a', 'b', 'c'));
			}
			$interface->assign('published', $published);
		}

		$marcFields = $marcRecord->getFields('250');
		if ($marcFields){
			$editionsThis = array();
			foreach ($marcFields as $marcField){
				$editionsThis[] = $this->getSubfieldData($marcField, 'a');
			}
			$interface->assign('editionsThis', $editionsThis);
		}

		$marcFields = $marcRecord->getFields('300');
		if ($marcFields){
			$physicalDescriptions = array();
			foreach ($marcFields as $marcField){
				$description = $this->getSubfieldData($marcField, 'a');
				if ($description != 'p. cm.'){
					$description = preg_replace("/[\/|;:]$/", '', $description);
					$description = preg_replace("/p\./", 'pages', $description);
					$physicalDescriptions[] = $description;
				}
			}
			$interface->assign('physicalDescriptions', $physicalDescriptions);
		}

		// Get ISBN for cover and review use
		$mainIsbnSet = false;
		if ($isbnFields = $this->marcRecord->getFields('020')) {
			$isbns = array();
			//Use the first good ISBN we find.
			foreach ($isbnFields as $isbnField){
				if ($isbnSubfieldA = $isbnField->getSubfield('a')) {
					$tmpIsbn = trim($isbnSubfieldA->getData());
					if (strlen($tmpIsbn) > 0){

						$isbns[] = $isbnSubfieldA->getData();
						$pos = strpos($tmpIsbn, ' ');
						if ($pos > 0) {
							$tmpIsbn = substr($tmpIsbn, 0, $pos);
						}
						$tmpIsbn = trim($tmpIsbn);
						if (strlen($tmpIsbn) > 0){
							if (strlen($tmpIsbn) < 10){
								$tmpIsbn = str_pad($tmpIsbn, 10, "0", STR_PAD_LEFT);
							}
							if (!$mainIsbnSet){
								$this->isbn = $tmpIsbn;
								$interface->assign('isbn', $tmpIsbn);
								$mainIsbnSet = true;
							}
						}
					}
				}
			}
			if (isset($this->isbn)){
				if (strlen($this->isbn) == 13){
					require_once('Drivers/marmot_inc/ISBNConverter.php');
					$this->isbn10 = ISBNConverter::convertISBN13to10($this->isbn);
				}else{
					$this->isbn10 = $this->isbn;
				}
				$interface->assign('isbn10', $this->isbn10);
			}
			$interface->assign('isbns', $isbns);
		}

		if ($upcField = $this->marcRecord->getField('024')) {
			if ($upcField = $upcField->getSubfield('a')) {
				$this->upc = trim($upcField->getData());
				$interface->assign('upc', $this->upc);
			}
		}


		if ($issnField = $this->marcRecord->getField('022')) {
			if ($issnField = $issnField->getSubfield('a')) {
				$this->issn = trim($issnField->getData());
				if ($pos = strpos($this->issn, ' ')) {
					$this->issn = substr($this->issn, 0, $pos);
				}
				$interface->assign('issn', $this->issn);
				//Also setup GoldRush link
				if (isset($library) && strlen($library->goldRushCode) > 0){
					$interface->assign('goldRushLink', "http://goldrush.coalliance.org/index.cfm?fuseaction=Search&amp;inst_code={$library->goldRushCode}&amp;search_type=ISSN&amp;search_term={$this->issn}");
				}
			}
		}

		$timer->logTime("Got basic data from Marc Record subaction = $subAction, record_id = $record_id");
		//stop if this is not the main action.
		if ($subAction == true){
			return;
		}
				
		//Get street date
		if ($streetDateField = $this->marcRecord->getField('263')) {
			$streetDate = $this->getSubfieldData($streetDateField, 'a');
			if ($streetDate != ''){
				$interface->assign('streetDate', $streetDate);
			}
		}

		$marcField440 = $marcRecord->getFields('440');
		$marcField490 = $marcRecord->getFields('490');
		$marcField830 = $marcRecord->getFields('830');
		if ($marcField440 || $marcField490 || $marcField830){
			$series = array();
			foreach ($marcField440 as $field){
				$series[] = $this->getSubfieldData($field, 'a');
			}
			foreach ($marcField490 as $field){
				if ($field->getIndicator(1) == 0){
					$series[] = $this->getSubfieldData($field, 'a');
				}
			}
			foreach ($marcField830 as $field){
				$series[] = $this->getSubfieldData($field, 'a');
			}
			$interface->assign('series', $series);
		}

		//Load description from Syndetics
		$useMarcSummary = true;
		if ($this->isbn || $this->upc){
			require_once 'Drivers/marmot_inc/GoDeeperData.php';
			$summaryInfo = GoDeeperData::getSummary($this->isbn, $this->upc);
			if (isset($summaryInfo['summary'])){
				$interface->assign('summaryTeaser', $summaryInfo['summary']);
				$interface->assign('summary', $summaryInfo['summary']);
				$useMarcSummary = false;
			}
		}
		if ($useMarcSummary){
			if ($summaryField = $this->marcRecord->getField('520')) {
				$interface->assign('summary', $this->getSubfieldData($summaryField, 'a'));
				$interface->assign('summaryTeaser', $this->getSubfieldData($summaryField, 'a'));
			}
		}

		if ($mpaaField = $this->marcRecord->getField('521')) {
			$interface->assign('mpaaRating', $this->getSubfieldData($mpaaField, 'a'));
		}

		if (isset($configArray['Content']['subjectFieldsToShow'])){
			$subjectFieldsToShow = $configArray['Content']['subjectFieldsToShow'];
			$subjectFields = explode(',', $subjectFieldsToShow);
			$subjects = array();
			foreach ($subjectFields as $subjectField){
				$marcFields = $marcRecord->getFields($subjectField);
				if ($marcFields){
					foreach ($marcFields as $marcField){
						$searchSubject = "";
						$subject = array();
						foreach ($marcField->getSubFields() as $subField){
							if ($subField->getCode() != 2){
								$searchSubject .= " " . $subField->getData();
								$subject[] = array(
		                            'search' => trim($searchSubject),
		                            'title'  => $subField->getData(),
								);
							}
						}
						$subjects[] = $subject;
					}
				}
				$interface->assign('subjects', $subjects);
			}
		}
		
		$format = $record['format'];
		$interface->assign('recordFormat', $record['format']);
		$format_category = $record['format_category'][0];
		$interface->assign('format_category', $record['format_category'][0]);
		$interface->assign('recordLanguage', $record['language']);
		
		$timer->logTime('Got detailed data from Marc Record');
		
		$notes = array();

		$marcFields500 = $marcRecord->getFields('500');
		$marcFields504 = $marcRecord->getFields('504');
		$marcFields505 = $marcRecord->getFields('505');
		$marcFields511 = $marcRecord->getFields('511');
		$marcFields518 = $marcRecord->getFields('518');
		$marcFields520 = $marcRecord->getFields('520');
		if ($marcFields500 || $marcFields504 || $marcFields505 || $marcFields511 || $marcFields518 || $marcFields520){
			$allFields = array_merge($marcFields500, $marcFields504, $marcFields505, $marcFields511, $marcFields518, $marcFields520);
			foreach ($allFields as $marcField){
				foreach ($marcField->getSubFields() as $subfield){
					$note = $subfield->getData();
					if ($subfield->getCode() == 't'){
						$note = "&nbsp;&nbsp;&nbsp;" . $note;
					}
					$note = trim($note);
					if (strlen($note) > 0){
						$notes[] = $note;
					}
				}

			}
		}

		$additionalNotesFields = array(
          '310' => 'Current Publication Frequency',
          '321' => 'Former Publication Frequency',
          '351' => 'Organization & arrangement of materials',
          '362' => 'Dates of publication and/or sequential designation',
		      '590' => 'Local note',

		);
		foreach ($additionalNotesFields as $tag => $label){
			$marcFields = $marcRecord->getFields($tag);
			foreach ($marcFields as $marcField){
				$noteText = array();
				foreach ($marcField->getSubFields() as $subfield){
					$noteText[] = $subfield->getData();
				}
				$note = implode(',', $noteText);
				if (strlen($note) > 0){
					$notes[] = $label . ': ' . $note;
				}
			}
		}

		if (count($notes) > 0){
			$interface->assign('notes', $notes);
		}

		$linkFields =$marcRecord->getFields('856') ;
		if ($linkFields){
			$internetLinks = array();
			$purchaseLinks = array();
			$field856Index = 0;
			foreach ($linkFields as $marcField){
				$field856Index++;
				//Get the link
				if ($marcField->getSubfield('u')){
					$link = $marcField->getSubfield('u')->getData();
					if ($marcField->getSubfield('3')){
						$linkText = $marcField->getSubfield('3')->getData();
					}elseif ($marcField->getSubfield('y')){
						$linkText = $marcField->getSubfield('y')->getData();
					}elseif ($marcField->getSubfield('z')){
						$linkText = $marcField->getSubfield('z')->getData();
					}else{
						$linkText = $link;
					}
					$showLink = true;
					//Process some links differently so we can either hide them
					//or show them in different areas of the catalog.
					if (preg_match('/purchase|buy/i', $linkText) ||
						preg_match('/barnesandnoble|tatteredcover|amazon|smashwords\.com/i', $link)){
						$showLink = false;
					}
					$isBookLink = preg_match('/acs\.dcl\.lan|vufind\.douglascountylibraries\.org|catalog\.douglascountylibraries\.org/i', $link);
					if ($isBookLink == 1){
						//e-book link, don't show
						$showLink = false;
					}
	     
					if ($showLink){
						//Rewrite the link so we can track usage
						$link = $configArray['Site']['path'] . '/Record/' . $this->id . '/Link?index=' . $field856Index;
						$internetLinks[] = array(
		        		  'link' => $link,
		        		  'linkText' => $linkText,
						);
					}
				}
			}
			if (count($internetLinks) > 0){
				$interface->assign('internetLinks', $internetLinks);
			}
		}
		if (isset($purchaseLinks) && count($purchaseLinks) > 0){
			$interface->assign('purchaseLinks', $purchaseLinks);
		}

		//Determine the cover to use
		$bookCoverUrl = $configArray['Site']['coverUrl'] . "/bookcover.php?id={$this->id}&amp;isn={$this->isbn}&amp;size=large&amp;upc={$this->upc}&amp;category=" . urlencode($format_category) . "&amp;format=" . urlencode(isset($recordFormat[0]) ? $recordFormat[0] : '');
		$interface->assign('bookCoverUrl', $bookCoverUrl);
		
		//Load accelerated reader data
		if (isset($record['accelerated_reader_interest_level'])){
			$arData = array(
				'interestLevel' => $record['accelerated_reader_interest_level'],
				'pointValue' => $record['accelerated_reader_point_value'],
				'readingLevel' => $record['accelerated_reader_reading_level']
			);
			$interface->assign('arData', $arData);
		}
		
		if (isset($record['lexile_score'])){
			$interface->assign('lexileScore', $record['lexile_score']);
		}


		//Do actions needed if this is the main action.

		//$interface->caching = 1;
		$interface->assign('id', $this->id);
		if (substr($this->id, 0, 1) == '.'){
			$interface->assign('shortId', substr($this->id, 1));
		}else{
			$interface->assign('shortId', $this->id);
		}

		$interface->assign('addHeader', '<link rel="alternate" type="application/rdf+xml" title="RDF Representation" href="' . $configArray['Site']['url']  . '/Record/' . urlencode($this->id) . '/RDF" />');

		// Define Default Tab
		$tab = (isset($_GET['action'])) ? $_GET['action'] : 'Description';
		$interface->assign('tab', $tab);

		if (isset($_REQUEST['detail'])){
			$detail = strip_tags($_REQUEST['detail']);
			$interface->assign('defaultDetailsTab', $detail);
		}

		// Define External Content Provider
		if ($this->marcRecord->getField('020')) {
			if (isset($configArray['Content']['reviews'])) {
				$interface->assign('hasReviews', true);
			}
			if (isset($configArray['Content']['excerpts'])) {
				$interface->assign('hasExcerpt', true);
			}
		}

		// Retrieve User Search History
		$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ?
		$_SESSION['lastSearchURL'] : false);

		// Retrieve tags associated with the record
		$limit = 5;
		$resource = new Resource();
		$resource->record_id = $_GET['id'];
		$resource->source = 'VuFind';
		$resource->find(true);
		$tags = array();
		$tags = $resource->getTags($limit);
		$interface->assign('tagList', $tags);
		$timer->logTime('Got tag list');

		$this->cacheId = 'Record|' . $_GET['id'] . '|' . get_class($this);

		// Find Similar Records
		global $memcache;
		$similar = $memcache->get('similar_titles_' . $this->id);
		if ($similar == false){
			$similar = $this->db->getMoreLikeThis($this->id);
			// Send the similar items to the template; if there is only one, we need
			// to force it to be an array or things will not display correctly.
			if (isset($similar) && count($similar['response']['docs']) > 0) {
				$similar = $similar['response']['docs'];
			}else{
				$similar = array();
				$timer->logTime("Did not find any similar records");
			}
			$memcache->set('similar_titles_' . $this->id, $similar, 0, $configArray['Caching']['similar_titles']);
		}
		$interface->assign('similarRecords', $similar);
		$timer->logTime('Loaded similar titles');
		
		// Find Other Editions
		if ($configArray['Content']['showOtherEditionsPopup'] == false){
			$editions = OtherEditionHandler::getEditions($this->id, $this->isbn, isset($this->record['issn']) ? $this->record['issn'] : null);
			if (!PEAR::isError($editions)) {
				$interface->assign('editions', $editions);
			}else{
				$logger->logTime("Did not find any other editions");
			}
			$timer->logTime('Got Other editions');
		}
		
		$interface->assign('showStrands', isset($configArray['Strands']['APID']) && strlen($configArray['Strands']['APID']) > 0);

		// Send down text for inclusion in breadcrumbs
		$interface->assign('breadcrumbText', $this->recordDriver->getBreadcrumb());

		// Send down OpenURL for COinS use:
		$interface->assign('openURL', $this->recordDriver->getOpenURL());

		// Send down legal export formats (if any):
		$interface->assign('exportFormats', $this->recordDriver->getExportFormats());

		// Set AddThis User
		$interface->assign('addThis', isset($configArray['AddThis']['key']) ?
		$configArray['AddThis']['key'] : false);

		// Set Proxy URL
		if (isset($configArray['EZproxy']['host'])) {
			$interface->assign('proxy', $configArray['EZproxy']['host']);
		}

		//setup 5 star ratings
		global $user;
		$ratingData = $resource->getRatingData($user);
		$interface->assign('ratingData', $ratingData);
		$timer->logTime('Got 5 star data');

		$this->getNextPrevLinks();

		//Load Staff Details
		$interface->assign('staffDetails', $this->recordDriver->getStaffView());
	}
	
	function getNextPrevLinks(){
		global $interface;
		global $timer;
		//Setup next and previous links based on the search results.
		if (isset($_REQUEST['searchId'])){
			//rerun the search
			$s = new SearchEntry();
			$s->id = $_REQUEST['searchId'];
			$interface->assign('searchId', $_REQUEST['searchId']);
			$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			$interface->assign('page', $currentPage);

			$s->find();
			if ($s->N > 0){
				$s->fetch();
				$minSO = unserialize($s->search_object);
				$searchObject = SearchObjectFactory::deminify($minSO);
				$searchObject->setPage($currentPage);
				//Run the search
				$result = $searchObject->processSearch(true, false, false);

				//Check to see if we need to run a search for the next or previous page
				$currentResultIndex = $_REQUEST['recordIndex'] - 1;
				$recordsPerPage = $searchObject->getLimit();

				if (($currentResultIndex) % $recordsPerPage == 0 && $currentResultIndex > 0){
					//Need to run a search for the previous page
					$interface->assign('previousPage', $currentPage - 1);
					$previousSearchObject = clone $searchObject;
					$previousSearchObject->setPage($currentPage - 1);
					$previousSearchObject->processSearch(true, false, false);
					$previousResults = $previousSearchObject->getResultRecordSet();
				}else if (($currentResultIndex + 1) % $recordsPerPage == 0 && ($currentResultIndex + 1) < $searchObject->getResultTotal()){
					//Need to run a search for the next page
					$nextSearchObject = clone $searchObject;
					$interface->assign('nextPage', $currentPage + 1);
					$nextSearchObject->setPage($currentPage + 1);
					$nextSearchObject->processSearch(true, false, false);
					$nextResults = $nextSearchObject->getResultRecordSet();
				}

				if (PEAR::isError($result)) {
					//If we get an error excuting the search, just eat it for now.
				}else{
					if ($searchObject->getResultTotal() < 1) {
						//No results found
					}else{
						$recordSet = $searchObject->getResultRecordSet();
						//Record set is 0 based, but we are passed a 1 based index
						if ($currentResultIndex > 0){
							if (isset($previousResults)){
								$previousRecord = $previousResults[count($previousResults) -1];
							}else{
								$previousRecord = $recordSet[$currentResultIndex - 1 - (($currentPage -1) * $recordsPerPage)];
							}
						//Convert back to 1 based index
							$interface->assign('previousIndex', $currentResultIndex - 1 + 1);
							$interface->assign('previousTitle', $previousRecord['title']);
							if (strpos($previousRecord['id'], 'econtentRecord') === 0){
								$interface->assign('previousType', 'EcontentRecord');
								$interface->assign('previousId', str_replace('econtentRecord', '', $previousRecord['id']));
							}else{
								$interface->assign('previousType', 'Record');
								$interface->assign('previousId', $previousRecord['id']);
							}
						}
						if ($currentResultIndex + 1 < $searchObject->getResultTotal()){

							if (isset($nextResults)){
								$nextRecord = $nextResults[0];
							}else{
								$nextRecordIndex = $currentResultIndex + 1 - (($currentPage -1) * $recordsPerPage);
								if (isset($recordSet[$nextRecordIndex])){
									$nextRecord = $recordSet[$nextRecordIndex];
								}
							}
							//Convert back to 1 based index
							if (isset($nextRecord)){
								$interface->assign('nextIndex', $currentResultIndex + 1 + 1);
								$interface->assign('nextTitle', $nextRecord['title']);
								if (strpos($nextRecord['id'], 'econtentRecord') === 0){
									$interface->assign('nextType', 'EcontentRecord');
									$interface->assign('nextId', str_replace('econtentRecord', '', $nextRecord['id']));
								}else{
									$interface->assign('nextType', 'Record');
									$interface->assign('nextId', $nextRecord['id']);
								}
							}
						}

					}
				}
			}
			$timer->logTime('Got next/previous links');
		}
	}

	/**
	 * Record a record hit to the statistics index when stat tracking is enabled;
	 * this is called by the Home action.
	 */
	public function recordHit()
	{
		//Don't do this since we implemented stats in MySQL rather than Solr
		/*global $configArray;

		if ($configArray['Statistics']['enabled']) {
		// Setup Statistics Index Connection
		$solrStats = new SolrStats($configArray['Statistics']['solr']);
		if ($configArray['System']['debugSolr']) {
		$solrStats->debug = true;
		}

		// Save Record View
		$solrStats->saveRecordView($this->recordDriver->getUniqueID());
		unset($solrStats);
		}*/
	}

	public function getSubfieldData($marcField, $subField){
		if ($marcField){
			return $marcField->getSubfield($subField) ? $marcField->getSubfield($subField)->getData() : '';
		}else{
			return '';
		}
	}
	public function concatenateSubfieldData($marcField, $subFields){
		$value = '';
		foreach ($subFields as $subField){
			$subFieldValue = $this->getSubfieldData($marcField, $subField);
			if (strlen($subFieldValue) > 0){
				$value .= ' ' . $subFieldValue;
			}
		}
		return $value;
	}
}