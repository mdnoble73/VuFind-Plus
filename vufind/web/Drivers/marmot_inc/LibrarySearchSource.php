<?php
/**
 * Information about searches for a particular location
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/13/13
 * Time: 10:42 AM
 */
require_once 'SearchSource.php';
class LibrarySearchSource extends SearchSource {
	public $__table = 'library_search_source';

	public $libraryId;

	static function getObjectStructure(){
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
		return '/Admin/LibrarySearchSources?objectAction=edit&id=' . $this->id;
	}
}