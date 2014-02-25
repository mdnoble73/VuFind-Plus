<?php
/**
 * Loads and processes holds for Milllennium
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/20/13
 * Time: 11:33 AM
 */
class MillenniumHolds{
	/** @var  MillenniumDriver $driver */
	private $driver;
	private $holds = array();

	public function __construct($driver){
		$this->driver = $driver;
	}
	protected function _getHoldResult($holdResultPage){
		$hold_result = array();
		//Get rid of header and footer information and just get the main content
		$matches = array();

		$numMatches = preg_match('/<td.*?class="pageMainArea">(.*)?<\/td>/s', $holdResultPage, $matches);
		//For Encore theme, try with some divs
		if ($numMatches == 0){
			$numMatches = preg_match('/<div class="pageContentInner">(.*?)<div id="footerArea">/s', $holdResultPage, $matches);
		}
		$itemMatches = preg_match('/Choose one item from the list below/', $holdResultPage);

		if ($numMatches > 0 && $itemMatches == 0){
			//$logger->log('Place Hold Body Text\n' . $matches[1], PEAR_LOG_INFO);
			$cleanResponse = preg_replace("^\n|\r|&nbsp;^", "", $matches[1]);
			$cleanResponse = preg_replace("^<br\s*/>^", "\n", $cleanResponse);
			$cleanResponse = trim(strip_tags($cleanResponse));

			if (strpos($cleanResponse, "\n") > 0){
				list($book,$reason)= explode("\n",$cleanResponse);
			}else{
				$book = $cleanResponse;
				$reason = '';
			}

			$hold_result['title'] = $book;
			if (preg_match('/success/', $cleanResponse) && preg_match('/request denied/', $cleanResponse) == 0){
				//Hold was successful
				$hold_result['result'] = true;
				if (!isset($reason) || strlen($reason) == 0){
					$hold_result['message'] = 'Your hold was placed successfully';
				}else{
					$hold_result['message'] = $reason;
				}
			}else if (!isset($reason) || strlen($reason) == 0){
				//Didn't get a reason back.  This really shouldn't happen.
				$hold_result['result'] = false;
				$hold_result['result'] = false;
				$hold_result['message'] = 'Did not receive a response from the circulation system.  Please try again in a few minutes.';
			}else{
				//Got an error message back.
				$hold_result['result'] = false;
				$hold_result['message'] = $reason;
			}
		}else{
			if ($itemMatches > 0){
				//Get information about the items that are available for holds
				preg_match_all('/<tr\\s+class="bibItemsEntry">.*?<input type="radio" name="radio" value="(.*?)".*?>.*?<td.*?>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>.*?<\/tr>/s', $holdResultPage, $itemInfo, PREG_PATTERN_ORDER);
				$items = array();
				for ($i = 0; $i < count($itemInfo[0]); $i++) {
					$items[] = array(
						'itemNumber' => $itemInfo[1][$i],
						'location' => trim(str_replace('&nbsp;', '', $itemInfo[2][$i])),
						'callNumber' => trim(str_replace('&nbsp;', '', $itemInfo[3][$i])),
						'status' => trim(str_replace('&nbsp;', '', $itemInfo[4][$i])),
					);
				}
				$hold_result['items'] = $items;
				if (count($items) > 0){
					$message = 'This title requires item level holds, please select an item to place a hold on.';
				}else{
					$message = 'There are no holdable items for this title.';
				}
			}else{
				$message = 'Unable to contact the circulation system.  Please try again in a few minutes.';
			}
			$hold_result['result'] = false;
			$hold_result['message'] = $message;

			global $logger;
			$logger->log('Place Hold Full HTML\n' . $holdResultPage, PEAR_LOG_INFO);
		}
		return $hold_result;
	}

	public function updateHold($requestId, $patronId, $type, $title){
		$xnum = "x" . $_REQUEST['x'];
		//Strip the . off the front of the bib and the last char from the bib
		if (isset($_REQUEST['cancelId'])){
			$cancelId = $_REQUEST['cancelId'];
		}else{
			$cancelId = substr($requestId, 1, -1);
		}
		$locationId = $_REQUEST['location'];
		$freezeValue = isset($_REQUEST['freeze']) ? 'on' : 'off';
		return $this->updateHoldDetailed($patronId, $type, $title, $xnum, $cancelId, $locationId, $freezeValue);
	}

	/**
	 * Update a hold that was previously placed in the system.
	 * Can cancel the hold or update pickup locations.
	 */
	public function updateHoldDetailed($patronId, $type, $title, $xNum, $cancelId, $locationId, $freezeValue='off')
	{
		global $logger;
		global $configArray;

		$patronDump = $this->driver->_getPatronDump($this->driver->_getBarcode());

		// Millennium has a "quirk" where you can still freeze and thaw a hold even if it is in the wrong status.
		// therefore we need to check the current status before we freeze or unfreeze.
		$scope = $this->driver->getDefaultScope();
		//go to the holds page and get the number of holds on the account
		$holds = $this->getMyHolds();
		$numHoldsStart = count($holds['available'] + $holds['unavailable']);

		if (!isset($xNum) ){
			$waitingHolds = isset($_REQUEST['waitingholdselected']) ? $_REQUEST['waitingholdselected'] : array();
			$availableHolds = isset($_REQUEST['availableholdselected']) ? $_REQUEST['availableholdselected'] : array();
			$xNum = array_merge($waitingHolds, $availableHolds);
		}
		$location = new Location();
		if (isset($locationId) && is_numeric($locationId)){
			$location->whereAdd("locationId = '$locationId'");
			$location->find();
			if ($location->N == 1) {
				$location->fetch();
				$paddedLocation = str_pad(trim($location->code), 5, "+");
			}
		}else{
			$paddedLocation = null;
		}

		$cancelValue = ($type == 'cancel' || $type == 'recall') ? 'on' : 'off';

		if (!is_array($xNum)){
			$xNum = array($xNum);
		}

		$loadTitles = (!isset($title) || strlen($title) == 0);
		$logger->log("Load titles = $loadTitles", PEAR_LOG_DEBUG);
		$extraGetInfo = array(
			'updateholdssome' => 'YES',
			'currentsortorder' => 'current_pickup',
		);
		foreach ($xNum as $tmpXnumInfo){
			list($tmpBib, $tmpXnum) = explode("~", $tmpXnumInfo);
			if ($type == 'cancel'){
				$extraGetInfo['cancel' . $tmpBib . 'x' . $tmpXnum] = $cancelValue;
			}
			if ($type == 'update'){
				$holdForXNum = $this->getHoldByXNum($holds, $tmpXnum);
				$canUpdate = false;
				if ($holdForXNum != null){
					if ($freezeValue == 'off'){
						if ($holdForXNum['frozen']){
							$canUpdate = true;
						}
					} else if ($freezeValue == 'on'){
						if ($holdForXNum['frozen'] == false){
							if ($holdForXNum['freezeable'] == true){
								$canUpdate = true;
							}
						}
					} else if ($freezeValue == '' && isset($paddedLocation) && $holdForXNum['locationUpdateable']){
						$canUpdate = true;
					}
				}
				if ($canUpdate){
					if (isset($paddedLocation)){
						$extraGetInfo['loc' . $tmpBib . 'x' . $tmpXnum] = $paddedLocation;
					}
					if (strlen($freezeValue) > 0){
						$extraGetInfo['freeze' . $tmpBib . 'x' . $tmpXnum] = $freezeValue;
					}
				}
			}
			if ($loadTitles){
				$resource = new Resource();
				$resource->shortId = $tmpBib;
				if ($resource->find(true)){
					if (strlen($title) > 0) $title .= ", ";
					$title .= $resource->title;
				}else{
					$logger->log("Did not find bib for = $tmpBib", PEAR_LOG_DEBUG);
				}
			}
		}

		$get_items = array();
		foreach ($extraGetInfo as $key => $value) {
			$get_items[] = $key . '=' . urlencode($value);
		}
		$holdUpdateParams = implode ('&', $get_items);

		//Login to the patron's account
		$cookieJar = tempnam ("/tmp", "CURLCOOKIE");
		$success = false;

		$curl_url = $configArray['Catalog']['url'] . "/patroninfo";
		$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);

		$curl_connection = curl_init($curl_url);
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, false);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = $this->driver->_getLoginFormValues();
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		//Load the page, but we don't need to do anything with the results.
		$loginResult = curl_exec($curl_connection);

		//When a library uses Encore, the initial login does a redirect and requires additional parameters.
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResult, $loginMatches)) {
			//Get the lt value
			$lt = $loginMatches[1];
			//Login again
			$post_data['lt'] = $lt;
			$post_data['_eventId'] = 'submit';
			$post_items = array();
			foreach ($post_data as $key => $value) {
				$post_items[] = $key . '=' . $value;
			}
			$post_string = implode ('&', $post_items);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$loginResult = curl_exec($curl_connection);
			$curlInfo = curl_getinfo($curl_connection);
		}

		//Issue a post request with the information about what to do with the holds
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/holds";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $holdUpdateParams);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$sResult = curl_exec($curl_connection);
		//$holds = $this->parseHoldsPage($sResult);
		//At this stage, we get messages if there were any errors freezing holds.

		//Go back to the hold page to check make sure our hold was cancelled
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/holds";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sResult = curl_exec($curl_connection);
		$holds = $this->parseHoldsPage($sResult);
		$numHoldsEnd = count($holds['available'] + $holds['unavailable']);

		curl_close($curl_connection);

		unlink($cookieJar);

		//Finally, check to see if the update was successful.
		if ($type == 'cancel' || $type=='recall'){
			if ($numHoldsEnd != $numHoldsStart){
				$logger->log('Cancelled ok', PEAR_LOG_INFO);
				$success = true;
			}
		}

		//Make sure to clear any cached data
		global $memCache;
		$memCache->delete("patron_dump_{$this->driver->_getBarcode()}");
		usleep(250);
		//Clear holds for the patron
		unset($this->holds[$patronId]);

		global $analytics;
		if ($type == 'cancel' || $type == 'recall'){
			if ($success){
				$analytics->addEvent('ILS Integration', 'Hold Cancelled', $title);
				return array(
					'title' => $title,
					'result' => true,
					'message' => 'Your hold was cancelled successfully.');
			}else{
				$analytics->addEvent('ILS Integration', 'Hold Not Cancelled', $title);
				return array(
					'title' => $title,
					'result' => false,
					'message' => 'Your hold could not be cancelled.  Please try again later or see your librarian.');
			}
		}else{
			$analytics->addEvent('ILS Integration', 'Hold(s) Updated', $title);
			return array(
				'title' => $title,
				'result' => true,
				'message' => 'Your hold was updated successfully.');
		}
	}

	public function parseHoldsPage($sResult){
		global $logger;
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);

		$sResult = preg_replace("/<[^<]+?>\W<[^<]+?>\W\d* HOLD.?\W<[^<]+?>\W<[^<]+?>/", "", $sResult);
		//$logger->log('Hold information = ' . $sresult, PEAR_LOG_INFO);

		$s = substr($sResult, stripos($sResult, 'patFunc'));

		$s = substr($s,strpos($s,">")+1);

		$s = substr($s,0,stripos($s,"</table"));

		$s = preg_replace ("/<br \/>/","", $s);

		$sRows = preg_split("/<tr([^>]*)>/",$s);
		$sCount = 0;
		$sKeys = array_pad(array(),10,"");
		foreach ($sRows as $sRow) {
			if (strlen(trim($sRow)) == 0){
				continue;
			}
			$sCols = preg_split("/<t(h|d)([^>]*)>/",$sRow);
			$curHold= array();
			$curHold['create'] = null;
			$curHold['reqnum'] = null;
			$curHold['holdSource'] = 'ILS';

			//Holds page occasionally has a header with number of items checked out.
			for ($i=0; $i < sizeof($sCols); $i++) {
				$sCols[$i] = str_replace("&nbsp;"," ",$sCols[$i]);
				$sCols[$i] = preg_replace ("/<br+?>/"," ", $sCols[$i]);
				$sCols[$i] = html_entity_decode(trim(substr($sCols[$i],0,stripos($sCols[$i],"</t"))));
				//print_r($scols[$i]);
				if ($sCount <= 1) {
					$sKeys[$i] = $sCols[$i];
				} else if ($sCount > 1) {
					if ($sKeys[$i] == "CANCEL") { //Only check Cancel key, not Cancel if not filled by
						//Extract the id from the checkbox
						$matches = array();
						$numMatches = preg_match_all('/.*?cancel(.*?)x(\\d\\d).*/s', $sCols[$i], $matches);
						if ($numMatches > 0){
							$curHold['renew'] = "BOX";
							$curHold['cancelable'] = true;
							$curHold['itemId'] = $matches[1][0];
							$curHold['xnum'] = $matches[2][0];
							$curHold['cancelId'] = $matches[1][0] . '~' . $matches[2][0];
						}else{
							$curHold['cancelable'] = false;
						}
					}

					if (stripos($sKeys[$i],"TITLE") > -1) {
						if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $sCols[$i], $matches)) {
							$shortId = $matches[1];
							$bibid = '.' . $matches[1]; //Technically, this isn't correct since the check digit is missing
							$title = strip_tags($matches[2]);
						}elseif (preg_match('/.*<a href=".*?\/record\/C__R(.*?)\\?.*?">(.*?)<\/a>.*/si', $sCols[$i], $matches)){
							$shortId = $matches[1];
							$bibid = '.' . $matches[1]; //Technically, this isn't correct since the check digit is missing
							$title = strip_tags($matches[2]);
						}else{
							$bibid = '';
							$shortId = '';
							$title = trim($sCols[$i]);
						}

						$curHold['id'] = $bibid;
						$curHold['shortId'] = $shortId;
						$curHold['title'] = $title;
					}
					if (stripos($sKeys[$i],"Ratings") > -1) {
						$curHold['request'] = "STARS";
					}

					if (stripos($sKeys[$i],"PICKUP LOCATION") > -1) {

						//Extract the current location for the hold if possible
						$matches = array();
						if (preg_match('/<select\\s+name=loc(.*?)x(\\d\\d).*?<option\\s+value="([a-z]{1,5})[+ ]*"\\s+selected="selected">.*/s', $sCols[$i], $matches)){
							$curHold['locationId'] = $matches[1];
							$curHold['locationXnum'] = $matches[2];
							$curPickupBranch = new Location();
							$curPickupBranch->whereAdd("code = '{$matches[3]}'");
							$curPickupBranch->find(1);
							if ($curPickupBranch->N > 0){
								$curPickupBranch->fetch();
								$curHold['currentPickupId'] = $curPickupBranch->locationId;
								$curHold['currentPickupName'] = $curPickupBranch->displayName;
								$curHold['location'] = $curPickupBranch->displayName;
							}
							$curHold['locationUpdateable'] = true;

							//Return the full select box for reference.
							$curHold['locationSelect'] = $sCols[$i];
						}else{
							$curHold['location'] = $sCols[$i];
							//Trim the carrier code if any
							if (preg_match('/.*\s[\w\d]{4}/', $curHold['location'])){
								$curHold['location'] = substr($curHold['location'], 0, strlen($curHold['location']) - 5);
							}
							$curHold['currentPickupName'] = $curHold['location'];
							$curHold['locationUpdateable'] = false;
						}
					}

					if (stripos($sKeys[$i],"STATUS") > -1) {
						$status = trim(strip_tags($sCols[$i]));
						$status = strtolower($status);
						$status = ucwords($status);
						if ($status !="&nbsp"){
							$curHold['status'] = $status;
							if (preg_match('/READY.*(\d{2}-\d{2}-\d{2})/i', $status, $matches)){
								$curHold['status'] = 'Ready';
								//Get expiration date
								$exipirationDate = $matches[1];
								$expireDate = DateTime::createFromFormat('m-d-y', $exipirationDate);
								$curHold['expire'] = $expireDate->getTimestamp();

							}elseif (preg_match('/READY\sFOR\sPICKUP/i', $status, $matches)){
								$curHold['status'] = 'Ready';
							}else{
								$curHold['status'] = $status;
							}
						}else{
							$curHold['status'] = "Pending $status";
						}
						$matches = array();
						$curHold['renewError'] = false;
						if (preg_match('/.*DUE\\s(\\d{2}-\\d{2}-\\d{2}).*(?:<font color="red">\\s*(.*)<\/font>).*/s', $sCols[$i], $matches)){
							//Renew error
							$curHold['renewError'] = $matches[2];
							$curHold['statusMessage'] = $matches[2];
						}else{
							if (preg_match('/.*DUE\\s(\\d{2}-\\d{2}-\\d{2})\\s(.*)?/s', $sCols[$i], $matches)){
								$curHold['statusMessage'] = $matches[2];
							}
						}
						//$logger->log('Status for item ' . $curHold['id'] . '=' . $sCols[$i], PEAR_LOG_INFO);
					}
					if (stripos($sKeys[$i],"CANCEL IF NOT FILLED BY") > -1) {
						//$curHold['expire'] = strip_tags($scols[$i]);
					}
					if (stripos($sKeys[$i],"FREEZE") > -1) {
						$matches = array();
						$curHold['frozen'] = false;
						if (preg_match('/<input.*name="freeze(.*?)"\\s*(\\w*)\\s*\/>/', $sCols[$i], $matches)){
							$curHold['freezeable'] = true;
							if (strlen($matches[2]) > 0){
								$curHold['frozen'] = true;
								$curHold['status'] = 'Frozen';
							}
						}elseif (preg_match('/This hold can\s?not be frozen/i', $sCols[$i], $matches)){
							//If we detect an error Freezing the hold, save it so we can report the error to the user later.
							$shortId = str_replace('.b', 'b', $curHold['id']);
							$_SESSION['freezeResult'][$shortId]['message'] = $sCols[$i];
							$_SESSION['freezeResult'][$shortId]['result'] = false;
						}else{
							$curHold['freezeable'] = false;
						}
					}
				}
			} //End of columns

			if ($sCount > 1) {
				if (!isset($curHold['status']) || strcasecmp($curHold['status'], "ready") != 0){
					$holds['unavailable'][] = $curHold;
				}else{
					$holds['available'][] = $curHold;
				}
			}

			$sCount++;

		}//End of the row

		return $holds;
	}

	public function getMyHolds($patron = null, $page = 1, $recordsPerPage = -1, $sortOption = 'title')
	{
		global $timer;
		global $configArray;
		global $user;
		$patronDump = $this->driver->_getPatronDump($this->driver->_getBarcode());

		//Load the information from millennium using CURL
		$sResult = $this->driver->_fetchPatronInfoPage($patronDump, 'holds');
		$timer->logTime("Got holds page from Millennium");

		$holds = $this->parseHoldsPage($sResult);
		$timer->logTime("Parsed Holds page");

		//Get a list of all record id so we can load supplemental information
		$recordIds = array();
		foreach($holds as $holdSections){
			foreach($holdSections as $hold){
				$recordIds[] = "'" . $hold['shortId'] . "'";
			}
		}
		//Get records from resource table
		$resourceInfo = new Resource();
		if (count($recordIds) > 0){
			$recordIdString = implode(",", $recordIds);
			mysql_select_db($configArray['Database']['database_vufind_dbname']);
			$resourceSql = "SELECT * FROM resource where source = 'VuFind' AND shortId in (" . $recordIdString .")";
			$resourceInfo->query($resourceSql);
			$timer->logTime('Got records for all titles');

			//Load title author, etc. information
			while ($resourceInfo->fetch()){
				foreach($holds as $section => $holdSections){
					foreach($holdSections as $key => $hold){
						$hold['recordId'] = $hold['id'];
						if ($hold['shortId'] == $resourceInfo->shortId){
							$hold['recordId'] = $resourceInfo->record_id;
							$hold['id'] = $resourceInfo->record_id;
							$hold['shortId'] = $resourceInfo->shortId;
							//Load title, author, and format information about the title
							$hold['title'] = isset($resourceInfo->title) ? $resourceInfo->title : 'Unknown';
							$hold['sortTitle'] = isset($resourceInfo->title_sort) ? $resourceInfo->title_sort : 'unknown';
							$hold['author'] = isset($resourceInfo->author) ? $resourceInfo->author : null;
							$hold['format'] = isset($resourceInfo->format) ?$resourceInfo->format : null;
							$hold['isbn'] = isset($resourceInfo->isbn) ? $resourceInfo->isbn : '';
							$hold['upc'] = isset($resourceInfo->upc) ? $resourceInfo->upc : '';
							$hold['format_category'] = isset($resourceInfo->format_category) ? $resourceInfo->format_category : '';

							//Load rating information
							$hold['ratingData'] = $resourceInfo->getRatingData($user);

							$holds[$section][$key] = $hold;
						}
					}
				}
			}
		}

		//Process sorting
		//echo ("<br/>\r\nSorting by $sortOption");
		foreach ($holds as $sectionName => $section){
			$sortKeys = array();
			$i = 0;
			foreach ($section as $key => $hold){
				$sortTitle = isset($hold['sortTitle']) ? $hold['sortTitle'] : (isset($hold['title']) ? $hold['title'] : "Unknown");
				if ($sectionName == 'available'){
					$sortKeys[$key] = $sortTitle;
				}else{
					if ($sortOption == 'title'){
						$sortKeys[$key] = $sortTitle;
					}elseif ($sortOption == 'author'){
						$sortKeys[$key] = (isset($hold['author']) ? $hold['author'] : "Unknown") . '-' . $sortTitle;
					}elseif ($sortOption == 'placed'){
						$sortKeys[$key] = $hold['createTime'] . '-' . $sortTitle;
					}elseif ($sortOption == 'format'){
						$sortKeys[$key] = (isset($hold['format']) ? $hold['format'] : "Unknown") . '-' . $sortTitle;
					}elseif ($sortOption == 'location'){
						$sortKeys[$key] = (isset($hold['location']) ? $hold['location'] : "Unknown") . '-' . $sortTitle;
					}elseif ($sortOption == 'holdQueueLength'){
						$sortKeys[$key] = (isset($hold['holdQueueLength']) ? $hold['holdQueueLength'] : 0) . '-' . $sortTitle;
					}elseif ($sortOption == 'position'){
						$sortKeys[$key] = str_pad((isset($hold['position']) ? $hold['position'] : 1), 3, "0", STR_PAD_LEFT) . '-' . $sortTitle;
					}elseif ($sortOption == 'status'){
						$sortKeys[$key] = (isset($hold['status']) ? $hold['status'] : "Unknown") . '-' . (isset($hold['reactivateTime']) ? $hold['reactivateTime'] : "0") . '-' . $sortTitle;
					}else{
						$sortKeys[$key] = $sortTitle;
					}
					//echo ("<br/>\r\nSort Key for $key = {$sortKeys[$key]}");
				}

				$sortKeys[$key] = strtolower($sortKeys[$key] . '-' . $i++);
			}
			array_multisort($sortKeys, $section);
			$holds[$sectionName] = $section;
		}

		//Limit to a specific number of records
		if (isset($holds['unavailable'])){
			$numUnavailableHolds = count($holds['unavailable']);
			if ($recordsPerPage != -1){
				$startRecord = ($page - 1) * $recordsPerPage;
				$holds['unavailable'] = array_slice($holds['unavailable'], $startRecord, $recordsPerPage);
			}
		}else{
			$numUnavailableHolds = 0;
		}

		if (!isset($holds['available'])){
			$holds['available'] = array();
		}
		if (!isset($holds['unavailable'])){
			$holds['unavailable'] = array();
		}
		//Sort the hold sections so available holds are first.
		ksort($holds);

		$patronId = isset($patron) ? $patron['id'] : $this->driver->_getBarcode();
		$this->holds[$patronId] = $holds;
		$timer->logTime("Processed hold pagination and sorting");
		return array(
			'holds' => $holds,
			'numUnavailableHolds' => $numUnavailableHolds,
		);
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $itemId     The id of the item to hold
	 * @param   string  $patronId   The id of the patron
	 * @param   string  $comment    Any comment regarding the hold or recall
	 * @param   string  $type       Whether to place a hold or recall
	 * @param   string  $type       The date when the hold should be cancelled if any
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	public function placeItemHold($recordId, $itemId, $patronId, $comment, $type){
		global $configArray;

		$bib1= $recordId;
		if (substr($bib1, 0, 1) != '.'){
			$bib1 = '.' . $bib1;
		}

		$bib = substr(str_replace('.b', 'b', $bib1), 0, -1);
		if (strlen($bib) == 0){
			return array(
				'result' => false,
				'message' => 'A valid record id was not provided. Please try again.');
		}

		//Get the title of the book.
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->driver->db = new $class($url);

		// Retrieve Full Marc Record
		if (!($record = $this->driver->db->getRecord($bib1))) {
			$title = null;
		}else{
			if (isset($record['title_full'][0])){
				$title = $record['title_full'][0];
			}else{
				$title = $record['title'];
			}
		}

		if ($configArray['Catalog']['offline']){
			global $user;
			require_once ROOT_DIR . '/sys/OfflineHold.php';
			$offlineHold = new OfflineHold();
			$offlineHold->bibId = $bib1;
			$offlineHold->patronBarcode = $patronId;
			$offlineHold->patronId = $user->id;
			$offlineHold->timeEntered = time();
			$offlineHold->status = 'Not Processed';
			if ($offlineHold->insert()){
				return array(
					'title' => $title,
					'bib' => $bib1,
					'result' => true,
					'message' => 'The circulation system is currently offline.  This hold will be entered for you automatically when the circulation system is online.');
			}else{
				return array(
					'title' => $title,
					'bib' => $bib1,
					'result' => false,
					'message' => 'The circulation system is currently offline and we could not place this hold.  Please try again later.');
			}

		}else{
			//Cancel a hold
			if ($type == 'cancel' || $type == 'recall' || $type == 'update') {
				$result = $this->updateHold($recordId, $patronId, $type, $title);
				$result['title'] = $title;
				$result['bid'] = $bib1;
				return $result;

			} else {
				if (isset($_REQUEST['canceldate']) && !is_null($_REQUEST['canceldate']) && $_REQUEST['canceldate'] != ''){
					$date = $_REQUEST['canceldate'];
				}else{
					//Default to a date 6 months (half a year) in the future.
					$sixMonthsFromNow = time() + 182.5 * 24 * 60 * 60;
					$date = date('m/d/Y', $sixMonthsFromNow);
				}

				if (isset($_POST['campus'])){
					$campus=trim($_POST['campus']);
				}else{
					global $user;
					$campus = $user->homeLocationId;
				}

				if (is_numeric($campus)){
					$location = new Location();
					$location->locationId = $campus;
					if ($location->find(true)){
						$campus = $location->code;
					}
				}

				list($Month, $Day, $Year)=explode("/", $date);

				//------------BEGIN CURL-----------------------------------------------------------------
				$header=array();
				$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
				$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
				$header[] = "Cache-Control: max-age=0";
				$header[] = "Connection: keep-alive";
				$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
				$header[] = "Accept-Language: en-us,en;q=0.5";

				$cookie = tempnam ("/tmp", "CURLCOOKIE");

				$curl_connection = curl_init();
				curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
				curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
				curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
				curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
				curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
				curl_setopt($curl_connection, CURLOPT_HEADER, false);
				curl_setopt($curl_connection, CURLOPT_POST, true);

				$lt = null;
				if (isset($configArray['Catalog']['loginPriorToPlacingHolds']) && $configArray['Catalog']['loginPriorToPlacingHolds'] = true){
					//User must be logged in as a separate step to placing holds
					$curl_url = $configArray['Catalog']['url'] . "/patroninfo";
					$post_data = $this->driver->_getLoginFormValues();
					$post_data['submit.x']="35";
					$post_data['submit.y']="21";
					$post_data['submit']="submit";
					curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
					curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
					$post_items = array();
					foreach ($post_data as $key => $value) {
						$post_items[] = $key . '=' . $value;
					}
					$post_string = implode ('&', $post_items);
					curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
					$loginResult = curl_exec($curl_connection);
					$curlInfo = curl_getinfo($curl_connection);
					//When a library uses Encore, the initial login does a redirect and requires additional parameters.
					if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResult, $loginMatches)) {
						//Get the lt value
						$lt = $loginMatches[1];
						//Login again
						$post_data['lt'] = $lt;
						$post_data['_eventId'] = 'submit';
						$post_items = array();
						foreach ($post_data as $key => $value) {
							$post_items[] = $key . '=' . $value;
						}
						$post_string = implode ('&', $post_items);
						curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
						$loginResult = curl_exec($curl_connection);
						$curlInfo = curl_getinfo($curl_connection);
					}
					$post_data = array();
				}else{
					$post_data = $this->driver->_getLoginFormValues();
				}
				$curl_url = $configArray['Catalog']['url'] . "/search/." . $bib . "/." . $bib ."/1,1,1,B/request~" . $bib;
				//echo "$curl_url";
				curl_setopt($curl_connection, CURLOPT_URL, $curl_url);

				/** @var Library $librarySingleton */
        global $librarySingleton;
        $patronHomeBranch = $librarySingleton->getPatronHomeLibrary();
        if ($patronHomeBranch->defaultNotNeededAfterDays != -1){
					$post_data['needby_Month']= $Month;
					$post_data['needby_Day']= $Day;
					$post_data['needby_Year']=$Year;
				}

				$post_data['submit.x']="35";
				$post_data['submit.y']="21";
				$post_data['submit']="submit";
				$post_data['locx00']= str_pad($campus, 5-strlen($campus), '+');
				if (!is_null($itemId) && $itemId != -1){
					$post_data['radio']=$itemId;
				}
				$post_data['x']="48";
				$post_data['y']="15";
				if ($lt != null){
					$post_data['lt'] = $lt;
					$post_data['_eventId'] = 'submit';
				}

				$post_items = array();
				foreach ($post_data as $key => $value) {
					$post_items[] = $key . '=' . $value;
				}
				$post_string = implode ('&', $post_items);
				curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
				$sResult = curl_exec($curl_connection);

				global $logger;
				$logger->log("Placing hold $curl_url?$post_string", PEAR_LOG_INFO);

				$sResult = preg_replace("/<!--([^(-->)]*)-->/","",$sResult);

				curl_close($curl_connection);

				//Parse the response to get the status message
				$hold_result = $this->_getHoldResult($sResult);
				$hold_result['title']  = $title;
				$hold_result['bid'] = $bib1;
				global $analytics;
				if ($analytics){
					if ($hold_result['result'] == true){
						$analytics->addEvent('ILS Integration', 'Successful Hold', $title);
					}else{
						$analytics->addEvent('ILS Integration', 'Failed Hold', $hold_result['message'] . ' - ' . $title);
					}
				}
				return $hold_result;
			}
		}
	}

	private function getHoldByXNum($holds, $tmpXnum) {
		$patronHolds = reset($this->holds);
		$unavailableHolds = $patronHolds['unavailable'];
		foreach ($unavailableHolds as $hold){
			if ($hold['xnum'] == $tmpXnum){
				return $hold;
			}
		}
		return null;
	}
}
