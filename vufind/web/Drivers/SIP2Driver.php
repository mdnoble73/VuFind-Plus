<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/20/2015
 * Time: 10:09 PM
 */

abstract class SIP2Driver implements DriverInterface{
	/** @var sip2 $sipConnection  */
	private $sipConnection = null;

	private $transactions = array();
	public function getMyCheckouts($page = 1, $recordsPerPage = -1, $sortOption = 'dueDate') {
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
							$transaction['dueDate'] = $dueTime;
						}
					}elseif ($headerLabels[$col] == 'renew'){
						if (preg_match('/item=(\\d+).*?\\((\\d+) of (\\d+) renewals/si', $tableCell, $renewalData)) {
							$transaction['itemid'] = $renewalData[1];
							$transaction['renewIndicator'] = $renewalData[1];
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
				if (preg_match('/.*?(\\d{1,2})[-\/](\\d{1,2})[-\/](\\d{2,4}).*/', $transaction['dueDate'], $matches)) {
					$sortKeys[$key] = $matches[3] . '-' . $matches[1] . '-' . $matches[2] . '-' . $sortTitle;
				} else {
					$sortKeys[$key] = $transaction['dueDate'] . '-' . $sortTitle;
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
		if ($itemSip2Data == false || isset($_REQUEST['reload'])){
			//Check to see if the SIP2 information is already cached
			if ($this->initSipConnection()){
				$in = $this->sipConnection->msgItemInformation($barcode);
				$msg_result = $this->sipConnection->get_message($in);
				// Make sure the response is 18 as expected
				if (preg_match("/^18/", $msg_result)) {
					$result = $this->sipConnection->parseItemInfoResponse( $msg_result );
					if (isset($result['variable']['AH']) && $itemStatus != 'i'){
						$itemSip2Data['dueDate'] = $result['variable']['AH'][0];
					}else{
						$itemSip2Data['dueDate'] = '';
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
					if (isset($result['fixed']['CirculationStatus'])){
						$itemSip2Data['status'] = $result['fixed']['CirculationStatus'];
						$itemSip2Data['status_full'] = mapValue('item_status', $result['fixed']['CirculationStatus']);
						$itemSip2Data['availability'] = $result['fixed']['CirculationStatus'] == 3;
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
	protected function initSipConnection($host, $post) {
		if ($this->sipConnection == null){
			require_once ROOT_DIR . '/sys/SIP2.php';
			$this->sipConnection = new sip2();
			$this->sipConnection->hostname = $host;
			$this->sipConnection->port = $post;
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
	}
}