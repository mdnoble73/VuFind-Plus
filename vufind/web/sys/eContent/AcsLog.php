<?php
/**
 * Table Definition for EContentItem
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class AcsLog extends DB_DataObject {
	public $__table = 'acs_log';    // table name
	public $id;                      //int(25)
	public $acsTransactionId;
	public $userAcsId;
	public $transactionDate;
	public $fulfilled;
	public $returned;
	
	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('acs_log',$k,$v); }

	function keys() {
		return array('id');
	}
}