<?php
/**
 * Record Driver to control the display of eContent that is stored within the ILS.
 * Usage is restricted by DRM and/or a number of simultaneous checkouts.
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/7/14
 * Time: 10:00 AM
 */

require_once ROOT_DIR . '/RecordDrivers/BaseEContentDriver.php';
class RestrictedEContentDriver extends BaseEContentDriver{

	function getValidProtectionTypes(){
		return array('acs', 'drm');
	}


	function getRecordUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		return $configArray['Site']['path'] . '/RestrictedEContent/' . $recordId;
	}
	function isItemAvailable($itemId, $totalCopies){
		require_once ROOT_DIR . '/sys/eContent/EContentCheckout.php';
		$eContentCheckout = new EContentCheckout();
		$eContentCheckout->recordId = $this->getUniqueID();
		$eContentCheckout->itemId = $itemId;
		$eContentCheckout->status = 'out';
		$eContentCheckout->protectionType = 'acs';
		$eContentCheckout->find();
		$numCurrentlyOut = $eContentCheckout->N;

		return $numCurrentlyOut < $totalCopies;
	}
	function isEContentHoldable($locationCode, $eContentFieldData){
		return $this->isValidForUser($locationCode, $eContentFieldData);
	}
	function isLocalItem($locationCode, $eContentFieldData){
		$sharing = $this->getSharing($locationCode, $eContentFieldData);
		if ($sharing == 'shared'){
			return true;
		}else{
			$searchLibrary = Library::getSearchLibrary();
			if ($searchLibrary == null || (strlen($searchLibrary->ilsCode) > 0 && strpos($locationCode, $searchLibrary->ilsCode) === 0)){
				return true;
			}else{
				return false;
			}
		}
	}
	function isLibraryItem($locationCode, $eContentFieldData){
		$sharing = $this->getSharing($locationCode, $eContentFieldData);
		if ($sharing == 'shared'){
			return true;
		}else{
			$searchLibrary = Library::getSearchLibrary();
			if ($searchLibrary == null || (strlen($searchLibrary->ilsCode) > 0 && strpos($locationCode, $searchLibrary->ilsCode) === 0)){
				return true;
			}else{
				return false;
			}
		}
	}
	function isValidForUser($locationCode, $eContentFieldData){
		$sharing = $this->getSharing($locationCode, $eContentFieldData);
		if ($sharing == 'shared'){
			return true;
		}else if ($sharing == 'library'){
			$searchLibrary = Library::getSearchLibrary();
			if ($searchLibrary == null || (strlen($searchLibrary->ilsCode) > 0 && strpos($locationCode, $searchLibrary->ilsCode) === 0)){
				return true;
			}else{
				return false;
			}
		}else{
			//Just share with the specific location
			$searchLocation = Location::getSearchLocation();
			if (!$searchLocation){
				return true;
			}elseif (strpos($locationCode, $searchLocation->code) === 0){
				return true;
			}else{
				return false;
			}
		}
	}

	function getSharing($locationCode, $eContentFieldData){
		if ($locationCode == 'mdl'){
			return 'shared';
		}else{
			$sharing = 'library';
			if (count($eContentFieldData) >= 3){
				$sharing = trim(strtolower($eContentFieldData[2]));
			}
			return $sharing;
		}
	}

	protected function getRecordType(){
		return 'ils';
	}

	function getModuleName(){
		return 'RestrictedEContent';
	}

	/**
	 * @param String $itemId
	 * @param String $fileName
	 * @param String $acsId
	 * @return array
	 */
	function getActionsForItem($itemId, $fileName, $acsId){
		$actions = array();
		if (!$this->isCheckedOut($itemId)){
			//Get the number of current checkouts
			//TODO:
			if ($this->isItemAvailable($itemId, 1)){
				$actions[] = array(
						'url' => '',
						'onclick' => "VuFind.LocalEContent.checkoutRestrictedEContent('{$this->getUniqueID()}', '{$itemId}')", //Checkout based on item id
						'title' => 'Check Out',
						'requireLogin' => true,
						'showInSummary' => true,
						'showInFormats' => false,
				);
			}else{
				$actions[] = array(
						'url' => '',
						'onclick' => "VuFind.LocalEContent.placeHoldOnRestrictedEContent('{$this->getUniqueID()}', '{$itemId}')",
						'title' => 'Place Hold',
						'requireLogin' => true,
						'showInSummary' => true,
						'showInFormats' => false,
				);
			}
		}else{
			$actions['return'] = array(
					'url' => '',
					'onclick' => "return VuFind.LocalEContent.returnRestrictedEContent('{$this->getUniqueID()}', '{$itemId}')",
					'title' => 'Return Now',
					'requireLogin' => true,
					'showInSummary' => true,
					'showInFormats' => false,
			);

			$fileExtension = '';
			if (strpos($fileName, '.') !== FALSE){
				$fileExtension = substr($fileName, strrpos($fileName, '.') + 1);
			}
			global $configArray;
			//Add actions to read online, or fulfill hold in ACS server as appropriate
			if ($fileExtension == 'epub'){
				$actions[] = array(
						'url' => $configArray['Site']['path'] . "/RestrictedEContent/{$this->getUniqueID()}/Viewer?itemId=$itemId&file=$fileName",
						'onclick' => '',
						'title' => 'Read Online',
						'requireLogin' => true,
						'showInSummary' => false,
						'showInFormats' => true,
				);
			}

			//Get the checkout for the current user
			$eContentCheckout = $this->getCurrentCheckout($itemId);
			require_once ROOT_DIR . '/sys/AdobeContentServer.php';
			$acsDownloadLink = AdobeContentServer::mintDownloadLink($acsId, $eContentCheckout);
			if ($acsDownloadLink){
				$actions[] = array(
						'url' => AdobeContentServer::mintDownloadLink($acsId, $eContentCheckout),
						'onclick' => '',
						'title' => 'Download',
						'requireLogin' => true,
						'showInSummary' => false,
						'showInFormats' => true,
				);
			}
		}
		return $actions;
	}

	private $checkedOut = null;
	function isCheckedOut($itemId){
		if ($this->checkedOut == null){
			global $user;
			if (!$user){
				$this->checkedOut = false;
			}else{
				$currentCheckout = $this->getCurrentCheckout($itemId);
				$this->checkedOut = $currentCheckout != null;

			}
		}
		return $this->checkedOut;
	}

	private function getCurrentCheckout($itemId){
		global $user;
		require_once ROOT_DIR . '/sys/eContent/EContentCheckout.php';
		$eContentCheckout = new EContentCheckout();
		$eContentCheckout->recordId = $this->getUniqueID();
		$eContentCheckout->itemId = $itemId;
		$eContentCheckout->userId = $user->id;
		$eContentCheckout->status = 'out';
		$eContentCheckout->protectionType = 'acs';
		if ($eContentCheckout->find(true)){
			return $eContentCheckout;
		}else{
			return null;
		}
	}

	function getFormat(){
		$result = array();
		/** @var File_MARC_Data_Field[] $itemFields */
		$itemFields = $this->getMarcRecord()->getFields('989');
		foreach ($itemFields as $item){
			$subfieldW = $item->getSubfield('w');
			if ($subfieldW != null){
				if (strpos($subfieldW->getData(), ':') !== FALSE){
					$eContentFieldData = explode(':', $subfieldW->getData());
					$protectionType = trim($eContentFieldData[1]);
					if ($this->isValidProtectionType($protectionType)){
						//Format is based off the iType
						$iTypeField = $item->getSubfield('j');
						if ($iTypeField != null){
							$result[] = mapValue('econtent_itype_format', $iTypeField->getData());
						}else{
							$result[] = 'eBook';
						}
					}
				}
			}
		}
		return $result;
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

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);

		//Load more details options
		$items = $this->getItems();
		$interface->assign('items', $items);
		$moreDetailsOptions['formats'] = array(
				'label' => 'Formats',
				'body' => $interface->fetch('RestrictedEContent/view-formats.tpl'),
				'openByDefault' => true
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

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	function checkout($itemId){
		global $user;

		if (!$user){
			return array(
					'result' => false,
					'message' => 'You must be logged in to checkout a title'
			);
		}else{
			//TODO: count the existing checkouts to determine if there are items available
			require_once ROOT_DIR . '/sys/eContent/EContentCheckout.php';
			$eContentCheckout = new EContentCheckout();
			$eContentCheckout->userId = $user->id;
			$eContentCheckout->recordId = $this->getUniqueID();
			$eContentCheckout->itemId = $itemId;
			$eContentCheckout->protectionType = 'acs';
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
			require_once ROOT_DIR . '/sys/eContent/EContentCheckout.php';
			$eContentCheckout = new EContentCheckout();
			$eContentCheckout->userId = $user->id;
			$eContentCheckout->recordId = $this->getUniqueID();
			$eContentCheckout->itemId = $itemId;
			$eContentCheckout->status = 'out';
			$eContentCheckout->protectionType = 'acs';
			if (!$eContentCheckout->find(true)){
				return array(
						'result' => true,
						'message' => 'This title is not checked out to you.'
				);
			}else{
				$eContentCheckout->dateReturned = time();
				$eContentCheckout->status = 'returned';
				if ($eContentCheckout->update()){
					return array(
							'result' => true,
							'message' => 'The title was returned successfully.'
					);
				}else{
					return array(
							'result' => false,
							'message' => 'Unexpected error returning out the title.'
					);
				}
			}
		}
	}

	public function placeHold($itemId) {
		global $user;

		if (!$user){
			return array(
					'result' => false,
					'message' => 'You must be logged in to place a hold'
			);
		}else{
			require_once ROOT_DIR . '/sys/eContent/EContentHold.php';
			$eContentHold = new EContentHold();
			$eContentHold->userId = $user->id;
			$eContentHold->recordId = $this->getUniqueID();
			$eContentHold->itemId = $itemId;
			$eContentHold->whereAdd("status NOT IN ('cancelled', 'filled')");
			if (!$eContentHold->find(true)){
				$eContentHold->status = 'active';
				$eContentHold->datePlaced = time();
				if ($eContentHold->insert()){
					return array(
							'result' => true,
							'message' => 'Successfully placed hold for you.'
					);
				}else{
					return array(
							'result' => false,
							'message' => 'There was an unknown error placing a hold on this title.'
					);
				}
			}else{
				return array(
						'result' => false,
						'message' => 'Sorry, this title is already on hold for you.'
				);
			}
		}
	}
} 