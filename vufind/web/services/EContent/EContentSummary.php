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
require_once 'sys/eContent/EContentItem.php';
require_once 'sys/eContent/EContentRecord.php';
require_once 'sys/eContent/EContentHistoryEntry.php';
require_once 'sys/Pager.php';
require_once("PHPExcel.php");

class EContentSummary extends Admin
{
	function launch()
	{
		global $interface;

		$interface->setTemplate('econtentSummary.tpl');
		$interface->setPageTitle('eContent Summary');

		$collectionSummary = $this->loadCollectionSummary();
		$interface->assign('collectionSummary', $collectionSummary);

		$interface->display('layout.tpl');
	}



	function loadCollectionSummary(){
		$collectionSummary = array();
		$epubFile = new EContentRecord();
		$query = "SELECT COUNT(DISTINCT id) as numTitles FROM `{$epubFile->__table}`";

		$epubFile->query($query);
		if ($epubFile->N > 0){
			$epubFile->fetch();
			$collectionSummary['numTitles'] = $epubFile->numTitles;
		}

		$statsByDRM = new EContentRecord();
		$query = "SELECT accessType, COUNT(DISTINCT id) as numTitles FROM `{$statsByDRM->__table}` GROUP BY accessType ORDER BY accessType ASC";
		$statsByDRM->query($query);
		while ($statsByDRM->fetch()){
			$collectionSummary['statsByDRM'][$statsByDRM->accessType] = $statsByDRM->numTitles;
		}

		$statsBySource = new EContentRecord();
		$query = "SELECT source, COUNT(DISTINCT id) as numTitles FROM `{$statsBySource->__table}` GROUP BY source ORDER BY source ASC";
		$statsBySource->query($query);
		while ($statsBySource->fetch()){
			$collectionSummary['statsBySource'][$statsBySource->source] = $statsBySource->numTitles;
		}

		return $collectionSummary;
	}


	function getAllowableRoles(){
		return array('epubAdmin', 'libraryAdmin', 'opacAdmin');
	}

}