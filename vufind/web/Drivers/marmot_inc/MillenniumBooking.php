<?php
/**
 * Created by PhpStorm.
 * User: Pascal Brammeier
 * Date: 7/13/2015
 * Time: 10:52 AM
 */

class MillenniumBooking {
	/** @var  MillenniumDriver $driver */
	private $driver;
//	private $bookings = array();

	public function __construct($driver){
		/** @var  MillenniumDriver $driver */
		$this->driver = $driver;
	}

	public function __destruct(){
		$this->_close_curl();
	}

 // Curl Connection Resources
	private $cookieJar,
		$curl_connection;

	public function setCookieJar(){
		$cookieJar = tempnam("/tmp", "CURLCOOKIE");
		$this->cookieJar = $cookieJar;
	}

	/**
	 * @return mixed CookieJar name
	 */
	public function getCookieJar() {
		if (is_null($this->cookieJar)) $this->setCookieJar();
		return $this->cookieJar;
	}

	/**
	 * Initialize and configure curl connection
	 *
	 * @param null $curl_url optional url passed to curl_init
	 * @param null|Array $curl_options is an array of curl options to include or overwrite.
	 *                    Keys is the curl option constant, Values is the value to set the option to.
	 * @return resource
	 */
	public function _curl_connect($curl_url = null, $curl_options = null){
		// differences from James' version
//		curl_setopt($this->curl_connection, CURLOPT_USERAGENT,"Pika 2015.10.0");

		$header = array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";

		$cookie = $this->getCookieJar();

		$this->curl_connection = curl_init($curl_url);
		$default_curl_options = array(
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_UNRESTRICTED_AUTH => true,
			CURLOPT_COOKIEJAR => $cookie,
			CURLOPT_COOKIESESSION => true,
			CURLOPT_FORBID_REUSE => false,
			CURLOPT_HEADER => false,
//			CURLOPT_HEADER => true, // debugging only
//			CURLOPT_VERBOSE => true, // debugging only
		);

		if ($curl_options) $default_curl_options = array_merge($default_curl_options, $curl_options);
		$result =
			curl_setopt_array($this->curl_connection, $default_curl_options);

		return $this->curl_connection;
	}

	public function _close_curl() {
		if ($this->curl_connection) curl_close($this->curl_connection);
		if ($this->cookieJar) unlink($this->cookieJar);
	}

	public function _curl_login() {
		global $configArray, $logger;
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo";
		$post_data   = $this->driver->_getLoginFormValues();
		$post_string = http_build_query($post_data);

		$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);

		$this->_curl_connect($curl_url);
		curl_setopt_array($this->curl_connection, array(
			CURLOPT_POST => true, // default is post
			CURLOPT_POSTFIELDS => $post_string
		));

		$loginResult = curl_exec($this->curl_connection); // Load the page, but we don't need to do anything with the results.

		//When a library uses Encore, the initial login does a redirect and requires additional parameters.
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResult, $loginMatches)) {
			$lt = $loginMatches[1]; //G et the lt value

			//Login again
			$post_data['lt']       = $lt;
			$post_data['_eventId'] = 'submit';
			$post_string = http_build_query($post_data);
			curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $post_string);

			$loginResult = curl_exec($this->curl_connection);
//			$curlInfo    = curl_getinfo($this->curl_connection); // debug info
		}
		return $loginResult; // TODO read $loginResult for a successful login??, then return $success boolean,  or $error
	}

	/**
	 * Taken from the class MarcRecord method getShortId.
	 *
	 * TODO: if we end up using that class, use it instead
	 *
	 * @param $longId  III record Id with the 8th check digit included
	 * @return mixed|string the initial dot & the trailing check digit removed
	 */
	public static function getShortId($longId){
		$shortId = str_replace('.b', 'b', $longId);
		$shortId = substr($shortId, 0, strlen($shortId) -1);
		return $shortId;
	}

	public function bookMaterial($recordId, $startDate, $startTime = null, $endDate = null, $endTime = null){
		if (empty($recordId) || empty($startDate)) { // at least these two fields should be required input
			if (!$recordId) return array('success' => false, 'message' => 'Item ID required');
			else return array('success' => false, 'message' => 'Start Date Required.');
		}
		if (!$startTime) $startTime = '8:00am';   // set a default start time if not specified (a morning time)
		if (!$endDate)   $endDate = $startDate;   // set a default end date to the start date if not specified
		if (!$endTime)   $endTime = '8:00pm';     // set a default end time if not specified (an evening time)

		// set bib number in format .b{recordNumber}
		$bib = $this->getShortId($recordId);

		$startDateTime = new DateTime("$startDate $startTime");// create a date with input and set it to the format the ILS expects
		if (!$startDateTime) {
			return array('success' => false, 'message' => 'Invalid Start Date or Time.');
		}

		$endDateTime = new DateTime("$endDate $endTime");// create a date with input and set it to the format the ILS expects
		if (!$endDateTime){
			return array('success' => false, 'message' => 'Invalid End Date or Time.');
		}

//		$marc = $this->driver->getItemsFast($recordId, true); // first step to get item location code

		// Login to Millennium webPac
		$this->_curl_login();

//		$scope = $this->driver->getLibraryScope();
//		$bookingUrl = $configArray['Catalog']['url'] ."/webbook~S$scope?/$bib=&back=";

		global $configArray;
		$bookingUrl = $configArray['Catalog']['url'] ."/webbook?/$bib=&back=";
		// the strange get url parameters ?/$bib&back= is needed to avoid a response from the server claiming a 502 proxy error
		// Scope appears to be unnecessary at this point.

		// Get pagen from form
		$result = curl_setopt($this->curl_connection, CURLOPT_URL, $bookingUrl);
		$curlResponse = curl_exec($this->curl_connection);
//<input name="webbook_pagen" value="2" type="hidden">

		$tag = 'input';
		$tag_pattern =
			'@<(?P<tag>'.$tag.')           # <tag
      (?P<attributes>\s[^>]+)?       # attributes, if any
            \s*/?>                   # /> or just >, being lenient here
            @xsi';
		$attribute_pattern =
			'@
        (?P<name>\w+)                         # attribute name
        \s*=\s*
        (
            (?P<quote>[\"\'])(?P<value_quoted>.*?)(?P=quote)    # a quoted value
                                    |                           # or
            (?P<value_unquoted>[^\s"\']+?)(?:\s+|$)             # an unquoted value (terminated by whitespace or EOF)
        )
        @xsi';

		if(preg_match_all($tag_pattern, $curlResponse, $matches)) {
			foreach ($matches['attributes'] as $attributes) {
				if (preg_match_all($attribute_pattern, $attributes, $attributeMatches)) {
					$search = array_flip($attributeMatches['name']); //flip so that index can be used to get actual names & values of attributes
					if (array_key_exists('name', $search)) { // find name attribute
						$attributeName  = trim($attributeMatches['value_quoted'][$search['name'] ], '"\'');
						$attributeValue = trim($attributeMatches['value_quoted'][$search['value']], '"\'');
						if ($attributeName == 'webbook_pagen') {
							$pageN = $attributeValue;
						} elseif ($attributeName == 'webbook_loc') {
							$loc = $attributeValue;
						}
					}
				}
			}
		}

		global $user;
		$patronId = $user->username; // username seems to be the patron Id

		$post = array(
			'webbook_pnum' => $patronId,
			'webbook_pagen' => $pageN ? $pageN : '2', // needed, reading from screen scrape; 2 or 4 are the only values i have seen so far. plb 7-16-2015
//			'refresh_cal' => '0', // not needed
//			'webbook_loc' => 'flmdv', // this may only be needed when the scoping is used
		  'webbook_bgn_Month' => $startDateTime->format('m'),
			'webbook_bgn_Day' => $startDateTime->format('d'),
			'webbook_bgn_Year' => $startDateTime->format('Y'),
			'webbook_bgn_Hour' => $startDateTime->format('h'),
			'webbook_bgn_Min' => $startDateTime->format('i'),
			'webbook_bgn_AMPM' => $startDateTime->format('H') > 11 ? 'PM' : 'AM',
			'webbook_end_n_Month' => $endDateTime->format('m'),
			'webbook_end_n_Day' => $endDateTime->format('d'),
			'webbook_end_n_Year' => $endDateTime->format('Y'),
			'webbook_end_n_Hour' => $endDateTime->format('h'),
			'webbook_end_n_Min' => $endDateTime->format('i'),
			'webbook_end_n_AMPM' => $endDateTime->format('H') > 11 ? 'PM' : 'AM', // has to be uppercase for the screenscraping
			'webbook_note' => '', //TODO

			// hidden items from the confirmation page
//			'webbook_item' => '',
//			'webbook_itemnum' => '',
		);
		if (!empty($loc)) $post['webbook_loc'] = $loc; // if we have this info add it, don't include otherwise.
		$postString = http_build_query($post);

		$result = curl_setopt_array($this->curl_connection, array(
//			CURLOPT_URL => $bookingUrl,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $postString,
		));

		$curlResponse = curl_exec($this->curl_connection);
		if ($curlError = curl_errno($this->curl_connection)) {
			//TODO log error as well.
			return array(
				'success' => false,
				'message' => 'There was an error communicating with the library system.'
			);
		}

		// Look for Error Messages
		$numMatches = preg_match('/<span.\s?class="errormessage">(?P<error>.+?)<\/span>/', $curlResponse, $matches);
		// ?P<name> syntax will creates named matches in the matches array
		if ($numMatches) {
			return array(
				'success' => false,
				'message' => is_array($matches['error']) ? implode('<br>', $matches['error']) : $matches['error'],
				'retry' => true, // communicate back that we think the user could adjust their input to get success
			);
		}

		// Look for Success Messages
		$numMatches = preg_match('/<span.\s?class="bookingsConfirmMsg">(?P<success>.+?)<\/span>/', $curlResponse, $matches);

		if ($numMatches) {
			return array(
				'success' => true,
				'message' => is_array($matches['success']) ? implode('<br>', $matches['success']) : $matches['success']
			);
		}

		// Catch all Failure
		//TODO: log error
		return array(
			'success' => false,
			'message' => 'There was an unexpected result while booking your material'
		);
	}

	public function getMyBookings(){
		$patronDump = $this->driver->_getPatronDump($this->driver->_getBarcode());

		// Fetch Millennium Webpac Bookings page
		$html = $this->driver->_fetchPatronInfoPage($patronDump, 'bookings');

		// Parse out Bookings Information
		$bookings = $this->parseBookingsPage($html);

		require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			foreach($bookings as /*$key =>*/ &$booking){
				disableErrorHandler();
				$recordDriver = new MarcRecord($booking['id']);
				if ($recordDriver->isValid()){
//					$booking['id'] = $recordDriver->getUniqueID(); //redundant
					$booking['shortId'] = $recordDriver->getShortId();
					//Load title, author, and format information about the title
					$booking['title'] = $recordDriver->getTitle();
					$booking['sortTitle'] = $recordDriver->getSortableTitle();
					$booking['author'] = $recordDriver->getAuthor();
					$booking['format'] = $recordDriver->getFormat();
					$booking['isbn'] = $recordDriver->getCleanISBN();
					$booking['upc'] = $recordDriver->getCleanUPC();
					$booking['format_category'] = $recordDriver->getFormatCategory();

					//Load rating information
					$booking['ratingData'] = $recordDriver->getRatingData();

				}
				enableErrorHandler();
			}


		return $bookings;

	}

	private function parseBookingsPage($html) {
		$bookings = array();

//		// Column Headers
//		if(preg_match_all('/<th\\s+class="patFuncHeaders">\\s*(?<columnNames>[\\w\\s]*?)\\s*<\/th>/si', $html, $columnNames, PREG_SET_ORDER)) {
//			foreach ($columnNames as $i => $col) {
//				$columnNames[$i] = $col['columnNames'];
//				$columnNames[$col['columnNames']] = $i; // set keys to get column order
//			}
//		}

		// Table Rows for each Booking
		if(preg_match_all('/<tr\\s+class="patFuncEntry">(?<bookingRow>.*?)<\/tr>/si', $html, $rows, PREG_SET_ORDER)) {
			foreach ($rows as $index => $row) { // Go through each row

				// Get Record/Title
				if (!preg_match('/.*?<a href=\\"\/record=(?<recordId>.*?)(?:~S\\d{1,2})\\">(?<title>.*?)<\/a>.*/', $row['bookingRow'], $matches))
						 preg_match('/.*<a href=".*?\/record\/C__R(?<recordId>.*?)\\?.*?">(?<title>.*?)<\/a>.*/si',    $row['bookingRow'], $matches);
				// Don't know if this situation comes into play. It is taken from millennium holds parser. plb 7-17-2015

				$shortId = $matches['recordId'];
				$bibId = '.' . $shortId . $this->driver->getCheckDigit($shortId);
				$title = strip_tags($matches['title']);

					// Get From & To Dates
				if (preg_match_all('/.*?<td nowrap class=\\"patFuncBookDate\\">(?<bookingDate>.*?)<\/td>.*/', $row['bookingRow'], $matches, PREG_SET_ORDER)) {
					$startDateTime = trim($matches[0]['bookingDate']); // time component looks ambiguous
					$endDateTime   = trim($matches[1]['bookingDate']);
				} else {
					$startDateTime = null;
					$endDateTime = null;
				}

				// Get Status
				if (preg_match('/.*?<td nowrap class=\\"patFuncStatus\\">(?<status>.*?)<\/td>.*/', $row['bookingRow'], $matches)) {
					$status = ($matches['status'] == '&nbsp;') ? '' : $matches['status']; // at this point, I don't know what status we will ever see
				} else $status = '';

				$bookings[] = array(
					'id' => $bibId,
					'title' => $title,
					'startDateTime' => $startDateTime, //TODO set as DateTime objects?
					'EndDateTime' => $endDateTime,
					'status' => $status
				);

			}


		}
		return $bookings;
		}


}