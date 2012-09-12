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

require_once 'services/Report/Report.php';
require_once("sys/pChart/class/pData.class.php");
require_once("sys/pChart/class/pDraw.class.php");
require_once("sys/pChart/class/pImage.class.php");
require_once 'sys/Pager.php';
require_once("PHPExcel.php");

class ReportPageViewsLocation extends Report{

	function launch(){
		global $configArray;
		global $interface;
		global $user;

		//////////Populate the Date Filter Start
		$today = getdate();
		//Grab the Selected Date Start
		if (isset($_REQUEST['dateFilterStart'])){
			if (preg_match('/\\d{1,2}\/\\d{1,2}\/\\d{4}/', $_REQUEST['dateFilterStart'])){
				$selectedDateStart = DateTime::createFromFormat('m/d/Y', $_REQUEST['dateFilterStart']);
				$selectedDateStart = $selectedDateStart->getTimestamp();
			}else{
				$selectedDateStart = strtotime($_REQUEST['dateFilterStart']);
			}
		} else {
			$selectedDateStart = strtotime('-30 days');
		}
		$selectedDateStartTime = $selectedDateStart;
		$selectedDateStart = date('Y-m-d', $selectedDateStart);
		$interface->assign('selectedDateStart', $selectedDateStart);

		//Populate the Date Filter End
		//Grab the Selected End Date
		if (isset($_REQUEST['dateFilterEnd'])){
			if (preg_match('/\\d{1,2}\/\\d{1,2}\/\\d{4}/', $_REQUEST['dateFilterEnd'])){
				$selectedDateEnd = DateTime::createFromFormat('m/d/Y', $_REQUEST['dateFilterEnd']);
				$selectedDateEnd = $selectedDateEnd->getTimestamp();
			}else{
				$selectedDateEnd = strtotime($_REQUEST['dateFilterEnd']);
			}
		} else {
			$selectedDateEnd = strtotime('today');
		}
		$selectedDateEndTime = $selectedDateEnd;
		$selectedDateEnd = date('Y-m-d', $selectedDateEnd);
		$interface->assign('selectedDateEnd', $selectedDateEnd);

		//////////Populate the Locations Filter
		$queryLocationsFilter = "SELECT id AS ipId, location FROM ip_lookup
				ORDER BY location ASC";
		$resLocationsFilter = mysql_query($queryLocationsFilter);

		$resultsLocationsFilter = array();
		$i=0;
		while ($r=mysql_fetch_array($resLocationsFilter)) {
			$tmp = array(
		    'ipId' => $r['ipId'],
				'location' => $r['location']
			);
			$resultsLocationsFilter[$i++] = $tmp;
		}
		$resultsLocationsFilter[] = array(
			'ipId' => '-1',
			'location' => 'Home Users'
		);
		$resultsLocationsFilter[]  = array(
			'ipId' => '-2',
			'location' => 'Search Bots'
		);
		$interface->assign('resultsLocationsFilter', $resultsLocationsFilter);

		//////////Grab the Selected Locations Filter Value
		$selectedLocationsFilter = array();
		if (isset($_REQUEST['locationsFilter'])){
			$selectedLocationsFilter = $_REQUEST['locationsFilter'];
		}
		else {
			//Pre-Populate the Locations Filter MultiSelect list
			$resLocationsPreSelect = mysql_query($queryLocationsFilter);

			$i=0;
			while ($r=mysql_fetch_array($resLocationsPreSelect)) {
				$selectedLocationsFilter[$i++] = $r['ipId'];
			}
			$selectedLocationsFilter[] = -1;
			//Do not include search bots by default
			//$selectedLocationsFilter[] = -2;
		}
		$interface->assign('selectedLocationsFilter', $selectedLocationsFilter);

		$baseQueryPageViews = "SELECT ut.ipId as ipId, ".
				"(SELECT location FROM ip_lookup WHERE id = ut.ipId LIMIT 1) AS Location, ".
				"sum(ut.numPageViews) AS PageViews, sum(ut.numHolds) Holds, ".
				"sum(ut.numRenewals) AS Renewals  ".
				"FROM usage_tracking ut ".
				"WHERE ut.trackingDate BETWEEN '". $selectedDateStartTime . "' AND '". $selectedDateEndTime . "' ";
		if (count($selectedLocationsFilter) > 0) {
			$ipIds = join(",",$selectedLocationsFilter);
			$baseQueryPageViews .= "AND (ut.ipId IN (". $ipIds . ") ";
		}
		if (in_array("-1", $selectedLocationsFilter)) {
		$baseQueryPageViews .= "OR (ut.ipId = -1) ";
		}
		if (in_array("-2", $selectedLocationsFilter)) {
			$baseQueryPageViews .= "OR (ut.ipId = -2) ";
		}
		$baseQueryPageViews .= ") ";
		//Add If then for the Unknown filter
		$baseQueryPageViews .= "GROUP BY ipId ";

		//////////Get a count of the page view data
		$queryPageViewsCount = "SELECT COUNT(*) AS RowCount from ( ". $baseQueryPageViews . ") As ResultCount";

		$resPageViewsCount = mysql_query($queryPageViewsCount);
		$rowCount = mysql_fetch_object($resPageViewsCount);
		$totalResultCount = $rowCount->RowCount;

		//////////Create the items per page array
		$itemsPerPageList = array();
		$itemsPerPageList = $this->getItemsPerPageList();

		///////////////////PAGING
		$currentPage = 1;
		$resultTotal = $totalResultCount;
		$startRecord = 1;
		if (isset($_GET['itemsPerPage'])) {
			switch ($_GET['itemsPerPage']) {
				case "20":
					$itemsPerPage = 20;
					$itemsPerPageList["20"]["selected"] = true;
					break;
				case "100":
					$itemsPerPage = 100;
					$itemsPerPageList["100"]["selected"] = true;
					break;
				default:
					$itemsPerPage = 50;
					$itemsPerPageList["50"]["selected"] = true;
			}
		} else {
			$itemsPerPage = 50;
			$itemsPerPageList["50"]["selected"] = true;
		}
		$endRecord = $itemsPerPage;
		$interface->assign('itemsPerPageList', $itemsPerPageList);

		if (isset($_GET['page'])) {
			$currentPage = $_GET['page'];
			// 1st record is easy, work out the start of this page
			$startRecord = (($currentPage - 1) * $itemsPerPage) + 1;
			// Last record needs more care
			if ($resultTotal < $itemsPerPage) {
				// There are less records returned then one page, use total results
				$endRecord = $resultTotal;
			} else if (($currentPage * $itemsPerPage) > $resultTotal) {
				// The end of the curent page runs past the last record, use total results
				$endRecord = $resultTotal;
			} else {
				// Otherwise use the last record on this page
				$endRecord = $currentPage * $itemsPerPage;
			}

		}

		//////////Get the Page View Data with paging and sorting
		if (isset($_GET['reportSort'])) {
			$sortValue = $_GET['reportSort'];
		}else{
			$sortValue = 'PageViewsASC';
		}



		//////////Create a sort array
		$sortList = $this->getSortList();
		if (isset($sortValue)) {
			switch ($sortValue) {
				case "PageViewsDESC":
					$baseQueryPageViews .= "ORDER BY PageViews DESC ";
					$sortList["PageViewsDESC"]["selected"] = true;
					break;
				case "PageViewsASC":
					$baseQueryPageViews .= "ORDER BY PageViews ASC ";
					$sortList["PageViewsASC"]["selected"] = true;
					break;
				case "HoldsDESC":
					$baseQueryPageViews .= "ORDER BY Holds DESC ";
					$sortList["HoldsDESC"]["selected"] = true;
					break;
				case "HoldsASC":
					$baseQueryPageViews .= "ORDER BY Holds ASC ";
					$sortList["HoldsASC"]["selected"] = true;
					break;
				case "RenewalsDESC":
					$baseQueryPageViews .= "ORDER BY Renewals DESC ";
					$sortList["RenewalsDESC"]["selected"] = true;
					break;
				case "RenewalsASC":
					$baseQueryPageViews .= "ORDER BY Renewals ASC ";
					$sortList["RenewalsASC"]["selected"] = true;
					break;
				case "LocationASC":
					$baseQueryPageViews .= "ORDER BY Location ASC ";
					$sortList["LocationASC"]["selected"] = true;
					break;
				case "LocationDESC":
					$baseQueryPageViews .= "ORDER BY Location DESC ";
					$sortList["LocationDESC"]["selected"] = true;
					break;
				case "TrackingDateASC":
					$baseQueryPageViews .= "ORDER BY TrackingDate ASC ";
					$sortList["TrackingDateASC"]["selected"] = true;
					break;
				case "TrackingDateDESC":
					$baseQueryPageViews .= "ORDER BY TrackingDate DESC ";
					$sortList["TrackingDateDESC"]["selected"] = true;
					break;
					default:
					$baseQueryPageViews .= "ORDER BY PageViews DESC  ";
					$sortList["PageViewsDESC"]["selected"] = true;
			}
		}
		else {
			$baseQueryPageViews .= "ORDER BY PageViews DESC ";
		}

		//append on a limit to return a result
		if (!isset($_REQUEST['exportToExcel'])) {
			$baseQueryPageViews .= "LIMIT ".($startRecord -1).", ".$itemsPerPage ." ";
		}

		$resPageViews = mysql_query($baseQueryPageViews);

		//Build an array based on the data to dump out to the grid
		$resultsPageViews = array();
		$i=0;
		while ($r=mysql_fetch_array($resPageViews)) {

			if ($r['ipId'] == '-1') {
				$tmpLocation = 'Home Users';
			}elseif ($r['ipId'] == '-2') {
				$tmpLocation = 'Search Bots';
			} else {
				$tmpLocation = $r['Location'];
			}

			$tmp = array(
      	'ipId' => $r['ipId'],
				'Location' => $tmpLocation,
				'PageViews' => $r['PageViews'],
				'Holds' => $r['Holds'],
				'Renewals' => $r['Renewals'],
			);
			$resultsPageViews[$i++] = $tmp;
		}
		$interface->assign('resultsPageViews', $resultsPageViews);

		//////////Paging Array
		$summary = array(
      	'page' => $currentPage,
				'perPage' => $itemsPerPage,
				'resultTotal' => $totalResultCount,
				'startRecord' => $startRecord,
        'endRecord'=> $endRecord
		);

		$interface->assign('recordCount', $summary['resultTotal']);
		$interface->assign('recordStart', $summary['startRecord']);
		$interface->assign('recordEnd',   $summary['endRecord']);

		// Process Paging using VuFind Pager object
		if (strrpos($_SERVER["REQUEST_URI"], "page=")) {
			//replace the page variable with a new one
			$link = str_replace(("page=".$currentPage),"page=%d",$_SERVER["REQUEST_URI"]);
		}
		else {
			if (strrpos($_SERVER["REQUEST_URI"], "?")) {
				$link = $_SERVER["REQUEST_URI"]."&page=%d";
			}
			else {
				$link = $_SERVER["REQUEST_URI"]."?page=%d";
			}
		}
		$options = array(	'totalItems' => $summary['resultTotal'],
												'fileName'   => $link,
												'perPage'    => $summary['perPage']);
		$pager = new VuFindPager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		///////////////////END PAGING


		//////////Sorting
		$sortUrl = $_SERVER["REQUEST_URI"];
		if (isset($sortValue)) {
			//Set the URL for sorting
			if (strrpos($_SERVER["REQUEST_URI"], "reportSort=")) {
				//replace the page variable with a new one
				$sortUrl = str_replace(("sort=".$currentPage),"reportSort=".$sortValue,$_SERVER["REQUEST_URI"]);
			}
			else {
				if (strrpos($_SERVER["REQUEST_URI"], "?")) {
					$sortUrl = $_SERVER["REQUEST_URI"]."&reportSort=".$sortValue;
				}
				else {
					$sortUrl = $_SERVER["REQUEST_URI"]."?reportSort=".$sortValue;
				}
			}
		}
		$interface->assign('sortUrl', $sortUrl);
		$interface->assign('sortValue', $sortValue);
		$interface->assign('sortList', $sortList);

		//////////CHART
		//Create the chart and load data into the results.
		$queryDailyPageViews = "SELECT (DATE_FORMAT(DATE(FROM_UNIXTIME(trackingDate)), '%Y-%m-%d')) AS TrackingDate, ".
				"SUM(numPageViews) AS PageViews, SUM(numHolds) AS Holds, ".
				"SUM(numRenewals) AS Renewals ".
				"FROM usage_tracking ".
				"WHERE (DATE_FORMAT(DATE(FROM_UNIXTIME(trackingDate)), '%Y-%m-%d')) ".
				"BETWEEN '". $selectedDateStart . "' AND '". $selectedDateEnd . "' ";
		if (count($selectedLocationsFilter) > 0) {
			$ipIds = join(",",$selectedLocationsFilter);
			$queryDailyPageViews .= "AND ipId IN (". $ipIds . ") ";
		}
		$queryDailyPageViews .= "GROUP BY TrackingDate ORDER BY TrackingDate ASC";
		$dailyPageViews = mysql_query($queryDailyPageViews);

		//Initialize data by loading all of the dates that we are looking at so we can show the correct counts or each series on the right day.
		$check_date = $selectedDateStart;
		$datesInReport = array();
		$pageViewsByLocationByDay = array();

		while ($check_date != $selectedDateEnd) {
			$check_date = date ("Y-m-d", strtotime ("+1 day", strtotime($check_date)));
			$datesInReport[] = $check_date;
			//Default number of purchases for the day to 0
			$pageViewsByLocationByDay['Page Views'][$check_date] = 0;
			$pageViewsByLocationByDay['Holds'][$check_date] = 0;
			$pageViewsByLocationByDay['Renewals'][$check_date] = 0;
		}

		//Chart section
		$reportData = new pData();
		while ($r=mysql_fetch_array($dailyPageViews)) {
			$pageViewsByLocationByDay['Page Views'][$r['TrackingDate']] = $r['PageViews'];
			$pageViewsByLocationByDay['Holds'][$r['TrackingDate']] = $r['Holds'];
			$pageViewsByLocationByDay['Renewals'][$r['TrackingDate']] = $r['Renewals'];
		}

		foreach ($pageViewsByLocationByDay as $statisticName => $dailyResults){
			$reportData->addPoints($dailyResults, $statisticName);
		}

		$reportData->setAxisName(0,"Page Views");
		$reportData->addPoints($datesInReport, "Dates");
		$reportData->setAbscissa("Dates");

		/* Create the pChart object */
		$myPicture = new pImage(700,290,$reportData);

		/* Draw the background */
		$Settings = array("R"=>225, "G"=>225, "B"=>225);
		$myPicture->drawFilledRectangle(0,0,700,290,$Settings);

		/* Add a border to the picture */
		$myPicture->drawRectangle(0,0,699,289,array("R"=>0,"G"=>0,"B"=>0));

		$myPicture->setFontProperties(array("FontName"=> "sys/pChart/Fonts/verdana.ttf","FontSize"=>9));
		$myPicture->setGraphArea(50,30,670,190);
		//$myPicture->drawFilledRectangle(30,30,670,150,array("R"=>255,"G"=>255,"B"=>255,"Surrounding"=>-200,"Alpha"=>10));
		$myPicture->drawScale(array("DrawSubTicks"=>TRUE, "LabelRotation"=>90));
		$myPicture->setFontProperties(array("FontName"=> "sys/pChart/Fonts/verdana.ttf","FontSize"=>9));
		$myPicture->drawLineChart(array("DisplayValues"=>TRUE,"DisplayColor"=>DISPLAY_AUTO));

		/* Write the chart legend */
		$myPicture->drawLegend(80,20,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

		/* Render the picture (choose the best way) */
		$time = time();
		$chartHref = "/images/charts/dailyPageViewsLocation{$time}.png";
		$chartPath = $configArray['Site']['local'] . $chartHref;
		$myPicture->render($chartPath);
		$interface->assign('chartPath', $chartHref);
		sleep(5);

		//////////EXPORT To EXCEL
		if (isset($_REQUEST['exportToExcel'])) {

			//PHPEXCEL
			// Create new PHPExcel object
			$objPHPExcel = new PHPExcel();

			// Set properties
			$objPHPExcel->getProperties()->setCreator("DCL")
			->setLastModifiedBy("DCL")
			->setTitle("Office 2007 XLSX Document")
			->setSubject("Office 2007 XLSX Document")
			->setDescription("Office 2007 XLSX, generated using PHP.")
			->setKeywords("office 2007 openxml php")
			->setCategory("Page Views Location Report");

			// Add some data
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'Page Views By Location Report')
			->setCellValue('A3', 'LOCATION')
			->setCellValue('B3', 'PAGE VIEWS')
			->setCellValue('C3', 'HOLDS')
			->setCellValue('D3', 'RENEWALS')
			->setCellValue('E3', 'DATE');

			$a=4;
			//Loop Through The Report Data
			foreach ($resultsPageViews as $resultsPageViews) {

				$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$a, $resultsPageViews['Location'])
				->setCellValue('B'.$a, $resultsPageViews['PageViews'])
				->setCellValue('C'.$a, $resultsPageViews['Holds'])
				->setCellValue('D'.$a, $resultsPageViews['Renewals'])
				->setCellValue('E'.$a, $resultsPageViews['TrackingDate'])
				;
				$a++;
			}
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);


			// Rename sheet
			$objPHPExcel->getActiveSheet()->setTitle('Simple');

			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$objPHPExcel->setActiveSheetIndex(0);

			// Redirect output to a client’s web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="PageViewsLocationReport.xls"');
			header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
			exit;

		}

		$interface->setPageTitle('Report - Page Views By Location');
		$interface->setTemplate('reportPageViewsLocation.tpl');
		$interface->display('layout.tpl');

	}

	public function getSortList()
	{
		//loop through the
		$sortList = array();
		$sortList["PageViewsDESC"] = array(
                'column'  => "PageViewsDESC",
                'displayName' => "PageViews DESC",
                'selected' => false );
		$sortList["PageViewsASC"] = array(
                'column'  => "PageViewsASC",
                'displayName' => "PageViews ASC",
                'selected' => false );
		$sortList["HoldsDESC"] = array(
                'column'  => "HoldsDESC",
                'displayName' => "Holds DESC",
                'selected' => false );
		$sortList["HoldsASC"] = array(
                'column'  => "HoldsASC",
                'displayName' => "Holds ASC",
                'selected' => false );
		$sortList["RenewalsDESC"] = array(
                'column'  => "RenewalsDESC",
                'displayName' => "Renewals DESC",
                'selected' => false );
		$sortList["RenewalsASC"] = array(
                'column'  => "RenewalsASC",
                'displayName' => "Renewals ASC",
                'selected' => false );
		$sortList["LocationASC"] = array(
                'column'  => "LocationASC",
                'displayName' => "Location ASC",
                'selected' => false );
		$sortList["LocationDESC"] = array(
                'column'  => "LocationDESC",
                'displayName' => "Location DESC",
                'selected' => false );
		return $sortList;
	}

	public function getItemsPerPageList()
	{
		//loop through the
		$itemsPerPageList = array();
		$itemsPerPageList["20"] = array(
					      'amount'  => 20,
					      'selected' => false );
		$itemsPerPageList["50"] = array(
					      'amount'  => 50,
					      'selected' => false );
		$itemsPerPageList["100"] = array(
					      'amount'  => 100,
      					'selected' => false );

		return $itemsPerPageList;
	}


}