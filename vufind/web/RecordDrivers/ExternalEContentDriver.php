<?php
/**
 * Record Driver to Handle the display of eContent that is stored in the ILS, but accessed
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/7/14
 * Time: 9:48 AM
 */

require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
class ExternalEContentDriver extends MarcRecord{
	public function getItems(){
		return $this->getItemsFast();
	}
	private $fastItems = null;
	public function getItemsFast(){
		if ($this->fastItems == null){
			$this->fastItems = array();
			$itemFields = $this->marcRecord->getFields('989');
			foreach ($itemFields as $itemField){
				$locationCode = trim($itemField->getSubfield('d') != null ? $itemField->getSubfield('d')->getData() : '');
				$eContentData = trim($itemField->getSubfield('w') != null ? $itemField->getSubfield('w')->getData() : '');
				if ($eContentData && strpos($eContentData, ':') > 0){
					$eContentFieldData = explode(':', $eContentData);
					$source = trim($eContentFieldData[0]);
					$protectionType = trim($eContentFieldData[1]);
					$isLocalItem = false;
					$isLibraryItem = false;
					if ($locationCode == 'mdl'){
						$isLocalItem = true;
						$isLibraryItem = true;
					}
					if (strcasecmp($protectionType, 'external') == 0){
						//Add an item
						$item = array(
							'location' => $locationCode,
							'callnumber' => '',
							'availability' => true, //We assume that all external econtent is always available
							'holdable' => false,
							'isLocalItem' => $isLocalItem,
							'isLibraryItem' => $isLibraryItem,
							'locationLabel' => 'Online ' . $source,
							'shelfLocation' => 'Online ' . $source,
						);
						$this->fastItems[] = $item;
					}
				}
			}
		}
		return $this->fastItems;
	}

	function getFormat(){
		$result = array();
		$itemFields = $this->marcRecord->getFields('989');
		foreach ($itemFields as $item){
			$subfieldW = $item->getSubfield('w');
			if ($subfieldW != null){
				if (strpos($subfieldW->getData(), ':') !== FALSE){
					$eContentFieldData = explode(':', $subfieldW->getData());
					$source = trim($eContentFieldData[0]);
					$protectionType = trim($eContentFieldData[1]);
					if (strcasecmp($protectionType, 'external') == 0){
						$result[] = $source;
					}
				}
			}
		}
		return $result;
	}

	function getRecordUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		return $configArray['Site']['path'] . '/ExternalEContent/' . $recordId;
	}
} 