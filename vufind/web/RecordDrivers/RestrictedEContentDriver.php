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
	function isAvailable($realTime){
		//TODO: Check to see if this is actually checked out or not.
		return true;
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
	 * @param File_MARC_Data_Field $itemField
	 * @return array
	 */
	function getActionsForItem($itemField){
		$itemId = $itemField->getSubfield('1')->getData();
		$actions = array();
		if ($this->isAvailable(true)){
			$actions[] = array(
					'url' => '',
					'onclick' => "VuFind.LocalEContent.checkout('{$itemId}')", //Checkout based on item id
					'title' => 'Check Out',
					'requireLogin' => true,
			);
		}else{
			$actions[] = array(
					'url' => '',
					'onclick' => "VuFind.LocalEContent.placeHold('{$itemId}')",
					'title' => 'Place Hold',
					'requireLogin' => true,
			);
		}
		return $actions;
	}
	function getActionsForItemFromIndexData($itemData){
		$actions = array();
		if ($this->isAvailable(true)){
			$actions[] = array(
					'url' => '',
					'onclick' => "VuFind.LocalEContent.checkout('{$itemData[1]}')", //Checkout based on item id
					'title' => 'Check Out',
					'requireLogin' => true,
			);
		}else{
			$actions[] = array(
					'url' => '',
					'onclick' => "VuFind.LocalEContent.placeHold('{$itemData[1]}')",
					'title' => 'Place Hold',
					'requireLogin' => true,
			);
		}
		return $actions;
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

	function getFormatCategory(){
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
							return mapValue('econtent_itype_format_category', $iTypeField->getData());
						}else{
							return 'eBook';
						}
					}
				}
			}
		}
		return 'eBook';
	}
} 