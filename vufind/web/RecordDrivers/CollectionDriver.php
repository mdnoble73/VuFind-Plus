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
class CollectionDriver extends IslandoraDriver {

	public function getViewAction() {
		return "Exhibit";
	}

	private $anonymousMasterDownload = null;
	private $verifiedMasterDownload = null;
	private $anonymousLcDownload = null;
	private $verifiedLcDownload = null;
	public function canAnonymousDownloadMaster() {
		$this->loadDownloadRestrictions();
		return $this->anonymousMasterDownload;
	}
	public function canVerifiedDownloadMaster() {
		$this->loadDownloadRestrictions();
		return $this->verifiedMasterDownload;
	}

	public function canAnonymousDownloadLC() {
		$this->loadDownloadRestrictions();
		return $this->anonymousLcDownload;
	}
	public function canVerifiedDownloadLC() {
		$this->loadDownloadRestrictions();
		return $this->verifiedLcDownload;
	}

	public function loadDownloadRestrictions(){
		if ($this->anonymousMasterDownload != null){
			return;
		}
		$this->anonymousMasterDownload = true;
		$this->verifiedMasterDownload = true;
		$this->anonymousLcDownload = true;
		$this->verifiedLcDownload = true;
		if ($this->getMarmotExtension()){
			/** @var SimpleXMLElement $marmotLocal */
			$marmotLocal = $this->getMarmotExtension()->marmotLocal;
			if ($marmotLocal->count() > 0) {
				if ($marmotLocal->pikaOptions->count() > 0){
					/** @var SimpleXMLElement $pikaOptions */
					$pikaOptions = $marmotLocal->pikaOptions;
					$this->anonymousMasterDownload = $pikaOptions->anonymousMasterDownload == 'yes';
					$this->verifiedMasterDownload = $pikaOptions->verifiedMasterDownload == 'yes';
					$this->anonymousLcDownload = $pikaOptions->anonymousLcDownload == 'yes';
					$this->verifiedLcDownload = $pikaOptions->verifiedLcDownload == 'yes';
				}
			}
		}
	}
}