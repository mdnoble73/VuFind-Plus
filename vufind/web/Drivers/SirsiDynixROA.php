<?php
/**
 * Created by PhpStorm.
 * User: mnoble
 * Date: 4/10/2017
 * Time: 1:50 PM
 */

require_once ROOT_DIR . '/Drivers/HorizonAPI.php';
require_once ROOT_DIR . '/sys/Account/User.php';

abstract class SirsiDynixROA extends HorizonAPI {
	//TODO: Additional caching of sessionIds by patron
	private static $sessionIdsForUsers = array();

	public function getWebServiceResponse($url, $params = null, $session = null){
		global $configArray;
		global $logger;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$clientId = $configArray['Catalog']['clientId'];
		$headers = array(
				'Accept: application/json',
				'Content-Type: application/json',
				'SD-Originating-App-Id: Pika',
				'x-sirs-clientID: ' . $clientId,
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
		$logger->log("Web service response\r\n$json", PEAR_LOG_INFO);
		curl_close($ch);

		if ($json !== false && $json !== 'false'){
			return json_decode($json);
		}else{
			$logger->log('Curl problem in getWebServiceResponse', PEAR_LOG_WARNING);
			return false;
		}
	}

	public function getWebServiceURL(){
		$webServiceURL = null;
		if (!empty($this->accountProfile->patronApiUrl)) {
			$webServiceURL = $this->accountProfile->patronApiUrl;
		} elseif (!empty($configArray['Catalog']['webServiceUrl'])) {
			$webServiceURL = $configArray['Catalog']['webServiceUrl'];
		} else {
			global $logger;
			$logger->log('No Web Service URL defined in Horizon API Driver', PEAR_LOG_CRIT);
		}
		return $webServiceURL;
	}

	public function patronLogin($username, $password, $validatedViaSSO){
		global $timer;
		global $configArray;
		global $logger;

		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		//Authenticate the user via WebService
		//First call loginUser
		$logger->log("Logging in through Symphony APIs", PEAR_LOG_INFO);
		list($userValid, $sessionToken, $userID) = $this->loginViaWebService($username, $password);
		if ($validatedViaSSO){
			$userValid = true;
		}
		if ($userValid){
			$logger->log("User is valid in symphony", PEAR_LOG_INFO);
			$webServiceURL = $this->getWebServiceURL();

			//$userDescribeResponse = $this->getWebServiceResponse($webServiceURL . '/v1/user/describe', null, $sessionToken);
			$patronDescribeResponse = $this->getWebServiceResponse($webServiceURL . '/ws/user/patron/describe', null, $sessionToken);
			$patronStatusInfoDescribeResponse = $this->getWebServiceResponse($webServiceURL . '/ws/user/patronStatusInfo/describe', null, $sessionToken);
			$userProfileDescribeResponse = $this->getWebServiceResponse($webServiceURL . '/ws/policy/profile/describe', null, $sessionToken);
			//$lookupMyAccountInfoResponse = $this->getWebServiceResponse($webServiceURL . '/ws/user/patron/key/' . $userID . '?includeFields=firstName,lastName,displayName,privilegeExpiresDate,estimatedOverdueAmount,patronStatusInfo,preferredAddress,address1,address2,address3,primaryPhone,patronstatus,checkoutLocation,library', null, $sessionToken);
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse($webServiceURL . '/ws/user/patron/key/' . $userID , null, $sessionToken);
			if ($lookupMyAccountInfoResponse){
				$lastName = $lookupMyAccountInfoResponse->fields->lastName;
				$firstName = $lookupMyAccountInfoResponse->fields->firstName;

				if (isset($lookupMyAccountInfoResponse->fields->displayName)){
					$fullName = $lookupMyAccountInfoResponse->fields->displayName;
				}else{
					$fullName = $lastName . ', ' . $firstName;
				}

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
				if (isset($lookupMyAccountInfoResponse->fields->library->key)){
					$homeBranchCode = strtolower(trim($lookupMyAccountInfoResponse->fields->library->key));
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
					$logger->log('SirsiDynixROA Driver: No Home Library Location or Hold location found in account look-up. User : '.$user->id, PEAR_LOG_ERR);
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
				if (isset($lookupMyAccountInfoResponse->fields->privilegeExpiresDate)){
					$user->expires = $lookupMyAccountInfoResponse->fields->privilegeExpiresDate;
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

				//Get additional information about fines, etc
				$patronStatusResponse = $this->getWebServiceResponse($webServiceURL . '/ws/user/patronStatusInfo/key/' . $userID, null, $sessionToken);
				$patronStatusResponse2 = $this->getWebServiceResponse($webServiceURL . '/ws/user/patron/key/' . $userID . '?includeFields=patronStatusInfo,circRecordList,estimatedOverdueAmount,blockList,holdRecordList' , null, $sessionToken);

				$finesVal = 0;
				if (isset($patronStatusResponse2->fields->blockList)){
					foreach ($patronStatusResponse2->fields->blockList as $block){
						// $block is a simplexml object with attribute info about currency, type casting as below seems to work for adding up. plb 3-27-2015
						$fineAmount = (float) $block->balance;
						$finesVal += $fineAmount;

					}
				}

				$numHoldsAvailable = 0;
				$numHoldsRequested = 0;
				if (isset($patronStatusResponse2->fields->holdRecordList)){
					foreach ($patronStatusResponse2->fields->holdRecordList as $hold){
						$holdInfo = $this->getWebServiceResponse($webServiceURL . '/ws/circulation/holdRecord/key/' . $hold->key, null, $sessionToken);
						if ($holdInfo->fields->status == 'BEING_HELD'){
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
		$loginDescribeResponse = $this->getWebServiceResponse($configArray['Catalog']['webServiceUrl'] . '/user/patron/login/describe');
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
				SirsiDynixROA::$sessionIdsForUsers[$userID] = $sessionToken;
				return array(true, $sessionToken, $userID);
			}else{
				return array(false, false, false);
			}
		}
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $patron    The user to load transactions for
	 *
	 * @return array          Array of the patron's holds
	 * @access public
	 */
	public function getMyHolds($patron){
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
				'available'   => $availableHolds,
				'unavailable' => $unavailableHolds
		);

		//Get the session token for the user
		if (isset(SirsiDynixROA::$sessionIdsForUsers[$patron->id])){
			$sessionToken = SirsiDynixROA::$sessionIdsForUsers[$patron->id];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			if (!$userValid){
				return $holds;
			}
		}

		//Now that we have the session token, get holds information
		$webServiceURL = $this->getWebServiceURL();
		//Get a list of holds for the user
		$patronHolds = $this->getWebServiceResponse($webServiceURL . '/ws/user/patron/key/' . $patron->username . '?includeFields=holdRecordList' , null, $sessionToken);
		$holdRecord = $this->getWebServiceResponse($webServiceURL . "/ws/circulation/holdRecord/describe", null, $sessionToken);
		if ($patronHolds){
			require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			foreach ($patronHolds->fields->holdRecordList as $hold){
				$holdInfo = $this->getWebServiceResponse($webServiceURL . '/ws/circulation/holdRecord/key/' . $hold->key, null, $sessionToken);
				$curHold = array();
				$bibId                         = $holdInfo->fields->bib->key;
				$expireDate                    = $holdInfo->fields->expirationDate;
				$reactivateDate                = $holdInfo->fields->suspendEndDate;
				$curHold['user']               = $patron->getNameAndLibraryLabel(); //TODO: Likely not needed, because Done in Catalog Connection
				$curHold['id']                 = $hold->key;
				$curHold['holdSource']         = 'ILS';
				$curHold['itemId']             = $holdInfo->fields->item->key;
				$curHold['cancelId']           = $hold->key;
				$curHold['position']           = $holdInfo->fields->queuePosition;
				$curHold['recordId']           = $bibId;
				$curHold['shortId']            = $bibId;
				//$curHold['title']              = (string)$hold->title;
				//$curHold['sortTitle']          = (string)$hold->title;
				//$curHold['author']             = (string)$hold->author;
				$curHold['location']           = $holdInfo->fields->pickupLibrary->key;
				$curHold['locationUpdateable'] = true;
				$curHold['currentPickupName']  = $curHold['location'];
				$curHold['status']             = ucfirst(strtolower($holdInfo->fields->status));
				$curHold['expire']             = strtotime($expireDate);
				$curHold['reactivate']         = $reactivateDate;
				$curHold['reactivateTime']     = strtotime($reactivateDate);
				$curHold['cancelable']         = strcasecmp($curHold['status'], 'Suspended') != 0;
				$curHold['frozen']             = strcasecmp($curHold['status'], 'Suspended') == 0;
				$curHold['freezeable'] = true;
				if (strcasecmp($curHold['status'], 'Transit') == 0) {
					$curHold['freezeable'] = false;
				}

				$recordDriver = new MarcRecord('a' . $bibId);
				if ($recordDriver->isValid()){
					$curHold['sortTitle']       = $recordDriver->getSortableTitle();
					$curHold['format']          = $recordDriver->getFormat();
					$curHold['isbn']            = $recordDriver->getCleanISBN();
					$curHold['upc']             = $recordDriver->getCleanUPC();
					$curHold['format_category'] = $recordDriver->getFormatCategory();
					$curHold['coverUrl']        = $recordDriver->getBookcoverUrl();
					$curHold['link']            = $recordDriver->getRecordUrl();

					//Load rating information
					$curHold['ratingData']      = $recordDriver->getRatingData();

					if (empty($curHold['title'])){
						$curHold['title'] = $recordDriver->getTitle();
					}
					if (empty($curHold['author'])){
						$curHold['author'] = $recordDriver->getPrimaryAuthor();
					}
				}

				if (!isset($curHold['status']) || strcasecmp($curHold['status'], "being_held") != 0){
					$holds['unavailable'][] = $curHold;
				}else{
					$holds['available'][]   = $curHold;
				}
			}
		}
		return $holds;
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param   User    $patron     The User to place a hold for
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $itemId     The id of the item to hold
	 * @param   string  $comment    Any comment regarding the hold or recall
	 * @param   string  $type       Whether to place a hold or recall
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $comment = '', $type = 'request') {
		global $configArray;

		$userId = $patron->id;

		//Get the session token for the user
		if (isset(SirsiDynixROA::$sessionIdsForUsers[$userId])){
			$sessionToken = SirsiDynixROA::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			if (!$userValid){
				return array(
						'success' => false,
						'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again');
			}
		}

		// Retrieve Full Marc Record
		require_once ROOT_DIR . '/RecordDrivers/Factory.php';
		$record = RecordDriverFactory::initRecordDriverById('ils:' . $recordId);
		if (!$record) {
			$title = null;
		}else{
			$title = $record->getTitle();
		}

		if ($configArray['Catalog']['offline']){
			require_once ROOT_DIR . '/sys/OfflineHold.php';
			$offlineHold = new OfflineHold();
			$offlineHold->bibId = $recordId;
			$offlineHold->patronBarcode = $patron->getBarcode();
			$offlineHold->patronId = $patron->id;
			$offlineHold->timeEntered = time();
			$offlineHold->status = 'Not Processed';
			if ($offlineHold->insert()){
				//TODO: use bib or bid ??
				return array(
						'title'   => $title,
						'bib'     => $recordId,
						'success' => true,
						'message' => 'The circulation system is currently offline.  This hold will be entered for you automatically when the circulation system is online.');
			}else{
				return array(
						'title'   => $title,
						'bib'     => $recordId,
						'success' => false,
						'message' => 'The circulation system is currently offline and we could not place this hold.  Please try again later.');
			}

		}else{
			if ($type == 'cancel' || $type == 'recall' || $type == 'update') {
				$result = $this->updateHold($patron, $recordId, $type/*, $title*/);
				$result['title'] = $title;
				$result['bid']   = $recordId;
				return $result;

			} else {
				if (isset($_REQUEST['campus'])){
					$campus=trim($_REQUEST['campus']);
				}else{
					$campus = $patron->homeLocationId;
				}
				//create the hold using the web service
				$webServiceURL = $this->getWebServiceURL();

				$holdData = array(
						'patronBarcode' => $patron->getBarcode(),
						'pickupLibrary' => array(
								'resource' => '/policy/library',
								'key' => strtoupper($campus)
						),
				);
				if ($itemId){
					$holdData['itemBarcode'] = $itemId;
					$holdData['holdType'] = 'COPY';
				}else{
					$shortRecordId = str_replace('a', '', $recordId);
					$holdData['bib'] = array(
							'resource' => '/catalog/bib',
							'key' => $shortRecordId
					);
					$holdData['holdType'] = 'TITLE';
				}

				$holdRecord = $this->getWebServiceResponse($webServiceURL . "/ws/circulation/holdRecord/placeHold/describe", null, $sessionToken);
				$createHoldResponse = $this->getWebServiceResponse($webServiceURL . "/ws/circulation/holdRecord/placeHold", $holdData, $sessionToken);

				$hold_result = array();
				if (isset($createHoldResponse->messageList)){
					$hold_result['success'] = false;
					$hold_result['message'] = 'Your hold could not be placed. ';
					if (isset($createHoldResponse->messageList)){
						$hold_result['message'] .= (string)$createHoldResponse->messageList[0]->message;
					}
				}else{
					$hold_result['success'] = true;
					$hold_result['message'] = 'Your hold was placed successfully.';
				}

				$hold_result['title']  = $title;
				$hold_result['bid']    = $recordId;
				global $analytics;
				if ($analytics){
					if ($hold_result['success'] == true){
						$analytics->addEvent('ILS Integration', 'Successful Hold', $title);
					}else{
						$analytics->addEvent('ILS Integration', 'Failed Hold', $hold_result['message'] . ' - ' . $title);
					}
				}
				//Clear the patron profile
				return $hold_result;

			}
		}
	}
}