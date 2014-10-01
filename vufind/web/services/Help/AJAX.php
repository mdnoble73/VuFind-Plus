<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Proxy_Request.php';
require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';

global $configArray;

class Help_AJAX extends Action {

	function AJAX() {

	}

	function launch() {
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];
		if (in_array($method, array('getHelpTopic', 'getSupportForm'))){
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
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

	function getSupportForm(){
		global $interface;
		$results = array(
				'title' => 'eContent Support Request',
				'modalBody' => $interface->fetch('Help/eContentSupport.tpl'),
				'modalButtons' => "<span class='tool btn btn-primary' onclick='$(\"#eContentSupport\").submit(); return false;'>Submit</span>"
		);
		return json_encode($results);
	}

	function getHelpTopic(){
		// used to call eContent Help. This content has ben removed.
	}
}
