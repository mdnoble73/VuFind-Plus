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

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/sys/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequestStatus.php';
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

/**
 * MaterialsRequest MyRequests Page, displays materials request information for the active user.
 */
class MaterialsRequest_MyRequests extends MyAccount
{

	function launch()
	{
		global $configArray;
		global $interface;
		global $user;
		
		$showOpen = true;
		if (isset($_REQUEST['requestsToShow']) && $_REQUEST['requestsToShow'] == 'allRequests'){
			$showOpen  = false;
		}
		$interface->assign('showOpen', $showOpen);

//		global $library;
		$homeLibrary = Library::getPatronHomeLibrary();


		$maxActiveRequests  = isset($homeLibrary) ? $homeLibrary->maxOpenRequests : 5;
		$maxRequestsPerYear = isset($homeLibrary) ? $homeLibrary->maxRequestsPerYear : 60;
		$interface->assign('maxActiveRequests', $maxActiveRequests);
		$interface->assign('maxRequestsPerYear', $maxRequestsPerYear);


		$defaultStatus = new MaterialsRequestStatus();
		$defaultStatus->isDefault = 1;
		$defaultStatus->libraryId = $homeLibrary->libraryId;
		$defaultStatus->find(true);
		$interface->assign('defaultStatus', $defaultStatus->id);
		
		//Get a list of all materials requests for the user
		$allRequests = array();
		if ($user){
			$materialsRequests = new MaterialsRequest();
			$materialsRequests->createdBy = $user->id;
			$materialsRequests->whereAdd('dateCreated >= UNIX_TIMESTAMP(concat(year(now()), \'-1-1\'))');
			$requestsThisYear = $materialsRequests->count();
			$interface->assign('requestsThisYear', $requestsThisYear);

			$statusQuery = new MaterialsRequestStatus();
			$statusQuery->libraryId = $homeLibrary->libraryId;
			$statusQuery->isOpen = 1;

			$materialsRequests = new MaterialsRequest();
			$materialsRequests->createdBy = $user->id;
			$materialsRequests->joinAdd($statusQuery);
			$openRequests = $materialsRequests->count();
			$interface->assign('openRequests', $openRequests);


			$materialsRequests = new MaterialsRequest();
			$materialsRequests->createdBy = $user->id;
			$materialsRequests->orderBy('title, dateCreated');
			$statusQuery = new MaterialsRequestStatus();
			if ($showOpen){
				$homeLibrary = Library::getPatronHomeLibrary();
				$statusQuery->libraryId = $homeLibrary->libraryId;
				$statusQuery->isOpen = 1;
			}
			$materialsRequests->joinAdd($statusQuery);
			$materialsRequests->selectAdd();
			$materialsRequests->selectAdd('materials_request.*, description as statusLabel');
			$materialsRequests->find();
			while ($materialsRequests->fetch()){
				$allRequests[] = clone $materialsRequests;
			}
		}else{
			header('Location: ' . $configArray['Site']['path'] . '/MyAccount/Home?followupModule=MaterialsRequest&followupAction=MyRequests');
		}
		$interface->assign('allRequests', $allRequests);

		$this->display('myMaterialRequests.tpl', 'My Materials Requests');
	}
}