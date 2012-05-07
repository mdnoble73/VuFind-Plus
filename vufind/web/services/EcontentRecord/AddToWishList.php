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

class AddToWishList extends Action {

	private $user;

	function __construct()
	{
		$this->user = UserAccount::isLoggedIn();
	}

	function launch()
	{
		global $interface;
		global $configArray;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		// Check if user is logged in
		if (!$this->user) {
			$interface->assign('recordId', $id);
			$interface->assign('followupModule', 'EcontentRecord');
			$interface->assign('followupAction', 'AddToWishList');
			$interface->assign('followup', 'AddToWishList');
			if (isset($_GET['lightbox'])) {
				$interface->assign('title', $_GET['message']);
				$interface->assign('message', 'You must be logged in first');
				return $interface->fetch('AJAX/login.tpl');
			} else {
				
				//Var for the IDCLREADER TEMPLATE
				$interface->assign('ButtonBack',true);
				$interface->assign('ButtonHome',true);
				$interface->assign('MobileTitle','Login to your account');
				
				$interface->setPageTitle('You must be logged in first');
				$interface->assign('subTemplate', '../MyResearch/login.tpl');
				$interface->setTemplate('view-alt.tpl');
				$interface->display('layout.tpl', 'AddToWishList' . $id);
			}
			exit();
		}

		//Add to the wishlist
		
		//Add to wishlist if not already on the wishlist
		require_once('Drivers/EContentDriver.php');
		$eContentDriver = new EContentDriver();
		$ret = $eContentDriver->addToWishList($id, $this->user);
		
		header('Location: ' . $configArray['Site']['path'] . '/EcontentRecord/' . $id . '/Home');
	}
}