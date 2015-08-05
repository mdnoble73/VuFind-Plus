<?php
/**
 * Catalog Driver for Aspencat libraries based on Koha
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/3/14
 * Time: 5:51 PM
 */
class Aspencat implements DriverInterface{
	/** @var string $cookieFile A temporary file to store cookies  */
	private $cookieFile = null;
	/** @var resource connection to AspenCat  */
	private $curl_connection = null;

	private $dbConnection = null;
	public $accountProfile;

	/**
	 * @return array
	 */
	private static $holdingSortingData = null;
	protected static function getSortingDataForHoldings() {
		if (self::$holdingSortingData == null){
			global $user;
			global $library;
			global $locationSingleton; /** @var $locationSingleton Location */

			$holdingSortingData = array();

			//Get location information so we can put things into sections
			$physicalLocation = $locationSingleton->getPhysicalLocation();
			if ($physicalLocation != null) {
				$holdingSortingData['physicalBranch'] = $physicalLocation->holdingBranchLabel;
			} else {
				$holdingSortingData['physicalBranch'] = '';
			}
			$holdingSortingData['homeBranch'] = '';
			$homeBranchId = 0;
			$holdingSortingData['nearbyBranch1'] = '';
			$nearbyBranch1Id = 0;
			$holdingSortingData['nearbyBranch2'] = '';
			$nearbyBranch2Id = 0;

			//Set location information based on the user login.  This will override information based
			if (isset($user) && $user != false) {
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
				$holdingSortingData['homeBranch'] = $userLocation->holdingBranchLabel;
			}
			//Load nearby branch 1
			$nearbyLocation1 = new Location();
			$nearbyLocation1->whereAdd("locationId = '$nearbyBranch1Id'");
			$nearbyLocation1->find();
			if ($nearbyLocation1->N == 1) {
				$nearbyLocation1->fetch();
				$holdingSortingData['nearbyBranch1'] = $nearbyLocation1->holdingBranchLabel;
			}
			//Load nearby branch 2
			$nearbyLocation2 = new Location();
			$nearbyLocation2->whereAdd();
			$nearbyLocation2->whereAdd("locationId = '$nearbyBranch2Id'");
			$nearbyLocation2->find();
			if ($nearbyLocation2->N == 1) {
				$nearbyLocation2->fetch();
				$holdingSortingData['nearbyBranch2'] = $nearbyLocation2->holdingBranchLabel;
			}

			//Get a list of the display names for all locations based on holding label.
			$locationLabels = array();
			$location = new Location();
			$location->find();
			$holdingSortingData['libraryLocationLabels'] = array();
			$locationCodes = array();
			$suppressedLocationCodes = array();
			while ($location->fetch()) {
				if (strlen($location->holdingBranchLabel) > 0 && $location->holdingBranchLabel != '???') {
					if ($library && $library->libraryId == $location->libraryId) {
						$cleanLabel = str_replace('/', '\/', $location->holdingBranchLabel);
						$libraryLocationLabels[] = str_replace('.', '\.', $cleanLabel);
					}

					$locationLabels[$location->holdingBranchLabel] = $location->displayName;
					$locationCodes[$location->code] = $location->holdingBranchLabel;
					if ($location->suppressHoldings == 1) {
						$suppressedLocationCodes[$location->code] = $location->code;
					}
				}
			}
			if (count($holdingSortingData['libraryLocationLabels']) > 0) {
				$holdingSortingData['libraryLocationLabels'] = '/^(' . join('|', $holdingSortingData['libraryLocationLabels']) . ').*/i';
			} else {
				$holdingSortingData['libraryLocationLabels'] = '';
			}
			self::$holdingSortingData = $holdingSortingData;
			global $timer;
			$timer->logTime("Finished loading sorting information for holdings");
		}
		return self::$holdingSortingData;
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
		$fastItems = $this->getHolding($id);
		return $fastItems;
	}

	public function getStatus($id) {
		return $this->getHolding($id);
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

	private $holdings = array();
	public function getHolding($id) {
		if (isset($this->holdings[$id])){
			return $this->holdings[$id];
		}
		global $timer;
		global $library;

		$holdingSortingData = self::getSortingDataForHoldings();

		// Retrieve Full Marc Record
		$recordURL = null;

		$sorted_array = array();

		$holdingsFromKoha  = $this->getHoldingsFromKohaDB($id);
		$timer->logTime("Finished loading holdings from Koha");

		/** @var array $items */
		$i=0;
		foreach ($holdingsFromKoha as $item){
			$i++;

			//No data exists
			$itemData = $item;

			//Get the barcode from the horizon database
			$itemData['isLocalItem'] = false;
			$itemData['isLibraryItem'] = false;
			$itemData['locationLabel'] = $item['library'];

			$groupedStatus = mapValue('item_grouped_status', $item['status']);
			if ($groupedStatus == 'On Shelf' || $groupedStatus == 'Available Online'){
				$itemData['availability'] = true;
			}else{
				$itemData['availability'] = false;
			}

			//Make the item holdable by default.  Then check rules to make it non-holdable.
			$itemData['holdable'] = true;
			$itemData['reserve'] = 'N';

			$itemData['isDownload'] = false;

			$itemData['holdQueueLength'] = $this->getNumHolds($id);

			$itemData['statusfull'] = $itemData['status'];

			$itemData['shelfLocation'] = $itemData['library'];
			if (isset($item['location']) && $item['location'] != ''){
				$itemData['shelfLocation'] .= ' - ' . $item['location'];
			}
			if (isset($item['collection']) && $item['collection'] != ''){
				$itemData['shelfLocation'] .= ' - ' . $item['collection'];
			}
			$itemData['location'] = $itemData['shelfLocation'];

			$itemData['groupedStatus'] = mapValue('item_grouped_status', $itemData['statusfull']);

			$paddedNumber = str_pad(count($sorted_array) + 1, 3, '0', STR_PAD_LEFT);
			$sortString = $itemData['location'] . $itemData['callnumber'] . $paddedNumber;
			//$sortString = $holding['location'] . $holding['callnumber']. $i;
			if (strlen($holdingSortingData['physicalBranch']) > 0 && stripos($itemData['location'], $holdingSortingData['physicalBranch']) !== false){
				//If the user is in a branch, those holdings come first.
				$itemData['section'] = 'In this library';
				$itemData['sectionId'] = 1;
				$itemData['isLocalItem'] = true;
				$sorted_array['1' . $sortString] = $itemData;
			} else if (strlen($holdingSortingData['homeBranch']) > 0 && stripos($itemData['location'], $holdingSortingData['homeBranch']) !== false){
				//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
				$itemData['section'] = 'Your library';
				$itemData['sectionId'] = 2;
				$itemData['isLocalItem'] = true;
				$sorted_array['2' . $sortString] = $itemData;
			} else if ((strlen($holdingSortingData['nearbyBranch1']) > 0 && stripos($itemData['location'], $holdingSortingData['nearbyBranch1']) !== false)){
				//Next come nearby locations for the user
				$itemData['section'] = 'Nearby Libraries';
				$itemData['sectionId'] = 3;
				$sorted_array['3' . $sortString] = $itemData;
			} else if ((strlen($holdingSortingData['nearbyBranch2']) > 0 && stripos($itemData['location'], $holdingSortingData['nearbyBranch2']) !== false)){
				//Next come nearby locations for the user
				$itemData['section'] = 'Nearby Libraries';
				$itemData['sectionId'] = 4;
				$sorted_array['4' . $sortString] = $itemData;
			} else if (strlen($holdingSortingData['libraryLocationLabels']) > 0 && preg_match($holdingSortingData['libraryLocationLabels'], $itemData['location'])){
				//Next come any locations within the same system we are in.
				$holding['section'] = $library->displayName;
				$holding['sectionId'] = 5;
				$sorted_array['5' . $sortString] = $itemData;
			} else {
				//Finally, all other holdings are shown sorted alphabetically.
				$itemData['section'] = $library->displayName;
				$holding['sectionId'] = 5;
				$sorted_array['5' . $sortString] = $itemData;
			}
		}

		ksort($sorted_array);
		$this->holdings[$id] = $sorted_array;
		$timer->logTime("Finished loading status information");
		return $this->holdings[$id];
	}

	/**
	 * Returns a summary of the holdings information for a single id. Used to display
	 * within the search results and at the top of a full record display to ensure
	 * the holding information makes sense to all users.
	 *
	 * @param string $id the id of the bid to load holdings for
	 *
	 * @return array an associative array with a summary of the holdings.
	 */
	public function getStatusSummary($id){
		global $timer;
		global $library;
		global $locationSingleton;
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		//Holdings summaries need to be cached based on the actual location since part of the information
		//includes local call numbers and statuses.
		$ipLocation = $locationSingleton->getPhysicalLocation();
		$location = $ipLocation;
		if (!isset($location) && $location == null){
			$location = $locationSingleton->getUserHomeLocation();
		}
		$ipLibrary = null;
		if (isset($ipLocation)){
			$ipLibrary = new Library();
			$ipLibrary->libraryId = $ipLocation->libraryId;
			if (!$ipLibrary->find(true)){
				$ipLibrary = null;
			}
		}
		if (!isset($location) && $location == null){
			$locationId = -1;
		}else{
			$locationId = $location->locationId;
		}
		$summaryInformation = $memCache->get("holdings_summary_{$id}_{$locationId}" );
		if ($summaryInformation == false){

			$canShowHoldButton = true;
			if ($library && $library->showHoldButton == 0){
				$canShowHoldButton = false;
			}
			if ($location != null && $location->showHoldButton == 0){
				$canShowHoldButton = false;
			}

			$holdings = $this->getStatus($id);
			$timer->logTime('Retrieved Status of holding');

			$summaryInformation = array();
			$summaryInformation['recordId'] = $id;
			$summaryInformation['shortId'] = $id;
			$summaryInformation['isDownloadable'] = false; //Default value, reset later if needed.
			$summaryInformation['holdQueueLength'] = 0;

			//Check to see if we are getting issue summaries or actual holdings
			if (count($holdings) > 0){
				$lastHolding = end($holdings);
				if (isset($lastHolding['type']) && ($lastHolding['type'] == 'issueSummary' || $lastHolding['type'] == 'issue')){
					$issueSummaries = $holdings;
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
			foreach ($holdings as $holding){
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
					//Check to see if the location should be listed in the list of locations that the title is available at.
					//Can only be in this system if there is a system active.
					if (!in_array($holding['locationCode'], array_keys($availableLocations))){
						$availableLocations[$holding['locationCode']] =  $holding['location'];
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

			$memCache->set("holdings_summary_{$id}_{$locationId}", $summaryInformation, 0, $configArray['Caching']['holdings_summary']);
		}
		return $summaryInformation;
	}

	private $patronProfiles = array();

	/**
	 * @param User $user              The User Object to make updates to
	 * @param $canUpdateContactInfo   Permission check that updating is allowed
	 * @return array                  Array of error messages for errors that occurred
	 */
	function updatePatronInfo($user, $canUpdateContactInfo){
		$updateErrors = array();
		if ($canUpdateContactInfo) {
			$updateErrors[] = "Profile Information can not be updated.";
		}
		return $updateErrors;
	}

	private $transactions = array();
	public function getMyCheckouts($page = 1, $recordsPerPage = -1, $sortOption = 'dueDate') {
		if (true){
			return $this->getMyCheckoutsFromOpac($page, $recordsPerPage, $sortOption);
		}else{
			return $this->getMyCheckoutsFromDB($page, $recordsPerPage, $sortOption);
		}
	}

	public function getMyCheckoutsFromOpac($page = 1, $recordsPerPage = -1, $sortOption = 'dueDate') {
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
			preg_match_all('/<tr(?:.*?)>(.*?)<\/tr>/si', $tableBody, $tableData, PREG_PATTERN_ORDER);
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
							$transaction['renewIndicator'] = $renewalData[1];
							$numRenewalsRemaining = $renewalData[2];
							$numRenewalsAllowed = $renewalData[3];
							$transaction['renewCount'] = $numRenewalsAllowed - $numRenewalsRemaining;
						}elseif(preg_match('/not renewable.*?\\((\\d+) of (\\d+) renewals/si', $tableCell, $renewalData)){
							$transaction['renewable'] = false;
							$numRenewalsRemaining = $renewalData[1];
							$numRenewalsAllowed = $renewalData[2];
							$transaction['renewCount'] = $numRenewalsAllowed - $numRenewalsRemaining;
						}elseif(preg_match('/not renewable/si', $tableCell, $renewalData)){
							$transaction['renewable'] = false;
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
		return $transactions;
	}

	public function getMyCheckoutsFromDB($page = 1, $recordsPerPage = -1, $sortOption = 'dueDate') {
		global $user;

		if (isset($this->transactions[$user->id])){
			return $this->transactions[$user->id];
		}

		//Get transactions by screen scraping
		$transactions = array();

		$this->initDatabaseConnection();

		$sql = "SELECT issues.*, items.biblionumber, title, author from issues left join items on items.itemnumber = issues.itemnumber left join biblio ON items.biblionumber = biblio.biblionumber where borrowernumber = ?";
		$checkoutsStmt = mysqli_prepare($this->dbConnection, $sql);
		$checkoutsStmt->bind_param('i', $user->username);
		$checkoutsStmt->execute();

		$results = $checkoutsStmt->get_result();
		while ($curRow = $results->fetch_assoc()){
			$transaction = array();
			$transaction['checkoutSource'] = 'ILS';

			$transaction['id'] = $curRow['biblionumber'];
			$transaction['recordId'] = $curRow['biblionumber'];
			$transaction['shortId'] = $curRow['biblionumber'];
			$transaction['title'] = $curRow['title'];
			$transaction['author'] = $curRow['author'];

			$dateDue = DateTime::createFromFormat('Y-m-d', $curRow['date_due']);
			if ($dateDue){
				$dueTime = $dateDue->getTimestamp();
			}else{
				$dueTime = null;
			}
			$transaction['duedate'] = $dueTime;
			if ($dueTime != null){
				$daysUntilDue = ceil(($dueTime - time()) / (24 * 60 * 60));
				$overdue = $daysUntilDue < 0;
				$transaction['duedate'] = $dueTime;
				$transaction['overdue'] = $overdue;
				$transaction['daysUntilDue'] = $daysUntilDue;
			}
			$transaction['itemid'] = $curRow['id'];
			$transaction['renewIndicator'] = $curRow['id'];
			$transaction['renewCount'] = $curRow['renewals'];

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

			$transaction['user'] = $user->getNameAndLibraryLabel();

			$transactions[] = $transaction;
		}

		$this->transactions[$user->id] = $transactions;

		return $transactions;
	}


	protected function getKohaPage($kohaUrl){
		if ($this->cookieFile == null) {
			$this->cookieFile = tempnam("/tmp", "KOHACURL");
		}

		//Setup the connection to the url
		if ($this->curl_connection == null){
			$this->curl_connection = curl_init($kohaUrl);
			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($this->curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
			curl_setopt($this->curl_connection, CURLOPT_COOKIEJAR, $this->cookieFile);
			curl_setopt($this->curl_connection, CURLOPT_COOKIESESSION, is_null($this->cookieFile) ? true : false);
			curl_setopt($this->curl_connection, CURLOPT_TIMEOUT, 5);
		}else{
			curl_setopt($this->curl_connection, CURLOPT_URL, $kohaUrl);
		}

		curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);

		//Get the response from the page
		$sResult = curl_exec($this->curl_connection);
		return $sResult;
	}

	/**
	 * @param $kohaUrl
	 * @param $postParams
	 * @return mixed
	 */
	protected function postToKohaPage($kohaUrl, $postParams) {
		//Post parameters to the login url using curl
		//If we haven't created a file to store cookies, create one
		if ($this->cookieFile == null) {
			$this->cookieFile = tempnam("/tmp", "KOHACURL");
		}

		//Setup the connection to the url
		if ($this->curl_connection == null){
			$this->curl_connection = curl_init($kohaUrl);
			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($this->curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
			curl_setopt($this->curl_connection, CURLOPT_COOKIEJAR, $this->cookieFile);
			curl_setopt($this->curl_connection, CURLOPT_COOKIESESSION, is_null($this->cookieFile) ? true : false);
		}else{
			curl_setopt($this->curl_connection, CURLOPT_URL, $kohaUrl);
		}

		//Set post parameters
		curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, http_build_query($postParams));
		curl_setopt($this->curl_connection, CURLOPT_POST, true);

		//Get the response from the page
		$sResult = curl_exec($this->curl_connection);
		return $sResult;
	}

	/** @var mysqli_stmt  */
	private $getUserInfoStmt = null;
	public function patronLogin($username, $password) {
		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		global $timer;

		//Use MySQL connection to load data
		$this->initDatabaseConnection();

		$barcodesToTest = array();
		$barcodesToTest[] = $username;
		//Special processing to allow users to login with short barcodes
		global $library;
		if ($library){
			if ($library->barcodePrefix){
				if (strpos($username, $library->barcodePrefix) !== 0){
					//Add the barcode prefix to the barcode
					$barcodesToTest[] = $library->barcodePrefix . $username;
				}
			}
		}

		foreach ($barcodesToTest as $i=>$barcode) {
			$this->getUserInfoStmt->bind_param('ss', $barcode, $barcode);
			$encodedPassword = rtrim(base64_encode(pack('H*', md5($password))), '=');

			if ($this->getUserInfoStmt->execute()) {
				if ($userFromDbResultSet = $this->getUserInfoStmt->get_result()) {
					$userFromDb = $userFromDbResultSet->fetch_assoc();
					if ($userFromDb['password'] == $encodedPassword) {
						$userExistsInDB = false;
						$user = new User();
						//Get the unique user id from Millennium
						$user->source = $this->accountProfile->name;
						$user->username = $userFromDb['borrowernumber'];
						if ($user->find(true)){
							$userExistsInDB = true;
						}
						$user->firstname = $userFromDb['firstname'];
						$user->lastname = $userFromDb['surname'];
						$user->fullname = $userFromDb['firstname'] . ' ' . $userFromDb['surname'];
						$user->cat_username = $barcode;
						$user->cat_password =  $password;
						$user->email = $userFromDb['email'];
						$user->patronType = $userFromDb['categorycode'];
						$user->web_note = '';

						$city = strtok($userFromDb['city'], ',');
						$state = strtok(',');
						$city = trim($city);
						$state = trim($state);

						$user->address1 = trim($userFromDb['streetnumber'] . ' ' . $userFromDb['address'] . ' ' . $userFromDb['address2']);
						$user->city = $city;
						$user->state = $state;
						$user->zip = $userFromDb['zipcode'];
						$user->phone = $userFromDb['phone'];

						//Get fines
						//Load fines from database
						$outstandingFines = $this->getOutstandingFineTotal();
						$user->fines = sprintf('$%0.2f', $outstandingFines);
						$user->finesVal = floatval($outstandingFines);

						//Get number of items checked out
						$checkedOutItemsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numCheckouts FROM issues WHERE borrowernumber = ' . $user->username);
						$numCheckouts = 0;
						if ($checkedOutItemsRS){
							$checkedOutItems = $checkedOutItemsRS->fetch_assoc();
							$numCheckouts = $checkedOutItems['numCheckouts'];
							$checkedOutItemsRS->close();
						}
						$user->numCheckedOutIls = $numCheckouts;

						//Get number of available holds
						$availableHoldsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numHolds FROM reserves WHERE waitingdate is not null and borrowernumber = ' . $user->username);
						$numAvailableHolds = 0;
						if ($availableHoldsRS){
							$availableHolds = $availableHoldsRS->fetch_assoc();
							$numAvailableHolds = $availableHolds['numHolds'];
							$availableHoldsRS->close();
						}
						$user->numHoldsAvailableIls = $numAvailableHolds;

						//Get number of unavailable
						$waitingHoldsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numHolds FROM reserves WHERE waitingdate is null and borrowernumber = ' . $user->username);
						$numWaitingHolds = 0;
						if ($waitingHoldsRS){
							$waitingHolds = $waitingHoldsRS->fetch_assoc();
							$numWaitingHolds = $waitingHolds['numHolds'];
							$waitingHoldsRS->close();
						}
						$user->numHoldsRequestedIls = $numWaitingHolds;
						$user->numHoldsIls = $user->numHoldsAvailableIls + $user->numHoldsRequestedIls;

						$homeBranchCode = $userFromDb['branchcode'];
						$location = new Location();
						$location->code = $homeBranchCode;
						$location->find(1);
						if ($location->N == 0){
							unset($location);
							$user->homeLocationId = -1;
						}else{
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
						}

						$user->expires = $userFromDb['dateexpiry'];
						//TODO: Implement determining if the user is expired or if expiration is close

						$user->noticePreferenceLabel = 'Unknown';

						if ($userExistsInDB){
							$user->update();
						}else{
							$user->created = date('Y-m-d');
							$user->insert();
						}

						$timer->logTime("patron logged in successfully");

						$userFromDbResultSet->close();
						return $user;
					}
				}
			}
		}
		return null;
	}

	function initDatabaseConnection(){
		global $configArray;
		if ($this->dbConnection == null){
			$this->dbConnection = mysqli_connect($configArray['Catalog']['db_host'], $configArray['Catalog']['db_user'], $configArray['Catalog']['db_pwd'], $configArray['Catalog']['db_name']);

			if (mysqli_errno($this->dbConnection) != 0){
				global $logger;
				$logger->log("Error connecting to Koha database " . mysqli_error($this->dbConnection), PEAR_LOG_ERR);
				$this->dbConnection = null;
			}else{
				$sql = "SELECT borrowernumber, cardnumber, surname, firstname, streetnumber, streettype, address, address2, city, zipcode, country, email, phone, mobile, categorycode, dateexpiry, password, userid, branchcode from borrowers where cardnumber = ? OR userid = ?";
				$this->getUserInfoStmt = mysqli_prepare($this->dbConnection, $sql);
			}
			global $timer;
			$timer->logTime("Initialized connection to Koha");
		}
	}

	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile){
		$this->accountProfile = $accountProfile;
		global $timer;
		$timer->logTime("Crea11ted Aspencat Driver");
	}

	function __destruct(){
		//Cleanup any connections we have to other systems
		if ($this->curl_connection != null){
			curl_close($this->curl_connection);
		}
		if ($this->dbConnection != null){
			if ($this->getNumHoldsStmt != null){
				$this->getNumHoldsStmt->close();
			}
			if ($this->getHoldingsStmt != null){
				$this->getHoldingsStmt->close();
			}
			mysqli_close($this->dbConnection);
		}
		if ($this->cookieFile != null){
			unlink($this->cookieFile);
		}
	}

	public function hasNativeReadingHistory() {
		return true;
	}

	/**
	 * Get Reading History
	 *
	 * This is responsible for retrieving a history of checked out items for the patron.
	 *
	 * @param   array   $patron     The patron array
	 * @param   int     $page
	 * @param   int     $recordsPerPage
	 * @param   string  $sortOption
	 *
	 * @return  array               Array of the patron's reading list
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	public function getReadingHistory(
		/** @noinspection PhpUnusedParameterInspection */
		$patron = null, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		global $user;

		$this->initDatabaseConnection();

		//Figure out if the user is opted in to reading history

		$sql = "select disable_reading_history from borrowers where borrowernumber = {$user->username}";
		$historyEnabledRS = mysqli_query($this->dbConnection, $sql);
		if ($historyEnabledRS){
			$historyEnabledRow = $historyEnabledRS->fetch_assoc();
			$historyEnabled = !$historyEnabledRow['disable_reading_history'];

			if (!$historyEnabled){
				return array('historyActive'=>false, 'titles'=>array(), 'numTitles'=> 0);
			}else{
				$historyActive = true;
				$readingHistoryTitles = array();

				//Borrowed from C4:Members.pm
				$readingHistoryTitleSql = "SELECT *,issues.renewals AS renewals,items.renewals AS totalrenewals,items.timestamp AS itemstimestamp
					FROM issues
					LEFT JOIN items on items.itemnumber=issues.itemnumber
					LEFT JOIN biblio ON items.biblionumber=biblio.biblionumber
					LEFT JOIN biblioitems ON items.biblioitemnumber=biblioitems.biblioitemnumber
					WHERE borrowernumber=?
					UNION ALL
					SELECT *,old_issues.renewals AS renewals,items.renewals AS totalrenewals,items.timestamp AS itemstimestamp
					FROM old_issues
					LEFT JOIN items on items.itemnumber=old_issues.itemnumber
					LEFT JOIN biblio ON items.biblionumber=biblio.biblionumber
					LEFT JOIN biblioitems ON items.biblioitemnumber=biblioitems.biblioitemnumber
					WHERE borrowernumber=?";
				$readingHistoryTitleStmt = mysqli_prepare($this->dbConnection, $readingHistoryTitleSql);
				$readingHistoryTitleStmt->bind_param('ii', $user->username, $user->username);
				if ($readingHistoryTitleStmt->execute()){
					$readingHistoryTitleRS = $readingHistoryTitleStmt->get_result();
					while ($readingHistoryTitleRow = $readingHistoryTitleRS->fetch_assoc()){
						$curTitle = array();
						$curTitle['id'] = $readingHistoryTitleRow['biblionumber'];
						$curTitle['shortId'] = $readingHistoryTitleRow['biblionumber'];
						$curTitle['recordId'] = $readingHistoryTitleRow['biblionumber'];
						$curTitle['title'] = $readingHistoryTitleRow['title'];
						$curTitle['checkout'] = $readingHistoryTitleRow['itemstimestamp'];

						$readingHistoryTitles[] = $curTitle;
					}
				}
			}

			$numTitles = count($readingHistoryTitles);

			//process pagination
			if ($recordsPerPage != -1){
				$startRecord = ($page - 1) * $recordsPerPage;
				$readingHistoryTitles = array_slice($readingHistoryTitles, $startRecord, $recordsPerPage);
			}

			set_time_limit(20 * count($readingHistoryTitles));
			foreach ($readingHistoryTitles as $key => $historyEntry){
				//Get additional information from resources table
				$historyEntry['ratingData'] = null;
				$historyEntry['permanentId'] = null;
				$historyEntry['linkUrl'] = null;
				$historyEntry['coverUrl'] = null;
				$historyEntry['format'] = array();
				if (isset($historyEntry['recordId']) && strlen($historyEntry['recordId']) > 0){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
					$recordDriver = new MarcRecord($historyEntry['recordId']);
					if ($recordDriver->isValid()){
						$historyEntry['ratingData'] = $recordDriver->getRatingData();
						$historyEntry['permanentId'] = $recordDriver->getPermanentId();
						$historyEntry['linkUrl'] = $recordDriver->getLinkUrl();
						$historyEntry['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
						$historyEntry['format'] = $recordDriver->getFormats();
						$historyEntry['author'] = $recordDriver->getPrimaryAuthor();
					}
					$recordDriver = null;
				}
				$readingHistoryTitles[$key] = $historyEntry;
			}

			return array('historyActive'=>$historyActive, 'titles'=>$readingHistoryTitles, 'numTitles'=> $numTitles);
		}
		return array('historyActive'=>false, 'titles'=>array(), 'numTitles'=> 0);
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
		$sResult = $this->postToKohaPage($loginUrl, $postParams);

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
	public function placeHold($patron, $recordId, $pickupBranch){
		global $user;

		$hold_result = array();
		$hold_result['success'] = false;

		//Set pickup location
		$campus = strtoupper($pickupBranch);

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

		//Check to see if the title requires item level holds
		/** @var File_MARC_Data_Field[] $holdTypeFields */
		$itemLevelHoldAllowed = false;
		$itemLevelHoldOnly = false;
		$holdTypeFields = $marcRecord->getFields('942');
		foreach ($holdTypeFields as $holdTypeField){
			if ($holdTypeField->getSubfield('r') != null){
				if ($holdTypeField->getSubfield('r')->getData() == 'itemtitle'){
					$itemLevelHoldAllowed = true;
				}else if ($holdTypeField->getSubfield('r')->getData() == 'item'){
					$itemLevelHoldAllowed = true;
					$itemLevelHoldOnly = true;
				}
			}
		}

		//Get the items the user can place a hold on
		$this->loginToKoha($user);
		$placeHoldPage = $this->getKohaPage($configArray['Catalog']['url'] . '/cgi-bin/koha/opac-reserve.pl?biblionumber=' . $recordId);
		preg_match_all('/<div class="dialog alert">(.*?)<\/div>/s', $placeHoldPage, $matches);
		if (count($matches) > 0 && count($matches[1]) > 0){
			$hold_result['title'] = $recordDriver->getTitle();
			$hold_result['success'] = false;
			$hold_result['message'] = '';
			foreach ($matches[1] as $errorMsg){
				$hold_result['message'] .= $errorMsg . '<br/>';
			}
			return $hold_result;
		}

		if ($itemLevelHoldAllowed){
			//Need to prompt for an item level hold
			$items = array();
			if (!$itemLevelHoldOnly){
				//Add a first title returned
				$items[-1] = array(
					'itemNumber' => -1,
					'location' => 'Next available copy',
					'callNumber' => '',
					'status' => '',
				);
			}

			//Get the item table from the page
			if (preg_match('/<table>\\s+<caption>Select a specific copy:<\/caption>\\s+(.*?)<\/table>/s', $placeHoldPage, $matches)) {
				$itemTable = $matches[1];
				//Get the header row labels
				$headerLabels = array();
				preg_match_all('/<th[^>]*>(.*?)<\/th>/si', $itemTable, $tableHeaders, PREG_PATTERN_ORDER);
				foreach ($tableHeaders[1] as $col => $tableHeader){
					$headerLabels[$col] = trim(strip_tags(strtolower($tableHeader)));
				}

				//Grab each row within the table
				preg_match_all('/<tr[^>]*>\\s+(<td.*?)<\/tr>/si', $itemTable, $tableData, PREG_PATTERN_ORDER);
				foreach ($tableData[1] as $tableRow){
					//Each row in the table represents a hold

					$curItem = array();
					$validItem = false;
					//Go through each cell in the row
					preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tableRow, $tableCells, PREG_PATTERN_ORDER);
					foreach ($tableCells[1] as $col => $tableCell){
						if ($headerLabels[$col] == 'copy'){
							if (strpos($tableCell, 'disabled') === false){
								$validItem = true;
								if (preg_match('/value="(\d+)"/', $tableCell, $valueMatches)){
									$curItem['itemNumber'] = $valueMatches[1];
								}
							}
						}else if ($headerLabels[$col] == 'item type'){
							$curItem['itemType'] = trim($tableCell);
						}else if ($headerLabels[$col] == 'barcode'){
							$curItem['barcode'] = trim($tableCell);
						}else if ($headerLabels[$col] == 'home library'){
							$curItem['location'] = trim($tableCell);
						}else if ($headerLabels[$col] == 'call number'){
							$curItem['callNumber'] = trim($tableCell);
						}else if ($headerLabels[$col] == 'vol info'){
							$curItem['volInfo'] = trim($tableCell);
						}else if ($headerLabels[$col] == 'information'){
							$curItem['status'] = trim($tableCell);
						}
					}
					if ($validItem){
						$items[$curItem['itemNumber']] = $curItem;
					}
				}
			}elseif (preg_match('/<div class="dialog alert">(.*?)<\/div>/s', $placeHoldPage, $matches)){
				$items = array();
				$message = trim($matches[1]);
			}

			$hold_result['title'] = $recordDriver->getTitle();
			$hold_result['items'] = $items;
			if (count($items) > 0){
				$message = 'This title allows item level holds, please select an item to place a hold on.';
			}else{
				if (!isset($message)){
					$message = 'There are no holdable items for this title.';
				}
			}
			$hold_result['success'] = false;
			$hold_result['message'] = $message;
			return $hold_result;
		}else{
			//Just a regular bib level hold
			$hold_result['title'] = $recordDriver->getTitle();

			//Post the hold to koha
			$placeHoldPage = $configArray['Catalog']['url'] . '/cgi-bin/koha/opac-reserve.pl';
			$holdParams = array(
				'biblionumbers' => $recordId . '/',
				'branch' => $campus,
				'place_reserve' => 1,
				"reqtype_$recordId" => 'Any',
				'reserve_mode' => 'multi',
				'selecteditems' => "$recordId//$campus/",
				'single_bib' => $recordId,
			);
			$kohaHoldResult = $this->postToKohaPage($placeHoldPage, $holdParams);

			//If the hold is successful we go back to the account page and can see

			$hold_result['id'] = $recordId;
			if (preg_match('/<a href="#opac-user-holds">Holds<\/a>/si', $kohaHoldResult)) {
				//We redirected to the holds page, everything seems to be good
				$holds = $this->getMyHolds($user, 1, -1, 'title', $kohaHoldResult);
				$hold_result['success'] = true;
				$hold_result['message'] = "Your hold was placed successfully.";
				//Find the correct hold (will be unavailable)
				foreach ($holds['unavailable'] as $holdInfo){
					if ($holdInfo['id'] == $recordId){
						if (isset($holdInfo['position'])){
							$hold_result['message'] .= "  You are number <b>" . $holdInfo['position'] . "</b> in the queue.";
						}
						break;
					}
				}
			}else{
				$hold_result['success'] = false;
				//Look for an alert message
				if (preg_match('/<div class="dialog alert">(.*?)<\/div>/', $kohaHoldResult, $matches)){
					$hold_result['message'] = 'Your hold could not be placed. ' . $matches[1] ;
				}else{
					$hold_result['message'] = 'Your hold could not be placed. ' ;
				}

			}
			return $hold_result;
		}
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
		global $configArray;

		$hold_result = array();
		$hold_result['success'] = false;

		require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
		$recordDriver = new MarcRecord($recordId);
		if (!$recordDriver->isValid()){
			$hold_result['message'] = 'Unable to find a valid record for this title.  Please try your search again.';
			return $hold_result;
		}
		$hold_result['title'] = $recordDriver->getTitle();

		//Set pickup location
		if (isset($_REQUEST['campus'])){
			$campus=trim($_REQUEST['campus']);
		}else{
			$campus = $patron->homeLocationId;
			//Get the code for the location
			$locationLookup = new Location();
			$locationLookup->locationId = $campus;
			$locationLookup->find();
			if ($locationLookup->N > 0){
				$locationLookup->fetch();
				$campus = $locationLookup->code;
			}
		}
		$campus = strtoupper($campus);

		//Login before placing the hold
		$this->loginToKoha($patron);

		//Post the hold to koha
		$placeHoldPage = $configArray['Catalog']['url'] . '/cgi-bin/koha/opac-reserve.pl';
		$holdParams = array(
			'biblionumbers' => $recordId . '/',
			'branch' => $campus,
			"checkitem_$recordId" => $itemId,
			'place_reserve' => 1,
			"reqtype_$recordId" => 'Specific',
			'reserve_mode' => 'multi',
			'selecteditems' => "$recordId/$itemId/$campus/",
			'single_bib' => $recordId,
		);
		$kohaHoldResult = $this->postToKohaPage($placeHoldPage, $holdParams);

		$hold_result['id'] = $recordId;
		if (preg_match('/<a href="#opac-user-holds">Holds<\/a>/si', $kohaHoldResult)) {
			//We redirected to the holds page, everything seems to be good
			$holds = $this->getMyHolds($patron, 1, -1, 'title', $kohaHoldResult);
			$hold_result['success'] = true;
			$hold_result['message'] = "Your hold was placed successfully.";
			//Find the correct hold (will be unavailable)
			foreach ($holds['unavailable'] as $holdInfo){
				if ($holdInfo['id'] == $recordId){
					if (isset($holdInfo['position'])){
						$hold_result['message'] .= "  You are number <b>" . $holdInfo['position'] . "</b> in the queue.";
					}
					break;
				}
			}
		}else{
			$hold_result['success'] = false;
			//Look for an alert message
			if (preg_match('/<div class="dialog alert">(.*?)<\/div>/', $kohaHoldResult, $matches)){
				$hold_result['message'] = 'Your hold could not be placed. ' . $matches[1] ;
			}else{
				$hold_result['message'] = 'Your hold could not be placed. ' ;
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
	public function getMyHolds(/** @noinspection PhpUnusedParameterInspection */
		$patron, $page = 1, $recordsPerPage = -1, $sortOption = 'title'){
		if (true){
			return $this->getMyHoldsFromOpac($patron);
		}else{
			return $this->getMyHoldsFromDB($patron);
		}
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds by a specific patron.
	 *
	 * @param array|User $patron      The patron array from patronLogin
	 *
	 * @return mixed        Array of the patron's holds on success, PEAR_Error
	 * otherwise.
	 * @access public
	 */
	public function getMyHoldsFromOpac($patron){
		global $logger;
		global $user;
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);
		//Get transactions by screen scraping
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
			preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableBody, $tableData, PREG_PATTERN_ORDER);
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
							$expireDate = DateTime::createFromFormat('m/d/Y', $tableCell);
							$curHold['expire'] = $expireDate->getTimestamp();
						}
					}elseif ($headerLabels[$col] == 'pick up location'){
						if (strlen($tableCell) != 0){
							$curHold['location'] = trim($tableCell);
							$curHold['locationUpdateable'] = false;
							$curHold['currentPickupName'] = $curHold['location'];
						}
					}elseif ($headerLabels[$col] == 'priority'){
						$curHold['position'] = trim($tableCell);
					}elseif ($headerLabels[$col] == 'status'){
						$curHold['status'] = trim($tableCell);
					}elseif ($headerLabels[$col] == 'cancel'){
						$curHold['cancelable'] = strlen($tableCell) > 0;
						if (preg_match('/<input type="hidden" name="reservenumber" value="(.*?)" \/>/', $tableCell, $matches)) {
							$curHold['cancelId'] = $matches[1];
						}
					}elseif ($headerLabels[$col] == 'suspend'){
						if (preg_match('/cannot be suspended/i', $tableCell)){
							$curHold['freezeable'] = false;
						}else{
							$curHold['freezeable'] = true;
						}

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
				if (!isset($curHold['status']) || !preg_match('/^Item waiting.*/i', $curHold['status'])){
					$holds['unavailable'][] = $curHold;
				}else{
					$holds['available'][] = $curHold;
				}
			}
		}
		//Get the suspended holds table
		if (preg_match_all('/<table id="sholdst">(.*?)<\/table>/si', $summaryPage, $holdsTableData, PREG_SET_ORDER)){
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
			preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableBody, $tableData, PREG_PATTERN_ORDER);
			foreach ($tableData[1] as $tableRow){
				//Each row in the table represents a hold
				$curHold= array();
				$curHold['holdSource'] = 'ILS';
				$curHold['frozen'] = true;
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
					}elseif ($headerLabels[$col] == 'resume now'){
						$curHold['cancelable'] = false;
						$curHold['freezeable'] = false;
						if (preg_match('/<input type="hidden" name="reservenumber" value="(.*?)" \/>/', $tableCell, $matches)) {
							$curHold['cancelId'] = $matches[1];
						}
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
				$curHold['user'] = $patron->getNameAndLibraryLabel();
				if (!isset($curHold['status']) || strcasecmp($curHold['status'], "filled") != 0){
					$holds['unavailable'][] = $curHold;
				}else{
					$holds['available'][] = $curHold;
				}
			}
		}
		return $holds;
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
	 * @param string $summaryPage     If the summary page has already been loaded, it can be passed in for performance reasons.
	 *
	 * @return mixed        Array of the patron's holds on success, PEAR_Error
	 * otherwise.
	 * @access public
	 */
	public function getMyHoldsFromDB(/** @noinspection PhpUnusedParameterInspection */
		$patron, $page = 1, $recordsPerPage = -1, $sortOption = 'title'){
		global $user;

		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);

		$this->initDatabaseConnection();

		$sql = "SELECT *, title, author FROM reserves inner join biblio on biblio.biblionumber = reserves.biblionumber where borrowernumber = ?";
		$holdsStmt = mysqli_prepare($this->dbConnection, $sql);
		$holdsStmt->bind_param('i', $user->username);
		$holdsStmt->execute();

		$results = $holdsStmt->get_result();
		while ($curRow = $results->fetch_assoc()){
			//Each row in the table represents a hold
			$curHold= array();
			$curHold['holdSource'] = 'ILS';
			$bibId = $curRow['biblionumber'];
			$curHold['id'] = $curRow['biblionumber'];
			$curHold['shortId'] = $curRow['biblionumber'];
			$curHold['recordId'] = $curRow['biblionumber'];
			$curHold['title'] = $curRow['title'];
			$curHold['create'] = date_parse_from_format('Y-M-d H:m:s', $curRow['reservedate']);
			$curHold['expire'] = date_parse_from_format('Y-M-d', $curRow['expirationdate']);
			$curHold['location'] = $curRow['branchcode'];
			$curHold['locationUpdateable'] = false;
			$curHold['currentPickupName'] = $curHold['location'];
			$curHold['position'] = $curRow['priority'];
			$curHold['frozen'] = false;
			$curHold['freezeable'] = false;
			$curHold['cancelable'] = true;
			if ($curRow['found'] == 'S'){
				$curHold['frozen'] = true;
				$curHold['status'] = "Suspended";
				$curHold['cancelable'] = false;
			}elseif ($curRow['found'] == 'W'){
				$curHold['status'] = "Ready to Pickup";
			}elseif ($curRow['found'] == 'T'){
				$curHold['status'] = "In Transit";
			}else{
				$curHold['status'] = "Pending";
				$curHold['freezeable'] = true;
			}
			$curHold['freezeable'] = true;
			$curHold['cancelId'] = $curRow['reservenumber'];

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
			$curHold['user'] = $patron->getNameAndLibraryLabel();

			if (!isset($curHold['status']) || !preg_match('/^Item waiting.*/i', $curHold['status'])){
				$holds['unavailable'][] = $curHold;
			}else{
				$holds['available'][] = $curHold;
			}
		}

		return $holds;
	}

	public function updateHold($requestId, $patronId, $type, $title){
		$xnum = "x" . $_REQUEST['x'];
		//Strip the . off the front of the bib and the last char from the bib
		if (isset($_REQUEST['cancelId'])){
			$cancelId = $_REQUEST['cancelId'];
		}else{
			$cancelId = substr($requestId, 1, -1);
		}
		$locationId = $_REQUEST['location'];
		$freezeValue = isset($_REQUEST['freeze']) ? 'on' : 'off';
		return $this->updateHoldDetailed($patronId, $type, $title, $xnum, $cancelId, $locationId, $freezeValue);
	}

	/**
	 * Update a hold that was previously placed in the system.
	 * Can cancel the hold or update pickup locations.
	 */
	public function updateHoldDetailed($patron, $type, $title, $xNum, $cancelId, $locationId, $freezeValue='off'){
		global $configArray;

		if (!isset($xNum) || empty($xNum)){
			if (isset($_REQUEST['waitingholdselected']) || isset($_REQUEST['availableholdselected'])){
				$waitingHolds = isset($_REQUEST['waitingholdselected']) ? $_REQUEST['waitingholdselected'] : array();
				$availableHolds = isset($_REQUEST['availableholdselected']) ? $_REQUEST['availableholdselected'] : array();
				$holdKeys = array_merge($waitingHolds, $availableHolds);
			}else{
				if (is_array($cancelId)){
					$holdKeys = $cancelId;
				}else{
					$holdKeys = array($cancelId);
				}
			}
		}else{
			$holdKeys = $xNum;
		}

		//In all cases, we need to login
		$result = $this->loginToKoha($patron);
		if ($type == 'cancel'){
			$allCancelsSucceed = true;
			$originalHolds = $this->getMyHolds($patron, 1, -1, 'title', $result['summaryPage']);

			//Post a request to koha
			foreach ($holdKeys as $holdKey){
				//Get the record Id for the hold
				if (isset($_REQUEST['recordId'][$holdKey])){
					$recordId = $_REQUEST['recordId'][$holdKey];
				}else{
					$recordId = "";
				}

				$postParams = array(
					'biblionumber' => $recordId,
					'reservenumber' => $holdKey,
					'submit' => 'Cancel'
				);
				$catalogUrl = $configArray['Catalog']['url'];
				$cancelUrl = "$catalogUrl/cgi-bin/koha/opac-modrequest.pl";
				$kohaHoldResult = $this->postToKohaPage($cancelUrl, $postParams);

				//Parse the result
				$updatedHolds = $this->getMyHolds($patron, 1, -1, 'title', $kohaHoldResult);
				if ((count($updatedHolds['available']) + count($updatedHolds['unavailable'])) < (count($originalHolds['available']) + count($originalHolds['unavailable']))){
					//We cancelled the hold
				}else{
					$allCancelsSucceed = false;
				}
			}
			if ($allCancelsSucceed){
				return array(
					'title' => $title,
					'success' => true,
					'message' => count($holdKeys) == 1 ? 'Cancelled 1 hold successfully.' : 'Cancelled ' . count($holdKeys) . ' hold(s) successfully.');
			}else{
				return array(
					'title' => $title,
					'success' => false,
					'message' => 'Some holds could not be cancelled.  Please try again later or see your librarian.');
			}
		}else{
			if ($locationId){
				return array(
					'title' => $title,
					'success' => false,
					'message' => 'Changing location for a hold is not supported.');
			}else{
				//Freeze/Thaw the hold
				if ($freezeValue == 'on'){
					//Suspend the hold

					$allLocationChangesSucceed = true;

					foreach ($holdKeys as $holdKey){
						$postParams = array(
							'suspend' => 1,
							'reservenumber' => $holdKey,
							'submit' => 'Suspend'
						);
						if (isset($_REQUEST['reactivationDate'])){
							$reactivationDate = strtotime($_REQUEST['reactivationDate']);
							$reactivationDate = date('m-d-Y', $reactivationDate);
							$postParams['resumedate_' . $holdKey] = $reactivationDate;
						}else{
							$postParams['resumedate_' . $holdKey] = '';
						}
						$catalogUrl = $configArray['Catalog']['url'];
						$updateUrl = "$catalogUrl/cgi-bin/koha/opac-modrequest.pl";
						$kohaUpdateResults = $this->postToKohaPage($updateUrl, $postParams);

						//Check the result of the update
					}
					if ($allLocationChangesSucceed){
						$this->clearPatronProfile();
						return array(
							'title' => $title,
							'success' => true,
							'message' => 'Your hold(s) were frozen successfully.');
					}else{
						return array(
							'title' => $title,
							'success' => false,
							'message' => 'Some holds could not be frozen.  Please try again later or see your librarian.');
					}
				}else{
					//Reactivate the hold
					$allUnsuspendsSucceed = true;

					foreach ($holdKeys as $holdKey){
						$postParams = array(
							'resume' => 1,
							'reservenumber' => $holdKey,
							'submit' => 'Resume'
						);
						$catalogUrl = $configArray['Catalog']['url'];
						$updateUrl = "$catalogUrl/cgi-bin/koha/opac-modrequest.pl";
						$this->postToKohaPage($updateUrl, $postParams);
						$this->clearPatronProfile();
					}
					if ($allUnsuspendsSucceed){
						return array(
							'title' => $title,
							'success' => true,
							'message' => 'Your hold(s) were thawed successfully.');
					}else{
						return array(
							'title' => $title,
							'success' => false,
							'message' => 'Some holds could not be thawed.  Please try again later or see your librarian.');
					}
				}
			}
		}
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

	public function renewItem($patron, $recordId, $itemId, $itemIndex){
		global $analytics;
		global $configArray;

		//Get the session token for the user
		$loginResult = $this->loginToKoha($user);
		if ($loginResult['success']){
			global $analytics;
			$postParams = array(
				'from' => 'opac_user',
				'item' => $itemId,
				'borrowernumber' => $patron->username,
			);
			$catalogUrl = $configArray['Catalog']['url'];
			$kohaUrl = "$catalogUrl/cgi-bin/koha/opac-renew.pl";
			$kohaUrl .= "?" . http_build_query($postParams);

			$kohaResponse = $this->getKohaPage($kohaUrl);

			//TODO: Renewal Failure Messages needed
			if (true) {
				$success = true;
				$message = 'Your item was successfully renewed.';
				//Clear the patron profile
				$this->clearPatronProfile();
				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Renew Successful');
				}
			}else{
				$success = false;
				$message = 'Invalid Response from SIP 2';
				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Renew Failed', $message);
				}
			}
		}else{
			$success = false;
			$message = 'Unable to login2';
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Failed', $message);
			}
		}

		return array(
			'itemId' => $itemId,
			'success'  => $success,
			'message' => $message);
	}

	/**
	 * Get a list of fines for the user.
	 * Code take from C4::Account getcharges method
	 *
	 * @param null $patron
	 * @param bool $includeMessages
	 * @return array
	 */
	public function getMyFines(
			/** @noinspection PhpUnusedParameterInspection */
			$patron = null, $includeMessages = false){

		global $user;

		$this->initDatabaseConnection();

		//Get a list of outstanding fees
		$query = "SELECT * FROM fees JOIN fee_transactions AS ft on(id = fee_id) WHERE borrowernumber = ? and accounttype in (select accounttype from accounttypes where class='fee' or class='invoice') ";

		$allFeesStmt = mysqli_prepare($this->dbConnection, $query);
		$allFeesStmt->bind_param('i', $user->username);
		$allFeesStmt->execute( );
		$allFeesRS = $allFeesStmt->get_result();

		$query2 = "SELECT sum(amount) as amountOutstanding from fees LEFT JOIN fee_transactions on (fees.id=fee_transactions.fee_id) where fees.id = ?";
		$outstandingFeesStmt = mysqli_prepare($this->dbConnection, $query2);

		$fines = array();
		while ($allFeesRow = $allFeesRS->fetch_assoc()){
			$feeId = $allFeesRow['id'];
			$outstandingFeesStmt->bind_param('i', $feeId);
			$outstandingFeesStmt->execute();

			$outstandingFeesRow = $outstandingFeesStmt->get_result()->fetch_assoc();
			$amountOutstanding = $outstandingFeesRow['amountOutstanding'];
			if ($amountOutstanding > 0){
				$curFine = array(
					'date' => $allFeesRow['timestamp'],
					'reason' => $allFeesRow['accounttype'],
					'message' => $allFeesRow['description'],
					'amount' => $allFeesRow['amount'],
					'amountOutstanding' => $amountOutstanding,
				);
				$fines[] = $curFine;
			}
		}

		return $fines;
	}

	/**
	 * Do an update or edit of reading history information.  Current actions are:
	 * deleteMarked
	 * deleteAll
	 * exportList
	 * optOut
	 *
	 * @param   string  $action         The action to perform
	 * @param   array   $selectedTitles The titles to do the action on if applicable
	 */
	function doReadingHistoryAction($action, /** @noinspection PhpUnusedParameterInspection */
	                                $selectedTitles){
		global $configArray;
		global $analytics;
		global $user;
		if (!$this->loginToKoha($user)){
			return;
		}else{
			if ($action == 'deleteMarked'){

				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Delete Marked Reading History Titles');
				}
			}elseif ($action == 'deleteAll'){

				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Delete All Reading History Titles');
				}
			}elseif ($action == 'exportList'){
				//Leave this unimplemented for now.
			}elseif ($action == 'optOut'){
				$kohaUrl = $configArray['Catalog']['url'] . '/cgi-bin/koha/opac-update_reading_history.pl';
				$postParams = array(
					'disable_reading_history' => 1
				);
				$this->postToKohaPage($kohaUrl, $postParams);
				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Opt Out of Reading History');
				}
			}elseif ($action == 'optIn'){
				$kohaUrl = $configArray['Catalog']['url'] . '/cgi-bin/koha/opac-update_reading_history.pl';
				$postParams = array(
					'disable_reading_history' => 0
				);
				$this->postToKohaPage($kohaUrl, $postParams);
				if ($analytics){
					$analytics->addEvent('ILS Integration', 'Opt in to Reading History');
				}
			}
			$this->clearPatronProfile();
		}
	}

	public function clearPatronProfile() {
		/** @var Memcache $memCache */
		global $memCache, $user, $serverName;
		$memCache->delete("patronProfile_{$serverName}_{$user->username}");
	}

	private $holdsByBib = array();
	/** @var mysqli_stmt  */
	private $getNumHoldsStmt = null;
	public function getNumHolds($id) {
		if (isset($this->holdsByBib[$id])){
			return $this->holdsByBib[$id];
		}
		$numHolds = 0;

		$this->initDatabaseConnection();
		if ($this->getNumHoldsStmt == null){
			$sql = "SELECT count(*) from reserves where biblionumber = ?";
			$this->getNumHoldsStmt = mysqli_prepare($this->dbConnection, $sql);
		}
		$this->getNumHoldsStmt->bind_param("i", $recordId);
		if (!$this->getNumHoldsStmt->execute()){
			global $logger;
			$logger->log("Unable to load hold count from Koha ({$this->getNumHoldsStmt->errno}) {$this->getNumHoldsStmt->error}", PEAR_LOG_ERR);

		}else{
			$results = $this->getNumHoldsStmt->get_result();
			$curRow = $results->fetch_row();
			$numHolds = $curRow[0];
			$results->close();
		}

		$this->holdsByBib[$id] = $numHolds;

		global $timer;
		$timer->logTime("Finished loading num holds for record ");

		return $numHolds;
	}

	/** @var mysqli_stmt  */
	private $getHoldingsStmt = null;

	/**
	 * Load all items from the database.
	 *
	 * Uses some code based on C4::Items GetItemsInfo in koha
	 *
	 * @param $recordId
	 * @return array
	 */
	private function getHoldingsFromKohaDB($recordId){
		$holdingsFromKoha = array();

		$this->initDatabaseConnection();

		if ($this->getHoldingsStmt == null){
			$sql = "SELECT itemnumber, barcode, itype, holdingbranch, location, itemcallnumber, onloan, ccode, itemnotes, enumchron, damaged, itemlost, wthdrawn, restricted FROM items where biblionumber = ? AND suppress = 0";
			$this->getHoldingsStmt = mysqli_prepare($this->dbConnection, $sql);
		}
		$this->getHoldingsStmt->bind_param("i", $recordId);

		if (!$this->getHoldingsStmt->execute()){
			global $logger;
			$logger->log("Unable to load holdings from Koha ({$this->getHoldingsStmt->errno}) {$this->getHoldingsStmt->error}", PEAR_LOG_ERR);
		}else{
			//Read the information
			$results = $this->getHoldingsStmt->get_result();
			while ($curRow = $results->fetch_assoc()){
				if ($curRow['itype'] == 'EAUDIO' || $curRow['itype'] == 'EBOOK' || $curRow['itype'] == 'ONLINE'){
					continue;
				}
				$curItem = array();
				$curItem['type'] = 'holding';
				$curItem['id'] = $curRow['itemnumber'];
				$curItem['barcode'] = $curRow['barcode'];
				$curItem['itemType'] = mapValue('itype', $curRow['itype']);
				$curItem['locationCode'] = $curRow['location'];
				$curItem['library'] = mapValue('location', $curRow['holdingbranch']);
				$curItem['location'] = $curRow['location'];
				$curItem['collection'] = mapValue('ccode', $curRow['ccode']);
				$curItem['callnumber'] = $curRow['itemcallnumber'];
				$curItem['volInfo'] = $curRow['enumchron'];
				$curItem['copy'] = $curRow['itemcallnumber'];

				$curItem['notes'] = $curRow['itemnotes'];
				$curItem['dueDate'] = $curRow['onloan'];

				//Figure out status based on all of the fields that make up the status
				if ($curRow['damaged'] == 1){
					$curItem['status'] = "Damaged";
				}else if ($curRow['itemlost'] != null){
					if ($curRow['itemlost'] == 'longoverdue'){
						$curItem['status'] = "Long Overdue";
					}elseif ($curRow['itemlost'] == 'missing'){
						$curItem['status'] = "Missing";
					}elseif ($curRow['itemlost'] == 'lost'){
						$curItem['status'] = "Lost";
					} elseif ($curRow['itemlost'] == 'trace'){
						$curItem['status'] = "Trace";
					}
				}else if ($curRow['restricted'] == 1 ){
					$curItem['status'] = "Not For Loan";
				}else if ($curRow['wthdrawn'] == 1){
					$curItem['status'] = "Withdrawn";
				}else{
					if ($curItem['dueDate'] == null){
						$curItem['status'] = "On Shelf";
					}else{
						$curItem['status'] = "Due {$curItem['dueDate']}";
					}
				}

				$holdingsFromKoha[] = $curItem;
			}
			$results->close();
		}

		return $holdingsFromKoha;
	}

	/**
	 * Get Total Outstanding fines for a user.  Lifted from Koha:
	 * C4::Accounts.pm gettotalowed method
	 *
	 * @return mixed
	 */
	private function getOutstandingFineTotal() {
		global $user;
		//Since borrowernumber is stored in fees and payments, not fee_transactions,
		//this is done with two queries: the first gets all outstanding charges, the second
		//picks up any unallocated credits.
		$this->initDatabaseConnection();
		$stmt = mysqli_prepare($this->dbConnection, "SELECT SUM(amount) FROM fees LEFT JOIN fee_transactions on(fees.id = fee_transactions.fee_id) where fees.borrowernumber = ?");
		$stmt->bind_param('i', $user->username);
		/** @var mysqli_result $result */
		$stmt->execute( );
		$amountOutstanding = $stmt->get_result()->fetch_array();
		$amountOutstanding = $amountOutstanding[0];

		$creditStmt = mysqli_prepare($this->dbConnection, "SELECT SUM(amount) FROM payments LEFT JOIN fee_transactions on(payments.id = fee_transactions.payment_id) where payments.borrowernumber = ? and fee_id is null" );
		$creditStmt->bind_param('i', $user->username);
		$creditStmt->execute();
		$credit = $creditStmt->get_result()->fetch_array();
		$credit = $credit[0];
		if ($credit != null){
			$amountOutstanding += $credit;
		}

		return $amountOutstanding ;
	}

	// use User->isStaff() instead
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

	function cancelHold($patron, $recordId, $cancelId) {
		return $this->updateHoldDetailed($patron, 'cancel', '', null, $cancelId, '', '');
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate) {
		return $this->updateHoldDetailed($patron, 'update', '', null, $itemToFreezeId, '', 'on');
	}

	function thawHold($patron, $recordId, $itemToThawId) {
		return $this->updateHoldDetailed($patron, 'update', '', null, $itemToThawId, '', 'off');
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation) {
		return $this->updateHoldDetailed($patron, 'update', '', null, $itemToUpdateId, $newPickupLocation, 'off');
	}
}