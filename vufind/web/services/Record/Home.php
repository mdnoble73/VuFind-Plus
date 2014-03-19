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
require_once 'Reviews.php';
require_once 'UserComments.php';
require_once 'Cite.php';
require_once 'Holdings.php';
require_once(ROOT_DIR . '/sys/LocalEnrichment/EditorialReview.php');

class Record_Home extends Record_Record{
	function launch(){
		global $interface;
		global $timer;
		global $configArray;
		global $user;

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

		if (!isset($this->isbn)){
			$interface->assign('showOtherEditionsPopup', false);
		}

		$interface->assign('moreDetailsOptions', $this->recordDriver->getMoreDetailsOptions());

		//Build the actual view
		$interface->assign('sidebar', 'Record/full-record-sidebar.tpl');
		$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
		$interface->setTemplate('view.tpl');

		$interface->setPageTitle($this->recordDriver->getTitle());

		// Display Page
		$interface->display('layout.tpl');

	}


	/**
	 * Load information from the review provider and update the interface with the data.
	 *
	 * @return array       Returns array with review data, otherwise a
	 *                      PEAR_Error.
	 */
	function loadEnrichment(){
		global $interface;
		global $configArray;

		// Fetch from provider
		if (isset($configArray['Content']['enrichment'])) {
			$providers = explode(',', $configArray['Content']['enrichment']);
			foreach ($providers as $provider) {
				$provider = explode(':', trim($provider));
				$func = strtolower($provider[0]);
				$enrichment[$func] = $this->$func();

				// If the current provider had no valid reviews, store nothing:
				if (empty($enrichment[$func]) || PEAR_Singleton::isError($enrichment[$func])) {
					unset($enrichment[$func]);
				}
			}
		}

		if ($enrichment) {
			$interface->assign('enrichment', $enrichment);
		}

		return $enrichment;
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