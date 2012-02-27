<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class EContentWishList extends DB_DataObject 
{
	public $__table = 'econtent_wishlist';   // table name
	public $id;
	public $userId;     //int(11)
	public $recordId;   //int(11)
	public $dateAded;   //date
	public $status;     //date
	
	/* Static get */
  function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('EContentWishList',$k,$v); }
    
	function keys() {
	    return array('id', 'userId', 'recordId');
 	}
}
