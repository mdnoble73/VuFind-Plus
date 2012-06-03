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

class AJAX extends Action {


	function launch() {
		global $timer;
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");
		if (in_array($method, array())){
			//JSON Responses
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if (in_array($method, array('getEContentAttachNotes', 'getEContentImportDetails', 'getPackagingDetails'))){
			//HTML responses
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else{
			//XML responses
			header ('Content-type: text/xml');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$xml = '<?xml version="1.0" encoding="UTF-8"?' . ">\n" .
	               "<AJAXResponse>\n";
			if (is_callable(array($this, $_GET['method']))) {
				$xml .= $this->$_GET['method']();
			} else {
				$xml .= '<Error>Invalid Method</Error>';
			}
			$xml .= '</AJAXResponse>';
			 
			echo $xml;
		}
	}
	
	function getEContentAttachNotes()
	{
		$id = $_REQUEST['id'];
		require_once 'sys/eContent/EContentAttachmentLogEntry.php';
		$logEntry = new EContentAttachmentLogEntry();
		$logEntry->id = $id;
		if ($logEntry->find(true)){
			if (strlen($logEntry->notes) == 0){
				return "No notes have been entered for this process";
			}else{
				return $logEntry->notes;
			}
		}else{
			return "We could not find a process with that id.  No notes available.";
		}
	}
	
	function getEContentImportDetails()
	{
		global $interface;
		
		$id = $_REQUEST['id'];
		require_once 'sys/eContent/EContentImportDetailsEntry.php';
		$logEntry = new EContentImportDetailsEntry();
		$logEntry->id = $id;
		if ($logEntry->find(true)){
			$interface->assign('logEntry', $logEntry);
			return $interface->fetch('EContent/eContentImportDetailsEntry.tpl');
		}else{
			return "We could not find a import log entry with that id.";
		}
	}
	
	function getPackagingDetails()
	{
		global $interface;
		
		$id = $_REQUEST['id'];
		require_once 'sys/eContent/PackagingDetailsEntry.php';
		$logEntry = new PackagingDetailsEntry();
		$logEntry->id = $id;
		if ($logEntry->find(true)){
			$interface->assign('logEntry', $logEntry);
			return $interface->fetch('EContent/packagingDetailsEntry.tpl');
		}else{
			return "We could not find a packaging log entry with that id.";
		}
	}
}
