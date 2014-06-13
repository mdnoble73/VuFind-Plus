<?php
/**
 *
 * Copyright (C) Villanova University 2010.
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
require_once ROOT_DIR . '/sys/Pager.php';

/**
 * FavoriteHandler Class
 *
 * This class contains shared logic for displaying lists of favorites (based on
 * earlier logic duplicated between the MyResearch/Home and MyResearch/MyList
 * actions).
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class FavoriteHandler
{
	/** @var UserListEntry[] */
	private $favorites;
	/** @var User */
	private $user;
	private $listId;
	private $allowEdit;
	private $ids = array();

	/**
	 * Constructor.
	 *
	 * @access  public
	 * @param   UserListEntry[]   $favorites  Array of grouped work ids.
	 * @param   User  $user       User object owning tag/note metadata.
	 * @param   int     $listId     ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param   bool    $allowEdit  Should we display edit controls?
	 */
	public function __construct($favorites, $user, $listId = null, $allowEdit = true)
	{
		$this->favorites = $favorites;
		$this->user = $user;
		$this->listId = $listId;
		$this->allowEdit = $allowEdit;

		// Process the IDs found in the favorites (sort by source):
		foreach ($favorites as $favorite){
			if (is_object($favorite)){
				$this->ids[] = $favorite->groupedWorkPermanentId;
			}else{
				$this->ids[] = $favorite;
			}
		}
	}

	/**
	 * Assign all necessary values to the interface.
	 *
	 * @access  public
	 */
	public function assign()
	{
		global $interface;

		$recordsPerPage = isset($_REQUEST['pagesize']) && (is_numeric($_REQUEST['pagesize'])) ? $_REQUEST['pagesize'] : 20;
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$startRecord = ($page - 1) * $recordsPerPage + 1;
		if ($startRecord < 0){
			$startRecord = 0;
		}
		$endRecord = $page * $recordsPerPage;
		if ($endRecord > count($this->favorites)){
			$endRecord = count($this->favorites);
		}
		$pageInfo = array(
			'resultTotal' => count($this->favorites),
			'startRecord' => $startRecord,
			'endRecord' => $endRecord,
			'perPage' => $recordsPerPage
		);
		//Don't slice here since it is done in the search object
		//$this->favorites = array_slice($this->favorites, $startRecord -1, $recordsPerPage);

		// Initialise from the current search globals
		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		$searchObject->disableScoping();
		$searchObject->setLimit($recordsPerPage);
		$searchObject->setPage($page);
		$interface->assign('sortList', $searchObject->getSortList());

		// Retrieve records from index (currently, only Solr IDs supported):
		if (count($this->favorites) > 0){
			$searchObject->setQueryIDs($this->favorites);
			$result = $searchObject->processSearch();
			$resourceList = $searchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit);
		}else{
			$resourceList = array();
		}

		$interface->assign('resourceList', $resourceList);

		// Set up paging of list contents:
		$interface->assign('recordCount', $pageInfo['resultTotal']);
		$interface->assign('recordStart', $pageInfo['startRecord']);
		$interface->assign('recordEnd',   $pageInfo['endRecord']);

		$link = $_SERVER['REQUEST_URI'];
		if (preg_match('/[&?]page=/', $link)){
			$link = preg_replace("/page=\\d+/", "page=%d", $link);
		}else if (strpos($link, "?") > 0){
			$link .= "&page=%d";
		}else{
			$link .= "?page=%d";
		}
		$options = array('totalItems' => $pageInfo['resultTotal'],
		                 'perPage' => $pageInfo['perPage'],
		                 'fileName' => $link,
		                 'append'    => false);
		$pager = new VuFindPager($options);
		$interface->assign('pageLinks', $pager->getLinks());
	}

	function getTitles(){
		// Initialise from the current search globals
		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Retrieve records from index (currently, only Solr IDs supported):
		if (count($this->ids) > 0) {
			$searchObject->setQueryIDs($this->ids);
			$searchObject->processSearch();
			return $searchObject->getResultRecordSet();
		}else{
			return array();
		}
	}

	function getCitations($citationFormat){
		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Retrieve records from index (currently, only Solr IDs supported):
		if (count($this->ids) > 0) {
			$searchObject->setQueryIDs($this->ids);
			$searchObject->processSearch();
			return $searchObject->getCitations($citationFormat);
		}else{
			return array();
		}
	}
}

?>