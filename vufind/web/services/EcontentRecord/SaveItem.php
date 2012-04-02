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

require_once 'sys/eContent/EContentRecord.php';
require_once 'sys/eContent/EContentItem.php';
require_once 'sys/DataObjectUtil.php';

class SaveItem extends Action{
	function launch(){
		global $interface;
		global $timer;
		global $configArray;
		global $user;

		$id = $_REQUEST['id'];
		$structure = EContentItem::getObjectStructure();
		$ret = DataObjectUtil::saveObject($structure, 'EContentItem');
		
		if (!$ret['validatedOk']){
			echo("Item failed validation.");
		}elseif (!$ret['saveOk']){
			echo("Could not save the new item");
		}else{
			$object = $ret['object'];
			header("Location: " . $configArray['Site']['path'] . "/EcontentRecord/{$object->recordId}/Home");
		}
	}
}