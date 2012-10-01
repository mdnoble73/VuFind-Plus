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

require_once 'services/MyResearch/lib/Resource.php';
require_once 'services/MyResearch/lib/User.php';

class Home extends Action
{
	private $user;

	/**
	 * Process notifications from the ACS server when an item is checked out
	 * or returned.
	 **/
	function launch()
	{
		global $configArray;

		global $logger;
		$post_body = file_get_contents('php://input');
		if (isset($_POST['body'])){
			$post_body = $_POST['body'];
		}
		$logger->log("POST_BODY $post_body", PEAR_LOG_INFO);

		$notificationData = new SimpleXMLElement($post_body);
		//Check to see of the EPUB is being fulfilled or returned
		$isFulfilled = strcasecmp((string)$notificationData->body->fulfilled, 'true') == 0;
		$isReturned = strcasecmp((string)$notificationData->body->returned, 'true') == 0;
		//Get the transactionId
		$transactionId = (string)$notificationData->body->transaction;
		//Get the user acsId
		$userAcsId = (string)$notificationData->body->user;

		if ($isFulfilled){
			if ($isReturned){
				$logger->log("Transaction $transactionId was returned, returning it in the catalog.", PEAR_LOG_INFO);
			}else{
				$logger->log("Transaction $transactionId was fulfilled, checking it out in the catalog.", PEAR_LOG_INFO);
			}
		}else{
			$logger->log("Transaction $transactionId was not fulfilled or returned, ignoring it.", PEAR_LOG_INFO);
			exit();
		}
		//Add a log entry for debugging.
		$logger->log("Preparing to insert log entry for transaction", PEAR_LOG_INFO);

		require_once('sys/eContent/AcsLog.php');
		$acsLog = new AcsLog();
		$acsLog->acsTransactionId = $transactionId;
		$acsLog->fulfilled = $isFulfilled;
		$acsLog->returned = $isReturned;
		$acsLog->userAcsId = $userAcsId;
		$ret = $acsLog->insert();
		$logger->log("Inserted log entry result: $ret", PEAR_LOG_INFO);

		//Update the database as appropriate
		//Get the chckd out item for the transaction Id
		require_once('sys/eContent/EContentCheckout.php');
		$checkout = new EContentCheckout();
		$checkout->acsTransactionId = $transactionId;
		if ($checkout->find(true)){
			//Update the checkout to show
			if ($isReturned){
				if ($checkout->status == 'out'){
					//return the item
					require_once 'Drivers/EContentDriver.php';
					$driver = new EContentDriver();
					$driver->returnRecord($checkout->recordId);
				}
			}else{
				//Update the checked out item with information from acs
				if ($checkout->downloadedToReader == 0){
					$checkout->downloadedToReader = 1;
					$checkout->dateFulfilled = time();
					$checkout->userAcsId = $userAcsId;
					$checkout->update();
				}
				//Mark that the record is downloaded
				require_once('sys/eContent/EContentRecord.php');
				$eContentRecord = new EContentRecord();
				$eContentRecord->id = $checkout->recordId;
				$eContentRecord->find(true);
				require_once 'Drivers/EContentDriver.php';
				$driver = new EContentDriver();
				$driver->recordEContentAction($checkout->recordId, 'Download', $eContentRecord->accessType);
			}
		}
	}
}