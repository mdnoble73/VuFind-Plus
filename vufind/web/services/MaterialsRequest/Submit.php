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
 * MaterialsRequest Home Page, displays an existing Materials Request.
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
				$materialsRequest->title = strip_tags($_REQUEST['title']);
				$materialsRequest->author = strip_tags($_REQUEST['author']);
				$materialsRequest->format = strip_tags($_REQUEST['format']);
				$materialsRequest->ageLevel = strip_tags($_REQUEST['ageLevel']);
				$materialsRequest->isbn_upc = strip_tags($_REQUEST['isbn_upc']);
				$materialsRequest->oclcNumber = strip_tags($_REQUEST['oclcNumber']);
				$materialsRequest->publisher = strip_tags($_REQUEST['publisher']);
				$materialsRequest->publicationYear = strip_tags($_REQUEST['publicationYear']);
				$materialsRequest->articleInfo = strip_tags($_REQUEST['articleInfo']);
				$materialsRequest->abridged = isset($_REQUEST['abridged']) && $_REQUEST['abridged'] == 'abridged' ? 1 : 0;
				$materialsRequest->about = strip_tags($_REQUEST['about']);
				$materialsRequest->comments = strip_tags($_REQUEST['comments']);
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