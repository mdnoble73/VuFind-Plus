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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once ROOT_DIR . '/sys/Proxy_Request.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/CirculationStatus.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LoanRule.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LoanRuleDeterminer.php';
require_once ROOT_DIR . '/Drivers/ScreenScrapingDriver.php';

/**
 * Pika Connector for Marmot's Innovative catalog (millennium)
 *
 * This class uses screen scraping techniques to gather record holdings written
 * by Adam Bryn of the Tri-College consortium.
 *
 * @author Adam Brin <abrin@brynmawr.com>
 *
 * Extended by Mark Noble and CJ O'Hara based on specific requirements for
 * Marmot Library Network.
 *
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @author CJ O'Hara <cj@marmot.org>
 */
class Millennium extends ScreenScrapingDriver
{
	public $fixShortBarcodes = true;

	var $statusTranslations = null;
	var $holdableStatiRegex = null;
	var $availableStatiRegex = null;
	/** @var  Solr */
	public $db;

	private static function loadLibraryLocationInformation() {
		if (Millennium::$libraryLocationInformationLoaded == false){
			//Get a list of all locations for the active library
			global $library;
			global $timer;
			$userLibrary = Library::getPatronHomeLibrary();
			Millennium::$libraryLocations = array();
			Millennium::$libraryLocationLabels = array();
			$libraryLocation = new Location();
			if ($userLibrary){
				$libraryLocation->libraryId = $userLibrary->libraryId;
				$libraryLocation->find();
				while ($libraryLocation->fetch()){
					Millennium::$libraryLocations[] = $libraryLocation->code;
					Millennium::$libraryLocationLabels[$libraryLocation->code] = $libraryLocation->facetLabel;
				}
			}else{
				$libraryLocation->libraryId = $library->libraryId;
				$libraryLocation->find();
				while ($libraryLocation->fetch()){
					Millennium::$libraryLocations[] = $libraryLocation->code;
					Millennium::$libraryLocationLabels[$libraryLocation->code] = $libraryLocation->facetLabel;
				}
			}
			Millennium::$homeLocationCode = null;
			Millennium::$homeLocationLabel = null;
			$searchLocation = Location::getSearchLocation();
			if ($searchLocation){
				Millennium::$homeLocationCode = $searchLocation->code;
				Millennium::$homeLocationLabel = $searchLocation->facetLabel;
			}else{
				$homeLocation = Location::getUserHomeLocation();
				if ($homeLocation){
					Millennium::$homeLocationCode = $homeLocation->code;
					Millennium::$homeLocationLabel = $homeLocation->facetLabel;
				}
			}

			$timer->logTime("Finished loading location data");

			Millennium::$scopingLocationCode = '';

			$searchLibrary = Library::getSearchLibrary();
			$searchLocation = Location::getSearchLocation();
			if (isset($searchLibrary)){
				Millennium::$scopingLocationCode = $searchLibrary->ilsCode;
			}
			if (isset($searchLocation)){
				Millennium::$scopingLocationCode = $searchLocation->code;
			}
			Millennium::$libraryLocationInformationLoaded = true;
		}
	}

	/**
	 * Load information about circulation statuses from the database
	 * so we can perform translations easily and so we can determine
	 * what is available and what is not available
	 *
	 * @return void
	 */
	protected function loadCircStatusInfo(){
		if (is_null($this->holdableStatiRegex)){
			$circStatus = new CirculationStatus();
			$circStatus->find();
			$holdableStati = array();
			$availableStati = array();
			if ($circStatus->N > 0){
				while ($circStatus->fetch()){
					if ($circStatus->holdable == 1){
						$holdableStati[] = $circStatus->millenniumName;
					}
					if ($circStatus->available == 1){
						$availableStati[] = $circStatus->millenniumName;
					}
					if (isset($circStatus->displayName) && is_string($circStatus->displayName) && strlen($circStatus->displayName) > 0){
						$this->statusTranslations[$circStatus->millenniumName] = $circStatus->displayName;
					}
				}
			}
			//Holdable statuses are statuses where the patron could get the item in a reasonable amount of time if they place a hold.
			$this->holdableStatiRegex = implode('|', $holdableStati);
			//Available statuses are statuses where the patron can walk into the library and get it pretty much immediately.
			$this->availableStatiRegex = implode('|', $availableStati);
		}
	}

	/** @var LoanRule[] $loanRules  */
	var $loanRules = null;
	/** @var LoanRuleDeterminer[] $loanRuleDeterminers */
	var $loanRuleDeterminers = null;

	protected function loadLoanRules(){
		if (is_null($this->loanRules)){
			/** @var Memcache $memCache */
			global $memCache;
			global $configArray;
			global $serverName;
			$this->loanRules = $memCache->get($serverName . '_loan_rules');
			if (!$this->loanRules || isset($_REQUEST['reload'])){
				$this->loanRules = array();
				$loanRule = new LoanRule();
				$loanRule->find();
				while ($loanRule->fetch()){
					$this->loanRules[$loanRule->loanRuleId] = clone($loanRule);
				}
			}
			$memCache->set($serverName . '_loan_rules', $this->loanRules, 0, $configArray['Caching']['loan_rules']);

			$this->loanRuleDeterminers = $memCache->get($serverName . '_loan_rule_determiners');
			if (!$this->loanRuleDeterminers || isset($_REQUEST['reload'])){
				$this->loanRuleDeterminers = array();
				$loanRuleDeterminer = new LoanRuleDeterminer();
				$loanRuleDeterminer->active = 1;
				$loanRuleDeterminer->orderBy('rowNumber DESC');
				$loanRuleDeterminer->find();
				while ($loanRuleDeterminer->fetch()){
					$this->loanRuleDeterminers[$loanRuleDeterminer->rowNumber] = clone($loanRuleDeterminer);
				}
			}
			$memCache->set($serverName . '_loan_rule_determiners', $this->loanRuleDeterminers, 0, $configArray['Caching']['loan_rules']);
		}
	}

	public function isUserStaff(){
		global $configArray;
		global $user;
		if (count($user->getRoles()) > 0){
			return true;
		}else if (isset($configArray['Staff P-Types'])){
			$staffPTypes = $configArray['Staff P-Types'];
			$pType = $this->getPType();
			if (array_key_exists($pType, $staffPTypes)){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	public function getMillenniumScope(){
		if (isset($_REQUEST['useUnscopedHoldingsSummary'])){
			return $this->getDefaultScope();
		}
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();

		$branchScope = '';
		//Load the holding label for the branch where the user is physically.
		if (!is_null($searchLocation)){
			if ($searchLocation->useScope && $searchLocation->restrictSearchByLocation){
				$branchScope = $searchLocation->scope;
			}
		}
		if (strlen($branchScope)){
			return $branchScope;
		}else if (isset($searchLibrary) && $searchLibrary->useScope && $searchLibrary->restrictSearchByLibrary) {
			return $searchLibrary->scope;
		}else{
      return $this->getDefaultScope();
		}
	}

	public function getLibraryScope(){
		if (isset($_REQUEST['useUnscopedHoldingsSummary'])){
			return $this->getDefaultScope();
		}
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();

		$branchScope = '';
		//Load the holding label for the branch where the user is physically.
		if (!is_null($searchLocation)){
			if (isset($searchLocation->scope) && $searchLocation->scope > 0){
				$branchScope = $searchLocation->scope;
			}
		}
		if (strlen($branchScope)){
			return $branchScope;
		}else if (isset($searchLibrary) && isset($searchLibrary->scope) && $searchLibrary->scope > 0) {
			return $searchLibrary->scope;
		}else{
			return $this->getDefaultScope();
		}
	}

	public function getDefaultScope(){
		global $configArray;
		return isset($configArray['OPAC']['defaultScope']) ? $configArray['OPAC']['defaultScope'] : '93';
	}

	public function getMillenniumRecordInfo($id){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumCache.php';
		$scope = $this->getMillenniumScope();
		//Load the pages for holdings, order information, and items
		$millenniumCache = new MillenniumCache();
		$millenniumCache->recordId = $id;
		$millenniumCache->scope = $scope;
		global $timer;
		$host = $this->getVendorOpacUrl();

		// Strip ID
		$id_ = substr(str_replace('.b', '', $id), 0, -1);

		$req =  $host . "/search~S{$scope}/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/holdings~" . $id_;
		$millenniumCache->holdingsInfo = file_get_contents($req);
		//$logger->log("Loaded holdings from url $req", PEAR_LOG_DEBUG);
		$timer->logTime('got holdings from millennium');

		$req =  $host . "/search~S{$scope}/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/frameset~" . $id_;
		$millenniumCache->framesetInfo = file_get_contents($req);
		$timer->logTime('got frameset info from millennium');

		$millenniumCache->cacheDate = time();

		return $millenniumCache;

	}

	static $libraryLocationInformationLoaded = false;
	static $libraryLocations = null;
	static $libraryLocationLabels = null;
	static $homeLocationCode = null;
	static $homeLocationLabel = null;
	static $scopingLocationCode = null;

	/**
	 * Loads items information as quickly as possible (no direct calls to the ILS).  Does do filtering by loan rules
	 *
	 * return is an array of items with the following information:
	 *  location
	 *  call number
	 *  available
	 *  holdable
	 *  lastStatusCheck (time)
	 *
	 * @param $id              string The ID of the Record
	 * @param $scopingEnabled  boolean Limit Items by scoping
	 * @param $marcRecord      MarcRecord|null  Pass the MarcRecord Object if it has already been created
	 * @return mixed           Array of items' information
	 */
	public function getItemsFast($id, $scopingEnabled, $marcRecord = null){
		if ($marcRecord == null){
			$marcRecord = MarcLoader::loadMarcRecordByILSId($id);
			global $timer;
			$timer->logTime("Finished loading MARC Record for getItemsFast");
		}

		Millennium::loadLibraryLocationInformation();

		//Get the items Fields from the record
		/** @var File_MARC_Data_Field[] $itemFields */
		$itemFields = $marcRecord->getFields('989');
		global $timer;
		$timer->logTime("Finished loading item fields for $id, found " . count($itemFields));
		$items = array();
		$pType = $this->getPType();
		//$timer->logTime("Finished loading pType");

		global $configArray;
		$statusSubfield = $configArray['Reindex']['statusSubfield'];
		$iTypeSubfield = $configArray['Reindex']['iTypeSubfield'];
		$dueDateSubfield = $configArray['Reindex']['dueDateSubfield'];
		$lastCheckinDateSubfield = $configArray['Reindex']['lastCheckinDateSubfield'];

		foreach ($itemFields as $itemField){
			//Ignore eContent items
			$eContentData = trim($itemField->getSubfield('w') != null ? $itemField->getSubfield('w')->getData() : '');
			if ($eContentData && strpos($eContentData, ':') > 0){
				continue;
			}

			$locationCode = $itemField->getSubfield('d') != null ? trim($itemField->getSubfield('d')->getData()) : '';
			//Do a quick check of location code so we can remove this quickly when scoping is enabled
			if ($scopingEnabled && strlen(Millennium::$scopingLocationCode) > 0 && preg_match('/' . Millennium::$scopingLocationCode . '/i', $locationCode)){
				global $logger;
				$logger->log("Removed item because scoping is enabled and the location code $locationCode did not match " . Millennium::$scopingLocationCode, PEAR_LOG_DEBUG);
				continue;
			}
			$iType = $itemField->getSubfield($iTypeSubfield) != null ? trim($itemField->getSubfield($iTypeSubfield)->getData()) : '';
			$holdable = $this->isItemHoldableToPatron($locationCode, $iType, $pType);
			$bookable = $this->isItemBookableToPatron($locationCode, $iType, $pType);

			$isLibraryItem = false;
			$locationLabel = '';
			foreach (Millennium::$libraryLocations as $tmpLocation){
				if (strpos($locationCode, $tmpLocation) === 0){
					$isLibraryItem = true;
					$locationLabel = Millennium::$libraryLocationLabels[$tmpLocation];
					break;
				}
			}
			$timer->logTime("Finished checking if item is holdable");

			//Check to make sure the user has access to this item
			if ($holdable || $isLibraryItem){
				$isLocalItem = false;
				if (Millennium::$homeLocationCode != null && strpos($locationCode, Millennium::$homeLocationCode) === 0){
					$isLocalItem = true;
					$locationLabel = Millennium::$homeLocationLabel;
				}

				$status = trim($itemField->getSubfield($statusSubfield) != null ? trim($itemField->getSubfield($statusSubfield)->getData()) : '');
				$dueDate = $itemField->getSubfield($dueDateSubfield) != null ? trim($itemField->getSubfield($dueDateSubfield)->getData()) : null;

				$lastCheckinDate = $itemField->getSubfield($lastCheckinDateSubfield);
				if ($lastCheckinDate){ // convert to timestamp for ease of display in template
					$lastCheckinDate = trim($lastCheckinDate->getData());
					$lastCheckinDate = DateTime::createFromFormat('m-d-Y G:i', $lastCheckinDate);
					if ($lastCheckinDate) $lastCheckinDate = $lastCheckinDate->getTimestamp();
				}
				if (!$lastCheckinDate) $lastCheckinDate = null;

				$available = (in_array($status, array('-', 'o', 'd', 'w', ')', 'u')) && ($dueDate == null || strlen($dueDate) == 0));
				$inLibraryUseOnly = $status == 'o';
				$fullCallNumber = $itemField->getSubfield('s') != null ? ($itemField->getSubfield('s')->getData() . ' '): '';
				$fullCallNumber .= $itemField->getSubfield('a') != null ? $itemField->getSubfield('a')->getData() : '';
				$fullCallNumber .= $itemField->getSubfield('r') != null ? (' ' . $itemField->getSubfield('r')->getData()) : '';
				$fullCallNumber .= $itemField->getSubfield('v') != null ? (' ' . $itemField->getSubfield('v')->getData()) : '';

				$shelfLocation = mapValue('shelf_location', $locationCode);
				if (preg_match('/(.*?)\\sC\\d{3}\\w{0,2}$/', $shelfLocation, $locationParts)){
					$shelfLocation = $locationParts[1];
				}
				$item = array(
					'location' => $locationCode,
					'callnumber' => $fullCallNumber,
					'availability' => $available,
					'holdable' => $holdable,
					'bookable' => $bookable,
					'inLibraryUseOnly' => $inLibraryUseOnly,
					'isLocalItem' => $isLocalItem,
					'isLibraryItem' => $isLibraryItem,
					'locationLabel' => $locationLabel,
					'shelfLocation' => $shelfLocation,
					'status' => $status,
					'dueDate' => $dueDate,
					'iType' => $iType,
					'lastCheckinDate' => $lastCheckinDate,
				);
				$items[] = $item;
			}
			//$timer->logTime("Finished processing item");
		}
		global $timer;
		$timer->logTime("Finished load items fast for Millennium record $id there were " . count($itemFields) . " item fields originally, filtered to " . count($items));
		return $items;
	}

	var $statuses = array();
	public function getStatus($id){
		global $timer;

		if (isset($this->statuses[$id])){
			return $this->statuses[$id];
		}
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumStatusLoader.php';
		$millenniumStatusLoader = new MillenniumStatusLoader($this);
		//Load circulation status information so we can use it later on to
		//determine what is holdable and what is not.
		self::loadCircStatusInfo();
		self::loadLoanRules();
		$timer->logTime('loadCircStatusInfo, loadLoanRules');

		$this->statuses[$id] = $millenniumStatusLoader->getStatus($id);

		return $this->statuses[$id];
	}

	public function getStatuses($ids) {
		$items = array();
		$count = 0;
		foreach ($ids as $id) {
			$items[$count] = $this->getStatus($id);
			$count++;
		}
		return $items;
	}

	/**
	 * Returns a summary of the holdings information for a single id. Used to display
	 * within the search results and at the top of a full record display to ensure
	 * the holding information makes sense to all users.
	 *
	 * @param string $id the id of the bid to load holdings for
	 * @param boolean $forSearch whether or not the summary will be shown in search results
	 * @return array an associative array with a summary of the holdings.
	 */
	public function getStatusSummary($id, $forSearch = false){
		//Load circulation status information so we can use it later on to
		//determine what is holdable and what is not.
		self::loadCircStatusInfo();
		self::loadLoanRules();

		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumStatusLoader.php';
		$millenniumStatusLoader = new MillenniumStatusLoader($this);
		return $millenniumStatusLoader->getStatusSummary($id, $forSearch);
	}

	/**
	 * Returns summary information for an array of ids.  This allows the search results
	 * to query all holdings at one time.
	 *
	 * @param array $ids an array ids to load summary information for.
	 * @param boolean $forSearch whether or not the summary will be shown in search results
	 * @return array an associative array containing a second array with summary information.
	 */
	public function getStatusSummaries($ids, $forSearch = false){
		$items = array();
		$count = 0;
		foreach ($ids as $id) {
			$items[$count] = $this->getStatusSummary($id, $forSearch);
			$count++;
		}
		return $items;
	}

	public function getHolding($id)
	{
		return $this->getStatus($id);
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
		global $configArray;

		//Get the barcode property
		if ($this->accountProfile->loginConfiguration == 'barcode_pin'){
			$barcode = $username;
		}else{
			$barcode = $password;
		}

		//Strip any non digit characters from the password
		//Can't do this any longer since some libraries do have characters in their barcode:
		//$password = preg_replace('/[a-or-zA-OR-Z\W]/', '', $password);
		//Remove any spaces from the barcode
		$barcode = preg_replace('/[^a-zA-Z\d\s]/', '', trim($barcode));

		//Load the raw information about the patron
		$patronDump = $this->_getPatronDump($barcode);

		//Create a variety of possible name combinations for testing purposes.
		$userValid = false;
		//Break up the patron name into first name, last name and middle name based on the
		list($fullName, $lastName, $firstName, $userValid) = $this->validatePatronName($username, $patronName);
		if ($this->accountProfile->loginConfiguration == 'barcode_pin'){
			$userValid = $this->_doPinTest($username, $password);
		}else{
			if (isset($patronDump['PATRN_NAME'])){
				$patronName = $patronDump['PATRN_NAME'];
				list($fullName, $lastName, $firstName, $userValid) = $this->validatePatronName($username, $patronName);
			}
		}

		if ($userValid){
			if ($patronName == null){
				if (isset($patronDump['PATRN_NAME'])){
					$patronName = $patronDump['PATRN_NAME'];
					list($fullName, $lastName, $firstName) = $this->validatePatronName($username, $patronName);
				}
			}
			$userExistsInDB = false;
			$user = new User();
			//Get the unique user id from Millennium
			$user->source = $this->accountProfile->name;
			$user->username = $patronDump['RECORD_#'];
			if ($user->find(true)){
				$userExistsInDB = true;
			}
			$user->firstname = isset($firstName) ? $firstName : '';
			$user->lastname = isset($lastName) ? $lastName : '';
			$user->fullname = isset($fullName) ? $fullName : '';
			if ($this->accountProfile->loginConfiguration == 'barcode_pin'){
				$user->cat_username = $username;
				$user->cat_password = $password;
			}else{
				$user->cat_username = $patronDump['PATRN_NAME'];
				$user->cat_password = $password;
			}

			$user->phone = isset($patronDump['TELEPHONE']) ? $patronDump['TELEPHONE'] : (isset($patronDump['HOME_PHONE']) ? $patronDump['HOME_PHONE'] : '');
			$user->email = isset($patronDump['EMAIL_ADDR']) ? $patronDump['EMAIL_ADDR'] : '';
			$user->patronType = $patronDump['P_TYPE'];
			$user->web_note = isset($patronDump['WEB_NOTE']) ? $patronDump['WEB_NOTE'] : '';
			if (empty($user->displayName)) {
				if (strlen($user->firstname) >= 1) {
					$user->displayName = substr($user->firstname, 0, 1) . '. ' . $user->lastname;
				} else {
					$user->displayName = $user->lastname;
				}
			}

			//Setup home location
			$location = null;
			if (isset($patronDump['HOME_LIBR']) || isset($patronDump['HOLD_LIBR'])){
				$homeBranchCode = isset($patronDump['HOME_LIBR']) ? $patronDump['HOME_LIBR'] : $patronDump['HOLD_LIBR'];
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

			//see if expiration date is close
			if (trim($patronDump['EXP_DATE']) != '-  -'){
				$user->expires = $patronDump['EXP_DATE'];
				list ($monthExp, $dayExp, $yearExp) = explode("-",$patronDump['EXP_DATE']);
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
			}else{
				$user->expired = 0;
				$user->expireClose = 0;
			}

			//Get additional information that doesn't necessarily get stored in the User Table
			if (isset($patronDump['ADDRESS'])){
				$fullAddress = $patronDump['ADDRESS'];
				$addressParts =explode('$',$fullAddress);
				$user->address1 = $addressParts[0];
				$user->city = isset($addressParts[1]) ? $addressParts[1] : '';
				$user->state = isset($addressParts[2]) ? $addressParts[2] : '';
				$user->zip = isset($addressParts[3]) ? $addressParts[3] : '';

				if (preg_match('/(.*?),\\s+(.*)\\s+(\\d*(?:-\\d*)?)/', $user->city, $matches)) {
					$user->city = $matches[1];
					$user->state = $matches[2];
					$user->zip = $matches[3];
				}else if (preg_match('/(.*?)\\s+(\\w{2})\\s+(\\d*(?:-\\d*)?)/', $user->city, $matches)) {
					$user->city = $matches[1];
					$user->state = $matches[2];
					$user->zip = $matches[3];
				}
			}else{
				$user->address1 = "";
				$user->city = "";
				$user->state = "";
				$user->zip = "";
			}
			$user->address2 = $user->city . ', ' . $user->state;

			$user->workPhone = (isset($patronDump) && isset($patronDump['G/WK_PHONE'])) ? $patronDump['G/WK_PHONE'] : '';
			$user->mobileNumber = (isset($patronDump) && isset($patronDump['MOBILE_NO'])) ? $patronDump['MOBILE_NO'] : '';

			$user->finesVal = floatval(preg_replace('/[^\\d.]/', '', $patronDump['MONEY_OWED']));
			$user->fines = $patronDump['MONEY_OWED'];

			$numHoldsAvailable = 0;
			$numHoldsRequested = 0;
			$availableStatusRegex = isset($configArray['Catalog']['patronApiAvailableHoldsRegex']) ? $configArray['Catalog']['patronApiAvailableHoldsRegex'] : "/ST=(105|98),/";
			if (isset($patronDump) && isset($patronDump['HOLD']) && count($patronDump['HOLD']) > 0){
				foreach ($patronDump['HOLD'] as $hold){
					if (preg_match("$availableStatusRegex", $hold)){
						$numHoldsAvailable++;
					}else{
						$numHoldsRequested++;
					}
				}
			}
			$user->numCheckedOutIls = $patronDump['CUR_CHKOUT'];
			$user->numHoldsIls = isset($patronDump) ? (isset($patronDump['HOLD']) ? count($patronDump['HOLD']) : 0) : '?';
			$user->numHoldsAvailableIls = $numHoldsAvailable;
			$user->numHoldsRequestedIls = $numHoldsRequested;
			$user->numBookings = isset($patronDump) ? (isset($patronDump['BOOKING']) ? count($patronDump['BOOKING']) : 0) : '?';

			$noticeLabels = array(
				//'-' => 'Mail',  // officially None in Sierra, as in No Preference Selected.
				'-' => '',  // notification will generally be based on what information is available so can't determine here. plb 12-02-2014
				'a' => 'Mail', // officially Print in Sierra
				'p' => 'Telephone',
				'z' => 'E-mail',
			);
			$user->notices = isset($patronDump) ? $patronDump['NOTICE_PREF'] : '-';
			if (array_key_exists($user->notices, $noticeLabels)){
				$user->noticePreferenceLabel = $noticeLabels[$user->notices];
			}else{
				$user->noticePreferenceLabel = 'Unknown';
			}

			if ($userExistsInDB){
				$user->update();
			}else{
				$user->created = date('Y-m-d');
				$user->insert();
			}

			$timer->logTime("patron logged in successfully");
			return $user;

		} else {
			$timer->logTime("patron login failed");
			return null;
		}
	}

	/**
	 * Get a dump of information from Millennium that can be used in other
	 * routines.
	 *
	 * @param string  $barcode the patron's barcode
	 * @param boolean $forceReload whether or not cached data can be used.
	 * @return array
	 */
	public function _getPatronDump(&$barcode, $forceReload = false)
	{
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		global $library;

		$patronDump = $memCache->get("patron_dump_$barcode");
		if (!$patronDump || $forceReload){
			$host = isset($this->accountProfile->patronApiUrl) ? $this->accountProfile->patronApiUrl : null; // avoid warning notices
			if ($host == null){
				$host = $configArray['OPAC']['patron_host'];
			}
			$barcodesToTest = array();
			$barcodesToTest[] = $barcode;

			//Special processing to allow users to login with short barcodes
			if ($library){
				if ($library->barcodePrefix){
					if (strpos($barcode, $library->barcodePrefix) !== 0){
						//Add the barcode prefix to the barcode
						$barcodesToTest[] = $library->barcodePrefix . $barcode;
					}
				}
			}

			//Special processing to allow MCVSD Students to login
			//with their student id.
			if (strlen($barcode)== 5){
				$barcodesToTest[] = "41000000" . $barcode;
				$barcodesToTest[] = "mv" . $barcode;
			}elseif (strlen($barcode)== 6){
				$barcodesToTest[] = "4100000" . $barcode;
				$barcodesToTest[] = "mv" . $barcode;
			}

			foreach ($barcodesToTest as $i=>$barcode){
				$patronDump = $this->_parsePatronApiPage($host, $barcode);

				if (is_null($patronDump)){
					return $patronDump;
				}else if ((isset($patronDump['ERRNUM']) || count($patronDump) == 0) && $i != count($barcodesToTest) - 1){
					//check the next barcode
				}else{

					$memCache->set("patron_dump_$barcode", $patronDump, 0, $configArray['Caching']['patron_dump']);
					//Need to wait a little bit since getting the patron api locks the record in the DB
					usleep(250);
					break;
				}
			}

		}
		return $patronDump;
	}

	private function _parsePatronApiPage($host, $barcode){
		global $timer;
		// Load Record Page.  This page has a dump of all patron information
		//as a simple name value pair list within the body of the webpage.
		//Sample format of a row is as follows:
		//P TYPE[p47]=100<BR>
		$patronApiUrl =  $host . "/PATRONAPI/" . $barcode ."/dump" ;
		$result = $this->_curlGetPage($patronApiUrl);

		//Strip the actual contents out of the body of the page.
		$cleanPatronData = strip_tags($result);

		//Add the key and value from each row into an associative array.
		$patronDump = array();
		preg_match_all('/(.*?)\\[.*?\\]=(.*)/', $cleanPatronData, $patronData, PREG_SET_ORDER);
		for ($curRow = 0; $curRow < count($patronData); $curRow++) {
			$patronDumpKey = str_replace(" ", "_", trim($patronData[$curRow][1]));
			switch ($patronDumpKey) {
				// multiple entries
				case 'HOLD' :
				case 'BOOKING' :
					$patronDump[$patronDumpKey][] = isset($patronData[$curRow][2]) ? $patronData[$curRow][2] : '';
					break;
				// single entries
				default :
					$patronDump[$patronDumpKey] = isset($patronData[$curRow][2]) ? $patronData[$curRow][2] : '';
			}
		}

		$timer->logTime("Got patron information from Patron API");
		return $patronDump;
	}

	public function _curl_login($patron) {
		global $logger;

		$curlUrl = $this->getVendorOpacUrl() . "/patroninfo/";
		$post_data   = $this->_getLoginFormValues($patron);

		$logger->log('Loading page ' . $curlUrl, PEAR_LOG_INFO);

		$loginResult = $this->_curlPostPage($curlUrl, $post_data);

		//When a library uses IPSSO, the initial login does a redirect and requires additional parameters.
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResult, $loginMatches)) {
			$lt = $loginMatches[1]; //Get the lt value
			//Login again
			$post_data['lt']       = $lt;
			$post_data['_eventId'] = 'submit';

			//Don't issue a post, just call the same page (with redirects as needed)
			$post_string = http_build_query($post_data);
			curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $post_string);

			$loginResult = curl_exec($this->curl_connection);
		}
		return $loginResult;// Note: $this->_fetchPatronInfoPage uses the actual html result of this
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
	public function getMyCheckouts( $user ) {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumCheckouts.php';
		$millenniumCheckouts = new MillenniumCheckouts($this);
		return $millenniumCheckouts->getMyCheckouts($user);
	}

	/**
	 * Return a page from classic with comments stripped
	 *
	 * @param $patron             User The unique identifier for the patron
	 * @param $page               string The page to be loaded
	 * @return string             The page from classic
	 */
	public function _fetchPatronInfoPage($patron, $page){
		$scope = $this->getDefaultScope();

		//First we have to login to classic
		$this->_curl_login($patron);

		//Now we can get the page
		$curlUrl = $this->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patron->username ."/$page";
		$curlResponse = $this->_curlGetPage($curlUrl);

		//Strip HTML comments
		$curlResponse = preg_replace("/<!--([^(-->)]*)-->/"," ",$curlResponse);
		return $curlResponse;
	}

	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumReadingHistory.php';
		$millenniumReadingHistory = new MillenniumReadingHistory($this);
		return $millenniumReadingHistory->getReadingHistory($patron, $page, $recordsPerPage, $sortOption);
	}

	/**
	 * Do an update or edit of reading history information.  Current actions are:
	 * deleteMarked
	 * deleteAll
	 * exportList
	 * optOut
	 *
	 * @param   User    $patron
	 * @param   string  $action         The action to perform
	 * @param   array   $selectedTitles The titles to do the action on if applicable
	 */
	function doReadingHistoryAction($patron, $action, $selectedTitles){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumReadingHistory.php';
		$millenniumReadingHistory = new MillenniumReadingHistory($this);
		$millenniumReadingHistory->doReadingHistoryAction($patron, $action, $selectedTitles);
	}


	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $patron    The user to load transactions for
	 *
	 * @return array          Array of the patron's holds
	 * @access public
	 */
	public function getMyHolds($patron){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->getMyHolds($patron);
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
		$result = $this->placeItemHold($patron, $recordId, '', $pickupBranch);
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
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch) {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->placeItemHold($patron, $recordId, $itemId, $pickupBranch);
	}

	public function updateHold($patron, $requestId, $type, $title){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->updateHold($patron, $requestId, $type, $title);
	}

	public function updateHoldDetailed($patron, $type, $title, $xNum, $cancelId, $locationId, $freezeValue='off'){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->updateHoldDetailed($patron, $type, $title, $xNum, $cancelId, $locationId, $freezeValue);
	}

	public function cancelHold($patron, $recordId, $cancelId){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->updateHoldDetailed($patron, 'cancel', '', null, $cancelId, '', '');
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->updateHoldDetailed($patron, 'update', '', null, $itemToFreezeId, '', 'on');
	}

	function thawHold($patron, $recordId, $itemToThawId){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->updateHoldDetailed($patron, 'update', '', null, $itemToThawId, '', 'off');
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumHolds.php';
		$millenniumHolds = new MillenniumHolds($this);
		return $millenniumHolds->updateHoldDetailed($patron, 'update', '', null, $itemToUpdateId, $newPickupLocation, 'off');
	}

	public function hasFastRenewAll(){
		return true;
	}

	public function renewAll($patron){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumCheckouts.php';
		$millenniumCheckouts = new MillenniumCheckouts($this);
		return $millenniumCheckouts->renewAll($patron);
	}

	public function renewItem($patron, $recordId, $itemId, $itemIndex){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumCheckouts.php';
		$millenniumCheckouts = new MillenniumCheckouts($this);
		return $millenniumCheckouts->renewItem($patron, $itemId, $itemIndex);
	}

	public function bookMaterial($recordId, $startDate, $startTime = null, $endDate = null, $endTime = null) {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumBooking.php';
		$millenniumBooking = new MillenniumBooking($this);
		return $millenniumBooking->bookMaterial($recordId, $startDate, $startTime, $endDate, $endTime);
	}

	/**
	 * @param $cancelIds  array uses a specific id for canceling a booking, rather than a record Id.
	 * @return array      data for client-side AJAX responses
	 */
	public function cancelBookedMaterial($cancelIds) {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumBooking.php';
		$millenniumBooking = new MillenniumBooking($this);
		return $millenniumBooking->cancelBookedMaterial($cancelIds);
	}

	/**
	 * @return array      data for client-side AJAX responses
	 */
	public function cancelAllBookedMaterial() {
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumBooking.php';
		$millenniumBooking = new MillenniumBooking($this);
		return $millenniumBooking->cancelAllBookedMaterial();
	}

public function getBookingCalendar($recordId) {
	require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumBooking.php';
	$millenniumBooking = new MillenniumBooking($this);
	return $millenniumBooking->getBookingCalendar($recordId);
}

	public function updatePatronInfo($canUpdateContactInfo){
		global $user;
		global $analytics;
		$updateErrors = array();

		//Setup the call to Millennium
		$patronDump = $this->_getPatronDump($this->_getBarcode());

		if ($canUpdateContactInfo){
			//Update profile information
			$extraPostInfo = array();
			if (isset($_REQUEST['address1'])){
				$extraPostInfo['addr1a'] = $_REQUEST['address1'];
				$extraPostInfo['addr1b'] = $_REQUEST['city'] . ', ' . $_REQUEST['state'] . ' ' . $_REQUEST['zip'];
				$extraPostInfo['addr1c'] = '';
				$extraPostInfo['addr1d'] = '';
			}
			$extraPostInfo['tele1'] = $_REQUEST['phone'];
			if (isset($_REQUEST['workPhone'])){
				$extraPostInfo['tele2'] = $_REQUEST['workPhone'];
			}
			$extraPostInfo['email'] = $_REQUEST['email'];

			if (isset($_REQUEST['pickupLocation'])){
				$pickupLocation = $_REQUEST['pickupLocation'];
				if (strlen($pickupLocation) < 5){
					$pickupLocation = $pickupLocation . str_repeat(' ', 5 - strlen($pickupLocation));
				}
				$extraPostInfo['locx00'] = $pickupLocation;
			}

			if (isset($_REQUEST['notices'])){
				$extraPostInfo['notices'] = $_REQUEST['notices'];
			}

			if (isset($_REQUEST['mobileNumber'])){
//				$ils = $configArray['Catalog']['ils']; // code not used anywhere. plb 4-29-2015
				$extraPostInfo['mobile'] = preg_replace('/\D/', '', $_REQUEST['mobileNumber']);
				if (strlen($_REQUEST['mobileNumber']) > 0 && $_REQUEST['smsNotices'] == 'on'){
					$extraPostInfo['optin'] = 'on';
					global $library;
					if ($library->addSMSIndicatorToPhone){
						//If the user is using SMS notices append TEXT ONLY to the primary phone number
						if (strpos($extraPostInfo['tele1'], 'TEXT ONLY') !== 0){
							$extraPostInfo['tele1'] = 'TEXT ONLY ' . $extraPostInfo['tele1'];
						}
					}
				}else{
					$extraPostInfo['optin'] = 'off';
					$extraPostInfo['mobile'] = "";
					global $library;
					if ($library->addSMSIndicatorToPhone){
						if (strpos($extraPostInfo['tele1'], 'TEXT ONLY') === 0){
							$extraPostInfo['tele1'] = str_replace('TEXT ONLY ', '', $extraPostInfo['tele1']);
						}
					}
				}
			}

			//Login to the patron's account
			$cookieJar = tempnam ("/tmp", "CURLCOOKIE");

			$scope = $this->getMillenniumScope();
			$curl_url = $this->getVendorOpacUrl() . "/patroninfo~" . $scope;

			//TODO: use _curl_login() instead

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
			$post_data = $this->_getLoginFormValues($user);
			$post_string = http_build_query($post_data);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$loginResult = curl_exec($curl_connection);

			//TODO: success check for login? Expired cards will be rejected

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

			//Issue a post request to update the patron information
			$patronUpdateParams = http_build_query($extraPostInfo);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $patronUpdateParams);
			$curl_url = $this->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/modpinfo";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			$sresult = curl_exec($curl_connection);

			curl_close($curl_connection);
			unlink($cookieJar);


		// Update Patron Information on success
		if (isset($sresult) && strpos($sresult, 'Patron information updated') !== false){
			$user->phone = $_REQUEST['phone'];
			$user->email = $_REQUEST['email'];
			$user->update();

			//Update the serialized instance stored in the session
			$_SESSION['userinfo'] = serialize($user);
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Profile updated successfully');
			}
		}else{
			// Doesn't look like the millennium (actually sierra) server ever provides error messages. plb 4-29-2015
			$errorMsg = 'There were errors updating your information.'; // generic error message
			$updateErrors[] = $errorMsg;
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Profile update failed');
			}
		}

		//Make sure to clear any cached data
		/** @var Memcache $memCache */
		global $memCache;
		$memCache->delete("patron_dump_{$this->_getBarcode()}");
		$this->clearPatronProfile();

		} else $updateErrors[] = 'You can not update your information.';
		return $updateErrors;
	}

	var $pType;
	/**
	 * returns the patron type identifier if a patron is logged in or if the patron
	 * is not logged in, it will return the default PType for the library domain.
	 * If a domain is not in use it will return -1.
	 *
	 * @return int
	 */
	public function getPType(){
		if ($this->pType == null){
			/** @var $user User */
			global $user;
			/** @var $locationSingleton Location */
			global $locationSingleton;
			$searchLocation = $locationSingleton->getSearchLocation();
			$searchLibrary = Library::getSearchLibrary();
			if (isset($user) && $user != false){
				$patronDump = $this->_getPatronDump($this->_getBarcode());
				if (isset($patronDump['P_TYPE'])){
					$this->pType = $patronDump['P_TYPE'];
				}else{
					$this->pType = -1;
				}
			}else if (isset($searchLocation) && $searchLocation->defaultPType >= 0){
				$this->pType = $searchLocation->defaultPType;
			}else if (isset($searchLibrary) && $searchLibrary->defaultPType >= 0){
				$this->pType = $searchLibrary->defaultPType;
			}else{
				$this->pType = -1;
			}
		}
		return $this->pType;
	}

	/**
	 * @param null|User $patron
	 * @return mixed
	 */
	public function _getBarcode($patron = null){
		if ($patron == null){
			global $user;
			$patron = $user;
		}
		if ($patron){
			return $patron->getBarcode();
		}else{
			return '';
		}
	}

	/**
	 * Checks millennium to determine if there are issue summaries available.
	 * If there are issue summaries available, it will return them in an array.
	 * With holdings below them.
	 *
	 * If there are no issue summaries, null will be returned from the summary.
	 *
	 * @param MillenniumCache $millenniumInfo - Information from Millennium to load issue information from.
	 *
	 * @return mixed - array or null
	 */
	public function getIssueSummaries($millenniumInfo){
		//Issue summaries are loaded from the main record page.

		if (preg_match('/class\\s*=\\s*\\"bibHoldings\\"/s', $millenniumInfo->framesetInfo)){
			//There are issue summaries available
			//Extract the table with the holdings
			$issueSummaries = array();
			$matches = array();
			if (preg_match('/<table\\s.*?class=\\"bibHoldings\\">(.*?)<\/table>/s', $millenniumInfo->framesetInfo, $matches)) {
				$issueSummaryTable = trim($matches[1]);
				//Each holdingSummary begins with a holdingsDivider statement
				$summaryMatches = explode('<tr><td colspan="2"><hr  class="holdingsDivider" /></td></tr>', $issueSummaryTable);
				if (count($summaryMatches) > 1){
					//Process each match independently
					foreach ($summaryMatches as $summaryData){
						$summaryData = trim($summaryData);
						if (strlen($summaryData) > 0){
							//Get each line within the summary
							$issueSummary = array();
							$issueSummary['type'] = 'issueSummary';
							$summaryLines = array();
							preg_match_all('/<tr\\s*>(.*?)<\/tr>/s', $summaryData, $summaryLines, PREG_SET_ORDER);
							for ($matchi = 0; $matchi < count($summaryLines); $matchi++) {
								$summaryLine = trim(str_replace('&nbsp;', ' ', $summaryLines[$matchi][1]));
								$summaryCols = array();
								if (preg_match('/<td.*?>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>/s', $summaryLine, $summaryCols)) {
									$label = trim($summaryCols[1]);
									$value = trim(strip_tags($summaryCols[2]));
									//Check to see if this has a link to a check-in grid.
									if (preg_match('/.*?<a href="(.*?)">.*/s', $label, $linkData)) {
										//Parse the check-in id
										$checkInLink = $linkData[1];
										if (preg_match('/\/search~S\\d+\\?\/.*?\/.*?\/.*?\/(.*?)&.*/', $checkInLink, $checkInGridInfo)) {
											$issueSummary['checkInGridId'] = $checkInGridInfo[1];
										}
										$issueSummary['checkInGridLink'] = 'http://www.millenium.marmot.org' . $checkInLink;
									}
									//Convert to camel case
									$label = (preg_replace('/[^\\w]/', '', strip_tags($label)));
									$label = strtolower(substr($label, 0, 1)) . substr($label, 1);
									if ($label == 'location'){
										//Try to trim the courier code if any
										if (preg_match('/(.*?)\\sC\\d{3}\\w{0,2}$/', $value, $locationParts)){
											$value = $locationParts[1];
										}
									}
									$issueSummary[$label] = $value;
								}
							}
							$issueSummaries[$issueSummary['location'] . count($issueSummaries)] = $issueSummary;
						}
					}
				}
			}
			return $issueSummaries;
		}else{
			return null;
		}
	}

	/**
	 * @param File_MARC_Record $marcRecord
	 * @return bool
	 */
	function isRecordHoldable($marcRecord){
		global $configArray;
		$pType = $this->getPType();
		/** @var File_MARC_Data_Field[] $items */
		$marcItemField = isset($configArray['Reindex']['itemTag']) ? $configArray['Reindex']['itemTag'] : '989';
		$iTypeSubfield = isset($configArray['Reindex']['iTypeSubfield']) ? $configArray['Reindex']['iTypeSubfield'] : 'j';
		$locationSubfield = isset($configArray['Reindex']['locationSubfield']) ? $configArray['Reindex']['locationSubfield'] : 'j';
		$items = $marcRecord->getFields($marcItemField);
		$holdable = false;
		$itemNumber = 0;
		foreach ($items as $item){
			$itemNumber++;
			$subfield_j = $item->getSubfield($iTypeSubfield);
			if (is_object($subfield_j) && !$subfield_j->isEmpty()){
				$iType = $subfield_j->getData();
			}else{
				$iType = '0';
			}
			$subfield_d = $item->getSubfield($locationSubfield);
			if (is_object($subfield_d) && !$subfield_d->isEmpty()){
				$locationCode = $subfield_d->getData();
			}else{
				$locationCode = '?????';
			}
			//$logger->log("$itemNumber) iType = $iType, locationCode = $locationCode", PEAR_LOG_DEBUG);

			//Check the determiner table to see if this matches
			$holdable = $this->isItemHoldableToPatron($locationCode, $iType, $pType);

			if ($holdable){
				break;
			}
		}
		return $holdable;
	}

	function isItemHoldableToPatron($locationCode, $iType, $pType){
		/** @var Memcache $memCache*/
		global $memCache;
		global $configArray;
		global $timer;
		$memcacheKey = "loan_rule_result_{$locationCode}_{$iType}_{$pType}";
		$cachedValue = $memCache->get($memcacheKey);
		if ($cachedValue !== false && !isset($_REQUEST['reload'])){
			return $cachedValue == 'true';
		}else{
			$timer->logTime("Start checking if item is holdable $locationCode, $iType, $pType");
			$this->loadLoanRules();
			if (count($this->loanRuleDeterminers) == 0){
				//If we don't have any loan rules determiners, assume that the item is holdable.
				return true;
			}
			$holdable = false;
			//global $logger;
			//$logger->log("Checking loan rules for $locationCode, $iType, $pType", PEAR_LOG_DEBUG);
			foreach ($this->loanRuleDeterminers as $loanRuleDeterminer){
				//$logger->log("Determiner {$loanRuleDeterminer->rowNumber}", PEAR_LOG_DEBUG);
				//Check the location to be sure the determiner applies to this item
				if ($loanRuleDeterminer->matchesLocation($locationCode) ){
					//$logger->log("{$loanRuleDeterminer->rowNumber}) Location correct $locationCode, {$loanRuleDeterminer->location} ({$loanRuleDeterminer->trimmedLocation()})", PEAR_LOG_DEBUG);
					//Check that the iType is correct
					if ($loanRuleDeterminer->itemType == '999' || in_array($iType, $loanRuleDeterminer->iTypeArray())){
						//$logger->log("{$loanRuleDeterminer->rowNumber}) iType correct $iType, {$loanRuleDeterminer->itemType}", PEAR_LOG_DEBUG);
						if ($pType == -1 || $loanRuleDeterminer->patronType == '999' || in_array($pType, $loanRuleDeterminer->pTypeArray())){
							//$logger->log("{$loanRuleDeterminer->rowNumber}) pType correct $pType, {$loanRuleDeterminer->patronType}", PEAR_LOG_DEBUG);
							$loanRule = $this->loanRules[$loanRuleDeterminer->loanRuleId];
							//$logger->log("Determiner {$loanRuleDeterminer->rowNumber} indicates Loan Rule {$loanRule->loanRuleId} applies, holdable {$loanRule->holdable}", PEAR_LOG_DEBUG);
							$holdable = ($loanRule->holdable == 1);
							if ($holdable || $pType != -1){
								break;
							}
						}else{
							//$logger->log("PType incorrect", PEAR_LOG_DEBUG);
						}
					}else{
						//$logger->log("IType incorrect", PEAR_LOG_DEBUG);
					}
				}else{
					//$logger->log("Location incorrect {$loanRuleDeterminer->location} != {$locationCode}", PEAR_LOG_DEBUG);
				}
			}
			$memCache->set($memcacheKey, ($holdable ? 'true' : 'false'), 0 , $configArray['Caching']['loan_rule_result']);
			$timer->logTime("Finished checking if item is holdable $locationCode, $iType, $pType");
		}

		return $holdable;
	}

	function isRecordBookable($marcRecord){
		//TODO: finish this, template from Holds
		global $configArray;
		$pType = $this->getPType();
		/** @var File_MARC_Data_Field[] $items */
		$marcItemField = isset($configArray['Reindex']['itemTag']) ? $configArray['Reindex']['itemTag'] : '989';
		$iTypeSubfield = isset($configArray['Reindex']['iTypeSubfield']) ? $configArray['Reindex']['iTypeSubfield'] : 'j';
		$locationSubfield = isset($configArray['Reindex']['locationSubfield']) ? $configArray['Reindex']['locationSubfield'] : 'j';
		$items = $marcRecord->getFields($marcItemField);
		$bookable = false;
		$itemNumber = 0;
		foreach ($items as $item){
			$itemNumber++;
			$subfield_j = $item->getSubfield($iTypeSubfield);
			if (is_object($subfield_j) && !$subfield_j->isEmpty()){
				$iType = $subfield_j->getData();
			}else{
				$iType = '0';
			}
			$subfield_d = $item->getSubfield($locationSubfield);
			if (is_object($subfield_d) && !$subfield_d->isEmpty()){
				$locationCode = $subfield_d->getData();
			}else{
				$locationCode = '?????';
			}
			//$logger->log("$itemNumber) iType = $iType, locationCode = $locationCode", PEAR_LOG_DEBUG);

			//Check the determiner table to see if this matches
			$bookable = $this->isItemBookableToPatron($locationCode, $iType, $pType);

			if ($bookable){
				break;
			}
		}
		return $bookable;
	}

	public function isItemBookableToPatron($locationCode, $iType, $pType){
		/** @var Memcache $memCache*/
		global $memCache;
		global $configArray;
		global $timer;
		$memcacheKey = "loan_rule_material_booking_result_{$locationCode}_{$iType}_{$pType}";
		$cachedValue = $memCache->get($memcacheKey);
		if ($cachedValue !== false && !isset($_REQUEST['reload'])){
			return $cachedValue == 'true';
		}else {
			$timer->logTime("Start checking if item is bookable $locationCode, $iType, $pType");
			$this->loadLoanRules();
			if (count($this->loanRuleDeterminers) == 0){
				//If we don't have any loan rules determiners, assume that the item isn't bookable.
				return false;
			}
			$bookable = false;
			//global $logger;
			//$logger->log("Checking loan rules for $locationCode, $iType, $pType", PEAR_LOG_DEBUG);
			foreach ($this->loanRuleDeterminers as $loanRuleDeterminer){
				//$logger->log("Determiner {$loanRuleDeterminer->rowNumber}", PEAR_LOG_DEBUG);
				//Check the location to be sure the determiner applies to this item
				if ($loanRuleDeterminer->matchesLocation($locationCode) ){
					//$logger->log("{$loanRuleDeterminer->rowNumber}) Location correct $locationCode, {$loanRuleDeterminer->location} ({$loanRuleDeterminer->trimmedLocation()})", PEAR_LOG_DEBUG);
					//Check that the iType is correct
					if ($loanRuleDeterminer->itemType == '999' || in_array($iType, $loanRuleDeterminer->iTypeArray())){
						//$logger->log("{$loanRuleDeterminer->rowNumber}) iType correct $iType, {$loanRuleDeterminer->itemType}", PEAR_LOG_DEBUG);
						if ($pType == -1 || $loanRuleDeterminer->patronType == '999' || in_array($pType, $loanRuleDeterminer->pTypeArray())){
							//$logger->log("{$loanRuleDeterminer->rowNumber}) pType correct $pType, {$loanRuleDeterminer->patronType}", PEAR_LOG_DEBUG);
							$loanRule = $this->loanRules[$loanRuleDeterminer->loanRuleId];
							//$logger->log("Determiner {$loanRuleDeterminer->rowNumber} indicates Loan Rule {$loanRule->loanRuleId} applies, bookable {$loanRule->bookable}", PEAR_LOG_DEBUG);
							$bookable = ($loanRule->bookable == 1);
							if ($bookable || $pType != -1){
								break;
							}
						}
//						else{
//							//$logger->log("PType incorrect", PEAR_LOG_DEBUG);
//						}
					}
//					else{
//						//$logger->log("IType incorrect", PEAR_LOG_DEBUG);
//					}
				}
//				else{
//					//$logger->log("Location incorrect {$loanRuleDeterminer->location} != {$locationCode}", PEAR_LOG_DEBUG);
//				}
			}
			$memCache->set($memcacheKey, ($bookable ? 'true' : 'false'), 0 , $configArray['Caching']['loan_rule_result']); // TODO: set a different config option for booking results?
			$timer->logTime("Finished checking if item is bookable $locationCode, $iType, $pType");
		}

		return $bookable;

	}

	public function getMyBookings(){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumBooking.php';
		$millenniumBookings = new MillenniumBooking($this);
		return $millenniumBookings->getMyBookings();
	}

	function getCheckInGrid($id, $checkInGridId){
		//Issue summaries are loaded from the main record page.
		global $configArray;

		// Strip ID
		$id_ = substr(str_replace('.b', '', $id), 0, -1);

		// Load Record Page
		if (substr($configArray['Catalog']['url'], -1) == '/') {
			$host = substr($configArray['Catalog']['url'], 0, -1);
		} else {
			$host = $configArray['Catalog']['url'];
		}

		$branchScope = $this->getMillenniumScope();
		$req =  $host . "/search~S{$branchScope}/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/$checkInGridId&FF=1,0,";
		$result = file_get_contents($req);

		//Extract the actual table
		$checkInData = array();
		if (preg_match('/<table  class="checkinCardTable">(.*?)<\/table>/s', $result, $matches)) {
			$checkInTable = trim($matches[1]);

			//Extract each item from the grid.
			preg_match_all('/.*?<td valign="top" class="(.*?)">(.*?)<\/td>/s', $checkInTable, $checkInCellMatch, PREG_SET_ORDER);
			for ($matchi = 0; $matchi < count($checkInCellMatch); $matchi++) {
				$checkInCell = array();
				$checkInCell['class'] = $checkInCellMatch[$matchi][1];
				$cellData = trim($checkInCellMatch[$matchi][2]);
				//Load issue date, status, date received, issue number, copies received
				if (preg_match('/(.*?)<br\\s*\/?>.*?<span class="(?:.*?)">(.*?)<\/span>.*?on (\\d{1,2}-\\d{1,2}-\\d{1,2})<br\\s*\/?>(.*?)(?:<!-- copies --> \\((\\d+) copy\\))?<br\\s*\/?>/s', $cellData, $matches)) {
					$checkInCell['issueDate'] = trim($matches[1]);
					$checkInCell['status'] = trim($matches[2]);
					$checkInCell['statusDate'] = trim($matches[3]);
					$checkInCell['issueNumber'] = trim($matches[4]);
					if (isset($matches[5])){
						$checkInCell['copies'] = trim($matches[5]);
					}
				}
				$checkInData[] = $checkInCell;
			}
		}
		return $checkInData;
	}

	function _getItemDetails($id, $holdings){
		global $logger;
		global $configArray;
		$scope = $this->getDefaultScope();

		$shortId = substr(str_replace('.b', 'b', $id), 0, -1);

		//Login to the site using vufind login.
		$cookie = tempnam ("/tmp", "CURLCOOKIE");
		$curl_url = $this->getVendorOpacUrl() . "/patroninfo";
		$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);
		//echo "$curl_url";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		$post_data['name'] = $configArray['Catalog']['ils_admin_user'];
		$post_data['code'] = $configArray['Catalog']['ils_admin_pwd'];
//		$post_items = array();
//		foreach ($post_data as $key => $value) {
//			$post_items[] = $key . '=' . urlencode($value);
//		}
//		$post_string = implode ('&', $post_items);
		$post_string = http_build_query($post_data);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		curl_exec($curl_connection);

		foreach ($holdings as $itemNumber => $holding){
			//Get the staff page for the record
			//$curl_url = "https://sierra.marmot.org/search~S93?/Ypig&searchscope=93&SORT=D/Ypig&searchscope=93&SORT=D&SUBKEY=pig/1,383,383,B/staffi1~$shortId&FF=Ypig&2,2,";
			$curl_url = $this->getVendorOpacUrl() . "/search~S{$scope}?/Ypig&searchscope={$scope}&SORT=D/Ypig&searchscope={$scope}&SORT=D&SUBKEY=pig/1,383,383,B/staffi$itemNumber~$shortId&FF=Ypig&2,2,";
			$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);
			//echo "$curl_url";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
			curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie );
			curl_setopt($curl_connection, CURLOPT_COOKIESESSION, false);
			$sResult = curl_exec($curl_connection);

			//Extract Item information
			if (preg_match('/<!-- Fixfields -->.*?<table.*?>(.*?)<\/table>.*?<!-- Varfields -->.*?<table.*?>(.*?)<\/table>.*?<!-- Lnkfields -->.*?<table.*?>(.*?)<\/table>/s', $sResult, $matches)) {
				$fixFieldString = $matches[1];
				$varFieldString = $matches[2];
			}

			//Extract the fixFields into an array of name value pairs
			$fixFields = array();
			if (isset($fixFieldString)){
				preg_match_all('/<td><font size="-1"><em>(.*?)<\/em><\/font>&nbsp;<strong>(.*?)<\/strong><\/td>/s', $fixFieldString, $fieldData, PREG_PATTERN_ORDER);
				for ($i = 0; $i < count($fieldData[0]); $i++) {
					$fixFields[$fieldData[1][$i]] = $fieldData[2][$i];
				}
			}

			//Extract the fixFields into an array of name value pairs
			$varFields = array();
			if (isset($varFieldString)){
				preg_match_all('/<td.*?><font size="-1"><em>(.*?)<\/em><\/font><\/td><td width="80%">(.*?)<\/td>/s', $varFieldString, $fieldData, PREG_PATTERN_ORDER);
				for ($i = 0; $i < count($fieldData[0]); $i++) {
					$varFields[$fieldData[1][$i]] = $fieldData[2][$i];
				}
			}

			//Add on the item information
			$holdings[$itemNumber] = array_merge($fixFields, $varFields, $holding);
		}
		curl_close($curl_connection);
	}

	function combineCityStateZipInSelfRegistration(){
		return true;
	}
	function selfRegister(){
		global $logger;
		global $configArray;
		global $library;

		$firstName = trim($_REQUEST['firstName']);
		$middleName = trim($_REQUEST['middleName']);
		$lastName = trim($_REQUEST['lastName']);
		$address = trim($_REQUEST['address']);
		$city = trim($_REQUEST['city']);
		$state = trim($_REQUEST['state']);
		$zip = trim($_REQUEST['zip']);
		$email = trim($_REQUEST['email']);

		$cookie = tempnam ("/tmp", "CURLCOOKIE");
		$curl_url = $this->getVendorOpacUrl() . "/selfreg~S" . $this->getLibraryScope();
		$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);
		//echo "$curl_url";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);

		$post_data['nfirst'] = $middleName ? $firstName.' '.$middleName : $firstName; // add middle name onto first name;
		$post_data['nlast'] = $lastName;
		$post_data['stre_aaddress'] = $address;
		if ($this->combineCityStateZipInSelfRegistration()){
			$post_data['city_aaddress'] = "$city $state, $zip";
		}else{
			$post_data['city_aaddress'] = "$city";
			$post_data['stat_aaddress'] = "$state";
			$post_data['post_aaddress'] = "$zip";
		}

		$post_data['zemailaddr'] = $email;
		if (isset($_REQUEST['phone'])){
			$phone = trim($_REQUEST['phone']);
			$post_data['tphone1'] = $phone;
		}
		if (isset($_REQUEST['birthDate'])){
			$post_data['F051birthdate'] = $_REQUEST['birthDate'];
		}
		if (isset($_REQUEST['universityID'])){
			$post_data['universityID'] = $_REQUEST['universityID'];
		}

		if ($library->selfRegistrationTemplate && $library->selfRegistrationTemplate != 'default'){
			$post_data['TemplateName'] = $library->selfRegistrationTemplate;
		}


//		$post_items = array();
//		foreach ($post_data as $key => $value) {
//			$post_items[] = $key . '=' . urlencode($value);
//		}
//		$post_string = implode ('&', $post_items);
		$post_string = http_build_query($post_data);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		curl_close($curl_connection);
		unlink($cookie);

		//Parse the library card number from the response
		if (preg_match('/Your barcode is:.*?(\\d+)<\/(b|strong)>/s', $sresult, $matches)) {
			$barcode = $matches[1];
			return array('success' => true, 'barcode' => $barcode);
		} else {
			return array('success' => false, 'barcode' => '');
		}

	}

	public function _getLoginFormValues($patron){
		$loginData = array();
		$loginData['name'] = $patron->cat_username;
		$loginData['code'] = $patron->cat_password;

		return $loginData;
	}

	/**
	 * Process inventory for a particular item in the catalog
	 *
	 * @param string $login     Login for the user doing the inventory
	 * @param string $password1 Password for the user doing the inventory
	 * @param string $initials
	 * @param string $password2
	 * @param string[] $barcodes
	 * @param boolean $updateIncorrectStatuses
	 *
	 * @return array
	 */
	function doInventory($login, $password1, $initials, $password2, $barcodes, $updateIncorrectStatuses){
		require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumInventory.php';
		$millenniumInventory = new MillenniumInventory($this);
		return $millenniumInventory->doInventory($login, $password1, $initials, $password2, $barcodes, $updateIncorrectStatuses);
	}

	/**
	 * @param $username
	 * @param $patronName
	 * @return array
	 */
	public function validatePatronName($username, $patronName) {
		$fullName = str_replace(",", " ", $patronName);
		$fullName = str_replace(";", " ", $fullName);
		$fullName = str_replace(";", "'", $fullName);
		$fullName = preg_replace("/\\s{2,}/", " ", $fullName);
		$allNameComponents = preg_split('^[\s-]^', strtolower($fullName));
		$nameParts = explode(' ', $fullName);
		$lastName = strtolower($nameParts[0]);
		$middleName = isset($nameParts[2]) ? strtolower($nameParts[2]) : '';
		$firstName = isset($nameParts[1]) ? strtolower($nameParts[1]) : $middleName;

		//Get the first name that the user supplies.
		//This expects the user to enter one or two names and only
		//Validates the first name that was entered.
		$enteredNames = preg_split('^[\s-]^', strtolower($username));
		$userValid = false;
		foreach ($enteredNames as $name) {
			if (in_array($name, $allNameComponents, false)) {
				$userValid = true;
				break;
			}
		}
		return array($fullName, $lastName, $firstName, $userValid);
	}

	/**
	 * @param User $patron
	 * @param bool $includeMessages
	 * @return array
	 */
	public function getMyFines($patron = null, $includeMessages = false){
		//Load the information from millennium using CURL
		$pageContents = $this->_fetchPatronInfoPage($patron, 'overdues');

		//Get the fines table data
		$messages = array();
		if (preg_match('/<table border="0" class="patFunc">(.*?)<\/table>/si', $pageContents, $regs)) {
			$finesTable = $regs[1];
			//Get the title and, type, and fine detail from the page
			preg_match_all('/<tr class="patFuncFinesEntryTitle">(.*?)<\/tr>.*?<tr class="patFuncFinesEntryDetail">.*?<td class="patFuncFinesDetailType">(.*?)<\/td>.*?<td align="right" class="patFuncFinesDetailAmt">(.*?)<\/td>.*?<\/tr>/si', $finesTable, $fineDetails, PREG_SET_ORDER);
			for ($matchi = 0; $matchi < count($fineDetails); $matchi++) {
				$reason = ucfirst(strtolower(trim($fineDetails[$matchi][2])));
				if ($reason == '&nbsp' || $reason == '&nbsp;'){
					$reason = 'Fee';
				}
				$messages[] = array(
					'reason' => $reason,
					'message' => trim(strip_tags($fineDetails[$matchi][1])),
					'amount' => trim($fineDetails[$matchi][3]),
				);
			}
		}

		return $messages;
	}

	public function requestPinReset($barcode){
		//Go to the pinreset page
		$pinResetUrl = $this->getVendorOpacUrl() . '/pinreset';
		$cookieJar = tempnam ("/tmp", "CURLCOOKIE");
		$curl_connection = curl_init();
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, is_null($cookieJar) ? true : false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);

		curl_setopt($curl_connection, CURLOPT_URL, $pinResetUrl);
		$pinResetPageHtml = curl_exec($curl_connection);

		//Now submit the request
		$post_data['code'] = $barcode;
		$post_data['pat_submit'] = 'xxx';
//		$post_items = array();
//		foreach ($post_data as $key => $value) {
//			$post_items[] = $key . '=' . $value;
//		}
//		$post_string = implode ('&', $post_items);
		$post_string = http_build_query($post_data);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$pinResetResultPageHtml = curl_exec($curl_connection);

		//Parse the response
		$result = array(
			'success' => false,
			'error' => true,
			'message' => 'Unknown error resetting pin'
		);

		if (preg_match('/<div class="errormessage">(.*?)<\/div>/is', $pinResetResultPageHtml, $matches)){
			$result['error'] = false;
			$result['message'] = trim($matches[1]);
		}elseif (preg_match('/<div class="pageContent">.*?<strong>(.*?)<\/strong>/si', $pinResetResultPageHtml, $matches)){
			$result['error'] = false;
			$result['success'] = true;
			$result['message'] = trim($matches[1]);
		}
		return $result;
	}

	/**
	 * Import Lists from the ILS
	 *
	 * @return array - an array of results including the names of the lists that were imported as well as number of titles.
	 */
	function importListsFromIls($patron){
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
		global $user;
		$results = array(
			'totalTitles' => 0,
			'totalLists' => 0
		);

		//Get the page which contains a table with all lists in them.
		$listsPage = $this->_fetchPatronInfoPage($patron, 'mylists');
		//Get the actual table
		if (preg_match('/<table[^>]*?class="patFunc"[^>]*?>(.*?)<\/table>/si', $listsPage, $listsPageMatches)) {
			$allListTable = $listsPageMatches[1];
			//Now that we have the table, get the actual list names and ids
			preg_match_all('/<tr[^>]*?class="patFuncEntry"[^>]*?>.*?<input type="checkbox" id ="(\\d+)".*?<a.*?>(.*?)<\/a>.*?<td[^>]*class="patFuncDetails">(.*?)<\/td>.*?<\/tr>/si', $allListTable, $listDetails, PREG_SET_ORDER);
			for ($listIndex = 0; $listIndex < count($listDetails); $listIndex++ ){
				$listId = $listDetails[$listIndex][1];
				$title = $listDetails[$listIndex][2];
				$description = str_replace('&nbsp;', '', $listDetails[$listIndex][3]);

				//Create the list (or find one that already exists)
				$newList = new UserList();
				$newList->user_id = $user->id;
				$newList->title = $title;
				if (!$newList->find(true)){
					$newList->description = $description;
					$newList->insert();
				}

				$currentListTitles = $newList->getListTitles();

				//Get a list of all titles within the list to be imported
				$listDetailsPage = $this->_fetchPatronInfoPage($patron, 'mylists?listNum='. $listId);
				//Get the table for the details
				if (preg_match('/<table[^>]*?class="patFunc"[^>]*?>(.*?)<\/table>/si', $listDetailsPage, $listsDetailsMatches)) {
					$listTitlesTable = $listsDetailsMatches[1];
					//Get the bib numbers for the title
					preg_match_all('/<input type="checkbox" name="(b\\d{1,7})".*?<span[^>]*class="patFuncTitle(?:Main)?">(.*?)<\/span>/si', $listTitlesTable, $bibNumberMatches, PREG_SET_ORDER);
					for ($bibCtr = 0; $bibCtr < count($bibNumberMatches); $bibCtr++){
						$bibNumber = $bibNumberMatches[$bibCtr][1];
						$bibTitle = strip_tags($bibNumberMatches[$bibCtr][2]);

						//Get the grouped work for the resource
						require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
						require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
						$primaryIdentifier = new GroupedWorkPrimaryIdentifier();
						$groupedWork = new GroupedWork();
						$primaryIdentifier->identifier = '.' . $bibNumber . $this->getCheckDigit($bibNumber);
						$primaryIdentifier->type = 'ils';
						$primaryIdentifier->joinAdd($groupedWork);
						if ($primaryIdentifier->find(true)){
							//Check to see if this title is already on the list.
							$resourceOnList = false;
							foreach ($currentListTitles as $currentTitle){
								if ($currentTitle->groupedWorkPermanentId == $primaryIdentifier->permanent_id){
									$resourceOnList = true;
									break;
								}
							}

							if (!$resourceOnList){
								$listEntry = new UserListEntry();
								$listEntry->groupedWorkPermanentId = $primaryIdentifier->permanent_id;
								$listEntry->listId = $newList->id;
								$listEntry->notes = '';
								$listEntry->dateAdded = time();
								$listEntry->insert();
							}
						}else{
							//The title is not in the resources, add an error to the results
							if (!isset($results['errors'])){
								$results['errors'] = array();
							}
							$results['errors'][] = "\"$bibTitle\" on list $title could not be found in the catalog and was not imported.";
						}

						$results['totalTitles']++;
					}
				}

				$results['totalLists'] += 1;
			}
		}

		return $results;
	}

	/**
	 * Calculates a check digit for a III identifier
	 * @param basedId String the base id without checksum
	 * @return String the check digit
	 */
	function getCheckDigit($baseId){
		$baseId = str_replace('b', '', $baseId);
		if (strlen($baseId) != 7){
			return "a";
		}else{
			$sumOfDigits = 0;
			for ($i = 0; $i < 7; $i++){
				$curDigit = substr($baseId, $i, 1);
				$sumOfDigits += (8 - $i) * $curDigit;
			}
			$modValue = $sumOfDigits % 11;
			if ($modValue == 10){
				return "x";
			}else{
				return $modValue;
			}
		}
	}

	// This function is duplicated in the User Object as deletePatronProfileCache()
	public function clearPatronProfile() {
		/** @var Memcache $memCache
		 * @var User $user */
		global $memCache, $user, $serverName;
		$memCache->delete("patronProfile_{$serverName}_{$user->username}");
	}

	public function getSelfRegistrationFields(){
		global $library;
		$fields = array();
		$fields[] = array('property'=>'firstName', 'type'=>'text', 'label'=>'First Name', 'description'=>'Your first name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property'=>'middleName', 'type'=>'text', 'label'=>'Middle Name', 'description'=>'Your middle name', 'maxLength' => 40, 'required' => false);
		// gets added to the first name separated by a space
		$fields[] = array('property'=>'lastName', 'type'=>'text', 'label'=>'Last Name', 'description'=>'Your last name', 'maxLength' => 40, 'required' => true);
		if ($library && $library->promptForBirthDateInSelfReg){
			$fields[] = array('property'=>'birthDate', 'type'=>'date', 'label'=>'Date of Birth (MM-DD-YYYY)', 'description'=>'Date of birth', 'maxLength' => 10, 'required' => true);
		}
		$fields[] = array('property'=>'address', 'type'=>'text', 'label'=>'Mailing Address', 'description'=>'Mailing Address', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'city', 'type'=>'text', 'label'=>'City', 'description'=>'City', 'maxLength' => 48, 'required' => true);
		$fields[] = array('property'=>'state', 'type'=>'text', 'label'=>'State', 'description'=>'State', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'zip', 'type'=>'text', 'label'=>'Zip Code', 'description'=>'Zip Code', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'email', 'type'=>'email', 'label'=>'E-Mail', 'description'=>'E-Mail', 'maxLength' => 128, 'required' => false);
		$fields[] = array('property'=>'phone', 'type'=>'text', 'label'=>'Phone (xxx-xxx-xxxx)', 'description'=>'Phone', 'maxLength' => 128, 'required' => false);

		return $fields;
	}

	public function hasNativeReadingHistory() {
		return true;
	}

	public function getNumHolds($id) {
		return 0;
	}

	protected function _doPinTest($barcode, $pin) {
		$pin = urlencode(trim($pin));
		$barcode = trim($barcode);
		$pinTestUrl = $this->accountProfile->patronApiUrl . "/PATRONAPI/$barcode/$pin/pintest";
		$pinTestResultRaw = $this->_curlGetPage($pinTestUrl);
		//$logger->log('PATRONAPI pintest response : ' . $api_contents, PEAR_LOG_DEBUG);
		if ($pinTestResultRaw){
			$pinTestResult = strip_tags($pinTestResultRaw);

			//Parse the page
			$pinTestData = array();
			preg_match_all('/(.*?)=(.*)/', $pinTestResult, $patronData, PREG_SET_ORDER);
			for ($curRow = 0; $curRow < count($patronData); $curRow++) {
				$patronDumpKey = str_replace(" ", "_", trim($patronData[$curRow][1]));
				$pinTestData[$patronDumpKey] = isset($patronData[$curRow][2]) ? $patronData[$curRow][2] : '';
			}
			if (!isset($pinTestData['RETCOD'])){
				$userValid = false;
			}else if ($pinTestData['RETCOD'] == 0){
				$userValid = true;
			}else{
				$userValid = false;
			}
		}else{
			$userValid = false;
		}

		return $userValid;
	}
}
