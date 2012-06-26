<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
require_once 'sys/ListWidgetList.php';

class ListWidget extends DB_DataObject
{
	public $__table = 'list_widgets';    // table name
	public $id;                      //int(25)
	public $name;                    //varchar(255)
	public $description;                    //varchar(255)
	public $showTitleDescriptions;
	public $onSelectCallback;
	public $fullListLink;
	public $customCss;
	public $listDisplayType;
	public $showMultipleTitles;
	public $autoRotate;
	
	private $lists; //varchar(500)
	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('ListWidget',$k,$v); }

	function keys() {
		return array('id');
	}

	function getObjectStructure(){
		global $configArray;
		$structure = array(
      'id' => array(
        'property'=>'id', 
        'type'=>'hidden', 
        'label'=>'Id', 
        'description'=>'The unique id of the list widget file.',
        'primaryKey' => true,
        'storeDb' => true, 
      ),
      'name' => array(
        'property'=>'name', 
        'type'=>'text', 
        'label'=>'Name', 
        'description'=>'The name of the widget.',
        'maxLength' => 255,
        'size' => 100,
        'serverValidation' => 'validateName',
        'storeDb' => true, 
      ),
      'description' => array(
        'property'=>'description', 
        'type'=>'textarea', 
        'rows' => 3,
        'cols'=> 80,
        'label'=>'Description', 
        'description'=>'A descrption for the widget', 
        'storeDb' => true, 
      ),
      'showTitleDescriptions' => array(
        'property' => 'showTitleDescriptions',
        'type' => 'checkbox',
        'label' => 'Should the description pop-up be shown when hovering over titles?',
        'storeDb' => true,
        'default' => true,
      ),
      'showMultipleTitles' => array(
        'property' => 'showMultipleTitles',
        'type' => 'checkbox',
        'label' => 'Should multiple titles by shown in in the widget or should only one title be shown at a time?',
        'storeDb' => true,
        'default' => true,
      ),
      'autoRotate' => array(
        'property' => 'autoRotate',
        'type' => 'checkbox',
        'label' => 'Should the widget automatically rotate between titles?',
        'storeDb' => true,
      ),
      'onSelectCallback' => array(
        'property'=>'onSelectCallback', 
        'type'=>'text', 
        'label'=>'On Select Callback', 
        'description'=>'A javascript callback to invoke when a title is selected to override the default behavior.', 
        'storeDb' => true, 
      ),
      'customCss' => array(
        'property'=>'customCss', 
        'type'=>'url', 
        'label'=>'Custom CSS File', 
        'maxLength' => 255,
        'size' => 100,
        'description'=>'The URL to an external css file to be included when rendering as an iFrame.', 
        'storeDb' => true, 
        'required' => false,
      ),
      'listDisplayType' => array(
        'property'=>'listDisplayType', 
        'type'=>'enum', 
        'values' => array(
          'tabs' => 'Tabbed Display',
          'dropdown' => 'Drop Down List'
        ),
        'label'=>'Display lists as', 
        'description'=>'The URL to an external css file to be included wen rendering as an iFrame.', 
        'storeDb' => true, 
      ),
      'lists' => array(
        'property' => 'lists',
        'type'=> 'oneToMany',
        'keyThis' => 'id',
        'keyOther' => 'listWidgetId',
        'subObjectType' => 'ListWidgetList',
        'structure' => ListWidgetList::getObjectStructure(),
        'label' => 'Lists',
        'description' => 'The lists to be displayed within the widget.',
        'sortable' => true,
        'storeDb' => true, 
        'serverValidation' => 'validateLists',
      	'editLink' => 'ListWidgetsListsLinks'
      ),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}

	function validateName(){
		//Setup validation return array
		$validationResults = array(
      'validatedOk' => true,
      'errors' => array(),
		);

		//Check to see if the name is unique
		$query = "SELECT * FROM list_widgets WHERE name='" . mysql_escape_string($this->name) . "' and id != '{$this->id}'";
		$result = mysql_query($query);
		if (mysql_numrows($result) > 0){
			//The title is not unique
			$validationResults['errors'][] = "This widget has already been created.  Please select another name.";
		}
		//Make sure there aren't errors
		if (count($validationResults['errors']) > 0){
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;
	}

	public function __get($name){
		if ($name == "lists") {
			if (!isset($this->lists)){
				//Get the list of lists that are being displayed for the widget
				$this->lists = array();
				$listWidgetList = new ListWidgetList();
				$listWidgetList->listWidgetId = $this->id;
				$listWidgetList->orderBy('weight ASC');
				$listWidgetList->find();
				while($listWidgetList->fetch()){
					$this->lists[$listWidgetList->id] = clone($listWidgetList);
				}
			}
			return $this->lists;
		}
	}
	public function __set($name, $value){
		if ($name == "lists") {
			$this->lists = $value;
		}
	}

	/**
	 * Override the update functionality to save the associated lists
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(){
		$ret = parent::update();
		if ($ret === FALSE ){
			return $ret;
		}else{
			$this->saveLists();
		}
	}

	/**
	 * Override the update functionality to save the associated lists
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		$ret = parent::insert();
		if ($ret === FALSE ){
			return $ret;
		}else{
			$this->saveLists();
		}
	}

	public function saveLists(){
		if (isset ($this->lists)){
			foreach ($this->lists as $list){
				if (isset($list->deleteOnSave) && $list->deleteOnSave == true){
					$list->delete();
				}else{
					if (isset($list->id) && is_numeric($list->id)){
						$list->update();
					}else{
						$list->listWidgetId = $this->id;
						$list->insert();
					}
				}
			}
			//Clear the lists so they are reloaded the next time
			unset($this->lists);
		}
	}
	
	public function validateLists(){
		//Setup validation return array
		$validationResults = array(
      'validatedOk' => true,
      'errors' => array(),
		);

		$listNames = array();
		require_once 'services/API/ListAPI.php';
		$listAPI = new ListAPI();
		$allListIds = $listAPI->getAllListIds();
			
		foreach ($this->lists as $list){
			//Check to make sure that all list names are unique
			if (in_array($list->name, $listNames)){
				$validationResults['errors'][] = "This name {$list->name} was used mulitple times.  Please make sure that each name is unique.";
			}
			$listNames[] = $list->name;
			
			//Check to make sure that each list source is valid
			$source = $list->source;
			//The source is valid if it is in the all lists array or if it starts with strands: or review:
			if (preg_match('/^(strands:|review:|search:).*/', $source)){
				//source is valid
			}elseif (in_array($source, $allListIds)){
				//source is valid
			}else{
				//source is not valid
				$validationResults['errors'][] = "This source {$list->source} is not valid.  Please enter a valid list source.";
			}
		}
		
		//Make sure there aren't errors
		if (count($validationResults['errors']) > 0){
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;
	}
}