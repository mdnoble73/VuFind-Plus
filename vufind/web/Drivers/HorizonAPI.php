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
				list($userValid, $sessionToken) = $this->loginViaWebService($patron['cat_username'], $patron['cat_password']);
				if (!$userValid){
					echo("No session id found for user");
					return null;
				}
			}
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse($configArray['Catalog']['webServiceUrl'] . '/standard/lookupMyAccountInfo?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&includeAddressInfo=true&includeHoldInfo=true&includeBlockInfo=true&includeItemsOutInfo=true');

			if (isset($lookupMyAccountInfoResponse->AddressInfo)){
				$Address1 = (string)$lookupMyAccountInfoResponse->AddressInfo->line1;
				$cityState = (string)$lookupMyAccountInfoResponse->AddressInfo->cityState;
				list($City, $State) = explode(', ', $cityState);
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
				$curHold['position'] = (string)$hold->queuePosition;
				$curHold['recordId'] = $bibId;
				$curHold['shortId'] = $bibId;
				$curHold['title'] = (string)$hold->title;
				$curHold['author'] = (string)$hold->author;
				$curHold['location'] = (string)$hold->pickupLocDescription;
				//$curHold['locationId'] = $matches[1];
				$curHold['locationUpdateable'] = true;
				$curHold['currentPickupName'] = $curHold['location'];
				$curHold['status'] = ucfirst((string)$hold->status);
				$expireDate = (string)$hold->expireDate;
				$curHold['expire'] = $expireDate;
				$curHold['expireTime'] = strtotime($expireDate);
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
			return simplexml_load_string($xml);
		}else{
			return false;
		}
	}
}