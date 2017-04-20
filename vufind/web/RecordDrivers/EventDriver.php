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
class EventDriver extends IslandoraDriver {


	public function getViewAction() {
		return 'Event';
	}

	protected function getPlaceholderImage() {
		global $configArray;
		return $configArray['Site']['path'] . '/interface/themes/responsive/images/events.png';
	}

	public function isEntity(){
		return true;
	}

	public function getFormat(){
		return 'Event';
	}

	public function getMoreDetailsOptions() {
		//Load more details options
		global $interface;
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions();
		}
		if ((count($interface->getVariable('creators')) > 0)
				|| $this->hasDetails
				|| (count($interface->getVariable('marriages')) > 0)
				|| (count($this->unlinkedEntities) > 0)){
			$moreDetailsOptions['details'] = array(
					'label' => 'Details',
					'body' => $interface->fetch('Archive/detailsSection.tpl'),
					'hideByDefault' => false
			);
		}else{
			unset($moreDetailsOptions['details']);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}
}