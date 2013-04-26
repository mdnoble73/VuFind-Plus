<?php
/**
 *
 * Copyright (C) Villanova University 2010.
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
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/eContent/EContentAttachmentLogEntry.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once(ROOT_DIR . "/PHPExcel.php");

class CronLog extends Admin
{
	function launch()
	{
		global $interface;

		global $interface;

		$interface->setPageTitle('Cron Log');
		
		$logEntries = array();
		$cronLogEntry = new CronLogEntry();
		$cronLogEntry->orderBy('startTime DESC');
		$cronLogEntry->limit(0, 30);
		$cronLogEntry->find();
		while ($cronLogEntry->fetch()){
			$logEntries[] = clone($cronLogEntry);
		}
		$interface->assign('logEntries', $logEntries);
		$interface->setTemplate('cronLog.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('opacAdmin');
	}
}
