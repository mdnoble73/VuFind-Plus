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
require_once 'services/MyResearch/lib/Resource.php';
require_once 'sys/Pager.php';

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
	private $favorites;
	private $user;
	private $listId;
	private $allowEdit;
	private $ids = array();

	/**
	 * Constructor.
	 *
	 * @access  public
	 * @param   array   $favorites  Array of Resource objects.
	 * @param   object  $user       User object owning tag/note metadata.
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
		if (is_array($favorites)) {
			foreach($favorites as $current) {
				$id = $current->record_id;
				if (!empty($id)) {
					$source = strtolower($current->source);
					if (!isset($this->ids[$source])) {
						$this->ids[$source] = array();
					}
					$this->ids[$source][] = $id;
				}
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
		
		$recordsPerPage = isset($_REQUEST['pagesize']) && (is_numeric($_REQUEST['pagesize'])) ? $_REQUEST['pagesize'] : 25;
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
		$this->favorites = array_slice($this->favorites, $startRecord -1, $recordsPerPage);
		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		$interface->assign('sortList', $searchObject->getSortList());
		
		$resourceList = array();
		if (is_array($this->favorites)) {
			foreach($this->favorites as $currentResource) {
				$interface->assign('resource', $currentResource);
				$resourceEntry = $interface->fetch('RecordDrivers/Resource/listentry.tpl');
				$resourceList[] = $resourceEntry; 
			}
		}
		$interface->assign('resourceList', $resourceList);

		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		/*$searchObject->init();
		$interface->assign('sortList', $searchObject->getSortList());

		// Retrieve records from index (currently, only Solr IDs supported):
		$vuFindList = array();
		if (array_key_exists('vufind', $this->ids) && count($this->ids['vufind']) > 0) {
			$searchObject->setQueryIDs($this->ids['vufind']);
			$result = $searchObject->processSearch();
			$vuFindList = $searchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit);
		}
		$eContentList = array();
		if (array_key_exists('econtent', $this->ids) && count($this->ids['econtent']) > 0) {
			$eContentIds = array();
			foreach ($this->ids['econtent'] as $eContentId){
				$eContentIds[] = 'econtentRecord' . $eContentId;
			}
			$searchObject->setQueryIDs($eContentIds);
			$result = $searchObject->processSearch();
			$eContentList = $searchObject->getResultListHTML($this->user, $this->listId, $this->allowEdit);
		}
		$resourceList = array_merge($vuFindList, $eContentList);
		$interface->assign('resourceList', $resourceList);*/

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
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Retrieve records from index (currently, only Solr IDs supported):
		if (array_key_exists('vufind', $this->ids) &&
		count($this->ids['vufind']) > 0) {
			$searchObject->setQueryIDs($this->ids['vufind']);
			$result = $searchObject->processSearch();
			return $searchObject->getResultRecordSet();
		}else{
			return array();
		}
	}
}

?>