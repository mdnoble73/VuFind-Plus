<?php
/**
 * Allows display of a single image from Islandora
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 9/8/2015
 * Time: 8:43 PM
 */

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_LargeImage extends Archive_Object{
	function launch() {
		global $interface;
		global $configArray;
		$this->loadArchiveObjectData();
		$this->loadExploreMoreContent();

		$hasImage = false;
		if ($this->archiveObject->getDatastream('JP2') != null) {
			$interface->assign('large_image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/JP2/view");
			$hasImage = true;
		}
		if ($this->archiveObject->getDatastream('JPG') != null){
			$interface->assign('image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/JPG/view");
			$hasImage = true;
		}elseif ($this->archiveObject->getDatastream('LC') != null){
			$interface->assign('image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/LC/view");
			$hasImage = true;
		}else if ($this->archiveObject->getDatastream('MC') != null){
			$interface->assign('image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/MC/view");
			$hasImage = true;
		}
		if (!$hasImage){
			$interface->assign('noImage', true);
		}

		//TODO: This should be the collapsible sidebar
		//$interface->assign('sidebar', 'Record/full-record-sidebar.tpl');
		$interface->assign('showExploreMore', true);
		$interface->setTemplate('largeImage.tpl');

		// Display Page
		$interface->display('layout.tpl');
	}
}