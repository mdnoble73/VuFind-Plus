<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
require_once 'Drivers/marmot_inc/LocationHours.php';

class Location extends DB_DataObject
{
	public $__table = 'location';   // table name
	public $locationId;				//int(11)
	public $code;					//varchar(5)
	public $displayName;			//varchar(40)
	public $libraryId;				//int(11)
	public $validHoldPickupBranch;	//tinyint(4)
	public $nearbyLocation1;		//int(11)
	public $nearbyLocation2;		//int(11)
	public $holdingBranchLabel;     //varchar(40)
	public $scope;
	public $useScope;
	public $facetLabel;
	public $defaultLocationFacet;
	public $facetFile;
	public $showHoldButton;
	public $showAmazonReviews;
	public $showStandardReviews;
	public $repeatSearchOption;
	public $repeatInProspector;
	public $repeatInWorldCat;
	public $repeatInOverdrive;
	public $systemsToRepeatIn;
	public $homeLink;
	public $defaultPType;
	public $ptypesToAllowRenewals;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Location',$k,$v); }

	function keys() {
		return array('locationId', 'code');
	}

	function getObjectStructure(){
		//Load Libraries for lookup values
		$library = new Library();
		$library->orderBy('displayName');
		$library->find();
		$libraryList = array();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		//Look lookup information for display in the user interface
		$location = new Location();
		$location->orderBy('displayName');
		$location->find();
		$locationList = array();
		$locationLookupList = array();
		$locationLookupList[-1] = '<No Nearby Location>';
		while ($location->fetch()){
			$locationLookupList[$location->locationId] = $location->displayName;
			$locationList[$location->locationId] = clone $location;
		}

		// get the structure for the location's hours
		$hoursStructure = LocationHours::getObjectStructure();
		
		// we don't want to make the locationId property editable
		// because it is associated with this location only
		unset($hoursStructure['locationId']);
		
		$structure = array(
		array('property'=>'code', 'type'=>'text', 'label'=>'Code', 'description'=>'The code for use when communicating with Millennium'),
		array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'The full name of the location for display to the user'),
		array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'A link to the library which the location belongs to'),
		array('property'=>'defaultPType', 'type'=>'text', 'label'=>'Default P-Type', 'description'=>'The P-Type to use when accessing a subdomain if the patron is not logged in.  Use -1 to use the library default PType.'),
		array('property'=>'validHoldPickupBranch', 'type'=>'checkbox', 'label'=>'Valid Hold Pickup Branch?', 'description'=>'Determines if the location can be used as a pickup location if it is not the patrons home location or the location they are in.'),
		array('property'=>'nearbyLocation1', 'type'=>'enum', 'values'=>$locationLookupList, 'label'=>'Nearby Location 1', 'description'=>'A secondary location which is nearby and could be used for pickup of materials.'),
		array('property'=>'nearbyLocation2', 'type'=>'enum', 'values'=>$locationLookupList, 'label'=>'Nearby Location 2', 'description'=>'A tertiary location which is nearby and could be used for pickup of materials.'),
		array('property'=>'holdingBranchLabel', 'type'=>'text', 'label'=>'Holding Branch Label', 'description'=>'The label used within the holdings table in Millennium'),
		array('property'=>'scope', 'type'=>'text', 'label'=>'Scope', 'description'=>'The scope for the system in Millennium to refine holdings to the branch.  If there is no scope defined for the branch, this can be set to 0.'),
		array('property'=>'useScope', 'type'=>'checkbox', 'label'=>'Use Scope?', 'description'=>'Whether or not the scope should be used when displaying holdings.'),
		array('property'=>'facetLabel', 'type'=>'text', 'label'=>'Facet Label', 'description'=>'The label of the facet that identifies this location.'),
		array('property'=>'defaultLocationFacet', 'type'=>'text', 'label'=>'Default Location Facet', 'description'=>'A facet to apply during initial searches.  If left blank, no additional refinement will be done.'),
		array('property'=>'facetFile', 'type'=>'text', 'label'=>'Facet File', 'description'=>'The name of the facet file which should be used while searching'),
		array('property'=>'showHoldButton', 'type'=>'checkbox', 'label'=>'Show Hold Button', 'description'=>'Whether or not the hold button is displayed so patrons can place holds on items'),
		array('property'=>'showAmazonReviews', 'type'=>'checkbox', 'label'=>'Show Amazon Reviews', 'description'=>'Whether or not reviews from Amazon are displayed on the full record page.'),
		array('property'=>'showStandardReviews', 'type'=>'checkbox', 'label'=>'Show Standard Reviews', 'description'=>'Whether or not reviews from Content Cafe/Syndetics are displayed on the full record page.'),
		array('property'=>'repeatSearchOption', 'type'=>'enum', 'values'=>array('none'=>'None', 'librarySystem'=>'Library System','marmot'=>'Marmot'), 'label'=>'Repeat Search Options', 'description'=>'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all'),
		array('property'=>'repeatInProspector', 'type'=>'checkbox', 'label'=>'Repeat In Prospector', 'description'=>'Turn on to allow repeat search in Prospector functionality.'),
		array('property'=>'repeatInWorldCat', 'type'=>'checkbox', 'label'=>'Repeat In WorldCat', 'description'=>'Turn on to allow repeat search in WorldCat functionality.'),
		array('property'=>'repeatInOverdrive', 'type'=>'checkbox', 'label'=>'Repeat In Overdrive', 'description'=>'Turn on to allow repeat search in Overdrive functionality.'),
		array('property'=>'systemsToRepeatIn', 'type'=>'text', 'label'=>'Systems To Repeat In', 'description'=>'A list of library codes that you would like to repeat search in separated by pipes |.'),
		array('property'=>'homeLink', 'type'=>'text', 'label'=>'Home Link', 'description'=>'The location to send the user when they click on the home button or logo.  Use default or blank to go back to the vufind home location.'),
		array('property'=>'ptypesToAllowRenewals', 'type'=>'text', 'label'=>'PTypes that can renew', 'description'=>'A list of P-Types that can renew items or * to allow all P-Types to renew items.'),
		array(
				'property' => 'hours',
				'type'=> 'oneToMany',
				'keyThis' => 'locationId',
				'keyOther' => 'locationId',
				'subObjectType' => 'LocationHours',
				'structure' => $hoursStructure,
				'label' => 'Hours',
				'description' => 'Library Hours',
				'hideInLists' => true,
				'sortable' => false,
				'storeDb' => true
			),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}

	function getPickupBranches($patronProfile, $selectedBranchId) {
		//Get the library for the patron's home branch.
		global $librarySingleton;
		if ($patronProfile){
			if (is_object($patronProfile)){
				$patronProfile = get_object_vars($patronProfile);
			}
		}
		$homeLibrary = $librarySingleton->getLibraryForLocation($patronProfile['homeLocationId']);
		

		if (isset($homeLibrary) && $homeLibrary->inSystemPickupsOnly == 1){
			if (strlen($homeLibrary->validPickupSystems) > 0){
				$pickupIds = array();
				$pickupIds[] = $homeLibrary->libraryId;
				$validPickupSystems = split('\|', $homeLibrary->validPickupSystems);
				foreach ($validPickupSystems as $pickupSystem){
					$pickupLocation = new Library();
					$pickupLocation->subdomain = $pickupSystem;
					$pickupLocation->find();
					if ($pickupLocation->N == 1){
						$pickupLocation->fetch();
						$pickupIds[] = $pickupLocation->libraryId;
					}
				}
				$this->whereAdd("libraryId IN (" . implode(',', $pickupIds) . ")", 'AND');
				//Deal with Steamboat Springs Juvenile which is a special case.
				$this->whereAdd("code <> 'ssjuv'", 'AND');
			}else{
				$this->whereAdd("libraryId = {$homeLibrary->libraryId}", 'AND');
				$this->whereAdd("validHoldPickupBranch = 1", 'AND');
				$this->whereAdd("locationId = {$patronProfile['homeLocationId']}", 'OR');
			}
		}else{
			$this->whereAdd("validHoldPickupBranch = 1");
		}

		if (isset($selectedBranchId) && is_numeric($selectedBranchId)){
			$this->whereAdd("locationId = $selectedBranchId", 'OR');
		}
		$this->orderBy('displayName');

		$this->find();

		//Load the locations and sort them based on the user profile information as well as their physical location.
		$physicalLocation = $this->getPhysicalLocation();
		$locationList = array();
		while ($this->fetch()) {
			if ($this->locationId == $selectedBranchId){
				$selected = 'selected';
			}else{
				$selected = '';
			}
			$this->selected = $selected;
			if (isset($physicalLocation) && $physicalLocation->locationId == $this->locationId){
				//If the user is in a branch, those holdings come first.
				$locationList['1' . $this->displayName] = clone $this;
			} else if ($this->locationId == $patronProfile['homeLocationId']){
				//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
				$locationList['2' . $this->displayName] = clone $this;
			} else if (isset($patronProfile['myLocation1Id']) && $this->locationId == $patronProfile['myLocation1Id']){
				//Next come nearby locations for the user
				$locationList['3' . $this->displayName] = clone $this;
			} else if (isset($patronProfile['myLocation2Id']) && $this->locationId == $patronProfile['myLocation2Id']){
				//Next come nearby locations for the user
				$locationList['4' . $this->displayName] = clone $this;
			} else if (isset($homeLibrary) && $this->libraryId == $homeLibrary->libraryId){
				//Other locations that are within the same library system
				$locationList['5' . $this->displayName] = clone $this;
			} else {
				//Finally, all other locations are shown sorted alphabetically.
				$locationList['6' . $this->displayName] = clone $this;
			}
		}
		ksort($locationList);
		return $locationList;
	}

	/**
	 * Returns the active location to use when doing search scoping, etc.
	 * This does not include the IP address
	 *
	 * @return Location
	 */
	private $activeLocation = 'unset';
	function getActiveLocation(){
		if (isset($this->activeLocation) && $this->activeLocation != 'unset') return $this->activeLocation;

		//default value
		$this->activeLocation = null;

		//load information about the library we are in.
		global $library;
		if (is_null($library)){
			//If we are not in a library, then do not allow branch scoping, etc.
			$this->activeLocation == null;
		}else{
			//Check to see if a branch location has been specified.
			$locationCode = $this->getBranchLocationCode();

			if ($locationCode != null && $locationCode != '' && $locationCode != 'all'){
				$activeLocation = $this->staticGet('code', $locationCode);
				//Only use the location if we are in the subdomain for the parent library
				if ($activeLocation != null && $library->libraryId == $activeLocation->libraryId){
					$this->activeLocation = clone($activeLocation);
				}
			}
			global $timer;
			$timer->logTime('Finished getActiveLocation'); 
		}

		return $this->activeLocation;
	}
	function setActiveLocation($location){
		$this->activeLocation = $location;
	}

	private $userHomeLocation = 'unset';
	function getUserHomeLocation(){
		if (isset($this->userHomeLocation) && $this->userHomeLocation != 'unset') return $this->userHomeLocation;

		//default value
		$this->userHomeLocation = null;

		global $user;
		global $library;
		if (isset($user) && $user != false){
			$homeLocation = new Location();
			$homeLocation->locationId = $user->homeLocationId;
			if ($homeLocation->find(true)){
				$this->userHomeLocation = clone($homeLocation);
			}
		}

		return $this->userHomeLocation;
	}


	private $branchLocationCode = 'unset';
	function getBranchLocationCode(){
		if (isset($this->branchLocationCode) && $this->branchLocationCode != 'unset') return $this->branchLocationCode;
		if (isset($_GET['branch'])){
			$this->branchLocationCode = $_GET['branch'];
		}elseif (isset($_COOKIE['branch'])){
			$this->branchLocationCode = $_COOKIE['branch'];
		}else{
			$this->branchLocationCode = '';
		}
		if ($this->branchLocationCode == 'all'){
			$this->branchLocationCode = '';
		}
		return $this->branchLocationCode;
	}

	/**
	 * The physical location where the user is based on
	 * IP address and branch parameter, and only for It's Here messages
	 *
	 */
	private $physicalLocation = 'unset';
	function getPhysicalLocation(){
		if (isset($this->physicalLocation) && $this->physicalLocation != 'unset'){
			if ($this->physicalLocation == 'null'){
				return null;
			}else{
				return $this->physicalLocation;
			}
		}
		$this->physicalLocation = 'null';
		//The branch parameter trumps IP Address if set.
		if ($this->getBranchLocationCode() != ''){
			$this->physicalLocation = $this->getActiveLocation();
		}else{
			$this->physicalLocation = $this->getIPLocation();
		}
		return $this->physicalLocation;
	}

	static function getSearchLocation(){
		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		if ($searchSource == 'local'){
			global $locationSingleton;
			return $locationSingleton->getActiveLocation();
		}else if ($searchSource == 'marmot'){
			return null;
		}else{
			$location = new Location();
			$location->code = $searchSource;
			$location->find();
			if ($location->N > 0){
				$location->fetch();
				return clone($location);
			}
			return null;
		}
	}

	/**
	 * The location we are in based solely on IP address.
	 * @var unknown_type
	 */
	private $ipLocation = 'unset';
	private $ipId = 'unset';
	function getIPLocation(){
		global $timer;
		global $memcache;
		global $configArray;
		$timer->logTime('Starting getIPLocation');
		//Check the current IP address to see if we are in a branch
		$activeIp = $this->getActiveIp();
		$this->ipLocation = $memcache->get('location_for_ip_' . $activeIp);
		$this->ipId = $memcache->get('ipId_for_ip_' . $activeIp);
		
		if (!isset($this->ipLocation) || $this->ipLocation === false || $this->ipId === false){
			//echo("Active IP is $activeIp");
			require_once './Drivers/marmot_inc/ipcalc.php';
			require_once './Drivers/marmot_inc/subnet.php';
			
			$subnetSql = new subnet();
			$subnetSql->find();
			$subnets = array();
			while ($subnetSql->fetch()) {
				$subnets[] = clone $subnetSql;
			}
			$bestmatch=FindBestMatch($activeIp,$subnets);
			//Get the locationId for the subnet.
			if (isset($bestmatch) && $bestmatch != null){
				//echo("Best match Location is {$bestmatch->locationid}");
				 
				$matchedLocation = $this->staticGet('locationId', $bestmatch->locationid);
				//Only use the physical location regardless of where we are
				$this->ipLocation = clone($matchedLocation);
				$this->ipId = $bestmatch->id;
			} else {
				//Clear the cookie if we don't get a match.
				$this->activeIp = '';
				$this->ipLocation = null;
				$this->ipId = -1;
			}
			$memcache->set('ipId_for_ip_' . $activeIp, $this->ipId, 0, $configArray['Caching']['ipId_for_ip']);
			$memcache->set('location_for_ip_' . $activeIp, $this->ipLocation, 0, $configArray['Caching']['location_for_ip']);
		}
		$timer->logTime('Finished getIPLocation');
		return $this->ipLocation;
	}

	/**
	 * Must be called after the call to getIPLocation
	 * Enter description here ...
	 */
	function getIPid(){
		return $this->ipId;
	}

	private $activeIp;
	function getActiveIp(){
		if (isset($this->activeIp)) return $this->activeIp;
		//Make sure gets and cookies are processed in the correct order.
		if (isset($_GET['test_ip'])){
			$ip = $_GET['test_ip'];
			//Set a coookie so we don't have to transfer the ip from page to page.
			setcookie('test_ip', $ip, 0, '/');
		}elseif (isset($_COOKIE['test_ip'])){
			$ip = $_COOKIE['test_ip'];
		}else{
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		$this->activeIp = $ip;
		return $this->activeIp;
	}

	function getLocationsFacetsForLibrary($libraryId){
		$location = new Location();
		$location->libraryId = $libraryId;
		$location->find();
		$facets = array();
		if ($location->N > 0){
			while ($location->fetch()){
				$facets[] = $location->facetLabel;
			}
		}
		return $facets;
	}
	
	public function __get($name){
		if ($name == "hours") {
			if (!isset($this->hours)){
				$this->hours = array();
				$hours = new LocationHours();
				$hours->locationId = $this->locationId;
				$hours->orderBy('day');
				$hours->find();
				while($hours->fetch()){
					$this->hours[$hours->id] = clone($hours);
				}
			}
			return $this->hours;
		}
	}
	
	public function __set($name, $value){
		if ($name == "hours") {
			$this->hours = $value;
		}
	}

	/**
	 * Override the update functionality to save the hours
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(){
		$ret = parent::update();
		if ($ret === FALSE ){
			return $ret;
		}else{
			$this->saveHours();
		}
	}
	
	/**
	 * Override the update functionality to save the hours
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		$ret = parent::insert();
		if ($ret === FALSE ){
			return $ret;
		}else{
			$this->saveHours();
		}
	}
	
	public function saveHours(){
		if (isset ($this->hours) && is_array($this->hours)){
			foreach ($this->hours as $hours){
				if (isset($hours->deleteOnSave) && $hours->deleteOnSave == true){
					$hours->delete();
				}else{
					if (isset($hours->id) && is_numeric($hours->id)){
						$hours->update();
					}else{
						$hours->locationId = $this->locationId;
						$hours->insert();
					}
				}
			}
			unset($this->hours);
		}
	}
	
	public static function getLibraryHours($locationId, $timeToCheck){
		$location = new Location();
		$location->locationId = $locationId;
		if ($location->find(true)){
			// format $timeToCheck according to MySQL default date format
			$todayFormatted = date('Y-m-d', $timeToCheck);
			
			// check to see if today is a holiday
			require_once 'Drivers/marmot_inc/Holiday.php';
			$holidays = array();
			$holiday = new Holiday();
			$holiday->date = $todayFormatted;
			$holiday->libraryId = $location->libraryId;
			if ($holiday->find(true)){
				return array(
					'closed' => true,
					'closureReason' => $holiday->name
				);
			}
			
			// get the day of the week (0=Sunday to 6=Saturday)
			$dayOfWeekToday = strftime ('%w', $timeToCheck);
	
			// find library hours for the above day of the week
			require_once 'Drivers/marmot_inc/LocationHours.php';
			$hours = new LocationHours();
			$hours->locationId = $locationId;
			$hours->day = $dayOfWeekToday;
			if ($hours->find(true)){
				$hours->fetch();
				return array(
					'open' => ltrim($hours->open, '0'),
					'close' => ltrim($hours->close, '0'),
					'closed' => $hours->closed ? true : false,
					'openFormatted' => ($hours->open == '12:00' ? 'Noon' : date("g:i A", strtotime($hours->open))),
					'closeFormatted' => ($hours->open == '12:00' ? 'Noon' : date("g:i A", strtotime($hours->close)))
				);
			}
		}
		

		// no hours found
		return null;
	}
	
	public static function getLibraryHoursMessage($locationId){
		$today = time();
		$todaysLibraryHours = Location::getLibraryHours($locationId, $today);
		if (isset($todaysLibraryHours) && is_array($todaysLibraryHours)){
			if (isset($todaysLibraryHours['closed']) && ($todaysLibraryHours['closed'] == true || $todaysLibraryHours['closed'] == 1)){
				if (isset($todaysLibraryHours['closureReason'])){
					$closureReason = $todaysLibraryHours['closureReason'];
				}
				//Library is closed now
				$nextDay = time() + (24 * 60 * 60);
				$nextDayHours = Location::getLibraryHours($locationId,  $nextDay);
				while (isset($nextDayHours['closed']) && $nextDayHours['closed'] == true){
					$nextDay += (24 * 60 * 60);
					$nextDayHours = Location::getLibraryHours($locationId,  $nextDay);
				}
	
				$nextDayOfWeek = strftime ('%a', $nextDay);
				if (isset($closureReason)){
					$libraryHoursMessage = "The library is closed today for $closureReason. It will reopen on $nextDayOfWeek from {$nextDayHours['openFormatted']} to {$nextDayHours['closeFormatted']}";
				}else{
					$libraryHoursMessage = "The library is closed today. It will reopen on $nextDayOfWeek from {$nextDayHours['openFormatted']} to {$nextDayHours['closeFormatted']}";
				}
			}else{
				//Library is open
				$currentHour = strftime ('%H', $today);
				$openHour = strftime ('%H', strtotime($todaysLibraryHours['open']));
				$closeHour = strftime ('%H', strtotime($todaysLibraryHours['close']));
				if ($currentHour < $openHour){
					$libraryHoursMessage = "The library will be open today from " . $todaysLibraryHours['openFormatted'] . " to " . $todaysLibraryHours['closeFormatted'] . ".";
				}else if ($currentHour > $closeHour){
					$tomorrowsLibraryHours = Location::getLibraryHours($locationId,  time() + (24 * 60 * 60));
					$libraryHoursMessage = "The library will be open tomorrow from " . $tomorrowsLibraryHours['openFormatted'] . " to " . $tomorrowsLibraryHours['closeFormatted'] . ".";
				}else{
					$libraryHoursMessage = "The library is open today from " . $todaysLibraryHours['openFormatted'] . " to " . $todaysLibraryHours['closeFormatted'] . ".";
				}
			}
		}else{
			$libraryHoursMessage = null;
		}
		return $libraryHoursMessage;
	}
}