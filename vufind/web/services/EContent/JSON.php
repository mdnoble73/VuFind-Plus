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

require_once ROOT_DIR . '/services/MyResearch/lib/User.php';
require_once ROOT_DIR . '/RecordDrivers/PublicEContentDriver.php';
require_once ROOT_DIR . '/RecordDrivers/RestrictedEContentDriver.php';

class EContent_JSON extends Action
{
	function launch()
	{
		global $configArray;

		$id = $_REQUEST['id'];
		$file = $_REQUEST['file'];

		//Check the database to see if there is an existing title
		$recordDriver = new PublicEContentDriver($id);
		$restrictedDriver = new RestrictedEContentDriver($id);
		if (!$recordDriver->isValid() && !$restrictedDriver->isValid()){
			$output = json_encode(array('error'=>'Invalid Record.  The specified title does not exist.'));
		}else{
			$itemId = $_REQUEST['item'];
			if (!$recordDriver->isCheckedOut($itemId) && !$restrictedDriver->isCheckedOut($itemId)){
				$output = json_encode(array('error'=>'This title is not checked out to you.  Please checkout the title.'));
			}else{
				//TODO: Validate that this file belongs to the checked out record
				$libraryPath = $configArray['EContent']['library'];
				$bookFile = "{$libraryPath}/{$file}";
				if (!file_exists($bookFile)){
					$bookFile = null;
					$epubExists = false;
				}else{
					$epubExists = true;
				}

				require_once(ROOT_DIR . '/sys/eReader/ebook.php');
				$ebook = new ebook($bookFile);

				if ($epubExists){
					if ( $_GET['method'] == 'getComponentCustom') {
						//Content type will depend on the type of content created.
						$output = $this->$_GET['method']($ebook, $id, $itemId, $file);
					}else{
						header('Content-type: text/plain');
						header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
						if (is_callable(array($this, $_GET['method']))) {
							$output = json_encode(array('result'=>$this->$_GET['method']($ebook, $id, $itemId, $file)));
						} else {
							$output = json_encode(array('error'=>'invalid_method ' . $_GET['method']));
						}
					}
				}else{
					header('Content-type: text/plain');
					header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
					header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
					$output = json_encode(array('error'=>'e-pub file does not exist'));
				}
			}
		}

		echo $output;
	}

	/**
	 * @param ebook $ebook
	 * @param string $id
	 * @param string $item
	 * @param string $file
	 * @return mixed|string
	 */
	function getComponentCustom($ebook, $id, $item, $file){
		global $configArray;
		$component = $_REQUEST['component'];
		$component = stripslashes($component);
		if (strpos($component, "#") > 0){
			$component = substr($component, 0, strpos($component, "#"));
		}

		try{
			$componentText = $ebook->getContentById($component);
		}catch(Exception $e){
			return 'Unable to load content for component ' . $component;
		}
		//Get the componentType of the content we are getting.
		for ($i = 0; $i < $ebook->getManifestSize(); $i++){
			$manifestId = $ebook->getManifestItem($i, 'id');
			$manifestType= $ebook->getManifestItem($i, 'type');

			if ($manifestId == $component){
				$componentType = $manifestType;
			}
		}

		if (isset($componentType) && in_array($componentType, array('image/jpeg', 'image/gif', 'image/tif', 'text/css'))){
			header("Content-type: {$componentType}");
			//Do not json encode the data
		}else{
			//After we get the component, we need to do some processing to fix internal links, images, and css files
			//so they display properly.
			//Loop through the manifest to find any files that are referenced
			for ($i = 0; $i < $ebook->getManifestSize(); $i++){
				$manifestId = $ebook->getManifestItem($i, 'id');
				$manifestHref= $ebook->getManifestItem($i, 'href');
				$manifestType= $ebook->getManifestItem($i, 'type');

				if (in_array($manifestType, array('image/jpeg', 'image/gif', 'image/tif', 'text/css'))){
					//Javascript or image
					$pattern = str_replace("~", "\~", preg_quote($manifestHref));
					if ($manifestType == 'text/css'){
						//Ignore css for now
						$replacement = '';
					}else{
						$replacement = $configArray['Site']['path'] . "/EContent/" . preg_quote($id) ."/JSON?method=getComponentCustom&item=" . $item . "&component=" . preg_quote($manifestId) . "&file=" . $file;
					}
					$componentText = preg_replace("~$pattern~", $replacement, $componentText);
				}else{
					//Link to another location within the document
					//convert to a window.reader.moveTo(componentId, location)
					//$componentText = preg_replace('/<a href=["\']#'. preg_quote($manifestHref) . '["\']/', "<a onclick=\"window.parent.reader.moveTo({componentId: '{$manifestId}', xpath:'//a[@id={$manifestId}]'})\" href=\"#\"", $componentText);
					$quotedManifest = preg_quote($manifestHref, '/');
					$componentText = preg_replace('/<a href=["\']'. $quotedManifest . '["\']/', "<a onclick=\"return showTocEntry('{$manifestId}');\" href=\"#\"", $componentText);
					$componentText = preg_replace('/<a href=["\']'. $quotedManifest . '#(.*?)["\']/', "<a onclick=\"return showTocEntry('{$manifestId}#\\1');\" href=\"#\"", $componentText);
					/*$pattern = str_replace("~", "\~", '<a (.*?)href=["\']'. preg_quote($manifestHref) . '#(.*?)["\']');
					$replacement = '<a \\1 onclick=\"window.parent.reader.moveTo({componentId: \'' . addslashes($manifestId) . '\', xpath:\'//a[@id=\\2]\'});return false;" href="#"';
					$componentText = preg_replace("~$pattern~", $replacement, $componentText);*/
				}
			}

			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			if (is_null($componentText)){
				$componentText = '';
			}
			$componentText = json_encode(array('result'=>$componentText));
		}

		return $componentText;
	}
}