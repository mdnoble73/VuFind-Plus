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
		global $interface;
		$device = $_REQUEST['device'];
		$format = $_REQUEST['format'];
		$result = array();
		if ($format == 'kindle'){
			if ($device == 'kindle' || $device == 'kindle_fire'){
				$result['helpText'] = $interface->fetch("Help/en/ebook_kindle.tpl");
			}else{
				$result['helpText'] = $interface->fetch("Help/en/overdrive_kindle_help.tpl");
			}
		}elseif ($format == 'ebook' ){
			if ($device == 'kindle_fire' || $device == 'kindle'){
				$result['helpText'] = $interface->fetch("Help/en/econtent_unsupported.tpl");
			}elseif ($device == 'android' || $device == 'ios'){
				$result['helpText'] = $interface->fetch("Help/en/ebook_mobile.tpl");
			}else{
				$result['helpText'] = $interface->fetch("Help/en/ebook_pc_mac.tpl");
			}
		}elseif ($format == 'springerlink'){
			$result['helpText'] = $interface->fetch("Help/en/springerlink.tpl");
		}elseif ($format == 'ebsco'){
			$result['helpText'] = $interface->fetch("Help/en/ebsco.tpl");
		}elseif	($format == 'wma') {
			if ($device == 'kindle' || $device == 'kindle_fire' || $device == 'mac' || $device == 'nook'){
				$result['helpText'] = $interface->fetch("Help/en/wma_sucks.tpl");
			}else if ($device == 'pc'){
				$result['helpText'] = $interface->fetch("Help/en/audiobook_pc.tpl");
			}
		}elseif ($format == 'mp3'){
			if ($device == 'pc'){
				$result['helpText'] = $interface->fetch("Help/en/audiobook_pc.tpl");
			}elseif ($device == 'mac'){
				$result['helpText'] = $interface->fetch("Help/en/mp3_mac.tpl");
			}elseif ($device == 'kindle'){
				$result['helpText'] = $interface->fetch("Help/en/audiobook_pc.tpl");
			}elseif ($device == 'kindle_fire'){
				$result['helpText'] = $interface->fetch("Help/en/mp3_kindle_fire.tpl");
			}else{
				$result['helpText'] = $interface->fetch("Help/en/audiobook_mobile.tpl");
			}
		}elseif ($format == 'eVideo' ){
			if ($device == 'pc'){
				$result['helpText'] = $interface->fetch("Help/en/evideo_pc.tpl");
			}elseif ($device == 'mac'){
				$result['helpText'] = $interface->fetch("Help/en/evideo_mac.tpl");
			}else{
				$result['helpText'] = $interface->fetch("Help/en/evideo_mobile.tpl");
			}
		}elseif ($format == 'eMusic' ){
			if ($device == 'pc'){
				$result['helpText'] = $interface->fetch("Help/en/emusic_pc.tpl");
			}elseif ($device == 'mac'){
				$result['helpText'] = $interface->fetch("Help/en/emusic_mac.tpl");
			}else{
				$result['helpText'] = $interface->fetch("Help/en/emusic_mobile.tpl");
			}
		}else{
			$result['helpText'] = $interface->fetch("Help/en/no_econtent_help.tpl");
		}
		echo json_encode($result);
	}

}
