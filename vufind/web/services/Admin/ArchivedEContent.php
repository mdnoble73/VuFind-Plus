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
require_once 'services/Admin/Admin.php';
require_once 'sys/eContent/EContentRecord.php';
require_once 'sys/Pager.php';

class ArchivedEContent extends Admin
{
	function launch()
	{
		global $interface;

		$interface->setTemplate('archivedEcontent.tpl');
		$interface->setPageTitle('Archived eContent');
		
		$today = time();
		//Grab the Selected Date Start
		if (isset($_REQUEST['dateFilterStart'])){
			$selectedDateStart = $_REQUEST['dateFilterStart'];
		} else {
			$selectedDateStart = strtotime('-30 days');
			$selectedDateStart = date('m/d/Y', $selectedDateStart);
		}
		$interface->assign('selectedDateStart', $selectedDateStart);

		//Grab the Selected End Date
		if (isset($_REQUEST['dateFilterEnd'])){
			$selectedDateEnd = $_REQUEST['dateFilterEnd'];
		} else {
			$selectedDateEnd = strtotime('now');
			$selectedDateEnd = date('m/d/Y', $selectedDateEnd);
		}
		$interface->assign('selectedDateEnd', $selectedDateEnd);

		//Source Filter
		$interface->assign('resultsSourceFilter', $this->getSourceFilter());
		$selectedSourceFilter = null;
		if (isset($_REQUEST['sourceFilter'])){
			$selectedSourceFilter = array();
			$selectedSourceFilter = $_REQUEST['sourceFilter'];
		}
		$interface->assign('selectedSourceFilter', $selectedSourceFilter);

		//Load the list of records that were archived
		$eContentRecord = new EContentRecord();
		$sourceRestriction = " ";
		if (isset($_REQUEST['sourceFilter'])){
			$sourcesToShow = $_REQUEST['sourceFilter'];
			foreach ($sourcesToShow as $key=>$item){
				$sourcesToShow[$key] = "'" . mysql_escape_string(strip_tags($item)) . "'";
			}
			$sourceRestriction = " AND source IN (" . join(",", $sourcesToShow) . ") ";
		}
		
		$startDateSqlFormatted = strtotime($selectedDateStart);
		$endDateSqlFormatted = strtotime($selectedDateEnd) + 24 * 3600; //Make sure that we are at the end of the day rather than the beginning
		$dateRestriction = " AND date_updated BETWEEN ". $startDateSqlFormatted . " AND ". $endDateSqlFormatted;
	
		$query = "SELECT econtent_record.id, title, author, isbn, ilsId, source, date_updated FROM econtent_record WHERE status = 'archived' $dateRestriction $sourceRestriction";
		$eContentRecord->query($query);
		
		$archivedRecords = array();
		while ($eContentRecord->fetch()){
			$archivedRecords[] = clone($eContentRecord);
		}
		$interface->assign('archivedRecords', $archivedRecords);
		
		//EXPORT To EXCEL
		if (isset($_REQUEST['exportToExcel'])) {
			$this->exportToExcel($archivedRecords);
		}

		$interface->setTemplate('archivedEContent.tpl');
		$interface->display('layout.tpl');
	}

	function getSourceFilter(){
		$eContentRecord = new EContentRecord();
		//Populate the Source Filter
		$querySourceFilter = "SELECT DISTINCT source AS SourceValue FROM econtent_record WHERE source IS NOT NULL AND source <> ''".
			"ORDER BY SourceValue ASC";
		$eContentRecord->query($querySourceFilter);

		$resultsSourceFilter = array();
		$i=0;
		while ($eContentRecord->fetch()) {
			$tmp = array(
		    'SourceValue' => $eContentRecord->SourceValue
			);
			$resultsSourceFilter[$i++] = $tmp;
		}
			
		return $resultsSourceFilter;
	}
	
	function exportToExcel($itemlessRecords){
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
			->setCategory("Archived eContent Report");

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'Archived eContent')
			->setCellValue('A3', 'ID')
			->setCellValue('B3', 'Title')
			->setCellValue('C3', 'Author')
			->setCellValue('D3', 'ISBN')
			->setCellValue('E3', 'ILS Id')
			->setCellValue('F3', 'Source')
			->setCellValue('G3', 'Date Archived');

		$a=4;
		//Loop Through The Report Data
		foreach ($itemlessRecords as $itemlessRecord) {
				
			$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$a, $itemlessRecord->id)
				->setCellValue('B'.$a, $itemlessRecord->title)
				->setCellValue('C'.$a, $itemlessRecord->author)
				->setCellValue('D'.$a, $itemlessRecord->isbn)
				->setCellValue('E'.$a, $itemlessRecord->ilsId)
				->setCellValue('F'.$a, $itemlessRecord->source)
				->setCellValue('G'.$a, date('m/d/Y', $itemlessRecord->date_updated));
			$a++;
		}
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
			
		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('Archived eContent');

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename=ArchivedEContentReport.xls');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}