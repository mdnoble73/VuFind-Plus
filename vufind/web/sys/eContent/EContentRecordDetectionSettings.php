<?php
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class EContentRecordDetectionSettings extends DataObject {
	public $__table = 'econtent_record_detection_settings';    // table name
	public $id;
	public $fieldSpec;
	public $valueToMatch;
	public $source;
	public $item_type;
	public $add856FieldsAsExternalLinks;
	
/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('EContentRecordDetectionSettings',$k,$v); }

	function keys() {
		return array('id');
	}
	
	function getObjectStructure(){
		$itemTypes = array();
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the detection settings within the database'),
			'fieldSpec' => array('property'=>'fieldSpec', 'type'=>'text', 'label'=>'Field Specification', 'description'=>'The marc record field to test to see if the record should be treated as eContent uses the same format as marc_local.properties.'),
			'valueToMatch' => array('property'=>'valueToMatch', 'type'=>'text', 'label'=>'Value To Match', 'description'=>'The value to match to see if the record should be treated as eContent.  Regular expressions are allowed.'),
			'source' => array('property'=>'source', 'type'=>'text', 'label'=>'Source', 'description'=>'The source to set for the record.'),
			'item_type' => array('property'=>'source', 'type'=>'text', 'label'=>'Source', 'description'=>'The source to set for the record.'),
		);
	}
}