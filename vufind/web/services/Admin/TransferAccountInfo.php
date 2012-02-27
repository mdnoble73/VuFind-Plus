<?php
/**
 *
 * Copyright (C) Villanova University 2010.
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
require_once 'services/Admin/Admin.php';
require_once 'sys/Pager.php';

class TransferAccountInfo extends Admin
{
	function launch()
	{
		global $interface;
		global $configArray;

		$interface->setPageTitle('Transfer Account Information');
		
		
		if (isset($_REQUEST['submit'])){
			$message = "";
			$okToTransfer = true;
			//Get the old user id 
			$oldUser = new User();
			$oldUser->cat_username = $_REQUEST['oldBarcode'];
			if (!$oldUser->find(true)){
				$message .= "<p>Sorry, we could not find a user for the old barcode.  Unable to transfer information.</p>";
				$okToTransfer = false;
			}
			
			//Get the new user id
			$newUser = new User();
			$newUser->cat_username = $_REQUEST['newBarcode'];
			if (!$newUser->find(true)){
				$message .= "<p>Sorry, we could not find a user for the new barcode.  Unable to transfer information.</p>";
				$okToTransfer = false;
			} 
			
			if ($okToTransfer){
				require_once 'services/MyResearch/lib/Resource.php';
				require_once 'sys/eContent/EContentRecord.php';
				//Transfer ratings for regular titles
				$message .= "<p>Transfered: <ul>";
				$resource = new Resource();
				$ret = $resource->query("UPDATE user_rating set userid = {$newUser->id} WHERE userid = {$oldUser->id}");
				$message .= "<li>{$ret} Ratings of print titles</li>";
				
				//Transfer reading history
				$resource = new Resource();
				$ret = $resource->query("UPDATE user_reading_history set userid = {$newUser->id} WHERE userid = {$oldUser->id}");
				$message .= "<li>{$ret} Reading History Entries</li>";
				
				//Transfer comments
				$resource = new Resource();
				$ret = $resource->query("UPDATE comments set user_id = {$newUser->id} WHERE user_id = {$oldUser->id}");
				$message .= "<li>{$ret} Reviews</li>";
			
				//Transfer tags
				$resource = new Resource();
				$ret = $resource->query("UPDATE resource_tags set user_id = {$newUser->id} WHERE user_id = {$oldUser->id}");
				$message .= "<li>{$ret} Tags</li>";
				
				//Transfer lists
				$resource = new Resource();
				$ret = $resource->query("UPDATE user_list set user_id = {$newUser->id} WHERE user_id = {$oldUser->id}");
				$message .= "<li>{$ret} User Lists</li>";
				
				//Transfer eContent ratings
				$eContentRecord = new EContentRecord();
				$ret = $eContentRecord->query("UPDATE econtent_rating set userId = {$newUser->id} WHERE userId = {$oldUser->id}");
				$message .= "<li>{$ret} Ratings of eContent titles</li>";
				
				//Transfer eContent checkouts
				$eContentRecord = new EContentRecord();
				$ret = $eContentRecord->query("UPDATE econtent_checkout set userId = {$newUser->id} WHERE userId = {$oldUser->id}");
				$message .= "<li>{$ret} eContent Checkouts</li>";
				
				//Transfer eContent holds
				$eContentRecord = new EContentRecord();
				$ret = $eContentRecord->query("UPDATE econtent_hold set userId = {$newUser->id} WHERE userId = {$oldUser->id}");
				$message .= "<li>{$ret} eContent Holds</li>";
				
				//Transfer eContent wishlist
				$eContentRecord = new EContentRecord();
				$ret = $eContentRecord->query("UPDATE econtent_wishlist set userId = {$newUser->id} WHERE userId = {$oldUser->id}");
				$message .= "<li>{$ret} eContent Wish List Entries</li>";
				
				$message .= "</ul></p>";
			}
			
			$interface->assign('message', $message);
		}

		$interface->setTemplate('transferAccountInfo.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('opacAdmin');
	}
}
