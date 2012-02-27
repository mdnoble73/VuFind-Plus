<?php
/**
 * Table Definition for bad words
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class BadWord extends DB_DataObject 
{
    public $__table = 'bad_words';    // table name
    public $id;                      //int(11)
    public $word;                    //varchar(50)
    public $replacement;             //varchar(50)
   
    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('BadWord',$k,$v); }
    
    function keys() {
        return array('id', 'word');
    }
    
    function getBadWordExpressions(){
        $badWordsList = array();
        $this->find();
        if ($this->N){
            while ($this->fetch()){
                 $quotedWord = preg_quote(trim($this->word));
                 //$badWordExpression = '/^(?:.*\W)?(' . preg_quote(trim($badWord->word)) . ')(?:\W.*)?$/';
                 $badWordsList[] = "/^$quotedWord(?=\W)|(?<=\W)$quotedWord(?=\W)|(?<=\W)$quotedWord$|^$quotedWord$/i";
            }
        }
        return $badWordsList;
    }

}