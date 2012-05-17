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
require_once 'sys/SolrStats.php';

class Series extends Record
{
	function launch()
	{
		global $configArray;

		global $interface;

		//Enable and disable functionality based on library settings
		global $library;
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		if (isset($library)){
			if ($location != null){
				$interface->assign('showHoldButton', (($location->showHoldButton == 1) && ($library->showHoldButton == 1)) ? 1 : 0);
			}else{
				$interface->assign('showHoldButton', $library->showHoldButton);
			}
			$interface->assign('showTagging', $library->showTagging);
			$interface->assign('showRatings', $library->showRatings);
			$interface->assign('showComments', $library->showComments);
		}else{
			if ($location != null){
				$interface->assign('showHoldButton', $location->showHoldButton);
			}else{
				$interface->assign('showHoldButton', 1);
			}
			$interface->assign('showTagging', 1);
			$interface->assign('showRatings', 1);
			$interface->assign('showComments', 1);
		}

		//Build the actual view
		$interface->setTemplate('view-series.tpl');

		require_once 'Enrichment.php';
		$enrichment = new Enrichment(true);
		$enrichmentData = $enrichment->loadEnrichment($this->isbn);
		$seriesTitles = $enrichmentData['novelist']['series'];
		$interface->assign('recordSet', $seriesTitles);
		//Loading the series title is not reliable.  Do not try to load it.
		$seriesTitle;
		$seriesAuthors = array();
		if (isset($seriesTitles) && is_array($seriesTitles)){
			foreach ($seriesTitles as $title){
				if (isset($title['series']) && strlen($title['series']) > 0 && !(isset($seriesTitle))){
					$seriesTitle = $title['series'];
					$interface->assign('seriesTitle', $seriesTitle);
				}
				if (isset($title['author'])){
					$seriesAuthors[$title['author']] = $title['author'];
				}
			}
		}
		$interface->assign('seriesAuthors', $seriesAuthors);

		$interface->assign('recordStart', 1);
		$interface->assign('recordEnd', count($seriesTitles));
		$interface->assign('recordCount', count($seriesTitles));

		$titleField = $this->marcRecord->getField('245');
		$mainTitle = $titleField->getSubfield('a')->getData();
		$interface->setPageTitle($mainTitle);

		// Display Page
		$interface->display('layout.tpl');
	}

}