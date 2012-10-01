<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 */

require_once 'Interface.php';
require_once 'sys/SIP2.php';

class Horizon implements DriverInterface{
	protected $db;
	protected $useDb = true;
	protected $hipUrl;
	protected $hipProfile;
	protected $selfRegProfile;

	function __construct()
	{
		// Load Configuration for this Module
		global $configArray;

		$this->hipUrl = $configArray['Catalog']['hipUrl'];
		$this->hipProfile = $configArray['Catalog']['hipProfile'];
		$this->selfRegProfile = $configArray['Catalog']['selfRegProfile'];
		
		// Connect to database
		if (!isset($configArray['Catalog']['useDb']) || $configArray['Catalog']['useDb'] == true){
			try{
				if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0 ){
					sybase_min_client_severity(11);
					$this->db = @sybase_connect($configArray['Catalog']['database'] ,
					$configArray['Catalog']['username'],
					$configArray['Catalog']['password']);
				}else{
					$this->db = mssql_connect($configArray['Catalog']['host'] . ':' . $configArray['Catalog']['port'],
					$configArray['Catalog']['username'],
					$configArray['Catalog']['password']);
	
					// Select the databse
					mssql_select_db($configArray['Catalog']['database']);
				}
			}catch (Exception $e){
				global $logger;
				$logger->log("Could not load Horizon database", PEAR_LOG_ERR);
			}
		}else{
			$this->useDb = false;
		}
	}

	public function getHolding($id, $record = null, $mysip = null, $forSummary = false){
		global $timer;
		global $configArray;

		$allItems = array();

		$sipInitialized = $mysip != null;

		global $locationSingleton;
		$homeLocation = $locationSingleton->getUserHomeLocation();
		$physicalLocation = $locationSingleton->getPhysicalLocation();
		
		// Retrieve Full Marc Record
		$recordURL = null;

		require_once 'sys/MarcLoader.php';
		$marcRecord = MarcLoader::loadMarcRecordByILSId($id);
		if ($marcRecord) {
			$timer->logTime('Loaded MARC record from search object');
			if (!$configArray['Catalog']['itemLevelCallNumbers']){
				$callNumber = '';
				$callNumberField = $marcRecord->getField('92', true);
				if ($callNumberField != null){
					$callNumberA = $callNumberField->getSubfield('a');
					$callNumberB = $callNumberField->getSubfield('b');
					if ($callNumberA != null){
						$callNumber = $callNumberA->getData();
					}
					if ($callNumberB != null){
						if (strlen($callNumber) > 0){
							$callNumber .= ' ';
						}
						$callNumber .= $callNumberB->getData();
					}
				}
				$timer->logTime('Got call number');
			}
			
			//Get the item records from the 949 tag
			$items = $marcRecord->getFields('949');
			$barcodeSubfield    = $configArray['Catalog']['barcodeSubfield'];
			$locationSubfield   = $configArray['Catalog']['locationSubfield'];
			$itemSubfield       = $configArray['Catalog']['itemSubfield'];
			$callnumberSubfield = $configArray['Catalog']['callnumberSubfield'];
			$statusSubfield     = $configArray['Catalog']['statusSubfield'];
			$firstItemWithSIPdata = null;
			foreach ($items as $itemIndex => $item){
				$barcode = trim($item->getSubfield($barcodeSubfield) != null ? $item->getSubfield($barcodeSubfield)->getData() : '');
				//Check to see if we already have data for this barcode 
				global $memcache;
				if (isset($barcode) && strlen($barcode) > 0){ 
					$itemData = $memcache->get("item_data_{$barcode}_{$forSummary}");
				}else{
					$itemData = false;
				}
				if ($itemData == false){
					//No data exists
				
					$itemData = array();
					$itemId = trim($item->getSubfield($itemSubfield) != null ? $item->getSubfield($itemSubfield)->getData() : '');					

					//Get the barcode from the horizon database
					$itemData['locationCode'] = trim(strtolower( $item->getSubfield($locationSubfield) != null ? $item->getSubfield($locationSubfield)->getData() : '' ));
					$itemData['location'] = $this->translateLocation($itemData['locationCode']);
					
					if (!$configArray['Catalog']['itemLevelCallNumbers'] && $callNumber != ''){
						$itemData['callnumber'] = $callNumber;
					}else{
						$itemData['callnumber'] = trim($item->getSubfield($callnumberSubfield) != null ? $item->getSubfield($callnumberSubfield)->getData() : '');
					}
					$itemData['callnumber'] = str_replace("~", " ", $itemData['callnumber']);
					//Set default status
					$itemData['status'] = trim($item->getSubfield($statusSubfield) != null ? $item->getSubfield($statusSubfield)->getData() : '');
					if ($this->useDb){
						//Get updated status from database
						//Query the database for realtime availability
						$query = "select item_status from item where item# = " . $itemId;
						$itemStatusResult = $this->_query($query);
						$itemsStatus = $this->_fetch_assoc($itemStatusResult);
						if (isset($itemsStatus['item_status']) && strlen($itemsStatus['item_status']) > 0){
							$itemData['status'] = trim($itemsStatus['item_status']);
							$timer->logTime("Got status from database item $itemIndex");
						}
					}
					$availableRegex = "/^({$configArray['Catalog']['availableStatuses']})$/i";
					if (preg_match($availableRegex, $itemData['status']) == 0){
						$itemData['availability'] = false;
					}else{
						$itemData['availability'] = true;
					}
					
					//Make the item holdable by default.  Then check rules to make it non-holdable.
					$itemData['holdable'] = true;
					//Make lucky day items not holdable
					$itemData['luckyDay'] = ($item->getSubfield('t') != null ? preg_match('/^yld.*$/i', $item->getSubfield('t')->getData()) == 1 : false);
					
					$subfield_t = $item->getSubfield('t');
					if ($subfield_t != null){
						$subfield_t = strtolower($subfield_t->getData());
						if (in_array($subfield_t, array('cp', 'lh', 'ill'))){
							//Make local history items and ill items not-holdable
							$itemData['holdable'] = false;
						}
					}
					//Online items are not holdable.
					if (preg_match("/^({$configArray['Catalog']['nonHoldableStatuses']})$/i", $itemData['status'])){
						$itemData['holdable'] = false;
					}

					$itemData['barcode'] = $barcode;
					$itemData['copy'] = $item->getSubfield('e') != null ? $item->getSubfield('e')->getData() : '';
					$itemData['holdQueueLength'] = 0;
					if (strlen($itemData['barcode']) > 0){
						if ($forSummary && $firstItemWithSIPdata != null ){
							$itemData = array_merge($firstItemWithSIPdata, $itemData);
						}else{
							$itemSip2Data = $this->_loadItemSIP2Data($itemData['barcode'], $itemData['status'], $forSummary);
							if ($firstItemWithSIPdata == null){
								$firstItemWithSIPdata = $itemSip2Data;
							}
							$itemData = array_merge($itemData, $itemSip2Data);
						}
					}

					$itemData['collection'] = $this->translateCollection($item->getSubfield('c') != null ? $item->getSubfield('c')->getData() : '');

					$itemData['statusfull'] = $this->translateStatus($itemData['status']);
					//Suppress items based on status
					if (isset($barcode) && strlen($barcode) > 0){ 
						$memcache->set("item_data_{$barcode}_{$forSummary}", $itemData, 0, $configArray['Caching']['item_data']);
					}
				}
				
				$suppressItem = false;
				$statusesToSuppress = $configArray['Catalog']['statusesToSuppress'];
				if (strlen($statusesToSuppress) > 0 && preg_match("/^($statusesToSuppress)$/i", $itemData['status'])){
					$suppressItem = true; 
				}
				//Suppress items based on location
				$locationsToSuppress = $configArray['Catalog']['locationsToSuppress'];
				if (strlen($locationsToSuppress) > 0 && preg_match("/^($locationsToSuppress)$/i", $itemData['locationCode'])){
					//Make sure that the active branch is not the suppresed location
					global $locationSingleton;
					$branch = $locationSingleton->getBranchLocationCode();
					if (!preg_match("/^($locationsToSuppress)$/i", $branch)){
						$suppressItem = true; 
					}
				}
				//Suppress staff items
				$isStaff = false;
				$subfieldO = $item->getSubfield('o');
				if (isset($subfieldO) && is_object($subfieldO) && $subfieldO->getData() == 1){
					$isStaff = true;
					$suppressItem = true; 
				}
					
				if (!$suppressItem){
					$sortString = $itemData['location'] . $itemData['callnumber'] . (count($allItems) + 1);
					if ($physicalLocation != null && strcasecmp($physicalLocation->code, $itemData['locationCode']) == 0){
						$sortString = "1" . $sortString;
					}elseif ($homeLocation != null && strcasecmp($homeLocation->code, $itemData['locationCode']) == 0){
						$sortString = "2" . $sortString;
					}
					$allItems[$sortString] = $itemData;
				}else{
					global $logger;
					$logger->log("item suppressed for barcode $barcode", PEAR_LOG_INFO);
				}
			}
		}
		$timer->logTime("Finished loading status information");

		return $allItems;
	}

	public function getHoldings($idList, $record = null, $mysip = null, $forSummary = false)
	{
		foreach ($idList as $id) {
			$holdings[] = $this->getHolding($id, $record, $mysip, $forSummary);
		}
		return $holdings;
	}

	public function getStatus($id, $record = null, $mysip = null, $forSummary = false)
	{
		return $this->getHolding($id, $record, $mysip, $forSummary);
	}

	public function getStatuses($idList, $record = null, $mysip = null, $forSummary = false)
	{
		return $this->getHoldings($idList, $record, $mysip, $forSummary);
	}

	public function getPurchaseHistory($id)
	{
		return array();
	}

	/** uses SIP2 authentication rather than database authentication **/
	public function patronLogin($username, $password)
	{
		require_once('sys/authn/SIPAuthentication.php');
		$sipAuth = new SIPAuthentication();
		$basicInfo = $sipAuth->validateAccount($username, $password);
		if ($basicInfo){
			$user = array(
                  'id'        => $basicInfo->id,
                  'username'  => $basicInfo->username,
                  'firstname' => $basicInfo->firstname,
                  'lastname'  => $basicInfo->lastname,
                  'fullname'  => $basicInfo->firstname . ' ' . $basicInfo->lastname,     //Added to array for possible display later. 
                  'cat_username' => $username, //Should this be $Fullname or $patronDump['PATRN_NAME']
                  'cat_password' => $password,
                  'displayName' => $basicInfo->displayName,
                  'homeLocationId' => $basicInfo->homeLocationId,
                  'email' => $basicInfo->email,
                  'major' => null,
                  'college' => null);   
			return $user;
		}else{
			//User is not valid.
			return null;
		}
	}

	private $holds = array();
	public function getMyHolds($patron, $page = 1, $recordsPerPage = -1, $sortOption = 'title'){
		global $configArray;
		global $timer;
		
		if (is_object($patron)){
			$patron = get_object_vars($patron);
		}
		if (isset($this->holds[$patron['id']])){
			$holds = $this->holds[$patron['id']];
			$numUnavailableHolds = isset($holds['unavailable']) ? count($holds['unavailable']) : 0;
			return array(
				'holds' => $holds,
				'numUnavailableHolds' => $numUnavailableHolds,
			);
		}
		if ($this->useDb){
			$holds = $this->getMyHoldsViaDB($patron);
		}else{
			$holds = $this->getMyHoldsViaHip($patron);
		}
		$timer->logTime("Retrieved holds from ILS");
		
		//Get a list of all record id so we can load supplemental information 
		$recordIds = array();
		foreach($holds as $section => $holdSections){
			foreach($holdSections as $hold){
				$recordIds[] = "'" . $hold['id'] . "'";
			}
		}
		//Get records from resource table
		$resourceInfo = new Resource();
		if (count($recordIds) > 0){
			$recordIdString = implode(",", $recordIds);
			$resourceSql = "SELECT * FROM resource where source = 'VuFind' AND record_id in ({$recordIdString})";
			$resourceInfo->query($resourceSql);
			$timer->logTime('Got records for all titles');
	
			//Load title author, etc. information
			while ($resourceInfo->fetch()){
				foreach($holds as $section => $holdSections){
					foreach($holdSections as $key => $hold){
						$hold['recordId'] = $hold['id'];
						if ($hold['id'] == $resourceInfo->record_id){
							$hold['shortId'] = $hold['id'];
							//Load title, author, and format information about the title
							$hold['title'] = isset($resourceInfo->title) ? $resourceInfo->title : 'Unknown';
							$hold['sortTitle'] = isset($resourceInfo->title_sort) ? $resourceInfo->title_sort : 'unknown';
							$hold['author'] = isset($resourceInfo->author) ? $resourceInfo->author : null;
							$hold['format'] = isset($resourceInfo->format) ?$resourceInfo->format : null;
							$hold['isbn'] = isset($resourceInfo->isbn) ? $resourceInfo->isbn : '';
							$hold['upc'] = isset($resourceInfo->upc) ? $resourceInfo->upc : '';
							$hold['format_category'] = isset($resourceInfo->format_category) ? $resourceInfo->format_category : '';
							$holds[$section][$key] = $hold;
						}
					}
				}
			}
		}

		foreach($holds as $section => $holdSections){
			foreach($holdSections as $key => $hold){
				$hold['cancelId'] = $hold['id'] . ':' . $hold['itemId'];
				$holds[$section][$key] = $hold;
			}
		}

		//Process sorting
		//echo ("<br/>\r\nSorting by $sortOption");
		foreach ($holds as $sectionName => $section){
			$sortKeys = array();
			$i = 0;
			foreach ($section as $key => $hold){
				$sortTitle = isset($hold['sortTitle']) ? $hold['sortTitle'] : (isset($hold['title']) ? $hold['title'] : "Unknown");
				if ($sectionName == 'available'){
					$sortKeys[$key] = $sortTitle;
				}else{
					if ($sortOption == 'title'){
						$sortKeys[$key] = $sortTitle;
					}elseif ($sortOption == 'author'){
						$sortKeys[$key] = (isset($hold['author']) ? $hold['author'] : "Unknown") . '-' . $sortTitle;
					}elseif ($sortOption == 'placed'){
						$sortKeys[$key] = $hold['createTime'] . '-' . $sortTitle;
					}elseif ($sortOption == 'format'){
						$sortKeys[$key] = (isset($hold['format']) ? $hold['format'] : "Unknown") . '-' . $sortTitle;
					}elseif ($sortOption == 'location'){
						$sortKeys[$key] = (isset($hold['location']) ? $hold['location'] : "Unknown") . '-' . $sortTitle;
					}elseif ($sortOption == 'holdQueueLength'){
						$sortKeys[$key] = (isset($hold['holdQueueLength']) ? $hold['holdQueueLength'] : 0) . '-' . $sortTitle;
					}elseif ($sortOption == 'position'){
						$sortKeys[$key] = str_pad((isset($hold['position']) ? $hold['position'] : 1), 3, "0", STR_PAD_LEFT) . '-' . $sortTitle;
					}elseif ($sortOption == 'status'){
						$sortKeys[$key] = (isset($hold['status']) ? $hold['status'] : "Unknown") . '-' . (isset($hold['reactivateTime']) ? $hold['reactivateTime'] : "0") . '-' . $sortTitle;
					}else{
						$sortKeys[$key] = $sortTitle;
					}
					//echo ("<br/>\r\nSort Key for $key = {$sortKeys[$key]}");
				}

				$sortKeys[$key] = strtolower($sortKeys[$key] . '-' . $i++);
			}
			array_multisort($sortKeys, $section);
			$holds[$sectionName] = $section;
		}

		//Limit to a specific number of records
		if (isset($holds['unavailable'])){
			$numUnavailableHolds = count($holds['unavailable']);
			if ($recordsPerPage != -1){
				$startRecord = ($page - 1) * $recordsPerPage;
				$holds['unavailable'] = array_slice($holds['unavailable'], $startRecord, $recordsPerPage);
			}
		}else{
			$numUnavailableHolds = 0;
		}
		
		if (!isset($holds['available'])){
			$holds['available'] = array();
		}
		if (!isset($holds['unavailable'])){
			$holds['unavailable'] = array();
		}
		//Sort the hold sections so vailable holds are first. 
		ksort($holds);

		$this->holds[$patron['id']] = $holds;
		return array(
			'holds' => $holds,
			'numUnavailableHolds' => $numUnavailableHolds,
		);
	}
	
	public function getMyHoldsViaHip($patron){
		global $user;
		global $configArray;
		global $logger;

		//Setup Curl
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$cookie = tempnam ("/tmp", "CURLCOOKIE");

		//Go to items out page
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$configArray['Catalog']['hipProfile']}&menu=account&submenu=holds";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
		curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl_connection, CURLOPT_HEADER, false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$logger->log("Loading holds $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR::raiseError('Could not load session information from page.');
		}

		//Login by posting username and password
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = array(
      'aspect' => 'overview',
      'button' => 'Login to Your Account',
      'login_prompt' => 'true',
      'menu' => 'account',
      'profile' => $configArray['Catalog']['hipProfile'],
      'ri' => '', 
      'sec1' => $user->cat_username,
      'sec2' => $user->cat_password,
      'session' => $sessionId,
		);
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		preg_match_all('/<input.*?name="(readyholdselected|waitingholdselected)" value="(.*?):(.*?)".*?><\/td>.*?<table.*?>(.*?)<\/table>(.*?)<\/tr>/s', $sresult, $holdItemInfo, PREG_SET_ORDER);
		$holdList = array();
		for ($matchi = 0; $matchi < count($holdItemInfo); $matchi++) {
			$holdInfo = array(
        'id' => $holdItemInfo[$matchi][2],
        'itemId' => $holdItemInfo[$matchi][3] ,
			);
			if ($holdItemInfo[$matchi][1] == 'readyholdselected'){
				$sectionKey = 'available';
				$holdInfo['available'] = true;
				//Get the rest of the details from the table contents
				preg_match('/<td.*>.*?<a.*?>(.*?)<\/a>.*?<a class="normalBlackFont1">(.*?)<\/a>.*?<a class="normalBlackFont2">(?:by )?(.*?)<\/a>.*?<a class="normalBlackFont1">Date Placed: (.*?)<\/a>/s', $holdItemInfo[$matchi][4], $titleDetails);
				$holdInfo['title'] = $titleDetails[1];
				$holdInfo['author'] = $titleDetails[3];
				$holdInfo['callNumber'] = $titleDetails[2];
				$holdInfo['create'] = $titleDetails[4];
				$holdInfo['createTime'] = strtotime($holdInfo['create']);
				preg_match('/.*?<a.*?>(.*?)<\/a>.*?<a.*?>(.*?)<\/a>/s', $holdItemInfo[$matchi][5], $holdDetails);
				$holdInfo['location'] = $holdDetails[1];
				$holdInfo['expire'] = strip_tags($holdDetails[2]);
				$holdInfo['expireTime'] = strtotime($holdInfo['expire']);
			}else{
				$sectionKey = 'unavailable';
				$holdInfo['available'] = false;
				$titleColumnDetails = $holdItemInfo[$matchi][4];
				if (preg_match('/.*?<td.*?>.*?<a.*?>(.*?)<\/a>.*?<a class="normalBlackFont2">(?:by )?(.*?)<\/a>.*?<a class="normalBlackFont1">(.*?)<\/a>.*?<a class="normalBlackFont1">Date Placed: (.*?)<\/a>.*?<a class="normalBlackFont1">Pickup Location: (.*?)<\/a>/s', $titleColumnDetails, $titleDetails)) {
					$holdInfo['title'] = $titleDetails[1];
					$holdInfo['author'] = $titleDetails[2];
					$holdInfo['callNumber'] = $titleDetails[3];
					$holdInfo['create'] = $titleDetails[4];
					$holdInfo['createTime'] = strtotime($holdInfo['create']);
					$holdInfo['location'] = $titleDetails[5];
				}
				if (preg_match('/.*?<a.*?>(.*?)<\/a>.*?<a.*?>(.*?)<\/a>.*?<a.*?>(.*?)<\/a>.*?<a.*?>(.*?)<\/a>/s', $holdItemInfo[$matchi][5], $holdDetails)){
					$holdInfo['status'] = $holdDetails[1];
					$holdInfo['position'] = $holdDetails[2];
					$holdInfo['expire'] = strip_tags($holdDetails[3]);
					$holdInfo['expireTime'] = strtotime($holdInfo['expire']);
					$holdInfo['frozen'] = ($holdDetails[4] != '&nbsp;');
					if ($holdDetails[4] != '&nbsp;'){
						$holdInfo['reactivate'] = $holdDetails[4];
						$holdInfo['reactivateTime'] = strtotime($holdInfo['reactivate']);

						if ($holdInfo['reactivateTime'] < time()){
							$holdInfo['frozen'] = false;
						}
					}
				}
			}
			$holdList[$sectionKey][] = $holdInfo;
		}

		unlink($cookie);
		return $holdList;
	}

public function getMyHoldsViaDB($patron)
	{
		//Load Available Holds from SIP2
		global $configArray;

		global $user;
		$holdList = array();
		if ($this->db == false){
			//return JSON data from production server to get test information
			return json_decode('{"unavailable":[{"id":954359,"location":"Parker","expire":"2013-03-13 12:00:00","expireTime":null,"create":"2011-03-14 12:00:00","createTime":15047,"reactivate":"","reactivateTime":null,"available":false,"position":11,"frozen":false,"itemId":0,"status":"Active"},{"id":895329,"location":"Parker","expire":"2013-03-15 12:00:00","expireTime":null,"create":"2011-03-16 12:00:00","createTime":15049,"reactivate":"","reactivateTime":null,"available":false,"position":1,"frozen":false,"itemId":0,"status":"Active"},{"id":912322,"location":"Parker","expire":"2011-03-21 12:00:00","expireTime":null,"create":"2011-03-14 12:00:00","createTime":15047,"reactivate":"2011-03-21 12:00:00","reactivateTime":15054,"available":false,"position":1,"frozen":true,"itemId":0,"status":"Suspended"},{"id":597608,"location":"Parker","expire":"[No expiration date]","expireTime":null,"create":"2011-03-15 12:00:00","createTime":15048,"reactivate":"","reactivateTime":null,"available":false,"position":1,"frozen":false,"itemId":1309228,"status":"In Transit"}],"available":[{"id":593339,"location":"Parker","expire":"2011-03-21 12:00:00","expireTime":null,"create":"2011-03-15 12:00:00","createTime":15048,"reactivate":"","reactivateTime":null,"available":true,"position":1,"frozen":false,"itemId":2078805,"status":"Available"},{"id":879626,"location":"Parker","expire":"2011-03-19 12:00:00","expireTime":null,"create":"2011-03-11 12:00:00","createTime":15044,"reactivate":"","reactivateTime":null,"available":true,"position":3,"frozen":false,"itemId":1610545,"status":"Available"}]}', true);
			//SIP does not return complete information (missing bib number, freeze information, expiration date, etc).
			$mysip = new sip2;
			$mysip->hostname = $configArray['SIP2']['host'];
			$mysip->port = $configArray['SIP2']['port'];

			if ($mysip->connect()) {
				//send selfcheck status message
				$in = $mysip->msgSCStatus();
				$msg_result = $mysip->get_message($in);

				// Make sure the response is 98 as expected
				if (preg_match("/^98/", $msg_result)) {
					$result = $mysip->parseACSStatusResponse($msg_result);

					//  Use result to populate SIP2 setings
					$mysip->AO = $result['variable']['AO'][0];
					$mysip->AN = $result['variable']['AN'][0];

					$mysip->patron = $patron['username'];
					$mysip->patronpwd = $patron['cat_password'];

					//First get holds that are available
					$in = $mysip->msgPatronInformation('hold');
					$msg_result = $mysip->get_message($in);

					if (preg_match("/^64/", $msg_result)) {
						$result = $mysip->parsePatronInfoResponse( $msg_result );
						$availableHolds = $this->parseSip2Holds($result['variable']['AS'], $mysip);
					} else {
						$availableHolds = new PEAR_Error('patron_info_error_technical');
					}

					//Now get the unavailable holds
					$in = $mysip->msgPatronInformation('unavail');
					$msg_result = $mysip->get_message($in);

					if (preg_match("/^64/", $msg_result)) {
						$result = $mysip->parsePatronInfoResponse( $msg_result );
						$unavailableHolds = $this->parseSip2Holds($result['variable']['CD'], $mysip);
					} else {
						$unavailableHolds = new PEAR_Error('patron_info_error_technical');
					}
				} else {
					$availableHolds = new PEAR_Error('patron_info_error_technical');
				}
				$mysip->disconnect();

				$holdList['available'] = $availableHolds['items'];
				$holdList['unavailable'] = $unavailableHolds['items'];
			}
		}else{
			//Request status 0 = Active
			//               1 = Available
			//               2 = in transit
			/*$sql = "select request.*, ibarcode from request " .
			 "join borrower_barcode on borrower_barcode.borrower#=request.borrower# " .
			 "inner join item on item.item#=request.item# " .
			 "where borrower_barcode.bbarcode=\"" . $patron['username'] . "\" and (request_status = 0 or request_status = 1 or request_status = 2)";*/
			$sql = "select request.* from request " .
                 "join borrower_barcode on borrower_barcode.borrower#=request.borrower# " .
                 "where borrower_barcode.bbarcode='" . $patron['username'] . "' and (request_status = 0 or request_status = 1 or request_status = 2)";

			try {
				$sqlStmt = $this->_query($sql);

				while ($row = $this->_fetch_assoc($sqlStmt)) {
					$available = $row['request_status'] == 1;
					$createDate = ($row['request_date']) ? $this->addDays('1970-01-01', $row['request_date']) : "[Not created]";
					$reactivateDate = ($row['reactivate_date']) ? $this->addDays('1970-01-01', $row['reactivate_date']) : "";
					$location = $this->translateLocation($row['pickup_location']);
					$sectionKey = $available ? 'available' : 'unavailable';
					if ($available){
						$expirationDate = $row['hold_exp_date'];
						$expireDate = ($row['hold_exp_date']) ? $this->addDays('1970-01-01', $row['hold_exp_date']) : "[No expiration date]";
					}else{
						$expirationDate = $row['expire_date'];
						$expireDate = ($row['expire_date']) ? $this->addDays('1970-01-01', $row['expire_date']) : "[No expiration date]";
					}
					switch ($row['request_status']){
						case 0:
							$status = 'Active';
							break;
						case 1:
							$status = 'Available';
							break;
						case 2:
							$status = 'In Transit';
							break;
					}
					$frozen = strlen($row['reactivate_date']) > 0 && strtotime($reactivateDate) > time();
					if ($frozen){
						$status = 'Suspended';
					}else{
						$reactivateDate = '';
					}

					//Get the time that the item will be available on the hold shelf
					if ($available){
						$itemId = $row['item#'];
						$availableTimeQuery = "select b.date, b.time from burb b, item i where b.item# = i.item# and b.block like 'hn%' and b.item# = i.item# and b.ord = 0 and b.item# = " . $itemId;
						$availableTimeRs = $this->_query($availableTimeQuery);
						$availableTimeData = $this->_fetch_array($availableTimeRs);
						$pickup_time=$availableTimeData[1];
						$availableTime = $this->addDays('1970-01-01 00:00:00', $availableTimeData[0]);
						//Allow 3 hours for the book to actually get to the shelf
						$availableTime = $this->addMinutes($availableTime, $availableTimeData[1] + 3 * 60);
						//Round down to the nearest 15 minutes for better presentation
						$availableTimeStamp = strtotime($availableTime);
						$availableTimeStamp = $availableTimeStamp - fmod($availableTimeStamp, 15 * 60);

						$availableTime = date('Y-m-d H:i:s', $availableTimeStamp);
						//echo("itemId - available Time: " . $availableTime);

						$curTime = time();
						if ($availableTimeStamp <= $curTime){
							//Don't show the exact time that a hold goes on shelf if it
							//is already there.
							$availableTime = null;
						}
					}else{
						$availableTime = null;
					}

					$holdList[$sectionKey][] = array('id' => $row['bib#'],
                    'location' => $location,
                    'expire' => $expireDate,
                    'expireTime' => $expirationDate,
                    'create' => $createDate,
                    'createTime' =>  $row['request_date'],
                    'reactivate' => $reactivateDate,
                    'reactivateTime' =>  $row['reactivate_date'],
                    'available' => $available,
                    'position' => $row['bib_queue_ord'],
                    'frozen' => $frozen,
                    'itemId' => isset($row['item#']) ? $row['item#'] : 0 ,
                    'status' => $status,
                    'availableTime' => $availableTime,

					);
				}

				//Lookup the barcode separately since the join above stopped working.
				foreach ($holdList as $sectionKey => $section){
					foreach ($section as $holdKey => $hold){
						//Load the barcode
						if ($hold['itemId'] != 0){
							$sqlStmt = $this->_query("SELECT ibarcode from item WHERE item# = {$hold['itemId']}");
							if ($row = $this->_fetch_assoc($sqlStmt)){
								$hold['barcode'] = $row['ibarcode'];
							}else{
								echo("Could not find barcode for item {$hold['itemId']}");
							}
						}
						$section[$holdKey] = $hold;
					}
					$holdList[$sectionKey] = $section;
				}

				//Sort sections so available are first
				//ksort($holdList);
				return $holdList;
			} catch (PDOException $e) {
				return new PEAR_Error($e->getMessage());
			}
		}
		return $holdList;
	}
	
	/**
	 * Returns a summary of the holdings information for a single id. Used to display
	 * within the search results and at the top of a full record display to ensure
	 * the holding information makes sense to all users.
	 *
	 * @param string $id the id of the bid to load holdings for
	 * @return array an associative array with a summary of the holdings.
	 */
	public function getStatusSummary($id, $record = null, $mysip = null){
		global $timer;
		global $library;
		global $locationSingleton;
		global $configArray;
		global $memcache;
		//Holdings summaries need to be cached based on the actual location since part of the information 
		//includes local call numbers and statuses. 
		$ipLocation = $locationSingleton->getPhysicalLocation();
		$location = $ipLocation;
		if (!isset($location) && $location == null){
			$location = $locationSingleton->getUserHomeLocation();
		}
		if (isset($ipLocation)){
			$ipLibrary = new Library();
			$ipLibrary->libraryId = $ipLocation->getLibraryId;
			if (!$ipLibrary->find(true)){
				$ipLibrary = null;
			}
		}
		if (!isset($location) && $location == null){
			$locationId = -1;
		}else{
			$locationId = $location->locationId;
		}
		$summaryInformation = $memcache->get("holdings_summary_{$id}_{$locationId}" );
		if ($summaryInformation == false){
	
			$canShowHoldButton = true;
			if ($library && $library->showHoldButton == 0){
				$canShowHoldButton = false;
			}
			if ($location != null && $location->showHoldButton == 0){
				$canShowHoldButton = false;
			}
	
			$holdings = $this->getStatus($id, $record, $mysip, true);
			$timer->logTime('Retrieved Status of holding');
	
			$counter = 0;
			$summaryInformation = array();
			$summaryInformation['recordId'] = $id;
			$summaryInformation['shortId'] = $id;
			$summaryInformation['isDownloadable'] = false; //Default value, reset later if needed.
			$summaryInformation['holdQueueLength'] = 0;
	
			//Check to see if we are getting issue summaries or actual holdings
			$isIssueSummary = false;
			$numSubscriptions = 0;
			if (count($holdings) > 0){
				$lastHolding = end($holdings);
				if (isset($lastHolding['type']) && ($lastHolding['type'] == 'issueSummary' || $lastHolding['type'] == 'issue')){
					$isIssueSummary = true;
					$issueSummaries = $holdings;
					$numSubscriptions = count($issueSummaries);
					$holdings = array();
					foreach ($issueSummaries as $issueSummary){
						if (isset($issueSummary['holdings'])){
							$holdings = array_merge($holdings, $issueSummary['holdings']);
						}else{
							//Create a fake holding for subscriptions so something
							//will be displayed in the holdings summary.
							$holdings[$issueSummary['location']] = array(
	                            'availability' => '1',
	                            'location' => $issueSummary['location'],
	                            'libraryDisplayName' => $issueSummary['location'],
	                            'callnumber' => $issueSummary['cALL'],
	                            'status' => 'Lib Use Only',
	                            'statusfull' => 'In Library Use Only',
							);
						}
					}
				}
			}
			$timer->logTime('Processed for subscriptions');
	
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
			foreach ($holdings as $holdingKey => $holding){
				if (is_null($allItemStatus)){
					//Do nothing, the status is not distinct
				}else if ($allItemStatus == ''){
					$allItemStatus = $holding['statusfull'];
				}elseif($allItemStatus != $holding['statusfull']){
					$allItemStatus = null;
				}
				if ($holding['availability'] == true){
					if ($ipLocation && strcasecmp($holding['locationCode'], $ipLocation->code) == 0){
						$availableHere = true;
					}
					$numAvailableCopies++;
					$addToAvailableLocation = false;
					$addToAdditionalAvailableLocation = false;
					//Check to see if the location should be listed in the list of locations that the title is available at.
					//Can only be in this system if there is a system active.
					if (!in_array($holding['locationCode'], array_keys($availableLocations))){
						$locationMapLink = $this->getLocationMapLink($holding['locationCode']);
						if (strlen($locationMapLink) > 0){
							$availableLocations[$holding['locationCode']] = "<a href='$locationMapLink' target='_blank'>" . preg_replace('/\s/', '&nbsp;', $holding['location']) . "</a>";
						}else{
							$availableLocations[$holding['locationCode']] =  $holding['location'];
						}
					}
				}else{
					if ($unavailableStatus == null){
						$unavailableStatus = $holding['statusfull'];
					}
				}
	
				if (isset($holding['holdable']) && $holding['holdable'] == 1){
					$numHoldableCopies++;
				}
				$numCopies++;
				//Check to see if the holding has a download link and if so, set that info.
				if (isset($holding['link'])){
					foreach ($holding['link'] as $link){
						if ($link['isDownload']){
							$summaryInformation['status'] = "Available for Download";
							$summaryInformation['class'] = 'here';
							$summaryInformation['isDownloadable'] = true;
							$summaryInformation['downloadLink'] = $link['link'];
							$summaryInformation['downloadText'] = $link['linkText'];
						}
					}
				}
				//Only show a call number if the book is at the user's home library, one of their preferred libraries, or in the library they are in.
				if (!isset($summaryInformation['callnumber'])){
					$summaryInformation['callnumber'] = $holding['callnumber'];
				}
				if ($holding['availability'] == 1){
					//The item is available within the physical library.  Patron should go get it off the shelf
					$summaryInformation['status'] = "Available At";
					if ($numHoldableCopies > 0){
						$summaryInformation['showPlaceHold'] = $canShowHoldButton;
					}else{
						$summaryInformation['showPlaceHold'] = 0;
					}
					$summaryInformation['class'] = 'available';
				}
				if ($holding['holdQueueLength'] > $summaryInformation['holdQueueLength']){
					$summaryInformation['holdQueueLength'] = $holding['holdQueueLength'];
				}
				if ($firstAvailableBarcode == '' && $holding['availability'] == true){
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
			if ($numCopies < $numCopiesOnOrder){
				$summaryInformation['numCopies'] = $numCopiesOnOrder;
			}else{
				$summaryInformation['numCopies'] = $numCopies;
			}
	
			if ($unavailableStatus != 'ONLINE'){
				$summaryInformation['unavailableStatus'] = $unavailableStatus;
			}
	
			//Status is not set, check to see if the item is downloadable
			if (!isset($summaryInformation['status']) && !isset($summaryInformation['downloadLink'])){
				// Retrieve Full Marc Record
				$recordURL = null;
				// Process MARC Data
				require_once 'sys/MarcLoader.php';
				$marcRecord = MarcLoader::loadMarcRecordByILSId($id);
				if ($marcRecord) {
					//Check the 856 tag to see if there is a URL
					if ($linkField = $marcRecord->getField('856')) {
						if ($linkURLField = $linkField->getSubfield('u')) {
							$linkURL = $linkURLField->getData();
						}
						if ($linkTextField = $linkField->getSubfield('3')) {
							$linkText = $linkTextField->getData();
						}else if ($linkTextField = $linkField->getSubfield('y')) {
							$linkText = $linkTextField->getData();
						}else if ($linkTextField = $linkField->getSubfield('z')) {
							$linkText = $linkTextField->getData();
						}
					}
				} else {
					//Can't process the marc record, ignore it.
				}
				
				//If there is a link, add that status information.
				if (isset($linkURL) ) {
					$isImageLink = preg_match('/.*\.(?:gif|jpg|jpeg|tif|tiff)/i' , $linkURL);
					$isInternalLink = preg_match('/vufind|catalog/i', $linkURL);
					$isPurchaseLink = preg_match('/amazon|barnesandnoble/i', $linkURL);
					if ($isImageLink == 0 && $isInternalLink == 0 && $isPurchaseLink == 0){
						$linkTestText = $linkText . ' ' . $linkURL;
						$isDownload = preg_match('/SpringerLink|NetLibrary|digital media|Online version\.|ebrary|gutenberg|emedia2go/i', $linkTestText);
						if ($linkTestText == 'digital media') $linkText = 'OverDrive';
						if (preg_match('/netlibrary/i', $linkURL)){
							$isDownload = true;
							$linkText = 'NetLibrary';
						}elseif(preg_match('/ebscohost/i', $linkURL)){
							$isDownload = true;
							$linkText = 'Ebsco';
						}elseif(preg_match('/overdrive|emedia2go/i', $linkURL)){
							$isDownload = true;
							$linkText = 'OverDrive';
						}elseif(preg_match('/ebrary/i', $linkURL)){
							$isDownload = true;
							$linkText = 'ebrary';
						}elseif(preg_match('/gutenberg/i', $linkURL)){
							$isDownload = true;
							$linkText = 'Gutenberg Project';
						}elseif(preg_match('/ezproxy/i', $linkURL)){
							$isDownload = true;
						}elseif(preg_match('/.*\.[pdf]/', $linkURL)){
							$isDownload = true;
						}
						if ($isDownload){
							$summaryInformation['status'] = "Available for Download";
							$summaryInformation['class'] = 'here';
							$summaryInformation['isDownloadable'] = true;
							$summaryInformation['downloadLink'] = $linkURL;
							$summaryInformation['downloadText'] = isset($linkText)? $linkText : 'Download';
							//Check to see if this is an eBook or eAudio book.  We can get this from the 245h tag
							$isEBook = true;
							$resource = new Resource();
							$resource->record_id = $id;
							$resource->source = 'VuFind';
							if ($resource->find(true)){
								$formatCategory = $resource->format_category;
								if (strcasecmp($formatCategory, 'eBooks') === 0){
									$summaryInformation['eBookLink'] = $linkURL;
								}elseif (strcasecmp($formatCategory, 'eAudio') === 0){
									$summaryInformation['eAudioLink'] = $linkURL;
								}
							}
						}
					}
				}
				$timer->logTime('Checked for downloadable link in 856 tag');
			}
	
			$showItsHere = ($ipLibrary == null) ? true : ($ipLibrary->showItsHere == 1);
			if ($availableHere && $showItsHere){
				$summaryInformation['status'] = "It's Here";
				$summaryInformation['class'] = 'here';
				unset($availableLocations[$location->code]);
				$summaryInformation['currentLocation'] = $location->displayName;
				$summaryInformation['availableAt'] = join(', ', $availableLocations);
				$summaryInformation['numAvailableOther'] = count($availableLocations);
			}else{
				//Replace all spaces in the name of a location with no break spaces
				$summaryInformation['availableAt'] = join(', ', $availableLocations);
				$summaryInformation['numAvailableOther'] = count($availableLocations);
			}
	
			//If Status is still not set, apply some logic based on number of copies
			if (!isset($summaryInformation['status'])){
				if ($numCopies == 0){
					if ($numCopiesOnOrder > 0){
						//No copies are currently available, but we do have some that are on order.
						//show the status as on order and make it available.
						$summaryInformation['status'] = "On Order";
						$summaryInformation['class'] = 'available';
						$summaryInformation['showPlaceHold'] = $canShowHoldButton;
					}else{
						//Deal with weird cases where there are no items by saying it is unavailable
						$summaryInformation['status'] = "Unavailable";
						$summaryInformation['showPlaceHold'] = false;
						$summaryInformation['class'] = 'unavailable';
					}
				}else{
					if ($numHoldableCopies == 0 && $canShowHoldButton && (isset($summaryInformation['showPlaceHold']) && $summaryInformation['showPlaceHold'] != true)){
						$summaryInformation['status'] = "Not Available For Checkout";
						$summaryInformation['showPlaceHold'] = false;
						$summaryInformation['class'] = 'reserve';
					}else{
						$summaryInformation['status'] = "Checked Out";
						$summaryInformation['showPlaceHold'] = $canShowHoldButton;
						$summaryInformation['class'] = 'checkedOut';
					}
				}
			}
	
			//Reset status if the status for all items is consistent.
			//That way it will jive with the actual full record display.
			if ($allItemStatus != null && $allItemStatus != ''){
				//Only override this for statuses that don't have special meaning
				if ($summaryInformation['status'] != 'Marmot' && $summaryInformation['status'] != 'Available At' && $summaryInformation['status'] != "It's Here"){
					$summaryInformation['status'] = $allItemStatus;
				}
			}
			if ($allItemStatus == 'In Library Use Only'){
				$summaryInformation['inLibraryUseOnly'] = true;
			}else{
				$summaryInformation['inLibraryUseOnly'] = false;
			}
	
	
			if ($summaryInformation['availableCopies'] == 0 && $summaryInformation['isDownloadable'] == true){
				$summaryInformation['showAvailabilityLine'] = false;
			}else{
				$summaryInformation['showAvailabilityLine'] = true;
			}
			$timer->logTime('Finished building summary');
			
			$memcache->set("holdings_summary_{$id}_{$locationId}", $summaryInformation, 0, $configArray['Caching']['holdings_summary']);
		}
		return $summaryInformation;
	}

	/**
	 * Returns summary information for an array of ids.  This allows the search results
	 * to query all holdings at one time.
	 *
	 * @param array $ids an array ids to load summary information for.
	 * @return array an associative array containing a second array with summary information.
	 */
	public function getStatusSummaries($ids){
		global $timer;
		global $configArray;

		//setup connection to SIP2 server
		$mysip = new sip2();
		$mysip->hostname = $configArray['SIP2']['host'];
		$mysip->port = $configArray['SIP2']['port'];

		if ($mysip->connect()) {
			//send selfcheck status message
			$in = $mysip->msgSCStatus();
			$msg_result = $mysip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mysip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
			}
		}
		$timer->logTime('Connected to SIP2 server');

		$items = array();
		if (is_array($ids)){
			$count = 0;
			foreach ($ids as $recordId){
				$items[$count] = $this->getStatusSummary($recordId, null, $mysip);
				$count++;
			}
		}
		return $items;
	}

	public function getMyFines($patron, $includeMessages){
		if ($this->useDb){
			return $this->getMyFinesViaDB($patron, $includeMessages);
		}else{
			return $this->getMyFinesViaHIP($patron, $includeMessages);
		}
	}

	public function getMyFinesViaHIP($patron, $includeMessages){
		global $user;
		global $configArray;
		global $logger;

		//Setup Curl
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$cookie = tempnam ("/tmp", "CURLCOOKIE");

		//Go to items out page
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$configArray['Catalog']['hipProfile']}&menu=account&submenu=blocks";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
		curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl_connection, CURLOPT_HEADER, false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$logger->log("Loading fines $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR::raiseError('Could not load session information from page.');
		}

		//Login by posting username and password
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = array(
      'aspect' => 'overview',
      'button' => 'Login to Your Account',
      'login_prompt' => 'true',
      'menu' => 'account',
      'profile' => $configArray['Catalog']['hipProfile'],
      'ri' => '', 
      'sec1' => $user->cat_username,
      'sec2' => $user->cat_password,
      'session' => $sessionId,
		);
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		preg_match_all('/<tr>.*?<td bgcolor="#FFFFFF"><a class="normalBlackFont2">(.*?)<\/a>.*?<a class="normalBlackFont2">(.*?)<\/a>.*?<a class="normalBlackFont2">(.*?)<\/a>.*?<a class="normalBlackFont2">(.*?)<\/a>.*?<\/tr>/s', $sresult, $messageInfo, PREG_SET_ORDER);
		$messages = array();
		for ($matchi = 0; $matchi < count($messageInfo); $matchi++) {
			$messages[] = array(
                'reason' => $messageInfo[$matchi][1],
                'amount' => $messageInfo[$matchi][3],
                'message' => ($messageInfo[$matchi][2] != '&nbsp;') ? $messageInfo[$matchi][2] : '',
                'date' => $messageInfo[$matchi][4]
			);
		}
		unlink($cookie);
		return $messages;
	}
	public function getMyFinesViaDB($patron, $includeMessages = false)
	{
		global $configArray;
		if (is_object($patron)){
			$patron = get_object_vars($patron);
		}
		global $user;
		$sql = "select title_inverted.title as TITLE, item.bib# as BIB_NUM, item.item# as ITEM_NUM, " .
               "burb.borrower# as BORROWER_NUM, burb.amount as AMOUNT, burb.comment, " .
               "burb.date as DUEDATE, " .
               "burb.block as FINE, burb.amount as BALANCE from burb " .
               "left join item on item.item#=burb.item# " .
		           "left join title_inverted on title_inverted.bib# = item.bib# " .
               "join borrower on borrower.borrower#=burb.borrower# " .
               "join borrower_barcode on borrower_barcode.borrower#=burb.borrower# " .
               "where borrower_barcode.bbarcode='" . $user->cat_username . "'" ;

		if ($includeMessages == false){
			$sql .= " and amount != 0";
		}
		//$sql .= " ORDER BY burb.date ASC";

		//print_r($sql);
		try {
			$sqlStmt = $this->_query($sql);

			$balance = 0;

			while ($row = $this->_fetch_assoc($sqlStmt)) {
				if (preg_match('/infocki|infodue|infocil|infocko|note|spec|supv/i', $row['FINE'])){
					continue;
				}

				//print_r($row);
				$checkout = '';
				$duedate = $this->addDays('1970-01-01', $row['DUEDATE']);
				$bib_num = $row['BIB_NUM'];
				$item_num = $row['ITEM_NUM'];
				$borrower_num = $row['BORROWER_NUM'];
				$amount = $row['AMOUNT'];
				$balance += $amount;
				$comment = is_null($row['comment']) ? $row['TITLE'] : $row['comment'];

				if (isset($bib_num) && isset($item_num))
				{
					$cko = "select date as CHECKOUT " .
                           "from burb where borrower#=" . $borrower_num . " " .
                           "and item#=" . $item_num . " and block='infocko'";
					$sqlStmt_cko = $this->_query($cko);

					if ($row_cko = $this->_fetch_assoc($sqlStmt_cko)) {
						$checkout = $this->addDays('1970-01-01', $row_cko['CHECKOUT']);
					}

					$due = "select convert(varchar(12),dateadd(dd, date, '01 jan 1970')) as DUEDATE " .
                           "from burb where borrower#=" . $borrower_num . " " .
                           "and item#=" . $item_num . " and block='infodue'";
					$sqlStmt_due = $this->_query($due);

					if ($row_due = $this->_fetch_assoc($sqlStmt_due)) {
						$duedate = $row_due['DUEDATE'];
					}
				}

				$fineList[] = array('id' => $bib_num,
                                    'message' => $comment,
                                    'amount' => $amount > 0 ? '$' . sprintf('%0.2f', $amount / 100) : '',
                                    'reason' => $this->translateFineMessageType($row['FINE']),
                                    'balance' => $balance,
                                    'checkout' => $checkout,
                                    'date' => date('M j, Y', strtotime($duedate)));
			}
			return $fineList;
		} catch (PDOException $e) {
			return new PEAR_Error($e->getMessage());
		}

	}

	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "dueDate"){
		require_once('sys/ReadingHistoryEntry.php');
		require_once('services/MyResearch/lib/Resource.php');
		//Reading History is stored within VuFind for each patron.
		global $user;
		$historyActive = $user->trackReadingHistory == 1;

		//Get a list of titles for the user.
		$titles = array();
		$readingHistory = new ReadingHistoryEntry();
		$readingHistorySql = "SELECT * FROM user_reading_history INNER JOIN resource ON user_reading_history.resourceId = resource.id where userId = {$user->id}";
		if ($sortOption == "title"){
			$readingHistorySql .= " order by title_sort ASC, title ASC";
		}elseif ($sortOption == "author"){
			$readingHistorySql .= " order by author ASC, title ASC";
		}elseif ($sortOption == "checkedOut"){
			$readingHistorySql .= " order by firstCheckoutDate DESC, title ASC";
		}elseif ($sortOption == "returned"){
			$readingHistorySql .= " order by lastCheckoutDate DESC, title ASC";
		}elseif ($sortOption == "format"){
			$readingHistorySql .= " order by format DESC, title ASC";
		}

		//Get count of reading history
		$readingHistoryCount = new ReadingHistoryEntry();
		$readingHistoryCount->query($readingHistorySql);
		$numTitles = $readingHistoryCount->N;

		//Get individual titles to display
		if ($recordsPerPage > 0){
			$startRecord = ($page - 1) * $recordsPerPage;
			$readingHistorySql .= " LIMIT $startRecord, $recordsPerPage";
		}
		$readingHistory->query($readingHistorySql);
		if ($readingHistory->N > 0){
			//Load additional details for each title
			global $configArray;
			// Setup Search Engine Connection

			$i = 0;
			$titles = array();
			while ($readingHistory->fetch()){
				$firstCheckoutDate = $readingHistory->firstCheckoutDate;
				$firstCheckoutTime = strtotime($firstCheckoutDate);
				$lastCheckoutDate = $readingHistory->lastCheckoutDate;
				$lastCheckoutTime = strtotime($lastCheckoutDate);
				$titles[] = array(
					'recordId' => $readingHistory->record_id,
					'source' => $readingHistory->source,
					'checkout' => $firstCheckoutDate,
					'checkoutTime' => $firstCheckoutTime,
					'lastCheckout' => $lastCheckoutDate,
					'lastCheckoutTime' => $lastCheckoutTime,
					'title' => $readingHistory->title,
					'title_sort' => $readingHistory->title_sort,
					'author' => $readingHistory->author,
					'format' => $readingHistory->format,
					'format_category' => $readingHistory->format_category,
					'isbn' => $readingHistory->isbn,
					'upc' => $readingHistory->upc,
				);
			}
		}

		return array(
		  'historyActive' => $historyActive,
		  'titles' => array_values($titles),
		  'numTitles' => $numTitles,
		);
	}

	/**
	 * Do an update or edit of reading history information.  Current actions are:
	 * deleteMarked
	 * deleteAll
	 * exportList
	 * optOut
	 * optIn
	 *
	 * @param   array   $patron         The patron array
	 * @param   string  $action         The action to perform
	 * @param   array   $selectedTitles The titles to do the action on if applicable
	 */
	function doReadingHistoryAction($patron, $action, $selectedTitles){
		require_once('sys/ReadingHistoryEntry.php');
		global $user;
		//Reading History Information is stored in the VuFind database
		if ($action == 'deleteMarked'){
			//Remove selected titles from the database
			foreach ($selectedTitles as $selectedId => $selectValue){
				//Get the resourceid for the bib
				$resource = new Resource();
				if (is_numeric($selectValue)){
					$resource->record_id = $selectValue;
				}else{
					$resource->record_id = $selectedId;
				}
				$resource->find();
				if ($resource->N){
					$resource->fetch();
					$resourceId = $resource->id;
					$readingHistory = new ReadingHistoryEntry();
					$readingHistory->userId = $user->id;
					$readingHistory->resourceId = $resourceId;
					$readingHistory->delete();
				}
			}
		}elseif ($action == 'deleteAll'){
			//remove all titles from the database
			$readingHistory = new ReadingHistoryEntry();
			$readingHistory->userId = $user->id;
			$readingHistory->delete();
		}elseif ($action == 'exportList'){
			//Export the list (not currently implemented)
		}elseif ($action == 'optOut'){
			//remove all titles from the database
			$readingHistory = new ReadingHistoryEntry();
			$readingHistory->userId = $user->id;
			$readingHistory->delete();

			//Stop recording reading history and remove all titles from the database
			$user->trackReadingHistory = 0;
			$user->update();
			UserAccount::updateSession($user);

		}elseif ($action == 'optIn'){
			//Start recording reading history
			$user->trackReadingHistory = 1;
			$user->update();
			UserAccount::updateSession($user);
		}
	}

private $patronProfiles = array();
	public function getMyProfile($patron) {
		global $timer;
		global $configArray;
		if (is_object($patron)){
			$patron = get_object_vars($patron);
		}
		if (array_key_exists($patron['username'], $this->patronProfiles)){
			$timer->logTime('Retrieved Cached Profile for Patron');
			return $this->patronProfiles[$patron['username']];
		}

		$mysip = new sip2;
		$mysip->hostname = $configArray['SIP2']['host'];
		$mysip->port = $configArray['SIP2']['port'];

		if ($mysip->connect()) {
			//send selfcheck status message
			$in = $mysip->msgSCStatus();
			$msg_result = $mysip->get_message($in);

			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mysip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */

				$mysip->patron = $patron['username'];
				$mysip->patronpwd = $patron['cat_password'];

				$in = $mysip->msgPatronInformation('fine');
				$msg_result = $mysip->get_message($in);

				// Make sure the response is 24 as expected
				if (preg_match("/^64/", $msg_result)) {
					$result = $mysip->parsePatronInfoResponse( $msg_result );
					$address = $result['variable']['BD'][0];
					$addressParts = explode(',', $address);
					$expirationDate = $result['variable']['PE'][0];
					$formattedExpiration = substr($expirationDate, 4,2) . '/' . substr($expirationDate, 6,2) . '/' . substr($expirationDate, 0,4);
					//$fines = $this->parseSip2Fines($result['variable']['AV']);
					$location = new Location();
					$location->code = $result['variable']['AQ'][0];
					$location->find();
					if ($location->N > 0){
						$location->fetch();
						$homeLocationId = $location->locationId;
					}
					global $user;
					
					$profile= array(
            'lastname' => $result['variable']['DJ'][0],
            'firstname' => isset($result['variable']['DH'][0]) ? $result['variable']['DH'][0] : '',
            'displayName' => $patron['displayName'],
            'fullname' => $result['variable']['AE'][0],
            'address1' => trim($addressParts[0]),
            'city' => trim($addressParts[1]),
            'state' => trim($addressParts[2]),
            'zip' => isset($addressParts[3]) ? trim($addressParts[3]) : '',
            'phone' => isset($result['variable']['BF'][0]) ? $result['variable']['BF'][0] : '',
            'email' => isset($result['variable']['BE'][0]) ? $result['variable']['BE'][0] : '',
            'homeLocationId' => isset($homeLocationId) ? $homeLocationId : -1,
            'homeLocationName' => $this->translateLocation($result['variable']['AQ'][0]),
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
					require_once('Drivers/EContentDriver.php');
					$eContentDriver = new EContentDriver(); 
					$eContentAccountSummary = $eContentDriver->getAccountSummary();
					$profile = array_merge($profile, $eContentAccountSummary);
					
					//Get a count of the materials requests for the user
					$materialsRequest = new MaterialsRequest();
					$materialsRequest->createdBy = $user->id;
					$statusQuery = new MaterialsRequestStatus();
					$statusQuery->isOpen = 1;
					$materialsRequest->joinAdd($statusQuery);
					$materialsRequest->find();
					$profile['numMaterialsRequests'] = $materialsRequest->N;
				} else {
					$profile = new PEAR_Error('patron_info_error_technical');
				}
			} else {
				$profile = new PEAR_Error('patron_info_error_technical');
			}
			$mysip->disconnect();

		} else {
			$profile = new PEAR_Error('patron_info_error_technical');
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

		if (!$this->useDb){
			//Get transactions by parsing hip
			$transactions = $this->getMyTransactionsViaHIP($patron);
			$timer->logTime("Got transactions from HIP");
			//return json_decode('[{"id":843869,"itemid":1466355,"duedate":"2011-03-29 12:00:00","checkoutdate":"2011-03-07 12:00:00","barcode":"33025015826504","renewCount":1,"request":null},{"id":944097,"itemid":1897017,"duedate":"2011-03-28 12:00:00","checkoutdate":"2011-03-07 12:00:00","barcode":"33025021052830","renewCount":0,"request":null},{"id":577167,"itemid":2057415,"duedate":"2011-03-29 12:00:00","checkoutdate":"2011-03-07 12:00:00","barcode":"33025021723778","renewCount":3,"request":null}]', true);
		}else{
			$transactions = $this->getMyTransactionsViaDB($patron);
			$timer->logTime("Got transactions from Database");
		}

		if (isset($transactions)){
			//Load information about titles from Resources table (for peformance)
			$recordIds = array();
			foreach ($transactions as $i => $data) {
				$recordIds[] = "'" . $data['id'] . "'";
			}
			//Get records from resource table
			$resourceInfo = new Resource();
			if (count($recordIds) > 0){
				$recordIdString = implode(",", $recordIds);
				$resourceSql = "SELECT * FROM resource where source = 'VuFind' AND record_id in ({$recordIdString})";
				$resourceInfo->query($resourceSql);
	
				$timer->logTime('Got records for all titles');
	
				//Load title author, etc. information
				while ($resourceInfo->fetch()){
					foreach ($transactions as $key => $transaction){
						if ($transaction['id'] == $resourceInfo->record_id){
							$transaction['shortId'] = $transaction['id'];
							//Load title, author, and format information about the title
							$transaction['recordId'] = $transaction['id'];
							$transaction['title'] = isset($resourceInfo->title) ? $resourceInfo->title : 'Unknown';
							$transaction['sortTitle'] = isset($resourceInfo->title_sort) ? $resourceInfo->title_sort : 'unknown';
							$transaction['author'] = isset($resourceInfo->author) ? $resourceInfo->author : null;
							$transaction['format'] = isset($resourceInfo->format) ?$resourceInfo->format : null;
							$transaction['isbn'] = isset($resourceInfo->isbn) ? $resourceInfo->isbn : '';
							$transaction['upc'] = isset($resourceInfo->upc) ? $resourceInfo->upc : '';
							$transaction['format_category'] = isset($resourceInfo->format_category) ? $resourceInfo->format_category : '';
							$transaction['renewIndicator'] = $transaction['barcode'] . '|';
							$transactions[$key] = $transaction;
						}
					}
				}
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
		return array(
			'transactions' => $transactions,
			'numTransactions' => $totalTransactions
		);
	}
	
	private $sipInitialized = false;
	private $mysip = false;
	private function _loadItemSIP2Data($barcode, $itemStatus){
		global $memcache;
		global $configArray;
		global $timer;
		$itemSip2Data = $memcache->get("item_sip2_data_{$barcode}");
		if ($itemSip2Data == false){
			//Check to see if the SIP2 information is already cached
			if ($this->sipInitialized == false){
				//setup connection to SIP2 server
				$this->mysip = new sip2();
				$this->mysip->hostname = $configArray['SIP2']['host'];
				$this->mysip->port = $configArray['SIP2']['port'];

				if ($this->mysip->connect()) {
					//send selfcheck status message
					$in = $this->mysip->msgSCStatus();
					$msg_result = $this->mysip->get_message($in);
					// Make sure the response is 98 as expected
					if (preg_match("/^98/", $msg_result)) {
						$result = $this->mysip->parseACSStatusResponse($msg_result);

						//  Use result to populate SIP2 setings
						$this->mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
						$this->mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
					}
				}
				$this->sipInitialized = true;
				$timer->logTime('Connected to SIP2 server');
			}
				
			$in = $this->mysip->msgItemInformation($barcode);
			$msg_result = $this->mysip->get_message($in);

			// Make sure the response is 18 as expected
			if (preg_match("/^18/", $msg_result)) {
				$result = $this->mysip->parseItemInfoResponse( $msg_result );
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
				if ($configArray['Catalog']['realtimeLocations'] == true && isset($result['variable']['AQ'][0])){
					//Looks like horizon is returning these backwards via SIP.  
					//AQ should be current, but is always returning the same code. 
					//AP should be permanent, but is returning the current location
					//echo("Permanent location " . $result['variable']['AQ'][0] . " current location " . $result['variable']['AP'][0] . "\r\n");
					$itemSip2Data['locationCode'] = $result['variable']['AQ'][0];
					$itemSip2Data['location'] = $this->translateLocation($itemData['locationCode']);
				}
				if (!$this->useDb){
					//Override circulation status based on SIP
					if ($result['fixed']['CirculationStatus'] == 4){
						$itemSip2Data['status'] = 'o';
						$itemSip2Data['availability'] = false;
					}
				}
			}
			$memcache->set("item_sip2_data_{$barcode}", $itemSip2Data, 0, $configArray['Caching']['item_sip2_data']);
			$timer->logTime("Got due date and hold queue length from SIP 2 for barcode $barcode");
		}
		return $itemSip2Data;
	}
	public function getMyTransactionsViaHIP($patron){
		global $user;
		global $configArray;
		global $logger;

		//Setup Curl
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$cookie = tempnam ("/tmp", "CURLCOOKIE");

		//Go to items out page
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$configArray['Catalog']['hipProfile']}&menu=account&submenu=itemsout";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
		curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl_connection, CURLOPT_HEADER, false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$logger->log("Loading items out $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR::raiseError('Could not load session information from page.');
		}

		//Login by posting username and password
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = array(
      'aspect' => 'overview',
      'button' => 'Login to Your Account',
      'login_prompt' => 'true',
      'menu' => 'account',
      'profile' => $configArray['Catalog']['hipProfile'],
      'ri' => '', 
      'sec1' => $user->cat_username,
      'sec2' => $user->cat_password,
      'session' => $sessionId,
		);
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		preg_match_all('/<tr>.*?name="renewitemkeys" value="(.*?)".*?<tr><td>.*?full=3100001~!(\\d+)~!.*?>(.*?)<\/a><\/td>.*?<a class="normalBlackFont1">(.*?)<\/a><\/td>.*?<a class="normalBlackFont2">(.*?)<\/a>.*?<a class="normalBlackFont2">(.*?)<\/a><\/td>.*?<a class="normalBlackFont2">(.*?)<\/a><\/td>.*?<\/tr>/s', $sresult, $checkedOutItemInfo, PREG_SET_ORDER);
		$checkedOutItems = array();
		for ($matchi = 0; $matchi < count($checkedOutItemInfo); $matchi++) {
			$dueTime = strtotime($checkedOutItemInfo[$matchi][6]);
			$daysUntilDue = ceil(($dueTime - time()) / (24 * 60 * 60));
			$overdue = $daysUntilDue < 0;
			$checkedOutItems[$checkedOutItemInfo[$matchi][1]] = array(
        'id' => $checkedOutItemInfo[$matchi][2],
        'itemid' => $checkedOutItemInfo[$matchi][1],
        'duedate' => $checkedOutItemInfo[$matchi][6],
        'checkoutdate' => date('Y-m-d H:i:s', strtotime($checkedOutItemInfo[$matchi][5])),
        'barcode' => $checkedOutItemInfo[$matchi][1],
        'renewCount' => $checkedOutItemInfo[$matchi][7],
        'request' => null,
        'title' => $checkedOutItemInfo[$matchi][3],
        'author' => $checkedOutItemInfo[$matchi][4],
        'overdue' => $overdue,
        'daysUntilDue' => $daysUntilDue,
			);
		}
		unlink($cookie);
		return $checkedOutItems;
	}
	public function getMyTransactionsViaDB($patron){
		global $user;
		$sql = "select item.bib# as BIB_NUM, item.ibarcode as ITEM_BARCODE, " .
               "item.due_date, " .
         "item.last_cko_date, item.item#, " .
               "item.n_renewals as RENEW, request.bib_queue_ord as REQUEST from circ " .
               "join item on item.item#=circ.item# " .
               "join borrower on borrower.borrower#=circ.borrower# " .
               "join borrower_barcode on borrower_barcode.borrower#=circ.borrower# " .
               "left outer join request on request.item#=circ.item# " .
               "where borrower_barcode.bbarcode='" . $user->cat_username . "'";

		//print_r($sql);
		try {
			$sqlStmt = $this->_query($sql);

			$transList = array();
			while ($row = $this->_fetch_assoc($sqlStmt)) {
				$dueDate = $this->addDays('1970-01-01', $row['due_date']);
				//Convert date to the proper format for sorting.
				$dueTime = strtotime($dueDate);
				$daysUntilDue = ceil(($dueTime - time()) / (24 * 60 * 60));
				$overdue = $daysUntilDue < 0;
				$dueDate = date('m/d/Y', $dueTime);
				$checkoutDate = $this->addDays('1970-01-01', $row['last_cko_date']);
				$transList[$row['ITEM_BARCODE']] = array(
          'id' => $row['BIB_NUM'],
          'itemid' => $row['item#'],
          'duedate' => $dueDate,
          'checkoutdate' => $checkoutDate,
          'barcode' => $row['ITEM_BARCODE'],
          'renewCount' => $row['RENEW'],
          'request' => $row['REQUEST'],
          'overdue' => $overdue,
          'daysUntilDue' => $daysUntilDue,
				);
			}

			return $transList;
		} catch (PDOException $e) {
			return new PEAR_Error($e->getMessage());
		}
	}

public function renewItem($patronId, $itemId){
		$locationSingleton = new Location();
		$ipLocation = $locationSingleton->getIPLocation();
		$ipId = $locationSingleton->getIPid();

		if (false){
			$ret =  $this->renewItemViaHIP($patronId, $itemId);
		}else{
			$ret =  $this->renewItemViaSIP($patronId, $itemId);
		}
		if ($ret['result'] == true){
			// Log the usageTracking data
			$usageTracking = new UsageTracking();
			$usageTracking->logTrackingData('numRenewals', 1, $ipLocation, $ipId);
		}

		return $ret;
	}

	public function renewItemViaHIP($patronId, $itemId){
		global $user;
		global $configArray;

		global $logger;

		$originalCheckedOutItems = $this->getMyTransactions($user);

		//Setup Curl
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$cookie = tempnam ("/tmp", "CURLCOOKIE");

		//Start at My Account Page
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$configArray['Catalog']['hipProfile']}&menu=account";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
		curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl_connection, CURLOPT_HEADER, false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$logger->log("Loading Full Record $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR::raiseError('Could not load session information from page.');
		}

		//Login by posting username and password
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = array(
      'aspect' => 'overview',
      'button' => 'Login to Your Account',
      'login_prompt' => 'true',
      'menu' => 'account',
      'profile' => $configArray['Catalog']['hipProfile'],
      'ri' => '', 
      'sec1' => $user->cat_username,
      'sec2' => $user->cat_password,
      'session' => $sessionId,
		);
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		//Renew the item
		$post_data = array(
      'menu' => 'account',
      'profile' => $configArray['Catalog']['hipProfile'],
      'renewitems' => 'Renew',
      'renewitemkeys' => $itemId, //(array of barcodes)
      'session' => $sessionId,
      'submenu' => 'itemsout',
		);

		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		$updatedCheckedOutItems = $this->getMyTransactions($user);
		$originalItem = $originalCheckedOutItems['transactions'][$itemId];
		$updatedItem = $updatedCheckedOutItems['transactions'][$itemId];
		if ($originalItem['renewCount'] == $updatedItem['renewCount']){
			$success = false;
			$message = "This item could not be renewed.";
		}else{
			$success = true;
			$message = "Item renewed successfully.";

			// Log the usageTracking data
			$usageTracking = new UsageTracking();
			$usageTracking->logTrackingData('numRenewals', 1, $ipLocation);
		}

		unlink($cookie);

		return array(
                    'title' => $originalItem['title'],
                    'itemId' => $itemId,
                    'result'  => $success,
                    'message' => $message);
	}

	public function renewItemViaSIP($patronId, $itemId, $useAlternateSIP = false){
		global $configArray;
		global $user;

		//renew the item via SIP 2
		$mysip = new sip2();
		$mysip->hostname = $configArray['SIP2']['host'];
		if ($useAlternateSIP){
			$mysip->port = $configArray['SIP2']['alternate_port'];
		}else{
			$mysip->port = $configArray['SIP2']['port'];
		}

		$hold_result['itemId'] = $itemId;
		$hold_result['title'] = $itemId;
		$hold_result['result'] = false;
		if ($mysip->connect()) {
			//send selfcheck status message
			$in = $mysip->msgSCStatus();
			$msg_result = $mysip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mysip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				$mysip->patron = $user->cat_username;
				$mysip->patronpwd = $user->cat_password;

				$in = $mysip->msgRenew($itemId, '', '', '', 'N', 'N', 'Y');
				//print_r($in . '<br/>');
				$msg_result = $mysip->get_message($in);
				//print_r($msg_result);

				if (preg_match("/^30/", $msg_result)) {
					$result = $mysip->parseRenewResponse($msg_result );
					
					$hold_result['title'] = $result['variable']['AJ'][0];
					
					//print_r($result);
					$hold_result['result'] = ($result['fixed']['Ok'] == 1);
					$hold_result['message'] = $result['variable']['AF'][0];
					
					//If the renew fails, check to see if we need to override the SIP port
					$alternatePortSet = false;
					if (isset($configArray['SIP2']['alternate_port']) && strlen($configArray['SIP2']['alternate_port']) > 0 && $configArray['SIP2']['alternate_port'] != $configArray['SIP2']['port']){
						$alternatePortSet = true;
					}
					if ($alternatePortSet && $hold_result['result'] == false && $useAlternateSIP == false){
						//Can override the SIP port if there are sufficient copies on the shelf to cover any holds
						
						//Get the id for the item 
						$searchObject = SearchObjectFactory::initSearchObject();
						$class = $configArray['Index']['engine'];
						$url = $configArray['Index']['url'];
						$index = new $class($url);
						if ($configArray['System']['debugSolr']) {
							$index->debug = true;
						}
						
						$record = $index->getRecordByBarcode($itemId);
						
						if ($record){
							//Get holdings summary
							$statusSummary = $this->getStatusSummary($record['id'], $record, $mysip);
							
							//If # of available copies >= waitlist change sip port and renew
							if ($statusSummary['availableCopies'] >= $statusSummary['holdQueueLength']){
								$hold_result = $this->renewItemViaSIP($patronId, $itemId, true);
							}
						}
					}
				}
			}
		}else{
			$hold_result['message'] = "Could not connect to circulation server, please try again later.";
		}
		
		return $hold_result;
	}

	public function renewAll($patronId){
		global $configArray;
		global $user;
		$locationSingleton = new Location();
		$ipLocation = $locationSingleton->getIPLocation();
		$ipId = $locationSingleton->getIPid();

		//renew the item via SIP 2
		$mysip = new sip2();
		$mysip->hostname = $configArray['SIP2']['host'];
		$mysip->port = $configArray['SIP2']['port'];

		$hold_result['result'] = false;
		if ($mysip->connect()) {
			//send selfcheck status message
			$in = $mysip->msgSCStatus();
			$msg_result = $mysip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mysip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				$mysip->patron = $user->cat_username;
				$mysip->patronpwd = $user->cat_password;

				$in = $mysip->msgRenewAll();
				//print_r($in);

				$msg_result = $mysip->get_message($in);
				//print_r($msg_result);

				if (preg_match("/^66/", $msg_result)) {
					$result = $mysip->parseRenewAllResponse($msg_result );
					//print_r($result);
					$numRenewed = $result['fixed']['Renewed'];
					$numUnrenewed = $result['fixed']['Unrenewed'];
					$hold_result['result'] = ($result['fixed']['Ok'] == 1);
					if ($numUnrenewed > 0){
						$totalItemsToRenew = $numRenewed + $numUnrenewed;
						$hold_result['Renewed'] = intval($result['fixed']['Renewed']);
						$hold_result['Unrenewed'] = intval($result['fixed']['Unrenewed']);
						$hold_result['Total'] = $totalItemsToRenew;
						$hold_result['message'] = "$numRenewed of $totalItemsToRenew items were renewed successfully.";
					}else{
						$hold_result['message'] = "All items were renewed successfully.";
					}
					if ($numRenewed > 0){
						// Log the usageTracking data
						$usageTracking = new UsageTracking();
						$usageTracking->logTrackingData('numRenewals', $numRenewed, $ipLocation, $ipId);
					}
				}
			}
		}
		return $hold_result;
	}

	function updatePatronInfo($password){
		global $configArray;
		global $user;
		require_once 'Drivers/marmot_inc/BadWord.php';

		$updateErrors = array();
		//Check to make sure the patron alias is valid if provided
		if (isset($_REQUEST['displayName']) && $_REQUEST['displayName'] != $user->displayName && strlen($_REQUEST['displayName']) > 0){
			//make sure the display name is less than 15 characters
			if (strlen($_REQUEST['displayName']) > 15){
				$updateErrors[] = 'Sorry your display name must be 15 characters or less.';
				return $updateErrors;
			}else{
				//Make sure that we are not using bad words
				$badWords = new BadWord();
				$badWordsList = $badWords->getBadWordExpressions();
				$okToAdd = true;
				foreach ($badWordsList as $badWord){
					if (preg_match($badWord,$_REQUEST['displayName'])){
						$okToAdd = false;
						break;
					}
				}
				if (!$okToAdd){
					$updateErrors[] = 'Sorry, that name is in use or invalid.';
					return $updateErrors;
				}
				//Make sure no one else is using that
				$userValidation = new User();
				$userValidation->query("SELECT * from {$userValidation->__table} WHERE id <> {$user->id} and displayName = '{$_REQUEST['displayName']}'");
				if ($userValidation->N > 0){
					$updateErrors[] = 'Sorry, that name is in use or invalid.';
					return $updateErrors;
				}
			}
		}

		global $logger;

		//Setup Curl
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$cookie = tempnam ("/tmp", "CURLCOOKIE");

		//Start at My Account Page
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$configArray['Catalog']['hipProfile']}&menu=account";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
		curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl_connection, CURLOPT_HEADER, false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$logger->log("Loading Full Record $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR::raiseError('Could not load session information from page.');
		}

		//Login by posting username and password
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = array(
      'aspect' => 'overview',
      'button' => 'Login to Your Account',
		//'ipp' => '20',
		//'lastlogin' => '1299616721524',
      'login_prompt' => 'true',
      'menu' => 'account',
		//'npp' => '10',
      'profile' => $configArray['Catalog']['hipProfile'],
      'ri' => '', 
      'sec1' => $user->cat_username,
      'sec2' => $user->cat_password,
      'session' => $sessionId,
		//'spp' => '20'
		);
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		//update patron information.  Use HIP to update the e-mail to make sure that all business rules are followed.
		if (isset($_REQUEST['email'])){
			$post_data = array(
        'menu' => 'account',
        'newemailtext' => $_REQUEST['email'],
        'newpin' => '', 
        'oldpin' => '', 
        'profile' => $configArray['Catalog']['hipProfile'],
        'renewpin' => '', 
        'session' => $sessionId,
        'submenu' => 'info',
        'updateemail' => 'Update',
			);
			$post_items = array();
			foreach ($post_data as $key => $value) {
				$post_items[] = $key . '=' . urlencode($value);
			}
			$post_string = implode ('&', $post_items);
			$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$sresult = curl_exec($curl_connection);

			//check for errors in boldRedFont1
			if (preg_match('/<td.*?class="boldRedFont1".*?>(.*?)(?:<br>)*<\/td>/si', $sresult, $matches)) {
				$updateErrors[] = $matches[1];
			}else{
				//Update the users cat_password in the VuFind database
				$user->email = $_REQUEST['email'];
				$user->update();
				UserAccount::updateSession($user);
			}
		}
		if (isset($_REQUEST['oldPin']) && strlen($_REQUEST['oldPin']) > 0 && isset($_REQUEST['newPin']) && strlen($_REQUEST['newPin']) > 0){

			$post_data = array(
        'menu' => 'account',
        'newemailtext' => $_REQUEST['email'],
        'newpin' => $_REQUEST['newPin'],  
        'oldpin' => $_REQUEST['oldPin'],  
        'profile' => $configArray['Catalog']['hipProfile'],
        'renewpin' => $_REQUEST['verifyPin'],
        'session' => $sessionId,
        'submenu' => 'info',
        'updatepin' => 'Update',
			);
			$post_items = array();
			foreach ($post_data as $key => $value) {
				$post_items[] = $key . '=' . urlencode($value);
			}
			$post_string = implode ('&', $post_items);
			$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$sresult = curl_exec($curl_connection);

			//check for errors in boldRedFont1
			if (preg_match('/<td.*?class="boldRedFont1".*?>(.*?)(?:<br>)*<\/td>/', $sresult, $matches)) {
				$updateErrors[] = $matches[1];
			}else{
				//Update the users cat_password in the VuFind database
				$user->cat_password = $_REQUEST['newPin'];
				$user->update();
				UserAccount::updateSession($user);
			}
		}
		//check to see if the user has provided an alias
		if ((isset($_REQUEST['displayName']) && $_REQUEST['displayName'] != $user->displayName) ||
		(isset($_REQUEST['disableRecommendations']) && $_REQUEST['disableRecommendations'] != $user->disableRecommendations) ||
		(isset($_REQUEST['disableCoverArt']) && $_REQUEST['disableCoverArt'] != $user->disableCoverArt) || 
		(isset($_REQUEST['bypassAutoLogout']) && $_REQUEST['bypassAutoLogout'] != $user->bypassAutoLogout)){
			$user->displayName = $_REQUEST['displayName'];
			$user->disableRecommendations = $_REQUEST['disableRecommendations'];
			$user->disableCoverArt = $_REQUEST['disableCoverArt'];
			if (isset($_REQUEST['bypassAutoLogout'])){
				$user->bypassAutoLogout = $_REQUEST['bypassAutoLogout'] == 'yes' ? 1 : 0;
			}
			$user->update();
			UserAccount::updateSession($user);
		}

		unlink($cookie);

		return $updateErrors;
	}

	public function placeHold($recordId, $patronId, $comment, $type){
		//Self registered cards need to use HIP to place holds 
		if (preg_match('/^\\d{12}-\\d$/', $patronId)){
			$result = $this->placeHoldViaHIP($recordId, $patronId, $comment, $type);
		}else{
			$result = $this->placeHoldViaSIP($recordId, $patronId, $comment, $type);
		}

		if ($result['result'] == true){
			//Make a call to strands to update that the item was added to the list
			global $configArray;
			global $user;
			if (isset($configArray['Strands']['APID']) && $user->disableRecommendations == 0){
				$strandsUrl = "http://bizsolutions.strands.com/api2/event/addshoppingcart.sbs?needresult=true&apid={$configArray['Strands']['APID']}&item={$recordId}&user={$user->id}";
				$ret = file_get_contents($strandsUrl);
			}

			// Log the usageTracking data
			UsageTracking::logTrackingData('numHolds');
		}
		return $result;
	}

	public function placeHoldViaHIP($recordId, $patronId, $comment, $type)
	{
		global $user;
		global $configArray;
		global $logger;
		$profile = $this->getMyProfile($user);

		//Setup Curl
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$cookie = tempnam ("/tmp", "CURLCOOKIE");

		//Go to full record in HIP
		//http://hip.douglascountylibraries.org/ipac20/ipac.jsp?full=3100001~!973657~!0
		//http://hip.douglascountylibraries.org/ipac20/ipac.jsp?full=<<hardcoded value>>~!<<record id>>~!<<index>>
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?full=3100001~!$recordId~!0&profile={$configArray['Catalog']['selfRegProfile']}";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
		curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl_connection, CURLOPT_HEADER, false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$logger->log("Loading Full Record $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR::raiseError('Could not load session information from page.');
		}

		//Go to the request copy page (login)
		//http://hip.douglascountylibraries.org/ipac20/ipac.jsp?session=<<sessionId>>&profile=rem&bibkey=<<record id>>&aspect=subtab54&lang=eng&menu=request&submenu=none&source=~!horizon&uri=&time=<<ms since 1970>>
		$curTime = time();
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?session=$sessionId&profile={$configArray['Catalog']['selfRegProfile']}&bibkey=$recordId&aspect=subtab54&lang=eng&menu=request&submenu=none&source=~!horizon&uri=&time=$curTime";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		$sresult = curl_exec($curl_connection);

		//Call post to http://hip.douglascountylibraries.org/ipac20/ipac.jsp
		//with the following parameters:
		// - aspect=subtab54
		// - bibkey=<<record id>>
		// - lang=eng
		// - menu=request
		// - session = <<sessionId>>
		// - submenu=none
		// - time = <<currenttime>>
		// - sec1 = <<cat_username>>
		// - sec2 = <<cat_password>>
		// - uri = link=direct
		// - ipp = 20?? - may not be needed
		// - npp = 10?? - may not be needed
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = array();
		$post_data['aspect'] = 'subtab54';
		$post_data['bibkey'] = $recordId;
		$post_data['lang'] = 'eng';
		$post_data['menu'] = 'request';
		$post_data['session'] = $sessionId;
		$post_data['submenu'] = 'none';
		$post_data['time'] = $curTime;
		$post_data['sec1'] = $user->cat_username;
		$post_data['sec2'] = $user->cat_password;
		$post_data['uri'] = 'link=direct';
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);


		//Parse the results page to get hold queue position and effective date
		//Set pickup location and confirm the hold by calling GET to
		//http://hip.douglascountylibraries.org/ipac20/ipac.jsp
		$notifyBy = null;
		if (preg_match('/Request Confirmation/', $sresult)) {
			//The request is successful
		} elseif (preg_match('/name="notifyby" value="(.*?)"/', $sresult, $matches)) {
			$notifyBy = $matches[1];
		} else {
			//May have faile to place the hold
			if (preg_match('/<font size="3" color="white" face="Arial, Helvetica"><b>(.*?)<\/b><\/font>/', $sresult, $matches)) {
				$failureReason = $matches[1];
			} else {
				PEAR::raiseError('Could not get notify by or failure information from page.');
			}

		}
		if (!isset($failureReason)){
			if (isset($_REQUEST['campus'])){
				$campus=trim($_REQUEST['campus']);
			}else{
				$campus = $profile['homeLocationId'];
				//Get the code for the location
				$locationLookup = new Location();
				$locationLookup->locationId = $campus;
				$locationLookup->find();
				if ($locationLookup->N > 0){
					$locationLookup->fetch();
					$campus = $locationLookup->code;
				}
			}
			$post_data = array();
			$post_data['aspect'] = 'none';
			$post_data['cl'] = 'PlaceRequestjsp';
			$post_data['notifyby'] = (isset($notifyBy) && !is_null($notifyBy)) ? $notifyBy : 'phone';
			$post_data['pickuplocation'] = $pickupLocation;
			$post_data['profile'] = $this->selfRegProfile;
			$post_data['session'] = $sessionId;
			$post_data['request_finish'] = 'Request';
			$post_items = array();
			foreach ($post_data as $key => $value) {
				$post_items[] = $key . '=' . urlencode($value);
			}
			$post_string = implode ('&', $post_items);
			$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
			curl_setopt($curl_connection, CURLOPT_POST, true);
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$sresult = curl_exec($curl_connection);
			
			if (preg_match('/Must specify a valid pickup location/', $sresult)){
				$failureReason = "Sorry the pickup location could not be recognized.";
			}
			
		}

		$hold_result = array();

		$hold_result['title'] = $this->getRecordTitle($recordId);
		if (preg_match('/Your request has been successfully placed/', $sresult)){
			$hold_result['result'] = true;
		}else{
			$hold_result['result'] = false;
			$hold_result['message'] = $failureReason;
		}
		unlink($cookie);
		return $hold_result;

	}

	public function getRecordTitle($recordId){
		//Get the title of the book.
		global $configArray;
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$this->db->debug = true;
		}

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($recordId))) {
			$title = null;
		}else{
			if (isset($record['title_full'][0])){
				$title = $record['title_full'][0];
			}else{
				$title = $record['title'];
			}
		}
		return $title;
	}

	public function placeHoldViaSIP($recordId, $patronId, $comment, $type){
		global $configArray;
		global $user;
		//Place the hold via SIP 2
		$mysip = new sip2();
		$mysip->hostname = $configArray['SIP2']['host'];
		$mysip->port = $configArray['SIP2']['port'];

		$hold_result = array();
		$hold_result['result'] = false;
		if ($mysip->connect()) {
			//send selfcheck status message
			$in = $mysip->msgSCStatus();
			$msg_result = $mysip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mysip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				$mysip->patron = $user->cat_username;
				$mysip->patronpwd = $user->cat_password;
				
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
				
				//expire the hold in 2 years by default
				$expirationTime = time() + 2 * 365 * 24 * 60 * 60;
				$in = $mysip->msgHold($mode, $expirationTime, '2', '', $recordId, '', $campus);
				$msg_result = $mysip->get_message($in);

				$hold_result['title'] = $this->getRecordTitle($recordId);
				$hold_result['id'] = $recordId;
				if (preg_match("/^16/", $msg_result)) {
					$result = $mysip->parseHoldResponse($msg_result );
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
			}
		}
		return $hold_result;
	}

	/**
	 * Update a hold that was previously placed in the system.
	 * Can cancel the hold or update pickup locations.
	 */
	public function updateHoldDetailed($requestId, $patronId, $type, $title, $xnum, $cancelId, $locationId, $freezeValue='off') {
		if (false){
			$ret = $this->updateHoldDetailedViaSIP($requestId, $patronId, $type, $title, $xnum, $cancelId, $locationId, $freezeValue);
		}else{
			$ret = $this->updateHoldDetailedViaHIP($requestId, $patronId, $type, $title, $xnum, $cancelId, $locationId, $freezeValue);
		}
		return $ret;
	}

	/**
	 * Update a hold that was previously placed in the system.
	 * Can cancel the hold or update pickup locations.
	 */
	private function updateHoldDetailedViaSIP($requestId, $patronId, $type, $title, $xnum, $cancelId, $locationId, $freezeValue='off') {
		global $logger;
		global $configArray;
		global $user;

		$originalHolds['holds'] = $this->getMyHolds($user);
		//Place the hold via SIP 2
		$mysip = new sip2();
		$mysip->hostname = $configArray['SIP2']['host'];
		$mysip->port = $configArray['SIP2']['port'];

		$result = array();
		if ($mysip->connect()) {
			//send selfcheck status message
			$in = $mysip->msgSCStatus();
			$msg_result = $mysip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mysip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				$mysip->patron = $user->cat_username;
				$mysip->patronpwd = $user->cat_password;
				//place the hold
				if ($type == 'cancel' || $type == 'recall'){
					$mode = '-';
				}elseif ($type == 'update'){
					$mode = '*';
				}

				//TODO: This needs to be finished if we can determine how to suspend holds.
			}
		}
		return array();
	}

	/**
	 * Update a hold that was previously placed in the system.
	 * Can cancel the hold or update pickup locations.
	 */
	private function updateHoldDetailedViaHIP($requestId, $patronId, $type, $title, $xnum, $cancelId, $locationId, $freezeValue='off') {
		global $logger;
		global $configArray;
		global $user;

		$originalHolds = $this->getMyHolds($user);

		//Login to the account
		//Setup Curl
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$cookie = tempnam ("/tmp", "CURLCOOKIE");

		//Start at My Account Page
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$this->hipProfile}&menu=account";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
		curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl_connection, CURLOPT_HEADER, false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$logger->log("Logging in to my account $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR::raiseError('Could not load session information from page.');
		}

		//Login by posting username and password
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = array(
      'aspect' => 'overview',
      'button' => 'Login to Your Account',
      'login_prompt' => 'true',
      'menu' => 'account',
      'profile' => $this->hipProfile,
      'ri' => '', 
      'sec1' => $user->cat_username,
      'sec2' => $user->cat_password,
      'session' => $sessionId,
		);
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		if ($type == 'cancel' || $type == 'recall'){
			$post_data = array(
        'cancelhold' => 'Cancel Request',
        'menu' => 'account',
        'profile' => $this->hipProfile,
        'session' => $sessionId,
        'submenu' => 'holds',
        'suspend_date' => '',
			);
			//add ready holds that are selected
			//add waiting holds that are selected
			$post_items = array();
			foreach ($post_data as $key => $value) {
				$post_items[] = $key . '=' . urlencode($value);
			}
			if (isset($_REQUEST['waitingholdselected'])){
				if (is_array($_REQUEST['waitingholdselected'])){
					foreach ($_REQUEST['waitingholdselected'] as $holdId){
						$post_items[] = 'waitingholdselected=' . urlencode($holdId);
					}
				}else{
					$post_items[] = 'waitingholdselected=' . urlencode($_REQUEST['waitingholdselected']);
				}
			}
			if (isset($_REQUEST['availableholdselected'])){
				if (is_array($_REQUEST['availableholdselected'])){
					foreach ($_REQUEST['availableholdselected'] as $holdId){
						$post_items[] = 'readyholdselected=' . urlencode($holdId);
					}
				}else{
					$post_items[] = 'readyholdselected=' . urlencode($_REQUEST['availableholdselected']);
				}
			}
			$post_string = implode ('&', $post_items);
			$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$sresult = curl_exec($curl_connection);
		}elseif ($type == 'update'){
			if (isset($_REQUEST['suspendDate'])){
				$suspendDate = $_REQUEST['suspendDate'];
			}else{
				$suspendDate = date('mm-dd-yyyy');
			}
			$dateParts = date_parse($suspendDate);
			$currentTime = strtotime($suspendDate) . '000'; //Convert seconds to milliseconds
			//The freeze/hold functionality is just a toggle in HIP
			$post_data = array(
        'changestatus' => 'Change Status',
        'menu' => 'account',
        'profile' => $this->hipProfile,
        'select1' => $dateParts['month'],
        'select2' => $dateParts['day'],
        'select3' => $dateParts['year'] ,
        'session' => $sessionId,
        'submenu' => 'holds',
        'suspend_date' => $currentTime,
			);
			//add ready holds that are selected
			//add waiting holds that are selected
			$post_items = array();
			foreach ($post_data as $key => $value) {
				$post_items[] = $key . '=' . urlencode($value);
			}
			if (isset($_REQUEST['waitingholdselected'])){
				if (is_array($_REQUEST['waitingholdselected'])){
					foreach ($_REQUEST['waitingholdselected'] as $holdId){
						$post_items[] = 'waitingholdselected=' . urlencode($holdId);
					}
				}else{
					$post_items[] = 'waitingholdselected=' . urlencode($_REQUEST['waitingholdselected']);
				}
			}
			$post_string = implode ('&', $post_items);
			$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$sresult = curl_exec($curl_connection);

		}

		unlink($cookie);

		if ($type == 'cancel' || $type == 'recall'){
			//Clear the holds if any exist
			if (isset($this->holds[$user->id])){
				unset($this->holds[$user->id]);
			}
			$newHolds = $this->getMyHolds($user);
			$numAvailableHoldsCancelled = isset($_REQUEST['availableholdselected']) ? count($_REQUEST['availableholdselected']) : 0;
			$numActiveHoldsCancelled = isset($_REQUEST['waitingholdselected']) ? count($_REQUEST['waitingholdselected']) : 0;
			$success = true;
			$numAvailableHoldsOriginal = isset($originalHolds['holds']['available']) ? count($originalHolds['holds']['available']) : 0;
			$numAvailableHoldsNew = isset($newHolds['holds']['available']) ? count($newHolds['holds']['available']) : 0;
			if ($numAvailableHoldsOriginal - $numAvailableHoldsNew != $numAvailableHoldsCancelled){
				$success = false;
			}
			$numUnavailableHoldsOriginal = isset($originalHolds['holds']['unavailable']) ? count($originalHolds['holds']['unavailable']) : 0;
			$numUnavailableHoldsNew = isset($newHolds['holds']['unavailable']) ? count($newHolds['holds']['unavailable']) : 0;
			if ($numUnavailableHoldsOriginal - $numUnavailableHoldsNew != $numActiveHoldsCancelled){
				$success = false;
			}
			if ($success){
				return array(
                    'title' => $title,
                    'result' => true,
                    'message' => 'Your hold was cancelled successfully.');
			}else{
				return array(
                    'title' => $title,
                    'result' => false,
                    'message' => 'Your hold could not be cancelled.  Please try again later or see your librarian.');
			}
		}else{
			return array(
                    'title' => $title,
                    'result' => true,
                    'message' => 'Your hold was updated successfully.');
		}
	}
	
private function parseSip2Fines($finesData){
		$fines = array();
		$totalFines = 0;
		//Fines come in as an array of strings with the following fields.
		//barcode/item number, amount, title
		foreach ($finesData as $data){
			if (preg_match('/^([\\d\\s-]+?)\\$(\\d+\\.\\d+)\\s"(\\w+)\\"\\s(.*)$/m', $data, $matches)) {
				$fineInfo = array(
          'itemNumber' => trim($matches[1]),
          'fineAmount' => $matches[2],
          'title' => trim($matches[3]),
				);
				$fines['items'][] = $fineInfo;
				$totalFines +=  $fineInfo['fineAmount'];
			} else {
				//Fine data not in the expected format.
			}
		}
		$fines['total'] = $totalFines;
		$fines['count'] = count($finesData);
		$fines['formatted'] = sprintf('$%01.2f', $totalFines);
		return $fines;
	}
	
	private function parseSip2Holds($holdData, $mysip){
		$holds = array();
		//Fines come in as an array of strings with the following fields.
		//barcode/item number, amount, title
		if (isset($holdData)){
			foreach ($holdData as $data){
				if (preg_match('/^([\\d]+|acq\\d+)\\s(\\w{2,4})\\s(\\d{2}\/\\d{2}\/\\d{2})\\s\\$(\\d+\\.\\d+)\\s(\\w)\\s(.*)$/m', $data, $matches)) {
					//get information about the item
					$holdInfo = array(
            'itemId' => trim($matches[1]),
            'location' => $this->translateLocation($matches[2]),
            'owningBranchCode' => trim($matches[2]),
            'create' => trim($matches[3]),
            'feeAmount' => $matches[4],
            'holdStatus' => $matches[5], //???? Need to verify this
            'title' => $matches[6],
					);

					//Get additional information about the item from SIP2
					$in = $mysip->msgItemInformation($holdInfo['itemId']);
					$msg_result = $mysip->get_message($in);
					$result = $mysip->parseItemInfoResponse($msg_result);
					if (isset($result['variable']['CF'])){
						$holdInfo['position'] = $result['variable']['CF'];
					}
					if (isset($result['variable']['AH'])){
						$holdInfo['dueDate'] = $result['variable']['AH'];
					}
					if (isset($result['variable']['CF'])){

					}
					//query solr to get the bib number based on the item number (barcode)


					$holds['items'][] = $holdInfo;
				} else {
					//Hold data not in the expected format.
				}
			}
			$holds['count'] = count($holdData);
		}else{
			$holds['count'] = 0;
		}
		return $holds;
	}

	/*
	 * Return an item in the catalog.  Should be called with care to not incorrectly return items.
	 */
	public function checkInItem($barcode){
		global $configArray;

		//Place the hold via SIP 2
		$mysip = new sip2();
		$mysip->hostname = $configArray['SIP2']['host'];
		$mysip->port = $configArray['SIP2']['online_port'];

		$hold_result['result'] = false;
		if ($mysip->connect()) {
			//send selfcheck status message
			$in = $mysip->msgSCStatus();
			$msg_result = $mysip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mysip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 setings
				$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				$mysip->patron = $user->cat_username;
				$mysip->patronpwd = $user->cat_password;

				$in = $mysip->msgCheckin($barcode, $mysip->_datestamp());
				$msg_result = $mysip->get_message($in);

				if (preg_match("/^10/", $msg_result)) {
					$result = $mysip->parseCheckinResponse($msg_result );
					$checkout_result['result'] = ($result['fixed']['Ok'] == 1);
					$checkout_result['success'] = $checkout_result['result'];
					$checkout_result['message'] = $result['variable']['AF'][0];
					$checkout_result['sipinput'] = $in;
					$checkout_result['sipresult'] = $result;
				}
			}
		}
		return $checkout_result;
	}

	private function parseSip2ChargedItems($chargedData){
		$chargedItems = array();
		$chargedItems['count'] = count($chargedData);
		return $chargeedItems;
	}

	function addDays($givendate,$day) {
		$cd = strtotime($givendate);
		$newdate = date('Y-m-d H:i:s', mktime(date('H',$cd),
		date('i',$cd), date('s',$cd), date('m',$cd),
		date('d',$cd)+$day, date('Y',$cd)));
		return $newdate;
	}

	function addMinutes($givendate,$minutes) {
		$cd = strtotime($givendate);
		$newdate = date('Y-m-d H:i:s', mktime(date('H',$cd),
		date('i',$cd) + $minutes, date('s',$cd), date('m',$cd),
		date('d',$cd), date('Y',$cd)));
		return $newdate;
	}

	protected function _query($query){
		global $configArray;
		if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0){
			return sybase_query($query);
		}else{
			return mssql_query($query);
		}
	}
	
	protected function _fetch_assoc($result_id){
		global $configArray;
		if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0){
			return sybase_fetch_assoc($result_id);
		}else{
			return mssql_fetch_assoc($result_id);
		}
	}
	
	protected function _fetch_array($result_id){
		global $configArray;
		if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0){
			return sybase_fetch_array($result_id);
		}else{
			return mssql_fetch_array($result_id);
		}
	}
	
/**
	 * Email the user's pin number to the account on record if any exists.
	 */
	function emailPin($barcode){
		global $configArray;
		if ($this->useDb){
			$sql = "SELECT name, borrower.borrower#, bbarcode, pin#, email_name, email_address from borrower inner join borrower_barcode on borrower.borrower# = borrower_barcode.borrower# inner join borrower_address on borrower.borrower# = borrower_address.borrower#  where bbarcode= '" . mysql_escape_string($barcode) . "'";

			try {
				$sqlStmt = $this->_query($sql);
				$foundPatron = false;
				while ($row = $this->_fetch_assoc($sqlStmt)) {
					$pin = $row['pin#'];
					$email = $row['email_address'];
					$foundPatron = true;
					break;
				}

				if ($foundPatron){
					if (strlen($email) == 0){
						return array('error' => 'Your account does not have an email address on record. Please visit your local library to retrieve your PIN number.');
					}
					require_once 'sys/Mailer.php';

					$mailer = new VuFindMailer();
					$subject = "PIN number for your Library Card";
					$body = "The PIN number for your Library Card is $pin.  You may use this PIN number to login to your account.";
					$mailer->send($email, $configArray['Site']['email'],$subject, $body);
					return array(
						'success' => true,
						'pin' => $pin,
						'email' => $email,
					);
				}else{
					return array('error' => 'Sorry, we could not find an account with that barcode.');
				}
			} catch (PDOException $e) {
				return array(
					'error' => 'Unable to ready you PIN from the database.  Please try again later.'
					);
			}
		}else{
			$result = array(
				'error' => 'This functionality requires a connection to the database.',
			);
		}
		return $result;
	}
}