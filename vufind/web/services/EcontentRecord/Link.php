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
		$itemId = $_REQUEST['itemId'];
				
		// Retrieve Full Marc Record
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $recordId;
		if (!$eContentRecord->find(true)) {
			PEAR::raiseError(new PEAR_Error('Record Does Not Exist'));
		}
		
		$eContentItem = new EContentItem();
		$eContentItem->id = $itemId;
		if (!$eContentItem->find(true)) {
			PEAR::raiseError(new PEAR_Error('Item Does Not Exist'));
		}
		
		$linkUrl = $eContentItem->link;
		$linkParts = parse_url($linkUrl);
		$title = str_replace("/", "", $eContentRecord->title);

		//Insert into the externalLinkTracking table
		require_once('sys/BotChecker.php');
		if (!BotChecker::isRequestFromBot()){
			require_once('sys/ExternalLinkTracking.php');
			$externalLinkTracking = new ExternalLinkTracking();
			$externalLinkTracking->ipAddress = $ipAddress;
			$externalLinkTracking->recordId = "econtentRecord" . $recordId;
			$externalLinkTracking->linkUrl = $linkUrl;
			$externalLinkTracking->linkHost = $linkParts['host'];
			$result = $externalLinkTracking->insert();
		}

		//redirects them to the link they clicked
		if ($linkUrl != ""){
			header( "Location:" .$linkUrl);
		} else {
			PEAR::raiseError(new PEAR_Error("Failed to load link for this title."));
		}
			
	}

}