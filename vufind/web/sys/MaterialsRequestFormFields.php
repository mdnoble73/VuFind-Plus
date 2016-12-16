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
		'text'    => 'text',
		'textbox' => 'textbox',
		'yes/no'  => 'yes/no',
		'format'  => 'Format *',
		'author'  => 'Author *',
		'ageLevel' => 'Age Level *',
	  'isbn'    => 'ISBN *',
		'oclcNumber' => 'OCLC Number',



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

}