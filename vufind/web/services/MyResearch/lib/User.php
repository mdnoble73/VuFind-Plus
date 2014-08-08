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
	public $college;                         // string(100)  not_null
	public $major;                           // string(100)  not_null
	public $created;                         // datetime(19)  not_null binary
	public $homeLocationId;					 // int(11)
	public $myLocation1Id;					 // int(11)
	public $myLocation2Id;					 // int(11)
	public $trackReadingHistory; 			 // tinyint
	public $bypassAutoLogout;        //tinyint
	public $disableRecommendations;     //tinyint
	public $disableCoverArt;     //tinyint
	public $overdriveEmail;
	public $promptForOverdriveEmail;
	public $preferredLibraryInterface;
	private $roles;
	private $data = array();

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('User',$k,$v); }

	/* the code above is auto generated do not remove the tag below */
	###END_AUTOCODE

	function __sleep()
	{
		return array('id', 'username', 'password', 'cat_username', 'cat_password', 'firstname', 'lastname', 'email', 'phone', 'college', 'major', 'homeLocationId', 'myLocation1Id', 'myLocation2Id', 'trackReadingHistory', 'roles', 'bypassAutoLogout', 'displayName', 'disableRecommendations', 'disableCoverArt', 'patronType', 'overdriveEmail', 'promptForOverdriveEmail', 'preferredLibraryInterface');
	}

	function __wakeup()
	{
	}

	function getTags($id = null){
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

		$sql = "SELECT user_list.*, COUNT(user_resource.id) AS cnt FROM user_list " .
               "LEFT JOIN user_resource ON user_list.id = user_resource.list_id " .
               "WHERE user_list.user_id = '$this->id' " .
               "GROUP BY user_list.id, user_list.user_id, user_list.title, " .
               "user_list.description, user_list.created, user_list.public " .
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

	function __get($name){
		if ($name == 'roles'){
			return $this->getRoles();
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
				$role->query("INSERT INTO user_roles ( `userId` , `roleId` ) VALUES " . $values);
			}
		}
	}

	function update(){
		parent::update();
		$this->saveRoles();
	}

	function insert(){
		//set default values as needed
		if (!isset($this->homeLocationId)) $this->homeLocationId = 0;
		if (!isset($this->myLocation1Id)) $this->myLocation1Id = 0;
		if (!isset($this->myLocation2Id)) $this->myLocation2Id = 0;
		if (!isset($this->bypassAutoLogout)) $this->bypassAutoLogout = 0;

		parent::insert();
		$this->saveRoles();
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

		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
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
		if (isset($_REQUEST['promptForOverdriveEmail'])){
			if ($_REQUEST['promptForOverdriveEmail'] == 'yes' || $_REQUEST['promptForOverdriveEmail'] == 'on'){
				$this->promptForOverdriveEmail = 1;
			}else{
				$this->promptForOverdriveEmail = 0;
			}
		}
		if (isset($_REQUEST['overdriveEmail'])){
			$this->overdriveEmail = strip_tags($_REQUEST['overdriveEmail']);
		}
		$this->update();
		//Update the serialized instance stored in the session
		$_SESSION['userinfo'] = serialize($this);
		/** @var Memcache $memCache */
		global $memCache;
		$memCache->delete('patronProfile_' . $this->id);
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
		//Update the serialized instance stored in the session
		$_SESSION['userinfo'] = serialize($this);

		/** @var Memcache $memCache */
		global $memCache;
		$memCache->delete('patronProfile_' . $this->id);
	}
}
