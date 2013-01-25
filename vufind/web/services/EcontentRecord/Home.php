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

require_once 'services/Record/UserComments.php';
require_once 'sys/eContent/EContentRecord.php';
require_once 'RecordDrivers/EcontentRecordDriver.php';
require_once 'sys/SolrStats.php';

class Home extends Action{
	private $db;
	private $id;
	private $isbn;
	private $issn;
	private $recordDriver;

	function launch(){
		global $interface;
		global $timer;
		global $configArray;
		global $user;

		//Enable and disable functionality based on library settings
		global $library;
		global $locationSingleton;

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$this->db->debug = true;
		}

		if (isset($_REQUEST['searchId'])){
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		}else if (isset($_SESSION['searchId'])){
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		$location = $locationSingleton->getActiveLocation();
		if (isset($library)){
			$interface->assign('showTextThis', $library->showTextThis);
			$interface->assign('showEmailThis', $library->showEmailThis);
			$interface->assign('showFavorites', $library->showFavorites);
			$interface->assign('linkToAmazon', $library->linkToAmazon);
			$interface->assign('enablePurchaseLinks', $library->linkToAmazon);
			$interface->assign('enablePospectorIntegration', $library->enablePospectorIntegration);
			if ($location != null){
				$interface->assign('showAmazonReviews', (($location->showAmazonReviews == 1) && ($library->showAmazonReviews == 1)) ? 1 : 0);
				$interface->assign('showStandardReviews', (($location->showStandardReviews == 1) && ($library->showStandardReviews == 1)) ? 1 : 0);
				$interface->assign('showHoldButton', (($location->showHoldButton == 1) && ($library->showHoldButton == 1)) ? 1 : 0);
			}else{
				$interface->assign('showAmazonReviews', $library->showAmazonReviews);
				$interface->assign('showStandardReviews', $library->showStandardReviews);
				$interface->assign('showHoldButton', $library->showHoldButton);
			}
			$interface->assign('showTagging', $library->showTagging);
			$interface->assign('showRatings', $library->showRatings);
			$interface->assign('showComments', $library->showComments);
			$interface->assign('tabbedDetails', $library->tabbedDetails);
			$interface->assign('showOtherEditionsPopup', $library->showOtherEditionsPopup == 1 ? true : false);
			$interface->assign('showProspectorTitlesAsTab', $library->showProspectorTitlesAsTab);
		}else{
			$interface->assign('showTextThis', 1);
			$interface->assign('showEmailThis', 1);
			$interface->assign('showFavorites', 1);
			$interface->assign('linkToAmazon', 1);
			$interface->assign('enablePurchaseLinks', 1);
			$interface->assign('enablePospectorIntegration', 0);
			if ($location != null){
				$interface->assign('showAmazonReviews', $location->showAmazonReviews);
				$interface->assign('showStandardReviews', $location->showStandardReviews);
				$interface->assign('showHoldButton', $location->showHoldButton);
			}else{
				$interface->assign('showAmazonReviews', 1);
				$interface->assign('showStandardReviews', 1);
				$interface->assign('showHoldButton', 1);
			}
			$interface->assign('showTagging', 1);
			$interface->assign('showRatings', 1);
			$interface->assign('showComments', 1);
			$interface->assign('tabbedDetails', 1);
			$interface->assign('showProspectorTitlesAsTab', 0);
		}
		$interface->assign('showOtherEditionsPopup', $configArray['Content']['showOtherEditionsPopup']);
		$interface->assign('chiliFreshAccount', $configArray['Content']['chiliFreshAccount']);
		$showCopiesLineInHoldingsSummary = true;
		if ($library && $library->showCopiesLineInHoldingsSummary == 0){
			$showCopiesLineInHoldingsSummary = false;
		}
		$interface->assign('showCopiesLineInHoldingsSummary', $showCopiesLineInHoldingsSummary);
		$timer->logTime('Configure UI for library and location');

		UserComments::loadEContentComments();
		$timer->logTime('Loaded Comments');

		$eContentRecord = new EContentRecord();
		$this->id = strip_tags($_REQUEST['id']);
		$eContentRecord->id = $this->id;
		if (!$eContentRecord->find(true)){
			//TODO: display record not found error
		}else{
			$this->recordDriver = new EcontentRecordDriver();
			$this->recordDriver->setDataObject($eContentRecord);

			if ($configArray['Catalog']['ils'] == 'Millennium'){
				if (isset($eContentRecord->ilsId) && strlen($eContentRecord->ilsId) > 0){
					$interface->assign('classicId', substr($eContentRecord->ilsId, 1, strlen($eContentRecord->ilsId) -2));
					$interface->assign('classicUrl', $configArray['Catalog']['linking_url']);
				}
			}

			$this->isbn = $eContentRecord->getIsbn();
			if (is_array($this->isbn)){
				if (count($this->isbn) > 0){
					$this->isbn = $this->isbn[0];
				}else{
					$this->isbn = "";
				}
			}elseif ($this->isbn == null || strlen($this->isbn) == 0){
				$interface->assign('showOtherEditionsPopup', false);
			}
			$this->issn = $eContentRecord->getPropertyArray('issn');
			if (is_array($this->issn)){
				if (count($this->issn) > 0){
					$this->issn = $this->issn[0];
				}else{
					$this->issn = "";
				}
			}
			$interface->assign('additionalAuthorsList', $eContentRecord->getPropertyArray('author2'));
			$rawSubjects = $eContentRecord->getPropertyArray('subject');
			$subjects = array();
			foreach ($rawSubjects as $subject){
				$explodedSubjects = explode(' -- ', $subject);
				$searchSubject = "";
				$subject = array();
				foreach ($explodedSubjects as $tmpSubject){
					$searchSubject .= $tmpSubject . ' ';
					$subject[] = array(
		        'search' => trim($searchSubject),
		        'title'  => $tmpSubject,
					);
				}
				$subjects[] = $subject;
			}
			$interface->assign('subjects', $subjects);
			$interface->assign('lccnList', $eContentRecord->getPropertyArray('lccn'));
			$interface->assign('isbnList', $eContentRecord->getPropertyArray('isbn'));
			$interface->assign('isbn', $eContentRecord->getIsbn());
			$interface->assign('isbn10', $eContentRecord->getIsbn10());
			$interface->assign('issnList', $eContentRecord->getPropertyArray('issn'));
			$interface->assign('upcList', $eContentRecord->getPropertyArray('upc'));
			$interface->assign('seriesList', $eContentRecord->getPropertyArray('series'));
			$interface->assign('topicList', $eContentRecord->getPropertyArray('topic'));
			$interface->assign('genreList', $eContentRecord->getPropertyArray('genre'));
			$interface->assign('regionList', $eContentRecord->getPropertyArray('region'));
			$interface->assign('eraList', $eContentRecord->getPropertyArray('era'));

			$interface->assign('eContentRecord', $eContentRecord);
			$interface->assign('cleanDescription', strip_tags($eContentRecord->description, '<p><br><b><i><em><strong>'));

			$interface->assign('id', $eContentRecord->id);

			require_once('sys/eContent/EContentRating.php');
			$eContentRating = new EContentRating();
			$eContentRating->recordId = $eContentRecord->id;
			$interface->assign('ratingData', $eContentRating->getRatingData($user, false));

			//Determine the cover to use
			$bookCoverUrl = $configArray['Site']['coverUrl'] . "/bookcover.php?id={$eContentRecord->id}&amp;econtent=true&amp;isn={$eContentRecord->getIsbn()}&amp;size=large&amp;upc={$eContentRecord->getUpc()}&amp;category=" . urlencode($eContentRecord->format_category()) . "&amp;format=" . urlencode($eContentRecord->getFirstFormat());
			$interface->assign('bookCoverUrl', $bookCoverUrl);

			if (isset($_REQUEST['detail'])){
				$detail = strip_tags($_REQUEST['detail']);
				$interface->assign('defaultDetailsTab', $detail);
			}

			// Find Similar Records
			$similar = $this->db->getMoreLikeThis('econtentRecord' . $eContentRecord->id);
			$timer->logTime('Got More Like This');

			// Find Other Editions
			if ($configArray['Content']['showOtherEditionsPopup'] == false){
				$editions = OtherEditionHandler::getEditions($eContentRecord->solrId(), $eContentRecord->getIsbn(), null);
				if (!PEAR::isError($editions)) {
					$interface->assign('editions', $editions);
				}
				$timer->logTime('Got Other editions');
			}

			//Load the citations
			$this->loadCitation($eContentRecord);

			// Retrieve User Search History
			$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ?
			$_SESSION['lastSearchURL'] : false);

			//Get Next/Previous Links
			$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init($searchSource);
			$searchObject->getNextPrevLinks();

			// Retrieve tags associated with the record
			$limit = 5;
			$resource = new Resource();
			$resource->record_id = $_GET['id'];
			$resource->source = 'eContent';
			$resource->find(true);
			$tags = $resource->getTags($limit);
			$interface->assign('tagList', $tags);
			$timer->logTime('Got tag list');

			//Load notes if any
			$marcRecord = MarcLoader::loadEContentMarcRecord($eContentRecord);
			if ($marcRecord){
				$tableOfContents = array();
				$marcFields505 = $marcRecord->getFields('505');
				if ($marcFields505){
					$tableOfContents = $this->processTableOfContentsFields($marcFields505);
				}

				$notes = array();
				$marcFields500 = $marcRecord->getFields('500');
				$marcFields504 = $marcRecord->getFields('504');
				$marcFields511 = $marcRecord->getFields('511');
				$marcFields518 = $marcRecord->getFields('518');
				$marcFields520 = $marcRecord->getFields('520');
				if ($marcFields500 || $marcFields504 || $marcFields505 || $marcFields511 || $marcFields518 || $marcFields520){
					$allFields = array_merge($marcFields500, $marcFields504, $marcFields511, $marcFields518, $marcFields520);
					$notes = $this->processNoteFields($allFields);
				}

				if ((isset($library) && $library->showTableOfContentsTab == 0) || count($tableOfContents) == 0) {
					$notes = array_merge($notes, $tableOfContents);
				}else{
					$interface->assign('tableOfContents', $tableOfContents);
				}
				if (isset($library)){
					$interface->assign('notesTabName', $library->notesTabName);
				}else{
					$interface->assign('notesTabName', 'Notes');
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
			}

			$this->loadReviews($eContentRecord);

			if (isset($_REQUEST['subsection'])){
				$subsection = $_REQUEST['subsection'];
				if ($subsection == 'Description'){
					$interface->assign('extendedMetadata', $this->recordDriver->getExtendedMetadata());
					$interface->assign('subTemplate', 'view-description.tpl');
				}elseif ($subsection == 'Reviews'){
					$interface->assign('subTemplate', 'view-reviews.tpl');
				}
			}
			//Build the actual view
			$interface->setTemplate('view.tpl');

			$interface->setPageTitle($eContentRecord->title);

			//Var for the IDCLREADER TEMPLATE
			$interface->assign('ButtonBack',true);
			$interface->assign('ButtonHome',true);
			$interface->assign('MobileTitle','&nbsp;');

			//Load Staff Details
			$interface->assign('staffDetails', $this->getStaffView($eContentRecord));

			// Display Page
			$interface->display('layout.tpl');

		}
	}

	public function getStaffView($eContentRecord){
		global $interface;
		$marcRecord = $eContentRecord->marcRecord;
		if (strlen($marcRecord) > 0){
			$marc = trim($marcRecord);
			$marc = preg_replace('/#31;/', "\x1F", $marc);
			$marc = preg_replace('/#30;/', "\x1E", $marc);
			$marc = new File_MARC($marc, File_MARC::SOURCE_STRING);

			if (!($marcRecord = $marc->next())) {
				PEAR::raiseError(new PEAR_Error('Could not load marc record for record ' . $record['id']));
			}
			$interface->assign('marcRecord', $marcRecord);
			return 'RecordDrivers/Marc/staff.tpl';
		}else{
			return null;
		}
	}

	function loadReviews($eContentRecord){
		global $interface;

		//Load the Editorial Reviews
		//Populate an array of editorialReviewIds that match up with the recordId
		$editorialReview = new EditorialReview();
		$editorialReviewResults = array();
		$editorialReview->recordId = 'econtentRecord' . $eContentRecord->id;
		$editorialReview->find();
		$reviewTabs = array();
		$editorialReviewResults['reviews'] = array(
			'tabName' => 'Reviews',
			'reviews' => array()
		);
		if ($editorialReview->N > 0){
			$ctr = 0;
			while ($editorialReview->fetch()){
				$reviewKey = preg_replace('/\W/', '_', strtolower($editorialReview->tabName));
				if (!array_key_exists($reviewKey, $editorialReviewResults)){
					$editorialReviewResults[$reviewKey] = array(
						'tabName' => $editorialReview->tabName,
						'reviews' => array()
					);
				}
				$editorialReviewResults[$reviewKey]['reviews'][$ctr++] = get_object_vars($editorialReview);
			}
		}

		if ($interface->isMobile()){
			//If we are in mobile interface, load standard reviews
			$reviews = array();
			require_once 'sys/Reviews.php';
			if ($eContentRecord->getIsbn()){
				$externalReviews = new ExternalReviews($eContentRecord->getIsbn());
				$reviews = $externalReviews->fetch();
			}

			if (count($editorialReviewResults) > 0) {
				foreach ($editorialReviewResults as $tabName => $reviewsList){
					foreach ($reviewsList['reviews'] as $key=>$result ){
						$reviews["editorialReviews"][$key]["Content"] = $result['review'];
						$reviews["editorialReviews"][$key]["Copyright"] = $result['source'];
						$reviews["editorialReviews"][$key]["Source"] = $result['source'];
						$reviews["editorialReviews"][$key]["ISBN"] = null;
						$reviews["editorialReviews"][$key]["username"] = null;


						$reviews["editorialReviews"][$key] = ExternalReviews::cleanupReview($reviews["editorialReviews"][$key]);
						if ($result['teaser']){
							$reviews["editorialReviews"][$key]["Teaser"] = $result['teaser'];
						}
					}
				}
			}
			$interface->assign('reviews', $reviews);
			$interface->assign('editorialReviews', $editorialReviewResults);
		}else{
			$interface->assign('reviews', $editorialReviewResults);
		}


	}

	/**
	 * Load information from the review provider and update the interface with the data.
	 *
	 * @return array       Returns array with review data, otherwise a
	 *                      PEAR_Error.
	 */
	function loadEnrichment(){
		global $interface;
		global $configArray;

		// Fetch from provider
		if (isset($configArray['Content']['enrichment'])) {
			$providers = explode(',', $configArray['Content']['enrichment']);
			foreach ($providers as $provider) {
				$provider = explode(':', trim($provider));
				$func = strtolower($provider[0]);
				$enrichment[$func] = $this->$func();

				// If the current provider had no valid reviews, store nothing:
				if (empty($enrichment[$func]) || PEAR::isError($enrichment[$func])) {
					unset($enrichment[$func]);
				}
			}
		}

		if ($enrichment) {
			$interface->assign('enrichment', $enrichment);
		}

		return $enrichment;
	}

	function loadCitation($eContentRecord)
	{
		global $interface;

		$citationCount = 0;
		$formats = $this->recordDriver->getCitationFormats();
		foreach($formats as $current) {
			$interface->assign(strtolower($current), $this->recordDriver->getCitation($current));
			$citationCount++;
		}
		$interface->assign('citationCount', $citationCount);
	}

	function processNoteFields($allFields){
		$notes = array();
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
		return $notes;
	}

	function processTableOfContentsFields($allFields){
		$notes = array();
		foreach ($allFields as $marcField){
			$curNote = '';
			foreach ($marcField->getSubFields() as $subfield){
				$note = $subfield->getData();
				$curNote .= " " . $note;
				$curNote = trim($curNote);
				if (strlen($curNote) > 0 && in_array($subfield->getCode(), array('t', 'a'))){
					$notes[] = $curNote;
					$curNote = '';
				}
			}
		}
		return $notes;
	}
}