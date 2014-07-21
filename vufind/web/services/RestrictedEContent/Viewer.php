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

require_once ROOT_DIR . '/Action.php';

require_once ROOT_DIR . '/RecordDrivers/RestrictedEContentDriver.php';

class RestrictedEContent_Viewer extends Action
{
	function launch()
	{
		global $interface;
		global $configArray;
		global $user;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$itemId = $_REQUEST['itemId'];
		$interface->assign('itemId', $itemId);
		$file = $_REQUEST['file'];
		$interface->assign('file', $file);
		$recordDriver = new RestrictedEContentDriver($id);

		$isAudio = false;

		if (!$recordDriver->isValid()){
			$interface->setTemplate('../Record/invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}else{
			$interface->assign('recordDriver', $recordDriver);
			$itemId = $_REQUEST['itemId'];

			$errorOccurred = false;
			if ($user == false){
				$interface->assign('errorMessage', 'Sorry, you must be logged in to view this title.');
				$errorOccurred = true;
				$interface->assign('showLogin', true);
			}else{
				if (!$recordDriver->isCheckedOut($itemId)){
					$interface->assign('errorMessage', "You must checkout this title before you view it, please <a href='{$configArray['Site']['path']}/PublicEContent/{$id}/Checkout'>click here</a> to checkout the title.");
					$errorOccurred = true;
					$interface->assign('showLogin', false);
				}else{
					require_once (ROOT_DIR . '/sys/eContent/EContentRecord.php');
					$filename = $_REQUEST['file'];
					$bookFile = $configArray['EContent']['library'] . '/'. $filename;
					if (file_exists($bookFile)){
						//Check the database to see if there is an existing title
						$fileExtension = '';
						if (strpos($bookFile, '.') !== FALSE){
							$fileExtension = substr($bookFile, strrpos($bookFile, '.') + 1);
						}

						//Record that the title is being viewed.
						require_once(ROOT_DIR . '/sys/eContent/EContentHistoryEntry.php');
						$entry = new EContentHistoryEntry();
						$entry->userId = $user->id;
						$entry->recordId = $id;
						$entry->itemId = $itemId;
						$entry->action = 'Read Online';
						//Open date will be filled out automatically.
						$entry->insert();

						if ($fileExtension == 'epub'){
							require_once(ROOT_DIR . '/sys/eReader/ebook.php');

							$ebook = new ebook($bookFile);
							if ($ebook->readErrorOccurred()){
								$errorOccurred = true;
								$interface->assign('errorMessage', $ebook->readError());
							}else{
								$spineInfo = $ebook->getSpine();
								$toc = $ebook->getTOC();
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

								$interface->assign('bookTitle', $ebook->getTitle());
								$errorOccurred = false;
							}
						}elseif ($fileExtension == 'txt'){
							header("Content-Type: text/plain;\n");
							header('Content-Length: ' . filesize($bookFile));
							readfile($bookFile);
							exit();
						}elseif (is_dir($bookFile)){
							//A folder of mp3 files?
							//Display information so patron can listen to the recording.
							//Table of contents is based on the actual files uploaded.
							$isAudio = true;
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
						}
					}else{
						$errorOccurred = true;
						$interface->assign('errorMessage', 'Sorry, we could not find that title in our online library.');
					}
				}
				$interface->assign('errorOccurred', $errorOccurred);

				if ($isAudio){
					$interface->display('EcontentRecord/viewer-mp3.tpl');
				}else{
					$interface->display('EcontentRecord/viewer-custom.tpl');
				}
			}
		}
	}
}
