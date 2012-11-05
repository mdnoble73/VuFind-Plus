<?php
require_once 'sys/analytics/Analytics_Session.php';
require_once 'sys/analytics/Analytics_Event.php';
require_once 'sys/analytics/Analytics_Search.php';
require_once 'sys/analytics/Analytics_PageView.php';

class Analytics
{
	private $session;
	private $pageView;
	private $events = array();
	private $search;
	private $finished = false;
	private $trackingDisabled = false;

	function __construct($ipAddress, $startTime){
		global $configArray;
		if (!isset($configArray)){
			die("You must load configuration before creating a tracker");
		}
		global $interface;
		global $user;
		if (!isset($interface)){
			die("You must setup the interface before creating a tracker");
		}

		//disable error handler since the tables may not be installed yet.
		disableErrorHandler();
		$sessionId = session_id();
		$session = new Analytics_Session();
		$session->session_id = $sessionId;
		if ($session->find(true)){
			$this->session = $session;
			if ($this->session->ip != $ipAddress){
				$this->session->ip = $ipAddress;
				$this->doGeoIP();
			}
		}else{
			$this->session = $session;
			$this->session->sessionStartTime = $startTime;
			$this->session->lastRequestTime = $startTime;
			$this->session->ip = $ipAddress;

			$this->doGeoIP();

			$this->session->insert();
		}

		$this->pageView = new Analytics_PageView();
		$this->pageView->sessionId = $this->session->id;
		$this->pageView->pageStartTime = $startTime;
		$this->pageView->fullUrl = $_SERVER['REQUEST_URI'];
		enableErrorHandler();
	}

	function disableTracking(){
		$this->trackingDisabled = true;
	}

	function setModule($module){
		$this->pageView->module = $module;
	}

	function setAction($action){
		$this->pageView->action = $action;
	}

	function setObjectId($objectId){
		$this->pageView->objectId = $objectId;
	}

	function setMethod($method){
		$this->pageView->method = $method;
	}

	function setLanguage($language){
		$this->pageView->language = $language;
	}

	function setTheme($language){
		$this->session->theme = $language;
	}

	function setMobile($mobile){
		$this->session->mobile = $mobile;
	}

	function setDevice($device){
		$this->session->device = $device;
	}

	function setPhysicalLocation($physicalLocation){
		$this->session->physicalLocation = $physicalLocation;
	}

	function setPatronType($patronType){
		$this->session->patronType = $patronType;
	}

	function setHomeLocationId($homeLocationId){
		$this->session->homeLocationId = $homeLocationId;
	}

	function doGeoIP(){
		global $configArray;
		//Load GeoIP data
		require_once 'sys/MaxMindGeoIP/geoip.inc';
		require_once 'sys/MaxMindGeoIP/geoipcity.inc';
		$geoIP = geoip_open($configArray['Site']['local'] . '/../../sites/default/GeoIPCity.dat', GEOIP_MEMORY_CACHE);
		$geoRecord = GeoIP_record_by_addr($geoIP, $this->session->ip);
		if ($geoRecord){
			$this->session->country = $geoRecord->country_code;
			$this->session->states = $geoRecord->region;
			$this->session->city = $geoRecord->city;
			$this->session->latitude = $geoRecord->latitude;
			$this->session->longitude = $geoRecord->longitude;
		}
		geoip_close($geoIP);
	}

	function addEvent($category, $action, $data = ''){
		$event = new Analytics_Event();
		$event->sessionId = $this->session->id;
		$event->category = $category;
		$event->action = $action;
		$event->data = $data;
		$event->eventTime = time();
		$this->events[] = $event;
	}

	function addSearch($scope, $lookfor, $isAdvanced, $searchType, $facetsApplied, $numResults){
		$this->search = new Analytics_Search();
		$this->search->sessionId = $this->session->id;
		$this->search->scope = $scope;
		$this->search->lookfor = $lookfor;
		$this->search->isAdvanced = $isAdvanced;
		$this->search->searchType = $searchType;
		$this->search->facetsApplied = $facetsApplied;
		$this->search->searchTime = time();
		$this->search->numResults = $numResults;
	}

	function __destruct(){
		$this->finish();
	}

	function finish(){
		if ($this->finished){
			return;
		}
		$this->finished = true;
		global $configArray;
		if (!isset($configArray['System']['enableAnalytics']) || $configArray['System']['enableAnalytics'] == false){
			return;
		}

		//Make sure that we don't track visits from bots
		if (BotChecker::isRequestFromBot()){
			return;
		}

		//disableErrorHandler();
		if (!$this->trackingDisabled){
			//Save or update the session
			$this->session->lastRequestTime = time();
			$this->session->update();
			//Save the page view
			$this->pageView->pageEndTime = time();
			$this->pageView->insert();
			//Save searches
			if ($this->search){
				$this->search->insert();
			}
		}
		//Save events
		foreach ($this->events as $event){
			$event->insert();
		}

		//enableErrorHandler();
	}
}