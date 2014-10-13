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

	/** @var SearchObject_Solr|SearchObject_Base $searchObject*/
	private $searchObject;

	function launch()
	{
		header ('Content-type: application/json');
		$method = $_REQUEST['method'];
		$response = $this->$method();

		echo json_encode($response);
	}

	function getAddBrowseCategoryForm(){
		global $interface;
		// Display Page
		$interface->assign('searchId', strip_tags($_REQUEST['searchId']));
		$results = array(
			'title' => 'Add to Home Page',
			'modalBody' => $interface->fetch('Browse/addBrowseCategoryForm.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='return VuFind.Browse.createBrowseCategory();'>Create Category</span>"
		);
		return $results;
	}

	function createBrowseCategory(){
		global $library;
		global $locationSingleton;
		global $user;
		$searchLocation = $locationSingleton->getSearchLocation();
		$categoryName = isset($_REQUEST['categoryName']) ? $_REQUEST['categoryName'] : '';

		//Get the text id for the category
		$textId = str_replace(' ', '_', strtolower(trim($categoryName)));
		if (strlen($textId) == 0){
			return array(
				'result' => false,
				'message' => 'Please enter a category name'
			);
		}
		if ($searchLocation){
			$textId = $searchLocation->code . '_' . $textId;
		}elseif ($library){
			$textId = $library->subdomain . '_' . $textId;
		}

		//Check to see if we have an existing browse category
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategory = new BrowseCategory();
		$browseCategory->textId = $textId;
		if ($browseCategory->find(true)){
			return array(
				'result' => false,
				'message' => "Sorry the title of the category was not unique.  Please enter a new name."
			);
		}else{
			$searchId = $_REQUEST['searchId'];

			/** @var SearchObject_Solr|SearchObject_Base $searchObj */
			$searchObj = SearchObjectFactory::initSearchObject();
			$searchObj->init();
			$searchObj = $searchObj->restoreSavedSearch($searchId, false, true);

			if (!$browseCategory->updateFromSearch($searchObj)){
				return array(
					'result' => false,
					'message' => "Sorry, this search is too complex to create a category from."
				);
			}

			$browseCategory->label = $categoryName;
			$browseCategory->userId = $user->id;
			$browseCategory->sharing = 'everyone';
			$browseCategory->catalogScoping = 'unscoped';
			$browseCategory->description = '';

			//setup and add the category
			if (!$browseCategory->insert()){
				return array(
					'result' => false,
					'message' => "There was an unknown error saving the category.  Please contact Marmot."
				);
			}

			//Now add to the library/location
			/*if ($searchLocation){
				require_once ROOT_DIR . '/sys/Browse/LocationBrowseCategory.php';
				$locationBrowseCategory = new LocationBrowseCategory();
				$locationBrowseCategory->locationId = $searchLocation->locationId;
				$locationBrowseCategory->browseCategoryTextId = $textId;
				$locationBrowseCategory->insert();
			}else*/
			if ($library){
				require_once ROOT_DIR . '/sys/Browse/LibraryBrowseCategory.php';
				$libraryBrowseCategory = new LibraryBrowseCategory();
				$libraryBrowseCategory->libraryId = $library->libraryId;
				$libraryBrowseCategory->browseCategoryTextId = $textId;
				$libraryBrowseCategory->insert();
			}

			return array(
				'result' => true
			);
		}
	}


	function getBrowseCategoryInfo($textId = null){
		global $interface;
		$this->searchObject = SearchObjectFactory::initSearchObject();
		$result = array('result' => false);
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategory = new BrowseCategory();
		if ($textId == null){
			$textId = isset($_REQUEST['textId']) ? $_REQUEST['textId'] : null;
		}
		if ($textId == null){
			return $result;
		}
		$browseCategory->textId = $textId;
		if ($browseCategory->find(true)){
			$interface->assign('browseCategoryId', $textId);
			$result['result'] = true;
			$result['textId'] = $browseCategory->textId;
			$result['label'] = $browseCategory->label;
			$result['description'] = $browseCategory->description;

			if ($browseCategory->sourceListId != null && $browseCategory->sourceListId > 0){
				require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
				$sourceList = new UserList();
				$sourceList->id = $browseCategory->sourceListId;
				if ($sourceList->find(true)){
					$records = $sourceList->getBrowseRecords(0, 24);
				}else{
					$records = array();
				}
				$result['searchUrl'] = '/MyAccount/MyList/' . $browseCategory->sourceListId;
			}else{
				$defaultFilterInfo = $browseCategory->defaultFilter;
				$defaultFilters = preg_split('/[\r\n,;]+/', $defaultFilterInfo);
				foreach ($defaultFilters as $filter){
					$this->searchObject->addFilter(trim($filter));
				}
				//Set Sorting, this is actually slightly mangled from the category to Solr
				$this->searchObject->setSort($browseCategory->getSolrSort());
				if ($browseCategory->searchTerm != ''){
					$this->searchObject->setSearchTerm($browseCategory->searchTerm);
				}

				$this->searchObject->clearFacets();
				$this->searchObject->disableSpelling();
				$this->searchObject->disableLogging();
				$this->searchObject->setLimit(24);
				//Get titles for the list
				$this->searchObject->processSearch();

				$records = $this->searchObject->getBrowseRecordHTML();

				$result['searchUrl'] = $this->searchObject->renderSearchUrl();
			}
			if (count($records) == 0){
				$records[] = $interface->fetch('Browse/noResults.tpl');
			}

			$result['records'] = implode('',$records);
			$result['numRecords'] = count($records);

			$browseCategory->numTimesShown += 1;
			$browseCategory->update();
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
			$interface->assign('browseCategoryId', $textId);
			$result['result'] = true;
			$result['textId'] = $browseCategory->textId;
			$result['label'] = $browseCategory->label;
			$result['description'] = $browseCategory->description;
			if ($browseCategory->sourceListId != null && $browseCategory->sourceListId > 0){
				require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
				$sourceList = new UserList();
				$sourceList->id = $browseCategory->sourceListId;
				if ($sourceList->find(true)){
					$records = $sourceList->getBrowseRecords(($pageToLoad -1) * 24, 24);
				}else{
					$records = array();
				}
			}else{
				$defaultFilterInfo = $browseCategory->defaultFilter;
				$defaultFilters = preg_split('/[\r\n,;]+/', $defaultFilterInfo);
				foreach ($defaultFilters as $filter){
					$this->searchObject->addFilter(trim($filter));
				}
				//Set Sorting, this is actually slightly mangled from the category to Solr
				$this->searchObject->setSort($browseCategory->getSolrSort());
				if ($browseCategory->searchTerm != ''){
					$this->searchObject->setSearchTerm($browseCategory->searchTerm);
				}

				//Get titles for the list
				$this->searchObject->clearFacets();
				$this->searchObject->disableSpelling();
				$this->searchObject->disableLogging();
				$this->searchObject->setLimit(24);
				$this->searchObject->setPage($pageToLoad);
				$this->searchObject->processSearch();
				$records = $this->searchObject->getBrowseRecordHTML();
			}
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