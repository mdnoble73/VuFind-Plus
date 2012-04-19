<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class ReindexLogEntry extends DB_DataObject 
{
	public $__table = 'reindex_log';   // table name
	public $id;
	public $startTime;
	public $endTime;
	
	/* Static get */
  function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('ReindexLogEntry',$k,$v); }
    
	function keys() {
	    return array('id');
 	}
}
