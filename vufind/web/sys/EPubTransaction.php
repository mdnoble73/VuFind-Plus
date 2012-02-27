<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class EPubTransaction extends DB_DataObject 
{
    public $__table = 'epub_transaction';    // table name
    public $id;                      //int(25)
    public $userId;
    public $recordId;
    public $itemId;
    public $userAcsId;
    public $downloadUrl;
    public $transaction;
    public $timeLinkGenerated;
    public $timeFulfilled;
    public $timeReturned;
    
    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('epub_files',$k,$v); }
    
    function keys() {
        return array('id', 'userId', 'userAcsId');
    }

}
?>