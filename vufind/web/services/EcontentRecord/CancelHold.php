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

require_once 'services/MyResearch/lib/Resource.php';
require_once 'services/MyResearch/lib/User.php';

class CancelHold extends Action
{
	/**
	 * Process notifications from the ACS server when an item is checked out
	 * or returned.
	 **/
	function launch(){
		global $configArray;
		global $user;
		
		$id = $_REQUEST['id'];

		//Setup JSON response
		header('Content-type: text/plain');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		
		require_once('Drivers/EContentDriver.php');
		$driver = new EContentDriver();
		$result = $driver->cancelHold($id);
		
		echo json_encode($result);
		exit();
	}
}