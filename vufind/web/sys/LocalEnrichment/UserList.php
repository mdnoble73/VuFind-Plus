<?php
/**
 * Table Definition for user_list
 */
require_once 'DB/DataObject.php';

class UserList extends DB_DataObject
{
	###START_AUTOCODE
	/* the code below is auto generated do not remove the above tag */

	public $__table = 'user_list';												// table name
	public $id;															// int(11)	not_null primary_key auto_increment
	public $user_id;													// int(11)	not_null multiple_key
	public $title;														// string(200)	not_null
	public $description;											// string(500)
	public $created;													// datetime(19)	not_null binary
	public $public;													// int(11)	not_null
	public $deleted;
	public $dateUpdated;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('UserList',$k,$v); }

	/* the code above is auto generated do not remove the tag below */
	###END_AUTOCODE

	function title(){
		return $this->title;
	}
	function title_proper(){
		return $this->title;
	}
	function title_sort(){
		return $this->title;
	}
	function format_category(){
		return '';
	}
	function format(){
		return 'List';
	}
	function bib_suppression(){
		if ($this->public == 1){
			return "notsuppressed";
		}else{
			return "suppressed";
		}
	}
	function institution(){
		//Get the user home library
		$user = new User();
		$user->id = $this->user_id;
		$user->find(true);

		//home library
		$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);
		$institutions = array();
		$institutions[] = $homeLibrary->facetLabel;

		return $institutions;
	}
	function building(){
		//Get the user home library
		$user = new User();
		$user->id = $this->user_id;
		$user->find(true);

		//get the home location
		$homeLocation = new Location();
		$homeLocation->locationId = $user->homeLocationId;
		$homeLocation->find(true);

		//If the user is scoped to just see holdings for their location, only make the list available for that location
		//unless the user a library admin
		$scopeToLocation = false;
		if ($homeLocation->useScope == 1 && $homeLocation->restrictSearchByLocation){
			if ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin')){
				$scopeToLocation = false;
			}else{
				$scopeToLocation = true;
			}
		}

		$buildings = array();
		if ($scopeToLocation){
			//publish to all locations
			$buildings[] = $homeLocation->facetLabel;
		}else{
			//publish to all locations for the library
			$location = new Location();
			$location->libraryId = $homeLocation->libraryId;
			$location->find();
			while ($location->fetch()){
				$buildings[] = $location->facetLabel;
			}
		}
		return $buildings;
	}
	function format_boost(){
		return 100;
	}
	function language_boost(){
		return 500;
	}
	function getObjectStructure(){
		$structure = array(
			'id' => array(
				'property'=>'id',
				'type'=>'hidden',
				'label'=>'Id',
				'primaryKey'=>true,
				'description'=>'The unique id of the e-pub file.',
				'storeDb' => true,
				'storeSolr' => false,
			),
			'recordtype' => array(
				'property'=>'recordtype',
				'type'=>'method',
				'methodName'=>'recordtype',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'solrId' => array(
				'property'=>'id',
				'type'=>'method',
				'methodName'=>'solrId',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'title' => array(
				'property' => 'title',
				'type' => 'text',
				'size' => 100,
				'maxLength'=>255,
				'label' => 'Title',
				'description' => 'The title of the item.',
				'required'=> true,
				'storeDb' => true,
				'storeSolr' => true,
			),
			'title_proper' => array(
				'property' => 'title_proper',
				'type' => 'method',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'title_sort' => array(
				'property' => 'title_sort',
				'type' => 'method',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'format_category' => array(
				'property' => 'format_category',
				'type' => 'method',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'format' => array(
				'property' => 'format',
				'type' => 'method',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'description' => array(
				'property' => 'description',
				'type' => 'textarea',
				'label' => 'Description',
				'rows'=>3,
				'cols'=>80,
				'description' => 'A brief description of the file for indexing and display if there is not an existing record within the catalog.',
				'required'=> false,
				'storeDb' => true,
				'storeSolr' => true,
			),
			'num_titles' => array(
				'property' => 'num_titles',
				'type' => 'method',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'num_holdings' => array(
				'property' => 'num_holdings',
				'type' => 'method',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'format_boost' => array(
				'property' => 'format_boost',
				'type' => 'method',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'language_boost' => array(
				'property' => 'language_boost',
				'type' => 'method',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'contents' => array(
				'property' => 'contents',
				'type' => 'method',
				'required'=> false,
				'storeDb' => false,
				'storeSolr' => true,
			),
			'bib_suppression' => array(
				'property' => 'bib_suppression',
				'type' => 'method',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'institution' => array(
				'property'=>'institution',
				'type'=>'method',
				'methodName'=>'institution',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'building' => array(
				'property'=>'building',
				'type'=>'method',
				'methodName'=>'building',
				'storeDb' => false,
				'storeSolr' => true,
			),
			'usable_by' => array(
				'property'=>'usable_by',
				'type'=>'method',
				'methodName'=>'usable_by',
				'storeDb' => false,
				'storeSolr' => true,
			)
		);

		//Add local formats
		$library = new Library();
		$library->find();
		while ($library->fetch() == true){
			$structure['format_' . $library->subdomain] = array(
				'property' => 'format_' . $library->subdomain,
				'type' => 'method',
				'methodName' => 'format',
				'storeDb' => false,
				'storeSolr' => true,
			);
		}

		$location = new Location();
		$location->find();
		while ($location->fetch() == true){
			$structure['format_' . $location->code] = array(
				'property' => 'format_' . $location->code,
				'type' => 'method',
				'methodName' => 'format',
				'storeDb' => false,
				'storeSolr' => true,
			);
		}

		return $structure;
	}
	function contents(){
		$resources = $this->getListTitles();
		$contents = '';
		foreach ($resources as $resource){
			$contents .= ' ' . $resource->title . ' ' . (isset($resource->author) ? $resource->author : '') ;
		}
		return $contents;
	}
	function num_titles(){
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		//Join with grouped work to make sure we only load valid entries
		$listEntry = new UserListEntry();
		$listEntry->listId = $this->id;

		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$listEntry->joinAdd($groupedWork);
		$listEntry->find();

		return $listEntry->N;
	}
	function num_holdings(){
		return count($this->getListTitles());
	}
	function insert(){
		$this->created = time();
		$this->dateUpdated = time();
		parent::insert();
	}
	function update(){
		if ($this->created == 0){
			$this->created = time();
		}
		$this->dateUpdated = time();
		parent::update();
	}
	function delete(){
		$this->deleted = 1;
		$this->dateUpdated = time();
		parent::update();
	}

	/**
	 * @var array An array of resources keyed by the list id since we can iterate over multiple lists while fetching from the DB
	 */
	private $listTitles = array();

	function getListEntries(){
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->listId = $this->id;
		$listEntries = array();
		$listEntry->find();
		while ($listEntry->fetch()){
			$listEntries[] = $listEntry->groupedWorkPermanentId;
		}
		return $listEntries;
	}

	/**
	 * @return UserListEntry[]|null
	 */
	function getListTitles()
	{
		if (isset($this->listTitles[$this->id])){
			return $this->listTitles[$this->id];
		}
		$listTitles = array();

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->listId = $this->id;
		$listEntry->find();

		while ($listEntry->fetch()){
			$cleanedEntry = $this->cleanListEntry(clone($listEntry));
			if ($cleanedEntry != false){
				$listTitles[] = $cleanedEntry;
			}
		}

		$this->listTitles[$this->id] = $listTitles;
		return $this->listTitles[$this->id];
	}

	var $catalog;

	/**
	 * @param UserListEntry $listEntry - The resource to be cleaned
	 * @return UserListEntry|bool
	 */
	function cleanListEntry($listEntry){
		global $configArray;
		global $user;

		// Connect to Database
		$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);

		//Filter list information for bad words as needed.
		if ($user == false || $this->user_id != $user->id){
			//Load all bad words.
			global $library;
			require_once(ROOT_DIR . '/Drivers/marmot_inc/BadWord.php');
			$badWords = new BadWord();
			$badWordsList = $badWords->getBadWordExpressions();

			//Determine if we should censor bad words or hide the comment completely.
			$censorWords = true;
			if (isset($library)) $censorWords = $library->hideCommentsWithBadWords == 0 ? true : false;
			if ($censorWords){
				//Filter Title
				$titleText = $this->title;
				foreach ($badWordsList as $badWord){
					$titleText = preg_replace($badWord, '***', $titleText);
				}
				$this->title = $titleText;
				//Filter description
				$descriptionText = $this->description;
				foreach ($badWordsList as $badWord){
					$descriptionText = preg_replace($badWord, '***', $descriptionText);
				}
				$this->description = $descriptionText;
				//Filter notes
				$notesText = $listEntry->notes;
				foreach ($badWordsList as $badWord){
					$notesText = preg_replace($badWord, '***', $notesText);
				}
				$this->description = $notesText;
			}else{
				//Check for bad words in the title or description
				$titleText = $this->title;
				if (isset($listEntry->description)){
					$titleText .= ' ' . $listEntry->description;
				}
				//Filter notes
				$titleText .= ' ' . $listEntry->notes;
				foreach ($badWordsList as $badWord){
					if (preg_match($badWord,$titleText)){
						return false;
					}
				}
			}
		}
		return $listEntry;
	}

	/**
	 * @param String $workToRemove
	 */
	function removeListEntry($workToRemove)
	{
		// Remove the Saved List Entry
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->groupedWorkPermanentId = $workToRemove;
		$listEntry->listId = $this->id;
		$listEntry->delete();

		unset($this->listTitles[$this->id]);
	}

	/**
		* remove all resources within this list
		*/
	function removeAllListEntries($tags = null){
		$allListEntries = $this->getListTitles($tags);
		foreach ($allListEntries as $listEntry){
			$this->removeListEntry($listEntry);
		}
	}
	function usable_by(){
		return 'all';
	}
}