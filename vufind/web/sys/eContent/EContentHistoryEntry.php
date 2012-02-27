<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class EContentHistoryEntry extends DB_DataObject 
{
	public $__table = 'econtent_history';   // table name
	public $id;
	public $userId;				//int(11)
	public $recordId;			//int(11)
	public $openDate;  //date
	public $lastRead;  //date
	public $lastPage;  //The last page read
	public $action; //The action performed (read online, download, checkout, check-in, place hold)
	public $accessType; //0 = free, 1 = ACS protected, 2 = single usage
	public $type;
	
	/* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('EContentHistoryEntry',$k,$v); }
    
	function keys() {
	    return array('userHistoryId', 'userId', 'resourceId');
 	}
}
