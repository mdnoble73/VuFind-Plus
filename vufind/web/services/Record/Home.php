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

require_once 'Record.php';
require_once ROOT_DIR . '/sys/SolrStats.php';
require_once 'Reviews.php';
require_once 'UserComments.php';
require_once 'Cite.php';
require_once 'Holdings.php';
require_once(ROOT_DIR . '/sys/LocalEnrichment/EditorialReview.php');

class Record_Home extends Record_Record{
	function launch(){
		global $interface;
		global $timer;
		global $configArray;
		global $user;

		$recordId = $this->id;

		// Load Supplemental Information
		Record_UserComments::loadComments($this->mergedRecords);
		$timer->logTime('Loaded Comments');
		Record_Cite::loadCitation();
		$timer->logTime('Loaded Citations');

		if (isset($_REQUEST['searchId'])){
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		}else if (isset($_SESSION['searchId'])){
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		//Load the Editorial Reviews
		//Populate an array of editorialReviewIds that match up with the recordId
		$editorialReview = new EditorialReview();
		$editorialReviewResults = array();
		if (count($this->mergedRecords) > 0){
			$allIds = $this->mergedRecords;
			$allIds[] = $recordId;
			$editorialReview->whereAddIn('recordId', $allIds, 'string');
		}else{
			$editorialReview->recordId = $recordId;
		}
		$editorialReview->find();
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
		$interface->assign('editorialReviews', $editorialReviewResults);
		$interface->assign('recordId', $recordId);

		//Enable and disable functionality based on library settings
		global $library;
		global $locationSingleton;
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
			$interface->assign('showSeriesAsTab', $library->showSeriesAsTab);
			$interface->assign('showOtherEditionsPopup', 0);
			$interface->assign('show856LinksAsTab', $library->show856LinksAsTab);
			$interface->assign('showProspectorTitlesAsTab', $library->showProspectorTitlesAsTab);
		}else{
			$interface->assign('showTextThis', 1);
			$interface->assign('showEmailThis', 1);
			$interface->assign('showFavorites', 1);
			$interface->assign('linkToAmazon', 1);
			$interface->assign('enablePospectorIntegration', isset($configArray['Content']['Prospector']) && $configArray['Content']['Prospector'] == true ? 1 : 0);
			$interface->assign('enablePurchaseLinks', 1);
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
			$interface->assign('tabbedDetails', !isset($configArray['Content']['tabbedDetails']) || $configArray['Content']['tabbedDetails'] == false ? 0 : 1);
			$interface->assign('showSeriesAsTab', 0);
			$interface->assign('showOtherEditionsPopup', 0);
			$interface->assign('show856LinksAsTab', 1);
			$interface->assign('showProspectorTitlesAsTab', 0);
		}
		if (!isset($this->isbn)){
			$interface->assign('showOtherEditionsPopup', false);
		}

		$resource = new Resource();
		$resource->record_id = $this->id;
		$resource->source = 'VuFind';
		$solrId = $this->id;
		//TODO: Restore other edition functionality
		if ($resource->find(true) && false){
			$otherEditions = OtherEditionHandler::getEditions($solrId, $resource->isbn , null, 10);
			if (is_array($otherEditions)){
				foreach ($otherEditions as $edition){
					/** @var Resource $editionResource */
					$editionResource = new Resource();
					if (preg_match('/econtentRecord(\d+)/', $edition['id'], $matches)){
						$editionResource->source = 'eContent';
						$editionResource->record_id = trim($matches[1]);
					}else{
						$editionResource->record_id = $edition['id'];
						$editionResource->source = 'VuFind';
					}

					if ($editionResource->find(true)){
						if (isset($edition['language'])){
							$editionResource->language = $edition['language'];
						}
						$editionResource->shortId = str_replace('.', '', $editionResource->record_id);

						$editionResources[] = $editionResource;
					}else{
						$logger= new Logger();
						$logger->log("Could not find resource {$editionResource->source} {$editionResource->record_id} - {$edition['id']}", PEAR_LOG_DEBUG);
					}
				}
			}else{
				$editionResources = null;
			}
		}else{
			$otherEditions = null;
			$editionResources = null;
		}
		$interface->assign('otherEditions', $otherEditions);
		$interface->assign('editionResources', $editionResources);

		$interface->assign('chiliFreshAccount', $configArray['Content']['chiliFreshAccount']);
		$timer->logTime('Configure UI for library and location');

		//Build the actual view
		$interface->setTemplate('view.tpl');

		$titleField = $this->marcRecord->getField('245');
		if ($titleField){
			if ($titleField->getSubfield('a')){
				$mainTitle = $titleField->getSubfield('a')->getData();
			}else{
				$mainTitle = 'Title not available';
			}
			$interface->setPageTitle($mainTitle);
		}

		// Display Page
		$interface->display('layout.tpl');

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

}