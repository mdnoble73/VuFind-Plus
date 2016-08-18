<?php

/**
 * Record Driver for display of LargeImages from Islandora
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/9/2015
 * Time: 1:47 PM
 */
require_once ROOT_DIR . '/RecordDrivers/IslandoraDriver.php';
class PageDriver extends IslandoraDriver {

	public function getViewAction() {
		return 'Page';
	}

	public function getFormat(){
		return 'Page';
	}

	/**
	 * @return null|FedoraObject
	 */
	public function getParentObject(){
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$parentIdArray = $this->archiveObject->relationships->get(FEDORA_RELS_EXT_URI, 'isMemberOf');
		if ($parentIdArray != null){
			$parentIdInfo = reset($parentIdArray);
			$parentId = $parentIdInfo['object']['value'];
			return $fedoraUtils->getObject($parentId);
		}
		return null;
	}
}