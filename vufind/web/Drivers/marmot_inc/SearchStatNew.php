<?php
/**
 * Table Definition for bad words
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class SearchStatNew extends DB_DataObject
{
	public $__table = 'search_stats_new';    // table name
	public $id;                      //int(11)
	public $phrase;                    //varchar(500)
	public $lastSearch;       //timestamp
	public $numSearches;      //int(16)

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('SearchStat',$k,$v); }

	function keys() {
		return array('id', 'phrase');
	}

	function getSearchSuggestions($phrase, $type){
		$searchStat = new SearchStatNew();
		//Don't suggest things to users that will result in them not getting any results
		$searchStat->whereAdd("MATCH(phrase) AGAINST ('" . $searchStat->escape($phrase) ."')");
		//$searchStat->orderBy("numSearches DESC");
		$searchStat->limit(0, 20);
		$searchStat->find();
		$results = array();
		if ($searchStat->N > 0){
			while($searchStat->fetch()){
				$searchStat->phrase = trim(str_replace('"', '', $searchStat->phrase));
				if ($this->phrase != $phrase && !array_key_exists($searchStat->phrase, $results)){
					$results[$searchStat->phrase] = array('phrase'=>$searchStat->phrase, 'numSearches'=>$searchStat->numSearches, 'numResults'=>1);
				}
			}
		}else{
			//Try another search using like
			$searchStat = new SearchStatNew();
			//Don't suggest things to users that will result in them not getting any results
			$searchStat->whereAdd("phrase LIKE '" . $searchStat->escape($phrase, true) ."%'");
			$searchStat->orderBy("numSearches DESC");
			$searchStat->limit(0, 11);
			$searchStat->find();
			$results = array();
			if ($searchStat->N > 0){
				while($searchStat->fetch()){
					$searchStat->phrase = trim(str_replace('"', '', $searchStat->phrase));
					if ($this->phrase != $phrase && !array_key_exists($searchStat->phrase, $results)){
						$results[$searchStat->phrase] = array('phrase'=>$searchStat->phrase, 'numSearches'=>$searchStat->numSearches, 'numResults'=>1);
					}
				}
			}else{
				//Try another search using like

			}
		}
		return array_values($results);
	}

	function saveSearch($phrase, $type = false, $numResults){
		//Don't bother to count things that didn't return results.
		if (!isset($numResults) || $numResults == 0){
			return;
		}

		//Only save basic searches
		if (strpos($phrase, '(') !== FALSE || strpos($phrase, ')') !== FALSE){
			return;
		}

		$phrase = str_replace("\t", '', $phrase);
		$searchStat = new SearchStatNew();
		$searchStat->phrase = trim(strtolower($phrase));
		$searchStat->find();
		$isNew = true;
		if ($searchStat->N > 0){
			$searchStat->fetch();
			$searchStat->numSearches++;
			$isNew = false;
		}else{
			$searchStat->numSearches = 1;
		}
		$searchStat->lastSearch = time();
		if ($isNew){
			$searchStat->insert();
		}else{
			$searchStat->update();
		}
	}

}