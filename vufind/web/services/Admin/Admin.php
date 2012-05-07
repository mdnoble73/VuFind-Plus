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

abstract class Admin extends Action
{
	protected $db;

	function __construct()
	{
		global $interface;
		global $configArray;
		global $user;

		//If the user isn't logged in, take them to the login page
		if (!$user){
			header("Location: {$configArray['Site']['url']}/MyResearch/Login");
			die();
		}

		//Make sure the user has permission to access the page
		$allowableRoles = $this->getAllowableRoles();
		$userCanAccess = false;
		foreach($allowableRoles as $roleId => $roleName){
			if ($user->hasRole($roleName)){
				$userCanAccess = true;
				break;
			}
		}
		
		$interface->assign('ils', $configArray['Catalog']['ils']);
		
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
		//It is also in MyList.php
		if ($user !== false){
			$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);
			
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

		if (!$userCanAccess){
			$interface->setTemplate('noPermission.tpl');
			$interface->display('layout.tpl');
			exit();
		}
	}

	abstract function getAllowableRoles();
}