<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class ListCache extends DB_DataObject 
{
    public $__table = 'list_cache';    // table name
    public $listName;                    //varchar(20)
    public $jsonData;                    //int(16)
    public $cacheDate;         //timestamp
    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('list_cache',$k,$v); }

    function __construct(){
      //remove any records that have been cached for more than 6 hours
      $cacheExpirationTime = time() - 60 * 60 * 6;
      $this->query("DELETE FROM list_cache where cacheDate < $cacheExpirationTime");
    }
    
    function keys() {
        return array('listName');
    }

}