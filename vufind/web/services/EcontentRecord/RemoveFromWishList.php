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

class RemoveFromWishList extends Action {

	private $user;

	function __construct()
	{
		$this->user = UserAccount::isLoggedIn();
	}

	function launch()
	{
		global $interface;
		global $configArray;

		$id = strip_tags($_GET['id']);
		$interface->assign('id', $id);

		// Check if user is logged in
		if (!$this->user) {
			$interface->assign('recordId', $id);
			$interface->assign('followupModule', 'EContentRecord');
			$interface->assign('followupAction', 'AddToWishList');
			if (isset($_GET['lightbox'])) {
				$interface->assign('title', $_GET['message']);
				$interface->assign('message', 'You must be logged in first');
				return $interface->fetch('AJAX/login.tpl');
			} else {
				$interface->assign('followup', true);
				$interface->setPageTitle('You must be logged in first');
				$interface->assign('subTemplate', '../MyResearch/login.tpl');
				$interface->setTemplate('view-alt.tpl');
				$interface->display('layout.tpl', 'AddToWishList' . $id);
			}
			exit();
		}

		//Add to the wishlist
		require_once 'sys/eContent/EContentWishList.php';
		$wishlistEntry = new EContentWishList();
		$wishlistEntry->userId = $this->user->id;
		$wishlistEntry->recordId = $id;
		$wishlistEntry->status = 'active';
		if ($wishlistEntry->find(true)){
			$wishlistEntry->status = 'deleted';
			$wishlistEntry->update();
		}
		
		header('Location: ' . $configArray['Site']['path'] . '/MyResearch/MyEContentWishList');
	}
}