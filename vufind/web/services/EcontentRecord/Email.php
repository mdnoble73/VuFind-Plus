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

require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';
require_once ROOT_DIR . '/sys/Mailer.php';
require_once ROOT_DIR . '/Drivers/EContentDriver.php';

class EcontentRecord_Email extends Action
{
	function launch()
	{
		global $interface;
		global $configArray;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		if (isset($_POST['submit'])) {
			$result = $this->sendEmail($_POST['to'], $_POST['from'], $_POST['message']);
			if (!PEAR_Singleton::isError($result)) {
				require_once 'Home.php';
				EcontentRecord_Home::launch();
				exit();
			} else {
				$interface->assign('message', $result->getMessage());
			}
		}

		// Display Page
		if (isset($_GET['lightbox'])) {
			$interface->assign('lightbox', true);
			echo $interface->fetch('EcontentRecord/email.tpl');
		} else {
			$interface->setPageTitle('Email Record');
			$interface->assign('subTemplate', 'email.tpl');
			$interface->setTemplate('view-alt.tpl');
			$interface->display('layout.tpl', 'RecordEmail' . $_GET['id']);
		}
	}

	function sendEmail($to, $from, $message)
	{
		global $interface;
		global $configArray;

		$id = $_REQUEST['id'];
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $id;
		$eContentRecord->find(true);

		$subject = translate("Library Catalog Record") . ": " . $eContentRecord->title;
		$interface->assign('from', $from);
		$emailDetails = $eContentRecord->title . "\n";
		if (strlen($eContentRecord->author) > 0){
			$emailDetails .= "by: {$eContentRecord->author}\n";
		}
		$interface->assign('emailDetails', $emailDetails );
		$interface->assign('id', $id);
		//Check for spam
		if (strpos($message, 'http') === false && strpos($message, 'mailto') === false && $message == strip_tags($message)){
			$interface->assign('message', $message);
			$body = $interface->fetch('Emails/eContent-record.tpl');

			$mail = new VuFindMailer();
			return $mail->send($to, $configArray['Site']['email'], $subject, $body, $from);
		}else{
			//This looks like spam
			return false;
		}

	}
}
?>
