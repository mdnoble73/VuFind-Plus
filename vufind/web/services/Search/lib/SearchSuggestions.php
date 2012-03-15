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
//require_once 'sys/SolrStats.php';
//require_once 'Drivers/marmot_inc/BadWord.php';
require_once 'Drivers/marmot_inc/SpellingWord.php';

class SearchSuggestions{
	/*function getCommonSearchesSolr($searchTerm){
		global $configArray;
		// Load SOLR Statistics
		$solr = new SolrStats($configArray['Statistics']['solr']);
		if ($configArray['System']['debugSolr']) {
			$solr->debug = true;
		}

		// Query statistics for phrases starting with the search term.
		//Return up to 1 for speed since we are looking at facets only.
		$searchTerm = strtolower(trim($searchTerm));
		$searchTerm = preg_replace('/[^\\w\\d\\s]/', '', $searchTerm);
		if (strlen($searchTerm) == 0){
			return array();
		}
		$result = $solr->search("phrase:$searchTerm*", null, null, 0, 1,
		array('field' => array('phrase')),
                                '', null, null, null, HTTP_REQUEST_METHOD_GET);
		if (!PEAR::isError($result)) {
			$badWords = new BadWord();
			$badWordsList = $badWords->getBadWordExpressions();

			$searchFacets = $result['facet_counts']['facet_fields']['phrase'];
			if (!is_array($searchFacets) || count($searchFacets) == 0){
				return array();
			}
			//search suggestions are filled with the suggestion as the key and count of times used as the index
			$searchSuggestions = array();
			foreach($searchFacets as $facet){
				$searchSuggestion = strtolower(trim($facet[0]));
				//Remove any stop words to preserve the minds of children (or something like that)
				$okToAdd = true;
				foreach ($badWordsList as $badWord){
					if (preg_match($badWord,$searchSuggestion)){
						$okToAdd = false;
					}
				}
				if (!$okToAdd){
					continue;
				}
				//Add to the array
				if (array_key_exists($searchSuggestion, $searchSuggestions)){
					//Increase the count for the facet
					$searchSuggestions[$searchSuggestion] += $facet[1];
				}else{
					//New item
					$searchSuggestions[$searchSuggestion] = $facet[1];
				}
			}
			//Sort the array based on number of suggestions
			//suggestions may have gotten out of order as we combined facets
			arsort($searchSuggestions);

			//Now get just the key values and return that as the suggestions
			$searchSuggestions = array_keys($searchSuggestions);

			//Return up to 10 results max
			if (count ($searchSuggestions) > 10){
				$searchSuggestions = array_slice($searchSuggestions, 0, 10);
			}
			return $searchSuggestions;
		}
		return array();
	}*/

	function getCommonSearchesMySql($searchTerm, $searchType){
		require_once('Drivers/marmot_inc/SearchStat.php');
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
		
		$searchSuggestions = $this->getCommonSearchesMySql($searchTerm, $searchType);
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