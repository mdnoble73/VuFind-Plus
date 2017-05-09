<?php

/**
 * Implements
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 6/10/2016
 * Time: 1:53 PM
 */
//require_once ROOT_DIR . '/Drivers/SIP2Driver.php';
require_once ROOT_DIR . '/sys/SIP2.php';
class CarlX extends SIP2Driver{
	public $accountProfile;
	public $patronWsdl;
	public $catalogWsdl;

	private $soapClient;

	public function __construct($accountProfile) {
		$this->accountProfile = $accountProfile;
		global $configArray;
		$this->patronWsdl  = $configArray['Catalog']['patronApiWsdl'];
		$this->catalogWsdl = $configArray['Catalog']['catalogApiWsdl'];
	}

	public function patronLogin($username, $password, $validatedViaSSO){
		global $timer;

		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		$request = new stdClass();
		$request->SearchType = 'Patron ID';
		$request->SearchID   = $username;
		$request->Modifiers  = '';

		$result = $this->doSoapRequest('getPatronInformation', $request);

		$patronValid = false;
		if ($result){
			if (isset($result->Patron)){
				//Check to see if the pin matches
				if ($result->Patron->PatronPIN == $password || $validatedViaSSO){
					$fullName = $result->Patron->FullName;
					$firstName = $result->Patron->FirstName;
					$lastName = $result->Patron->LastName;

					$userExistsInDB = false;
					$user = new User();
					$user->source   = $this->accountProfile->name;
					$user->username = $result->Patron->GeneralUserID;
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
					$user->fullname     = isset($fullName) ? $fullName : '';
					$user->cat_username = $username;
					$user->cat_password = $result->Patron->PatronPIN;
					$user->email        = $result->Patron->Email;

					if ($userExistsInDB && $user->trackReadingHistory != $result->Patron->LoanHistoryOptInFlag) {
						$user->trackReadingHistory = $result->Patron->LoanHistoryOptInFlag;
					}

					$homeBranchCode = strtolower($result->Patron->DefaultBranch);
					$location = new Location();
					$location->code = $homeBranchCode;
					if (!$location->find(1)){
						unset($location);
						$user->homeLocationId = 0;
						// Logging for Diagnosing PK-1846
						global $logger;
						$logger->log('CarlX Driver: No Location found, user\'s homeLocationId being set to 0. User : '.$user->id, PEAR_LOG_WARNING);
					}

					if ((empty($user->homeLocationId) || $user->homeLocationId == -1) || (isset($location) && $user->homeLocationId != $location->locationId)) { // When homeLocation isn't set or has changed
						if ((empty($user->homeLocationId) || $user->homeLocationId == -1) && !isset($location)) {
							// homeBranch Code not found in location table and the user doesn't have an assigned homelocation,
							// try to find the main branch to assign to user
							// or the first location for the library
							global $library;

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

					if (isset($result->Patron->Addresses)){
						//Find the primary address
						$primaryAddress = null;
						foreach ($result->Patron->Addresses->Address as $address){
							if ($address->Type == 'Primary'){
								$primaryAddress = $address;
								break;
							}
						}
						if ($primaryAddress != null){
							$user->address1 = $primaryAddress->Street;
							$user->address2 = $primaryAddress->City . ', ' . $primaryAddress->State;
							$user->city     = $primaryAddress->City;
							$user->state    = $primaryAddress->State;
							$user->zip      = $primaryAddress->PostalCode;
						}
					}

					if ($result->Patron->EmailNotices == 'send email') {
						$user->notices = 'z';
						$user->noticePreferenceLabel = 'E-mail';
					} elseif ($result->Patron->EmailNotices == 'do not send email' || $result->Patron->EmailNotices == 'opted out') {
						$user->notices = '-';
						$user->noticePreferenceLabel = null;
					}

					$user->patronType  = $result->Patron->PatronType; // Example: "ADULT"
					$user->web_note    = '';
					$user->phone       = $result->Patron->Phone1;
					$user->expires     = $this->extractDateFromCarlXDateField($result->Patron->ExpirationDate);
					$user->expired     = 0; // default setting
					$user->expireClose = 0;

					$timeExpire   = strtotime($user->expires);
					$timeNow      = time();
					$timeToExpire = $timeExpire - $timeNow;
					if ($timeToExpire <= 30 * 24 * 60 * 60) {
						if ($timeToExpire <= 0) {
							$user->expired = 1;
						}
						$user->expireClose = 1;
					}

					//Load summary information for number of holds, checkouts, etc
					$patronSummaryRequest = new stdClass();
					$patronSummaryRequest->SearchType = 'Patron ID';
					$patronSummaryRequest->SearchID  = $username;
					$patronSummaryRequest->Modifiers = '';

					$patronSummaryResponse = $this->doSoapRequest('getPatronSummaryOverview', $patronSummaryRequest, $this->patronWsdl);

					if (!empty($patronSummaryRequest) && is_object($patronSummaryRequest)) {
						$user->numCheckedOutIls     = $patronSummaryResponse->ChargedItemsCount;
						$user->numHoldsAvailableIls = $patronSummaryResponse->HoldItemsCount;
						$user->numHoldsRequestedIls = $patronSummaryResponse->UnavailableHoldsCount;
						$user->numHoldsIls          = $user->numHoldsAvailableIls + $user->numHoldsRequestedIls;

						$outstandingFines = $patronSummaryResponse->FineTotal + $patronSummaryResponse->LostItemFeeTotal;
						$user->fines      = sprintf('$%0.2f', $outstandingFines);
						$user->finesVal   = floatval($outstandingFines);
					}

					if ($userExistsInDB){
						$user->update();
					}else{
						$user->created = date('Y-m-d');
						$user->insert();
					}

					$timer->logTime("patron logged in successfully");
					return $user;
				}
			}
		}

		if (!$patronValid){
			$timer->logTime("patron login failed");
			return null;
		}
	}

	public function hasNativeReadingHistory() {
		return true;
	}

	public function getNumHolds($id) {
		// TODO: Implement getNumHolds() method.
	}

	/**
	 * @return boolean true if the driver can renew all titles in a single pass
	 */
	public function hasFastRenewAll() {
		// TODO: Implement hasFastRenewAll() method.
		// TODO: There is a Renew All through SIP, this should become true
		return false;
	}

	/**
	 * Renew all titles currently checked out to the user
	 *
	 * @param $patron  User
	 * @return mixed
	 */
	public function renewAll($patron) {
		// TODO: Implement renewAll() method.
		return false;
	}

	private $genericResponseSOAPCallOptions = array(
		'features' => SOAP_WAIT_ONE_WAY_CALLS, // This setting overcomes the SOAP client's expectation that there is no response from our update request.
		'trace' => 1,                          // enable use of __getLastResponse, so that we can determine the response.
	);

	private function doSoapRequest($requestName, $request, $WSDL = '', $soapRequestOptions = array()) {
		if (empty($WSDL)) { // Let the patron WSDL be the assumed default WSDL when not specified.
			if (!empty($this->patronWsdl)) {
				$WSDL = $this->patronWsdl;
			} else {
				global $logger;
				$logger->log('No Default Patron WSDL defined for SOAP calls in CarlX Driver', PEAR_LOG_ERR);
				return false;
			}
		}

		// There are exceptions in the Soap Client that need to be caught for smooth functioning
		try {
			$this->soapClient = new SoapClient($WSDL, $soapRequestOptions);
			$result = $this->soapClient->$requestName($request);
		} catch (SoapFault $e) {
			global $logger;
			$logger->log("Soap Client error in CarlX: while calling $requestName ".$e->getMessage(), PEAR_LOG_ERR);
			return false;
		}
	return $result;
	}

	/**
	 * Renew a single title currently checked out to the user
	 *
	 * @param $patron     User
	 * @param $recordId   string
	 * @param $itemId     string
	 * @param $itemIndex  string
	 * @return mixed
	 */
	public function renewItem($patron, $recordId, $itemId=null, $itemIndex=null) {
		// For CarlX RecordId is the same as the itemId

		// Renew Via SIP
		return $result = $this->renewItemViaSIP($patron, $recordId);

		// For an AlternateSIP Port
//		$useAlternateSIP = false;
//		$result = $this->renewItemViaSIP($patron, $itemId, $useAlternateSIP);
	}

	private $holdStatusCodes = array(
	                                  'H'  => 'Hold Shelf',
	                                  ''   => 'In Queue',
	                                  'IH' => 'In Transit',
	                                  // '?' => 'Suspended',
	                                  // '?' => 'filled',
	);
	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $user The user to load transactions for
	 *
	 * @return array        Array of the patron's holds
	 * @access public
	 */
	public function getMyHolds($user) {
		$holds = array(
			'available'   => array(),
			'unavailable' => array()
		);

		//Search for the patron in the database
		$result = $this->getPatronTransactions($user);

		if ($result && ($result->HoldItemsCount > 0 || $result->UnavailableHoldsCount > 0)) {

			// Available Holds
			if ($result->HoldItemsCount > 0) {
				//TODO: a single hold is not in an array; Need to verify that multiple holds are in an array
				if (!is_array($result->HoldItems->HoldItem)) $result->HoldItems->HoldItem = array($result->HoldItems->HoldItem); // For the case of a single hold
				foreach($result->HoldItems->HoldItem as $hold) {
					$curHold = array();
					$bibId          = $hold->BID;
					$carlID         = $this->fullCarlIDfromBID($bibId);
					$expireDate     = isset($hold->ExpirationDate) ? $this->extractDateFromCarlXDateField($hold->ExpirationDate) : null;
					$pickUpBranch   = $this->getBranchInformation($hold->PickUpBranch); //TODO: Use local DB; will require adding ILS branch numbers to DB or memcache (there is a getAllBranchInfo Call)

					$curHold['id']                 = $bibId;
					$curHold['holdSource']         = 'ILS';
					$curHold['itemId']             = $hold->ItemNumber;
					$curHold['cancelId']           = $bibId;
					$curHold['position']           = $hold->QueuePosition;
					$curHold['recordId']           = $carlID;
					$curHold['shortId']            = $bibId;
					$curHold['title']              = $hold->Title;
					$curHold['sortTitle']          = $hold->Title;
					$curHold['author']             = $hold->Author;
					$curHold['location']           = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold['locationUpdateable'] = true; //TODO: unless status is in transit?
					$curHold['currentPickupName']  = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold['status']             = $this->holdStatusCodes[$hold->ItemStatus];
					$curHold['expire']             = strtotime($expireDate); // give a time stamp  // use this for available holds
					$curHold['reactivate']         = null;
					$curHold['reactivateTime']     = null;
					$curHold['frozen']             = $hold->Suspended == true;
					$curHold['cancelable']         = true; //TODO: Can Cancel Available Holds?
					$curHold['freezeable']         = false;

					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($carlID);
					if ($recordDriver->isValid()){
						$curHold['sortTitle']       = $recordDriver->getSortableTitle();
						$curHold['format']          = $recordDriver->getFormat();
						$curHold['isbn']            = $recordDriver->getCleanISBN();
						$curHold['upc']             = $recordDriver->getCleanUPC();
						$curHold['format_category'] = $recordDriver->getFormatCategory();
						$curHold['coverUrl']        = $recordDriver->getBookcoverUrl();
						$curHold['link']            = $recordDriver->getRecordUrl();
						$curHold['ratingData']      = $recordDriver->getRatingData(); //Load rating information

						if (empty($curHold['title'])){
							$curHold['title'] = $recordDriver->getTitle();
						}
						if (empty($curHold['author'])){
							$curHold['author'] = $recordDriver->getPrimaryAuthor();
						}
					}
					$holds['available'][]   = $curHold;

				}
			}

			// Unavailable Holds
			if ($result->UnavailableHoldsCount > 0) {
				if (!is_array($result->UnavailableHoldItems->UnavailableHoldItem)) $result->UnavailableHoldItems->UnavailableHoldItem = array($result->UnavailableHoldItems->UnavailableHoldItem); // For the case of a single hold
				foreach($result->UnavailableHoldItems->UnavailableHoldItem as $hold) {
					$curHold = array();
					$bibId          = $hold->BID;
					$carlID         = $this->fullCarlIDfromBID($bibId);
					$expireDate     = isset($hold->ExpirationDate) ? $this->extractDateFromCarlXDateField($hold->ExpirationDate) : null;
					$pickUpBranch   = $this->getBranchInformation($hold->PickUpBranch);

					$curHold['id']                 = $bibId;
					$curHold['holdSource']         = 'ILS';
					$curHold['itemId']             = $hold->ItemNumber;
					$curHold['cancelId']           = $bibId; // Unavailable holds only
					$curHold['position']           = $hold->QueuePosition;
					$curHold['recordId']           = $carlID;
					$curHold['shortId']            = $bibId;
					$curHold['title']              = $hold->Title;
					$curHold['sortTitle']          = $hold->Title;
					$curHold['author']             = $hold->Author;
					$curHold['location']           = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold['locationUpdateable'] = true; //TODO: unless status is in transit?
					$curHold['currentPickupName']  = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold['frozen']             = $hold->Suspended;
					$curHold['status']             = $this->holdStatusCodes[$hold->ItemStatus];
					$curHold['automaticCancellation'] = strtotime($expireDate); // use this for unavailable holds
					$curHold['cancelable']         = true;

					if ($curHold['frozen']){
						$curHold['reactivate']         = $this->extractDateFromCarlXDateField($hold->SuspendedUntilDate);
						$curHold['reactivateTime']     = strtotime($hold->SuspendedUntilDate);
						$curHold['status']             = 'Frozen';
					}
					$curHold['freezeable'] = true;


					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($carlID);
					if ($recordDriver->isValid()){
						$curHold['sortTitle']       = $recordDriver->getSortableTitle();
						$curHold['format']          = $recordDriver->getFormat();
						$curHold['isbn']            = $recordDriver->getCleanISBN();
						$curHold['upc']             = $recordDriver->getCleanUPC();
						$curHold['format_category'] = $recordDriver->getFormatCategory();
						$curHold['coverUrl']        = $recordDriver->getBookcoverUrl();
						$curHold['link']            = $recordDriver->getRecordUrl();
						$curHold['ratingData']      = $recordDriver->getRatingData(); //Load rating information

						if (empty($curHold['title'])){
							$curHold['title'] = $recordDriver->getTitle();
						}
						if (empty($curHold['author'])){
							$curHold['author'] = $recordDriver->getPrimaryAuthor();
						}
					}
					$holds['unavailable'][] = $curHold;

				}
			}

		} else {
			//TODO: Log Errors
		}

		return $holds;
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds as well as placing recalls.
	 *
	 * @param   User $patron The User to place a hold for
	 * @param   string $recordId The id of the bib record
	 * @param   string $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  array                 An array with the following keys
	 *                                result - true/false
	 *                                message - the message to display (if item holds are required, this is a form to select the item).
	 *                                needsItemLevelHold - An indicator that item level holds are required
	 *                                title - the title of the record the user is placing a hold on
	 * @access  public
	 */
	public function placeHold($patron, $recordId, $pickupBranch, $cancelDate = null) {
		return $this->placeHoldViaSIP($patron, $recordId, $pickupBranch, $cancelDate);
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param   User $patron The User to place a hold for
	 * @param   string $recordId The id of the bib record
	 * @param   string $itemId The id of the item to hold
	 * @param   string $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch) {
		// TODO: Implement placeItemHold() method.
	}

	/**
	 * Cancels a hold for a patron
	 *
	 * @param   User $patron The User to cancel the hold for
	 * @param   string $recordId The id of the bib record
	 * @param   string $cancelId Information about the hold to be cancelled
	 * @return  array
	 */
	function cancelHold($patron, $recordId, $cancelId) {
		return $this->placeHoldViaSIP($patron, $recordId, null, null, 'cancel');

	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate) {
		$result = $this->freezeThawHoldViaSIP($patron, $recordId, null, $dateToReactivate);
		return $result;
	}

	function thawHold($patron, $recordId, $itemToThawId) {
		$timeStamp = strtotime('+1 year');
		$date = date('m/d/Y', $timeStamp);
		$result = $this->freezeThawHoldViaSIP($patron, $recordId, null, $date, 'thaw');
		return $result;
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation) {
		// The recordId ends up being passed via the $itemToUpdateId
		$result = $this->placeHoldViaSIP($patron, $itemToUpdateId, $newPickupLocation, null, 'update');
		return $result;
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

	public function getMyCheckouts($user) {
		$checkedOutTitles = array();

		//Search for the patron in the database
		$result = $this->getPatronTransactions($user);

		if ($result && !empty($result->ChargeItems->ChargeItem)) {
			if (!is_array($result->ChargeItems->ChargeItem)) {
				// Structure an single entry as an array of one.
				$result->ChargeItems->ChargeItem = array($result->ChargeItems->ChargeItem);
			}
			foreach ($result->ChargeItems->ChargeItem as $chargeItem) {
				$carlID = $this->fullCarlIDfromBID($chargeItem->BID);
				$dueDate = strstr($chargeItem->DueDate, 'T', true);
				$curTitle['checkoutSource']  = 'ILS';
				$curTitle['recordId']        = $carlID;
				$curTitle['shortId']         = $chargeItem->BID;
				$curTitle['id']              = $chargeItem->BID;
				$curTitle['barcode']         = $chargeItem->ItemNumber;   // Barcode & ItemNumber are the same for CarlX
				$curTitle['title']           = $chargeItem->Title;
				$curTitle['author']          = $chargeItem->Author;
				$curTitle['dueDate']         = strtotime($dueDate);
				$curTitle['checkoutdate']    = strstr($chargeItem->TransactionDate, 'T', true);
				$curTitle['renewCount']      = $chargeItem->RenewalCount;
				$curTitle['canrenew']        = true;
				$curTitle['renewIndicator']  = null;

				$curTitle['format']          = 'Unknown';
				if (!empty($carlID)){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($carlID); // This needs the $carlID
					if ($recordDriver->isValid()){
						$curTitle['coverUrl']      = $recordDriver->getBookcoverUrl('medium');
						$curTitle['groupedWorkId'] = $recordDriver->getGroupedWorkId();
						$curTitle['format']        = $recordDriver->getPrimaryFormat();
						if (empty($curTitle['title'])){
							$curTitle['title']       = $recordDriver->getTitle();
							$curTitle['title_sort']  = $recordDriver->getSortableTitle();
						} else {
							$curTitle['title_sort']  = $curTitle['title']; // TODO: Always use getSortableTitle?? set to all lower case
						}
						if (empty($curTitle['author'])){
							$curTitle['author']     = $recordDriver->getPrimaryAuthor();
						}
					}else{
						$curTitle['coverUrl']     = "";
					}
					$curTitle['link']           = $recordDriver->getLinkUrl();
				}
				$checkedOutTitles[] = $curTitle;

			}

		} else {
			global $logger;
			$logger->log('Failed to retrieve user Check outs from CarlX API call.', PEAR_LOG_WARNING);
		}

	return $checkedOutTitles;
	}

	public function updatePin($user, $oldPin, $newPin, $confirmNewPin) {
		$request = $this->getSearchbyPatronIdRequest($user);
		$request->Patron->PatronPIN = $newPin;
		$result = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);

		if (is_null($result)) {
			$result = $this->soapClient->__getLastResponse();
			if ($result) {
				$unxml   = new XML_Unserializer();
				$unxml->unserialize($result);
				$response = $unxml->getUnserializedData();

				if ($response) {
					$success = stripos($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'], 'Success') !== false;
					if (!$success) {
						// TODO: might not want to include sending message back to user
						$errorMessage = $response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:LongMessage'];
						return 'Failed to update your pin'. ($errorMessage ? ' : ' .$errorMessage : '');
					} else {
						$user->cat_password = $newPin;
						$user->update();
						return "Your pin number was updated successfully.";
					}

				} else {
					global $logger;
					$logger->log('Unable to read XML from CarlX response when attempting to update Patron PIN.', PEAR_LOG_ERR);
					return 'Unable to update your pin.';
				}

			} else {
				global $logger;
				$logger->log('CarlX ILS gave no response when attempting to update Patron PIN.', PEAR_LOG_ERR);
				return 'Unable to update your pin.';
			}
		} elseif (!$result) {
			return 'Failed to contact Circulation System.';
		}
	}

	public function updatePatronInfo($user, $canUpdateContactInfo) {
		$updateErrors = array();
		if ($canUpdateContactInfo){

			$request = $this->getSearchbyPatronIdRequest($user);


			// Patron Info to update.
			$request->Patron->Email  = $_REQUEST['email'];
			$request->Patron->Phone1 = $_REQUEST['phone'];
//			$request->Patron->PhoneType = 0; // Set phone Type for Primary Phone (PhoneType 0 is Home, 1 is Work)
//			$phoneTypes = $this->getPhoneTypeList();
//			print_r($phoneTypes);

			if (isset($_REQUEST['workPhone'])){
				$request->Patron->Phone2 = $_REQUEST['workPhone'];
			}

			$request->Patron->Addresses->Address->Type        = 'Primary';
			$request->Patron->Addresses->Address->Street      = $_REQUEST['address1'];
			$request->Patron->Addresses->Address->City        = $_REQUEST['city'];
			$request->Patron->Addresses->Address->State       = $_REQUEST['state'];
			$request->Patron->Addresses->Address->PostalCode  = $_REQUEST['zip'];

			if (isset($_REQUEST['notices'])){
//				$noticeLabels = array(
//					//'-' => 'Mail',  // officially None in Sierra, as in No Preference Selected.
//					'-' => '',        // notification will generally be based on what information is available so can't determine here. plb 12-02-2014
//					'a' => 'Mail',    // officially Print in Sierra
//					'p' => 'Telephone',
//					'z' => 'E-mail',
//				);

				if ($_REQUEST['notices'] == 'z') {
					$request->Patron->EmailNotices = 'send email';
				} else {
					$request->Patron->EmailNotices = 'do not send email';
				}

			}

			$result = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);

			if (is_null($result)) {
				$result = $this->soapClient->__getLastResponse();
				if ($result) {
					$unxml   = new XML_Unserializer();
					$unxml->unserialize($result);
					$response = $unxml->getUnserializedData();

					if ($response) {
						$success = stripos($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'], 'Success') !== false;
						if (!$success) {
							$errorMessage = $response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:LongMessage'];
							$updateErrors[] = 'Failed to update your information'. ($errorMessage ? ' : ' .$errorMessage : '');
						}

					} else {
						$updateErrors[] = 'Unable to update your information.';
						global $logger;
						$logger->log('Unable to read XML from CarlX response when attempting to update Patron Information.', PEAR_LOG_ERR);
					}

				} else {
					$updateErrors[] = 'Unable to update your information.';
					global $logger;
					$logger->log('CarlX ILS gave no response when attempting to update Patron Information.', PEAR_LOG_ERR);
				}
			}

		} else {
			$updateErrors[] = 'You can not update your information.';
		}
		return $updateErrors;
	}

	public function getSelfRegistrationFields() {
		global $library;
		$fields = array();
		$fields[] = array('property'=>'firstName',   'type'=>'text', 'label'=>'First Name', 'description'=>'Your first name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property'=>'middleName',  'type'=>'text', 'label'=>'Middle Name', 'description'=>'Your middle name', 'maxLength' => 40, 'required' => true);
		// gets added to the first name separated by a space
		$fields[] = array('property'=>'lastName',   'type'=>'text', 'label'=>'Last Name', 'description'=>'Your last name', 'maxLength' => 40, 'required' => true);
		if ($library && $library->promptForBirthDateInSelfReg){
			$fields[] = array('property'=>'birthDate', 'type'=>'date', 'label'=>'Date of Birth (MM-DD-YYYY)', 'description'=>'Date of birth', 'maxLength' => 10, 'required' => true);
		}
		$fields[] = array('property'=>'address',     'type'=>'text', 'label'=>'Mailing Address', 'description'=>'Mailing Address', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'city',        'type'=>'text', 'label'=>'City', 'description'=>'City', 'maxLength' => 48, 'required' => true);
		$fields[] = array('property'=>'state',       'type'=>'text', 'label'=>'State', 'description'=>'State', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'zip',         'type'=>'text', 'label'=>'Zip Code', 'description'=>'Zip Code', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'email',       'type'=>'email', 'label'=>'E-Mail', 'description'=>'E-Mail', 'maxLength' => 128, 'required' => false);
		$fields[] = array('property'=>'pin',         'type'=>'pin',   'label'=>'Pin', 'description'=>'Your desired 4-digit pin', 'maxLength' => 4, 'size' => 4, 'required' => true);
		$fields[] = array('property'=>'pin1',        'type'=>'pin',   'label'=>'Confirm Pin', 'description'=>'Re-type your desired 4-digit pin', 'maxLength' => 4, 'size' => 4, 'required' => true);
		//$fields[] = array('property'=>'universityID', 'type'=>'text', 'label'=>'Drivers License #', 'description'=>'Drivers License', 'maxLength' => 128, 'required' => false);

		//TODO: Home Branch
		return $fields;

	}

	public function selfRegister(){
		global $library,
		       $configArray;
		$success = false;

		$lastPatronID = new Variable();
		$lastPatronID->get('name', 'last_selfreg_patron_id');

		if (!empty($lastPatronID->value)) {
			$currentPatronIDNumber = ++$lastPatronID->value;

			$tempPatronID = $configArray['Catalog']['selfRegIDPrefix'] . str_pad($currentPatronIDNumber, $configArray['Catalog']['selfRegIDNumberLength'], '0', STR_PAD_LEFT);

			$firstName  = trim($_REQUEST['firstName']);
			$middleName = trim($_REQUEST['middleName']);
			$lastName   = trim($_REQUEST['lastName']);
			$address    = trim($_REQUEST['address']);
			$city       = trim($_REQUEST['city']);
			$state      = trim($_REQUEST['state']);
			$zip        = trim($_REQUEST['zip']);
			$email      = trim($_REQUEST['email']);
			$pin        = trim($_REQUEST['pin']);
			$pin1       = trim($_REQUEST['pin1']);

			if (!empty($pin) && !empty($pin1) && $pin == $pin1) {

				$request                                         = new stdClass();
				$request->Modifiers                              = '';
				$request->PatronFlags->PatronFlag                = 'DUPCHECK_NAME_DOB'; // Do a duplicate name/date of birth check
				$request->Patron->PatronID                       = $tempPatronID;
				$request->Patron->Email                          = $email;
				$request->Patron->FirstName                      = $firstName;
				$request->Patron->MiddleName                     = $middleName;
				$request->Patron->LastName                       = $lastName;
				$request->Patron->Addresses->Address->Type       = 'Primary';
				$request->Patron->Addresses->Address->Street     = $address;
				$request->Patron->Addresses->Address->City       = $city;
				$request->Patron->Addresses->Address->State      = $state;
				$request->Patron->Addresses->Address->PostalCode = $zip;
				$request->Patron->PatronPIN                      = $pin;
				// TODO: Set Home Branch?
				// TODO: Set Expiration Date?

				if ($library && $library->promptForBirthDateInSelfReg) {
					$birthDate                  = trim($_REQUEST['birthDate']);
					$date                       = DateTime::createFromFormat('m-d-Y', $birthDate);
					$request->Patron->BirthDate = $date->format('Y-m-d');
				}

				$request->Patron->RegisteredBy = 'Pika Discovery Layer';

				$result = $this->doSoapRequest('createPatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);

				if (is_null($result) && $this->soapClient) {
					$result = $this->soapClient->__getLastResponse();
//				echo '<pre>';
//				print_r($this->soapClient->__getFunctions());
//				echo '</pre>';
					if ($result) {
						$unxml = new XML_Unserializer();
						$unxml->unserialize($result);
						$response = $unxml->getUnserializedData();
						if ($response) {
							$success = isset($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'])
								&& stripos($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'], 'Success') !== false;
							if (!$success) {
								$errorMessage = array();
								if (is_array($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus'])) {
									foreach($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus'] as $errorResponse) {
										$errorMessage[] = $errorResponse['ns2:LongMessage'];
									}
								} else {
									$errorMessage[] = $response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:LongMessage'];
								}
								if (in_array('A patron with that id already exists', $errorMessage)) {
									global $logger;
									$logger->log('While self-registering user for CarlX, temp id number was reported in use. Increasing internal counter', PEAR_LOG_ERR);
									// Increment the temp patron id number.
									$lastPatronID->value = $currentPatronIDNumber;
									if (!$lastPatronID->update()) {
										$logger->log('Failed to update Variables table with new value ' . $currentPatronIDNumber . ' for "last_selfreg_patron_id" in CarlX Driver', PEAR_LOG_ERR);
									}
								}
//						$updateErrors[] = 'Failed to update your information'. ($errorMessage ? ' : ' .$errorMessage : '');

							} else {
								$lastPatronID->value = $currentPatronIDNumber;
								if (!$lastPatronID->update()) {
									global $logger;
									$logger->log('Failed to update Variables table with new value ' . $currentPatronIDNumber . ' for "last_selfreg_patron_id" in CarlX Driver', PEAR_LOG_ERR);
								}
								// Get Patron
								$request = new stdClass();
								$request->SearchType = 'Patron ID';
								$request->SearchID   = $tempPatronID;
								$request->Modifiers  = '';

								$result = $this->doSoapRequest('getPatronInformation', $request);
								// Check That the Pin was set  (the create Patron call does not seem to set the Pin)
								if ($result && isset($result->Patron) && $result->Patron->PatronPIN == '') {
									$request->Patron->PatronPIN = $pin;
									$result = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);
									if (is_null($result)) {
										$result = $this->soapClient->__getLastResponse();
										if ($result) {
											$unxml = new XML_Unserializer();
											$unxml->unserialize($result);
											$response = $unxml->getUnserializedData();

											if ($response) {
												$success = stripos($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'], 'Success') !== false;
												if (!$success) {
													global $logger;
													$logger->log('Unable to set pin for Self-Registered user on update call after initial creation call.', PEAR_LOG_ERR);
													// The Pin will be an empty.
													// Return Success Any way, because the account was created.
													return array(
														'success' => true,
														'barcode' => $tempPatronID,
													);
												}
											}
										}
									}
								}

								return array(
									'success' => $success,
									'barcode' => $tempPatronID,
								);
							}

						} else {
//					$updateErrors[] = 'Unable to update your information.';
							global $logger;
							$logger->log('Unable to read XML from CarlX response when attempting to create Patron.', PEAR_LOG_ERR);
						}

					} else {
//				$updateErrors[] = 'Unable to update your information.';
						global $logger;
						$logger->log('CarlX ILS gave no response when attempting to create Patron.', PEAR_LOG_ERR);
					}
				}
			} else {
				global $logger;
				$logger->log('CarlX Self Registration Form was passed bad data for a user\'s pin.', PEAR_LOG_WARNING);
			}
		} else {
			global $logger;
			$logger->log('No value for "last_selfreg_patron_id" set in Variables table. Can not self-register patron in CarlX Driver.', PEAR_LOG_ERR);

		}

		return array(
			'success' => $success
		);

	}

	public function getReadingHistory($user, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		global $timer;

		$readHistoryEnabled = false;
		$request = $this->getSearchbyPatronIdRequest($user);
		$result = $this->doSoapRequest('getPatronInformation', $request, $this->patronWsdl);
		if ($result && $result->Patron) {
			$readHistoryEnabled = $result->Patron->LoanHistoryOptInFlag;
		}

		if ($readHistoryEnabled) { // Create Reading History Request
			$historyActive = true;
			$readingHistoryTitles = array();
			$numTitles = 0;

			$request->HistoryType = 'L'; //  From Documentation: The type of charge history to return, (O)utreach or (L)oan History opt-in
			$result = $this->doSoapRequest('getPatronChargeHistory', $request);

			if ($result) {
				// Process Reading History Response
				if (!empty($result->ChargeHistoryItems->ChargeItem)) {
					foreach ($result->ChargeHistoryItems->ChargeItem as $readingHistoryEntry) {
						// Process Reading History Entries
						$checkOutDate  = new DateTime($readingHistoryEntry->ChargeDateTime);
						$curTitle  = array();
						$curTitle['itemId']       = $readingHistoryEntry->ItemNumber;
						$curTitle['id']           = $readingHistoryEntry->BID;
						$curTitle['shortId']      = $readingHistoryEntry->BID;
						$curTitle['recordId']     = $this->fullCarlIDfromBID($readingHistoryEntry->BID);
						$curTitle['title']        = rtrim($readingHistoryEntry->Title, ' /');
						$curTitle['checkout']     = $checkOutDate->format('m-d-Y'); // this format is expected by Pika's java cron program.
						$curTitle['borrower_num'] = $user->id;
						$curTitle['dueDate']      = null; // Not available in ChargeHistoryItems
						$curTitle['author']       = null; // Not available in ChargeHistoryItems

						$readingHistoryTitles[] = $curTitle;
					}

					$numTitles = count($readingHistoryTitles);

					//process pagination
					if ($recordsPerPage != -1){
						$startRecord = ($page - 1) * $recordsPerPage;
						$readingHistoryTitles = array_slice($readingHistoryTitles, $startRecord, $recordsPerPage);
					}

					set_time_limit(20 * count($readingHistoryTitles)); // Taken from Aspencat Driver

					// Fetch Additional Information for each Item
					foreach ($readingHistoryTitles as $key => $historyEntry){
						//Get additional information from resources table
						$historyEntry['ratingData']  = null;
						$historyEntry['permanentId'] = null;
						$historyEntry['linkUrl']     = null;
						$historyEntry['coverUrl']    = null;
						$historyEntry['format']      = 'Unknown';
						if (!empty($historyEntry['recordId'])){
							require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
							$recordDriver = new MarcRecord($this->accountProfile->recordSource.':'.$historyEntry['recordId']);
							if ($recordDriver->isValid()){
								$historyEntry['ratingData']  = $recordDriver->getRatingData();
								$historyEntry['permanentId'] = $recordDriver->getPermanentId();
								$historyEntry['linkUrl']     = $recordDriver->getGroupedWorkDriver()->getLinkUrl();
								$historyEntry['coverUrl']    = $recordDriver->getBookcoverUrl('medium');
								$historyEntry['format']      = $recordDriver->getFormats();
								$historyEntry['author']      = $recordDriver->getPrimaryAuthor();
								if (empty($curTitle['title'])){
									$curTitle['title']         = $recordDriver->getTitle();
								}
							}

							$recordDriver = null;
						}
						$historyEntry['title_sort'] = preg_replace('/[^a-z\s]/', '', strtolower($historyEntry['title']));

						$readingHistoryTitles[$key] = $historyEntry;
					}


				}

				// Return Reading History
				return array('historyActive'=>$historyActive, 'titles'=>$readingHistoryTitles, 'numTitles'=> $numTitles);

			} else {
				global $logger;
				$logger->log('CarlX ILS gave no response when attempting to get Reading History.', PEAR_LOG_ERR);
			}
		}
		return array('historyActive' => false, 'titles' => array(), 'numTitles' => 0);
	}

	public function doReadingHistoryAction($user, $action, $selectedTitles){
		switch ($action) {
			case 'optIn' :
			case 'optOut' :
				$request = $this->getSearchbyPatronIdRequest($user);
				$request->Patron->LoanHistoryOptInFlag = ($action == 'optIn');
				$result = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);

				$success = false;
				// code block below has been taken from updatePatronInfo()
				if (is_null($result)) {
					$result = $this->soapClient->__getLastResponse();
					if ($result) {
						$unxml   = new XML_Unserializer();
						$unxml->unserialize($result);
						$response = $unxml->getUnserializedData();

						if ($response) {
							$success = stripos($response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:ShortMessage'], 'Success') !== false;
							if (!$success) {
								$errorMessage = $response['SOAP-ENV:Body']['ns3:GenericResponse']['ns3:ResponseStatuses']['ns2:ResponseStatus']['ns2:LongMessage'];
//								$updateErrors[] = 'Failed to update your information'. ($errorMessage ? ' : ' .$errorMessage : '');
							}

						} else {
//							$updateErrors[] = 'Unable to update your information.';
							global $logger;
							$logger->log('Unable to read XML from CarlX response when attempting to update Patron Information.', PEAR_LOG_ERR);
						}

					} else {
//						$updateErrors[] = 'Unable to update your information.';
						global $logger;
						$logger->log('CarlX ILS gave no response when attempting to update Patron Information.', PEAR_LOG_ERR);
					}
				}
				return $success;

				break;

		}

	}

	public function getMyFines($user) {
		$myFines = array();

		$request = $this->getSearchbyPatronIdRequest($user);

		// Fines
		$request->TransactionType = 'Fine';
		$result = $this->doSoapRequest('getPatronTransactions', $request);

		if ($result && !empty($result->FineItems->FineItem)) {
			if (!is_array($result->FineItems->FineItem)) {
				$result->FineItems->FineItem = array($result->FineItems->FineItem);
			}
			foreach($result->FineItems->FineItem as $fine) {
				if ($fine->FineAmountPaid > 0) {
					$fine->FineAmount -= $fine->FineAmountPaid;
				}
				$myFines[] = array(
					'reason'  => $fine->FeeNotes,
					'amount'  => $fine->FineAmount,
					'message' => $fine->Title,
					'date'    => date('M j, Y', strtotime($fine->FineAssessedDate)),
				);
			}
		}

		// Lost Item Fees

		// TODO: Lost Items don't have the fine amount
		$request->TransactionType = 'Lost';
		$result = $this->doSoapRequest('getPatronTransactions', $request);

		if ($result && !empty($result->LostItems->LostItem)) {
			if (!is_array($result->LostItems->LostItem)) {
				$result->LostItems->LostItem = array($result->LostItems->LostItem);
			}
			foreach($result->LostItems->LostItem as $fine) {
				$myFines[] = array(
					'reason'  => $fine->FeeNotes,
//					'amount'  => $fine->FineAmount, // TODO: There is no corresponding amount
					'amount'  => '',
					'message' => $fine->Title,
					'date'    => date('M j, Y', strtotime($fine->TransactionDate)),
				);
			}
		}

		return $myFines;
	}

//	public function getMyFines($user) {
//		$myFines = array();
//
//		$request = $this->getSearchbyPatronIdRequest($user);
////		$request->CirculationFilter = false; //TODO: not sure what this filters, might be needed in actual system
//		$request->CirculationFilter = true;
//		$result = $this->doSoapRequest('getPatronFiscalHistory', $request);
//		if ($result && !empty($result->FiscalHistoryItem)) {
//			if (!is_array($result->FiscalHistoryItem)) {
//				$result->FiscalHistoryItem = array($result->FiscalHistoryItem); // single entries are not presented as an array
//			}
//			foreach($result->FiscalHistoryItem as $fine) {
//				if ($fine->FiscalType == 'Credit') {
//					$amount = $fine->Amount > 0 ? '-$' . sprintf('%0.2f', $fine->Amount / 100) : ''; // amounts are in cents
//				} else {
//					$amount = $fine->Amount > 0 ? '$' . sprintf('%0.2f', $fine->Amount / 100) : ''; // amounts are in cents
//				}
//				$myFines[] = array(
//					'reason'  => $fine->Notes,
//					'amount'  => $amount,
//					'message' => $fine->Title,
////					'date'    => $this->extractDateFromCarlXDateField($fine->TransDate), //TODO: set as datetime?
//					'date'    => date('M j, Y', strtotime($fine->TransDate)), //TODO: set as datetime?
//				);
//			}
//
//			//TODO: Look At Page Result if additional Calls need to be made.
//		}
//
//		return $myFines;
//	}

	/**
	 * Get Patron Transactions
	 *
	 * This is responsible for retrieving all transactions (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $user The user to load transactions for
	 *
	 * @return array        Array of the patron's transactions on success
	 * @access public
	 */
	private function getPatronTransactions($user)
	{
		$request = $this->getSearchbyPatronIdRequest($user);
		$result = $this->doSoapRequest('getPatronTransactions', $request, $this->patronWsdl);
		return $result;
	}

	private function getPhoneTypeList() {
		$request             = new stdClass();
		$request->Modifiers  = '';

		$result = $this->doSoapRequest('getPhoneTypeList', $request);
		if ($result) {
			$phoneTypes = array();
			foreach ($result->PhoneTypes->PhoneType as $phoneType) {
				$phoneTypes[$phoneType->SortGroup][$phoneType->PhoneTypeId] = $phoneType->Description;
			}
			return $phoneTypes;
		}
		return false;
	}

	private function getLocationInformation($locationNumber) {

		$request = new stdClass();
		$request->LocationSearchType = 'Location Number';
		$request->LocationSearchValue = $locationNumber;
		$request->Modifiers  = '';

		$result = $this->doSoapRequest('GetLocationInformation', $request, $this->catalogWsdl);
		if ($result && $result->LocationInfo) {
			return $result->LocationInfo; // convert to array instead?
		}
		return false;

	}

	private function getBranchInformation($branchNumber) {
//		TODO: Store in Memcache instead

		$request                    = new stdClass();
		$request->BranchSearchType  = 'Branch Number';
		$request->BranchSearchValue = $branchNumber;
		$request->Modifiers         = '';

		$result = $this->doSoapRequest('GetBranchInformation', $request, $this->catalogWsdl);
		if ($result && $result->BranchInfo) {
			return $result->BranchInfo; // convert to array instead?
		}
		return false;
	}

	/**
	 * @param $dateField string
	 * @return string
	 */
	private function extractDateFromCarlXDateField($dateField)
	{
		return strstr($dateField, 'T', true);
	}

	/**
	 * @param $user
	 * @return stdClass
	 */
	private function getSearchbyPatronIdRequest($user)
	{
		$request             = new stdClass();
		$request->SearchType = 'Patron ID';
		$request->SearchID   = $user->cat_username; // TODO: Question: barcode/pin check
		$request->Modifiers  = '';
		return $request;
	}


	public function freezeThawHoldViaSIP($patron, $recordId, $itemToFreezeId = null, $dateToReactivate = null, $type = 'freeze'){
		global $configArray;
		//Place the hold via SIP 2
		require_once ROOT_DIR . '/sys/SIP2.php';
		$mySip = new sip2();
		$mySip->hostname = $configArray['SIP2']['host'];
		$mySip->port     = $configArray['SIP2']['port'];

		$success = false;
		$title = '';
		$message = 'Failed to connect to complete requested action.';
		if ($mySip->connect()) {
			//send self check status message
			$in = $mySip->msgSCStatus();
			$msg_result = $mySip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mySip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (!empty($result['variable']['AO'][0])) $mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				if (!empty($result['variable']['AN'][0])) $mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */

				$mySip->patron    = $patron->cat_username;
				$mySip->patronpwd = $patron->cat_password;

				$holdId = $recordId;

//				$holds = $this->getMyHolds($patron);
				$hold = $this->getUnavailableHold($patron, $holdId);
				if ($hold) {

					$pickupLocation = $hold->PickUpBranch;
					if (!empty($hold->Title)) {
						$title = $hold->Title;
					}
					$freeze = true;
					if ($type == 'thaw') {
						$freeze = false;
					}

//					$in = $mySip->freezeSuspendHold($dateToReactivate, $freeze, '', '1', '', $holdId, 'N', $pickupLocation);
					$in = $mySip->freezeSuspendHold($dateToReactivate, $freeze, '', '2', '', $holdId, 'N', $pickupLocation);
//				$in = $mySip->freezeHoldCarlX($dateToReactivate, $holdId);
					$msg_result = $mySip->get_message($in);

					if (preg_match("/^16/", $msg_result)) {
						$result  = $mySip->parseHoldResponse($msg_result);
						$success = ($result['fixed']['Ok'] == 1);
						$message = $result['variable']['AF'][0];
						if (!empty($result['variable']['AJ'][0])) {
							$title = $result['variable']['AJ'][0];
						}
					}
				} else {
					$message = 'Failed to get Pickup Location';
				}
			}
		}
		return array(
			'title'   => $title,
			'bib'     => $recordId,
			'success' => $success,
			'message' => $message
		);
	}

	private function getUnavailableHold($patron, $holdID) {
		$request = $this->getSearchbyPatronIdRequest($patron);
		$request->TransactionType = 'UnavailableHold';
		$result = $this->doSoapRequest('getPatronTransactions', $request);

		if ($result && !empty($result->UnavailableHoldItems->UnavailableHoldItem)) {
			if (!is_array($result->UnavailableHoldItems->UnavailableHoldItem)) {
				$result->UnavailableHoldItems->UnavailableHoldItem = array($result->UnavailableHoldItems->UnavailableHoldItem);
			}
			foreach($result->UnavailableHoldItems->UnavailableHoldItem as $hold) {
				if ($hold->BID == $holdID) {
					return $hold;
				}
			}
		}
		return false;
	}

	public function placeHoldViaSIP($patron, $recordId, $pickupBranch = null, $cancelDate = null, $type = null){
		global $configArray;
		//Place the hold via SIP 2
		require_once ROOT_DIR . '/sys/SIP2.php';
		$mySip = new sip2();
		$mySip->hostname = $configArray['SIP2']['host'];
		$mySip->port     = $configArray['SIP2']['port'];

		$success = false;
		$title = '';
		$message = 'Failed to connect to complete requested action.';
		if ($mySip->connect()) {
			//send selfcheck status message
			$in = $mySip->msgSCStatus();
			$msg_result = $mySip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mySip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (!empty($result['variable']['AO'][0])) $mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				if (!empty($result['variable']['AN'][0])) $mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */

				$mySip->patron    = $patron->cat_username;
				$mySip->patronpwd = $patron->cat_password;

				if (empty($pickupBranch)){
					//Get the code for the location
					$locationLookup = new Location();
					$locationLookup->locationId = $patron->homeLocationId;
					$locationLookup->find(1);
					if ($locationLookup->N > 0){
						$pickupBranch = $locationLookup->code;
					}
				}

				//place the hold
				if ($type == 'cancel' || $type == 'recall'){
					$mode = '-';
					$holdId = $recordId;

					// Get Title  (Title is not part of the cancel response)
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($this->fullCarlIDfromBID($recordId));
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
					}

				}elseif ($type == 'update'){
					$mode = '*';
					$holdId = $recordId;
				}else{
					$mode = '+';
					$holdId = $this->BIDfromFullCarlID($recordId);
				}

				//TODO: Should change cancellation date when updating pick up locations
				if (!empty($cancelDate)) {
					$dateObject = date_create_from_format('m/d/Y', $cancelDate);
					$expirationTime = $dateObject->getTimestamp();
				} else {
					//expire the hold in 2 years by default
					$expirationTime = time() + 2 * 365 * 24 * 60 * 60;
				}

				$in = $mySip->msgHold($mode, $expirationTime, '2', '', $holdId, '', $pickupBranch);
				$msg_result = $mySip->get_message($in);

//				$title = $this->getRecordTitle($recordId); //TODO: method isn't defined

				if (preg_match("/^16/", $msg_result)) {
					$result = $mySip->parseHoldResponse($msg_result );
					$success = ($result['fixed']['Ok'] == 1);
					$message = $result['variable']['AF'][0];
					if (!empty($result['variable']['AJ'][0])) {
						$title = $result['variable']['AJ'][0];
					}
				}
			}
		}
		return array(
				'title'   => $title,
				'bib'     => $recordId,
				'success' => $success,
				'message' => $message
		);
	}


	public function renewItemViaSIP($patron, $itemId, $useAlternateSIP = false){
		global $configArray;

		//renew the item via SIP 2
		require_once ROOT_DIR . '/sys/SIP2.php';
		$mysip = new sip2();
		$mysip->hostname = $configArray['SIP2']['host'];
		if ($useAlternateSIP){
			$mysip->port = $configArray['SIP2']['alternate_port'];
		}else{
			$mysip->port = $configArray['SIP2']['port'];
		}

		$success = false;
		$message = 'Failed to connect to complete requested action.';
		if ($mysip->connect()) {
			//send selfcheck status message
			$in = $mysip->msgSCStatus();
			$msg_result = $mysip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mysip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 settings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (!empty($result['variable']['AO'][0])) $mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				if (!empty($result['variable']['AN'][0])) $mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */

				$mysip->patron    = $patron->cat_username;
				$mysip->patronpwd = $patron->cat_password;

				$in = $mysip->msgRenew($itemId, '', '', '', 'N', 'N', 'Y');
				//print_r($in . '<br/>');
				$msg_result = $mysip->get_message($in);
				//print_r($msg_result);

				if (preg_match("/^30/", $msg_result)) {
					$result = $mysip->parseRenewResponse($msg_result);

					$title = $result['variable']['AJ'][0];

					$success = ($result['fixed']['Ok'] == 1);
					$message = $result['variable']['AF'][0];

					//Looks like a holds process, rather than a renewal process. pascal 7-12-2016
//					//If the renew fails, check to see if we need to override the SIP port
//					$alternatePortSet = false;
//					if (isset($configArray['SIP2']['alternate_port']) && strlen($configArray['SIP2']['alternate_port']) > 0 && $configArray['SIP2']['alternate_port'] != $configArray['SIP2']['port']){
//						$alternatePortSet = true;
//					}
//					if ($alternatePortSet && $success == false && $useAlternateSIP == false){
//						//Can override the SIP port if there are sufficient copies on the shelf to cover any holds
//
//						//Get the id for the item
//						$searchObject = SearchObjectFactory::initSearchObject();
//						$class = $configArray['Index']['engine'];
//						$url = $configArray['Index']['url'];
//						$index = new $class($url);
//
//						$record = $index->getRecordByBarcode($itemId);
//
//						if ($record){
//							//Get holdings summary
//							$statusSummary = $this->getStatusSummary($record['id'], $record, $mysip);
//
//							//If # of available copies >= waitlist change sip port and renew
//							if ($statusSummary['availableCopies'] >= $statusSummary['holdQueueLength']){  // this looks like a hold test, rather than renewal. plb 7-12-2016
//								$renew_result = $this->renewItemViaSIP($patron, $itemId, true);
//							}
//						}
//					}
				}
			}
		}else{
			$message = "Could not connect to circulation server, please try again later.";
		}

		return array(
			'itemId'  => $itemId,
			'success' => $success,
			'message' => $message
		);
	}

	/**
	 * @param $BID
	 * @return string CARL ID
	 */
	private function fullCarlIDfromBID($BID)
	{
		return 'CARL' . str_pad($BID, 10, '0', STR_PAD_LEFT);
	}

	private function BIDfromFullCarlID($CarlID) {
		$temp = str_replace('CARL', '', $CarlID);  // Remove CARL prefix
		$temp = ltrim($temp, '0'); // Remove preceding zeros
		return $temp;
	}


	public function findNewUser($patronBarcode) {
		// Use the validateViaSSO switch to bypass Pin check. If a user is found, patronLogin will return a new User object.
		$newUser = $this->patronLogin($patronBarcode, null, true);
		if (!empty($newUser) && !PEAR_Singleton::isError($newUser)) {
			return $newUser;
		}
		return false;
	}

}
