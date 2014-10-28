<?php
/**
 * Created by PhpStorm.
 * User: Pascal Brammeier
 * Date: 10/28/2014
 * Time: 12:08 PM
 *
 * Based on PHPInfo.php
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Admin_APCInfo extends Admin_Admin {
	function launch() {
		global $interface;

		ob_start();
		//phpinfo();
		include_once 'usr/share/pear/apc.php';
		$info = ob_get_contents();
		ob_end_clean();

		$interface->assign("apcinfo", $info);

		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('apcinfo.tpl');
		$interface->setPageTitle('APC Information');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles() {
		return array('opacAdmin');
	}
}