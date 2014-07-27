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


require_once ROOT_DIR . '/Action.php';

class eContentHelp extends Action
{
	function launch()
	{
		global $interface;
		$interface->setPageTitle('eContent Help');

		$device = get_device_name();
		$defaultDevice = '';
		if ($device == 'Kindle'){
			$defaultDevice = 'kindle';
		}elseif ($device == 'Kindle Fire'){
			$defaultDevice = 'kindle_fire';
		}elseif ($device == 'iPad' || $device == 'iPhone'){
			$defaultDevice = 'ios';
		}elseif ($device == 'Android Phone' || $device == 'Android Tablet'){
			$defaultDevice = 'android';
		}elseif ($device == 'Android Phone' || $device == 'Android Tablet' || $device == 'Google TV'){
			$defaultDevice = 'android';
		}elseif ($device == 'BlackBerry'){
			$defaultDevice = 'other';
		}elseif ($device == 'Mac'){
			$defaultDevice = 'mac';
		}elseif ($device == 'PC'){
			$defaultDevice = 'pc';
		}
		$interface->assign('defaultDevice', $defaultDevice);

		if (isset($_REQUEST['lightbox'])){
			$result = array(
				'title' => 'Step by Step Instructions for using eContent',
				'modalBody' => $interface->fetch('Help/eContentHelp.tpl'),
				'modalButtons' => ''
			);
			echo json_encode($result);
		}else{
			$interface->setTemplate('eContentHelp.tpl');
			$interface->assign('sidebar', 'Search/home-sidebar.tpl');
			$interface->display('layout.tpl');
		}
	}
}

?>
