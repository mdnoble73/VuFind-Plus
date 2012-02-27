<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

/**
 * Caches the formatted results of Lists for display within the title scrollers.  
 * 
 * @author Mark Noble
 *
 */
class ListCache2 extends DB_DataObject 
{
    public $__table = 'list_cache2';    // table name
    public $listName;                    //varchar(20)
    public $scrollerName;                    //varchar(20)
    public $jsonData;                    //int(16)
    public $cacheDate;         //timestamp
    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('list_cache',$k,$v); }

    function __construct(){
      //rebuild the cached list every 2 hours.
      $cacheExpirationTime = time() - 60 * 60 * 2;
      $this->query("DELETE FROM list_cache2 where cacheDate < $cacheExpirationTime");
    }
    
    function keys() {
        return array('listName');
    }

}