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

class GetMedia extends Action {
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
			$econtentItem = new EContentItem();
			$econtentItem->id = $itemId;
			$econtentItem->find();
			$bookFile = null;
			if ($econtentItem->find(true)){
				$eContentRecord = new EContentRecord();
				$eContentRecord->id = $econtentItem->recordId;
				$eContentRecord->find(true);
				
				$libraryPath = $configArray['EContent']['library'];
				if ($econtentItem->item_type == 'mp3'){
						
					//Load the correct segment
					$segmentIndex = $_REQUEST['segment'];
					$dirHnd = opendir("{$libraryPath}/{$econtentItem->folder}");
					$mp3Files = array();
					while (false !== ($file = readdir($dirHnd))) {
						if (preg_match('/^.*?\.mp3$/i', $file)) {
							$mp3Files[] = $file;
						}
					}
					$files = readdir($dirHnd);
					sort($mp3Files);
					closedir($dirHnd);
					$bookFile = "{$libraryPath}/{$econtentItem->folder}/{$mp3Files[$segmentIndex]}";
						
				}else{
					$bookFile = "{$libraryPath}/{$econtentItem->filename}";
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
					$driver->recordEContentAction($id, 'Download', $econtentItem->getAccessType());
						
					if (strcasecmp($econtentItem->item_type, 'mp3') == 0){
						header("Content-Type: audio/mpeg3;\n");
						header('Content-Length: ' . filesize($bookFile));
						if (isset($_REQUEST['download'])){
							header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
						}
						echo readfile($bookFile);
						exit();
					}
				}
			} else {
				$errorOccurred = true;
				$interface->assign('errorMessage', 'Sorry, we could not find that book in our online library.');
			}
		}
		$interface->assign('errorOccurred', $errorOccurred);
	}
}