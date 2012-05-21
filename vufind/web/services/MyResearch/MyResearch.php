<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';

require_once 'CatalogConnection.php';

require_once 'services/MyResearch/lib/User.php';
require_once 'services/MyResearch/lib/Resource.php';

class MyResearch extends Action
{
	protected $db;
	protected $catalog;

	function __construct()
	{
		global $interface;
		global $configArray;
		global $user;

		$interface->assign('page_body_style', 'sidebar_left');
		$interface->assign('ils', $configArray['Catalog']['ils']);

		if (!UserAccount::isLoggedIn()) {
			require_once 'Login.php';
			Login::launch();
			exit();
		}
		//$interface->assign('userNoticeFile', 'MyResearch/listNotice.tpl');

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$this->db = new $class($configArray['Index']['url']);
		if ($configArray['System']['debugSolr']) {
			$this->db->debug = true;
		}

		// Connect to Database
		$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);

		// Register Library Catalog Account
		if (isset($_POST['submit']) && !empty($_POST['submit'])) {
			if ($this->catalog && isset($_POST['cat_username']) && isset($_POST['cat_password'])) {
				$result = $this->catalog->patronLogin($_POST['cat_username'], $_POST['cat_password']);
				if ($result && !PEAR::isError($result)) {
					$user->cat_username = $_POST['cat_username'];
					$user->cat_password = $_POST['cat_password'];
					$user->update();
					UserAccount::updateSession($user);
					$interface->assign('user', $user);
				} else {
					$interface->assign('loginError', 'Invalid Patron Login');
				}
			}
		}
		
		//Determine whether or not materials request functionality should be enabled
		$interface->assign('enableMaterialsRequest', MaterialsRequest::enableMaterialsRequest());
		
		//Check to see if we have any acs or single use eContent in the catalog 
		//to enable the holds and wishlist appropriately
		if (isset($configArray['EContent']['hasProtectedEContent'])){
			$interface->assign('hasProtectedEContent', $configArray['EContent']['hasProtectedEContent']);
		}else{
			$interface->assign('hasProtectedEContent', false);
		}

		//This code is also in Search/History since that page displays in the My Account menu as well.
		//It is also in MyList.php and Admin.php
		if ($user !== false){
			$interface->assign('user', $user);
			// Get My Profile
			if ($this->catalog->status) {
				if ($user->cat_username) {
					$patron = $this->catalog->patronLogin($user->cat_username, $user->cat_password);
					if (PEAR::isError($patron)){
						PEAR::raiseError($patron);
					}

					$profile = $this->catalog->getMyProfile($patron);
					if (!PEAR::isError($profile)) {
						$interface->assign('profile', $profile);
					}
				}
			}
			//Figure out if we should show a link to classic opac to pay holds.
			$ecommerceLink = $configArray['Site']['ecommerceLink'];
			$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);
			if (strlen($ecommerceLink) > 0 && isset($homeLibrary) && $homeLibrary->showEcommerceLink == 1){
				$interface->assign('showEcommerceLink', true);
				$interface->assign('minimumFineAmount', $homeLibrary->minimumFineAmount);
				$interface->assign('ecommerceLink', $ecommerceLink);
			}else{
				$interface->assign('showEcommerceLink', false);
				$interface->assign('minimumFineAmount', 0);
			}
		}
	}

	/**
	 * Log the current user into the catalog using stored credentials; if this
	 * fails, clear the user's stored credentials so they can enter new, corrected
	 * ones.
	 *
	 * @access  protected
	 * @return  mixed               $user array (on success) or false (on failure)
	 */
	protected function catalogLogin()
	{
		global $user;

		if ($this->catalog->status) {
			if ($user->cat_username) {
				$patron = $this->catalog->patronLogin($user->cat_username,
				$user->cat_password);
				if (empty($patron) || PEAR::isError($patron)) {
					// Problem logging in -- clear user credentials so they can be
					// prompted again; perhaps their password has changed in the
					// system!
					unset($user->cat_username);
					unset($user->cat_password);
				} else {
					return $patron;
				}
			}
		}

		return false;
	}
}

?>
