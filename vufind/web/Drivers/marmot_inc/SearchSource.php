<?php
/**
 * Information about what should be searched within the catalog
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/13/13
 * Time: 10:41 AM
 */

class SearchSource extends DB_DataObject{
	public $id;
	public $label;
	public $weight;
	public $searchWhat; //Catalog, Genealogy, WorldCat, OverDrive, Gold Rush

	public $defaultFilter;
	public $defaultSort;

	public $catalogScoping;

	function __construct(){

	}
	function init($label, $defaultFilter, $defaultSort, $catalogScoping){
		$this->label = $label;
		$this->defaultFilter = $defaultFilter;
		$this->defaultSort = $defaultSort;
		$this->catalogScoping = $catalogScoping;
	}
	static function getObjectStructure(){

		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of this association'),
			'weight' => array('property'=>'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order of the book store', 'default' => 0),
			'label' => array('property'=>'label', 'type'=>'text', 'label'=>'Label', 'description'=>'The label to show to the user'),
			'searchWhat' => array('property'=>'searchWhat', 'type'=>'enum', 'label'=>'Search What?', 'values' => array('catalog' => 'Catalog', 'genealogy' => 'Genealogy', 'tags' => 'Tags', 'overdrive' => 'OverDrive', 'worldcat' => 'World Cat', 'prospector' => 'Prospector', 'goldrush' => 'Gold Rush'), 'description'=>'What source does this search use.', 'default'=>'Catalog'),
			'catalogScoping' => array('property'=>'catalogScoping', 'type'=>'enum', 'label'=>'Catalog Scoping', 'values' => array('unscoped' => 'Unscoped', 'library' => 'Current Library', 'location' => 'Current Location'), 'description'=>'What scoping should be used for this search scope?.', 'default'=>'unscoped'),
			'defaultFilter' => array('property'=>'defaultFilter', 'type'=>'textarea', 'label'=>'Default Filter(s)', 'description'=>'Filters to apply to the search by default.', 'hideInLists' => true),
			'defaultSort' => array('property' => 'defaultSort', 'type' => 'enum', 'label' => 'Default Sort', 'values' => array('relevance' => 'Best Match', 'popularity' => 'Popularity', 'newest_to_oldest' => 'Newest First', 'oldest_to_newest' => 'Oldest First', 'author' => 'Author', 'title' => 'Title', 'user_rating' => 'Rating'), 'description'=>'The default sort for the search if none is specified', 'default'=>'relevance'),
		);
		return $structure;
	}

}