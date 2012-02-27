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

require_once 'Action.php';
require_once('services/Admin/Admin.php');
require_once('sys/MaterialsRequest.php');

class ManageRequests extends Admin {

  function launch()
  {
    global $configArray;
		global $interface;
		global $user;
		
		//Process status change if needed
		if (isset($_REQUEST['updateStatus']) && isset($_REQUEST['select'])){
			//Look for which titles should be modified 
			$selectedRequests = $_REQUEST['select'];
			$statusToSet = $_REQUEST['newStatus'];
			foreach ($selectedRequests as $requestId => $selected){
				$materialRequest = new MaterialsRequest();
				$materialRequest->id = $requestId;
				if ($materialRequest->find(true)){
					$materialRequest->status = $statusToSet;
					$materialRequest->dateUpdated = time();
					$materialRequest->update();
				}
			}
		}
		
		$defaultStatusesToShow = array('pending', 'referredToILL', 'ILLplaced', 'notEnoughInfo');
		if (isset($_REQUEST['statusFilter'])){
			$statusesToShow = $_REQUEST['statusFilter'];
		}else{
			$statusesToShow = $defaultStatusesToShow;
		}
		$interface->assign('statusFilter', $statusesToShow);
		//Get a list of all materials requests for the user
		$allRequests = array();
		if ($user){
			$materialsRequests = new MaterialsRequest();
			
			$statusSql = "";
			foreach ($statusesToShow as $status){
				if (strlen($statusSql) > 0) $statusSql .= ",";
				$statusSql .= "'" . mysql_escape_string($status) . "'";
			}
			$materialsRequests->whereAdd("status in ($statusSql)");
			
			//Add filtering by date as needed
			if (isset($_REQUEST['startDate']) && strlen($_REQUEST['startDate']) > 0){
				$startDate = strtotime($_REQUEST['startDate']);
				$materialsRequests->whereAdd("dateCreated >= $startDate");
				$interface->assign('startDate', $_REQUEST['startDate']);
			}
			if (isset($_REQUEST['endDate']) && strlen($_REQUEST['endDate']) > 0){
				$endDate = strtotime($_REQUEST['endDate']);
				$materialsRequests->whereAdd("dateCreated <= $endDate");
				$interface->assign('endDate', $_REQUEST['endDate']);
			}
			
			$materialsRequests->find();
			while ($materialsRequests->fetch()){
				$allRequests[] = clone $materialsRequests;
			}
		}else{
			$interface->assign('error', "You must be logged in to manage requests.");
		}
		$interface->assign('allRequests', $allRequests);

		$interface->setTemplate('manageRequests.tpl');
		$interface->setPageTitle('Manage Materials Requests');
		$interface->display('layout.tpl');
  }

  function getAllowableRoles(){
  	return array('cataloging');
  }
}
