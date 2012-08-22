<?php
class BookCoverProcessor{
	private $bookCoverPath;
	private $category;
	private $id;
	private $isn;
	private $isbn10;
	private $isbn13;
	private $upc;
	private $isEContent;
	private $cacheName;
	private $cacheFile;
	public $error;
	private $reload;
	private $logger;
	private $configArray;
	private $timer;
	public function loadCover($configArray, $timer, $logger){
		$this->configArray = $configArray;
		$this->timer = $timer;
		$this->logger = $logger;
		$this->log("Starting to load cover", PEAR_LOG_INFO);
		$this->bookCoverPath = $configArray['Site']['coverPath'];
		if (!$this->loadParameters()){
			return;
		}
		if (!$this->reload){
			$this->log("Looking for Cached cover", PEAR_LOG_INFO);
			if ($this->getCachedCover()){
				return;
			}
		}

		if ($this->isEContent){
			$this->initDatabaseConnection();
			if ($this->getCoverFromEContent()){
				return;
			}
		}

		$this->log("Looking for cover from providers", PEAR_LOG_INFO);
		if ($this->getCoverFromProvider()){
			return;
		}

		if ($this->getCoverFromMarc()){
			return;
		}

		$this->log("No image found, using die image", PEAR_LOG_INFO);
		$this->dieWithFailImage();

	}

	private function getCoverFromEContent(){
		if ($this->configArray['EContent']['library'] && isset($this->id) && is_numeric($this->id)){
			$this->log("Looking for eContent Cover", PEAR_LOG_INFO);
			$this->initMemcache();

			$this->log("Checking eContent database to see if there is a record for $this->id", PEAR_LOG_INFO);
			//Check the database to see if there is an existing title
			require_once('sys/eContent/EContentRecord.php');
			$epubFile = new EContentRecord();
			$epubFile->id = $this->id;
			if ($epubFile->find(true)){
				$this->log("Found an eContent record for $this->id, source is {$epubFile->source}", PEAR_LOG_INFO);
				//Get the cover for the epub if one exists.
				if ((strcasecmp($epubFile->source, 'OverDrive') == 0) && ($epubFile->cover == null || strlen($epubFile->cover) == 0)){
					$this->log("Record is an OverDrive record that needs cover information fetched.", PEAR_LOG_INFO);
					//Get the image from OverDrive
					require_once('Drivers/OverDriveDriver.php');
					$overDriveDriver = new OverDriveDriver();
					$filename = $overDriveDriver->getCoverUrl($epubFile);
					$this->log("Got OverDrive cover information for $epubFile->id $epubFile->sourceUrl", PEAR_LOG_INFO);
					$this->log("Received filename $filename", PEAR_LOG_INFO);
					if ($filename != null){
						$epubFile->cover = $filename;
						$ret = $epubFile->updateDetailed(false); //Don't update solr for performance reasons
						$this->log("Result of saving cover url is $ret", PEAR_LOG_INFO);
					}
				}elseif (preg_match('/Colorado State Gov\\. Docs/si', $epubFile->source) == 1){
					//Cover is colorado state flag
					$this->log("Record is a gov docs file.", PEAR_LOG_INFO);
					$themeName = $this->configArray['Site']['theme'];
					$filename = "interface/themes/{$themeName}/images/state_flag_of_colorado.png";
					if ($this->processImageURL($filename, true)){
						return;
					}
				}
				if ($epubFile->cover && strlen($epubFile->cover) > 0){
					$this->log("Cover for the file is specified as {$epubFile->cover}.", PEAR_LOG_INFO);
					if (strpos($epubFile->cover, 'http://') === 0){
						$filename = $epubFile->cover;


						if ($this->processImageURL($filename, true)){
							$this->timer->writeTimings();
							exit();
						}
					}else{
						$filename = $bookCoverPath . '/original/' . $epubFile->cover;
						global $localFile;
						$localFile = $bookCoverPath . '/' . $this->size . '/' . $cacheName . '.png';
						if (file_exists($filename)){

							if ($this->processImageURL($filename, true)){
								$this->timer->writeTimings();
								exit();
							}
						}else{
							$this->log("Did not find econtent cover file $filename", PEAR_LOG_ERR);
						}
					}
				}
			}
		}
		$this->log("Did not find a cover based on eContent information.", PEAR_LOG_INFO);
	}

	private function initDatabaseConnection(){
		// Setup Local Database Connection
		if (!defined('DB_DATAOBJECT_NO_OVERLOAD')){
			define('DB_DATAOBJECT_NO_OVERLOAD', 0);
		}
		$options =& PEAR::getStaticProperty('DB_DataObject', 'options');
		$options = $this->configArray['Database'];
		$this->logTime("Connect to databse");
	}

	private function initMemcache(){
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
		$this->logTime("Initialize Memcache");
	}

	private function loadParameters(){
		//Check parameters
		if (!count($_GET)) {
			$this->error = "No parameters provided.";
			return false;
		}
		$this->reload = isset($_GET['reload']);
		// Sanitize incoming parameters to avoid filesystem attacks.  We'll make sure the
		// provided size matches a whitelist, and we'll strip illegal characters from the
		// ISBN.
		$this->size = isset($_GET['size']) ? $_GET['size'] : 'small';
		if (!in_array($this->size, array('small', 'medium', 'large'))) {
			$this->error = "No size provided, please specify small, medium, or large.";
			return false;
		}
		$this->isn = isset($_GET['isn']) ? preg_replace('/[^0-9xX]/', '', $_GET['isn']) : null;
		if (strlen($this->isn) == 0){
			$this->isn = null;
		}
		$this->upc = isset($_GET['upc']) ? preg_replace('/[^0-9xX]/', '', $_GET['upc']) : null;
		if (strlen($this->upc) == 0){
			$this->upc = null;
		}
		$this->id = isset($_GET['id']) ? $_GET['id'] : null;
		$this->isEContent = isset($_GET['econtent']);
		$this->category = isset($_GET['category']) ? strtolower($_GET['category']) : null;
		//First check to see if this has a custom cover due to being an e-book
		if (preg_match('/econtentRecord\d+/', $this->id)){
			$this->isEContent = true;
			$this->id = substr($this->id, strlen('econtentRecord'));
			$this->log("Record is eContent short id is $id", PEAR_LOG_INFO);
		}
		if (!is_null($this->id)){
			if ($this->isEContent){
				$this->cacheName = 'econtent' . $this->id;
			}else{
				$this->cacheName = $this->id;
			}
		}else if (!is_null($this->isn)){
			$this->cacheName = $this->isn;
		}else if (!is_null($this->upc)){
			$this->cacheName = $this->upc;
		}else{
			$this->error = "ISN, UPC, or id must be provided.";
			return false;
		}
		$this->cacheFile = $this->bookCoverPath . '/' . $this->size . '/' . $this->cacheName . '.png';
		$this->logTime("load parameters");
		return true;
	}

	private function addCachingHeader(){
		//Add caching information
		$expires = 60*60*24*14;  //expire the cover in 2 weeks on the client side
		header("Cache-Control: maxage=".$expires);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
		$this->log("Added caching header", PEAR_LOG_INFO);
	}

	private function addModificationHeaders($filename){
		$timestamp = filemtime($filename);
		$this->logTime("Got filetimestamp $timestamp");
		$last_modified = substr(date('r', $timestamp), 0, -5).'GMT';
		$etag = '"'.md5($last_modified).'"';
		$this->logTime("Got last_modified $last_modified and etag $etag");
		// Send the headers
		header("Last-Modified: $last_modified");
		header("ETag: $etag");

		if ($this->reload){
			return true;
		}
		// See if the client has provided the required headers
		$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
		$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? 	stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : 	false;
		if (!$if_modified_since && !$if_none_match) {
			$this->log("Caching headers not sent, return full image", PEAR_LOG_INFO);
			return true;
		}
		// At least one of the headers is there - check them
		if ($if_none_match && $if_none_match != $etag) {
			$this->log("ETAG changed ", PEAR_LOG_INFO);
			return true; // etag is there but doesn't match
		}
		if ($if_modified_since && $if_modified_since != $last_modified) {
			$this->log("Last modified changed", PEAR_LOG_INFO);
			return true; // if-modified-since is there but doesn't match
		}
		// Nothing has changed since their last request - serve a 304 and exit
		$this->log("File has not been modified", PEAR_LOG_INFO);
		header('HTTP/1.0 304 Not Modified');
		return false;
	}

	private function returnImage($localPath){
		header('Content-type: image/png');
		if ($this->addModificationHeaders($localPath)){
			$this->logTime("Added modification headers");
			$this->addCachingHeader();
			$this->logTime("Added caching headers");
			ob_clean();
			flush();
			readfile($localPath);
			$this->log("Read file $localPath");
			$this->logTime("echo file $localPath");
		}else{
			$this->logTime("Added modification headers");
		}
	}

	private function getCoverFromProvider(){
		// Update to allow retrieval of covers based on upc
		if (!is_null($this->isn) || !is_null($this->upc)) {
			$this->log("Looking for picture based on isbn and upc.", PEAR_LOG_INFO);

			// Fetch from provider
			if (isset($this->configArray['Content']['coverimages'])) {
				$providers = explode(',', $this->configArray['Content']['coverimages']);
				foreach ($providers as $provider) {
					$this->log("Checking provider $provider", PEAR_LOG_INFO);
					$provider = explode(':', $provider);
					$func = $provider[0];
					$key = isset($provider[1]) ? $provider[1] : '';
					if ($this->$func($key)) {
						$this->log("Found image from $provider[0]", PEAR_LOG_INFO);
						$this->logTime("Checked $func");
						return true;
					}else{
						$this->logTime("Checked $func");
					}
				}

				//Have not found an image yet, check files uploaded by publisher
				if ($this->configArray['Content']['loadPublisherCovers'] && isset($this->isn) ){
					$this->log("Looking for image from publisher isbn10: $isbn10 isbn13: $isbn13 in $bookCoverPath/original/.", PEAR_LOG_INFO);
					$this->makeIsbn10And13();
					if ($this->getCoverFromPublisher($bookCoverPath . '/original/')){
						return true;
					}
					$this->log("Did not find a file in publisher folder.", PEAR_LOG_INFO);
				}

				$this->log("Could not find a cover, using default based on category $this->category.", PEAR_LOG_INFO);
			}
		}
	}

	private function makeIsbn10And13(){
		if (!is_null($this->isn) && strlen($this->isn) >= 10 ){
			global $localFile;
			require_once 'Drivers/marmot_inc/ISBNConverter.php';
			if (strlen($this->isn) == 10){
				//$this->log("Provided ISBN is 10 digits.", PEAR_LOG_INFO);
				$this->isbn10 = $this->isn;
				$this->isbn13 = ISBNConverter::convertISBN10to13($this->isbn10);
			}elseif (strlen($isbn) == 13){
				//$this->log("Provided ISBN is 13 digits.", PEAR_LOG_INFO);
				$this->isbn13 = $this->isn;
				$this->isbn10 = ISBNConverter::convertISBN13to10($this->isbn13);
			}
			$this->log("Loaded isbn10 $this->isbn10 and isbn13 $this->isbn13.", PEAR_LOG_INFO);
			$this->logTime("create isbn 10 and isbn 13");
		}
	}

	private function getCoverFromMarc(){
		if ($this->configArray['Content']['loadCoversFrom856'] && $id && is_numeric($id) && $category && strtolower($category) == 'other'){
			$this->log("Looking for picture as part of 856 tag.", PEAR_LOG_INFO);
			//Retrieve the marc record
			require_once 'sys/SearchObject/Factory.php';
			require_once 'sys/Solr.php';

			// Setup Search Engine Connection
			$class = $this->configArray['Index']['engine'];
			$url = $this->configArray['Index']['url'];
			$db = new $class($url);
			if ($this->configArray['System']['debug']) {
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
							if ($this->processImageURL($matches[1], true)){
								//We got a successful match
								$this->timer->writeTimings();
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
	private function getCachedCover(){
		$imageFound = false;
		$filenames = array(
			"{$this->bookCoverPath}/{$this->size}/{$this->cacheName}.png" => 'png',
			//"{$this->bookCoverPath}/{$this->size}/{$this->cacheName}.jpg" => 'jpg',
		);

		foreach ($filenames as $filename => $type){
			$this->log("Checking $filename", PEAR_LOG_INFO);
			if (is_readable($filename)) {
				// Load local cache if available
				$this->logTime("Found cached cover");
				$this->log("$filename exists, returning", PEAR_LOG_INFO);
				$this->returnImage($filename, $type);
				$imageFound = true;
				break;
			}else{
				$this->logTime("Did not find cached cover");
			}
		}
		$this->logTime("Finished checking for cached cover.");
		return $imageFound;
	}

	/**
	 * Display a "cover unavailable" graphic and terminate execution.
	 */
	function dieWithFailImage(){
		$useDefaultNoCover = true;

		$themeName = $this->configArray['Site']['theme'];
		if (isset($this->category) && strlen($this->category) > 0){
			if (is_readable("interface/themes/{$themeName}/images/{$this->category}_{$this->size}.png")){
				$this->log("Found category image {$this->category}_{$this->size} .", PEAR_LOG_INFO);
				$nocoverurl = "interface/themes/{$themeName}/images/{$this->category}_{$this->size}.png";
				$useDefaultNoCover = false;
			}elseif (is_readable("interface/themes/{$themeName}/images/$this->category.png")){
				$nocoverurl = "interface/themes/{$themeName}/images/$this->category.png";
				header('Content-type: image/png');
				$useDefaultNoCover = false;
			}elseif (is_readable("interface/themes/default/images/{$this->category}_{$this->size}.png")){
				$this->log("Found category image {$this->category}_{$this->size} .", PEAR_LOG_INFO);
				$nocoverurl = "interface/themes/default/images/{$this->category}_{$this->size}.png";
				header('Content-type: image/png');
				$useDefaultNoCover = false;
			}elseif (is_readable("interface/themes/default/images/$this->category.png")){
				$nocoverurl = "interface/themes/default/images/$this->category.png";
				header('Content-type: image/png');
				$useDefaultNoCover = false;
			}
		}

		if ($useDefaultNoCover){
			$nocoverurl = "interface/themes/default/images/noCover2.png";
			header('Content-type: image/png');
		}

		$ret = $this->processImageURL($nocoverurl, true);
		//$ret = copy($nocoverurl, $this->cacheFile);
		if (!$ret){
			$this->error = "Unable to copy file $nocoverurl to $this->cacheFile";
			return false;
		}else{
			return true;
		}


	}

	function processImageURL($url, $cache = true) {
		$this->log("Processing $url", PEAR_LOG_INFO);

		if ($image = @file_get_contents($url)) {
			// Figure out file paths -- $tempFile will be used to store the downloaded
			// image for analysis.  $finalFile will be used for long-term storage if
			// $cache is true or for temporary display purposes if $cache is false.
			$tempFile = str_replace('.png', uniqid(), $this->cacheFile);
			$finalFile = $cache ? $this->cacheFile : $tempFile . '.png';
			$this->log("Processing url $url to $finalFile");

			// If some services can't provide an image, they will serve a 1x1 blank
			// or give us invalid image data.  Let's analyze what came back before
			// proceeding.
			if (!@file_put_contents($tempFile, $image)) {
				$this->log("Unable to write to image directory $tempFile.", PEAR_LOG_ERR);
				$this->error = "Unable to write to image directory $tempFile.";
				return false;
			}
			list($width, $height, $type) = @getimagesize($tempFile);

			// File too small -- delete it and report failure.
			if ($width < 2 && $height < 2) {
				@unlink($tempFile);
				return false;
			}

			if ($this->size == 'small'){
				$maxDimension = 100;
			}elseif ($this->size == 'medium'){
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

				$this->log("Resizing image New Width: $new_width, New Height: $new_height", PEAR_LOG_INFO);

				// create a new temporary image
				$tmp_img = imagecreatetruecolor( $new_width, $new_height );

				$imageResource = imagecreatefromstring($image);
				// copy and resize old image into new image
				if (!imagecopyresampled( $tmp_img, $imageResource, 0, 0, 0, 0, $new_width, $new_height, $width, $height )){
					$this->log("Could not resize image $url to $localFile", PEAR_LOG_ERR);
					return false;
				}

				// save thumbnail into a file
				if (!@imagepng( $tmp_img, $finalFile, 9)){
					$this->log("Could not save resized file $localFile", PEAR_LOG_ERR);
					return false;
				}


			}else{
				$this->log("Image is the correct size, not resizing.", PEAR_LOG_INFO);

				// Conversion needed -- do some normalization for non-PNG images:
				if ($type != IMAGETYPE_PNG) {
					$this->log("Image is not a png, converting to png.", PEAR_LOG_INFO);

					$conversionOk = true;
					// Try to create a GD image and rewrite as PNG, fail if we can't:
					if (!($imageGD = @imagecreatefromstring($image))) {
						$this->log("Could not create image from string $url", PEAR_LOG_ERR);
						$conversionOk = false;
					}
					if (!@imagepng($imageGD, $finalFile, 9)) {
						$this->log("Could not save image to file $url $localFile", PEAR_LOG_ERR);
						$conversionOk = false;
					}
					// We no longer need the temp file:
					@unlink($tempFile);
					imagedestroy($imageGD);
					if (!$conversionOk){
						return false;
					}
					$this->log("Finished creating png at $finalFile.", PEAR_LOG_INFO);
				} else {
					// If $tempFile is already a PNG, let's store it in the cache.
					@rename($tempFile, $finalFile);
				}
			}

			// Display the image:
			$this->returnImage($finalFile, 'png');

			// If we don't want to cache the image, delete it now that we're done.
			if (!$cache) {
				@unlink($finalFile);
			}
			$this->logTime("Finished processing image url");

			return true;
		} else {
			$this->log("Could not load the file as an image $url", PEAR_LOG_INFO);
			return false;
		}
	}

	/**
	 * Load image from URL, store in cache if requested, display if possible.
	 *
	 * @param   $url        URL to load image from
	 * @param   $cache      Boolean -- should we store in local cache?
	 * @return  bool        True if image displayed, false on failure.
	 */


	function syndetics($id)
	{
		if (is_null($this->isn) && is_null($this->upc)){
			return false;
		}
		switch ($this->size) {
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

		$url = isset($this->configArray['Syndetics']['url']) ? $this->configArray['Syndetics']['url'] : 'http://syndetics.com';
		$url .= "/index.aspx?type=xw12&isbn={$this->isn}/{$size}&client={$id}&upc=" . (!is_null($this->upc) ? $this->upc : '');
		return $this->processImageURL($url);
	}

	function librarything($id)
	{
		if (is_null($this->isn)){
			return false;
		}
		$url = 'http://covers.librarything.com/devkey/' . $id . '/' . $this->size . '/isbn/' . $this->isn;
		return $this->processImageURL($url);
	}

	function openlibrary($id = null)
	{
		if (is_null($this->isn)){
			return false;
		}
		// Convert internal size value to openlibrary equivalent:
		switch($this->size) {
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
		$url = "http://covers.openlibrary.org/b/isbn/{$this->isn}-{$size}.jpg?default=false";
		return $this->processImageURL($url);
	}

	function google($id = null)
	{
		if (is_null($this->isn)){
			return false;
		}
		if (is_callable('json_decode')) {
			$url = 'http://books.google.com/books?jscmd=viewapi&' .
	               'bibkeys=ISBN:' . $this->isn . '&callback=addTheCover';
			$client = new Proxy_Request();
			$client->setMethod(HTTP_REQUEST_METHOD_GET);
			$client->setURL($url);

			$result = $client->sendRequest();
			if (!PEAR::isError($result)) {
				$json = $client->getResponseBody();

				// strip off addthecover( -- note that we need to account for length of ISBN (10 or 13)
				$json = substr($json, 21 + strlen($this->isn));
				// strip off );
				$json = substr($json, 0, -3);
				// convert \x26 to &
				$json = str_replace("\\x26", "&", $json);
				if ($json = json_decode($json, true)) {
					//The google API always returns small images by default, but we can manipulate the URL to get larger images
					$size = $this->size;
					if (isset($json['thumbnail_url'])){
						$imageUrl = $json['thumbnail_url'];
						if ($size == 'small'){

						}else if ($size == 'medium'){
							$imageUrl = preg_replace('/zoom=\d/', 'zoom=1', $imageUrl);
						}else{ //large
							$imageUrl = preg_replace('/zoom=\d/', 'zoom=0', $imageUrl);
						}
						return $this->processImageURL($imageUrl, true);
					}
				}
			}
		}
		return false;
	}

	function amazon($id)
	{
		if (is_null($this->isn)){
			return false;
		}
		require_once 'sys/Amazon.php';
		require_once 'XML/Unserializer.php';

		$params = array('ResponseGroup' => 'Images', 'ItemId' => $this->isn);
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
				switch ($this->size) {
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
					return $this->processImageURL($imageUrl, false);
				}
			}
		}

		return false;
	}

	function getCoverFromPublisher($folderToCheck){
		if (!file_exists($folderToCheck)){
			$this->log("No publisher directory, expected to find in $folderToCheck", PEAR_LOG_INFO);
			return;
		}
		//$this->log("Looking in folder $folderToCheck for cover image supplied by publisher.", PEAR_LOG_INFO);
		//Check to see if the file exists in the folder

		$matchingFiles10 = glob($folderToCheck . $this->isbn10 . "*.jpg");
		$matchingFiles13 = glob($folderToCheck . $this->isbn13 . "*.jpg");
		if (count($matchingFiles10) > 0){
			//We found a match
			$this->log("Found a publisher file by 10 digit ISBN " . $matchingFiles10[0], PEAR_LOG_INFO);
			return $this->processImageURL($matchingFiles10[0], true);
		}elseif(count($matchingFiles13) > 0){
			//We found a match
			$this->log("Found a publisher file by 13 digit ISBN " . $matchingFiles13[0], PEAR_LOG_INFO);
			return $this->processImageURL($matchingFiles13[0], true);
		}else{
			//$this->log("Did not find match by isbn 10 or isbn 13, checking sub folders", PEAR_LOG_INFO);
			//Check all subdirectories of the current folder
			$subDirectories = array();
			$dh = opendir($folderToCheck);
			if ($dh){
				while (($file = readdir($dh)) !== false) {

					if (is_dir($folderToCheck . $file) && $file != '.' && $file != '..'){
						//$this->log("Found file $file", PEAR_LOG_INFO);
						$subDirectories[] = $folderToCheck . $file . '/';
					}
				}
				closedir($dh);
				foreach ($subDirectories as $subDir){
					//$this->log("Looking in subfolder $subDir for cover image supplied by publisher.");
					if ($this->getCoverFromPublisher($subDir)){
						return true;
					}
				}
			}
		}
		return false;
	}

	function log($message, $level){
		//return;
		$this->logger->log($message, $level);
	}

	function logTime($message){
		//return;
		$this->timer->logTime($message);
	}
}