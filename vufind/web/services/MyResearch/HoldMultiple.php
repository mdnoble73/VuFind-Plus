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

require_once ROOT_DIR . '/CatalogConnection.php';

require_once ROOT_DIR . '/Action.php';

class MyResearch_HoldMultiple extends Action
{
	var $catalog;

	function launch()
	{
		global $configArray;

		try {
			$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);
		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}

		// Check How to Process Hold
		if (method_exists($this->catalog->driver, 'placeHold')) {
			$this->placeHolds();
		} else {
			PEAR_Singleton::raiseError(new PEAR_Error('Cannot Process Place Hold - ILS Not Supported'));
		}
	}

	function placeHolds(){
		global $interface;
		global $configArray;
		global $user;

		if (!isset($_REQUEST['selected'])){
			$hold_message_data = array(
				'successful' => 'none',
				'error' => 'No titles were selected',
				'titles' => array()
			);
			$showMessage = true;
		}else{
			$selectedIds = $_REQUEST['selected'];

			$eContentDriver = null;
			$showMessage = false;

			$holdings = array();
			//Check to see if all items are eContent
			$ids = array();
			$allItemsEContent = true;
			foreach ($selectedIds as $recordId => $onOff){
				$ids[] = $recordId;
				//Get the title for the item
				$resource = new Resource();
				if (strpos($recordId, 'econtentRecord') !== 0){
					$allItemsEContent = false;
					$resource->record_id = '.' . $recordId;
					$resource->source = 'VuFind';
					$resource->deleted = 0;
				}else{
					$shortId = str_replace('econtentRecord', '', $recordId);
					$resource->record_id = $shortId;
					$resource->source = 'eContent';
					$resource->deleted = 0;
				}
				if ($resource->find(true)){
					$holdings[] = $resource->title;
				}else{
					echo("Could not find resource for record id $recordId");
				}
			}
			$interface->assign('ids', $ids);
			$interface->assign('holdings', $holdings);

			$hold_message_data = array(
	          'successful' => 'all',
	          'titles' => array()
			);

			if (isset($_REQUEST['autologout'])){
				$_SESSION['autologout'] = true;
			}

			//Check to see if we are ready to place the hold.
			$placeHold = false;
			if (isset($_REQUEST['holdType']) && isset($_REQUEST['campus'])){
				$placeHold = true;
			}else if ($user && $allItemsEContent){
				$placeHold = true;
			}
			if ($placeHold) {
				$hold_message_data['campus'] = $_REQUEST['campus'];

				//This is a new login
				if (isset($_REQUEST['username']) && isset($_REQUEST['password'])){
					$user = UserAccount::login();
				}
				if ($user == false) {
					$hold_message_data['error'] = 'Incorrect Patron Information';
					$showMessage = true;
				}else{
					$atLeast1Successful = false;
					foreach ($selectedIds as $recordId => $onOff){
						if (strpos($recordId, 'econtentRecord', 0) === 0){
							if ($eContentDriver == null){
								require_once(ROOT_DIR . '/Drivers/EContentDriver.php');
								$eContentDriver = new EContentDriver();
							}

							$return = $eContentDriver->placeHold($recordId, $user);
						} else {
							$return = $this->catalog->placeHold($recordId, $user->password, '', $_REQUEST['holdType']);
						}
						$hold_message_data['titles'][] = $return;
						if (!$return['result']){
							$hold_message_data['successful'] = 'partial';
						}else{
							$atLeast1Successful = true;
						}
						//Check to see if there are item level holds that need follow-up by the user
						if (isset($return['items'])){
							$hold_message_data['showItemForm'] = true;
						}
						$showMessage = true;
					}
					if (!$atLeast1Successful){
						$hold_message_data['successful'] = 'none';
					}
				}
			} else {
				//Get the referrer so we can go back there.
				if (isset($_SERVER['HTTP_REFERER'])){
					$referer = $_SERVER['HTTP_REFERER'];
					$_SESSION['hold_referrer'] = $referer;
				}

				//Showing place hold form.
				if ($user){
					$profile = $this->catalog->getMyProfile($user);
					$interface->assign('profile', $profile);

					//Get information to show a warning if the user does not have sufficient holds
					require_once ROOT_DIR . '/Drivers/marmot_inc/PType.php';
					$maxHolds = -1;
					//Determine if we should show a warning
					$ptype = new PType();
					$ptype->pType = $user->patronType;
					if ($ptype->find(true)){
						$maxHolds = $ptype->maxHolds;
					}
					$currentHolds = $profile['numHolds'];
					if ($maxHolds != -1 && ($currentHolds + count($selectedIds) > $maxHolds)){
						$interface->assign('showOverHoldLimit', true);
						$interface->assign('maxHolds', $maxHolds);
						$interface->assign('currentHolds', $currentHolds);
					}

					global $locationSingleton;
					//Get the list of pickup branch locations for display in the user interface.
					$locations = $locationSingleton->getPickupBranches($profile, $profile['homeLocationId']);
					$interface->assign('pickupLocations', $locations);
					//set focus to the submit button if the user is logged in since the campus will be correct most of the time.
					$interface->assign('focusElementId', 'submit');
				}else{
					//set focus to the username field by default.
					$interface->assign('focusElementId', 'username');
				}

				global $library;
				$patronHomeBranch = Library::getPatronHomeLibrary();
				if ($patronHomeBranch != null){
					if ($patronHomeBranch->defaultNotNeededAfterDays > 0){
						$interface->assign('defaultNotNeededAfterDays', date('m/d/Y', time() + $patronHomeBranch->defaultNotNeededAfterDays * 60 * 60 * 24));
					}else{
						$interface->assign('defaultNotNeededAfterDays', '');
					}
					$interface->assign('showHoldCancelDate', $patronHomeBranch->showHoldCancelDate);
				}else{
					if ($library){
						//Show the hold cancellation date for now.  It may be hidden later when the user logs in.
						if ($library->defaultNotNeededAfterDays > 0){
							$interface->assign('defaultNotNeededAfterDays', date('m/d/Y', time() + $library->defaultNotNeededAfterDays * 60 * 60 * 24));
						}else{
							$interface->assign('defaultNotNeededAfterDays', '');
						}
						$interface->assign('showHoldCancelDate', $library->showHoldCancelDate);
					}else{
						//Show the hold cancellation date for now.  It may be hidden later when the user logs in.
						$interface->assign('showHoldCancelDate', 1);
						$interface->assign('defaultNotNeededAfterDays', '');
					}
				}
				$activeLibrary = Library::getActiveLibrary();
				if ($activeLibrary != null){
					$interface->assign('holdDisclaimer', $activeLibrary->holdDisclaimer);
				}else{
					//Show the hold cancellation date for now.  It may be hidden later when the user logs in.
					$interface->assign('holdDisclaimer', '');
				}
			}
		}

		if ($showMessage) {
			$hold_message_data['fromCart'] = isset($_REQUEST['fromCart']);
			$_SESSION['hold_message'] = $hold_message_data;
			if (isset($_SESSION['hold_referrer'])){
				if ($_REQUEST['type'] != 'recall' && $_REQUEST['type'] != 'cancel' && $_REQUEST['type'] != 'update'){
					header("Location: " . $_SESSION['hold_referrer']);
				} else{
					//Redirect for hold cancellation or update
					header("Location: " . $configArray['Site']['path'] . '/MyResearch/Holds');
				}
				if (!isset($hold_message_data['showItemForm']) || $hold_message_data['showItemForm'] == false){
					unset($_SESSION['hold_referrer']);
					if (isset($_SESSION['autologout'])){
						unset($_SESSION['autologout']);
						UserAccount::softLogout();
					}
				}
			}else{
				header("Location: " . $configArray['Site']['path'] . '/MyResearch/Holds');
			}
		} else {
			$interface->assign('fromCart', isset($_REQUEST['fromCart']));
			$interface->setPageTitle('Request Items');
			$interface->setTemplate('holdMultiple.tpl');
			$interface->display('layout.tpl', 'RecordHolds');
		}
	}
}