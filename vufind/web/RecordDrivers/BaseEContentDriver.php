<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/9/14
 * Time: 9:50 PM
 */

require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';

abstract class BaseEContentDriver  extends MarcRecord {
	abstract function getModuleName();
	abstract function getValidProtectionTypes();

	protected $itemsFromIndex;
	public function setItemsFromIndex($itemsFromIndex){
		$this->itemsFromIndex = $itemsFromIndex;
	}

	protected $detailedRecordInfoFromIndex;
	public function setDetailedRecordInfoFromIndex($detailedRecordInfoFromIndex){
		$this->detailedRecordInfoFromIndex = $detailedRecordInfoFromIndex;
	}

	public function getItems(){
		return $this->getItemsFast();
	}
	private $fastItems = null;
	public function getItemsFast(){
		if ($this->fastItems == null){
			$searchLibrary = Library::getSearchLibrary();
			if ($searchLibrary){
				$libraryLocationCode = $searchLibrary->ilsCode;
			}
			$searchLocation = Location::getSearchLocation();
			if ($searchLocation){
				$homeLocationCode = $searchLocation->code;
				$homeLocationLabel = $searchLocation->facetLabel;
			}else{
				$homeLocation = Location::getUserHomeLocation();
				if ($homeLocation){
					$homeLocationCode = $homeLocation->code;
					$homeLocationLabel = $homeLocation->facetLabel;
				}
			}

			$this->fastItems = array();

			if ($this->itemsFromIndex){
				$this->fastItems = array();
				foreach ($this->itemsFromIndex as $itemData){
					$locationCode = $itemData[2];
					$sharing = $itemData[4];
					$source = $itemData[5];
					$actions = $this->getActionsForItemFromIndexData($itemData);
					$libraryLabelObj = new Library();
					$libraryLabelObj->whereAdd("'$locationCode' LIKE CONCAT(ilsCode, '%') and ilsCode <> ''");
					$libraryLabelObj->selectAdd();
					$libraryLabelObj->selectAdd('displayName');
					if ($libraryLabelObj->find(true)){
						$libraryLabel = $libraryLabelObj->displayName;
					}else{
						$libraryLabel = $locationCode . ' Online';
					}
					$locationLabelObj = new Location();
					$locationLabelObj->whereAdd("'$locationCode' LIKE CONCAT(code, '%') and code <> ''");
					if ($locationLabelObj->find(true)){
						$locationLabel = $locationLabelObj->displayName;
					}else{
						$locationLabel = $locationCode . ' Online';
					}
					$this->fastItems[] = array(
							'location' => $locationCode,
							'callnumber' => '',
							'availability' => $itemData[3] == 'true',
							'holdable' => $this->isEContentHoldable($locationCode, $itemData),
							'inLibraryUseOnly' => false,
							'isLocalItem' => isset($libraryLocationCode) && strlen($libraryLocationCode) > 0 && strpos($locationCode, $libraryLocationCode) === 0,
							'isLibraryItem' => isset($homeLocationCode) && strlen($homeLocationCode) > 0 && strpos($locationCode, $homeLocationCode) === 0,
							'locationLabel' => 'Online',
							'libraryLabel' => $libraryLabel,
							'shelfLocation' => mapValue('shelf_location', $itemData[2]),
							'source' => 'Online ' . $source,
							'sharing' => $sharing,
							'actions' => $actions,
					);
				}
			}else{
				/** @var File_MARC_Data_Field[] $itemFields */
				$itemFields = $this->getMarcRecord()->getFields('989');
				foreach ($itemFields as $itemField){
					$locationCode = trim($itemField->getSubfield('d') != null ? $itemField->getSubfield('d')->getData() : '');
					$eContentData = trim($itemField->getSubfield('w') != null ? $itemField->getSubfield('w')->getData() : '');
					if ($eContentData && strpos($eContentData, ':') > 0){
						$eContentFieldData = explode(':', $eContentData);
						$source = trim($eContentFieldData[0]);
						$protectionType = trim($eContentFieldData[1]);
						if ($this->isValidProtectionType($protectionType)){
							if ($this->isValidForUser($locationCode, $eContentFieldData)){
								$libraryLabelObj = new Library();
								$libraryLabelObj->whereAdd("'$locationCode' LIKE CONCAT(ilsCode, '%') and ilsCode <> ''");
								$libraryLabelObj->selectAdd();
								$libraryLabelObj->selectAdd('displayName');
								if ($libraryLabelObj->find(true)){
									$libraryLabel = $libraryLabelObj->displayName;
								}else{
									$libraryLabel = $locationCode . ' Online';
								}
								$locationLabelObj = new Location();
								$locationLabelObj->whereAdd("'$locationCode' LIKE CONCAT(code, '%') and code <> ''");
								if ($locationLabelObj->find(true)){
									$locationLabel = $locationLabelObj->displayName;
								}else{
									$locationLabel = $locationCode . ' Online';
								}
								$actions = $this->getActionsForItem($itemField);

								//Add an item
								$item = array(
										'location' => $locationCode,
										'locationLabel' => $locationLabel,
										'libraryLabel' => $libraryLabel,
										'callnumber' => '',
										'availability' => $this->isAvailable(false), //We assume that all external econtent is always available
										'holdable' => $this->isEContentHoldable($locationCode, $eContentFieldData),
										'isLocalItem' => $this->isLocalItem($locationCode, $eContentFieldData),
										'isLibraryItem' => $this->isLibraryItem($locationCode, $eContentFieldData),
										'shelfLocation' => 'Online ' . $source,
										'source' => $source,
										'sharing' => $this->getSharing($locationCode, $eContentFieldData),
										'actions' => $actions,
								);

								$this->fastItems[] = $item;
							}
						}
					}
				}
			}
		}
		return $this->fastItems;
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

	function getRelatedRecords(){
		$parentRecords = parent::getRelatedRecords();
		$relatedRecords = array();
		$sources = $this->getSources();
		//Add a record per source
		foreach ($sources as $source){
			foreach ($parentRecords as $relatedRecord){
				$relatedRecord['source'] = $source;
				$relatedRecord['usageRestrictions'] = $this->getUsageRestrictions();
				$relatedRecords[] = $relatedRecord;
			}
		}

		return $relatedRecords;
	}

	function getSources(){
		$sources = array();
		$items = $this->getItemsFast();
		foreach ($items as $item){
			$sources[$item['source']] = $item['source'];
		}
		return $sources;
	}

	protected function isValidProtectionType($protectionType) {
		return in_array(strtolower($protectionType), $this->getValidProtectionTypes());
	}

	abstract function isEContentHoldable($locationCode, $eContentFieldData);
	abstract function isLocalItem($locationCode, $eContentFieldData);
	abstract function isLibraryItem($locationCode, $eContentFieldData);
	function getUsageRestrictions(){
		$fastItems = $this->getItemsFast();
		$shareWith = array();
		foreach ($fastItems as $fastItem){
			$sharing = $fastItem['sharing'];
			if ($sharing == 'shared'){
				return "Available to Everyone";
			}else if ($sharing == 'library'){
				$shareWith[] = $fastItem['libraryLabel'];
			}else if ($sharing == 'location'){
				$shareWith[] = $fastItem['locationLabel'];
			}
		}
		return 'Available to patrons of ' . implode(', ', $shareWith);
	}
	abstract function isValidForUser($locationCode, $eContentFieldData);

	public function getLinkUrl($useUnscopedHoldingsSummary = false) {
		global $interface;
		$baseUrl = $this->getRecordUrl();
		$linkUrl = $baseUrl . '?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page='  . $interface->get_template_vars('page');
		if ($useUnscopedHoldingsSummary){
			$linkUrl .= '&amp;searchSource=marmot';
		}else{
			$linkUrl .= '&amp;searchSource=' . $interface->get_template_vars('searchSource');
		}
		return $linkUrl;
	}

	function getQRCodeUrl(){
		global $configArray;
		return $configArray['Site']['url'] . '/qrcode.php?type=' . $this->getModuleName() . '&id=' . $this->getPermanentId();
	}

	abstract function getSharing($locationCode, $eContentFieldData);

	abstract function getActionsForItem($itemField);

	abstract function getActionsForItemFromIndexData($itemData);

}
