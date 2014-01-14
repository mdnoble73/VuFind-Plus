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
		//TODO: Load these for real
		$libraryLinks = array(
			'About Us' => array(
				'Board of Trustees' => 'http://www.steamboatlibrary.org/about-us/board-of-trustees-0',
				'History' => 'http://www.steamboatlibrary.org/about-us/history',
				'Building' => 'http://www.steamboatlibrary.org/about-us/building',
				'Mission' => 'http://www.steamboatlibrary.org/about-us/mission',
				'Coffee Bar' => 'http://www.steamboatlibrary.org/about-us/building-cafe/coffee-bar',
				'Policies' => 'http://www.steamboatlibrary.org/about-us/policies',
				'Jobs' => 'http://www.steamboatlibrary.org/about-us/jobs'
			),
			'Support Us' => array(
				'Book Donations' => 'http://www.steamboatlibrary.org/support-us/book-donations',
				'Volunteer' => 'http://www.steamboatlibrary.org/support-us/volunteer',
				'Donate' => 'http://www.steamboatlibrary.org/support-us/donate',
				'Thanks To' => 'http://www.steamboatlibrary.org/support-us/thanks-to',
			),
			'Contact Us' => array(
				'Questions, Comments, Suggestions' => 'http://www.steamboatlibrary.org/questions-comments-suggestions/questions-comments-suggestions',
				'Ask a Librarian' => 'http://www.steamboatlibrary.org/questions-comments-suggestions/ask-a-librarian',
				'Request a Title' => 'http://www.steamboatlibrary.org/how-do-i/manage-my-account/request-a-title',
				'Suggest a Purchase' => 'http://www.steamboatlibrary.org/how-do-i/manage-my-account/request-a-title/suggest-a-purchase',
				'Staff Directory' => 'http://www.steamboatlibrary.org/questions-comments-suggestions/staff-directory'
			)

		);
		$interface->assign('libraryLinks', $libraryLinks);

		//Load browse categories
		//TODO: Load these for real
		$browseCategories = array(
			'New Fiction',
			'New Non-fiction',
			'New Movies',
			'New eBooks',
			'New Audio Books',
			'New Young Adult',
			'New Kids',
			'Popular Romance',
			'Popular Mysteries',
			'Popular Science Fiction',
			'Popular Young Adults',
			'Recommended for You'
		);
		$interface->assign('browseCategories', $browseCategories);


		$interface->setPageTitle('Catalog Home');
		$interface->setTemplate('home.tpl');
		$interface->display('layout.tpl');
	}

}