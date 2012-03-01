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

require_once 'services/MyResearch/MyResearch.php';
require_once 'services/MyResearch/lib/FavoriteHandler.php';
require_once 'services/MyResearch/lib/Suggestions.php';

/**
 * MyResearch Home Page
 *
 * This controller needs some cleanup and organization.
 *
 * @version  $Revision: 1.27 $
 */
class Favorites extends MyResearch
{

	function launch()
	{
		global $configArray;
		global $interface;
		global $user;

		if (isset($_REQUEST['followup'])) {
			$followupUrl =  $configArray['Site']['url'] . "/". strip_tags($_REQUEST['followupModule']);
			if (!empty($_REQUEST['recordId'])) {
				$followupUrl .= "/" . strip_tags($_REQUEST['recordId']);
			}
			$followupUrl .= "/" . strip_tags($_REQUEST['followupAction']);
			if(isset($_REQUEST['comment'])) $followupUrl .= "?comment=" . urlencode($_REQUEST['comment']);
			header("Location: " . $followupUrl);
		}

		if (isset($_REQUEST['returnUrl'])) {
			$followupUrl =  $_REQUEST['returnUrl'];
			header("Location: " . $followupUrl);
		}

		// Delete Resource
		if (isset($_GET['delete'])) {
			$resource = Resource::staticGet('record_id', strip_tags($_GET['delete']));
			$user->removeResource($resource);
		}

		// Narrow by Tag
		if (isset($_GET['tag'])) {
			$interface->assign('tags', strip_tags($_GET['tag']));
		}

		global $library;
		if (isset($library)){
			$interface->assign('showRatings', $library->showRatings);
		}else{
			$interface->assign('showRatings', 1);
		}

		//Check to see if the user has rated any titles
		$interface->assign('hasRatings', $user->hasRatings());

		// Get My Lists
		$listList = $user->getLists();
		$interface->assign('listList', $listList);

		// Get My Tags
		$tagList = $user->getTags();
		$interface->assign('tagList', $tagList);
		$interface->setPageTitle('Favorites');
		$interface->setTemplate('favorites.tpl');
		$interface->display('layout.tpl');
	}

}