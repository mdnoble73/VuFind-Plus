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
	private $eContentRecord;
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
		return 'Available to Everyone';
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
} 