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

global $configArray;

class AJAX extends Action {

	function AJAX() {
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
		}else if (in_array($method, array('GetAddTagForm', 'GetNovelistData'))){
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
		require_once ROOT_DIR . '/services/MyResearch/lib/User_list.php';

		$result = array();
		if (UserAccount::isLoggedIn()) {
			$saveService = new Save();
			$result = $saveService->saveRecord();
			if (!PEAR_Singleton::isError($result)) {
				$result['result'] = "Done";
			} else {
				$result['result'] = "Error";
			}
		} else {
			$result['result'] = "Unauthorized";
		}
		return json_encode($result);
	}

	function GetAddTagForm(){
		$user = UserAccount::isLoggedIn();
		global $interface;
		if ($user === false) {
			//Shouldn't get here since we check that the user is logged in before calling this. 
			return $interface->fetch('MyResearch/ajax-login.tpl');
		}else{
			$interface->assign('id', $_REQUEST['id']);
			$interface->assign('source', $_REQUEST['source']);
			$interface->assign('title', translate("Add a Tag"));
			return $interface->fetch('Resource/addtag.tpl');
		}
	}
	
	function SaveTag()
	{
		$user = UserAccount::isLoggedIn();
		if ($user === false) {
			return json_encode(array('result' => 'Unauthorized'));
		}

		// Create a resource entry for the current ID if necessary (or find the
		// existing one):
		$resource = new Resource();
		$resource->record_id = $_GET['id'];
		$resource->source = $_REQUEST['source'];
		if (!$resource->find(true)) {
			$resource->insert();
		}

		// Parse apart the tags and save them in association with the resource:
		preg_match_all('/"[^"]*"|[^,]+/', $_REQUEST['tag'], $words);
		foreach ($words[0] as $tag) {
			$tag = trim(strtolower(str_replace('"', '', $tag)));
			$resource->addTag($tag, $user);
		}

		return json_encode(array('result' => 'Done'));
	}
	
	function GetTags() {
		require_once ROOT_DIR . '/services/MyResearch/lib/Resource.php';

		$resource = new Resource();
		$resource->record_id = $_GET['id'];
		$resource->source = $_GET['source'];
		$tags = array();
		if ($resource->find(true)) {
			$tagList = $resource->getTags();
			foreach ($tagList as $tag) {
				$tags[] = array('count' => $tag->cnt, 'tag' => $tag->tag);
			}
			$return = array('tags' => $tags);
		}else{
			$return = array('error' => "Could not find record");
		}

		
		return json_encode(array('result' => $return));
	}

	function MarkNotInterested(){
		global $user;
		$recordId = $_REQUEST['recordId'];
		$source = $_REQUEST['source'];
		require_once ROOT_DIR . '/sys/NotInterested.php';
		$notInterested = new NotInterested();
		$notInterested->userId = $user->id;
		require_once ROOT_DIR . '/services/MyResearch/lib/Resource.php';
		$resource = new Resource();
		$resource->source = $source;
		$resource->record_id = $recordId;

		if ($resource->find(true)){
			$notInterested->resourceId = $resource->id;
			if (!$notInterested->find(true)){
				$notInterested->dateMarked = time();
				$notInterested->insert();
				$result = array(
					'result' => true,
				);
			}else{
				$result = array(
					'result' => false,
					'message' => "This record was already marked as something you aren't interested in.",
				);
			}
		}else{
			$result = array(
				'result' => false,
				'message' => 'Unable to find the resource specified.',
			);
		}
		return json_encode($result);
	}

	function ClearNotInterested(){
		global $user;
		$idToClear = $_REQUEST['id'];
		require_once ROOT_DIR . '/sys/NotInterested.php';
		$notInterested = new NotInterested();
		$notInterested->userId = $user->id;
		$notInterested->id = $idToClear;
		$result = array('result' => false);
		if ($notInterested->find(true)){
			$notInterested->delete();
			$result = array('result' => true);
		}
		return json_encode($result);
	}

	function GetNovelistData(){
		$url = $_REQUEST['novelistUrl'];
		$rawNovelistData = file_get_contents($url);
		//Trim off the wrapping data ();
		$rawNovelistData = substr($rawNovelistData, 1, -2);
		$jsonData = json_decode($rawNovelistData);
		$novelistData = $jsonData->body;
		echo($novelistData);
	}
}