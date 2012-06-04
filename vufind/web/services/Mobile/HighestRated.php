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

class HighestRated extends Action {

	function launch()
	{
		global $interface;
		global $configArray;
		global $library;
		global $locationSingleton;
		global $timer;
		global $user;
		
		
		$listAPI = new ListAPI();
		$listTitlesHR = $listAPI->getListTitles('EContentStrands:home_3');//Check success key
		$interface->assign('LIST',($listTitlesHR['success'] ? $listTitlesHR['titles'] : ""));
		$interface->caching = 0;
		$cacheId = 'homepage|' . $interface->lang;
		//Disable Home page caching for now.
		if (!$interface->is_cached('layout.tpl', $cacheId)) {
			
			$interface->assign('ButtonBack',true);
			$interface->assign('ButtonHome',true);
			$interface->setPageTitle('Highest Rated');
			$interface->assign('MobileTitle','Highest Rated');
			$interface->setTemplate('listecontents.tpl');
		}
		$interface->display('layout.tpl', $cacheId);
	}
}