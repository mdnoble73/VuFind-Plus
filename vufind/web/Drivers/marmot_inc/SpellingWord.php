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
		//global $logger;
		//$logger->log("Loading spelling suggestions", PEAR_LOG_DEBUG);
		//Get suggestions, giving a little boost to words starting with what has been typed so far.
		$query = "SELECT word, commonality FROM spelling_words WHERE soundex = SOUNDEX('" . mysql_escape_string($word) . "') OR word like '" . mysql_escape_string($word) . "%' ORDER BY commonality, word LIMIT 10";
		$this->query($query);
		$suggestions = array();
		while ($this->fetch()){
			if ($this->word != $word){
				//$logger->log("Checking word {$this->word}", PEAR_LOG_DEBUG);
				$levenshteinDistance = levenshtein($this->word, $word);
				//$logger->log("  Levenshtein Distance is $levenshteinDistance", PEAR_LOG_DEBUG);
				similar_text($word, $this->word, $percent);
				//$logger->log("  Similarity is $percent", PEAR_LOG_DEBUG);
				$stringPosition = strpos($this->word, $word);
				//$logger->log("  String Position is $stringPosition", PEAR_LOG_DEBUG);
				if ($levenshteinDistance == 1 || $percent >= 75 || $stringPosition !== false){
					$suggestions[] = $this->word;
				}
			}
		}
		return $suggestions;
	}

}