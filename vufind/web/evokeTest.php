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

$curl_url = "http://www.evokecolorado.org/rest/v1/UserService/login";

//Login to the patron's account
$cookieJar = tempnam ("/tmp", "CURLCOOKIE");

$post_data = array(
	'user' => 'User5',
	'pass' => 'Test2014'
);
$post_items = array();
foreach ($post_data as $key => $value) {
	$post_items[] = $key . '=' . urlencode($value);
}
$post_string = implode ('&', $post_items);
$curl_connection = curl_init($curl_url . '?' . $post_string);

curl_setopt($curl_connection, CURLOPT_HTTPHEADER, array('Accept: application/json'));
curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
curl_setopt($curl_connection, CURLOPT_COOKIESESSION, false);
curl_setopt($curl_connection, CURLOPT_POST, false);
$loginResult = curl_exec($curl_connection);

echo("<h1>Login</h1>");
print_r($loginResult);

echo("<h1>Get All Records</h1>");
$curl_url = "http://www.evokecolorado.org/rest/v1/SearchService/SearchAll?limit=10000";
curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
$getLoanablesResult = curl_exec($curl_connection);

print_r($getLoanablesResult);

