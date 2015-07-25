<?php
/**
 * Rules about which records to include in a scope
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/18/2015
 * Time: 10:31 AM
 */
require_once ROOT_DIR . '/sys/Indexing/RecordToInclude.php';
class LocationRecordToInclude extends RecordToInclude{
	public $__table = 'location_records_to_include';    // table name
	public $locationId;

	static function getObjectStructure(){
		global $user;
		$location = new Location();
		$location->orderBy('displayName');
		if ($user->hasRole('libraryAdmin')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$location->libraryId = $homeLibrary->libraryId;
		}
		$location->find();
		while ($location->fetch()){
			$locationList[$location->locationId] = $location->displayName;
		}

		$structure = parent::getObjectStructure();
		$structure['locationId'] = array('property'=>'locationId', 'type'=>'enum', 'values'=>$locationList, 'label'=>'Location', 'description'=>'The id of a location');

		return $structure;
	}
}