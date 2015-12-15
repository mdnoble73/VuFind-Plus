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
class PersonDriver extends IslandoraDriver {

	public function getViewAction() {
		return 'Person';
	}

	protected function getPlaceholderImage() {
		global $configArray;
		return $configArray['Site']['path'] . '/interface/themes/responsive/images/People.png';
	}}