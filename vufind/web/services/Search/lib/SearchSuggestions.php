<?php
/**
 *
 * Copyright (C) Marmot Library Network 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once ROOT_DIR . '/Drivers/marmot_inc/SpellingWord.php';

class SearchSuggestions{
	function getCommonSearchesMySql($searchTerm, $searchType){
		require_once(ROOT_DIR . '/Drivers/marmot_inc/SearchStat.php');
		$searchStat = new SearchStat();
		$suggestions = $searchStat->getSearchSuggestions( $searchTerm, $searchType);
		if (count ($suggestions) > 10){
			$suggestions = array_slice($suggestions, 0, 10);
		}
		return $suggestions;
	}

	function getSpellingSearches($searchTerm){
		$spellingWord = new SpellingWord();
		$suggestions = $spellingWord->getSpellingSuggestions($searchTerm);
		//Return up to 10 results max
		if (count ($suggestions) > 10){
			$suggestions = array_slice($suggestions, 0, 10);
		}
		return $suggestions;
	}

	function getAllSuggestions($searchTerm, $searchType){
		global $timer;

		//$searchSuggestions = $this->getCommonSearchesMySql($searchTerm, $searchType);
		$searchSuggestions = array();
		$timer->logTime('Loaded common search suggestions');
		//ISN and Authors are not typically regular words
		if ($searchType != 'ISN' && $searchType != 'Author'){
			$spellingSearches = $this->getSpellingSearches($searchTerm);
			$timer->logTime('Loaded spelling suggestions');
			//Merge the two arrays together
			foreach($spellingSearches as $term){
				if (!in_array($term, $searchSuggestions)){
					$searchSuggestions[] = $term;
				}
			}
		}

		return $searchSuggestions;
	}

}