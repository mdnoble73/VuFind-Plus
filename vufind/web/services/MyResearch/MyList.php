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

require_once 'Action.php';
require_once 'services/MyResearch/lib/FavoriteHandler.php';
require_once 'services/MyResearch/lib/Resource.php';
require_once 'services/MyResearch/lib/User_resource.php';
require_once 'services/MyResearch/lib/Resource_tags.php';

/**
 * This class does not use MyResearch base class (we don't need to connect to
 * the catalog, and we need to bypass the "redirect if not logged in" logic to
 * allow public lists to work properly).
 * @version  $Revision$
 */
class MyList extends Action {
	private $db;
	var $catalog;

	function launch() {
		global $configArray;
		global $interface;
		global $user;

		//Get all lists for the user
		if ($user){
			$tmpList = new User_list();
			$tmpList->user_id = $user->id;
			$tmpList->orderBy("title ASC");
			$tmpList->find();
			$allLists = array();
			if ($tmpList->N > 0){
				while ($tmpList->fetch()){
					$allLists[$tmpList->id] = $tmpList->title;
				}
			}else{
				$allList["-1"] = "My Favorites";
			}
			$interface->assign('allLists', $allLists);
		}

		// Fetch List object
		if (isset($_GET['id'])){
			$list = User_list::staticGet($_GET['id']);
		}else{
			//Use the first list.
			if (isset($allLists)){
				$firstListId = reset(array_keys($allLists));
				if ($firstListId == false || $firstListId == -1){
					$list = new User_list();
					$list->user_id = $user->id;
					$list->public = false;
					$list->title = "My Favorites";
				}else{
					$list = User_list::staticGet($firstListId);
				}
			}
		}

		// Ensure user have privs to view the list
		if (!isset($list) || (!$list->public && !UserAccount::isLoggedIn())) {
			require_once 'Login.php';
			Login::launch();
			exit();
		}
		if (!$list->public && $list->user_id != $user->id) {
			PEAR::raiseError(new PEAR_Error(translate('list_access_denied')));
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
		if ($user != false && $user->id == $list->user_id && (isset($_REQUEST['myListActionHead']) || isset($_REQUEST['myListActionItem']) || isset($_GET['delete']))){
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
					header("Location: {$configArray['Site']['url']}/MyResearch/Home");
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
			header("Location: {$configArray['Site']['url']}/MyResearch/MyList/{$list->id}");
			die();

		}

		// Send list to template so title/description can be displayed:
		$interface->assign('favList', $list);
		$interface->assign('listSelected', $list->id);

		// Build Favorites List
		$favorites = $list->getResources(isset($_GET['tag']) ? $_GET['tag'] : null);

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
		$allowEdit = (($user != false) && ($user->id == $list->user_id));
		$interface->assign('allowEdit', $allowEdit);
		$favList = new FavoriteHandler($favorites, $listUser, $list->id, $allowEdit);
		$favList->assign();

		//Need to add profile information from MyResearch to show profile data.
		if ($user !== false){
			global $configArray;
			$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);
			// Get My Profile
			if ($this->catalog->status) {
				if ($user->cat_username) {
					$patron = $this->catalog->patronLogin($user->cat_username, $user->cat_password);
					if (PEAR::isError($patron)){
						PEAR::raiseError($patron);
					}

					$result = $this->catalog->getMyProfile($patron);
					if (!PEAR::isError($result)) {
						$interface->assign('profile', $result);
					}
				}
			}

			//Figure out if we should show a link to classic opac to pay holds.
			$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);
			if (isset($homeLibrary) && $homeLibrary->showEcommerceLink == 1){
				$interface->assign('showEcommerceLink', true);
				$interface->assign('minimumFineAmount', $homeLibrary->minimumFineAmount);
			}else{
				$interface->assign('showEcommerceLink', false);
				$interface->assign('minimumFineAmount', 0);
			}
		}

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