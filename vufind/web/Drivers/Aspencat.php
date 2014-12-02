<?php
/**
 * Catalog Driver for Aspencat libraries based on Koha
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/3/14
 * Time: 5:51 PM
 */
require_once ROOT_DIR . '/Drivers/Interface.php';

class Aspencat implements DriverInterface{
	/** @var sip2 $sipConnection  */
	private $sipConnection = null;
	/** @var mysqli $dbConnection */
	private $dbConnection = null;
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

	public function getPurchaseHistory($id) {
		// TODO: Implement getPurchaseHistory() method.
	}

	private $patronProfiles = array();
	public function getMyProfile($patron, $forceReload = false) {
		global $timer;
		if (is_object($patron)){
			$patron = get_object_vars($patron);
		}
		if (array_key_exists($patron['username'], $this->patronProfiles) && !$forceReload){
			$timer->logTime('Retrieved Cached Profile for Patron');
			return $this->patronProfiles[$patron['username']];
		}

		if (is_object($patron)){
			$patron = get_object_vars($patron);
		}

		if (!$this->initSipConnection()){
			$profile = new PEAR_Error('patron_info_error_technical');
		}else{

			$this->sipConnection->patron = $patron['username'];
			$this->sipConnection->patronpwd = $patron['cat_password'];

			$in = $this->sipConnection->msgPatronInformation('fine');
			$msg_result = $this->sipConnection->get_message($in);

			if (preg_match("/^64/", $msg_result)) {
				$result = $this->sipConnection->parsePatronInfoResponse( $msg_result );
				$address = $result['variable']['BD'][0];
				$addressParts = explode(',', $address);
				if (isset($result['variable']['PE'])){
					$expirationDate = $result['variable']['PE'][0];
					$formattedExpiration = substr($expirationDate, 4,2) . '/' . substr($expirationDate, 6,2) . '/' . substr($expirationDate, 0,4);
				}else{
					$formattedExpiration = '';
				}

				//$fines = $this->parseSip2Fines($result['variable']['AV']);
				$location = new Location();
				$location->code = $result['variable']['AO'][0];
				$location->find();
				if ($location->N > 0){
					$location->fetch();
					$homeLocationId = $location->locationId;
					$homeLocationName = $location->displayName;
				}
				global $user;
				list($fullName, $lastName, $firstName) = $this->splitFullName($result['variable']['AE'][0]);
				$profile = array(
					'lastname' => $lastName,
					'firstname' => $firstName,
					'displayName' => isset($patron['displayName']) ? $patron['displayName'] : '',
					'fullname' => $fullName,
					'address1' => trim($addressParts[0]),
					'city' => isset($addressParts[1]) ? trim($addressParts[1]) : '',
					'state' => isset($addressParts[2]) ? trim($addressParts[2]) : '',
					'zip' => isset($addressParts[3]) ? trim($addressParts[3]) : '',
					'phone' => isset($result['variable']['BF'][0]) ? $result['variable']['BF'][0] : '',
					'email' => isset($result['variable']['BE'][0]) ? $result['variable']['BE'][0] : '',
					'homeLocationId' => isset($homeLocationId) ? $homeLocationId : -1,
					'homeLocationName' => isset($homeLocationName) ? $homeLocationName : '',
					'expires' => $formattedExpiration,
					'fines' => isset($result['variable']['BV']) ? sprintf('$%01.2f', $result['variable']['BV'][0]) : 0,
					'finesval' => isset($result['variable']['BV']) ? $result['variable']['BV'][0] : '',
					'numHolds' => $result['fixed']['HoldCount'] + $result['fixed']['UnavailableCount'],
					'numHoldsAvailable' => $result['fixed']['HoldCount'],
					'numHoldsRequested' => $result['fixed']['UnavailableCount'],
					'numCheckedOut' => $result['fixed']['ChargedCount'] ,
					'bypassAutoLogout' => ($user ? $user->bypassAutoLogout : false),
				);

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
			} else {
				$profile = new PEAR_Error('patron_info_error_technical');
			}
		}

		$this->patronProfiles[$patron['username']] = $profile;
		$timer->logTime('Retrieved Profile for Patron from SIP 2');
		return $profile;
	}

	private $transactions = array();
	public function getMyTransactions($patron, $page = 1, $recordsPerPage = -1, $sortOption = 'dueDate') {
		global $configArray;
		global $timer;
		if (is_object($patron)){
			$patron = get_object_vars($patron);
		}

		if (isset($this->transactions[$patron['id']])){
			return $this->transactions[$patron['id']];
		}

		//Get transactions via Database
		if ($this->initDBConnection()){
			//Call SQL to get transactions
			$sql = "SELECT * FROM accountlines WHERE borrowernumber = {$patron['id']}";

			if (isset($transactions)){
				//Load information about titles from Resources table (for performance)
				$recordIds = array();
				foreach ($transactions as $i => $data) {
					$recordIds[] = "'" . $data['id'] . "'";
				}

				//Get econtent info and hold queue length
				foreach ($transactions as $key => $transaction){
					//Check for hold queue length
					$itemData = $this->_loadItemSIP2Data($transaction['barcode'], '');
					$transaction['holdQueueLength'] = intval($itemData['holdQueueLength']);

					$transactions[$key] = $transaction;
				}
			}

			//Process sorting
			$sortKeys = array();
			$i = 0;
			foreach ($transactions as $key => $transaction){
				$sortTitle = isset($transaction['sortTitle']) ? $transaction['sortTitle'] : "Unknown";
				if ($sortOption == 'title'){
					$sortKeys[$key] = $sortTitle;
				}elseif ($sortOption == 'author'){
					$sortKeys[$key] = (isset($transaction['author']) ? $transaction['author'] : "Unknown") . '-' . $sortTitle;
				}elseif ($sortOption == 'dueDate'){
					if (preg_match('/.*?(\\d{1,2})[-\/](\\d{1,2})[-\/](\\d{2,4}).*/', $transaction['duedate'], $matches)) {
						$sortKeys[$key] = $matches[3] . '-' . $matches[1] . '-' . $matches[2] . '-' . $sortTitle;
					} else {
						$sortKeys[$key] = $transaction['duedate'] . '-' . $sortTitle;
					}
				}elseif ($sortOption == 'format'){
					$sortKeys[$key] = (isset($transaction['format']) ? $transaction['format'] : "Unknown") . '-' . $sortTitle;
				}elseif ($sortOption == 'renewed'){
					$sortKeys[$key] = (isset($transaction['renewCount']) ? $transaction['renewCount'] : 0) . '-' . $sortTitle;
				}elseif ($sortOption == 'holdQueueLength'){
					$sortKeys[$key] = (isset($transaction['holdQueueLength']) ? $transaction['holdQueueLength'] : 0) . '-' . $sortTitle;
				}
				$sortKeys[$key] = $sortKeys[$key] . '-' . $i++;
			}
			array_multisort($sortKeys, $transactions);

			//Limit to a specific number of records
			$totalTransactions = count($transactions);
			if ($recordsPerPage != -1){
				$startRecord = ($page - 1) * $recordsPerPage;
				$transactions = array_slice($transactions, $startRecord, $recordsPerPage);
			}

			$this->transactions[$patron['id']] = $transactions;
		}


		return array(
			'transactions' => $transactions,
			'numTransactions' => $totalTransactions
		);
	}

	private function _loadItemSIP2Data($barcode, $itemStatus){
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $timer;
		$itemSip2Data = $memCache->get("item_sip2_data_{$barcode}");
		if ($itemSip2Data == false){
			//Check to see if the SIP2 information is already cached
			if ($this->initSipConnection()){
				$in = $this->sipConnection->msgItemInformation($barcode);
				$msg_result = $this->sipConnection->get_message($in);

				// Make sure the response is 18 as expected
				if (preg_match("/^18/", $msg_result)) {
					$result = $this->sipConnection->parseItemInfoResponse( $msg_result );
					if (isset($result['variable']['AH']) && $itemStatus != 'i'){
						$itemSip2Data['duedate'] = $result['variable']['AH'][0];
					}else{
						$itemSip2Data['duedate'] = '';
					}
					if (isset($result['variable']['CF'][0])){
						$itemSip2Data['holdQueueLength'] = intval($result['variable']['CF'][0]);
					}else{
						$itemSip2Data['holdQueueLength'] = 0;
					}
					$currentLocationSIPField = isset($configArray['Catalog']['currentLocationSIPField']) ? $configArray['Catalog']['currentLocationSIPField'] : 'AP';
					if ($configArray['Catalog']['realtimeLocations'] == true && isset($result['variable'][$currentLocationSIPField][0])){
						//Looks like horizon is returning these backwards via SIP.
						//AQ should be current, but is always returning the same code.
						//AP should be permanent, but is returning the current location

						//global $logger;
						//$logger->log("Permanent location " . $result['variable']['AQ'][0] . " current location " . $result['variable']['AP'][0], PEAR_LOG_INFO);
						$itemSip2Data['locationCode'] = $result['variable'][$currentLocationSIPField][0];
						$itemSip2Data['location'] = mapValue('shelf_location', $itemSip2Data['locationCode']);
					}
					//Override circulation status based on SIP
					if ($result['fixed']['CirculationStatus'] == 4){
						$itemSip2Data['status'] = 'o';
						$itemSip2Data['availability'] = false;
					}
				}
				$memCache->set("item_sip2_data_{$barcode}", $itemSip2Data, 0, $configArray['Caching']['item_sip2_data']);
				$timer->logTime("Got due date and hold queue length from SIP 2 for barcode $barcode");
			}else{
				$itemSip2Data = false;
			}
		}
		return $itemSip2Data;
	}

	public function patronLogin($username, $password) {
		//TODO: Actual login with Koha
		//The catalog is offline, check the database to see if the user is valid
		global $timer;
		$user = new User();
		$user->cat_password = $password;
		if ($user->find(true)){
			$userValid = false;
			if ($user->cat_username){
				$userValid = true;
			}
			if ($userValid){
				$returnVal = array(
					'id'        => $password,
					'username'  => $user->username,
					'firstname' => $user->firstname,
					'lastname'  => $user->lastname,
					'fullname'  => $user->firstname . ' ' . $user->lastname,     //Added to array for possible display later.
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
	}

	private function initDBConnection(){
		if ($this->dbConnection == null){
			global $configArray;
			$this->dbConnection = new mysqli($configArray['Catalog']['db_host'], $configArray['Catalog']['db_user'], $configArray['Catalog']['db_pwd']);
			if ($this->dbConnection->connect_error){
				global $logger;
				$logger->log("Error connecting to Koha database {$this->dbConnection->connect_error}", PEAR_LOG_ERR);
				$this->dbConnection = false;
				return false;
			}
		}
		return true;
	}

	private function initSipConnection() {
		if ($this->sipConnection == null){
			global $configArray;
			$this->sipConnection = new sip2();
			$this->sipConnection->hostname = $configArray['SIP2']['host'];
			$this->sipConnection->port = $configArray['SIP2']['port'];

			if ($this->sipConnection->connect()) {
				//send selfcheck status message
				$in = $this->sipConnection->msgSCStatus();
				$msg_result = $this->sipConnection->get_message($in);

				// Make sure the response is 98 as expected
				if (preg_match("/^98/", $msg_result)) {
					$result = $this->sipConnection->parseACSStatusResponse($msg_result);

					//  Use result to populate SIP2 setings
					$this->sipConnection->AO = $result['variable']['AO'][0]; /* set AO to value returned */
					if (isset($result['variable']['AN'])){
						$this->sipConnection->AN = $result['variable']['AN'][0]; /* set AN to value returned */
					}
					return true;
				}
				$this->sipConnection->disconnect();
			}
			$this->sipConnection = null;
			return false;
		}else{
			return true;
		}
	}

	function __destruct(){
		if ($this->sipConnection != null){
			$this->sipConnection->disconnect();
			$this->sipConnection = null;
		}
		if ($this->dbConnection != null){
			$this->dbConnection->close();
			$this->dbConnection = null;
		}
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
}