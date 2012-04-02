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

require_once 'CatalogConnection.php';

require_once 'sys/eContent/EContentRecord.php';
require_once 'Drivers/EContentDriver.php';

require_once 'sys/Mailer.php';

class SMS extends Action {
	private $sms;

	function __construct() {
		parent::__construct();
		$this->sms = new SMSMailer();
	}

	function launch() {
		global $interface;

		if (isset($_POST['submit'])) {
			$result = $this->sendSMS();
			if (PEAR::isError($result)) {
				$interface->assign('error', $result->getMessage());
			}
			$interface->assign('subTemplate', 'sms-status.tpl');
			$interface->setTemplate('view-alt.tpl');
			$interface->display('layout.tpl');
		} else {
			return $this->display();
		}
	}

	function display() {
		global $interface;

		$interface->assign('id', $_REQUEST['id']);
		$interface->assign('carriers', $this->sms->getCarriers());
		$interface->assign('formTargetPath',
            '/EcontentRecord/' . urlencode($_GET['id']) . '/SMS');

		if (isset($_GET['lightbox'])) {
			// Use for lightbox
			$interface->assign('title', $_GET['message']);
			return $interface->fetch('EContentRecord/sms.tpl');
		} else {
			// Display Page
			$interface->setPageTitle('Text this');
			$interface->assign('subTemplate', 'sms.tpl');
			$interface->setTemplate('view-alt.tpl');
			$interface->display('layout.tpl', 'RecordSMS' . $_GET['id']);
		}
	}

	// Email SMS
	function sendSMS() {
		global $configArray;
		global $interface;

		// Get Holdings
		$driver = new EContentDriver();
		
		$id = strip_tags($_REQUEST['id']);
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $id;
		$eContentRecord->find(true);
		$holdings = $driver->getHolding($id);
		$holdingsSummary = $driver->getStatusSummary($id, $holdings);
		if (PEAR::isError($holdingsSummary)) {
			return $holdingsSummary;
		}

		if ($holdingsSummary['status']){
			$interface->assign('status', $holdingsSummary['status']);
		}
		$interface->assign('title', $eContentRecord->title);
		$interface->assign('author', $eContentRecord->author);
		$interface->assign('recordID', $_GET['id']);
		$message = $interface->fetch('Emails/eContent-sms.tpl');

		return $this->sms->text($_REQUEST['provider'], $_REQUEST['to'],
		$configArray['Site']['email'], $message);
	}
}