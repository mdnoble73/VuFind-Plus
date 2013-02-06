<?php
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class LocationFacetSetting extends DB_DataObject {
	public $__table = 'location_facet_setting';    // table name
	public $id;                      //int(25)
	public $locationId;
	public $displayName;                    //varchar(255)
	public $facetName;
	public $weight;
	public $numEntriesToShowByDefault; //
	public $showAsDropDown;   //True or false
	public $sortMode;         //0 = alphabetically, 1 = by number of results
	public $showAboveResults;
	public $showInResults;
	public $showInAuthorResults;
	public $showInAdvancedSearch;

	public function getAvailableFacets(){
		$availableFacets = array(
			"institution" => "Library System",
			"building" => "Branch",
			"available_at" => "Available At",
			"collection_group" => "Collection",
			"collection_adams" => "Collection (ASU)",
			"collection_msc" => "Collection (CMU)",
			"collection_western" => "Collection (Western)",
			"rating_facet" => "Rating",
			"publishDate" => "Publication Year",
			"format" => "Format",
			"format_category" => "Format Category",
			"econtent_device" => "Compatible Device",
			"econtent_source" => "E-Content Collection",
			"econtent_protection_type" => "E-Content Protection",
			"topic_facet" => "Topics",
			"target_audience" => "Audience",
			"mpaa_rating" => "Movie Rating",
			"literary_form" => "Form",
			"authorStr" => "Author",
			"language" => "Language",
			"genre_facet" => "Genre",
			"era" => "Era",
			"geographic_facet" => "Region",
			"target_audience_full" => "Reading Level",
			"literary_form_full" => "Literary Form",
			"lexile_code" => "Lexile Code",
			"lexile_score" => "Lexile Score",
			"itype" => "Item Type",
			"time_since_added" => "Added In The Last",
			"callnumber-first" => "LC Call Number",
			"awards_facet" => "Awards",
			"detailed_location" => "Detailed Location",
		);
		asort($availableFacets);
		return $availableFacets;
	}

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

		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of this association'),
			'locationId' => array('property'=>'locationId', 'type'=>'enum', 'values'=>$locationList, 'label'=>'Location', 'description'=>'The id of a location'),
			'weight' => array('property'=>'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order of the book store', 'default' => 0),
			'facetName' => array('property'=>'facetName', 'type'=>'enum', 'label'=>'Facet', 'values' => LibraryFacetSetting::getAvailableFacets(), 'description'=>'The facet to include'),
			'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'The full name of the facet for display to the user'),
			'numEntriesToShowByDefault' => array('property'=>'numEntriesToShowByDefault', 'type'=>'integer', 'label'=>'Num Entries', 'description'=>'The number of values to show by default.', 'default' => '5'),
			'showAsDropDown' => array('property' => 'showAsDropDown', 'type' => 'checkbox', 'label' => 'Drop Down?', 'description'=>'Whether or not the facets should be shown in a drop down list', 'default'=>'0'),
			'sortMode' => array('property'=>'sortMode', 'type'=>'enum', 'label'=>'Sort', 'values' => array('alphabetically' => 'Alphabetically', 'num_results' => 'By number of results'), 'description'=>'How the facet values should be sorted.', 'default'=>'num_results'),
			'showAboveResults' => array('property' => 'showAboveResults', 'type' => 'checkbox', 'label' => 'Show Above Results', 'description'=>'Whether or not the facets should be shown above the results', 'default'=>0),
			'showInResults' => array('property' => 'showInResults', 'type' => 'checkbox', 'label' => 'Show on Results Page', 'description'=>'Whether or not the facets should be shown in regular search results', 'default'=>1),
			'showInAuthorResults' => array('property' => 'showInAuthorResults', 'type' => 'checkbox', 'label' => 'Show for Author Searches', 'description'=>'Whether or not the facets should be shown when searching by author', 'default'=>1),
			'showInAdvancedSearch' => array('property' => 'showInAdvancedSearch', 'type' => 'checkbox', 'label' => 'Show on Advanced Search', 'description'=>'Whether or not the facet should be an option on the Advanced Search Page', 'default'=>1),
		);
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