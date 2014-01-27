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

class Browse_AJAX extends Action {

	/** @var SearchObject_Solr */
	private $searchObject;

	function launch()
	{
		header ('Content-type: application/json');
		$response = array();
		$method = $_REQUEST['method'];
		if (in_array($method, array('getBrowseCategoryInfo', 'getMoreBrowseResults'))){
			$response = $this->$method();
		}else if (is_callable(array($this, $_GET['method']))) {
			$this->searchObject = SearchObjectFactory::initSearchObject();
			$this->searchObject->initBrowseScreen();
			$this->searchObject->disableLogging();
			$this->$method();
			$result = $this->searchObject->processSearch();
			$response['AJAXResponse'] = $result['facet_counts']['facet_fields'];
			// Shutdown the search object
			$this->searchObject->close();
		} else {
			$response['AJAXResponse'] = array('Error' => 'Invalid Method');
		}


		echo json_encode($response);
	}

	function GetOptions()
	{
		if (isset($_GET['field']))        $this->searchObject->addFacet($_GET['field']);
		if (isset($_GET['facet_prefix'])) $this->searchObject->addFacetPrefix($_GET['facet_prefix']);
		if (isset($_GET['query']))        $this->searchObject->setQueryString($_GET['query']);
	}

	function GetAlphabet()
	{
		if (isset($_GET['field'])) $this->searchObject->addFacet($_GET['field']);
		if (isset($_GET['query'])) $this->searchObject->setQueryString($_GET['query']);
		$this->searchObject->setFacetSortOrder(false);
	}

	function GetSubjects()
	{
		if (isset($_GET['field'])) $this->searchObject->addFacet($_GET['field']);
		if (isset($_GET['query'])) $this->searchObject->setQueryString($_GET['query']);
	}

	function getBrowseCategoryInfo($textId = null){
		global $interface;
		$this->searchObject = SearchObjectFactory::initSearchObject();
		$result = array('result' => false);
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategory = new BrowseCategory();
		if ($textId == null){
			$textId = $_REQUEST['textId'];
		}
		$browseCategory->textId = $textId;
		if ($browseCategory->find(true)){
			$result['result'] = true;
			$result['textId'] = $browseCategory->textId;
			$result['label'] = $browseCategory->label;
			$result['description'] = $browseCategory->description;
			$defaultFilterInfo = $browseCategory->defaultFilter;
			$defaultFilters = preg_split('/[\r\n,;]+/', $defaultFilterInfo);
			foreach ($defaultFilters as $filter){
				$this->searchObject->addFilter($filter);
			}
			//Get titles for the list
			$this->searchObject->setSort($browseCategory->defaultSort);
			$this->searchObject->clearFacets();
			$this->searchObject->disableLogging();
			$this->searchObject->processSearch();
			$records = $this->searchObject->getBrowseRecordHTML();
			if (count($records) == 0){
				$records[] = $interface->fetch('Browse/noResults.tpl');
			}

			$result['records'] = implode('',$records);
			$result['numRecords'] = count($records);
		}
		// Shutdown the search object
		$this->searchObject->close();
		return $result;
	}

	function getMoreBrowseResults($textId = null, $pageToLoad = null){
		global $interface;
		$this->searchObject = SearchObjectFactory::initSearchObject();
		$result = array('result' => false);
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategory = new BrowseCategory();
		if ($textId == null){
			$textId = $_REQUEST['textId'];
		}
		if ($pageToLoad == null){
			$pageToLoad = $_REQUEST['pageToLoad'];
		}
		$browseCategory->textId = $textId;
		if ($browseCategory->find(true)){
			$result['result'] = true;
			$result['textId'] = $browseCategory->textId;
			$result['label'] = $browseCategory->label;
			$result['description'] = $browseCategory->description;
			$defaultFilterInfo = $browseCategory->defaultFilter;
			$defaultFilters = preg_split('/[\r\n,;]+/', $defaultFilterInfo);
			foreach ($defaultFilters as $filter){
				$this->searchObject->addFilter($filter);
			}
			//Get titles for the list
			$this->searchObject->setSort($browseCategory->defaultSort);
			$this->searchObject->clearFacets();
			$this->searchObject->disableLogging();
			$this->searchObject->setPage($pageToLoad);
			$this->searchObject->processSearch();
			$records = $this->searchObject->getBrowseRecordHTML();
			if (count($records) == 0){
				$records[] = $interface->fetch('Browse/noResults.tpl');
			}

			$result['records'] = implode('',$records);

		}
		// Shutdown the search object
		$this->searchObject->close();
		return $result;
	}
}
?>