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
 * @author Juan Gimenez <jgimenez@dclibraries.org>
 *
 */

require_once 'Action.php';
require_once 'services/API/ListAPI.php';

class ODPlaceHold extends Action {

	function launch()
	{
		global $interface;
		global $configArray;
		global $library;
		global $locationSingleton;
		global $timer;
		global $user;

		if ($user)
		{
			if ( 
					(isset($_GET['overDriveId']) && isset($_GET['formatId'])) 
					||
					(isset($_POST['overDriveId']) && isset($_POST['formatId']))
				)
			{
				require_once('Drivers/OverDriveDriver.php');
			
				
				$catalog = new CatalogConnection($configArray['Catalog']['driver']);
				$patron = $catalog->patronLogin($user->cat_username, $user->cat_password);
				$profile = $catalog->getMyProfile($patron);
				if (!PEAR::isError($profile))
				{
					$interface->assign('profile', $profile);
				}
				
				$overDriveId = (isset($_GET['overDriveId']) ? $_GET['overDriveId'] : $_POST['overDriveId']);
				$formatId = (isset($_GET['formatId']) ? $_GET['formatId'] : $_POST['formatId']);
				
				$driver = new OverDriveDriver();
				$holdMessage = $driver->placeOverDriveHold($overDriveId, $formatId, $user);
				
				$interface->assign('message',$holdMessage['message']);
				
				$interface->assign('MobileTitle','OverDrive Place Hold');
				$interface->assign('ButtonBack',false);
				$interface->assign('ButtonHome',true);
				$interface->setTemplate('od-placeHold.tpl');
			}
		}
		else
		{
			if (isset($_GET['overDriveId']) && isset($_GET['formatId']) )
			{
				$interface->assign('overDriveId',$_GET['overDriveId']);
				$interface->assign('formatId',$_GET['formatId']);
				$interface->setTemplate('login.tpl');
			}
			else
			{
				header('Location: /');
			}
		}
		$interface->display('layout.tpl', $cacheId);
	}
}