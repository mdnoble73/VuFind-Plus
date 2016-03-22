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
class Archive_Organization extends Archive_Object{
	function launch(){
		global $interface;

		$this->loadArchiveObjectData();
		$this->loadExploreMoreContent();

		$interface->assign('showExploreMore', true);

		// Display Page
		$this->display('baseArchiveObject.tpl');
	}
}