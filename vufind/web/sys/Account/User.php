<?php
/**
 * Table Definition for user
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class User extends DB_DataObject
{
	###START_AUTOCODE
	/* the code below is auto generated do not remove the above tag */

	public $__table = 'user';                            // table name
	public $id;                              // int(11)  not_null primary_key auto_increment
	public $source;
	public $username;                        // string(30)  not_null unique_key
	public $displayName;                     // string(30)
	public $password;                        // string(32)  not_null
	public $firstname;                       // string(50)  not_null
	public $lastname;                        // string(50)  not_null
	public $email;                           // string(250)  not_null
	public $phone;                           // string(30)
	public $cat_username;                    // string(50)
	public $cat_password;                    // string(50)
	public $patronType;
	public $created;                         // datetime(19)  not_null binary
	public $homeLocationId;					 // int(11)
	public $myLocation1Id;					 // int(11)
	public $myLocation2Id;					 // int(11)
	public $trackReadingHistory; 			 // tinyint
	public $initialReadingHistoryLoaded;
	public $bypassAutoLogout;        //tinyint
	public $disableRecommendations;     //tinyint
	public $disableCoverArt;     //tinyint
	public $overdriveEmail;
	public $promptForOverdriveEmail;
	public $preferredLibraryInterface;
	public $noPromptForUserReviews; //tinyint(1)
	private $roles;

	/** @var User $parentUser */
	private $parentUser;
	/** @var User[] $linkedUsers */
	private $linkedUsers;
	private $viewers;

	//Data that we load, but don't store in the User table
	public $fullname;
	public $address1;
	public $address2;
	public $city;
	public $state;
	public $zip;
	public $workPhone;
	public $mobileNumber;
	public $web_note;
	public $expires;
	public $expired;
	public $expireClose;
	public $fines;
	public $finesVal;
	public $homeLocationCode;
	public $homeLocation;
	public $myLocation1;
	public $myLocation2;
	public $numCheckedOutIls;
	public $numHoldsIls;
	public $numHoldsAvailableIls;
	public $numHoldsRequestedIls;
	public $numCheckedOutEContent;
	public $numHoldsEContent;
	public $numHoldsAvailableEContent;
	public $numHoldsRequestedEContent;
	public $numCheckedOutOverDrive;
	public $canUseOverDrive;
	public $numHoldsOverDrive;
	public $numHoldsAvailableOverDrive;
	public $numHoldsRequestedOverDrive;
	public $numBookings;
	public $notices;
	public $noticePreferenceLabel;
	public $numMaterialsRequests;
	public $readingHistorySize;

	private $data = array();


	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('User',$k,$v); }

	/* the code above is auto generated do not remove the tag below */
	###END_AUTOCODE

	function getTags(){
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserTag.php';
		$tagList = array();

		$sql = "SELECT id, groupedRecordPermanentId, tag, COUNT(groupedRecordPermanentId) AS cnt " .
							 "FROM user_tags WHERE " .
							 "userId = '{$this->id}' ";
		$sql .= "GROUP BY tag ORDER BY tag ASC";
		$tag = new UserTag();
		$tag->query($sql);
		if ($tag->N) {
			while ($tag->fetch()) {
				$tagList[] = clone($tag);
			}
		}

		return $tagList;
	}


	function getLists() {
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';

		$lists = array();

		$sql = "SELECT user_list.* FROM user_list " .
							 "WHERE user_list.user_id = '$this->id' " .
							 "ORDER BY user_list.title";
		$list = new UserList();
		$list->query($sql);
		if ($list->N) {
			while ($list->fetch()) {
				$lists[] = clone($list);
			}
		}

		return $lists;
	}

	private $catalogDriver;
	/**
	 * Get a connection to the catalog for the user
	 *
	 * @return CatalogConnection
	 */
	function getCatalogDriver(){
		if ($this->catalogDriver == null){
			//Based off the source of the user, get the AccountProfile
			$accountProfile = $this->getAccountProfile();
			if ($accountProfile){
				$catalogDriver = $accountProfile->driver;
				$this->catalogDriver = CatalogFactory::getCatalogConnectionInstance($catalogDriver, $accountProfile);
			}
		}
		return $this->catalogDriver;
	}

	private $accountProfile;

	/**
	 * @return AccountProfile
	 */
	function getAccountProfile(){
		if ($this->accountProfile != null){
			return $this->accountProfile;
		}
		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		$accountProfile = new AccountProfile();
		$accountProfile->name = $this->source;
		if ($accountProfile->find(true)){
			$this->accountProfile = $accountProfile;
		}else{
			$this->accountProfile = null;
		}
		return $this->accountProfile;
	}

	function __get($name){
		if ($name == 'roles') {
			return $this->getRoles();
		}elseif ($name == 'linkedUsers'){
			return $this->getLinkedUsers();
		}else{
			return $this->data[$name];
		}
	}

	function __set($name, $value){
		if ($name == 'roles'){
			$this->roles = $value;
			//Update the database, first remove existing values
			$this->saveRoles();
		}else{
			$this->data[$name] = $value;
		}
	}

	function getRoles(){
		if (is_null($this->roles)){
			$this->roles = array();
			//Load roles for the user from the user
			require_once ROOT_DIR . '/sys/Administration/Role.php';
			$role = new Role();
			$canMasquerade = false;
			if ($this->id){
				$role->query("SELECT roles.* FROM roles INNER JOIN user_roles ON roles.roleId = user_roles.roleId WHERE userId = " . $this->id . " ORDER BY name");
				while ($role->fetch()){
					$this->roles[$role->roleId] = $role->name;
					if ($role->name == 'userAdmin'){
						$canMasquerade = true;
					}
				}
			}

			//Setup masquerading as different users
			$testRole = '';
			if (isset($_REQUEST['test_role'])){
				$testRole = $_REQUEST['test_role'];
			}elseif (isset($_COOKIE['test_role'])){
				$testRole = $_COOKIE['test_role'];
			}
			if ($canMasquerade && $testRole != ''){
				if (is_array($testRole)){
					$testRoles = $testRole;
				}else{
					$testRoles = array($testRole);
				}
				foreach ($testRoles as $tmpRole){
					$role = new Role();
					if (is_numeric($tmpRole)){
						$role->roleId = $tmpRole;
					}else{
						$role->name = $tmpRole;
					}
					$found = $role->find(true);
					if ($found == true){
						$this->roles[$role->roleId] = $role->name;
					}
				}
			}
			return $this->roles;
		}else{
			return $this->roles;
		}
	}

	function getBarcode(){
		global $configArray;
		//TODO: Check the login configuration for the driver
		if ($configArray['Catalog']['barcodeProperty'] == 'cat_username'){
			return $this->cat_username;
		}else{
			return $this->cat_password;
		}
	}

	function saveRoles(){
		if (isset($this->id) && isset($this->roles) && is_array($this->roles)){
			require_once ROOT_DIR . '/sys/Administration/Role.php';
			$role = new Role();
			$role->query("DELETE FROM user_roles WHERE userId = " . $this->id);
			//Now add the new values.
			if (count($this->roles) > 0){
				$values = array();
				foreach ($this->roles as $roleId => $roleName){
					$values[] = "({$this->id},{$roleId})";
				}
				$values = join(', ', $values);
				$role->query("INSERT INTO user_roles ( `userId` , `roleId` ) VALUES $values");
			}
		}
	}

	/**
	 * @return User[]
	 */
	function getLinkedUsers(){
		if (is_null($this->linkedUsers)){
			$this->linkedUsers = array();
			/* var Library $library */
			global $library;
			if ($this->id && $library->allowLinkedAccounts){
				require_once ROOT_DIR . '/sys/Account/UserLink.php';
				$userLink = new UserLink();
				$userLink->primaryAccountId = $this->id;
				$userLink->find();
				while ($userLink->fetch()){
					if (!$this->isBlockedAccount($userLink->id)) {
						$linkedUser = new User();
						$linkedUser->id = $userLink->linkedAccountId;
						if ($linkedUser->find(true)){
							//Load full information from the catalog
							$linkedUser = UserAccount::validateAccount($linkedUser->cat_username, $linkedUser->cat_password, $linkedUser->source, $this);
							if ($linkedUser && !PEAR_Singleton::isError($linkedUser)) {
								$this->linkedUsers[] = clone($linkedUser);
							}
						}
					}
				}
			}
		}
		return $this->linkedUsers;
	}

	public function setParentUser($user){
		$this->parentUser =  $user;
	}

	// Account Blocks //
	private $blockAll = null; // set to null to signal unset, boolean when set
	private $blockedAccounts = null; // set to null to signal unset, array when set

	/**
	 * Checks if there is any settings disallowing the account $accountIdToCheck to be linked to this user.
	 *
	 * @param  $accountIdToCheck string   linked account Id to check for blocking
	 * @return bool                       true for blocking, false for no blocking
	 */
	public function isBlockedAccount($accountIdToCheck) {
		if (is_null($this->blockAll)) $this->setAccountBlocks();
		return $this->blockAll || in_array($accountIdToCheck, $this->blockedAccounts);
	}

	private function setAccountBlocks() {
		// default settings
		$this->blockAll = false;
		$this->blockedAccounts = array();

		require_once ROOT_DIR . '/sys/Administration/BlockPatronAccountLink.php';
		$accountBlock = new BlockPatronAccountLink();
		$accountBlock->primaryAccountId = $this->id;
		if ($accountBlock->find()) {
			while ($accountBlock->fetch(false)) {
				if ($accountBlock->blockLinking) $this->blockAll = true; // any one row that has block all on will set this setting to true for this account.
				if ($accountBlock->blockedLinkAccountId) $this->blockedAccounts[] = $accountBlock->blockedLinkAccountId;
			}
		}
	}

	function getRelatedOverDriveUsers(){
		$overDriveUsers = array();
		if ($this->isValidForOverDrive()){
			$overDriveUsers[$this->cat_username . ':' . $this->cat_password] = $this;
		}
		foreach ($this->getLinkedUsers() as $linkedUser){
			if ($linkedUser->isValidForOverDrive()){
				if (array_key_exists($linkedUser->cat_username . ':' . $linkedUser->cat_password, $overDriveUsers)){
					$overDriveUsers[$linkedUser->cat_username . ':' . $linkedUser->cat_password] = $linkedUser;
				}
			}
		}

		return $overDriveUsers;
	}

	function isValidForOverDrive(){
		if (!$this->parentUser || ($this->getBarcode() != $this->parentUser->getBarcode())){
			$userHomeLibrary = Library::getPatronHomeLibrary($this);
			if ($userHomeLibrary && $userHomeLibrary->enableOverdriveCollection){
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns a list of users that can view this account
	 *
	 * @return User[]
	 */
	function getViewers(){
		if (is_null($this->viewers)){
			$this->viewers = array();
			/* var Library $library */
			global $library;
			if ($this->id && $library->allowLinkedAccounts){
				require_once ROOT_DIR . '/sys/Account/UserLink.php';
				$userLink = new UserLink();
				$userLink->linkedAccountId = $this->id;
				$userLink->find();
				while ($userLink->fetch()){
					$linkedUser = new User();
					$linkedUser->id = $userLink->primaryAccountId;
					if ($linkedUser->find(true)){
						$this->viewers[] = clone($linkedUser);
					}
				}
			}
		}
		return $this->viewers;
	}

	/**
	 * @param User $user
	 *
	 * @return boolean
	 */
	function addLinkedUser($user){
		/* var Library $library */
		global $library;
		if ($library->allowLinkedAccounts && $user->id != $this->id) { // library allows linked accounts and the account to link is not itself
			$linkedUsers = $this->getLinkedUsers();
			/** @var User $existingUser */
			foreach ($linkedUsers as $existingUser) {
				if ($existingUser->id == $user->id) {
					//We already have a link to this user
					return true;
				}
			}

			// Check for Account Blocks
			if ($this->isBlockedAccount($user->id)) return false;

			// Add Account Link
			require_once ROOT_DIR . '/sys/Account/UserLink.php';
			$userLink                   = new UserLink();
			$userLink->primaryAccountId = $this->id;
			$userLink->linkedAccountId  = $user->id;
			$result = $userLink->insert();
			$this->linkedUsers[] = clone($user);
			return true == $result; // return success or failure
		}
		return false;
	}

	function removeLinkedUser($userId){
		/* var Library $library */
		global $library;
		if ($library->allowLinkedAccounts) {
			require_once ROOT_DIR . '/sys/Account/UserLink.php';
			$userLink                   = new UserLink();
			$userLink->primaryAccountId = $this->id;
			$userLink->linkedAccountId  = $userId;
			$ret                        = $userLink->delete();

			//Force a reload of data
			$this->linkedUsers = null;
			$this->getLinkedUsers();

			return $ret == 1;
		}
		return false;
	}


	function update(){
		$result = parent::update();
		$this->saveRoles();
		$this->clearCache(); // Every update to object requires clearing the Memcached version of the object
		return $result;
	}

	function insert(){
		//set default values as needed
		if (!isset($this->homeLocationId)) $this->homeLocationId = 0;
		if (!isset($this->myLocation1Id)) $this->myLocation1Id = 0;
		if (!isset($this->myLocation2Id)) $this->myLocation2Id = 0;
		if (!isset($this->bypassAutoLogout)) $this->bypassAutoLogout = 0;

		parent::insert();
		$this->saveRoles();
		$this->clearCache();
	}

	function hasRole($roleName){
		$myRoles = $this->__get('roles');
		return in_array($roleName, $myRoles);
	}

	function getObjectStructure(){
		//Lookup available roles in the system
		require_once ROOT_DIR . '/sys/Administration/Role.php';
		$roleList = Role::getLookup();

		$structure = array(
					'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Administrator Id', 'description'=>'The unique id of the in the system'),
					'firstname' => array('property'=>'firstname', 'type'=>'label', 'label'=>'First Name', 'description'=>'The first name for the user.'),
					'lastname' => array('property'=>'lastname', 'type'=>'label', 'label'=>'Last Name', 'description'=>'The last name of the user.'),
		);

		global $configArray;
		$barcodeProperty = $configArray['Catalog']['barcodeProperty'];
		$structure['barcode'] = array('property'=>$barcodeProperty, 'type'=>'label', 'label'=>'Barcode', 'description'=>'The barcode for the user.');

		$structure['roles'] = array('property'=>'roles', 'type'=>'multiSelect', 'listStyle' =>'checkbox', 'values'=>$roleList, 'label'=>'Roles', 'description'=>'A list of roles that the user has.');

		return $structure;
	}

	function getFilters(){
		require_once ROOT_DIR . '/sys/Administration/Role.php';
		$roleList = Role::getLookup();
		$roleList[-1] = 'Any Role';
		return array(
		array('filter'=>'role', 'type'=>'enum', 'values'=>$roleList, 'label'=>'Role'),
		array('filter'=>'cat_password', 'type'=>'text', 'label'=>'Login'),
		array('filter'=>'cat_username', 'type'=>'text', 'label'=>'Name'),
		);
	}

	function hasRatings(){
		require_once ROOT_DIR . '/Drivers/marmot_inc/UserRating.php';

		$rating = new UserRating();
		$rating->userid = $this->id;
		$rating->find();
		if ($rating->N > 0){
			return true;
		}else{
			return false;
		}
	}

	function updateRuntimeInformation(){
		$this->getCatalogDriver()->updateUserWithAdditionalRuntimeInformation($this);
	}

	function updateOverDriveOptions(){
		if (isset($_REQUEST['promptForOverdriveEmail']) && ($_REQUEST['promptForOverdriveEmail'] == 'yes' || $_REQUEST['promptForOverdriveEmail'] == 'on')){
			// if set check & on check must be combined because checkboxes/radios don't report 'offs'
				$this->promptForOverdriveEmail = 1;
			}else{
				$this->promptForOverdriveEmail = 0;
			}
		if (isset($_REQUEST['overdriveEmail'])){
			$this->overdriveEmail = strip_tags($_REQUEST['overdriveEmail']);
		}
		$this->update();
	}

	function updateUserPreferences(){
		// Validate that the input data is correct
		if (isset($_POST['myLocation1']) && preg_match('/^\d{1,3}$/', $_POST['myLocation1']) == 0){
			PEAR_Singleton::raiseError('The 1st location had an incorrect format.');
		}
		if (isset($_POST['myLocation2']) && preg_match('/^\d{1,3}$/', $_POST['myLocation2']) == 0){
			PEAR_Singleton::raiseError('The 2nd location had an incorrect format.');
		}
		if (isset($_REQUEST['bypassAutoLogout']) && ($_REQUEST['bypassAutoLogout'] == 'yes' || $_REQUEST['bypassAutoLogout'] == 'on')){
			$this->bypassAutoLogout = 1;
		}else{
			$this->bypassAutoLogout = 0;
		}

		//Make sure the selected location codes are in the database.
		if (isset($_POST['myLocation1'])){
			$location = new Location();
			$location->get('locationId', $_POST['myLocation1'] );
			if ($location->N != 1) {
				PEAR_Singleton::raiseError('The 1st location could not be found in the database.');
			} else {
				$this->myLocation1Id = $_POST['myLocation1'];
			}
		}
		if (isset($_POST['myLocation2'])){
			$location = new Location();
			$location->get('locationId', $_POST['myLocation2'] );
			if ($location->N != 1) {
				PEAR_Singleton::raiseError('The 2nd location could not be found in the database.');
			} else {
				$this->myLocation2Id = $_POST['myLocation2'];
			}
		}

		$this->noPromptForUserReviews = (isset($_POST['noPromptForUserReviews']) && $_POST['noPromptForUserReviews'] == 'on')? 1 : 0;
		$this->clearCache();
		return $this->update();
	}

	/**
	 * Clear out the cached version of the patron profile.
	 */
	function clearCache(){
		/** @var Memcache $memCache */
		global $memCache, $serverName;
		$memCache->delete("user_{$serverName}_" . $this->id); // now stored by User object id column
	}

	/**
	 * @param $list UserList           object of the user list to check permission for
	 * @return  bool       true if this user can edit passed list
	 */
	function canEditList($list) {
		if ($this->id == $list->user_id){
			return true;
		}elseif ($this->hasRole('opacAdmin')){
			return true;
		}elseif ($this->hasRole('libraryAdmin') || $this->hasRole('contentEditor')){
			$listUser = new User();
			$listUser->id = $list->user_id;
			$listUser->find(true);
			$listLibrary = Library::getLibraryForLocation($listUser->homeLocationId);
			$userLibrary = Library::getLibraryForLocation($this->homeLocationId);
			if ($userLibrary->libraryId == $listLibrary->libraryId){
				return true;
			}
		}
		return false;
	}

	function getHomeLibrarySystemName(){
		$homeLibrary = Library::getPatronHomeLibrary($this);
		return $homeLibrary->displayName;
	}

	public function getNumCheckedOutTotal($includeLinkedUsers = true) {
		$myCheckouts = $this->numCheckedOutIls + $this->numCheckedOutEContent + $this->numCheckedOutOverDrive;
		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $user) {
					$myCheckouts += $user->getNumCheckedOutTotal(false);
				}
			}
		}
		return $myCheckouts;
	}

	public function getNumHoldsTotal($includeLinkedUsers = true) {
		$myHolds = $this->numHoldsIls + $this->numHoldsEContent + $this->numHoldsOverDrive;
		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->linkedUsers as $user) {
					$myHolds += $user->getNumHoldsTotal(false);
				}
			}
		}
		return $myHolds;
	}

	public function getNumHoldsAvailableTotal($includeLinkedUsers = true){
		$myHolds = $this->numHoldsAvailableIls + $this->numHoldsAvailableEContent + $this->numHoldsAvailableOverDrive;
		if ($includeLinkedUsers){
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->linkedUsers as $user) {
					$myHolds += $user->getNumHoldsAvailableTotal(false);
				}
			}
		}

		return $myHolds;
	}

	public function getNumBookingsTotal($includeLinkedUsers = true){
		$myBookings = $this->numBookings;
		if ($includeLinkedUsers){
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->linkedUsers as $user) {
					$myBookings += $user->getNumBookingsTotal(false);
				}
			}
		}

		return $myBookings;
	}

	public function getTotalFines($includeLinkedUsers = true){
		$totalFines = $this->finesVal;
		if ($includeLinkedUsers){
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->linkedUsers as $user) {
					$totalFines += $user->getTotalFines(false);
				}
			}
		}
		return $totalFines;
	}

	/**
	 * Return all titles that are currently checked out by the user.
	 *
	 * Will check:
	 * 1) The current ILS for the user
	 * 2) OverDrive
	 * 3) eContent stored by Pika
	 *
	 * @param bool $includeLinkedUsers
	 * @return array
	 */
	public function getMyCheckouts($includeLinkedUsers = true){
		global $timer;
		global $configArray;

		//Get checked out titles from the ILS
		$ilsCheckouts = $this->getCatalogDriver()->getMyCheckouts($this);
		$timer->logTime("Loaded transactions from catalog.");

		//Get checked out titles from OverDrive
		//Do not load OverDrive titles if the parent barcode (if any) is the same as the current barcode
		if ($this->isValidForOverDrive()){
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$overDriveDriver = OverDriveDriverFactory::getDriver();
			$overDriveCheckedOutItems = $overDriveDriver->getOverDriveCheckedOutItems($this);
		}else{
			$overDriveCheckedOutItems = array();
		}

		if ($configArray['EContent']['hasProtectedEContent']){
			//Get a list of eContent that has been checked out
			require_once ROOT_DIR . '/Drivers/EContentDriver.php';
			$driver = new EContentDriver(null);
			$eContentCheckedOut = $driver->getMyCheckouts($this);
		}else{
			$eContentCheckedOut = array();
		}


		$allCheckedOut = array_merge($ilsCheckouts, $overDriveCheckedOutItems, $eContentCheckedOut);

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $user) {
					$allCheckedOut = array_merge($allCheckedOut, $user->getMyCheckouts(false));
				}
			}
		}
		return $allCheckedOut;
	}

	public function getMyHolds($includeLinkedUsers = true){
		$ilsHolds = $this->getCatalogDriver()->getMyHolds($this);
		if (PEAR_Singleton::isError($ilsHolds)) {
			$ilsHolds = array();
		}

		//Get holds from OverDrive
		if ($this->isValidForOverDrive()){
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$overDriveDriver = OverDriveDriverFactory::getDriver();
			$overDriveHolds = $overDriveDriver->getOverDriveHolds($this);
		}else{
			$overDriveHolds = array();
		}

		global $configArray;
		if ($configArray['EContent']['hasProtectedEContent']) {
			//Get a list of eContent that has been checked out
			require_once ROOT_DIR . '/Drivers/EContentDriver.php';
			$driver = new EContentDriver(null);
			$eContentHolds = $driver->getMyHolds($this);
		}else{
			$eContentHolds = array();
		}

		$allHolds = array_merge_recursive($ilsHolds, $overDriveHolds, $eContentHolds);

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $user) {
					$allHolds = array_merge_recursive($allHolds, $user->getMyHolds(false));
				}
			}
		}
		return $allHolds;
	}

	public function getMyBookings($includeLinkedUsers = true){
		$ilsBookings = $this->getCatalogDriver()->getMyBookings($this);
		if (PEAR_Singleton::isError($ilsBookings)) {
			$ilsBookings = array();
		}

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $user) {
					$ilsBookings = array_merge_recursive($ilsBookings, $user->getMyBookings(false));
				}
			}
		}
		return $ilsBookings;
	}

	public function getMyFines($includeLinkedUsers = true){
		$ilsFines[$this->id] = $this->getCatalogDriver()->getMyFines($this);
		if (PEAR_Singleton::isError($ilsFines)) {
			$ilsFines[$this->id] = array();
		}

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $user) {
					$ilsFines += $user->getMyFines(false); // keep keys as userId
				}
			}
		}
		return $ilsFines;
	}

	public function getNameAndLibraryLabel(){
		return $this->displayName . ' - ' . $this->getHomeLibrarySystemName();
	}

	/**
	 * Get a list of locations where a record can be picked up.  Handles liked accounts
	 * and filtering to make sure that the user is able to
	 *
	 * @param $recordSource string   The source of the record that we are placing a hold on
	 *
	 * @return Location[]
	 */
	public function getValidPickupBranches($recordSource){
		//Get the list of pickup branch locations for display in the user interface.
		// using $user to be consistent with other code use of getPickupBranches()
		$userLocation = new Location();
		if ($recordSource == $this->getAccountProfile()->recordSource){
			$locations = $userLocation->getPickupBranches($this, $this->homeLocationId);
		}else{
			$locations = array();
		}
		$linkedUsers = $this->getLinkedUsers();
		foreach ($linkedUsers as $linkedUser){
			if ($recordSource == $linkedUser->source){
				$linkedUserLocation = new Location();
				$locations = array_merge($locations, $linkedUserLocation->getPickupBranches($linkedUser, null, true));
			}
		}
		ksort($locations);
		return $locations;
	}

	/**
	 * Place Hold
	 *
	 * Place a hold for the current user within their ILS
	 *
	 * @param   string  $recordId     The id of the bib record
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  array                 An array with the following keys
	 *                                result - true/false
	 *                                message - the message to display
	 * @access  public
	 */
	function placeHold($recordId, $pickupBranch) {
		$result = $this->getCatalogDriver()->placeHold($this, $recordId, $pickupBranch);
		if ($result['success']){
			$this->clearCache();
		}
		return $result;
	}

	function bookMaterial($recordId, $startDate, $startTime, $endDate, $endTime){
		$result = $this->getCatalogDriver()->bookMaterial($recordId, $startDate, $startTime, $endDate, $endTime);
		if ($result['success']){
			$this->clearCache();
		}
		return $result;
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for placing item level holds.
	 *
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $itemId     The id of the item to hold
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	function placeItemHold($recordId, $itemId, $pickupBranch) {
		$result = $this->getCatalogDriver()->placeItemHold($this, $recordId, $itemId, $pickupBranch);
		if ($result['success']){
			$this->clearCache();
		}
		return $result;
	}

	/**
	 * Get the user referred to by id.  Will return false if the specified patron id is not
	 * the id of this user or one of the users that is linked to this user.
	 *
	 * @param $patronId     int  The patron to check
	 * @return User|false
	 */
	function getUserReferredTo($patronId){
		$patron = false;
		//Get the correct patron based on the information passed in.
		if ($patronId == $this->id){
			$patron = $this;
		}else{
			foreach ($this->getLinkedUsers() as $tmpUser){
				if ($tmpUser->id == $patronId){
					$patron = $tmpUser;
					break;
				}
			}
		}
		return $patron;
	}

	/**
	 * Cancels a hold for the user in their ILS
	 *
	 * @param $recordId string  The Id of the record being cancelled
	 * @param $cancelId string  The Id of the hold to be cancelled.  Structure varies by ILS
	 *
	 * @return array            Information about the result of the cancellation process
	 */
	function cancelHold($recordId, $cancelId){
		$result = $this->getCatalogDriver()->cancelHold($this, $recordId, $cancelId);
		$this->clearCache();
		return $result;
	}

	function freezeHold($recordId, $holdId, $reactivationDate){
		$result = $this->getCatalogDriver()->freezeHold($this, $recordId, $holdId, $reactivationDate);
		$this->clearCache();
		return $result;
	}

	function thawHold($recordId, $holdId){
		$result = $this->getCatalogDriver()->thawHold($this, $recordId, $holdId);
		$this->clearCache();
		return $result;
	}

	function renewItem($recordId, $itemId, $itemIndex){
		$result = $this->getCatalogDriver()->renewItem($this, $recordId, $itemId, $itemIndex);
		$this->clearCache();
		return $result;
	}

	function renewAll($renewLinkedUsers = false){
		$renewAllResults = $this->getCatalogDriver()->renewAll($this);
		//Also renew linked Users if needed
		if ($renewLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $user) {
					$linkedResults = $user->renewAll(false);
					//Merge results
					$renewAllResults['Renewed'] += $linkedResults['Renewed'];
					$renewAllResults['Unrenewed'] += $linkedResults['Unrenewed'];
					$renewAllResults['Total'] += $linkedResults['Total'];
					if ($renewAllResults['success'] && !$linkedResults['success']){
						$renewAllResults['success'] = false;
						$renewAllResults['message'] = $linkedResults['message'];
					}else if (!$renewAllResults['success'] && !$linkedResults['success']){
						//Append the new message

						array_merge($renewAllResults['message'], $linkedResults['message']);
					}
				}
			}
		}
		$this->clearCache();
		return $renewAllResults;
	}

	public function getReadingHistory($page, $recordsPerPage, $selectedSortOption) {
		return $this->getCatalogDriver()->getReadingHistory($this, $page, $recordsPerPage, $selectedSortOption);
	}

	public function doReadingHistoryAction($readingHistoryAction, $selectedTitles){
		$result = $this->getCatalogDriver()->doReadingHistoryAction($this, $readingHistoryAction, $selectedTitles);
		$this->clearCache();
		return $result;
	}

	/**
	 * Used by Account Profile, to show users any additional Admin roles they may have.
	 * @return bool
	 */
	public function isStaff(){
		global $configArray;
		if (count($this->getRoles()) > 0){
			return true;
		}elseif (isset($configArray['Staff P-Types'])){
			$staffPTypes = $configArray['Staff P-Types'];
			$pType = $this->patronType;
			if ($pType && array_key_exists($pType, $staffPTypes)){
				return true;
			}
		}
		return false;
	}

	public function updatePatronInfo($canUpdateContactInfo){
		$result = $this->getCatalogDriver()->updatePatronInfo($this, $canUpdateContactInfo);
		$this->clearCache();
		return $result;
	}

	public function updatePin(){
		if (isset($_REQUEST['pin'])){
			$oldpin = $_REQUEST['pin'];
		}else{
			return "Please enter your current pin number";
		}
		if ($this->cat_password != $oldpin){
			return "The old pin number is incorrect";
		}
		if (!empty($_REQUEST['pin1'])){
			$newPin = $_REQUEST['pin1'];
		}else{
			return "Please enter the new pin number";
		}
		if (!empty($_REQUEST['pin2'])){
			$confirmNewPin = $_REQUEST['pin2'];
		}else{
			return "Please enter the new pin number again";
		}
		if ($newPin != $confirmNewPin){
			return "New PINs do not match. Please try again.";
		}
		$result = $this->getCatalogDriver()->updatePin($this, $oldpin, $newPin, $confirmNewPin);
		$this->clearCache();
		return $result;
	}
}
