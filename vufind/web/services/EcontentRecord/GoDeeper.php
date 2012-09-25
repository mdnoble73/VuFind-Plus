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

require_once 'Action.php';
require_once 'Drivers/marmot_inc/GoDeeperData.php';
require_once 'sys/eContent/EContentRecord.php';
require_once 'Drivers/EContentDriver.php';

class GoDeeper extends Action
{
	function launch()
	{
		global $interface;
		$id = $_REQUEST['id'];
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $id;
		$eContentRecord->find(true);

		$goDeeperOptions = GoDeeperData::getGoDeeperOptions($eContentRecord->getIsbn(), $eContentRecord->upc, true);
		$interface->assign('options', $goDeeperOptions['options']);
		if (isset($goDeeperOptions['defaultOption'])){
			$defaultData = GoDeeperData::getHtmlData($goDeeperOptions['defaultOption'], 'eContentRecord', $eContentRecord->getIsbn(), $eContentRecord->upc);
			$interface->assign('defaultGoDeeperData', $defaultData);
		}

		$interface->assign('title', translate("Additional information about this title"));
		echo $interface->fetch('Record/goDeeper.tpl');
	}
}