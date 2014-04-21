<?php
/**
 * Returns information about PHP
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 4/21/14
 * Time: 11:18 AM
 */
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once 'XML/Unserializer.php';

class Admin_PHPInfo extends Admin_Admin {
	function launch() {
		global $interface;

		ob_start();
		phpinfo();
		$info = ob_get_contents();
		ob_end_clean();

		$interface->assign("phpinfo", $info);

		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('phpinfo.tpl');
		$interface->setPageTitle('PHP Information');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles() {
		return array('userAdmin', 'opacAdmin');
	}
}