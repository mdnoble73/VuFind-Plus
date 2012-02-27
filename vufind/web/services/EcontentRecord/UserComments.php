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

require_once 'sys/eContent/EContentRecord.php';
require_once 'RecordDrivers/EcontentRecordDriver.php';
require_once 'services/MyResearch/lib/Comments.php';

class UserComments extends Action{
	function launch(){
		global $interface;
		global $user;
		global $configArray;

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
		
		$interface->assign('id', $_GET['id']);

		if (isset($_REQUEST['comment'])) {
			if (!$user) {
				$interface->assign('recordId', $_GET['id']);
				$interface->assign('comment', $_REQUEST['comment']);
				$interface->assign('followup', true);
				$interface->assign('followupModule', 'EContentRecord');
				$interface->assign('followupAction', 'UserComments');
				$interface->setPageTitle('You must be logged in first');
				$interface->assign('subTemplate', '../MyResearch/login.tpl');
				$interface->setTemplate('view-alt.tpl');
				$interface->display('layout.tpl', 'UserComments' . $_GET['id']);
				exit();
			}
			$result = $this->saveComment();
		}

		$interface->assign('user', $user);
		
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $_GET['id'];
		$eContentRecord->find(true);
		
		$recordDriver = new EcontentRecordDriver();
		$recordDriver->setDataObject($eContentRecord);

		$interface->setPageTitle(translate('Comments') . ': ' . $recordDriver->getBreadcrumb());

		$this->loadEContentComments();

		$interface->assign('subTemplate', 'view-comments.tpl');
		$interface->setTemplate('view-alt.tpl');

		// Display Page
		$interface->display('layout.tpl'/*, $cacheId */);
	}

	function loadEContentComments(){
		global $interface;

		$resource = new Resource();
		$resource->record_id = $_GET['id'];
		$resource->source = 'EContent';
		if ($resource->find(true)) {
			$commentLists = $resource->getComments();
			$interface->assign('commentList', $commentLists['user']);
			$interface->assign('staffCommentList', $commentLists['staff']);
		}
	}

	function saveComment(){
		global $user;

		// What record are we operating on?
		if (!isset($_GET['id'])) {
			return false;
		}

		// record already saved as resource?
		$resource = new Resource();
		$resource->record_id = $_GET['id'];
		$resource->source = 'eContent';
		if (!$resource->find(true)) {
			$resource->insert();
		}

		$resource->addComment($_REQUEST['comment'], $user);

		return true;
	}

}