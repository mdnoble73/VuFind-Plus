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

class Viewer extends Action
{
	private $user;

	function __construct()
	{
		$this->user = UserAccount::isLoggedIn();
	}

	function launch()
	{
		global $interface;
		global $configArray;
			
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$item = $_REQUEST['item'];
		$interface->assign('item', $item);

		$viewer = 'custom';
		
		$errorOccurred = false;
		if ($this->user == false){

			$interface->assign('errorMessage', 'User is not logged in.');
			$errorOccurred = true;
			$interface->assign('showLogin', true);
		}else{
			require_once ('sys/eContent/EContentRecord.php');
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $id;
			if ($eContentRecord->find(true)){
				//Check the database to see if there is an existing title
				require_once('sys/eContent/EContentItem.php');
				$eContentItem = new EContentItem();
				$eContentItem->id = $_REQUEST['item'];
				if ($eContentItem->find(true)){
					$bookFile = null;
					$libraryPath = $configArray['EContent']['library'];
					if ($eContentItem->item_type == 'mp3'){
						$bookFile = "{$libraryPath}/{$eContentItem->folder}";
					}else{
						$bookFile = "{$libraryPath}/{$eContentItem->filename}";
					}
					if (!file_exists($bookFile)){
						$bookFile = null;
					}
				}
			}else{
				$errorOccurred = true;
				$interface->assign('errorMessage', 'Could not find the selected title.');
			}

			if (file_exists($bookFile) && $errorOccurred == false){
				require_once('Drivers/EContentDriver.php');
				$driver = new EContentDriver();
				//Check to see if the user has access to the title.
				$isCheckedOut = $driver->isRecordCheckedOutToUser($id);
				if (!$isCheckedOut){
					$errorOccurred = true;
					$interface->assign('errorMessage', "Sorry, you do not have access to that title, please <a href='{$configArray['Site']['path']}/Record/{$id}/Hold'>place a hold</a> on the title and you will be notified when it is ready for pickup.");
				}
					
				if (!$errorOccurred){
					//Record that the e-pub file is being opened.
					if (strcasecmp($eContentItem->item_type, 'epub') === 0){
						$driver->recordEContentAction($id, "Read Online", $eContentRecord->accessType);

						require_once('sys/eReader/ebook.php');

						$ebook = new ebook($bookFile);
						if ($ebook->readErrorOccurred()){
							$errorOccurred = true;
							$interface->assign('errorMessage', $ebook->readError());
						}else{
							$spineInfo = $ebook->getSpine();
							$toc = $ebook->getTOC();
							if ($viewer == 'monocle'){
								$spineData = addslashes(json_encode($spineInfo));
								$interface->assign('spineData', $spineData);
								$contents = addslashes(json_encode($toc));
								$interface->assign('contents', $contents);
								$metaData = addslashes(json_encode(array(
								  "title" => $ebook->getTitle(),
								  "creator" => $ebook->getDcCreator()
								)));
								$interface->assign('metaData', $metaData);
							}else{
								$interface->assign('spineData', $spineInfo);
								$interface->assign('contents', $toc);
								$interface->assign('bookCreator', $ebook->getDcCreator());
								//Load a translation map to translate locations into ids
								$manifest = array();
								for ($i = 0; $i < $ebook->getManifestSize(); $i++){
									$manifestId = $ebook->getManifestItem($i, 'id');
									$manifestHref= $ebook->getManifestItem($i, 'href');
									$manifestType= $ebook->getManifestItem($i, 'type');
									$manifest[$manifestHref] = $manifestId;
								}
								$interface->assign('manifest', $manifest);
							}

							$interface->assign('bookTitle', $ebook->getTitle());
							$errorOccurred = false;
						}
					}else if ($eContentItem->item_type == 'mp3'){
						//Display information so patron can listen to the recording.
						//Table of contents is based on the actual files uploaded.
						$viewer = 'mp3';
						$dirHnd = opendir($bookFile);
						$mp3Files = array();
						while (false !== ($file = readdir($dirHnd))) {
							if (preg_match('/^.*?\.mp3$/i', $file)) {
								$mp3Files[] = preg_replace('/\.mp3/i', '', $file);
							}
						}
						$files = readdir($dirHnd);
						closedir($dirHnd);
						//Sort the mp3 files by name. 
						sort($mp3Files);
						$interface->assign('mp3Filenames', $mp3Files);
					}else{
						$errorOccurred = true;
						$interface->assign('errorMessage', "Sorry, we could not find a viewer for that type of item, please contact support.");
					}
				}
			} else {
				$errorOccurred = true;
				$interface->assign('errorMessage', 'Sorry, we could not find that book in our online library.');
			}
		}
		$interface->assign('errorOccurred', $errorOccurred);

		if ($viewer == 'mp3'){
			$interface->display('EcontentRecord/viewer-mp3.tpl');
		}else{
			$interface->display('EcontentRecord/viewer-custom.tpl');
		}
	}


}
