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

require_once 'Action.php';

class AJAX extends Action {

	function launch() {
		global $timer;
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");
		if (in_array($method, array())){
			//XML responses
			header ('Content-type: text/xml');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$xml = '<?xml version="1.0" encoding="UTF-8"?' . ">\n" .
	               "<AJAXResponse>\n";
			if (is_callable(array($this, $_GET['method']))) {
				$xml .= $this->$_GET['method']();
			} else {
				$xml .= '<Error>Invalid Method</Error>';
			}
			$xml .= '</AJAXResponse>';
			echo $xml;
		}else if (in_array($method, array())){
			//HTML responses
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else{
			//JSON Responses
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo json_encode($this->$method());
		}
	}

	/**
	 * Active sessions are any sessions where the last request happened less than 1 minute ago.
	 */
	function getActiveSessions(){
		$analyticsSession = new Analytics_Session();
		$analyticsSession->whereAdd('lastRequestTime >= ' . (time() - 60));
		$analyticsSession->find();
		return array('activeSessionCount' => $analyticsSession->N);
	}

	/**
	 * Recent activity includes users, searches done, events, and page views
	 */
	function getRecentActivity(){
		$interval = isset($_REQUEST['interval']) ? $_REQUEST['interval'] : 10;
		$curTime = time();
		$activityByMinute = array();

		$analyticsSession = new Analytics_Session();
		$analyticsSession->selectAdd('count(id) as numActiveUsers');
		$analyticsSession->whereAdd('lastRequestTime > ' . ($curTime - $interval));
		 $analyticsSession->whereAdd("lastRequestTime <= $curTime");
		if ($analyticsSession->find(true)){
			$activityByMinute['activeUsers'] = $analyticsSession->numActiveUsers;
		}else{
			$activityByMinute['activeUsers'] = 0;
		}

		$pageView = new Analytics_PageView();
		$pageView->selectAdd('count(id) as numPageViews');
		$pageView->whereAdd("pageEndTime > " . ($curTime - $interval));
		$pageView->whereAdd("pageEndTime <= $curTime");
		if ($pageView->find(true)){
			$activityByMinute['pageViews'] = $pageView->numPageViews;
		}else{
			$activityByMinute['pageViews'] = 0;
		}

		$searches = new Analytics_Search();
		$searches->selectAdd('count(id) as numSearches');
		$searches->whereAdd("searchTime > " . ($curTime - $interval));
		$searches->whereAdd("searchTime <= $curTime");
		if ($searches->find(true)){
			$activityByMinute['searches'] = $searches->numSearches;
		}else{
			$activityByMinute['searches'] = 0;
		}

		$events = new Analytics_Event();
		$events->selectAdd('count(id) as numEvents');
		$events->whereAdd("eventTime > " . ($curTime - $interval));
		$events->whereAdd("eventTime <= $curTime");
		if ($events->find(true)){
			$activityByMinute['events'] = $events->numEvents;
		}else{
			$activityByMinute['events'] = 0;
		}

		return $activityByMinute;
	}

	function getSearchByTypeData(){
		//load searches by type
		$searches = new Analytics_Search();
		$searches->selectAdd('count(id) as numSearches');
		$searches->selectAdd('searchType');
		$searches->groupBy('searchType');
		$searches->find();
		$totalSearches = 0;
		$searchByTypeRaw = array();
		while ($searches->fetch()){
			$searchByTypeRaw[$searches->searchType] = $searches->numSearches;
			$totalSearches += $searches->numSearches;
		}
		$searchesByType = array();
		foreach ($searchByTypeRaw as $searchName => $searchCount){
			$searchesByType[] = array($searchName, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
		}

		return $searchesByType;
	}

	function getSearchByScopeData(){
		//load searches by type
		$searches = new Analytics_Search();
		$searches->selectAdd('count(id) as numSearches');
		$searches->selectAdd('scope');
		$searches->groupBy('scope');
		$searches->find();
		$totalSearches = 0;
		$searchByTypeRaw = array();
		while ($searches->fetch()){
			$searchByTypeRaw[$searches->scope] = $searches->numSearches;
			$totalSearches += $searches->numSearches;
		}
		$searchesByType = array();
		foreach ($searchByTypeRaw as $searchName => $searchCount){
			$searchesByType[] = array($searchName, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
		}

		return $searchesByType;
	}

	function getSearchWithFacetsData(){
		//load searches by type
		$searches = new Analytics_Search();
		$searches->selectAdd('count(id) as numSearches');
		$searches->groupBy('facetsApplied');
		$searches->find();
		$totalSearches = 0;
		$searchByTypeRaw = array();
		while ($searches->fetch()){
			$searchByTypeRaw[$searches->facetsApplied == 0 ? 'No Facets' : 'Facets Applied'] = $searches->numSearches;
			$totalSearches += $searches->numSearches;
		}
		$searchesByType = array();
		foreach ($searchByTypeRaw as $searchName => $searchCount){
			$searchesByType[] = array($searchName, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
		}

		return $searchesByType;
	}

	function getPageViewsByModuleData(){
		//load searches by type
		$pageViews = new Analytics_PageView();
		$pageViews->selectAdd('count(id) as numViews');
		$pageViews->selectAdd('module');
		$pageViews->groupBy('module');
		$pageViews->orderBy('numViews DESC');
		$pageViews->find();
		$pageViewsByModuleRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByModuleRaw[] = array ($pageViews->module, (int)$pageViews->numViews);
		}

		return $pageViewsByModuleRaw;
	}

	function getPageViewsByThemeData(){
		//load searches by type
		$pageViews = new Analytics_PageView();
		$session = new Analytics_Session();

		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$pageViews->joinAdd($session);
		$pageViews->selectAdd('theme');
		$pageViews->groupBy('theme');
		$pageViews->orderBy('numViews DESC');
		$pageViews->find();
		$pageViewsByThemeRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByThemeRaw[] = array ($pageViews->theme, (int)$pageViews->numViews);
		}

		return $pageViewsByThemeRaw;
	}

	function getPageViewsByDeviceData(){
		//load searches by type
		$pageViews = new Analytics_PageView();
		$session = new Analytics_Session();

		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$pageViews->joinAdd($session);
		$pageViews->selectAdd('device');
		$pageViews->groupBy('device');
		$pageViews->orderBy('numViews DESC');
		$pageViews->find();
		$pageViewsByDeviceRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByDeviceRaw[] = array ($pageViews->device, (int)$pageViews->numViews);
		}

		return $pageViewsByDeviceRaw;
	}

	function getPageViewsByHomeLocationData(){
		//load searches by type
		$pageViews = new Analytics_PageView();
		$session = new Analytics_Session();
		$location = new Location();

		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$session->joinAdd($location);
		$pageViews->joinAdd($session);
		$pageViews->selectAdd('displayName');
		$pageViews->groupBy('displayName');
		$pageViews->orderBy('numViews DESC');
		$pageViews->find();
		$pageViewsByDeviceRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByDeviceRaw[] = array ($pageViews->displayName, (int)$pageViews->numViews);
		}

		return $pageViewsByDeviceRaw;
	}

	function getPageViewsByPhysicalLocationData(){
		//load searches by type
		$pageViews = new Analytics_PageView();
		$session = new Analytics_Session();

		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$pageViews->joinAdd($session);
		$pageViews->selectAdd('physicalLocation');
		$pageViews->groupBy('physicalLocation');
		$pageViews->orderBy('numViews DESC');
		$pageViews->find();
		$pageViewsByDeviceRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByDeviceRaw[] = array ($pageViews->physicalLocation, (int)$pageViews->numViews);
		}

		return $pageViewsByDeviceRaw;
	}

	function getFacetUsageByTypeData(){
		//load searches by type
		$events = new Analytics_Event();

		$events->selectAdd('count(analytics_event.id) as numEvents');
		$events->category = 'Apply Facet';
		$events->selectAdd('action');
		$events->groupBy('action');
		$events->orderBy('numEvents DESC');
		$events->find();
		$eventsByFacetTypeRaw = array();
		while ($events->fetch()){
			$eventsByFacetTypeRaw[] = array ($events->action, (int)$events->numEvents);
		}

		return $eventsByFacetTypeRaw;
	}
}
