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
require_once "sys/MaterialsRequest.php";

/**
 * MaterialsRequest Submission processing, processes a new request for the user and 
 * displays a success/fail message to the user. 
 */
class Submit extends Action
{

	function launch()
	{
		global $configArray;
		global $interface;
		global $user;
		
		//Make sure that the user is valid 
		$processForm = true;
		if (!$user){
			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];
			$user = UserAccount::login();
			if ($user == null){
				$interface->assign('error', 'Sorry, we could not log you in.  Please enter a valid barcode and pin number submit a materials request.');
				$processForm = false;
			}
		}
		if ($processForm){
			//Check to see how many active materials request results the user has already. 
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->createdBy = $user->id;
			$materialsRequest->whereAdd("status in ('pending', 'referredToILL', 'ILLplaced', 'NotEnoughInfo')");
			$materialsRequest->find();
			if ($materialsRequest->N >= 5){
				$interface->assign('success', false);
				$interface->assign('error', 'Sorry, you can have a maxiumum of 5 requests for materials open at any one time.  Please wait until we process your existing requests before submitting another.');
			}else{
				//Materials request can be submitted. 
				$materialsRequest = new MaterialsRequest();
				$materialsRequest->phone = strip_tags($_REQUEST['phone']);
				$materialsRequest->email = strip_tags($_REQUEST['email']);
				$materialsRequest->title = strip_tags($_REQUEST['title']);
				$materialsRequest->season = isset($_REQUEST['season']) ? strip_tags($_REQUEST['season']) : '';
				$materialsRequest->magazineTitle = isset($_REQUEST['magazineTitle']) ? strip_tags($_REQUEST['magazineTitle']) : '';
				$materialsRequest->magazineDate = isset($_REQUEST['magazineDate']) ? strip_tags($_REQUEST['magazineDate']) : '';
				$materialsRequest->magazineVolume = isset($_REQUEST['magazineVolume']) ? strip_tags($_REQUEST['magazineVolume']) : '';
				$materialsRequest->magazinePageNumbers = isset($_REQUEST['magazinePageNumbers']) ? strip_tags($_REQUEST['magazinePageNumbers']) : '';
				$materialsRequest->author = strip_tags($_REQUEST['author']);
				$materialsRequest->format = strip_tags($_REQUEST['format']);
				$materialsRequest->subFormat = isset($_REQUEST['subFormat']) ? strip_tags($_REQUEST['subFormat']) : '';
				$materialsRequest->ageLevel = strip_tags($_REQUEST['ageLevel']);
				$materialsRequest->bookType = isset($_REQUEST['bookType']) ? strip_tags($_REQUEST['bookType']) : '';
				$materialsRequest->isbn = isset($_REQUEST['isbn']) ? strip_tags($_REQUEST['isbn']) : '';
				$materialsRequest->upc = isset($_REQUEST['upc']) ? strip_tags($_REQUEST['upc']) : '';
				$materialsRequest->issn = isset($_REQUEST['issn']) ? strip_tags($_REQUEST['issn']) : '';
				$materialsRequest->oclcNumber = isset($_REQUEST['oclcNumber']) ? strip_tags($_REQUEST['oclcNumber']) : '';
				$materialsRequest->publisher = strip_tags($_REQUEST['publisher']);
				$materialsRequest->publicationYear = strip_tags($_REQUEST['publicationYear']);
				if (isset($_REQUEST['abridged'])){
					if ($_REQUEST['abridged'] == 'abridged'){
						$materialsRequest->abridged = 1;
					}elseif($_REQUEST['abridged'] == 'unabridged'){
						$materialsRequest->abridged = 0;
					}else{
						$materialsRequest->abridged = 2; //Not applicable
					}
				}
				$materialsRequest->about = strip_tags($_REQUEST['about']);
				$materialsRequest->comments = strip_tags($_REQUEST['comments']);
				if (isset($_REQUEST['placeHoldWhenAvailable'])){
					$materialsRequest->placeHoldWhenAvailable = $_REQUEST['placeHoldWhenAvailable'];
				}
				if (isset($_REQUEST['holdPickupLocation'])){
					$materialsRequest->holdPickupLocation = $_REQUEST['holdPickupLocation'];
				}
				if (isset($_REQUEST['bookmobileStop'])){
					$materialsRequest->bookmobileStop = $_REQUEST['bookmobileStop'];
				}
				$materialsRequest->status = 'pending';
				$materialsRequest->dateCreated = time();
				$materialsRequest->createdBy = $user->id;
				$materialsRequest->dateUpdated = time();
				
				if ($materialsRequest->insert()){
					$interface->assign('success', true);
					$interface->assign('materialsRequest', $materialsRequest);
				}else{
					$interface->assign('success', false);
					$interface->assign('error', 'There was an error submitting your materials request.');
				}
			}
		}

		$interface->setTemplate('submision-result.tpl');
		$interface->setPageTitle('Submission Result');
		$interface->display('layout.tpl');
	}
}