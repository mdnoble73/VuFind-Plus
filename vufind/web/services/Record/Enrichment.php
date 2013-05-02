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

require_once ROOT_DIR . '/sys/Amazon.php';
require_once ROOT_DIR . '/sys/Proxy_Request.php';
require_once ROOT_DIR . '/sys/Novelist.php';

class Record_Enrichment
{
	/**
	 * Load information from the review provider and update the interface with the data.
	 *
	 * @param  string   $isbn The ISBN to load data for
	 * @return array       Returns array with review data, otherwise a
	 *                      PEAR_Error.
	 */
	static function loadEnrichment($isbn)
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
				if (method_exists(new Record_Enrichment(), $func)){
					$enrichment[$func] = Record_Enrichment::$func($isbn);
	
					// If the current provider had no valid reviews, store nothing:
					if (empty($enrichment[$func]) || PEAR_Singleton::isError($enrichment[$func])) {
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
	 * @param   string    $isbn The ISBN to return data for
	 * @return  array       Returns array with enrichment information, otherwise a
	 *                      PEAR_Error.
	 * @access  public
	 * @author  Mark Noble <mnoble@turningleaftech.com>
	 */
	function novelist($isbn)
	{
		$novelist = NovelistFactory::getNovelist();;
		return $novelist->loadEnrichment($isbn);
	}


}