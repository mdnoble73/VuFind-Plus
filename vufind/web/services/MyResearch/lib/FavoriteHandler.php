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
	private $ids = array(),
					$catalogIds = array(),
					$archiveIds = array();
	private $defaultSort = 'title'; // initial setting
	private $sort;
	private $isUserListSort; // true for sorting options not done by Solr
	private $isMixedUserList = false; // Flag for user lists that have both catalog & archive items (and eventually other type of items)

	protected $userListSortOptions = array(
								// URL_value => SQL code for Order BY clause
								'dateAdded' => 'dateAdded ASC',
								'custom' => 'weight ASC',
							);
	protected $solrSortOptions = array('title', 'author'), // user list sorting options handled by Solr engine.
						$islandoraSortOptions = array(); // user list sorting options handled by the Islandora Solr engine.

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
	public function __construct($list, $user, $allowEdit = true)
	{
		$this->list      = $list;
		$this->user      = $user;
		$this->listId    = $list->id;
		$this->allowEdit = $allowEdit;


		// Determine Sorting Option //
		if (isset($list->defaultSort)) $this->defaultSort = $list->defaultSort; // when list has a sort setting use that
		if (isset($_REQUEST['sort']) && (in_array($_REQUEST['sort'], $this->solrSortOptions) || in_array($_REQUEST['sort'], $this->islandoraSortOptions) || in_array($_REQUEST['sort'], array_keys($this->userListSortOptions))) ) {
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
		$hasArchiveItems = $hasCatalogItems = false;
		foreach ($this->favorites as $favorite){
			$favoriteID = is_object($favorite) ? $favorite->groupedWorkPermanentId : $favorite;
			$this->ids[] = $favoriteID;
			//TODO: Filter out Archive Object IDs? (Determine different uses of $this->ids from $this->favorites)
			if (strpos($favoriteID, ':') !== false) {
				//Is an archive Object
				// (This may be the point where a specified source is needed for UserList Items.)
				$this->archiveIds[] = $favoriteID;
				$hasArchiveItems = true;
			} else {
				// Assuming all other Ids are grouped work Ids.
				$this->catalogIds[] = $favoriteID;
				$hasCatalogItems = true;
			}
		}

		// Determine if this UserList mixes catalog & archive Items
		if ($hasArchiveItems && $hasCatalogItems) {
			$this->isMixedUserList = true;
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
			'endRecord'   => $endRecord,
			'perPage'     => $recordsPerPage
		);

		$sortOptions = $defaultSortOptions = array();

		// Catalog Search
		$catalogResourceList = array();
		if (count($this->catalogIds) > 0) {
			// Initialise from the current search globals
			/** @var SearchObject_Solr $catalogSearchObject */
			$catalogSearchObject = SearchObjectFactory::initSearchObject();
			$catalogSearchObject->init();
			$catalogSearchObject->disableScoping();
			$catalogSearchObject->setLimit($recordsPerPage); //MDN 3/30 this was set to 200, but should be based off the page size
//			$catalogSearchObject->setPage($page);

			if (!$this->isUserListSort && !$this->isMixedUserList) { // is a solr sort
				$catalogSearchObject->setSort($this->sort); // set solr sort. (have to set before retrieving solr sort options below)
			}
			if (!$this->isMixedUserList) {
				$SolrSortList = $catalogSearchObject->getSortList(); // get all the search sort options (retrieve after setting solr sort option)
				//TODO: There is no longer an author sort option?
				foreach ($this->solrSortOptions as $option) { // extract just the ones we want
					if (isset ($SolrSortList[$option])) {
						$sortOptions[$option]        = $SolrSortList[$option];
						$defaultSortOptions[$option] = $SolrSortList[$option]['desc'];
					}
				}
			}
			foreach ($this->userListSortOptions as $option => $value_ignored) { // Non-Solr options
				$sortOptions[$option]        = array(
					'sortUrl' => $catalogSearchObject->renderLinkWithSort($option),
					'desc' => "sort_{$option}_userlist", // description in translation dictionary
					'selected' => ($option == $this->sort)
				);
				$defaultSortOptions[$option] = "sort_{$option}_userlist";
			}

			/*			Use Cases:
						Only Catalog items, user sort
						Only Catalog items, solr sort
						Only Archive items, user sort
						Only Archive items, islandora sort
						Mixed Items, user sort

			*/

			// Catalog Only Searches //
			if (!$this->isMixedUserList) {
				// User Sorted Catalog Only Search
				if ($this->isUserListSort) {
					$this->catalogIds = array_slice($this->catalogIds, $startRecord - 1, $recordsPerPage);
					$catalogSearchObject->setPage(1); // set to the first page for the search only

					$catalogSearchObject->setQueryIDs($this->catalogIds); // do solr search by Ids
					$catalogResult = $catalogSearchObject->processSearch();

					//TODO: Check how Paging Results work
					$catalogResourceList = $catalogSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit, $this->favorites);
				} // Solr Sorted Catalog Only Search //
				else {
					$catalogSearchObject->setQueryIDs($this->catalogIds); // do solr search by Ids
					$catalogSearchObject->setPage($page); // restore the actual sort page //TODO: Page needs set before processSearch() call?
					$catalogResult       = $catalogSearchObject->processSearch();
					$catalogResourceList = $catalogSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit);
				}
			} // Mixed Items Searches (All User Sorted) //
			else {
				// Removed all catalog items from previous page searches
				$totalItemsFromPreviousPages = $recordsPerPage * $page;
				for ($i = 0; $i++; $i < $totalItemsFromPreviousPages) {
					$IdToTest = $this->favorites[$i];
					$key      = array_search($IdToTest, $this->catalogIds);
					if ($key !== false) {
						unset($this->catalogIds[$key]);
					}
				}
				$this->catalogIds = array_slice($this->catalogIds, 0, $recordsPerPage);
				$catalogSearchObject->setPage(1); // set to the first page for the search only
				$catalogResult       = $catalogSearchObject->processSearch();
				$catalogResourceList = $catalogSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit, $this->favorites);
			}
		}


			/* OLD Catalog Search Code below. Probably mangled  */
			// Retrieve records from index (currently, only Solr IDs supported):

//			// Use Case: Only Catalog items, user sort
//				if ($this->isUserListSort && !$this->isMixedUserList) {
//					$this->catalogIds = array_slice($this->catalogIds, $startRecord - 1, $recordsPerPage);
//					$catalogSearchObject->setPage(1); // set to the first page for the search only
//
//					$catalogSearchObject->setQueryIDs($this->catalogIds); // do solr search by Ids
//				}
//
//
//				// Use Case: Only Catalog items, solr sort
//				if (!$this->isUserListSort && !$this->isMixedUserList) { // adjust paging based on search
//
//					$catalogSearchObject->setQueryIDs($this->catalogIds); // do solr search by Ids
//					$catalogResult = $catalogSearchObject->processSearch();
//
//					$pageInfo['resultTotal'] = $catalogResult['response']['numFound'];
//					if ($endRecord > $pageInfo['resultTotal']) {
//						$endRecord             = $pageInfo['resultTotal'];
//						$pageInfo['endRecord'] = $endRecord;
//					}
//					$catalogResult = $catalogSearchObject->processSearch();
//					$catalogResourceList = $catalogSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit);
//				}
//
//
//				// Use Cases: Only Catalog items, user sort; and Mixed Items, user sort
//				else {
//					// Use Case: Only Catalog items, user sort;
//					if (!$this->isMixedUserList) {
//						$catalogSearchObject->setPage($page); // restore the actual sort page
//					}
//					// Use Case: Mixed Items, user sort
//					else {
//						// TODO:  Determine Which catalog page to get
//						// Total Items from previous pages - Archive Items from Previous Pages = Number of Catalog Items on Previous Pages
//						$totalItemsFromPreviousPages = $recordsPerPage * $page;
//						$catalogItemsFromPreviousPages = 0;
//						for ($i=0;$i++;$i<$totalItemsFromPreviousPages) {
//							$IdToTest = $this->favorites[$i];
//							if (in_array($IdToTest, $this->catalogIds)) { $catalogItemsFromPreviousPages++; }
//						}
//						$pageToFetch = floor(2.3  );
//
//					}
//					$catalogResourceList = $catalogSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit, $this->favorites);
//					// puts html in order of favorites
//				}
//
//		}


		// Archive Search
		$archiveResourceList = array();
		if (count($this->archiveIds) > 0) {

			// Initialise from the current search globals
			/** @var SearchObject_Islandora $archiveSearchObject */
			$archiveSearchObject = SearchObjectFactory::initSearchObject('Islandora');
			$archiveSearchObject->init();
			$archiveSearchObject->setPrimarySearch(true);
			$archiveSearchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
			$archiveSearchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
			$archiveSearchObject->setLimit($recordsPerPage); //MDN 3/30 this was set to 200, but should be based off the page size
			$archiveSearchObject->setPage($page);

			// TODO: Set Sorting Options for Archive Only Searches

			/*
//			if (!$this->isUserListSort) { // is a solr sort
//				$archiveSearchObject->setSort($this->sort); // set solr sort. (have to set before retrieving solr sort options below)
//			}
//			$SolrSortList = $archiveSearchObject->getSortList(); // get all the search sort options (retrieve after setting solr sort option)
			//TODO: Match corresponding sort options here: sort_title, ?
//			$sortOptions = $defaultSortOptions = array();
//			foreach ($this->solrSortOptions as $option) { // extract just the ones we want
//				if (isset ($SolrSortList[$option])) {
//					$sortOptions[$option]        = $SolrSortList[$option];
//					$defaultSortOptions[$option] = $SolrSortList[$option]['desc'];
//				}
//			}
//			foreach ($this->userListSortOptions as $option => $value_ignored) { // Non-Solr options
//				$sortOptions[$option]        = array(
//					'sortUrl' => $archiveSearchObject->renderLinkWithSort($option),
//					'desc' => "sort_{$option}_userlist", // description in translation dictionary
//					'selected' => ($option == $this->sort)
//				);
//				$defaultSortOptions[$option] = "sort_{$option}_userlist";
//			}
			*/


			// Archive Only Searches //
			if (!$this->isMixedUserList) {
				// User Sorted Archive Only Searches
				if ($this->isUserListSort) {
					$this->archiveIds = array_slice($this->archiveIds, $startRecord - 1, $recordsPerPage);
					$archiveSearchObject->setPage(1); // set to the first page for the search only

					$archiveSearchObject->setQueryIDs($this->archiveIds); // do solr search by Ids
					$archiveResult = $archiveSearchObject->processSearch();
					$archiveResourceList = $archiveSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit, $this->favorites);
				}

				// Islandora Sorted Archive Only Searches
				else {

				}
			}

		 // Mixed Items Searches (All User Sorted) //
		else {
			// Removed all catalog items from previous page searches
			$totalItemsFromPreviousPages = $recordsPerPage * $page;
			for ($i = 0; $i++; $i < $totalItemsFromPreviousPages) {
				$IdToTest = $this->favorites[$i];
				$key      = array_search($IdToTest, $this->archiveIds);
				if ($key !== false) {
					unset($this->archiveIds[$key]);
				}
			}
			$this->archiveIds = array_slice($this->archiveIds, 0, $recordsPerPage);
			$archiveSearchObject->setPage(1); // set to the first page for the search only

			$archiveSearchObject->setQueryIDs($this->archiveIds); // do solr search by Ids
			$archiveResult = $archiveSearchObject->processSearch();
			$archiveResourceList = $archiveSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit, $this->favorites);
			}

			// OLDer CODE Below for Archive Searches ///

/*			// Retrieve records from index
			if ($this->isUserListSort) {
				$this->archiveIds = array_slice($this->archiveIds, $startRecord - 1, $recordsPerPage);
				$archiveSearchObject->setPage(1); // set to the first page for the search only
			}

			$archiveSearchObject->setQueryIDs($this->archiveIds); // do solr search by Ids
			$archiveResult = $archiveSearchObject->processSearch();
			if (!$this->isUserListSort) { // adjust paging based on search
//				$pageInfo['resultTotal'] = $archiveResult['response']['numFound'];
//				if ($endRecord > $pageInfo['resultTotal']) {
//					$endRecord             = $pageInfo['resultTotal'];
//					$pageInfo['endRecord'] = $endRecord;
//				}
				$archiveResourceList = $archiveSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit);
			} else {
				$archiveSearchObject->setPage($page); // restore the actual sort page
				$archiveResourceList = $archiveSearchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit, $this->favorites);
				// TODO: $this->favorities should be $this->archiveIds
			}*/

		}

		$interface->assign('sortList', $sortOptions);
		$interface->assign('defaultSortList', $defaultSortOptions);
		$interface->assign('defaultSort', $this->defaultSort);
		$interface->assign('userSort', ($this->getSort() == 'custom')); // switch for when users can sort their list


		//TODO: Combine Searches in correct order


		$resourceList = array_merge($catalogResourceList, $archiveResourceList); // Default Combining

		$interface->assign('resourceList', $resourceList);

		// Set up paging of list contents:
		//TODO: recalculate based on combined searches
		$interface->assign('recordCount', $pageInfo['resultTotal']);
		$interface->assign('recordStart', $pageInfo['startRecord']);
		$interface->assign('recordEnd',   $pageInfo['endRecord']);
		$interface->assign('recordsPerPage', $pageInfo['perPage']);

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