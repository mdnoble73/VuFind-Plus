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
		global $configArray;
		global $timer;

		//Include Search Engine
		require_once ROOT_DIR . '/sys/Ebsco/EDS_API.php';
		$searchObject = EDS_API::getInstance();
		$timer->logTime('Include search engine');

		$interface->setPageTitle('EBSCO Search Results');

		$edsResults = $searchObject->getSearchResults($_REQUEST['lookfor']);

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

		$summary = $searchObject->getResultSummary();
		$interface->assign('recordCount', $summary['resultTotal']);
		$interface->assign('recordStart', $summary['startRecord']);
		$interface->assign('recordEnd',   $summary['endRecord']);

		if ($summary['resultTotal'] > 0){
			$link    = $searchObject->renderLinkPageTemplate();
			$options = array('totalItems' => $summary['resultTotal'],
					'fileName' => $link,
					'perPage' => $summary['perPage']);
			$pager   = new VuFindPager($options);
			$interface->assign('pageLinks', $pager->getLinks());
			if ($pager->isLastPage()) {
				$numUnscopedTitlesToLoad = 5;
			}
		}

		$displayTemplate = 'EBSCO/list-list.tpl'; // structure for regular results
		$interface->assign('subpage', $displayTemplate);
		$this->display($summary['resultTotal'] > 0 ? 'list.tpl' : 'list-none.tpl', $pageTitle, 'Search/results-sidebar.tpl');
	}
}