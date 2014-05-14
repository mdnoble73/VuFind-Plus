<?php
/**
 * Asynchronous functionality for MyAccount module
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 3/25/14
 * Time: 4:26 PM
 */

class MyAccount_AJAX {
	function launch(){
		$method = $_GET['method'];
		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		echo $this->$method();
	}

	function getBulkAddToListForm(){
		global $interface;
		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['listId']));
		$interface->assign('popupTitle', 'Add titles to list');
		$formDefinition = array(
			'title' => 'Add titles to list',
			'modalBody' => $interface->fetch('MyResearch/bulkAddToListPopup.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.Lists.processBulkAddForm(); return false;'>Add To List</span>"
		);
		return json_encode($formDefinition);
	}

	function removeTag(){
		global $user;
		$tagToRemove = $_REQUEST['tag'];

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserTag.php';
		$userTag = new UserTag();
		$userTag->tag = $tagToRemove;
		$userTag->userId = $user->id;
		$numDeleted = $userTag->delete();
		$result = array(
				'result' => true,
				'message' => "Removed tag '{$tagToRemove}' from $numDeleted titles."
		);
		return json_encode($result);
	}

	function saveSearch(){
		global $user;

		$searchId = $_REQUEST['searchId'];
		$search = new SearchEntry();
		$search->id = $searchId;
		$saveOk = false;
		if ($search->find(true)) {
			// Found, make sure this is a search from this user
			if ($search->session_id == session_id() || $search->user_id == $user->id) {
				if ($search->saved != 1) {
					global $user;
					$search->user_id = $user->id;
					$search->saved = 1;
					$saveOk = ($search->update() !== FALSE);
					$message = $saveOk ? "Your search was saved successfully.  You can view the saved search by clicking on Search History within My Account." : "Sorry, we could not save that search for you.  It may have expired.";
				}else{
					$saveOk = true;
					$message = "That search was already saved.";
				}
			}else{
				$message = "Sorry, it looks like that search does not belong to you.";
			}
		}else{
			$message = "Sorry, it looks like that search has expired.";
		}
		$result = array(
			'result' => $saveOk,
			'message' => $message,
		);
		return json_encode($result);
	}

	function deleteSavedSearch(){
		global $user;

		$searchId = $_REQUEST['searchId'];
		$search = new SearchEntry();
		$search->id = $searchId;
		$saveOk = false;
		if ($search->find(true)) {
			// Found, make sure this is a search from this user
			if ($search->session_id == session_id() || $search->user_id == $user->id) {
				if ($search->saved != 0) {
					$search->saved = 0;
					$saveOk = ($search->update() !== FALSE);
					$message = $saveOk ? "Your saved search was deleted successfully." : "Sorry, we could not delete that search for you.  It may have already been deleted.";
				}else{
					$saveOk = true;
					$message = "That search is not saved.";
				}
			}else{
				$message = "Sorry, it looks like that search does not belong to you.";
			}
		}else{
			$message = "Sorry, it looks like that search has expired.";
		}
		$result = array(
				'result' => $saveOk,
				'message' => $message,
		);
		return json_encode($result);
	}
} 