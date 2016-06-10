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

	public function __construct($accountProfile) {
		$this->accountProfile = $accountProfile;
		global $configArray;
		$this->patronWsdl = $configArray['Catalog']['patronApiWsdl'];
	}

	public function patronLogin($username, $password){
		global $timer;

		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		//Search for the patron in the database
		$soapClient = new SoapClient($this->patronWsdl);

		$request = new stdClass();
		$request->SearchType = 'Patron ID';
		$request->SearchID = $username;
		$request->Modifiers = '';

		$result = $soapClient->getPatronInformation($request);

		$patronValid = false;
		if ($result){
			if ($result->Patron){
				//Check to see if the pin matches
				if ($result->Patron->PatronPIN == $password){
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
						$logger->log('Aspencat Driver: No Location found, user\'s homeLocationId being set to 0. User : '.$user->id, PEAR_LOG_WARNING);
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
							$user->city = $primaryAddress->City;
							$user->state = $primaryAddress->State;
							$user->zip = $primaryAddress->PostalCode;
						}
					}

					$user->phone = $result->Patron->Phone1;
					$user->expires = substr($result->Patron->ExpirationDate, 0, strpos($result->Patron->ExpirationDate, 'T'));
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
					$patronSummaryRequest->PatronID = $username;
					$patronSummaryRequest->Modifiers = '';
					$patronSummaryResponse = $soapClient->getPatronSummaryOverview($patronSummaryRequest);

					$user->numCheckedOutIls = $patronSummaryResponse->ChargedItemsCount;
					$user->numHoldsAvailableIls = $patronSummaryResponse->HoldItemsCount;
					$user->numHoldsRequestedIls = $patronSummaryResponse->UnavailableHoldsCount;
					$user->numHoldsIls = $user->numHoldsAvailableIls + $user->numHoldsRequestedIls;

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
		// TODO: Implement hasNativeReadingHistory() method.
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
		// TODO: Implement getMyHolds() method.
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
		// TODO: Implement getMyCheckouts() method.
	}
}