<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LocationHours.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LocationFacetSetting.php';
require_once ROOT_DIR . '/sys/Browse/LocationBrowseCategory.php';
require_once ROOT_DIR . '/sys/Browse/LocationBrowseCategory.php';

class Location extends DB_DataObject
{
	public $__table = 'location';   // table name
	public $locationId;				//int(11)
	public $code;					//varchar(5)
	public $displayName;			//varchar(40)
	public $showDisplayNameInHeader;
	public $libraryId;				//int(11)
	public $address;
	public $phone;
	public $showInLocationsAndHoursList;
	public $extraLocationCodesToInclude;
	public $validHoldPickupBranch;	//tinyint(4)
	public $nearbyLocation1;		//int(11)
	public $nearbyLocation2;		//int(11)
	public $holdingBranchLabel;     //varchar(40)
	public $scope;
	public $useScope;
	public $facetLabel;
	public $restrictSearchByLocation;
	public $includeDigitalCollection;
	public $showHoldButton;
	public $showAmazonReviews;
	public $showStandardReviews;
	public $repeatSearchOption;
	public $repeatInOnlineCollection;
	public $repeatInProspector;
	public $repeatInWorldCat;
	public $repeatInOverdrive;
	public $systemsToRepeatIn;
	public $homeLink;
	public $defaultPType;
	public $ptypesToAllowRenewals;
	public $footerTemplate;
	public $homePageWidgetId;
	public $boostByLocation;
	public $additionalLocalBoostFactor;
	public $recordsToBlackList;
	public $automaticTimeoutLength;
	public $automaticTimeoutLengthLoggedOut;
	public $suppressHoldings;
	public $additionalCss;
	public $showTextThis;
	public $showEmailThis;
	public $showShareOnExternalSites;
	public $showFavorites;
	public $showComments;
	public $showQRCode;
	public $showStaffView;
	public $showGoodReadsReviews;
	public $econtentLocationsToInclude;

	/** @var  array $data */
	protected $data;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Location',$k,$v); }

	function keys() {
		return array('locationId', 'code');
	}

	function getObjectStructure(){
		global $user;
		//Load Libraries for lookup values
		$library = new Library();
		$library->orderBy('displayName');
		if ($user->hasRole('libraryAdmin')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
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

		$facetSettingStructure = LocationFacetSetting::getObjectStructure();
		unset($facetSettingStructure['weight']);
		unset($facetSettingStructure['locationId']);
		unset($facetSettingStructure['numEntriesToShowByDefault']);
		unset($facetSettingStructure['showAsDropDown']);
		//unset($facetSettingStructure['sortMode']);

		$locationBrowseCategoryStructure = LocationBrowseCategory::getObjectStructure();
		unset($locationBrowseCategoryStructure['weight']);
		unset($locationBrowseCategoryStructure['locationId']);

		$structure = array(
			array('property'=>'code', 'type'=>'text', 'label'=>'Code', 'description'=>'The code for use when communicating with Millennium'),
			array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'The full name of the location for display to the user', 'size'=>'40'),
			array('property'=>'showDisplayNameInHeader', 'type'=>'checkbox', 'label'=>'Show Display Name in Header', 'description'=>'Whether or not the display name should be shown in the header next to the logo', 'hideInLists' => true, 'default'=>false),
			array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'A link to the library which the location belongs to'),
			array('property'=>'showInLocationsAndHoursList', 'type'=>'checkbox', 'label'=>'Show In Locations And Hours List', 'description'=>'Whether or not this location should be shown in the list of library hours and locations', 'hideInLists' => true, 'default'=>true),
			array('property'=>'address', 'type'=>'textarea', 'label'=>'Address', 'description'=>'The address of the branch.', 'hideInLists' => true),
			array('property'=>'phone', 'type'=>'text', 'label'=> 'Phone Number', 'description'=>'The main phone number for the site .', 'size' => '40', 'hideInLists' => true),
			array('property'=>'extraLocationCodesToInclude', 'type'=>'text', 'label'=> 'Extra Locations To Include', 'description'=>'A list of other location codes to include in this location for indexing special collections, juvenile collections, etc.', 'size' => '40', 'hideInLists' => true),
			array('property'=>'nearbyLocation1', 'type'=>'enum', 'values'=>$locationLookupList, 'label'=>'Nearby Location 1', 'description'=>'A secondary location which is nearby and could be used for pickup of materials.', 'hideInLists' => true),
			array('property'=>'nearbyLocation2', 'type'=>'enum', 'values'=>$locationLookupList, 'label'=>'Nearby Location 2', 'description'=>'A tertiary location which is nearby and could be used for pickup of materials.', 'hideInLists' => true),
			array('property'=>'automaticTimeoutLength', 'type'=>'integer', 'label'=>'Automatic Timeout Length (logged in)', 'description'=>'The length of time before the user is automatically logged out in seconds.', 'size'=>'8', 'hideInLists' => true, 'default'=>90),
			array('property'=>'automaticTimeoutLengthLoggedOut', 'type'=>'integer', 'label'=>'Automatic Timeout Length (logged out)', 'description'=>'The length of time before the catalog resets to the home page set to 0 to disable.', 'size'=>'8', 'hideInLists' => true,'default'=>450),

			array('property'=>'displaySection', 'type' => 'section', 'label' =>'Basic Display', 'hideInLists' => true, 'properties' => array(
				array('property'=>'homeLink', 'type'=>'text', 'label'=>'Home Link', 'description'=>'The location to send the user when they click on the home button or logo.  Use default or blank to go back to the vufind home location.', 'hideInLists' => true, 'size'=>'40'),
				array('property'=>'additionalCss', 'type'=>'textarea', 'label'=>'Additional CSS', 'description'=>'Extra CSS to apply to the site.  Will apply to all pages.', 'hideInLists' => true),
				array('property'=>'homePageWidgetId', 'type'=>'text', 'label'=>'Home Page Widget Id', 'description'=>'An id for the list widget to display on the home page.  To show more than one widget, separate the ids with commas.', 'hideInLists' => true),
				array('property'=>'footerTemplate', 'type'=>'text', 'label'=>'Footer Template', 'description'=>'The name of the footer file to display in the regular interface when scoped to a single school.  Use default to display the default footer', 'hideInLists' => true, 'default' => 'default'),
			)),

			array('property'=>'ilsSection', 'type' => 'section', 'label' =>'ILS/Account Integration', 'hideInLists' => true, 'properties' => array(
				array('property'=>'holdingBranchLabel', 'type'=>'text', 'label'=>'Holding Branch Label', 'description'=>'The label used within the holdings table in Millennium'),
				array('property'=>'scope', 'type'=>'text', 'label'=>'Scope', 'description'=>'The scope for the system in Millennium to refine holdings to the branch.  If there is no scope defined for the branch, this can be set to 0.'),
				array('property'=>'useScope', 'type'=>'checkbox', 'label'=>'Use Scope?', 'description'=>'Whether or not the scope should be used when displaying holdings.', 'hideInLists' => true),
				array('property'=>'defaultPType', 'type'=>'text', 'label'=>'Default P-Type', 'description'=>'The P-Type to use when accessing a subdomain if the patron is not logged in.  Use -1 to use the library default PType.', 'default'=>-1),
				array('property'=>'validHoldPickupBranch', 'type'=>'checkbox', 'label'=>'Valid Hold Pickup Branch?', 'description'=>'Determines if the location can be used as a pickup location if it is not the patrons home location or the location they are in.', 'hideInLists' => true, 'default'=>true),
				array('property'=>'showHoldButton', 'type'=>'checkbox', 'label'=>'Show Hold Button', 'description'=>'Whether or not the hold button is displayed so patrons can place holds on items', 'hideInLists' => true, 'default'=>true),
				array('property'=>'ptypesToAllowRenewals', 'type'=>'text', 'label'=>'PTypes that can renew', 'description'=>'A list of P-Types that can renew items or * to allow all P-Types to renew items.', 'hideInLists' => true),
				array('property'=>'suppressHoldings','type'=>'checkbox', 'label'=>'Suppress Holdings', 'description'=>'Whether or not all items for the title should be suppressed', 'hideInLists' => true, 'default'=>false),
			)),

			array('property'=>'searchingSection', 'type' => 'section', 'label' =>'Searching', 'hideInLists' => true, 'properties' => array(
				array('property'=>'facetLabel', 'type'=>'text', 'label'=>'Facet Label', 'description'=>'The label of the facet that identifies this location.', 'hideInLists' => true, 'size'=>'40'),
				//array('property'=>'defaultLocationFacet', 'type'=>'text', 'label'=>'Default Location Facet', 'description'=>'A facet to apply during initial searches.  If left blank, no additional refinement will be done.', 'hideInLists' => true, 'size'=>'40'),
				array('property'=>'restrictSearchByLocation', 'type'=>'checkbox', 'label'=>'Restrict Search By Location', 'description'=>'Whether or not search results should only include titles from this location', 'hideInLists' => true, 'default'=>false),
				array('property'=>'includeDigitalCollection', 'type'=>'checkbox', 'label'=>'Include Digital Collection', 'description'=>'Whether or not titles from the digital collection should be included in searches', 'hideInLists' => true, 'default'=>true),
				array('property'=>'econtentLocationsToInclude', 'type'=>'text', 'label'=>'eContent Locations To Include', 'description'=>'A list of eContent Locations to include within the scope.', 'size'=>'40', 'hideInLists' => true,),
				array('property'=>'boostByLocation', 'type'=>'checkbox', 'label'=>'Boost By Location', 'description'=>'Whether or not boosting of titles owned by this location should be applied', 'hideInLists' => true, 'default'=>true),
				array('property'=>'additionalLocalBoostFactor', 'type'=>'integer', 'label'=>'Additional Local Boost Factor', 'description'=>'An additional numeric boost to apply to any locally owned and locally available titles', 'hideInLists' => true, 'default'=>1),
				array('property'=>'recordsToBlackList', 'type'=>'textarea', 'label'=>'Records to deaccession', 'description'=>'A list of records to deaccession (hide) in search results.  Enter one record per line.', 'hideInLists' => true,),
				array('property'=>'repeatSearchOption', 'type'=>'enum', 'values'=>array('none'=>'None', 'librarySystem'=>'Library System','marmot'=>'Marmot'), 'label'=>'Repeat Search Options', 'description'=>'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all', 'default'=>'marmot'),
				array('property'=>'repeatInOnlineCollection', 'type'=>'checkbox', 'label'=>'Repeat In Online Collection', 'description'=>'Turn on to allow repeat search in the Online Collection.', 'hideInLists' => true, 'default'=>false),
				array('property'=>'repeatInProspector', 'type'=>'checkbox', 'label'=>'Repeat In Prospector', 'description'=>'Turn on to allow repeat search in Prospector functionality.', 'hideInLists' => true, 'default'=>false),
				array('property'=>'repeatInWorldCat', 'type'=>'checkbox', 'label'=>'Repeat In WorldCat', 'description'=>'Turn on to allow repeat search in WorldCat functionality.', 'hideInLists' => true, 'default'=>false),
				array('property'=>'repeatInOverdrive', 'type'=>'checkbox', 'label'=>'Repeat In Overdrive', 'description'=>'Turn on to allow repeat search in Overdrive functionality.', 'hideInLists' => true, 'default'=>false),
				array('property'=>'systemsToRepeatIn', 'type'=>'text', 'label'=>'Systems To Repeat In', 'description'=>'A list of library codes that you would like to repeat search in separated by pipes |.', 'hideInLists' => true),
			)),

			array('property'=>'enrichmentSection', 'type' => 'section', 'label' =>'Catalog Enrichment', 'hideInLists' => true, 'properties' => array(
				array('property'=>'showAmazonReviews', 'type'=>'checkbox', 'label'=>'Show Amazon Reviews', 'description'=>'Whether or not reviews from Amazon are displayed on the full record page.', 'hideInLists' => true, 'default'=>false),
				array('property'=>'showStandardReviews', 'type'=>'checkbox', 'label'=>'Show Standard Reviews', 'description'=>'Whether or not reviews from Content Cafe/Syndetics are displayed on the full record page.', 'hideInLists' => true, 'default'=>true),
				array('property'=>'showGoodReadsReviews', 'type'=>'checkbox', 'label'=>'Show GoodReads Reviews', 'description'=>'Whether or not reviews from GoodReads are displayed on the full record page.', 'hideInLists' => true, 'default'=>true),
				'showFavorites'  => array('property'=>'showFavorites', 'type'=>'checkbox', 'label'=>'Show Favorites', 'description'=>'Whether or not users can maintain favorites lists', 'hideInLists' => true, 'default' => 1),
			)),

			array('property'=>'fullRecordSection', 'type' => 'section', 'label' =>'Full Record Display', 'hideInLists' => true, 'properties' => array(
				'showTextThis'  => array('property'=>'showTextThis', 'type'=>'checkbox', 'label'=>'Show Text This', 'description'=>'Whether or not the Text This link is shown', 'hideInLists' => true, 'default' => 1),
				'showEmailThis'  => array('property'=>'showEmailThis', 'type'=>'checkbox', 'label'=>'Show Email This', 'description'=>'Whether or not the Email This link is shown', 'hideInLists' => true, 'default' => 1),
				'showShareOnExternalSites'  => array('property'=>'showShareOnExternalSites', 'type'=>'checkbox', 'label'=>'Show Sharing To External Sites', 'description'=>'Whether or not sharing on external sites (Twitter, Facebook, Pinterest, etc. is shown)', 'hideInLists' => true, 'default' => 1),
				'showComments'  => array('property'=>'showComments', 'type'=>'checkbox', 'label'=>'Show Comments', 'description'=>'Whether or not user comments are shown (also disables adding comments)', 'hideInLists' => true, 'default' => 1),
				'showQRCode'  => array('property'=>'showQRCode', 'type'=>'checkbox', 'label'=>'Show QR Code', 'description'=>'Whether or not the catalog should show a QR Code in full record view', 'hideInLists' => true, 'default' => 1),
				array('property'=>'showStaffView', 'type'=>'checkbox', 'label'=>'Show Staff View', 'description'=>'Whether or not the staff view is displayed in full record view.', 'hideInLists' => true, 'default'=>true),
			)),

			array(
				'property' => 'hours',
				'type'=> 'oneToMany',
				'keyThis' => 'locationId',
				'keyOther' => 'locationId',
				'subObjectType' => 'LocationHours',
				'structure' => $hoursStructure,
				'label' => 'Hours',
				'description' => 'Library Hours',
				//'hideInLists' => true,
				'sortable' => false,
				'storeDb' => true
			),

			'facets' => array(
				'property'=>'facets',
				'type'=>'oneToMany',
				'label'=>'Facets',
				'description'=>'A list of facets to display in search results',
				'keyThis' => 'locationId',
				'keyOther' => 'locationId',
				'subObjectType' => 'LocationFacetSetting',
				'structure' => $facetSettingStructure,
				//'hideInLists' => true,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
			),

			'browseCategories' => array(
				'property'=>'browseCategories',
				'type'=>'oneToMany',
				'label'=>'Browse Categories',
				'description'=>'Browse Categories To Show on the Home Screen',
				'keyThis' => 'locationId',
				'keyOther' => 'locationId',
				'subObjectType' => 'LocationBrowseCategory',
				'structure' => $locationBrowseCategoryStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
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
		/** @var Library $librarySingleton */
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
				$validPickupSystems = explode('|', $homeLibrary->validPickupSystems);
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
				//$this->whereAdd("locationId = {$patronProfile['homeLocationId']}", 'OR');
			}
		}else{
			$this->whereAdd("validHoldPickupBranch = 1");
		}

		/*if (isset($selectedBranchId) && is_numeric($selectedBranchId)){
			$this->whereAdd("locationId = $selectedBranchId", 'OR');
		}*/
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

		if (count($locationList) == 0 && (isset($homeLibrary) && $homeLibrary->inSystemPickupsOnly == 1)){
			$homeLocation = Location::staticGet($patronProfile['homeLocationId']);
			if ($homeLocation->showHoldButton == 1){
				//We didn't find any locations.  This for schools where we want holds available, but don't want the branch to be a
				//pickup location anywhere else.
				$homeLocation->selected = true;
				$locationList['1' . $homeLocation->displayName] = clone $homeLocation;
			}
		}

		return $locationList;
	}

	private static $activeLocation = 'unset';
	/**
	 * Returns the active location to use when doing search scoping, etc.
	 * This does not include the IP address
	 *
	 * @return Location
	 */
	function getActiveLocation(){
		if (Location::$activeLocation != 'unset') {
			return Location::$activeLocation;
		}

		//default value
		Location::$activeLocation = null;

		//load information about the library we are in.
		global $library;
		if (is_null($library)){
			//If we are not in a library, then do not allow branch scoping, etc.
			Location::$activeLocation = null;
		}else{

			//Check to see if a branch location has been specified.
			$locationCode = $this->getBranchLocationCode();

			if ($locationCode != null && $locationCode != '' && $locationCode != 'all'){
				$activeLocation = new Location();
				$activeLocation->code = $locationCode;
				if ($activeLocation->find(true)){
					//Only use the location if we are in the subdomain for the parent library
					if ($library->libraryId == $activeLocation->libraryId){
						Location::$activeLocation = clone($activeLocation);
					}
				}
			}else{
				$physicalLocation = $this->getPhysicalLocation();
				if ($physicalLocation != null){
					Location::$activeLocation = $physicalLocation;
				}
			}
			global $timer;
			$timer->logTime('Finished getActiveLocation');
		}

		return Location::$activeLocation;
	}
	function setActiveLocation($location){
		Location::$activeLocation = $location;
	}

	private static $userHomeLocation = 'unset';

	/**
	 * Get the home location for the currently logged in user.
	 *
	 * @return Location
	 */
	static function getUserHomeLocation(){
		if (isset(Location::$userHomeLocation) && Location::$userHomeLocation != 'unset') return Location::$userHomeLocation;

		//default value
		Location::$userHomeLocation = null;

		global $user;
		if (isset($user) && $user != false){
			$homeLocation = new Location();
			$homeLocation->locationId = $user->homeLocationId;
			if ($homeLocation->find(true)){
				Location::$userHomeLocation = clone($homeLocation);
			}
		}

		return Location::$userHomeLocation;
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
		if ($this->physicalLocation != 'unset'){
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

	static function getSearchLocation($searchSource = null){
		if (is_null($searchSource)){
			$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			if (strpos($searchSource, 'library') === 0){
				$trimmedSearchSource = str_replace('library', '', $searchSource);
				require_once  ROOT_DIR . '/Drivers/marmot_inc/LibrarySearchSource.php';
				$librarySearchSource = new LibrarySearchSource();
				$librarySearchSource->id = $trimmedSearchSource;
				if ($librarySearchSource->find(true)){
					$searchSource = $librarySearchSource;
				}
			}
		}
		if (is_object($searchSource)){
			$scopingSetting = $searchSource->catalogScoping;
		}else{
			$scopingSetting = $searchSource;
		}
		if ($scopingSetting == 'local' || $scopingSetting == 'econtent' || $scopingSetting == 'location'){
			global $locationSingleton;
			return $locationSingleton->getActiveLocation();
		}else if ($scopingSetting == 'marmot' || $scopingSetting == 'unscoped'){
			return null;
		}else{
			$location = new Location();
			$location->code = $scopingSetting;
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
	 * @var string
	 */
	private $ipLocation = 'unset';
	private $ipId = 'unset';
	function getIPLocation(){
		if ($this->ipLocation != 'unset'){
			return $this->ipLocation;
		}
		global $timer;
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $logger;
		//Check the current IP address to see if we are in a branch
		$activeIp = $this->getActiveIp();
		//$logger->log("Active IP is $activeIp", PEAR_LOG_DEBUG);
		$this->ipLocation = $memCache->get('location_for_ip_' . $activeIp);
		$this->ipId = $memCache->get('ipId_for_ip_' . $activeIp);
		if ($this->ipId == -1){
			$this->ipLocation = false;
		}

		if ($this->ipLocation == false || $this->ipId == false){
			$timer->logTime('Starting getIPLocation');
			//echo("Active IP is $activeIp");
			require_once ROOT_DIR . '/Drivers/marmot_inc/subnet.php';
			$subnet = new subnet();
			$ipVal = ip2long($activeIp);

			$this->ipLocation = null;
			$this->ipId = -1;
			if (is_numeric($ipVal)){
				disableErrorHandler();
				$subnet->whereAdd('startIpVal <= ' . $ipVal);
				$subnet->whereAdd('endIpVal >= ' . $ipVal);
				if ($subnet->find(true)){
					//$logger->log("Found {$subnet->N} matching IP addresses {$subnet->location}", PEAR_LOG_DEBUG);
					$matchedLocation = new Location();
					$matchedLocation->locationId = $subnet->locationid;
					if ($matchedLocation->find(true)){
						//Only use the physical location regardless of where we are
						//$logger->log("Active location is {$matchedLocation->displayName}", PEAR_LOG_DEBUG);
						$this->ipLocation = clone($matchedLocation);
						$this->ipId = $subnet->id;
					}else{
						$logger->log("Did not find location for ip location id {$subnet->locationid}", PEAR_LOG_WARNING);
					}
				}
				enableErrorHandler();
			}

			$memCache->set('ipId_for_ip_' . $activeIp, $this->ipId, 0, $configArray['Caching']['ipId_for_ip']);
			$memCache->set('location_for_ip_' . $activeIp, $this->ipLocation, 0, $configArray['Caching']['location_for_ip']);
			$timer->logTime('Finished getIPLocation');
		}

		return $this->ipLocation;
	}

	/**
	 * Must be called after the call to getIPLocation
	 * Enter description here ...
	 */
	function getIPid(){
		return $this->ipId;
	}

	private static $activeIp = null;
	static function getActiveIp(){
		if (!is_null(Location::$activeIp)) return Location::$activeIp;
		global $timer;
		//Make sure gets and cookies are processed in the correct order.
		if (isset($_GET['test_ip'])){
			$ip = $_GET['test_ip'];
			//Set a coookie so we don't have to transfer the ip from page to page.
			setcookie('test_ip', $ip, 0, '/');
		}elseif (isset($_COOKIE['test_ip']) && $_COOKIE['test_ip'] != '127.0.0.1' && strlen($_COOKIE['test_ip']) > 0){
			$ip = $_COOKIE['test_ip'];
		}else{
			if (isset($_SERVER["HTTP_CLIENT_IP"])){
				$ip = $_SERVER["HTTP_CLIENT_IP"];
			}elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
				$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
			}elseif (isset($_SERVER["HTTP_X_FORWARDED"])){
				$ip = $_SERVER["HTTP_X_FORWARDED"];
			}elseif (isset($_SERVER["HTTP_FORWARDED_FOR"])){
				$ip = $_SERVER["HTTP_FORWARDED_FOR"];
			}elseif (isset($_SERVER["HTTP_FORWARDED"])){
				$ip = $_SERVER["HTTP_FORWARDED"];
			}elseif (isset($_SERVER['REMOTE_HOST']) && strlen($_SERVER['REMOTE_HOST']) > 0){
				$ip = $_SERVER['REMOTE_HOST'];
			}elseif (isset($_SERVER['REMOTE_ADDR']) && strlen($_SERVER['REMOTE_ADDR']) > 0){
				$ip = $_SERVER['REMOTE_ADDR'];
			}else{
				$ip = '';
			}
		}
		Location::$activeIp = $ip;
		$timer->logTime("getActiveIp");
		return Location::$activeIp;
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
				if ($this->locationId){
					$hours = new LocationHours();
					$hours->locationId = $this->locationId;
					$hours->orderBy('day');
					$hours->find();
					while($hours->fetch()){
						$this->hours[$hours->id] = clone($hours);
					}
				}
			}
			return $this->hours;
		}elseif ($name == "facets") {
			if (!isset($this->facets)){
				$this->facets = array();
				if ($this->locationId){
					$facet = new LocationFacetSetting();
					$facet->locationId = $this->locationId;
					$facet->orderBy('weight');
					$facet->find();
					while($facet->fetch()){
						$this->facets[$facet->id] = clone($facet);
					}
				}
			}
			return $this->facets;
		}elseif  ($name == 'browseCategories'){
			if (!isset($this->browseCategories) && $this->libraryId){
				$this->browseCategories = array();
				$browseCategory = new LocationBrowseCategory();
				$browseCategory->locationId = $this->locationId;
				$browseCategory->orderBy('weight');
				$browseCategory->find();
				while($browseCategory->fetch()){
					$this->browseCategories[$browseCategory->id] = clone($browseCategory);
				}
			}
			return $this->browseCategories;
		}else{
			return $this->data[$name];
		}

	}

	public function __set($name, $value){
		if ($name == "hours") {
			$this->hours = $value;
		}elseif ($name == "facets") {
			$this->facets = $value;
		}elseif ($name == 'browseCategories'){
			$this->browseCategories = $value;
		}else{
			$this->data[$name] = $value;
		}
	}

	/**
	 * Override the update functionality to save the hours
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(){
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveHours();
			$this->saveFacets();
			$this->saveBrowseCategories();
		}
		return $ret;
	}

	/**
	 * Override the update functionality to save the hours
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveHours();
			$this->saveFacets();
			$this->saveBrowseCategories();
		}
		return $ret;
	}

	public function saveBrowseCategories(){
		if (isset ($this->browseCategories) && is_array($this->browseCategories)){
			/** @var LocationBrowseCategory[] $browseCategories */
			foreach ($this->browseCategories as $locationBrowseCategory){
				if (isset($locationBrowseCategory->deleteOnSave) && $locationBrowseCategory->deleteOnSave == true){
					$locationBrowseCategory->delete();
				}else{
					if (isset($locationBrowseCategory->id) && is_numeric($locationBrowseCategory->id)){
						$ret = $locationBrowseCategory->update();
					}else{
						$locationBrowseCategory->locationId = $this->locationId;
						$locationBrowseCategory->insert();
					}
				}
			}
			unset($this->browseCategories);
		}
	}

	public function clearBrowseCategories(){
		$browseCategories = new LocationBrowseCategory();
		$browseCategories->locationId = $this->locationId;
		$browseCategories->delete();
		$this->browseCategories = array();
	}

	public function saveFacets(){
		if (isset ($this->facets) && is_array($this->facets)){
			/** @var LocationFacetSetting $facet */
			foreach ($this->facets as $facet){
				if (isset($facet->deleteOnSave) && $facet->deleteOnSave == true){
					$facet->delete();
				}else{
					if (isset($facet->id) && is_numeric($facet->id)){
						$facet->update();
					}else{
						$facet->locationId = $this->locationId;
						$facet->insert();
					}
				}
			}
			unset($this->facets);
		}
	}

	public function clearFacets(){
		$facets = new LocationFacetSetting();
		$facets->locationId = $this->locationId;
		$facets->delete();
		$this->facets = array();
	}

	public function saveHours(){
		if (isset ($this->hours) && is_array($this->hours)){
			/** @var LocationHours $hours */
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
		if ($locationId > 0 && $location->find(true)){
			// format $timeToCheck according to MySQL default date format
			$todayFormatted = date('Y-m-d', $timeToCheck);

			// check to see if today is a holiday
			require_once ROOT_DIR . '/Drivers/marmot_inc/Holiday.php';
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
			require_once ROOT_DIR . '/Drivers/marmot_inc/LocationHours.php';
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
					'closeFormatted' => ($hours->close == '12:00' ? 'Noon' : date("g:i A", strtotime($hours->close)))
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
				$daysChecked = 0;
				while (isset($nextDayHours['closed']) && $nextDayHours['closed'] == true && $daysChecked < 7){
					$nextDay += (24 * 60 * 60);
					$nextDayHours = Location::getLibraryHours($locationId,  $nextDay);
					$daysChecked++;
				}

				$nextDayOfWeek = strftime ('%a', $nextDay);
				if (isset($nextDayHours['closed']) && $nextDayHours['closed'] == true){
					if (isset($closureReason)){
						$libraryHoursMessage = "The library is closed today for $closureReason.";
					}else{
						$libraryHoursMessage = "The library is closed today.";
					}
				}else{
					if (isset($closureReason)){
						$libraryHoursMessage = "The library is closed today for $closureReason. It will reopen on $nextDayOfWeek from {$nextDayHours['openFormatted']} to {$nextDayHours['closeFormatted']}";
					}else{
						$libraryHoursMessage = "The library is closed today. It will reopen on $nextDayOfWeek from {$nextDayHours['openFormatted']} to {$nextDayHours['closeFormatted']}";
					}
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
					if (isset($tomorrowsLibraryHours['closed'])  && ($tomorrowsLibraryHours['closed'] == true || $tomorrowsLibraryHours['closed'] == 1)){
						$libraryHoursMessage = "The library will be closed tomorrow for {$tomorrowsLibraryHours['closureReason']}.";
					}else{
						$libraryHoursMessage = "The library will be open tomorrow from " . $tomorrowsLibraryHours['openFormatted'] . " to " . $tomorrowsLibraryHours['closeFormatted'] . ".";
					}
				}else{
					$libraryHoursMessage = "The library is open today from " . $todaysLibraryHours['openFormatted'] . " to " . $todaysLibraryHours['closeFormatted'] . ".";
				}
			}
		}else{
			$libraryHoursMessage = null;
		}
		return $libraryHoursMessage;
	}
	static function getDefaultFacets($locationId = -1){
		global $configArray;
		$defaultFacets = array();

		$facet = new LocationFacetSetting();
		$facet->setupTopFacet('format_category', 'Format Category');
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		if ($configArray['Index']['enableDetailedAvailability']){
			$facet = new LocationFacetSetting();
			$facet->setupTopFacet('availability_toggle', 'Available?', false);
			$facet->locationId = $locationId;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;
		}

		if ($configArray['Index']['enableDetailedAvailability']){
			$facet = new LocationFacetSetting();
			$facet->setupSideFacet('available_at', 'Available Now At', false);
			$facet->locationId = $locationId;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;
		}

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('format', 'Format', false);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('literary_form_full', 'Literary Form', false);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('target_audience_full', 'Reading Level', false);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$facet->numEntriesToShowByDefault = 8;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('topic_facet', 'Subject', false);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('time_since_added', 'Added in the Last', false);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('authorStr', 'Author', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('awards_facet', 'Awards', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('econtent_device', 'Compatible Device', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('econtent_source', 'eContent Source', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('econtent_protection_type', 'eContent Protection', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('era', 'Era', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('genre_facet', 'Genre', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('itype', 'Item Type', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('language', 'Language', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('lexile_code', 'Lexile Code', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('lexile_score', 'Lexile Score', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('mpaa_rating', 'Movie Rating', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('institution', 'Owning System', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('building', 'Owning Branch', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('publishDate', 'Publication Date', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupAdvancedFacet('geographic_facet', 'Region', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LocationFacetSetting();
		$facet->setupSideFacet('rating_facet', 'User Rating', true);
		$facet->locationId = $locationId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		return $defaultFacets;
	}

	/** @return LocationHours[] */
	function getHours(){
		return $this->hours;
	}
}
