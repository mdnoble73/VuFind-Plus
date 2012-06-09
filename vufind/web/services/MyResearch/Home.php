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

require_once 'services/MyResearch/MyResearch.php';
require_once 'services/MyResearch/lib/FavoriteHandler.php';

/**
 * MyResearch Home Page, should only get here when going to the login page.
 *
 * This controller needs some cleanup and organization.
 *
 * @version  $Revision: 1.27 $
 */
class Home extends MyResearch
{

	function launch()
	{
		global $configArray;
		global $interface;
		global $user;

		if (isset($_REQUEST['returnUrl'])) {
			$followupUrl =  $_REQUEST['returnUrl'];
			header("Location: " . $followupUrl);
			exit();
		}

		// Delete Resource
		if (isset($_GET['delete'])) {
			$resource = Resource::staticGet('record_id',  strip_tags($_GET['delete']));
			$user->removeResource($resource);
		}

		// Narrow by Tag
		if (isset($_GET['tag'])) {
			$interface->assign('tags',  strip_tags($_GET['tag']));
		}
		
		//We are going to the "main page of My Research"
		//Be smart about this depending on the user's information.
		$hasHomeTemplate = $interface->template_exists('MyResearch/home.tpl'); 

		if (!$user){
			$action = 'Home';
		}elseif ($hasHomeTemplate){
			//Var for the IDCLREADER TEMPLATE
			$interface->assign('ButtonBack',false);
			$interface->assign('ButtonHome',true);
			$interface->assign('MobileTitle','&nbsp;');
			
			$interface->setTemplate('home.tpl');
		}else{
			if ($user && !$interface->isMobile()){
				// Connect to Database
				$catalog = new CatalogConnection($configArray['Catalog']['driver']);
				$patron = $catalog->patronLogin($user->cat_username, $user->cat_password);
				$profile = $catalog->getMyProfile($patron);
				if ($profile['numCheckedOut'] > 0){
					$action ='CheckedOut';
				}elseif ($profile['numHolds'] > 0){
					$action ='Holds';
				}else{
					$action ='Favorites';
				}
				header("Location: /MyResearch/$action");
			}else{
				//Go to the login page which is the home page
				$action = 'Home';
			}
		
			// Build Favorites List
			$favorites = $user->getResources(isset($_GET['tag']) ?  strip_tags($_GET['tag']) : null);
			$favList = new FavoriteHandler($favorites, $user);
			$favList->assign();
	
			// Get My Lists
			$listList = $user->getLists();
			$interface->assign('listList', $listList);
	
			// Get My Tags
			$tagList = $user->getTags();
			$interface->assign('tagList', $tagList);
			$interface->setPageTitle('Favorites');
			$interface->setTemplate('favorites.tpl');
		}
		$interface->display('layout.tpl');
	}
}