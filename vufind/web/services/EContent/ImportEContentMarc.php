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
require_once 'sys/Pager.php';

class ImportEContentMarc extends Admin
{
	function launch()
	{
		global $interface;
		global $configArray;

		$interface->setPageTitle('Import eContent from Marc');
		
		
		if (isset($_REQUEST['submit'])){
			$errors = array();
			//Get the marc file to be processed
			$marcCopyResult = false;
			if ($_FILES['marcFile']["error"] > 0){
				$errors[] = "Error reading marc file, please upload a valid marc file.";
			}else{
				$destFolder = $configArray['EContent']['marcPath'];
				$destFile = $_FILES['marcFile']["name"];
				$destFullPath = $destFolder . '/' . $destFile;
				$uniqueNumber = 1;
				while (file_exists($destFullPath)){
					$destFullPath = $destFolder . '/' . $destFile . '.' . $uniqueNumber++;
				}
				$marcCopyResult = copy($_FILES['marcFile']["tmp_name"], $destFullPath);
				if (!$marcCopyResult){
					$errors[] = "Could not copy the marc file for processing";
				}
			}
			
			//Get the supplemental csv with collection information etc
			$supplementalCopyResult= false;
			$hasSupplementalCsv = true;
			if ($_FILES['suplementalCSV']["error"] == UPLOAD_ERR_NO_FILE){
				$hasSupplementalCsv = false;
				$supplementalCopyResult = true;
			}else if ($_FILES['suplementalCSV']["error"] > 0){
				$errors[] = "Error reading supplemental CSV file, please upload a valid supplemental CSV file.";
			}else{
				$destFolder = $configArray['EContent']['marcPath'];
				$destSupplementalFile = $_FILES['suplementalCSV']["name"];
				$supplementalFullPath = $destFolder . '/' . $destSupplementalFile;
				$uniqueNumber = 1;
				while (file_exists($supplementalFullPath)){
					$supplementalFullPath = $destFolder . '/' . $destSupplementalFile . '.' . $uniqueNumber++;
				}
				$supplementalCopyResult = copy($_FILES['suplementalCSV']["tmp_name"], $supplementalFullPath);
				if (!$supplementalCopyResult){
					$errors[] = "Could not copy the supplemental CSV file for processing";
				}
			}
			
			if ($marcCopyResult && $supplementalCopyResult){
				//Get import information
				$cronPath = $configArray['Site']['cronPath']; 
				if (file_exists($cronPath) && is_dir($cronPath)){
					global $servername;
					if ($configArray['System']['operatingSystem'] == 'windows'){
						$commandToRun = "cd $cronPath && start /b java -jar cron.jar $servername org.epub.ImportMarcRecord";
					}else{
						$commandToRun = "cd {$cronPath}; java -jar cron.jar $servername org.epub.ImportMarcRecord";
					}
					//Set the servername 
					$commandToRun .= " marcFile=" . escapeshellarg($destFullPath);
					if ($hasSupplementalCsv){
						$commandToRun .= " supplementalFile=" . escapeshellarg($supplementalFullPath);
					}
					$commandToRun .= " source=" . escapeshellarg($_REQUEST['source']);
					$commandToRun .= " accessType=" . escapeshellarg($_REQUEST['accessType']);
					$logger = new Logger();
					$logger->log("importing marc records $commandToRun", PEAR_LOG_INFO);
					//$commandToRun .= " > process.out 2> process.err < /dev/null &";
					$handle = popen($commandToRun, 'r');
					if ($handle == false){
						$errors[] = "Unable to start process $commandToRun";
					}else{
						pclose($handle);
						header("Location: {$configArray['Site']['path']}/EContent/MarcImportLog");
						exit();
					}
				}else{
					$errors[] = "Cron path $cronPath is not valid.  Please check the config file.";
				}
			}
			$interface->assign('errors', $errors);
		}
		
		$interface->setTemplate('importEContentMarc.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}
