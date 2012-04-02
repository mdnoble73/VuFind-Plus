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
 * MaterialsRequest Update Page, updates an existing materials request.
 */
class Update extends Action {

	function launch() {
		global $configArray;
		global $interface;
		global $user;
		
		//Make sure that the user is valid 
		$processForm = true;
		if (!$user){
			$interface->assign('error', 'Sorry, you must be logged in to update a materials request.');
			$processForm = false;
		}if (!($user->hasRole('cataloging') || ($user->id == $materialsRequest->createdBy))){
			$interface->assign('error', 'Sorry, you do not have permission to update this materials request.');
			$processForm = false;
		}
		if ($processForm){
			//Check to see how many active materials request results the user has already. 
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $_REQUEST['id'];
			if ($materialsRequest->find(true)){
				//Materials request can be submitted. 
				$materialsRequest->title = strip_tags($_REQUEST['title']);
				$materialsRequest->season = isset($_REQUEST['season']) ? strip_tags($_REQUEST['season']) : '';
				$materialsRequest->magazineTitle = isset($_REQUEST['magazineTitle']) ? strip_tags($_REQUEST['magazineTitle']) : '';
				$materialsRequest->magazineDate = isset($_REQUEST['magazineDate']) ? strip_tags($_REQUEST['magazineDate']) : '';
				$materialsRequest->magazineVolume = isset($_REQUEST['magazineVolume']) ? strip_tags($_REQUEST['magazineVolume']) : '';
				$materialsRequest->magazineNumber = isset($_REQUEST['magazineNumber']) ? strip_tags($_REQUEST['magazineNumber']) : '';
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
				$materialsRequest->comments = strip_tags($_REQUEST['comments']);
				$materialsRequest->dateUpdated = time();
				
				if ($materialsRequest->update()){
					$interface->assign('success', true);
					$interface->assign('materialsRequest', $materialsRequest);
				}else{
					$interface->assign('success', false);
					$interface->assign('error', 'There was an error updating the materials request.');
				}
			}else{
				$interface->assign('success', false);
				$interface->assign('error', 'Sorry, we could not find a request with that id.');
			}
		}
		
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
		
		$interface->setTemplate('update-result.tpl');
		$interface->setPageTitle('Update Result');
		$interface->display('layout.tpl');
	}
}