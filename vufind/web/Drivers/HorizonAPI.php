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

require_once 'DriverInterface.php';
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

		//Authenticate the user via WebService
		//First call loginUser
		list($userValid, $sessionToken, $userID) = $this->loginViaWebService($username, $password);
		if ($userValid){
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse($configArray['Catalog']['webServiceUrl'] . '/standard/lookupMyAccountInfo?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&includeAddressInfo=true&includeHoldInfo=true&includeBlockInfo=true&includeItemsOutInfo=true');
			if ($lookupMyAccountInfoResponse){
				$fullName = (string)$lookupMyAccountInfoResponse->name;
				list($fullName, $lastName, $firstName) = $this->splitFullName($fullName);

				$email = '';
				if (isset($lookupMyAccountInfoResponse->AddressInfo)){
					if (isset($lookupMyAccountInfoResponse->AddressInfo->email)){
						$email = (string)$lookupMyAccountInfoResponse->AddressInfo->email;
					}
				}

				$userExistsInDB = false;
				$user = new User();
				$user->source = $this->accountProfile->name;
				$user->username = $userID;
				if ($user->find(true)){
					$userExistsInDB = true;
				}

				$user->firstname = isset($firstName) ? $firstName : '';
				$user->lastname = isset($lastName) ? $lastName : '';
				$user->fullname = isset($fullName) ? $fullName : '';
				$user->cat_username = $username;
				$user->cat_password = $password;
				$user->email = $email;

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

				//Get additional information about the patron's home branch for display.
				if (isset($lookupMyAccountInfoResponse->locationID)){
					$homeBranchCode = trim((string)$lookupMyAccountInfoResponse->locationID);
					//Translate home branch to plain text
					$location = new Location();
					$location->code = $homeBranchCode;
					$location->find(1);
					if ($location->N == 0){
						unset($location);
					}
				}

				if ($user->homeLocationId == 0 && isset($location)) {
					$user->homeLocationId = $location->locationId;
					if ($location->nearbyLocation1 > 0) {
						$user->myLocation1Id = $location->nearbyLocation1;
					} else {
						$user->myLocation1Id = $location->locationId;
					}
					if ($location->nearbyLocation2 > 0) {
						$user->myLocation2Id = $location->nearbyLocation2;
					} else {
						$user->myLocation2Id = $location->locationId;
					}
				}else if (isset($location) && $location->locationId != $user->homeLocationId){
					$user->homeLocationId = $location->locationId;
				}

				//Get display name for preferred location 1
				$myLocation1 = new Location();
				$myLocation1->whereAdd("locationId = '$user->myLocation1Id'");
				$myLocation1->find(1);

				//Get display name for preferred location 1
				$myLocation2 = new Location();
				$myLocation2->whereAdd("locationId = '$user->myLocation2Id'");
				$myLocation2->find(1);

				//TODO: See if we can get information about card expiration date
				$expireClose = 0;

				$finesVal = 0;
				if (isset($lookupMyAccountInfoResponse->BlockInfo)){
					foreach ($lookupMyAccountInfoResponse->BlockInfo as $block){
						// $block is a simplexml object with attribute info about currency, type casting as below seems to work for adding up. plb 3-27-2015
						$fineAmount = (float) $block->balance;
						$finesVal += $fineAmount;
					}
				}

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

				$user->address1 = $Address1;
				$user->address2 = $City . ', ' . $State;
				$user->city = $City;
				$user->state = $State;
				$user->zip = $Zip;
				$user->phone = isset($lookupMyAccountInfoResponse->phone) ? (string)$lookupMyAccountInfoResponse->phone : '';
				$user->fines = sprintf('$%01.2f', $finesVal);
				$user->finesVal = $finesVal;
				$user->expires = ''; //TODO: Determine if we can get this
				$user->expireClose = $expireClose;
				$user->homeLocationCode = isset($homeBranchCode) ? trim($homeBranchCode) : '';
				$user->homeLocationId = isset($location) ? $location->locationId : 0;
				$user->homeLocation = isset($location) ? $location->displayName : '';
				$user->myLocation1Id = ($user) ? $user->myLocation1Id : -1;
				$user->myLocation1 = isset($myLocation1) ? $myLocation1->displayName : '';
				$user->myLocation2Id = ($user) ? $user->myLocation2Id : -1;
				$user->myLocation2 = isset($myLocation2) ? $myLocation2->displayName : '';
				$user->numCheckedOutIls = isset($lookupMyAccountInfoResponse->ItemsOutInfo) ? count($lookupMyAccountInfoResponse->ItemsOutInfo) : 0;
				$user->numHoldsIls = $numHoldsAvailable + $numHoldsRequested;
				$user->numHoldsAvailableIls = $numHoldsAvailable;
				$user->numHoldsRequestedIls = $numHoldsRequested;
				$user->patronType = 0;
				$user->notices = '-';
				$user->noticePreferenceLabel = 'e-mail';
				$user->web_note = '';

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
				return null;
			}
		}
	}

	private function loginViaWebService($username, $password) {
		global $configArray;
		$loginUserUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/loginUser?clientID=' . $configArray['Catalog']['clientId'] . '&login=' . urlencode($username) . '&password=' . urlencode($password);
		$loginUserResponse = $this->getWebServiceResponse($loginUserUrl);
		if (!$loginUserResponse){
			return array(false, false, false);
		}else if (isset($loginUserResponse->Fault)){
			return array(false, false, false);
		}else{
			//We got at valid user, next call lookupMyAccountInfo
			if (isset($loginUserResponse->sessionToken)){
				$userID = (string)$loginUserResponse->userID;
				$sessionToken = (string)$loginUserResponse->sessionToken;
				HorizonAPI::$sessionIdsForUsers[$userID] = $sessionToken;
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
		global $configArray;

		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$patron->id])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$patron->id];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			if (!$userValid){
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

				$curHold['cancelable'] = strcasecmp($curHold['status'], 'Suspended') != 0;
				$curHold['frozen'] = strcasecmp($curHold['status'], 'Suspended') == 0;
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
					$curHold['coverUrl'] = $recordDriver->getBookcoverUrl();
					$curHold['link'] = $recordDriver->getRecordUrl();

					//Load rating information
					$curHold['ratingData'] = $recordDriver->getRatingData();
				}
				$curHold['user'] = $patron->getNameAndLibraryLabel();

				if (!isset($curHold['status']) || strcasecmp($curHold['status'], "filled") != 0){
					$holds['unavailable'][] = $curHold;
				}else{
					$holds['available'][] = $curHold;
				}
			}
		}
		return $holds;
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds as well as placing recalls.
	 *
	 * @param   User    $patron       The User to place a hold for
	 * @param   string  $recordId     The id of the bib record
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed                 True if successful, false if unsuccessful
	 *                                If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	public function placeHold($patron, $recordId, $pickupBranch) {
		$result = $this->placeItemHold($patron, $recordId, null, $pickupBranch);
		return $result;
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
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
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
				return array(
					'title' => $title,
					'bib' => $recordId,
					'success' => true,
					'message' => 'The circulation system is currently offline.  This hold will be entered for you automatically when the circulation system is online.');
			}else{
				return array(
					'title' => $title,
					'bib' => $recordId,
					'success' => false,
					'message' => 'The circulation system is currently offline and we could not place this hold.  Please try again later.');
			}

		}else{
			if ($type == 'cancel' || $type == 'recall' || $type == 'update') {
				$result = $this->updateHold($patron, $recordId, $type, $title);
				$result['title'] = $title;
				$result['bid'] = $recordId;
				return $result;

			} else {
				if (isset($_REQUEST['campus'])){
					$campus=trim($_REQUEST['campus']);
				}else{
					$campus = $patron->homeLocationId;
				}
				//create the hold using the web service
				$createHoldUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/createMyHold?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&pickupLocation=' . $campus . '&titleKey=' . $recordId ;
				if ($itemId){
					$createHoldUrl .= '&itemKey=' . $itemId;
				}

				$createHoldResponse = $this->getWebServiceResponse($createHoldUrl);

				$hold_result = array();
				if ($createHoldResponse == true){
					$hold_result['success'] = true;
					$hold_result['message'] = 'Your hold was placed successfully.';
				}else{
					$hold_result['success'] = false;
					$hold_result['message'] = 'Your hold could not be placed. ';
					if (isset($createHoldResponse->message)){
						$hold_result['message'] .= (string)$createHoldResponse->message;
					}else if (isset($createHoldResponse->string)){
						$hold_result['message'] .= (string)$createHoldResponse->string;
					}

				}

				$hold_result['title']  = $title;
				$hold_result['bid'] = $recordId;
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

	function cancelHold($patron, $recordId, $cancelId) {
		return $this->updateHoldDetailed($patron, 'cancel', '', null, $cancelId, '', '');
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate) {
		return $this->updateHoldDetailed($patron, 'update', '', null, $itemToFreezeId, '', 'on');
	}

	function thawHold($patron, $recordId, $itemToThawId) {
		return $this->updateHoldDetailed($patron, 'update', '', null, $itemToThawId, '', 'off');
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation) {
		return $this->updateHoldDetailed($patron, 'update', '', null, $itemToUpdateId, $newPickupLocation, 'off');
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
	public function updateHoldDetailed($patron, $type, $titles, $xNum, $cancelId, $locationId, $freezeValue='off'){
		global $configArray;

		$patronId = $patron->id;

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$patronId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$patronId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			if (!$userValid){
				return array(
					'success' => false,
					'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again');
			}
		}

		if (!isset($xNum)){ //AJAX function passes IDs through $cancelID below shouldn't be needed anymore. plb 2-4-2015
			if (isset($_REQUEST['waitingholdselected']) || isset($_REQUEST['availableholdselected'])){
				$waitingHolds = isset($_REQUEST['waitingholdselected']) ? $_REQUEST['waitingholdselected'] : array();
				$availableHolds = isset($_REQUEST['availableholdselected']) ? $_REQUEST['availableholdselected'] : array();
				$holdKeys = array_merge($waitingHolds, $availableHolds);
			}else{
				$holdKeys = is_array($cancelId) ? $cancelId : array($cancelId);
			}
		}

		$loadTitles = empty($titles);
		if ($loadTitles) {
			$holds = $this->getMyHolds($patron);
			$combined_holds = array_merge($holds['unavailable'], $holds['available']);
		}
//		$logger->log("Load titles = $loadTitles", PEAR_LOG_DEBUG); // move out of foreach loop



		if ($type == 'cancel'){
			$allCancelsSucceed = true;
			$failure_messages = array();

			foreach ($holdKeys as $holdKey){
				$title = 'an item';  // default in case title name isn't found.

				if ($loadTitles) { // get title for this hold
					foreach ($combined_holds as $hold){
						if ($hold['cancelId'] == $holdKey) {
							$title = $hold['title'];
							break;
						}
					}
				} // else {} // Get from parameter $titles
				$titles[] = $title; // build array of all titles


				//create the hold using the web service
				$cancelHoldUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/cancelMyHold?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&holdKey=' . $holdKey;

				$cancelHoldResponse = $this->getWebServiceResponse($cancelHoldUrl);

				global $analytics;
				if ($cancelHoldResponse){
					//Clear the patron profile
					$analytics->addEvent('ILS Integration', 'Hold Cancelled', $title);
				}else{
					$allCancelsSucceed = false;
					$failure_messages[$holdKey] = "The hold for $title could not be cancelled.  Please try again later or see your librarian.";
					$analytics->addEvent('ILS Integration', 'Hold Not Cancelled', $title);
				}
			}
			if ($allCancelsSucceed){
				$plural = count($holdKeys) > 1;

				return array(
					'title' => $titles,
					'success' => true,
					'message' => 'Your hold'.($plural ? 's were' : ' was' ).' cancelled successfully.');
			}else{
				return array(
					'title' => $titles,
					'success' => false,
					'message' => $failure_messages
				);
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
						$analytics->addEvent('ILS Integration', 'Hold Suspended', $titles);
					}else{
						$allLocationChangesSucceed = false;
						$analytics->addEvent('ILS Integration', 'Hold Not Suspended', $titles);
					}
				}
				if ($allLocationChangesSucceed){
					return array(
						'title' => $titles,
						'success' => true,
						'message' => 'Pickup location for your hold(s) was updated successfully.');
				}else{
					return array(
						'title' => $titles,
						'success' => false,
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
							$analytics->addEvent('ILS Integration', 'Hold Suspended', $titles);
						}else{
							$allLocationChangesSucceed = false;
							$analytics->addEvent('ILS Integration', 'Hold Not Suspended', $titles);
						}
					}

					$frozen = translate('frozen');
					if ($allLocationChangesSucceed){
						return array(
							'title' => $titles,
							'success' => true,
							'message' => "Your hold(s) were $frozen successfully.");
					}else{
						return array(
							'title' => $titles,
							'success' => false,
							'message' => "Some holds could not be $frozen.  Please try again later or see your librarian.");
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
							$analytics->addEvent('ILS Integration', 'Hold Suspended', $titles);
						}else{
							$allUnsuspendsSucceed = false;
							$analytics->addEvent('ILS Integration', 'Hold Not Suspended', $titles);
						}
					}

					$thawed = translate('thawed');
					if ($allUnsuspendsSucceed){
						return array(
							'title' => $titles,
							'success' => true,
							'message' => "Your hold(s) were $thawed successfully.");
					}else{
						return array(
							'title' => $titles,
							'success' => false,
							'message' => "Some holds could not be $thawed.  Please try again later or see your librarian.");
					}
				}
			}
		}
	}

	public function getMyCheckouts($patron, $page = 1, $recordsPerPage = -1, $sortOption = 'dueDate') {
		global $configArray;

		$userId = $patron->id;

		$checkedOutTitles = array();

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			if (!$userValid){
//				echo("No session id found for user");
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

				$curTitle['dueDate'] = strtotime((string)$itemOut->dueDate);
				$curTitle['checkoutdate'] = (string)$itemOut->ckoDate;
				$curTitle['renewCount'] = (string)$itemOut->renewals;
				$curTitle['canrenew'] = true; //TODO: Figure out if the user can renew the title or not
				$curTitle['renewIndicator'] = (string)$itemOut->itemBarcode;
				$curTitle['barcode'] = (string)$itemOut->itemBarcode;
				$curTitle['holdQueueLength'] = $this->getNumHolds($bibId);

				$curTitle['format'] = 'Unknown';
				if ($curTitle['id'] && strlen($curTitle['id']) > 0){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($curTitle['id']);
					if ($recordDriver->isValid()){
						$curTitle['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
						$curTitle['groupedWorkId'] = $recordDriver->getGroupedWorkId();
						$formats = $recordDriver->getFormats();
						$curTitle['format'] = reset($formats);
					}else{
						$curTitle['coverUrl'] = "";
					}
					$curTitle['link'] = $recordDriver->getLinkUrl();
				}
				$sortTitle = isset($curTitle['title_sort']) ? $curTitle['title_sort'] : $curTitle['title'];
				$sortKey = $sortTitle;
				if ($sortOption == 'title'){
					$sortKey = $sortTitle;
				}elseif ($sortOption == 'author'){
					$sortKey = (isset($curTitle['author']) ? $curTitle['author'] : "Unknown") . '-' . $sortTitle;
				}elseif ($sortOption == 'dueDate'){
					if (isset($curTitle['dueDate'])){
						if (preg_match('/.*?(\\d{1,2})[-\/](\\d{1,2})[-\/](\\d{2,4}).*/', $curTitle['dueDate'], $matches)) {
							$sortKey = $matches[3] . '-' . $matches[1] . '-' . $matches[2] . '-' . $sortTitle;
						} else {
							$sortKey = $curTitle['dueDate'] . '-' . $sortTitle;
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

		return $checkedOutTitles;
	}

	public function hasFastRenewAll(){
		return false;
	}

	public function renewAll($patron){
		return array(
			'success' => false,
			'message' => 'Renew All not supported directly, call through Catalog Connection',
		);
	}

	// TODO: Test with linked accounts (9-3-2015)
	public function renewItem($patron, $recordId, $itemId, $itemIndex){
		global $configArray;

		$userId = $patron->id;

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			if (!$userValid){
				return array(
					'success' => false,
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
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Successful');
			}
		}else{
			//TODO: check that title is included in the message
			$success = false;
			$message = $renewItemResponse->string;
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Failed', $renewItemResponse->string);
			}
		}
		return array(
			'itemId' => $itemId,
			'success'  => $success,
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
	 *  dueDate
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
		global $library;

		$fullId = $_REQUEST['id'];
		$recordInfo = explode(':', $fullId);
		$recordType = $recordInfo[0];
		$ilsId = $recordInfo[1];

		//Get location information so we can put things into sections
		global $locationSingleton; /** @var $locationSingleton Location */
		$physicalLocation = $locationSingleton->getPhysicalLocation();
		if ($physicalLocation != null){
			$physicalBranch = $physicalLocation->code;
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
		//QUESTION global $user needed here? TODO: does this if block get used?
		global $user;
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
			$homeBranch = $userLocation->code;
		}
		//Load nearby branch 1
		$nearbyLocation1 = new Location();
		$nearbyLocation1->whereAdd("locationId = '$nearbyBranch1Id'");
		$nearbyLocation1->find();
		if ($nearbyLocation1->N == 1) {
			$nearbyLocation1->fetch();
			$nearbyBranch1 = $nearbyLocation1->code;
		}
		//Load nearby branch 2
		$nearbyLocation2 = new Location();
		$nearbyLocation2->whereAdd();
		$nearbyLocation2->whereAdd("locationId = '$nearbyBranch2Id'");
		$nearbyLocation2->find();
		if ($nearbyLocation2->N == 1) {
			$nearbyLocation2->fetch();
			$nearbyBranch2 = $nearbyLocation2->code;
		}

		//Get a list of items from Horizon
		$lookupTitleInfoUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/lookupTitleInfo?clientID=' . $configArray['Catalog']['clientId'] . '&titleKey=' . $ilsId . '&includeItemInfo=true&includeHoldCount=true' ;

		$lookupTitleInfoResponse = $this->getWebServiceResponse($lookupTitleInfoUrl);
		$holdings = array();
		if ($lookupTitleInfoResponse->titleInfo){
			$i=0;
			foreach ($lookupTitleInfoResponse->titleInfo->itemInfo as $itemInfo){
				if (!isset($itemInfo->locationID)){
					//Suppress anything without a location code
					continue;
				}
				$i++;
				$holding = array(
					'id' => $id,
					'number' => $i++,
					'type' => 'holding',
					'status' => isset($itemInfo->statusID) ? (string)$itemInfo->statusID : 'Unknown',
					'statusfull' => isset($itemInfo->statusDescription) ? (string)$itemInfo->statusDescription : 'Unknown',
					'availability' => isset($itemInfo->available) ? ((string)$itemInfo->available == "true") : false,
					'holdable' => true,
					'reserve' => 'N',
					'holdQueueLength' => (int)$lookupTitleInfoResponse->titleInfo->holdCount,
					'dueDate' => isset($itemInfo->dueDate) ? (string)$itemInfo->dueDate : 'Unknown',
					'locationCode' => (string)$itemInfo->locationID,
					'location' => (string)$itemInfo->locationDescription,
					'callnumber' => (string)$itemInfo->callNumber,
					'isDownload' => false,
					'barcode' => (string)$itemInfo->barcode,
					'isLocalItem' => false,
					'isLibraryItem' => true,
					'locationLabel' => (string)$itemInfo->locationDescription,
					'shelfLocation' => (string)$itemInfo->locationDescription,
				);

				$holding['groupedStatus'] = mapValue('item_grouped_status', $holding['status']);

				$paddedNumber = str_pad($i, 3, '0', STR_PAD_LEFT);
				$sortString = $holding['location'] . '-'. $paddedNumber;
				//$sortString = $holding['location'] . $holding['callnumber']. $i;
				if (strlen($physicalBranch) > 0 && strcasecmp($holding['locationCode'], $physicalBranch) === 0){
					//If the user is in a branch, those holdings come first.
					$holding['section'] = 'In this library';
					$holding['sectionId'] = 1;
					$holding['isLocalItem'] = true;
					$sorted_array['1' . $sortString] = $holding;
				} else if (strlen($homeBranch) > 0 && strcasecmp($holding['locationCode'], $homeBranch) === 0){
					//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
					$holding['section'] = 'Your library';
					$holding['sectionId'] = 2;
					$holding['isLocalItem'] = true;
					$sorted_array['2' . $sortString] = $holding;
				} else if ((strlen($nearbyBranch1) > 0 && strcasecmp($holding['locationCode'], $nearbyBranch1) === 0)){
					//Next come nearby locations for the user
					$holding['section'] = 'Nearby Libraries';
					$holding['sectionId'] = 3;
					$sorted_array['3' . $sortString] = $holding;
				} else if ((strlen($nearbyBranch2) > 0 && strcasecmp($holding['locationCode'], $nearbyBranch2) === 0)){
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
					$holding['section'] = $library->displayName;
					$holding['sectionId'] = 5;
					$sorted_array['5' . $sortString] = $holding;
				}

				$holdings[] = $holding;
			}
			ksort($sorted_array);
		}
		return $sorted_array;
	}

	public function getHoldings($idList, $record = null, $mysip = null, $forSummary = false) {
		$holdings = array();
		foreach ($idList as $id) {
			$holdings[] = $this->getHolding($id, $record, $mysip, $forSummary);
		}
		return $holdings;
	}

	public function getStatus($id, $record = null, $mysip = null, $forSummary = false)
	{
		return $this->getHolding($id, $record, $mysip, $forSummary);
	}

	public function getStatuses($idList, $record = null, $mysip = null, $forSummary = false)
	{
		return $this->getHoldings($idList, $record, $mysip, $forSummary);
	}

	public function getNumHolds($id) {
		global $configArray;
		if (!$configArray['Catalog']['offline']){
			$lookupTitleInfoUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/lookupTitleInfo?clientID=' . $configArray['Catalog']['clientId'] . '&titleKey=' . $id . '&includeItemInfo=false&includeHoldCount=true' ;
			$lookupTitleInfoResponse = $this->getWebServiceResponse($lookupTitleInfoUrl);
			if ($lookupTitleInfoResponse->titleInfo){
				return (int)$lookupTitleInfoResponse->titleInfo->holdCount;
			}
		}

		return 0;
	}

	function updatePin($user, $oldPin, $newPin, $confirmNewPin){
		global $configArray;
		$userId = $user->id;

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($user->cat_username, $user->cat_password);
			if (!$userValid){
				return 'Sorry, it does not look like you are logged in currently.  Please login and try again';
			}
		}

		//create the hold using the web service
		$updatePinUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/changeMyPin?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&currentPin=' . $oldPin . '&newPin=' . $newPin;
		$updatePinResponse = $this->getWebServiceResponse($updatePinUrl);

		if ($updatePinResponse){
			$user->cat_password = $newPin;
			$user->update();
//			UserAccount::updateSession($user);  //TODO only if $user is the primary user
			return "Your pin number was updated successfully.";
		}else{
			return "Sorry, we could not update your pin number. Please try again later.";
		}
	}

	public function emailPin($barcode){
		global $configArray;
		$barcode = $_REQUEST['barcode'];

		//email the pin to the user
		$updatePinUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/emailMyPin?clientID=' . $configArray['Catalog']['clientId'] . '&login=' . $barcode . '&profile=' . $this->hipProfile;

		$updatePinResponse = $this->getWebServiceResponse($updatePinUrl);

		if ($updatePinResponse == true && !isset($updatePinResponse['code'])){
			return array(
				'success' => true,
			);
		}else{
			$result = array(
				'error' => "Sorry, we could not e-mail your pin to you.  Please visit the library to reset your pin."
			);
			if (isset($updatePinResponse['code'])){
				$result['error'] .= '  ' . $updatePinResponse['code'];
			}
			return $result;
		}
	}

	public function getSelfRegistrationFields() {
		global $configArray;
		$lookupSelfRegistrationFieldsUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/lookupSelfRegistrationFields?clientID=' . $configArray['Catalog']['clientId'];

		$lookupSelfRegistrationFieldsResponse = $this->getWebServiceResponse($lookupSelfRegistrationFieldsUrl);
		$fields = array();
		if ($lookupSelfRegistrationFieldsResponse){
			foreach($lookupSelfRegistrationFieldsResponse->registrationField as $registrationField){
				$newField = array(
					'property' => (string)$registrationField->column,
					'label' => (string)$registrationField->label,
					'maxLength' => (int)$registrationField->length,
					'type' => 'text',
					'required' => (string)$registrationField->required == 'true',
				);
				if ((string)$registrationField->masked == 'true'){
					$newField['type'] = 'password';
				}
				if (isset($registrationField->values)){
					$newField['type'] = 'enum';
					$values = array();
					foreach($registrationField->values->value as $value){
						$values[(string)$value->code] = (string)$value->description;
					}
					$newField['values'] = $values;
				}
				$fields[] = $newField;
			}
		}
		return $fields;
	}

	/**
	 * Returns a summary of the holdings information for a single id. Used to display
	 * within the search results and at the top of a full record display to ensure
	 * the holding information makes sense to all users.
	 *
	 * @param string $id the id of the bid to load holdings for
	 * @return array an associative array with a summary of the holdings.
	 */
	public function getStatusSummary($id, $record = null, $mysip = null){
		$summary = parent::getStatusSummary($id, $record, $mysip);
		$summary['holdQueueLength'] = $this->getNumHolds($id);
		return $summary;
	}

	//This function does not currently work due to posting of the self registration data.  Using HIP for now in individual drivers.
	/*function selfRegister(){
		global $configArray;
		$fields = $this->getSelfRegistrationFields();

		$createSelfRegisteredPatronUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/createSelfRegisteredPatron?clientID=' . $configArray['Catalog']['clientId'] . '&secret=' . $configArray['Catalog']['clientSecret'];
		foreach ($fields as $field){
			if (isset($_REQUEST[$field['property']])){
				$createSelfRegisteredPatronUrl .= '&' . $field['property'] . '=' . urlencode($_REQUEST[$field['property']]);
			}
		}
		$createSelfRegisteredPatronResponse = $this->getWebServiceResponse($createSelfRegisteredPatronUrl);
		if ($createSelfRegisteredPatronResponse){
			return array('success' => true, 'barcode' => (string)$createSelfRegisteredPatronResponse);
		}else{
			return array('success' => false, 'barcode' => '');
		}
	}*/

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
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Charset: utf-8'));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$xml = curl_exec($ch);
		curl_close($ch);

		if ($xml !== false && $xml !== 'false'){
			if (strpos($xml, '<') !== FALSE){
				//Strip any non-UTF-8 characters
				$xml = preg_replace('/[^(\x20-\x7F)]*/','', $xml);

				libxml_use_internal_errors(true);
				$parsedXml = simplexml_load_string($xml);
				if ($parsedXml === false){
					//Failed to load xml
					global $logger;
					$logger->log("Error parsing xml", PEAR_LOG_ERR);
					$logger->log($xml, PEAR_LOG_DEBUG);
					foreach(libxml_get_errors() as $error) {
						$logger->log("\t {$error->message}", PEAR_LOG_ERR);
					}
					return false;
				}else{
					return $parsedXml;
				}
			}else{
				return $xml;
			}
		}else{
			return false;
		}
	}

	public function hasNativeReadingHistory() {
		return false;
	}

}