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

class ODLoanPeriod extends Action {

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
			$catalog = new CatalogConnection($configArray['Catalog']['driver']);
			$patron = $catalog->patronLogin($user->cat_username, $user->cat_password);
			$profile = $catalog->getMyProfile($patron);
			if (!PEAR::isError($profile))
			{
				$interface->assign('profile', $profile);
			}
		
			if (!isset($_REQUEST['overDriveId']) || !isset($_REQUEST['formatId']))
			{
				header('Location: /');
				exit();
			}
			else
			{
				$interface->assign('overDriveId',$_REQUEST['overDriveId']);
				$interface->assign('overDriveFormatId',$_REQUEST['formatId']);
				
				$interface->setPageTitle('OverDrive Loan Period');
				$interface->setTemplate('od-loan-period.tpl');
			}
			
			//Var for the IDCLREADER TEMPLATE
			$interface->assign('ButtonBack',false);
			$interface->assign('ButtonHome',true);
			$interface->assign('MobileTitle','OverDrive Loan Period');
			
			
		}
		else
		{
			$interface->setTemplate('odCOlogin.tpl');
		}
		$interface->display('layout.tpl');
	}
}