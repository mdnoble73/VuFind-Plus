<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/20/13
 * Time: 2:42 PM
 */

class MillenniumCheckouts {
	/** @var  Millennium $driver */
	private $driver;

	public function __construct($driver){
		$this->driver = $driver;
	}

	private function extract_title_from_row($row) {
		//Standard Millennium WebPAC
		if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $row, $matches)) {
			return trim(strip_tags($matches[2]));
		}
		//Encore
		elseif (preg_match('/.*<a href=".*?\/record\/C__R(.*?)\\?.*?">(.*?)<\/a>.*/si', $row, $matches)){
			return trim(strip_tags($matches[2]));
		}else{
			return trim(strip_tags($row));
		}
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
	public function getMyTransactions($user) {
		global $timer;
		$timer->logTime("Ready to load checked out titles from Millennium");
		//Load the information from millennium using CURL
		$sResult = $this->driver->_fetchPatronInfoPage($user, 'items');
		$timer->logTime("Loaded checked out titles from Millennium");

		$sResult = preg_replace("/<[^<]+?>\\W<[^<]+?>\\W\\d* ITEM.? CHECKED OUT<[^<]+?>\\W<[^<]+?>/i", "", $sResult);

		$s = substr($sResult, stripos($sResult, 'patFunc'));

		$s = substr($s,strpos($s,">")+1);

		$s = substr($s,0,stripos($s,"</table"));

		$s = preg_replace ("/<br \\/>/","", $s);

		$sRows = preg_split("/<tr([^>]*)>/",$s);
		$sCount = 0;
		$sKeys = array_pad(array(),10,"");
		$checkedOutTitles = array();

		//Get patron's location to determine if renewals are allowed.
		global $locationSingleton;
		/** @var Location $patronLocation */
		$patronLocation = $locationSingleton->getUserHomeLocation();
		if (isset($patronLocation)){
			$patronPType = $user->patronType;
			$patronCanRenew = false;
			if ($patronLocation->ptypesToAllowRenewals == '*'){
				$patronCanRenew = true;
			}else if (preg_match("/^({$patronLocation->ptypesToAllowRenewals})$/", $patronPType)){
				$patronCanRenew = true;
			}
		}else{
			$patronCanRenew = true;
		}
		$timer->logTime("Determined if patron can renew");

		foreach ($sRows as $srow) {
			$scols = preg_split("/<t(h|d)([^>]*)>/",$srow);
			$curTitle = array();
			for ($i=0; $i < sizeof($scols); $i++) {
				$scols[$i] = str_replace("&nbsp;"," ",$scols[$i]);
				$scols[$i] = preg_replace ("/<br+?>/"," ", $scols[$i]);
				$scols[$i] = html_entity_decode(trim(substr($scols[$i],0,stripos($scols[$i],"</t"))));
				//print_r($scols[$i]);
				if ($sCount == 1) {
					$sKeys[$i] = $scols[$i];
				} else if ($sCount > 1) {

					if (stripos($sKeys[$i],"TITLE") > -1) {
						if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $scols[$i], $matches)) {
							//Standard Millennium WebPAC
							$shortId = $matches[1];
							$bibid = '.' . $matches[1]; //Technically, this isn't correct since the check digit is missing
							$title = strip_tags($matches[2]);
						}elseif (preg_match('/.*<a href=".*?\/record\/C__R(.*?)\\?.*?">(.*?)<\/a>.*/si', $scols[$i], $matches)){
							//Encore
							$shortId = $matches[1];
							$bibid = '.' . $matches[1]; //Technically, this isn't correct since the check digit is missing
							$title = strip_tags($matches[2]);
						}else{
							$title = strip_tags($scols[$i]);
							$shortId = '';
							$bibid = '';
						}
						$curTitle['checkoutSource'] = 'ILS';
						$curTitle['shortId'] = $shortId;
						$curTitle['id'] = $bibid;
						$curTitle['title'] = utf8_encode($title);
					}

					if (stripos($sKeys[$i],"STATUS") > -1) {
						// $sret[$scount-2]['duedate'] = strip_tags($scols[$i]);
						$due = trim(str_replace("DUE", "", strip_tags($scols[$i])));
						$renewCount = 0;
						if (preg_match('/FINE\(up to now\) (\$\d+\.\d+)/i', $due, $matches)){
							$curTitle['fine'] = trim($matches[1]);
						}
						if (preg_match('/(.*)Renewed (\d+) time(?:s)?/i', $due, $matches)){
							$due = trim($matches[1]);
							$renewCount = $matches[2];
						}else if (preg_match('/(.*)\+\d+ HOLD.*/i', $due, $matches)){
							$due = trim($matches[1]);
						}
						if (preg_match('/(\d{2}-\d{2}-\d{2})/', $due, $dueMatches)){
							$dateDue = DateTime::createFromFormat('m-d-y', $dueMatches[1]);
							if ($dateDue){
								$dueTime = $dateDue->getTimestamp();
							}else{
								$dueTime = null;
							}
						}else{
							$dueTime = strtotime($due);
						}
						if ($dueTime != null){
							$daysUntilDue = ceil(($dueTime - time()) / (24 * 60 * 60));
							$overdue = $daysUntilDue < 0;
							$curTitle['duedate'] = $dueTime;
							$curTitle['overdue'] = $overdue;
							$curTitle['daysUntilDue'] = $daysUntilDue;
						}
						$curTitle['renewCount'] = $renewCount;

					}

					if (stripos($sKeys[$i],"BARCODE") > -1) {
						$curTitle['barcode'] = strip_tags($scols[$i]);
					}


					if (stripos($sKeys[$i],"RENEW") > -1) {
						$matches = array();
						if (preg_match('/<input\s*type="checkbox"\s*name="renew(\d+)"\s*id="renew(\d+)"\s*value="(.*?)"\s*\/>/', $scols[$i], $matches)){
							$curTitle['canrenew'] = $patronCanRenew;
							$curTitle['itemindex'] = $matches[1];
							$curTitle['itemid'] = $matches[3];
							$curTitle['renewIndicator'] = $curTitle['itemid'] . '|' . $curTitle['itemindex'];
							$curTitle['renewMessage'] = '';
						}else{
							$curTitle['canrenew'] = false;
						}

					}


					if (stripos($sKeys[$i],"CALL NUMBER") > -1) {
						$curTitle['request'] = "null";
					}
				}

			}
			if ($sCount > 1){
				//Get additional information from the MARC Record
				if ($curTitle['shortId'] && strlen($curTitle['shortId']) > 0){
					$checkDigit = $this->driver->getCheckDigit($curTitle['shortId']);
					$curTitle['recordId'] = '.' . $curTitle['shortId'] . $checkDigit;
					$curTitle['id'] = '.' . $curTitle['shortId'] . $checkDigit;
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord( $this->driver->accountProfile->recordSource . ":" . $curTitle['recordId']);
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
				$checkedOutTitles[] = $curTitle;
			}

			$sCount++;
		}
		$timer->logTime("Parsed checkout information");

		$numTransactions = count($checkedOutTitles);

		return array(
			'transactions' => $checkedOutTitles,
			'numTransactions' => $numTransactions
		);
	}

	public function renewAll(){
		global $logger;
		global $configArray;

		//Setup the call to Millennium
		$barcode = $this->driver->_getBarcode();
		$patronDump = $this->driver->_getPatronDump($barcode);
		$curCheckedOut = $patronDump['CUR_CHKOUT'];

		//Login to the patron's account
		$cookieJar = tempnam ("/tmp", "CURLCOOKIE");

		$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo";
		$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);

		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, false);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = $this->driver->_getLoginFormValues();
		$post_string = http_build_query($post_data);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$loginResult = curl_exec($curl_connection);
		//When a library uses Encore, the initial login does a redirect and requires additional parameters.
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResult, $loginMatches)) {
			//Get the lt value
			$lt = $loginMatches[1];
			//Login again
			$post_data['lt'] = $lt;
			$post_data['_eventId'] = 'submit';

			$post_string = http_build_query($post_data);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$loginResult = curl_exec($curl_connection);
			$curlInfo = curl_getinfo($curl_connection);
		}

		//Go to the items page
		$scope = $this->driver->getDefaultScope();
		$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/items";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		curl_exec($curl_connection);

		//Post renewal information
		$extraGetInfo = array(
			'currentsortorder' => 'current_checkout',
			'renewall' => 'YES',
		);

		$renewItemParams = http_build_query($extraGetInfo);
		$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/items";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $renewItemParams);
		$checkedOutPageText = curl_exec($curl_connection);
		//$logger->log("Result of Renew All\r\n" . $sresult, PEAR_LOG_INFO);

		curl_close($curl_connection);
		unlink($cookieJar);

		//Clear the existing patron info and get new information.
		$renew_result = array();
		$renew_result['Total'] = $curCheckedOut;
		preg_match_all("/RENEWED successfully/si", $checkedOutPageText, $matches);
		$numRenewals = count($matches[0]);
		$renew_result['Renewed'] = $numRenewals;
		$renew_result['Unrenewed'] = $renew_result['Total'] - $renew_result['Renewed'];
		if ($renew_result['Unrenewed'] > 0) {
			$renew_result['result'] = false;
			// Now Extract Failure Messages

			// Overall Failure
			if (preg_match('/<h2>\\s*You cannot renew items because:\\s*<\/h2><ul><li>(.*?)<\/ul>/si', $checkedOutPageText, $matches)) {
				$msg = ucfirst(strtolower(trim($matches[1])));
				$renew_result['message'] = "Unable to renew items: $msg.";
//				if ($analytics){
//					$analytics->addEvent('ILS Integration', 'Renew Failed', $msg);
//				}
			}

			// The Account is busy
			elseif (preg_match('/Your record is in use/si', $checkedOutPageText)) {
				$renew_result['message'] = 'Unable to renew this item now, your account is in use by the system.  Please try again later.';
//				if ($analytics){
//					$analytics->addEvent('ILS Integration', 'Renew Failed', 'Account in Use');
//				}
			}

			// Let's Look at the Results
			elseif (preg_match('/<table border="0" class="patFunc">(.*?)<\/table>/s', $checkedOutPageText, $matches)) {
				$checkedOutTitleTable = $matches[1];
				if (preg_match_all('/<tr class="patFuncEntry">(.*?)<\/tr>/s', $checkedOutTitleTable, $rowMatches, PREG_SET_ORDER)){
//					$rows = array_column($rowMatches, 1); // extract the only column we need. php 5.5

					foreach ($rowMatches as $row) {
						$row = $row[1];

							//Extract failure  message
							if (preg_match('/<td align="left" class="patFuncStatus">.*?<em><font color="red">(.*?)<\/font><\/em>.*?<\/td>/s', $row, $statusMatches)){
								$msg = ucfirst(strtolower(trim( $statusMatches[1])));

								// Add Title to msg
								$title = $this->extract_title_from_row($row);
//								if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $row, $matches)) {
//									//Standard Millennium WebPAC
////									$shortId = $matches[1];
////									$bibid = '.' . $matches[1]; //Technically, this isn't correct since the check digit is missing
//									$title = strip_tags($matches[2]);
//								}elseif (preg_match('/.*<a href=".*?\/record\/C__R(.*?)\\?.*?">(.*?)<\/a>.*/si', $row, $matches)){
//									//Encore
////									$shortId = $matches[1];
////									$bibid = '.' . $matches[1]; //Technically, this isn't correct since the check digit is missing
//									$title = strip_tags($matches[2]);
//								}else{
//									$title = strip_tags($row);
////									$shortId = '';
////									$bibid = '';
//								}

								$renew_result['message'][] = "Unable to renew $title: $msg.";
							}

					}
				}else{
					$logger->log("Did not find any rows for the table $checkedOutTitleTable", PEAR_LOG_DEBUG);
				}
			}



//			$failureMessages = $this->getMyRenewalTransactions($sresult);
//			foreach ($failureMessages as $index => $failure){
//				$renew_result['message'][] = $failure;
//			}
		}
		else{
			$renew_result['result'] = true;
			$renew_result['message'] = "All items were renewed successfully.";
		}

		return $renew_result;
	}

	public function renewItem($itemId, $itemIndex){
		global $logger;
		global $configArray;
		global $analytics;

		//Setup the call to Millennium
		$patronDump = $this->driver->_getPatronDump($this->driver->_getBarcode());

		$extraGetInfo = array(
			'currentsortorder' => 'current_checkout',
			'renewsome' => 'YES',
			'renew' . $itemIndex => $itemId,
		);
		$renewItemParams = http_build_query($extraGetInfo);

		//Login to the patron's account
		$cookieJar = tempnam ("/tmp", "CURLCOOKIE");

		$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo";
		//$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);

		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, false);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = $this->driver->_getLoginFormValues();
		$post_string = http_build_query($post_data);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$loginResult = curl_exec($curl_connection);
		//When a library uses Encore, the initial login does a redirect and requires additional parameters.
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResult, $loginMatches)) {
			//Get the lt value
			$lt = $loginMatches[1];
			//Login again
			$post_data['lt'] = $lt;
			$post_data['_eventId'] = 'submit';
			$post_string = http_build_query($post_data);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$loginResult = curl_exec($curl_connection);
			$curlInfo = curl_getinfo($curl_connection);
		}

		//Go to the items page
		$scope = $this->driver->getDefaultScope();
		$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/items";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		curl_exec($curl_connection);

		//Post renewal information
		$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/items";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $renewItemParams);
		$checkedOutPageText = curl_exec($curl_connection);

		//Parse the checked out titles into individual rows
		$message = 'Unable to load renewal information for this entry.';
		$success = false;
		if (preg_match('/<h2>\\s*You cannot renew items because:\\s*<\/h2><ul><li>(.*?)<\/ul>/si', $checkedOutPageText, $matches)) {
			$success = false;
			$msg = ucfirst(strtolower(trim($matches[1])));
			$message = "Unable to renew this item: $msg.";
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Failed', $msg);
			}
		}
		elseif (preg_match('/Your record is in use/si', $checkedOutPageText, $matches)) {
			$success = false;
			$message = 'Unable to renew this item now, your account is in use by the system.  Please try again later.';
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Failed', 'Account in Use');
			}
		}
		elseif (preg_match('/<table border="0" class="patFunc">(.*?)<\/table>/s', $checkedOutPageText, $matches)) {
			$checkedOutTitleTable = $matches[1];
			//$logger->log("Found checked out titles table", PEAR_LOG_DEBUG);
			if (preg_match_all('/<tr class="patFuncEntry">(.*?)<\/tr>/s', $checkedOutTitleTable, $rowMatches, PREG_SET_ORDER)){
				//$logger->log("Checked out titles table has " . count($rowMatches) . "rows", PEAR_LOG_DEBUG);
				//$logger->log(print_r($rowMatches, true), PEAR_LOG_DEBUG);
//				for ($i = 0; $i < count($rowMatches); $i++) {
					foreach ($rowMatches as $i => $row) {
					$rowData = $row[1];
					if (preg_match("/{$itemId}/", $rowData)){
						//$logger->log("Found the row for this item", PEAR_LOG_DEBUG);
						//Extract the renewal message
						if (preg_match('/<td align="left" class="patFuncStatus">.*?<em><font color="red">(.*?)<\/font><\/em>.*?<\/td>/s', $rowData, $statusMatches)){
							$success = false;
							$msg = ucfirst(strtolower(trim( $statusMatches[1])));
							$title = $this->extract_title_from_row($rowData);
							$message = "Unable to renew $title: $msg.";
								// title needed for in renewSelectedItems to distinguish which item failed.
						} elseif (preg_match('/<td align="left" class="patFuncStatus">.*?<em>(.*?)<\/em>.*?<\/td>/s', $rowData, $statusMatches)){
							$success = true;
							$message = 'Your item was successfully renewed';
						}
						$logger->log("Renew success = $success, $message", PEAR_LOG_DEBUG);
						break; // found our item, get out of loop.
					}
				}
			}else{
				$logger->log("Did not find any rows for the table $checkedOutTitleTable", PEAR_LOG_DEBUG);
			}
		}
		else{
			$success = true;
			$message = 'Your item was successfully renewed';
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Successful');
			}
		}
		curl_close($curl_connection);
		unlink($cookieJar);

		return array(
			'itemId' => $itemId,
			'result'  => $success,
			'message' => $message);
	}
}
