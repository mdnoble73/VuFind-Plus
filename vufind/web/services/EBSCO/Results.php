<?php

/**
 * Description goes here
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/15/2016
 * Time: 6:09 PM
 */
class EBSCO_Results extends Action{
	function launch() {
		global $interface;
		global $timer;

		//Include Search Engine
		require_once ROOT_DIR . '/sys/Ebsco/EDS_API.php';
		$searchObject = EDS_API::getInstance();
		$timer->logTime('Include search engine');

		$interface->setPageTitle('EBSCO Search Results');

		$sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : null;
		$filters = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : array();
		$searchObject->getSearchResults($_REQUEST['lookfor'], $sort, $filters);

		$displayQuery = $_REQUEST['lookfor'];
		$pageTitle = $displayQuery;
		if (strlen($pageTitle) > 20){
			$pageTitle = substr($pageTitle, 0, 20) . '...';
		}

		$interface->assign('qtime',               round($searchObject->getQuerySpeed(), 2));
		$interface->assign('lookfor',             $displayQuery);

		// Big one - our results //
		$recordSet = $searchObject->getResultRecordHTML();
		$interface->assign('recordSet', $recordSet);
		$timer->logTime('load result records');

		$interface->assign('sortList',   $searchObject->getSortList());

		$summary = $searchObject->getResultSummary();
		$interface->assign('recordCount', $summary['resultTotal']);
		$interface->assign('recordStart', $summary['startRecord']);
		$interface->assign('recordEnd',   $summary['endRecord']);

		$appliedFacets = $searchObject->getAppliedFilters();
		$interface->assign('filterList', $appliedFacets);
		$facetSet = $searchObject->getFacetSet();
		$interface->assign('sideFacetSet', $facetSet);

		if ($summary['resultTotal'] > 0){
			$link    = $searchObject->renderLinkPageTemplate();
			$options = array('totalItems' => $summary['resultTotal'],
					'fileName' => $link,
					'perPage' => $summary['perPage']);
			$pager   = new VuFindPager($options);
			$interface->assign('pageLinks', $pager->getLinks());
		}

		//Load explore more data
		require_once ROOT_DIR . '/sys/ExploreMore.php';
		$exploreMore = new ExploreMore();
		$exploreMore->loadExploreMoreBar('ebsco');

		$displayTemplate = 'EBSCO/list-list.tpl'; // structure for regular results
		$interface->assign('subpage', $displayTemplate);
		$this->display($summary['resultTotal'] > 0 ? 'list.tpl' : 'list-none.tpl', $pageTitle, 'EBSCO/results-sidebar.tpl');
	}
}