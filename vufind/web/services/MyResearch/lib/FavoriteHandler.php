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
 /** @var UserList */
	private $list;
	/** @var UserListEntry[] */
	private $favorites;
	/** @var User */
	private $user;
	private $listId;
	private $allowEdit;
	private $ids = array();
	private $defaultSort = 'title'; // initial setting
	private $sort;
	private $isUserListSort; // true for sorting options not done by Solr

	protected $userListSortOptions = array(
		// URL_value => SQL code for Order BY clause
		'dateAdded' => 'dateAdded ASC',
		'custom' => 'weight ASC',
	);

	protected $solrSortOptions = array('title', 'author'); // user list sorting options handled by Solr engine.




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
//	public function __construct($favorites, $user, $listId = null, $allowEdit = true, $defaultSort='title')
//	{
//		$this->favorites = $favorites;
//		$this->user = $user;
//		$this->listId = $listId;
//		$this->allowEdit = $allowEdit;
//		$this->defaultSort;
//
//		// Process the IDs found in the favorites (sort by source):
//		foreach ($favorites as $favorite){
//			if (is_object($favorite)){
//				$this->ids[] = $favorite->groupedWorkPermanentId;
//			}else{
//				$this->ids[] = $favorite;
//			}
//		}
//	}


	/**
	 * Constructor.
	 *
	 * @access  public
	 * @param   UserList   $list        User List Object.
	 * @param   User       $user        User object owning tag/note metadata.
	 * @param   int        $listId      ID of list containing desired tags/notes (or
	 *                                  null to show tags/notes from all user's lists).
	 * @param   bool       $allowEdit   Should we display edit controls?
	 */
	//TODO: update references to the constructor
	public function __construct($list, $user, $allowEdit = true)
	{
		$this->list = $list;
		$this->user = $user;
		$this->listId = $list->id;
		$this->allowEdit = $allowEdit;

		// Determine Sorting Option //
		if (isset($list->defaultSort)) $this->defaultSort = $list->defaultSort; // when list as a sort setting use that
		if (isset($_REQUEST['sort']) && (in_array($_REQUEST['sort'], $this->solrSortOptions) || in_array($_REQUEST['sort'], array_keys($this->userListSortOptions))) ) {
			// if URL variable is a valid sort option, set the list's sort setting
			$this->sort = $_REQUEST['sort'];
		} else {
			$this->sort = $this->defaultSort;
		}

		$this->isUserListSort = in_array($this->sort, array_keys($this->userListSortOptions));

		// Get the Favorites //
		$userListSort = $this->isUserListSort ? $this->userListSortOptions[$this->sort] : null;
		$this->favorites = $list->getListEntries($userListSort); // when using a user list sorting, rather than solr sorting, get results in order

		// Process the IDs found in the favorites
		foreach ($this->favorites as $favorite){
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

		// Initialise from the current search globals
		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		$searchObject->disableScoping();
		$searchObject->setLimit($recordsPerPage); //MDN 3/30 this was set to 200, but should be based off the page size
		$searchObject->setPage($page);

		if (!$this->isUserListSort) { // is a solr sort
			$searchObject->setSort($this->sort); // set solr sort. (have to set before retrieving solr sort options below)
		}
		$SolrSortList = $searchObject->getSortList(); // get all the search sort options (retrieve after setting solr sort option)
		$sortOptions = $defaultSortOptions = array();
		foreach ($this->solrSortOptions as $option) { // extract just the ones we want
			$sortOptions[$option] = $SolrSortList[$option];
			$defaultSortOptions[$option] = $SolrSortList[$option]['desc'];
		}
		foreach ($this->userListSortOptions as $option => $value_ignored) { // Non-Solr options
			$sortOptions[$option] = array(
				'sortUrl' => $searchObject->renderLinkWithSort($option),
				'desc' => "sort_{$option}_userlist", // description in translation dictionary
				'selected' => ($option == $this->sort)
			);
			$defaultSortOptions[$option] = "sort_{$option}_userlist";
		}

		$interface->assign('sortList', $sortOptions);
		$interface->assign('defaultSortList', $defaultSortOptions);
		$interface->assign('defaultSort', $this->defaultSort);
		$interface->assign('userSort', ($this->getSort() == 'custom')); // switch for when users can sort their list

		// Retrieve records from index (currently, only Solr IDs supported):
		if (count($this->favorites) > 0){
			if ($this->isUserListSort) {
				$this->favorites = array_slice($this->favorites, $startRecord - 1, $recordsPerPage);
				$searchObject->setPage(1); // set to the first page for the search only
			}

			$searchObject->setQueryIDs($this->favorites); // do solr search by Ids
			$result = $searchObject->processSearch();
			if (!$this->isUserListSort) { // adjust paging based on search
				$pageInfo['resultTotal'] = $result['response']['numFound'];
				if ($endRecord > $pageInfo['resultTotal']) {
					$endRecord             = $pageInfo['resultTotal'];
					$pageInfo['endRecord'] = $endRecord;
				}
			$resourceList = $searchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit);
			} else {
				$searchObject->setPage($page); // restore the actual sort page
				$resourceList = $searchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit, $this->favorites);
				// puts html in order of favorites
			}

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
		require_once ROOT_DIR . '/sys/Pager.php';
		$pager = new VuFindPager($options);
		$interface->assign('pageLinks', $pager->getLinks());

	}

	function getTitles($numListEntries){
		// Currently only used by AJAX call for emailing lists

		// Initialise from the current search globals
		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		// these are added for emailing list  plb 10-8-2014
		$searchObject->disableScoping(); // get title data regardless of scope
		$searchObject->setLimit($numListEntries); // only get results for each item

		// Retrieve records from index (currently, only Solr IDs supported):
		if (count($this->ids) > 0) {
			$searchObject->setQueryIDs($this->ids);
			$searchObject->processSearch();
			$recordSet = $searchObject->getResultRecordSet();
			//TODO: user list sorting here

			return $recordSet;
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

	/**
	 * @return string
	 */
	public function getSort()
	{
		return $this->sort;
	}
}

?>