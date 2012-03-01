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

require_once "Action.php";
require_once 'services/MyResearch/lib/Tags.php';
require_once 'services/MyResearch/lib/Resource.php';

class RemoveTag extends Action
{
	function launch($msg = null)
	{
		global $interface;
		global $configArray;
		global $user;

		if (!($user = UserAccount::isLoggedIn())) {
			require_once 'Login.php';
			Login::launch();
			exit();
		}

		// Save Data
		if (isset($_REQUEST['tagId'])) {
			//Remove the tag for the user.
			$resource = new Resource();
			if (isset($_REQUEST['resourceId'])){
				$resource = $resource->staticGet('record_id', $_REQUEST['resourceId']);
				$resource->removeTag($_REQUEST['tagId'], $user, false);
				header('Location: ' . $configArray['Site']['url'] . '/Record/' . $_REQUEST['resourceId']);
				exit();
			}else{
				$resource->removeTag($_REQUEST['tagId'], $user, true);
				header('Location: ' . $configArray['Site']['url'] . '/MyResearch/Favorites');
				exit();
			}

		}else{
			//No id provided to delete raise an error?
			PEAR::raiseError(new PEAR_Error('Tag Id Missing'));
		}

	}
}

?>
