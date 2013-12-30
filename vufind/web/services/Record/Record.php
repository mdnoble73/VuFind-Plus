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

require_once ROOT_DIR  . '/Action.php';

require_once 'File/MARC.php';

require_once ROOT_DIR  . '/sys/Language.php';

require_once ROOT_DIR  . '/services/MyResearch/lib/User.php';
require_once ROOT_DIR  . '/services/MyResearch/lib/Resource.php';
require_once ROOT_DIR  . '/services/MyResearch/lib/Resource_tags.php';
require_once ROOT_DIR  . '/services/MyResearch/lib/Tags.php';
require_once ROOT_DIR  . '/RecordDrivers/Factory.php';

abstract class Record_Record extends Action
{
	public $id;

	/**
	 * marc record in File_Marc object
	 */
	protected $recordDriver;
	public $marcRecord;

	public $record;
	public $similarTitles;

	public $isbn;
	public $issn;
	public $upc;

	public $cacheId;

	/** @var  Solr */
	public $db;

	public $description;
	protected $mergedRecords = array();

	function __construct($subAction = false, $record_id = null)
	{
		global $interface;
		global $configArray;
		global $library;
		global $timer;
		global $logger;

		$interface->assign('page_body_style', 'sidebar_left');
		$interface->assign('libraryThingUrl', $configArray['LibraryThing']['url']);

		//Determine whether or not materials request functionality should be enabled
		$interface->assign('enableMaterialsRequest', MaterialsRequest::enableMaterialsRequest());

		//Load basic information needed in subclasses
		if ($record_id == null || !isset($record_id)){
			$this->id = $_GET['id'];
		}else{
			$this->id = $record_id;
		}

		require_once(ROOT_DIR . '/sys/MergedRecord.php');
		$mergedRecord = new MergedRecord();
		$mergedRecord->original_record = $this->id;
		if ($mergedRecord->find(true)){
			//redirect to the new record
			header('Location: ' . $configArray['Site']['url'] . '/Record/' . $mergedRecord->new_record);
			exit();
		}

		//Look for any records that have been merged with this record
		$mergedRecord = new MergedRecord();
		$mergedRecord->new_record = $this->id;
		if ($mergedRecord->find()){
			while ($mergedRecord->fetch()){
				$this->mergedRecords[] = $mergedRecord->original_record;
			}
		}

		//Check to see if the record exists within the resources table
		$resource = new Resource();
		$resource->record_id = $this->id;
		$resource->source = 'VuFind';
		$resource->deleted = 0;
		if (!$resource->find()){
			//Check to see if the record has been converted to an eContent record
			require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';
			$econtentRecord = new EContentRecord();
			$econtentRecord->ilsId = $this->id;
			$econtentRecord->status = 'active';
			if ($econtentRecord->find(true)){
				header("Location: /EcontentRecord/{$econtentRecord->id}/Home");
				die();
			}
			$logger->log("Did not find a record for id {$this->id} in resources table." , PEAR_LOG_DEBUG);
			$interface->setTemplate('invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}

		if ($configArray['Catalog']['ils'] == 'Millennium' || $configArray['Catalog']['ils'] == 'Sierra'){
			$interface->assign('classicId', substr($this->id, 1, strlen($this->id) -2));
			$interface->assign('classicUrl', $configArray['Catalog']['linking_url']);
		}

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		$this->db->disableScoping();

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			$logger->log("Did not find a record for id {$this->id} in solr." , PEAR_LOG_DEBUG);
			$interface->setTemplate('invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}
		$this->db->enableScoping();

		$this->record = $record;
		$interface->assign('record', $record);
		$this->recordDriver = RecordDriverFactory::initRecordDriver($record);
		$timer->logTime('Initialized the Record Driver');

		$interface->assign('coreMetadata', $this->recordDriver->getCoreMetadata());

		// Process MARC Data
		require_once ROOT_DIR  . '/sys/MarcLoader.php';
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

		$published = $this->recordDriver->getPublicationDetails();
		$interface->assign('published', $published);

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
				$description = $this->concatenateSubfieldData($marcField, array('a', 'b', 'c', 'e', 'f', 'g'));
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
		/** @var File_MARC_Data_Field[] $isbnFields */
		if ($isbnFields = $this->marcRecord->getFields('020')) {
			$isbns = array();
			//Use the first good ISBN we find.
			foreach ($isbnFields as $isbnField){
				/** @var File_MARC_Subfield $isbnSubfieldA */
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
					require_once(ROOT_DIR  . '/Drivers/marmot_inc/ISBNConverter.php');
					$this->isbn10 = ISBNConverter::convertISBN13to10($this->isbn);
				}else{
					$this->isbn10 = $this->isbn;
				}
				$interface->assign('isbn10', $this->isbn10);
			}
			$interface->assign('isbns', $isbns);
		}

		if ($upcField = $this->marcRecord->getField('024')) {
			/** @var File_MARC_Data_Field $upcField */
			if ($upcSubField = $upcField->getSubfield('a')) {
				$this->upc = trim($upcSubField->getData());
				$interface->assign('upc', $this->upc);
			}
		}


		if ($issnField = $this->marcRecord->getField('022')) {
			/** @var File_MARC_Data_Field $issnField */
			if ($issnSubField = $issnField->getSubfield('a')) {
				$this->issn = trim($issnSubField->getData());
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

		/** @var File_MARC_Data_Field[] $marcField440 */
		$marcField440 = $marcRecord->getFields('440');
		/** @var File_MARC_Data_Field[] $marcField490 */
		$marcField490 = $marcRecord->getFields('490');
		/** @var File_MARC_Data_Field[] $marcField830 */
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
			if (!$library || ($library && $library->preferSyndeticsSummary == 1)){
				require_once ROOT_DIR  . '/Drivers/marmot_inc/GoDeeperData.php';
				$summaryInfo = GoDeeperData::getSummary($this->isbn, $this->upc);
				if (isset($summaryInfo['summary'])){
					$interface->assign('summaryTeaser', $summaryInfo['summary']);
					$interface->assign('summary', $summaryInfo['summary']);
					$useMarcSummary = false;
				}
			}
		}
		if ($useMarcSummary){
			if ($summaryField = $this->marcRecord->getField('520')) {
				$interface->assign('summary', $this->getSubfieldData($summaryField, 'a'));
				$interface->assign('summaryTeaser', $this->getSubfieldData($summaryField, 'a'));
			}elseif ($library && $library->preferSyndeticsSummary == 0){
				require_once ROOT_DIR  . '/Drivers/marmot_inc/GoDeeperData.php';
				$summaryInfo = GoDeeperData::getSummary($this->isbn, $this->upc);
				if (isset($summaryInfo['summary'])){
					$interface->assign('summaryTeaser', $summaryInfo['summary']);
					$interface->assign('summary', $summaryInfo['summary']);
					$useMarcSummary = false;
				}
			}

		}

		if ($mpaaField = $this->marcRecord->getField('521')) {
			$interface->assign('mpaaRating', $this->getSubfieldData($mpaaField, 'a'));
		}

		if (isset($configArray['Content']['subjectFieldsToShow'])){
			$subjectFieldsToShow = $configArray['Content']['subjectFieldsToShow'];
			$subjectFields = explode(',', $subjectFieldsToShow);

			$subjects = array();
			$standardSubjects = array();
			$bisacSubjects = array();
			$oclcFastSubjects = array();
			foreach ($subjectFields as $subjectField){
				/** @var File_MARC_Data_Field[] $marcFields */
				$marcFields = $marcRecord->getFields($subjectField);
				if ($marcFields){
					foreach ($marcFields as $marcField){
						$searchSubject = "";
						$subject = array();
						//Determine the type of the subject
						$type = 'standard';
						$subjectSource = $marcField->getSubfield('2');
						if ($subjectSource != null){
							if (preg_match('/bisac/i', $subjectSource->getData())){
								$type = 'bisac';
							}elseif (preg_match('/fast/i', $subjectSource->getData())){
								$type = 'fast';
							}
						}

						foreach ($marcField->getSubFields() as $subField){
							/** @var File_MARC_Subfield $subField */
							if ($subField->getCode() != '2' && $subField->getCode() != '0'){
								$subFieldData = $subField->getData();
								if ($type == 'bisac' && $subField->getCode() == 'a'){
									$subFieldData = ucwords(strtolower($subFieldData));
								}
								$searchSubject .= " " . $subFieldData;
								$subject[] = array(
		                            'search' => trim($searchSubject),
		                            'title'  => $subFieldData,
								);
							}
						}
						if ($type == 'bisac'){
							$bisacSubjects[] = $subject;
							$subjects[] = $subject;
						}elseif ($type == 'fast'){
							//Suppress fast subjects by default
							$oclcFastSubjects[] = $subject;
						}else{
							$subjects[] = $subject;
							$standardSubjects[] = $subject;
						}

					}
				}
			}
			$interface->assign('subjects', $subjects);
			$interface->assign('standardSubjects', $standardSubjects);
			$interface->assign('bisacSubjects', $bisacSubjects);
			$interface->assign('oclcFastSubjects', $oclcFastSubjects);
		}

		$format = $record['format'];
		$interface->assign('recordFormat', $record['format']);
		$format_category = isset($record['format_category'][0]) ? $record['format_category'][0] : '';
		$interface->assign('format_category', $format_category);
		$interface->assign('recordLanguage', isset($record['language']) ? $record['language'] : null);

		$timer->logTime('Got detailed data from Marc Record');

		$tableOfContents = array();
		$marcFields505 = $marcRecord->getFields('505');
		if ($marcFields505){
			$tableOfContents = $this->processTableOfContentsFields($marcFields505);
		}

		$notes = array();
		/*$marcFields500 = $marcRecord->getFields('500');
		$marcFields504 = $marcRecord->getFields('504');
		$marcFields511 = $marcRecord->getFields('511');
		$marcFields518 = $marcRecord->getFields('518');
		$marcFields520 = $marcRecord->getFields('520');
		if ($marcFields500 || $marcFields504 || $marcFields505 || $marcFields511 || $marcFields518 || $marcFields520){
			$allFields = array_merge($marcFields500, $marcFields504, $marcFields511, $marcFields518, $marcFields520);
			$notes = $this->processNoteFields($allFields);
		}*/

		if ((isset($library) && $library->showTableOfContentsTab == 0) || count($tableOfContents) == 0) {
			$notes = array_merge($notes, $tableOfContents);
		}else{
			$interface->assign('tableOfContents', $tableOfContents);
		}
		if (isset($library) && strlen($library->notesTabName) > 0){
			$interface->assign('notesTabName', $library->notesTabName);
		}else{
			$interface->assign('notesTabName', 'Notes');
		}

		$additionalNotesFields = array(
						'520' => 'Description',
						'500' => 'General Note',
						'504' => 'Bibliography',
						'511' => 'Participants/Performers',
						'518' => 'Date/Time and Place of Event',
						'310' => 'Current Publication Frequency',
            '321' => 'Former Publication Frequency',
            '351' => 'Organization & arrangement of materials',
            '362' => 'Dates of publication and/or sequential designation',
            '501' => '"With"',
            '502' => 'Dissertation',
            '506' => 'Restrictions on Access',
            '507' => 'Scale for Graphic Material',
            '508' => 'Creation/Production Credits',
            '510' => 'Citation/References',
            '513' => 'Type of Report an Period Covered',
            '515' => 'Numbering Peculiarities',
            '521' => 'Target Audience',
            '522' => 'Geographic Coverage',
            '525' => 'Supplement',
            '526' => 'Study Program Information',
            '530' => 'Additional Physical Form',
            '533' => 'Reproduction',
            '534' => 'Original Version',
            '536' => 'Funding Information',
            '538' => 'System Details',
            '545' => 'Biographical or Historical Data',
            '546' => 'Language',
            '547' => 'Former Title Complexity',
            '550' => 'Issuing Body',
            '555' => 'Cumulative Index/Finding Aids',
            '556' => 'Information About Documentation',
            '561' => 'Ownership and Custodial History',
            '563' => 'Binding Information',
            '580' => 'Linking Entry Complexity',
            '581' => 'Publications About Described Materials',
            '586' => 'Awards',
            '590' => 'Local note',
            '599' => 'Differentiable Local note',
		);

		foreach ($additionalNotesFields as $tag => $label){
			$marcFields = $marcRecord->getFields($tag);
			foreach ($marcFields as $marcField){
				$noteText = array();
				foreach ($marcField->getSubFields() as $subfield){
					/** @var File_MARC_Subfield $subfield */
					$noteText[] = $subfield->getData();
				}
				$note = implode(',', $noteText);
				if (strlen($note) > 0){
					$notes[] = "<dt>$label</dt><dd>" . $note . '</dd>';
				}
			}
		}

		if (count($notes) > 0){
			$interface->assign('notes', $notes);
		}

		/** @var File_MARC_Data_Field[] $linkFields */
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
		$bookCoverUrl = $configArray['Site']['coverUrl'] . "/bookcover.php?id={$this->id}&amp;isn={$this->isbn}&amp;issn={$this->issn}&amp;size=large&amp;upc={$this->upc}&amp;category=" . urlencode($format_category) . "&amp;format=" . urlencode(isset($format[0]) ? $format[0] : '');
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

		if (isset($record['lexile_score']) && $record['lexile_score'] > -1){
			$lexileScore = $record['lexile_score'];
			if (isset($record['lexile_code'])){
				$lexileScore = $record['lexile_code'] . $lexileScore;
			}
			$interface->assign('lexileScore', $lexileScore . 'L');
		}


		//Do actions needed if this is the main action.

		//$interface->caching = 1;
		$interface->assign('id', $this->id);
		if (substr($this->id, 0, 1) == '.'){
			$interface->assign('shortId', substr($this->id, 1));
		}else{
			$interface->assign('shortId', $this->id);
		}

		$interface->assign('addHeader', '<link rel="alternate" type="application/rdf+xml" title="RDF Representation" href="' . $configArray['Site']['path']  . '/Record/' . urlencode($this->id) . '/RDF" />');

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
		$tags = $resource->getTags($limit);
		$interface->assign('tagList', $tags);
		$timer->logTime('Got tag list');

		$this->cacheId = 'Record|' . $_GET['id'] . '|' . get_class($this);

		// Find Similar Records
		/** @var Memcache $memCache */
		global $memCache;
		$similar = $memCache->get('similar_titles_' . $this->id);
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
			$memCache->set('similar_titles_' . $this->id, $similar, 0, $configArray['Caching']['similar_titles']);
		}
		$this->similarTitles = $similar;
		$interface->assign('similarRecords', $similar);
		$timer->logTime('Loaded similar titles');

		// Find Other Editions
		if ($configArray['Content']['showOtherEditionsPopup'] == false){
			$editions = OtherEditionHandler::getEditions($this->id, $this->isbn, isset($this->record['issn']) ? $this->record['issn'] : null);
			if (!PEAR_Singleton::isError($editions)) {
				$interface->assign('editions', $editions);
			}else{
				$timer->logTime("Did not find any other editions");
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

		//Get Next/Previous Links
		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$searchObject->getNextPrevLinks();

		//Load Staff Details
		$interface->assign('staffDetails', $this->recordDriver->getStaffView());
	}

	/**
	 * @param File_MARC_Data_Field[] $allFields
	 * @return array
	 */
	function processNoteFields($allFields){
		$notes = array();
		/** File_MARC_Data_Field $marcField */
		foreach ($allFields as $marcField){
			/** @var File_MARC_Subfield $subfield */
			foreach ($marcField->getSubfields() as $subfield){
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
		return $notes;
	}

	/**
	 * @param File_MARC_Data_Field[] $allFields
	 * @return array
	 */
	function processTableOfContentsFields($allFields){
		$notes = array();
		foreach ($allFields as $marcField){
			$curNote = '';
			/** @var File_MARC_Subfield $subfield */
			foreach ($marcField->getSubfields() as $subfield){
				$note = $subfield->getData();
				$curNote .= " " . $note;
				$curNote = trim($curNote);
//				if (strlen($curNote) > 0 && in_array($subfield->getCode(), array('t', 'a'))){
//					$notes[] = $curNote;
//					$curNote = '';
//				}
// 20131112 split 505 contents notes on double-hyphens instead of title subfields (which created bad breaks misassociating titles and authors)
				if (preg_match("/--$/",$curNote)) {
					$notes[] = $curNote;
					$curNote = '';
				} 
			}
			$notes[] = $curNote;
			$curNote = '';
		}
		return $notes;
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

	/**
	 * @param File_MARC_Data_Field $marcField
	 * @param File_MARC_Subfield $subField
	 * @return string
	 */
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
