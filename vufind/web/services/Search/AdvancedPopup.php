<?php
/**
 * Service to show an Advanced Popup form to streamline the advanced search.
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 6/26/13
 * Time: 9:50 AM
 */

require_once ROOT_DIR . '/services/Search/AdvancedBase.php';
class AdvancedPopup extends Search_AdvancedBase {
	function launch() {
		global $interface;

		// Create our search object
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->initAdvancedFacets();
		// We don't want this search in the search history
		$searchObject->disableLogging();
		// Go get the facets
		$searchObject->processSearch();
		$facetList = $searchObject->getFacetList();
		//print_r($facetList);
		// Shutdown the search object
		$searchObject->close();

		// Load a saved search, if any:
		$savedSearch = $this->loadSavedSearch();
		if ($savedSearch){
			$searchTerms = $savedSearch->getSearchTerms();

			$searchGroups = array();
			$numGroups = 0;
			foreach ($searchTerms as $search){
				$groupStart = true;
				$numItemsInGroup = count($search['group']);
				$curItem = 0;
				foreach ($search['group'] as $group) {
					$searchGroups[$numGroups] = array(
						'groupStart' => $groupStart ? 1 : 0,
						'searchType' => $group['field'],
						'lookfor' => $group['lookfor'],
						'join' => $group['bool'],
						'groupEnd' => ++$curItem === $numItemsInGroup ? 1 : 0
					);

					$groupStart = false;
					$numGroups++;
				}
			}
			$interface->assign('searchGroups', $searchGroups);
		}

		// Send search type settings to the template
		$interface->assign('advSearchTypes', $searchObject->getAdvancedTypes());

		$interface->assign('facetList', $facetList);

		$interface->assign('popupTitle', 'Advanced Search');
		$popupContent = $interface->fetch('Search/advancedPopup.tpl');
		$interface->assign('popupContent', $popupContent);
		$interface->display('popup-wrapper.tpl');
	}
}