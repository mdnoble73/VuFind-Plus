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

require_once 'Record.php';

class Holdings extends Record
{
	function launch()
	{
		global $interface;
		global $configArray;

		// Do not cache holdings page
		$interface->caching = 0;

		$interface->setPageTitle('Title: ' . $this->record['title_short']);

		$this->loadHoldings($this->id);

		global $module;
		global $action;
		if ($module == 'Record' && $action == 'Holdings'){
			$interface->assign('subTemplate', 'view-holdings.tpl');
		}
		$interface->setTemplate('view.tpl');

		// Display Page
		$interface->display('layout.tpl');
	}

	function loadHoldings($id)
	{
		global $interface;
		global $configArray;
		global $library;
		$showCopiesLineInHoldingsSummary = true;
		$showCheckInGrid = true;
		if ($library && $library->showCopiesLineInHoldingsSummary == 0){
			$showCopiesLineInHoldingsSummary = false;
		}
		$interface->assign('showCopiesLineInHoldingsSummary', $showCopiesLineInHoldingsSummary);
		if ($library && $library->showCheckInGrid == 0){
			$showCheckInGrid = false;
		}
		$interface->assign('showCheckInGrid', $showCheckInGrid);

		try {
			$catalog = new CatalogConnection($configArray['Catalog']['driver']);
		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}

		$holdingData = new stdClass();
		// Get Holdings Data
		if ($catalog->status) {
			$result = $catalog->getHolding($id);
			if (PEAR::isError($result)) {
				PEAR::raiseError($result);
			}
			if (count($result)) {
				$holdings = array();
				$issueSummaries = array();
				foreach ($result as $copy) {
					if (isset($copy['type']) && $copy['type'] == 'issueSummary'){
						$issueSummaries = $result;
						break;
					}else{
						$holdings[$copy['location']][] = $copy;
					}
				}
				if (isset($issueSummaries) && count($issueSummaries) > 0){
					$interface->assign('issueSummaries', $issueSummaries);
					$holdingData->issueSummaries = $issueSummaries;
				}else{
					$interface->assign('holdings', $holdings);
					$holdingData->holdings = $holdings;
				}
			}else{
				$interface->assign('holdings', array());
				$holdingData->holdings = array();
			}

			// Get Acquisitions Data
			$result = $catalog->getPurchaseHistory($id);
			if (PEAR::isError($result)) {
				PEAR::raiseError($result);
			}
			$interface->assign('history', $result);
			$holdingData->history = $result;

			//Holdings summary
			$result = $catalog->getStatusSummary($id);
			if (PEAR::isError($result)) {
				PEAR::raiseError($result);
			}
			$holdingData->holdingsSummary = $result;
			$interface->assign('holdingsSummary', $result);

			$interface->assign('formattedHoldingsSummary', $interface->fetch('Record/holdingsSummary.tpl'));
		}

		return $holdingData;
	}
}