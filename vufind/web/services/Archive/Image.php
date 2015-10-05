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
class Archive_Image extends Archive_Object{
	function launch(){
		global $interface;
		$this->loadArchiveObjectData();
		$this->loadExploreMoreContent();

		//TODO: This should be the collapsible sidebar
		//$interface->assign('sidebar', 'Record/full-record-sidebar.tpl');
		$interface->assign('showExploreMore', true);
		$interface->setTemplate('image.tpl');

		// Display Page
		$interface->display('layout.tpl');
	}
}