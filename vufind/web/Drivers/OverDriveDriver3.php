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

	protected $format_map = array(
		'ebook-epub-adobe' => 'Adobe EPUB eBook',
		'ebook-epub-open' => 'Open EPUB eBook',
		'ebook-pdf-adobe' => 'Adobe PDF eBook',
		'ebook-pdf-open' => 'Open PDF eBook',
		'ebook-kindle' => 'Kindle Book',
		'ebook-disney' => 'Disney Online Book',
		'ebook-overdrive' => 'OverDrive Read',
		'ebook-microsoft' => 'Microsoft eBook',
		'audiobook-wma' => 'OverDrive WMA Audiobook',
		'audiobook-mp3' => 'OverDrive MP3 Audiobook',
		'audiobook-streaming' => 'Streaming Audiobook',
		'music-wma' => 'OverDrive Music',
		'video-wmv' => 'OverDrive Video',
		'video-wmv-mobile' => 'OverDrive Video (mobile)'
	);

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

	//private function _connectToPatronAPI($patronBarcode, $patronPin = 1234, $forceNewConnection = false){
	private function _connectToPatronAPI($patronBarcode, $patronPin, $forceNewConnection = false){
		/** @var Memcache $memCache */
		global $memCache;
		$patronTokenData = $memCache->get('overdrive_patron_token_' . $patronBarcode);
		if ($forceNewConnection || $patronTokenData == false){
			$tokenData = $this->_connectToAPI($forceNewConnection);
			if ($tokenData){
				global $configArray;
				$ch = curl_init("https://oauth-patron.overdrive.com/patrontoken");
				$websiteId = $configArray['OverDrive']['patronWebsiteId'];
				//$websiteId = 100300;
				$ilsname = $configArray['OverDrive']['LibraryCardILS'];
				//$ilsname = "default";
				$clientSecret = $configArray['OverDrive']['clientSecret'];
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
				curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				$encodedAuthValue = base64_encode($configArray['OverDrive']['clientKey'] . ":" . $clientSecret);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
					"Authorization: Basic " . $encodedAuthValue,
					"User-Agent: VuFind-Plus"
				));
				//curl_setopt($ch, CURLOPT_USERPWD, "");
				//$clientSecret = $configArray['OverDrive']['clientSecret'];
				//curl_setopt($ch, CURLOPT_USERPWD, $configArray['OverDrive']['clientKey'] . ":" . $clientSecret);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch, CURLOPT_POST, 1);

				if ($patronPin == null){
					$postFields = "grant_type=password&username={$patronBarcode}&password=ignore&password_required=false&scope=websiteId:{$websiteId}%20ilsname:{$ilsname}";
				}else{
					$postFields = "grant_type=password&username={$patronBarcode}&password={$patronPin}&password_required=true&scope=websiteId:{$websiteId}%20ilsname:{$ilsname}";
				}
				//$postFields = "grant_type=client_credentials&scope=websiteid:{$websiteId}%20ilsname:{$ilsname}%20cardnumber:{$patronBarcode}";

				curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

				$return = curl_exec($ch);
				$curlInfo = curl_getinfo($ch);
				curl_close($ch);
				$patronTokenData = json_decode($return);
				if ($patronTokenData){
					if (isset($patronTokenData->error)){
						if ($patronTokenData->error == 'unauthorized_client'){
							return false;
						}else{
							echo("Error connecting to overdrive apis ". $patronTokenData->error);
						}
					}else{
						$memCache->set('overdrive_patron_token_' . $patronBarcode, $patronTokenData, 0, $patronTokenData->expires_in - 10);
					}
				}
			}else{
				return false;
			}
		}
		return $patronTokenData;
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

	public function _callPatronUrl($user, $url, $postParams = null){
		global $configArray;
		$requirePin = $configArray['OverDrive']['requirePin'];
		if ($requirePin){
			$tokenData = $this->_connectToPatronAPI($user->cat_username, $user->cat_password, false);
		}else{
			$tokenData = $this->_connectToPatronAPI($user->cat_password, null, false);
		}
		if ($tokenData){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			if (isset($tokenData->token_type) && isset($tokenData->access_token)){
				$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;
				$headers = array(
					"Authorization: $authorizationData",
					"User-Agent: VuFind-Plus",
					"Host: patron.api.overdrive.com" // production
					//"Host: integration-patron.api.overdrive.com" // testing
				);
			}else{
				//The user is not valid
				if ($configArray['Site']['debug'] == true){
					print_r($tokenData);
				}
				return false;
			}

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			if ($postParams != null){
				curl_setopt($ch, CURLOPT_POST, 1);
				//Convert post fields to json
				$jsonData = array('fields' => array());
				foreach ($postParams as $key => $value){
					$jsonData['fields'][] = array(
						'name' => $key,
						'value' => $value
					);
				}
				$postData = json_encode($jsonData);
				//print_r($postData);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
				$headers[] = 'Content-Type: application/vnd.overdrive.content.api+json';
			}else{
				curl_setopt($ch, CURLOPT_HTTPGET, true);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

				$return = curl_exec($ch);
			$curlInfo = curl_getinfo($ch);
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
				$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;
				$headers = array(
					"Authorization: $authorizationData",
					"User-Agent: VuFind-Plus",
					"Host: patron.api.overdrive.com",
					//"Host: integration-patron.api.overdrive.com"
				);
			}else{
				$headers = array("User-Agent: VuFind-Plus", "Host: api.overdrive.com");
			}

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$return = curl_exec($ch);
			$returnInfo = curl_getinfo($ch);
			if ($returnInfo['http_code'] == 204){
				$result = true;
			}else{
				//echo("Response code was " . $returnInfo['http_code']);
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

	private $checkouts = array();

	/**
	 * Loads information about items that the user has checked out in OverDrive
	 *
	 * @param User $user
	 * @param array $overDriveInfo optional array of information loaded from _loginToOverDrive to improve performance.
	 *
	 * @return array
	 */
	public function getOverDriveCheckedOutItems($user, $overDriveInfo = null){
		if (isset($this->checkouts[$user->id])){
			return $this->checkouts[$user->id];
		}
		global $configArray;
		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/checkouts';
		$response = $this->_callPatronUrl($user, $url);
		if ($response == false){
			//The user is not authorized to use OverDrive
			return false;
		}

		//print_r($response);
		$checkedOutTitles = array();
		if (isset($response->checkouts)){
			foreach ($response->checkouts as $curTitle){
				$bookshelfItem = array();
				//Load data from api
				$bookshelfItem['checkoutSource'] = 'OverDrive';
				$bookshelfItem['overDriveId'] = $curTitle->reserveId;
				$bookshelfItem['expiresOn'] = $curTitle->expires;
				$bookshelfItem['overdriveRead'] = false;
				$bookshelfItem['formatSelected'] = ($curTitle->isFormatLockedIn == 1);
				$bookshelfItem['formats'] = array();
				if (isset($curTitle->formats)){
					foreach ($curTitle->formats as $id => $format){
						if ($format->formatType == 'ebook-overdrive'){
							$bookshelfItem['overdriveRead'] = true;
						}else{
							$bookshelfItem['selectedFormat'] = array(
								'name' => $this->format_map[$format->formatType],
								'format' => $format->formatType,
							);
						}
						$curFormat = array();
						$curFormat['id'] = $id;
						$curFormat['format'] = $format;
						$curFormat['name'] = $format->formatType;
						if (isset($format->links->self)){
							$curFormat['downloadUrl'] = $format->links->self->href . '/downloadlink';
						}
						if ($format->formatType != 'ebook-overdrive'){
							$bookshelfItem['formats'][] = $curFormat;
						}else{
							if (isset($curFormat['downloadUrl'])){
								$bookshelfItem['overdriveReadUrl'] = $curFormat['downloadUrl'];
							}
						}
					}
				}
				if (isset($curTitle->actions->format) && !$bookshelfItem['formatSelected']){
					//Get the options for the format which includes the valid formats
					$formatField = null;
					foreach ($curTitle->actions->format->fields as $curFieldIndex => $curField){
						if ($curField->name == 'formatType'){
							$formatField = $curField;
							break;
						}
					}
					foreach ($formatField->options as $index => $format){
						$curFormat = array();
						$curFormat['id'] = $format;
						$curFormat['name'] = $this->format_map[$format];
						$bookshelfItem['formats'][] = $curFormat;
					}
				}

				if (isset($curTitle->actions->earlyReturn)){
					$bookshelfItem['earlyReturn']  = true;
				}
				//Figure out which eContent record this is for.
				require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
				$overDriveRecord = new OverDriveRecordDriver($bookshelfItem['overDriveId']);
				$bookshelfItem['recordId'] = $overDriveRecord->getUniqueID();
				$bookshelfItem['coverUrl'] = $overDriveRecord->getCoverUrl('medium');
				$bookshelfItem['recordUrl'] = $configArray['Site']['path'] . '/OverDrive/' . $overDriveRecord->getUniqueID() . '/Home';
				$bookshelfItem['title'] = $overDriveRecord->getTitle();
				$bookshelfItem['author'] = $overDriveRecord->getAuthor();
				$bookshelfItem['imageUrl'] = $overDriveRecord->getLinkUrl(false);
				$bookshelfItem['ratingData'] = $overDriveRecord->getRatingData();

				$key = $bookshelfItem['checkoutSource'] . $bookshelfItem['overDriveId'];
				$checkedOutTitles[$key] = $bookshelfItem;
			}
		}
		$this->checkouts[$user->id] = $checkedOutTitles;
		return array(
			'items' => $checkedOutTitles
		);
	}

	private $holds = array();

	/**
	 * @param User $user
	 * @param null $overDriveInfo
	 * @return array
	 */
	public function getOverDriveHolds($user, $overDriveInfo = null){
		//Cache holds for the user just for this call.
		if (isset($this->holds[$user->id])){
			return $this->holds[$user->id];
		}
		global $configArray;
		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/holds';
		$response = $this->_callPatronUrl($user, $url);
		$holds = array();
		$holds['holds'] = array(
			'available' => array(),
			'unavailable' => array()
		);
		if (isset($response->holds)){
			foreach ($response->holds as $curTitle){
				$hold = array();
				$hold['overDriveId'] = $curTitle->reserveId;
				$hold['notifyEmail'] = $curTitle->emailAddress;
				$hold['holdQueueLength'] = $curTitle->numberOfHolds;
				$hold['holdQueuePosition'] = $curTitle->holdListPosition;
				$hold['available'] = isset($curTitle->actions->checkout);
				if ($hold['available']){
					$hold['expirationDate'] = strtotime($curTitle->holdExpires);
				}
				$hold['holdSource'] = 'OverDrive';

				//Figure out which eContent record this is for.
				$eContentRecord = new EContentRecord();
				$eContentRecord->externalId = $hold['overDriveId'];
				$eContentRecord->source = 'OverDrive';
				$eContentRecord->status = 'active';
				if ($eContentRecord->find(true)){
					$hold['recordId'] = $eContentRecord->id;
					$hold['recordUrl'] = $configArray['Site']['path'] . '/EcontentRecord/' . $eContentRecord->id . '/Home';
					$hold['title'] = $eContentRecord->title;
					$hold['author'] = $eContentRecord->author;
					$hold['imageUrl'] = $configArray['Site']['coverUrl'] . '/bookcover.php?size=medium&econtent=true&id=' . $eContentRecord->id;

					//Get Rating
					require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
					$econtentRating = new EContentRating();
					$econtentRating->recordId = $eContentRecord->id;
					$hold['ratingData'] = $econtentRating->getRatingData($user, false);
				}else{
					$hold['recordId'] = -1;
				}

				$key = $hold['holdSource'] . $hold['overDriveId'];
				if ($hold['available']){
					$holds['holds']['available'][$key] = $hold;
				}else{
					$holds['holds']['unavailable'][$key] = $hold;
				}
			}
		}
		$this->holds[$user->id] = $holds;
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
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $timer;

		if ($user == false){
			return array(
				'numCheckedOut' => 0,
				'numAvailableHolds' => 0,
				'numUnavailableHolds' => 0,
				'checkedOut' => array(),
				'holds' => array()
			);
		}

		$summary = $memCache->get('overdrive_summary_' . $user->id);
		if ($summary == false || isset($_REQUEST['reload'])){

			//Get account information from api

			//TODO: Optimize so we don't need to load all checkouts and holds
			$summary = array();
			$checkedOutItems = $this->getOverDriveCheckedOutItems($user);
			$summary['numCheckedOut'] = count($checkedOutItems['items']);

			$holds = $this->getOverDriveHolds($user);
			$summary['numAvailableHolds'] = count($holds['holds']['available']);
			$summary['numUnavailableHolds'] = count($holds['holds']['unavailable']);

			$summary['checkedOut'] = $checkedOutItems;
			$summary['holds'] = $holds['holds'];

			$timer->logTime("Finished loading titles from overdrive summary");
			$memCache->set('overdrive_summary_' . $user->id, $summary, 0, $configArray['Caching']['overdrive_summary']);
		}

		return $summary;
	}

	public function getLendingPeriods($user){
		//TODO: Replace this with an API when available
		require_once ROOT_DIR . '/Drivers/OverDriveDriver2.php';
		$overDriveDriver2 = new OverDriveDriver2();
		return $overDriveDriver2->getLendingPeriods($user);
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
		global $memCache;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/holds/' . $overDriveId;
		$params = array(
			'reserveId' => $overDriveId,
			'emailAddress' => trim($user->overdriveEmail)
		);
		$response = $this->_callPatronUrl($user, $url, $params);

		$holdResult = array();
		$holdResult['result'] = false;
		$holdResult['message'] = '';

		//print_r($response);
		if (isset($response->holdListPosition)){
			$holdResult['result'] = true;
			$holdResult['message'] = 'Your hold was placed successfully.  You are number ' . $response->holdListPosition . ' on the wait list.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Place Hold', 'succeeded');
		}else{
			$holdResult['message'] = 'Sorry, but we could not place a hold for you on this title.  ' . $response->message;
			if ($analytics) $analytics->addEvent('OverDrive', 'Place Hold', 'failed');
		}
		$memCache->delete('overdrive_summary_' . $user->id);

		return $holdResult;
	}

	/**
	 * @param User $user
	 * @param string $overDriveId
	 * @param string $format
	 * @return array
	 */
	public function cancelOverDriveHold($overDriveId, $format, $user){
		global $configArray;
		global $analytics;
		global $memCache;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/holds/' . $overDriveId;
                $requirePin = $configArray['OverDrive']['requirePin'];
                if ($requirePin){
			$response = $this->_callPatronDeleteUrl($user->cat_username, $user->cat_password, $url);
                }else{
			$response = $this->_callPatronDeleteUrl($user->cat_password, null, $url);
                }
 
		$cancelHoldResult = array();
		$cancelHoldResult['result'] = false;
		$cancelHoldResult['message'] = '';
		if ($response === true){
			$cancelHoldResult['result'] = true;
			$cancelHoldResult['message'] = 'Your hold was cancelled successfully.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Cancel Hold', 'succeeded');
		}else{
			$cancelHoldResult['message'] = 'There was an error cancelling your hold.  ' . $response->message;
			if ($analytics) $analytics->addEvent('OverDrive', 'Cancel Hold', 'failed');
		}
		$memCache->delete('overdrive_summary_' . $user->id);
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
	 *
	 * @return array results (result, message)
	 */
	public function checkoutOverDriveItem($overDriveId, $format, $lendingPeriod, $user){

		global $configArray;
		global $analytics;
		global $memCache;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/checkouts';
		$params = array(
			'reserveId' => $overDriveId,
		);
		if ($format){
			$params['formatType'] = $format;
		}
		$response = $this->_callPatronUrl($user, $url, $params);

		$result = array();
		$result['result'] = false;
		$result['message'] = '';

		//print_r($response);
		if (isset($response->expires)){
			$result['result'] = true;
			$result['message'] = 'Your title was checked out successfully. You may now download the title from your Account.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Checkout Item', 'succeeded');
		}else{
			$result['message'] = 'Sorry, we could not checkout this title to you.  ' . $response->message;
			if ($analytics) $analytics->addEvent('OverDrive', 'Checkout Item', 'failed');
		}

		$memCache->delete('overdrive_summary_' . $user->id);
		return $result;
	}

	public function getLoanPeriodsForFormat($formatId){
		//TODO: API for this?
		if ($formatId == 35){
			return array(3, 5, 7);
		}else{
			return array(7, 14, 21);
		}
	}

	public function returnOverDriveItem($overDriveId, $transactionId, $user){
		global $configArray;
		global $analytics;
		global $memCache;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/checkouts/' . $overDriveId;

                $requirePin = $configArray['OverDrive']['requirePin'];
                if ($requirePin){
                        $response = $this->_callPatronDeleteUrl($user->cat_username, $user->cat_password, $url);
                }else{
                        $response = $this->_callPatronDeleteUrl($user->cat_password, null, $url);
                }

		$cancelHoldResult = array();
		$cancelHoldResult['result'] = false;
		$cancelHoldResult['message'] = '';
		if ($response === true){
			$cancelHoldResult['result'] = true;
			$cancelHoldResult['message'] = 'Your item was returned successfully.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Return Item', 'succeeded');
		}else{
			$cancelHoldResult['message'] = 'There was an error returning this item. ' . $response->message;
			if ($analytics) $analytics->addEvent('OverDrive', 'Return Item', 'failed');
		}

		$memCache->delete('overdrive_summary_' . $user->id);
		return $cancelHoldResult;
	}

	public function selectOverDriveDownloadFormat($overDriveId, $formatId, $user){
		global $configArray;
		global $analytics;
		/** @var Memcache $memCache */
		global $memCache;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/checkouts/' . $overDriveId . '/formats';
		$params = array(
			'reserveId' => $overDriveId,
			'formatType' => $formatId
		);
		$response = $this->_callPatronUrl($user, $url, $params);
		//print_r($response);

		$result = array();
		$result['result'] = false;
		$result['message'] = '';

		if (isset($response->linkTemplates->downloadLink)){
			$result['result'] = true;
			$result['message'] = 'This format was locked in';
			if ($analytics) $analytics->addEvent('OverDrive', 'Select Download Format', 'succeeded');
			$downloadLink = $this->getDownloadLink($overDriveId, $formatId, $user);
			$result = $downloadLink;
		}else{
			$result['message'] = 'Sorry, but we could not select a format for you. '  .$response->message;
			if ($analytics) $analytics->addEvent('OverDrive', 'Select Download Format', 'failed');
		}
		$memCache->delete('overdrive_summary_' . $user->id);

		return $result;
	}

	public function isUserValidForOverDrive($user){
		global $configArray;
		$requirePin = $configArray['OverDrive']['requirePin'];
		if ($requirePin){
			$tokenData = $this->_connectToPatronAPI($user->cat_username, $user->cat_password, false);
		}else{
			$tokenData = $this->_connectToPatronAPI($user->cat_password, null, false);
		}
		return $tokenData !== false;
	}

	public function updateLendingOptions(){
		//TODO: Replace this with an API when available
		require_once ROOT_DIR . '/Drivers/OverDriveDriver2.php';
		$overDriveDriver2 = new OverDriveDriver2();
		return $overDriveDriver2->updateLendingOptions();
	}

	public function getDownloadLink($overDriveId, $format, $user){
		global $configArray;
		global $analytics;

		$url = $configArray['OverDrive']['patronApiUrl'] . "/v1/patrons/me/checkouts/{$overDriveId}/formats/{$format}/downloadlink";
		$url .= '?errorpageurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
		if ($format == 'ebook-overdrive'){
			$url .= '&odreadauthurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveReadError');
		}

		$response = $this->_callPatronUrl($user, $url);
		//print_r($response);

		$result = array();
		$result['result'] = false;
		$result['message'] = '';

		if (isset($response->links->contentlink)){
			$result['result'] = true;
			$result['message'] = 'Created Download Link';
			$result['downloadUrl'] = $response->links->contentlink->href;
			if ($analytics) $analytics->addEvent('OverDrive', 'Get Download Link', 'succeeded');
		}else{
			$result['message'] = 'Sorry, but we could not get a download link for you. ' . $response->message;
			if ($analytics) $analytics->addEvent('OverDrive', 'Get Download Link', 'failed');
		}

		return $result;
	}

	/**
	 * Get Holding
	 *
	 * This is responsible for retrieving the holding information of a certain
	 * record.
	 *
	 * @param   OverDriveRecordDriver  $overDriveRecordDriver   The record id to retrieve the holdings for
	 * @return  mixed               An associative array with the following keys:
	 *                              availability (boolean), status, location,
	 *                              reserve, callnumber, duedate, number,
	 *                              holding summary, holding notes
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	public function getHoldings($overDriveRecordDriver){
		global $user;
		global $configArray;

		/** @var OverDriveAPIProductFormats[] $items */
		$items = $overDriveRecordDriver->getItems();
		//Add links as needed
		$availability = $overDriveRecordDriver->getAvailability();
		$addCheckoutLink = false;
		$addPlaceHoldLink = false;
		foreach($availability as $availableFrom){
			if ($availableFrom->copiesAvailable > 0){
				$addCheckoutLink = true;
			}else{
				$addPlaceHoldLink = true;
			}
		}
		foreach ($items as $key => $item){
			$item->links = array();
			if ($addCheckoutLink){
				if ($configArray['OverDrive']['interfaceVersion'] == 1){
					$checkoutLink = "return checkoutOverDriveItem('{$overDriveRecordDriver->getUniqueID()}', '{$item->numericId}');";
					$checkoutLinkResponsive = "return VuFind.OverDrive.checkoutOverDriveItem('{$overDriveRecordDriver->getUniqueID()}', '{$item->numericId}');";
				}else{
					$checkoutLink = "return checkoutOverDriveItemOneClick('{$overDriveRecordDriver->getUniqueID()}', '{$item->numericId}');";
					$checkoutLinkResponsive = "return VuFind.OverDrive.checkoutOverDriveItemOneClick('{$overDriveRecordDriver->getUniqueID()}', '{$item->numericId}');";
				}
				$item->links[] = array(
					'onclick' => $checkoutLink,
					'onclickResponsive' => $checkoutLinkResponsive,
					'text' => 'Check Out',
					'overDriveId' => $overDriveRecordDriver->getUniqueID(),
					'formatId' => $item->numericId,
					'action' => 'CheckOut'
				);
			}else if ($addPlaceHoldLink){
				$item->links[] = array(
					'onclick' => "return placeOverDriveHold('{$overDriveRecordDriver->getUniqueID()}', '{$item->numericId}');",
					'onclickResponsive' => "return VuFind.OverDrive.placeOverDriveHold('{$overDriveRecordDriver->getUniqueID()}', '{$item->numericId}');",
					'text' => 'Place Hold',
					'overDriveId' => $overDriveRecordDriver->getUniqueID(),
					'formatId' => $item->numericId,
					'action' => 'Hold'
				);
			}
			$items[$key] = $item;
		}

		return $items;
	}

	public function getLibraryScopingId(){
		//For econtent, we need to be more specific when restricting copies
		//since patrons can't use copies that are only available to other libraries.
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		$activeLibrary = Library::getActiveLibrary();
		$activeLocation = Location::getActiveLocation();
		$homeLibrary = Library::getPatronHomeLibrary();

		//Load the holding label for the branch where the user is physically.
		if (!is_null($homeLibrary)){
			return $homeLibrary->includeOutOfSystemExternalLinks ? -1 : $homeLibrary->libraryId;
		}else if (!is_null($activeLocation)){
			$activeLibrary = Library::getLibraryForLocation($activeLocation->locationId);
			return $activeLibrary->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		}else if (isset($activeLibrary)) {
			return $activeLibrary->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		}else if (!is_null($searchLocation)){
			$searchLibrary = Library::getLibraryForLocation($searchLibrary->locationId);
			return $searchLibrary->includeOutOfSystemExternalLinks ? -1 : $searchLocation->libraryId;
		}else if (isset($searchLibrary)) {
			return $searchLibrary->includeOutOfSystemExternalLinks ? -1 : $searchLibrary->libraryId;
		}else{
			return -1;
		}
	}

	/**
	 * @param OverDriveRecordDriver $overDriveRecordDriver
	 * @return array
	 */
	public function getScopedAvailability($overDriveRecordDriver){
		$availability = array();
		$availability['mine'] = $overDriveRecordDriver->getAvailability();
		$availability['other'] = array();
		$scopingId = $this->getLibraryScopingId();
		if ($scopingId != -1){
			foreach ($availability['mine'] as $key => $availabilityItem){
				if ($availabilityItem->libraryId != -1 && $availabilityItem->libraryId != $scopingId){
					$availability['other'][$key] = $availability['mine'][$key];
					unset($availability['mine'][$key]);
				}
			}
		}
		return $availability;
	}

	public function getStatusSummary($id, $scopedAvailability, $holdings){
		global $user;
		$addedToWishList = false;
		$holdPosition = 0;

		$availableCopies = 0;
		$totalCopies = 0;
		$onOrderCopies = 0;
		$checkedOut = 0;
		$onHold = 0;
		$wishListSize = 0;
		$numHolds = 0;
		if (count($scopedAvailability['mine']) > 0){
			foreach ($scopedAvailability['mine'] as $curAvailability){
				$availableCopies += $curAvailability->copiesAvailable;
				$totalCopies += $curAvailability->copiesOwned;
				if ($curAvailability->numberOfHolds > $numHolds){
					$numHolds = $curAvailability->numberOfHolds;
				}
			}
		}

		//Load status summary
		$statusSummary = array();
		$statusSummary['recordId'] = $id;
		$statusSummary['totalCopies'] = $totalCopies;
		$statusSummary['onOrderCopies'] = $onOrderCopies;
		$statusSummary['accessType'] = 'overdrive';
		$statusSummary['isOverDrive'] = false;
		$statusSummary['alwaysAvailable'] = false;
		$statusSummary['class'] = 'checkedOut';
		$statusSummary['available'] = false;
		$statusSummary['status'] = 'Not Available';

		$statusSummary['availableCopies'] = $availableCopies;
		$statusSummary['isOverDrive'] = true;
		if ($totalCopies >= 999999){
			$statusSummary['alwaysAvailable'] = true;
		}
		if ($availableCopies > 0){
			$statusSummary['status'] = "Available from OverDrive";
			$statusSummary['available'] = true;
			$statusSummary['class'] = 'available';
		}else{
			$statusSummary['status'] = 'Checked Out';
			$statusSummary['available'] = false;
			$statusSummary['class'] = 'checkedOut';
			$statusSummary['isOverDrive'] = true;
		}


		//Determine which buttons to show
		$statusSummary['holdQueueLength'] = $numHolds;
		$statusSummary['showPlaceHold'] = $availableCopies == 0 && count($scopedAvailability['mine']) > 0;
		$statusSummary['showCheckout'] = $availableCopies > 0 && count($scopedAvailability['mine']) > 0;
		$statusSummary['showAddToWishlist'] = false;
		$statusSummary['showAccessOnline'] = false;

		$statusSummary['onHold'] = $onHold;
		$statusSummary['checkedOut'] = $checkedOut;
		$statusSummary['holdPosition'] = $holdPosition;
		$statusSummary['numHoldings'] = count($holdings);
		$statusSummary['wishListSize'] = $wishListSize;

		return $statusSummary;
	}
}
