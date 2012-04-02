<?php
/**
 * Table Definition for spelling words
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class SpellingWord extends DB_DataObject
{
	public $__table = 'spelling_words';    // table name
	public $word;                    //varchar(50)
	public $commonality;             //int(11)
	 
	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('BadWord',$k,$v); }

	function keys() {
		return array('word');
	}

	function getSpellingSuggestions($word){
		//Get suggestions, giving a little boost to words starting with what has been typed so far.
		$query = "SELECT word, commonality FROM spelling_words WHERE soundex = SOUNDEX('" . mysql_escape_string($word) . "') OR word like '" . mysql_escape_string($word) . "%' ORDER BY commonality, word LIMIT 10";
		$this->query($query);
		$suggestions = array();
		while ($this->fetch()){
			$suggestions[] = $this->word;
		}
		return $suggestions;
	}

}