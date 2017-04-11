<?php
/**
 * Created by PhpStorm.
 * User: mnoble
 * Date: 4/10/2017
 * Time: 1:50 PM
 */

require_once ROOT_DIR . '/Drivers/HorizonAPI.php';
require_once ROOT_DIR . '/sys/Account/User.php';

abstract class SymphonyAPI extends HorizonAPI {
	//TODO: Additional caching of sessionIds by patron
	private static $sessionIdsForUsers = array();

	public function getWebServiceResponse($url, $params = null, $session = null){
		global $configArray;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$headers = array(
				'Accept: application/json',
				'Content-Type: application/json',
				'SD-Originating-App-Id: Pika',
				'x-sirs-clientID: ' . $configArray['Catalog']['clientId'],
		);
		if ($session != null){
			$headers[] = 'x-sirs-sessionToken: ' . $session;
		}
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($params != null){
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
		$json = curl_exec($ch);
		curl_close($ch);

		if ($json !== false && $json !== 'false'){
			return json_decode($json);
		}else{
			global $logger;
			$logger->log('Curl problem in getWebServiceResponse', PEAR_LOG_WARNING);
			return false;
		}
	}

	public function patronLogin($username, $password, $validatedViaSSO){
		global $timer;
		global $configArray;

		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		//Authenticate the user via WebService
		//First call loginUser
		list($userValid, $sessionToken, $userID) = $this->loginViaWebService($username, $password);
		if ($validatedViaSSO){
			$userValid = true;
		}
		if ($userValid){
			if (!empty($this->accountProfile->patronApiUrl)) {
				$webServiceURL = $this->accountProfile->patronApiUrl;
			} elseif (!empty($configArray['Catalog']['webServiceUrl'])) {
				$webServiceURL = $configArray['Catalog']['webServiceUrl'];
			} else {
				global $logger;
				$logger->log('No Web Service URL defined in Horizon API Driver', PEAR_LOG_CRIT);
				return null;
			}

			$lookupMyAccountInfoResponse = $this->getWebServiceResponse($webServiceURL . '/v1/user/patron/describe', null, $sessionToken);
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse($webServiceURL . '/v1/user/patronStatusInfo/describe', null, $sessionToken);
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse($webServiceURL . '/v1/user/patron/key/' . $userID . '?includeFields=firstName,lastName,displayName,patronStatusInfo,preferredAddress,address1,address2,address3', null, $sessionToken);
			if ($lookupMyAccountInfoResponse){
				$fullName = $lookupMyAccountInfoResponse->fields->displayName;
				$lastName = $lookupMyAccountInfoResponse->fields->lastName;
				$firstName = $lookupMyAccountInfoResponse->fields->firstName;

				$userExistsInDB = false;
				/** @var User $user */
				$user = new User();
//				$user->source = $this->accountProfile->name;
				$user->username = $userID;
				if ($user->find(true)){
					$userExistsInDB = true;
				}

				$forceDisplayNameUpdate = false;
				$firstName = isset($firstName) ? $firstName : '';
				if ($user->firstname != $firstName) {
					$user->firstname = $firstName;
					$forceDisplayNameUpdate = true;
				}
				$lastName = isset($lastName) ? $lastName : '';
				if ($user->lastname != $lastName){
					$user->lastname = isset($lastName) ? $lastName : '';
					$forceDisplayNameUpdate = true;
				}
				if ($forceDisplayNameUpdate){
					$user->displayName = '';
				}
				$user->fullname = isset($fullName) ? $fullName : '';
				$user->cat_username = $username;
				$user->cat_password = $password;

				if (isset($lookupMyAccountInfoResponse->preferredAddress)){
					if ($lookupMyAccountInfoResponse->preferredAddress == 1) {
						$address = $lookupMyAccountInfoResponse->address1;
					}elseif ($lookupMyAccountInfoResponse->preferredAddress == 2){
						$address = $lookupMyAccountInfoResponse->address2;
					}elseif ($lookupMyAccountInfoResponse->preferredAddress == 3){
						$address = $lookupMyAccountInfoResponse->address3;
					}
					foreach ($address as $addressField){
						if ($addressField->key == '2'){
							$Address1 = $addressField->fields->data;
						}elseif ($addressField->key == '3'){
							$cityState = $addressField->fields->data;
							list($City, $State) = explode(' ', $cityState);
						}elseif ($addressField->key == '4'){
							$Zip = $addressField->fields->data;
						}elseif ($addressField->key == '6'){
							$email = $addressField->fields->data;
							$user->email = $email;
						}
					}

				}else{
					$Address1 = "";
					$City = "";
					$State = "";
					$Zip = "";
					$user->email = '';
				}

				//Get additional information about the patron's home branch for display.
				if (isset($lookupMyAccountInfoResponse->library->key)){
					$homeBranchCode = strtolower(trim($lookupMyAccountInfoResponse->library->key));
					//Translate home branch to plain text
					/** @var \Location $location */
					$location = new Location();
					$location->code = $homeBranchCode;
//					$location->find(1);
					if (!$location->find(true)){
						unset($location);
					}
				} else {
					global $logger;
					$logger->log('SymphonyAPI Driver: No Home Library Location or Hold location found in account look-up. User : '.$user->id, PEAR_LOG_ERR);
					// The code below will attempt to find a location for the library anyway if the homeLocation is already set
				}

				if (empty($user->homeLocationId) || (isset($location) && $user->homeLocationId != $location->locationId)) { // When homeLocation isn't set or has changed
					if (empty($user->homeLocationId) && !isset($location)) {
						// homeBranch Code not found in location table and the user doesn't have an assigned homelocation,
						// try to find the main branch to assign to user
						// or the first location for the library
						global $library;

						/** @var \Location $location */
						$location            = new Location();
						$location->libraryId = $library->libraryId;
						$location->orderBy('isMainBranch desc'); // gets the main branch first or the first location
						if (!$location->find(true)) {
							// Seriously no locations even?
							global $logger;
							$logger->log('Failed to find any location to assign to user as home location', PEAR_LOG_ERR);
							unset($location);
						}
					}
					if (isset($location)) {
						$user->homeLocationId = $location->locationId;
						$user->myLocation1Id  = ($location->nearbyLocation1 > 0) ? $location->nearbyLocation1 : $location->locationId;
						$user->myLocation2Id  = ($location->nearbyLocation2 > 0) ? $location->nearbyLocation2 : $location->locationId;

						/** @var /Location $location */
						//Get display name for preferred location 1
						$myLocation1 = new Location();
						$myLocation1->locationId = $user->myLocation1Id;
						if ($myLocation1->find(true)) {
							$user->myLocation1 = $myLocation1->displayName;
						}

						//Get display name for preferred location 2
						$myLocation2 = new Location();
						$myLocation2->locationId = $user->myLocation2Id;
						if ($myLocation2->find(true)) {
							$user->myLocation2 = $myLocation2->displayName;
						}
					}
				}

				if (isset($location)){
					//Get display names that aren't stored
					$user->homeLocationCode = $location->code;
					$user->homeLocation     = $location->displayName;
				}


				//TODO: See if we can get information about card expiration date
				$expireClose = 0;
				if (isset($lookupMyAccountInfoResponse->privilegeExpiresDate)){
					$user->expires = $lookupMyAccountInfoResponse->privilegeExpiresDate;
					list ($monthExp, $dayExp, $yearExp) = explode("-",$user->expires);
					$timeExpire = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
					$timeNow = time();
					$timeToExpire = $timeExpire - $timeNow;
					if ($timeToExpire <= 30 * 24 * 60 * 60){
						if ($timeToExpire <= 0){
							$user->expired = 1;
						}
						$user->expireClose = 1;
					}
				}

				$finesVal = 0;
				if (isset($lookupMyAccountInfoResponse->blockList)){
					foreach ($lookupMyAccountInfoResponse->blockList as $block){
						// $block is a simplexml object with attribute info about currency, type casting as below seems to work for adding up. plb 3-27-2015
						$fineAmount = (float) $block->balance;
						$finesVal += $fineAmount;
					}
				}

				$numHoldsAvailable = 0;
				$numHoldsRequested = 0;
				if (isset($lookupMyAccountInfoResponse->holdRecordList)){
					foreach ($lookupMyAccountInfoResponse->holdRecordList as $hold){
						if ($hold->status == 'FILLED'){
							$numHoldsAvailable++;
						}else{
							$numHoldsRequested++;
						}
					}
				}

				$user->address1              = $Address1;
				$user->address2              = $City . ', ' . $State;
				$user->city                  = $City;
				$user->state                 = $State;
				$user->zip                   = $Zip;
				$user->phone                 = isset($lookupMyAccountInfoResponse->phone) ? (string)$lookupMyAccountInfoResponse->phone : '';
				$user->fines                 = sprintf('$%01.2f', $finesVal);
				$user->finesVal              = $finesVal;
				$user->expires               = ''; //TODO: Determine if we can get this
				$user->expireClose           = $expireClose;
				$user->numCheckedOutIls      = isset($lookupMyAccountInfoResponse->ItemsOutInfo) ? count($lookupMyAccountInfoResponse->ItemsOutInfo) : 0;
				$user->numHoldsIls           = $numHoldsAvailable + $numHoldsRequested;
				$user->numHoldsAvailableIls  = $numHoldsAvailable;
				$user->numHoldsRequestedIls  = $numHoldsRequested;
				$user->patronType            = 0;
				$user->notices               = '-';
				$user->noticePreferenceLabel = 'E-mail';
				$user->web_note              = '';

				if ($userExistsInDB){
					$user->update();
				}else{
					$user->created = date('Y-m-d');
					$user->insert();
				}

				$timer->logTime("patron logged in successfully");
				return $user;
			} else {
				$timer->logTime("lookupMyAccountInfo failed");
				global $logger;
				$logger->log('Horizon API call lookupMyAccountInfo failed.', PEAR_LOG_ERR);
//				$logger->log($configArray['Catalog']['webServiceUrl'] . '/standard/lookupMyAccountInfo?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&includeAddressInfo=true&includeHoldInfo=true&includeBlockInfo=true&includeItemsOutInfo=true', PEAR_LOG_ERR);
				return null;
			}
		}
	}

	protected function loginViaWebService($username, $password) {
		global $configArray;
		$loginUserUrl = $configArray['Catalog']['webServiceUrl'] . '/user/patron/login';
		$params = array(
				'login' => $username,
				'password' => $password,
		);
		$loginUserResponse = $this->getWebServiceResponse($loginUserUrl, $params);
		if (!$loginUserResponse){
			return array(false, false, false);
		}else if (isset($loginUserResponse->messageList)){
			return array(false, false, false);
		}else{
			//We got at valid user, next call lookupMyAccountInfo
			if (isset($loginUserResponse->sessionToken)){
				$userID = $loginUserResponse->patronKey;
				$sessionToken = $loginUserResponse->sessionToken;
				SymphonyAPI::$sessionIdsForUsers[$userID] = $sessionToken;
				return array(true, $sessionToken, $userID);
			}else{
				return array(false, false, false);
			}
		}
	}
}