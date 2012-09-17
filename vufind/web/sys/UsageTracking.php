<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once 'DB/DataObject.php';
require_once('sys/BotChecker.php');

class UsageTracking extends DB_DataObject{
	public $__table = 'usage_tracking';
	public $usageId;
	public $ipId;
	public $locationId;
	public $numPageViews;
	public $numHolds;
	public $numRenewals;
	public $trackingDate;

	public static function logTrackingData($trackingType, $trackingIncrement = 1, $ipLocation = null, $ipId = null){
		global $user;
		global $locationSingleton;

		try{
			if ($ipLocation == null){
				$ipLocation = $locationSingleton->getIPLocation();
			}
			if ($ipId == null){
				$ipId = $locationSingleton->getIPid();
			}

			//Usage Tracking Variables
			$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'none';
			$pageURL = $_SERVER['REQUEST_URI'];

			// If the Subnet (location) is unknown save as a -1
			//print_r($ipLocation);
			$requestFromBot = BotChecker::isRequestFromBot();
			if ($requestFromBot){
				$ipLocationId = -2;
				$locationId = -2;
				$ipId = -2;
			}else if ($ipLocation == null) {
				$ipLocationId = -1;
				$locationId = -1;
			} else {
				$ipLocationId = $ipLocation->locationId;
				$locationId = $ipLocationId;
			}

			// Set the tracking date for today and format it
			$trackingDate = strtotime(date('m/d/Y'));

			//Look up the date and the ipId in the usageTracking table and increment the pageView total by 1
			disableErrorHandler();
			$usageTracking = new UsageTracking();
			$usageTracking->ipId = $ipId;
			$usageTracking->trackingDate = $trackingDate;
			if ($usageTracking->find(true)){
				$usageTracking->$trackingType += $trackingIncrement;
				$result = $usageTracking->update();
			}else{
				$usageTracking->locationId = $locationId;
				$usageTracking->$trackingType = $trackingIncrement;
				$result = $usageTracking->insert();
			}
			enableErrorHandler();
			return $result;
		}catch (Exception $e){
			//Ignore errors while logging usage data since this can happen if tables aren't
			//Setup yet and if we throw an error, we can't do anything else.
		}
	}
}