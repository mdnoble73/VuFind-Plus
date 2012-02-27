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

class Rate extends Action
{
	private $user;

	function __construct()
	{
		$this->user = UserAccount::isLoggedIn();
	}

	function launch()
	{
		global $interface;
		global $configArray;

		if (isset($_REQUEST['rating'])){
			$rating = $_REQUEST['rating'];
			$interface->assign('rating', $rating);
		}

		$id = $_REQUEST['id'];

		// Check if user is logged in
		if (!$this->user) {
			// Needed for "back to record" link in view-alt.tpl:
			$interface->assign('id', $id);

			//Display the login form
			$login = $interface->fetch('EContentRecord/ajax-rate-login.tpl');
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			echo json_encode(array(
              'result' => 'true',
              'loginForm' => $login,
			));
			exit();
		}

		if (isset($_GET['submit'])) {
			//Save the rating
			require_once('sys/eContent/EContentRating.php');
			$eContentRating = new EContentRating();
			$eContentRating->rating = $rating;
			$eContentRating->recordId = $id;
			$eContentRating->userId = $this->user->id;
			$eContentRating->dateRated = time();
			$eContentRating->insert();
			
			return json_encode(array(
		          'result' => 'true',
		          'rating' => $rating,
			));
		}
	}
}