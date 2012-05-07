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

require_once 'Action.php';

require_once 'services/MyResearch/lib/Resource.php';
require_once 'services/MyResearch/lib/User.php';
require_once('sys/eContent/EContentItem.php');
require_once('sys/eContent/EContentRecord.php');

class Download extends Action {
	private $user;

	function __construct() {
		$this->user = UserAccount::isLoggedIn();
	}

	/**
	 * Download an EPUB file from the server so a user can use it locally.
	 **/
	function launch()	{
		global $interface;
		global $configArray;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$itemId = $_REQUEST['item'];

		$errorOccurred = false;
		if ($this->user == false){

			$interface->assign('errorMessage', 'User is not logged in.');
			$errorOccurred = true;
			$interface->assign('showLogin', true);
		}else{

			//Check the database to see if there is an existing title
			$epubFile = new EContentItem();
			$epubFile->id = $itemId;
			$bookFile = null;
				
			if ($epubFile->find(true)){
				$eContentRecord = new EContentRecord();
				$eContentRecord->id = $epubFile->recordId;
				$eContentRecord->find(true);

				$libraryPath = $configArray['EContent']['library'];
				if (isset($epubFile->filename) && strlen($epubFile->filename) > 0){
					$bookFile = "{$libraryPath}/{$epubFile->filename}";
				}else{
					$bookFile = "{$libraryPath}/{$epubFile->folder}";
				}
				if (!file_exists($bookFile)){
					$bookFile = null;
				}
			}

			$errorOccurred = false;

			if (file_exists($bookFile)){
				require_once('Drivers/EContentDriver.php');
				$driver = new EContentDriver();
				//Check to see if the user has access to the title.
				if (!$driver->isRecordCheckedOutToUser($id)){
					$errorOccurred = true;
					$interface->assign('errorMessage', "Sorry, you do not have access to that title, please <a href='{$configArray['Site']['path']}/Record/{$id}/Hold'>place a hold</a> on the title and you will be notified when it is ready for pickup.");
				}
				
				if (!$errorOccurred){
					//Record that the e-pub file is being opened.
					$driver->recordEContentAction($id, 'Download', $eContentRecord->accessType);
						
					if (strcasecmp($epubFile->item_type, 'epub') == 0){
						require_once('sys/eReader/ebook.php');
						$ebook = new ebook($bookFile);

						//Return the contents of the epub file
						header("Content-Type: application/epub+zip;\n");
						header('Content-Length: ' . filesize($bookFile));
						header('Content-Description: ' . $ebook->getTitle());
						header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
						echo readfile($bookFile);
						exit();
					}else if (strcasecmp($epubFile->item_type, 'pdf') == 0){
						header("Content-Type: application/pdf;\n");
						header('Content-Length: ' . filesize($bookFile));
						header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
						echo readfile($bookFile);
						exit();
					}else if (strcasecmp($epubFile->item_type, 'kindle') == 0){
						header('Content-Length: ' . filesize($bookFile));
						header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
						echo readfile($bookFile);
						exit();
					}else if (strcasecmp($epubFile->item_type, 'plucker') == 0){
						header('Content-Length: ' . filesize($bookFile));
						header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
						echo readfile($bookFile);
						exit();
					}else if (strcasecmp($epubFile->item_type, 'mp3') == 0){
						$id = $_REQUEST['id'];
						$interface->assign('id', $id);

						$item = $_REQUEST['item'];
						$interface->assign('item', $item);
						//Get all of the MP3 files that
						$dirHnd = opendir($bookFile);
						$mp3Files = array();
						while (false !== ($file = readdir($dirHnd))) {
							if (preg_match('/^.*?\.mp3$/i', $file)) {
								$mp3Files[] = array(
									'name' => preg_replace('/\.mp3/i', '', $file),
									'size' => filesize($bookFile . '/' . $file)
								);
							}
						}
						$files = readdir($dirHnd);
						closedir($dirHnd);
						//Sort the mp3 files by name.
						sort($mp3Files);
						$interface->assign('mp3Filenames', $mp3Files);
						$interface->display('EcontentRecord/download-mp3.tpl');
						exit();
					}
				}
			} else {
				$errorOccurred = true;
				$interface->assign('errorMessage', 'Sorry, we could not find that book in our online library.');
			}
		}
		$interface->assign('errorOccurred', $errorOccurred);

		$interface->display('EcontentRecord/download.tpl');
	}
}