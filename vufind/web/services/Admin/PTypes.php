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
require_once 'Drivers/marmot_inc/PType.php';

class PTypes extends ObjectEditor
{

	function getObjectType(){
		return 'PType';
	}
	function getToolName(){
		return 'PTypes';
	}
	function getPageTitle(){
		return 'PTypes';
	}
	function getAllObjects(){
		$libraryList = array();

		global $user;
		if ($user->hasRole('opacAdmin')){
			$library = new PType();
			$library->orderBy('pType');
			$library->find();
			while ($library->fetch()){
				$libraryList[$library->pType] = clone $library;
			}
		}

		return $libraryList;
	}
	function getObjectStructure(){
		return PType::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'pType';
	}
	function getIdKeyColumn(){
		return 'pType';
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}
	function showExportAndCompare(){
		global $user;
		return $user->hasRole('opacAdmin');
	}
	function canAddNew(){
		global $user;
		return $user->hasRole('opacAdmin');
	}
	function canDelete(){
		global $user;
		return $user->hasRole('opacAdmin');
	}

}