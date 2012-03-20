<?php
/**
 * Table Definition for Materials Request
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class MaterialsRequestStatus extends DB_DataObject {
	public $__table = 'materials_request_status';   // table name

	public $id;
	public $description;
	public $isDefault;
	public $sendEmailToPatron;
	public $emailTemplate;
	public $isOpen;
	public $isPatronCancel;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('MaterialsRequestStatus',$k,$v); }

	function keys() {
		return array('id');
	}

	function getObjectStructure(){
		$structure = array(
          'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the libary within the database'),
          'description' => array('property'=>'description', 'type'=>'text', 'size' => 80, 'label'=>'Description', 'description'=>'A unique name for the Status'),
          'isDefault' => array('property'=>'isDefault', 'type'=>'checkbox', 'label'=>'Is Default', 'description'=>'Whether or not this status is the default status to apply to new requests'),
          'sendEmailToPatron' => array('property'=>'sendEmailToPatron', 'type'=>'checkbox', 'label'=>'Send Email To Patron', 'description'=>'Whether or not an email should be sent to the patron when this status is set'),
          'emailTemplate' => array('property'=>'emailTemplate', 'type'=>'textarea', 'rows' => 6, 'cols' => 60, 'label'=>'Email Template', 'description'=>'The template to use when sending emails to the user', 'hideInLists' => true),
          'isOpen' => array('property'=>'isOpen', 'type'=>'checkbox', 'label'=>'Is Open', 'description'=>'Whether or not this status needs further processing'),
		      'isPatronCancel' => array('property'=>'isPatronCancel', 'type'=>'checkbox', 'label'=>'Is Patron Cancel', 'description'=>'Whether or not this status should be set when the patron cancels their request'),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}
}