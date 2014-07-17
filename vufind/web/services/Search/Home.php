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

class Search_Home extends Action {

	function launch()
	{
		global $interface;
		global $configArray;
		global $library;
		global $locationSingleton;
		global $timer;
		global $user;

		// Include Search Engine Class
		require_once ROOT_DIR . "/sys/{$configArray['Index']['engine']}.php";
		$timer->logTime('Include search engine');

		$interface->assign('showBreadcrumbs', 0);

		if ($user){
			$catalog = new CatalogConnection($configArray['Catalog']['driver']);
			$patron = $catalog->patronLogin($user->cat_username, $user->cat_password);
			$profile = $catalog->getMyProfile($patron);
			if (!PEAR_Singleton::isError($profile)) {
				$interface->assign('profile', $profile);
			}
		}

		$activeLocation = $locationSingleton->getActiveLocation();

		//Load browse categories
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		/** @var BrowseCategory[] $browseCategories */
		$browseCategories = array();
		if ($activeLocation != null){
			$localBrowseCategories = $activeLocation->browseCategories;
			foreach ($localBrowseCategories as $localBrowseCategory){
				$browseCategory = new BrowseCategory();
				$browseCategory->textId = $localBrowseCategory->browseCategoryTextId;
				if($browseCategory->find(true)){
					$browseCategories[] = clone($browseCategory);
				}
			}
		}
		if (isset($library) && count($browseCategories) == 0){
			$localBrowseCategories = $library->browseCategories;
			foreach ($localBrowseCategories as $localBrowseCategory){
				$browseCategory = new BrowseCategory();
				$browseCategory->textId = $localBrowseCategory->browseCategoryTextId;
				if($browseCategory->find(true)){
					$browseCategories[] = clone($browseCategory);
				}
			}
		}
		if (count($browseCategories) == 0){
			$browseCategory = new BrowseCategory();
			$browseCategory->find();
			while($browseCategory->fetch()){
				$browseCategories[] = clone($browseCategory);
			}
		}

		$interface->assign('browseCategories', $browseCategories);

		//Get the Browse Results for the first list
		require_once ROOT_DIR . '/services/Browse/AJAX.php';
		$browseAJAX = new Browse_AJAX();
		$browseResults = $browseAJAX->getBrowseCategoryInfo(reset($browseCategories)->textId);

		$interface->assign('browseResults', $browseResults);


		$interface->setPageTitle('Catalog Home');
		$interface->assign('sidebar', 'Search/home-sidebar.tpl');
		$interface->setTemplate('home.tpl');
		$interface->display('layout.tpl');
	}

}