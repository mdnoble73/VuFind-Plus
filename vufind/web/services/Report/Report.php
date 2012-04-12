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

require_once 'Action.php';

require_once 'CatalogConnection.php';


class Report extends Action
{
	protected $db;
	protected $catalog;

	function __construct()
	{
		global $interface;
		global $configArray;
		global $user;

		if (!UserAccount::isLoggedIn()) {
			header("Location: " . $configArray['Site']['url'] . "/MyResearch/Home");
		}

	}

	/**
	 * Log the current user into the catalog using stored credentials; if this
	 * fails, clear the user's stored credentials so they can enter new, corrected
	 * ones.
	 *
	 * @access  protected
	 * @return  mixed               $user array (on success) or false (on failure)
	 */
	protected function catalogLogin()
	{
		global $user;

		if ($this->catalog->status) {
			if ($user->cat_username) {
				$patron = $this->catalog->patronLogin($user->cat_username,
				$user->cat_password);
				if (empty($patron) || PEAR::isError($patron)) {
					// Problem logging in -- clear user credentials so they can be
					// prompted again; perhaps their password has changed in the
					// system!
					unset($user->cat_username);
					unset($user->cat_password);
				} else {
					return $patron;
				}
			}
		}

		return false;
	}
}