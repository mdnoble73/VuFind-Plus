<?php
/**
 * Table Definition for LocationHours.
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class LocationHours extends DB_DataObject
{
	public $__table = 'location_hours';   // table name
	public $id;                           // int(11)  not_null primary_key auto_increment
	public $locationCode;                 // varchar(10)
	public $day;                          // int(11)
	public $open;                         // varchar(10)
	public $close;                        // varchar(10)
	
	/* Static get */
	function staticGet($k,$v=NULL) {
		return DB_DataObject::staticGet('LocationHours',$k,$v);
	}
	
	function keys() {
		return array('id');
	}

	function getObjectStructure(){
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'),
			'locationCode' => array('property'=>'locationCode', 'type'=>'text', 'label'=>'Location Code', 'description'=>'The unique location code.'),
			'day' => array('property'=>'day', 'type'=>'text', 'label'=>'Day of Week', 'description'=>'The day of the week 0 to 6 (0 = Sunday to 6 = Saturday)'),
			'open' => array('property'=>'open', 'type'=>'text', 'label'=>'Opening Hour', 'description'=>'The opening hour. Use 24 hour format HH:MM, eg: 08:30'),
			'close' => array('property'=>'close', 'type'=>'text', 'label'=>'Closing Hour', 'description'=>'The closing hour. Use 24 hour format HH:MM, eg: 16:30'),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}
}