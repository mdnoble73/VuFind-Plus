<?php
/**
 * Displays Information about Places stored in the Digital Repository
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/7/2015
 * Time: 7:55 AM
 */

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Place extends Archive_Object{
	function launch(){
		global $interface;

		$this->loadArchiveObjectData();
		$this->loadExploreMoreContent();

		$interface->assign('showExploreMore', true);

		//Get all images related to the event


		// Display Page
		$this->display('place.tpl');
	}
}