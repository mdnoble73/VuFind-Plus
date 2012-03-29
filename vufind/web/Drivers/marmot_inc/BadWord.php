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
		global $memcache;
		global $configArray;
		global $timer;
		$badWordsList = $memcache->get('bad_words_list');
		if ($badWordsList == false){
			$badWordsList = array();
			$this->find();
			if ($this->N){
				while ($this->fetch()){
					$quotedWord = preg_quote(trim($this->word));
					//$badWordExpression = '/^(?:.*\W)?(' . preg_quote(trim($badWord->word)) . ')(?:\W.*)?$/';
					$badWordsList[] = "/^$quotedWord(?=\W)|(?<=\W)$quotedWord(?=\W)|(?<=\W)$quotedWord$|^$quotedWord$/i";
				}
			}
			$timer->logTime("Loaded bad words");
			$memcache->set('bad_words_list', $badWordsList, 0, $configArray['Caching']['bad_words_list']);
		}
		return $badWordsList;
	}

}