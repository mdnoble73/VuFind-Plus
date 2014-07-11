<?php
/**
 * Record Driver to handle display of eContent stored in the ILS with files stored locally for display in VuFind
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/7/14
 * Time: 9:45 AM
 */
require_once ROOT_DIR . '/RecordDrivers/BaseEContentDriver.php';
require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';
class PublicEContentDriver extends BaseEContentDriver{
	/**
	 * @param array|File_MARC_Record|string $record
	 */
	public function __construct($record){
		//Do default constructor
		parent::__construct($record);
		$this->loadGroupedWork();
	}

	function getValidProtectionTypes(){
		return array('free', 'public domain');
	}

	function isAvailable($realTime){
		return true;
	}
	function isEContentHoldable($locationCode, $eContentFieldData){
		//Not holdable because you can always get it
		return false;
	}
	function isLocalItem($locationCode, $eContentFieldData){
		return true;
	}
	function isLibraryItem($locationCode, $eContentFieldData){
		return true;
	}

	function getUsageRestrictions(){
		return 'Always Available';
	}
	function isValidForUser($locationCode, $eContentFieldData){
		return true;
	}

	function getRecordUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		return $configArray['Site']['path'] . '/PublicEContent/' . $recordId;
	}

	public function getHoldings() {

	}

	protected function getRecordType(){
		return 'ils';
	}

	function getModuleName(){
		return 'PublicEContent';
	}

	function getSharing($locationCode, $eContentFieldData){
		return 'shared';
	}

	private $checkedOut = null;
	function isCheckedOut($itemId){
		if ($this->checkedOut == null){
			global $user;
			if (!$user){
				$this->checkedOut = false;
			}else{
				require_once ROOT_DIR . '/sys/eContent/EContentCheckout.php';
				$eContentCheckout = new EContentCheckout();
				$eContentCheckout->recordId = $this->getUniqueID();
				$eContentCheckout->itemId = $itemId;
				$eContentCheckout->userId = $user->id;
				$eContentCheckout->status = 'out';
				if ($eContentCheckout->find(true)){
					$this->checkedOut = true;
				}else{
					$this->checkedOut = false;
				}
			}
		}
		return $this->checkedOut;
	}

	/**
	 * @param String $itemId
	 * @param String $fileName
	 * @return array
	 */
	function getActionsForItem($itemId, $fileName){
		global $configArray;
		$actions = array();
		if (!$this->isCheckedOut($itemId)){
			$actions[] = array(
					'url' => '',
					'onclick' => "return VuFind.LocalEContent.checkoutPublicEContent('{$this->getUniqueID()}', '{$itemId}')",
					'title' => 'Check Out',
					'requireLogin' => true,
					'showInSummary' => true,
			);
		}else{
			$actions['return'] = array(
					'url' => '',
					'onclick' => "return VuFind.LocalEContent.returnPublicEContent('{$this->getUniqueID()}', '{$itemId}')",
					'title' => 'Return Now',
					'requireLogin' => true,
					'showInSummary' => true,
					'showInFormats' => false,
			);
			$fileExtension = '';
			if (strpos($fileName, '.') !== FALSE){
				$fileExtension = substr($fileName, strrpos($fileName, '.') + 1);
			}
			//Add actions to read online, download, or listen as appropriate.
			$actions[] = array(
					'url' => $configArray['Site']['path'] . "/PublicEContent/{$this->getUniqueID()}/Download?itemId=$itemId&file=$fileName",
					'onclick' => '',
					'title' => 'Download',
					'requireLogin' => true,
					'showInSummary' => false,
					'showInFormats' => true,
			);
			if ($fileExtension == 'epub' || $fileExtension == 'txt'){
				$actions[] = array(
						'url' => $configArray['Site']['path'] . "/PublicEContent/{$this->getUniqueID()}/Viewer?itemId=$itemId&file=$fileName",
						'onclick' => '',
						'title' => 'Read Online',
						'requireLogin' => true,
						'showInSummary' => false,
						'showInFormats' => true,
				);
			}
		}
		return $actions;
	}

	function getActionsForItemFromIndexData($itemData){
		$actions = array();
		$actions[] = array(
				'url' => '',
				'onclick' => "VuFind.LocalEContent.checkoutPublicEContent('{$this->getUniqueID()}','{$itemData[1]}')",
				'title' => 'Check Out',
				'requireLogin' => true,
		);
		return $actions;
	}

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();

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
		$items = $this->getItems();
		$interface->assign('items', $items);
		$moreDetailsOptions['formats'] = array(
				'label' => 'Formats',
				'body' => $interface->fetch('PublicEContent/view-formats.tpl'),
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
		if ($interface->getVariable('enablePospectorIntegration')){
			$moreDetailsOptions['prospector'] = array(
					'label' => 'More Copies In Prospector',
					'body' => '<div id="inProspectorPlaceholder">Loading Prospector Copies...</div>',
					'hideByDefault' => false
			);
		}
		$moreDetailsOptions['tableOfContents'] = array(
				'label' => 'Table of Contents',
				'body' => $interface->fetch('GroupedWork/tableOfContents.tpl'),
				'hideByDefault' => $interface->getVariable('tableOfContents') ? false : true
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
			if ($interface->getVariable('showSimilarTitles')){
				$moreDetailsOptions['similarTitles'] = array(
						'label' => 'Similar Titles From Novelist',
						'body' => '<div id="novelisttitlesPlaceholder"></div>',
						'hideByDefault' => true
				);
			}
			if ($interface->getVariable('showSimilarAuthors')){
				$moreDetailsOptions['similarAuthors'] = array(
						'label' => 'Similar Authors From Novelist',
						'body' => '<div id="novelistauthorsPlaceholder"></div>',
						'hideByDefault' => true
				);
			}
			if ($interface->getVariable('showSimilarTitles')){
				$moreDetailsOptions['similarSeries'] = array(
						'label' => 'Similar Series From Novelist',
						'body' => '<div id="novelistseriesPlaceholder"></div>',
						'hideByDefault' => true
				);
			}
		}
		$moreDetailsOptions['moreDetails'] = array(
				'label' => 'More Details',
				'body' => $interface->fetch('Record/view-more-details.tpl'),
		);
		if ($interface->getVariable('showTagging')){
			$moreDetailsOptions['tags'] = array(
					'label' => 'Tagging',
					'body' => $interface->fetch('GroupedWork/view-tags.tpl'),
			);
		}
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

	function checkout($itemId){
		global $user;

		if (!$user){
			return array(
					'result' => false,
					'message' => 'You must be logged in to checkout a title'
			);
		}else{
			require_once ROOT_DIR . '/sys/eContent/EContentCheckout.php';
			$eContentCheckout = new EContentCheckout();
			$eContentCheckout->userId = $user->id;
			$eContentCheckout->recordId = $this->getUniqueID();
			$eContentCheckout->itemId = $itemId;
			if ($eContentCheckout->find(true) && $eContentCheckout->status == 'out'){
				return array(
						'result' => true,
						'message' => 'This title is already checked out to you'
				);
			}else{
				global $configArray;
				$eContentCheckout->dateCheckedOut = time();
				$loanTerm = $configArray['EContent']['loanTerm'];
				$eContentCheckout->dateDue = time() + $loanTerm * 24 * 60 * 60; //Allow titles to be checked our for 3 weeks
				$eContentCheckout->status = 'out';
				if ($eContentCheckout->insert()){
					return array(
							'result' => true,
							'message' => 'The title was checked out to you successfully.  You may read it from Checked Out page within your account.'
					);
				}else{
					return array(
							'result' => false,
							'message' => 'Unexpected error checking out the title.'
					);
				}
			}
		}
	}

	public function returnTitle($itemId) {
		global $user;

		if (!$user){
			return array(
					'result' => false,
					'message' => 'You must be logged in to return a title'
			);
		}else{
			$eContentCheckout = new EContentCheckout();
			$eContentCheckout->userId = $user->id;
			$eContentCheckout->recordId = $this->getUniqueID();
			$eContentCheckout->itemId = $itemId;
			if ($eContentCheckout->find(true) && $eContentCheckout->status == 'out'){
				return array(
						'result' => true,
						'message' => 'This title is already checked out to you'
				);
			}else{
				global $configArray;
				$eContentCheckout->dateCheckedOut = time();
				$loanTerm = $configArray['EContent']['loanTerm'];
				$eContentCheckout->dateDue = time() + $loanTerm * 24 * 60 * 60; //Allow titles to be checked our for 3 weeks
				$eContentCheckout->status = 'out';
				if ($eContentCheckout->insert()){
					return array(
							'result' => true,
							'message' => 'The title was checked out to you successfully.  You may read it from Checked Out page within your account.'
					);
				}else{
					return array(
							'result' => false,
							'message' => 'Unexpected error checking out the title.'
					);
				}
			}
		}
	}

	function getFormat(){
		return $this->getFormats();
	}
	function getFormats(){
		global $configArray;
		$formats = array();
		//Get the format based on the iType
		$itemFields = $this->getMarcRecord()->getFields('989');
		/** @var File_MARC_Data_Field[] $itemFields */
		foreach ($itemFields as $itemField){
			$locationCode = trim($itemField->getSubfield('d') != null ? $itemField->getSubfield('d')->getData() : '');
			$iTypeField = $itemField->getSubfield($configArray['Reindex']['iTypeSubfield'])->getData();
			/** @var File_MARC_Subfield[] $eContentFields */
			$eContentFields = $itemField->getSubfields('w');
			foreach ($eContentFields as $eContentField){
				$eContentData = trim($eContentField->getData());
				if ($eContentData && strpos($eContentData, ':') > 0){
					$eContentFieldData = explode(':', $eContentData);
					$protectionType = trim($eContentFieldData[1]);
					if ($this->isValidProtectionType($protectionType)){
						if ($this->isValidForUser($locationCode, $eContentFieldData)){
							//Get the format from the item
							if (count($eContentFieldData) > 3){
								$file = trim($eContentFieldData[3]);
								$format = $this->getEContentFormatCategory($file, $iTypeField);
								if ($format){
									$formats[] = $format;
								}
							}else{
								//echo("filename not specified");
							}
						}
					}
				}
			}
		}
		return $formats;
	}
	function getFormatCategory(){
		global $configArray;
		$formats = array();
		//Get the format based on the iType
		$itemFields = $this->getMarcRecord()->getFields('989');
		/** @var File_MARC_Data_Field[] $itemFields */
		foreach ($itemFields as $itemField){
			$locationCode = trim($itemField->getSubfield('d') != null ? $itemField->getSubfield('d')->getData() : '');
			$eContentData = trim($itemField->getSubfield('w') != null ? $itemField->getSubfield('w')->getData() : '');
			$iTypeField = $itemField->getSubfield($configArray['Reindex']['iTypeSubfield'])->getData();

			if ($eContentData && strpos($eContentData, ':') > 0){
				$eContentFieldData = explode(':', $eContentData);
				$protectionType = trim($eContentFieldData[1]);
				if ($this->isValidProtectionType($protectionType)){
					if ($this->isValidForUser($locationCode, $eContentFieldData)){
						//Get the format from the item
						if (count($eContentFieldData) > 3){
							$file = trim($eContentFieldData[3]);
							$format = $this->getEContentFormatCategory($file, $iTypeField);
							if ($format){
								$formats[] = $format;
							}
						}
					}
				}
			}
		}
		return $formats;
	}

	function getEContentFormat($fileOrUrl, $iType){
		if ($fileOrUrl){
			$fileExtension = '';
			if (strpos($fileOrUrl, '.') !== FALSE){
				$fileExtension = substr($fileOrUrl, strrpos($fileOrUrl, '.') + 1);
			}
			$format = mapValue('format', $fileExtension);
		}

		if (isset($format) && strlen($format) > 0){
			return $format;
		}else{
			return mapValue('econtent_itype_format', $iType);
		}
	}

	function getEContentFormatCategory($fileOrUrl, $iType){
		if ($fileOrUrl){
			$fileExtension = '';
			if (strpos($fileOrUrl, '.') !== FALSE){
				$fileExtension = substr($fileOrUrl, strrpos($fileOrUrl, '.') + 1);
			}
			$format = mapValue('format_category', $fileExtension);
		}

		if (isset($format) && strlen($format) > 0){
			return $format;
		}else{
			return mapValue('econtent_itype_format', $iType);
		}
	}

	function getHelpText($fileOrUrl){
		$helpText = '';
		if ($fileOrUrl){
			$fileExtension = '';
			if (strpos($fileOrUrl, '.') !== FALSE){
				$fileExtension = substr($fileOrUrl, strrpos($fileOrUrl, '.') + 1);
			}
			if ($fileExtension == 'mp3'){
				$helpText = "How to use a MP3";
			}else if ($fileExtension == 'epub'){
				$helpText = "How to use an EPUB eBook";
			}else if ($fileExtension == 'mobi'){
				$helpText = "How to use a Kindle eBook";
			}else if ($fileExtension == 'pdb'){

			}else if ($fileExtension == 'pdf'){
				$helpText = "How to use a PDF eBook";
			}else if ($fileExtension == 'external_eaudio'){

			}else if ($fileExtension == 'external_emusic'){

			}else if ($fileExtension == 'text'){

			}else if ($fileExtension == 'itunes'){

			}else if ($fileExtension == 'gifs'){

			}else{

			}
			return $helpText;
		}
		return $helpText;
	}

	function getFormatNotes($fileOrUrl){
		$notes = '';
		if ($fileOrUrl){
			$fileExtension = '';
			if (strpos($fileOrUrl, '.') !== FALSE){
				$fileExtension = substr($fileOrUrl, strrpos($fileOrUrl, '.') + 1);
			}
			if ($fileExtension == 'mp3'){

			}else if ($fileExtension == 'epub'){
				$notes = "Works on all eReaders (except Kindles), desktop computers and mobile devices with with reading apps installed.";
			}else if ($fileExtension == 'mobi'){
				$notes = "Works on Kindles and devices with a Kindle app installed.";
			}else if ($fileExtension == 'pdb'){
				$notes = "Works on Palm OS devices, Windows Mobile devices, and some other PDAs.";
			}else if ($fileExtension == 'pdf'){
				$notes = "Works on most eReaders (except Kindles), desktop computers and mobile devices with with Acrobat Reader (or similar_ Installed.";
			}else if ($fileExtension == 'externalMP3'){

			}else if ($fileExtension == 'text'){

			}else if ($fileExtension == 'itunes'){

			}else if ($fileExtension == 'gifs'){

			}else{

			}
		}
		return $notes;
	}

	function getFileSize($fileOrUrl){
		global $configArray;
		$fullPath = $configArray['EContent']['library'] . '/'. $fileOrUrl;
		if (file_exists($fullPath)){
			return filesize($fullPath);
		}else{
			return 0;
		}
	}
}