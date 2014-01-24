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
		$browseCategories = array();
		//TODO: Load these for real
		$newFiction = new SearchSource();
		$newFiction->init('New Fiction', 'time_since_added:Month&literary_form_full=Fiction', 'relevance', 'catalog');
		$browseCategories[] = $newFiction;
		$newNonFiction = new SearchSource();
		$newNonFiction->init('New Non-Fiction', 'time_since_added:Month&literary_form_full="Non Fiction"', 'relevance', 'catalog');
		$browseCategories[] = $newNonFiction;
		$newMovies = new SearchSource();
		$newMovies->init('New Movies', 'time_since_added:Month&format_category="Movies"', 'relevance', 'catalog');
		$browseCategories[] = $newMovies;
		$newEBooks = new SearchSource();
		$newEBooks->init('New eBooks', 'time_since_added:Month&format_category="eBooks"', 'relevance', 'catalog');
		$browseCategories[] = $newEBooks;
		$newMusic = new SearchSource();
		$newMusic->init('New Music', 'time_since_added:Month&format_category="Music"', 'relevance', 'catalog');
		$browseCategories[] = $newMusic;
		$newAudioBooks = new SearchSource();
		$newAudioBooks->init('New Audio Books', 'time_since_added:Month&format_category="Audio Books"', 'relevance', 'catalog');
		$browseCategories[] = $newAudioBooks;


		/*$browseCategories = array(
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
		);*/
		$interface->assign('browseCategories', $browseCategories);

		$browseResults = array();
		$browseResults[] = array('id'=>'04d7a306-1cb3-c3a0-e84f-b92e2a3c29a5','isbn'=>'9780982857151');
		$browseResults[] = array('id'=>'39fd54fa-7981-de5e-edb4-a5b82699657a','isbn'=>'9780985114640');
		$browseResults[] = array('id'=>'29aa806f-b091-99f6-ecc5-2fd71aa27ef0','isbn'=>'9781101631065');
		$browseResults[] = array('id'=>'0fcf60ad-ffc6-e27e-8b45-8131852e4d93','isbn'=>'9780985114657');
		$browseResults[] = array('id'=>'e56932f2-ca56-dcc9-8032-f52c04fd0882','isbn'=>'9780786030484');
		$browseResults[] = array('id'=>'9f7620af-b169-3fb3-09bf-9ead57d7b5f1','isbn'=>'9780143121558');
		$browseResults[] = array('id'=>'dc269029-bcc8-8efd-9385-ba3993e925b8','isbn'=>'9780062236739');
		$browseResults[] = array('id'=>'9b00c5b6-14d3-ea60-a399-f64bfd5121a3','isbn'=>'9780307958846');
		$browseResults[] = array('id'=>'002962ce-451d-6b1a-650f-05a0816a02bc','isbn'=>'9781414379340');
		$browseResults[] = array('id'=>'d4b99360-8d97-c042-b67b-be429df1ffd8','isbn'=>'9781564745309');
		$browseResults[] = array('id'=>'4a64a5e8-a306-7147-2524-6832b38e5f74','isbn'=>'9780316244121');
		$browseResults[] = array('id'=>'c99215c3-da4d-f658-8f17-c1b35072a4aa','isbn'=>'9780307980496');
		$browseResults[] = array('id'=>'b5577aec-e8f8-9180-6cf4-03f60cbedb97','isbn'=>'9780345541024');

		$interface->assign('browseResults', $browseResults);


		$interface->setPageTitle('Catalog Home');
		$interface->assign('sidebar', 'Search/home-sidebar.tpl');
		$interface->setTemplate('home.tpl');
		$interface->display('layout.tpl');
	}

}