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
require_once 'sys/eContent/EContentItem.php';

class Link extends Action {

	function launch() {

		global $configArray;
		global $interface;

		//Grab the tracking data
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
		
		// Process MARC Data
		require_once 'sys/MarcLoader.php';
		$marcRecord = MarcLoader::loadMarcRecordFromRecord($record);
		if ($marcRecord) {
			$this->marcRecord = $marcRecord;
		} else {
			PEAR::raiseError(new PEAR_Error("Failed to load the MAC record for this title."));
		}
		
		$linkFields = $marcRecord->getFields('856') ;
		if ($linkFields){
			$cur856Index = 0;
			foreach ($linkFields as $marcField){
				$cur856Index++;
				if ($cur856Index == $field856Index){
					//Get the link
					if ($marcField->getSubfield('u')){
						$link = $marcField->getSubfield('u')->getData();
						$externalLink = $link;
					}
				}
			}
		}
		
		$linkParts = parse_url($externalLink);

		//Insert into the purchaseLinkTracking table
		require_once('sys/BotChecker.php');
		if (!BotChecker::isRequestFromBot()){
			require_once('sys/ExternalLinkTracking.php');
			$externalLinkTracking = new ExternalLinkTracking();
			$externalLinkTracking->ipAddress = $ipAddress;
			$externalLinkTracking->recordId = $recordId;
			$externalLinkTracking->linkUrl = $externalLink;
			$externalLinkTracking->linkHost = $linkParts['host'];
			$result = $externalLinkTracking->insert();
		}

		//redirects them to the link they clicked
		if ($externalLink != ""){
			header( "Location:" .$externalLink);
		} else {
			PEAR::raiseError(new PEAR_Error("Failed to load link for this record."));
		}
			
	}

}