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
require_once 'services/MyResearch/lib/User_list.php';
require_once 'services/MyResearch/lib/FavoriteHandler.php';

class CiteList extends Action {
	function launch() {
		global $interface;
		global $configArray;
		global $user;

		//Get all lists for the user

		// Fetch List object
		if (isset($_REQUEST['id'])){
			$list = User_list::staticGet($_GET['listId']);
		}
		$interface->assign('favList', $list);

		// Get all titles on the list
		$favorites = $list->getResources(null);

		$favList = new FavoriteHandler($favorites, null, $list->id, false);
		$citationFormat = $_REQUEST['citationFormat'];
		$citationFormats = CitationBuilder::getCitationFormats();
		$interface->assign('citationFormat', $citationFormats[$citationFormat]);
		$citations = $favList->getCitations($citationFormat);

		$interface->assign('citations', $citations);

		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['id']));
		$interface->setTemplate('listCitations.tpl');
		$interface->display('layout.tpl');
	}
}
?>