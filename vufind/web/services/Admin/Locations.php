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

class Locations extends ObjectEditor
{

	function getObjectType(){
		return 'Location';
	}
	function getToolName(){
		return 'Locations';
	}
	function getPageTitle(){
		return 'Locations (Branches)';
	}
	function getAllObjects(){
		//Look lookup information for display in the user interface
		global $user;

		$location = new Location();
		$location->orderBy('displayName');
		if (!$user->hasRole('opacAdmin')){
			//Scope to just locations for the user based on home library
			$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$location->libraryId = $patronLibrary->libraryId;
		}
		$location->find();
		$locationList = array();
		while ($location->fetch()){
			$locationList[$location->locationId] = clone $location;
		}
		return $locationList;
	}

	function getObjectStructure(){
		return Location::getObjectStructure();
	}

	function getPrimaryKeyColumn(){
		return 'code';
	}

	function getIdKeyColumn(){
		return 'locationId';
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
				'url' => '/Admin/LocationFacetSettings?locationId=' . $existingObject->locationId,
			);
			$objectActions[] = array(
				'text' => 'Reset Facets To Default',
				'url' => '/Admin/Locations?objectAction=resetFacetsToDefault&amp;id=' . $existingObject->locationId,
			);
			$objectActions[] = array(
				'text' => 'Copy Location Facets',
				'url' => '/Admin/Locations?id=' . $existingObject->locationId . '&amp;objectAction=copyFacetsFromLocation',
			);
		}else{
			echo("Existing object is null");
		}
		return $objectActions;
	}

	function copyFacetsFromLocation(){
		$locationId = $_REQUEST['id'];
		if (isset($_REQUEST['submit'])){
			$location = new Location();
			$location->locationId = $locationId;
			$location->find(true);
			$location->clearFacets();

			$locationToCopyFromId = $_REQUEST['locationToCopyFrom'];
			$locationToCopyFrom = new Location();
			$locationToCopyFrom->locationId = $locationToCopyFromId;
			$location->find(true);

			$facetsToCopy = $locationToCopyFrom->facets;
			foreach ($facetsToCopy as $facetKey => $facet){
				$facet->locationId = $locationId;
				$facetsToCopy[$facetKey] = $facet;
			}
			$location->facets = $facetsToCopy;
			$location->update();
			header("Location: /Admin/Locations?objectAction=edit&id=" . $locationId);
		}else{
			//Prompt user for the location to copy from
			$allLocations = $this->getAllObjects();

			unset($allLocations[$locationId]);
			foreach ($allLocations as $key => $location){
				if (count($location->facets) == 0){
					unset($allLocations[$key]);
				}
			}
			global $interface;
			$interface->assign('allLocations', $allLocations);
			$interface->assign('id', $locationId);
			$interface->setTemplate('../Admin/copyLocationFacets.tpl');
		}
	}

	function resetFacetsToDefault(){
		$location = new Location();
		$locationId = $_REQUEST['id'];
		$location->locationId = $locationId;
		if ($location->find(true)){
			$location->clearFacets();

			$defaultFacets = array();

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$facet = new LocationFacetSetting();
			$facet->locationId = $locationId;
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

			$location->facets = $defaultFacets;
			$location->update();

			$_REQUEST['objectAction'] = 'edit';
		}
		$structure = $this->getObjectStructure();
		$this->viewIndividualObject($structure);
	}
}