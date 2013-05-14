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
require_once ROOT_DIR . '/Drivers/marmot_inc/LocationSearchSource.php';

class Admin_LocationSearchSources extends ObjectEditor
{

	function getObjectType(){
		return 'LocationSearchSource';
	}
	function getToolName(){
		return 'LocationSearchSources';
	}
	function getPageTitle(){
		return 'Location Search Sources';
	}
	function getAllObjects(){
		$facetsList = array();
		$library = new LocationSearchSource();
		if (isset($_REQUEST['locationId'])){
			$libraryId = $_REQUEST['locationId'];
			$library->locationId = $libraryId;
		}
		$library->orderBy('weight');
		$library->find();
		while ($library->fetch()){
			$facetsList[$library->id] = clone $library;
		}

		return $facetsList;
	}
	function getObjectStructure(){
		return LibrarySearchSource::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin');
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
	function getAdditionalObjectActions($existingObject){
		$objectActions = array();
		if (isset($existingObject) && $existingObject != null){
			$objectActions[] = array(
				'text' => 'Return to Location',
				'url' => '/Admin/Locations?objectAction=edit&id=' . $existingObject->libraryId,
			);
		}
		return $objectActions;
	}
}