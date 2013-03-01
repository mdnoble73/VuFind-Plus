<?php
class OverDriveDriverFactory{
	static function getDriver(){
		global $configArray;
		if (!isset($configArray['OverDrive']['interfaceVersion']) || $configArray['OverDrive']['interfaceVersion'] == 1){
			require_once 'Drivers/OverDriveDriver.php';
			$overDriveDriver = new OverDriveDriver();
		}else{
			require_once 'Drivers/OverDriveDriver2.php';
			$overDriveDriver = new OverDriveDriver2();
		}
		return $overDriveDriver;
	}
}