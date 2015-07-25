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

	/**
	 * Get a connection to the catalog for the user
	 *
	 * @return CatalogConnection
	 */
	function getCatalogDriver(){
		//Based off the source of the user, get the AccountProfile
		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		$accountProfile = new AccountProfile();
		$accountProfile->name = $this->source;
		$catalogDriver = null;
		if ($accountProfile->find(true)){
			$catalogDriver = $accountProfile->driver;
		}else{
			$accountProfile = null;
		}
		$catalog = CatalogFactory::getCatalogConnectionInstance($catalogDriver, $accountProfile);
		return $catalog;
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
			//Load roles for the user from the user
			require_once ROOT_DIR . '/sys/Administration/Role.php';
			$role = new Role();
			$canMasquerade = false;
			if ($this->id){
				$role->query("SELECT roles.* FROM roles INNER JOIN user_roles ON roles.roleId = user_roles.roleId WHERE userId = " . $this->id . " ORDER BY name");
				$this->roles = array();
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
				$this->roles = array();
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
					$linkedUser = new User();
					$linkedUser->id = $userLink->linkedAccountId;
					if ($linkedUser->find(true)){
						//Load full information from the catalog
						$linkedUser = UserAccount::validateAccount($linkedUser->cat_username, $linkedUser->cat_password, $linkedUser->source);
						$this->linkedUsers[] = clone($linkedUser);
					}
				}
			}
		}
		return $this->linkedUsers;
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
	 */
	function addLinkedUser($user){
		/* var Library $library */
		global $library;
		if ($library->allowLinkedAccounts) {
			$linkedUsers = $this->getLinkedUsers();
			/** @var User $existingUser */
			foreach ($linkedUsers as $existingUser) {
				if ($existingUser->id == $user->id) {
					//We already have a link to this user
					return;
				}
			}
			require_once ROOT_DIR . '/sys/Account/UserLink.php';
			$userLink                   = new UserLink();
			$userLink->primaryAccountId = $this->id;
			$userLink->linkedAccountId  = $user->id;
			$userLink->insert();
			$this->linkedUsers[] = clone($user);
		}
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

		// Every update to object requires clearing the Memcached version of the object & the version stored in the $_SESSION variable
//		if ($result) {
			$this->deletePatronProfileCache();
			$_SESSION['userinfo'] = serialize($this);
//		}
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
		$this->deletePatronProfileCache();
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
//		//Update the serialized instance stored in the session
//		$_SESSION['userinfo'] = serialize($this);
//		$this->deletePatronProfileCache();
	}

	function updateCatalogOptions(){
		//Validate that the input data is correct
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
			$location->whereAdd("locationId = '{$_POST['myLocation1']}'");
			$location->find();
			if ($location->N != 1) {
				PEAR_Singleton::raiseError('The 1st location could not be found in the database.');
			}
			$this->myLocation1Id = $_POST['myLocation1'];
		}
		if (isset($_POST['myLocation2'])){
			$location = new Location();
			$location->whereAdd();
			$location->whereAdd("locationId = '{$_POST['myLocation2']}'");
			$location->find();
			if ($location->N != 1) {
				PEAR_Singleton::raiseError('The 2nd location could not be found in the database.');
			}
			$this->myLocation2Id = $_POST['myLocation2'];
		}
		$this->update();

	}

	function updateUserPreferences(){

		$this->noPromptForUserReviews = (isset($_POST['noPromptForUserReviews']) && $_POST['noPromptForUserReviews'] == 'on')? 1 : 0;
		return $this->update();
	}

	/**
	 * Clear out the cached version of the patron profile.
	 */
	private function deletePatronProfileCache(){
		/** @var Memcache $memCache */
		global $memCache, $serverName;
		$memCache->delete("patronProfile_{$serverName}_" . $this->username);
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

		//Get checked out titles from the ILS
		$catalogTransactions = $this->getCatalogDriver()->getMyTransactions($this);
		$timer->logTime("Loaded transactions from catalog.");

		//Get checked out titles from OverDrive
		require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
		$overDriveDriver = OverDriveDriverFactory::getDriver();
		$overDriveCheckedOutItems = $overDriveDriver->getOverDriveCheckedOutItems($this);

		//Get a list of eContent that has been checked out
		require_once ROOT_DIR . '/Drivers/EContentDriver.php';
		$driver = new EContentDriver(null);
		$eContentCheckedOut = $driver->getMyTransactions($this);

		$allCheckedOut = array_merge($catalogTransactions['transactions'], $overDriveCheckedOutItems['items'], $eContentCheckedOut['transactions']);

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
		require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
		$overDriveDriver = OverDriveDriverFactory::getDriver();
		$overDriveHolds = $overDriveDriver->getOverDriveHolds($this);

		//Get a list of eContent that has been checked out
		require_once ROOT_DIR . '/Drivers/EContentDriver.php';
		$driver = new EContentDriver(null);
		$eContentHolds = $driver->getMyHolds($this);

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
}
