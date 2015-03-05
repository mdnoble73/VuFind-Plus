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

require_once ROOT_DIR . "/Action.php";

require_once 'Home.php';

class MyAccount_Edit extends Action
{
	function __construct()
	{
	}

	private function saveChanges($user)
	{
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$userListEntry = new UserListEntry();
		$userListEntry->id = $_REQUEST['listEntry'];
		if ($userListEntry->find(true)){
			$userListEntry->notes = $_REQUEST['notes'];
			$userListEntry->update();
		}
	}

	function launch($msg = null)
	{
		global $interface;
		global $configArray;

		if (!($user = UserAccount::isLoggedIn())) {
			require_once ROOT_DIR . '/services/MyAccount/Login.php';
			MyAccount_Login::launch();
			exit();
		}

		// Save Data
		if (isset($_POST['submit'])) {
			$this->saveChanges($user);

			// After changes are saved, send the user back to an appropriate page;
			// either the list they were viewing when they started editing, or the
			// overall favorites list.
			if (isset($_REQUEST['list_id'])) {
				$nextAction = 'MyList/' . $_REQUEST['list_id'];
			} else {
				$nextAction = 'Home';
			}
			header('Location: ' . $configArray['Site']['path'] . '/MyAccount/' . $nextAction);
			exit();
		}

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
		$userList = new UserList();
		$userList->id = $_REQUEST['list_id'];
		$userList->find(true);
		$interface->assign('list', $userList);

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_GET['id'];
		$groupedWorkDriver = new GroupedWorkDriver($id);

		if ($groupedWorkDriver->isValid){
			$interface->assign('recordDriver', $groupedWorkDriver);
		}

		// Record ID
		$interface->assign('recordId', $id);

		// Retrieve saved information about record
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$userListEntry = new UserListEntry();
		$userListEntry->groupedWorkPermanentId = $id;
		$userListEntry->listId = $_REQUEST['list_id'];
		$userListEntry->find(true);
		$interface->assign('listEntry', $userListEntry);

		$interface->assign('listFilter', $_GET['list_id']);

		$interface->setTemplate('editListTitle.tpl');
		$interface->display('layout.tpl');
	}
}

?>
