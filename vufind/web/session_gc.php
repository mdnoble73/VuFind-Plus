<?php
require_once 'sys/Logger.php';
require_once 'PEAR.php';
require_once 'sys/ConfigArray.php';
$configArray = readConfig();
require_once 'sys/Timer.php';
global $timer;
$timer = new Timer();

if ($configArray['System']['debug']) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_DEPRECATED);
}
// Setup Local Database Connection
define('DB_DATAOBJECT_NO_OVERLOAD', 0);
$options =& PEAR::getStaticProperty('DB_DataObject', 'options');
$options = $configArray['Database'];

// Initiate Session State
$session_type = $configArray['Session']['type'];
$session_lifetime = $configArray['Session']['lifetime'];
$session_rememberMeLifetime = $configArray['Session']['rememberMeLifetime'];
register_shutdown_function('session_write_close');
if (isset($configArray['Site']['cookie_domain'])){
	session_set_cookie_params(0, '/', $configArray['Site']['cookie_domain']);
}
require_once 'sys/' . $session_type . '.php';
if (class_exists($session_type)) {
	$session = new $session_type();
	$session->init($session_lifetime, $session_rememberMeLifetime);
}

$session::gc(3600);