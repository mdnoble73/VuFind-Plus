<?php

/**
 * Description goes here
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 1/31/2016
 * Time: 7:58 PM
 */
//Include code we need to use Tuque without Drupal
require_once(ROOT_DIR . '/sys/tuque/Cache.php');
require_once(ROOT_DIR . '/sys/tuque/FedoraApi.php');
require_once(ROOT_DIR . '/sys/tuque/FedoraApiSerializer.php');
require_once(ROOT_DIR . '/sys/tuque/Object.php');
require_once(ROOT_DIR . '/sys/tuque/HttpConnection.php');
require_once(ROOT_DIR . '/sys/tuque/Repository.php');
require_once(ROOT_DIR . '/sys/tuque/RepositoryConnection.php');
class FedoraUtils {
	/** @var FedoraRepository */
	private $repository;
	/** @var FedoraApi */
	private $api;
	/** @var  FedoraUtils */
	private static $singleton;

	/**
	 * @return FedoraUtils
	 */
	public static function getInstance(){
		if (FedoraUtils::$singleton == null){
			FedoraUtils::$singleton = new FedoraUtils();
		}
		return FedoraUtils::$singleton;
	}

	private function __construct(){
		global $configArray;
		try {
			$serializer = new FedoraApiSerializer();
			$cache = new SimpleCache();
			$fedoraUrl = $configArray['Islandora']['fedoraUrl'];
			$fedoraPassword = $configArray['Islandora']['fedoraPassword'];
			$fedoraUser = $configArray['Islandora']['fedoraUsername'];
			$connection = new RepositoryConnection($fedoraUrl, $fedoraUser, $fedoraPassword);
			$connection->verifyPeer = false;
			$this->api = new FedoraApi($connection, $serializer);
			$this->repository = new FedoraRepository($this->api, $cache);
		}catch (Exception $e){
			global $logger;
			$logger->log("Error connecting to repository $e", PEAR_LOG_ERR);
		}
	}

	/** AbstractObject */
	public function getObject($pid) {
		//Clean up the pid in case we get extra data
		$pid = str_replace('info:fedora/', '', $pid);
		try{
			return $this->repository->getObject($pid);
		}catch (Exception $e){
			return null;
		}
	}

	/** AbstractObject */
	public function getObjectLabel($pid) {
		try{
			$object = $this->repository->getObject($pid);
		}catch (Exception $e){
			//global $logger;
			//$logger->log("Could not find object $pid due to exception $e", PEAR_LOG_WARNING);
			$object = null;
		}

		if ($object == null){
			return 'Invalid Object';
		}else{
			if (empty($object->label)){
				return $pid;
			}else{
				return $object->label;
			}
		}
	}

	/**
	 * @param AbstractObject $archiveObject
	 * @param string $size
	 * @param string $defaultType
	 * @return string
	 */
	function getObjectImageUrl($archiveObject, $size = 'small', $defaultType = null){
		global $configArray;
		$objectUrl = $configArray['Islandora']['objectUrl'];
		if ($size == 'thumbnail'){
			if ($archiveObject->getDatastream('TN') != null){
				return $objectUrl . '/' . $archiveObject->id . '/datastream/TN/view';
			}else if ($archiveObject->getDatastream('SC') != null){
				return $objectUrl . '/' . $archiveObject->id . '/datastream/SC/view';
			}else {
				//return a placeholder
				return $this->getPlaceholderImage($defaultType);
			}
		}elseif ($size == 'small'){
			if ($archiveObject->getDatastream('SC') != null){
				return $objectUrl . '/' . $archiveObject->id . '/datastream/SC/view';
			}else if ($archiveObject->getDatastream('TN') != null){
				return $objectUrl . '/' . $archiveObject->id . '/datastream/TN/view';
			}else{
				//return a placeholder
				return $this->getPlaceholderImage($defaultType);
			}
		}elseif ($size == 'medium'){
			if ($archiveObject->getDatastream('MC') != null) {
				return $objectUrl . '/' . $archiveObject->id . '/datastream/MC/view';
			}else if ($archiveObject->getDatastream('MEDIUM_SIZE') != null) {
				return $objectUrl . '/' . $archiveObject->id . '/datastream/MEDIUM_SIZE/view';
			}else if ($archiveObject->getDatastream('TN') != null) {
				return $objectUrl . '/' . $archiveObject->id . '/datastream/TN/view';
			}else{
				return $this->getObjectImageUrl($archiveObject, 'small', $defaultType);
			}
		}if ($size == 'large'){
			if ($archiveObject->getDatastream('JPG') != null) {
				return $objectUrl . '/' . $archiveObject->id . '/datastream/JPG/view';
			}elseif ($archiveObject->getDatastream('LC') != null){
				return $objectUrl . '/' . $archiveObject->id . '/datastream/LC/view';
			}else{
				return $this->getObjectImageUrl($archiveObject, 'medium', $defaultType);
			}
		}
	}

	public function getPlaceholderImage($defaultType) {
		global $configArray;
		if ($defaultType == 'personCModel' || $defaultType == 'person') {
			return $configArray['Site']['path'] . '/interface/themes/responsive/images/people.png';
		}elseif ($defaultType == 'placeCModel' || $defaultType == 'place'){
			return $configArray['Site']['path'] . '/interface/themes/responsive/images/places.png';
		}elseif ($defaultType == 'eventCModel' || $defaultType == 'event'){
			return $configArray['Site']['path'] . '/interface/themes/responsive/images/events.png';
		}else{
			return $configArray['Site']['path'] . '/interface/themes/responsive/images/History.png';
		}
	}

	/**
	 * Retrieves MODS data for the specified object
	 *
	 * @param FedoraObject $archiveObject
	 *
	 * @return SimpleXMLElement
	 */
	public function getModsData($archiveObject){
		if (array_key_exists($archiveObject->id, $this->modsCache)) {
			$modsData = $this->modsCache[$archiveObject->id];
		}else{
			$modsStream = $archiveObject->getDatastream('MODS');
			if ($modsStream){
				$temp = tempnam('/tmp', 'mods');
				$modsStream->getContent($temp);
				$modsStreamContent = trim(file_get_contents($temp));
				if (strlen($modsStreamContent) > 0){
					$modsData = simplexml_load_string($modsStreamContent);
					if (sizeof($modsData) == 0){
						$modsData = $modsData->children('http://www.loc.gov/mods/v3');
					}
				}
				unlink($temp);
				$this->modsCache[$archiveObject->id] = $modsData;
			}else{
				return null;
			}
		}
		return $modsData;
	}

	private $modsCache = array();

	public function doSparqlQuery($query){
		$results = $this->repository->ri->sparqlQuery($query);
		return $results;
	}

}