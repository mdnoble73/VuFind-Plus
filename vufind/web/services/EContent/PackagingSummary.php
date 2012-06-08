<?php
/**
 *
 * Copyright (C) Villanova University 2010.
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
require_once('services/Admin/Admin.php');
require_once('sys/eContent/PackagingDetailsEntry.php');
require_once("sys/pChart/class/pData.class.php");
require_once("sys/pChart/class/pDraw.class.php");
require_once("sys/pChart/class/pImage.class.php");
require_once("PHPExcel.php");

class PackagingSummary extends Admin {

	function launch()
	{
		global $configArray;
		global $interface;
		global $user;

		// Distributor Filter
		$allDistributors = $this->getAllDistributors();
		$interface->assign('distributorFilter', $allDistributors);
		$selectedDistributorFilter = null;
		if (isset($_REQUEST['distributorFilter'])){
			$selectedDistributorFilter = $_REQUEST['distributorFilter'];
		}
		$interface->assign('selectedDistributorFilter', $selectedDistributorFilter);
		$distributors = empty($selectedDistributorFilter) ? $allDistributors : $selectedDistributorFilter;
		$interface->assign('distributors', $distributors);

		// Date range filter
		$period = isset($_REQUEST['period']) ? $_REQUEST['period'] : 'week';
		if ($period == 'week'){
			$periodLength  = new DateInterval("P1W");
		}elseif ($period == 'day'){
			$periodLength = new DateInterval("P1D");
		}elseif ($period == 'month'){
			$periodLength = new DateInterval("P1M");
		}elseif ($period == 'year'){
			$periodLength = new DateInterval("P1Y");
		}
		$interface->assign('period', $period);

		$endDate = (isset($_REQUEST['endDate']) && strlen($_REQUEST['endDate']) > 0) ? DateTime::createFromFormat('m/d/Y', $_REQUEST['endDate']) : new DateTime();
		$interface->assign('endDate', $endDate->format('m/d/Y'));

		if (isset($_REQUEST['startDate']) && strlen($_REQUEST['startDate']) > 0){
			$startDate = DateTime::createFromFormat('m/d/Y', $_REQUEST['startDate']);
		} else{
			if ($period == 'day'){
				$startDate = new DateTime($endDate->format('m/d/Y') . " - 7 days");
			}elseif ($period == 'week'){
				//Get the sunday after this
				$endDate->setISODate($endDate->format('Y'), $endDate->format("W"), 0);
				$endDate->modify("+7 days");
				$startDate = new DateTime($endDate->format('m/d/Y') . " - 28 days");
			}elseif ($period == 'month'){
				$endDate->modify("+1 month");
				$numDays = $endDate->format("d");
				$endDate->modify(" -$numDays days");
				$startDate = new DateTime($endDate->format('m/d/Y') . " - 6 months");
			}elseif ($period == 'year'){
				$endDate->modify("+1 year");
				$numDays = $endDate->format("m");
				$endDate->modify(" -$numDays months");
				$numDays = $endDate->format("d");
				$endDate->modify(" -$numDays days");
				$startDate = new DateTime($endDate->format('m/d/Y') . " - 2 years");
			}
		}

		$interface->assign('startDate', $startDate->format('m/d/Y'));

		//Set the end date to the end of the day
		$endDate->setTime(24, 0, 0);
		$startDate->setTime(0, 0, 0);

		//Create the periods that are being represented
		$periods = array();
		$periodEnd = clone $endDate;
		while ($periodEnd >= $startDate){
			array_unshift($periods, clone $periodEnd);
			$periodEnd->sub($periodLength);
		}

		// create a SQL clause to filter by selected distributors
		$distributorRestriction = null;
		if (isset($_REQUEST['distributorFilter'])){
			$distributorsToShow = array();
			foreach ($_REQUEST['distributorFilter'] as $id) {
				if (is_numeric($id)) {
					$distributorsToShow[] = $id;
				}
			}
			if (!empty($distributorsToShow)) {
				$distributorRestriction = "distributorId IN (" . implode(",", $distributorsToShow) . ") ";
			}
		}

		//Load data for each period
		$periodDataByDistributor = array();
		$periodDataByStatus = array();
		for ($i = 0; $i < count($periods) - 1; $i++){
			$periodStart = clone $periods[$i];
			//$periodStart->setTime(0,0,0);
			$periodEnd = clone $periods[$i+1];
			//$periodStart->setTime(23, 59, 59);
			$periodDataByDistributor[$periodStart->getTimestamp()] = array();
			$periodDataByStatus[$periodStart->getTimestamp()] = array();
				
			//Determine how many files were imported by distributor
			$packagingDetails = new PackagingDetailsEntry();
			$packagingDetails->selectAdd();
			$packagingDetails->selectAdd('COUNT(id) as numberOfFiles, distributorId');
			$packagingDetails->whereAdd('created >= ' . $periodStart->getTimestamp() . ' AND created < ' . $periodEnd->getTimestamp());
			if ($distributorRestriction) {
				$packagingDetails->whereAdd($distributorRestriction);
			}
			$packagingDetails->groupBy('distributorId');
			$packagingDetails->addOrder('distributorId');
			$packagingDetails->find();
			while ($packagingDetails->fetch()){
				$periodDataByDistributor[$periodStart->getTimestamp()][$packagingDetails->distributorId] = $packagingDetails->numberOfFiles;
			}

			//Determine how many files were imported by status
			$packagingDetails = new PackagingDetailsEntry();
			$packagingDetails->selectAdd();
			$packagingDetails->selectAdd('COUNT(id) as numberOfFiles, status');
			$packagingDetails->whereAdd('created >= ' . $periodStart->getTimestamp() . ' AND created < ' . $periodEnd->getTimestamp());
			if ($distributorRestriction) {
				$packagingDetails->whereAdd($distributorRestriction);
			}
			$packagingDetails->groupBy('status');
			$packagingDetails->addOrder('status');
			$packagingDetails->find();
			while ($packagingDetails->fetch()){
				$periodDataByStatus[$periodStart->getTimestamp()][$packagingDetails->status] = $packagingDetails->numberOfFiles;
			}
		}
		$interface->assign('periodDataByDistributor', $periodDataByDistributor);
		$interface->assign('periodDataByStatus', $periodDataByStatus);

		//Get a list of all of the statuses that will be shown
		$statusesUntranslated = $this->getStatuses();
		$statuses = array();
		foreach ($statusesUntranslated as $status){
			$statuses[$status] = translate($status);
		}
		$interface->assign('statuses', $statuses);

		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])){
			$this->exportToExcel($periodDataByDistributor, $distributors, $periodDataByStatus, $statuses);
		}else{
			//Generate the graphs
			$this->generateGraphByDistributor($periodDataByDistributor, $periods, $distributors);
			$this->generateGraphByStatus($periodDataByStatus, $periods, $statuses);
		}

		$interface->setTemplate('packagingSummary.tpl');
		$interface->setPageTitle('Packaging Summary Report');
		$interface->display('layout.tpl');
	}

	function exportToExcel($periodDataByDistributor, $distributors, $periodDataByStatus, $statuses){
		global $configArray;
		//PHPEXCEL
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator($configArray['Site']['title'])
		->setLastModifiedBy($configArray['Site']['title'])
		->setTitle("Office 2007 XLSX Document")
		->setSubject("Office 2007 XLSX Document")
		->setDescription("Office 2007 XLSX, generated using PHP.")
		->setKeywords("office 2007 openxml php")
		->setCategory("Packaging Summary Report");

		// Add period data by Distributor
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		$activeSheet->setCellValue('A1', 'Packaging Summary by Distributor');
		$activeSheet->setCellValue('A3', 'Date');
		$column = 1;
		foreach ($distributors as $distributor){
			$activeSheet->setCellValueByColumnAndRow($column++, 3, $distributor);
		}
		
		$row = 4;
		$column = 0;
		//Loop Through The Report Data
		foreach ($periodDataByDistributor as $date => $periodInfo) {
			$activeSheet->setCellValueByColumnAndRow($column++, $row, date('M j, Y', $date));
			foreach ($distributors as $distributor){
				$activeSheet->setCellValueByColumnAndRow($column++, $row, isset($periodInfo[$distributor]) ? $periodInfo[$distributor] : 0);
			}
			$row++;
			$column = 0;
		}
		for ($i = 0; $i < count($distributors) + 1; $i++){
			$activeSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
		}
				
		// skip 5 rows to create some spaces 
		$row += 5;
		
		// Add period data by Status
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		$activeSheet->setCellValue('A'.$row, 'Packaging Summary by Status');
		$activeSheet->setCellValue('A'.($row+2), 'Date');
		$column = 1;
		foreach ($statuses as $status => $statusLabel){
			$activeSheet->setCellValueByColumnAndRow($column++, $row+2, $statusLabel);
		}
		
		$row += 3;
		$column = 0;
		//Loop Through The Report Data
		foreach ($periodDataByStatus as $date => $periodInfo) {
			$activeSheet->setCellValueByColumnAndRow($column++, $row, date('M j, Y', $date));
			foreach ($statuses as $status => $statusLabel){
				$activeSheet->setCellValueByColumnAndRow($column++, $row, isset($periodInfo[$status]) ? $periodInfo[$status] : 0);
			}
			$row++;
			$column = 0;
		}
		for ($i = 0; $i < count($statuses) + 1; $i++){
			$activeSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
		}
		
		// Rename sheet
		$activeSheet->setTitle('Packaging Summary Report');

		// Redirect output to a client�s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="PackagingSummaryReport.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;

	}

	function generateGraphByDistributor($periodData, $periods, $distributors) {
		global $configArray;
		global $interface;
		$reportData = new pData();

		//Add points for each distributor
		$periodsFormatted = array();
		foreach ($distributors as $distributor){
			$distributorData = array();
			foreach ($periodData as $date => $periodInfo){
				$periodsFormatted[$date] = date('M-d-Y', $date);
				$distributorData[$date] = isset($periodInfo[$distributor]) ? $periodInfo[$distributor] : 0;
			}
			$reportData->addPoints($distributorData, $distributor);
		}

		$reportData->setAxisName(0,"Number of files");

		$reportData->addPoints($periodsFormatted, "Dates");
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
		$chartHref = "/images/charts/PackagingSummaryByDistributor". time() . ".png";
		$chartPath = $configArray['Site']['local'] . $chartHref;
		$myPicture->render($chartPath);
		$interface->assign('chartByDistributor', $chartHref);
	}

	function generateGraphByStatus($periodData, $periods, $statuses){
		global $configArray;
		global $interface;
		$reportData = new pData();

		//Add points for each status
		$periodsFormatted = array();
		foreach ($statuses as $status => $statusLabel){
			$statusData = array();
			foreach ($periodData as $date => $periodInfo){
				$periodsFormatted[$date] = date('M-d-Y', $date);
				$statusData[$date] = isset($periodInfo[$status]) ? $periodInfo[$status] : 0;
			}
			$reportData->addPoints($statusData, $status);
		}

		$reportData->setAxisName(0,"Number of files");

		$reportData->addPoints($periodsFormatted, "Dates");
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
		$chartHref = "/images/charts/eContentImportSummaryByStatus". time() . ".png";
		$chartPath = $configArray['Site']['local'] . $chartHref;
		$myPicture->render($chartPath);
		$interface->assign('chartByStatus', $chartHref);
	}

	function getAllDistributors() {
		$packagingDetails = new PackagingDetailsEntry();
		$packagingDetails->query('SELECT DISTINCT distributorId FROM ' . $packagingDetails->__table . ' ORDER BY distributorId');
		$distributors = array();
		while ($packagingDetails->fetch()){
			$distributors[$packagingDetails->distributorId] = $packagingDetails->distributorId;
		}
		return $distributors;
	}

	function getStatuses() {
		$packagingDetails = new PackagingDetailsEntry();
		$packagingDetails->query('SELECT DISTINCT status FROM ' . $packagingDetails->__table . ' ORDER BY status');
		$statuses = array();
		while ($packagingDetails->fetch()){
			$statuses[] = $packagingDetails->status;
		}
		return $statuses;
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}