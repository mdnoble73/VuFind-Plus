<?php
require_once 'sys/Logger.php';
require_once 'PEAR.php';
require_once 'sys/ConfigArray.php';
require_once 'sys/Utils/SwitchDatabase.php';
global $configArray;
$configArray = readConfig();
require_once 'sys/Timer.php';
global $timer;
$timer = new Timer();
global $logger;
$logger = new Logger();

if ($configArray['System']['debug']) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_DEPRECATED);
}
// Setup Local Database Connection
define('DB_DATAOBJECT_NO_OVERLOAD', 0);
$options =& PEAR::getStaticProperty('DB_DataObject', 'options');
$options = $configArray['Database'];

//Make sure that we intialize connection to the database
require_once('Drivers/marmot_inc/Library.php');
$library = new Library();
$library->find();

SwitchDatabase::switchToVuFind();