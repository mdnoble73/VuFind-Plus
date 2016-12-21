<?php
/**
 * Table Definition for Materials Request
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class MaterialsRequest extends DB_DataObject
{
	public $__table = 'materials_request';   // table name

	// Note: if table column names are changed, data for class MaterialsRequestFieldsToDisplay will need updated.
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
	public $assignedTo;



	//Dynamic properties setup by joins
	public $numRequests;
	public $description;
	public $userId;
	public $firstName;
	public $lastName;

	function keys() {
		return array('id');
	}

	static function getFormats(){
		require_once ROOT_DIR . '/sys/MaterialsRequestFormats.php';
		$availableFormats = array();
		$customFormats = new MaterialsRequestFormats();
		global $user;
		if (isset($user)) {
			$homeLibrary = $user->getHomeLibrary();
			if (isset($homeLibrary)) {
				$customFormats->libraryId = $homeLibrary->libraryId;

				if ($customFormats->count() == 0 ) {
					// Default Formats to use when no custom formats are created.
					$availableFormats = array(
						'book'       => translate('Book'),
						'largePrint' => translate('Large Print'),
						'dvd'        => translate('DVD'),
						'bluray'     => translate('Blu-ray'),
						'cdAudio'    => translate('CD Audio Book'),
						'cdMusic'    => translate('Music CD'),
						'ebook'      => translate('eBook'),
						'eaudio'     => translate('eAudio'),
						'playaway'   => translate('Playaway'),
						'article'    => translate('Article'),
						'cassette'   => translate('Cassette'),
						'vhs'        => translate('VHS'),
						'other'      => translate('Other'),
					);

					global $configArray;
					foreach ($availableFormats as $key => $label){
						if (isset($configArray['MaterialsRequestFormats'][$key]) && $configArray['MaterialsRequestFormats'][$key] == false){
							unset($availableFormats[$key]);
						}
					}

				} else {
					$availableFormats = $customFormats->fetchAll('format', 'formatLabel');
				}
			}
		}

		return $availableFormats;
	}

	static $materialsRequestEnabled = null;
	static function enableMaterialsRequest($forceReload = false){
		if (MaterialsRequest::$materialsRequestEnabled != null && $forceReload == false){
			return MaterialsRequest::$materialsRequestEnabled;
		}
		global $configArray;
		global $user;
		global $library;

		//First make sure we are enabled in the config file
		if (isset($configArray['MaterialsRequest']) && isset($configArray['MaterialsRequest']['enabled'])){
			$enableMaterialsRequest = $configArray['MaterialsRequest']['enabled'];
			//Now check if the library allows material requests
			if ($enableMaterialsRequest){
				if (isset($library) && $library->enableMaterialsRequest == 0){
					$enableMaterialsRequest = false;
				}else if ($user){
					$homeLibrary = Library::getPatronHomeLibrary();
					if (is_null($homeLibrary)){
						$enableMaterialsRequest = false;
					}else if ($homeLibrary->enableMaterialsRequest == 0){
						$enableMaterialsRequest = false;
					}else if (isset($library) && $homeLibrary->libraryId != $library->libraryId){
						$enableMaterialsRequest = false;
					}else if (isset($configArray['MaterialsRequest']['allowablePatronTypes'])){
						//Check to see if we need to do additional restrictions by patron type
						$allowablePatronTypes = $configArray['MaterialsRequest']['allowablePatronTypes'];
						if (strlen($allowablePatronTypes) > 0){
							if (!preg_match("/^$allowablePatronTypes$/i", $user->patronType)){
								$enableMaterialsRequest = false;
							}
						}
					}
				}
			}
		}else{
			$enableMaterialsRequest = false;
		}
		MaterialsRequest::$materialsRequestEnabled = $enableMaterialsRequest;
		return $enableMaterialsRequest;
	}

	function getHoldLocationName($locationId) {
		require_once ROOT_DIR . '/Drivers/marmot_inc/Location.php';
		$holdLocation = new Location();
		if ($holdLocation->get($locationId)) {
			return $holdLocation->holdingBranchLabel;
		}
		return false;
	}

}
