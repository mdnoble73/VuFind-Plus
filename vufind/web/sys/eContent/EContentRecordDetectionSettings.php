<?php
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
require_once 'sys/eContent/EContentItem.php';
require_once 'sys/eContent/EContentRecord.php';

class EContentRecordDetectionSettings extends DB_DataObject {
	public $__table = 'econtent_record_detection_settings';    // table name
	public $id;
	public $fieldSpec;
	public $valueToMatch;
	public $source;
	public $accessType;
	public $item_type;
	public $add856FieldsAsExternalLinks;
	
	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('EContentRecordDetectionSettings',$k,$v); }

	function keys() {
		return array('id');
	}
	
	function getObjectStructure(){
		$itemTypes = EContentItem::getExternalItemTypes();
		$accessTypes = EContentRecord::getValidAccessTypes();
		
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the detection settings within the database'),
			'fieldSpec' => array('property'=>'fieldSpec', 'type'=>'text', 'label'=>'Field Specification', 'description'=>'The marc record field to test to see if the record should be treated as eContent uses the same format as marc_local.properties.', 'size' => '20'),
			'valueToMatch' => array('property'=>'valueToMatch', 'type'=>'text', 'label'=>'Value To Match', 'description'=>'The value to match to see if the record should be treated as eContent.  Regular expressions are allowed.', 'size' => '60'),
			'source' => array('property'=>'source', 'type'=>'text', 'label'=>'Source', 'description'=>'The source to set for the record.', 'size' => '20'),
			'accessType' => array('property'=>'accessType', 'type'=>'enum', 'label'=>'Access Type', 'values'=>$accessTypes, 'description'=>'The type to apply to any items that are generated.'),
			'add856FieldsAsExternalLinks' => array('property'=>'add856FieldsAsExternalLinks', 'type'=>'checkbox', 'label'=>'Create Links?', 'description'=>'Whether or not automatic external links should be generated based on the 856 tag.'),
			'item_type' => array('property'=>'item_type', 'type'=>'enum', 'label'=>'Item Type', 'values'=>$itemTypes, 'description'=>'The type to apply to any items that are generated.'),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}
}