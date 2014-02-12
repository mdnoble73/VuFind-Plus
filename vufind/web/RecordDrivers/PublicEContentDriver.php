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
class PublicEContentDriver extends BaseEContentDriver{
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

	protected function getRecordType(){
		return 'free';
	}
} 