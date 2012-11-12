<?php
require_once 'sys/Logger.php';
require_once 'PEAR.php';
require_once 'sys/ConfigArray.php';
$configArray = readConfig();
require_once 'sys/Utils/SwitchDatabase.php';
require_once 'sys/Timer.php';
global $timer;
$timer = new Timer();
$logger = new Logger();

if ($configArray['System']['debug']) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_DEPRECATED);
}
// Setup Local Database Connection
define('DB_DATAOBJECT_NO_OVERLOAD', 0);
$options =& PEAR::getStaticProperty('DB_DataObject', 'options');
$options = $configArray['Database'];

require_once('Drivers/marmot_inc/Library.php');
$library = new Library();
$library->find();

ob_start();
echo("<br>Starting to optimize tables\r\n");
$logger->log('Starting to optimize tables', PEAR_LOG_INFO);
ob_flush();

foreach ($configArray['Database'] as $key => $value){
	if (preg_match('/table_(.*)/', $key, $matches)){
		if ($value =='vufind'){
			SwitchDatabase::switchToVuFind();
		}else{
			SwitchDatabase::switchToEcontent();
		}
		$tableName = $matches[1];
		set_time_limit(300);
		mysql_query("OPTIMIZE TABLE $tableName;");
		$logger->log('Optimized table: ' . $tableName, PEAR_LOG_INFO);
	}
}

//Optimize tables that are not part of the browse definition
SwitchDatabase::switchToVuFind();
set_time_limit(300);
mysql_query('OPTIMIZE TABLE title_browse;');
mysql_query('OPTIMIZE TABLE title_browse_metadata;');
mysql_query('OPTIMIZE TABLE title_browse_scoped_results_global;');
set_time_limit(300);
mysql_query('OPTIMIZE TABLE author_browse;');
mysql_query('OPTIMIZE TABLE author_browse_metadata;');
mysql_query('OPTIMIZE TABLE author_browse_scoped_results_global;');
set_time_limit(300);
mysql_query('OPTIMIZE TABLE subject_browse;');
mysql_query('OPTIMIZE TABLE subject_browse_metadata;');
mysql_query('OPTIMIZE TABLE subject_browse_scoped_results_global;');
set_time_limit(300);
mysql_query('OPTIMIZE TABLE callnumber_browse;');
mysql_query('OPTIMIZE TABLE callnumber_browse_metadata;');
mysql_query('OPTIMIZE TABLE callnumber_browse_scoped_results_global;');

while ($library->fetch()){
	set_time_limit(300);
	mysql_query("OPTIMIZE TABLE title_browse_scoped_results_library_{$library->subdomain};");
	mysql_query("OPTIMIZE TABLE author_browse_scoped_results_library_{$library->subdomain};");
	mysql_query("OPTIMIZE TABLE subject_browse_scoped_results_library_{$library->subdomain};");
	mysql_query("OPTIMIZE TABLE callnumber_browse_scoped_results_library_{$library->subdomain};");
	echo("<br>optimized browse tables for {$library->displayName}\r\n");
}
$logger->log('Finished optimizing tables', PEAR_LOG_INFO);
