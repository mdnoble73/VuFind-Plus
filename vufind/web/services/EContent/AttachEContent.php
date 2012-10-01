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
require_once 'sys/eContent/EContentRecord.php';
require_once 'sys/eContent/EContentAttachmentLogEntry.php';

class AttachEContent extends Admin
{
	function launch()
	{
		global $interface;
		global $configArray;

		$interface->setPageTitle('Attach eContent files to records.');
		
		
		if (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Attach eContent'){
			$errors = array();
			//Get the source folder to process
			$source = $_REQUEST['sourcePath'];
			if (!file_exists($source) && !is_dir($source)){
				$errors[] = "Sorry, we could not find a directory with that name to import files from.";
			}else{
				//Get import information
				global $servername;
				$cronPath = $configArray['Site']['cronPath']; 
				if ($configArray['System']['operatingSystem'] == 'windows'){
					$commandToRun = "cd $cronPath && start /b java -jar cron.jar $servername org.epub.AttachEContent";
				}else{
					$commandToRun = "cd {$cronPath}; java -jar cron.jar $servername org.epub.AttachEContent";
				}
				$commandToRun .= " source=\"" . $source . "\"";
				
				global $logger;
				$logger->log("attaching eContent $commandToRun", PEAR_LOG_INFO);
				//$commandToRun .= " > process.out 2> process.err < /dev/null &";
				$handle = popen($commandToRun, 'r');
				pclose($handle);
				header("Location: {$configArray['Site']['path']}/EContent/AttachEContentLog");
				exit();
			}
		}elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Process External Links'){
			$source = $_REQUEST['source'];
			$this->processExternalLinks($source);
			header("Location: {$configArray['Site']['path']}/EContent/AttachEContentLog");
			exit();
		}
		
		//Load source filter
		$sourceFilter = array();
		$sources = $this->loadEContentSources();
		$interface->assign('sourceFilter', $sources);

		$interface->setTemplate('attachEContent.tpl');
		$interface->display('layout.tpl');
	}
	
	function processExternalLinks($source){
		$eContentAttachmentLogEntry = new EContentAttachmentLogEntry();
		$eContentAttachmentLogEntry->dateStarted = time();
		$eContentAttachmentLogEntry->sourcePath = 'Attaching External links to ' . $source;
		$eContentAttachmentLogEntry->recordsProcessed = 0;
		$eContentAttachmentLogEntry->insert();
		//Get a list of all records that do not have items for the source
		$econtentRecord = new EContentRecord();
		$econtentRecord->source = $source;
		$econtentRecord->find();
		while ($econtentRecord->fetch()){
			if ($econtentRecord->getNumItems() == 0 && $econtentRecord->sourceUrl != null && strlen($econtentRecord->sourceUrl) > 0){
				$sourceUrl = $econtentRecord->sourceUrl;
				$econtentItem = new EContentItem();
				$econtentItem->recordId = $econtentRecord->id;
				$econtentItem->item_type = 'externalLink';
				$econtentItem->addedBy = 1;
				$econtentItem->date_added = time();
				$econtentItem->date_updated = time();
				$econtentItem->link = $sourceUrl;
				$econtentItem->insert();
				$eContentAttachmentLogEntry->recordsProcessed++;
				//Increase processing time since this can take awhile
				set_time_limit(30);
			}
		}
		$eContentAttachmentLogEntry->dateFinished = time();
		$eContentAttachmentLogEntry->status = 'finished';
		$eContentAttachmentLogEntry->update();
	}
	
	function loadEContentSources(){
		$sources = array();
		$econtentRecord = new EContentRecord();
		$econtentRecord->query("SELECT DISTINCT source FROM econtent_record ORDER BY source");
		while ($econtentRecord->fetch()){
			$sources[] =  $econtentRecord->source;
		}
		return $sources;
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}
