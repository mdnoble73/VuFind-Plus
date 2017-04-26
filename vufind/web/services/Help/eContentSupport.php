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

class eContentSupport extends Action
{
	function launch()
	{
		global $interface;
		global $configArray;
		global $user;
		global $analytics;
		$interface->setPageTitle('eContent Support');

		if (isset($_REQUEST['submit'])){
			//E-mail the library with details of the support request
			require_once ROOT_DIR . '/sys/Mailer.php';
			$mail = new VuFindMailer();
			$userLibrary = Library::getPatronHomeLibrary();
			if (!empty($userLibrary->eContentSupportAddress)){
				$to = $userLibrary->eContentSupportAddress;
			}elseif (!empty($configArray['Site']['email'])){
				$to = $configArray['Site']['email'];
			} else {
				echo(json_encode(array(
					'title' => "Support Request Not Sent",
					'message' => "<p>We're sorry, but your request could not be submitted because we do not have a support email address on file.</p><p>Please contact your local library.</p>"
				)));
			}

			$name = $_REQUEST['name'];
			$interface->assign('bookAuthor', $_REQUEST['bookAuthor']);
			$interface->assign('device', $_REQUEST['device']);
			$interface->assign('format', $_REQUEST['format']);
			$interface->assign('operatingSystem', $_REQUEST['operatingSystem']);
			$interface->assign('problem', $_REQUEST['problem']);

			$subject = 'eContent Support Request from ' . $name;
			$from = $_REQUEST['email'];

			$interface->assign('name', $name);
			$interface->assign('email', $from);

			$body = $interface->fetch('Help/eContentSupportEmail.tpl');
			//TODO: possibly remove $from as the Reply-To to prevent getting intercepted by spam blockers
//			if ($mail->send($to, $to, $subject, $body, $from)){
			if ($mail->send($to, $to, $subject, $body)){
				$analytics->addEvent("Emails", "eContent Support Succeeded", $_REQUEST['device'], $_REQUEST['format'], $_REQUEST['operatingSystem']);
				echo(json_encode(array(
					'title' => "Support Request Sent",
					'message' => "<p>Your request was sent to our support team.  We will respond to your request as quickly as possible.</p><p>Thank you for using the catalog.</p>"
				  ,'body' => $body //TODO: remove this
				)));
			}else{
				$analytics->addEvent("Emails", "eContent Support Failed", $_REQUEST['device'], $_REQUEST['format'], $_REQUEST['operatingSystem']);
				echo(json_encode(array(
						'title' => "Support Request Not Sent",
						'message' => "<p>We're sorry, but your request could not be submitted to our support team at this time.</p><p>Please try again later.</p>"
				)));
			}
		}else{
			if (isset($_REQUEST['lightbox'])){
				$interface->assign('lightbox', true);
				if ($user){
					$name = $user->firstname .' '. $user->lastname;
					$interface->assign('name', $name);
					$interface->assign('email', $user->email);
				}
				$result = array(
						'title' => 'eContent Support',
						'modalBody' => $interface->fetch('Help/eContentSupport.tpl'),
						'modalButtons' => "<button class='btn btn-sm btn-primary' onclick='return VuFind.EContent.submitHelpForm();'>Submit</button>",
				);
				echo json_encode($result);
			}else{
				$interface->assign('lightbox', false);
				$this->display('eContentSupport.tpl', 'eContent Support');
			}
		}
	}
}

