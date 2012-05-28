<?php 
require_once 'PackagingDatabase.php';
ini_set('display_errors', true);
error_reporting(E_ALL);

// read configuration file
$packagingServiceConfig = array();
// Retrieve values from configuration file
require_once 'sys/ConfigArray.php';
$configArray = readConfig();

// connect to database
$db = PackagingDatabase::connect(
	$configArray['Database']['host'],
	$configArray['Database']['username'],
	$configArray['Database']['password'],
	$configArray['Database']['database']
);

// make sure we got a good db connection
if ($db === false) {
	die('Unable to connect to database.');
}

$method = isset($_REQUEST['method']) ? $_REQUEST['method'] : 'index';
switch ($method) {
	case 'GetFileProtectionStatus':
		echo json_encode(GetFileProtectionStatus($db));
		break;
	case 'RequestFileProtection':
		echo json_encode(RequestFileProtection($db));
		break;
	default:
		die('Invalid method.');
}

function GetFileProtectionStatus($db){
	if (!isset($_REQUEST['distributorId'])){
		return array(
			'success' => false,
			'error' => 'You must provide the distributorId to return information for'
		);
	}
	$distributorId = $_REQUEST['distributorId'];
	if (isset($_REQUEST['updatedSince']) && is_numeric($_REQUEST['updatedSince'])){
		$records = $db->getRecordsSince($distributorId, $_REQUEST['updatedSince']);
		return array(
			'success' => true,
			'records' => $records
		);
	}else if (isset($_REQUEST['packagingId']) && is_numeric($_REQUEST['packagingId'])){
		$result = $db->getRecord($distributorId, $_REQUEST['packagingId']);
		return $result;
	}else{
		$curTime = time();
		$sixHoursAgo = $curTime - 6 * 60 * 60;
		$records = $db->getRecordsSince($distributorId, $sixHoursAgo);
		return array(
			'success' => true,
			'records' => $records
		);
	}
}

function RequestFileProtection($db){
	if (!isset($_REQUEST['distributorId'])){
		return array(
			'success' => false,
			'error' => 'You must provide the distributorId to get protection for'
		);
	}else if (!isset($_REQUEST['filename'])){
		return array(
			'success' => false,
			'error' => 'You must provide the filename to provide protection for.'
		);
	}else if (!isset($_REQUEST['copies']) && is_numeric($_REQUEST['copies'])){
		return array(
			'success' => false,
			'error' => 'You must provide the number of copies to provide protection for as the copies parameter'
		);
	}
	$distributorId = $_REQUEST['distributorId'];
	$filename = $_REQUEST['filename'];
	$copies = $_REQUEST['copies'];
	$previousAcsId = isset($_REQUEST['previousAcsId']) ? $_REQUEST['previousAcsId'] : null; 
	
	$result = $db->requestFileProtection($distributorId, $filename, $copies, $previousAcsId);
	return $result;
}