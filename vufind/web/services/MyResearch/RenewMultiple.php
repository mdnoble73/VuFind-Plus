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

require_once 'Action.php';

class RenewMultiple extends Action
{
	function launch()
	{
		global $configArray;
		global $user;

		try {
			$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);
		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}

		//Renew the hold
		if (method_exists($this->catalog->driver, 'renewItem')) {
			$selectedItems = $_GET['selected'];
			$renewMessages = array();
			foreach ($selectedItems as $itemInfo => $selectedState){
				list($itemId, $itemIndex) = explode('|', $itemInfo);
				$renewResult = $this->catalog->driver->renewItem($user->password, $itemId, $itemIndex);
				$_SESSION['renewResult'][$renewResult['itemId']] = $renewResult;
			}
		} else {
			PEAR::raiseError(new PEAR_Error('Cannot Renew Item - ILS Not Supported'));
		}

		//Redirect back to the hold screen with status from the renewal
		header("Location: " . $configArray['Site']['url'] . '/MyResearch/CheckedOut');
	}

}