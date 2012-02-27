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
require_once("sys/pChart/class/pData.class.php");
require_once("sys/pChart/class/pDraw.class.php");
require_once("sys/pChart/class/pImage.class.php");
require_once 'sys/Pager.php';
require_once("PHPExcel.php");

class ItemlessEContent extends Admin
{
	function launch()
	{
		global $interface;

		$interface->setTemplate('itemlessEcontent.tpl');
		$interface->setPageTitle('Itemless eContent');

		//Source Filter
		$interface->assign('resultsSourceFilter', $this->getSourceFilter());
		$selectedSourceFilter = null;
		if (isset($_REQUEST['sourceFilter'])){
			$selectedSourceFilter = array();
			$selectedSourceFilter = $_REQUEST['sourceFilter'];
		}
		$interface->assign('selectedSourceFilter', $selectedSourceFilter);

		//Load the list of eContent without items
		$eContentRecord = new EContentRecord();
		$sourceRestriction = " ";
		if (isset($_REQUEST['sourceFilter'])){
			$sourcesToShow = $_REQUEST['sourceFilter'];
			foreach ($sourcesToShow as $key=>$item){
				$sourcesToShow[$key] = "'" . mysql_escape_string(strip_tags($item)) . "'";
			}
			$sourceRestriction = " AND source IN (" . join(",", $sourcesToShow) . ") ";
		}
		
		//Get a list of econtent records that do have items
		$recordsWithItems = array();
		$eContentRecord->query("SELECT DISTINCT econtent_item.recordId from econtent_item inner join econtent_record on econtent_record.id = econtent_item.recordId $sourceRestriction");
		while ($eContentRecord->fetch()){
			$recordsWithItems[$eContentRecord->recordId] = $eContentRecord->recordId;
		}
		
		$eContentRecord->query("SELECT econtent_record.id, title, author, isbn, ilsId, source FROM econtent_record WHERE source != 'OverDrive' and status = 'active' $sourceRestriction");
		$itemlessRecords = array();
		while ($eContentRecord->fetch()){
			if (!array_key_exists($eContentRecord->id, $recordsWithItems)){
				$itemlessRecords[] = clone($eContentRecord);
			}
		}
		$interface->assign('itemlessRecords', $itemlessRecords);
		
		//EXPORT To EXCEL
		if (isset($_REQUEST['exportToExcel'])) {
			$this->exportToExcel($itemlessRecords);
		}

		$interface->setTemplate('itemlessEContent.tpl');
		$interface->display('layout.tpl');
	}

	function getSourceFilter(){
		$eContentRecord = new EContentRecord();
		//Populate the Source Filter
		$querySourceFilter = "SELECT DISTINCT source AS SourceValue FROM econtent_record WHERE source IS NOT NULL AND source <> '' AND source <> 'OverDrive' ".
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
		$objPHPExcel->getProperties()->setCreator("VuFind")
			->setLastModifiedBy("VuFind")
			->setTitle("Office 2007 XLSX Document")
			->setSubject("Office 2007 XLSX Document")
			->setDescription("Office 2007 XLSX, generated using PHP.")
			->setKeywords("office 2007 openxml php")
			->setCategory("Itemless eContent Report");

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'Itemless eContent')
			->setCellValue('A3', 'ID')
			->setCellValue('B3', 'Title')
			->setCellValue('C3', 'Author')
			->setCellValue('D3', 'ISBN')
			->setCellValue('E3', 'ILS Id')
			->setCellValue('F3', 'Source');

		$a=4;
		//Loop Through The Report Data
		foreach ($itemlessRecords as $itemlessRecord) {
				
			$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$a, $itemlessRecord->id)
				->setCellValue('B'.$a, $itemlessRecord->title)
				->setCellValue('C'.$a, $itemlessRecord->author)
				->setCellValue('D'.$a, $itemlessRecord->isbn)
				->setCellValue('E'.$a, $itemlessRecord->ilsId)
				->setCellValue('F'.$a, $itemlessRecord->source);
			$a++;
		}
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
			
		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('Itemless eContent');

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename=ItemlessEContentReport.xls');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}