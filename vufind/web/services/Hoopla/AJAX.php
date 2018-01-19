<?php
/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 1/8/2018
 *
 */


class Hoopla_AJAX extends Action
{
	function launch() {
		global $timer;
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");

		// Methods intend to return JSON data
		if (in_array($method, array(
			'reloadCover',
			'checkOutHooplaTitle', 'getHooplaCheckOutPrompt', 'returnHooplaTitle'
		))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo json_encode($this->$method());

			// Methods that return HTML
		}else if (in_array($method, array(
		))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if ($method == 'downloadMarc'){
			echo $this->$method();

			// Methods that return XML (default mode)
		}else{
			header ('Content-type: text/xml');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

			$xmlResponse = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
			$xmlResponse .= "<AJAXResponse>\n";
			if (is_callable(array($this, $_GET['method']))) {
				$xmlResponse .= $this->$_GET['method']();
			} else {
				$xmlResponse .= '<Error>Invalid Method</Error>';
			}
			$xmlResponse .= '</AJAXResponse>';

			echo $xmlResponse;
		}
	}

	function downloadMarc(){
		$id = $_REQUEST['id'];
		$marcData = MarcLoader::loadMarcRecordByILSId($id);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header("Content-Disposition: attachment; filename={$id}.mrc");
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');

		header('Content-Length: ' . strlen($marcData->toRaw()));
		ob_clean();
		flush();
		echo($marcData->toRaw());
	}

	function reloadCover(){
		require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
		$id = $_REQUEST['id'];
		$recordDriver = new MarcRecord($id);

		//Reload small cover
		$smallCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('small')) . '&reload';
		file_get_contents($smallCoverUrl);

		//Reload medium cover
		$mediumCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('medium')) . '&reload';
		file_get_contents($mediumCoverUrl);

		//Reload large cover
		$largeCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('large')) . '&reload';
		file_get_contents($largeCoverUrl);

		//Also reload covers for the grouped work
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkDriver = new GroupedWorkDriver($recordDriver->getGroupedWorkId());
		global $configArray;
		//Reload small cover
		$smallCoverUrl = $configArray['Site']['coverUrl'] . str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('small')) . '&reload';
		file_get_contents($smallCoverUrl);

		//Reload medium cover
		$mediumCoverUrl = $configArray['Site']['coverUrl'] . str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('medium')) . '&reload';
		file_get_contents($mediumCoverUrl);

		//Reload large cover
		$largeCoverUrl = $configArray['Site']['coverUrl'] . str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('large')) . '&reload';
		file_get_contents($largeCoverUrl);

		return array('success' => true, 'message' => 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.');
	}


	/**
	 * @return array
	 */
	function getHooplaCheckOutPrompt(){
		$user = UserAccount::getLoggedInUser();
		if ($user) {
			$hooplaUsers = $user->getRelatedHooplaUsers();

			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();

			$id = $_REQUEST['id'];
			if (strpos($id, ':') !== false) {
				list(, $id) = explode(':', $id);
			}
			if ($id) {
				global $interface;
				$interface->assign('hooplaId', $id);

				//TODO: need to determine what happens to cards without a Hoopla account
				$hooplaUserStatuses = array();
				foreach ($hooplaUsers as $tmpUser) {
					$checkOutStatus                   = $driver->getHooplaPatronStatus($tmpUser);
					$hooplaUserStatuses[$tmpUser->id] = $checkOutStatus;
				}

				if (count($hooplaUsers) > 1) {
					$interface->assign('hooplaUsers', $hooplaUsers);
					$interface->assign('hooplaUserStatuses', $hooplaUserStatuses);

					return
						array(
							'title'   => 'Hoopla Check Out',
							'body'    => $interface->fetch('Hoopla/ajax-hoopla-checkout-prompt.tpl'),
							'buttons' => '<button class="btn btn-primary" type= "button" title="Check Out" onclick="return VuFind.Hoopla.checkOutHooplaTitle(\'' . $id . '\');">Check Out</button>'
						);
				} elseif (count($hooplaUsers) == 1) {
					$hooplaUser = reset($hooplaUsers);
					if ($hooplaUser->id != $user->id) {
						$interface->assign('hooplaUser', $hooplaUser); // Display the account name when not using the main user
					}
					$checkOutStatus = $hooplaUserStatuses[$hooplaUser->id];
					$interface->assign('hooplaPatronStatus', $checkOutStatus);

					return
						array(
							'title'   => 'Confirm Hoopla Check Out',
							'body'    => $interface->fetch('Hoopla/ajax-hoopla-single-user-checkout-prompt.tpl'),
							'buttons' => '<button class="btn btn-primary" type= "button" title="Check Out" onclick="return VuFind.Hoopla.checkOutHooplaTitle(\'' . $id . '\', ' . $hooplaUser->id . ');">Check Out</button>'
						);
				} else {
					// No Hoopla Account Found, give the user an error message
					global $logger;
					$logger->log('No valid Hoopla account was found to check out a Hoopla title.', PEAR_LOG_ERR);
					return
						array(
							'title'   => 'Invalid Hoopla Account',
							'body'    => '<p class="alert alert-danger">The barcode or library for this account is not valid for Hoopla.</p>',
							'buttons' => ''
						);
				}
			}
		}
	}

	function checkOutHooplaTitle() {
		$user = UserAccount::getLoggedInUser();
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron   = $user->getUserReferredTo($patronId);
			if ($patron) {
				$id = $_REQUEST['id'];
				require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
				$driver = new HooplaDriver();
				$result = $driver->checkoutHooplaItem($id, $patron);
				if ($result['success']) {
					global $interface;
					$checkOutStatus = $driver->getHooplaPatronStatus($user);
					$interface->assign('hooplaPatronStatus', $checkOutStatus);
					$title = empty($result['title']) ? "Title checked out successfully" : $result['title'] . " checked out successfully";
					return array(
						'success' => true,
						'title'   => $title,
						'message' => $interface->fetch('Hoopla/hoopla-checkout-success.tpl'),
						'buttons' => '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">View My Check Outs</a>'
					);
				} else {
					return $result;
				}
			}else{
				return array('success'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to checkout titles for that user.');
			}
		}else{
			return array('success'=>false, 'message'=>'You must be logged in to checkout an item.');
		}
	}

	function returnHooplaTitle() {
		$user = UserAccount::getLoggedInUser();
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron   = $user->getUserReferredTo($patronId);
			if ($patron) {
				$id = $_REQUEST['id'];
				require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
				$driver = new HooplaDriver();
				$result = $driver->returnHooplaItem($id, $patron);
				return $result;
			}else{
				return array('success'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to return titles for that user.');
			}
		}else{
			return array('success'=>false, 'message'=>'You must be logged in to return an item.');
		}
	}

}