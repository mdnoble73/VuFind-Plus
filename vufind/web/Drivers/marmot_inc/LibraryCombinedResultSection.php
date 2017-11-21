<?php
/**
 * Created by PhpStorm.
 * User: mnoble
 * Date: 11/17/2017
 * Time: 4:00 PM
 */

require_once ROOT_DIR . '/Drivers/marmot_inc/CombinedResultSection.php';
class LibraryCombinedResultSection extends CombinedResultSection{
	public $__table = 'library_combined_results_section';    // table name
	public $libraryId;

	static function getObjectStructure(){
		$library = new Library();
		$library->orderBy('displayName');
		if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('libraryManager')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = array();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		$structure = parent::getObjectStructure();
		$structure['libraryId'] = array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'The id of a library');

		//Alter the source as well
		global $library;
		$validResultSources = array('pika' => 'Pika Results');
		if ($library->edsApiProfile != ''){
			$validResultSources['eds'] = 'EBSCO EDS';
		}
		if ($library->enablePospectorIntegration){
			$validResultSources['prospector'] = 'Prospector';
		}
		if ($library->enableArchive){
			$validResultSources['archive'] = 'Digital Archive';
		}
		global $configArray;
		if ($configArray['DPLA']['enabled']){
			$validResultSources['dpla'] = 'DPLA';
		}
		$structure['source']['values'] = $validResultSources;

		return $structure;
	}
}