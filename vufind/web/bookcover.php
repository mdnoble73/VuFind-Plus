<?php
define ('ROOT_DIR', __DIR__);
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/sys/PEAR_Singleton.php';
PEAR_Singleton::init();
require_once ROOT_DIR . '/sys/Timer.php';
require_once ROOT_DIR . '/sys/Logger.php';
require_once ROOT_DIR . '/sys/BookCoverProcessor.php';
require_once ROOT_DIR . '/sys/Proxy_Request.php';
//Bootstrap the process
if (!function_exists('vufind_autoloader')){
	// Set up autoloader (needed for YAML)
	function vufind_autoloader($class) {
		$fullClassName = str_replace('_', '/', $class) . '.php';
		require $fullClassName;
	}
	spl_autoload_register('vufind_autoloader');
}
global $timer;
if (empty($timer)){
	$timer = new Timer(microtime(false));
}

// Retrieve values from configuration file
require_once ROOT_DIR . '/sys/ConfigArray.php';
$configArray = readConfig();
$timer->logTime("Read config");
if (isset($configArray['System']['coverTimings'])){
	$timer->enableTimings($configArray['System']['coverTimings']);
}

//Start a logger
$logger = new Logger();

//Update error handling
if ($configArray['System']['debug']) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_DEPRECATED);
}

date_default_timezone_set($configArray['Site']['timezone']);
$timer->logTime("bootstrap");

//Create class to handle processing of covers
$processor = new BookCoverProcessor();
$processor->loadCover($configArray, $timer, $logger);
if ($processor->error){
	header('Content-type: text/plain'); //Use for debugging notices and warnings
	$logger->log("Error processing cover " . $processor->error, PEAR_LOG_ERR);
	echo($processor->error);
}
$timer->writeTimings();
