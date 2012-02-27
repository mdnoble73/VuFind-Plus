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

require_once 'CatalogConnection.php';

require_once 'Action.php';

class Hold extends Action {
	var $catalog;

	function launch() {
		global $configArray;
		global $user;

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
			$this->placeHold();
		} elseif (method_exists($this->catalog->driver, 'getHoldLink')) {
			// Redirect user to Place Hold screen on ILS OPAC
			$link = $this->catalog->getHoldLink($_GET['id'],$patron['id'],$_GET['date']);
			if (!PEAR::isError($link)) {
				header('Location:' . $link);
			} else {
				PEAR::raiseError($link);
			}
		} else {
			PEAR::raiseError(new PEAR_Error('Cannot Process Place Hold - ILS Not Supported'));
		}
	}

	function placeHold()
	{
		global $interface;
		global $configArray;
		global $user;
		$logger = new Logger();

		//TODO: Clean this up so there is only ever one id.
		if (isset($_REQUEST['recordId'])) {
			$recordId = $_REQUEST['recordId'];
		} else {
			$recordId = $_REQUEST['id'];
		}
		$interface->assign('id', $recordId);

		//Get title information for the record.
		$holding = $this->catalog->getHolding($recordId);
		if (PEAR::isError($holding)) {
			PEAR::raiseError($holding);
		}
		$interface->assign('holding', $holding);

		if (isset($_REQUEST['autologout'])){
			$_SESSION['autologout'] = true;
		}

		$showMessage = false;
		$type = isset($_REQUEST['holdType']) ? $_REQUEST['holdType'] : '';
		if (isset($_POST['submit']) || $type == 'recall' || $type == 'update' || $type == 'hold') {
			if (isset($_REQUEST['username']) && isset($_REQUEST['password'])){
				//Log the user in
				$user = UserAccount::login();
			}

			if ($user){
				//The user is already logged in
				$barcodeProperty = $configArray['Catalog']['barcodeProperty'];
				$return = $this->catalog->placeHold($recordId, $user->$barcodeProperty, '', $type);
				$interface->assign('result', $return['result']);
				$message = $return['message'];
				$interface->assign('message', $message);
				$showMessage = true;
			} else {
				$message = 'Incorrect Patron Information';
				$interface->assign('message', $message);
				$interface->assign('focusElementId', 'username');
				$showMessage = true;
			}
		} else{
			//Get the referrer so we can go back there.
			if (isset($_SERVER['HTTP_REFERER'])){
				$referer = $_SERVER['HTTP_REFERER'];
				$_SESSION['hold_referrer'] = $referer;
			}

			//Showing place hold form.
			if ($user){
				$profile = $this->catalog->getMyProfile($user);
				$interface->assign('profile', $profile);

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
			
			global $librarySingleton;
			$patronHomeBranch = $librarySingleton->getPatronHomeLibrary();
			if ($patronHomeBranch != null){
				$interface->assign('showHoldCancelDate', $patronHomeBranch->showHoldCancelDate);
			}else{
				//Show the hold cancellation date for now.  It may be hidden later when the user logs in.
				$interface->assign('showHoldCancelDate', 1);
			}
			$activeLibrary = $librarySingleton->getActiveLibrary();
			if ($activeLibrary != null){
				$interface->assign('holdDisclaimer', $activeLibrary->holdDisclaimer);
			}else{
				//Show the hold cancellation date for now.  It may be hidden later when the user logs in.
				$interface->assign('holdDisclaimer', '');
			}
		}

		$class = $configArray['Index']['engine'];
		$db = new $class($configArray['Index']['url']);
		$record = $db->getRecord($_GET['id']);
		if ($record) {
			$interface->assign('record', $record);
		} else {
			PEAR::raiseError(new PEAR_Error('Cannot find record'));
		}

		$interface->assign('id', $_GET['id']);
		if ($showMessage && isset($return)) {
			$hold_message_data = array(
              'successful' => $return['result'] ? 'all' : 'none',
              'error' => $return['error'],
              'titles' => array(
			$return,
			),
              'campus' => $_REQUEST['campus'],
			);
			//Check to see if there are item level holds that need follow-up by the user
			if (isset($return['items']) && count($return['items']) > 0){
				$hold_message_data['showItemForm'] = true;
				$hold_message_data['items'] = $return['items'];
			}

			$_SESSION['hold_message'] = $hold_message_data;
			if (isset($_SESSION['hold_referrer'])){
				$logger->log('Hold Referrer is set, redirecting to there.  type = ' . $_REQUEST['type'], PEAR_LOG_INFO);

				if ($_REQUEST['type'] != 'recall' && $_REQUEST['type'] != 'cancel' && $_REQUEST['type'] != 'update'){
					header("Location: " . $_SESSION['hold_referrer']);
				} else{
					//Redirect for hold cancellation or update
					header("Location: " . $configArray['Site']['url'] . '/MyResearch/Holds');
				}
				if (!isset($hold_message_data['showItemForm']) || $hold_message_data['showItemForm'] == false){
					unset($_SESSION['hold_referrer']);
					if (isset($_SESSION['autologout'])){
						unset($_SESSION['autologout']);
						UserAccount::softLogout();
					}
				}
			}else{
				$logger->log('No referrer set, but there is a message to show, go to the main holds page', PEAR_LOG_INFO);
				header("Location: " . $configArray['Site']['url'] . '/MyResearch/Holds');
				die();
			}
		} else {
			$logger->log('placeHold finished, do not need to show a message', PEAR_LOG_INFO);
			$interface->setPageTitle('Request an Item');
			$interface->assign('subTemplate', 'hold.tpl');
			$interface->setTemplate('hold.tpl');
			$interface->display('layout.tpl', 'RecordHold' . $_GET['id']);
		}
	}
}