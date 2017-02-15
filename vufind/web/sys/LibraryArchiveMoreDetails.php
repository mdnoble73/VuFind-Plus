<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 2/15/2017
 *
 */
class LibraryArchiveMoreDetails extends DB_DataObject{
	public $__table = 'library_archive_more_details';
	public $id;
	public $libraryId;
	public $section;
	public $collapseByDefault;
	public $weight;

	static $moreDetailsOptions = array(
  'Description',
  'Transcription',
  'Correspondence information',
  'Research Information',
  'Related Objects',
  'Obituaries',
  'Burial Details',
  'Context Notes',
  'Related People',
  'Related Organizations',
  'Related Places',
  'Related Events',
  'Education',
  'Military Service',
  'Notes',
  'Subject',
  'Acknowledgements',
  'Links',
  'More Details',
  'Rights Statements',
  'Staff View',
);

	static function getObjectStructure(){
		global $user;
		//Load Libraries for lookup values
		$library = new Library();
		$library->orderBy('displayName');
		if ($user->hasRole('libraryAdmin')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = array();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		$structure = array(
			'id'                => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'),
			'libraryId'         => array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'A link to the library which the location belongs to'),
			'section'           => array('property'=>'section', 'type'=>'enum', 'label'=>'Section', 'values' => array_combine(self::$moreDetailsOptions, self::$moreDetailsOptions), 'description'=>'The section to display'),
			'collapseByDefault' => array('property'=>'collapseByDefault', 'type'=>'checkbox', 'label'=>'Collapse By Default', 'description'=>'Whether or not the section should be collapsed by default', 'default' => true),
			'weight'            => array('property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines how lists are sorted within the accordion.  Lower weights are displayed to the left of the screen.', 'required'=> true),
		);
		return $structure;
	}

//	function getEditLink(){
//		return '';
//	}

	static function getDefaultOptions($libraryId = -1){
		$defaultOptions = array();

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Description';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Transcription';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Correspondence information';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Research Information';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Related Objects';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Obituaries';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Burial Details';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Context Notes';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Related People';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Related Organizations';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Related Places';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Related Events';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Education';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Military Service';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Notes';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Subject';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Acknowledgements';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Links';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'More Details';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Rights Statements';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

		$defaultOption = new LibraryArchiveMoreDetails();
		$defaultOption->libraryId = $libraryId;
		$defaultOption->section = 'Staff View';
		$defaultOption->collapseByDefault = false;
		$defaultOption->weight = count($defaultOptions) + 101;
		$defaultOptions[] = $defaultOption;

//		$defaultOption = new LibraryArchiveMoreDetails();
//		$defaultOption->libraryId = $libraryId;
//		$defaultOption->section = '';
//		$defaultOption->collapseByDefault = false;
//		$defaultOption->weight = count($defaultOptions) + 101;
//		$defaultOptions[] = $defaultOption;

	return $defaultOptions;
	}




}