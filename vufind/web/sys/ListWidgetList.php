<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class ListWidgetList extends DB_DataObject
{
	public $__table = 'list_widget_lists';    // table name
	public $id;                      //int(25)
	public $listWidgetId;                    //varchar(255)
	public $name;
	public $displayFor;
	public $source;                    //varchar(255)
	public $fullListLink;
	public $weight;
	
	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('DB_DataObject',$k,$v); }

	function keys() {
		return array('id');
	}

	static function getObjectStructure(){
		global $configArray;
		$structure = array(
      'id' => array(
        'property'=>'id', 
        'type'=>'label', 
        'label'=>'Id', 
        'description'=>'The unique id of the list widget file.'
      ),
      'listWidgetId' => array(
      	'property' => 'listWidgetId',
      	'type' => 'foreignKey',
      	'label' => 'List Widget Id',
      	'description' => 'The widget this list is associated with.' 
      ),
      'name' => array(
        'property'=>'name', 
        'type'=>'text', 
        'label'=>'Name', 
        'description'=>'The name of the list to display in the tab.',
      	'required' => true,
      ),
      'displayFor' => array(
      	'property' => 'displayFor',
      	'type' => 'enum',
      	'values' => array('all'=> 'Everyone', 'loggedIn' => 'Only when a user is logged in', 'notLoggedIn' => 'Only when no one is logged in'),
      	'label' => 'Display For',
      	'description' => 'Who this list should be displayed for.'
      ),
      'source' => array(
        'property'=>'source', 
        'type'=>'text', 
        'label'=>'Source', 
        'description'=>'The source of the list.', 
        'serverValidation' => 'validateSource',
        'required'=> true
      ),
      'fullListLink' => array(
        'property'=>'fullListLink', 
        'type'=>'text', 
        'label'=>'Full List Link', 
        'description'=>'The URL to see the entire list (optional).', 
        'required'=> false
      ),
      
      'weight' => array(
      	'property' => 'weight',
      	'type' => 'numeric',
      	'label' => 'Weight',
      	'weight' => 'Defines how lists are sorted within the widget.  Lower weights are displayed to the left of the screen.',
      	'required'=> true
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
    
    //TODO: Check to see if the name is unique
     
    //Make sure there aren't errors
    if (count($validationResults['errors']) > 0){
      $validationResults['validatedOk'] = false;
    }
    return $validationResults;
  }
  
  function __toString(){
  	return "{$this->name} ($this->source)";
  }
}