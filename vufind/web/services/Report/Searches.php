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

require_once ROOT_DIR . '/services/Report/AnalyticsReport.php';
require_once ROOT_DIR . '/sys/Pager.php';

class Report_Searches extends Report_AnalyticsReport{

	function launch(){
		global $interface;
		global $analytics;

		//Setup filters
		$this->setupFilters();
		$topSearches = $analytics->getTopSearches(true);
		$interface->assign('topSearches', $topSearches);

		$session = $analytics->getSessionFilters();

		$search = new Analytics_Search();
		$search->selectAdd();
		$search->selectAdd("count(analytics_search.id) as numSearches");
		$search->selectAdd("lookfor");
		if ($session != null){
			$search->joinAdd($session);
		}
		$search->whereAdd("numResults = 0");
		$search->groupBy('lookfor');
		$search->orderBy('numSearches DESC');
		$search->limit(0, 20);
		$search->find();
		$topNoHitSearches = array();
		while ($search->fetch()){
			if (!is_null($search->lookfor) || strlen(trim($search->lookfor)) > 0){
				$topNoHitSearches[] = "{$search->lookfor} ({$search->numSearches})";
			}else{
				$topNoHitSearches[] = "<blank> ({$search->numSearches})";
			}
		}
		$interface->assign('topNoHitSearches', $topNoHitSearches);

		$search = new Analytics_Search();
		$search->selectAdd();
		$search->selectAdd("lookfor");
		$search->selectAdd("MAX(searchTime) as lastSearch ");
		if ($session != null){
			$search->joinAdd($session);
		}
		$search->groupBy('lookfor');
		$search->orderBy('lastSearch DESC');
		$search->limit(0, 20);
		$search->find();
		$latestSearches = array();
		while ($search->fetch()){
			if (!is_null($search->lookfor) || strlen(trim($search->lookfor)) > 0){
				$latestSearches[] = "{$search->lookfor} {$search->searchTime}";
			}else{
				$latestSearches[] = "<blank>";
			}
		}
		$interface->assign('latestSearches', $latestSearches);

		$search = new Analytics_Search();
		$search->selectAdd();
		$search->selectAdd("lookfor");
		$search->selectAdd("MAX(searchTime) as lastSearch ");
		if ($session != null){
			$search->joinAdd($session);
		}
		$search->whereAdd("numResults = 0");
		$search->groupBy('lookfor');
		$search->orderBy('lastSearch DESC');
		$search->limit(0, 20);
		$search->find();
		$latestNoHitSearches = array();
		while ($search->fetch()){
			if (!is_null($search->lookfor) || strlen(trim($search->lookfor)) > 0){
				$latestNoHitSearches[] = "{$search->lookfor} {$search->searchTime}";
			}else{
				$latestNoHitSearches[] = "<blank>";
			}
		}
		$interface->assign('latestNoHitSearches', $latestNoHitSearches);

		$interface->setPageTitle('Report - Searches');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('searches.tpl');
		$interface->display('layout.tpl');
	}
}