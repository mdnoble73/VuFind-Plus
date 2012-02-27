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

class OverdriveWishList extends MyResearch {
	function launch(){
		global $configArray;
		global $interface;
		global $user;
		global $timer;

		$overDriveDriver = new OverDriveDriver();
		$overDriveWishList = $overDriveDriver->getOverDriveWishList($user);
		//Load the full record for each item in the wishlist
		foreach ($overDriveWishList['items'] as $key => $item){
			if ($item['recordId'] != -1){
				$econtentRecord = new EContentRecord();
				$econtentRecord->id = $item['recordId'];
				$econtentRecord->find(true);
				$item['record'] = clone($econtentRecord);
			} else{
				$item['record'] = null;
			}
			$item['numRows'] = count($item['formats']) + 1;
			$overDriveWishList['items'][$key] = $item;
		}
		$interface->assign('overDriveWishList', $overDriveWishList['items']);
		if (isset($overDriveWishList['error'])){
			$interface->assign('error', $overDriveWishList['error']);
		}
	
		$interface->setTemplate('overDriveWishList.tpl');
		$interface->setPageTitle('OverDrive Wish List');
		$interface->display('layout.tpl');
	}

}