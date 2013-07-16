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
	public $libraryId;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('MaterialsRequestStatus',$k,$v); }

	function keys() {
		return array('id');
	}

	function getObjectStructure(){
		global $user;
		$library = new Library();
		$library->orderBy('displayName');
		if ($user->hasRole('library_material_requests')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}else{
			$libraryList[-1] = 'Default';
		}
		$library->find();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		$structure = array(
          'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the libary within the database'),
          'description' => array('property'=>'description', 'type'=>'text', 'size' => 80, 'label'=>'Description', 'description'=>'A unique name for the Status'),
          'isDefault' => array('property'=>'isDefault', 'type'=>'checkbox', 'label'=>'Default Status?', 'description'=>'Whether or not this status is the default status to apply to new requests'),
					'isPatronCancel' => array('property'=>'isPatronCancel', 'type'=>'checkbox', 'label'=>'Set When Patron Cancels?', 'description'=>'Whether or not this status should be set when the patron cancels their request'),
					'isOpen' => array('property'=>'isOpen', 'type'=>'checkbox', 'label'=>'Open Status?', 'description'=>'Whether or not this status needs further processing'),
          'sendEmailToPatron' => array('property'=>'sendEmailToPatron', 'type'=>'checkbox', 'label'=>'Send Email To Patron?', 'description'=>'Whether or not an email should be sent to the patron when this status is set'),
          'emailTemplate' => array('property'=>'emailTemplate', 'type'=>'textarea', 'rows' => 6, 'cols' => 60, 'label'=>'Email Template', 'description'=>'The template to use when sending emails to the user', 'hideInLists' => true),
					'libraryId' => array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'The id of a library'),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}
}