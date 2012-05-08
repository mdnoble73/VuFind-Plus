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

class ReportPurchase extends Report{

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
		$queryStoresFilter = "SELECT DISTINCT store AS Store FROM purchase_link_tracking
				ORDER BY Store ASC";
		$resStoresFilter = mysql_query($queryStoresFilter);

		$allStores = array();
		$i=0;
		while ($r=mysql_fetch_array($resStoresFilter)) {
			$allStores[] = $r['Store'];
		}
		$interface->assign('resultsStoresFilter', $allStores);
			
		//////////Grab the Selected Stores Filter Value
		$selectedStoresFilter = array();
		if (isset($_REQUEST['storesFilter'])){
			$selectedStoresFilter = $_REQUEST['storesFilter'];
		}else {
			//Pre-Populate the Stores Filter MultiSelect list
			$queryStoresPreSelect = "SELECT DISTINCT store AS Store FROM purchase_link_tracking
				ORDER BY Store ASC";
			$resStoresPreSelect = mysql_query($queryStoresPreSelect);

			$i=0;
			while ($r=mysql_fetch_array($resStoresPreSelect)) {
				$selectedStoresFilter[$i++] = $r['Store'];
			}
		}
		$interface->assign('selectedStoresFilter', $selectedStoresFilter);

		$baseQueryPurchases = "SELECT COUNT(purchaseLinkId) AS Purchases, store AS Store ".
				"FROM purchase_link_tracking ".
				"WHERE (DATE_FORMAT(trackingDate, '%Y-%m-%d')) BETWEEN '". $selectedDateStart . "' AND '". $selectedDateEnd . "' "; 
		if (count($selectedStoresFilter) > 0) {
			$stores = join("','",$selectedStoresFilter);
			$baseQueryPurchases .= "AND store IN ('". $stores . "') ";
		}
		$baseQueryPurchases .= "GROUP BY store ";

		//////////Get a count of the page view data
		$queryPurchasesCount = "SELECT COUNT(*) AS RowCount from ( ". $baseQueryPurchases . ") As ResultCount";

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
			


		//////////Create a sort array
		$sortList = $this->getSortList();
		if (isset($sortValue)) {
			switch ($sortValue) {
				case "PurchasesDESC":
					$baseQueryPurchases .= "ORDER BY purchases DESC ";
					$sortList["PurchasesDESC"]["selected"] = true;
					break;
				case "PurchasesASC":
					$baseQueryPurchases .= "ORDER BY purchases ASC ";
					$sortList["PurchasesASC"]["selected"] = true;
					break;
				case "StoreASC":
					$baseQueryPurchases .= "ORDER BY store ASC ";
					$sortList["StoreASC"]["selected"] = true;
					break;
				case "StoreDESC":
					$baseQueryPurchases .= "ORDER BY store DESC ";
					$sortList["StoreDESC"]["selected"] = true;
					break;
				default:
					$baseQueryPurchases .= "ORDER BY store ASC  ";
					$sortList["StoreASC"]["selected"] = true;
			}
		}
		else {
			$baseQueryPurchases .= "ORDER BY store ASC ";
		}
			
		//append on a limit to return a result
		if (!isset($_REQUEST['exportToExcel'])) {
			$baseQueryPurchases .= "LIMIT ".($startRecord -1).", ".$itemsPerPage ." ";
		}

		$resPurchases = mysql_query($baseQueryPurchases);

		//Build an array based on the data to dump out to the grid
		$resultsPurchases = array();
		$i=0;
		while ($r=mysql_fetch_array($resPurchases)) {
			$tmp = array(
      	'Purchases' => $r['Purchases'],  
				'Store' => $r['Store']
			);
			$resultsPurchases[$i++] = $tmp;
		}
		$interface->assign('resultsPurchases', $resultsPurchases);

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
		$queryDailyPurchases = "SELECT DATE_FORMAT(trackingDate, '%Y-%m-%d') as date, COUNT(recordId) AS Purchases, store AS Store FROM purchase_link_tracking  ".
			"WHERE (DATE_FORMAT(trackingDate, '%Y-%m-%d')) BETWEEN '". $selectedDateStart . "' AND '". $selectedDateEnd . "' " ;	
		if (count($selectedStoresFilter) > 0) {
			$stores = join("','",$selectedStoresFilter);
			$queryDailyPurchases .= "AND store IN ('". $stores . "') ";
		}
		$queryDailyPurchases .= "GROUP BY DATE_FORMAT(trackingDate, '%Y-%m-%d'), store ORDER BY trackingDate ASC";
		$dailyPurchases = mysql_query($queryDailyPurchases);

		//Initialize data by loading all of the dates that we are looking at so we can show the correct counts or each series on the right day.
		$check_date = $selectedDateStart;
		$datesInReport = array();
		$purchasesByStoreByDay = array();
		foreach ($allStores as $storeName){
			$purchasesByStoreByDay[$storeName] = array();
		}
		$numDatesChecked = 0; //Prevent infinite loops
		while ($check_date != $selectedDateEnd && $numDatesChecked < 3000) {
			$check_date = date ("Y-m-d", strtotime ("+1 day", strtotime($check_date)));
			$datesInReport[] = $check_date;
			//Default number of purchases for the day to 0
			foreach ($allStores as $storeName){
				$purchasesByStoreByDay[$storeName][$check_date] = 0;
			}
			$numDatesChecked++;
		}
		
		//Chart section
		$reportData = new pData();
		while ($r=mysql_fetch_array($dailyPurchases)) {
			$store = $r['Store'];
			$purchasesByStoreByDay[$store][$r['date']] = $r['Purchases'];
		}

		foreach ($purchasesByStoreByDay as $storeName => $dailyResults){
			$reportData->addPoints($dailyResults, $storeName);
		}

		$reportData->setAxisName(0,"Purchases");
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

		//EXPORT To EXCEL
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
			->setCategory("Purchases Report");

			// Add some data
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'Purchases Report')
			->setCellValue('A3', 'STORE')
			->setCellValue('B3', 'PURCHASES');

			$a=4;
			//Loop Through The Report Data
			foreach ($resultsPurchases as $resultsPurchases) {
					
				$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$a, $resultsPurchases['Store'])
				->setCellValue('B'.$a, $resultsPurchases['Purchases']);
				$a++;
			}
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
				
				
			// Rename sheet
			$objPHPExcel->getActiveSheet()->setTitle('Purchases');

			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$objPHPExcel->setActiveSheetIndex(0);

			// Redirect output to a client’s web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="PurchaseLinkReport.xls"');
			header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
			exit;

		}

		$interface->setPageTitle('Report - Purchase Tracking');
		$interface->setTemplate('reportPurchase.tpl');
		$interface->display('layout.tpl');

	}

	public function getSortList()
	{
		//loop through the
		$sortList = array();
		$sortList["PurchasesDESC"] = array(
                'column'  => "PurchasesDESC",
                'displayName' => "Purchases DESC",
                'selected' => false );
		$sortList["PurchasesASC"] = array(
                'column'  => "PurchasesASC",
                'displayName' => "Purchases ASC",
                'selected' => false );
		$sortList["StoreASC"] = array(
                'column'  => "StoreASC",
                'displayName' => "Store ASC",
                'selected' => false );
		$sortList["StoreDESC"] = array(
                'column'  => "StoreDESC",
                'displayName' => "Store DESC",
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