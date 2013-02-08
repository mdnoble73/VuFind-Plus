<?php
require_once 'Drivers/marmot_inc/FacetSetting.php';

class LocationFacetSetting extends FacetSetting {
	public $__table = 'location_facet_setting';    // table name
	public $locationId;

	function getObjectStructure(){
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

		$structure = super::getObjectStructure();
		$structure['locationId'] = array('property'=>'locationId', 'type'=>'enum', 'values'=>$locationList, 'label'=>'Location', 'description'=>'The id of a location');

		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}

	function getEditLink(){
		return '/Admin/LocationFacetSettings?objectAction=edit&id=' . $this->id;
	}
}