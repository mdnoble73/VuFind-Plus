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

class Home extends Action {

	function launch()
	{
		global $interface;
		global $configArray;
		global $library;
		global $locationSingleton;
		global $timer;
		global $user;

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/' . $configArray['Index']['engine'] . '.php';
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

		//Get the lists to show on the home page
		require_once ROOT_DIR . '/sys/ListWidget.php';
		$widgetId = 1;
		$activeLocation = $locationSingleton->getActiveLocation();
		if ($activeLocation != null && $activeLocation->homePageWidgetId > 0){
			$widgetId = $activeLocation->homePageWidgetId;
			$widget = new ListWidget();
			$widget->id = $widgetId;
			if ($widget->find(true)){
				$interface->assign('widget', $widget);
			}
		}else if (isset($library) && $library->homePageWidgetId > 0){
			$widgetId = $library->homePageWidgetId;
			$widget = new ListWidget();
			$widget->id = $widgetId;
			if ($widget->find(true)){
				$interface->assign('widget', $widget);
			}
		}

		//Load library links
		$links = $library->libraryLinks;
		$libraryLinks = array();
		foreach ($links as $libraryLink){
			if (!array_key_exists($libraryLink->category, $libraryLinks)){
				$libraryLinks[$libraryLink->category] = array();
			}
			$libraryLinks[$libraryLink->category][$libraryLink->linkText] = $libraryLink->url;
		}
		$interface->assign('libraryLinks', $libraryLinks);

		//Load browse categories
		/** @var BrowseCategory[] $browseCategories */
		$browseCategories = array();
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategory = new BrowseCategory();
		$browseCategory->find();
		while($browseCategory->fetch()){
			$browseCategories[] = clone($browseCategory);
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