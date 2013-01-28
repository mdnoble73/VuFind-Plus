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

class eContentHelp extends Action
{
	function launch()
	{
		global $interface;
		global $configArray;
		$interface->setPageTitle('eContent Help');
		$defaultFormat = "";

		require_once 'sys/eContent/EContentRecord.php';
		if (isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
			$interface->assign('id', $id);
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $id;
			if ($eContentRecord->find(true)){
				require_once 'sys/eContent/EContentItem.php';
				$eContentItem = new EContentItem();
				$eContentItem->id = $_REQUEST['itemId'];
				if ($eContentItem->find(true)){
					$displayFormat = $eContentItem->getDisplayFormat();

					$popupContent = "Sorry, there is not detailed help available for this format yet.";
					if ($eContentItem->item_type == 'mp3'){
						$defaultFormat = 'mp3';
					}else if ($eContentItem->item_type == 'epub'){
						$defaultFormat = 'ebook';
					}else if ($eContentItem->item_type == 'kindle'){
						$defaultFormat = 'kindle';
					}else if ($eContentItem->item_type == 'plucker'){
						$defaultFormat = 'other';
					}else if ($eContentItem->item_type == 'pdf'){
						$defaultFormat = 'other';
					}else if ($eContentItem->item_type == 'externalMP3'){
						$defaultFormat = 'mp3';
					}else if ($eContentItem->item_type == 'external_ebook'){
						$defaultFormat = 'ebook';
					}else if ($eContentItem->item_type == 'externalLink'){
						$defaultFormat = 'other';
					}else if ($eContentItem->item_type == 'overdrive'){
						if ($eContentItem->externalFormatId == 'audiobook-mp3'){
							$defaultFormat = 'mp3';
						}else if ($eContentItem->externalFormatId == 'audiobook-wma'){
							$defaultFormat = 'wma';
						}else if ($eContentItem->externalFormatId == 'video-wmv'){
							$defaultFormat = 'eVideo';
						}else if ($eContentItem->externalFormatId == 'music-wma'){
							$defaultFormat = 'eMusic';
						}else if ($eContentItem->externalFormatId == 'ebook-kindle'){
							$defaultFormat = 'kindle';
						}else if ($eContentItem->externalFormatId == 'ebook-epub-adobe'){
							$defaultFormat = 'ebook';
						}else if ($eContentItem->externalFormatId == 'ebook-pdf-adobe'){
							$defaultFormat = 'other';
						}else if ($eContentItem->externalFormatId == 'ebook-epub-open'){
							$defaultFormat = 'ebook';
						}else if ($eContentItem->externalFormatId == 'ebook-pdf-open'){
							$defaultFormat = 'other';
						}else{
							$defaultFormat = 'other';
						}
					}else if ($eContentItem->item_type == 'external_eaudio'){
						$defaultFormat = 'other';
					}else if ($eContentItem->item_type == 'external_emusic'){
						$defaultFormat = 'eMusic';
					}else if ($eContentItem->item_type == 'text'){
						$defaultFormat = 'other';
					}else if ($eContentItem->item_type == 'itunes'){
						$defaultFormat = 'other';
					}else if ($eContentItem->item_type == 'gifs'){
						$defaultFormat = 'other';
					}else{
						$defaultFormat = 'other';
					}
				}
			}
		}
		$interface->assign('defaultFormat', $defaultFormat);

		$device = get_device_name();
		$defaultDevice = '';
		if ($device == 'Kindle'){
			$defaultDevice = 'kindle';
		}elseif ($device == 'Kindle Fire'){
			$defaultDevice = 'kindle_fire';
		}elseif ($device == 'iPad' || $device == 'iPhone'){
			$defaultDevice = 'ios';
		}elseif ($device == 'Android Phone' || $device == 'Android Tablet'){
			$defaultDevice = 'android';
		}elseif ($device == 'Android Phone' || $device == 'Android Tablet' || $device == 'Google TV'){
			$defaultDevice = 'android';
		}elseif ($device == 'BlackBerry'){
			$defaultDevice = 'other';
		}elseif ($device == 'Mac'){
			$defaultDevice = 'mac';
		}elseif ($device == 'PC'){
			$defaultDevice = 'pc';
		}
		$interface->assign('defaultDevice', $defaultDevice);

		if (isset($_REQUEST['lightbox'])){
			$interface->assign('popupTitle', 'Step by Step Instructions for using eContent');
			$popupContent = $interface->fetch('Help/eContentHelp.tpl');
			$interface->assign('popupContent', $popupContent);
			$interface->display('popup-wrapper.tpl');
		}else{
			$interface->setTemplate('eContentHelp.tpl');
			$interface->display('layout.tpl');
		}
	}
}

?>
