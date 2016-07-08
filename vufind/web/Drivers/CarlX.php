<?php

/**
 * Implements
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 6/10/2016
 * Time: 1:53 PM
 */
require_once ROOT_DIR . '/Drivers/SIP2Driver.php';
class CarlX extends SIP2Driver{
	public $accountProfile;
	public $patronWsdl;
	public $catalogWsdl;

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

		//Search for the patron in the database
		$soapClient = new SoapClient($this->patronWsdl
			,array(
				'trace' => 1
			)
		);

		$request = new stdClass();
		$request->SearchType = 'Patron ID';
		$request->SearchID = $username;
		$request->Modifiers = '';

		$result = $soapClient->getPatronInformation($request);

		$patronValid = false;
		if ($result){
			if ($result->Patron){
				//Check to see if the pin matches
				if ($result->Patron->PatronPIN == $password || $validatedViaSSO){
					$fullName = $result->Patron->FullName;
					$firstName = $result->Patron->FirstName;
					$lastName = $result->Patron->LastName;

					$userExistsInDB = false;
					$user = new User();
					$user->source = $this->accountProfile->name;
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
					$user->fullname = isset($fullName) ? $fullName : '';
					$user->cat_username = $username;
					$user->cat_password = $password;
					$user->email = $result->Patron->Email;

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

							//Get display names that aren't stored
							$user->homeLocationCode = $location->code;
							$user->homeLocation     = $location->displayName;

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

					if ($result->Patron->EmailReceiptFlag === true) {
						//$result->Patron->EmailNotices as ~4 values: "do not send email",
						$user->notices = 'z';
						$user->noticePreferenceLabel = 'E-mail';
					} else {
						// TODO: Set Phone Notice Setting
						$user->notices = '-';
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
					$patronSummaryRequest->PatronID  = $username;
					$patronSummaryRequest->Modifiers = '';
					$patronSummaryResponse = $soapClient->getPatronSummaryOverview($patronSummaryRequest);

					$user->numCheckedOutIls     = $patronSummaryResponse->ChargedItemsCount;
					$user->numHoldsAvailableIls = $patronSummaryResponse->HoldItemsCount;
					$user->numHoldsRequestedIls = $patronSummaryResponse->UnavailableHoldsCount;
					$user->numHoldsIls          = $user->numHoldsAvailableIls + $user->numHoldsRequestedIls;

					$outstandingFines = $patronSummaryResponse->FineTotal + $patronSummaryResponse->LostItemFeeTotal;
					$user->fines    = sprintf('$%0.2f', $outstandingFines);
					$user->finesVal = floatval($outstandingFines);

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
	}

	/**
	 * Renew all titles currently checked out to the user
	 *
	 * @param $patron  User
	 * @return mixed
	 */
	public function renewAll($patron) {
		// TODO: Implement renewAll() method.
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
	public function renewItem($patron, $recordId, $itemId, $itemIndex) {
		// TODO: Implement renewItem() method.
	}

	private $holdStatusCodes = array( //TODO: Set to Pika Common Values so they can be translated? (look at templates, Horizon Driver seems to just use Horizon values)
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
		$result = $this->getParonTransactions($user);

		if ($result && ($result->HoldItemsCount > 0 || $result->UnavailableHoldsCount > 0)) {
			if ($result->HoldItemsCount > 0) {
				//TODO: a single hold is not in an array; Need to verify that multiple holds are in an array
				if (!is_array($result->HoldItems->HoldItem)) $result->HoldItems->HoldItem = array($result->HoldItems->HoldItem); // For the case of a single hold
				foreach($result->HoldItems->HoldItem as $hold) {
					$curHold = array();
					$bibId          = $hold->BID;
					$expireDate     = isset($hold->ExpirationDate) ? $this->extractDateFromCarlXDateField($hold->ExpirationDate) : null;
					$pickUpBranch   = $this->getBranchInformation($hold->PickUpBranch); //TODO: Use local DB; will require adding ILS branch numbers to DB or memcache (there is a getAllBranchInfo Call)
//					$location       = $this->getLocationInformation($hold->Location); // IDK what this is referring to yet, or if it is needed

//						$reactivateDate = $this->extractDateFromCarlXDateField($hold) //TODO: activation date? unavailable holds only
//					$curHold['user']               = $user->getNameAndLibraryLabel(); // Done in CatalogConnection
					$curHold['id']                 = $bibId;
					$curHold['holdSource']         = 'ILS';
					$curHold['itemId']             = $hold->ItemNumber;
//						$curHold['cancelId']           = (string)$hold->holdKey; //TODO: Determine Cancellation Method
					$curHold['position']           = $hold->QueuePosition;
					$curHold['recordId']           = $bibId;
					$curHold['shortId']            = $bibId;
					$curHold['title']              = $hold->Title;
					$curHold['sortTitle']          = $hold->Title;
					$curHold['author']             = $hold->Author;
					$curHold['location']           = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold['locationUpdateable'] = true; //TODO: unless status is in transit?
					$curHold['currentPickupName']  = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
//					$curHold['status']             = ucfirst(strtolower((string)$hold->status));
					$curHold['status']             = $this->holdStatusCodes[$hold->ItemStatus];  // TODO: Is this the correct thing for hold status. Alternative is Transaction Code
					//TODO: Look up values for Hold Statuses

					$curHold['expire']         = strtotime($expireDate);
//						$curHold['reactivate']         = $reactivateDate; //TODO unavailable only
//						$curHold['reactivateTime']     = strtotime($reactivateDate); //TODO unavailable only
					$curHold['cancelable']         = strcasecmp($curHold['status'], 'Suspended') != 0; //TODO: need frozen status
//					$curHold['frozen']             = strcasecmp($curHold['status'], 'Suspended') == 0; //TODO: need frozen status
					$curHold['frozen']             = false;
//					if ($curHold['frozen']){  //TODO Can CarlX holds be frozen?
//						$curHold['reactivateTime']   = (int)$hold->reactivateDate;
//					}
					$curHold['freezeable'] = false;
//					$curHold['freezeable'] = true; //TODO Can CarlX holds be frozen?
//					if (strcasecmp($curHold['status'], 'Transit') == 0) {
//						$curHold['freezeable'] = false;
//					}

					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($bibId);
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
			if ($result->UnavailableHoldsCount > 0) {
				// TODO: SHould foreach loops be consolidated into one loop
				if (!is_array($result->UnavailableHoldItems->UnavailableHoldItem)) $result->UnavailableHoldItems->UnavailableHoldItem = array($result->UnavailableHoldItems->UnavailableHoldItem); // For the case of a single hold
				foreach($result->UnavailableHoldItems->UnavailableHoldItem as $hold) {
					$curHold = array();
					$bibId          = $hold->BID;
					$expireDate     = isset($hold->ExpirationDate) ? $this->extractDateFromCarlXDateField($hold->ExpirationDate) : null;
					$pickUpBranch   = $this->getBranchInformation($hold->PickUpBranch);
//					$location       = $this->getLocationInformation($hold->Location); // IDK what this is referring to yet, or if it is needed

//						$reactivateDate = $this->extractDateFromCarlXDateField($hold) //TODO: activation date? unavailable holds only
//					$curHold['user']               = $user->getNameAndLibraryLabel(); // Done in CatalogConnection
					$curHold['id']                 = $bibId;
					$curHold['holdSource']         = 'ILS';
					$curHold['itemId']             = $hold->ItemNumber;
//						$curHold['cancelId']           = (string)$hold->holdKey; //TODO: Determine Cancellation Method
					$curHold['position']           = $hold->QueuePosition;
					$curHold['recordId']           = $bibId;
					$curHold['shortId']            = $bibId;
					$curHold['title']              = $hold->Title;
					$curHold['sortTitle']          = $hold->Title;
					$curHold['author']             = $hold->Author;
					$curHold['location']           = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
					$curHold['locationUpdateable'] = true; //TODO: unless status is in transit?
					$curHold['currentPickupName']  = empty($pickUpBranch->BranchName) ? '' : $pickUpBranch->BranchName;
//					$curHold['status']             = ucfirst(strtolower((string)$hold->status));
					$curHold['status']             = $this->holdStatusCodes[$hold->ItemStatus];  // TODO: Is this the correct thing for hold status. Alternative is Transaction Code
					//TODO: Look up values for Hold Statuses

					$curHold['expire']         = strtotime($expireDate);
//						$curHold['reactivate']         = $reactivateDate; //TODO unavailable only
//						$curHold['reactivateTime']     = strtotime($reactivateDate); //TODO unavailable only
					$curHold['cancelable']         = strcasecmp($curHold['status'], 'Suspended') != 0; //TODO: need frozen status
//					$curHold['frozen']             = strcasecmp($curHold['status'], 'Suspended') == 0; //TODO: need frozen status
					$curHold['frozen']             = false;
//					if ($curHold['frozen']){  //TODO Can CarlX holds be frozen?
//						$curHold['reactivateTime']   = (int)$hold->reactivateDate;
//					}
					$curHold['freezeable'] = false;
//					$curHold['freezeable'] = true; //TODO Can CarlX holds be frozen?
//					if (strcasecmp($curHold['status'], 'Transit') == 0) {
//						$curHold['freezeable'] = false;
//					}

					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($bibId);
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
	public function placeHold($patron, $recordId, $pickupBranch) {
		// TODO: Implement placeHold() method.
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
		// TODO: Implement cancelHold() method.
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate) {
		// TODO: Implement freezeHold() method.
	}

	function thawHold($patron, $recordId, $itemToThawId) {
		// TODO: Implement thawHold() method.
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation) {
		// TODO: Implement changeHoldPickupLocation() method.
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
	public function getMyCheckouts($user) {
		$checkedOutTitles = array();

		// TODO: Implement getMyCheckouts() method.
		//Search for the patron in the database
		$result = $this->getParonTransactions($user);

		if ($result && !empty($result->ChargeItems)) {
			foreach ($result->ChargeItems->ChargeItem as $chargeItem) {
				//TODO: BID may be the bib number and may be needed for recordID, shortID, & ID instead of ItemNumber, which may be the barcode instead.
				$curTitle['checkoutSource']  = 'ILS';
				$curTitle['recordId']        = $chargeItem->ItemNumber;
				$curTitle['shortId']         = $chargeItem->ItemNumber;
				$curTitle['id']              = $chargeItem->ItemNumber;
				$curTitle['title']           = $chargeItem->Title; //TODO: trim trailing slashes?
				$curTitle['author']          = $chargeItem->Author;
				$dueDate = strstr($chargeItem->DueDate, 'T', true);
				$curTitle['dueDate']         = strtotime($dueDate);
				$curTitle['checkoutdate']    = strstr($chargeItem->TransactionDate, 'T', true);
				$curTitle['renewCount']      = $chargeItem->RenewalCount;
				$curTitle['canrenew']        = true; //TODO: Figure out if the user can renew the title or not
				$curTitle['renewIndicator']  = '';   //TODO: needed? Maybe a Millennium only field
				$curTitle['barcode']         = '';   //TODO: needed?
				$curTitle['holdQueueLength'] = $this->getNumHolds($chargeItem->ItemNumber);  //TODO: implement getNumHolds()

				$curTitle['format']          = 'Unknown';
				if (!empty($curTitle['id'])){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($curTitle['id']);
					if ($recordDriver->isValid()){
						$curTitle['coverUrl']      = $recordDriver->getBookcoverUrl('medium');
						$curTitle['groupedWorkId'] = $recordDriver->getGroupedWorkId();
						$curTitle['format']        = $recordDriver->getPrimaryFormat();
						if (empty($curTitle['title'])){
							$curTitle['title']       = $recordDriver->getTitle();
							$curTitle['title_sort']  = $recordDriver->getSortableTitle();
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
			//TODO: Log error
		}

	return $checkedOutTitles;
	}

	public function updatePatronInfo($user, $canUpdateContactInfo) {
		$updateErrors = array();
		if ($canUpdateContactInfo){
			$soapClient = new SoapClient($this->patronWsdl
				,array(
					'features' => SOAP_WAIT_ONE_WAY_CALLS, // This setting overcomes the SOAP client's expectation that there is no response from our update request.
					'trace' => 1, // enable use of __getLastResponse, so that we can determine the response.
//					'exceptions' => 0,
				)
			);

//			$soapClient = new SoapClientDebug($this->patronWsdl
//				,array(
//					'trace' => 1
//			);

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
				$noticeLabels = array(
					//'-' => 'Mail',  // officially None in Sierra, as in No Preference Selected.
					'-' => '',        // notification will generally be based on what information is available so can't determine here. plb 12-02-2014
					'a' => 'Mail',    // officially Print in Sierra
					'p' => 'Telephone',
					'z' => 'E-mail',
				);

				if ($_REQUEST['notices'] == 'z') {
					$request->Patron->EmailReceiptFlag = true;
				} else {
					//TODO: Set when phone preference is used
					$request->Patron->EmailReceiptFlag = false;
				}

			}

			$result = $soapClient->updatePatron($request);

			if (is_null($result)) {
				$result = $soapClient->__getLastResponse();
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

	public function getReadingHistory($user, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		global $timer;

		$readHistoryEnabled = true; //TODO: do check for enabled

		if ($readHistoryEnabled) { // Create Reading History Request
			$historyActive = true;
			$readingHistoryTitles = array();
			$numTitles = 0;

			$soapClient = new SoapClient($this->patronWsdl);

			$request              = $this->getSearchbyPatronIdRequest($user);
			$request->HistoryType = 'L';

			$result = $soapClient->getPatronChargeHistory($request);

			if ($result) {
				// Process Reading History Response

				// Fetch Additional Information for each Item

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

	}

	/**
	 * @param $user
	 * @return mixed
	 */
	private function getParonTransactions($user)
	{
		$soapClient = new SoapClient($this->patronWsdl);

		$request = $this->getSearchbyPatronIdRequest($user);

		$result = $soapClient->getPatronTransactions($request);
		return $result;
	}

	private function getPhoneTypeList()
	{
		$soapClient = new SoapClient($this->patronWsdl);

		$request             = new stdClass();
		$request->Modifiers  = '';

		$result = $soapClient->getPhoneTypeList($request);
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
		$soapClient = new SoapClient(($this->catalogWsdl));

		$request = new stdClass();
		$request->LocationSearchType = 'Location Number';
		$request->LocationSearchValue = $locationNumber;
		$request->Modifiers  = '';

		$result = $soapClient->GetLocationInformation($request);
		if ($result && $result->LocationInfo) {
			return $result->LocationInfo; // convert to array instead?
		}
		return false;

	}

	private function getBranchInformation($branchNumber)
	{
		$soapClient = new SoapClient(($this->catalogWsdl));

		$request                    = new stdClass();
		$request->BranchSearchType  = 'Branch Number';
		$request->BranchSearchValue = $branchNumber;
		$request->Modifiers         = '';

		$result = $soapClient->GetBranchInformation($request);
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

}

//class SoapClientDebug extends SoapClient
//{
//	public function __doRequest($request, $location, $action, $version, $one_way = NULL)
//	{
////		file_put_contents('soap_log.txt', $request);
//
//		return parent::__doRequest($request, $location, $action, $version, $one_way);
//	}
//}