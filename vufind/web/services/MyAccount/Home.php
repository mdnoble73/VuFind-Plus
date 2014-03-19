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
require_once ROOT_DIR . '/services/MyResearch/lib/Suggestions.php';
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

			// Setup Search Engine Connection
			$class = $configArray['Index']['engine'];
			$url = $configArray['Index']['url'];
			/** @var SearchObject_Solr $solrDb */
			$solrDb = new $class($url);

			if (is_array($suggestions)) {
				foreach($suggestions as $suggestion) {
					$interface->assign('resultIndex', ++$curIndex);
					$record = $solrDb->getRecord($suggestion['titleInfo']['id']);
					/** @var IndexRecord $recordDriver */
					$recordDriver = RecordDriverFactory::initRecordDriver($record);
					$resourceEntry = $interface->fetch($recordDriver->getSearchResult());
					$resourceList[] = $resourceEntry;
				}
			}
			$interface->assign('suggestions', $resourceList);

			//Check to see if the user has rated any titles
			$interface->assign('hasRatings', $user->hasRatings());
		}
		$interface->setPageTitle('My Account');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->display('layout.tpl');
	}
}