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

	public function getHolding($id) {
		global $timer;
		global $configArray;

		$allItems = array();

		//Get location information so we can put things into sections
		global $library;
		global $locationSingleton; /** @var $locationSingleton Location */
		$physicalLocation = $locationSingleton->getPhysicalLocation();
		if ($physicalLocation != null){
			$physicalBranch = $physicalLocation->holdingBranchLabel;
		}else{
			$physicalBranch = '';
		}
		$homeBranch    = '';
		$homeBranchId  = 0;
		$nearbyBranch1 = '';
		$nearbyBranch1Id = 0;
		$nearbyBranch2 = '';
		$nearbyBranch2Id = 0;

		//Set location information based on the user login.  This will override information based
		if (isset($user) && $user != false){
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
			$homeBranch = $userLocation->holdingBranchLabel;
		}
		//Load nearby branch 1
		$nearbyLocation1 = new Location();
		$nearbyLocation1->whereAdd("locationId = '$nearbyBranch1Id'");
		$nearbyLocation1->find();
		if ($nearbyLocation1->N == 1) {
			$nearbyLocation1->fetch();
			$nearbyBranch1 = $nearbyLocation1->holdingBranchLabel;
		}
		//Load nearby branch 2
		$nearbyLocation2 = new Location();
		$nearbyLocation2->whereAdd();
		$nearbyLocation2->whereAdd("locationId = '$nearbyBranch2Id'");
		$nearbyLocation2->find();
		if ($nearbyLocation2->N == 1) {
			$nearbyLocation2->fetch();
			$nearbyBranch2 = $nearbyLocation2->holdingBranchLabel;
		}

		//Get a list of the display names for all locations based on holding label.
		$locationLabels = array();
		$location = new Location();
		$location->find();
		$libraryLocationLabels = array();
		$locationCodes = array();
		$suppressedLocationCodes = array();
		while ($location->fetch()){
			if (strlen($location->holdingBranchLabel) > 0 && $location->holdingBranchLabel != '???'){
				if ($library && $library->libraryId == $location->libraryId){
					$cleanLabel =  str_replace('/', '\/', $location->holdingBranchLabel);
					$libraryLocationLabels[] = str_replace('.', '\.', $cleanLabel);
				}

				$locationLabels[$location->holdingBranchLabel] = $location->displayName;
				$locationCodes[$location->code] = $location->holdingBranchLabel;
				if ($location->suppressHoldings == 1){
					$suppressedLocationCodes[$location->code] = $location->code;
				}
			}
		}
		if (count($libraryLocationLabels) > 0){
			$libraryLocationLabels = '/^(' . join('|', $libraryLocationLabels) . ').*/i';
		}else{
			$libraryLocationLabels = '';
		}

		// Retrieve Full Marc Record
		$recordURL = null;

		$sorted_array = array();
		require_once ROOT_DIR . '/sys/MarcLoader.php';
		$marcRecord = MarcLoader::loadMarcRecordByILSId($id);
		$callNumber = '';
		if ($marcRecord) {
			$timer->logTime('Loaded MARC record from search object');
			if (!$configArray['Reindex']['useItemBasedCallNumbers']){
				/** @var File_MARC_Data_Field $callNumberField */
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
			$items = $marcRecord->getFields($configArray['Reindex']['itemTag']);
			$itemRecordNumberSubfield = $configArray['Reindex']['itemRecordNumberSubfield'];
			$barcodeSubfield    = $configArray['Reindex']['barcodeSubfield'];
			$locationSubfield   = $configArray['Reindex']['locationSubfield'];
			$callnumberSubfield = $configArray['Reindex']['callNumberSubfield'];

			//Get the holdings from aspencat
			$catalogUrl = $configArray['Catalog']['url'];
			$kohaUrl = "$catalogUrl/cgi-bin/koha/opac-detail.pl?biblionumber=$id";
			$holdingsPage = $this->getKohaPage($kohaUrl);
			$holdingsFromKoha = $this->getHoldingsFromKoha($holdingsPage);

			$firstItemWithSIPdata = null;
			/** @var File_MARC_Data_Field[] $items */
			$i=0;
			foreach ($items as $item){
				$i++;
				$barcode = trim($item->getSubfield($barcodeSubfield) != null ? $item->getSubfield($barcodeSubfield)->getData() : '');
				//Check to see if we already have data for this barcode
				/** @var Memcache $memCache */
				global $memCache;
				if (isset($barcode) && strlen($barcode) > 0 && !isset($_REQUEST['reload'])){
					$itemData = $memCache->get("item_data_{$barcode}");
				}else{
					$itemData = false;
				}
				if ($itemData == false){
					//No data exists
					$itemData = array();

					$itemData['id'] = trim($item->getSubfield($itemRecordNumberSubfield) != null ? $item->getSubfield($itemRecordNumberSubfield)->getData() : '');
					$itemData['type'] = 'holding';
					//Get the barcode from the horizon database
					$itemData['isLocalItem'] = false;
					$itemData['isLibraryItem'] = false;
					$itemData['locationCode'] = trim(strtolower( $item->getSubfield($locationSubfield) != null ? $item->getSubfield($locationSubfield)->getData() : '' ));
					$itemData['location'] = mapValue('location', $itemData['locationCode']);
					if ($itemData['location'] == ''){
						$itemData['location'] = $itemData['locationCode'];
					}
					$itemData['locationLabel'] = $itemData['location'];

					if (!$configArray['Reindex']['useItemBasedCallNumbers'] && $callNumber != ''){
						$itemData['callnumber'] = $callNumber;
					}else{
						$itemData['callnumber'] = trim($item->getSubfield($callnumberSubfield) != null ? $item->getSubfield($callnumberSubfield)->getData() : '');
					}
					$itemData['callnumber'] = str_replace("~", " ", $itemData['callnumber']);
					//Set default status
					$status = $this->getItemStatus($item);
					$itemData['status'] = $status;

					$groupedStatus = mapValue('item_grouped_status', $status);
					if ($groupedStatus == 'On Shelf' || $groupedStatus == 'Available Online'){
						$itemData['availability'] = true;
					}else{
						$itemData['availability'] = false;
					}

					//Make the item holdable by default.  Then check rules to make it non-holdable.
					$itemData['holdable'] = true;
					$itemData['reserve'] = 'N';

					$itemData['isDownload'] = false;

					$itemData['barcode'] = $barcode;
					$itemData['copy'] = $item->getSubfield('e') != null ? $item->getSubfield('e')->getData() : '';

					$itemData['holdQueueLength'] = 0;
					if (preg_match('/<span class="HoldsLabel">holds<\/span>: (\\d+)/i', $holdingsPage, $matches)) {
						$itemData['holdQueueLength'] = $matches[1];
					}

					$itemData['collection'] = mapValue('collection', $item->getSubfield('c') != null ? $item->getSubfield('c')->getData() : '');

					$itemData['statusfull'] = $itemData['status'];

					//Match the record to the data loaded from koha
					foreach ($holdingsFromKoha as $kohaItem){
						if ($kohaItem['matched'] == false){
							if ($itemData['location'] && $kohaItem['callnumber']){
								$locationMatched = strpos($kohaItem['library'], $itemData['location']) === 0;
								$callNumberMatched = strpos($kohaItem['callnumber'], $itemData['callnumber']) === 0;
								if ($locationMatched && $callNumberMatched){
									//TODO: Do a reverse mapping to get status code
									$itemData['statusfull'] = $kohaItem['status'];
									$itemData['dueDate'] = $kohaItem['dueDate'];
									$kohaItem['matched'] = true;
									if (isset($kohaItem['location']) && $kohaItem['location'] != ''){
										$itemData['location'] .= ' - ' . $kohaItem['location'];
									}
									if (isset($kohaItem['collection']) && $kohaItem['collection'] != ''){
										$itemData['location'] .= ' - ' . $kohaItem['collection'];
									}
									//Only need to match against one record
									break;
								}
							}
						}
					}
					$itemData['shelfLocation'] = $itemData['location'];
					$itemData['groupedStatus'] = mapValue('item_grouped_status', $itemData['statusfull']);

					//Suppress items based on status
					if (isset($barcode) && strlen($barcode) > 0){
						$memCache->set("item_data_{$barcode}", $itemData, 0, $configArray['Caching']['item_data']);
					}
				}


				$paddedNumber = str_pad(count($allItems) + 1, 3, '0', STR_PAD_LEFT);
				$sortString = $itemData['location'] . $itemData['callnumber'] . $paddedNumber;
				//$sortString = $holding['location'] . $holding['callnumber']. $i;
				if (strlen($physicalBranch) > 0 && stripos($itemData['location'], $physicalBranch) !== false){
					//If the user is in a branch, those holdings come first.
					$itemData['section'] = 'In this library';
					$itemData['sectionId'] = 1;
					$itemData['isLocalItem'] = true;
					$sorted_array['1' . $sortString] = $itemData;
				} else if (strlen($homeBranch) > 0 && stripos($itemData['location'], $homeBranch) !== false){
					//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
					$itemData['section'] = 'Your library';
					$itemData['sectionId'] = 2;
					$itemData['isLocalItem'] = true;
					$sorted_array['2' . $sortString] = $itemData;
				} else if ((strlen($nearbyBranch1) > 0 && stripos($itemData['location'], $nearbyBranch1) !== false)){
					//Next come nearby locations for the user
					$itemData['section'] = 'Nearby Libraries';
					$itemData['sectionId'] = 3;
					$sorted_array['3' . $sortString] = $itemData;
				} else if ((strlen($nearbyBranch2) > 0 && stripos($itemData['location'], $nearbyBranch2) !== false)){
					//Next come nearby locations for the user
					$itemData['section'] = 'Nearby Libraries';
					$itemData['sectionId'] = 4;
					$sorted_array['4' . $sortString] = $itemData;
					//MDN 11/17 - taken out because all Horizon libraries are single institution (so far)
				} else if (strlen($libraryLocationLabels) > 0 && preg_match($libraryLocationLabels, $itemData['location'])){
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
		}
		$timer->logTime("Finished loading status information");

		ksort($sorted_array);
		return $sorted_array;
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

	public function getPurchaseHistory($id) {
		// TODO: Implement getPurchaseHistory() method.
	}

	private $patronProfiles = array();

	/**
	 * @param User $patron
	 * @param bool $forceReload
	 * @return array
	 */
	public function getMyProfile($patron, $forceReload = false) {
		//Update to not use SIP, just screen scrape for performance (sigh)
		global $timer;
		global $configArray;

		if (!is_object($patron)){
			$tmpPatron = new User();
			$tmpPatron->username = $patron['username'];
			$tmpPatron->cat_username = $patron['cat_username'];
			$tmpPatron->cat_password = $patron['cat_password'];
			$tmpPatron->firstname = $patron['firstname'];
			$tmpPatron->lastname = $patron['lastname'];
			$tmpPatron->email = $patron['email'];
			$tmpPatron->patronType = $patron['patronType'];
			$patron = $tmpPatron;
		}

		if (array_key_exists($patron->username, $this->patronProfiles) && !$forceReload){
			$timer->logTime('Retrieved Cached Profile for Patron');
			return $this->patronProfiles[$patron->username];
		}

		$result = $this->loginToKoha($patron);
		if ($result['success']) {
			//Get the my personal details page
			$catalogUrl = $configArray['Catalog']['url'];
			$kohaUrl = "$catalogUrl/cgi-bin/koha/opac-userupdate.pl";
			$personalDetailsPage = $this->getKohaPage($kohaUrl);
			$summaryPage = $result['summaryPage'];

			if (preg_match('/<input [^>]*id="streetnumber"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
				$streetnumber = $matches[1];
			} else {
				$streetnumber = "";
			}
			if (preg_match('/<input [^>]*id="address"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
				$address = $matches[1];
			} else {
				$address = "";
			}
			if (preg_match('/<input [^>]*id="address2"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
				$address2 = $matches[1];
			} else {
				$address2 = "";
			}

			if (preg_match('/<input [^>]*id="city"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
				$city = $matches[1];
				$state = "";
				$commaPos = strpos($city, ',');
				if ($commaPos !== false){
					$cityState = $city;
					$city = substr($cityState, 0, $commaPos);
					$state = substr($cityState, $commaPos);
				}
			} else {
				$city = "";
				$state = "";
			}
			if (preg_match('/<input [^>]*id="zipcode"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
				$zipcode = $matches[1];
			} else {
				$zipcode = "";
			}
			if (preg_match('/<input [^>]*id="phone"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
				$phone = $matches[1];
			} else {
				$phone = "";
			}
			if (preg_match('/<input [^>]*id="email"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
				$email = $matches[1];
			} else {
				$email = "";
			}
			if (preg_match('/<input [^>]*id="dateexpiry"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
				$formattedExpiration = $matches[1];
			} else {
				$formattedExpiration = "";
			}

			//Get fines
			$kohaUrl = "$catalogUrl/cgi-bin/koha/opac-account.pl";
			$finesPage = $this->getKohaPage($kohaUrl);
			if (preg_match('/<span class="debit">(.*?)<\/span>/', $finesPage, $matches)) {
				$fines = $matches[1];
			} else {
				$fines = "";
			}

			//Get number of items checked out
			if (preg_match('/<caption>(\d) Items Checked Out<\/caption>/', $summaryPage, $matches)) {
				$numItemsOut = $matches[1];
			} else {
				$numItemsOut = 0;
			}
			$numAvailableHolds = preg_match('/Item waiting/', $summaryPage);
			if (preg_match('/<caption><span class="HoldsLabel">Holds <\/span><span class="count">\((\d+) total\)<\/span><\/caption>/', $summaryPage, $matches)) {
				$numHoldsTotal = $matches[1];
			} else {
				$numHoldsTotal = 0;
			}
			if (preg_match('/<caption>Suspended <span class="HoldsLabel">Holds <\/span><span class="count">\((\d+) total\)<\/span><\/caption>/', $summaryPage, $matches)) {
				$numHoldsTotal += $matches[1];
			}

			//TODO: Determine if there is some way of getting the location for the user.  So far, there does not seem to be.

			global $user;
			$profile = array(
				'lastname' => $patron->lastname,
				'firstname' => $patron->firstname,
				'displayName' => isset($patron->displayName) ? $patron->displayName : '',
				'fullname' => $user->lastname . ', ' . $user->firstname,
				'address1' => trim($streetnumber . ' ' . $address . ' ' . $address2),
				'city' => $city,
				'state' => $state,
				'zip' => $zipcode,
				'phone' => $phone,
				'email' => $email,
				'homeLocationId' => -1,
				'homeLocationName' => '',
				'expires' => $formattedExpiration,
				'fines' => $fines,
				'finesval' => floatval($fines),
				'numHolds' => $numHoldsTotal,
				'numHoldsAvailable' => $numAvailableHolds,
				'numHoldsRequested' => $numHoldsTotal - $numAvailableHolds,
				'numCheckedOut' => $numItemsOut ,
				'bypassAutoLogout' => ($user ? $user->bypassAutoLogout : false),
			);
			$profile['noticePreferenceLabel'] = 'Unknown';

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
				$homeLibrary = Library::getPatronHomeLibrary();
				if ($homeLibrary){
					$materialsRequest = new MaterialsRequest();
					$materialsRequest->createdBy = $user->id;
					$statusQuery = new MaterialsRequestStatus();
					$statusQuery->isOpen = 1;
					$statusQuery->libraryId = $homeLibrary->libraryId;
					$materialsRequest->joinAdd($statusQuery);
					$materialsRequest->find();
					$profile['numMaterialsRequests'] = $materialsRequest->N;
				}else{
					$profile['numMaterialsRequests'] = 0;
				}
			}
		} else {
			$profile = new PEAR_Error('patron_info_error_technical - invalid patron information response');
		}

		$this->patronProfiles[$patron->username] = $profile;
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

	public function patronLogin($username, $password) {
		//Koha uses SIP2 authentication for login.  See

		global $timer;
		global $configArray;

		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		if ($configArray['Catalog']['offline'] == true){
			//The catalog is offline, check the database to see if the user is valid
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
		}else{
			//Login through SIP is proving unreliable (works maybe 1/5 of the time).
			//Therefore, change to scraping the login process of classic.
			$tmpUser = new User();
			$tmpUser->cat_username = $username;
			$tmpUser->cat_password = $password;
			$loginResult = $this->loginToKoha($tmpUser);
			if ($loginResult['success']){
				//Get the my personal details page
				$catalogUrl = $configArray['Catalog']['url'];
				$kohaUrl = "$catalogUrl/cgi-bin/koha/opac-userupdate.pl";
				$personalDetailsPage = $this->getKohaPage($kohaUrl);
				if (preg_match('/<input [^>]*id="borrowernumber"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
					$patronNumber = $matches[1];
				} else {
					$patronNumber = "";
				}
				if (preg_match('/<input [^>]*id="firstname"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
					$firstname = $matches[1];
				} else {
					$firstname = "";
				}
				if (preg_match('/<input [^>]*id="surname"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
					$lastname = $matches[1];
				} else {
					$lastname = "";
				}
				if (preg_match('/<input [^>]*id="email"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
					$email = $matches[1];
				} else {
					$email = "";
				}
				if (preg_match('/<input [^>]*id="categorycode"[^>]*value="(.*?)"[^>]*\/>/', $personalDetailsPage, $matches)) {
					$categorycode = $matches[1];
				} else {
					$categorycode = "";
				}

				$returnVal = array(
					'username'  => $patronNumber, //The unique id of the patron in the ILS
					'firstname' => $firstname,
					'lastname'  => $lastname,
					'fullname'  => $firstname . ' ' . $lastname,     //Added to array for possible display later.
					'cat_username' => $username,
					'cat_password' => $password,

					'email' => $email,
					'major' => null,
					'college' => null,
					'patronType' => $categorycode,
					'web_note' => ''
				);
				return $returnVal;
			}else{
				return null;
			}
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

	function __construct(){
		global $timer;
		$timer->logTime("Created Aspencat Driver");
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
		global $configArray;
		global $logger;
		if (!$this->loginToKoha($user)){
			return array('historyActive'=>false, 'titles'=>array(), 'numTitles'=> 0);
		}else{
			//Get the reading history page
			$catalogUrl = $configArray['Catalog']['url'];
			$kohaUrl = "$catalogUrl/cgi-bin/koha/opac-readingrecord.pl?limit=full";
			$readingHistoryPage = $this->getKohaPage($kohaUrl);
			if (strpos($readingHistoryPage, '<input type="radio" name="disable_reading_history" value="0" checked>') !== false){
				$historyActive = true;
			}else{
				$historyActive = false;
			}
			$readingHistoryTitles = array();

			//Get the table
			if (preg_match_all('/<table id="readingrec">(.*?)<\/table>/si', $readingHistoryPage, $tableData, PREG_SET_ORDER)){
				$table = $tableData[0][0];

				//Get the header row labels
				$headerLabels = array();
				preg_match_all('/<th[^>]*>(.*?)<\/th>/si', $table, $tableHeaders, PREG_PATTERN_ORDER);
				foreach ($tableHeaders[1] as $col => $tableHeader){
					$headerLabels[$col] = trim(strip_tags(strtolower($tableHeader)));
				}

				//Get each row within the table
				preg_match_all('/<tr>\s+(<td.*?)<\/tr>/si', $table, $tableData, PREG_PATTERN_ORDER);

				foreach ($tableData[1] as $tableRow){
					//Each row in the table represents a title in the reading history
					$curTitle = array();
					preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tableRow, $tableCells, PREG_PATTERN_ORDER);
					foreach ($tableCells[1] as $col => $tableCell){
						//The first column in the headers is merged.  Adjust appropriately.
						$col -= 1;
						if ($col < 0){
							continue;
						}
						if ($headerLabels[$col] == 'title'){
							if (preg_match('/biblionumber=(\\d+)".*?>(.*?)<\/a>/si', $tableCell, $cellDetails)) {
								$curTitle['id'] = $cellDetails[1];
								$curTitle['shortId'] = $cellDetails[1];
								$curTitle['recordId'] = $cellDetails[1];
								$curTitle['title'] = $cellDetails[2];
							}else{
								$logger->log("Could not parse title for reading history entry", PEAR_LOG_WARNING);
								$curTitle['title'] = strip_tags($tableCell);
							}
						}elseif ($headerLabels[$col] == 'call no.'){
							//Ignore this for now
						}elseif ($headerLabels[$col] == 'date'){
							$curTitle['checkout'] = strip_tags($tableCell);
						}
					}
					$readingHistoryTitles[] = $curTitle;
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
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $patronId   The id of the patron
	 * @param   string  $comment    Any comment regarding the hold or recall
	 * @param   string  $type       Whether to place a hold or recall
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	public function placeHold(
		/** @noinspection PhpUnusedParameterInspection */
		$recordId, $patronId, $comment, $type){
		global $user;

		$hold_result = array();
		$hold_result['result'] = false;

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
		$holdTypeFields = $marcRecord->getFields('942');
		foreach ($holdTypeFields as $holdTypeField){
			if ($holdTypeField->getSubfield('r') != null){
				if ($holdTypeField->getSubfield('r')->getData() == 'itemtitle'){
					$itemLevelHoldAllowed = true;
				}
			}
		}

		//Get the items the user can place a hold on
		$this->loginToKoha($user);
		$placeHoldPage = $this->getKohaPage($configArray['Catalog']['url'] . '/cgi-bin/koha/opac-reserve.pl?biblionumber=' . $recordId);

		if ($itemLevelHoldAllowed){
			//Need to prompt for an item level hold
			$items = array();
			//Add a first title returned
			$items[-1] = array(
				'itemNumber' => -1,
				'location' => 'Next available copy',
				'callNumber' => '',
				'status' => '',
			);

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
							}
						}else if ($headerLabels[$col] == 'item type'){
							$curItem['itemType'] = trim($tableCell);
						}else if ($headerLabels[$col] == 'barcode'){
							$curItem['itemNumber'] = trim($tableCell);
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
			}

			$hold_result['title'] = $recordDriver->getTitle();
			$hold_result['items'] = $items;
			if (count($items) > 0){
				$message = 'This title allows item level holds, please select an item to place a hold on.';
			}else{
				$message = 'There are no holdable items for this title.';
			}
			$hold_result['result'] = false;
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
				$hold_result['result'] = true;
				$hold_result['message'] = "Your hold was placed successfully.";
				//Find the correct hold (will be unavailable)
				foreach ($holds['holds']['unavailable'] as $holdInfo){
					if ($holdInfo['id'] == $recordId){
						if (isset($holdInfo['position'])){
							$hold_result['message'] .= "  You are number <b>" . $holdInfo['position'] . "</b> in the queue.";
						}
						break;
					}
				}
			}else{
				$hold_result['result'] = false;
				$hold_result['message'] = 'Your hold could not be placed. ' ;
			}
			return $hold_result;
		}
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $itemId     The id of the item to hold
	 * @param   string  $patronId   The id of the patron
	 * @param   string  $comment    Any comment regarding the hold or recall
	 * @param   string  $type       Whether to place a hold or recall
	 * @param   string  $type       The date when the hold should be cancelled if any
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	public function placeItemHold(
		/** @noinspection PhpUnusedParameterInspection */
		$recordId, $itemId, $patronId, $comment, $type){
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
		if ($itemId == -1){
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
		}else{
			$barcodeToHold = $itemId;
		}

		$hold_result['title'] = $recordDriver->getTitle();

		$holdType = 3;
		if ($itemId == -1){
			$holdType = 2;
		}
		$in = $this->sipConnection->msgHold($mode, '', $holdType, $barcodeToHold, $recordId, '', strtoupper($campus));
		$msg_result = $this->sipConnection->get_message($in);

		$hold_result['id'] = $recordId;
		if (preg_match("/^16/", $msg_result)) {
			$result = $this->sipConnection->parseHoldResponse($msg_result );
			$hold_result['result'] = ($result['fixed']['Ok'] == 1);
			if (isset($result['variable']['AF'])){
				$hold_result['message'] = $result['variable']['AF'][0];
			}else{
				if ($result['fixed']['Ok'] == 1){
					$hold_result['message'] = 'Your hold was successful';
				}else{
					$hold_result['message'] = 'Your could not be placed';
				}
			}

			//Get the hold position.
			if ($result['fixed']['Ok'] == 1){
				$holds = $this->getMyHolds($user);
				//Find the correct hold (will be unavailable)
				foreach ($holds['holds']['unavailable'] as $holdInfo){
					if ($holdInfo['id'] == $recordId){
						if (isset($holdInfo['position'])){
							$hold_result['message'] .= "  You are number <b>" . $holdInfo['position'] . "</b> in the queue.";
						}
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
	 * @param string $summaryPage     If the summary page has already been loaded, it can be passed in for performance reasons.
	 *
	 * @return mixed        Array of the patron's holds on success, PEAR_Error
	 * otherwise.
	 * @access public
	 */
	public function getMyHolds(/** @noinspection PhpUnusedParameterInspection */
		$patron, $page = 1, $recordsPerPage = -1, $sortOption = 'title', $summaryPage = null){
		global $logger;
		global $user;

		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);

		//Get transactions by screen scraping
		if ($summaryPage == null){
			//Login to Koha classic interface
			$result = $this->loginToKoha($user);
			if (!$result['success']){
				return $holds;
			}

			//Get the summary page that contains both checked out titles and holds
			$summaryPage = $result['summaryPage'];
		}

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
						$curHold['freezeable'] = true;
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
	public function updateHoldDetailed(/** @noinspection PhpUnusedParameterInspection */
		$patronId, $type, $title, $xNum, $cancelId, $locationId, $freezeValue='off'){
		global $configArray;

		global $user;

		if (!isset($xNum) ){
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


		if ($type == 'cancel'){
			$allCancelsSucceed = true;
			$result = $this->loginToKoha($user);
			$originalHolds = $this->getMyHolds($user, 1, -1, 'title', $result['summaryPage']);
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
				$updatedHolds = $this->getMyHolds($user, 1, -1, 'title', $kohaHoldResult);
				if ((count($updatedHolds['holds']['available']) + count($updatedHolds['holds']['unavailable'])) < (count($originalHolds['holds']['available']) + count($originalHolds['holds']['unavailable']))){
					//We cancelled the hold
				}else{
					$allCancelsSucceed = false;
				}
			}
			if ($allCancelsSucceed){
				return array(
					'title' => $title,
					'result' => true,
					'message' => count($holdKeys) == 1 ? 'Cancelled 1 hold successfully.' : 'Cancelled ' . count($holdKeys) . ' hold(s) successfully.');
			}else{
				return array(
					'title' => $title,
					'result' => false,
					'message' => 'Some holds could not be cancelled.  Please try again later or see your librarian.');
			}
		}else{
			if ($locationId){
				return array(
					'title' => $title,
					'result' => false,
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
						$this->postToKohaPage($updateUrl, $postParams);
					}
					if ($allLocationChangesSucceed){
						$this->clearPatronProfile();
						return array(
							'title' => $title,
							'result' => true,
							'message' => 'Your hold(s) were frozen successfully.');
					}else{
						return array(
							'title' => $title,
							'result' => false,
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
							'result' => true,
							'message' => 'Your hold(s) were thawed successfully.');
					}else{
						return array(
							'title' => $title,
							'result' => false,
							'message' => 'Some holds could not be thawed.  Please try again later or see your librarian.');
					}
				}
			}
		}
	}

	public function renewAll(){
		//Get all list of all transactions
		$currentTransactions = $this->getMyTransactions();
		$renewResult = array();
		$renewResult['Total'] = $currentTransactions['numTransactions'];
		$numRenewals = 0;
		$failure_messages = array();
		foreach ($currentTransactions['transactions'] as $transaction){
			$curResult = $this->renewItem($transaction['renewIndicator'], null);
			if ($curResult['result']){
				$numRenewals++;
			} else {
				$failure_messages[] = $curResult['message'];
			}
		}
		$renewResult['Renewed'] = $numRenewals;
		$renewResult['Unrenewed'] = $renewResult['Total'] - $renewResult['Renewed'];
		if ($renewResult['Unrenewed'] > 0) {
			$renewResult['result'] = false;
		}else{
			$renewResult['result'] = true;
			$renewResult['message'] = "All items were renewed successfully.";
		}
		return $renewResult;
	}

	public function renewItem(/** @noinspection PhpUnusedParameterInspection */
		$itemId, $itemIndex){
		global $analytics;
		global $user;
		global $configArray;

		//Get the session token for the user
		$loginResult = $this->loginToKoha($user);
		if ($loginResult['success']){
			global $analytics;
			$postParams = array(
				'from' => 'opac_user',
				'item' => $itemId,
				'borrowernumber' => $user->username,
			);
			$catalogUrl = $configArray['Catalog']['url'];
			$kohaUrl = "$catalogUrl/cgi-bin/koha/opac-renew.pl";
			$kohaUrl .= "?" . http_build_query($postParams);

			$this->getKohaPage($kohaUrl);

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
			'result'  => $success,
			'message' => $message);
	}

	public function getMyFines(
			/** @noinspection PhpUnusedParameterInspection */
			$patron = null, $includeMessages = false){

		$messages = array();
		global $user;
		if ($this->loginToKoha($user)){
			//Load the information from millennium using CURL
			global $configArray;
			$catalogUrl = $configArray['Catalog']['url'];
			$kohaUrl = "$catalogUrl/cgi-bin/koha/opac-account.pl";
			$pageContents = $this->getKohaPage($kohaUrl);

			//Get the fines table data
			if (preg_match('/<table>(.*?)<\/table>/si', $pageContents, $regs)) {
				$table = $regs[1];

				//Get the header row labels
				$headerLabels = array();
				preg_match_all('/<th[^>]*>(.*?)<\/th>/si', $table, $tableHeaders, PREG_PATTERN_ORDER);
				foreach ($tableHeaders[1] as $col => $tableHeader){
					$headerLabels[$col] = trim(strip_tags(strtolower($tableHeader)));
				}

				//Get each row within the table
				//Grab the table body
				preg_match('/<tbody>(.*?)<\/tbody>/si', $table, $tableBody);
				$tableBody = $tableBody[1];
				preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableBody, $tableData, PREG_PATTERN_ORDER);

				foreach ($tableData[1] as $tableRow){
					//Each row in the table represents a hold
					$curHold= array();
					$curHold['holdSource'] = 'ILS';
					//Go through each cell in the row
					preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tableRow, $tableCells, PREG_PATTERN_ORDER);
					$message = array(
						'reason' => '',
					);
					foreach ($tableCells[1] as $col => $tableCell){
						//Based off which column we are in, fill out the transaction
						if ($headerLabels[$col] == 'date'){
							$message['date'] = strip_tags($tableCell);
						}elseif ($headerLabels[$col] == 'description'){
							$message['message'] = strip_tags($tableCell);
						}elseif ($headerLabels[$col] == 'fine amount'){
							$message['amount'] = strip_tags($tableCell);
						}elseif ($headerLabels[$col] == 'amount outstanding'){
							$message['amount_outstanding'] = strip_tags($tableCell);
						}
					}
					if ($message['reason'] == '&nbsp' || $message['reason'] == '&nbsp;'){
						$message['reason'] = 'Fee';
					}

					$messages[] = $message;
				}
			}
		}

		return $messages;
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
		global $memCache;
		global $user;
		$memCache->delete('patronProfile_' . $user->id);
	}

	public function getNumHolds($id) {
		return 0;
	}

	private function getHoldingsFromKoha($holdingsPage) {
		$holdingsFromKoha = array();

		//Get the table
		if (preg_match_all('/<div id="holdings">.*?<table.*?>(.*?)<\/table>/si', $holdingsPage, $tableData, PREG_SET_ORDER)){
			$table = $tableData[0][1];
			//Get the header row labels
			$headerLabels = array();
			preg_match_all('/<th[^>]*>(.*?)<\/th>/si', $table, $tableHeaders, PREG_PATTERN_ORDER);
			foreach ($tableHeaders[1] as $col => $tableHeader){
				$headerLabels[$col] = trim(strip_tags(strtolower($tableHeader)));
			}

			//Get each row within the table
			//Grab the table body
			preg_match('/<tbody>(.*?)<\/tbody>/si', $table, $tableBody);
			$tableBody = $tableBody[1];
			preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableBody, $tableData, PREG_PATTERN_ORDER);

			foreach ($tableData[1] as $tableRow){
				$curItem = array();
				$curItem['matched'] = false;

				//Go through each cell in the row
				preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tableRow, $tableCells, PREG_PATTERN_ORDER);
				foreach ($tableCells[1] as $col => $tableCell){
					if ($headerLabels[$col] == 'item type'){
						$curItem['itemType'] = trim($tableCell);
					}else if ($headerLabels[$col] == 'library'){
						//Remove courier code
						$library = trim($tableCell);
						$curItem['library'] = $library;
					}else if ($headerLabels[$col] == 'location'){
						$curItem['location'] = trim($tableCell);
					}else if ($headerLabels[$col] == 'call number'){
						$curItem['callnumber'] = trim($tableCell);
					}else if ($headerLabels[$col] == 'vol info'){
						$curItem['volInfo'] = trim($tableCell);
					}else if ($headerLabels[$col] == 'copy'){
						$curItem['copy'] = trim($tableCell);
					}else if ($headerLabels[$col] == 'status'){
						$curItem['status'] = trim($tableCell);
					}else if ($headerLabels[$col] == 'notes'){
						$curItem['notes'] = trim($tableCell);
					}else if ($headerLabels[$col] == 'date due'){
						$curItem['dueDate'] = trim($tableCell);
					}
				}
				$holdingsFromKoha[] = $curItem;
			}

		}

		return $holdingsFromKoha;
	}

	/**
	 * @param File_MARC_Data_Field $item The item to load data for
	 */
	private function getItemStatus($itemField) {
		//Determining status for Koha relies on a number of different fields
		$status = $this->getStatusFromSubfield($itemField, '0', "Withdrawn");
		if ($status != null) return $status;

		$status = $this->getStatusFromSubfield($itemField, '1', "Lost");
		if ($status != null) return $status;

		$status = $this->getStatusFromSubfield($itemField, '4', "Damaged");
		if ($status != null) return $status;

		$status = $this->getStatusFromSubfield($itemField, 'q', "Checked Out");
		if ($status != null) return $status;

		$status = $this->getStatusFromSubfield($itemField, '7', "Library Use Only");
		if ($status != null) return $status;

		$status = $this->getStatusFromSubfield($itemField, 'k', null);
		if ($status != null) return $status;

		return "On Shelf";
	}

	/**
	 * @param File_MARC_Data_Field $itemField
	 * @param string $subfield
	 * @param string $defaultStatus
	 * @return null|string
	 */
	private function getStatusFromSubfield($itemField, $subfield, $defaultStatus) {
		if ($itemField->getSubfield($subfield) != null){
			$fieldData = $itemField->getSubfield($subfield)->getData();
			if (!$fieldData == "0") {
				if ($fieldData == "1") {
					return $defaultStatus;
				}else{
					if ($subfield == 'q'){
						if (preg_match('/\d{4}-\d{2}-\d{2}/', $fieldData)){
							return "Checked Out";
						}
					}else if ($subfield == '1'){
						if ($fieldData == "lost"){
							return "Lost";
						}else if ($fieldData == "missing"){
							return "Missing";
						}
					}else if ($subfield == 'k') {
						if ($fieldData == "CATALOGED" || $fieldData == "READY") {
							return null;
						}else if ($fieldData == "BINDERY"){
							return "Bindery";
						}else if ($fieldData == "IN REPAIRS"){
							return "Repair";
						}
					}
				}
			}
		}
		return null;
	}
}