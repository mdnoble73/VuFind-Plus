<?php

/**
 * A Customizable section of the catalog that can be browsed within
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 1/25/14
 * Time: 10:04 AM
 */

class BrowseCategory extends  DB_DataObject{
	public $__table = 'browse_category';
	public $id;
	public $textId;  //A textual id to make it easier to transfer browse categories between systems

	public $userId; //The user who created the browse category
	public $sharing; //Who to share with (Private, Location, Library, Everyone)

	public $label; //A label for the browse category to be shown in the browse category listing
	public $description; //A description of the browse category

	public $searchTerm;
	public $defaultFilter;
	public $defaultSort;

	public $catalogScoping;

	public $numTimesShown;
	public $numTitlesClickedOn;

	function __construct(){

	}
	static function getObjectStructure(){

		global $user;
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of this association'),
			'label' => array('property'=>'label', 'type'=>'text', 'label'=>'Label', 'description'=>'The label to show to the user', 'maxLength'=>50),
			'textId' => array('property'=>'textId', 'type'=>'text', 'label'=>'textId', 'description'=>'A textual id to identify the category', 'serverValidation'=>'validateTextId', 'maxLength'=>50),
			'userId' => array('property'=>'userId', 'type'=>'label', 'label'=>'userId', 'description'=>'The User Id who created this category', 'default'=> $user->id),
			'sharing' => array('property'=>'sharing', 'type'=>'enum', 'values' => array('private' => 'Just Me', 'location' => 'My Home Branch', 'library' => 'My Home Library', 'everyone' => 'Everyone'), 'label'=>'Share With', 'description'=>'Who the category should be shared with', 'default' =>'everyone'),
			'description' => array('property'=>'description', 'type'=>'html', 'label'=>'Description', 'description'=>'A description of the category.', 'hideInLists' => true),
			'catalogScoping' => array('property'=>'catalogScoping', 'type'=>'enum', 'label'=>'Catalog Scoping', 'values' => array('unscoped' => 'Unscoped', 'library' => 'Current Library', 'location' => 'Current Location'), 'description'=>'What scoping should be used for this search scope?.', 'default'=>'unscoped'),
			'searchTerm' => array('property'=>'searchTerm', 'type'=>'text', 'label'=>'Search Term', 'description'=>'A default search term to apply to the category', 'default'=>'', 'hideInLists' => true),
			'defaultFilter' => array('property'=>'defaultFilter', 'type'=>'textarea', 'label'=>'Default Filter(s)', 'description'=>'Filters to apply to the search by default.', 'hideInLists' => true, 'rows' => 3, 'cols'=>80),
			'defaultSort' => array('property' => 'defaultSort', 'type' => 'enum', 'label' => 'Default Sort', 'values' => array('relevance' => 'Best Match', 'popularity' => 'Popularity', 'newest_to_oldest' => 'Newest First', 'oldest_to_newest' => 'Oldest First', 'author' => 'Author', 'title' => 'Title', 'user_rating' => 'Rating'), 'description'=>'The default sort for the search if none is specified', 'default'=>'relevance', 'hideInLists' => true),
			'numTimesShown' => array('property'=>'numTimesShown', 'type'=>'label', 'label'=>'Times Shown', 'description'=>'The number of times this category has been shown to users'),
			'numTitlesClickedOn' => array('property'=>'numTitlesClickedOn', 'type'=>'label', 'label'=>'Titles Clicked', 'description'=>'The number of times users have clicked on titles within this category'),
		);

		foreach ($structure as $fieldName => $field){
			if (isset($field['property'])){
				$field['propertyOld'] = $field['property'] . 'Old';
				$structure[$fieldName] = $field;
			}
		}
		return $structure;
	}

	function validateTextId(){
		//Setup validation return array
		$validationResults = array(
			'validatedOk' => true,
			'errors' => array(),
		);

		if (!$this->textId || strlen($this->textId) == 0){
			$this->textId = $this->label . ' ' . $this->sharing;
			if ($this->sharing == 'private'){
				$this->textId .= '_' . $this->userId;
			}elseif ($this->sharing == 'location'){
				$location = Location::getUserHomeLocation();
				$this->textId .= '_' . $location->code;
			}elseif ($this->sharing == 'library'){
				global $library;
				$this->textId .= '_' . $library->getPatronHomeLibrary()->subdomain;
			}

		}

		//First convert the text id to all lower case
		$this->textId = strtolower($this->textId);

		//Next convert any non word characters to _
		$this->textId = preg_replace('/\W/', '_', $this->textId);

		//Make sure the length is less than 50 characters
		if (strlen($this->textId) > 50){
			$this->textId = substr($this->textId, 0, 50);
		}

		//Now check if it is unique

		return $validationResults;
	}

	public function getSolrSort() {
		if ($this->defaultSort == 'relevance'){
			return 'relevance';
		}elseif ($this->defaultSort == 'popularity'){
			return 'popularity desc';
		}elseif ($this->defaultSort == 'newest_to_oldest'){
			return 'days_since_added asc';
		}elseif ($this->defaultSort == 'oldest_to_newest'){
			return 'days_since_added desc';
		}elseif ($this->defaultSort == 'author'){
			return 'author,title';
		}elseif ($this->defaultSort == 'title'){
			return 'title,author';
		}elseif ($this->defaultSort == 'user_rating'){
			return 'rating desc,title';
		}else{
			return 'relevance';
		}
	}

	/**
	 * @param SearchObject_Solr $searchObj
	 *
	 * @return boolean
	 */
	public function updateFromSearch($searchObj) {
		//Search terms
		$searchTerms = $searchObj->getSearchTerms();
		if (is_array($searchTerms)){
			if (count($searchTerms) > 1){
				return false;
			}else{
				if ($searchTerms[0]['index'] == 'Keyword'){
					$this->searchTerm = $searchTerms[0]['lookfor'];
				}else{
					$this->searchTerm = $searchTerms[0]['index'] . ':' . $searchTerms[0]['lookfor'];
				}
			}
		}else{
			$this->searchTerm = $searchTerms;
		}

		//Default Filter
		$filters = $searchObj->getFilterList();
		$formattedFilters = '';
		foreach ($filters as $filter){
			if (strlen($formattedFilters) > 0){
				$formattedFilters .= "\r\n";
			}
			$formattedFilters .= $filter[0]['field'] . ':' . $filter[0]['value'];
		}

		//Default sort
		$solrSort = $searchObj->getSort();
		if ($solrSort == 'relevance'){
			$this->defaultSort = 'relevance';
		}elseif ($solrSort == 'popularity desc'){
			$this->defaultSort = 'popularity';
		}elseif ($solrSort == 'days_since_added asc'){
			$this->defaultSort = 'newest_to_oldest';
		}elseif ($solrSort == 'days_since_added desc'){
			$this->defaultSort = 'oldest_to_newest';
		}elseif ($solrSort == 'author,title'){
			$this->defaultSort = 'author';
		}elseif ($solrSort == 'title,author'){
			$this->defaultSort = 'title';
		}elseif ($solrSort == 'rating desc,title'){
			$this->defaultSort = 'user_rating';
		}else{
			$this->defaultSort = 'relevance';
		}
		return true;

	}
} 