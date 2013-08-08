<?php
/**
 * Custom Reporting so the user can build reports based on their needs
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/10/13
 * Time: 5:42 PM
 */

require_once ROOT_DIR . '/services/Report/AnalyticsReport.php';
class Report_Custom extends Report_AnalyticsReport{
	public function launch(){
		global $interface;

		//Setup filters
		$this->setupFilters();

		$interface->setPageTitle('Report - Custom');
		$interface->setTemplate('customReport.tpl');
		$interface->display('layout.tpl');
	}

}