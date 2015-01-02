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
	/** @var string $cookieFile A temporary file to store cookies  */
	private $cookieFile = null;
	/** @var resource connection to AspenCat  */
	private $curl_connection = null;
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
	public function getMyTransactions($page = 1, $recordsPerPage = -1, $sortOption = 'dueDate') {
		global $logger;
		global $user;

		if (isset($this->transactions[$user->id])){
			return $this->transactions[$user->id];
		}

		//Get transactions by screen scraping
		$transactions = array();
		//Login to Koha classic interface
		$result = $this->loginToKoha($user);
		if (!$result['success']){
			return $transactions;
		}

		//Get the checked out titles page
		$transactionPage = $result['summaryPage'];

		//Parse the checked out titles page
		if (preg_match_all('/<table id="checkoutst">(.*?)<\/table>/si', $transactionPage, $transactionTableData, PREG_SET_ORDER)){
			$transactionTable = $transactionTableData[0][0];

			//Get the header row labels in case the columns are ever rearranged?
			$headerLabels = array();
			preg_match_all('/<th>([^<]*?)<\/th>/si', $transactionTable, $tableHeaders, PREG_PATTERN_ORDER);
			foreach ($tableHeaders[1] as $col => $tableHeader){
				$headerLabels[$col] = trim(strtolower($tableHeader));
			}

			//Get each row within the table
			//Grab the table body
			preg_match('/<tbody>(.*?)<\/tbody>/si', $transactionTable, $tableBody);
			$tableBody = $tableBody[1];
			preg_match_all('/<tr>(.*?)<\/tr>/si', $tableBody, $tableData, PREG_PATTERN_ORDER);
			foreach ($tableData[1] as $tableRow){
				//Each row represents a transaction
				$transaction = array();
				$transaction['checkoutSource'] = 'ILS';
				//Go through each cell in the row
				preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tableRow, $tableCells, PREG_PATTERN_ORDER);
				foreach ($tableCells[1] as $col => $tableCell){
					//Based off which column we are in, fill out the transaction
					if ($headerLabels[$col] == 'title'){
						//Title column contains title, author, and id link
						if (preg_match('/biblionumber=(\\d+)">\\s*([^<]*)\\s*<\/a>.*?>\\s*(.*?)\\s*<\/span>/si', $tableCell, $cellDetails)) {
							$transaction['id'] = $cellDetails[1];
							$transaction['shortId'] = $cellDetails[1];
							$transaction['title'] = $cellDetails[2];
							$transaction['author'] = $cellDetails[3];
						}else{
							$logger->log("Could not parse title for checkout", PEAR_LOG_WARNING);
							$transaction['title'] = strip_tags($tableCell);
						}
					}elseif ($headerLabels[$col] == 'call no.'){
						//Ignore this column for now
					}elseif ($headerLabels[$col] == 'due'){
						if (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $tableCell, $dueMatches)){
							$dateDue = DateTime::createFromFormat('m/d/Y', $dueMatches[1]);
							if ($dateDue){
								$dueTime = $dateDue->getTimestamp();
							}else{
								$dueTime = null;
							}
						}else{
							$dueTime = strtotime($tableCell);
						}
						if ($dueTime != null){
							$daysUntilDue = ceil(($dueTime - time()) / (24 * 60 * 60));
							$overdue = $daysUntilDue < 0;
							$transaction['duedate'] = $dueTime;
							$transaction['overdue'] = $overdue;
							$transaction['daysUntilDue'] = $daysUntilDue;
						}
					}elseif ($headerLabels[$col] == 'renew'){
						if (preg_match('/item=(\\d+).*?\\((\\d+) of (\\d+) renewals/si', $tableCell, $renewalData)) {
							$transaction['itemid'] = $renewalData[1];
							$numRenewalsRemaining = $renewalData[2];
							$numRenewalsAllowed = $renewalData[3];
							$transaction['renewCount'] = $numRenewalsAllowed - $numRenewalsRemaining;
						}
					}
					//TODO: Add display of fines on a title?
				}

				if ($transaction['id'] && strlen($transaction['id']) > 0){
					$transaction['recordId'] = $transaction['id'];
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($transaction['recordId']);
					if ($recordDriver->isValid()){
						$transaction['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
						$transaction['groupedWorkId'] = $recordDriver->getGroupedWorkId();
						$formats = $recordDriver->getFormats();
						$transaction['format'] = reset($formats);
						$transaction['author'] = $recordDriver->getPrimaryAuthor();
						if (!isset($transaction['title']) || empty($transaction['title'])){
							$transaction['title'] = $recordDriver->getTitle();
						}
					}else{
						$transaction['coverUrl'] = "";
						$transaction['groupedWorkId'] = "";
						$transaction['format'] = "Unknown";
						$transaction['author'] = "";
					}
				}

				$transactions[] = $transaction;
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

		$this->transactions[$user->id] = $transactions;

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
		//Koha uses SIP2 authentication for login.  See
		//The catalog is offline, check the database to see if the user is valid
		global $timer;
		$user = new User();
		$user->cat_username = $username;
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

	private function initSipConnection() {
		if ($this->sipConnection == null){
			global $configArray;
			require_once ROOT_DIR . '/sys/SIP2.php';
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

					//  Use result to populate SIP2 settings
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
		//Cleanup any connections we have to other systems
		if ($this->sipConnection != null){
			$this->sipConnection->disconnect();
			$this->sipConnection = null;
		}
		if ($this->curl_connection != null){
			curl_close($this->curl_connection);
		}
		if ($this->cookieFile != null){
			unlink($this->cookieFile);
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

	public function hasNativeReadingHistory() {
		return true;
	}

	private function loginToKoha($user) {
		global $configArray;
		$catalogUrl = $configArray['Catalog']['url'];

		//Construct the login url
		$loginUrl = "$catalogUrl/cgi-bin/koha/opac-user.pl";

		//Setup post parameters to the login url
		$postParams = array(
			'koha_login_context' => 'opac',
			'password' => $user->cat_password,
			'userid'=> $user->cat_username
		);

		//Post parameters to the login url using curl
		//If we haven't created a file to store cookies, create one
		if ($this->cookieFile == null){
			$this->cookieFile = tempnam ("/tmp", "KOHACURL");
		}
		
		//Setup the connection to the url
		$this->curl_connection = curl_init($loginUrl);

		curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($this->curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($this->curl_connection, CURLOPT_COOKIEJAR, $this->cookieFile );
		curl_setopt($this->curl_connection, CURLOPT_COOKIESESSION, is_null($this->cookieFile) ? true : false);

		//Set post parameters
		curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, http_build_query($postParams));
		
		//Get the response from the page
		$sResult = curl_exec($this->curl_connection);

		//Parse the response to make sure the login went ok
		//If we can see the logout link, it means that we logged in successfully.
		if (preg_match('/<a\\s+class="logout"\\s+id="logout"[^>]*?>/si', $sResult)){
			$result =array(
				'success' => true,
				'summaryPage' => $sResult
			);
		}else{
			$result =array(
				'success' => false,
			);
		}
		return $result;
	}

	public function placeHold($recordId, $patronId, $comment, $type){
		global $user;
		//Place the hold via SIP 2
		if (!$this->initSipConnection()){
			return array(
				'result' => false,
				'message' => 'Could not connect to SIP'
			);
		}

		$hold_result = array();
		$hold_result['result'] = false;

		$this->sipConnection->patron = $user->cat_username;
		$this->sipConnection->patronpwd = $user->cat_password;

		//Set pickup location
		if (isset($_REQUEST['campus'])){
			$campus=trim($_REQUEST['campus']);
		}else{
			$campus = $user->homeLocationId;
			//Get the code for the location
			$locationLookup = new Location();
			$locationLookup->locationId = $campus;
			$locationLookup->find();
			if ($locationLookup->N > 0){
				$locationLookup->fetch();
				$campus = $locationLookup->code;
			}
		}

		//place the hold
		if ($type == 'cancel' || $type == 'recall'){
			$mode = '-';
		}elseif ($type == 'update'){
			$mode = '*';
		}else{
			$mode = '+';
		}

		//Get a specific item number to place a hold on even though we are placing a title level hold.
		//because.... Koha
		require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
		$recordDriver = new MarcRecord($recordId);
		if (!$recordDriver->isValid()){
			$hold_result['message'] = 'Unable to find a valid record for this title.  Please try your search again.';
			return $hold_result;
		}
		global $configArray;
		$marcRecord = $recordDriver->getMarcRecord();
		$itemTag = $configArray['Reindex']['itemTag'];
		$barcodeSubfield = $configArray['Reindex']['barcodeSubfield'];
		/** @var File_MARC_Data_Field[] $itemFields */
		$itemFields = $marcRecord->getFields($itemTag);
		$barcodeToHold = null;
		foreach ($itemFields as $itemField){
			if ($itemField->getSubfield($barcodeSubfield) != null){
				$barcodeToHold = $itemField->getSubfield($barcodeSubfield)->getData();
				break;
			}
		}

		$hold_result['title'] = $recordDriver->getTitle();

		$in = $this->sipConnection->msgHold($mode, '', '2', $barcodeToHold, $recordId, '', $campus);
		$msg_result = $this->sipConnection->get_message($in);

		$hold_result['id'] = $recordId;
		if (preg_match("/^16/", $msg_result)) {
			$result = $this->sipConnection->parseHoldResponse($msg_result );
			$hold_result['result'] = ($result['fixed']['Ok'] == 1);
			$hold_result['message'] = $result['variable']['AF'][0];
			//Get the hold position.
			if ($result['fixed']['Ok'] == 1){
				$holds = $this->getMyHolds($user);
				//Find the correct hold (will be unavailable)
				foreach ($holds['holds']['unavailable'] as $key => $holdInfo){
					if ($holdInfo['id'] == $recordId){
						$hold_result['message'] .= "  You are number <b>" . $holdInfo['position'] . "</b> in the queue.";
						break;
					}
				}
			}
		}
		return $hold_result;
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds by a specific patron.
	 *
	 * @param array|User $patron      The patron array from patronLogin
	 * @param integer $page           The current page of holds
	 * @param integer $recordsPerPage The number of records to show per page
	 * @param string $sortOption      How the records should be sorted
	 *
	 * @return mixed        Array of the patron's holds on success, PEAR_Error
	 * otherwise.
	 * @access public
	 */
	public function getMyHolds($patron, $page = 1, $recordsPerPage = -1, $sortOption = 'title'){
		global $logger;
		global $user;

		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);

		//Get transactions by screen scraping
		$transactions = array();
		//Login to Koha classic interface
		$result = $this->loginToKoha($user);
		if (!$result['success']){
			return $holds;
		}

		//Get the summary page that contains both checked out titles and holds
		$summaryPage = $result['summaryPage'];

		//Get the holds table
		if (preg_match_all('/<table id="aholdst">(.*?)<\/table>/si', $summaryPage, $holdsTableData, PREG_SET_ORDER)){
			$holdsTable = $holdsTableData[0][0];

			//Get the header row labels
			$headerLabels = array();
			preg_match_all('/<th[^>]*>(.*?)<\/th>/si', $holdsTable, $tableHeaders, PREG_PATTERN_ORDER);
			foreach ($tableHeaders[1] as $col => $tableHeader){
				$headerLabels[$col] = trim(strip_tags(strtolower($tableHeader)));
			}

			//Get each row within the table
			//Grab the table body
			preg_match('/<tbody>(.*?)<\/tbody>/si', $holdsTable, $tableBody);
			$tableBody = $tableBody[1];
			preg_match_all('/<tr>(.*?)<\/tr>/si', $tableBody, $tableData, PREG_PATTERN_ORDER);

			foreach ($tableData[1] as $tableRow){
				//Each row in the table represents a hold
				$curHold= array();
				$curHold['holdSource'] = 'ILS';
				//Go through each cell in the row
				preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tableRow, $tableCells, PREG_PATTERN_ORDER);
				$bibId = "";
				foreach ($tableCells[1] as $col => $tableCell){
					//Based off which column we are in, fill out the transaction
					if ($headerLabels[$col] == 'title'){
						//Title column contains title, author, and id link
						if (preg_match('/biblionumber=(\\d+)".*?>(.*?)<\/a>/si', $tableCell, $cellDetails)) {
							$curHold['id'] = $cellDetails[1];
							$curHold['shortId'] = $cellDetails[1];
							$curHold['recordId'] = $cellDetails[1];
							$bibId = $cellDetails[1];
							$curHold['title'] = $cellDetails[2];
						}else{
							$logger->log("Could not parse title for checkout", PEAR_LOG_WARNING);
							$curHold['title'] = strip_tags($tableCell);
						}
					}elseif ($headerLabels[$col] == 'placed on'){
						$curHold['create'] = date_parse_from_format('m/d/Y', $tableCell);
					}elseif ($headerLabels[$col] == 'expires on'){
						if (strlen($tableCell) != 0){
							$curHold['expire'] = date_parse_from_format('m/d/Y', $tableCell);
						}
					}elseif ($headerLabels[$col] == 'pick up location'){
						if (strlen($tableCell) != 0){
							$curHold['location'] = $tableCell;
							$curHold['locationUpdateable'] = false;
							$curHold['currentPickupName'] = $curHold['location'];
						}
					}elseif ($headerLabels[$col] == 'status'){
						$curHold['status'] = $tableCell;
					}elseif ($headerLabels[$col] == 'cancel'){
						$curHold['cancelable'] = strlen($tableCell) > 0;
					}
				}
				if ($bibId){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
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
}