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
require_once('sys/ExternalLinkTracking.php');

class ReportExternalLinks extends Report{

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
		$selectedDateEnd = date('Y-m-d', $selectedDateEnd);
		$interface->assign('selectedDateEnd', $selectedDateEnd);

		//////////Populate the Stores Filter
		$queryHostsFilter = "SELECT DISTINCT linkHost AS linkHost FROM external_link_tracking ORDER BY linkHost ASC";
		$externalLinkTracking = new ExternalLinkTracking();
		$externalLinkTracking->query($queryHostsFilter);

		$allStores = array();
		$i=0;
		while ($externalLinkTracking->fetch()) {
			$allHosts[] = $externalLinkTracking->linkHost;
		}
		$interface->assign('hostFilter', $allHosts);
			
		//////////Grab the Selected Hosts Filter Value
		$selectedHosts = array();
		if (isset($_REQUEST['hostFilter'])){
			$selectedHosts = $_REQUEST['hostFilter'];
		}else {
			$selectedHosts = $allHosts;
		}
		$interface->assign('selectedHosts', $selectedHosts);

		$baseQueryLinks = "SELECT COUNT(externalLinkId) AS timesFollowed, linkUrl, linkHost ".
				"FROM externalLinkTracking ".
				"WHERE (DATE_FORMAT(trackingDate, '%Y-%m-%d')) BETWEEN '". $selectedDateStart . "' AND '". $selectedDateEnd . "' "; 
		if (count($selectedHosts) > 0) {
			$hosts = join("','",$selectedHosts);
			$baseQueryLinks .= "AND linkHost IN ('". $hosts . "') ";
		}
		$baseQueryLinks .= "GROUP BY linkUrl ";

		//////////Get a count of the page view data
		$queryPurchasesCount = "SELECT COUNT(*) AS RowCount from ( ". $baseQueryLinks . ") As ResultCount";

		$resPurchasesCount = mysql_query($queryPurchasesCount);
		$rowCount = mysql_fetch_object($resPurchasesCount);
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
		}
		
		//Create values for how to sort the table.
		$sortList = $this->getSortList();
		if (!isset($sortValue)) {
			$sortValue = 'UrlASC';
		}
		$sortList[$sortValue]["selected"] = true;
		$baseQueryLinks .= $sortList[$sortValue]['sql'];
			
		//append on a limit to return a result
		if (!isset($_REQUEST['exportToExcel'])) {
			$baseQueryLinks .= "LIMIT ".($startRecord -1).", ".$itemsPerPage ." ";
		}

		$resPurchases = mysql_query($baseQueryLinks);

		//Build an array based on the data to dump out to the grid
		$resultsPurchases = array();
		$i=0;
		while ($r=mysql_fetch_array($resPurchases)) {
			$tmp = array(
      	'timesFollowed' => $r['timesFollowed'],  
				'linkHost' => $r['linkHost'],
				'linkUrl' => $r['linkUrl']
			);
			$resultsPurchases[$i++] = $tmp;
		}
		$interface->assign('resultLinks', $resultsPurchases);

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
		$interface->assign('sortList', $sortList);
			
		//////////CHART
		//Create the chart and load data into the results.
		$queryDailyPurchases = "SELECT DATE_FORMAT(trackingDate, '%Y-%m-%d') as date, COUNT(externalLinkId) AS timesFollowed, linkHost FROM external_link_tracking  ".
			"WHERE (DATE_FORMAT(trackingDate, '%Y-%m-%d')) BETWEEN '". $selectedDateStart . "' AND '". $selectedDateEnd . "' " ;	
		if (count($selectedHosts) > 0) {
			$hosts = join("','",$selectedHosts);
			$queryDailyPurchases .= "AND linkHost IN ('". $hosts . "') ";
		}
		$queryDailyPurchases .= "GROUP BY DATE_FORMAT(trackingDate, '%Y-%m-%d'), linkHost ORDER BY trackingDate ASC";
		$dailyUsage = mysql_query($queryDailyPurchases);

		//Initialize data by loading all of the dates that we are looking at so we can show the correct counts or each series on the right day.
		$check_date = $selectedDateStart;
		$datesInReport = array();
		$linkUsageByHostByDay = array();
		foreach ($allHosts as $hostName){
			$linkUsageByHostByDay[$hostName] = array();
		}
		while ($check_date != $selectedDateEnd) {
			$check_date = date ("Y-m-d", strtotime ("+1 day", strtotime($check_date)));
			$datesInReport[] = $check_date;
			//Default number of link usage for the day to 0
			foreach ($allHosts as $host){
				$linkUsageByHostByDay[$host][$check_date] = 0;
			}
		}
		
		//Chart section
		$reportData = new pData();
		while ($r=mysql_fetch_array($dailyUsage)) {
			$linkHost = $r['linkHost'];
			$linkUsageByHostByDay[$linkHost][$r['date']] = $r['timesFollowed'];
		}

		foreach ($linkUsageByHostByDay as $hostName => $dailyResults){
			$reportData->addPoints($dailyResults, $hostName);
		}

		$reportData->setAxisName(0,"Usage");
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
		$chartHref = "/images/charts/dailyPurchases.png";
		$chartPath = $configArray['Site']['local'] . $chartHref;
		$myPicture->render($chartPath);
		$interface->assign('chartPath', $chartHref);

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
			->setCategory("External Link Usage Report");

			// Add some data
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'External Link Usage Report')
			->setCellValue('A3', 'Url')
			->setCellValue('B3', 'Host')
			->setCellValue('C3', 'Usage');

			$a=4;
			//Loop Through The Report Data
			foreach ($resultsPurchases as $resultsPurchases) {
					
				$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$a, $resultsPurchases['linkUrl'])
				->setCellValue('B'.$a, $resultsPurchases['linkHost'])
				->setCellValue('C'.$a, $resultsPurchases['timesFollowed']);
				$a++;
			}
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
				
			// Rename sheet
			$objPHPExcel->getActiveSheet()->setTitle('Simple');

			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$objPHPExcel->setActiveSheetIndex(0);

			// Redirect output to a client’s web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="ExternalLinkReport.xls"');
			header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
			exit;

		}

		$interface->setPageTitle('Report - External Link Tracking');
		$interface->setTemplate('reportExternalLinks.tpl');
		$interface->display('layout.tpl');

	}

	public function getSortList()
	{
		//loop through the
		$sortList = array();
		$sortList["UsageDESC"] = array(
                'column'  => "UsageDESC",
                'displayName' => "Usage DESC",
                'sql' => "ORDER BY timesFollowed DESC ", 
                'selected' => false );
		$sortList["PurchasesASC"] = array(
                'column'  => "UsageASC",
                'displayName' => "Usage ASC",
                'sql' => "ORDER BY timesFollowed ASC ", 
                'selected' => false );
		$sortList["HostASC"] = array(
                'column'  => "HostASC",
                'displayName' => "Host ASC",
                'sql' => "ORDER BY linkHost ASC ", 
                'selected' => false );
		$sortList["HostDESC"] = array(
                'column'  => "HostDESC",
                'displayName' => "Host DESC",
                'sql' => "ORDER BY linkHost DESC ", 
                'selected' => false );
		$sortList["UrlASC"] = array(
                'column'  => "UrlASC",
                'displayName' => "Url ASC",
                'sql' => "ORDER BY linkUrl ASC ", 
                'selected' => false );
		$sortList["UrlDESC"] = array(
                'column'  => "UrlDESC",
                'displayName' => "Url DESC",
                'sql' => "ORDER BY linkUrl DESC ", 
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