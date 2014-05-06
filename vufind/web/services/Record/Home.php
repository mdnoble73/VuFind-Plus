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

require_once 'Record.php';

class Record_Home extends Record_Record{
	function launch(){
		global $interface;
		global $timer;

		$recordId = $this->id;

		$this->loadCitations();
		$timer->logTime('Loaded Citations');

		if (isset($_REQUEST['searchId'])){
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		}else if (isset($_SESSION['searchId'])){
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		$interface->assign('recordId', $recordId);

		$interface->assign('moreDetailsOptions', $this->recordDriver->getMoreDetailsOptions());

		//Build the actual view
		$interface->assign('sidebar', 'Record/full-record-sidebar.tpl');
		$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
		$interface->setTemplate('view.tpl');

		$interface->setPageTitle($this->recordDriver->getTitle());

		// Display Page
		$interface->display('layout.tpl');

	}

	function loadCitations(){
		global $interface;

		$citationCount = 0;
		$formats = $this->recordDriver->getCitationFormats();
		foreach($formats as $current) {
			$interface->assign(strtolower($current),
					$this->recordDriver->getCitation($current));
			$citationCount++;
		}
		$interface->assign('citationCount', $citationCount);
	}
}