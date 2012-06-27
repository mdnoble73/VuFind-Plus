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
			$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);
			$driver = $this->catalog->driver;
			
			$registrationResult = $driver->selfRegister();
			$interface->assign('registrationResult', $registrationResult);
			$interface->setTemplate('getcardresult.tpl');
		}else{
			global $servername;
			if (file_exists("../../sites/{$servername}/conf/selfRegCityState.ini")){
				$selfRegCityStates = parse_ini_file("../../sites/{$servername}/conf/selfRegCityState.ini", true);
			}elseif (file_exists("../../sites/default/conf/selfRegCityState.ini")){
				$selfRegCityStates = parse_ini_file("../../sites/default/conf/selfRegCityState.ini", true);
			}else{
				$selfRegCityStates = null;
			}
			$interface->assign('selfRegCityStates', $selfRegCityStates);
			
			if (file_exists("../../sites/{$servername}/conf/selfRegLanguage.ini")){
				$selfRegLanguages = parse_ini_file("../../sites/{$servername}/conf/selfRegLanguage.ini", true);
			}elseif (file_exists("../../sites/default/conf/selfRegLanguage.ini")){
				$selfRegLanguages = parse_ini_file("../../sites/default/conf/selfRegLanguage.ini", true);
			}else{
				$selfRegLanguages = null;
			}
			$interface->assign('selfRegLanguages', $selfRegLanguages);
			
			if (file_exists("../../sites/{$servername}/conf/selfRegLocation.ini")){
				$selfRegLocations = parse_ini_file("../../sites/{$servername}/conf/selfRegLocation.ini", true);
			}elseif (file_exists("../../sites/default/conf/default/conf/selfRegLocation.ini")){
				$selfRegLocations = parse_ini_file("../../sites/default/conf/selfRegLocation.ini", true);
			}else{
				$selfRegLocations = null;
			}
			$interface->assign('selfRegLocations', $selfRegLocations);
			
			if (file_exists("../../sites/{$servername}/conf/selfRegPhoneType.ini")){
				$selfRegPhoneType = parse_ini_file("../../sites/{$servername}/conf/selfRegPhoneType.ini", true);
			}elseif (file_exists("../../sites/default/conf/selfRegPhoneType.ini")){
				$selfRegPhoneType = parse_ini_file("../../sites/default/conf/selfRegPhoneType.ini", true);
			}else{
				$selfRegPhoneType = null;
			}
			$interface->assign('selfRegPhoneType', $selfRegPhoneType);
			
			$interface->setTemplate('getcard.tpl');
		}
		$interface->display('layout.tpl');
	}
}

?>
