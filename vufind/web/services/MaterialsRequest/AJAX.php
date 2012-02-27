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

/**
 * MaterialsRequest AJAX Page, handles returing asynchronous information about Materials Requests.
 */
class AJAX extends Action{
	
	function AJAX() {
	}

	function launch(){
		$method = $_GET['method'];
		if (in_array($method, array('CancelRequest'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$result = $this->$method();
			echo json_encode($result);
		}else if (in_array($method, array('MaterialsRequestDetails'))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}
	}
	
	function CancelRequest(){
		global $user;
		if (!$user){
			return array('success' => false, 'error' => 'Could not cancel the request, you must be logged in to cancel the request.');
		}elseif (!isset($_REQUEST['id'])){
			return array('success' => false, 'error' => 'Could not cancel the request, no id provided.');
		}else{
			$id = $_REQUEST['id'];
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $id;
			$materialsRequest->createdBy = $user->id;
			if ($materialsRequest->find(true)){
				$materialsRequest->dateUpdated = time();
				$materialsRequest->status = 'requestCancelled';
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
	
	function MaterialsRequestDetails(){
		global $interface;
		
		if (!isset($_REQUEST['id'])){
			$interface->assign('error', 'Please provide an id of the materials request to view.');
		}else{
			$id = $_REQUEST['id'];
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $id;
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
}