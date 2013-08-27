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

require_once 'Record.php';

require_once ROOT_DIR . '/services/MyResearch/lib/Comments.php';

class Record_UserComments extends Record_Record{
	function launch(){
		global $interface;
		global $user;

		// Process Delete Comment
		if ((isset($_GET['delete'])) && (is_object($user))) {
			$comment = new Comments();
			$comment->id = $_GET['delete'];
			if ($comment->find(true)) {
				if ($user->id == $comment->user_id) {
					$comment->delete();
				}
			}
		}

		if (isset($_REQUEST['comment'])) {
			if (!$user) {
				$interface->assign('recordId', $_GET['id']);
				$interface->assign('comment', $_REQUEST['comment']);
				$interface->assign('followup', true);
				$interface->assign('followupModule', 'Record');
				$interface->assign('followupAction', 'UserComments');
				$interface->setPageTitle('You must be logged in first');
				$interface->assign('subTemplate', '../MyResearch/login.tpl');
				$interface->setTemplate('view-alt.tpl');
				$interface->display('layout.tpl', 'UserComments' . $_GET['id']);
				exit();
			}
			$this->saveComment();
		}

		$interface->assign('user', $user);

		$interface->setPageTitle(translate('Comments') . ': ' . $this->recordDriver->getBreadcrumb());

		$this->loadComments($this->mergedRecords);

		$interface->assign('subTemplate', 'view-comments.tpl');
		$interface->setTemplate('view.tpl');

		// Display Page
		$interface->display('layout.tpl'/*, $cacheId */);
	}

	function loadComments($mergedRecords){
		global $interface;

		$commentLists = null;
		$commentLists = Record_UserComments::loadCommentsForIdAndSource($_GET['id'], 'VuFind', $commentLists);

		//Get comments from merged records
		if (count($mergedRecords)){
			foreach ($mergedRecords as $mergedId){
				$commentLists = Record_UserComments::loadCommentsForIdAndSource($mergedId, 'VuFind', $commentLists);
			}
		}

		if ($commentLists != null){
			$interface->assign('commentList', $commentLists['user']);
			$interface->assign('staffCommentList', $commentLists['staff']);
		}
	}

	function loadCommentsForIdAndSource($id, $source, $commentLists){
		$resource = new Resource();
		$resource->record_id = $id;
		$resource->source = $source;
		$resource->deleted = 0;
		if ($resource->find(true)) {
			$newCommentLists = $resource->getComments();
			if ($commentLists == null){
				$commentLists = $newCommentLists;
			}else{
				$commentLists = array_merge($commentLists, $newCommentLists);
			}
		}
		return $commentLists;
	}
	
	function loadEContentComments(){
		global $interface;

		$resource = new Resource();
		$resource->record_id = $_GET['id'];
		$resource->source = 'EContent';
		$resource->deleted = 0;
		if ($resource->find(true)) {
			$commentLists = $resource->getComments();
			$interface->assign('commentList', $commentLists['user']);
			$interface->assign('staffCommentList', $commentLists['staff']);
		}
	}

	/**
	 * Return comments for a particular record and return them as an array.
	 *
	 * @param $id
	 * @return array|null
	 */
	static function getComments($id){
		$resource = new Resource();
		$resource->record_id = $id;
		$resource->source = 'VuFind';
		if ($resource->find(true)) {
			$commentList = $resource->getComments();
			return $commentList;
		}else{
			return null;
		}
	}

	function saveComment()
	{

		global $user;

		// What record are we operating on?
		if (!isset($_GET['id'])) {
			return false;
		}

		// record already saved as resource?
		$resource = new Resource();
		$resource->record_id = $_GET['id'];
		$resource->source = 'VuFind';
		if (!$resource->find(true)) {
			$resource->insert();
		}

		$resource->addComment($_REQUEST['comment'], $user);

		return true;
	}

}