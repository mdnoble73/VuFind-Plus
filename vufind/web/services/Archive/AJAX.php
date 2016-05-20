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

class Archive_AJAX extends Action {


	function launch() {
		global $timer;
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");
		//JSON Responses
		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		echo json_encode($this->$method());
	}

	function getExploreMoreContent(){
		if (!isset($_REQUEST['id'])){
			return array(
					'success' => false,
					'message' => 'You must supply the id to load explore more content for'
			);
		}
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();
		$this->pid = urldecode($_REQUEST['id']);
		$interface->assign('pid', $this->pid);
		$this->archiveObject = $fedoraUtils->getObject($this->pid);
		$this->recordDriver = RecordDriverFactory::initRecordDriver($this->archiveObject);
		$interface->assign('recordDriver', $this->recordDriver);

		require_once ROOT_DIR . '/sys/ExploreMore.php';
		$exploreMore = new ExploreMore();
		$exploreMore->loadExploreMoreSidebar('archive', $this->recordDriver);


		$relatedSubjects = $this->recordDriver->getAllSubjectHeadings();

		$ebscoMatches = $exploreMore->loadEbscoOptions('archive', array(), implode($relatedSubjects, " or "));
		if (count($ebscoMatches) > 0){
			$interface->assign('relatedArticles', $ebscoMatches);
		}

		return array(
				'success' => true,
				'exploreMore' => $interface->fetch('explore-more-sidebar.tpl')
		);
	}
}
