<?php
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

/*
 * @todo    Convert this to an AJAX approach to allow for client side access to
 *          images.  Also investigate local caching approach.  What about using
 *          Squid?
 */
require_once 'sys/Proxy_Request.php';
require_once 'sys/Logger.php';
// Retrieve values from configuration file
require_once 'sys/ConfigArray.php';
$configArray = readConfig();

if ($configArray['System']['debug']) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_DEPRECATED);
}

global $memcache;
// Set defaults if nothing set in config file.
$host = isset($configArray['Caching']['memcache_host']) ? $configArray['Caching']['memcache_host'] : 'localhost';
$port = isset($configArray['Caching']['memcache_port']) ? $configArray['Caching']['memcache_port'] : 11211;
$timeout = isset($configArray['Caching']['memcache_connection_timeout']) ? $configArray['Caching']['memcache_connection_timeout'] : 1;

date_default_timezone_set($configArray['Site']['timezone']);
$bookCoverPath = $configArray['Site']['coverPath'];

require_once 'sys/Timer.php';
global $timer;
if (empty($timer)){
	$timer = new Timer(microtime(false));
}

// Connect to Memcache:
$memcache = new Memcache();
if (!$memcache->pconnect($host, $port, $timeout)) {
	PEAR::raiseError(new PEAR_Error("Could not connect to Memcache (host = {$host}, port = {$port})."));
}
$timer->logTime("Initialize Memcache");

global $logger;
$logger = new Logger();

if (!function_exists('vufind_autoloader')){
	// Set up autoloader (needed for YAML)
	function vufind_autoloader($class) {
		require str_replace('_', '/', $class) . '.php';
	}
	spl_autoload_register('vufind_autoloader');
}

// Proxy server settings
if (isset($configArray['Proxy']['host'])) {
	if (isset($configArray['Proxy']['port'])) {
		$proxy_server = $configArray['Proxy']['host'].":".$configArray['Proxy']['port'];
	} else {
		$proxy_server = $configArray['Proxy']['host'];
	}
	$proxy = array('http' => array('proxy' => "tcp://$proxy_server", 'request_fulluri' => true));
	stream_context_get_default($proxy);
}

if (!count($_GET)) {
	if ($configArray['System']['debug']) {
		echo("No parameters provided.");
	}
	dieWithFailImage($bookCoverPath);
}

// Setup Local Database Connection
if (!defined('DB_DATAOBJECT_NO_OVERLOAD')){
	define('DB_DATAOBJECT_NO_OVERLOAD', 0);
}
$options =& PEAR::getStaticProperty('DB_DataObject', 'options');
$options = $configArray['Database'];

// Sanitize incoming parameters to avoid filesystem attacks.  We'll make sure the
// provided size matches a whitelist, and we'll strip illegal characters from the
// ISBN.
$validSizes = array('small', 'medium', 'large');
if (!in_array($_GET['size'], $validSizes)) {
	if ($configArray['System']['debug']) {
		echo("No size provided, please specify small, medium, or large.");
	}
	dieWithFailImage($bookCoverPath, '');
}
$_GET['isn'] = preg_replace('/[^0-9xX]/', '', $_GET['isn']);
$_GET['upc'] = preg_replace('/[^0-9xX]/', '', $_GET['upc']);
$id = isset($_GET['id']) ? $_GET['id'] : null;
$cacheName = $id;
if (isset($_GET['econtent'])){
	$cacheName = 'econtent' . $id;
}
if (is_null($id)){
	if (isset($_GET['isn'])){
		$cacheName = $_GET['isn'];
	}else{
		$cacheName = $_GET['upc'];
	}
}

//Add caching information
$expires = 60*60*24*14;  //expire the cover in 2 weeks on the client side
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

//First Check to see if there is already a cached version by id or isbn.  If so, return that.
$timer->logTime("Finished basic setup of bookcovers");
if (!isset($_GET['reload'])){
	$tmpName = $bookCoverPath . '/' . $_GET['size'] . '/' . $cacheName . '.png';
	if ($id != null && is_readable($bookCoverPath . '/' . $_GET['size'] . '/' . $cacheName . '.jpg')) {
		// Load local cache if available
		$filename = $bookCoverPath . '/' . $_GET['size'] . '/' . $cacheName . '.jpg';
		header('Content-type: image/jpeg');
		setupHeaders($filename);
		echo readfile($filename);
		return;
	}else if ($id != null && is_readable($bookCoverPath . '/' . $_GET['size'] . '/' . $cacheName . '.png')) {
		// Load local cache if available
		$filename = $bookCoverPath . '/' . $_GET['size'] . '/' . $cacheName . '.png';
		header('Content-type: image/png');
		setupHeaders($filename);
		echo readfile($filename);
		//$logger->log("Found cached png file for id", PEAR_LOG_INFO);
		return;
	}else if (strlen($_GET['isn']) > 0 && is_readable($bookCoverPath . '/' . $_GET['size'] . '/' . $_GET['isn'] . '.jpg')) {
		// Load local cache if available
		$filename = $bookCoverPath . '/' . $_GET['size'] . '/' . $_GET['isn'] . '.jpg';
		header('Content-type: image/jpeg');
		setupHeaders($filename);
		echo readfile($filename);
		//$logger->log("Found cached jpg file for isbn", PEAR_LOG_INFO);
		return;
	}else if (strlen($_GET['upc']) > 0 && is_readable($bookCoverPath . '/' . $_GET['size'] . '/' . $_GET['upc'] . '.jpg')) {
		// Load local cache if available
		$filename = $bookCoverPath . '/' . $_GET['size'] . '/' . $_GET['upc'] . '.jpg';
		header('Content-type: image/jpeg');
		setupHeaders($filename);
		echo readfile($filename);
		//$logger->log("Found cached jpg file for upc", PEAR_LOG_INFO);
		return;
	}
	$timer->logTime("Finished checking for cached cover.");
}else{
	//$logger->log("Bypassing cache because reload was specified", PEAR_LOG_INFO);
}

//First check to see if this has a custom cover due to being an e-book
if (preg_match('/econtentRecord\d+/', $id)){
	$_GET['econtent'] = true;
	$id = substr($id, strlen('econtentRecord'));
	$logger->log("Record is eContent short id is $id", PEAR_LOG_INFO);
}
if ($configArray['EContent']['library'] && isset($_GET['econtent']) && isset($id) && is_numeric($id)){
	$logger->log("Checking eContent database to see if there is a record for $id", PEAR_LOG_INFO);
	//Check the database to see if there is an existing title
	require_once('sys/eContent/EContentRecord.php');
	$epubFile = new EContentRecord();
	$epubFile->id = $id;
	if ($epubFile->find(true)){
		$logger->log("Found an eContent record for $id", PEAR_LOG_INFO);
		//Get the cover for the epub if one exists.
		if ((strcasecmp($epubFile->source, 'OverDrive') == 0) && ($epubFile->cover == null || strlen($epubFile->cover) == 0)){
			$logger->log("Record is an OverDrive record that needs cover information fetched.", PEAR_LOG_INFO);
			//Get the image from OverDrive
			require_once('Drivers/OverDriveDriver.php');
			$overDriveDriver = new OverDriveDriver();
			$filename = $overDriveDriver->getCoverUrl($epubFile);
			$logger->log("Got OverDrive cover information for $epubFile->id $epubFile->sourceUrl", PEAR_LOG_INFO);
			$logger->log("Received filename $filename", PEAR_LOG_INFO);
			if ($filename != null){
				$epubFile->cover = $filename;
				$ret = $epubFile->updateDetailed(false); //Don't update solr for performance reasons
				$logger->log("Result of saving cover url is $ret", PEAR_LOG_INFO);
			}
		}
		if ($epubFile->cover && strlen($epubFile->cover) > 0){
			$logger->log("Cover for the file is specified as {$epubFile->cover}.", PEAR_LOG_INFO);
			if (strpos($epubFile->cover, 'http://') === 0){
				$filename = $epubFile->cover;
				global $localFile;
				$localFile = $bookCoverPath . '/' . $_GET['size'] . '/' . $cacheName . '.jpg';
					
				if (processImageURL($filename, true)){
					exit();
				}
			}else{
				$filename = $bookCoverPath . '/original/' . $epubFile->cover;
				global $localFile;
				$localFile = $bookCoverPath . '/' . $_GET['size'] . '/' . $cacheName . '.jpg';
				if (file_exists($filename)){

					if (processImageURL($filename, true)){
						exit();
					}
				}else{
					$logger->log("Did not find econtent file $filename");
				}
			}
		}
	}
}
$logger->log("Did not find a cover based on eContent information.", PEAR_LOG_INFO);

//Check to see if there is an existing copy that was uploaded from the publisher
if (isset($_GET['isn']) && strlen($_GET['isn']) >= 10 ){
	global $localFile;
	require_once 'Drivers/marmot_inc/ISBNConverter.php';
	$isbn = $_GET['isn'];
	if (strlen($isbn) == 10){
		//$logger->log("Provided ISBN is 10 digits.", PEAR_LOG_INFO);
		$isbn10 = $isbn;
		$isbn13 = ISBNConverter::convertISBN10to13($isbn10);
	}elseif (strlen($isbn) == 13){
		//$logger->log("Provided ISBN is 13 digits.", PEAR_LOG_INFO);
		$isbn13 = $isbn;
		$isbn10 = ISBNConverter::convertISBN13to10($isbn13);
	}
	$logger->log("Loaded isbn10 $isbn10 and isbn13 $isbn13.", PEAR_LOG_INFO);
}

//Check for historical society images that have the image in their marc record.
if (isset($_GET['category'])){
	$category = strtolower(str_replace(" ", "", $_GET['category']));
}
if ($id && is_numeric($id) && $category && strtolower($category) == 'other'){
	$logger->log("Looking for picture as part of 856 tag.", PEAR_LOG_INFO);
	//Check to see if the cached file already exists
	global $localFile;
	$localFile = $bookCoverPath . '/' . $_GET['size'] . '/' . $cacheName . '.jpg';
	if (is_readable($localFile)) {
		// Load local cache if available
		header('Content-type: image/jpeg');
		echo readfile($localFile);
		return;
	}else{
		//Retrieve the marc record
		require_once 'sys/SearchObject/Factory.php';
		require_once 'sys/Solr.php';

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$db = new $class($url);
		if ($configArray['System']['debug']) {
			$db->debug = true;
		}

		// Retrieve Full Marc Record
		if (!($record = $db->getRecord($id))) {
			PEAR::raiseError(new PEAR_Error('Record Does Not Exist'));
		}
		//Process the marc record
		require_once 'sys/MarcLoader.php';
		$marcRecord = MarcLoader::loadMarcRecordFromRecord($record);
		if ($marcRecord) {
			PEAR::raiseError(new PEAR_Error('Cannot Process MARC Record'));
		}
		//Get the 856 tags
		$marcFields = $marcRecord->getFields('856');
		if ($marcFields){
			$links = array();
			foreach ($marcFields as $marcField){
				if ($marcField->getSubfield('y')){
					$subfield_y = $marcField->getSubfield('y')->getData();
					if (preg_match('/.*<img.*src=[\'"](.*?)[\'"].*>.*/i', $subfield_y, $matches)){
						if (processImageURL($matches[1], true)){
							//We got a successful match
							exit;
						}
					}
				}else{
					//no image link available on this link
				}
			}
		}
	}
}

// Update to allow retrieval of covers based on upc
if ((isset($_GET['isn']) && !empty($_GET['isn'])) || (isset($_GET['upc']) && !empty($_GET['upc']))) {
	global $localFile;
	$logger->log("Looking for picture based on isbn and upc.", PEAR_LOG_INFO);
	if (isset($_GET['isn']) && !empty($_GET['isn'])){
		$localFile = $bookCoverPath . '/' . $_GET['size'] . '/' . $_GET['isn'] . '.jpg';
	}else{
		$localFile = $bookCoverPath . '/' . $_GET['size'] . '/' . $_GET['upc'] . '.jpg';
	}

	// Fetch from provider
	if (isset($configArray['Content']['coverimages'])) {
		$providers = explode(',', $configArray['Content']['coverimages']);
		foreach ($providers as $provider) {
			$logger->log("Checking provider $provider", PEAR_LOG_INFO);
			$provider = explode(':', $provider);
			$func = $provider[0];
			$key = isset($provider[1]) ? $provider[1] : '';
			if ($func($key)) {
				$logger->log("Found image from $provider[0]", PEAR_LOG_INFO);
				exit();
			}
		}

		//Have not found an image yet, check files uploaded by publisher
		if (isset($isbn10) ){
			$logger->log("Looking for image from publisher isbn10: $isbn10 isbn13: $isbn13 in $bookCoverPath/original/.", PEAR_LOG_INFO);
			$localFile = $bookCoverPath . '/' . $_GET['size'] . '/' . $cacheName . '.jpg';
			if (lookForPublisherFile($bookCoverPath . '/original/', $isbn10, $isbn13)){
				exit();
			}
			$logger->log("Did not find a file in publisher folder.", PEAR_LOG_INFO);
		}

		$logger->log("Could not find a cover, using default based on category $category.", PEAR_LOG_INFO);
		dieWithFailImage($bookCoverPath, $_GET['size'], $category, $cacheName);

	} else {
		dieWithFailImage($bookCoverPath, $_GET['size'], $category, $cacheName);
	}

} else {
	$logger->log("Could not find a cover, using default based on category $category.", PEAR_LOG_INFO);
	dieWithFailImage($bookCoverPath, $_GET['size'], $category, $cacheName);
}

/**
 * Display a "cover unavailable" graphic and terminate execution.
 */
function dieWithFailImage($bookCoverPath, $size, $category, $id){
	$useDefaultNoCover = true;

	global $localFile;
	global $logger;
	global $configArray;
	if (isset($category) && strlen($category) > 0){
		if (is_readable("interface/themes/{$configArray['Site']['theme']}/images/{$category}_{$size}.png")){
			$logger->log("Found category image {$category}_{$size} .", PEAR_LOG_INFO);
			$nocoverurl = "interface/themes/{$configArray['Site']['theme']}/images/{$category}_{$size}.png";
			$localFile = $bookCoverPath . '/' . $_GET['size'] . '/' . $id . '.png';
			header('Content-type: image/png');
			$useDefaultNoCover = false;
		}elseif (is_readable("interface/themes/{$configArray['Site']['theme']}/images/$category.png")){
			$nocoverurl = "interface/themes/{$configArray['Site']['theme']}/images/$category.png";
			$localFile = $bookCoverPath . '/' . $_GET['size'] . '/' . $id . '.png';
			header('Content-type: image/png');
			$useDefaultNoCover = false;
		}elseif (is_readable("interface/themes/default/images/{$category}_{$size}.png")){
			$logger->log("Found category image {$category}_{$size} .", PEAR_LOG_INFO);
			$nocoverurl = "interface/themes/default/images/{$category}_{$size}.png";
			$localFile = $bookCoverPath . '/' . $_GET['size'] . '/' . $id . '.png';
			header('Content-type: image/png');
			$useDefaultNoCover = false;
		}elseif (is_readable("interface/themes/default/images/$category.png")){
			$nocoverurl = "interface/themes/default/images/$category.png";
			$localFile = $bookCoverPath . '/' . $_GET['size'] . '/' . $id . '.png';
			header('Content-type: image/png');
			$useDefaultNoCover = false;
		}
	}

	if ($useDefaultNoCover){
		$localFile = $bookCoverPath . '/' . $_GET['size'] . '/' . $id . '.png';
		$nocoverurl = "interface/themes/default/images/noCover2.png";
		header('Content-type: image/png');
	}

	$ret = copy($nocoverurl, $localFile);
	if (!$ret){
		$logger->log("Unable to copy file $nocoverurl to $localFile", PEAR_LOG_INFO);
	}else{
		$logger->log("Created cached image of $nocoverurl as $localFile", PEAR_LOG_INFO);
	}

	$logger->log("Returning contents of $nocoverurl", PEAR_LOG_INFO);
	//header('Content-type: text/plain'); //Use for debugging notices and warnings
	echo readfile($nocoverurl);
	exit();
}

/**
 * Load image from URL, store in cache if requested, display if possible.
 *
 * @param   $url        URL to load image from
 * @param   $cache      Boolean -- should we store in local cache?
 * @return  bool        True if image displayed, false on failure.
 */
function processImageURL($url, $cache = true)
{
	global $localFile;
	global $logger;
	global $timer;
	$logger->log("Processing $url", PEAR_LOG_INFO);

	if ($image = @file_get_contents($url)) {
		// Figure out file paths -- $tempFile will be used to store the downloaded
		// image for analysis.  $finalFile will be used for long-term storage if
		// $cache is true or for temporary display purposes if $cache is false.
		$tempFile = str_replace('.jpg', uniqid(), $localFile);
		$finalFile = $cache ? $localFile : $tempFile . '.jpg';

		// If some services can't provide an image, they will serve a 1x1 blank
		// or give us invalid image data.  Let's analyze what came back before
		// proceeding.
		if (!@file_put_contents($tempFile, $image)) {
			$logger->log("Unable to write to image directory $tempFile.", PEAR_LOG_ERR);
			die("Unable to write to image directory $tempFile.");
		}
		list($width, $height, $type) = @getimagesize($tempFile);

		// File too small -- delete it and report failure.
		if ($width < 2 && $height < 2) {
			@unlink($tempFile);
			return false;
		}

		if ($_GET['size'] == 'small'){
			$maxDimension = 100;
		}elseif ($_GET['size'] == 'medium'){
			$maxDimension = 200;
		}else{
			$maxDimension = 400;
		}

		//Check to see if the image neds to be resized
		if ($width > $maxDimension || $height > $maxDimension){
			// We no longer need the temp file:
			@unlink($tempFile);

			if ($width > $height){
				$new_width = $maxDimension;
				$new_height = floor( $height * ( $maxDimension / $width ) );
			}else{
				$new_height = $maxDimension;
				$new_width = floor( $width * ( $maxDimension / $height ) );
			}

			//$logger->log("Resizing image New Width: $new_width, New Height: $new_height", PEAR_LOG_INFO);

			// create a new temporary image
			$tmp_img = imagecreatetruecolor( $new_width, $new_height );

			$imageResource = imagecreatefromstring($image);
			// copy and resize old image into new image
			if (!imagecopyresampled( $tmp_img, $imageResource, 0, 0, 0, 0, $new_width, $new_height, $width, $height )){
				$logger->log("Could not resize image $url to $localFile", PEAR_LOG_ERR);
				return false;
			}

			// save thumbnail into a file
			if (!@imagejpeg( $tmp_img, $finalFile, 90 )){
				$logger->log("Could not save resized file $localFile", PEAR_LOG_ERR);
				return false;
			}


		}else{
			//$logger->log("Image is the correct size, not resizing.", PEAR_LOG_INFO);

			// Conversion needed -- do some normalization for non-JPEG images:
			if ($type != IMAGETYPE_JPEG) {
				// We no longer need the temp file:
				@unlink($tempFile);

				// Try to create a GD image and rewrite as JPEG, fail if we can't:
				if (!($imageGD = @imagecreatefromstring($image))) {
					$logger->log("Could not create image from string $url", PEAR_LOG_ERR);
					return false;
				}
				if (!@imagejpeg($imageGD, $finalFile, 90)) {
					$logger->log("Could not save image to file $url $localFile", PEAR_LOG_ERR);
					return false;
				}
			} else {
				// If $tempFile is already a JPEG, let's store it in the cache.
				@rename($tempFile, $finalFile);
			}
		}

		// Display the image:
		header('Content-type: image/jpeg');
		//header('Content-type: text/plain'); //Use this to debug notices and warnings
		readfile($finalFile);

		// If we don't want to cache the image, delete it now that we're done.
		if (!$cache) {
			@unlink($finalFile);
		}
		$timer->logTime("Finished processing image url");

		return true;
	} else {
		//$logger->log("Could not load the file as an image $url", PEAR_LOG_INFO);
		return false;
	}
}

function syndetics($id)
{
	global $configArray;

	switch ($_GET['size']) {
		case 'small':
			$size = 'SC.GIF';
			break;
		case 'medium':
			$size = 'MC.GIF';
			break;
		case 'large':
			$size = 'LC.JPG';
			break;
	}

	$url = isset($configArray['Syndetics']['url']) ?
	$configArray['Syndetics']['url'] : 'http://syndetics.com';
	$url .= "/index.aspx?type=xw12&isbn={$_GET['isn']}/{$size}&client={$id}&upc={$_GET['upc']}";
	return processImageURL($url);
}

function librarything($id)
{
	if (!(isset($_GET['isn']) && !empty($_GET['isn']))){
		return false;
	}
	$url = 'http://covers.librarything.com/devkey/' . $id . '/' . $_GET['size'] . '/isbn/' . $_GET['isn'];
	return processImageURL($url);
}

function openlibrary()
{
	if (!(isset($_GET['isn']) && !empty($_GET['isn']))){
		return false;
	}
	// Convert internal size value to openlibrary equivalent:
	switch($_GET['size']) {
		case 'large':
			$size = 'L';
			break;
		case 'medium':
			$size = 'M';
			break;
		case 'small':
		default:
			$size = 'S';
			break;
	}

	// Retrieve the image; the default=false parameter indicates that we want a 404
	// if the ISBN is not supported.
	$url = "http://covers.openlibrary.org/b/isbn/{$_GET['isn']}-{$size}.jpg?default=false";
	return processImageURL($url);
}

function google()
{
	global $logger;
	if (!(isset($_GET['isn']) && !empty($_GET['isn']))){
		return false;
	}
	if (is_callable('json_decode')) {
		$url = 'http://books.google.com/books?jscmd=viewapi&' .
               'bibkeys=ISBN:' . $_GET['isn'] . '&callback=addTheCover';
		$client = new Proxy_Request();
		$client->setMethod(HTTP_REQUEST_METHOD_GET);
		$client->setURL($url);

		$result = $client->sendRequest();
		if (!PEAR::isError($result)) {
			$json = $client->getResponseBody();

			// strip off addthecover( -- note that we need to account for length of ISBN (10 or 13)
			$json = substr($json, 21 + strlen($_GET['isn']));
			// strip off );
			$json = substr($json, 0, -3);
			// convert \x26 to &
			$json = str_replace("\\x26", "&", $json);
			if ($json = json_decode($json, true)) {
				//The google API always returns small images by default, but we can manipulate the URL to get larger images
				$size = $_GET['size'];
				if (isset($json['thumbnail_url'])){
					$imageUrl = $json['thumbnail_url'];
					if ($size == 'small'){

					}else if ($size == 'medium'){
						$imageUrl = preg_replace('/zoom=\d/', 'zoom=1', $imageUrl);
					}else{ //large
						$imageUrl = preg_replace('/zoom=\d/', 'zoom=0', $imageUrl);
					}
					return processImageURL($imageUrl, true);
				}
			}
		}
	}
	return false;
}

function amazon($id)
{
	if (!(isset($_GET['isn']) && !empty($_GET['isn']))){
		return false;
	}
	require_once 'sys/Amazon.php';
	require_once 'XML/Unserializer.php';

	$params = array('ResponseGroup' => 'Images', 'ItemId' => $_GET['isn']);
	$request = new AWS_Request($id, 'ItemLookup', $params);
	$result = $request->sendRequest();
	if (!PEAR::isError($result)) {
		$unxml = new XML_Unserializer();
		$unxml->unserialize($result);
		$data = $unxml->getUnserializedData();
		if (PEAR::isError($data)) {
			return false;
		}
		if (isset($data['Items']['Item']) && !$data['Items']['Item']['ASIN']) {
			$data['Items']['Item'] = $data['Items']['Item'][0];
		}
		if (isset($data['Items']['Item'])) {
			// Where in the XML can we find the URL we need?
			switch ($_GET['size']) {
				case 'small':
					$imageIndex = 'SmallImage';
					break;
				case 'medium':
					$imageIndex = 'MediumImage';
					break;
				case 'large':
					$imageIndex = 'LargeImage';
					break;
				default:
					$imageIndex = false;
					break;
			}

			// Does a URL exist?
			if ($imageIndex && isset($data['Items']['Item'][$imageIndex]['URL'])) {
				$imageUrl = $data['Items']['Item'][$imageIndex]['URL'];
				return processImageURL($imageUrl, false);
			}
		}
	}

	return false;
}

function lookForPublisherFile($folderToCheck, $isbn10, $isbn13){
	global $logger;
	if (!file_exists($folderToCheck)){
		$logger->log("No publisher directory, expected to find in $folderToCheck", PEAR_LOG_INFO);
		return;
	}
	//$logger->log("Looking in folder $folderToCheck for cover image supplied by publisher.", PEAR_LOG_INFO);
	//Check to see if the file exists in the folder
	$matchingFiles10 = glob($folderToCheck . $isbn10 . "*.jpg");
	$matchingFiles13 = glob($folderToCheck . $isbn13 . "*.jpg");
	if (count($matchingFiles10) > 0){
		//We found a match
		$logger->log("Found a publisher file by 10 digit ISBN " . $matchingFiles10[0], PEAR_LOG_INFO);
		return processImageURL($matchingFiles10[0], true);
	}elseif(count($matchingFiles13) > 0){
		//We found a match
		$logger->log("Found a publisher file by 13 digit ISBN " . $matchingFiles13[0], PEAR_LOG_INFO);
		return processImageURL($matchingFiles13[0], true);
	}else{
		//$logger->log("Did not find match by isbn 10 or isbn 13, checking sub folders", PEAR_LOG_INFO);
		//Check all subdirectories of the current folder
		$subDirectories = array();
		$dh = opendir($folderToCheck);
		if ($dh){
			while (($file = readdir($dh)) !== false) {

				if (is_dir($folderToCheck . $file) && $file != '.' && $file != '..'){
					//$logger->log("Found file $file", PEAR_LOG_INFO);
					$subDirectories[] = $folderToCheck . $file . '/';
				}
			}
			closedir($dh);
			foreach ($subDirectories as $subDir){
				//$logger->log("Looking in subfolder $subDir for cover image supplied by publisher.");
				if (lookForPublisherFile($subDir, $isbn10, $isbn13)){
					return true;
				}
			}
		}
	}
	return false;
}

function setupHeaders($filename){
	$timestamp = filemtime($filename);
	$last_modified = substr(date('r', $timestamp), 0, -5).'GMT';
	$etag = '"'.md5($last_modified).'"';
	// Send the headers
	header("Last-Modified: $last_modified");
	header("ETag: $etag");
	// See if the client has provided the required headers
	$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
	$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? 	stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : 	false;
	if (!$if_modified_since && !$if_none_match) {
		return;
	}
	// At least one of the headers is there - check them
	if ($if_none_match && $if_none_match != $etag) {
		return; // etag is there but doesn't match
	}
	if ($if_modified_since && $if_modified_since != $last_modified) {
		return; // if-modified-since is there but doesn't match
	}
	// Nothing has changed since their last request - serve a 304 and exit
	header('HTTP/1.0 304 Not Modified');
	die();
}