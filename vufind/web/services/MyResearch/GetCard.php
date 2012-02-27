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

require_once "Action.php";
require_once 'CatalogConnection.php';

class GetCard extends Action
{
	protected $catalog;
	
	function __construct()
	{
	}

	function launch($msg = null)
	{
		global $interface;
		global $configArray;
		
		if (isset($_REQUEST['submit'])){
			require_once 'Drivers/Anythink.php';
			$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);
			$driver = $this->catalog->driver;
			
			$registrationResult = $driver->selfRegister();
			$interface->assign('registrationResult', $registrationResult);
			$interface->setTemplate('getcardresult.tpl');
		}else{
			$selfRegCityStates = parse_ini_file('conf/selfRegCityState.ini', true);
			$interface->assign('selfRegCityStates', $selfRegCityStates);
			
			$selfRegLanguages = parse_ini_file('conf/selfRegLanguage.ini', true);
			$interface->assign('selfRegLanguages', $selfRegLanguages);
			
			$selfRegLocations = parse_ini_file('conf/selfRegLocation.ini', true);
			$interface->assign('selfRegLocations', $selfRegLocations);
			
			$selfRegPhoneType = parse_ini_file('conf/selfRegPhoneType.ini', true);
			$interface->assign('selfRegPhoneType', $selfRegPhoneType);
			
			$interface->setTemplate('getcard.tpl');
		}
		$interface->display('layout.tpl');
	}
}

?>
