<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class ReviewsCache extends DB_DataObject 
{
    public $__table = 'reviews_cache';    // table name
    public $recordId;              //varchar(20)
    public $reviewData;             //mediumText
    public $cacheDate;         //timestamp
    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('novelist_cache',$k,$v); }
    
    function keys() {
        return array('recordId');
    }

}
?>