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
	private $roles;
	private $data = array();

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('User',$k,$v); }

	/* the code above is auto generated do not remove the tag below */
	###END_AUTOCODE

	function __sleep()
	{
		return array('id', 'username', 'password', 'cat_username', 'cat_password', 'firstname', 'lastname', 'email', 'phone', 'college', 'major', 'homeLocationId', 'myLocation1Id', 'myLocation2Id', 'trackReadingHistory', 'roles', 'bypassAutoLogout', 'displayName', 'disableRecommendations', 'disableCoverArt', 'patronType');
	}

	function __wakeup()
	{
	}

	function hasResource($resource) {
		require_once 'User_resource.php';
		$join = new User_resource();
		$join->user_id = $this->id;
		$join->resource_id = $resource->id;
		if ($join->find()) {
			return true;
		} else {
			return false;
		}
	}

	function addResource($resource, $list, $tagArray, $notes, $updateSolr = true){
		require_once 'User_resource.php';
		require_once 'Tags.php';
		$join = new User_resource();
		$join->user_id = $this->id;
		$join->resource_id = $resource->id;
		$join->list_id = $list->id;
		if ($join->find(true)) {
			if ($notes) {
				$join->notes = $notes;
				$join->update();
			}
			$result = true;
		} else {
			if ($notes) {
				$join->notes = $notes;
			}
			$result = $join->insert();
		}
		if ($result) {
			if (is_array($tagArray) && count($tagArray)) {
				require_once 'Resource_tags.php';
				$join = new Resource_tags();
				$join->resource_id = $resource->id;
				$join->user_id = $this->id;
				$join->list_id = $list->id;
				$join->delete();
				foreach ($tagArray as $value) {
					$value = trim(strtolower(str_replace('"', '', $value)));
					$tag = new Tags();
					$tag->tag = $value;
					if (!$tag->find(true)) {
						$tag->insert();
					}
					$join->tag_id = $tag->id;
					$join->insert();
				}
			}

			if ($updateSolr){
				$list->updateDetailed(true);
			}

			//Make a call to strands to update that the item was added to the list
			global $configArray;
			if (isset($configArray['Strands']['APID'])){
				if ($resource->source == 'eContent'){
					$strandsUrl = "http://bizsolutions.strands.com/api2/event/addtofavorites.sbs?apid={$configArray['Strands']['APID']}&item={$resource->record_id}&user={$this->id}";
				}else{
					$strandsUrl = "http://bizsolutions.strands.com/api2/event/addtofavorites.sbs?apid={$configArray['Strands']['APID']}&item=econtentRecord{$resource->record_id}&user={$this->id}";
				}
				$ret = file_get_contents($strandsUrl);
			}

			return true;
		} else {
			return false;
		}
	}

	/**
	 * @todo: delete any unused tags
	 */
	function removeResource($resource){
		require_once 'User_resource.php';
		// Remove the Saved Resource
		$join = new User_resource();
		$join->user_id = $this->id;
		$join->resource_id = $resource->id;
		$join->delete();

		// Remove the Tags from the resource
		require_once 'Resource_tags.php';
		$join = new Resource_tags();
		$join->user_id = $this->id;
		$join->resource_id = $resource->id;
		$join->delete();
	}

	function getResources($tags = null) {
		require_once 'User_resource.php';
		$resourceList = array();

		$sql = "SELECT DISTINCT resource.* FROM resource, user_resource " .
               "WHERE resource.id = user_resource.resource_id " .
               "AND user_resource.user_id = '$this->id'";

		if ($tags) {
			for ($i=0; $i<count($tags); $i++) {
				$sql .= " AND resource.id IN (SELECT DISTINCT resource_tags.resource_id " .
                    "FROM resource_tags, tags " .
                    "WHERE resource_tags.tag_id=tags.id AND tags.tag = '" .
				addslashes($tags[$i]) . "' AND resource_tags.user_id = '$this->id')";
			}
		}

		$resource = new Resource();
		$resource->query($sql);
		if ($resource->N) {
			while ($resource->fetch()) {
				$resourceList[] = clone($resource);
			}
		}

		return $resourceList;
	}

	function getSavedData($resourceId, $source, $listId = null) {
		require_once 'User_resource.php';
		$savedList = array();

		$sql = "SELECT user_resource.*, user_list.title as list_title, user_list.id as list_id " .
               "FROM user_resource, resource, user_list " .
               "WHERE resource.id = user_resource.resource_id " .
               "AND user_resource.list_id = user_list.id " .
               "AND user_resource.user_id = '$this->id' " .
               "AND resource.source = '$source' " .
               "AND resource.record_id = '$resourceId'";
		if (!is_null($listId)) {
			$sql .= " AND user_resource.list_id='$listId'";
		}
		$saved = new User_resource();
		$saved->query($sql);
		if ($saved->N) {
			while ($saved->fetch()) {
				$savedList[] = clone($saved);
			}
		}

		return $savedList;
	}


	function getTags($resourceId = null, $listId = null){
		require_once 'Resource_tags.php';
		require_once 'Tags.php';
		$tagList = array();

		$sql = "SELECT tags.id, tags.tag, COUNT(resource_tags.id) AS cnt " .
               "FROM tags INNER JOIN resource_tags on tags.id = resource_tags.tag_id " .
               "INNER JOIN resource on resource_tags.resource_id = resource.id WHERE " .
               "resource_tags.user_id = '{$this->id}' ";
		if (!is_null($resourceId)) {
			$sql .= "AND resource.record_id = '$resourceId' ";
		}
		if (!is_null($listId)) {
			$sql .= "AND resource_tags.list_id = '$listId' ";
		}
		$sql .= "GROUP BY tags.tag ORDER BY cnt DESC, tags.tag ASC";
		$tag = new Tags();
		$tag->query($sql);
		if ($tag->N) {
			while ($tag->fetch()) {
				$tagList[] = clone($tag);
			}
		}

		return $tagList;
	}


	function getLists() {
		require_once 'User_list.php';

		$lists = array();

		$sql = "SELECT user_list.*, COUNT(user_resource.id) AS cnt FROM user_list " .
               "LEFT JOIN user_resource ON user_list.id = user_resource.list_id " .
               "WHERE user_list.user_id = '$this->id' " .
               "GROUP BY user_list.id, user_list.user_id, user_list.title, " .
               "user_list.description, user_list.created, user_list.public " .
               "ORDER BY user_list.title";
		$list = new User_list();
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
			if (is_null($this->roles)){
				//Load roles for the user from the user
				require_once 'sys/Administration/Role.php';
				$role = new Role();
				if ($this->id){
					$role->query("SELECT roles.* FROM roles INNER JOIN user_roles ON roles.roleId = user_roles.roleId WHERE userId = {$this->id} ORDER BY name");
					$this->roles = array();
					while ($role->fetch()){
						$this->roles[$role->roleId] = $role->name;
					}
				}
				return $this->roles;
			}else{
				return $this->roles;
			}
		}else{
			return $data[$name];
		}
	}

	function __set($name, $value){
		if ($name == 'roles'){
			$this->roles = $value;
			//Update the database, first remove existing values
			$this->saveRoles();
		}else{
			$data[$name] = $value;
		}
	}

	function saveRoles(){
		if (isset($this->id) && isset($this->roles) && $this->roles != null){
			require_once 'sys/Administration/Role.php';
			$role = new Role();
			$role->query("DELETE FROM user_roles WHERE userId = {$this->id}");
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
		require_once 'sys/Administration/Role.php';
		$roleList = Role::getLookup();

		$structure = array(
          'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Administrator Id', 'description'=>'The unique id of the in the system'),
          'firstname' => array('property'=>'firstname', 'type'=>'label', 'label'=>'First Name', 'description'=>'The first name for the user.'),
          'lastname' => array('property'=>'lastname', 'type'=>'label', 'label'=>'Last Name', 'description'=>'The last name of the user.'),
          'password' => array('property'=>'password', 'type'=>'label', 'label'=>'Barcode', 'description'=>'The barcode for the user.'),
          'roles' => array('property'=>'roles', 'type'=>'multiSelect', 'listStyle' =>'checkbox', 'values'=>$roleList, 'label'=>'Roles', 'description'=>'A list of roles that the user has.'),
		);

		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}

	function getFilters(){
		require_once 'sys/Administration/Role.php';
		$roleList = Role::getLookup();
		$roleList[-1] = 'Any Role';
		return array(
		array('filter'=>'role', 'type'=>'enum', 'values'=>$roleList, 'label'=>'Role'),
		array('filter'=>'cat_password', 'type'=>'text', 'label'=>'Login'),
		array('filter'=>'cat_username', 'type'=>'text', 'label'=>'Name'),
		);
	}

	function hasRatings(){
		require_once 'Drivers/marmot_inc/UserRating.php';

		$rating = new UserRating();
		$rating->userid = $this->id;
		$rating->find();
		if ($rating->N > 0){
			return true;
		}else{
			return false;
		}
	}
}
