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
require_once 'sys/eContent/EContentMarcImport.php';
require_once 'sys/Pager.php';
require_once("PHPExcel.php");

class MarcImportLog extends Admin
{
	function launch()
	{
		global $interface;

		global $interface;

		$interface->setPageTitle('eContent Marc File Import History');
		
		$logEntries = array();
		$eContentMarcImport = new EContentMarcImport();
		$eContentMarcImport->orderBy('dateStarted DESC');
		$eContentMarcImport->find();
		while ($eContentMarcImport->fetch()){
			$logEntries[] = clone($eContentMarcImport);
		}
		$interface->assign('logEntries', $logEntries);
		$interface->setTemplate('eContentMarcLog.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}
