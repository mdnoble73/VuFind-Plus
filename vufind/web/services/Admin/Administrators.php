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
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once 'XML/Unserializer.php';

class Admin_Administrators extends ObjectEditor
{
	function getObjectType(){
		return 'User';
	}
	function getToolName(){
		return 'Administrators';
	}
	function getPageTitle(){
		return 'Administrators';
	}
	function getAllObjects(){
		/** @var User $admin */
		$admin = new User();
		$admin->query('SELECT * FROM user INNER JOIN user_roles on user.id = user_roles.userId ORDER BY cat_password');
		$adminList = array();
		while ($admin->fetch()){
			$homeLibrary = Library::getLibraryForLocation($admin->homeLocationId);
			if ($homeLibrary != null){
				$admin->homeLibraryName = $homeLibrary->displayName;
			}else{
				$admin->homeLibraryName = 'Unknown';
			}

			$location = new Location();
			$location->locationId = $admin->homeLocationId;
			if ($location->find(true)) {
				$admin->homeLocation = $location->displayName;
			}else{
				$admin->homeLocation = 'Unknown';
			}

			$adminList[$admin->id] = clone $admin;
		}
		return $adminList;
	}
	function getObjectStructure(){
		return User::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'cat_password';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('userAdmin');
	}
	function canAddNew(){
		return false;
	}
	function customListActions(){
		return array(
		array('label'=>'Add Administrator', 'action'=>'addAdministrator'),
		);
	}
	function addAdministrator(){
		global $interface;
		//Basic List
		$interface->setTemplate('addAdministrator.tpl');
	}
	function processNewAdministrator(){
		global $interface;
		global $configArray;
		$login = $_REQUEST['login'];
		$newAdmin = new User();
		$barcodeProperty = $configArray['Catalog']['barcodeProperty'];

		$newAdmin->$barcodeProperty = $login;
		$newAdmin->find();
		if ($newAdmin->N == 1){
			global $logger;
			//$logger->log(print_r($_REQUEST['roles'], TRUE));
			if (isset($_REQUEST['roles'])){
				$newAdmin->fetch();
				$newAdmin->roles = $_REQUEST['roles'];
				$newAdmin->update();
			}else{
				$newAdmin->fetch();
				$newAdmin->query('DELETE FROM user_roles where user_roles.userId = ' . $newAdmin->id);
			}

			global $configArray;
			header("Location: {$configArray['Site']['path']}/Admin/{$this->getToolName()}");
			die();
		}else{
			$interface->assign('error', 'Could not find a user with that barcode. (The user needs to have logged in at least once.)');
			$interface->setTemplate('addAdministrator.tpl');
		}
	}
}