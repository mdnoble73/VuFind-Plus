<?php

/**
 * Description goes here
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/21/2015
 * Time: 10:50 AM
 */
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
class PayOnlineNashville extends Action{

	function launch() {
		global $interface;
		global $user;
		global $configArray;

		//Require appropriate classes
		//require_once ROOT_DIR . '/sys/FinesPayments/Nashville....php';

		//Get data from config array as needed
		//$var = $configArray['SectionName']['key']

		//Get data from the user
		$barcode = $user->cat_username;
		//$username = $user->firstname . ' ' . $user->lastname;

		//Do the actual processing here


		//Present a success or failure message
		$interface->setPageTitle('Payment Results');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('onlinePaymentResult.tpl');
		$interface->display('layout.tpl');
	}
}