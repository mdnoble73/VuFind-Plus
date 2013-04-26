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
require_once ROOT_DIR . '/Drivers/marmot_inc/BookStore.php';

class BookStores extends ObjectEditor
{
	
	function getObjectType(){
		return 'BookStore';
	}
	function getToolName(){
		return 'BookStores';
	}
	function getPageTitle(){
		return 'Book Stores';
	}
	function getAllObjects(){
		$store = new BookStore();
		$store->orderBy('storeName');
		$store->find();
		$list = array();
		while ($store->fetch()){
			$list[$store->id] = clone $store;
		}
		return $list;
	}
	function getObjectStructure(){
		return BookStore::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}

}