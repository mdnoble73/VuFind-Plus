<?php
include_once 'bootstrap.php';
global $logger;
global $configArray;

if (!isset($_REQUEST['subdomain'])){
	echo("Please provide the subdomain to build browse indexes for.");
	die();
}
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_DEPRECATED);
$subdomain = strip_tags($_REQUEST['subdomain']);
if (strpos($subdomain, ' ')){
	echo("Invalid subdomain");
}
$subdomain = mysql_escape_string($subdomain);

runSQLStatement(
	"CREATE TABLE `title_browse_scoped_results_library_{$subdomain}` (
		`browseValueId` INT( 11 ) NOT NULL ,
		`record` VARCHAR( 50 ) NOT NULL ,
		PRIMARY KEY ( `browseValueId` , `record` ) ,
		INDEX ( `browseValueId` ),
		INDEX (`record`)
	) ENGINE = MYISAM");
runSQLStatement(
	"CREATE TABLE `author_browse_scoped_results_library_{$subdomain}` (
		`browseValueId` INT( 11 ) NOT NULL ,
		`record` VARCHAR( 50 ) NOT NULL ,
		PRIMARY KEY ( `browseValueId` , `record` ) ,
		INDEX ( `browseValueId` ),
		INDEX (`record`)
	) ENGINE = MYISAM");
runSQLStatement(
	"CREATE TABLE `subject_browse_scoped_results_library_{$subdomain}` (
		`browseValueId` INT( 11 ) NOT NULL ,
		`record` VARCHAR( 50 ) NOT NULL ,
		PRIMARY KEY ( `browseValueId` , `record` ) ,
		INDEX ( `browseValueId` ),
		INDEX (`record`)
	) ENGINE = MYISAM");
runSQLStatement(
	"CREATE TABLE `callnumber_browse_scoped_results_library_{$subdomain}` (
		`browseValueId` INT( 11 ) NOT NULL ,
		`record` VARCHAR( 50 ) NOT NULL ,
		PRIMARY KEY ( `browseValueId` , `record` ) ,
		INDEX ( `browseValueId` ),
		INDEX (`record`)
	) ENGINE = MYISAM");
echo("Finished building for browse tables for $subdomain");


function runSQLStatement($sql){
	set_time_limit(500);
	$result = mysql_query($sql);
	$updateOk = true;
	if ($result == 0 || $result == false){
		echo('Update failed ' . mysql_error());
	}
	return $updateOk;
}