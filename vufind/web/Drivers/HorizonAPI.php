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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 */

require_once 'Interface.php';
require_once ROOT_DIR . '/Drivers/Horizon.php';

abstract class HorizonAPI extends Horizon{
	//TODO: Additional caching of sessionIds by patron
	private static $sessionIdsForUsers = array();
	/** uses SIP2 login the user via web services API **/
	public function patronLogin($username, $password){
		global $timer;
		global $configArray;

		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		//Call web service to login
		if ($configArray['Catalog']['offline'] == true){
			//The catalog is offline, check the database to see if the user is valid
			$user = new User();
			$user->cat_password = $password;
			if ($user->find(true)){
				$userValid = false;
				if ($user->cat_username){
					list($fullName, $lastName, $firstName) = $this->splitFullName($user->username);
				}
				if ($userValid){
					$returnVal = array(
						'id'        => $password,
						'username'  => $user->username,
						'firstname' => isset($firstName) ? $firstName : '',
						'lastname'  => isset($lastName) ? $lastName : '',
						'fullname'  => isset($fullName) ? $fullName : '',     //Added to array for possible display later.
						'cat_username' => $username, //Should this be $Fullname or $patronDump['PATRN_NAME']
						'cat_password' => $password,

						'email' => $user->email,
						'major' => null,
						'college' => null,
						'patronType' => $user->patronType,
						'web_note' => translate('The catalog is currently down.  You will have limited access to circulation information.'));
					$timer->logTime("patron logged in successfully");
					return $returnVal;
				} else {
					$timer->logTime("patron login failed");
					return null;
				}
			} else {
				$timer->logTime("patron login failed");
				return null;
			}
		}else{
			//Authenticate the user via WebService
			//First call loginUser
			list($userValid, $sessionToken, $userID) = $this->loginViaWebService($username, $password);
			if ($userValid){
				$lookupMyAccountInfoResponse = $this->getWebServiceResponse($configArray['Catalog']['webServiceUrl'] . '/standard/lookupMyAccountInfo?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&includeAddressInfo=true');
				if ($lookupMyAccountInfoResponse){
					$fullName = (string)$lookupMyAccountInfoResponse->name;
					list($fullName, $lastName, $firstName) = $this->splitFullName($fullName);

					$email = '';
					if (isset($lookupMyAccountInfoResponse->AddressInfo)){
						if (isset($lookupMyAccountInfoResponse->AddressInfo->email)){
							$email = (string)$lookupMyAccountInfoResponse->AddressInfo->email;
						}
					}

					$returnVal = array(
						'id'        => $userID,
						'username'  => $fullName,
						'firstname' => isset($firstName) ? $firstName : '',
						'lastname'  => isset($lastName) ? $lastName : '',
						'fullname'  => isset($fullName) ? $fullName : '',     //Added to array for possible display later.
						'cat_username' => $username,
						'cat_password' => $password,

						'email' => $email,
						'major' => null,
						'college' => null,
						'patronType' => '',
						'web_note' => '',
					);
					$timer->logTime("patron logged in successfully");
					return $returnVal;
				} else {
					$timer->logTime("lookupMyAccountInfo failed");
					return null;
				}
			}
		}
	}

	private function loginViaWebService($username, $password) {
		global $configArray;
		$loginUserUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/loginUser?clientID=' . $configArray['Catalog']['clientId'] . '&login=' . urlencode($username) . '&password=' . urlencode($password);
		$loginUserResponse = $this->getWebServiceResponse($loginUserUrl);
		if (!$loginUserResponse){
			return array(false);
		}else if (isset($loginUserResponse->Fault)){
			return array(false);
		}else{
			//We got at valid user, next call lookupMyAccountInfo
			if (isset($loginUserResponse->sessionToken)){
				$userID = (string)$loginUserResponse->userID;
				$sessionToken = (string)$loginUserResponse->sessionToken;
				HorizonAPI::$sessionIdsForUsers[$userID] = $sessionToken;
				return array(true, $sessionToken, $userID);
			}else{
				return array(false);
			}
		}
	}

	public function getMyProfile($patron, $forceReload = false){
		global $timer;
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;

		if (is_object($patron)){
			$patron = get_object_vars($patron);
			$userId = $patron['id'];
		}else{
			global $user;
			$userId = $user->id;
		}

		$patronProfile = $memCache->get('patronProfile_' . $userId);
		if ($patronProfile && !isset($_REQUEST['reload']) && !$forceReload){
			//echo("Using cached profile for patron " . $userId);
			$timer->logTime('Retrieved Cached Profile for Patron');
			return $patronProfile;
		}

		global $user;
		if ($configArray['Catalog']['offline'] == true){
			$fullName = $patron['cat_username'];

			$Address1 = "";
			$City = "";
			$State = "";
			$Zip = "";
			$finesVal = 0;
			$expireClose = false;
			$homeBranchCode = '';
			$numHoldsAvailable = '?';
			$numHoldsRequested = '?';

			if (!$user){
				$user = new User();
				$user->id = $userId;
				if ($user->find(true)){
					$location = new Location();
					$location->locationId = $user->homeLocationId;
					$location->find(1);
					$homeBranchCode = $location->code;
				}
			}


		}else{
			//Load the raw information about the patron from web services
			if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
				$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
			}else{
				//Log the user in
				$return = $this->loginViaWebService($patron['cat_username'], $patron['cat_password']);
				if (count($return) == 1){
					$userValid = $return[0];
				}else{
					list($userValid, $sessionToken) = $return;
				}
				if (!$userValid){
					echo("No session id found for user");
					return null;
				}
			}
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse($configArray['Catalog']['webServiceUrl'] . '/standard/lookupMyAccountInfo?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&includeAddressInfo=true&includeHoldInfo=true&includeBlockInfo=true&includeItemsOutInfo=true');

			if (isset($lookupMyAccountInfoResponse->AddressInfo)){
				$Address1 = (string)$lookupMyAccountInfoResponse->AddressInfo->line1;
				if (isset($lookupMyAccountInfoResponse->AddressInfo->cityState)){
					$cityState = (string)$lookupMyAccountInfoResponse->AddressInfo->cityState;
					list($City, $State) = explode(', ', $cityState);
				}else{
					$City = "";
					$State = "";
				}

				$Zip = (string)$lookupMyAccountInfoResponse->AddressInfo->postalCode;
			}else{
				$Address1 = "";
				$City = "";
				$State = "";
				$Zip = "";
			}

			$fullName = $lookupMyAccountInfoResponse->name;

			//Get additional information about the patron's home branch for display.
			if (isset($lookupMyAccountInfoResponse->locationID)){
				$homeBranchCode = (string)$lookupMyAccountInfoResponse->locationID;
				//Translate home branch to plain text
				$location = new Location();
				$location->whereAdd("code = '$homeBranchCode'");
				$location->find(1);
			}

			if ($user) {
				if ($user->homeLocationId == 0 && isset($location)) {
					$user->homeLocationId = $location->locationId;
					if ($location->nearbyLocation1 > 0){
						$user->myLocation1Id = $location->nearbyLocation1;
					}else{
						$user->myLocation1Id = $location->locationId;
					}
					if ($location->nearbyLocation2 > 0){
						$user->myLocation2Id = $location->nearbyLocation2;
					}else{
						$user->myLocation2Id = $location->locationId;
					}
					if ($user instanceof User) {
						//Update the database
						$user->update();
						//Update the serialized instance stored in the session
						$_SESSION['userinfo'] = serialize($user);
					}
				}
			}

			//TODO: See if we can get information about card expiration date
			$expireClose = 0;

			//TODO: Calculate total fines
			$finesVal = 0;

			$numHoldsAvailable = 0;
			$numHoldsRequested = 0;
			if (isset($lookupMyAccountInfoResponse->HoldInfo)){
				foreach ($lookupMyAccountInfoResponse->HoldInfo as $hold){
					if ($hold->status == 'FILLED'){
						$numHoldsAvailable++;
					}else{
						$numHoldsRequested++;
					}
				}
			}
		}

		if ($user) {
			//Get display name for preferred location 1
			$myLocation1 = new Location();
			$myLocation1->whereAdd("locationId = '$user->myLocation1Id'");
			$myLocation1->find(1);

			//Get display name for preferred location 1
			$myLocation2 = new Location();
			$myLocation2->whereAdd("locationId = '$user->myLocation2Id'");
			$myLocation2->find(1);
		}

		list($fullName, $lastName, $firstName) = $this->splitFullName($fullName);
		$profile = array('lastname' => $lastName,
			'firstname' => $firstName,
			'fullname' => $fullName,
			'address1' => $Address1,
			'address2' => $City . ', ' . $State,
			'city' => $City,
			'state' => $State,
			'zip'=> $Zip,
			'email' => ($user && $user->email) ? $user->email : (isset($patronDump) && isset($patronDump['EMAIL_ADDR']) ? $patronDump['EMAIL_ADDR'] : '') ,
			'overdriveEmail' => ($user) ? $user->overdriveEmail : (isset($patronDump) && isset($patronDump['EMAIL_ADDR']) ? $patronDump['EMAIL_ADDR'] : ''),
			'promptForOverdriveEmail' => $user ? $user->promptForOverdriveEmail : 1,
			'phone' => isset($lookupMyAccountInfoResponse->phone) ? (string)$lookupMyAccountInfoResponse->phone : '',
			'workPhone' => '',
			'mobileNumber' => '',
			'fines' => $finesVal,
			'finesval' => $finesVal,
			'expires' => '', //TODO: Determine if we can get this
			'expireclose' => $expireClose,
			'homeLocationCode' => isset($homeBranchCode) ? trim($homeBranchCode) : '',
			'homeLocationId' => isset($location) ? $location->locationId : 0,
			'homeLocation' => isset($location) ? $location->displayName : '',
			'myLocation1Id' => ($user) ? $user->myLocation1Id : -1,
			'myLocation1' => isset($myLocation1) ? $myLocation1->displayName : '',
			'myLocation2Id' => ($user) ? $user->myLocation2Id : -1,
			'myLocation2' => isset($myLocation2) ? $myLocation2->displayName : '',
			'numCheckedOut' => isset($lookupMyAccountInfoResponse->ItemsOutInfo) ? count($lookupMyAccountInfoResponse->ItemsOutInfo) : 0,
			'numHolds' => $numHoldsAvailable + $numHoldsRequested,
			'numHoldsAvailable' => $numHoldsAvailable,
			'numHoldsRequested' => $numHoldsRequested,
			'bypassAutoLogout' => ($user) ? $user->bypassAutoLogout : 0,
			'ptype' => 0,
			'notices' => '-',
			'web_note' => '',
		);
		$profile['noticePreferenceLabel'] = 'e-mail';

		//Get eContent info as well
		require_once(ROOT_DIR . '/Drivers/EContentDriver.php');
		$eContentDriver = new EContentDriver();
		$eContentAccountSummary = $eContentDriver->getAccountSummary();
		$profile = array_merge($profile, $eContentAccountSummary);

		require_once(ROOT_DIR . '/Drivers/OverDriveDriverFactory.php');
		$overDriveDriver = OverDriveDriverFactory::getDriver();
		if ($overDriveDriver->isUserValidForOverDrive($user)){
			$overDriveSummary = $overDriveDriver->getOverDriveSummary($user);
			$profile['numOverDriveCheckedOut'] = $overDriveSummary['numCheckedOut'];
			$profile['numOverDriveHoldsAvailable'] = $overDriveSummary['numAvailableHolds'];
			$profile['numOverDriveHoldsRequested'] = $overDriveSummary['numUnavailableHolds'];
			$profile['canUseOverDrive'] = true;
		}else{
			$profile['numOverDriveCheckedOut'] = 0;
			$profile['numOverDriveHoldsAvailable'] = 0;
			$profile['numOverDriveHoldsRequested'] = 0;
			$profile['canUseOverDrive'] = false;
		}

		$profile['numCheckedOutTotal'] = $profile['numCheckedOut'] + $profile['numOverDriveCheckedOut'] + $eContentAccountSummary['numEContentCheckedOut'];
		$profile['numHoldsAvailableTotal'] = $profile['numHoldsAvailable'] + $profile['numOverDriveHoldsAvailable'] + $eContentAccountSummary['numEContentAvailableHolds'];
		$profile['numHoldsRequestedTotal'] = $profile['numHoldsRequested'] + $profile['numOverDriveHoldsRequested'] + $eContentAccountSummary['numEContentUnavailableHolds'];
		$profile['numHoldsTotal'] = $profile['numHoldsAvailableTotal'] + $profile['numHoldsRequestedTotal'];

		//Get a count of the materials requests for the user
		if ($user){
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->createdBy = $user->id;
			$homeLibrary = Library::getPatronHomeLibrary();
			$statusQuery = new MaterialsRequestStatus();
			$statusQuery->isOpen = 1;
			$statusQuery->libraryId = $homeLibrary->libraryId;
			$materialsRequest->joinAdd($statusQuery);
			$materialsRequest->find();
			$profile['numMaterialsRequests'] = $materialsRequest->N;
		}

		$timer->logTime("Got Patron Profile");
		$memCache->set('patronProfile_' . $patron['id'], $profile, 0, $configArray['Caching']['patron_profile']) ;
		return $profile;
	}

	public function getMyHolds($patron, $page = 1, $recordsPerPage = -1, $sortOption = 'title'){
		global $configArray;

		if (is_object($patron)){
			$patron = get_object_vars($patron);
			$userId = $patron['id'];
		}else{
			global $user;
			$userId = $user->id;
		}

		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron['cat_username'], $patron['cat_password']);
			if (!$userValid){
				echo("No session id found for user");
				return $holds;
			}
		}

		//Now that we have the session token, get holds information
		$lookupMyAccountInfoResponse = $this->getWebServiceResponse($configArray['Catalog']['webServiceUrl'] . '/standard/lookupMyAccountInfo?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&includeHoldInfo=true');
		if (isset($lookupMyAccountInfoResponse->HoldInfo)){
			require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			foreach ($lookupMyAccountInfoResponse->HoldInfo as $hold){
				$curHold= array();
				$bibId = (string)$hold->titleKey;
				$curHold['id'] = $bibId;
				$curHold['holdSource'] = 'ILS';
				$curHold['itemId'] = (string)$hold->itemKey;
				$curHold['cancelId'] = (string)$hold->holdKey;
				$curHold['position'] = (string)$hold->queuePosition;
				$curHold['recordId'] = $bibId;
				$curHold['shortId'] = $bibId;
				$curHold['title'] = (string)$hold->title;
				$curHold['author'] = (string)$hold->author;
				$curHold['location'] = (string)$hold->pickupLocDescription;
				//$curHold['locationId'] = $matches[1];
				$curHold['locationUpdateable'] = true;
				$curHold['currentPickupName'] = $curHold['location'];
				$curHold['status'] = ucfirst(strtolower((string)$hold->status));
				$expireDate = (string)$hold->expireDate;
				$curHold['expire'] = $expireDate;
				$curHold['expireTime'] = strtotime($expireDate);
				$reactivateDate = (string)$hold->reactivateDate;
				$curHold['reactivate'] = $reactivateDate;
				$curHold['reactivateTime'] = strtotime($reactivateDate);

				$curHold['cancelable'] = $hold->status == 'Pending' || $hold->status == '';
				$curHold['frozen'] = $curHold['status'] == 'Suspended';
				if ($curHold['frozen']){
					$curHold['reactivateTime'] = (int)$hold->reactivateDate;
				}
				$curHold['freezeable'] = true;

				$curHold['sortTitle'] = (string)$hold->title;
				$recordDriver = new MarcRecord($bibId);
				if ($recordDriver->isValid()){
					$curHold['sortTitle'] = $recordDriver->getSortableTitle();
					$curHold['format'] = $recordDriver->getFormat();
					$curHold['isbn'] = $recordDriver->getCleanISBN();
					$curHold['upc'] = $recordDriver->getCleanUPC();
					$curHold['format_category'] = $recordDriver->getFormatCategory();

					//Load rating information
					$curHold['ratingData'] = $recordDriver->getRatingData();
				}

				if (!isset($curHold['status']) || strcasecmp($curHold['status'], "filled") != 0){
					$holds['unavailable'][] = $curHold;
				}else{
					$holds['available'][] = $curHold;
				}
			}
		}
		return array(
			'holds' => $holds,
			'numUnavailableHolds' => count($holds['unavailable']),
		);
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds as well as placing recalls.
	 *
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $patronId   The id of the patron
	 * @param   string  $comment    Any comment regarding the hold or recall
	 * @param   string  $type       Whether to place a hold or recall
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occures, return a PEAR_Error
	 * @access  public
	 */
	public function placeHold($recordId, $patronId, $comment, $type){
		$result = $this->placeItemHold($recordId, null, $patronId, $comment, $type);
		return $result;
	}

	public function placeItemHold($recordId, $itemId, $patronId, $comment, $type){
		global $configArray;

		global $user;
		$userId = $user->id;

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($user->cat_username, $user->cat_password);
			if (!$userValid){
				return array(
					'result' => false,
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
			global $user;
			require_once ROOT_DIR . '/sys/OfflineHold.php';
			$offlineHold = new OfflineHold();
			$offlineHold->bibId = $recordId;
			$offlineHold->patronBarcode = $patronId;
			$offlineHold->patronId = $user->id;
			$offlineHold->timeEntered = time();
			$offlineHold->status = 'Not Processed';
			if ($offlineHold->insert()){
				return array(
					'title' => $title,
					'bib' => $recordId,
					'result' => true,
					'message' => 'The circulation system is currently offline.  This hold will be entered for you automatically when the circulation system is online.');
			}else{
				return array(
					'title' => $title,
					'bib' => $recordId,
					'result' => false,
					'message' => 'The circulation system is currently offline and we could not place this hold.  Please try again later.');
			}

		}else{
			if ($type == 'cancel' || $type == 'recall' || $type == 'update') {
				$result = $this->updateHold($recordId, $patronId, $type, $title);
				$result['title'] = $title;
				$result['bid'] = $recordId;
				return $result;

			} else {
				if (isset($_REQUEST['campus'])){
					$campus=trim($_REQUEST['campus']);
				}else{
					global $user;
					$campus = $user->homeLocationId;
				}
				//create the hold using the web service
				$createHoldUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/createMyHold?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&pickupLocation=' . $campus . '&titleKey=' . $recordId ;
				if ($itemId){
					$createHoldUrl .= '&itemKey=' . $itemId;
				}

				$createHoldResponse = $this->getWebServiceResponse($createHoldUrl);

				$hold_result = array();
				if ($createHoldResponse && !isset($createHoldResponse->message)){
					$hold_result['result'] = true;
					$hold_result['message'] = 'Your hold was placed successfully.';
				}else{
					$hold_result['result'] = false;
					$hold_result['message'] = 'Your hold could not be placed. ' . (string)$createHoldResponse->message;
				}

				$hold_result['title']  = $title;
				$hold_result['bid'] = $recordId;
				global $analytics;
				if ($analytics){
					if ($hold_result['result'] == true){
						$analytics->addEvent('ILS Integration', 'Successful Hold', $title);
					}else{
						$analytics->addEvent('ILS Integration', 'Failed Hold', $hold_result['message'] . ' - ' . $title);
					}
				}
				//Clear the patron profile
				$this->clearPatronProfile();
				return $hold_result;

			}
		}
	}

	public function updateHold($requestId, $patronId, $type, $title){
		$xnum = "x" . $_REQUEST['x'];
		//Strip the . off the front of the bib and the last char from the bib
		if (isset($_REQUEST['cancelId'])){
			$cancelId = $_REQUEST['cancelId'];
		}else{
			$cancelId = substr($requestId, 1, -1);
		}
		$locationId = $_REQUEST['location'];
		$freezeValue = isset($_REQUEST['freeze']) ? 'on' : 'off';
		return $this->updateHoldDetailed($patronId, $type, $title, $xnum, $cancelId, $locationId, $freezeValue);
	}

	/**
	 * Update a hold that was previously placed in the system.
	 * Can cancel the hold or update pickup locations.
	 */
	public function updateHoldDetailed($patronId, $type, $title, $xNum, $cancelId, $locationId, $freezeValue='off'){
		global $configArray;

		global $user;
		$userId = $user->id;

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($user->cat_username, $user->cat_password);
			if (!$userValid){
				return array(
					'result' => false,
					'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again');
			}
		}

		if (!isset($xNum) ){
			if (isset($_REQUEST['waitingholdselected']) || isset($_REQUEST['availableholdselected'])){
				$waitingHolds = isset($_REQUEST['waitingholdselected']) ? $_REQUEST['waitingholdselected'] : array();
				$availableHolds = isset($_REQUEST['availableholdselected']) ? $_REQUEST['availableholdselected'] : array();
				$holdKeys = array_merge($waitingHolds, $availableHolds);
			}else{
				$holdKeys = array($cancelId);
			}
		}

		if ($type == 'cancel'){
			$allCancelsSucceed = true;
			foreach ($holdKeys as $holdKey){
				//create the hold using the web service
				$cancelHoldUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/cancelMyHold?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&holdKey=' . $holdKey;

				$cancelHoldResponse = $this->getWebServiceResponse($cancelHoldUrl);

				global $analytics;
				if ($cancelHoldResponse){
					//Clear the patron profile
					$this->clearPatronProfile();
					$analytics->addEvent('ILS Integration', 'Hold Cancelled', $title);
				}else{
					$allCancelsSucceed = false;
					$analytics->addEvent('ILS Integration', 'Hold Not Cancelled', $title);
				}
			}
			if ($allCancelsSucceed){
				return array(
					'title' => $title,
					'result' => true,
					'message' => 'Your hold(s) were cancelled successfully.');
			}else{
				return array(
					'title' => $title,
					'result' => false,
					'message' => 'Some holds could not be cancelled.  Please try again later or see your librarian.');
			}

		}else{
			if ($locationId){
				$allLocationChangesSucceed = true;

				foreach ($holdKeys as $holdKey){
					//create the hold using the web service
					$changePickupLocationUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/changePickupLocation?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&holdKey=' . $holdKey . '&newLocation=' . $locationId;

					$changePickupLocationResponse = $this->getWebServiceResponse($changePickupLocationUrl);

					global $analytics;
					if ($changePickupLocationResponse){
						//Clear the patron profile
						$this->clearPatronProfile();
						$analytics->addEvent('ILS Integration', 'Hold Suspended', $title);
					}else{
						$allLocationChangesSucceed = false;
						$analytics->addEvent('ILS Integration', 'Hold Not Suspended', $title);
					}
				}
				if ($allLocationChangesSucceed){
					return array(
						'title' => $title,
						'result' => true,
						'message' => 'Pickup location for your hold(s) was updated successfully.');
				}else{
					return array(
						'title' => $title,
						'result' => false,
						'message' => 'Pickup location for your hold(s) was could not be updated.  Please try again later or see your librarian.');
				}
			}else{
				//Freeze/Thaw the hold
				if ($freezeValue == 'on'){
					//Suspend the hold
					$reactivationDate = strtotime($_REQUEST['reactivationDate']);
					$reactivationDate = date('Y-m-d', $reactivationDate);
					$allLocationChangesSucceed = true;

					foreach ($holdKeys as $holdKey){
						//create the hold using the web service
						$changePickupLocationUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/suspendMyHold?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&holdKey=' . $holdKey . '&suspendEndDate=' . $reactivationDate;

						$changePickupLocationResponse = $this->getWebServiceResponse($changePickupLocationUrl);

						global $analytics;
						if ($changePickupLocationResponse){
							//Clear the patron profile
							$this->clearPatronProfile();
							$analytics->addEvent('ILS Integration', 'Hold Suspended', $title);
						}else{
							$allLocationChangesSucceed = false;
							$analytics->addEvent('ILS Integration', 'Hold Not Suspended', $title);
						}
					}
					if ($allLocationChangesSucceed){
						return array(
							'title' => $title,
							'result' => true,
							'message' => 'Your hold(s) were frozen successfully.');
					}else{
						return array(
							'title' => $title,
							'result' => false,
							'message' => 'Some holds could not be frozen.  Please try again later or see your librarian.');
					}
				}else{
					//Reactivate the hold
					$allUnsuspendsSucceed = true;

					foreach ($holdKeys as $holdKey){
						//create the hold using the web service
						$changePickupLocationUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/unsuspendMyHold?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&holdKey=' . $holdKey;

						$changePickupLocationResponse = $this->getWebServiceResponse($changePickupLocationUrl);

						global $analytics;
						if ($changePickupLocationResponse){
							//Clear the patron profile
							$this->clearPatronProfile();
							$analytics->addEvent('ILS Integration', 'Hold Suspended', $title);
						}else{
							$allUnsuspendsSucceed = false;
							$analytics->addEvent('ILS Integration', 'Hold Not Suspended', $title);
						}
					}
					if ($allUnsuspendsSucceed){
						return array(
							'title' => $title,
							'result' => true,
							'message' => 'Your hold(s) were thawed successfully.');
					}else{
						return array(
							'title' => $title,
							'result' => false,
							'message' => 'Some holds could not be thawed.  Please try again later or see your librarian.');
					}
				}
			}
		}
	}

	public function getMyTransactions( $page = 1, $recordsPerPage = -1, $sortOption = 'dueDate') {
		global $configArray;

		global $user;
		$userId = $user->id;

		$checkedOutTitles = array();

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($user->cat_username, $user->cat_password);
			if (!$userValid){
				echo("No session id found for user");
				return $checkedOutTitles;
			}
		}

		//Now that we have the session token, get checkouts information
		$lookupMyAccountInfoResponse = $this->getWebServiceResponse($configArray['Catalog']['webServiceUrl'] . '/standard/lookupMyAccountInfo?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&includeItemsOutInfo=true');
		if (isset($lookupMyAccountInfoResponse->ItemsOutInfo)){
			$sCount = 0;
			foreach ($lookupMyAccountInfoResponse->ItemsOutInfo as $itemOut){
				$sCount++;
				$bibId = (string)$itemOut->titleKey;
				$curTitle['checkoutSource'] = 'ILS';
				$curTitle['recordId'] = $bibId;
				$curTitle['shortId'] = $bibId;
				$curTitle['id'] = $bibId;
				$curTitle['title'] = (string)$itemOut->title;
				$curTitle['author'] = (string)$itemOut->author;

				$curTitle['duedate'] = (string)$itemOut->dueDate;
				$curTitle['checkoutdate'] = (string)$itemOut->ckoDate;
				$dueTime = strtotime($curTitle['duedate']);
				$daysUntilDue = ceil(($dueTime - time()) / (24 * 60 * 60));
				$overdue = $daysUntilDue < 0;
				$curTitle['overdue'] = $overdue;
				$curTitle['daysUntilDue'] = $daysUntilDue;
				$curTitle['renewCount'] = (string)$itemOut->renewals;
				$curTitle['canrenew'] = true; //TODO: Figure out if the user can renew the title or not
				$curTitle['renewIndicator'] = (string)$itemOut->itemBarcode;
				$curTitle['barcode'] = (string)$itemOut->itemBarcode;

				if ($curTitle['shortId'] && strlen($curTitle['shortId']) > 0){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($curTitle['id']);
					if ($recordDriver->isValid()){
						$curTitle['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
					}else{
						$curTitle['coverUrl'] = "";
					}
				}
				$sortTitle = isset($curTitle['title_sort']) ? $curTitle['title_sort'] : $curTitle['title'];
				$sortKey = $sortTitle;
				if ($sortOption == 'title'){
					$sortKey = $sortTitle;
				}elseif ($sortOption == 'author'){
					$sortKey = (isset($curTitle['author']) ? $curTitle['author'] : "Unknown") . '-' . $sortTitle;
				}elseif ($sortOption == 'dueDate'){
					if (isset($curTitle['duedate'])){
						if (preg_match('/.*?(\\d{1,2})[-\/](\\d{1,2})[-\/](\\d{2,4}).*/', $curTitle['duedate'], $matches)) {
							$sortKey = $matches[3] . '-' . $matches[1] . '-' . $matches[2] . '-' . $sortTitle;
						} else {
							$sortKey = $curTitle['duedate'] . '-' . $sortTitle;
						}
					}
				}elseif ($sortOption == 'format'){
					$sortKey = (isset($curTitle['format']) ? $curTitle['format'] : "Unknown") . '-' . $sortTitle;
				}elseif ($sortOption == 'renewed'){
					$sortKey = (isset($curTitle['renewCount']) ? $curTitle['renewCount'] : 0) . '-' . $sortTitle;
				}elseif ($sortOption == 'holdQueueLength'){
					$sortKey = (isset($curTitle['holdQueueLength']) ? $curTitle['holdQueueLength'] : 0) . '-' . $sortTitle;
				}
				$sortKey .= "_$sCount";
				$checkedOutTitles[$sortKey] = $curTitle;
			}
		}

		return array(
			'transactions' => $checkedOutTitles,
			'numTransactions' => count($checkedOutTitles)
		);
	}

	public function renewAll(){
		//Get all list of all transactions
		$currentTransactions = $this->getMyTransactions();
		$renewResult = array();
		$renewResult['Total'] = $currentTransactions['numTransactions'];
		$numRenewals = 0;
		foreach ($currentTransactions['transactions'] as $transaction){
			$curResult = $this->renewItem($transaction['renewIndicator'], null);
			if ($curResult['result']){
				$numRenewals++;
			}
		}
		$renewResult['Renewed'] = $numRenewals;
		$renewResult['Unrenewed'] = $renewResult['Total'] - $renewResult['Renewed'];
		if ($renewResult['Unrenewed'] > 0) {
			$renewResult['result'] = false;
		}else{
			$renewResult['result'] = true;
			$renewResult['message'] = "All items were renewed successfully.";
		}
		return $renewResult;
	}

	public function renewItem($itemId, $itemIndex){
		global $configArray;

		global $user;
		$userId = $user->id;

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($user->cat_username, $user->cat_password);
			if (!$userValid){
				return array(
					'result' => false,
					'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again');
			}
		}

		//create the hold using the web service
		$renewItemUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/renewMyCheckout?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&itemID=' . $itemId;

		$renewItemResponse = $this->getWebServiceResponse($renewItemUrl);

		global $analytics;
		if ($renewItemResponse && !isset($renewItemResponse->string)){
			$success = true;
			$message = 'Your item was successfully renewed.  The title is now due on ' . $renewItemResponse->dueDate;
			//Clear the patron profile
			$this->clearPatronProfile();
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Successful');
			}
		}else{
			$success = false;
			$message = $renewItemResponse->string;
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Failed', $renewItemResponse->string);
			}
		}
		return array(
			'itemId' => $itemId,
			'result'  => $success,
			'message' => $message);
	}

	private static $loadedStatus = array();
	/**
	 * Load status (holdings) for a record and filter them based on the logged in user information.
	 *
	 * Format of return array is:
	 * key = {section#}{location}-### where ### is the holding iteration
	 *
	 * value = array (
	 *  id = The id of the bib
	 *  number = The position of the holding within the original list of holdings
	 *  section = A description of the section
	 *  sectionId = a numeric id of the section for sorting
	 *  type = holding
	 *  status
	 *  statusfull
	 *  availability
	 *  holdable
	 *  nonHoldableReason
	 *  reserve
	 *  holdQueueLength
	 *  duedate
	 *  location
	 *  libraryDisplayName
	 *  locationCode
	 *  locationLink
	 *  callnumber
	 *  link = array
	 *  linkText
	 *  isDownload
	 * )
	 *
	 * Includes both physical titles as well as titles on order
	 *
	 * @param string            $id     the id of the record
	 * @return array A list of holdings for the record
	 */
	public function getHolding($id){
		if (array_key_exists($id, HorizonAPI::$loadedStatus)){
			return HorizonAPI::$loadedStatus[$id];
		}
		global $configArray;

		//Get location information so we can put things into sections
		global $locationSingleton; /** @var $locationSingleton Location */
		$physicalLocation = $locationSingleton->getPhysicalLocation();
		if ($physicalLocation != null){
			$physicalBranch = $physicalLocation->holdingBranchLabel;
		}else{
			$physicalBranch = '';
		}
		$homeBranch    = '';
		$homeBranchId  = 0;
		$nearbyBranch1 = '';
		$nearbyBranch1Id = 0;
		$nearbyBranch2 = '';
		$nearbyBranch2Id = 0;

		//Set location information based on the user login.  This will override information based
		if (isset($user) && $user != false){
			$homeBranchId = $user->homeLocationId;
			$nearbyBranch1Id = $user->myLocation1Id;
			$nearbyBranch2Id = $user->myLocation2Id;
		} else {
			//Check to see if the cookie for home location is set.
			if (isset($_COOKIE['home_location']) && is_numeric($_COOKIE['home_location'])) {
				$cookieLocation = new Location();
				$locationId = $_COOKIE['home_location'];
				$cookieLocation->whereAdd("locationId = '$locationId'");
				$cookieLocation->find();
				if ($cookieLocation->N == 1) {
					$cookieLocation->fetch();
					$homeBranchId = $cookieLocation->locationId;
					$nearbyBranch1Id = $cookieLocation->nearbyLocation1;
					$nearbyBranch2Id = $cookieLocation->nearbyLocation2;
				}
			}
		}
		//Load the holding label for the user's home location.
		$userLocation = new Location();
		$userLocation->whereAdd("locationId = '$homeBranchId'");
		$userLocation->find();
		if ($userLocation->N == 1) {
			$userLocation->fetch();
			$homeBranch = $userLocation->holdingBranchLabel;
		}
		//Load nearby branch 1
		$nearbyLocation1 = new Location();
		$nearbyLocation1->whereAdd("locationId = '$nearbyBranch1Id'");
		$nearbyLocation1->find();
		if ($nearbyLocation1->N == 1) {
			$nearbyLocation1->fetch();
			$nearbyBranch1 = $nearbyLocation1->holdingBranchLabel;
		}
		//Load nearby branch 2
		$nearbyLocation2 = new Location();
		$nearbyLocation2->whereAdd();
		$nearbyLocation2->whereAdd("locationId = '$nearbyBranch2Id'");
		$nearbyLocation2->find();
		if ($nearbyLocation2->N == 1) {
			$nearbyLocation2->fetch();
			$nearbyBranch2 = $nearbyLocation2->holdingBranchLabel;
		}

		//Get a list of items from Horizon
		$lookupTitleInfoUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/lookupTitleInfo?clientID=' . $configArray['Catalog']['clientId'] . '&titleKey=' . $id . '&includeItemInfo=true&includeHoldCount=true' ;

		$lookupTitleInfoResponse = $this->getWebServiceResponse($lookupTitleInfoUrl);
		$holdings = array();
		if ($lookupTitleInfoResponse->titleInfo){
			$i=0;
			foreach ($lookupTitleInfoResponse->titleInfo->itemInfo as $itemInfo){
				$holding = array(
					'id' => $id,
					'number' => $i++,
					'type' => 'holding',
					'status' => (string)$itemInfo->statusID,
					'statusfull' => (string)$itemInfo->statusDescription,
					'availability' => (boolean)$itemInfo->available,
					'holdable' => true,
					'reserve' => 'N',
					'holdQueueLength' => (int)$lookupTitleInfoResponse->titleInfo->holdCount,
					'dueDate' => (string)$itemInfo->dueDate,
					'locationCode' => (string)$itemInfo->locationID,
					'location' => (string)$itemInfo->locationDescription,
					'callnumber' => (string)$itemInfo->callNumber,
					'isDownload' => false,
					'barcode' => (string)$itemInfo->barcode,
				);

				$paddedNumber = str_pad($i, 3, '0', STR_PAD_LEFT);
				$sortString = $holding['location'] . '-'. $paddedNumber;
				//$sortString = $holding['location'] . $holding['callnumber']. $i;
				if (strlen($physicalBranch) > 0 && stripos($holding['location'], $physicalBranch) !== false){
					//If the user is in a branch, those holdings come first.
					$holding['section'] = 'In this library';
					$holding['sectionId'] = 1;
					$sorted_array['1' . $sortString] = $holding;
				} else if (strlen($homeBranch) > 0 && stripos($holding['location'], $homeBranch) !== false){
					//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
					$holding['section'] = 'Your library';
					$holding['sectionId'] = 2;
					$sorted_array['2' . $sortString] = $holding;
				} else if ((strlen($nearbyBranch1) > 0 && stripos($holding['location'], $nearbyBranch1) !== false)){
					//Next come nearby locations for the user
					$holding['section'] = 'Nearby Libraries';
					$holding['sectionId'] = 3;
					$sorted_array['3' . $sortString] = $holding;
				} else if ((strlen($nearbyBranch2) > 0 && stripos($holding['location'], $nearbyBranch2) !== false)){
					//Next come nearby locations for the user
					$holding['section'] = 'Nearby Libraries';
					$holding['sectionId'] = 4;
					$sorted_array['4' . $sortString] = $holding;
				//MDN 11/17 - taken out because all Horizon libraries are single institution (so far)
				/*} else if (strlen($libraryLocationLabels) > 0 && preg_match($libraryLocationLabels, $holding['location'])){
					//Next come any locations within the same system we are in.
					$holding['section'] = $library->displayName;
					$holding['sectionId'] = 5;
					$sorted_array['5' . $sortString] = $holding;
				*/} else {
					//Finally, all other holdings are shown sorted alphabetically.
					$holding['section'] = 'Other Locations';
					$holding['sectionId'] = 6;
					$sorted_array['6' . $sortString] = $holding;
				}

				$holdings[] = $holding;
			}

		}
		return $holdings;
	}


	/**
	 * Split a name into firstName, lastName, middleName.
	 *a
	 * Assumes the name is entered as LastName, FirstName MiddleName
	 * @param $fullName
	 * @return array
	 */
	public function splitFullName($fullName) {
		$fullName = str_replace(",", " ", $fullName);
		$fullName = str_replace(";", " ", $fullName);
		$fullName = str_replace(";", "'", $fullName);
		$fullName = preg_replace("/\\s{2,}/", " ", $fullName);
		$nameParts = explode(' ', $fullName);
		$lastName = strtolower($nameParts[0]);
		$middleName = isset($nameParts[2]) ? strtolower($nameParts[2]) : '';
		$firstName = isset($nameParts[1]) ? strtolower($nameParts[1]) : $middleName;
		return array($fullName, $lastName, $firstName);
	}

	public function getWebServiceResponse($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$xml = curl_exec($ch);
		curl_close($ch);

		if ($xml !== false){
			if (strpos($xml, '<') !== FALSE){
				return simplexml_load_string($xml);
			}else{
				return $xml;
			}
		}else{
			return false;
		}
	}

	public function clearPatronProfile() {
		/** @var Memcache $memCache */
		global $memCache;
		global $user;

		$patronProfile = $memCache->delete('patronProfile_' . $user->id);

	}
}