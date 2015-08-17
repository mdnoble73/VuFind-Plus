<?php
/**
 * Integration with Library.Solution for Schools
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/20/2015
 * Time: 2:17 PM
 */

require_once ROOT_DIR . '/Drivers/ScreenScrapingDriver.php';
class LibrarySolution extends ScreenScrapingDriver {
	/** @var  AccountProfile $accountProfile */
	public $accountProfile;

	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile){
		$this->accountProfile = $accountProfile;
	}

	/**
	 * Loads items information as quickly as possible (no direct calls to the ILS)
	 *
	 * return is an array of items with the following information:
	 *  callnumber
	 *  available
	 *  holdable
	 *  lastStatusCheck (time)
	 *
	 * @param $id
	 * @param $scopingEnabled
	 * @return mixed
	 */
	public function getItemsFast($id, $scopingEnabled) {
		// TODO: Implement getItemsFast() method.
	}

	public function getStatus($id) {
		return $this->getHolding($id);
	}

	public function getStatuses($ids) {
		return $this->getHoldings($ids);
	}

	/**
	 * Returns a summary of the holdings information for a single id. Used to display
	 * within the search results and at the top of a full record display to ensure
	 * the holding information makes sense to all users.
	 *
	 * @param string $id the id of the bid to load holdings for
	 * @return array an associative array with a summary of the holdings.
	 */
	public function getStatusSummary($id, $record = null, $mysip = null) {
		global $timer;
		global $library;
		global $locationSingleton;
		//Holdings summaries need to be cached based on the actual location since part of the information
		//includes local call numbers and statuses.
		$ipLocation = $locationSingleton->getPhysicalLocation();
		$location = $ipLocation;
		if (!isset($location) && $location == null) {
			$location = $locationSingleton->getUserHomeLocation();
		}
		$ipLibrary = null;
		if (isset($ipLocation)) {
			$ipLibrary = new Library();
			$ipLibrary->libraryId = $ipLocation->libraryId;
			if (!$ipLibrary->find(true)) {
				$ipLibrary = null;
			}
		}

		$canShowHoldButton = true;
		if ($library && $library->showHoldButton == 0) {
			$canShowHoldButton = false;
		}
		if ($location != null && $location->showHoldButton == 0) {
			$canShowHoldButton = false;
		}

		$holdings = $this->getStatus($id, $record, $mysip, true);
		$timer->logTime('Retrieved Status of holding');

		$summaryInformation = array();
		$summaryInformation['recordId'] = $id;
		$summaryInformation['shortId'] = $id;
		$summaryInformation['isDownloadable'] = false; //Default value, reset later if needed.
		$summaryInformation['holdQueueLength'] = 0;

		//Valid statuses are:
		//Available by Request
		//  - not at the user's home branch or preferred location, but at least one copy is not checked out
		//  - do not show the call number
		//  - show place hold button
		//Checked Out
		//  - all copies are checked out
		//  - show the call number for the local library if any
		//  - show place hold button
		//Downloadable
		//  - there is at least one download link for the record.
		$numAvailableCopies = 0;
		$numHoldableCopies = 0;
		$numCopies = 0;
		$numCopiesOnOrder = 0;
		$availableLocations = array();
		$unavailableStatus = null;
		//The status of all items.  Will be set to an actual status if all are the same
		//or null if the item statuses are inconsistent
		$allItemStatus = '';
		$firstAvailableBarcode = '';
		$availableHere = false;
		foreach ($holdings as $holdingKey => $holding) {
			if (is_null($allItemStatus)) {
				//Do nothing, the status is not distinct
			} else {
				if ($allItemStatus == '') {
					$allItemStatus = $holding['statusfull'];
				} elseif ($allItemStatus != $holding['statusfull']) {
					$allItemStatus = null;
				}
			}
			if ($holding['availability'] == true) {
				if ($ipLocation && strcasecmp($holding['locationCode'], $ipLocation->code) == 0) {
					$availableHere = true;
				}
				$numAvailableCopies++;
			} else {
				if ($unavailableStatus == null) {
					$unavailableStatus = $holding['statusfull'];
				}
			}

			if (isset($holding['holdable']) && $holding['holdable'] == 1) {
				$numHoldableCopies++;
			}
			$numCopies++;
			//Only show a call number if the book is at the user's home library, one of their preferred libraries, or in the library they are in.
			if (!isset($summaryInformation['callnumber'])) {
				$summaryInformation['callnumber'] = $holding['callnumber'];
			}
			if ($holding['availability'] == 1) {
				//The item is available within the physical library.  Patron should go get it off the shelf
				$summaryInformation['status'] = "Available At";
				if ($numHoldableCopies > 0) {
					$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				} else {
					$summaryInformation['showPlaceHold'] = 0;
				}
				$summaryInformation['class'] = 'available';
			}
			if ($holding['holdQueueLength'] > $summaryInformation['holdQueueLength']) {
				$summaryInformation['holdQueueLength'] = $holding['holdQueueLength'];
			}
			if ($firstAvailableBarcode == '' && $holding['availability'] == true) {
				$firstAvailableBarcode = $holding['barcode'];
			}
		}
		$timer->logTime('Processed copies');

		//If all items are checked out the status will still be blank
		$summaryInformation['availableCopies'] = $numAvailableCopies;
		$summaryInformation['holdableCopies'] = $numHoldableCopies;

		$summaryInformation['numCopiesOnOrder'] = $numCopiesOnOrder;
		//Do some basic sanity checking to make sure that we show the total copies
		//With at least as many copies as the number of copies on order.
		if ($numCopies < $numCopiesOnOrder) {
			$summaryInformation['numCopies'] = $numCopiesOnOrder;
		} else {
			$summaryInformation['numCopies'] = $numCopies;
		}

		$showItsHere = ($ipLibrary == null) ? true : ($ipLibrary->showItsHere == 1);
		if ($availableHere && $showItsHere) {
			$summaryInformation['status'] = "It's Here";
			$summaryInformation['class'] = 'here';
			unset($availableLocations[$location->code]);
			$summaryInformation['currentLocation'] = $location->displayName;
			$summaryInformation['availableAt'] = join(', ', $availableLocations);
			$summaryInformation['numAvailableOther'] = count($availableLocations);
		} else {
			//Replace all spaces in the name of a location with no break spaces
			$summaryInformation['availableAt'] = join(', ', $availableLocations);
			$summaryInformation['numAvailableOther'] = count($availableLocations);
		}

		//If Status is still not set, apply some logic based on number of copies
		if (!isset($summaryInformation['status'])) {
			if ($numCopies == 0) {
				if ($numCopiesOnOrder > 0) {
					//No copies are currently available, but we do have some that are on order.
					//show the status as on order and make it available.
					$summaryInformation['status'] = "On Order";
					$summaryInformation['class'] = 'available';
					$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				} else {
					//Deal with weird cases where there are no items by saying it is unavailable
					$summaryInformation['status'] = "Unavailable";
					$summaryInformation['showPlaceHold'] = false;
					$summaryInformation['class'] = 'unavailable';
				}
			} else {
				if ($numHoldableCopies == 0 && $canShowHoldButton && (isset($summaryInformation['showPlaceHold']) && $summaryInformation['showPlaceHold'] != true)) {
					$summaryInformation['status'] = "Not Available For Checkout";
					$summaryInformation['showPlaceHold'] = false;
					$summaryInformation['class'] = 'reserve';
				} else {
					$summaryInformation['status'] = "Checked Out";
					$summaryInformation['showPlaceHold'] = $canShowHoldButton;
					$summaryInformation['class'] = 'checkedOut';
				}
			}
		}

		//Reset status if the status for all items is consistent.
		//That way it will jive with the actual full record display.
		if ($allItemStatus != null && $allItemStatus != '') {
			//Only override this for statuses that don't have special meaning
			if ($summaryInformation['status'] != 'Marmot' && $summaryInformation['status'] != 'Available At' && $summaryInformation['status'] != "It's Here") {
				$summaryInformation['status'] = $allItemStatus;
			}
		}
		if ($allItemStatus == 'In Library Use Only') {
			$summaryInformation['inLibraryUseOnly'] = true;
		} else {
			$summaryInformation['inLibraryUseOnly'] = false;
		}


		if ($summaryInformation['availableCopies'] == 0 && $summaryInformation['isDownloadable'] == true) {
			$summaryInformation['showAvailabilityLine'] = false;
		} else {
			$summaryInformation['showAvailabilityLine'] = true;
		}
		$timer->logTime('Finished building summary');

		return $summaryInformation;
	}

	private static $loadedHoldings = array();
	public function getHolding($id) {
		if (array_key_exists($id, LibrarySolution::$loadedHoldings)){
			return LibrarySolution::$loadedHoldings[$id];
		}

		global $library;
		global $searchLocation;
		$searchLocation = Location::getSearchLocation();
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

		list($recordType, $shortId) = explode(':', $id);
		//Call LSS to get details about the record
		$url = $this->getVendorOpacUrl() . '/resource/byHostRecordId/' . $shortId;
		$recordInfoRaw = $this->_curlGetPage($url);
		$recordInfo = json_decode($recordInfoRaw);
		$holdings = array();
		if ($recordInfo){
			$resourceId = $recordInfo->id;

			$postData = array();
			foreach ($recordInfo->holdingsInformations as $holdingInfo){
				$postData[] = array(
					'itemIdentifier' => $holdingInfo->barcode,
					'resourceId' => $resourceId
				);
			}

			//Get updated availability
			$availabilityUrl = $this->getVendorOpacUrl() . '/availability';
			$availabilityResponseRaw = $this->_curlPostBodyData($availabilityUrl, $postData);
			$availabilityResponse = json_decode($availabilityResponseRaw);

			//Get the active indexing profile
			global $indexingProfiles;
			if (array_key_exists($recordType, $indexingProfiles)){
				$locationsToInclude = '^';
				$indexingProfile = $indexingProfiles[$recordType];
				$numRulesAdded = 0;
				//Determine what records to include based on ownership and inclusion rules for search location or library
				if ($searchLocation){
					//Load from our search library
					/** @var LocationRecordOwned $ownershipRule */
					foreach ($searchLocation->recordsOwned as $ownershipRule) {
						if ($ownershipRule->indexingProfileId == $indexingProfile->id) {
							if ($numRulesAdded > 0){
								$locationsToInclude .= '|';
							}
							$locationsToInclude .= '(' . $ownershipRule->location . ')';
							$numRulesAdded++;
						}
					}
					/** @var LocationRecordToInclude $inclusionRule */
					foreach ($searchLocation->recordsToInclude as $inclusionRule) {
						if ($inclusionRule->indexingProfileId == $indexingProfile->id) {
							if ($numRulesAdded > 0){
								$locationsToInclude .= '|';
							}
							$locationsToInclude .= '(' . $ownershipRule->location . ')';
							$numRulesAdded++;
						}
					}
				}else {
					//Load from the library
					/** @var LibraryRecordOwned $ownershipRule */
					foreach ($library->recordsOwned as $ownershipRule) {
						if ($ownershipRule->indexingProfileId == $indexingProfile->id) {
							if ($numRulesAdded > 0){
								$locationsToInclude .= '|';
							}
							$locationsToInclude .= '(' . $ownershipRule->location . ')';
						}
					}
					/** @var LibraryRecordToInclude $inclusionRule */
					foreach ($library->recordsToInclude as $inclusionRule) {
						if ($inclusionRule->indexingProfileId == $indexingProfile->id) {
							if ($numRulesAdded > 0){
								$locationsToInclude .= '|';
							}
							$locationsToInclude .= '(' . $ownershipRule->location . ')';
						}
					}
				}
				$locationsToInclude .= '$';
			}else{
				$locationsToInclude = ".*";
			}

			$i=0;
			foreach ($recordInfo->holdingsInformations as $holdingInfo){
				//Scope holdings for LSS based on information loaded from the indexing profile
				if (!preg_match("/{$locationsToInclude}/i", $holdingInfo->branchIdentifier)){
					continue;
				}

				$shelfLocation = $holdingInfo->branchName;
				if (isset($holdingInfo->collection)){
					$shelfLocation .= ' ' . $holdingInfo->collection;
				}
				$holding = array(
					'id' => $holdingInfo->barcode,
					'number' => $i,
					'type' => 'holding',
					'status' => '',
					'statusfull' => '',
					'availability' => false,
					'holdable' => true,
					'reserve' => $holdingInfo->reserved ? 'Y' : 'N',
					'holdQueueLength' => '',
					'dueDate' => '',
					'locationCode' => $holdingInfo->branchIdentifier,
					'location' => $holdingInfo->branchName,
					'callnumber' => trim($holdingInfo->callPrefix . ' ' . $holdingInfo->callClass . ' ' . $holdingInfo->callCutter),
					'isDownload' => false,
					'barcode' => $holdingInfo->barcode,
					'isLocalItem' => false,
					'isLibraryItem' => true,
					'locationLabel' => $shelfLocation,
					'shelfLocation' => $shelfLocation,
				);

				//Get that status
				foreach ($availabilityResponse->itemAvailabilities as $availability){
					if ($availability->itemIdentifier == $holdingInfo->barcode){
						$holding['status'] = $availability->statusCode;
						$holding['statusfull'] = $availability->status;
						$holding['availability'] = $availability->available;
						$holding['dueDate'] = $availability->dueDateString;
						$holding['holdable'] = !$availability->nonCirculating;
					}
				}

				$holding['groupedStatus'] = mapValue('item_grouped_status', $holding['status']);

				$paddedNumber = str_pad($i, 3, '0', STR_PAD_LEFT);
				$sortString = $holding['location'] . '-'. $paddedNumber;
				//$sortString = $holding['location'] . $holding['callnumber']. $i;
				if (strlen($physicalBranch) > 0 && stripos($holding['location'], $physicalBranch) !== false){
					//If the user is in a branch, those holdings come first.
					$holding['section'] = 'In this library';
					$holding['sectionId'] = 1;
					$holding['isLocalItem'] = true;
					$sorted_array['1' . $sortString] = $holding;
				} else if (strlen($homeBranch) > 0 && stripos($holding['location'], $homeBranch) !== false){
					//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
					$holding['section'] = 'Your library';
					$holding['sectionId'] = 2;
					$holding['isLocalItem'] = true;
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
						*/
				} else {
					//Finally, all other holdings are shown sorted alphabetically.
					$holding['section'] = $library->displayName;
					$holding['sectionId'] = 5;
					$sorted_array['5' . $sortString] = $holding;
				}

				$holdings[] = $holding;
			}
		}

		return $holdings;
	}

	public function getHoldings($ids) {
		$holdings = array();
		foreach ($ids as $id) {
			$holdings[] = $this->getHolding($id);
		}
		return $holdings;
	}

	/**
	 * Patron Login
	 *
	 * This is responsible for authenticating a patron against the catalog.
	 * Interface defined in CatalogConnection.php
	 *
	 * @param   string  $username   The patron username
	 * @param   string  $password   The patron password
	 * @return  User|null           A string of the user's ID number
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	public function patronLogin($username, $password) {
		global $timer;

		//Post enter barcode and pin to the login screen
		$loginSucceeded = $this->loginPatronToLSS($username, $password);
		if ($loginSucceeded){
			//Get the account summary
			$url = $this->getVendorOpacUrl() . '/account/summary?_=' . time() * 1000;
			$accountSummaryRaw = $this->_curlGetPage($url);
			$accountSummary = json_decode($accountSummaryRaw);

			$userExistsInDB = false;
			$user = new User();
			$user->username = $accountSummary->patron->guid;
			$user->source = $this->accountProfile->name;
			if ($user->find(true)){
				$userExistsInDB = true;
			}

			$user->password = $accountSummary->patron->pin;

			$user->firstname = $accountSummary->patron->firstName;
			$user->lastname = $accountSummary->patron->lastName;
			$user->fullname = $accountSummary->patron->fullName;
			$user->cat_username = $accountSummary->patron->patronId;
			$user->cat_password = $accountSummary->patron->pin;
			$user->phone = $accountSummary->patron->phone;
			$user->email = $accountSummary->patron->email;

			if (empty($user->displayName)) {
				if (strlen($user->firstname) >= 1) {
					$user->displayName = substr($user->firstname, 0, 1) . '. ' . $user->lastname;
				} else {
					$user->displayName = $user->lastname;
				}
			}

			//Setup home location
			$location = null;
			if (isset($accountSummary->patron->issuingBranchId) || isset($accountSummary->patron->defaultRequestPickupBranch)){
				$homeBranchCode = isset($accountSummary->patron->issuingBranchId) ? $accountSummary->patron->issuingBranchId : $accountSummary->patron->defaultRequestPickupBranch;
				$homeBranchCode = str_replace('+', '', $homeBranchCode);
				//Translate home branch to plain text
				$location = new Location();
				$location->code = $homeBranchCode;
				if ($location->find(1)){
					//Setup default location information if it hasn't been loaded or has been changed
					if ($user->homeLocationId == 0 || $location->locationId != $user->homeLocationId) {
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
					}
					//Get display names that aren't stored
					$user->homeLocationCode = $location->code;
					$user->homeLocation = $location->displayName;

					//Get display name for preferred location 1
					$myLocation1 = new Location();
					$myLocation1->whereAdd("locationId = '$user->myLocation1Id'");
					if ($myLocation1->find(true)){
						$user->myLocation1 = $myLocation1->displayName;
					}

					//Get display name for preferred location 2
					$myLocation2 = new Location();
					$myLocation2->whereAdd("locationId = '$user->myLocation2Id'");
					if ($myLocation2->find(true)){
						$user->myLocation2 = $myLocation2->displayName;
					}
				}else{
					unset($location);
				}
			}

			$user->expires = $accountSummary->patron->cardExpirationDate;
			list ($yearExp, $monthExp, $dayExp) = explode("-",$user->expires);
			$timeExpire = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
			$timeNow = time();
			$timeToExpire = $timeExpire - $timeNow;
			$user->expired = 0;
			if ($timeToExpire <= 30 * 24 * 60 * 60){
				if ($timeToExpire <= 0){
					$user->expired = 1;
				}
				$user->expireClose = 1;
			}else{
				$user->expireClose = 0;
			}

			$user->address1 = $accountSummary->patron->address1;
			$user->city = $accountSummary->patron->city;
			$user->state = $accountSummary->patron->state;
			$user->zip = $accountSummary->patron->zipcode;

			$user->fines = $accountSummary->patron->fees / 100;
			$user->finesVal = floatval(preg_replace('/[^\\d.]/', '', $user->fines / 100));

			$user->numCheckedOutIls = $accountSummary->accountSummary->loanCount;
			$user->numHoldsAvailableIls = $accountSummary->accountSummary->arrivedHolds;
			$user->numHoldsRequestedIls = $accountSummary->accountSummary->pendingHolds;
			$user->numHoldsIls = $user->numHoldsAvailableIls + $user->numHoldsRequestedIls;

			if ($userExistsInDB){
				$user->update();
			}else{
				$user->created = date('Y-m-d');
				$user->insert();
			}

			$timer->logTime("patron logged in successfully");
			return $user;
		}else{
			$info = curl_getinfo($this->curl_connection);
			$timer->logTime("patron login failed");
			return null;
		}
	}

	public function hasNativeReadingHistory() {
		return true;
	}

	/**
	 * @param User $patron
	 * @param int $page
	 * @param int $recordsPerPage
	 * @param string $sortOption
	 * @return array
	 */
	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		$readingHistory = array();
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)){
			//Load transactions from LSS
			//TODO: Verify that this will load more than 20 loans
			$url = $this->getVendorOpacUrl() . '/loans/history/0/20/OutDate?_=' . time() * 1000;
			$loanInfoRaw = $this->_curlGetPage($url);
			$loanInfo = json_decode($loanInfoRaw);

			foreach ($loanInfo->loanHistory as $loan){
				$curTitle = array();
				$curTitle['itemId'] = $loan->itemId;
				$curTitle['id'] = $loan->bibliographicId;
				$curTitle['shortId'] = $loan->bibliographicId;
				$curTitle['recordId'] = $loan->bibliographicId;
				$curTitle['title'] = utf8_encode($loan->title);
				$curTitle['author'] = utf8_encode($loan->author);
				$dueDate = $loan->dueDate;
				$curTitle['dueDate'] = $dueDate;        // item likely will not have a dueDate, (get null value)
				$curTitle['checkout'] = $loan->outDateString; // item always has a outDateString
				$curTitle['borrower_num'] = $patron->id;
				$curTitle['title_sort'] = preg_replace('/[^a-z\s]/', '', strtolower($curTitle['title']));

				//Get additional information from MARC Record
				if ($curTitle['shortId'] && strlen($curTitle['shortId']) > 0){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord( $this->accountProfile->recordSource . ":" . $curTitle['recordId']);
					if ($recordDriver->isValid()){
						$historyEntry['permanentId'] = $recordDriver->getPermanentId();
						$curTitle['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
						$curTitle['groupedWorkId'] = $recordDriver->getGroupedWorkId();
						$curTitle['ratingData'] = $recordDriver->getRatingData();
						$formats = $recordDriver->getFormats();
						$curTitle['format'] = reset($formats);
						$curTitle['author'] = $recordDriver->getPrimaryAuthor();
						if (!isset($curTitle['title']) || empty($curTitle['title'])){
							$curTitle['title'] = $recordDriver->getTitle();
						}
					}else{
						$historyEntry['permanentId'] = null;
						$curTitle['coverUrl'] = "";
						$curTitle['groupedWorkId'] = "";
						$curTitle['format'] = "Unknown";
						$curTitle['author'] = ""; //TODO Why does author get nulled out.  (Library Solutions does give an author)
					}
					$curTitle['linkUrl'] = $recordDriver->getLinkUrl();
				}

				$readingHistory[] = $curTitle;
			}
		}

		//LSS does not have a way to disable reading history so we will always set to true.
		if (!$patron->trackReadingHistory){
			$patron->trackReadingHistory = 1;
			$patron->update();
		}


		return array('historyActive'=>true, 'titles'=>$readingHistory, 'numTitles'=> count($readingHistory));
	}

	public function getNumHolds($id) {
		// TODO: Implement getNumHolds() method.
	}

	protected function getCustomHeaders() {
		return array(
			'Host: tlcweb01.mnps.org:8080',
			'User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0',
			'Accept: application/json, text/javascript, */*; q=0.01',
			'Accept-Language: en-US,en;q=0.5',
			'Accept-Encoding: gzip, deflate',
			'Content-Type: application/json; charset=utf-8',
			'Ls2pac-config-type: pac',
			'Ls2pac-config-name: ysm',
			'X-Requested-With: XMLHttpRequest',
			'Connection: keep-alive',
			'Pragma: no-cache',
			'Cache-Control: no-cache',
		);
	}

	/**
	 * Get Patron Transactions
	 *
	 * This is responsible for retrieving all transactions (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $user    The user to load transactions for
	 *
	 * @return mixed        Array of the patron's transactions on success,
	 * PEAR_Error otherwise.
	 * @access public
	 */
	public function getMyCheckouts($user){
		$transactions = array();
		if ($this->loginPatronToLSS($user->cat_username, $user->cat_password)){
			//Load transactions from LSS
			//TODO: Verify that this will load more than 20 loans
			$url = $this->getVendorOpacUrl() . '/loans/0/20/Status?_=' . time() * 1000;
			$loanInfoRaw = $this->_curlGetPage($url);
			$loanInfo = json_decode($loanInfoRaw);

			foreach ($loanInfo->loans as $loan){
				$curTitle = array();
				$curTitle['checkoutSource'] = 'ILS';
				$curTitle['itemId'] = $loan->itemId;
				$curTitle['renewIndicator'] = $loan->itemId;
				$curTitle['id'] = $loan->bibliographicId;
				$curTitle['shortId'] = $loan->bibliographicId;
				$curTitle['recordId'] = $loan->bibliographicId;
				$curTitle['title'] = utf8_encode($loan->title);
				$curTitle['author'] = utf8_encode($loan->author);
				$dueDate = $loan->dueDate;
				if ($dueDate){
					$dueDate = strtotime($dueDate);
				}
				$curTitle['dueDate'] = $dueDate;
				/*$curTitle['renewCount']
				$curTitle['barcode']
				$curTitle['canrenew']
				$curTitle['itemindex']
				$curTitle['renewIndicator']
				$curTitle['renewMessage']*/

				//Get additional information from MARC Record
				if ($curTitle['shortId'] && strlen($curTitle['shortId']) > 0){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord( $this->accountProfile->recordSource . ":" . $curTitle['recordId']);
					if ($recordDriver->isValid()){
						$curTitle['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
						$curTitle['groupedWorkId'] = $recordDriver->getGroupedWorkId();
						$curTitle['ratingData'] = $recordDriver->getRatingData();
						$formats = $recordDriver->getFormats();
						$curTitle['format'] = reset($formats);
						$curTitle['author'] = $recordDriver->getPrimaryAuthor();
						if (!isset($curTitle['title']) || empty($curTitle['title'])){
							$curTitle['title'] = $recordDriver->getTitle();
						}
					}else{
						$curTitle['coverUrl'] = "";
						$curTitle['groupedWorkId'] = "";
						$curTitle['format'] = "Unknown";
						$curTitle['author'] = "";
					}
					$curTitle['link'] = $recordDriver->getLinkUrl();
				}

				$transactions[] = $curTitle;
			}
		}

		return $transactions;
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

	public function isAuthenticated(){
		$url = $this->getVendorOpacUrl() . '/isAuthenticated?_=' . time() * 1000;
		$result = $this->_curlGetPage($url);
		return $result == 'true';
	}

	public function renewItem($patron, $recordId, $itemId, $itemIndex){
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => "Sorry, we were unable to renew your checkout.");
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			//$isAuthenticated = $this->isAuthenticated();
			$url = $this->getVendorOpacUrl() . '/loans/renew?_=' . time() * 1000;
			$postParams = '{"renewLoanInfos":"[{\"success\":false,\"itemId\":\"' . $itemId . '\",\"date\":' . (time() * 1000) . ',\"downloadable\":false}]"}';
			//$this->setupDebugging();
			$renewItemResponseRaw = $this->_curlPostBodyData($url, $postParams, false);
			$renewItemResponse = json_decode($renewItemResponseRaw);
			if ($renewItemResponse == null){
				//We didn't get valid JSON back
				$result['message'] = "We could not renew your item.  Received an invalid response from the server.";
			}else{
				foreach ($renewItemResponse->renewLoanInfos as $renewInfo){
					if ($renewInfo->success){
						$result['success'] = 'true';
						$result['message'] = "Your item was renewed successfully.  It is now due {$renewInfo->dateString}.";
					}else{
						$result['message'] = "Your item could not be renewed online.";
					}
				}
			}
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
		return $result;
	}

	/**
	 * @param $username
	 * @param $password
	 * @return array
	 */
	protected function loginPatronToLSS($username, $password) {
		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		$url = $this->getVendorOpacUrl() . '/login?rememberMe=false&_=' . time() * 1000;
		$postParams = array(
			'password' => $password,
			'pin' => $password,
			'rememberMe' => 'false',
			'username' => $username,
		);
		$loginResponse = $this->_curlPostBodyData($url, $postParams);
		if (strlen($loginResponse) > 0){
			$decodedResponse = json_decode($loginResponse);
			if ($decodedResponse){
				$loginSucceeded = $decodedResponse->success == 'true';
			}else{
				$loginSucceeded = false;
			}
		}else{
			global $logger;
			$logger->log("Unable to connect to LSS.  Received $loginResponse", PEAR_LOG_WARNING);
			$loginSucceeded = false;
		}

		return $loginSucceeded;
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $user      The user to load transactions for
	 *
	 * @return array          Array of the patron's holds
	 * @access public
	 */
	public function getMyHolds($user){
		$holds = array(
			'available' => array(),
			'unavailable' => array()
		);

		if ($this->loginPatronToLSS($user->cat_username, $user->cat_password)) {
			//Load transactions from LSS
			//TODO: Verify that this will load more than 20 loans
			$url = $this->getVendorOpacUrl() . '/requests/0/20/Status?_=' . time() * 1000;
			$holdInfoRaw = $this->_curlGetPage($url);
			$holdInfo = json_decode($holdInfoRaw);

			$indexingProfile = new IndexingProfile();
			$indexingProfile->name = $this->accountProfile->recordSource;
			if (!$indexingProfile->find(true)){
				$indexingProfile = null;
			}
			foreach ($holdInfo->holds as $hold){
				$curHold= array();
				$bibId = $hold->bibliographicId;
				$curHold['id'] = $bibId;
				$curHold['holdSource'] = 'ILS';
				$curHold['itemId'] = $hold->itemId;
				$curHold['cancelId'] = $hold->holdNumber;
				$curHold['position'] = $hold->holdQueueLength;
				$curHold['recordId'] = $bibId;
				$curHold['shortId'] = $bibId;
				$curHold['title'] = $hold->title;
				$curHold['author'] = $hold->author;
				$curHold['locationId'] = $hold->holdPickupBranchId;
				$curPickupBranch = new Location();
				$curPickupBranch->code = $hold->holdPickupBranchId;
				if ($curPickupBranch->find(true)) {
					$curHold['currentPickupId'] = $curPickupBranch->locationId;
					$curHold['currentPickupName'] = $curPickupBranch->displayName;
					$curHold['location'] = $curPickupBranch->displayName;
				}
				//$curHold['locationId'] = $matches[1];
				$curHold['locationUpdateable'] = false;
				$curHold['currentPickupName'] = $hold->holdPickupBranch;

				if ($indexingProfile){
					$curHold['status'] = $indexingProfile->translate('item_status', $hold->status);
				}else{
					$curHold['status'] = $hold->status;
				}

				//$expireDate = (string)$hold->expireDate;
				//$curHold['expire'] = $expireDate;
				//$curHold['expireTime'] = strtotime($expireDate);
				$curHold['reactivate'] = $hold->suspendUntilDateString;

				//MDN - it looks like holdCancelable is not accurate, setting to true always
				//$curHold['cancelable'] = $hold->holdCancelable;
				$curHold['cancelable'] = true;
				$curHold['frozen'] = $hold->suspendUntilDate != null;
				if ($curHold['frozen']){
					$curHold['reactivateTime'] = $hold->suspendUntilDate;
				}
				//Although LSS interface shows this is possible, we haven't been able to make it work in the
				//LSS OPAC, setting to false always
				//$curHold['freezeable'] = $hold->holdSuspendable;
				$curHold['freezeable'] = false;

				$curHold['sortTitle'] = $hold->title;
				require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
				$recordDriver = new MarcRecord($this->accountProfile->recordSource . ":" . $bibId);
				if ($recordDriver->isValid()){
					$curHold['sortTitle'] = $recordDriver->getSortableTitle();
					$curHold['format'] = $recordDriver->getFormat();
					$curHold['isbn'] = $recordDriver->getCleanISBN();
					$curHold['upc'] = $recordDriver->getCleanUPC();
					$curHold['format_category'] = $recordDriver->getFormatCategory();
					$curHold['coverUrl'] = $recordDriver->getBookcoverUrl('medium');

					//Load rating information
					$curHold['ratingData'] = $recordDriver->getRatingData();
				}
				$curHold['link'] = $recordDriver->getLinkUrl();
				$curHold['user'] = $user->getNameAndLibraryLabel();

				//TODO: Determine the status of available holds
				if (!isset($hold->status) || $hold->status == 'PE' || $hold->status == 'T'){
					$holds['unavailable'][$curHold['holdSource'] . $curHold['itemId'] . $curHold['cancelId']. $curHold['user']] = $curHold;
				}else{
					$holds['available'][$curHold['holdSource'] . $curHold['itemId'] . $curHold['cancelId']. $curHold['user']] = $curHold;
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
	function placeHold($patron, $recordId, $pickupBranch) {
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => 'Sorry, your hold could not be placed.');
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			$url = $this->getVendorOpacUrl() . '/requests/true?_=' . time() * 1000;
			//LSS allows multiple holds to be places at once, but we will only do one at a time for now.
			$postParams[] = array(
				'bibliographicId' => $recordId,
				'downloadable' => false,
				'interfaceType' => 'PAC',
				'pickupBranchId' => $pickupBranch,
				'titleLevelHold' => 'true'
			);
			$placeHoldResponseRaw = $this->_curlPostBodyData($url, $postParams);
			$placeHoldResponse = json_decode($placeHoldResponseRaw);

			foreach ($placeHoldResponse->placeHoldInfos as $holdResponse){
				if ($holdResponse->success){
					$result['success'] = true;
					$result['message'] = 'Your hold was placed successfully.';
				}else{
					$result['message'] = 'Sorry, your hold could not be placed.  ' . htmlentities(translate($holdResponse->message));
				}
			}
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
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
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch){
		return array('success' => false, 'message' => 'Unable to place Item level holds in Library.Solution at this time');
	}

	/**
	 * Cancels a hold for a patron
	 *
	 * @param   User    $patron     The User to cancel the hold for
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $cancelId   Information about the hold to be cancelled
	 * @return  array
	 */
	function cancelHold($patron, $recordId, $cancelId){
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => 'Sorry, your hold could not be cancelled.');
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			//for lss we need additional information about the hold
			$url = $this->getVendorOpacUrl() . '/requests/0/20/Status?_=' . time() * 1000;
			$holdInfoRaw = $this->_curlGetPage($url);
			$holdInfo = json_decode($holdInfoRaw);

			$selectedHold = null;
			foreach ($holdInfo->holds as $hold) {
				if ($hold->holdNumber == $cancelId){
					$selectedHold = $hold;
				}
			}

			$url = $this->getVendorOpacUrl() . '/requests/cancel?_=' . time() * 1000;
			$postParams = '{"cancelHoldInfos":"[{\"desireNumber\":\"' . $cancelId. '\",\"success\":false,\"holdQueueLength\":\"' . $selectedHold->holdQueueLength . '\",\"bibliographicId\":\"' . $recordId. '\",\"whichBranch\":' . $selectedHold->holdPickupBranchId . ',\"status\":\"' . $selectedHold->status . '\",\"downloadable\":false}]"}';

			$responseRaw = $this->_curlPostBodyData($url, $postParams, false);
			$response = json_decode($responseRaw);

			foreach ($response->cancelHoldInfos as $itemResponse){
				if ($itemResponse->success){
					$result['success'] = true;
					$result['message'] = 'Your hold was cancelled successfully.';
				}else{
					$result['message'] = 'Sorry, your hold could not be cancelled.';
				}
			}
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
		return $result;
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate){
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => 'Sorry, your hold could not be frozen.');
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			$url = $this->getVendorOpacUrl() . '/requests/suspend?_=' . time() * 1000;
			$formattedReactivationDate = $dateToReactivate;
			$postParams = '{"suspendHoldInfos":"[{\"desireNumber\":\"' . $itemToFreezeId . '\",\"success\":false,\"suspendDate\":\"' . $formattedReactivationDate . '\",\"queuePosition\":\"1\",\"bibliographicId\":\"' . $recordId . '\",\"pickupBranchId\":100,\"downloadable\":false}]"}';
			$responseRaw = $this->_curlPostBodyData($url, $postParams, false);
			$response = json_decode($responseRaw);

			foreach ($response->suspendHoldInfos as $itemResponse){
				if ($itemResponse->success){
					$result['success'] = true;
					$result['message'] = 'Your hold was frozen successfully.';
				}else{
					$result['message'] = 'Sorry, your hold could not be suspended.';
				}
			}
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
		return $result;
	}

	function thawHold($patron, $recordId, $itemToThawId){
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => 'Sorry, your hold could not be thawed.');
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			$result['message'] = 'This functionality is currently unimplemented';
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
		return $result;
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation){
		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$result = array(
			'success' => false,
			'title' => $recordDriver->getTitle(),
			'message' => 'Sorry, the pickup location for your hold could not be changed.');
		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			//Not possible in LSS
			$result['message'] = 'This functionality is currently unimplemented';
		}else{
			$result['message'] = 'Sorry, the user supplied was not valid in the catalog. Please try again.';
		}
		return $result;
	}

	function updatePin($user, $oldPin, $newPin, $confirmNewPin){
		/* var Logger $logger */
		global $logger;
		$logger->log('Call to updatePin(), function not implemented.', PEAR_LOG_WARNING);

		return 'Can not update Pins';
	}

	/**
	 * @param User $patron patron to get fines for
	 * @return array  Array of messages
	 */
	function getMyFines($patron, $includeMessages = false) {
		$fines = array();

		if ($this->loginPatronToLSS($patron->cat_username, $patron->cat_password)) {
			//Load transactions from LSS
			//TODO: Verify that this will load more than 10000 fines
			$url = $this->getVendorOpacUrl() . '/fees/0/10000/OutDate?_=' . time() * 1000;
			$feeInfoRaw = $this->_curlGetPage($url);
			$feeInfo = json_decode($feeInfoRaw);

			foreach ($feeInfo->fees as $fee){
				$fines[] = array(
					'reason' => $fee->title,
					'message' => $fee->feeComment,
					'amount' => '$' . sprintf('%0.2f', $fee->fee / 100),
				);
			}
		}

		return $fines;
	}
}