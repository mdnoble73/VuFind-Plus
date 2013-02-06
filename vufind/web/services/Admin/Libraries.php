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

require_once 'Action.php';
require_once 'services/Admin/ObjectEditor.php';
require_once 'XML/Unserializer.php';

class Libraries extends ObjectEditor
{

	function getObjectType(){
		return 'Library';
	}
	function getToolName(){
		return 'Libraries';
	}
	function getPageTitle(){
		return 'Library Systems';
	}
	function getAllObjects(){
		$libraryList = array();

		global $user;
		if ($user->hasRole('opacAdmin')){
			$library = new Library();
			$library->orderBy('subdomain');
			$library->find();
			while ($library->fetch()){
				$libraryList[$library->libraryId] = clone $library;
			}
		}else if ($user->hasRole('libraryAdmin')){
			$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$libraryList[$patronLibrary->libraryId] = clone $patronLibrary;
		}

		return $libraryList;
	}
	function getObjectStructure(){
		return Library::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'subdomain';
	}
	function getIdKeyColumn(){
		return 'libraryId';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin');
	}
	function showExportAndCompare(){
		global $user;
		return $user->hasRole('opacAdmin');
	}
	function canAddNew(){
		global $user;
		return $user->hasRole('opacAdmin');
	}
	function canDelete(){
		global $user;
		return $user->hasRole('opacAdmin');
	}
	function getAdditionalObjectActions($existingObject){
		$objectActions = array();
		if ($existingObject != null){
			$objectActions[] = array(
				'text' => 'Edit Facets',
				'url' => '/Admin/LibraryFacetSettings?libraryId=' . $existingObject->libraryId,
			);
			$objectActions[] = array(
				'text' => 'Reset Facets To Default',
				'url' => '/Admin/Libraries?id=' . $existingObject->libraryId . '&amp;objectAction=resetFacetsToDefault',
			);
		}else{
			echo("Existing object is null");
		}
		return $objectActions;
	}

	function resetFacetsToDefault(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearFacets();

			$defaultFacets = array();

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Format Category";
			$facet->facetName = 'format_category';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = true;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Available Now At";
			$facet->facetName = 'available_at';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Location";
			$facet->facetName = 'detailed_location';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Rating";
			$facet->facetName = 'rating_facet';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Publication Year";
			$facet->facetName = 'publishDate';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Format";
			$facet->facetName = 'format';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Compatible Device";
			$facet->facetName = 'econtent_device';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "eContent Collection";
			$facet->facetName = 'econtent_source';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = false;
			$facet->showInAuthorResults = false;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "eContent Protection";
			$facet->facetName = 'econtent_protection_type';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = false;
			$facet->showInAuthorResults = false;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Topics";
			$facet->facetName = 'topic_facet';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Audience";
			$facet->facetName = 'target_audience';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = false;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Movie Rating";
			$facet->facetName = 'mpaa_rating';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Form";
			$facet->facetName = 'literary_form';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = false;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Author";
			$facet->facetName = 'authorStr';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Language";
			$facet->facetName = 'language';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Genre";
			$facet->facetName = 'genre_facet';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Era";
			$facet->facetName = 'era';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Region";
			$facet->facetName = 'geographic_facet';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Reading Level";
			$facet->facetName = 'target_audience_full';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = false;
			$facet->showInAuthorResults = false;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Literary Form";
			$facet->facetName = 'literary_form_full';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = false;
			$facet->showInAuthorResults = false;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Lexile Code";
			$facet->facetName = 'lexile_code';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Lexile Score";
			$facet->facetName = 'lexile_score';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Item Type";
			$facet->facetName = 'itype';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$facet = new LibraryFacetSetting();
			$facet->libraryId = $libraryId;
			$facet->displayName = "Added In The Last";
			$facet->facetName = 'time_since_added';
			$facet->weight = count($defaultFacets) + 1;
			$facet->showAsDropDown = false;
			$facet->sortMode = 1;
			$facet->showAboveResults = false;
			$facet->showInResults = true;
			$facet->showInAuthorResults = true;
			$facet->showInAdvancedSearch = true;
			$defaultFacets[] = $facet;

			$library->facets = $defaultFacets;
			$library->update();

			$_REQUEST['objectAction'] = 'edit';
		}
		$structure = $this->getObjectStructure();
		$this->viewIndividualObject($structure);
	}
}