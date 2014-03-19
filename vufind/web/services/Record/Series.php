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

require_once 'Record.php';

class Record_Series extends Record_Record
{
	function launch()
	{
		global $configArray;
		global $interface;
		global $user;

		//Build the actual view
		$interface->setTemplate('view-series.tpl');

		require_once 'Enrichment.php';
		$enrichment = new Record_Enrichment();
		$enrichmentData = $enrichment->loadEnrichment($this->isbn);

		//Loading the series title is not reliable.  Do not try to load it.
		$seriesTitle = '';
		$seriesAuthors = array();
		$seriesTitles = array();
		$resourceList = array();
		if (isset($enrichmentData['novelist'])){
			$seriesTitles = $enrichmentData['novelist']['series'];
			if (isset($seriesTitles) && is_array($seriesTitles)){
				foreach ($seriesTitles as $key => $title){
					if (isset($title['series']) && strlen($title['series']) > 0 && !(isset($seriesTitle))){
						$seriesTitle = $title['series'];
						$interface->assign('seriesTitle', $seriesTitle);
					}
					if (isset($title['author'])){
						$seriesAuthors[$title['author']] = $title['author'];
					}
					if ($title['libraryOwned']){
						$record = RecordDriverFactory::initRecordDriver($title);
						$resourceList[] = $interface->fetch($record->getSearchResult($user, null, false));
					}else{
						$interface->assign('record', $title);
						$resourceList[] = $interface->fetch('RecordDrivers/Index/nonowned_result.tpl');
					}
				}
			}
		}
		$interface->assign('seriesAuthors', $seriesAuthors);
		$interface->assign('recordSet', $seriesTitles);
		$interface->assign('resourceList', $resourceList);

		$interface->assign('recordStart', 1);
		$interface->assign('recordEnd', count($seriesTitles));
		$interface->assign('recordCount', count($seriesTitles));

		$interface->setPageTitle($seriesTitle);

		// Display Page
		$interface->display('layout.tpl');
	}

}