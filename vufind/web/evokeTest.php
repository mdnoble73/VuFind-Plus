<?php
/**
 * Testing for eVoke integration
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/15/14
 * Time: 9:22 AM
 */
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'bootstrap.php';
require_once ROOT_DIR . '/Drivers/EVokeDriver.php';

//Connect to the driver
$driver = new EVokeDriver();

if (!isset($_REQUEST['username'])){
	echo("Specify the username to test with in a username parameter");
	return;
}
if (!isset($_REQUEST['password'])){
	echo("Specify the password to test with in a password parameter");
	return;
}
//Login to the patron's account
$user = new User();
$user->cat_username = $_REQUEST['username'];
$user->cat_password = $_REQUEST['password'];

$loginResult = $driver->login($user);

echo("<h1>Login</h1>");
print_r($loginResult);

/*echo("<h1>Get All Records</h1>");
$curl_url = "http://www.evokecolorado.org/rest/v1/SearchService/SearchAll?limit=10000";
curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
$getLoanablesResult = curl_exec($curl_connection);
$getLoanables = json_decode($getLoanablesResult);
print_r($getLoanablesResult);*/

/*echo("<h1>Get MARC for a record</h1>");
$curl_url = "http://www.evokecolorado.org/rest/v1/RecordService/Get_Record?recordId=00005363";
curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
$getRecordResult = curl_exec($curl_connection);

print_r($getRecordResult);*/

echo("<h1>Get eVoke Checked Out titles</h1>");
$checkedOutTitles = $driver->getCheckedOutItems($user);
print_r($checkedOutTitles);

echo("<h1>Get Titles on Hold</h1>");
$holds = $driver->getHolds($user);
print_r($holds);

echo("<h1>Get formats for a title</h1>");
$formatsForTitle = $driver->getFormatsForTitle('00006319');
print_r($formatsForTitle);

echo("<h1>Checkout title</h1>");
$checkoutResult = $driver->checkoutTitle('00006319', '0', $user);
print_r($checkoutResult);

echo("<h1>Return Title</h1>");
$returnResult = $driver->returnTitle($checkedOutTitles['items'][0]['loanId'], $user);
print_r($returnResult);

echo("<h1>Place Hold</h1>");
$placeHoldResult = $driver->placeHold('00006319', $user);
print_r($placeHoldResult);

echo("<h1>Cancel Hold</h1>");
$cancelHoldResult = $driver->cancelHold($placeHoldResult->response->reserveId, $user);
print_r($cancelHoldResult);
