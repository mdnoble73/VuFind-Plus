<?php
/**
 * Created by PhpStorm.
 * User: mnoble
 * Date: 11/17/2017
 * Time: 4:01 PM
 */

require_once ROOT_DIR . '/Drivers/marmot_inc/CombinedResultSection.php';
class LocationCombinedResultSection extends CombinedResultSection{
	public $__table = 'location_combined_results_section';    // table name
	public $locationId;

	static function getObjectStructure(){
		$location = new Location();
		$location->orderBy('displayName');
		if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('libraryManager')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$location->libraryId = $homeLibrary->libraryId;
		}
		$location->find();
		while ($location->fetch()){
			$locationList[$location->locationId] = $location->displayName;
		}

		$structure = parent::getObjectStructure();
		$structure['locationId'] = array('property'=>'locationId', 'type'=>'enum', 'values'=>$locationList, 'label'=>'Location', 'description'=>'The id of a location');

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