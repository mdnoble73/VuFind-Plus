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
require_once ROOT_DIR . '/services/MyResearch/lib/FavoriteHandler.php';
require_once ROOT_DIR . '/services/MyResearch/lib/Resource.php';
require_once ROOT_DIR . '/services/MyResearch/lib/User_resource.php';
require_once ROOT_DIR . '/services/MyResearch/lib/Resource_tags.php';
require_once ROOT_DIR . '/services/MyResearch/MyResearch.php';

/**
 * This class does not use MyResearch base class (we don't need to connect to
 * the catalog, and we need to bypass the "redirect if not logged in" logic to
 * allow public lists to work properly).
 * @version  $Revision$
 */
class MyList extends MyResearch {
	function __construct(){
		$this->requireLogin = false;
		parent::__construct();
	}
	function launch() {
		global $configArray;
		global $interface;
		global $user;

		// Fetch List object
		$listId = $_REQUEST['id'];
		$list = new UserList();
		$list->id = $listId;
		if (!$list->find(true)){
			//TODO: Use the first list?
			$list = new UserList();
			$list->user_id = $user->id;
			$list->public = false;
			$list->title = "My Favorites";
		}

		// Ensure user has privileges to view the list
		if (!isset($list) || (!$list->public && !UserAccount::isLoggedIn())) {
			require_once 'Login.php';
			Login::launch();
			exit();
		}
		if (!$list->public && $list->user_id != $user->id) {
			PEAR_Singleton::raiseError(new PEAR_Error(translate('list_access_denied')));
		}

		//Reindex can happen by anyone since it needs to be called by cron
		if (isset($_REQUEST['myListActionHead']) && strlen($_REQUEST['myListActionHead']) > 0){
			$actionToPerform = $_REQUEST['myListActionHead'];
			if ($actionToPerform == 'reindex'){
				$list->updateDetailed(true);
			}
		}

		if (isset($_SESSION['listNotes'])){
			$interface->assign('notes', $_SESSION['listNotes']);
			unset($_SESSION['listNotes']);
		}

		//Perform an action on the list, but verify that the user has permission to do so.
		$userCanEdit = false;
		if ($user != false){
			if ($user->id == $list->user_id){
				$userCanEdit = true;
			}elseif ($user->hasRole('opacAdmin')){
				$userCanEdit = true;
			}elseif ($user->hasRole('libraryAdmin') || $user->hasRole('contentEditor')){
				$listUser = new User();
				$listUser->id = $list->user_id;
				$listUser->find(true);
				$listLibrary = Library::getLibraryForLocation($listUser->homeLocationId);
				$userLibrary = Library::getLibraryForLocation($user->homeLocationId);
				if ($userLibrary->libraryId == $listLibrary->libraryId){
					$userCanEdit = true;
				}
			}
		}

		if ($userCanEdit && (isset($_REQUEST['myListActionHead']) || isset($_REQUEST['myListActionItem']) || isset($_GET['delete']))){
			if (isset($_REQUEST['myListActionHead']) && strlen($_REQUEST['myListActionHead']) > 0){
				$actionToPerform = $_REQUEST['myListActionHead'];
				if ($actionToPerform == 'makePublic'){
					$list->public = 1;
					$list->update();
				}elseif ($actionToPerform == 'makePrivate'){
					$list->public = 0;
					$list->updateDetailed(false);
					$list->removeFromSolr();
				}elseif ($actionToPerform == 'saveList'){
					$list->title = $_REQUEST['newTitle'];
					$list->description = $_REQUEST['newDescription'];
					$list->update();
				}elseif ($actionToPerform == 'deleteList'){
					$list->delete();
					header("Location: {$configArray['Site']['path']}/MyResearch/Home");
					die();
				}elseif ($actionToPerform == 'bulkAddTitles'){
					$notes = $this->bulkAddTitles($list);
					$_SESSION['listNotes'] = $notes;
				}
			}elseif (isset($_REQUEST['myListActionItem']) && strlen($_REQUEST['myListActionItem']) > 0){
				$actionToPerform = $_REQUEST['myListActionItem'];

				if ($actionToPerform == 'deleteMarked'){
					//get a list of all titles that were selected
					$itemsToRemove = $_REQUEST['selected'];
					foreach ($itemsToRemove as $id => $selected){
						//add back the leading . to get the full bib record
						$resource = Resource::staticGet('record_id', "$id");
						$list->removeResource($resource);
					}
				}elseif ($actionToPerform == 'deleteAll'){
					$list->removeAllResources(isset($_GET['tag']) ? $_GET['tag'] : null);
				}
				$list->update();
			}elseif (isset($_GET['delete'])) {
				$resource = Resource::staticGet('record_id', $_GET['delete']);
				$list->removeResource($resource);
				$list->update();
			}

			//Redirect back to avoid having the parameters stay in the URL.
			header("Location: {$configArray['Site']['path']}/MyResearch/MyList/{$list->id}");
			die();

		}

		// Send list to template so title/description can be displayed:
		$interface->assign('favList', $list);
		$interface->assign('listSelected', $list->id);

		// Build Favorites List
		$favorites = $list->getListEntries();

		// Load the User object for the owner of the list (if necessary):
		if ($user && ($user->id == $list->user_id)) {
			$listUser = $user;
		} else if ($list->user_id != 0){
			$listUser = new User();
			$listUser->id = $list->user_id;
			if (!$listUser->fetch(true)){
				$listUser = false;
			}
		}else{
			$listUser = false;
		}

		// Create a handler for displaying favorites and use it to assign
		// appropriate template variables:
		$interface->assign('allowEdit', $userCanEdit);
		$favList = new FavoriteHandler($favorites, $listUser, $list->id, $userCanEdit);
		$favList->assign();

		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('list.tpl');
		$interface->display('layout.tpl');
	}

	function bulkAddTitles($list){
		global $user;
		$numAdded = 0;
		$notes = array();
		$titlesToAdd = $_REQUEST['titlesToAdd'];
		$titleSearches[] = preg_split("/\\r\\n|\\r|\\n/", $titlesToAdd);

		foreach ($titleSearches[0] as $titleSearch){
			$_REQUEST['lookfor'] = $titleSearch;
			$_REQUEST['type'] = 'Keyword';
			// Initialise from the current search globals
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->setLimit(1);
			$searchObject->init();
			$searchObject->clearFacets();
			$results = $searchObject->processSearch(false, false);
			if ($results['response'] && $results['response']['numFound'] >= 1){
				$firstDoc = $results['response']['docs'][0];
				//Get the id of the document
				$id = $firstDoc['id'];
				if (preg_match('/eContentRecord/', $id)){
					$source = 'eContent';
					$id = substr($id, 14);
				}else{
					$source = 'VuFind';
				}
				//Get the resource for the id
				$resource = new Resource();
				$resource->record_id = $id;
				$resource->source = $source;
				if ($resource->find(true)){
					$numAdded++;
					$user->addResource($resource, $list, null, false);
				}else{
					//Could not find a resource for the id
					$notes[] = "Could not find a resource matching " . $titleSearch;
				}
			}else{
				$notes[] = "Could not find a title matching " . $titleSearch;
			}
		}

		//Update solr
		$list->update();

		if ($numAdded > 0){
			$notes[] = "Added $numAdded titles to the list";
		}

		return $notes;
	}
}