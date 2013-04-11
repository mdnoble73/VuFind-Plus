<?php

require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';

/**
 * Loads information from OverDrive Next Gen interface (version 2) and provides updates to OverDrive by screen scraping
 * Will be updated to use APIs when APIs become available.
 *
 * Copyright (C) Douglas County Libraries 2011.
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
 * @version 1.0
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Douglas County Libraries 2011.
 */
class OverDriveDriver2 {
	public $version = 2;

	/**
	 * Retrieves the URL for the cover of the record by screen scraping OverDrive.
	 * ..
	 * @param EContentRecord $record
	 * @return string
	 */
	public function getCoverUrl($record){
		$overDriveId = $record->getOverDriveId();
		//Get metadata for the record
		$metadata = $this->getProductMetadata($overDriveId);
		if (isset($metadata->images) && isset($metadata->images->cover)){
			return $metadata->images->cover->href;
		}else{
			return "";
		}
	}

	private function _connectToAPI($forceNewConnection = false){
		global $memCache;
		$tokenData = $memCache->get('overdrive_token');
		if ($forceNewConnection || $tokenData == false){
			global $configArray;
			$ch = curl_init("https://oauth.overdrive.com/token");
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8'));
			curl_setopt($ch, CURLOPT_USERPWD, $configArray['OverDrive']['clientKey'] . ":" . $configArray['OverDrive']['clientSecret']);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$return = curl_exec($ch);
			curl_close($ch);
			$tokenData = json_decode($return);
			if ($tokenData){
				$memCache->set('overdrive_token', $tokenData, 0, $tokenData->expires_in - 10);
			}
		}
		return $tokenData;
	}

	public function _callUrl($url){
		for ($i = 1; $i < 5; $i++){
			$tokenData = $this->_connectToAPI($i != 1);
			if ($tokenData){
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
				curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$tokenData->token_type} {$tokenData->access_token}"));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				$return = curl_exec($ch);
				curl_close($ch);
				$returnVal = json_decode($return);
				//print_r($returnVal);
				if ($returnVal != null){
					if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
						return $returnVal;
					}
				}
			}
			usleep(500);
		}
		return null;
	}

	public function getLibraryAccountInformation(){
		global $configArray;
		$libraryId = $configArray['OverDrive']['accountId'];
		return $this->_callUrl("http://api.overdrive.com/v1/libraries/$libraryId");
	}

	public function getAdvantageAccountInformation(){
		global $configArray;
		$libraryId = $configArray['OverDrive']['accountId'];
		return $this->_callUrl("http://api.overdrive.com/v1/libraries/$libraryId/advantageAccounts");
	}

	public function getProductsInAccount($productsUrl = null, $start = 0, $limit = 25){
		global $configArray;
		if ($productsUrl == null){
			$libraryId = $configArray['OverDrive']['accountId'];
			$productsUrl = "http://api.overdrive.com/v1/collections/$libraryId/products";
		}
		$productsUrl .= "?offeset=$start&limit=$limit";
		return $this->_callUrl($productsUrl);
	}

	public function getProductMetadata($overDriveId, $productsKey = null){
		global $configArray;
		if ($productsKey == null){
			$productsKey = $configArray['OverDrive']['productsKey'];
		}
		$overDriveId= strtoupper($overDriveId);
		$metadataUrl = "http://api.overdrive.com/v1/collections/$productsKey/products/$overDriveId/metadata";
		//echo($metadataUrl);
		return $this->_callUrl($metadataUrl);
	}

	public function getProductAvailability($overDriveId, $productsKey = null){
		global $configArray;
		if ($productsKey == null){
			$productsKey = $configArray['OverDrive']['productsKey'];
		}
		$availabilityUrl = "http://api.overdrive.com/v1/collections/$productsKey/products/$overDriveId/availability";
		//print_r($availabilityUrl);
		return $this->_callUrl($availabilityUrl);
	}

	public function _parseOverDriveCheckedOutItems($checkedOutSection, $overDriveInfo){
		global $user;
		global $logger;
		$bookshelf = array();
		$bookshelf['items'] = array();
		if (preg_match_all('/<li class="mobile-four bookshelf-title-li".*?data-transaction="(.*?)".*?>.*?<div class="is-enhanced" data-transaction=".*?" title="(.*?)".*?<img.*?class="lrgImg" src="(.*?)".*?data-crid="(.*?)".*?<div.*?class="dwnld-container".*?>(.*?)<div class="expiration-date".*?<noscript>(.*?)<\/noscript>.*?data-earlyreturn="(.*?)"/si', $checkedOutSection, $bookshelfInfo, PREG_SET_ORDER)) {
			//echo("\r\n");
			//print_r($bookshelfInfo);
			for ($i = 0; $i < count($bookshelfInfo); $i++){
				$bookshelfItem = array();
				$group = 1;
				$bookshelfItem['transactionId'] = $bookshelfInfo[$i][$group++];
				$bookshelfItem['title'] = $bookshelfInfo[$i][$group++];
				$bookshelfItem['imageUrl'] = $bookshelfInfo[$i][$group++];
				$bookshelfItem['overDriveId'] = $bookshelfInfo[$i][$group++];
				//Figure out which eContent record this is for.
				$eContentRecord = new EContentRecord();
				$eContentRecord->externalId = $bookshelfItem['overDriveId'];
				$eContentRecord->source = 'OverDrive';
				$eContentRecord->status = 'active';
				if ($eContentRecord->find(true)){
					$bookshelfItem['recordId'] = $eContentRecord->id;

					//Get Rating
					require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
					$econtentRating = new EContentRating();
					$econtentRating->recordId = $eContentRecord->id;
					$bookshelfItem['ratingData'] = $econtentRating->getRatingData($user, false);
				}else{
					$bookshelfItem['recordId'] = -1;
				}
				$formatSection = $bookshelfInfo[$i][$group++];
				//print_r("\r\nFormat Section $i\r\n$formatSection\r\n");
				$bookshelfItem['expiresOn'] = $bookshelfInfo[$i][$group++];
				$bookshelfItem['earlyReturn'] = $bookshelfInfo[$i][$group++];
				$bookshelfItem['overdriveRead'] = false;
				//Check to see if a format has been selected
				if (preg_match_all('/<li class="dwnld-litem.*?".*?data-fmt="(.*?)".*?data-lckd="(.*?)".*?data-enhanced="(.*?)".*?<a.*?href="(.*?)".*?>(.*?)<\/a>/si', $formatSection, $formatOptions, PREG_SET_ORDER)) {
					$bookshelfItem['formatSelected'] = false;
					$bookshelfItem['formats'] = array();
					for ($fmt = 0; $fmt < count($formatOptions); $fmt++){
						$format = array();
						$format['id'] = $formatOptions[$fmt][1];
						$format['locked'] = $formatOptions[$fmt][2]; //This means the format is selected
						$format['enhanced'] = $formatOptions[$fmt][3];
						$format['name'] = $formatOptions[$fmt][5];
						if ($format['locked'] == 1){
							$bookshelfItem['formatSelected'] = true;
							$bookshelfItem['selectedFormat'] = $format;
							$bookshelfItem['downloadUrl'] = $overDriveInfo['baseLoginUrl'] . 'BANGPurchase.dll?Action=Download&ReserveID=' . $bookshelfItem['overDriveId'] . '&FormatID=' . $format['id'] . '&url=MyAccount.htm';
						}
						if ($format['id'] == 610){
							$bookshelfItem['overdriveRead'] = true;
							$bookshelfItem['overdriveReadUrl'] = $overDriveInfo['baseLoginUrl'] . 'BANGPurchase.dll?Action=Download&ReserveID=' . $bookshelfItem['overDriveId'] . '&FormatID=' . $format['id'] . '&url=MyAccount.htm';
						}else{
							$bookshelfItem['formats'][] = $format;
						}
					}
				}
				//Parse special formats
				/*if (preg_match('/<div class="dwnld-kindle" data-transaction=".*?">(.*?)<\/div>.*?<div class="dwnld-odread" data-transaction=".*?".*?>(.*?)<\/div>.*?<div class="dwnld-locked-in" data-transaction=".*?">(.*?)<\/div>/si', $formatSection, $specialDownloads)) {
					$bookshelfItem['kindle'] = $specialDownloads[1];
					$overDriveRead = $specialDownloads[2];
					if (strlen($overDriveRead) > 0){
						$bookshelfItem['overdriveRead'] = true;
						if (preg_match('/href="(.*?)"/si', $overDriveRead, $matches)){
							$bookshelfItem['overdriveReadUrl'] = $overDriveInfo['baseUrlWithSession'] . $matches[1];
						}
					}else{
						$bookshelfItem['overdriveRead'] = false;
					}
					//$bookshelfItem['overdriveRead'] = $specialDownloads[2];
					$bookshelfItem['lockedIn'] = $specialDownloads[3];
					$logger->log("Matched special formats $formatSection", PEAR_LOG_DEBUG);
				}else{
					$logger->log("Did not match special formats $formatSection", PEAR_LOG_DEBUG);
				}*/

				$bookshelf['items'][] = $bookshelfItem;
			}
		}
		return $bookshelf;
	}

	private function _parseOverDriveHolds($holdsSection){
		global $user;
		$holds = array();
		$holds['available'] = array();
		$holds['unavailable'] = array();
		//Match holds
		//Get the individual holds by splitting the section based on each <li class="mobile-four">
		//Trim to the first li
		$firstTitlePos = strpos($holdsSection, '<li class="mobile-four">');
		$holdsSection = substr($holdsSection, $firstTitlePos);
		$heldTitles = explode('<li class="mobile-four">', $holdsSection);
		$i = 0;
		foreach ($heldTitles as $titleHtml){
			//echo("\r\nSection " . $i++ . "\r\n$titleHtml");
			if (preg_match('/<div class="coverID">.*?<a href="ContentDetails\\.htm\\?id=(.*?)">.*?<img class="lrgImg" src="(.*?)".*?<div class="trunc-title-line".*?title="(.*?)".*?<div class="trunc-author-line".*?title="(.*?)".*?<div class="(?:holds-info)?".*?>(.*)/si', $titleHtml, $holdInfo)){
				$hold = array();
				$grpCtr = 1;
				$hold['overDriveId'] = $holdInfo[$grpCtr++];
				$hold['imageUrl'] = $holdInfo[$grpCtr++];
				$eContentRecord = new EContentRecord();
				$eContentRecord->externalId = $hold['overDriveId'];
				$eContentRecord->source = 'OverDrive';
				$eContentRecord->status = 'active';
				if ($eContentRecord->find(true)){
					$hold['recordId'] = $eContentRecord->id;

					//Get Rating
					require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
					$econtentRating = new EContentRating();
					$econtentRating->recordId = $eContentRecord->id;
					$hold['ratingData'] = $econtentRating->getRatingData($user, false);
				}else{
					$hold['recordId'] = -1;
				}
				$hold['title'] = $holdInfo[$grpCtr++];
				$hold['author'] = $holdInfo[$grpCtr++];

				$holdDetails = $holdInfo[$grpCtr++];

				if (preg_match('/<h6 class="holds-wait-position">(.*?)<\/h6>.*?<h6 class="holds-wait-email">(.*?)<\/h6>/si', $holdDetails, $holdDetailInfo)) {
					$notificationInformation = $holdDetailInfo[1];
					if (preg_match('/You are (?:patron|user) <b>(\\d+)<\/b> out of <b>(\\d+)<\/b> on the waiting list/si', $notificationInformation, $notifyInfo)) {
						$hold['holdQueuePosition'] = $notifyInfo[1];
						$hold['holdQueueLength'] = $notifyInfo[2];
					}else{
						echo($notificationInformation);
					}
					$hold['notifyEmail'] = $holdDetailInfo[2];
					$holds['unavailable'][] = $hold;
				}elseif (preg_match('/<div id="borrowingPeriodHold"><div>(.*?)<\/div>.*?new Date \("(.*?)"\)/si', $holdDetails, $holdDetailInfo)){
					///print_r($holdDetails);
					$hold['emailSent'] = $holdDetailInfo[2];
					$hold['notificationDate'] = strtotime($hold['emailSent']);
					$hold['expirationDate'] = $hold['notificationDate'] + 3 * 24 * 60 * 60;
					$holds['available'][] = $hold;
				}
			}
		}
		return $holds;
	}

	private function _parseLendingOptions($lendingPeriods){
		$lendingOptions = array();
		//print_r($lendingPeriods);
		if (preg_match('/<script>.*?var hazVariableLending.*?<\/script>.*?<noscript>(.*?)<\/noscript>/si', $lendingPeriods, $matches)){
			preg_match_all('/<li>\\s?\\d+\\s-\\s(.*?)<select name="(.*?)">(.*?)<\/select><\/li>/si', $matches[1], $lendingPeriodInfo, PREG_SET_ORDER);
			for ($i = 0; $i < count($lendingPeriodInfo); $i++){
				$lendingOption = array();
				$lendingOption['name'] = $lendingPeriodInfo[$i][1];
				$lendingOption['id'] = $lendingPeriodInfo[$i][2];
				$options = $lendingPeriodInfo[$i][3];
				$lendingOption['options']= array();
				preg_match_all('/<option value="(.*?)".*?(selected="selected")?>(.*?)<\/option>/si', $options, $optionInfo, PREG_SET_ORDER);
				for ($j = 0; $j < count($optionInfo); $j++){
					$option = array();
					$option['value'] = $optionInfo[$j][1];
					$option['selected'] = strlen($optionInfo[$j][2]) > 0;
					$option['name'] = $optionInfo[$j][3];
					$lendingOption['options'][] = $option;
				}
				$lendingOptions[] = $lendingOption;
			}
		}
		//print_r($lendingOptions);
		return $lendingOptions;
	}

	/**
	 * Loads information about items that the user has checked out in OverDrive
	 *
	 * @param User $user
	 * @param array $overDriveInfo optional array of information loaded from _loginToOverDrive to improve performance.
	 *
	 * @return array
	 */
	public function getOverDriveCheckedOutItems($user, $overDriveInfo = null){
		global $memCache;
		global $configArray;
		global $timer;

		$summary = $this->getAccountDetails($user);
		$checkedOutTitles = $summary['checkedOut'];
		return $checkedOutTitles;
	}

	public function getOverDriveHolds($user, $overDriveInfo = null){
		global $memCache;
		global $configArray;
		global $timer;

		$summary = $this->getAccountDetails($user);
		$holds = array();
		$holds['holds'] = $summary['holds'];
		return $holds;
	}

	/**
	 * Returns a summary of information about the user's account in OverDrive.
	 *
	 * @param User $user
	 * @param array $overDriveInfo optional array of information loaded from _loginToOverDrive to improve performance.
	 *
	 * @return array
	 */
	public function getOverDriveSummary($user){
		$apiURL = "https://temp-patron.api.overdrive.com/Marmot/Marmot/" . $user->cat_password;
		$summaryResultRaw = file_get_contents($apiURL);
		$summary = array(
			'numCheckedOut' => 0,
			'numAvailableHolds' => 0,
			'numUnavailableHolds' => 0,
		);
		if ($summaryResultRaw != "Library patron not found."){
			$summaryResults = json_decode($summaryResultRaw, true);
			$summary['numCheckedOut'] = $summaryResults['CheckoutCount'];
			$summary['numAvailableHolds'] = $summaryResults['AvailableHoldCount'];
			$summary['numUnavailableHolds'] = $summaryResults['PendingHoldCount'];
		}
		return $summary;
	}

	public function getAccountDetails($user){
		global $memCache;
		global $configArray;
		global $timer;
		global $logger;

		$summary = $memCache->get('overdrive_summary_' . $user->id);
		if ($summary == false || isset($_REQUEST['reload'])){
			$summary = array();
			$ch = curl_init();

			$overDriveInfo = $this->_loginToOverDrive($ch, $user);
			//Navigate to the account page
			//Load the My Holds page
			//print_r("Account url: " . $overDriveInfo['accountUrl']);
			curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $overDriveInfo['accountUrl']);
			$accountPage = curl_exec($overDriveInfo['ch']);

			//Get bookshelf information
			if (preg_match('/<li .*?id="myAccount1Tab".*?>(.*?)<li [^>\/]*?id="myAccount2Tab".*?>/s', $accountPage, $matches)) {
				$checkedOutSection = $matches[1];
				//print_r($checkedOutSection);
				//Get a list of titles that are checked out
				$checkedOut = $this->_parseOverDriveCheckedOutItems($checkedOutSection, $overDriveInfo);
				//print_r($checkedOut);
				$summary['numCheckedOut'] = count($checkedOut['items']);
				$summary['checkedOut'] = $checkedOut;
			}

			//Get holds
			if (preg_match('/<li .*?id="myAccount2Tab".*?>(.*?)<li [^>\/]*?id="myAccount3Tab".*?>/s', $accountPage, $matches)) {
				$holdsSection = $matches[1];
				//print_r($holdsSection);
				//Get a list of titles that are checked out
				$holds = $this->_parseOverDriveHolds($holdsSection);
				//echo("<br>\r\n");
				//print_r($holds);
				//echo("<br>\r\n");
				$summary['numAvailableHolds'] = count($holds['available']);
				$summary['numUnavailableHolds'] = count($holds['unavailable']);
				$summary['holds'] = $holds;
			}

			//Get lending options
			if (preg_match('/<li id="myAccount4Tab">(.*?)<!-- myAccountContent -->/s', $accountPage, $matches)) {
				$lendingOptionsSection = $matches[1];
				$lendingOptions = $this->_parseLendingOptions($lendingOptionsSection);
				$summary['lendingOptions'] = $lendingOptions;
			}else{
				$start = strpos($accountPage, '<li id="myAccount4Tab">') + strlen('<li id="myAccount4Tab">');
				$end = strpos($accountPage, '<!-- myAccountContent -->');
				$logger->log("Lending options from $start to $end", PEAR_LOG_DEBUG);

				$lendingOptionsSection = substr($accountPage, $start, $end);
				$lendingOptions = $this->_parseLendingOptions($lendingOptionsSection);
				$summary['lendingOptions'] = $lendingOptions;
			}

			curl_close($ch);

			$timer->logTime("Finished loading titles from overdrive summary");
			$memCache->set('overdrive_summary_' . $user->id, $summary, 0, $configArray['Caching']['overdrive_summary']);
		}

		return $summary;
	}

	/**
	 * Places a hold on an item within OverDrive
	 *
	 * @param string $overDriveId
	 * @param int $format
	 * @param User $user
	 */
	public function placeOverDriveHold($overDriveId, $format, $user){
		global $memCache;
		global $configArray;
		global $logger;

		$holdResult = array();
		$holdResult['result'] = false;
		$holdResult['message'] = '';

		$ch = curl_init();
		$overDriveInfo = $this->_loginToOverDrive($ch, $user);
		if ($overDriveInfo['result'] == false){
			$holdResult = $overDriveInfo;
		}else{

			//Switch back to get method
			curl_setopt($overDriveInfo['ch'], CURLOPT_HTTPGET, true);

			//Open the record page
			$contentInfoPage = $overDriveInfo['contentInfoPage'] . "?ID=" . $overDriveId;
			curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $contentInfoPage);
			$recordPage = curl_exec($overDriveInfo['ch']);
			$recordPageInfo = curl_getinfo($overDriveInfo['ch']);
			$logger->log("View record " . $contentInfoPage, PEAR_LOG_DEBUG);

			//Navigate to place a hold page
			$waitingListUrl = $overDriveInfo['waitingListUrl'];
			if ($format == "" || $format == 'undefined'){
				$format = "";
				if (preg_match('/<a href="BANGAuthenticate\.dll\?Action=AuthCheck&ForceLoginFlag=0&URL=WaitingListForm.htm%3FID=(.*?)%26Format=(.*?)" class="radius large button details-title-button" data-checkedout="(.*?)" data-contentstatus="(.*?)">Place a Hold<\/a>/si', $recordPage, $formatInfo)){
					$format = $formatInfo[2];
				}else{
					$logger->log("Did not find hold button for this title to retrieve format", PEAR_LOG_INFO);
					$holdResult['result'] = false;
					$holdResult['message'] = "This title is available for checkout.";
					$holdResult['availableForCheckout'] = true;
					return $holdResult;
				}
			}
			$waitingListUrl .= '%3FID=' . $overDriveId . '%26Format=' . $format;
			//echo($waitingListUrl . "\r\n");
			curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $waitingListUrl);
			$logger->log("Click place a hold button " . $waitingListUrl, PEAR_LOG_DEBUG);
			$setEmailPage = curl_exec($overDriveInfo['ch']);
			$setEmailPageInfo = curl_getinfo($ch);
			if (preg_match('/already placed a hold or borrowed this title/', $setEmailPage)){
				$holdResult['result'] = false;
				$holdResult['message'] = "We're sorry, but you are already on the waiting list for the selected title or have it checked out.";
			}else{

				$secureBaseUrl = preg_replace('~[^/.]+?.htm.*~', '', $setEmailPageInfo['url']);

				//Login (again)
				curl_setopt($overDriveInfo['ch'], CURLOPT_POST, true);
				$barcodeProperty = isset($configArray['Catalog']['barcodeProperty']) ? $configArray['Catalog']['barcodeProperty'] : 'cat_username';
				$barcode = $user->$barcodeProperty;
				$postParams = array(
					'LibraryCardNumber' => $barcode,
					'URL' => 'MyAccount.htm',
				);
				if (isset($configArray['OverDrive']['LibraryCardILS']) && strlen($configArray['OverDrive']['LibraryCardILS']) > 0){
					$postParams['LibraryCardILS'] = $configArray['OverDrive']['LibraryCardILS'];
				}
				$post_items = array();
				foreach ($postParams as $key => $value) {
					$post_items[] = $key . '=' . urlencode($value);
				}
				$post_string = implode ('&', $post_items);
				curl_setopt($overDriveInfo['ch'], CURLOPT_POSTFIELDS, $post_string);
				curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $secureBaseUrl . 'BANGAuthenticate.dll');
				$waitingListPage = curl_exec($overDriveInfo['ch']);
				$waitingListPageInfo = curl_getinfo($overDriveInfo['ch']);
				//echo($waitingListPage);
				if (preg_match('/already on/', $waitingListPage)){
					$holdResult['result'] = false;
					$holdResult['message'] = "We're sorry, but you are already on the waiting list for the selected title or have it checked out.";
				}else{
					//Get the format from the form

					//Fill out the email address to use for notification
					//echo($user->overdriveEmail . "\r\n");
					$postParams = array(
						'ID' => $overDriveId,
						'Format' => $format,
						'URL' => 'WaitingListConfirm.htm',
						'Email' => $user->overdriveEmail,
						'Email2' => $user->overdriveEmail,
					);
					$post_items = array();
					foreach ($postParams as $key => $value) {
						$post_items[] = $key . '=' . urlencode($value);
					}
					$post_string = implode ('&', $post_items);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
					curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $secureBaseUrl . "BANGAuthenticate.dll?Action=LibraryWaitingList");
					$waitingListConfirm = curl_exec($overDriveInfo['ch']);
					$logger->log("Submitting email for notification {$secureBaseUrl}BANGAuthenticate.dll?Action=LibraryWaitingList  $post_string"  , PEAR_LOG_INFO);
					//$logger->log($waitingListConfirm, PEAR_LOG_INFO);

					$waitingListConfirm = strip_tags($waitingListConfirm, "'<p><a><li><ul><div><em><b>'");
					if (preg_match('/<section id="mainContent" class=".*?">(.*?)<\/section>/is', $waitingListConfirm, $matches)){
						$logger->log("Found main content section", PEAR_LOG_INFO);
						$mainSection = $matches[1];

						if (preg_match('/already on/si', $mainSection)){
							$holdResult['result'] = false;
							$holdResult['message'] = 'This title is already on hold or checked out to you.';
						}elseif (preg_match('/did not complete all of the required fields/', $mainSection)){
							$holdResult['result'] = false;
							$holdResult['message'] = 'You must provide an e-mail address to request titles from OverDrive.  Please add an e-mail address to your profile.';
						}elseif (preg_match('/reached the request \(hold\) limit of \d+ titles./', $mainSection)){
							$holdResult['result'] = false;
							$holdResult['message'] = 'You have reached the maximum number of holds for your account.';
						}elseif (preg_match('/Some of our digital titles are only available for a limited time\. This title may be available in the future\. Be sure to check back/', $waitingListConfirm)){
							$holdResult['result'] = false;
							$holdResult['message'] = 'This title is no longer available.  Some of our digital titles are only available for a limited time. This title may be available in the future. Be sure to check back.';
						}else{
							$holdResult['result'] = false;
							$holdResult['message'] = 'There was an error placing your hold.';
							global $logger;
							$logger->log("Placing hold on OverDrive item. OverDriveId ". $overDriveId, PEAR_LOG_INFO);
							$logger->log('URL: '.$secureBaseUrl . "BANGAuthenticate.dll?Action=LibraryWaitingList $post_string\r\n" . $mainSection ,PEAR_LOG_INFO);
						}
					}elseif (preg_match('/Unfortunately this title is not available to your library at this time./', $waitingListConfirm)){
						$holdResult['result'] = false;
						$holdResult['message'] = 'This title is not available to your library at this time.';
					}elseif (preg_match('/You will receive an email when the title becomes available./', $waitingListConfirm)){
						$holdResult['result'] = true;
						$holdResult['message'] = 'Your hold was placed successfully.';

						$memCache->delete('overdrive_summary_' . $user->id);

						//Record that the entry was checked out in strands
						global $configArray;
						if (isset($configArray['Strands']['APID']) && $user->disableRecommendations == 0){
							//Get the record for the item
							$eContentRecord = new EContentRecord();
							$eContentRecord->whereAdd("sourceUrl like '%$overDriveId'");
							if ($eContentRecord->find(true)){
								$orderId = $user->id . '_' . time() ;
								$strandsUrl = "http://bizsolutions.strands.com/api2/event/addshoppingcart.sbs?needresult=true&apid={$configArray['Strands']['APID']}&item=econtentRecord{$eContentRecord->id}::0.00::1&user={$user->id}&orderid={$orderId}";
								$ret = file_get_contents($strandsUrl);
								/*global $logger;
								$logger->log("Strands Hold\r\n$ret", PEAR_LOG_INFO);*/
							}
						}

						//Delete the cache for the record
						$memCache->delete('overdrive_record_' . $overDriveId);
					}else{
						$holdResult['result'] = false;
						$holdResult['message'] = 'Unknown error placing your hold.';
						global $logger;
						$logger->log("Placing hold on OverDrive item. OverDriveId ". $overDriveId, PEAR_LOG_INFO);
						$logger->log('URL: '.$secureBaseUrl . "BANGAuthenticate.dll?Action=LibraryWaitingList $post_string\r\n" . $waitingListConfirm ,PEAR_LOG_INFO);
					}
				}
			}
		}
		curl_close($ch);

		return $holdResult;
	}

	public function cancelOverDriveHold($overDriveId, $format, $user){
		global $memCache;

		$cancelHoldResult = array();
		$cancelHoldResult['result'] = false;
		$cancelHoldResult['message'] = '';

		$ch = curl_init();
		$overDriveInfo = $this->_loginToOverDrive($ch, $user);
		curl_setopt($overDriveInfo['ch'], CURLOPT_HTTPGET, true);

		//Navigate to hold cancellation page
		$holdCancelUrl = $overDriveInfo['baseLoginUrl'] . "BangAuthenticate.dll?Action=RemoveFromWaitingList&id={{$overDriveId}}&format=$format&url=waitinglistremove.htm";
		curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $holdCancelUrl);
		$cancellationResult = curl_exec($overDriveInfo['ch']);

		if (preg_match('/You have successfully cancelled your hold/', $cancellationResult)){
			$cancelHoldResult['result'] = true;
			$cancelHoldResult['message'] = 'Your hold was cancelled successfully.';

			//Check to see if the user has cached hold information and if so, clear it
			$memCache->delete('overdrive_summary_' . $user->id);

			//Delete the cache for the record
			$memCache->delete('overdrive_record_' . $overDriveId);
		}else{
			echo($cancellationResult);
			$cancelHoldResult['result'] = false;
			$cancelHoldResult['message'] = 'There was an error cancelling your hold.';
		}

		curl_close($overDriveInfo['ch']);

		return $cancelHoldResult;
	}

	/**
	 *
	 * Add an item to the cart in overdrive and then process the cart so it is checked out.
	 *
	 * @param string $overDriveId
	 * @param int $format
	 * @param int $lendingPeriod  the number of days that the user would like to have the title chacked out. or -1 to use the default
	 * @param User $user
	 */
	public function checkoutOverDriveItem($overDriveId, $format, $lendingPeriod, $user){
		global $logger;
		global $memCache;
		$accountSummaryBeforeCheckout = $this->getOverDriveSummary($user);
		$ch = curl_init();
		$overDriveInfo = $this->_loginToOverDrive($ch, $user);
		$result = array();
			if (!$overDriveInfo['result']){
			$result['result'] = false;
			$result['message'] = $overDriveInfo['message'];
		}else{
			$closeSession = true;

			//Switch back to get method
			curl_setopt($overDriveInfo['ch'], CURLOPT_HTTPGET, true);

			//Open the record page
			$contentInfoPage = $overDriveInfo['contentInfoPage'] . "?ID=" . $overDriveId;
			curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $contentInfoPage);
			$recordPage = curl_exec($overDriveInfo['ch']);
			$recordPageInfo = curl_getinfo($overDriveInfo['ch']);

			//Do one click checkout
			$checkoutUrl = $overDriveInfo['checkoutUrl'] . '&ReserveID=' . $overDriveId;
			curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $checkoutUrl);
			$checkoutPage = curl_exec($overDriveInfo['ch']);

			//OverDrive no longer provides a status indicator in the text of the site.  Just check to see if we have more.
			$accountSummaryAfterCheckout = $this->getOverDriveSummary($user);
			if ($accountSummaryAfterCheckout['numCheckedOut'] > $accountSummaryBeforeCheckout['numCheckedOut']){
				$result['result'] = true;
				$result['message'] = "Your title was checked out successfully. You may now download the title from your Account.";
				$memCache->delete('overdrive_summary_' . $user->id);
			}else{
				$logger->log("OverDrive checkout failed calling page $checkoutUrl", PEAR_LOG_ERR);
				$logger->log($checkoutPage, PEAR_LOG_INFO);
				$result['result'] = false;
				$result['message'] = 'Sorry, we could not checkout this title to you.  Please try again later';
			}
		}
		curl_close($ch);
		return $result;
	}

	/**
	 * Logs the user in to OverDrive and returns urls for the pages that can be accessed from the account as wel
	 * as the curl handle to use when accessing the
	 *
	 * @param mixed $ch An open curl connection to use when talking to OverDrive.  Will not be closed by this method.
	 * @param User $user The user to login.
	 *
	 * @return array
	 */
	private function _loginToOverDrive($ch, $user){
		global $configArray;
		$overdriveUrl = $configArray['OverDrive']['url'];
		curl_setopt_array($ch, array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTPGET => true,
			CURLOPT_URL => $overdriveUrl . '/10/50/en/Default.htm',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:8.0.1) Gecko/20100101 Firefox/8.0.1",
			CURLOPT_AUTOREFERER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			//CURLOPT_COOKIEJAR => $cookieJar
		));
		$initialPage = curl_exec($ch);
		$pageInfo = curl_getinfo($ch);

		$urlWithSession = $pageInfo['url'];
		//print_r($pageInfo);


		//Go to the login form
		$loginUrl = str_replace('Default.htm', 'BANGAuthenticate.dll?Action=AuthCheck&URL=MyAccount.htm&ForceLoginFlag=0',  $urlWithSession);
		curl_setopt($ch, CURLOPT_URL, $loginUrl);
		$loginPageContent = curl_exec($ch);
		$loginPageInfo = curl_getinfo($ch);
		$loginFormUrl = $loginPageInfo['url'];

		//Post to the login form
		curl_setopt($ch, CURLOPT_POST, true);
		$barcodeProperty = isset($configArray['Catalog']['barcodeProperty']) ? $configArray['Catalog']['barcodeProperty'] : 'cat_username';
		$barcode = $user->$barcodeProperty;
		if (strlen($barcode) == 5){
			$user->cat_password = '41000000' . $barcode;
		}else if (strlen($barcode) == 6){
			$user->cat_password = '4100000' . $barcode;
		}
		if (isset($configArray['OverDrive']['maxCardLength'])){
			$barcode = substr($barcode, -$configArray['OverDrive']['maxCardLength']);
		}
		$postParams = array(
			'LibraryCardNumber' => $barcode,
			'URL' => 'Default.htm',
		);
		if (isset($configArray['OverDrive']['LibraryCardILS']) && strlen($configArray['OverDrive']['LibraryCardILS']) > 0){
			$postParams['LibraryCardILS'] = $configArray['OverDrive']['LibraryCardILS'];
		}
		$post_items = array();
		foreach ($postParams as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
		$loginUrl = str_replace('SignIn.htm?URL=MyAccount%2ehtm', 'BANGAuthenticate.dll',  $loginFormUrl);
		curl_setopt($ch, CURLOPT_URL, $loginUrl);
		$myAccountMenuContent = curl_exec($ch);
		$accountPageInfo = curl_getinfo($ch);

		$matchAccount = preg_match('/sign-out-link-top/is', $myAccountMenuContent);
		if (($matchAccount > 0)){
			$overDriveInfo = array(
				'baseLoginUrl' => str_replace('BANGAuthenticate.dll', '', $loginUrl),
				'baseUrlWithSession' => str_replace('Default.htm', '',  $urlWithSession),
				'contentInfoPage' => str_replace('Default.htm', 'ContentDetails.htm',  $urlWithSession),
				'accountUrl' => str_replace('BANGAuthenticate.dll', 'MyAccount.htm?PerPage=80', $loginUrl),
				'waitingListUrl' => str_replace('Default.htm', 'BANGAuthenticate.dll?Action=AuthCheck&ForceLoginFlag=0&URL=WaitingListForm.htm',  $urlWithSession),
				'placeHoldUrl' => str_replace('Default.htm', 'BANGAuthenticate.dll?Action=LibraryWatingList',  $urlWithSession),
				'checkoutUrl' => str_replace('Default.htm', 'BANGPurchase.dll?Action=OneClickCheckout&ForceLoginFlag=0&URL=MyAccount.htm%3FPerPage=80',  $urlWithSession),
				'returnUrl' => str_replace('Default.htm', 'BANGPurchase.dll?Action=EarlyReturn&URL=MyAccount.htm%3FPerPage=80',  $urlWithSession),
				'result' => true,
				'ch' => $ch,
			);
		}else if (preg_match('/You are barred from borrowing/si', $myAccountMenuContent)){
			$overDriveInfo = array();
			$overDriveInfo['result'] = false;
			$overDriveInfo['message'] = "We're sorry, your account is currently barred from borrowing OverDrive titles. Please see the circulation desk.";

		}else if (preg_match('/You are barred from borrowing/si', $myAccountMenuContent)){
			$overDriveInfo = array();
			$overDriveInfo['result'] = false;
			$overDriveInfo['message'] = "We're sorry, your account is currently barred from borrowing OverDrive titles. Please see the circulation desk.";

		}else if (preg_match('/Library card has expired/si', $myAccountMenuContent)){
			$overDriveInfo = array();
			$overDriveInfo['result'] = false;
			$overDriveInfo['message'] = "We're sorry, your library card has expired. Please contact your library to renew.";

		}else if (preg_match('/more than (.*?) in library fines are outstanding/si', $myAccountMenuContent)){
			$overDriveInfo = array();
			$overDriveInfo['result'] = false;
			$overDriveInfo['message'] = "We're sorry, your account cannot borrow from OverDrive because you have unpaid fines.";

		}else{
			global $logger;
			$logger->log("Could not login to OverDrive ($matchAccount), page results: \r\n" . $myAccountMenuContent, PEAR_LOG_INFO);
			$overDriveInfo = null;
			$overDriveInfo = array();
			$overDriveInfo['result'] = false;
			$overDriveInfo['message'] = "Unknown error logging in to OverDrive.";
		}
		//global $logger;
		//$logger->log(print_r($overDriveInfo, true) , PEAR_LOG_INFO);
		return $overDriveInfo;
	}

	public function getLoanPeriodsForFormat($formatId){
		if ($formatId == 35){
			return array(3, 5, 7);
		}else{
			return array(7, 14, 21);
		}
	}

	public function returnOverDriveItem($overDriveId, $transactionId, $user){
		global $logger;
		global $memCache;
		$ch = curl_init();
		$overDriveInfo = $this->_loginToOverDrive($ch, $user);

		//Switch back to get method
		curl_setopt($overDriveInfo['ch'], CURLOPT_HTTPGET, true);

		//Open the record page
		$contentInfoPage = $overDriveInfo['contentInfoPage'] . "?ID=" . $overDriveId;
		curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $contentInfoPage);
		$recordPage = curl_exec($overDriveInfo['ch']);
		$recordPageInfo = curl_getinfo($overDriveInfo['ch']);

		//Do one click checkout
		$returnUrl = $overDriveInfo['returnUrl'] . '&ReserveID=' . $overDriveId . '&TransactionID=' . $transactionId;
		curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $returnUrl);
		$returnPage = curl_exec($overDriveInfo['ch']);

		$result = array();
		//We just go back to the main account page, to see if the return succeeded, we need to make sure the
		//transaction is no longer listed
		if (!preg_match("/$transactionId/si", $returnPage)){
			$result['result'] = true;
			$result['message'] = "Your title was returned successfully.";
			$memCache->delete('overdrive_summary_' . $user->id);
			//Delete the cache for the record
			$memCache->delete('overdrive_record_' . $overDriveId);
		}else{
			$logger->log("OverDrive return failed", PEAR_LOG_ERR);
			$logger->log($returnPage, PEAR_LOG_INFO);
			$result['result'] = false;
			$result['message'] = 'Sorry, we could not return this title for you.  Please try again later';
		}
		curl_close($ch);
		return $result;
	}

	public function selectOverDriveDownloadFormat($overDriveId, $formatId, $user){
		global $logger;
		global $memCache;
		$ch = curl_init();
		$overDriveInfo = $this->_loginToOverDrive($ch, $user);

		//Switch back to get method
		curl_setopt($overDriveInfo['ch'], CURLOPT_HTTPGET, true);

		$result = array(
			'result' => true,
			'downloadUrl' => $overDriveInfo['baseLoginUrl'] . 'BANGPurchase.dll?Action=Download&ReserveID=' . $overDriveId . '&FormatID=' . $formatId . '&url=MyAccount.htm'
		);
		$memCache->delete('overdrive_summary_' . $user->id);
		curl_close($ch);
		return $result;
	}

	public function updateLendingOptions(){
		global $memCache;
		global $user;
		global $logger;
		$ch = curl_init();
		$overDriveInfo = $this->_loginToOverDrive($ch, $user);
		$closeSession = true;

		$updateSettingsUrl = $overDriveInfo['baseLoginUrl']  . 'BANGAuthenticate.dll?Action=EditUserLendingPeriodsFormatClass';
		$postParams = array(
			'URL' => 'MyAccount.htm?PerPage=80#myAccount4',
		);

		//Load settings
		foreach ($_REQUEST as $key => $value){
			if (preg_match('/class_\d+_preflendingperiod/i', $key)){
				$postParams[$key] = $value;
			}
		}

		$post_items = array();
		foreach ($postParams as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($overDriveInfo['ch'], CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $updateSettingsUrl);

		$logger->log("Updating user lending options $updateSettingsUrl $post_string", PEAR_LOG_DEBUG);
		$lendingOptionsPage = curl_exec($overDriveInfo['ch']);
		//$logger->log($lendingOptionsPage, PEAR_LOG_DEBUG);

		$memCache->delete('overdrive_summary_' . $user->id);
		return true;
	}
}
