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

require_once ROOT_DIR . '/services/MyResearch/lib/Resource.php';
require_once ROOT_DIR . '/services/MyResearch/lib/User.php';

class Record_Save extends Action
{
	/**
	 * @var User the user to save to.
	 */
	private $user;

	function __construct()
	{
		$this->user = UserAccount::isLoggedIn();
	}

	function launch()
	{
		global $interface;
		global $configArray;

		// Check if user is logged in
		if (!$this->user) {
			// Needed for "back to record" link in view-alt.tpl:
			$interface->assign('id', $_GET['id']);
			// Needed for login followup:
			$interface->assign('recordId', $_GET['id']);
			if (isset($_GET['lightbox'])) {
				$interface->assign('title', $_GET['message']);
				$interface->assign('message', 'You must be logged in first');
				$interface->assign('followup', true);
				$interface->assign('followupModule', 'Record');
				$interface->assign('followupAction', 'Save');
				return $interface->fetch('AJAX/login.tpl');
			} else {
				$interface->assign('followup', true);
				$interface->assign('followupModule', 'Record');
				$interface->assign('followupAction', 'Save');
				$interface->setPageTitle('You must be logged in first');
				$interface->assign('subTemplate', '../MyResearch/login.tpl');
				$interface->setTemplate('view-alt.tpl');
				$interface->display('layout.tpl', 'RecordSave' . $_GET['id']);
			}
			exit();
		}

		if (isset($_GET['submit'])) {
			$this->saveRecord();
			header('Location: ' . $configArray['Site']['path'] . '/Record/' .
			urlencode($_GET['id']));
			exit();
		}

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		/** @var SearchObject_Solr $db */
		$db = new $class($configArray['Index']['url']);

		// Get Record Information
		$details = $db->getRecord($_GET['id']);
		$interface->assign('record', $details);

		// Find out if the item is already part of any lists; save list info/IDs
		$saved = $this->user->getSavedData($_GET['id'], 'VuFind');
		$containingLists = array();
		$containingListIds = array();
		foreach($saved as $current) {
			$containingLists[] = array('id' => $current->list_id,
                'title' => $current->list_title);
			$containingListIds[] = $current->list_id;
		}
		$interface->assign('containingLists', $containingLists);

		// Create a list of all the lists that do NOT already contain the item:
		$lists = $this->user->getLists();
		$nonContainingLists = array();
		foreach($lists as $current) {
			if (!in_array($current->id, $containingListIds)) {
				$nonContainingLists[] = array('id' => $current->id,
                    'title' => $current->title);
			}
		}
		$interface->assign('nonContainingLists', $nonContainingLists);

		// Display Page
		$interface->assign('id', $_GET['id']);
		if (isset($_GET['lightbox'])) {
			$interface->assign('title', $_GET['message']);
			return $interface->fetch('Record/save.tpl');
		} else {
			$interface->setPageTitle('Add to favorites');
			$interface->assign('subTemplate', 'save.tpl');
			$interface->setTemplate('view-alt.tpl');
			$interface->display('layout.tpl', 'RecordSave' . $_GET['id']);
		}
		return true;
	}

	function saveRecord()
	{
		if ($this->user) {
			$list = new UserList();
			if ($_GET['list'] != '') {
				$list->id = $_GET['list'];
				$list->find(true);
			} else {
				$list->user_id = $this->user->id;
				$list->title = "My Favorites";
				$list->insert();
			}

			$resource = new Resource();
			$resource->record_id = $_GET['id'];
			if (isset($_GET['service'])){
				$resource->source = $_GET['service'];
			}else{
				$resource->source = $_GET['source'];
			}
			if (!$resource->find(true)) {
				PEAR_Singleton::raiseError(new PEAR_Error('Unable find a resource for that title.'));
			}

			if (array_key_exists('mytags', $_REQUEST) ){
				preg_match_all('/"[^"]*"|[^,]+/', $_REQUEST['mytags'], $tagArray);
				$tags = $tagArray[0];
			}else{
				$tags = null;
			}
			$notes = '';
			if (array_key_exists('notes', $_REQUEST)){
				$notes = $_REQUEST['notes'];
			}

			return $this->user->addResource($resource, $list, $tags, $notes);
		} else {
			return false;
		}
	}

}