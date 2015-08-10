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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 */

require_once ROOT_DIR . '/sys/SIP2.php';
require_once ROOT_DIR . '/Drivers/ScreenScrapingDriver.php';
abstract class Horizon extends ScreenScrapingDriver{

	protected $db;
	protected $useDb = true;
	protected $hipUrl;
	protected $hipProfile;
	protected $selfRegProfile;
	public $accountProfile;
	function __construct($accountProfile) {
		$this->accountProfile = $accountProfile;
		// Load Configuration for this Module
		global $configArray;

		$this->hipUrl = $configArray['Catalog']['hipUrl'];
		$this->hipProfile = $configArray['Catalog']['hipProfile'];
		$this->selfRegProfile = $configArray['Catalog']['selfRegProfile'];

		// Connect to database
		if (!isset($configArray['Catalog']['useDb']) || $configArray['Catalog']['useDb'] == true){
			try{
				if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0 ){
					sybase_min_client_severity(11);
					$this->db = @sybase_connect($configArray['Catalog']['database'] ,
					$configArray['Catalog']['username'],
					$configArray['Catalog']['password']);
				}else{
						$this->db = mssql_connect($configArray['Catalog']['host'] . ':' . $configArray['Catalog']['port'],
							$configArray['Catalog']['username'],
							$configArray['Catalog']['password']);
	
						// Select the database
						mssql_select_db($configArray['Catalog']['database']);
					}
			}catch (Exception $e){
					global $logger;
				$logger->log("Could not load Horizon database", PEAR_LOG_ERR);
			}
		}else{
				$this->useDb = false;
			}
	}
	/**
	 * Loads items information as quickly as possible (no direct calls to the ILS).  Does do filtering by loan rules
	 *
	 * return is an array of items with the following information:
	 *  location
	 *  callnumber
	 *  available
	 *  holdable
	 *  lastStatusCheck (time)
	 *
	 * @param $id
	 * @param $scopingEnabled
	 * @param $marcRecord
	 * @return mixed
	 */
	public function getItemsFast($id, $scopingEnabled, $marcRecord = null){
		$fastItems = $this->getHolding($id);
		return $fastItems;
	}

	/**
	 * Returns a summary of the holdings information for a single id. Used to display
	 * within the search results and at the top of a full record display to ensure
	 * the holding information makes sense to all users.
	 *
	 * @param string $id the id of the bid to load holdings for
	 * @return array an associative array with a summary of the holdings.
	 */
	public function getStatusSummary($id, $record = null, $mysip = null) {
		global $timer;
		global $library;
		global $locationSingleton;
		global $configArray;
		global $memCache;
		//Holdings summaries need to be cached based on the actual location since part of the information
		//includes local call numbers and statuses.
		$ipLocation = $locationSingleton->getPhysicalLocation();
		$location = $ipLocation;
		if (!isset($location) && $location == null) {
			$location = $locationSingleton->getUserHomeLocation();
		}
		$ipLibrary = null;
		if (isset($ipLocation)) {
			$ipLibrary = new Library();
			$ipLibrary->libraryId = $ipLocation->libraryId;
			if (!$ipLibrary->find(true)) {
				$ipLibrary = null;
			}
		}
		if (!isset($location) && $location == null) {
			$locationId = -1;
		} else {
			$locationId = $location->locationId;
		}
		$summaryInformation = $memCache->get("holdings_summary_{$id}_{$locationId}");
		if ($summaryInformation == false) {

			$canShowHoldButton = true;
			if ($library && $library->showHoldButton == 0) {
				$canShowHoldButton = false;
			}
			if ($location != null && $location->showHoldButton == 0) {
				$canShowHoldButton = false;
			}

			$holdings = $this->getStatus($id, $record, $mysip, true);
			$timer->logTime('Retrieved Status of holding');

			$counter = 0;
			$summaryInformation = array();
			$summaryInformation['recordId'] = $id;
			$summaryInformation['shortId'] = $id;
			$summaryInformation['isDownloadable'] = false; //Default value, reset later if needed.
			$summaryInformation['holdQueueLength'] = 0;

			//Check to see if we are getting issue summaries or actual holdings
			$isIssueSummary = false;
			$numSubscriptions = 0;
			if (count($holdings) > 0) {
				$lastHolding = end($holdings);
				if (isset($lastHolding['type']) && ($lastHolding['type'] == 'issueSummary' || $lastHolding['type'] == 'issue')) {
					$isIssueSummary = true;
					$issueSummaries = $holdings;
					$numSubscriptions = count($issueSummaries);
					$holdings = array();
					foreach ($issueSummaries as $issueSummary) {
						if (isset($issueSummary['holdings'])) {
							$holdings = array_merge($holdings, $issueSummary['holdings']);
						} else {
							//Create a fake holding for subscriptions so something
							//will be displayed in the holdings summary.
							$holdings[$issueSummary['location']] = array(
								'availability' => '1',
								'location' => $issueSummary['location'],
								'libraryDisplayName' => $issueSummary['location'],
								'callnumber' => $issueSummary['cALL'],
								'status' => 'Lib Use Only',
								'statusfull' => 'In Library Use Only',
							);
						}
					}
				}
			}
			$timer->logTime('Processed for subscriptions');

			//Valid statuses are:
			//Available by Request
			//  - not at the user's home branch or preferred location, but at least one copy is not checked out
			//  - do not show the call number
			//  - show place hold button
			//Checked Out
			//  - all copies are checked out
			//  - show the call number for the local library if any
			//  - show place hold button
			//Downloadable
			//  - there is at least one download link for the record.
			$numAvailableCopies = 0;
			$numHoldableCopies = 0;
			$numCopies = 0;
			$numCopiesOnOrder = 0;
			$availableLocations = array();
			$unavailableStatus = null;
			//The status of all items.  Will be set to an actual status if all are the same
			//or null if the item statuses are inconsistent
			$allItemStatus = '';
			$firstAvailableBarcode = '';
			$availableHere = false;
			foreach ($holdings as $holdingKey => $holding) {
				if (is_null($allItemStatus)) {
					//Do nothing, the status is not distinct
				} else {
					if ($allItemStatus == '') {
						$allItemStatus = $holding['statusfull'];
					} elseif ($allItemStatus != $holding['statusfull']) {
						$allItemStatus = null;
					}
				}
				if ($holding['availability'] == true) {
					if ($ipLocation && strcasecmp($holding['locationCode'], $ipLocation->code) == 0) {
						$availableHere = true;
					}
					$numAvailableCopies++;
					$addToAvailableLocation = false;
					$addToAdditionalAvailableLocation = false;
					//Check to see if the location should be listed in the list of locations that the title is available at.
					//Can only be in this system if there is a system active.
					if (!in_array($holding['locationCode'], array_keys($availableLocations))) {
						$locationMapLink = $this->getLocationMapLink($holding['locationCode']);
						if (strlen($locationMapLink) > 0) {
							$availableLocations[$holding['locationCode']] = "<a href='$locationMapLink' target='_blank'>" . preg_replace('/\s/', '&nbsp;', $holding['location']) . "</a>";
						} else {
							$availableLocations[$holding['locationCode']] = $holding['location'];
						}
					}
				} else {
					if ($unavailableStatus == null) {
						$unavailableStatus = $holding['statusfull'];
					}
				}

				if (isset($holding['holdable']) && $holding['holdable'] == 1) {
					$numHoldableCopies++;
				}
				$numCopies++;
				//Check to see if the holding has a download link and if so, set that info.
				if (isset($holding['link'])) {
					foreach ($holding['link'] as $link) {
						if ($link['isDownload']) {
							$summaryInformation['status'] = "Available for Download";
							$summaryInformation['class'] = 'here';
							$summaryInformation['isDownloadable'] = true;
							$summaryInformation['downloadLink'] = $link['link'];
							$summaryInformation['downloadText'] = $link['linkText'];
						}
					}
				}
				//Only show a call number if the book is at the user's home library, one of their preferred libraries, or in the library they are in.
				if (!isset($summaryInformation['callnumber'])) {
					$summaryInformation['callnumber'] = $holding['callnumber'];
				}
				if ($holding['availability'] == 1) {
					//The item is available within the physical library.  Patron should go get it off the shelf
					$summaryInformation['status'] = "Available At";
					if ($numHoldableCopies > 0) {
						$summaryInformation['showPlaceHold'] = $canShowHoldButton;
					} else {
						$summaryInformation['showPlaceHold'] = 0;
					}
					$summaryInformation['class'] = 'available';
				}
				if ($holding['holdQueueLength'] > $summaryInformation['holdQueueLength']) {
					$summaryInformation['holdQueueLength'] = $holding['holdQueueLength'];
				}
				if ($firstAvailableBarcode == '' && $holding['availability'] == true) {
					$firstAvailableBarcode = $holding['barcode'];
				}
			}
			$timer->logTime('Processed copies');

			//If all items are checked out the status will still be blank
			$summaryInformation['availableCopies'] = $numAvailableCopies;
			$summaryInformation['holdableCopies'] = $numHoldableCopies;

			$summaryInformation['numCopiesOnOrder'] = $numCopiesOnOrder;
			//Do some basic sanity checking to make sure that we show the total copies
			//With at least as many copies as the number of copies on order.
			if ($numCopies < $numCopiesOnOrder) {
				$summaryInformation['numCopies'] = $numCopiesOnOrder;
			} else {
				$summaryInformation['numCopies'] = $numCopies;
			}

			if ($unavailableStatus != 'ONLINE') {
				$summaryInformation['unavailableStatus'] = $unavailableStatus;
			}

			//Status is not set, check to see if the item is downloadable
			if (!isset($summaryInformation['status']) && !isset($summaryInformation['downloadLink'])) {
				// Retrieve Full Marc Record
				$recordURL = null;
				// Process MARC Data
				require_once ROOT_DIR . '/sys/MarcLoader.php';
				$marcRecord = MarcLoader::loadMarcRecordByILSId($id);
				if ($marcRecord) {
					//Check the 856 tag to see if there is a URL
					if ($linkField = $marcRecord->getField('856')) {
						if ($linkURLField = $linkField->getSubfield('u')) {
							$linkURL = $linkURLField->getData();
						}
						if ($linkTextField = $linkField->getSubfield('3')) {
							$linkText = $linkTextField->getData();
						} else {
							if ($linkTextField = $linkField->getSubfield('y')) {
								$linkText = $linkTextField->getData();
							} else {
								if ($linkTextField = $linkField->getSubfield('z')) {
									$linkText = $linkTextField->getData();
								}
							}
						}
					}
				} else {
					//Can't process the marc record, ignore it.
				}

				//If there is a link, add that status information.
				if (isset($linkURL)) {
					$isImageLink = preg_match('/.*\.(?:gif|jpg|jpeg|tif|tiff)/i', $linkURL);
					$isInternalLink = preg_match('/vufind|catalog/i', $linkURL);
					$isPurchaseLink = preg_match('/amazon|barnesandnoble/i', $linkURL);
					if ($isImageLink == 0 && $isInternalLink == 0 && $isPurchaseLink == 0) {
						$linkTestText = $linkText . ' ' . $linkURL;
						$isDownload = preg_match('/SpringerLink|NetLibrary|digital media|Online version\.|ebrary|gutenberg|emedia2go/i', $linkTestText);
						if ($linkTestText == 'digital media') {
							$linkText = 'OverDrive';
						}
						if (preg_match('/netlibrary/i', $linkURL)) {
							$isDownload = true;
							$linkText = 'NetLibrary';
						} elseif (preg_match('/ebscohost/i', $linkURL)) {
							$isDownload = true;
							$linkText = 'Ebsco';
						} elseif (preg_match('/overdrive|emedia2go/i', $linkURL)) {
							$isDownload = true;
							$linkText = 'OverDrive';
						} elseif (preg_match('/ebrary/i', $linkURL)) {
							$isDownload = true;
							$linkText = 'ebrary';
						} elseif (preg_match('/gutenberg/i', $linkURL)) {
							$isDownload = true;
							$linkText = 'Gutenberg Project';
						} elseif (preg_match('/ezproxy/i', $linkURL)) {
							$isDownload = true;
						} elseif (preg_match('/.*\.[pdf]/', $linkURL)) {
							$isDownload = true;
						}
						if ($isDownload) {
							$summaryInformation['status'] = "Available for Download";
							$summaryInformation['class'] = 'here';
							$summaryInformation['isDownloadable'] = true;
							$summaryInformation['downloadLink'] = $linkURL;
							$summaryInformation['downloadText'] = isset($linkText) ? $linkText : 'Download';
							//Check to see if this is an eBook or eAudio book.  We can get this from the 245h tag
							$isEBook = true;
							$resource = new Resource();
							$resource->record_id = $id;
							$resource->source = 'VuFind';
							if ($resource->find(true)) {
								$formatCategory = $resource->format_category;
								if (strcasecmp($formatCategory, 'eBooks') === 0) {
									$summaryInformation['eBookLink'] = $linkURL;
								} elseif (strcasecmp($formatCategory, 'eAudio') === 0) {
									$summaryInformation['eAudioLink'] = $linkURL;
								}
							}
						}
					}
				}
				$timer->logTime('Checked for downloadable link in 856 tag');
			}

			$showItsHere = ($ipLibrary == null) ? true : ($ipLibrary->showItsHere == 1);
			if ($availableHere && $showItsHere) {
				$summaryInformation['status'] = "It's Here";
				$summaryInformation['class'] = 'here';
				unset($availableLocations[$location->code]);
				$summaryInformation['currentLocation'] = $location->displayName;
				$summaryInformation['availableAt'] = join(', ', $availableLocations);
				$summaryInformation['numAvailableOther'] = count($availableLocations);
			} else {
				//Replace all spaces in the name of a location with no break spaces
				$summaryInformation['availableAt'] = join(', ', $availableLocations);
				$summaryInformation['numAvailableOther'] = count($availableLocations);
			}

			//If Status is still not set, apply some logic based on number of copies
			if (!isset($summaryInformation['status'])) {
				if ($numCopies == 0) {
					if ($numCopiesOnOrder > 0) {
						//No copies are currently available, but we do have some that are on order.
						//show the status as on order and make it available.
						$summaryInformation['status'] = "On Order";
						$summaryInformation['class'] = 'available';
						$summaryInformation['showPlaceHold'] = $canShowHoldButton;
					} else {
						//Deal with weird cases where there are no items by saying it is unavailable
						$summaryInformation['status'] = "Unavailable";
						$summaryInformation['showPlaceHold'] = false;
						$summaryInformation['class'] = 'unavailable';
					}
				} else {
					if ($numHoldableCopies == 0 && $canShowHoldButton && (isset($summaryInformation['showPlaceHold']) && $summaryInformation['showPlaceHold'] != true)) {
						$summaryInformation['status'] = "Not Available For Checkout";
						$summaryInformation['showPlaceHold'] = false;
						$summaryInformation['class'] = 'reserve';
					} else {
						$summaryInformation['status'] = "Checked Out";
						$summaryInformation['showPlaceHold'] = $canShowHoldButton;
						$summaryInformation['class'] = 'checkedOut';
					}
				}
			}

			//Reset status if the status for all items is consistent.
			//That way it will jive with the actual full record display.
			if ($allItemStatus != null && $allItemStatus != '') {
				//Only override this for statuses that don't have special meaning
				if ($summaryInformation['status'] != 'Marmot' && $summaryInformation['status'] != 'Available At' && $summaryInformation['status'] != "It's Here") {
					$summaryInformation['status'] = $allItemStatus;
				}
			}
			if ($allItemStatus == 'In Library Use Only') {
				$summaryInformation['inLibraryUseOnly'] = true;
			} else {
				$summaryInformation['inLibraryUseOnly'] = false;
			}


			if ($summaryInformation['availableCopies'] == 0 && $summaryInformation['isDownloadable'] == true) {
				$summaryInformation['showAvailabilityLine'] = false;
			} else {
				$summaryInformation['showAvailabilityLine'] = true;
			}
			$timer->logTime('Finished building summary');

			$memCache->set("holdings_summary_{$id}_{$locationId}", $summaryInformation, 0, $configArray['Caching']['holdings_summary']);
		}
		return $summaryInformation;
	}


	public function getMyFines($patron, $includeMessages){
		if ($this->useDb){
			return $this->getMyFinesViaDB($patron, $includeMessages);
		}else{
			return $this->getMyFinesViaHIP($patron, $includeMessages);
		}
	}

	public function getMyFinesViaHIP($patron, $includeMessages){
		global $user;
		global $configArray;
		global $logger;

		//Setup Curl
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$cookie = tempnam ("/tmp", "CURLCOOKIE");

		//Go to items out page
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$configArray['Catalog']['hipProfile']}&menu=account&submenu=blocks";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
		curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl_connection, CURLOPT_HEADER, false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$logger->log("Loading fines $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR_Singleton::raiseError('Could not load session information from page.');
		}

		//Login by posting username and password
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = array(
      'aspect' => 'overview',
      'button' => 'Login to Your Account',
      'login_prompt' => 'true',
      'menu' => 'account',
      'profile' => $configArray['Catalog']['hipProfile'],
      'ri' => '',
      'sec1' => $user->cat_username,
      'sec2' => $user->cat_password,
      'session' => $sessionId,
		);
		$post_string = http_build_query($post_data);

		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		preg_match_all('/<tr>.*?<td bgcolor="#FFFFFF"><a class="normalBlackFont2">(.*?)<\/a>.*?<a class="normalBlackFont2">(.*?)<\/a>.*?<a class="normalBlackFont2">(.*?)<\/a>.*?<a class="normalBlackFont2">(.*?)<\/a>.*?<\/tr>/s', $sresult, $messageInfo, PREG_SET_ORDER);
		$messages = array();
		for ($matchi = 0; $matchi < count($messageInfo); $matchi++) {
			$messages[] = array(
                'reason' => $messageInfo[$matchi][1],
                'amount' => $messageInfo[$matchi][3],
                'message' => ($messageInfo[$matchi][2] != '&nbsp;') ? $messageInfo[$matchi][2] : '',
                'date' => $messageInfo[$matchi][4]
			);
		}
		unlink($cookie);
		return $messages;
	}
	public function getMyFinesViaDB($patron, $includeMessages = false)
	{
		$sql = "select title_inverted.title as TITLE, item.bib# as BIB_NUM, item.item# as ITEM_NUM, " .
               "burb.borrower# as BORROWER_NUM, burb.amount as AMOUNT, burb.comment, " .
               "burb.date as DUEDATE, " .
               "burb.block as FINE, burb.amount as BALANCE from burb " .
               "left join item on item.item#=burb.item# " .
		           "left join title_inverted on title_inverted.bib# = item.bib# " .
               "join borrower on borrower.borrower#=burb.borrower# " .
               "join borrower_barcode on borrower_barcode.borrower#=burb.borrower# " .
               "where borrower_barcode.bbarcode='" . $patron->cat_username . "'" ;

		if ($includeMessages == false){
			$sql .= " and amount != 0";
		}
		//$sql .= " ORDER BY burb.date ASC";

		//print_r($sql);
		try {
			$sqlStmt = $this->_query($sql);

			$balance = 0;

			while ($row = $this->_fetch_assoc($sqlStmt)) {
				if (preg_match('/infocki|infodue|infocil|infocko|note|spec|supv/i', $row['FINE'])){
					continue;
				}

				//print_r($row);
				$checkout = '';
				$duedate = $this->addDays('1970-01-01', $row['DUEDATE']);
				$bib_num = $row['BIB_NUM'];
				$item_num = $row['ITEM_NUM'];
				$borrower_num = $row['BORROWER_NUM'];
				$amount = $row['AMOUNT'];
				$balance += $amount;
				$comment = is_null($row['comment']) ? $row['TITLE'] : $row['comment'];

				if (isset($bib_num) && isset($item_num))
				{
					$cko = "select date as CHECKOUT " .
                           "from burb where borrower#=" . $borrower_num . " " .
                           "and item#=" . $item_num . " and block='infocko'";
					$sqlStmt_cko = $this->_query($cko);

					if ($row_cko = $this->_fetch_assoc($sqlStmt_cko)) {
						$checkout = $this->addDays('1970-01-01', $row_cko['CHECKOUT']);
					}

					$due = "select convert(varchar(12),dateadd(dd, date, '01 jan 1970')) as DUEDATE " .
                           "from burb where borrower#=" . $borrower_num . " " .
                           "and item#=" . $item_num . " and block='infodue'";
					$sqlStmt_due = $this->_query($due);

					if ($row_due = $this->_fetch_assoc($sqlStmt_due)) {
						$duedate = $row_due['DUEDATE'];
					}
				}

				$fineList[] = array('id' => $bib_num,
                                    'message' => $comment,
                                    'amount' => $amount > 0 ? '$' . sprintf('%0.2f', $amount / 100) : '',
                                    'reason' => $this->translateFineMessageType($row['FINE']),
                                    'balance' => $balance,
                                    'checkout' => $checkout,
                                    'date' => date('M j, Y', strtotime($duedate)));
			}
			return $fineList;
		} catch (PDOException $e) {
			return new PEAR_Error($e->getMessage());
		}

	}

	/**
	 * @param User $user                     The User Object to make updates to
	 * @param boolean $canUpdateContactInfo  Permission check that updating is allowed
	 * @return array                         Array of error messages for errors that occurred
	 */
	function updatePatronInfo($user, $canUpdateContactInfo){
		$updateErrors = array();
		if ($canUpdateContactInfo) {
			global $configArray;
			//Check to make sure the patron alias is valid if provided
			if (isset($_REQUEST['displayName']) && $_REQUEST['displayName'] != $user->displayName && strlen($_REQUEST['displayName']) > 0) {
				//make sure the display name is less than 15 characters
				if (strlen($_REQUEST['displayName']) > 15) {
					$updateErrors[] = 'Sorry your display name must be 15 characters or less.';
					return $updateErrors;
				} else {
					//Make sure that we are not using bad words
					require_once ROOT_DIR . '/Drivers/marmot_inc/BadWord.php';
					$badWords     = new BadWord();
					$okToAdd = $badWords->hasBadWords($_REQUEST['displayName']);
					if (!$okToAdd) {
						$updateErrors[] = 'Sorry, that name is in use or invalid.';
						return $updateErrors;
					}
					//Make sure no one else is using that
					$userValidation = new User();
					$userValidation->query("SELECT * from {$userValidation->__table} WHERE id <> {$user->id} and displayName = '{$_REQUEST['displayName']}'");
					if ($userValidation->N > 0) {
						$updateErrors[] = 'Sorry, that name is in use or is invalid.';
						return $updateErrors;
					}
				}
			}

			// TODO Use screen scraping driver for curl ops
			//Setup Curl
			$curl_url        = $this->hipUrl . "/ipac20/ipac.jsp?profile={$configArray['Catalog']['hipProfile']}&menu=account";
			$this->_curl_connect($curl_url, array(
				CURLOPT_COOKIESESSION => true,
			));

			//Start at My Account Page
			$sresult = $this->_curlGetPage($curl_url);
			global $logger;
			$logger->log("Logging into user account from updatePatronInfo $curl_url", PEAR_LOG_INFO);

			//Extract the session id from the requestcopy javascript on the page
			if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
				$sessionId = $matches[1];
			} else {
				PEAR_Singleton::raiseError('Could not load session information from page.');
			}

			//Login by posting username and password
			$post_data   = array(
				'aspect' => 'overview',
				'button' => 'Login to Your Account',
				//'ipp' => '20',
				//'lastlogin' => '1299616721524',
				'login_prompt' => 'true',
				'menu' => 'account',
				//'npp' => '10',
				'profile' => $configArray['Catalog']['hipProfile'],
				'ri' => '',
				'sec1' => $user->cat_username,
				'sec2' => $user->cat_password,
				'session' => $sessionId,
				//'spp' => '20'
			);
			$curl_url    = $this->hipUrl . "/ipac20/ipac.jsp";
			$sresult = $this->_curlPostPage($curl_url, $post_data);

//			/** @var Memcache $memCache */
//			global $memCache; // needed here?

			//update patron information.  Use HIP to update the e-mail to make sure that all business rules are followed.
			if (isset($_REQUEST['email'])) {
				$post_data   = array(
					'menu' => 'account',
					'newemailtext' => $_REQUEST['email'],
					'newpin' => '',
					'oldpin' => '',
					'profile' => $configArray['Catalog']['hipProfile'],
					'renewpin' => '',
					'session' => $sessionId,
					'submenu' => 'info',
					'updateemail' => 'Update',
				);
				$sresult =$this->_curlPostPage($curl_url, $post_data);

				//check for errors in boldRedFont1
				if (preg_match('/<td.*?class="boldRedFont1".*?>(.*?)(?:<br>)*<\/td>/si', $sresult, $matches)) {
					$updateErrors[] = $matches[1];
				} else {
					// Update the users cat_password in the Pika database
					$user->email = $_REQUEST['email'];
				}
			}
			if (isset($_REQUEST['oldPin']) && strlen($_REQUEST['oldPin']) > 0 && isset($_REQUEST['newPin']) && strlen($_REQUEST['newPin']) > 0) {

				$post_data   = array(
					'menu' => 'account',
					'newemailtext' => $_REQUEST['email'],
					'newpin' => $_REQUEST['newPin'],
					'oldpin' => $_REQUEST['oldPin'],
					'profile' => $configArray['Catalog']['hipProfile'],
					'renewpin' => $_REQUEST['verifyPin'],
					'session' => $sessionId,
					'submenu' => 'info',
					'updatepin' => 'Update',
				);
				$sresult =$this->_curlPostPage($curl_url, $post_data);

				//check for errors in boldRedFont1
				if (preg_match('/<td.*?class="boldRedFont1".*?>(.*?)(?:<br>)*<\/td>/', $sresult, $matches)) {
					$updateErrors[] = $matches[1];
				} else {
					//Update the users cat_password in the Pika database
					$user->cat_password = $_REQUEST['newPin'];
				}
			}
			if (isset($_REQUEST['phone'])) {
				//TODO: Implement Setting Notification Methods
				$updateErrors[] = 'Phone number can not be updated.';
			}
			if (isset($_REQUEST['address1']) || isset($_REQUEST['city']) || isset($_REQUEST['state']) || isset($_REQUEST['zip'])) {
				//TODO: Implement Setting Notification Methods
				$updateErrors[] = 'Address Information can not be updated.';
			}
			if (isset($_REQUEST['notices'])) {
				//TODO: Implement Setting Notification Methods
				$updateErrors[] = 'Notice Method can not be updated.';
			}
			if (isset($_REQUEST['pickuplocation'])) {
				//TODO: Implement Setting Pick-up Locations
				$updateErrors[] = 'Pickup Locations can not be updated.';
			}

			//check to see if the user has provided an alias
			if ((isset($_REQUEST['displayName']) && $_REQUEST['displayName'] != $user->displayName) ||
				(isset($_REQUEST['disableRecommendations']) && $_REQUEST['disableRecommendations'] != $user->disableRecommendations) ||
				(isset($_REQUEST['disableCoverArt']) && $_REQUEST['disableCoverArt'] != $user->disableCoverArt) ||
				(isset($_REQUEST['bypassAutoLogout']) && $_REQUEST['bypassAutoLogout'] != $user->bypassAutoLogout)
			) {
				$user->displayName            = $_REQUEST['displayName'];
				$user->disableRecommendations = $_REQUEST['disableRecommendations'];
				$user->disableCoverArt        = $_REQUEST['disableCoverArt'];
				if (isset($_REQUEST['bypassAutoLogout'])) {
					$user->bypassAutoLogout = $_REQUEST['bypassAutoLogout'] == 'yes' ? 1 : 0;
				}
			}

			// update Pika user data & clear cache of patron profile
			$user->update();
//			UserAccount::updateSession($user); //TODO if this is required it must be determined that the user being updated is the same as the session holding user.
			$user->deletePatronProfileCache();

		} else $updateErrors[] = 'You do not have permission to update profile information.';
		return $updateErrors;
	}

	public function getRecordTitle($recordId){
		//Get the title of the book.
		$searchObject = SearchObjectFactory::initSearchObject();

		// Retrieve Full Marc Record
		if (!($record = $searchObject->getRecord($recordId))) {
			$title = null;
		}else{
			if (isset($record['title_full'][0])){
				$title = $record['title_full'][0];
			}else{
				$title = $record['title'];
			}
		}
		return $title;
	}

	function addDays($givendate,$day) {
		$cd = strtotime($givendate);
		$newdate = date('Y-m-d H:i:s', mktime(date('H',$cd),
		date('i',$cd), date('s',$cd), date('m',$cd),
		date('d',$cd)+$day, date('Y',$cd)));
		return $newdate;
	}

	function addMinutes($givendate,$minutes) {
		$cd = strtotime($givendate);
		$newdate = date('Y-m-d H:i:s', mktime(date('H',$cd),
		date('i',$cd) + $minutes, date('s',$cd), date('m',$cd),
		date('d',$cd), date('Y',$cd)));
		return $newdate;
	}

	protected function _query($query){
		global $configArray;
		if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0){
			return sybase_query($query);
		}else{
			return mssql_query($query);
		}
	}

	protected function _fetch_assoc($result_id){
		global $configArray;
		if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0){
			return sybase_fetch_assoc($result_id);
		}else{
			return mssql_fetch_assoc($result_id);
		}
	}

	protected function _fetch_array($result_id){
		global $configArray;
		if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0){
			return sybase_fetch_array($result_id);
		}else{
			return mssql_fetch_array($result_id);
		}
	}

	protected function _num_rows($result_id){
		global $configArray;
		if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0){
			return sybase_num_rows($result_id);
		}else{
			return mssql_num_rows($result_id);
		}
	}

/**
	 * Email the user's pin number to the account on record if any exists.
	 */
	function emailPin($barcode){
		global $configArray;
		if ($this->useDb){
			$sql = "SELECT name, borrower.borrower#, bbarcode, pin#, email_name, email_address from borrower inner join borrower_barcode on borrower.borrower# = borrower_barcode.borrower# inner join borrower_address on borrower.borrower# = borrower_address.borrower#  where bbarcode= '" . mysql_escape_string($barcode) . "'";

			try {
				$sqlStmt = $this->_query($sql);
				$foundPatron = false;
				while ($row = $this->_fetch_assoc($sqlStmt)) {
					$pin = $row['pin#'];
					$email = $row['email_address'];
					$foundPatron = true;
					break;
				}

				if ($foundPatron){
					if (strlen($email) == 0){
						return array('error' => 'Your account does not have an email address on record. Please visit your local library to retrieve your PIN number.');
					}
					require_once ROOT_DIR . '/sys/Mailer.php';

					$mailer = new VuFindMailer();
					$subject = "PIN number for your Library Card";
					$body = "The PIN number for your Library Card is $pin.  You may use this PIN number to login to your account.";
					$mailer->send($email, $configArray['Site']['email'],$subject, $body);
					return array(
						'success' => true,
						'pin' => $pin,
						'email' => $email,
					);
				}else{
					return array('error' => 'Sorry, we could not find an account with that barcode.');
				}
			} catch (PDOException $e) {
				return array(
					'error' => 'Unable to ready you PIN from the database.  Please try again later.'
					);
			}
		}else{
			$result = array(
				'error' => 'This functionality requires a connection to the database.',
			);
		}
		return $result;
	}

	// This function is duplicated in the User Object as deletePatronProfileCache()
	// That function should be preferred over this now. plb 8-5-2015
	/**
	 * @param null|User $patron
	 */
	public function clearPatronProfile($patron = null) {
		if (is_null($patron)) {
			global $user;
			$patron = $user;
		}
		$patron->deletePatronProfileCache();
	}

	abstract function translateCollection($collection);

	abstract function translateLocation($locationCode);

	abstract function translateStatus($status);

	public function hasNativeReadingHistory() {
		return false;
	}
}