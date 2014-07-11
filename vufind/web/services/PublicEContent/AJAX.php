<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/RecordDrivers/PublicEContentDriver.php';

global $configArray;

class PublicEContent_AJAX extends Action {

	function AJAX() {
	}

	function launch() {
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];

		header('Content-type: text/plain');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		echo $this->$method();
	}

	function checkout(){
		$recordId = $_REQUEST['id'];
		$itemId = $_REQUEST['itemId'];

		require_once ROOT_DIR . '/RecordDrivers/PublicEContentDriver.php';
		$recordDriver = new PublicEContentDriver($recordId);
		$result = $recordDriver->checkout($itemId);
		return json_encode($result);
	}

	function returnTitle(){
		$recordId = $_REQUEST['id'];
		$itemId = $_REQUEST['itemId'];

		require_once ROOT_DIR . '/RecordDrivers/PublicEContentDriver.php';
		$recordDriver = new PublicEContentDriver($recordId);
		$result = $recordDriver->returnTitle($itemId);
		return json_encode($result);
	}
}