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
require_once 'Drivers/marmot_inc/CirculationStatus.php';
require_once 'services/Admin/ObjectEditor.php';
require_once 'XML/Unserializer.php';

class CirculationStatuses extends ObjectEditor
{
	function getObjectType(){
		return 'CirculationStatus';
	}
	function getToolName(){
		return 'CirculationStatuses';
	}
	function getPageTitle(){
		return 'Circulation Statuses';
	}
	function getAllObjects(){
		$object = new CirculationStatus();
		$object->orderBy('millenniumName');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->circulationStatusId] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		return CirculationStatus::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'millenniumName';
	}
	function getIdKeyColumn(){
		return 'circulationStatusId';
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}
}