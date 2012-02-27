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
require_once 'sys/eContent/EContentRecord.php';
require_once 'sys/Pager.php';
require_once("PHPExcel.php");

class EContentTrialRecords extends Admin
{
	function launch()
	{
		global $interface;

		$interface->setPageTitle('eContent Trial Records');

		//Load the list of eContent Reocrds that have more than 4 times as many holds as items
		$eContentRecord = new EContentRecord();
		if (isset($_REQUEST['sourceFilter'])){
			$sourcesToShow = $_REQUEST['sourceFilter'];
			foreach ($sourcesToShow as $key=>$item){
				$sourcesToShow[$key] = "'" . mysql_escape_string(strip_tags($item)) . "'";
			}
			$sourceRestriction = " WHERE source IN (" . join(",", $sourcesToShow) . ") ";
		}
		$eContentRecord->query("SELECT econtent_record.id, title, author, source, count(DISTINCT econtent_checkout.userId) as numCheckouts FROM econtent_record LEFT JOIN econtent_checkout on econtent_record.id = econtent_checkout.recordId WHERE trialTitle = 1 GROUP BY econtent_record.id");
		$trialRecordsToPurchase = array();
		while ($eContentRecord->fetch()){
			if ($eContentRecord->numCheckouts > 3){
				$trialRecordsToPurchase[] = clone($eContentRecord);
			}
		}
		$interface->assign('trialRecordsToPurchase', $trialRecordsToPurchase);

		$interface->setTemplate('econtentTrialRecords.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}
