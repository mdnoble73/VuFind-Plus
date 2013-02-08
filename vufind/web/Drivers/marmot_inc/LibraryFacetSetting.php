<?php
require_once 'Drivers/marmot_inc/FacetSetting.php';

class LibraryFacetSetting extends FacetSetting {
	public $__table = 'library_facet_setting';    // table name
	public $libraryId;

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

		$structure = super::getObjectStructure();
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