<?php
/**
 * Creates a report for all patrons of a particular location including
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/8/13
 * Time: 10:24 AM
 */
class Report_PatronStatus extends Action{
	function launch(){
		global $interface;
		if (isset($_REQUEST['submit'])){
			//Generate the report
		}

		$interface->setPageTitle('Patron Status Report');
		$interface->setTemplate('patronStatus.tpl');
		$interface->display('layout.tpl');
	}
}