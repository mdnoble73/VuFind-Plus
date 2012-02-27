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

class AttachEContent extends Admin
{
	function launch()
	{
		global $interface;
		global $configArray;

		$interface->setPageTitle('Attach eContent files to records.');
		
		
		if (isset($_REQUEST['submit'])){
			$errors = array();
			//Get the source folder to process
			$source = $_REQUEST['sourcePath'];
			if (!file_exists($source) && !is_dir($source)){
				$errors[] = "Sorry, we could not find a directory with that name to import files from.";
			}else{
				//Get import information
				$cronPath = $configArray['Site']['cronPath']; 
				$commandToRun = "cd $cronPath && start /b java -jar cron.jar org.epub.AttachEContent";
				$commandToRun .= " source=\"" . $source . "\"";
				//$commandToRun .= " > process.out 2> process.err < /dev/null &";
				$handle = popen($commandToRun, 'r');
				pclose($handle);
				header("Location: {$configArray['Site']['path']}/Admin/AttachEContentLog");
				exit();
			}
		}

		$interface->setTemplate('attachEContent.tpl');
		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}
