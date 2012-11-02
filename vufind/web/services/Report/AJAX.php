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
}
