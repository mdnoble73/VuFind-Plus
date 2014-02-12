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
	abstract function getValidProtectionTypes();

	public function getItems(){
		return $this->getItemsFast();
	}
	private $fastItems = null;
	public function getItemsFast(){
		if ($this->fastItems == null){
			$this->fastItems = array();

			/** @var File_MARC_Data_Field[] $itemFields */
			$itemFields = $this->marcRecord->getFields('989');
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
							);
							$this->fastItems[] = $item;
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
		$itemFields = $this->marcRecord->getFields('989');
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
		$itemFields = $this->marcRecord->getFields('989');
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

	private function isValidProtectionType($protectionType) {
		return in_array(strtolower($protectionType), $this->getValidProtectionTypes());
	}

	abstract function isEContentHoldable($locationCode, $eContentFieldData);
	abstract function isLocalItem($locationCode, $eContentFieldData);
	abstract function isLibraryItem($locationCode, $eContentFieldData);
	abstract function getUsageRestrictions();
	abstract function isValidForUser($locationCode, $eContentFieldData);
}
