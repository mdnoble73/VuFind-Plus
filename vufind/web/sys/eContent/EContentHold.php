<?php
/**
 * Table Definition for EContentHold
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
require_once ROOT_DIR . '/sys/SolrDataObject.php';

class EContentHold extends DB_DataObject {
	public $__table = 'econtent_hold';    // table name
	public $id;
	public $recordId;
	public $itemId;
	public $datePlaced;
	public $dateUpdated;
	public $userId;
	public $status; //Active, Suspended, Cancelled, Filled 
	public $reactivateDate;
	public $title;
	public $author;
	
	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('econtent_hold',$k,$v); }

	function keys() {
		return array('id', 'userId');
	}
}