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

require_once ROOT_DIR . '/RecordDrivers/PublicEContentDriver.php';

class PublicEContent_Download extends Action {
	/**
	 * Download a file from the server so a user can use it locally.
	 **/
	function launch()	{
		global $interface;
		global $configArray;
		global $user;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$recordDriver = new PublicEContentDriver($id);

		if (!$recordDriver->isValid()){
			$interface->setTemplate('../Record/invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}else{
			$interface->assign('recordDriver', $recordDriver);
			$itemId = $_REQUEST['itemId'];

			if ($user == false){
				$interface->assign('errorMessage', 'Sorry, you must be logged in to download this title.');
				$errorOccurred = true;
				$interface->assign('showLogin', true);
			}else{
				if (!$recordDriver->isCheckedOut($itemId)){
					$interface->assign('errorMessage', "You must checkout this title before you download it, please <a href='{$configArray['Site']['path']}/PublicEContent/{$id}/Checkout'>click here</a> to checkout the title.");
					$errorOccurred = true;
					$interface->assign('showLogin', false);
				}else{
					$errorOccurred = false;
					$filename = $_REQUEST['file'];
					$bookFile = $configArray['EContent']['library'] . '/'. $filename;
					if (file_exists($bookFile)){
						global $user;
						//Record that the e-pub file is being downloaded.
						require_once(ROOT_DIR . '/sys/eContent/EContentHistoryEntry.php');
						$entry = new EContentHistoryEntry();
						$entry->userId = $user->id;
						$entry->recordId = $id;
						$entry->itemId = $itemId;
						$entry->action = 'Download';
						//Open date will be filled out automatically.
						$entry->insert();

						$fileExtension = '';
						if (strpos($bookFile, '.') !== FALSE){
							$fileExtension = substr($bookFile, strrpos($bookFile, '.') + 1);
						}
						if (strcasecmp($fileExtension, 'epub') == 0){
							require_once ROOT_DIR . '/sys/eReader/ebook.php';
							$ebook = new ebook($bookFile);

							//Return the contents of the epub file
							header("Content-Type: application/epub+zip;\n");
							//header('Content-Length: ' . filesize($bookFile));
							header('Content-Description: ' . $ebook->getTitle());
							//header('Content-Transfer-Encoding: binary');
							header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
							readfile($bookFile);
							die();
						}else if (strcasecmp($fileExtension, 'pdf') == 0){
							header("Content-Type: application/pdf;\n");
							header('Content-Length: ' . filesize($bookFile));
							header('Content-Transfer-Encoding: binary');
							header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
							readfile($bookFile);
							exit();
						}else if (strcasecmp($fileExtension, 'mobi') == 0){
							header('Content-Length: ' . filesize($bookFile));
							header('Content-Transfer-Encoding: binary');
							header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
							readfile($bookFile);
							exit();
						}else if (strcasecmp($fileExtension, 'pdb') == 0){
							header('Content-Length: ' . filesize($bookFile));
							header('Content-Transfer-Encoding: binary');
							header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
							readfile($bookFile);
							exit();
						}else if (strcasecmp($fileExtension, 'txt') == 0){
							header("Content-Type: text/plain;\n");
							header('Content-Length: ' . filesize($bookFile));
							header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
							readfile($bookFile);
							exit();
						}else if (strcasecmp($fileExtension, 'mp3') == 0){
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
					}else{
						$errorOccurred = true;
						$interface->assign('errorMessage', 'Sorry, we could not find that title in our online library.');
					}

				}
			}
		}

		$interface->assign('errorOccurred', $errorOccurred);
		// Display Page
		$interface->assign('sidebar', 'PublicEContent/full-record-sidebar.tpl');
		$interface->setTemplate('download-error.tpl');

		$interface->display('layout.tpl');
	}
}