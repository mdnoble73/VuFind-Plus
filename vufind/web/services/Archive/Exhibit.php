<?php
/**
 * Displays Information about Digital Repository (Islandora) Exhibit
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/7/2015
 * Time: 7:55 AM
 */

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Exhibit extends Archive_Object{
	function launch(){
		global $interface;
		global $configArray;

		$this->loadArchiveObjectData();
		$this->loadExploreMoreContent();

		if ($this->archiveObject->getDatastream('BANNER') != null) {
			$interface->assign('main_image', $configArray['Islandora']['objectUrl'] . "/{$this->pid}/datastream/BANNER/view");
		}

		//TODO: This should be the collapsible sidebar
		//$interface->assign('sidebar', 'Record/full-record-sidebar.tpl');
		$interface->assign('showExploreMore', true);
		$interface->setTemplate('exhibit.tpl');

		// Display Page
		$interface->display('layout.tpl');
	}
}