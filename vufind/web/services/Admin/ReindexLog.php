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

require_once 'Action.php';
require_once 'services/Admin/Admin.php';
require_once 'sys/Pager.php';

class ReindexLog extends Admin
{
	function launch()
	{
		global $interface;

		global $interface;

		$interface->setPageTitle('Reindex Log');
		
		$logEntries = array();
		$logEntry = new ReindexLogEntry();
		$logEntry->orderBy('startTime DESC');
		$logEntry->limit(0, 30);
		$logEntry->find();
		while ($logEntry->fetch()){
			$logEntries[] = clone($logEntry);
		}
		$interface->assign('logEntries', $logEntries);
		$interface->setTemplate('reindexLog.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('opacAdmin');
	}
}
