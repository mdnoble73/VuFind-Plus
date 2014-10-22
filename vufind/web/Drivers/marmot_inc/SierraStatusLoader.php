<?php
/**
 * Class SierraStatusLoader
 *
 * Processes status information from Sierra to load holdings.
 */
require_once ROOT_DIR . '/Drivers/marmot_inc/MillenniumStatusLoader.php';
require_once ROOT_DIR . '/RecordDrivers/Factory.php';
class SierraStatusLoader extends MillenniumStatusLoader{
	/** @var  Sierra $driver */
	private $driver;

	public function __construct($driver){
		parent::__construct($driver);
		$this->driver = $driver;
	}

	private static $loadedStatus = array();
	/**
	 * In Sierra, all status information is up to date within the MARC record
	 * due to the export so we don't need to screen scrape!
	 *
	 * Format of return array is:
	 * key = {section#}{location}-### where ### is the holding iteration
	 *
	 * value = array (
	 *  id = The id of the bib
	 *  number = The position of the holding within the original list of holdings
	 *  section = A description of the section
	 *  sectionId = a numeric id of the section for sorting
	 *  type = holding
	 *  status
	 *  statusfull
	 *  reserve
	 *  holdQueueLength
	 *  duedate
	 *  location
	 *  locationLink
	 *  callnumber
	 *  link = array
	 *  linkText
	 *  isDownload
	 * )
	 *
	 * Includes both physical titles as well as titles on order
	 *
	 * @param string            $id     the id of the record
	 * @return array A list of holdings for the record
	 */
	public function getStatus($id){
		$recordDriver = RecordDriverFactory::initRecordDriverById('ils:' . $id);
		$format = $recordDriver->getFormat();
		if ($format[0] == 'Journal'){
			return parent::getStatus($id);
		}
		if (array_key_exists($id, SierraStatusLoader::$loadedStatus)){
			return SierraStatusLoader::$loadedStatus[$id];
		}

		//Load local information
		global $library;
		global $locationSingleton; /** @var $locationSingleton Location */
		global $user;

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

		//In Sierra, we can just load data from the MARC Record/Index
		$items = $recordDriver->getItemsFast();
		$holdQueueLength = $recordDriver->getNumHolds();
		$itemStatus = array();
		$i = 0;
		foreach ($items as $item){
			//Determine what section this holding is in
			$sectionId = 1;
			$location = $item['shelfLocation'];
			if (strlen($physicalBranch) > 0 && stripos($location, $physicalBranch) !== false){
				//If the user is in a branch, those holdings come first.
				$section = 'In this library';
				$sectionId = 1;
			} else if (strlen($homeBranch) > 0 && stripos($location, $homeBranch) !== false){
				//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
				$section = 'Your library';
				$sectionId = 2;
			} else if ((strlen($nearbyBranch1) > 0 && stripos($location, $nearbyBranch1) !== false)){
				//Next come nearby locations for the user
				$section = 'Nearby Libraries';
				$sectionId = 3;
			} else if ((strlen($nearbyBranch2) > 0 && stripos($location, $nearbyBranch2) !== false)){
				//Next come nearby locations for the user
				$section = 'Nearby Libraries';
				$sectionId = 4;
			} else if (strlen($libraryLocationLabels) > 0 && preg_match($libraryLocationLabels, $location)){
				//Next come any locations within the same system we are in.
				$section = $library->displayName;
				$sectionId = 5;
			} else {
				//Finally, all other holdings are shown sorted alphabetically.
				$section = 'Other Locations';
				$sectionId = 6;
			}

			$holding = array(
				'location' => $item['shelfLocation'],
				'reserve' => stripos($item['shelfLocation'], 'reserve') !== false ? 'Y' : 'N',
				'callnumber' => $item['callnumber'],
				'status' => $item['status'],
				'duedate' => $item['dueDate'],
				'statusfull' => $this->translateStatusCode($item['status'], $item['dueDate']),
				'id' => $id,
				'number' => $i++,
				'holdQueueLength' => $holdQueueLength,
				'type' => 'holding',
				'availability' => $item['availability'],
				'holdable' => $item['holdable'] ? 1 : 0,
				'libraryDisplayName' => $item['shelfLocation'],
				'locationCode' => $item['location'],
				'iType' => $item['iType'],
				'section' => $section,
				'sectionId' => $sectionId,
			);

			$paddedNumber = str_pad($i, 3, '0', STR_PAD_LEFT);
			$holdingKey = $sectionId . $holding['location'] . $paddedNumber;
			$itemStatus[$holdingKey] = $holding;
		}

		ksort($itemStatus);
		SierraStatusLoader::$loadedStatus[$id] = $itemStatus;
		return $itemStatus;
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
		global $configArray;
		$holdings = SierraStatusLoader::getStatus($id);
		$summaryInformation = array();
		$summaryInformation['recordId'] = $id;
		$summaryInformation['shortId'] = substr($id, 1);
		$summaryInformation['isDownloadable'] = false; //Default value, reset later if needed.

		if ($configArray['Catalog']['offline']){
			$summaryInformation['offline'] = true;
			$summaryInformation['status'] = 'The circulation system is offline, status not available.';
			$summaryInformation['holdable'] = true;
			$summaryInformation['class'] = "unavailable";
			$summaryInformation['showPlaceHold'] = true;
			return $summaryInformation;
		}

		global $library;
		/** Location $locationSingleton */
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		$canShowHoldButton = true;
		if ($library){
			if ($forSearch){
				$canShowHoldButton = ($library->showHoldButtonInSearchResults != 0);
			}else{
				$canShowHoldButton = ($library->showHoldButton != 0);
			}
		}
		if ($location){
			if ($forSearch){
				if ($library){
					$canShowHoldButton = ($library->showHoldButtonInSearchResults != 0);
				}else{
					$canShowHoldButton = ($location->showHoldButton != 0);
				}
			}else{
				$canShowHoldButton = ($location->showHoldButton != 0);
			}
		}
		$physicalLocation = $locationSingleton->getPhysicalLocation();

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
							'callnumber' => isset($issueSummary['cALL']) ? $issueSummary['cALL'] : '',
							'showPlaceHold' => $canShowHoldButton,
						);
						$summaryInformation['status'] = 'Available';
						$summaryInformation['statusfull'] = 'Available';
						$summaryInformation['class'] = 'available';
					}
				}
			}
		}

		//Valid statuses are:
		//It's here
		//  - at the physical location and not checked out
		//  - also show the call number for the location
		//  - do not show place hold button
		//It's at *location*
		//  - at the user's home branch or preferred location and not checked out
		//  - also show the call number for the location
		//  - show place hold button
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
		$additionalAvailableLocations = array();
		$unavailableStatus = null;
		$holdQueueLength = 0;
		//The status of all items.  Will be set to an actual status if all are the same
		//or null if the item statuses are inconsistent
		$allItemStatus = '';
		$firstCallNumber = null;
		$firstLocation = null;
		foreach ($holdings as $holdingKey => $holding){
			if (is_null($allItemStatus)){
				//Do nothing, the status is not distinct
			}else if ($allItemStatus == '' && isset($holding['statusfull'])){
				$allItemStatus = $holding['statusfull'];
			}elseif(isset($holding['statusfull']) && $allItemStatus != $holding['statusfull']){
				$allItemStatus = null;
			}
			if (isset($holding['holdQueueLength'])){
				$holdQueueLength = $holding['holdQueueLength'];
			}
			if (isset($holding['availability']) && $holding['availability'] == 1){
				$numAvailableCopies++;
				$addToAvailableLocation = false;
				$addToAdditionalAvailableLocation = false;
				//Check to see if the location should be listed in the list of locations that the title is available at.
				//Can only be in this system if there is a system active.
				if (sizeof($availableLocations) < 3 && !in_array($holding['libraryDisplayName'], $availableLocations)){
					if (isset($library)){
						//Check to see if the location is within this library system. It is if the key is less than or equal to 5
						if (substr($holdingKey, 0, 1) <= 5){
							$addToAvailableLocation = true;
						}
					}else{
						$addToAvailableLocation = true;
					}
				}
				//Check to see if the location is listed in the count of additional locations (can be any system).
				if (!$addToAvailableLocation && !in_array($holding['libraryDisplayName'], $availableLocations) && !in_array($holding['libraryDisplayName'], $additionalAvailableLocations)){
					$addToAdditionalAvailableLocation = true;
				}
				if ($addToAvailableLocation){
					$availableLocations[] = $holding['libraryDisplayName'];
				}elseif ($addToAdditionalAvailableLocation){
					$additionalAvailableLocations[] = $holding['libraryDisplayName'];
				}
			}else{
				if ($unavailableStatus == null && isset($holding['status'])){
					$unavailableStatus = $holding['status'];
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
			$showItsHere = ($library == null) ? true : ($library->showItsHere == 1);
			if (in_array(substr($holdingKey, 0, 1), array('1', '2', '3', '4', '5')) && !isset($summaryInformation['callnumber'])){
				//Try to get an available non reserver call number
				if ($holding['availability'] == 1 && $holding['holdable'] == 1){
					//echo("Including call number " . $holding['callnumber'] . " because is  holdable");
					$summaryInformation['callnumber'] = $holding['callnumber'];
				}else if (is_null($firstCallNumber)){
					//echo("Skipping call number " . $holding['callnumber'] . " because it is holdable");
					$firstCallNumber = $holding['callnumber'];
				}else if (is_null($firstLocation)){
					//echo("Skipping call number " . $holding['callnumber'] . " because it is holdable");
					$firstLocation = $holding['location'];
				}
			}
			if ($showItsHere && substr($holdingKey, 0, 1) == '1' && $holding['availability'] == 1){
				//The item is available within the physical library.  Patron should go get it off the shelf
				$summaryInformation['status'] = "It's here";
				$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				$summaryInformation['class'] = 'here';
				$summaryInformation['location'] = $holding['location'];
			}elseif ($showItsHere && !isset($summaryInformation['status']) &&
				substr($holdingKey, 0, 1) >= 2 && (substr($holdingKey, 0, 1) <= 4) &&
				$holding['availability'] == 1 ){
				if (!isset($summaryInformation['class']) || $summaryInformation['class'] != 'here'){
					//The item is at one of the patron's preferred branches.
					$summaryInformation['status'] = "It's at " . $holding['location'];
					$summaryInformation['showPlaceHold'] = $canShowHoldButton;
					$summaryInformation['class'] = 'nearby';
					$summaryInformation['location'] = $holding['location'];
				}
			}elseif (!isset($summaryInformation['status']) &&
				((!$showItsHere && substr($holdingKey, 0, 1) <= 5) || substr($holdingKey, 0, 1) == 5 || !isset($library) ) &&
				(isset($holding['availability']) && $holding['availability'] == 1)){
				if (!isset($summaryInformation['class']) || ($summaryInformation['class'] != 'here' && $summaryInformation['class'] = 'nearby')){
					//The item is at a location either in the same system or another system.
					$summaryInformation['status'] = "Available At";
					$summaryInformation['showPlaceHold'] = $canShowHoldButton;
					if ($physicalLocation != null){
						$summaryInformation['class'] = 'availableOther';
					}else{
						$summaryInformation['class'] = 'available';
					}
				}
			}elseif (!isset($summaryInformation['status']) &&
				(substr($holdingKey, 0, 1) == 6 ) &&
				(isset($holding['availability']) && $holding['availability'] == 1)){
				//The item is at a location either in the same system or another system.
				$summaryInformation['status'] = "Marmot";
				$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				$summaryInformation['class'] = 'available';
			}elseif (substr($holdingKey, 0, 1) == 7){
				$numCopiesOnOrder++;
				$numCopies--; //Don't increment number of copies for titles we don't have yet.
			}
		}

		//If all items are checked out the status will still be blank
		$summaryInformation['availableCopies'] = $numAvailableCopies;
		$summaryInformation['holdableCopies'] = $numHoldableCopies;
		if ($numHoldableCopies == 0){
			$summaryInformation['showPlaceHold'] = false;
		}

		$summaryInformation['numCopiesOnOrder'] = $numCopiesOnOrder;
		//Do some basic sanity checking to make sure that we show the total copies
		//With at least as many copies as the number of copies on order.
		if ($numCopies < $numCopiesOnOrder){
			$summaryInformation['numCopies'] = $numCopiesOnOrder;
		}else{
			$summaryInformation['numCopies'] = $numCopies;
		}
		$summaryInformation['copies'] = "$numAvailableCopies of $numCopies are on shelf";
		if ($numCopiesOnOrder > 0){
			$summaryInformation['copies'] .= ", $numCopiesOnOrder on order";
		}

		$summaryInformation['holdQueueLength'] = $holdQueueLength;

		if ($unavailableStatus != 'ONLINE'){
			$summaryInformation['unavailableStatus'] = $unavailableStatus;
		}

		if (isset($summaryInformation['status']) && $summaryInformation['status'] != "It's here"){
			//Replace all spaces in the name of a location with no break spaces
			foreach ($availableLocations as $key => $location){
				$availableLocations[$key] = str_replace(' ', ' ', $location);
			}
			$summaryInformation['availableAt'] = join(', ', $availableLocations);
			if ($summaryInformation['status'] == 'Marmot'){
				$summaryInformation['numAvailableOther'] = count($additionalAvailableLocations) + count($availableLocations);
			}else{
				$summaryInformation['numAvailableOther'] = count($additionalAvailableLocations);
			}
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
				if ($numHoldableCopies == 0 && $canShowHoldButton){
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
			if ($summaryInformation['status'] != 'Marmot' && $summaryInformation['status'] != 'Available At' && $summaryInformation['class'] != 'here' && $summaryInformation['class'] != 'nearby'){
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

		//Clear unavailable status if it matches the status
		if (isset($summaryInformation['unavailableStatus']) && strcasecmp(trim($summaryInformation['unavailableStatus']), trim($summaryInformation['status'])) == 0){
			$summaryInformation['unavailableStatus'] = '';
		}

		//Reset call number as needed
		if (!is_null($firstCallNumber) && !isset($summaryInformation['callnumber'])){
			$summaryInformation['callnumber'] = $firstCallNumber;
		}
		//Reset location as needed
		if (!is_null($firstLocation) && !isset($summaryInformation['location'])){
			$summaryInformation['location'] = $firstLocation;
		}

		//Set Status text for the summary
		if ($summaryInformation['status'] == 'Available At'){
			if ($summaryInformation['numCopies'] == 0){
				$summaryInformation['statusText'] = "No Copies Found";
			}else{
				if (strlen($summaryInformation['availableAt']) > 0){
					$summaryInformation['statusText'] = "Available now" . ($summaryInformation['inLibraryUseOnly'] ? "for in library use" : "") . " at " . $summaryInformation['availableAt'] . ($summaryInformation['numAvailableOther'] > 0 ? (", and {$summaryInformation['numAvailableOther']} other location" . ($summaryInformation['numAvailableOther'] > 1 ? "s" : "")) : "");
				}else{
					$summaryInformation['statusText'] = "Available now" . ($summaryInformation['inLibraryUseOnly'] ? "for in library use" : "");
				}
			}
		}else if ($summaryInformation['status'] == 'Marmot'){
			$summaryInformation['class'] = "nearby";
			$totalLocations = intval($summaryInformation['numAvailableOther']) + intval($summaryInformation['availableAt']);
			$summaryInformation['statusText'] = "Available now at " . $totalLocations . " Marmot " . ($totalLocations == 1 ? "Library" : "Libraries");
		}else{
			$summaryInformation['statusText'] = translate($summaryInformation['status']);
		}

		return $summaryInformation;
	}
}
