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
require_once("PHPExcel.php");

class EContentPurchaseAlert extends Admin
{
	function launch()
	{
		global $interface;
		global $configArray;
		
		$purchaseRatio = $configArray['EContent']['holdRatioForPurchase'];

		$interface->setPageTitle('eContent Purchase Alert');

		//Load the list of eContent Reocrds that have more than 4 times as many holds as items
		$eContentRecord = new EContentRecord();
		if (isset($_REQUEST['sourceFilter'])){
			$sourcesToShow = $_REQUEST['sourceFilter'];
			foreach ($sourcesToShow as $key=>$item){
				$sourcesToShow[$key] = "'" . mysql_escape_string(strip_tags($item)) . "'";
			}
			$sourceRestriction = " WHERE source IN (" . join(",", $sourcesToShow) . ") ";
		}
		$eContentRecord->query("SELECT econtent_record.id, title, author, isbn, ilsId, source, count(econtent_hold.id) as numHolds, availableCopies, onOrderCopies " .
		    "FROM econtent_record " .
		    "LEFT JOIN econtent_hold ON econtent_record.id = econtent_hold.recordId " .
		    "WHERE econtent_hold.status " .
		    "IN ( " .
		    "'active', 'suspended' " .
		    ") " .
		    "GROUP BY econtent_record.id");
		$recordsToPurchase = array();
		while ($eContentRecord->fetch()){
			$totalCopies = $eContentRecord->availableCopies + $eContentRecord->onOrderCopies;
			if ($eContentRecord->numHolds > $purchaseRatio * $totalCopies){
				$eContentRecord->totalCopies = $totalCopies;
				$recordsToPurchase[] = clone($eContentRecord);
			}
		}
		$interface->assign('recordsToPurchase', $recordsToPurchase);

		//EXPORT To EXCEL
		if (isset($_REQUEST['exportToExcel'])) {
			$this->exportToExcel($recordsToPurchase);
		}
		
		$interface->setTemplate('econtentPurchaseAlert.tpl');
		$interface->display('layout.tpl');
	}

	function exportToExcel($recordsToPurchase){
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
			->setCategory("eContent Purchase Alert");

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'eContent Purchase Alert')
			->setCellValue('A3', 'ID')
			->setCellValue('B3', 'Title')
			->setCellValue('C3', 'Author')
			->setCellValue('D3', 'ISBN')
			->setCellValue('E3', 'ILS Id')
			->setCellValue('F3', 'Source')
			->setCellValue('G3', 'Total Copies')
			->setCellValue('H3', 'Num Holds');

		$a=4;
		//Loop Through The Report Data
		foreach ($recordsToPurchase as $recordToPurchase) {
				
			$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$a, $recordToPurchase->id)
				->setCellValue('B'.$a, $recordToPurchase->title)
				->setCellValue('C'.$a, $recordToPurchase->author)
				->setCellValue('D'.$a, $recordToPurchase->isbn)
				->setCellValue('E'.$a, $recordToPurchase->ilsId)
				->setCellValue('F'.$a, $recordToPurchase->source)
				->setCellValue('G'.$a, $recordToPurchase->totalCopies)
				->setCellValue('H'.$a, $recordToPurchase->numHolds);
			$a++;
		}
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
			
			
		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle('Purchase Alert');

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename=EContentWishListReport.xls');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}
	
	function getAllowableRoles(){
		return array('epubAdmin');
	}
}
