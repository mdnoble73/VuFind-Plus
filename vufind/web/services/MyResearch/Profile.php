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

require_once ROOT_DIR . '/services/MyResearch/MyResearch.php';

class Profile extends MyResearch
{
	function launch()
	{
		global $configArray;
		global $interface;
		global $user;

		global $librarySingleton;
		$activeLibrary = $librarySingleton->getActiveLibrary();
		if ($activeLibrary == null || $activeLibrary->allowProfileUpdates){
			$canUpdateContactInfo = true;
		}else{
			$canUpdateContactInfo = false;
		}
		$interface->assign('canUpdateContactInfo', $canUpdateContactInfo);

		if (isset($_POST['update'])) {
			$result = $this->catalog->updatePatronInfo($user->cat_password, $canUpdateContactInfo);
			$_SESSION['profileUpdateErrors'] = $result;
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$overDriveDriver = OverDriveDriverFactory::getDriver();
			$result = $overDriveDriver->updateLendingOptions();

			header("Location: " . $configArray['Site']['path'] . '/MyResearch/Profile');
			exit();
		}elseif (isset($_POST['updatePin'])) {
			$result = $this->catalog->updatePin();
			$_SESSION['profileUpdateErrors'] = $result;

			header("Location: " . $configArray['Site']['path'] . '/MyResearch/Profile');
			exit();
		}else if (isset($_POST['edit'])){
			$interface->assign('edit', true);
		}else{
			$interface->assign('edit', false);
		}

		require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
		$overDriveDriver = OverDriveDriverFactory::getDriver();
		if ($overDriveDriver->version >= 2){
			$overDriveSummary = $overDriveDriver->getAccountDetails($user);
			$interface->assign('overDriveLendingOptions', $overDriveSummary['lendingOptions']);
		}


		if (isset($_SESSION['profileUpdateErrors'])){
			$interface->assign('profileUpdateErrors', $_SESSION['profileUpdateErrors']);
			unset($_SESSION['profileUpdateErrors']);
		}

		//Get the list of locations for display in the user interface.
		global $locationSingleton;
		$locationSingleton->whereAdd("validHoldPickupBranch = 1");
		$locationSingleton->find();

		$locationList = array();
		while ($locationSingleton->fetch()) {
			$locationList[$locationSingleton->locationId] = $locationSingleton->displayName;
		}
		$interface->assign('locationList', $locationList);

		if ($this->catalog->checkFunction('isUserStaff')){
			$userIsStaff = $this->catalog->isUserStaff();
			$interface->assign('userIsStaff', $userIsStaff);
		}else{
			$interface->assign('userIsStaff', false);
		}

		$interface->setTemplate('profile.tpl');
		$interface->setPageTitle(translate('My Profile'));
		$interface->display('layout.tpl');
	}

}