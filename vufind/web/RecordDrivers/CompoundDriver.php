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
class CompoundDriver extends IslandoraDriver {

	public function getViewAction() {
		$genre = $this->getModsValue('genre', 'mods');
		if ($genre != null && strlen($genre) > 0){
			return ucfirst($genre);
		}
		return "Compound";
	}

}