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

	function isItemAvailable($itemId, $totalCopies){
		return true;
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
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);

		$moreDetailsOptions['copies'] = array(
			'label' => 'Copies',
			'body' => $interface->fetch('ExternalEContent/view-items.tpl'),
			'openByDefault' => true
		);

		$moreDetailsOptions['moreDetails'] = array(
			'label' => 'More Details',
			'body' => $interface->fetch('EcontentRecord/view-title-details.tpl'),
		);
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = array(
				'label' => 'Subjects',
				'body' => $interface->fetch('Record/view-subjects.tpl'),
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

	function getEContentFormat($fileOrUrl, $iType){
		return mapValue('econtent_itype_format', $iType);
	}

	/**
	 * @param string $itemId
	 * @param string $fileOrUrl
	 * @param string|null $acsId
	 * @return array
	 */
	function getActionsForItem($itemId, $fileOrUrl, $acsId){
		$actions = array();
		$title = 'Access Online';
		if (substr_compare($fileOrUrl, 'pdf', strlen($fileOrUrl)-3, strlen(3)) === 0){
			$title = 'Access PDF';
		}
		$actions[] = array(
				'url' => $fileOrUrl,
				'title' => $title,
				'requireLogin' => false,
		);
		return $actions;
	}
}
