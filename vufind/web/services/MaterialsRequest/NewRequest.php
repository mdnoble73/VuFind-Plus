<?php
/**
 *
 * Copyright (C) Anythink Libraries 2012.
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
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Anythink Libraries 2012.
 *
 */

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . "/sys/MaterialsRequest.php";

/**
 * MaterialsRequest Home Page, displays an existing Materials Request.
 */
class MaterialsRequest_NewRequest extends Action
{

	function launch()
	{
		global /** @var Location $locationSingleton */
		$configArray,
		       $interface,
		       $user,
		       $library,
		       $locationSingleton;
		
		if ($user){
			$interface->assign('defaultPhone', $user->phone);
			if ($user->email != 'notice@salidalibrary.org'){
				$interface->assign('defaultEmail', $user->email);
			}
			// TODO: Only show locations for the library the request is going to.

			// Hold Pick-up Locations
			$locations = $locationSingleton->getPickupBranches($user, $user->homeLocationId);

		}else{
			$locations = $locationSingleton->getPickupBranches(false, -1);
		}
		$pickupLocations = array();
		foreach ($locations as $curLocation) {
			$pickupLocations[] = array(
				'id' => $curLocation->locationId,
				'displayName' => $curLocation->displayName,
				'selected' => $curLocation->selected,
			);
		}
		$interface->assign('pickupLocations', $pickupLocations);

		
		//Get a list of formats to show 
		$availableFormats = MaterialsRequest::getFormats();
		$interface->assign('availableFormats', $availableFormats);
		
		//Setup a default title based on the search term
		$interface->assign('new', true);
		if (isset($_REQUEST['lookfor']) && strlen ($_REQUEST['lookfor']) > 0){ 
			$request = new MaterialsRequest();
			$searchType = isset($_REQUEST['basicType']) ? $_REQUEST['basicType'] : (isset($_REQUEST['type']) ? $_REQUEST['type'] : 'Keyword');
			if (strcasecmp($searchType, 'author') == 0){
				$request->author = $_REQUEST['lookfor'];
			}else{
				$request->title = $_REQUEST['lookfor'];
			}
			$interface->assign('materialsRequest', $request);
		}

		$interface->assign('showPhoneField',        $configArray['MaterialsRequest']['showPhoneField']);
		$interface->assign('showAgeField',          $configArray['MaterialsRequest']['showAgeField']);
		$interface->assign('showBookTypeField',     $configArray['MaterialsRequest']['showBookTypeField']);
		$interface->assign('showEbookFormatField',  $configArray['MaterialsRequest']['showEbookFormatField']);
		$interface->assign('showEaudioFormatField', $configArray['MaterialsRequest']['showEaudioFormatField']);
		$interface->assign('showPlaceHoldField',    $configArray['MaterialsRequest']['showPlaceHoldField']);
		$interface->assign('showIllField',          $configArray['MaterialsRequest']['showIllField']);
		$interface->assign('requireAboutField',     $configArray['MaterialsRequest']['requireAboutField']);
		
		$useWorldCat = false;
		if (isset($configArray['WorldCat']) && isset($configArray['WorldCat']['apiKey'])){
			$useWorldCat = strlen($configArray['WorldCat']['apiKey']) > 0;
		}
		$interface->assign('useWorldCat', $useWorldCat);

		if (isset($library)){
			// Get the Fields to Display for the form
			require_once ROOT_DIR . '/sys/MaterialsRequestFormFields.php';
			$formFields            = new MaterialsRequestFormFields();
			$formFields->libraryId = $library->libraryId;
			$usingDefaultFormFields = $formFields->count() == 0;
			if ($usingDefaultFormFields) {
				$fieldsToSortByCategory = $formFields::getDefaultFormFields($library->libraryId);
			} else {
				$formFields->orderBy('weight');
				/** @var MaterialsRequestFormFields[] $fieldsToSortByCategory */
				$fieldsToSortByCategory = $formFields->fetchAll();
			}

			// If we use another interface variable that is sorted by category, this should be a method in the Interface class
			$requestFormFields = array();
			if ($fieldsToSortByCategory) {
				foreach ($fieldsToSortByCategory as $formField) {
					if (!array_key_exists($formField->formCategory, $requestFormFields)) {
						$requestFormFields[$formField->formCategory] = array();
					}
					$requestFormFields[$formField->formCategory][] = $formField;
				}
			} else {
				//TODO: Check for sql error & log as an error
			}
			$interface->assign('requestFormFields', $requestFormFields);


			// Get Author Labels for all Formats
			$formatsUsingSpecialFields = new MaterialsRequestFormats();
			$formatsUsingSpecialFields->libraryId = $library->libraryId;
			$formatAuthorLabels = $specialFieldFormats = array();
			$usingDefaultFormats = $formatsUsingSpecialFields->count() == 0;
			if ($usingDefaultFormats) {
				/** @var MaterialsRequestFormats $formatObj */
				foreach (MaterialsRequestFormats::getDefaultMaterialRequestFormats() as $formatObj) {
					$formatAuthorLabels[$formatObj->format] = $formatObj->authorLabel;
					if (!empty($formatObj->specialFields)) {
						$specialFieldFormats[$formatObj->format] = $formatObj->specialFields;
					}
				}
			} else {
				$formatAuthorLabels = $formatsUsingSpecialFields->fetchAll('format', 'authorLabel');

				// Get Formats that use Special Fields
				$formatsUsingSpecialFields = new MaterialsRequestFormats();
				$formatsUsingSpecialFields->libraryId = $library->libraryId;
				$formatsUsingSpecialFields->whereAdd('`specialFields` IS NOT NULL');
				$specialFieldFormats = $formatsUsingSpecialFields->fetchAll('format', 'specialFields');

			}
			$interface->assign('formatAuthorLabelsJSON', json_encode($formatAuthorLabels));
			$interface->assign('specialFieldFormatsJSON', json_encode($specialFieldFormats));
		}

		// Set up for User Log in
		if (isset($library)){
			$interface->assign('newMaterialsRequestSummary', $library->newMaterialsRequestSummary);

			$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
			$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
			$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');
		}else{
			$interface->assign('enableSelfRegistration', 0);
			$interface->assign('usernameLabel', 'Your Name');
			$interface->assign('passwordLabel', 'Library Card Number');
		}

		$this->display('new.tpl', 'Materials Request');
	}
}