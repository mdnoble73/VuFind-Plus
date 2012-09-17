<?php
/**
 * Table Definition for EContentHold
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
require_once 'sys/SolrDataObject.php';

class EContentAvailability extends DB_DataObject {
	public $__table = 'econtent_availability';    // table name
	public $id;
	public $recordId;
	public $copiesOwned;
	public $availableCopies;
	public $numberOfHolds;
	public $onOrderCopies;
	public $libraryId;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('EContentAvailability',$k,$v); }

	function keys() {
		return array('id');
	}

	function getLibraryName(){
		if ($this->libraryId == -1){
			return 'Shared Digital Collection';
		}else{
			$library = new Library();
			$library->libraryId = $this->libraryId;
			$library->find(true);
			return $library->displayName;
		}
	}
}