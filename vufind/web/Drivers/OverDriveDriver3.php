<?php

require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';

/**
 * Complete integration via APIs including availability and account informatino.
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
class OverDriveDriver3 {
	public $version = 3;

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
	/** @var Memcache $memCache */
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

	private function _connectToPatronAPI($patronBarcode, $patronPin = 1234, $forceNewConnection = false){
		/** @var Memcache $memCache */
		global $memCache;
		$tokenData = $memCache->get('overdrive_patron_token_' . $patronBarcode);
		if ($forceNewConnection || $tokenData == false){
			global $configArray;
			$ch = curl_init("https://oauth.overdrive.com/patrontoken");
			//$websiteId = $configArray['OverDrive']['accountId'];
			$websiteId = 9;
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8'));
			curl_setopt($ch, CURLOPT_USERPWD, "");
			//curl_setopt($ch, CURLOPT_USERPWD, $configArray['OverDrive']['clientKey'] . ":" . $configArray['OverDrive']['clientSecret']);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials&scope=websiteId:{$websiteId}%20ilsname:default%20cardnumber:{$patronBarcode}%20pin:{$patronPin}");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

			$return = curl_exec($ch);
			curl_close($ch);
			$tokenData = json_decode($return);
			if ($tokenData){
				$memCache->set('overdrive_patron_token_' . $patronBarcode, $tokenData, 0, $tokenData->expires_in - 10);
			}
		}
		return $tokenData;
	}

	public function _callUrl($url){
		$tokenData = $this->_connectToAPI();
		//TODO: Remove || true needed for mock environment
		if ($tokenData || true){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$tokenData->token_type} {$tokenData->access_token}", "User-Agent: VuFind-Plus"));
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
		return null;
	}

	public function _callPatronUrl($patronBarcode, $patronPin, $url, $postParams = null){
		$tokenData = $this->_connectToPatronAPI($patronBarcode, $patronPin, false);
		//TODO: Remove || true when oauth works
		if ($tokenData || true){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			if ($tokenData){
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$tokenData->token_type} {$tokenData->access_token}", "User-Agent: VuFind-Plus", "Host: api.mock.overdrive.com"));
			}else{
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: VuFind-Plus", "Host: api.mock.overdrive.com"));
			}

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			if ($postParams != null){
				curl_setopt($ch, CURLOPT_POST, true);
				$postParamString = "";
				foreach ($postParams as $key => $value){
					if (strlen($postParamString) > 0){
						$postParamString .= '&';
					}
					$postParamString .= $key . '=' . $value;
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postParamString);
			}

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
		return false;
	}

	private function _callPatronDeleteUrl($patronBarcode, $patronPin, $url){
		$tokenData = $this->_connectToPatronAPI($patronBarcode, $patronPin, false);
		//TODO: Remove || true when oauth works
		if ($tokenData || true){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			if ($tokenData){
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$tokenData->token_type} {$tokenData->access_token}", "User-Agent: VuFind-Plus", "Host: api.mock.overdrive.com"));
			}else{
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: VuFind-Plus", "Host: api.mock.overdrive.com"));
			}

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

			$return = curl_exec($ch);
			$returnInfo = curl_getinfo($ch);
			if ($returnInfo['http_code'] == 400){
				$result = true;
			}else{
				echo("Response code was " . $returnInfo['http_code']);
				$result = false;
			}
			curl_close($ch);
			$returnVal = json_decode($return);
			//print_r($returnVal);
			if ($returnVal != null){
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
					return $returnVal;
				}
			}else{
				return $result;
			}
		}
		return false;
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
		global $configArray;
		$url = $configArray['OverDrive']['patronApiUrl'] . '/v2/patrons/me/checkouts';
		$response = $this->_callPatronUrl($user->cat_password, null, $url);
		$checkedOutTitles = array();
		foreach ($response->checkouts as $curTitle){
			$bookshelfItem = array();
			//Load data from api
			$bookshelfItem['overDriveId'] = $curTitle->reserveId;
			$bookshelfItem['expiresOn'] = $curTitle->expires;
			$bookshelfItem['overdriveRead'] = false;
			$bookshelfItem['formatSelected'] = ($curTitle->isFormatLockedIn == 1);
			$bookshelfItem['formats'] = array();
			foreach ($curTitle->formats as $id => $format){
				if ($format->formatType == 'ebook-overdrive'){
					$bookshelfItem['overdriveRead'] = true;
				}else{
					$bookshelfItem['selectedFormat'] = array(
						'name' => $format->formatType,
					);
				}
				$curFormat['downloadUrl'] = $format->links->downloadLink->href;
				$curFormat = array();
				$curFormat['id'] = $id;
				$curFormat['name'] = $format->formatType;
				$bookshelfItem['formats'][] = $curFormat;
			}
			if (isset($curTitle->actions->earlyReturn)){
				$bookshelfItem['earlyReturn']  = true;
			}
			//Figure out which eContent record this is for.
			$eContentRecord = new EContentRecord();
			$eContentRecord->externalId = $bookshelfItem['overDriveId'];
			$eContentRecord->source = 'OverDrive';
			$eContentRecord->status = 'active';
			if ($eContentRecord->find(true)){
				$bookshelfItem['recordId'] = $eContentRecord->id;
				$bookshelfItem['title'] = $eContentRecord->title;
				$bookshelfItem['imageUrl'] = $eContentRecord->cover;

				//Get Rating
				require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
				$econtentRating = new EContentRating();
				$econtentRating->recordId = $eContentRecord->id;
				$bookshelfItem['ratingData'] = $econtentRating->getRatingData($user, false);
			}else{
				$bookshelfItem['recordId'] = -1;
			}
			$checkedOutTitles[] = $bookshelfItem;
		}
		return array(
			'items' => $checkedOutTitles
		);
	}

	public function getOverDriveHolds($user, $overDriveInfo = null){
		global $configArray;
		$url = $configArray['OverDrive']['patronApiUrl'] . '/v2/patrons/me/holds';
		$response = $this->_callPatronUrl($user->cat_password, null, $url);
		$holds = array();
		$holds['holds'] = array(
			'available' => array(),
			'unavailable' => array()
		);
		foreach ($response->holds as $curTitle){
			$hold = array();
			$hold['overDriveId'] = $curTitle->reserveId;
			$hold['notifyEmail'] = $curTitle->emailAddress;
			$hold['holdQueueLength'] = $curTitle->numberOfHolds;
			$hold['holdQueuePosition'] = $curTitle->holdListPosition;
			$hold['available'] = ($curTitle->holdListPosition == 0);
			if ($hold['available']){
				$hold['expirationDate'] = strtotime($curTitle->holdExpires);
			}

			//Figure out which eContent record this is for.
			$eContentRecord = new EContentRecord();
			$eContentRecord->externalId = $hold['overDriveId'];
			$eContentRecord->source = 'OverDrive';
			$eContentRecord->status = 'active';
			if ($eContentRecord->find(true)){
				$hold['recordId'] = $eContentRecord->id;
				$hold['title'] = $eContentRecord->title;
				$hold['author'] = $eContentRecord->author;
				$hold['imageUrl'] = $eContentRecord->cover;

				//Get Rating
				require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
				$econtentRating = new EContentRating();
				$econtentRating->recordId = $eContentRecord->id;
				$hold['ratingData'] = $econtentRating->getRatingData($user, false);
			}else{
				$hold['recordId'] = -1;
			}

			if ($hold['available']){
				$holds['holds']['available'][] = $hold;
			}else{
				$holds['holds']['unavailable'][] = $hold;
			}
		}
		return $holds;
	}

	/**
	 * Returns a summary of information about the user's account in OverDrive.
	 *
	 * @param User $user
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
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $timer;
		global $logger;

		$summary = $memCache->get('overdrive_summary_' . $user->id);
		if ($summary == false || isset($_REQUEST['reload'])){
			//Get account information from api

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
	 *
	 * @return array (result, message)
	 */
	public function placeOverDriveHold($overDriveId, $format, $user){
		global $configArray;
		global $analytics;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v2/patrons/me/holds';
		$params = array(
			'reserveId' => $overDriveId,
			'email' => $user->overdriveEmail
		);
		$response = $this->_callPatronUrl($user->cat_password, null, $url, $params);

		$holdResult = array();
		$holdResult['result'] = false;
		$holdResult['message'] = '';

		if ($response->holdListPosition > 0){
			$holdResult['result'] = true;
			$holdResult['message'] = 'Your hold was placed successfully.  You are number ' . $response->holdListPosition . ' on the wait list.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Place Hold', 'succeeded');
		}else{
			$holdResult['message'] = 'Sorry, but we could not place a hold for you on this title.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Place Hold', 'failed');
		}

		return $response;
	}

	/**
	 * @param User $user
	 * @param string $overDriveId
	 * @param string $format
	 * @return array
	 */
	public function cancelOverDriveHold($user, $overDriveId, $format){
		global $configArray;
		global $analytics;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v2/patrons/me/holds/' . $overDriveId;
		$response = $this->_callPatronUrl($user->cat_password, null, $url);

		$cancelHoldResult = array();
		$cancelHoldResult['result'] = false;
		$cancelHoldResult['message'] = '';
		if ($response){
			$cancelHoldResult['result'] = true;
			$cancelHoldResult['message'] = 'Your hold was cancelled successfully.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Cancel Hold', 'succeeded');
		}else{
			$cancelHoldResult['message'] = 'There was an error cancelling your hold.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Cancel Hold', 'failed');
		}

		return $response;
	}

	/**
	 *
	 * Add an item to the cart in overdrive and then process the cart so it is checked out.
	 *
	 * @param string $overDriveId
	 * @param int $format
	 * @param int $lendingPeriod  the number of days that the user would like to have the title chacked out. or -1 to use the default
	 * @param User $user
	 *
	 * @return array results (result, message)
	 */
	public function checkoutOverDriveItem($overDriveId, $format, $lendingPeriod, $user){

		global $configArray;
		global $analytics;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v2/patrons/me/checkouts';
		$params = array(
			'reserveId' => $overDriveId,
		);
		if ($format){
			$params['formatType'] = $format;
		}
		$response = $this->_callPatronUrl($user->cat_password, null, $url, $params);

		$result = array();
		$result['result'] = false;
		$result['message'] = '';

		if ($response->expires){
			$result['result'] = true;
			$result['message'] = 'Your title was checked out successfully. You may now download the title from your Account.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Checkout Item', 'succeeded');
		}else{
			$result['message'] = 'Sorry, we could not checkout this title to you.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Checkout Item', 'failed');
		}

		return $result;
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
		/** @var Memcache $memCache */
		global $memCache;
		global $analytics;
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
			$analytics->addEvent('OverDrive', 'Checkout Item', 'success');
		}else{
			$logger->log("OverDrive return failed", PEAR_LOG_ERR);
			$logger->log($returnPage, PEAR_LOG_INFO);
			$result['result'] = false;
			$result['message'] = 'Sorry, we could not return this title for you.  Please try again later';
			$analytics->addEvent('OverDrive', 'Checkout Item', 'failed');
		}
		curl_close($ch);
		return $result;
	}

	public function selectOverDriveDownloadFormat($overDriveId, $formatId, $user){
		global $analytics;
		/** @var Memcache $memCache */
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
		$analytics->addEvent('OverDrive', 'Select Download Format');
		return $result;
	}

	public function updateLendingOptions(){
		/** @var Memcache $memCache */
		global $memCache;
		global $user;
		global $logger;
		global $analytics;
		$ch = curl_init();
		$overDriveInfo = $this->_loginToOverDrive($ch, $user);

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

		$analytics->addEvent('OverDrive', 'Update Lending Periods');
		return true;
	}
}
