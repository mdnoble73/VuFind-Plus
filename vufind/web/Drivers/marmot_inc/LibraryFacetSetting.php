<?php
require_once 'Drivers/marmot_inc/FacetSetting.php';

class LibraryFacetSetting extends FacetSetting {
	public $__table = 'library_facet_setting';    // table name
	public $libraryId;

	function getObjectStructure(){
		global $user;
		$library = new Library();
		$library->orderBy('displayName');
		if ($user->hasRole('libraryAdmin')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		$structure = parent::getObjectStructure();
		$structure['libraryId'] = array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'The id of a library');

		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}

	function getEditLink(){
		return '/Admin/LibraryFacetSettings?objectAction=edit&id=' . $this->id;
	}
}