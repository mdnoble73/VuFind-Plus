<?php
/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 1/8/2018
 *
 */


class HooplaDriver
{
	const memCacheKey = 'hoopla_api_access_token';
//	public $hooplaAPIBaseURL = 'hoopla-api-dev.hoopladigital.com';
	public $hooplaAPIBaseURL = 'hoopla-api-dev.hoopladigital.com';
	private $accessToken;
	private $hooplaEnabled = false;



	public function __construct()
	{
		global $configArray;
		if (!empty($configArray['Hoopla']['HooplaAPIUser']) && !empty($configArray['Hoopla']['HooplaAPIpassword'])) {
			$this->hooplaEnabled = true;
			if (!empty($configArray['Hoopla']['APIBaseURL'])) {
				$this->hooplaAPIBaseURL = $configArray['Hoopla']['APIBaseURL'];
				$this->getAccessToken();
			}
		}
	}


	// Originally copied from SirsiDynixROA Driver
	// $customRequest is for curl, can be 'PUT', 'DELETE', 'POST'
	private function getAPIResponse($url, $params = null, $customRequest = null, $additionalHeaders = null)
	{
		global $logger;
		$logger->log('Hoopla API URL :' .$url, PEAR_LOG_INFO);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$headers  = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'Authorization: Bearer ' . $this->accessToken,
			'Originating-App-Id: Pika',
		);
		if (!empty($additionalHeaders) && is_array($additionalHeaders)) {
			$headers = array_merge($headers, $additionalHeaders);
		}
		if (empty($customRequest)) {
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		} elseif ($customRequest == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
		}
		else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		global $instanceName;
		if (stripos($instanceName, 'localhost') !== false) {
			// For local debugging only
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		}
		if ($params != null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
		$json = curl_exec($ch);
//		// For debugging only
//		if (stripos($instanceName, 'localhost') !== false) {
//		$err  = curl_getinfo($ch);
//		$headerRequest = curl_getinfo($ch, CURLINFO_HEADER_OUT);
//		}

		$logger->log("Hoopla API response\r\n$json", PEAR_LOG_DEBUG);
		curl_close($ch);

		if ($json !== false && $json !== 'false') {
			return json_decode($json);
		} else {
			$logger->log('Curl problem in getAPIResponse', PEAR_LOG_WARNING);
			return false;
		}
	}

	/**
	 * @param $user User
	 */
	function getHooplaLibraryID($user) {
		if ($this->hooplaEnabled) {
			$library  = $user->getHomeLibrary();
			$hooplaID = $library->hooplaLibraryID;
			return $hooplaID;
		}
		return false;
}

	/**
	 * @param $user User
	 */
	public function getHooplaCheckedOutItems($user)
	{
		$checkedOutItems = array();
		if ($this->hooplaEnabled) {
			$hooplaID = $this->getHooplaLibraryID($user);
			$barcode  = $user->getBarcode();
			if (!empty($hooplaID) && !empty($barcode)) {
				$getCheckOutsURL   = $this->hooplaAPIBaseURL . '/api/v1/libraries/' .
					$hooplaID . '/patrons/' . $barcode . '/checkouts/current';
				$checkOutsResponse = $this->getAPIResponse($getCheckOutsURL);
				if (is_array($checkOutsResponse)) {
					foreach ($checkOutsResponse as $checkOut) {
						$hooplaRecordID  = 'MWT' . $checkOut->contentId;
						$simpleSortTitle = preg_replace('/^The\s|^A\s/i', '', $checkOut->title); // remove begining The or A

						$currentTitle = array(
							'checkoutSource' => 'Hoopla',
							'title' => $checkOut->title,
							'title_sort' => empty($simpleSortTitle) ? $checkOut->title : $simpleSortTitle,
							'author' => isset($checkOut->author) ? $checkOut->author : null,
							'format' => $checkOut->kind,
							'checkoutdate' => $checkOut->borrowed,
							'dueDate' => $checkOut->due,
						);
						require_once ROOT_DIR . '/RecordDrivers/HooplaDriver.php';
						$hooplaRecordDriver = new HooplaRecordDriver($hooplaRecordID);
						if ($hooplaRecordDriver->isValid()) {
							// Get Record For other details
							$currentTitle['coverUrl']      = $hooplaRecordDriver->getBookcoverUrl('medium');
							$currentTitle['linkUrl']       = $hooplaRecordDriver->getLinkUrl();
							$currentTitle['groupedWorkId'] = $hooplaRecordDriver->getGroupedWorkId();
							$currentTitle['ratingData']    = $hooplaRecordDriver->getRatingData();
							$currentTitle['title_sort']    = $hooplaRecordDriver->getSortableTitle();
							$currentTitle['author']        = $hooplaRecordDriver->getPrimaryAuthor();
							$currentTitle['format']        = implode(', ', $hooplaRecordDriver->getFormat());
						}
						$checkedOutItems[] = $currentTitle;
					}
				} else {
					global $logger;
					$logger->log('Error retrieving checkouts from Hoopla.', PEAR_LOG_ERR);
				}
			}
		}
		return $checkedOutItems;
	}

	/**
	 * @return string
	 */
	private function getAccessToken()
	{
		if (empty($this->accessToken)) {
			/** @var Memcache $memCache */
			global $memCache;
			$accessToken = $memCache->get(self::memCacheKey);
			if (empty($accessToken)) {
				$this->renewAccessToken();
			} else {
				$this->accessToken = $accessToken;
			}

		}
		return $this->accessToken;
	}

	private function renewAccessToken (){
		global $configArray;
		if (!empty($configArray['Hoopla']['HooplaAPIUser']) && !empty($configArray['Hoopla']['HooplaAPIpassword'])) {
			$url = 'https://' . str_replace(array('http://', 'https://'),'', $this->hooplaAPIBaseURL) . '/v2/token';
			// Ensure https is used

			$username = $configArray['Hoopla']['HooplaAPIUser'];
			$password = $configArray['Hoopla']['HooplaAPIpassword'];

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, array());

			global $instanceName;
			if (stripos($instanceName, 'localhost') !== false) {
				// For local debugging only
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLINFO_HEADER_OUT, true);
			}
			$response = curl_exec($curl);
//			// Use for debugging
//			if (stripos($instanceName, 'localhost') !== false) {
//				$err  = curl_getinfo($curl);
//				$headerRequest = curl_getinfo($curl, CURLINFO_HEADER_OUT);
//			}
			curl_close($curl);

			if ($response) {
				$json = json_decode($response);
				if (!empty($json->access_token)) {
					$this->accessToken = $json->access_token;

					/** @var Memcache $memCache */
					global $memCache;
					$memCache->set(self::memCacheKey, $this->accessToken, null, $json->expires_in);

				} else {
					global $logger;
					$logger->log('Hoopla API retrieve access token call did not contain an access token', PEAR_LOG_ERR);
				}
			} else {
				global $logger;
				$logger->log('Curl Error in Hoopla API call to retrieve access token', PEAR_LOG_ERR);
			}
		} else {
			global $logger;
			$logger->log('Hoopla API user and/or password not set. Can not retrieve access token', PEAR_LOG_ERR);
		}
	}

}