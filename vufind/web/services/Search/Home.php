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
		$showWidgets = false;
		$activeLocation = $locationSingleton->getActiveLocation();
		if ($activeLocation != null && strlen($activeLocation->homePageWidgetId) > 0 && $activeLocation->homePageWidgetId != 0){
			$widgetsToShow = $activeLocation->homePageWidgetId;
			$showWidgets = true;
		}else if (isset($library) && strlen($library->homePageWidgetId) > 0 && $library->homePageWidgetId != 0){
			$widgetsToShow = $library->homePageWidgetId;
			$showWidgets = true;
		}
		if ($showWidgets){
			$widgetIds = explode(",", $widgetsToShow);
			$widgets = array();
			foreach ($widgetIds as $widgetId){
				$widget = new ListWidget();
				$widget->id = $widgetId;
				if ($widget->find(true)){
					$widgets[] = $widget;
				}
			}
			if (count($widgets) > 0){
				$interface->assign('widgets', $widgets);
			}
		}

		$interface->setPageTitle('Catalog Home');
		$interface->setTemplate('home.tpl');
		$interface->display('layout.tpl');
	}

}