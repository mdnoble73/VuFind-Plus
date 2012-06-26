<?php
/**
 *
 * Copyright (C) Anythink Libraries 2012.
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
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Anythink Libraries 2012.
 *
 */

require_once "Action.php";
require_once 'sys/MaterialsRequest.php';
require_once 'sys/MaterialsRequestStatus.php';

/**
 * MaterialsRequest AJAX Page, handles returing asynchronous information about Materials Requests.
 */
class AJAX extends Action{
	
	function AJAX() {
	}

	function launch(){
		$method = $_GET['method'];
		if (in_array($method, array('CancelRequest', 'GetWorldCatTitles', 'GetWorldCatIdentifiers'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$result = $this->$method();
			echo json_encode($result);
		}else if (in_array($method, array('MaterialsRequestDetails', 'UpdateMaterialsRequest'))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else{
			echo "Unknown Method";
		}
	}
	
	function CancelRequest(){
		global $user;
		if (!$user){
			return array('success' => false, 'error' => 'Could not cancel the request, you must be logged in to cancel the request.');
		}elseif (!isset($_REQUEST['id'])){
			return array('success' => false, 'error' => 'Could not cancel the request, no id provided.');
		}else{
			$cancelledStatus = new MaterialsRequestStatus();
			$cancelledStatus->isPatronCancel = 1;
			$cancelledStatus->find(true);
			
			$id = $_REQUEST['id'];
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $id;
			$materialsRequest->createdBy = $user->id;
			if ($materialsRequest->find(true)){
				$materialsRequest->dateUpdated = time();
				$materialsRequest->status = $cancelledStatus->id;
				if ($materialsRequest->update()){
					return array('success' => true);
				}else{
					return array('success' => false, 'error' => 'Could not cancel the request, error during update.');
				}
			}else{
				return array('success' => false, 'error' => 'Could not cancel the request, could not find a request for the provided id.');
			}
		}
	}
	
	function UpdateMaterialsRequest(){
		global $interface;
		global $configArray;
		
		$useWorldCat = false;
		if (isset($configArray['WorldCat']) && isset($configArray['WorldCat']['apiKey'])){
			$useWorldCat = strlen($configArray['WorldCat']['apiKey']) > 0;
		}
		$interface->assign('useWorldCat', $useWorldCat);
		
		if (!isset($_REQUEST['id'])){
			$interface->assign('error', 'Please provide an id of the materials request to view.');
		}else{
			$id = $_REQUEST['id'];
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $id;
			if ($materialsRequest->find(true)){
				
				global $user;
				if ($user && ($user->hasRole('cataloging') || ($user->id == $materialsRequest->createdBy))){
					//Get a list of formats to show 
					$availableFormats = MaterialsRequest::getFormats();
					$interface->assign('availableFormats', $availableFormats);
		
					$interface->assign('showPhoneField', $configArray['MaterialsRequest']['showPhoneField']);
					$interface->assign('showAgeField', $configArray['MaterialsRequest']['showAgeField']);
					$interface->assign('showBookTypeField', $configArray['MaterialsRequest']['showBookTypeField']);
					$interface->assign('showEbookFormatField', $configArray['MaterialsRequest']['showEbookFormatField']);
					$interface->assign('showEaudioFormatField', $configArray['MaterialsRequest']['showEaudioFormatField']);
					$interface->assign('showPlaceHoldField', $configArray['MaterialsRequest']['showPlaceHoldField']);
					$interface->assign('showIllField', $configArray['MaterialsRequest']['showIllField']);
					$interface->assign('requireAboutField', $configArray['MaterialsRequest']['requireAboutField']);
		
					$interface->assign('materialsRequest', $materialsRequest);
					$interface->assign('showUserInformation', true);
					//Load user information 
					$requestUser = new User();
					$requestUser->id = $materialsRequest->createdBy;
					if ($requestUser->find(true)){
						$interface->assign('requestUser', $requestUser);
					}
				}else{
					$interface->assign('error', 'Sorry, you don\'t have permission to update this request.');
				}
			}else{
				$interface->assign('error', 'Sorry, we couldn\'t find a materials request for that id.');
			}
		}
		return $interface->fetch('MaterialsRequest/ajax-update-request.tpl');
	}
	
	function MaterialsRequestDetails(){
		global $interface;
		
		if (!isset($_REQUEST['id'])){
			$interface->assign('error', 'Please provide an id of the materials request to view.');
		}else{
			$id = $_REQUEST['id'];
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $id;
			$statusQuery = new MaterialsRequestStatus();
			$materialsRequest->joinAdd($statusQuery);
			$locationQuery = new Location();
			$materialsRequest->joinAdd($locationQuery, "LEFT");
			$materialsRequest->selectAdd();
			$materialsRequest->selectAdd('materials_request.*, description as statusLabel, location.displayName as location');
			if ($materialsRequest->find(true)){
				$interface->assign('materialsRequest', $materialsRequest);
				
				global $user;
				if ($user && $user->hasRole('cataloging')){
					$interface->assign('showUserInformation', true);
					//Load user information 
					$requestUser = new User();
					$requestUser->id = $materialsRequest->createdBy;
					if ($requestUser->find(true)){
						$interface->assign('requestUser', $requestUser);
					}
				}else{
					$interface->assign('showUserInformation', false);
				}
			}else{
				$interface->assign('error', 'Sorry, we couldn\'t find a materials request for that id.');
			}
		}
		return $interface->fetch('MaterialsRequest/ajax-request-details.tpl');
	}
	
	function GetWorldCatIdentifiers(){
		$worldCatTitles = $this->GetWorldCatTitles();
		if ($worldCatTitles['success'] == false){
			return $worldCatTitles;
		}else{
			$suggestedIdentifiers = array();
			foreach ($worldCatTitles['titles'] as $title){
				$identifier = null;
				if (isset($title['ISBN'])){
					//Get the first 13 digit ISBN if available
					foreach ($title['ISBN'] as $isbn){
						$identifier = $isbn;
						if (strlen($isbn) == 13){
							break;
						}
					}
					$title['isbn'] = $identifier; 
				}elseif (isset($title['oclcNumber'])){
					$identifier = $title['oclcNumber'];
				}
				if (!is_null($identifier) && !array_key_exists($identifier, $suggestedIdentifiers)){
					$suggestedIdentifiers[$identifier] = $title;
				}
			}
		}
		global $interface;
		$interface->assign('suggestedIdentifiers', $suggestedIdentifiers);
		return array(
			'success' => true,
			'identifiers' => $suggestedIdentifiers,
			'formattedSuggestions' => $interface->fetch('MaterialsRequest/ajax-suggested-identifiers.tpl')
		);
	}
	
	function GetWorldCatTitles(){
		global $configArray;
		if (!isset($_REQUEST['title']) && !isset($_REQUEST['author'])){
			return array(
				'success' => false,
				'error' => 'Cannot load titles from WorldCat, an API Key must be provided in the config file.'
			);
		}else if (isset($configArray['WorldCat']['apiKey']) & strlen($configArray['WorldCat']['apiKey']) > 0){
			$worldCatUrl = "http://www.worldcat.org/webservices/catalog/search/opensearch?q=";
			if (isset($_REQUEST['title'])){
				$worldCatUrl .= urlencode($_REQUEST['title']);
			}
			if (isset($_REQUEST['author'])){
				$worldCatUrl .= '+' . urlencode($_REQUEST['author']);
			} 
			if (isset($_REQUEST['format'])){
				if (in_array($_REQUEST['format'],array('dvd', 'cassette', 'vhs', 'playaway'))){
					$worldCatUrl .= '+' . urlencode($_REQUEST['format']);
				}elseif (in_array($_REQUEST['format'],array('cdAudio', 'cdMusic'))){
					$worldCatUrl .= '+' . urlencode('cd');
				}
			}
			$worldCatUrl .= "&wskey=" . $configArray['WorldCat']['apiKey'];
			$worldCatUrl .= "&format=rss&cformat=mla";
			//echo($worldCatUrl);
			$worldCatData = simplexml_load_file($worldCatUrl);
			//print_r($worldCatData);
			$worldCatResults = array();
			foreach($worldCatData->channel->item as $item){
				$curTitle= array(
					'title' => (string)$item->title,
					'author' => (string)$item->author->name,
					'description' => (string)$item->description,
					'link' => (string)$item->link
				);
				
				$oclcChildren = $item->children('oclcterms', TRUE);
				foreach ($oclcChildren as $child){
					if ($child->getName() == 'recordIdentifier'){
						$curTitle['oclcNumber'] = (string)$child;
					}
					
				}
				$dcChildren = $item->children('dc', TRUE);
				foreach ($dcChildren as $child){
					if ($child->getName() == 'identifier'){
						$identifierFields = explode(":", (string)$child);
						$curTitle[$identifierFields[1]][] = $identifierFields[2];
					}
				}
				
				$contentChildren = $item->children('content', TRUE);
				foreach ($contentChildren as $child){
					if ($child->getName() == 'encoded'){
						$curTitle['citation'] = (string)$child;
					}
				}
				
				if (strlen($curTitle['description']) == 0 && isset($curTitle["ISBN"]) && is_array($curTitle["ISBN"]) && count($curTitle["ISBN"]) > 0){
					//Get the description from syndetics
					require_once 'Drivers/marmot_inc/GoDeeperData.php';
					$summaryInfo = GoDeeperData::getSummary($curTitle["ISBN"][0], null);
					if (isset($summaryInfo['summary'])){
						$curTitle['description'] = $summaryInfo['summary'];
					}
				}
				$worldCatResults[] = $curTitle;
			}
			return array(
				'success' => true,
				'titles' => $worldCatResults
			);
		}else{
			return array(
				'success' => false,
				'error' => 'Cannot load titles from WorldCat, an API Key must be provided in the config file.'
			);
		}
	}
}