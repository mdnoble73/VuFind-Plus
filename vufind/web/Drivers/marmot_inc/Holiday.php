<?php
/**
 * Table Definition for LocationHours.
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class Holiday extends DB_DataObject
{
	public $__table = 'holiday';   // table name
	public $id;                    // int(11)  not_null primary_key auto_increment
	public $date;                  // date
	public $name;                  // varchar(100)
	
	/* Static get */
	function staticGet($k,$v=NULL) {
		return DB_DataObject::staticGet('Holiday',$k,$v);
	}
	
	function keys() {
		return array('date');
	}

	function getObjectStructure(){
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the holiday within the database'),
			'date' => array('property'=>'date', 'type'=>'text', 'label'=>'Date', 'description'=>'The date of a holiday.'),
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Holiday Name', 'description'=>'The name of a holiday')
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}
}