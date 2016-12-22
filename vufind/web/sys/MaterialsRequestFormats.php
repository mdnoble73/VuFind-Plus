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
		  'specialFields' => array('property' => 'specialFields', 'type' => 'multiSelect', 'listStyle' => 'checkboxList', 'label' => 'Special Fields for Format', 'description' => 'Any Special Fields to use with this format', 'values' => self::$materialsRequestFormatsSpecialFieldOptions)
			//			'libraryId' => array(), // hidden value or internally updated.

		);
		return $structure;
	}

	static function getDefaultMaterialRequestFormats($libraryId = -1) {
		$defaultFormats = array();

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'book';
		$defaultFormat->formatLabel = translate('Book');
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = 'Abridged/Unabridged'; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'largePrint';
		$defaultFormat->formatLabel = translate('Large Print');
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = 'Abridged/Unabridged'; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'dvd';
		$defaultFormat->formatLabel = translate('DVD');
		$defaultFormat->authorLabel = 'Actor / Director';
		$defaultFormat->specialFields = 'Season'; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'bluray';
		$defaultFormat->formatLabel = translate('Blu-ray');
		$defaultFormat->authorLabel = 'Actor / Director';
		$defaultFormat->specialFields = 'Season'; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'cdAudio';
		$defaultFormat->formatLabel = translate('CD Audio Book');
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = ''; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'cdMusic';
		$defaultFormat->formatLabel = translate('Music CD');
		$defaultFormat->authorLabel = 'Artist / Composer';
		$defaultFormat->specialFields = ''; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'ebook';
		$defaultFormat->formatLabel = translate('eBook');
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = 'Ebook format'; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'eaudio';
		$defaultFormat->formatLabel = translate('eAudio');
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = 'Eaudio format'; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'playaway';
		$defaultFormat->formatLabel = translate('Playaway');
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = ''; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'article';
		$defaultFormat->formatLabel = translate('Article');
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = 'Article Field'; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'other';
		$defaultFormat->formatLabel = 'Other';
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = ''; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

//		$defaultFormat = new MaterialsRequestFormats();
//		$defaultFormat->libraryId = $libraryId;
//		$defaultFormat->format = '';
//		$defaultFormat->formatLabel = '';
//		$defaultFormat->authorLabel = 'Author';
//		$defaultFormat->specialFields = ''; // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
//		$defaultFormat->weight = count($defaultFormats) + 1;
//		$defaultFormats[] = $defaultFormat;

		return $defaultFormats;
	}

	public function fetch(){
		$return = parent::fetch();
		if ($return) {
				$this->specialFields = empty($this->specialFields) ? null : explode(',', $this->specialFields);
		}
		return $return;
	}

	public function insert() {
		if (is_array($this->specialFields)) {
			$this->specialFields = implode(',', $this->specialFields);
		}
		return parent::insert();
	}

	public function update($dataObject = false) {
		if (is_array($this->specialFields)) {
			$this->specialFields = implode(',', $this->specialFields);
		}
		return parent::update($dataObject);
	}
}