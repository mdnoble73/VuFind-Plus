<?php
/**
 * Table Definition for marriage
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class Marriage extends DB_DataObject
{
	public $__table = 'marriage';    // table name
	public $marriageId;
	public $personId;
	public $spouseName;
	public $spouseId;
	public $marriageDate;
	public $comments;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Marriage',$k,$v); }

	function keys() {
		return array('marriageId');
	}

	function id(){
		return $this->marriageId;
	}

	function label(){
		return $this->spouseName . (isset($this->marriageDate) ? (' - ' . $this->marriageDate) : '');
	}

	function getObjectStructure(){
		$structure = array(
		array('property'=>'marriageId', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the marriage in the database', 'storeDb' => true),
		array('property'=>'personId', 'type'=>'hidden', 'label'=>'Person Id', 'description'=>'The id of the person this marriage is for', 'storeDb' => true),
		//array('property'=>'person', 'type'=>'method', 'label'=>'Person', 'description'=>'The person this obituary is for', 'storeDb' => false),
		array('property'=>'spouseName', 'type'=>'text', 'maxLength'=>100, 'label'=>'Spouse', 'description'=>'The spouse&apos;s name.', 'storeDb' => true),
		array('property'=>'marriageDate', 'type'=>'partialDate', 'label'=>'Date', 'description'=>'The date of the marriage.', 'storeDb' => true, 'propNameMonth'=>'marriageDateMonth', 'propNameDay'=>'marriageDateDay', 'propNameYear'=>'marriageDateYear'),
		array('property'=>'comments', 'type'=>'textarea', 'rows'=>10, 'cols'=>80, 'label'=>'Comments', 'description'=>'Information about the marriage.', 'storeDb' => true, 'hideInLists'=>true),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}

	function insert(){
		parent::insert();
		//Load the person this is for, and update solr
		if ($this->personId){
			require_once 'sys/Genealogy/Person.php';
			$person = Person::staticGet('personId', $this->personId);
			$person->saveToSolr();
		}
	}

	function update(){
		parent::update();
		//Load the person this is for, and update solr
		if ($this->personId){
			require_once 'sys/Genealogy/Person.php';
			$person = Person::staticGet('personId', $this->personId);
			$person->saveToSolr();
		}
	}

	function delete(){
		$personId = $this->personId;
		$ret = parent::delete();
		//Load the person this is for, and update solr
		if ($personId){
			require_once 'sys/Genealogy/Person.php';
			$person = Person::staticGet('personId', $personId);
			$person->saveToSolr();
		}
		return $ret;
	}
}