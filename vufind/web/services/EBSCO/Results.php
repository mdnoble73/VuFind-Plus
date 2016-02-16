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
		$displayTemplate = 'Search/list-list.tpl'; // structure for regular results
		// Done, display the page
		//$this->display($searchObject->getResultTotal() ? 'list.tpl' : 'list-none.tpl', $pageTitle, 'Search/results-sidebar.tpl');
	}
}