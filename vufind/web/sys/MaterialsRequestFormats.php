<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 12/14/2016
 *
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
class MaterialsRequestFormats extends DB_DataObject
{
	public $__table = 'materials_request_formats';
	public $id;
	public $libraryId;
	public $format;
	public $formatLabel;
	public $authorLabel;
	public $specialFields;   // SET Data type, possible values : 'Abridged/Unabridged', 'Article Field', 'Eaudio format', 'Ebook format', 'Season'
	public $weight;

	static $materialsRequestFormatsSpecialFieldOptions = array(
		'Abridged/Unabridged', 'Article Field', 'Eaudio format', 'Ebook format', 'Season'
	);


	static function getObjectStructure() {
		$structure = array(
			'id'            => array('property' => 'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of this association'),
			'weight'        => array('property' => 'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order of rule', 'default' => 0),
			'format'        => array('property' => 'format', 'type' => 'text', 'label' => 'Format', 'description' => 'internal value for format, please use camelCase and no spaces ie. cdAudio'),
			'formatLabel'   => array('property' => 'formatLabel', 'type' => 'text', 'label' => 'Format Label', 'description' => 'Label for the format that will be displayed to users.'),
			'authorLabel'   => array('property' => 'authorLabel', 'type' => 'text', 'label' => 'Author Label', 'description' => 'Label for the author field associated with this format that will be displayed to users.'),
			//			'libraryId' => array(), // hidden value or internally updated.

		);
		return $structure;
	}

}
