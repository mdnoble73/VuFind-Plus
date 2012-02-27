<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class UserSuggestion extends DB_DataObject 
{
    public $__table = 'user_suggestions';   // table name
    public $suggestionId;                //int(11)
    public $name;                 //varchar(50)
    public $email;            //varchar(100)
    public $suggestion;               //mediumText
    public $enteredOn;              //timestamp
    public $hide;              //tinyint(4)
    public $internalNotes;              //mediumText
    
    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('UserSuggestion',$k,$v); }
    
    function keys() {
        return array('suggestionId');
    }
    
}