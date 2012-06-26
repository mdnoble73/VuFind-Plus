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
require_once 'services/Admin/Admin.php';
require_once 'sys/ListWidget.php';
require_once 'sys/ListWidgetList.php';
require_once 'sys/DataObjectUtil.php';
				
/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 *
 * @author Mark Noble
 *
 */
class ListWidgets extends Admin {
	function launch() 	{
		global $configArray;
		global $interface;
		
		//Figure out what mode we are in 
		if (isset($_REQUEST['objectAction'])){
			$objectAction = $_REQUEST['objectAction'];
		}else{
			$objectAction = 'list';
		}
		
		if ($objectAction == 'delete' && isset($_REQUEST['id'])){
			$widget = new ListWidget(); 
			$widget->id = $_REQUEST['id'];
			if ($widget->find(true)){
				$widget->delete();
			}
			
			header("Location: $path/Admin/ListWidgets");
			exit();
		}
		
		//Get all available widgets
		$availableWidgets = array();
		$listWidget = new ListWidget();
		$listWidget->orderBy('name ASC');
		$listWidget->find();
		while ($listWidget->fetch()){
			$availableWidgets[$listWidget->id] = clone($listWidget);
		}
		$interface->assign('availableWidgets', $availableWidgets);
		
		//Get the selected widget
		if (isset($_REQUEST['id'])  && is_numeric($_REQUEST['id'])){
			$widget = $availableWidgets[$_REQUEST['id']];
			$interface->assign('object', $widget);
		}
		
	//Do actions that require preprocessing
		if ($objectAction == 'save'){
			if (!isset($widget)){
				$widget = new ListWidget();
			}
			DataObjectUtil::updateFromUI($widget, $listWidget->getObjectStructure());
			$validationResults = DataObjectUtil::saveObject($listWidget->getObjectStructure(), "ListWidget");
			if (!$validationResults['validatedOk']){
				$interface->assign('object', $widget);
				$interface->assign('errors', $validationResults['errors']);
				$objectAction = 'edit';
			}else{
				$interface->assign('object', $validationResults['object']);
				$objectAction = 'view';
			}
			
		}
		
		if ($objectAction == 'list'){
			$interface->setTemplate('listWidgets.tpl');
		}else{
			if ($objectAction == 'edit' || $objectAction == 'add'){
				if (isset($_REQUEST['id'])){
					$interface->assign('widgetid',$_REQUEST['id']);
				}
				$editForm = DataObjectUtil::getEditForm($listWidget->getObjectStructure());
				$interface->assign('editForm', $editForm);
				$interface->setTemplate('listWidgetEdit.tpl');
			}else{
				$interface->setTemplate('listWidget.tpl');
			}
		}
		
		$interface->setPageTitle('List Widgets');
		$interface->display('layout.tpl');

	}

	

	function getAllowableRoles(){
		return array('opacAdmin');
	}

	
}