<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class ReadingHistoryEntry extends DB_DataObject 
{
	public $__table = 'user_reading_history';   // table name
	public $id;
	public $userId;				//int(11)
	public $resourceId;			//int(11)
	public $firstCheckoutDate;  //date
	public $lastCheckoutDate;   //date
  	public $daysCheckedOut;		//int(11)
	
	/* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('ReadingHistoryEntry',$k,$v); }
    
	function keys() {
	    return array('id');
 	}
}
