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

require_once 'services/Report/AnalyticsReport.php';
require_once 'sys/Pager.php';
require_once("PHPExcel.php");

class PageViews extends AnalyticsReport{

	function launch(){
		global $configArray;
		global $interface;
		global $user;

		//Setup filters
		$this->setupFilters();

		$this->loadSlowestPageViews();

		$interface->setPageTitle('Report - Page Views');
		$interface->setTemplate('pageviews.tpl');
		$interface->display('layout.tpl');
	}

	function loadSlowestPageViews(){
		global $interface;
		global $user;

		$pageView = new Analytics_PageView();
		$pageView->query('select module, action, method, AVG(loadTime) as loadTime, count(id) as numViews FROM (SELECT id, module, action, method, pageEndTime-pageStartTime as loadTime FROM `analytics_page_view`) rawData group by module, action order by loadTime DESC LIMIT 0, 20');
		$slowPages = array();
		while ($pageView->fetch()){
			$slowPages[] = array(
				'module' => $pageView->module,
				'action' => $pageView->action,
				'method' => $pageView->method,
				'loadTime' => $pageView->loadTime,
				'numViews' => $pageView->numViews,
			);
		}
		$interface->assign('slowPages', $slowPages);
	}


}