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

require_once 'sys/Amazon.php';
require_once 'sys/Proxy_Request.php';
require_once 'sys/Novelist.php';

require_once 'sys/eContent/EContentRecord.php';

class Enrichment
{
	function launch()
	{
		global $interface;
		global $configArray;
		global $library;

		if (!$interface->is_cached($this->cacheId)) {
			$interface->setPageTitle('Extra Information: ' . $this->record['title_short']);

			//Load the data for the reviews and populate in the user interface
			$this->loadData();

			$interface->assign('subTemplate', 'view-series.tpl');
			$interface->setTemplate('view.tpl');
		}
		
		if (isset($library)){
			$interface->assign('showSeriesAsTab', $library->showSeriesAsTab);
		}else{
			$interface->assign('showSeriesAsTab', 0);
		}

		// Display Page
		$interface->display('layout.tpl', $this->cacheId);
	}

	/**
	 * Load information from the review provider and update the interface with the data.
	 *
	 * @return array       Returns array with review data, otherwise a
	 *                      PEAR_Error.
	 */
	function loadEnrichment($isbn)
	{
		global $interface;
		global $configArray;

		$enrichment = array();
		// Fetch from provider
		if (isset($configArray['Content']['enrichment'])) {
			$providers = explode(',', $configArray['Content']['enrichment']);
			foreach ($providers as $provider) {
				$provider = explode(':', trim($provider));
				$func = strtolower($provider[0]);
				if (method_exists(new Enrichment(), $func)){
					$enrichment[$func] = Enrichment::$func($isbn);
	
					// If the current provider had no valid reviews, store nothing:
					if (empty($enrichment[$func]) || PEAR::isError($enrichment[$func])) {
						unset($enrichment[$func]);
					}
				}
			}
		}

		if ($enrichment) {
			$interface->assign('enrichment', $enrichment);
		}

		return $enrichment;
	}

	/**
	 * novelist
	 *
	 * This method is responsible for fetching enrichment information from NoveList
	 * uses the REST Interface provided by NoveList
	 *
	 * @return  array       Returns array with enrichment information, otherwise a
	 *                      PEAR_Error.
	 * @access  public
	 * @author  Mark Noble <mnoble@turningleaftech.com>
	 */
	function novelist($isbn)
	{
		$novelist = new Novelist();
		return $novelist->loadEnrichment($isbn);
	}


}