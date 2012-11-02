<?php
require_once 'DB/DataObject.php';

class Analytics_Session extends DB_DataObject
{
	public $__table = 'analytics_session';                        // table name
	public $id;
	public $session_id;
	public $rememberMe;
	public $sessionStartTime;
	public $lastRequestTime;
	public $country;
	public $state;
	public $city;
	public $latitude;
	public $longitude;
	public $ip;
	public $theme;
	public $mobile;
	public $device;
	public $physicalLocation;
	public $patronType;
	public $homeLocationId;

}