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
require_once 'sys/DataObjectUtil.php';
require_once 'sys/eContent/EContentRecord.php';
class Edit extends Action {

	function launch()
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
		if (!$user->hasRole('epubAdmin')){
			$interface->setTemplate('noPermission.tpl');
			$interface->display('layout.tpl');
			exit();
		}


		$structure = EContentRecord::getObjectStructure();

		if (isset($_REQUEST['submit'])){
			//Save the object
			$results = DataObjectUtil::saveObject($structure, 'EContentRecord');
			$eContentRecord = $results['object'];
			//redirect to the view of the eContentRecord if we saved ok.
			if (!$results['validatedOk'] || !$results['saveOk']){
				//Display the errors for the user.
				$interface->assign('errors', $results['errors']);
				$interface->assign('object', $eContentRecord);
				$_REQUEST['id'] = $$eContentRecord->id;
			}else{
				//Show the new tip that was created
				header('Location:' . $configArray['Site']['path'] . "/EcontentRecord/{$eContentRecord->id}/Home");
				exit();
			}
		}

		$isNew = true;
		if (isset($_REQUEST['id']) && strlen($_REQUEST['id']) > 0 ){
			$object = EContentRecord::staticGet('id', strip_tags($_REQUEST['id']));
			$interface->assign('object', $object);
			$interface->setPageTitle('Edit EContentRecord');
			$isNew = false;
		}else{
			$interface->setPageTitle('Submit a New EContentRecord');
		}

		//Manipulate the structure as needed
		if ($isNew){
		}else{
			
		}

		$interface->assign('isNew', $isNew);
		$interface->assign('submitUrl', $configArray['Site']['path'] . '/EcontentRecord/Edit');
		$interface->assign('editForm', DataObjectUtil::getEditForm($structure));
		
		$interface->setTemplate('edit.tpl');

		$interface->display('layout.tpl');
	}

}
