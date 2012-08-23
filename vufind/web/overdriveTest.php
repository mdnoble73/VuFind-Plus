<?php
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'sys/ConfigArray.php';
$configArray = readConfig();

global $memcache;
// Set defaults if nothing set in config file.
$host = isset($configArray['Caching']['memcache_host']) ? $configArray['Caching']['memcache_host'] : 'localhost';
$port = isset($configArray['Caching']['memcache_port']) ? $configArray['Caching']['memcache_port'] : 11211;
$timeout = isset($configArray['Caching']['memcache_connection_timeout']) ? $configArray['Caching']['memcache_connection_timeout'] : 1;

// Connect to Memcache:
$memcache = new Memcache();
if (!$memcache->pconnect($host, $port, $timeout)) {
	PEAR::raiseError(new PEAR_Error("Could not connect to Memcache (host = {$host}, port = {$port})."));
}

require_once 'Drivers/OverDriveDriver.php';

$driver = new OverDriveDriver();
print_r($driver->_connectToAPI());

echo("<br/><br/>");

print_r($driver->getLibraryAccountInformation());