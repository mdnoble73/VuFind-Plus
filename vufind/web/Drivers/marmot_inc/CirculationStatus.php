<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class CirculationStatus extends DB_DataObject
{
	public $__table = 'circulation_status';   // table name
	public $circulationStatusId;				//int(11)
	public $millenniumName;					//varchar(25)
	public $displayName;			//varchar(40)
	public $holdable;				//tinyint(4)
	public $available;	            //tinyint(4)

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('CirculationStatus',$k,$v); }

	function keys() {
		return array('circulationStatusId');
	}
	function getObjectStructure(){
		$structure = array(
          'millenniumName' => array('property'=>'millenniumName', 'type'=>'text', 'label'=>'Millennium Name', 'description'=>'The name of the status as it displays in the Millennium holdings list'),
          'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'A name to translate the status into for display in vufind. Leave blank to use the Millennium name.'),
          'holdable' => array('property'=>'holdable', 'type'=>'checkbox', 'label'=>'Holdable', 'description'=>'Whether or not patrons can place holds on items with this status'),
          'available' => array('property'=>'available', 'type'=>'checkbox', 'label'=>'Available', 'description'=>'Whether or not the item is available for immediate usage (if the patron is at that branch)'),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}
}