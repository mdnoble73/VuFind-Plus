<?php
/**
 * Home Page for Account Functionality
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/10/13
 * Time: 1:11 PM
 */
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/services/MyResearch/lib/Suggestions.php';
class MyAccount_Home  extends MyAccount{
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
			/*$searchObject = SearchObjectFactory::initSearchObject();

			if (is_array($suggestions)) {
				$suggestionIds = array();
				foreach($suggestions as $suggestion) {
					$suggestionIds[] = $suggestion['titleInfo']['id'];
				}
				$results = $searchObject->searchForRecordIds($suggestionIds);
				$resourceList = $searchObject->getSuggestionListHTML();
			}
			$interface->assign('suggestions', $resourceList);*/

			//Check to see if the user has rated any titles
			$interface->assign('hasRatings', $user->hasRatings());
		}
		$interface->setPageTitle('My Account');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->display('layout.tpl');
	}
}