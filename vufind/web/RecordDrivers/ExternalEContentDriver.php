<?php
/**
 * Record Driver to Handle the display of eContent that is stored in the ILS, but accessed
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/7/14
 * Time: 9:48 AM
 */

require_once ROOT_DIR . '/RecordDrivers/BaseEContentDriver.php';
class ExternalEContentDriver extends BaseEContentDriver{
	function getValidProtectionTypes(){
		return array('external');
	}

	function getRecordUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		return $configArray['Site']['path'] . '/ExternalEContent/' . $recordId;
	}

	function isAvailable($realTime){
		return true;
		//$itemFields = $this->getMarcRecord()->getFields('989');
		/** @var File_MARC_Data_Field[] $itemFields */
		/*foreach ($itemFields as $itemField){
			$locationCode = trim($itemField->getSubfield('d') != null ? $itemField->getSubfield('d')->getData() : '');
			$eContentData = trim($itemField->getSubfield('w') != null ? $itemField->getSubfield('w')->getData() : '');
			if ($eContentData && strpos($eContentData, ':') > 0){
				$eContentFieldData = explode(':', $eContentData);
				$protectionType = trim($eContentFieldData[1]);
				if ($this->isValidProtectionType($protectionType)){
					if ($this->isValidForUser($locationCode, $eContentFieldData)){
						return true;
					}
				}
			}
		}
		return false;*/
	}
	function isEContentHoldable($locationCode, $eContentFieldData){
		return false;
	}
	function isLocalItem($locationCode, $eContentFieldData){
		return $this->isLibraryItem($locationCode, $eContentFieldData);
	}
	function isLibraryItem($locationCode, $eContentFieldData){
		$sharing = $this->getSharing($locationCode, $eContentFieldData);
		if ($sharing == 'shared'){
			return true;
		}else if ($sharing == 'library'){
			$searchLibrary = Library::getSearchLibrary();
			if ($searchLibrary == null || $searchLibrary->econtentLocationsToInclude == 'all' || strlen($searchLibrary->econtentLocationsToInclude) == 0  || $searchLibrary->includeOutOfSystemExternalLinks || (strlen($searchLibrary->ilsCode) > 0 && strpos($locationCode, $searchLibrary->ilsCode) === 0)){
				return true;
			}else{
				return false;
			}
		}else{
			$searchLibrary = Library::getSearchLibrary();
			$searchLocation = Location::getSearchLocation();
			if ($searchLibrary == null || $searchLibrary->includeOutOfSystemExternalLinks || strpos($locationCode, $searchLocation->code) === 0){
				return true;
			}else{
				return false;
			}
		}
	}

	function isValidForUser($locationCode, $eContentFieldData){
		$sharing = $this->getSharing($locationCode, $eContentFieldData);
		if ($sharing == 'shared'){
			$searchLibrary = Library::getSearchLibrary();
			if ($searchLibrary == null || $searchLibrary->econtentLocationsToInclude == 'all' || strlen($searchLibrary->econtentLocationsToInclude) == 0 || (strpos($searchLibrary->econtentLocationsToInclude, $locationCode) !== FALSE)){
				return true;
			}else{
				return false;
			}
		}else if ($sharing == 'library'){
			$searchLibrary = Library::getSearchLibrary();
			if ($searchLibrary == null || $searchLibrary->includeOutOfSystemExternalLinks || (strlen($searchLibrary->ilsCode) > 0 && strpos($locationCode, $searchLibrary->ilsCode) === 0)){
				return true;
			}else{
				return false;
			}
		}else{
			$searchLibrary = Library::getSearchLibrary();
			$searchLocation = Location::getSearchLocation();
			if ($searchLibrary->includeOutOfSystemExternalLinks || strpos($locationCode, $searchLocation->code) === 0){
				return true;
			}else{
				return false;
			}
		}
	}

	function getSharing($locationCode, $eContentFieldData){
		if (strpos($locationCode, 'mdl') === 0){
			return 'shared';
		}else{
			$sharing = 'library';
			if (count($eContentFieldData) >= 3){
				$sharing = trim(strtolower($eContentFieldData[2]));
			}
			return $sharing;
		}
	}

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();

		$items = $this->getItemsFast();
		$interface->assign('items', $items);

		//Load more details options
		$moreDetailsOptions = array();
		$moreDetailsOptions['series'] = array(
				'label' => 'Also in this Series',
				'body' => $interface->fetch('GroupedWork/series.tpl'),
				'hideByDefault' => false,
				'openByDefault' => true
		);
		$moreDetailsOptions['moreLikeThis'] = array(
				'label' => 'More Like This',
				'body' => $interface->fetch('GroupedWork/moreLikeThis.tpl'),
				'hideByDefault' => false,
				'openByDefault' => true
		);
		$moreDetailsOptions['copies'] = array(
			'label' => 'Copies',
			'body' => $interface->fetch('ExternalEContent/view-items.tpl'),
			'openByDefault' => true
		);
		//Other editions if applicable (only if we aren't the only record!)
		$relatedRecords = $this->getGroupedWorkDriver()->getRelatedRecords();
		if (count($relatedRecords) > 1){
			$interface->assign('relatedManifestations', $this->getGroupedWorkDriver()->getRelatedManifestations());
			$moreDetailsOptions['otherEditions'] = array(
					'label' => 'Other Editions',
					'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
					'hideByDefault' => false
			);
		}
		$moreDetailsOptions['tableOfContents'] = array(
			'label' => 'Table of Contents',
			'body' => $interface->fetch('GroupedWork/tableOfContents.tpl'),
			'hideByDefault' => true
		);
		$moreDetailsOptions['excerpt'] = array(
			'label' => 'Excerpt',
			'body' => '<div id="excerptPlaceholder">Loading Excerpt...</div>',
			'hideByDefault' => true
		);
		$moreDetailsOptions['borrowerReviews'] = array(
			'label' => 'Borrower Reviews',
			'body' => "<div id='customerReviewPlaceholder'></div>",
		);
		$moreDetailsOptions['editorialReviews'] = array(
			'label' => 'Editorial Reviews',
			'body' => "<div id='editorialReviewPlaceholder'></div>",
		);
		if ($isbn){
			$moreDetailsOptions['syndicatedReviews'] = array(
				'label' => 'Published Reviews',
				'body' => "<div id='syndicatedReviewPlaceholder'></div>",
			);
		}
		//A few tabs require an ISBN
		if ($isbn){
			if ($interface->getVariable('showGoodReadsReviews')){
				$moreDetailsOptions['goodreadsReviews'] = array(
					'label' => 'Reviews from GoodReads',
					'body' => '<iframe id="goodreads_iframe" class="goodReadsIFrame" src="https://www.goodreads.com/api/reviews_widget_iframe?did=DEVELOPER_ID&format=html&isbn=' . $isbn . '&links=660&review_back=fff&stars=000&text=000" width="100%" height="400px" frameborder="0"></iframe>',
				);
			}
			$moreDetailsOptions['similarTitles'] = array(
				'label' => 'Similar Titles From Novelist',
				'body' => '<div id="novelisttitlesPlaceholder"></div>',
				'hideByDefault' => true
			);
			$moreDetailsOptions['similarAuthors'] = array(
				'label' => 'Similar Authors From Novelist',
				'body' => '<div id="novelistauthorsPlaceholder"></div>',
				'hideByDefault' => true
			);
			$moreDetailsOptions['similarSeries'] = array(
				'label' => 'Similar Series From Novelist',
				'body' => '<div id="novelistseriesPlaceholder"></div>',
				'hideByDefault' => true
			);
		}
		$moreDetailsOptions['details'] = array(
			'label' => 'Details',
			'body' => $interface->fetch('EcontentRecord/view-title-details.tpl'),
		);
		$moreDetailsOptions['citations'] = array(
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		);
		if ($interface->getVariable('showStaffView')){
			$moreDetailsOptions['staff'] = array(
				'label' => 'Staff View',
				'body' => $interface->fetch($this->getStaffView()),
			);
		}

		return $moreDetailsOptions;
	}

	protected function getRecordType(){
		return 'ils';
	}

	function getModuleName(){
		return 'ExternalEContent';
	}

	function getFormats(){
		global $configArray;
		$formats = array();
		//Get the format based on the iType
		$itemFields = $this->getMarcRecord()->getFields('989');
		/** @var File_MARC_Data_Field[] $itemFields */
		foreach ($itemFields as $itemField){
			$locationCode = trim($itemField->getSubfield('d') != null ? $itemField->getSubfield('d')->getData() : '');
			$eContentData = trim($itemField->getSubfield('w') != null ? $itemField->getSubfield('w')->getData() : '');
			if ($eContentData && strpos($eContentData, ':') > 0){
				$eContentFieldData = explode(':', $eContentData);
				$source = trim($eContentFieldData[0]);
				$protectionType = trim($eContentFieldData[1]);
				if ($this->isValidProtectionType($protectionType)){
					if ($this->isValidForUser($locationCode, $eContentFieldData)){
						$iTypeField = $itemField->getSubfield($configArray['Reindex']['iTypeSubfield'])->getData();
						$format = mapValue('econtent_itype_format', $iTypeField);
						$formats[$format] = $format;
					}
				}
			}
		}
		return $formats;
	}

	/**
	 * @param File_MARC_Data_Field $itemField
	 * @return array
	 */
	function getActionsForItem($itemField){
		$urlSubfield = $itemField->getSubfield('u');
		$actions = array();
		if ($urlSubfield != null){
			$url = $urlSubfield->getData();
			$actions[] = array(
					'url' => $url,
					'title' => 'Access Online',
					'requireLogin' => false,
			);
		}else{
			//Get from the 856 field
			/** @var File_MARC_Data_Field $linkFields */
			$linkFields = $this->getMarcRecord()->getFields('856');
			foreach ($linkFields as $link){
				$urlSubfield = $link->getSubfield('u');
				if ($urlSubfield != null){
					$url = $urlSubfield->getData();
					$title = 'Access Online';
					if (substr_compare($url, 'pdf', strlen($url)-3, strlen(3)) === 0){
						$title = 'Access PDF';
					}
					$actions[] = array(
							'url' => $url,
							'title' => $title,
							'requireLogin' => false,
					);
				}
			}
		}
		return $actions;
	}

	/**
	 * @param String[] $itemData
	 * @return array
	 */
	function getActionsForItemFromIndexData($itemData){
		$actions = array();
		if (count($itemData) >= 7){
			$url = $itemData[6];
			$title = 'Access Online';
			if (substr_compare($url, 'pdf', strlen($url)-3, strlen(3)) === 0){
				$title = 'Access PDF';
			}
			$actions[] = array(
					'url' => $url,
					'title' => $title,
					'requireLogin' => false,
			);
		}

		return $actions;
	}

} 