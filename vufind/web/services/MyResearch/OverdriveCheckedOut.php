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

require_once 'services/MyResearch/MyResearch.php';
require_once 'Drivers/OverDriveDriver.php';
require_once 'sys/eContent/EContentRecord.php';

class OverdriveCheckedOut extends MyResearch {
	function launch(){
		global $configArray;
		global $interface;
		global $user;
		global $timer;

		$overDriveDriver = new OverDriveDriver();
		$overDriveCheckedOutItems = $overDriveDriver->getOverDriveCheckedOutItems($user);
		//Load the full record for each item in the wishlist
		foreach ($overDriveCheckedOutItems['items'] as $key => $item){
			if ($item['recordId'] != -1){
				$econtentRecord = new EContentRecord();
				$econtentRecord->id = $item['recordId'];
				$econtentRecord->find(true);
				$item['record'] = clone($econtentRecord);
			} else{
				$item['record'] = null;
			}
			$overDriveCheckedOutItems['items'][$key] = $item;
		}
		$interface->assign('overDriveCheckedOutItems', $overDriveCheckedOutItems['items']);
	
		$interface->setTemplate('overDriveCheckedOut.tpl');
		$interface->setPageTitle('OverDrive Checked Out Items');
		$interface->display('layout.tpl');
	}

}