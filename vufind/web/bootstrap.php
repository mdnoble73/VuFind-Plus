<?php
define ('ROOT_DIR', __DIR__);

global $errorHandlingEnabled;
$errorHandlingEnabled = true;

$startTime = microtime(true);
require_once ROOT_DIR . '/sys/Logger.php';
require_once ROOT_DIR . '/sys/PEAR_Singleton.php';
PEAR_Singleton::init();

require_once ROOT_DIR . '/sys/ConfigArray.php';
require_once ROOT_DIR . '/sys/Utils/SwitchDatabase.php';
global $configArray;
$configArray = readConfig();
require_once ROOT_DIR . '/sys/Timer.php';
global $timer;
$timer = new Timer($startTime);
global $logger;
$logger = new Logger();
$timer->logTime("Read Config");

if ($configArray['System']['debug']) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

//Use output buffering to allow session cookies to have different values
// this can't be determined before session_start is called
ob_start();

initMemcache();
initDatabase();
requireSystemLibraries();
initLocale();
// Sets global error handler for PEAR errors
PEAR_Singleton::setErrorHandling(PEAR_ERROR_CALLBACK, 'handlePEARError');
loadLibraryAndLocation();

$timer->logTime('Bootstrap');

function initMemcache(){
	//Connect to memcache
	/** @var Memcache $memCache */
	global $memCache;
	global $timer;
	global $configArray;
	// Set defaults if nothing set in config file.
	$host = isset($configArray['Caching']['memcache_host']) ? $configArray['Caching']['memcache_host'] : 'localhost';
	$port = isset($configArray['Caching']['memcache_port']) ? $configArray['Caching']['memcache_port'] : 11211;
	$timeout = isset($configArray['Caching']['memcache_connection_timeout']) ? $configArray['Caching']['memcache_connection_timeout'] : 1;

	// Connect to Memcache:
	$memCache = new Memcache();
	if (!@$memCache->pconnect($host, $port, $timeout)) {
		//Try again with a non-persistent connection
		if (!$memCache->connect($host, $port, $timeout)) {
			PEAR_Singleton::raiseError(new PEAR_Error("Could not connect to Memcache (host = {$host}, port = {$port})."));
		}
	}
	$timer->logTime("Initialize Memcache");
}

function initDatabase(){
	global $configArray;
	// Setup Local Database Connection
	define('DB_DATAOBJECT_NO_OVERLOAD', 0);
	$options =& PEAR_Singleton::getStaticProperty('DB_DataObject', 'options');
	$options = $configArray['Database'];
}

function requireSystemLibraries(){
	global $timer;
	// Require System Libraries
	require_once ROOT_DIR . '/sys/Interface.php';
	$timer->logTime("Include Interface");
	require_once ROOT_DIR . '/sys/User.php';
	$timer->logTime("Include User");
	require_once ROOT_DIR . '/sys/Translator.php';
	$timer->logTime("Include Translator");
	require_once ROOT_DIR . '/sys/SearchObject/Factory.php';
	$timer->logTime("Include Search Object Factory");
	require_once ROOT_DIR . '/sys/ConnectionManager.php';
	$timer->logTime("Include ConnectionManager");
	require_once ROOT_DIR . '/Drivers/marmot_inc/Library.php';
	require_once ROOT_DIR . '/Drivers/marmot_inc/Location.php';
	$timer->logTime("Include Library and Location");
}

function initLocale(){
	global $configArray;
	// Try to set the locale to UTF-8, but fail back to the exact string from the config
	// file if this doesn't work -- different systems may vary in their behavior here.
	setlocale(LC_MONETARY, array($configArray['Site']['locale'] . ".UTF-8",
	$configArray['Site']['locale']));
	date_default_timezone_set($configArray['Site']['timezone']);
}

/**
 * Handle an error raised by pear
 *
 * @var PEAR_Error $error;
 * @var string $method
 *
 * @return null
 */
function handlePEARError($error, $method = null){
	global $errorHandlingEnabled;
	if (isset($errorHandlingEnabled) && $errorHandlingEnabled == false){
		return;
	}
	global $configArray;

	// It would be really bad if an error got raised from within the error handler;
	// we would go into an infinite loop and run out of memory.  To avoid this,
	// we'll set a static value to indicate that we're inside the error handler.
	// If the error handler gets called again from within itself, it will just
	// return without doing anything to avoid problems.  We know that the top-level
	// call will terminate execution anyway.
	static $errorAlreadyOccurred = false;
	if ($errorAlreadyOccurred) {
		return;
	} else {
		$errorAlreadyOccurred = true;
	}

	//Clear any output that has been generated so far so the user just gets the error message.
	if (!$configArray['System']['debug']){
		@ob_clean();
		header("Content-Type: text/html");
	}

	// Display an error screen to the user:
	global $interface;
	if (!isset($interface) || $interface == false){
		$interface = new UInterface();
	}
	global $analytics;
	if ($analytics){
		$analytics->addEvent('Unexpected Error', 'Unexpected Error', $error, $method);
	}

	$interface->assign('error', $error);
	$interface->assign('debug', $configArray['System']['debug']);
	$interface->setTemplate('../error.tpl');
	$interface->display('layout.tpl');

	// Exceptions we don't want to log
	$doLog = true;
	// Microsoft Web Discussions Toolbar polls the server for these two files
	//    it's not script kiddie hacking, just annoying in logs, ignore them.
	if (strpos($_SERVER['REQUEST_URI'], "cltreq.asp") !== false) $doLog = false;
	if (strpos($_SERVER['REQUEST_URI'], "owssvr.dll") !== false) $doLog = false;
	// If we found any exceptions, finish here
	if (!$doLog) exit();

	// Log the error for administrative purposes -- we need to build a variety
	// of pieces so we can supply information at five different verbosity levels:
	$baseError = $error->toString();
	$basicServer = " (Server: IP = {$_SERVER['REMOTE_ADDR']}, " .
        "Referer = " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') . ", " .
        "User Agent = " . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') . ", " .
        "Request URI = {$_SERVER['REQUEST_URI']})";
	$detailedServer = "\nServer Context:\n" . print_r($_SERVER, true);
	$basicBacktrace = "\nBacktrace:\n";
	if (is_array($error->backtrace)) {
		foreach($error->backtrace as $line) {
			$basicBacktrace .= (isset($line['file']) ? $line['file'] : 'none') . "  line " . (isset($line['line']) ? $line['line'] : 'none') . " - " .
                "class = " . (isset($line['class']) ? $line['class'] : 'none') . ", function = " . (isset($line['function']) ? $line['function'] : 'none') . "\n";
		}
	}
	$detailedBacktrace = "\nBacktrace:\n" . print_r($error->backtrace, true);
	$errorDetails = array(
	1 => $baseError,
	2 => $baseError . $basicServer,
	3 => $baseError . $basicServer . $basicBacktrace,
	4 => $baseError . $detailedServer . $basicBacktrace,
	5 => $baseError . $detailedServer . $detailedBacktrace
	);

	global $logger;
	$logger->log($errorDetails, PEAR_LOG_ERR);

	exit();
}

function loadLibraryAndLocation(){
	global $timer;
	global $librarySingleton;
	global $locationSingleton;
	global $configArray;
	//Create global singleton instances for Library and Location
	$librarySingleton = new Library();
	$timer->logTime('Created library');
	$locationSingleton = new Location();
	$timer->logTime('Created Location');

	global $active_ip;
	$active_ip = $locationSingleton->getActiveIp();
	if (!isset($_COOKIE['test_ip']) || $active_ip != $_COOKIE['test_ip']){
		if ($active_ip == ''){
			setcookie('test_ip', $active_ip, time() - 1000, '/');
		}else{
			setcookie('test_ip', $active_ip, 0, '/');
		}
	}
	$timer->logTime('Got active ip address');
	$branch = $locationSingleton->getBranchLocationCode();
	if (!isset($_COOKIE['branch']) || $branch != $_COOKIE['branch']){
		if ($branch == ''){
			setcookie('branch', $branch, time() - 1000, '/');
		}else{
			setcookie('branch', $branch, 0, '/');
		}
	}
	$timer->logTime('Got branch');

	//Update configuration information for scoping now that the database is setup.
	$configArray = updateConfigForScoping($configArray);
	$timer->logTime('Updated config for scoping');
}

function disableErrorHandler(){
	global $errorHandlingEnabled;
	$errorHandlingEnabled = false;
}
function enableErrorHandler(){
	global $errorHandlingEnabled;
	$errorHandlingEnabled = true;
}

function array_remove_by_value($array, $value){
	return array_values(array_diff($array, array($value)));
}