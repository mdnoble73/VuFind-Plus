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

class OverdriveHolds extends MyResearch {
	function launch(){
		global $configArray;
		global $interface;
		global $user;
		global $timer;

		$overDriveDriver = new OverDriveDriver();
		$overDriveHolds = $overDriveDriver->getOverDriveHolds($user);
		//Load the full record for each item in the wishlist
		foreach ($overDriveHolds['holds'] as $sectionKey => $sectionData){
			foreach ($sectionData as $key => $item){
				if ($item['recordId'] != -1){
					$econtentRecord = new EContentRecord();
					$econtentRecord->id = $item['recordId'];
					$econtentRecord->find(true);
					$item['record'] = clone($econtentRecord);
				} else{
					$item['record'] = null;
				}
				if ($sectionKey == 'available'){
					$item['numRows'] = count($item['formats']) + 1;
				}
				$overDriveHolds['holds'][$sectionKey][$key] = $item;
			}
		}
		$interface->assign('overDriveHolds', $overDriveHolds['holds']);
	
		$interface->assign('ButtonBack',true);
		$interface->assign('ButtonHome',true);
		$interface->assign('MobileTitle','OverDrive Holds');
		
		$interface->setTemplate('overDriveHolds.tpl');
		$interface->setPageTitle('OverDrive Holds');
		$interface->display('layout.tpl');
	}

}