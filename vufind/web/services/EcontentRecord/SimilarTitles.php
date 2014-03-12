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

require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';
require_once ROOT_DIR . '/RecordDrivers/EcontentRecordDriver.php';
require_once ROOT_DIR . '/sys/SolrStats.php';

class EcontentRecord_SimilarTitles extends Action
{
	function launch()
	{
		global $configArray;
		global $interface;
		global $user;

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		/** @var  SearchObject_Solr $db */
		$db = new $class($url);

		//Build the actual view
		$interface->setTemplate('../Record/view-series.tpl');

		$eContentRecord = new EContentRecord();
		$id = strip_tags($_REQUEST['id']);
		$eContentRecord->id = $id;
		$eContentRecord->find(true);
		
		$similar = $db->getMoreLikeThis2($eContentRecord->getSolrId());
		// Send the similar items to the template; if there is only one, we need
		// to force it to be an array or things will not display correctly.
		if (isset($similar) && count($similar['response']['docs']) > 0) {
			$this->similarTitles = $similar['response']['docs'];
		}else{
			$this->similarTitles = array();
		}

		$resourceList = array();
		$curIndex = 0;
		$groupingTerms = array();
		if (isset($this->similarTitles) && is_array($this->similarTitles)){
			foreach ($this->similarTitles as $title){
				$groupingTerm = $title['grouping_term'];
				if (array_key_exists($groupingTerm, $groupingTerms)){
					continue;
				}
				$groupingTerms[$groupingTerm] = $groupingTerm;
				$interface->assign('resultIndex', ++$curIndex);
				$record = RecordDriverFactory::initRecordDriver($title);
				$resourceList[] = $interface->fetch($record->getSearchResult($user, null, false));
			}
		}
		$interface->assign('recordSet', $this->similarTitles);
		$interface->assign('resourceList', $resourceList);

		$interface->assign('recordStart', 1);
		$interface->assign('recordEnd', count($resourceList));
		$interface->assign('recordCount', count($resourceList));

		$novelist = NovelistFactory::getNovelist();
		$enrichment = $novelist->loadEnrichment($eContentRecord->getIsbn());
		$interface->assign('enrichment', $enrichment);

		$interface->assign('id', $id);

		//Build the actual view
		$interface->setTemplate('view-similar.tpl');

		$interface->setPageTitle('Similar to ' . $eContentRecord->title);
		$interface->assign('eContentRecord', $eContentRecord);

		// Display Page
		$interface->display('layout.tpl');
	}

}