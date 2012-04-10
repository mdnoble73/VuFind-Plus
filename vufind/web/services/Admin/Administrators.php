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
require_once 'services/Admin/ObjectEditor.php';
require_once 'XML/Unserializer.php';

class Administrators extends ObjectEditor
{
	function getObjectType(){
		return 'User';
	}
	function getToolName(){
		return 'Administrators';
	}
	function getPageTitle(){
		return 'Users';
	}
	function getAllObjects(){
		$admin = new User();
		$admin->query('SELECT * FROM user INNER JOIN user_roles on user.id = user_roles.userId ORDER BY cat_password');
		$adminList = array();
		while ($admin->fetch()){
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
			$newAdmin->fetch();
			$newAdmin->roles = $_REQUEST['roles'];
			$newAdmin->update();
			global $configArray;
			if (isset($_SESSION['redirect_location']) && $objectAction != 'delete'){
				header("Location: " . $_SESSION['redirect_location']);
			}else{
				header("Location: {$configArray['Site']['url']}/Admin/{$this->getToolName()}");
			}
			die();
		}else{
			$interface->assign('error', 'Could not find a user with that barcode.');
			$interface->setTemplate('addAdministrator.tpl');
		}
	}
}