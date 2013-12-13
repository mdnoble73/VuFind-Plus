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

require_once ROOT_DIR . '/Action.php';

require_once ROOT_DIR . '/CatalogConnection.php';

require_once ROOT_DIR . '/services/MyResearch/lib/User.php';
require_once ROOT_DIR . '/services/MyResearch/lib/Resource.php';

abstract class MyResearch extends Action
{
	/** @var  SearchObject_Solr|SearchObject_Base */
	protected $db;
	/** @var  CatalogConnection $catalog */
	protected $catalog;
	protected $requireLogin = true;

	function __construct()
	{
		global $interface;
		global $configArray;
		global $user;

		$interface->assign('page_body_style', 'sidebar_left');
		$interface->assign('ils', $configArray['Catalog']['ils']);

		if ($this->requireLogin && !UserAccount::isLoggedIn()) {
			require_once 'Login.php';
			Login::launch();
			exit();
		}
		//$interface->assign('userNoticeFile', 'MyResearch/listNotice.tpl');

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$this->db = new $class($configArray['Index']['url']);

		// Connect to Database
		$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);

		// Register Library Catalog Account
		if (isset($_POST['submit']) && !empty($_POST['submit'])) {
			if ($this->catalog && isset($_POST['cat_username']) && isset($_POST['cat_password'])) {
				$result = $this->catalog->patronLogin($_POST['cat_username'], $_POST['cat_password']);
				if ($result && !PEAR_Singleton::isError($result)) {
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

		global $library;
		if (isset($library)){
			$interface->assign('showFavorites', $library->showFavorites);
			$interface->assign('showRatings', $library->showRatings);
			$interface->assign('showComments', $library->showComments);
		}else{
			$interface->assign('showFavorites', 1);
			$interface->assign('showRatings', 1);
			$interface->assign('showComments', 1);
		}

		//This code is also in Search/History since that page displays in the My Account menu as well.
		//It is also in MyList.php and Admin.php
		if ($user !== false){
			$interface->assign('user', $user);
			// Get My Profile
			if ($this->catalog->status) {
				if ($user->cat_username) {
					$patron = $this->catalog->patronLogin($user->cat_username, $user->cat_password);
					if (PEAR_Singleton::isError($patron)){
						PEAR_Singleton::raiseError($patron);
					}

					$profile = $this->catalog->getMyProfile($patron);
					//global $logger;
					//$logger->log("Patron profile phone number in MyResearch = " . $profile['phone'], PEAR_LOG_INFO);
					if (!PEAR_Singleton::isError($profile)) {
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
				if ($homeLibrary->payFinesLink == 'default'){
					$interface->assign('ecommerceLink', $ecommerceLink);
				}else{
					$interface->assign('ecommerceLink', $homeLibrary->payFinesLink);
				}
				$interface->assign('payFinesLinkText', $homeLibrary->payFinesLinkText);
			}else{
				$interface->assign('showEcommerceLink', false);
				$interface->assign('minimumFineAmount', 0);
			}

			//Load a list of lists
			$lists = array();
			if ($user->disableRecommendations == 0){
				$lists[] = array('name' => 'Recommended For You', 'url' => '/MyResearch/SuggestedTitles', 'id' => 'suggestions');
			}
			$tmpList = new User_list();
			$tmpList->user_id = $user->id;
			$tmpList->orderBy("title ASC");
			$tmpList->find();
			if ($tmpList->N > 0){
				while ($tmpList->fetch()){
					$lists[$tmpList->id] = array('name' => $tmpList->title, 'url' => '/MyResearch/MyList/' .$tmpList->id , 'id' => $tmpList->id);
				}
			}else{
				$lists[-1] = array('name' => "My Favorites", 'url' => '/MyResearch/MyList/-1', 'id' => -1);
			}
			$interface->assign('lists', $lists);

			// Get My Tags
			$tagList = $user->getTags();
			$interface->assign('tagList', $tagList);
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
				if (empty($patron) || PEAR_Singleton::isError($patron)) {
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
