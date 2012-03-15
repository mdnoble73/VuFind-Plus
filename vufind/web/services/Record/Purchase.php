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

class Purchase extends Action {

	function launch() {

		global $configArray;
		global $interface;

		//Grab the tracking data
		$store = urldecode(strip_tags($_GET['store']));
		$recordId = $_REQUEST['id'];
		$ipAddress = $_SERVER['REMOTE_ADDR'];
		$field856Index = isset($_REQUEST['index']) ? $_REQUEST['index'] : null;

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$this->db->debug = true;
		}

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($recordId))) {
			PEAR::raiseError(new PEAR_Error('Record Does Not Exist'));
		}
		$this->record = $record;
		$interface->assign('record', $record);

		$titleTerm = $record["title"];
		$title = str_replace("/", "", $titleTerm);

		if ($field856Index == null){
			switch ($store){
				case "Tattered Cover":
					$purchaseLinkUrl = "http://www.tatteredcover.com/search/apachesolr_search/" . urlencode($title) . "?source=dcl";
					break;
				case "Barnes and Noble":
					$purchaseLinkUrl = "http://www.barnesandnoble.com/s/?title=" . urlencode($title) . "&source=dcl";
					break;
				case "Amazon":
					$purchaseLinkUrl = "http://www.amazon.com/s/ref=nb_sb_noss?url=search-alias%3Daps&field-keywords=" . urlencode($title) . "&source=dcl";
					break;
			}
		}else{
			// Process MARC Data
			require_once 'sys/MarcLoader.php';
			$marcRecord = MarcLoader::loadMarcRecordFromRecord($record);
			if ($marcRecord) {
				$this->marcRecord = $marcRecord;
			} else {
				PEAR::raiseError(new PEAR_Error("Failed to load the MAC record for this title."));
			}
			
			$linkFields =$marcRecord->getFields('856') ;
			if ($linkFields){
				$cur856Index = 0;
				foreach ($linkFields as $marcField){
					$cur856Index++;
					if ($cur856Index == $field856Index){
						//Get the link
						if ($marcField->getSubfield('u')){
							$link = $marcField->getSubfield('u')->getData();
							$purchaseLinkUrl = $link;
						}
					}
				}
			}
		}

		//Do not track purchases from Bots
		require_once('sys/BotChecker.php');
		if (!BotChecker::isRequestFromBot()){
			require_once 'sys/PurchaseLinkTracking.php';
			$tracking = new PurchaseLinkTracking();
			$tracking->ipAddress = $ipAddress;
			$tracking->recordId = $recordId;
			$tracking->store = $store;
			$insertResult = $tracking->insert();
		}

		//redirects them to the link they clicked
		if ($purchaseLinkUrl != ""){
			header( "Location:" .$purchaseLinkUrl);
		} else {
			PEAR::raiseError(new PEAR_Error("Failed to load the store information for this title."));
		}
			
	}

}