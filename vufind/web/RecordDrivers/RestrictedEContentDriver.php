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
		return true;
	}
	function isLocalItem($locationCode, $eContentFieldData){
		$sharing = $this->getSharing($locationCode, $eContentFieldData);
		if ($sharing == 'shared'){
			return true;
		}else{
			return false;
		}
	}
	function isLibraryItem($locationCode, $eContentFieldData){
		$sharing = $this->getSharing($locationCode, $eContentFieldData);
		if ($sharing == 'shared'){
			return true;
		}else{
			return false;
		}
	}
	function isValidForUser($locationCode, $eContentFieldData){
		global $user;
		if (!$user){
			return true;
		}
		$sharing = $this->getSharing($locationCode, $eContentFieldData);
		if ($sharing == 'shared'){
			return true;
		}else{
			return false;
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
		return 'drm';
	}

	function getModuleName(){
		return 'RestrictedEContent';
	}
} 