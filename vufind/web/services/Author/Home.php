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

require_once ROOT_DIR . '/sys/Proxy_Request.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/Novelist.php';

class Author_Home extends Action
{
	private $lang;

	function launch()
	{
		global $configArray;
		global $interface;
		global $library;

		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Build RSS Feed for Results (if requested)
		if ($searchObject->getView() == 'rss') {
			// Throw the XML to screen
			echo $searchObject->buildRSS();
			// And we're done
			exit();
		}

		$interface->caching = false;

		if (!isset($_GET['author'])) {
			PEAR_Singleton::raiseError(new PEAR_Error('Unknown Author'));
		} else {
			$interface->assign('author', $_GET['author']);
		}

		// What language should we use?
		$this->lang = $configArray['Site']['language'];

		// Retrieve User Search History -- note that we only want to offer a
		// "back to search" link if the saved URL is not for the current action;
		// when users first reach this page from search results, the "last URL"
		// will be their original search, which we want to link to.  However,
		// since this module will later set the "last URL" value in order to
		// allow the user to return from a record view to this page, after they
		// return here, we will no longer have access to the last non-author
		// search, and it is better to display nothing than to provide an infinite
		// loop of links.  Perhaps this can be solved more elegantly with a stack
		// or with multiple session variables, but for now this seems okay.
		$interface->assign('lastsearch', (isset($_SESSION['lastSearchURL']) && !strstr($_SESSION['lastSearchURL'], 'Author/Home')) ? $_SESSION['lastSearchURL'] : false);

		$interface->assign('lookfor', $_GET['author']);
		$interface->assign('basicSearchIndex', 'Author');
		$interface->assign('searchIndex', 'Author');

		if (!$interface->is_cached('layout.tpl|Author' . $_GET['author'])) {
			// Clean up author string
			$author = $_GET['author'];
			if (substr($author, strlen($author) - 1, 1) == ",") {
				$author = substr($author, 0, strlen($author) - 1);
			}
			$author = explode(',', $author);
			$interface->assign('author', $author);

			// Create First Name
			$firstName = '';
			if (isset($author[1])) {
				$firstName = $author[1];
				if (isset($author[2])) {
					// Remove punctuation
					if ((strlen($author[2]) > 2) && (substr($author[2], -1) == '.')) {
						$author[2] = substr($author[2], 0, -1);
					}
					$firstName = $author[2] . ' ' . $firstName;
				}
			}

			// Remove dates
			$firstName = preg_replace('/[0-9]+-[0-9]*/', '', $firstName);

			// Build Author name to display.
			if (substr($firstName, -3, 1) == ' ') {
				// Keep period after initial
				$authorName = $firstName . ' ';
			} else {
				// No initial so strip any punctuation from the end
				if ((substr(trim($firstName), -1) == ',') ||
				(substr(trim($firstName), -1) == '.')) {
					$authorName = substr(trim($firstName), 0, -1) . ' ';
				} else {
					$authorName = $firstName . ' ';
				}
			}
			$authorName .= $author[0];
			$interface->assign('authorName', trim($authorName));

			// Pull External Author Content
			$interface->assign('showWikipedia', false);
			if ($searchObject->getPage() == 1) {
				// Only load Wikipedia info if turned on in config file:
				if (isset($configArray['Content']['authors'])
						&& stristr($configArray['Content']['authors'], 'wikipedia')
						&& (!$library || $library->showWikipediaContent == 1)
						) {

					$interface->assign('showWikipedia', true);
					$wikipediaAuthorName = $authorName;
					//Strip anything in parenthesis
					if (strpos($wikipediaAuthorName, '(') > 0){
						$wikipediaAuthorName = substr($wikipediaAuthorName, 0, strpos($wikipediaAuthorName, '(') );
					}
					$interface->assign('wikipediaAuthorName', $wikipediaAuthorName);
				}
			}
		}

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed
		$interface->setPageTitle('Author Search Results');
		$interface->assign('sortList',   $searchObject->getSortList());
		$interface->assign('limitList', $searchObject->getLimitList());
		$interface->assign('viewList',  $searchObject->getViewList());
		$interface->assign('rssLink',    $searchObject->getRSSUrl());
		$interface->assign('filterList', $searchObject->getFilterList());

		// Process Search
		/** @var PEAR_Error|null $result */
		$result = $searchObject->processSearch(false, true);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result->getMessage());
		}

		// Some more variables
		//   Those we can construct AFTER the search is executed, but we need
		//   no matter whether there were any results
		$interface->assign('qtime', round($searchObject->getQuerySpeed(), 2));

		// Assign interface variables
		$summary = $searchObject->getResultSummary();
		$interface->assign('recordCount', $summary['resultTotal']);
		$interface->assign('recordStart', $summary['startRecord']);
		$interface->assign('recordEnd',   $summary['endRecord']);
		$interface->assign('sideRecommendations',
		$searchObject->getRecommendationsTemplates('side'));

		//Enable and disable functionality based on library settings
		global $library;
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		if (isset($library) && $location != null){
			$interface->assign('showFavorites', $library->showFavorites);
			$interface->assign('showHoldButton', (($location->showHoldButton == 1) && ($library->showHoldButton == 1)) ? 1 : 0);
		}else if ($location != null){
			$interface->assign('showFavorites', 1);
			$interface->assign('showHoldButton', $location->showHoldButton);
		}else if (isset($library)){
			$interface->assign('showFavorites', $library->showFavorites);
			$interface->assign('showHoldButton', $library->showHoldButton);
		}else{
			$interface->assign('showFavorites', 1);
			$interface->assign('showHoldButton', 1);
		}

		// Big one - our results
		$authorTitles = $searchObject->getResultRecordHTML();
		$interface->assign('recordSet',  $authorTitles);

		//Load similar author information.
		$authorIsbn = null;
		foreach ($authorTitles as $title){
			if (isset($title['isbn'])){
				if (is_array($title['isbn'])){
					$authorIsbn = $title['isbn'][0];
				}else{
					$authorIsbn = $title['isbn'];
				}
				break;
			}
		}

		if (!is_null($authorIsbn)){
			//Make sure to trim off any format information from the ISBN
			$isbnParts = explode(' ', $authorIsbn);
			$authorIsbn = $isbnParts[0];
			$novelist = NovelistFactory::getNovelist();
			$enrichment['novelist'] = $novelist->loadEnrichment($authorIsbn, false, false, true);
			if ($enrichment) {
				$interface->assign('enrichment', $enrichment);
			}
		}

		// Setup Display
		$interface->assign('sitepath', $configArray['Site']['path']);

		// Process Paging
		$link = $searchObject->renderLinkPageTemplate();
		$options = array('totalItems' => $summary['resultTotal'],
                         'fileName'   => $link,
                         'perPage'    => $summary['perPage']);
		$pager = new VuFindPager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		// Save the ID of this search to the session so we can return to it easily:
		$_SESSION['lastSearchId'] = $searchObject->getSearchId();
		// Save the URL of this search to the session so we can return to it easily:
		$_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();
		//Get view & load template
		$currentView  = $searchObject->getView();
		$interface->assign('subpage', 'Search/list-' . $currentView .'.tpl');
		$interface->setTemplate('home.tpl');
		$interface->display('layout.tpl', 'Author' . $_GET['author']);
	}


}
?>
