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
/* This file is now deprecated. Retaining until it is known it is no longer needed.
plb 10-15-2014

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Mailer.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
require_once ROOT_DIR . '/services/MyResearch/lib/FavoriteHandler.php';

class EmailList extends Action {

	private $listId;

	function launch() {
		global $interface;

		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) $this->listId = $_REQUEST['listId'];
		// set the list id only if consists of only numbers

		// AJAX call
		if (isset($_GET['from'])) {
			$result = $this->sendEmail($_GET['to'], $_GET['from'], $_GET['message']);
			echo json_encode($result);

		// Traditional Form Submit (on browser-side javascript fail)
		} elseif (isset($_POST['from'])) {
			$result = $this->sendEmail($_POST['to'], $_POST['from'], $_POST['message']);

			// Reload List
			require_once 'MyList.php';
			header("Location:/MyAccount/MyList/" . $this->listId);
			die();

		} else { 			// Display Email Form
			$interface->assign('listId', strip_tags($_REQUEST['id']));
			$formDefinition = array(
					'title' => 'Email a list',
					'modalBody' => $interface->fetch('MyAccount/emailListPopup.tpl'),
					'modalButtons' => "<input type='submit' name='submit' value='Send' class='btn btn-primary' onclick='$(\"#emailListForm\").submit();'/>"
			);
			echo json_encode($formDefinition);
		}
	}

	function sendEmail($to, $from, $message) {
		global $interface;
		global $user;

		//Load the list
		$list = new UserList();
		$list->id = $this->listId;
		if ($list->find(true)){
			// Build Favorites List
			$titles = $list->getListTitles();
			$interface->assign('listEntries', $titles);

			// Load the User object for the owner of the list (if necessary):
			if ($list->public == true || ($user && $user->id == $list->user_id)) {
				//The user can access the list
				$favoriteHandler = new FavoriteHandler($titles, $user, $list->id, false);
				$titleDetails = $favoriteHandler->getTitles(count($titles)); // get all titles for email list, not just a page's worth
				$interface->assign('titles', $titleDetails);
				$interface->assign('list', $list);
			} else {
				$interface->assign('error', 'You do not have access to this list.');
			}
		}else{
			$interface->assign('error', 'Unable to read list');
		}

		//$interface->assign('from', $from);
		// not used in my-list.tpl  plb 10-7-2014

		if (strpos($message, 'http') === false && strpos($message, 'mailto') === false && $message == strip_tags($message)){
			$interface->assign('message', $message);
			$body = $interface->fetch('Emails/my-list.tpl');

			$mail = new VuFindMailer();
			$subject = $list->title;
			$emailResult = $mail->send($to, $from, $subject, $body);

			if ($emailResult === true){
				$result = array(
					'result' => true,
					'message' => 'Your e-mail was sent successfully.'
				);
			}elseif (PEAR_Singleton::isError($emailResult)){
				$result = array(
					'result' => false,
					'message' => "Your e-mail message could not be sent {$emailResult}."
				);
			}else{
				$result = array(
					'result' => false,
					'message' => 'Your e-mail message could not be sent due to an unknown error.'
				);
			}
		}else{
			$result = array(
				'result' => false,
				'message' => 'Sorry, we can&apos;t send e-mails with html or other data in it.'
			);
		}
		return $result;
	}

} */
?>