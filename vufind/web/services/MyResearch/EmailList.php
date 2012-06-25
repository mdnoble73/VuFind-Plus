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
require_once 'sys/Mailer.php';
require_once 'services/MyResearch/lib/User_list.php';
require_once 'services/MyResearch/lib/FavoriteHandler.php';

class EmailList extends Action {
	function launch() {
		global $interface;
		global $configArray;

		if (isset($_POST['submit'])) {
			$result = $this->sendEmail($_POST['to'], $_POST['from'], $_POST['message']);
			if (!PEAR::isError($result)) {
				require_once 'MyList.php';
				$_GET['id'] = $_REQUEST['listId'];
				MyList::launch();
				exit();
			} else {
				$interface->assign('message', $result->getMessage());
			}
		}

		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['id']));
		$interface->assign('popupTitle', 'Email a list');
		$pageContent = $interface->fetch('MyResearch/emailListPopup.tpl');
		$interface->assign('popupContent', $pageContent);
		echo $interface->fetch('popup-wrapper.tpl');
	}

	function sendEmail($to, $from, $message) {
		global $interface;
		global $user;

		//Load the list
		$list = new User_list();
		$list->id = $_REQUEST['listId'];
		if ($list->find(true)){
			// Build Favorites List
			$titles = $list->getResources(null);

			// Load the User object for the owner of the list (if necessary):
			if ($user && $user->id == $list->user_id || $list->public == 1) {
				//The user can access the list
				$favoriteHandler = new FavoriteHandler($titles, $user);
				$titleDetails = $favoriteHandler->getTitles();
				$interface->assign('titles', $titleDetails);
				$interface->assign('list', $list);
			} else {
				$interface->assign('error', 'You do not have access to this list.');
			}
		}else{
			$interface->assign('error', 'Unable to read list');
		}

		$interface->assign('from', $from);
		$interface->assign('message', $message);
		$body = $interface->fetch('Emails/my-list.tpl');

		$mail = new VuFindMailer();
		$subject = $list->title;
		return $mail->send($to, $from, $subject, $body);
	}
}
?>