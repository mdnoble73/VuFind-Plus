<?php
/**
 * Table Definition for administrators
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class Role extends DB_DataObject
{
	public $__table = 'roles';// table name
	public $roleId;                        //int(11)
	public $name;                     //varchar(50)
	public $description;              //varchar(100)

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Role',$k,$v); }

	function keys() {
		return array('roleId');
	}

	function getObjectStructure(){
		$structure = array(
          'roleId' => array('property'=>'roleId', 'type'=>'label', 'label'=>'Role Id', 'description'=>'The unique id of the role within the database'),
          'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'maxLength'=>50, 'description'=>'The full name of the role.'),
          'description' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'maxLength'=>100, 'description'=>'The full name of the role.'),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}

	static function getLookup(){
		$role = new Role();
		$role->orderBy('name');
		$role->find();
		$roleList = array();
		while ($role->fetch()){
			$roleList[$role->roleId] = $role->name . ' - ' . $role->description;
		}
		return $roleList;
	}
}