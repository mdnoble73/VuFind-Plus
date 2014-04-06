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
			if ($userLibrary == null){
				$to = $configArray['Site']['email'];
			}else{
				$to = $userLibrary->eContentSupportAddress;
			}
			$name = $_REQUEST['name'];
			$interface->assign('bookAuthor', $_REQUEST['bookAuthor']);
			$interface->assign('device', $_REQUEST['device']);
			$interface->assign('format', $_REQUEST['format']);
			$interface->assign('operatingSystem', $_REQUEST['operatingSystem']);
			$interface->assign('problem', $_REQUEST['problem']);

			$subject = 'eContent Support Request from ' . $name;
			$from = $_REQUEST['email'];

			$body = $interface->fetch('Help/eContentSupportEmail.tpl');
			if ($mail->send($to, $configArray['Site']['email'], $subject, $body, $from)){
				$analytics->addEvent("Emails", "eContent Support Succeeded", $_REQUEST['device'], $_REQUEST['format'], $_REQUEST['operatingSystem']);
				echo("<p>Your request was sent to our support team.  We will respond to your request as quickly as possible.</p><p>Thank you for using the catalog.</p><input type='button' onclick='hideLightbox()' value='Close'/>");
			}else{
				$analytics->addEvent("Emails", "eContent Support Failed", $_REQUEST['device'], $_REQUEST['format'], $_REQUEST['operatingSystem']);
				echo("<p>We're sorry, but your request could not be submitted to our support team at this time.</p><p>Please try again later.</p><input type='button' onclick='hideLightbox()' value='Close' />");
			}
		}else{
			if (isset($_REQUEST['lightbox'])){
				$interface->assign('lightbox', true);
				if ($user){
					$interface->assign('name', $user->cat_username);
					$interface->assign('email', $user->email);
				}
				$interface->assign('popupTitle', 'eContent Support');
				$popupContent = $interface->fetch('Help/eContentSupport.tpl');
				$interface->assign('popupContent', $popupContent);
				$interface->display('popup-wrapper.tpl');
			}else{
				$interface->assign('lightbox', false);
				$interface->setTemplate('eContentSupport.tpl');
				$interface->assign('sidebar', 'Search/home-sidebar.tpl');
				$interface->display('layout.tpl');
			}
		}
	}
}

?>
