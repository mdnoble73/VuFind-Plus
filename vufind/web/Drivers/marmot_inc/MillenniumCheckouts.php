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
	/** @var  MillenniumDriver $driver */
	private $driver;

	public function __construct($driver){
		$this->driver = $driver;
	}

	public function getMyTransactions($page = 1, $recordsPerPage = -1, $sortOption = 'dueDate') {
		global $timer;
		$patronDump = $this->driver->_getPatronDump($this->driver->_getBarcode());

		$timer->logTime("Ready to load checked out titles from Millennium");
		//Load the information from millennium using CURL
		$sResult = $this->driver->_fetchPatronInfoPage($patronDump, 'items');
		$timer->logTime("Loaded checked out titles from Millennium");

		$sResult = preg_replace("/<[^<]+?>\W<[^<]+?>\W\d* ITEM.? CHECKED OUT<[^<]+?>\W<[^<]+?>/i", "", $sResult);

		$s = substr($sResult, stripos($sResult, 'patFunc'));

		$s = substr($s,strpos($s,">")+1);

		$s = substr($s,0,stripos($s,"</table"));

		$s = preg_replace ("/<br \/>/","", $s);

		$sRows = preg_split("/<tr([^>]*)>/",$s);
		$sCount = 0;
		$sKeys = array_pad(array(),10,"");
		$checkedOutTitles = array();

		//Get patron's location to determine if renewals are allowed.
		global $locationSingleton;
		/** @var Location $patronLocation */
		$patronLocation = $locationSingleton->getUserHomeLocation();
		if (isset($patronLocation)){
			$patronPType = $this->driver->getPType();
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
						$curTitle['title'] = $title;
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
				//Get additional information from resources table
				if ($curTitle['shortId'] && strlen($curTitle['shortId']) > 0){
					/** @var Resource|object $resource */
					$resource = new Resource();
					$resource->source = 'VuFind';
					$resource->shortId = $curTitle['shortId'];
					if ($resource->find(true)){
						$timer->logTime("Found resource for " . $curTitle['shortId']);
						$curTitle = array_merge($curTitle, get_object_vars($resource));
						$curTitle['recordId'] = $resource->record_id;
						$curTitle['id'] = $resource->record_id;
					}else{
						$timer->logTime("Did not find resource for " . $curTitle['shortId']);
						//echo("Warning did not find resource for {$historyEntry['shortId']}");
					}
				}
				$sortTitle = isset($curTitle['title_sort']) ? $curTitle['title_sort'] : $curTitle['title'];
				$sortKey = $sortTitle;
				if ($sortOption == 'title'){
					$sortKey = $sortTitle;
				}elseif ($sortOption == 'author'){
					$sortKey = (isset($curTitle['author']) ? $curTitle['author'] : "Unknown") . '-' . $sortTitle;
				}elseif ($sortOption == 'dueDate'){
					if (isset($curTitle['duedate'])){
						if (preg_match('/.*?(\\d{1,2})[-\/](\\d{1,2})[-\/](\\d{2,4}).*/', $curTitle['duedate'], $matches)) {
							$sortKey = $matches[3] . '-' . $matches[1] . '-' . $matches[2] . '-' . $sortTitle;
						} else {
							$sortKey = $curTitle['duedate'] . '-' . $sortTitle;
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

			$sCount++;
		}
		ksort($checkedOutTitles);
		$timer->logTime("Parsed checkout information");

		$numTransactions = count($checkedOutTitles);
		//Process pagination
		if ($recordsPerPage != -1){
			$startRecord = ($page - 1) * $recordsPerPage;
			if ($startRecord > $numTransactions){
				$startRecord = 0;
			}
			$checkedOutTitles = array_slice($checkedOutTitles, $startRecord, $recordsPerPage);
		}

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

		$curl_url = $configArray['Catalog']['url'] . "/patroninfo";
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
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$loginResult = curl_exec($curl_connection);
		//When a library uses Encore, the initial login does a redirect and requires additional parameters.
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResult, $loginMatches)) {
			//Get the lt value
			$lt = $loginMatches[1];
			//Login again
			$post_data['lt'] = $lt;
			$post_data['_eventId'] = 'submit';
			$post_items = array();
			foreach ($post_data as $key => $value) {
				$post_items[] = $key . '=' . $value;
			}
			$post_string = implode ('&', $post_items);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$loginResult = curl_exec($curl_connection);
			$curlInfo = curl_getinfo($curl_connection);
		}

		//Go to the items page
		$scope = $this->driver->getDefaultScope();
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/items";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		curl_exec($curl_connection);

		//Post renewal information
		$extraGetInfo = array(
			'currentsortorder' => 'current_checkout',
			'renewall' => 'YES',
		);

		$get_items = array();
		foreach ($extraGetInfo as $key => $value) {
			$get_items[] = $key . '=' . urlencode($value);
		}
		$renewItemParams = implode ('&', $get_items);
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/items";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $renewItemParams);
		$sresult = curl_exec($curl_connection);
		//$logger->log("Result of Renew All\r\n" . $sresult, PEAR_LOG_INFO);

		curl_close($curl_connection);
		unlink($cookieJar);

		//Clear the existing patron info and get new information.
		$hold_result = array();
		$hold_result['Total'] = $curCheckedOut;
		preg_match_all("/RENEWED successfully/si", $sresult, $matches);
		$numRenewals = count($matches[0]);
		$hold_result['Renewed'] = $numRenewals;
		$hold_result['Unrenewed'] = $hold_result['Total'] - $hold_result['Renewed'];
		if ($hold_result['Unrenewed'] > 0) {
			$hold_result['result'] = false;
		}else{
			$hold_result['result'] = true;
			$hold_result['message'] = "All items were renewed successfully.";
		}

		return $hold_result;
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

		$get_items = array();
		foreach ($extraGetInfo as $key => $value) {
			$get_items[] = $key . '=' . urlencode($value);
		}
		$renewItemParams = implode ('&', $get_items);

		//Login to the patron's account
		$cookieJar = tempnam ("/tmp", "CURLCOOKIE");

		$curl_url = $configArray['Catalog']['url'] . "/patroninfo";
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
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$loginResult = curl_exec($curl_connection);
		//When a library uses Encore, the initial login does a redirect and requires additional parameters.
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResult, $loginMatches)) {
			//Get the lt value
			$lt = $loginMatches[1];
			//Login again
			$post_data['lt'] = $lt;
			$post_data['_eventId'] = 'submit';
			$post_items = array();
			foreach ($post_data as $key => $value) {
				$post_items[] = $key . '=' . $value;
			}
			$post_string = implode ('&', $post_items);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$loginResult = curl_exec($curl_connection);
			$curlInfo = curl_getinfo($curl_connection);
		}

		//Go to the items page
		$scope = $this->driver->getDefaultScope();
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/items";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		curl_exec($curl_connection);

		//Post renewal information
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/items";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $renewItemParams);
		$checkedOutPageText = curl_exec($curl_connection);

		//Parse the checked out titles into individual rows
		$message = 'Unable to load renewal information for this entry';
		$success = false;
		if (preg_match('/<h2>\\s*You cannot renew items because:\\s*<\/h2><ul><li>(.*?)<\/ul>/si', $checkedOutPageText, $matches)) {
			$success = false;
			$message = 'Unable to renew this item, ' . strtolower($matches[1]) . '.';
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Failed', strtolower($matches[1]));
			}
		}else if (preg_match('/Your record is in use/si', $checkedOutPageText, $matches)) {
			$success = false;
			$message = 'Unable to renew this item now, your account is in use by the system.  Please try again later.';
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Failed', 'Account in Use');
			}
		}else if (preg_match('/<table border="0" class="patFunc">(.*?)<\/table>/s', $checkedOutPageText, $matches)) {
			$checkedOutTitleTable = $matches[1];
			//$logger->log("Found checked out titles table", PEAR_LOG_DEBUG);
			if (preg_match_all('/<tr class="patFuncEntry">(.*?)<\/tr>/s', $checkedOutTitleTable, $rowMatches, PREG_SET_ORDER)){
				//$logger->log("Checked out titles table has " . count($rowMatches) . "rows", PEAR_LOG_DEBUG);
				//$logger->log(print_r($rowMatches, true), PEAR_LOG_DEBUG);
				for ($i = 0; $i < count($rowMatches); $i++) {
					$rowData = $rowMatches[$i][1];
					if (preg_match("/{$itemId}/", $rowData)){
						//$logger->log("Found the row for this item", PEAR_LOG_DEBUG);
						//Extract the renewal message
						if (preg_match('/<td align="left" class="patFuncStatus">.*?<em><font color="red">(.*?)<\/font><\/em>.*?<\/td>/s', $rowData, $statusMatches)){
							$success = false;
							$message = 'Unable to renew this item, ' . $statusMatches[1];
						}elseif (preg_match('/<td align="left" class="patFuncStatus">.*?<em>(.*?)<\/em>.*?<\/td>/s', $rowData, $statusMatches)){
							$success = true;
							$message = 'Your item was successfully renewed';
						}
						$logger->log("Renew success = $success, $message", PEAR_LOG_DEBUG);
					}
				}
			}else{
				$logger->log("Did not find any rows for the table $checkedOutTitleTable", PEAR_LOG_DEBUG);
			}
		}else{
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
