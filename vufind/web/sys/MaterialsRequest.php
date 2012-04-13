<?php
/**
 * Table Definition for Materials Request
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class MaterialsRequest extends DB_DataObject
{
	public $__table = 'materials_request';   // table name
	public $id;
	public $title;
	public $season;
	public $magazineTitle;
	public $magazineDate;
	public $magazineVolume;
	public $magazineNumber;
	public $magazinePageNumbers;
	public $author;
	public $format;
	public $subFormat;
	public $ageLevel;
	public $bookType;
	public $isbn;
	public $upc;
	public $issn;
	public $oclcNumber;
	public $publisher;
	public $publicationYear;
	public $abridged;
	public $about;
	public $comments;
	public $status;
	public $phone;
	public $email;
	public $dateCreated;
	public $createdBy;
	public $dateUpdated;
	public $emailSent;
	public $holdsCreated;
	public $placeHoldWhenAvailable;
	public $illItem;
	public $holdPickupLocation;
	public $bookmobileStop;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('MaterialsRequest',$k,$v); }

	function keys() {
		return array('id');
	}

	static function getFormats(){
		$availableFormats = array(
			'book' => translate('Book'),
 			'largePrint' => translate('Large Print'),
			'dvd' => translate('DVD'),
			'bluray' => translate('Blu-ray'),
			'cdAudio' => translate('CD Audio Book'),
			'cdMusic' => translate('Music CD'),
			'ebook' => translate('eBook'),
			'eaudio' => translate('eAudio'),
			'playaway' => translate('Playaway'),
			'article' => translate('Article'),
			'cassette' => translate('Cassette'),
			'vhs' => translate('VHS'),
 			'other' => translate('Other'),
		);

		global $configArray;
		foreach ($availableFormats as $key => $label){
			if (isset($configArray['MaterialsRequestFormats'][$key]) && $configArray['MaterialsRequestFormats'][$key] == false){
				unset($availableFormats[$key]);
			}
		}

		return $availableFormats;
	}

	static function enableMaterialsRequest(){
		global $configArray;
		global $user;
		global $library;
		if (isset($configArray['MaterialsRequest']) && isset($configArray['MaterialsRequest']['enabled'])){
			$enableMaterialsRequest = $configArray['MaterialsRequest']['enabled'];
			if ($enableMaterialsRequest && isset($library) && $library->enableMaterialsRequest == 0){
				$enableMaterialsRequest = false;
			}elseif ($enableMaterialsRequest && isset($configArray['MaterialsRequest']['allowablePatronTypes'])){
				//Check to see if we need to do additonal restrictions by patron type
				$allowablePatronTypes = $configArray['MaterialsRequest']['allowablePatronTypes'];
				if (strlen($allowablePatronTypes) > 0 && $user){
					if (!preg_match("/^$allowablePatronTypes$/i", $user->patronType)){
						$enableMaterialsRequest = false;
					}
				}
			}
		}else{
			$enableMaterialsRequest = false;
		}
		return $enableMaterialsRequest;
	}
}
