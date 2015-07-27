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
		// TODO: Implement getStatus() method.
	}

	public function getStatuses($ids) {
		// TODO: Implement getStatuses() method.
	}

	public function getHolding($id) {
		// TODO: Implement getHolding() method.
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
				$location->whereAdd("code = '$homeBranchCode'");
				if ($location->find(true)){
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

			$user->fines = $accountSummary->patron->fees;
			$user->finesVal = floatval(preg_replace('/[^\\d.]/', '', $user->fines));

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
		// TODO: Implement hasNativeReadingHistory() method.
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
			//'Referer: ' . ($this->referer == null ? $this->getVendorOpacUrl() . '/' : $this->referer) ,
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
	public function getMyTransactions($user){
		$transactions = array(
			'transactions' => array(),
			'numTransactions' => 0
		);
		if ($this->loginPatronToLSS($user->cat_username, $user->cat_password)){
			//Load transactions from LSS
			//TODO: Verify that this will load more than 20 loans
			$url = $this->getVendorOpacUrl() . '/loans/0/20/Status?_=' . time() * 1000;
			$loanInfoRaw = $this->_curlGetPage($url);
			$loanInfo = json_decode($loanInfoRaw);

			foreach ($loanInfo->loans as $loan){
				$curTitle = array();
				$curTitle['checkoutSource'] = 'ILS';
				$curTitle['id'] = $loan->bibliographicId;
				$curTitle['shortId'] = $loan->bibliographicId;
				$curTitle['recordId'] = $loan->bibliographicId;
				$curTitle['title'] = utf8_encode($loan->title);
				$curTitle['author'] = utf8_encode($loan->author);
				$curTitle['duedate'] = $loan->dueDate;
				/*$curTitle['overdue']
				$curTitle['daysUntilDue']
				$curTitle['renewCount']
				$curTitle['barcode']
				$curTitle['canrenew']
				$curTitle['itemindex']
				$curTitle['itemid']
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

				$transactions['transactions'][] = $curTitle;
				$transactions['numTransactions']++;
			}
		}

		return $transactions;
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
				$curHold['location'] = $hold->holdPickupBranchId;
				//$curHold['locationId'] = $matches[1];
				$curHold['locationUpdateable'] = false;
				$curHold['currentPickupName'] = $hold->holdPickupBranch;
				$curHold['status'] = $hold->status;
				//$expireDate = (string)$hold->expireDate;
				//$curHold['expire'] = $expireDate;
				//$curHold['expireTime'] = strtotime($expireDate);
				$curHold['reactivate'] = $hold->suspendUntilDateString;

				$curHold['cancelable'] = $hold->holdCancelable;
				$curHold['frozen'] = $hold->suspendUntilDate != null;
				if ($curHold['frozen']){
					$curHold['reactivateTime'] = $hold->suspendUntilDate;
				}
				$curHold['freezeable'] = $hold->holdSuspendable;

				$curHold['sortTitle'] = $hold->title;
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

				//TODO: Determine the status of available holds
				if (!isset($curHold['status']) || $curHold['status'] == 'PE' || $curHold['status'] == 'T'){
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
}