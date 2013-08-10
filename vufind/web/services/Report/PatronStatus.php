<?php
/**
 * Creates a report for all patrons of a particular location including
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/8/13
 * Time: 10:24 AM
 */
class Report_PatronStatus extends Action{
	function launch(){
		global $interface;
		$errors = array();
		if (isset($_REQUEST['submit'])){
			//Generate the report
			if (isset($_FILES['patronReport'])){
				$patronReportFile = fopen($_FILES['patronReport']["tmp_name"], 'r');
			}else{
				$errors[] = "Please upload a patron report.";
				$patronReportFile = null;
			}
			if (isset($_FILES['itemReport'])){
				$itemReportFile = fopen($_FILES['itemReport']["tmp_name"], 'r');
			}else{
				$errors[] = "Please upload a item report.";
				$itemReportFile = null;
			}
			if ($patronReportFile && $itemReportFile){
				$this->createPatronStatusReport($patronReportFile, $itemReportFile);
			}
		}

		$interface->assign('errors', $errors);
		$interface->setPageTitle('Patron Status Report');
		$interface->setTemplate('patronStatus.tpl');
		$interface->display('layout.tpl');
	}

	function createPatronStatusReport($patronReportFile, $itemReportFile){
		global $configArray;
		$allPatronBarcodes = array();
		$allHomeLibraries = array();
		//Load patron data into an array keyed by patron barcode
		$this->loadPatronData($patronReportFile, $allPatronBarcodes, $allHomeLibraries, $patronData, $headerRowRead, $patronBarcode, $homeLibrary);

		//Load items into an array keyed by patron barcode
		$this->loadItemData($itemReportFile, $allPatronBarcodes, $allHomeLibraries, $itemData);

		//Sort barcodes by home library and patron name
		asort($allPatronBarcodes);

		//Create the spreadsheet
		require_once ROOT_DIR . '/PHPExcel.php';
		$excel = new PHPExcel();
		// Set properties
		$excel->getProperties()->setCreator($configArray['Site']['title'])
			->setLastModifiedBy($configArray['Site']['title'])
			->setTitle("Patron Status Report " . date('M j Y'))
			->setCategory("Patron Status Report");

		//Create a sheet for each library (as well as all libraries)
		ksort($allHomeLibraries);
		$excel->createSheet(0)->setTitle('All Libraries');
		$curIndex = 0;
		$curRow = array();
		$curRow['all'] = 2;
		$this->addHeaders($excel, 0);
		foreach($allHomeLibraries as $library){
			$curIndex++;
			$excel->createSheet($curIndex)->setTitle($library);
			$allHomeLibraries[$library] = $curIndex;
			$curRow[$library] = 2;
			$this->addHeaders($excel, $curIndex);
		}
		$allHomeLibraries['all'] = 0;

		//Loop through each barcode and extract the appropriate data
		foreach ($allPatronBarcodes as $barcode => $patronName){
			//Get patron information
			$patronInfo = null;
			if (isset($patronData[$barcode])){
				$patronInfo = $patronData[$barcode];
			}
			$itemInfo = null;
			if (isset($itemData[$barcode])){
				$itemInfo = $itemData[$barcode];
			}
			if ($itemInfo == null){
				//We just have patron information
				//write to all sheet
				$curRow['all'] = $this->writePatronInfo($excel, $patronInfo, null, $allHomeLibraries['all'], $curRow['all']);
				//write to library sheet
				$libraryName = trim($patronInfo[3]);
				if (strlen($libraryName) > 0){
					$sheetIndex = $allHomeLibraries[$libraryName];
					$currentRow = $curRow[$libraryName];
					$curRow[$libraryName] = $this->writePatronInfo($excel, $patronInfo, null, $sheetIndex, $currentRow);
				}
			}else{
				foreach($itemInfo as $itemKey => $curItemData){
					//Write to all sheet
					$curRow['all'] = $this->writePatronInfo($excel, $patronInfo, $curItemData, $allHomeLibraries['all'], $curRow['all']);
					//write to library sheet
					$libraryName = trim($curItemData[3]);
					if (strlen($libraryName) > 0){
						$curRow[$libraryName] = $this->writePatronInfo($excel, $patronInfo, $curItemData, $allHomeLibraries[$libraryName], $curRow[$libraryName]);
					}
				}
			}
		}
		$excel->setActiveSheetIndex(0);

		// Redirect output to a client's web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header("Content-Disposition: attachment;filename=Patron_Status_Report_" . date('Y-m-d') . ".xls");
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}

	/**
	 * @param PHPExcel $excel
	 * @param int $sheetIndex
	 */
	private function addHeaders($excel, $sheetIndex) {
		$excel->setActiveSheetIndex($sheetIndex)
	    ->setCellValue('A1', 'P CODE 1')
	    ->setCellValue('B1', 'PATRON NAME')
	    ->setCellValue('C1', 'HOME LIB')
	    ->setCellValue('D1', 'P BARCODE')
	    ->setCellValue('E1', 'GRD LVL')
			->setCellValue('F1', 'HOME ROOM')
	    ->setCellValue('G1', '$ OWED')
	    ->setCellValue('H1', 'CALL #')
	    ->setCellValue('I1', 'TITLE')
	    ->setCellValue('J1', 'ITEM BARCODE')
	    ->setCellValue('K1', 'ITEM LOC')
	    ->setCellValue('L1', 'DUE DATE')
	    ->setCellValue('M1', 'STAT');
	}

	/**
	 * @param PHPExcel $excel
	 * @param string[] $patronInfo
	 * @param string[] $itemInfo
	 * @param int[] $sheetIndex
	 * @param int[] $curRow
	 *
	 * @return int
	 */
	private function writePatronInfo($excel, $patronInfo, $itemInfo, $sheetIndex, $curRow) {
		$curSheet = $excel->setActiveSheetIndex($sheetIndex);
		if ($patronInfo != null){
			$curSheet->setCellValueByColumnAndRow(0, $curRow, $patronInfo[1]); //P Code 1
			$curSheet->setCellValueByColumnAndRow(1, $curRow, $patronInfo[2]); //Patron name
			$curSheet->setCellValueByColumnAndRow(2, $curRow, $patronInfo[3]); //Home library
			$curSheet->setCellValueByColumnAndRow(3, $curRow, $patronInfo[4]); //Patron barcode
			$curSheet->setCellValueByColumnAndRow(4, $curRow, $patronInfo[5]); //Grade Level
			$curSheet->setCellValueByColumnAndRow(5, $curRow, $patronInfo[6]); //Home room
			$curSheet->setCellValueByColumnAndRow(6, $curRow, $patronInfo[7]); //$ owed
		}
		if ($itemInfo != null){
			if ($patronInfo == null){
				$curSheet->setCellValueByColumnAndRow(0, $curRow, $itemInfo[1]); //P Code 1
				$curSheet->setCellValueByColumnAndRow(1, $curRow, $itemInfo[2]); //Patron name
				$curSheet->setCellValueByColumnAndRow(2, $curRow, $itemInfo[3]); //Home library
				$curSheet->setCellValueByColumnAndRow(3, $curRow, $itemInfo[4]); //Patron barcode
				$curSheet->setCellValueByColumnAndRow(4, $curRow, $itemInfo[5]); //Grade Level
				$curSheet->setCellValueByColumnAndRow(5, $curRow, $itemInfo[6]); //Home room
				$curSheet->setCellValueByColumnAndRow(6, $curRow, $itemInfo[7]); //$ owed
			}
			$curSheet->setCellValueByColumnAndRow(7, $curRow, $itemInfo[8]); //call #
			$curSheet->setCellValueByColumnAndRow(8, $curRow, $itemInfo[9]); //title
			$curSheet->setCellValueByColumnAndRow(9, $curRow, $itemInfo[10]); //item barcode
			$curSheet->setCellValueByColumnAndRow(10, $curRow, $itemInfo[11]); //item location
			$curSheet->setCellValueByColumnAndRow(11, $curRow, $itemInfo[12]); //due date
			$curSheet->setCellValueByColumnAndRow(12, $curRow, $itemInfo[13]); //stat
		}
		return ++$curRow;
	}

	/**
	 * @param $patronReportFile
	 * @param $allPatronBarcodes
	 * @param $allHomeLibraries
	 * @param $patronData
	 * @param $headerRowRead
	 * @param $patronBarcode
	 * @param $homeLibrary
	 */
	public function loadPatronData($patronReportFile, &$allPatronBarcodes, &$allHomeLibraries, &$patronData, &$headerRowRead, &$patronBarcode, &$homeLibrary) {
		$patronData = array();
		$headerRowRead = false;
		while (($patronDataRow = fgetcsv($patronReportFile, 1000, ",", '"', ';')) !== FALSE) {
			//Skip the header
			if (!$headerRowRead) {
				$headerRowRead = true;
				continue;
			}
			if (count($patronDataRow) >= 7) {
				$patronBarcode = trim($patronDataRow[4]);
				if (strlen($patronBarcode) > 0) {
					$homeLibrary = trim($patronDataRow[3]);
					$allPatronBarcodes[$patronBarcode] = $homeLibrary . '-' . $patronDataRow[2];
					$patronData[$patronBarcode] = $patronDataRow;
					$allHomeLibraries[$homeLibrary] = $homeLibrary;
				}
			}
		}
		fclose($patronReportFile);
	}

	/**
	 * @param $itemReportFile
	 * @param $allPatronBarcodes
	 * @param $allHomeLibraries
	 * @param $itemData
	 */
	public function loadItemData($itemReportFile, &$allPatronBarcodes, &$allHomeLibraries, &$itemData) {
		$itemData = array();
		$headerRowRead = false;
		while (($itemDataRowRaw = fgets($itemReportFile)) !== FALSE) {
			//Manually parse the line because iii exports in a format that isn't true csv
			$itemDataRow = array();
			$inField = false;
			$curFieldIndex = -1;
			$curFieldValue = "";
			for ($i = 0; $i < strlen($itemDataRowRaw); $i++){
				$curChar = $itemDataRowRaw[$i];
				if ($curChar == '"'){
					if ($inField){
						$inField = false;
					}else{
						$inField = true;
						$curFieldValue = "";
						$curFieldIndex++;
					}
				}elseif ($curChar == ';'){
					if (!$inField){
						$inField = true;
						$curFieldIndex--;
						$curFieldValue = $itemDataRow[$curFieldIndex];
						$i++;
					}else{
						$curFieldValue .= $curChar;
					}
				}elseif ($curChar == ','){
					if (!$inField){
						$itemDataRow[$curFieldIndex] = $curFieldValue;
					}else{
						$curFieldValue .= $curChar;
					}
				}else{
					$curFieldValue .= $curChar;
				}
			}
			//Skip the header
			if (!$headerRowRead) {
				$headerRowRead = true;
				continue;
			}
			//Periodically, an item has multiple titles separated by semi-colons.  This causes issues so we need to normalize the data.
			foreach ($itemDataRow as $col => $value){
				if (strpos($value, ';') > 0){
					$value = substr($value, 0, strpos($value, ';'));
					$itemDataRow[$col] = $value;
				}
			}

			if (count($itemDataRow) >= 13) {
				$patronBarcode = trim($itemDataRow[4]);
				if (strlen($patronBarcode) > 0) {
					if (!array_key_exists($itemDataRow[4], $itemData)) {
						$itemData[$patronBarcode] = array();
					}
					$homeLibrary = trim($itemDataRow[3]);
					$allPatronBarcodes[$patronBarcode] = $homeLibrary . '-' . $itemDataRow[2];
					$allHomeLibraries[$homeLibrary] = $homeLibrary;
					$itemData[$patronBarcode][] = $itemDataRow;
				}
			}
		}
		fclose($itemReportFile);
	}
}