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

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Proxy_Request.php';

class Resource_AJAX extends Action {

	function Resource_AJAX() {
	}

	function launch() {
		global $timer;
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");
		if (in_array($method, array('SaveRecord', 'SaveTag', 'GetTags', 'MarkNotInterested', 'ClearNotInterested'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if (in_array($method, array('GetAddTagForm', 'GetNovelistData', 'GetReviewForm'))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}
	}

	// Saves a Record to User's List
	function SaveRecord()
	{
		require_once ROOT_DIR . '/services/Resource/Save.php';
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';

		$result = array();
		if (UserAccount::isLoggedIn()) {
			$saveService = new Save();
			$saveResult = $saveService->saveRecord();
			if (!PEAR_Singleton::isError($result) && $saveResult == true) {
				$result['result'] = "Done";
			} else {
				$result['result'] = "Error";
			}
		} else {
			$result['result'] = "Unauthorized";
		}
		return json_encode($result);
	}


}