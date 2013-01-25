<?php
require_once 'Action.php';
require_once 'sys/Proxy_Request.php';
require_once 'sys/eContent/EContentRecord.php';

global $configArray;

class AJAX extends Action {

	function AJAX() {
	}

	function launch() {
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];
		if (in_array($method, array('getHelpTopic'))){
			header('Content-type: text/plain');
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

	function getHelpTopic(){
		global $interface;
		global $logger;
		$device = $_REQUEST['device'];
		$format = $_REQUEST['format'];
		$result = array();
		if ($format == 'kindle'){
			if ($device == 'kindle' || $device == 'kindle_fire'){
				$result['helpText'] = $interface->fetch("Help/en/ebook_kindle.tpl");
			}else{
				$result['helpText'] = $interface->fetch("Help/en/econtent_unsupported.tpl");
			}
		}elseif ($format == 'ebook' ){
			if ($device == 'kindle_fire' || $device == 'kindle'){
				$result['helpText'] = $interface->fetch("Help/en/econtent_unsupported.tpl");
			}elseif ($device == 'android' || $device == 'ios'){
				$result['helpText'] = $interface->fetch("Help/en/ebook_mobile.tpl");
			}else{
				$result['helpText'] = $interface->fetch("Help/en/ebook_pc_mac.tpl");
			}
		}elseif ($format == 'mp3' || $format == 'wma' ){
			if ($device == 'pc'){
				$result['helpText'] = $interface->fetch("Help/en/audiobook_pc.tpl");
			}elseif ($device == 'mac'){
				$result['helpText'] = $interface->fetch("Help/en/audiobook_mac.tpl");
			}elseif ($device == 'kindle_fire'){
				if ($format == 'mp3' ){
					$result['helpText'] = $interface->fetch("Help/en/mp3_kindle_fire.tpl");
				}else{
					$result['helpText'] = $interface->fetch("Help/en/wma_kindle_fire.tpl");
				}
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