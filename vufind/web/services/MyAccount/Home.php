<?php
/**
 * Home Page for Account Functionality
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/10/13
 * Time: 1:11 PM
 */
require_once ROOT_DIR . '/services/MyResearch/MyResearch.php';
class MyAccount_Home  extends MyResearch{
	function launch(){
		global $configArray;
		global $interface;
		global $user;

		if ($user){
			$interface->setTemplate('home.tpl');

			//Show how many titles the user has checked out and on hold.

			//Show alerts if any titles are overdue and if holds are ready to pickup.

			//If user has suggestions on, show them a list of suggested titles.$suggestions = Suggestions::getSuggestions();
			$suggestions = Suggestions::getSuggestions();
			$resourceList = array();
			$curIndex = 0;
			if (is_array($suggestions)) {
				foreach($suggestions as $suggestion) {
					$interface->assign('resultIndex', ++$curIndex);
					/** @var IndexRecord $recordDriver */
					$recordDriver = RecordDriverFactory::initRecordDriver($suggestion['titleInfo']);
					$resourceEntry = $interface->fetch($recordDriver->getSearchResult());
					$resourceList[] = $resourceEntry;
				}
			}
			$interface->assign('suggestions', $resourceList);

			//Check to see if the user has rated any titles
			$interface->assign('hasRatings', $user->hasRatings());
		}
		$interface->display('layout.tpl');
	}
}