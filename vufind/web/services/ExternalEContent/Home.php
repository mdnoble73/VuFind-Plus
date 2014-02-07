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

require_once ROOT_DIR . '/RecordDrivers/ExternalEContentDriver.php';
require_once ROOT_DIR . '/sys/SolrStats.php';

class ExternalEContent_Home extends Action{
	/** @var  SearchObject_Solr $db */
	private $id;
	private $isbn;
	private $issn;
	private $recordDriver;

	function launch(){
		global $interface;
		global $timer;
		global $configArray;

		if (isset($_REQUEST['searchId'])){
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		}else if (isset($_SESSION['searchId'])){
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		$interface->assign('overDriveVersion', isset($configArray['OverDrive']['interfaceVersion']) ? $configArray['OverDrive']['interfaceVersion'] : 1);

		$this->id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $this->id);
		$recordDriver = new ExternalEContentDriver($this->id);

		if (!$recordDriver->isValid()){
			$interface->setTemplate('../Record/invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}else{
			$this->isbn = $recordDriver->getCleanISBN();
			$interface->assign('recordDriver', $recordDriver);

			$interface->assign('cleanDescription', strip_tags($recordDriver->getDescriptionFast(), '<p><br><b><i><em><strong>'));

			//$interface->assign('ratingData', $overDriveDriver->getRatingData($user, false));

			//Determine the cover to use
			//$bookCoverUrl = $configArray['Site']['coverUrl'] . "/bookcover.php?id={$eContentRecord->id}&amp;econtent=true&amp;issn={$eContentRecord->getIssn()}&amp;isn={$eContentRecord->getIsbn()}&amp;size=large&amp;upc={$eContentRecord->getUpc()}&amp;category=" . urlencode($eContentRecord->format_category()) . "&amp;format=" . urlencode($eContentRecord->getFirstFormat());
			//$interface->assign('bookCoverUrl', $bookCoverUrl);

			if (isset($_REQUEST['detail'])){
				$detail = strip_tags($_REQUEST['detail']);
				$interface->assign('defaultDetailsTab', $detail);
			}

			//Load the citations
			//$this->loadCitation($eContentRecord);

			// Retrieve User Search History
			$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ?
			$_SESSION['lastSearchURL'] : false);

			//Get Next/Previous Links
			$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init($searchSource);
			$searchObject->getNextPrevLinks();

			//Build the actual view
			$interface->setTemplate('view.tpl');

			$interface->setPageTitle($recordDriver->getTitle());

			//Load Staff Details
			$interface->assign('staffDetails', $recordDriver->getStaffView());

			// Display Page
			$interface->display('layout.tpl');

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
			require_once ROOT_DIR . '/sys/Reviews.php';
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
				if (empty($enrichment[$func]) || PEAR_Singleton::isError($enrichment[$func])) {
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