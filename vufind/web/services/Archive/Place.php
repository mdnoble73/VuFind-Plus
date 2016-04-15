<?php
/**
 * Displays Information about Places stored in the Digital Repository
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/7/2015
 * Time: 7:55 AM
 */

require_once ROOT_DIR . '/services/Archive/Entity.php';
class Archive_Place extends Archive_Entity{
	function launch(){
		global $interface;

		$this->loadArchiveObjectData();
		$this->loadExploreMoreContent();
		$this->loadLinkedData();
		$this->loadRelatedContentForEntity();

		$interface->assign('showExploreMore', true);

		//Look to see if we have a link to who's on first.  If so, show the polygon
		if (isset($this->links) && is_array($this->links)){
			foreach ($this->links as $link){
				if ($link['type'] == 'whosOnFirst'){
					$addressInfo = $interface->getVariable('addressInfo');
					if ($addressInfo == null || count($addressInfo) == 0){
						$whosOnFirstDataRaw = file_get_contents($link['link']);
						$whosOnFirstData = json_decode($whosOnFirstDataRaw, true);

						$addressInfo['latitude'] = $whosOnFirstData['properties']['lbl:latitude'];
						$addressInfo['longitude'] = $whosOnFirstData['properties']['lbl:longitude'];

						$boundingBox = $whosOnFirstData['bbox'];

						$interface->assign('addressInfo', $addressInfo);
					}
				}
			}
		}


		// Display Page
		$this->display('baseArchiveObject.tpl');
	}
}