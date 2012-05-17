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

class EContentWishListReport extends Admin
{
	function launch()
	{
		global $interface;

		$interface->setPageTitle('eContent WishList');

		//Load the list of eContent Records that people have added to their wishlist
		$eContentRecord = new EContentRecord();
		$eContentRecord->query("SELECT econtent_record.id, title, author, source, ilsId, isbn, count(DISTINCT econtent_wishlist.userId) as numWishList FROM econtent_record INNER JOIN econtent_wishlist on econtent_record.id = econtent_wishlist.recordId WHERE econtent_wishlist.status = 'active' GROUP BY econtent_record.id ORDER BY numWishList DESC, title ASC");
		$recordsOnWishList = array();
		while ($eContentRecord->fetch()){
			$recordsOnWishList[] = clone($eContentRecord);
		}
		$interface->assign('recordsOnWishList', $recordsOnWishList);
		
		//EXPORT To EXCEL
		if (isset($_REQUEST['exportToExcel'])) {
			$this->exportToExcel($recordsOnWishList);
		}

		$interface->setTemplate('econtentWishList.tpl');
		$interface->display('layout.tpl');
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
			->setCategory("eContent Wish List Report");

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'eContent Wish List Report')
			->setCellValue('A3', 'ID')
			->setCellValue('B3', 'Title')
			->setCellValue('C3', 'Author')
			->setCellValue('D3', 'ISBN')
			->setCellValue('E3', 'ILS Id')
			->setCellValue('F3', 'Source')
			->setCellValue('G3', 'Wishlist Size');

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
				->setCellValue('G'.$a, $itemlessRecord->numWishList);
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
		$objPHPExcel->getActiveSheet()->setTitle('Wish List');

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
