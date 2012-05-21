<?php 
require_once 'PackagingDatabase.php';
ini_set('display_errors', true);
error_reporting(E_ALL);

// read configuration file
$packagingServiceConfig = array();
$configFile = '../../../sites/default/conf/packaging_web_service.ini';
if (file_exists($configFile)) {
	$packagingServiceConfig = @parse_ini_file($configFile, true);
} else {
	die('Configuration file not found.');
}

// connect to database
$db = PackagingDatabase::connect(
	$packagingServiceConfig['Database']['host'],
	$packagingServiceConfig['Database']['username'],
	$packagingServiceConfig['Database']['password'],
	$packagingServiceConfig['Database']['database']
);

// make sure we got a good db connection
if ($db === false) {
	die('Unable to connect to database.');
}

$method = isset($_REQUEST['method']) ? $_REQUEST['method'] : 'index';
switch ($method) {
	case 'getRecord':
		$record = $db->getRecord($_REQUEST['packagingId']);
		echo json_encode($record);
		break;
	case 'saveRecord':
		$id = $db->saveRecord($_REQUEST);
		$record = null;
		if ($id !== false) {
			$record = $db->getRecord($id);
		}
		echo json_encode($record);
		break;
	default:
		die('Invalid method.');
}
?>