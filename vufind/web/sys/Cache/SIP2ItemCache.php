<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class SIP2ItemCache extends DB_DataObject 
{
    public $__table = 'sip2_item_cache';    // table name
    public $barcode;                    //varchar(20)
    public $holdQueueLength;                    //int(16)
    public $duedate;
    public $cacheDate;         //timestamp
    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('sip2_item_cache',$k,$v); }
    
    function __construct(){
    	//remove any records that have been cached for more than 10 minutes
    	$cacheExpirationTime = time() - 60 * 10;
    	$this->query("DELETE FROM sip2_item_cache where cacheDate < $cacheExpirationTime");
    }
    
    function keys() {
        return array('barcode');
    }

}