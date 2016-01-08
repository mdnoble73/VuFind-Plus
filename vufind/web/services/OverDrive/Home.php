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

require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';

class OverDrive_Home extends Action{
	/** @var  SearchObject_Solr $db */
	private $id;

	function launch(){
		global $interface;
		global $configArray;

		if (isset($_REQUEST['searchId'])){
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		}else if (isset($_SESSION['searchId'])){
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		$interface->assign('overDriveVersion', isset($configArray['OverDrive']['interfaceVersion']) ? $configArray['OverDrive']['interfaceVersion'] : 1);

		$this->id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $this->id);
		$recordDriver = new OverDriveRecordDriver($this->id);

		if (!$recordDriver->isValid()){
			$interface->setTemplate('../Record/invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}else{
			$interface->assign('recordDriver', $recordDriver);

			//Load status summary
			require_once (ROOT_DIR . '/Drivers/OverDriveDriverFactory.php');
			$driver = OverDriveDriverFactory::getDriver();
			$holdings = $driver->getHoldings($recordDriver);
			$scopedAvailability = $driver->getScopedAvailability($recordDriver);
			$holdingsSummary = $driver->getStatusSummary($this->id, $scopedAvailability, $holdings);
			if (PEAR_Singleton::isError($holdingsSummary)) {
				PEAR_Singleton::raiseError($holdingsSummary);
			}
			$interface->assign('holdingsSummary', $holdingsSummary);

			//Load the citations
			$this->loadCitations($recordDriver);

			// Retrieve User Search History
			$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ?
			$_SESSION['lastSearchURL'] : false);

			//Get Next/Previous Links
			$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init($searchSource);
			$searchObject->getNextPrevLinks();

			// Set Show in Main Details Section options for templates
			// (needs to be set before moreDetailsOptions)
			global $library;
			foreach ($library->showInMainDetails as $detailoption) {
				$interface->assign($detailoption, true);
			}

			$interface->assign('moreDetailsOptions', $recordDriver->getMoreDetailsOptions());

			// Display Page
//			$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
//			$interface->setPageTitle($recordDriver->getTitle());
//			$interface->assign('sidebar', 'OverDrive/full-record-sidebar.tpl');
//			$interface->setTemplate('view.tpl');
//			$interface->display('layout.tpl');

			$this->display('view.tpl', $recordDriver->getTitle());

		}
	}

	/**
	 * @param string $mainContentTemplate  Name of the SMARTY template file for the main content of the OverDrive Page
	 * @param string $pageTitle     What to display is the html title tag
	 * @param bool|true $sidebar    enables the account sidebar on the page to be displayed
	 */
//	function display($mainContentTemplate, $pageTitle='OverDrive', $sidebar=true) {
//		global $interface;
////		if ($sidebar) $interface->assign('sidebar', 'OverDrive/full-record-sidebar.tpl');
//		if ($sidebar) $interface->assign('sidebar', 'Search/home-sidebar.tpl');
////		TODO: is this the best template to use?
//		$interface->setTemplate($mainContentTemplate);
//		$interface->setPageTitle($pageTitle);
//		$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
//		$interface->display('layout.tpl');
//	}

	/**
	 * @param OverDriveRecordDriver $recordDriver
	 */
	function loadCitations($recordDriver){
		global $interface;

		$citationCount = 0;
		$formats = $recordDriver->getCitationFormats();
		foreach($formats as $current) {
			$interface->assign(strtolower($current), $recordDriver->getCitation($current));
			$citationCount++;
		}
		$interface->assign('citationCount', $citationCount);
	}
}