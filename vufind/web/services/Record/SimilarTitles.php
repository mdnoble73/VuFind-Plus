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
require_once ROOT_DIR . '/sys/SolrStats.php';

class Record_SimilarTitles extends Record_Record
{
	function launch()
	{
		global $interface;
		global $user;

		$similar = $this->db->getMoreLikeThis2($this->id);
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
		$enrichment = $novelist->loadEnrichment($this->isbn);
		$interface->assign('enrichment', $enrichment);

		$interface->assign('id', $this->id);

		//Build the actual view
		$interface->setTemplate('view-similar.tpl');

		$interface->setPageTitle('Similar to ' . $this->record['title']);

		// Display Page
		$interface->display('layout.tpl');
	}

}