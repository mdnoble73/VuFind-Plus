<?php
/**
 * Created by PhpStorm.
 * User: mnoble
 * Date: 11/22/2017
 * Time: 12:11 PM
 */

class Union_CombinedResults extends Action{
	function launch() {
		global $library;
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		$combinedResultsName = 'Combined Results';
		if ($location && !$location->useLibraryCombinedResultsSettings){
			$enableCombinedResults = $location->enableCombinedResults;
			$showCombinedResultsFirst = $location->defaultToCombinedResults;
			$combinedResultsName = $location->combinedResultsLabel;
		}else if ($library){
			$enableCombinedResults = $library->enableCombinedResults;
			$showCombinedResultsFirst = $library->defaultToCombinedResults;
			$combinedResultsName = $library->combinedResultsLabel;
		}
		$this->display(
				$pageTitle, 'Search/results-sidebar.tpl');
	}
}