<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 12/16/2016
 *
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
class MaterialsRequestFormFields extends DB_DataObject
{
	public $__table = 'materials_request_form_fields';
	public $id;
	public $libraryId;
	public $weight;
	public $formCategory;
	public $fieldLabel; // unique
	public $fieldType;

	static $fieldTypeOptions = array(
//		'text'    => 'text',
//		'textbox' => 'textarea',
//		'yes/no'  => 'yes/no',
		'id'      => 'Request ID Number',
		'title'   => 'Title',
		'author'  => 'Author',
		'format'  => 'Format',
		'ageLevel' => 'Age Level',
	  'isbn'     => 'ISBN',
		'oclcNumber' => 'OCLC Number',
		'publisher'  => 'Publisher',
		'publicationYear' => 'Publication Year',
//		'articleInfo' => 'Article Information',
//		'abridged' => 'abridged',
		'about'    => 'About',
		'comments' => 'Comments',
		'status'   => 'Status',
		'dateCreated' => 'Date Created',
		'createdBy'   => 'Created By',
		'dateUpdated' => 'dateUpdated',
		'libraryCardNumber' => 'Library Card Number',
		'emailSent'   => 'Email Sent',
		'holdsCreated' => 'Holds Created',
	  'email'  => 'Email',
		'phone'  => 'Phone',
		'upc' => 'UPC',
		'issn' => 'ISSN',
		'bookType' => 'Book Type', // TODO
	  'subFormat' => 'Sub-format', // TODO
		'placeHoldWhenAvailable' => 'Place Hold when Available',
		'holdPickupLocation' => 'Hold Pick-up Location',
		'bookmobileStop' => 'Bookmobile Stop', // kept with hold pick up location
		'illItem'        => 'Inter-library Loan Item',
		'assignedTo'     => 'Assigned To'
	);


	static function getObjectStructure() {
		$structure = array(
			'id'            => array('property' => 'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of this association'),
//			'libraryId'     => array(), // hidden value or internally updated.
			'formCategory'  => array('property' => 'formCategory', 'type' => 'text', 'label' => 'Form Category', 'description' => 'The name of the section this field will belong in.'),
			'fieldLabel'    => array('property' => 'fieldLabel', 'type' => 'text', 'label' => 'Field Label', 'description' => 'Label for this field that will be displayed to users.'),
			'fieldType'     => array('property' => 'fieldType', 'type' => 'enum', 'label' => 'Field Type', 'description' => 'Type of data this field will be', 'values' => self::$fieldTypeOptions, 'default' => 'text'),
			//			'required'      => array(), // checkbox
			'weight'        => array('property' => 'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order of rule', 'default' => 0),
		);
		return $structure;
	}


	static function getDefaultFormFields($libraryId = -1) {
		$defaultFieldsToDisplay = array();

		//This Replicates MyRequest Form structure.

		// Title Information
		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Material Information';
		$defaultField->fieldLabel = 'Format';
		$defaultField->fieldType = 'format';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Title Information';
		$defaultField->fieldLabel = 'Title';
		$defaultField->fieldType = 'title';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Title Information';
		$defaultField->fieldLabel = 'Author';
		$defaultField->fieldType = 'author';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

		// Hold Options
		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Hold Options';
		$defaultField->fieldLabel = 'Place a hold for me when the item is available';
		$defaultField->fieldType = 'placeHoldWhenAvailable';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Hold Options';
		$defaultField->fieldLabel = 'Pick-up Location';
		$defaultField->fieldType = 'holdPickupLocation';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Hold Options';
		$defaultField->fieldLabel = 'Do you want us to borrow from another library if not purchased?';
		$defaultField->fieldType = 'illItem';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;


		// Supplemental Details (optional)
		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'Age Level';
		$defaultField->fieldType = 'ageLevel';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'Type';
		$defaultField->fieldType = 'bookType';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'Publisher';
		$defaultField->fieldType = 'publisher';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'Publication Year';
		$defaultField->fieldType = 'publicationYear';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'How and/or where did you hear about this title';
		$defaultField->fieldType = 'about';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'Comments';
		$defaultField->fieldType = 'comments';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;


		// Contact Information
		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Contact Information';
		$defaultField->fieldLabel = 'Email';
		$defaultField->fieldType = 'email';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Contact Information';
		$defaultField->fieldLabel = 'Phone';
		$defaultField->fieldType = 'phone';
		$defaultField->weight = count($defaultFieldsToDisplay)+1;
		$defaultFieldsToDisplay[] = $defaultField;

//		$defaultField = new MaterialsRequestFormFields();
//		$defaultField->libraryId = $libraryId;
//		$defaultField->formCategory = '';
//		$defaultField->fieldLabel = '';
//		$defaultField->fieldType = '';
//		$defaultField->weight = count($defaultFieldsToDisplay)+1;
//		$defaultFieldsToDisplay[] = $defaultField;

		return $defaultFieldsToDisplay;

	}

}