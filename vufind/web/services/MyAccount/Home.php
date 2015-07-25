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
		global $interface;
		global $user;

		if ($user){
			$interface->setTemplate('home.tpl');

			//Check to see if the user has rated any titles
			$interface->assign('hasRatings', $user->hasRatings());
		}
		$interface->setPageTitle('My Account');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->display('layout.tpl');
	}
}