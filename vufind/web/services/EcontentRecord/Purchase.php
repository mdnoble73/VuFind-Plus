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

require_once 'Action.php';
require_once 'sys/eContent/EContentRecord.php';

class Purchase extends Action {

	function launch() {

		global $configArray;
		global $interface;
		$libraryName = $configArray['Site']['title'];
		
		//Grab the tracking data
		$store = urldecode(strip_tags($_GET['store']));
		$recordId = $_REQUEST['id'];
		$ipAddress = $_SERVER['REMOTE_ADDR'];
				
		// Retrieve Full Marc Record
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $recordId;
		if (!$eContentRecord->find(true)) {
			PEAR::raiseError(new PEAR_Error('Record Does Not Exist'));
		}

		$title = str_replace("/", "", $eContentRecord->title);

		if ($eContentRecord->purchaseUrl == null){
			switch ($store){
				case "Tattered Cover":
					$purchaseLinkUrl = "http://www.tatteredcover.com/search/apachesolr_search/" . urlencode($title). "?source=" . urlencode($libraryName);
					break;
				case "Barnes and Noble":
					$purchaseLinkUrl = "http://www.barnesandnoble.com/s/?title=" . urlencode($title) . "&source=" . urlencode($libraryName);
					break;
				case "Amazon":
					$purchaseLinkUrl = "http://www.amazon.com/s/ref=nb_sb_noss?url=search-alias%3Daps&field-keywords=" . urlencode($title) . "&source=" . urlencode($libraryName);
					break;
			}
		}else{
			// Process MARC Data
			$purchaseLinkUrl = $eContentRecord->purchaseUrl;
			
		}

		//Do not track purchases from Bots
		require_once('sys/BotChecker.php');
		if (!BotChecker::isRequestFromBot()){
			require_once 'sys/PurchaseLinkTracking.php';
			$tracking = new PurchaseLinkTracking();
			$tracking->ipAddress = $ipAddress;
			$tracking->recordId = 'econtentRecord' . $recordId;
			$tracking->store = $store;
			$insertResult = $tracking->insert();
		}
		
		//redirects them to the link they clicked
		if ($purchaseLinkUrl != ""){
			header( "Location:" .$purchaseLinkUrl);
		} else {
			PEAR::raiseError(new PEAR_Error("Failed to load link for this title."));
		}
			
	}

}