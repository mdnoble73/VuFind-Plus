<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class NovelistCache extends DB_DataObject 
{
    public $__table = 'novelist_cache';    // table name
    public $isbn;                    //varchar(20)
    public $enrichmentInfo;             //mediumText
    public $cacheDate;         //timestamp
    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('novelist_cache',$k,$v); }
    
    function keys() {
        return array('isbn');
    }

}
?>