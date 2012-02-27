<?php
/**
 * Table Definition for bad words
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class UserRating extends DB_DataObject 
{
    public $__table = 'user_rating';    // table name
    public $id;                       //int(11)
    public $userid;                   //int(11)
    public $resourceid;               //int(11)
    public $rating;                   //int(5)
   
    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('UserRating',$k,$v); }
    
    function keys() {
        return array('id', 'userid', 'resourceid');
    }
    
    

}