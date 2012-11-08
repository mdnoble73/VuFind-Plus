<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class PType extends DB_DataObject
{
	public $__table = 'ptype';   // table name
	public $pType;				//int(11)
	public $maxHolds;			//int(11)

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('PType',$k,$v); }

	function keys() {
		return array('id');
	}

	function getObjectStructure(){
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the p-type within the database', 'hideInLists' => true),
			'pType' => array('property'=>'pType', 'type'=>'integer', 'label'=>'P-Type', 'description'=>'The P-Type for the patron'),
			'maxHolds' => array('property'=>'maxHolds', 'type'=>'integer', 'label'=>'Max Holds', 'description'=>'The maximum holds that a patron can have.', 'default' => 300),
		);
		foreach ($structure as $fieldName => $field){
			if (isset($field['property'])){
				$field['propertyOld'] = $field['property'] . 'Old';
				$structure[$fieldName] = $field;
			}
		}
		return $structure;
	}
}