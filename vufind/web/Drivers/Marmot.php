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
require_once 'sys/Proxy_Request.php';
require_once 'Drivers/marmot_inc/CirculationStatus.php';
require_once 'Drivers/marmot_inc/NonHoldableLocation.php';
require_once 'Drivers/marmot_inc/PTypeRestrictedLocation.php';
require_once 'Interface.php';
require_once 'Innovative.php';

/**
 * VuFind Connector for Marmot's Innovative catalog (millenium)
 *
 * This class uses screen scraping techniques to gather record holdings written
 * by Adam Bryn of the Tri-College consortium.
 *
 * @author Adam Brin <abrin@brynmawr.com>
 *
 * Extended by Mark Noble and CJ O'Hara based on specific requirements for
 * Marmot Library Network.
 *
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @author CJ O'Hara <cj@marmot.org>
 */
class Marmot implements DriverInterface
{

	var $statusTranslations = null;
	var $holdableStatiRegex = null;
	var $availableStatiRegex = null;

	/**
	 * Load information about circulation statuses from the database
	 * so we can perform translations easily and so we can determine
	 * what is available and what is not available
	 *
	 * @return void
	 */
	private function loadCircStatusInfo(){
		if (is_null($this->holdableStatiRegex)){
			$circStatus = new CirculationStatus();
			$circStatus->find();
			$holdableStati = array();
			$availableStati = array();
			if ($circStatus->N > 0){
				while ($circStatus->fetch()){
					if ($circStatus->holdable == 1){
						$holdableStati[] = $circStatus->millenniumName;
					}
					if ($circStatus->available == 1){
						$availableStati[] = $circStatus->millenniumName;
					}
					if (isset($circStatus->displayName) && is_string($circStatus->displayName) && strlen($circStatus->displayName) > 0){
						$this->statusTranslations[$circStatus->millenniumName] = $circStatus->displayName;
					}
				}
			}
			//Holdable statuses are statuses where the patron could get the item in a reasonable amount of time if they place a hold.
			$this->holdableStatiRegex = implode('|', $holdableStati);
			//Available statuses are statuses where the patron can walk into the library and get it pretty much immediately.
			$this->availableStatiRegex = implode('|', $availableStati);
		}
	}

	var $nonHoldableLocations = null;
	private function loadNonHoldableLocations(){
		if (is_null($this->nonHoldableLocations)){
			$nonHoldableLocation = new NonHoldableLocation();
			$nonHoldableLocation->find();
			$this->nonHoldableLocations = array();
			if ($nonHoldableLocation->N > 0){
				while ($nonHoldableLocation->fetch()){
					$this->nonHoldableLocations[trim($nonHoldableLocation->holdingDisplay)] = clone $nonHoldableLocation;
				}
			}
		}
	}

	var $ptypeRestrictedLocations;
	private function loadPtypeRestrictedLocations(){
		if (is_null($this->ptypeRestrictedLocations)){
			$ptypeRestrictedLocation = new PTypeRestrictedLocation();
			$ptypeRestrictedLocation->find();
			$this->ptypeRestrictedLocations = array();
			if ($ptypeRestrictedLocation->N > 0){
				while ($ptypeRestrictedLocation->fetch()){
					$this->ptypeRestrictedLocations[trim($ptypeRestrictedLocation->holdingDisplay)] = clone $ptypeRestrictedLocation;
				}
			}
		}
	}

	public function isUserStaff(){
		global $configArray;
		$staffPTypes = $configArray['Staff P-Types'];
		$pType = $this->getPType();
		if (array_key_exists($pType, $staffPTypes)){
			return true;
		}else{
			return false;
		}
	}

	public function getMillenniumScope(){
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();

		$branchScope = '';
		//Load the holding label for the branch where the user is physically.
		if (!is_null($searchLocation)){
			if ($searchLocation->useScope && strlen($searchLocation->defaultLocationFacet) > 0){
				$branchScope = $searchLocation->scope;
			}
		}
		if (strlen($branchScope)){
			return $branchScope;
		}else if (isset($searchLibrary) && $searchLibrary->useScope && strlen($searchLibrary->defaultLibraryFacet) > 0) {
			return $searchLibrary->scope;
		}else{
			return $this->getDefaultScope();
		}
	}
	
	public function getDefaultScope(){
		global $configArray;
		return isset($configArray['OPAC']['defaultScope']) ? $configArray['OPAC']['defaultScope'] : '93';
	}

	public function getMillenniumRecordInfo($id){
		require_once 'Drivers/marmot_inc/MillenniumCache.php';
		$scope = $this->getMillenniumScope();
		$logger = new Logger();
		$logger->log('Loaded millennium info for id ' . $id . ' scope ' . $scope, PEAR_LOG_INFO);
		$millenniumCache = new MillenniumCache();
		//First clean out any records that are more than 5 minutes old
		$cacheExpirationTime = time() - 5 * 60;
		$millenniumCache->whereAdd("cacheDate < $cacheExpirationTime");
		$millenniumCache->delete(true);
		//Now see if the record already exists in our cache.
		$millenniumCache = new MillenniumCache();
		$millenniumCache->recordId = $id;
		$millenniumCache->scope = $scope;
		$millenniumCache->find();
		if ($millenniumCache->N > 0){
			//Found a cache entry
			$millenniumCache->fetch();
			//We already deleted old cache entries so we don't need to check to see if the entry is stale.
			//Just return the entry
			return $millenniumCache;
		}
		//Load the pages for holdings, order information, and items
		$millenniumCache = new MillenniumCache();
		$millenniumCache->recordId = $id;
		$millenniumCache->scope = $scope;
		global $configArray;
		global $timer;
		if (substr($configArray['Catalog']['url'], -1) == '/') {
			$host = substr($configArray['Catalog']['url'], 0, -1);
		} else {
			$host = $configArray['Catalog']['url'];
		}

		// Strip ID
		$id_ = substr(str_replace('.b', '', $id), 0, -1);

		$req =  $host . "/search~S{$scope}/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/holdings~" . $id_;
		$millenniumCache->holdingsInfo = file_get_contents($req);
		$timer->logTime('got holdings from millennium');

		$req =  $host . "/search~S{$scope}/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/frameset~" . $id_;
		$millenniumCache->framesetInfo = file_get_contents($req);
		$timer->logTime('got frameset info from millennium');

		$millenniumCache->cacheDate = time();
		//Temporarily ignore errors
		disableErrorHandler();
		$millenniumCache->insert();
		enableErrorHandler();

		return $millenniumCache;

	}

	var $statuses = array();
	public function getStatus($id)
	{
		if (isset($this->statuses[$id])){
			return $this->statuses[$id];
		}
		global $library;
		global $user;
		global $timer;
		global $configArray;

		//Load circulation status information so we can use it later on to
		//determine what is holdable and what is not.
		self::loadCircStatusInfo();
		self::loadNonHoldableLocations();
		self::loadPtypeRestrictedLocations();
		$timer->logTime('loadCircStatusInfo, loadNonHoldableLocations, loadPtypeRestrictedLocations');

		//Get information about holdings, order information, and issue information
		$millenniumInfo = $this->getMillenniumRecordInfo($id);

		// Load Record Page
		$r = substr($millenniumInfo->holdingsInfo, stripos($millenniumInfo->holdingsInfo, 'bibItems'));
		$r = substr($r,strpos($r,">")+1);
		$r = substr($r,0,stripos($r,"</table"));
		$rows = preg_split("/<tr([^>]*)>/",$r);
		$count = 0;
		$keys = array_pad(array(),10,"");

		$loc_col_name      = $configArray['OPAC']['location_column'];
		$call_col_name     = $configArray['OPAC']['call_no_column'];
		$status_col_name   = $configArray['OPAC']['status_column'];
		$reserves_col_name = $configArray['OPAC']['location_column'];
		$reserves_key_name = $configArray['OPAC']['reserves_key_name'];
		$transit_key_name  = $configArray['OPAC']['transit_key_name'];
		$stat_avail 	   = $configArray['OPAC']['status_avail'];
		$stat_due	   	   = $configArray['OPAC']['status_due'];
		$stat_libuse	   = $configArray['OPAC']['status_libuse'];

		$ret = array();
		//Process each row in the callnumber table.
		$numHoldings = 0;
		foreach ($rows as $row) {
			//Skip the first row, it is always blank.
			if ($count == 0){
				$count++;
				continue;
			}
			//Break up each row into columns
			$cols = array();
			preg_match_all('/<t[dh].*?>\\s*(?:\\s*<!-- .*? -->\\s*)*\\s*(.*?)\\s*<\/t[dh]>/s', $row, $cols, PREG_PATTERN_ORDER);

			$curHolding = array();
			$addHolding = true;
			//Process each cell
			for ($i=0; $i < sizeof($cols[1]); $i++) {
				//Get the value of the cell
				$cellValue = str_replace("&nbsp;"," ",$cols[1][$i]);
				$cellValue = trim(html_entity_decode($cellValue));
				if ($count == 1) {
					//Header cell, this will become the key used later.
					$keys[$i] = $cellValue;
					$addHolding = false;
				} else {
					//We are in the body of the call number field.
					if (sizeof($cols[1]) == 1){
						//This is a special case, i.e. a download link.  Process it differently
						//Get the last holding we processed.
						if (count($ret) > 0){
							$lastHolding = $ret[$numHoldings -1];
							$linkParts = array();
							if (preg_match_all('/<a href=[\'"](.*?)[\'"]>(.*)(?:<\/a>)*/s', $cellValue, $linkParts)){
								$linkCtr = 0;
								foreach ($linkParts[1] as $index => $linkInfo){
									$linkText = $linkParts[2][$index];
									$linkText = trim(preg_replace('/Click here (for|to) access\.?\s*/', '', $linkText));
									$isDownload = preg_match('/(SpringerLink|NetLibrary|digital media|Online version|ebrary|gutenberg|Literature Online)\.?/i', $linkText);
									$linkUrl = $linkParts[1][$index];
									if (preg_match('/netlibrary/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'NetLibrary';
									}elseif (preg_match('/ebscohost/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'Ebsco';
									}elseif (preg_match('/overdrive/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'OverDrive';
									}elseif (preg_match('/ebrary/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'ebrary';
									}elseif (preg_match('/gutenberg/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'Gutenberg Project';
									}elseif (preg_match('/gale/i', $linkUrl)){
										$isDownload = true;
										//$linkText = 'Gale Group';
									}
									$lastHolding['link'][] = array('link' => $linkUrl,
                                                                   'linkText' => $linkText,
                                                                   'isDownload' => $isDownload);
									$linkCtr++;
								}
								$ret[$numHoldings -1] = $lastHolding;
							}

							$addHolding = false;
						}
					}else{
						//This is a normal call number row.
						//should have Location, Call Number, and Status
						if (stripos($keys[$i],$loc_col_name) > -1) {
							//If the location has a link in it, it is a link to a map of the library
							//Process that differently and store independently
							if (preg_match('/<a href=[\'"](.*?)[\'"]>(.*)/s', $cellValue, $linkParts)){
								$curHolding['locationLink'] = $linkParts[1];
								$location = trim($linkParts[2]);
								if (substr($location, strlen($location) -4, 4) == '</a>'){
									$location = substr($location, 0, strlen($location) -4);
								}
								$curHolding['location'] = $location;

							}else{
								$curHolding['location'] = strip_tags($cellValue);
							}
							//Trim off the courier code if one exists
							if (preg_match('/(.*?)\\sC\\d{3}\\w{0,2}$/', $curHolding['location'], $locationParts)){
								$curHolding['location'] = $locationParts[1];
							}else{
								$curHolding['location'] = $curHolding['location'];
							}
						}
						if (stripos($keys[$i],$reserves_col_name) > -1) {
							if (stripos($cellValue,$reserves_key_name) > -1) {  // if the location name has "reserves"
								$curHolding['reserve'] = 'Y';
							} else if(stripos($cols[1][$i],$transit_key_name) > -1) {
								$curHolding['reserve'] = 'Y';
							} else {
								$curHolding['reserve'] = 'N';
							}
						}
						if (stripos($keys[$i],$call_col_name) > -1) {
							$curHolding['callnumber'] = strip_tags($cellValue);
						}
						if (stripos($keys[$i],$status_col_name) > -1) {
							//Load status information
							$curHolding['status'] = $cellValue;
							if (stripos($cellValue,$stat_due) > -1) {
								$p = substr($cellValue,stripos($cellValue,$stat_due));
								$s = trim($p, $stat_due);
								$curHolding['duedate'] = $s;
							}

							$statfull = strip_tags($cellValue);
							if (isset($this->statusTranslations[$statfull])){
								$statfull = $this->statusTranslations[$statfull];
							}else{
								$statfull = strtolower($statfull);
								$statfull = ucwords($statfull);
							}
							$curHolding['statusfull'] = $statfull;
						}
					}

				}
			} //End looping through columns
			if ($addHolding){
				$numHoldings++;
				$curHolding['id'] = $id;
				$curHolding['number'] = $numHoldings;
				$ret[] = $curHolding;
			}
			$count++;
		} //End looping through rows
		$timer->logTime('processed all holdings rows');

		//Load additional item information available only to staff.
		//This takes WAY too long.  Skip for now per Jimmy
		//$staffDetails = $this->_getItemDetails($id, $ret);
		//$curHolding['iType'] = $staffDetails['I TYPE'];
		//$curHolding['barcode'] = $staffDetails['BARCODE'];
		global $locationSingleton;
		$physicalLocation = $locationSingleton->getPhysicalLocation();
		if ($physicalLocation != null){
			$physicalBranch = $physicalLocation->holdingBranchLabel;
		}else{
			$physicalBranch = '';
		}
		$homeBranch    = '';
		$homeBranchId  = 0;
		$nearbyBranch1 = '';
		$nearbyBranch1Id = 0;
		$nearbyBranch2 = '';
		$nearbyBranch2Id = 0;

		//Set location information based on the user login.  This will override information based
		if (isset($user) && $user != false){
			$homeBranchId = $user->homeLocationId;
			$nearbyBranch1Id = $user->myLocation1Id;
			$nearbyBranch2Id = $user->myLocation2Id;
		} else {
			//Check to see if the cookie for home location is set.
			if (isset($_COOKIE['home_location']) && is_numeric($_COOKIE['home_location'])) {
				$cookieLocation = new Location();
				$locationId = $_COOKIE['home_location'];
				$cookieLocation->whereAdd("locationId = '$locationId'");
				$cookieLocation->find();
				if ($cookieLocation->N == 1) {
					$cookieLocation->fetch();
					$homeBranchId = $cookieLocation->locationId;
					$nearbyBranch1Id = $cookieLocation->nearbyLocation1;
					$nearbyBranch2Id = $cookieLocation->nearbyLocation2;
				}
			}
		}
		//Load the holding label for the user's home location.
		$userLocation = new Location();
		$userLocation->whereAdd("locationId = '$homeBranchId'");
		$userLocation->find();
		if ($userLocation->N == 1) {
			$userLocation->fetch();
			$homeBranch = $userLocation->holdingBranchLabel;
		}
		//Load nearby branch 1
		$nearbyLocation1 = new Location();
		$nearbyLocation1->whereAdd("locationId = '$nearbyBranch1Id'");
		$nearbyLocation1->find();
		if ($nearbyLocation1->N == 1) {
			$nearbyLocation1->fetch();
			$nearbyBranch1 = $nearbyLocation1->holdingBranchLabel;
		}
		//Load nearby branch 2
		$nearbyLocation2 = new Location();
		$nearbyLocation2->whereAdd();
		$nearbyLocation2->whereAdd("locationId = '$nearbyBranch2Id'");
		$nearbyLocation2->find();
		if ($nearbyLocation2->N == 1) {
			$nearbyLocation2->fetch();
			$nearbyBranch2 = $nearbyLocation2->holdingBranchLabel;
		}
		$sorted_array = array();

		//Get a list of the display names for all locations based on holding label.
		$locationLabels = array();
		$location = new Location();
		$location->find();
		$libraryLocationLabels = array();
		while ($location->fetch()){
			if (strlen($location->holdingBranchLabel) > 0 && $location->holdingBranchLabel != '???'){
				if ($library && $library->libraryId == $location->libraryId){
					$cleanLabel =  str_replace('/', '\/', $location->holdingBranchLabel);
					$libraryLocationLabels[] = str_replace('.', '\.', $cleanLabel);
				}

				$locationLabels[$location->holdingBranchLabel] = $location->displayName;
			}
		}
		if (count($libraryLocationLabels) > 0){
			$libraryLocationLabels = '/^(' . join('|', $libraryLocationLabels) . ').*/i';
		}else{
			$libraryLocationLabels = '';
		}

		//Get the current Ptype for later usage.
		$pType = $this->getPType();
		$timer->logTime('setup for additional holdings processing.');

		//Now that we have the holdings, we need to filter and sort them according to scoping rules.
		$i = 0;
		foreach ($ret as $holding){
			$holding['type'] = 'holding';
			//Process holdings without call numbers - Need to show items without call numbers
			//because they may have links, etc.  Also show if there is a status.  Since some
			//In process items may not have a call number yet.
			if ( (!isset($holding['callnumber']) || strlen($holding['callnumber']) == 0) &&
			(!isset($holding['link']) || count($holding['link']) == 0) && !isset($holding['status'])){
				continue;
			}

			//Determine if the holding is available or not.
			//First check the status
			if (preg_match('/^' . $this->availableStatiRegex . '$/', $holding['status'])){
				$holding['availability'] = 1;
			}else{
				$holding['availability'] = 0;
			}
			if (preg_match('/^' . $this->holdableStatiRegex . '$/', $holding['status'])){
				$holding['holdable'] = 1;
			}else{
				$holding['holdable'] = 0;
				$holding['nonHoldableReason'] = "This item is not currently available for Patron Holds";
			}
			//Now check the location
			if (array_key_exists($holding['location'], $this->nonHoldableLocations)){
				$holding['holdable'] = 0;
				if ($this->nonHoldableLocations[$holding['location']]->availableAtCircDesk == 1){
					$holding['nonHoldableReason'] = "To place a hold for this item, please see the Circulation Desk.";
				}else{
					$holding['nonHoldableReason'] = "This item is not available for Patron Holds";
				}
			}
			//Now check location and Ptype
			if (array_key_exists($holding['location'], $this->ptypeRestrictedLocations)){
				$ptypeLocation = $this->ptypeRestrictedLocations[$holding['location']];
				$allowablePtypes = $ptypeLocation->allowablePtypes;
				if (preg_match("~$allowablePtypes~", $pType)){
					//Allow the holding?
				}else{
					$holding['holdable'] = 0;
					$holding['nonHoldableReason'] = "This item is only available to local library patrons.";
				}
			}
			//Get the library display name for the holding location
			/*foreach ($locationLabels as $holdingLabel => $displayName){
			if (strpos($holding['location'], $holdingLabel) !== false){
			$holding['libraryDisplayName'] = $displayName;
			break;
			}
			}*/
			if (!isset($holding['libraryDisplayName'])){
				$holding['libraryDisplayName'] = $holding['location'];
			}

			//Add the holding to the sorted array to determine
			$sortString = $holding['location'] . $holding['callnumber']. $i;
			if (strlen($physicalBranch) > 0 && stripos($holding['location'], $physicalBranch) !== false){
				//If the user is in a branch, those holdings come first.
				$holding['section'] = 'In this library';
				$sorted_array['1' . $sortString] = $holding;
			} else if (strlen($homeBranch) > 0 && stripos($holding['location'], $homeBranch) !== false){
				//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
				$holding['section'] = 'Your library';
				$sorted_array['2' . $sortString] = $holding;
			} else if ((strlen($nearbyBranch1) > 0 && stripos($holding['location'], $nearbyBranch1) !== false)){
				//Next come nearby locations for the user
				$holding['section'] = 'Nearby Libraries';
				$sorted_array['3' . $sortString] = $holding;
			} else if ((strlen($nearbyBranch2) > 0 && stripos($holding['location'], $nearbyBranch2) !== false)){
				//Next come nearby locations for the user
				$holding['section'] = 'Nearby Libraries';
				$sorted_array['4' . $sortString] = $holding;
			} else if (strlen($libraryLocationLabels) > 0 && preg_match($libraryLocationLabels, $holding['location'])){
				//Next come any locations within the same system we are in.
				$holding['section'] = $library->displayName;
				$sorted_array['5' . $sortString] = $holding;
			} else {
				//Finally, all other holdings are shown sorted alphabetically.
				$holding['section'] = 'Other Locations';
				$sorted_array['6' . $sortString] = $holding;
			}
			$i++;
		}
		$timer->logTime('finished processign holdings');

		//Load order records, these only show in the full page view, not the item display
		$orderMatches = array();
		if (preg_match_all('/<tr\\s+class="bibOrderEntry">.*?<td\\s*>(.*?)<\/td>/s', $millenniumInfo->framesetInfo, $orderMatches)){
			for ($i = 0; $i < count($orderMatches[1]); $i++) {
				$location = trim($orderMatches[1][$i]);
				$sorted_array['7' . $location . $i] = array(
                    'location' => $location,
                    'section' => 'On Order',
                    'holdable' => 1,
				);
			}
		}
		$timer->logTime('loaded order records');

		ksort($sorted_array);

		//Check to see if we can remove the sections.
		//We can if all section keys are the same.
		$removeSection = true;
		$lastKeyIndex = '';
		foreach ($sorted_array as $key => $holding){
			$currentKey = substr($key, 0, 1);
			if ($lastKeyIndex == ''){
				$lastKeyIndex = $currentKey;
			}else if ($lastKeyIndex != $currentKey){
				$removeSection = false;
				break;
			}
		}
		foreach ($sorted_array as $key => $holding){
			if ($removeSection == true){
				$holding['section'] = '';
				$sorted_array[$key] = $holding;
			}
		}

		$issueSummaries = $this->getIssueSummaries($id, $millenniumInfo);
		$timer->logTime('loaded issue summaries');
		if (!is_null($issueSummaries)){
			//Group holdings under the issue issue summary that is related.
			foreach ($sorted_array as $key => $holding){
				//Have issue summary = false
				$haveIssueSummary = false;
				$issueSummaryKey = null;
				foreach ($issueSummaries as $issueKey => $issueSummary){
					if ($issueSummary['location'] == $holding['location']){
						$haveIssueSummary = true;
						$issueSummaryKey = $issueKey;
						break;
					}
				}

				if ($haveIssueSummary){
					$issueSummaries[$issueSummaryKey]['holdings'][$key] = $holding;
				}else{
					//Need to automatically add a summary so we don't lose data
					$issueSummaries[$holding['location']] = array(
                        'location' => $holding['location'],
                        'type' => 'issue',
                        'holdings' => array($key => $holding),
					);
				}
			}
			ksort($issueSummaries);
			$this->statuses[$id] = $issueSummaries;
			return $issueSummaries;
		}else{
			$this->statuses[$id] = $sorted_array;
			return $sorted_array;
		}


	}

	public function getStatuses($ids) {
		$items = array();
		$count = 0;
		foreach ($ids as $id) {
			$items[$count] = $this->getStatus($id);
			$count++;
		}
		return $items;
	}

	/**
	 * Returns a summary of the holdings information for a single id. Used to display
	 * within the search results and at the top of a full record display to ensure
	 * the holding information makes sense to all users.
	 *
	 * @param string $id the id of the bid to load holdings for
	 * @return array an associative array with a summary of the holdings.
	 */
	public function getStatusSummary($id){
		global $timer;
		$holdings = $this->getStatus($id);

		$counter = 0;
		$summaryInformation = array();
		$summaryInformation['recordId'] = $id;
		$summaryInformation['shortId'] = substr($id, 1);
		$summaryInformation['isDownloadable'] = false; //Default value, reset later if needed.

		//Check to see if we are getting issue summaries or actual holdings
		$isIssueSummary = false;
		$numSubscriptions = 0;
		if (count($holdings) > 0){
			$lastHolding = end($holdings);
			if (isset($lastHolding['type']) && ($lastHolding['type'] == 'issueSummary' || $lastHolding['type'] == 'issue')){
				$isIssueSummary = true;
				$issueSummaries = $holdings;
				$numSubscriptions = count($issueSummaries);
				$holdings = array();
				foreach ($issueSummaries as $issueSummary){
					if (isset($issueSummary['holdings'])){
						$holdings = array_merge($holdings, $issueSummary['holdings']);
					}else{
						//Create a fake holding for subscriptions so something
						//will be displayed in the holdings summary.
						$holdings[$issueSummary['location']] = array(
                            'availability' => '1',
                            'location' => $issueSummary['location'],
                            'libraryDisplayName' => $issueSummary['location'],
                            'callnumber' => isset($issueSummary['cALL']) ? $issueSummary['cALL'] : '',
                            'status' => 'Lib Use Only',
                            'statusfull' => 'In Library Use Only',
						);
					}
				}
			}
		}

		global $library;
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		$canShowHoldButton = true;
		if ($library && $library->showHoldButton == 0){
			$canShowHoldButton = false;
		}
		if ($location != null && $location->showHoldButton == 0){
			$canShowHoldButton = false;
		}
			
		//Valid statuses are:
		//It's here
		//  - at the physical location and not checked out
		//  - also show the call number for the location
		//  - do not show place hold button
		//It's at *location*
		//  - at the user's home branch or preferred location and not checked out
		//  - also show the call number for the location
		//  - show place hold button
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
		$additionalAvailableLocations = array();
		$unavailableStatus = null;
		//The status of all items.  Will be set to an actual status if all are the same
		//or null if the item statuses are inconsistent
		$allItemStatus = '';
		foreach ($holdings as $holdingKey => $holding){
			if (is_null($allItemStatus)){
				//Do nothing, the status is not distinct
			}else if ($allItemStatus == ''){
				$allItemStatus = $holding['statusfull'];
			}elseif($allItemStatus != $holding['statusfull']){
				$allItemStatus = null;
			}
			if (isset($holding['availability']) && $holding['availability'] == 1){
				$numAvailableCopies++;
				$addToAvailableLocation = false;
				$addToAdditionalAvailableLocation = false;
				//Check to see if the location should be listed in the list of locations that the title is available at.
				//Can only be in this system if there is a system active.
				if (sizeof($availableLocations) < 3 && !in_array($holding['libraryDisplayName'], $availableLocations)){
					if (isset($library)){
						//Check to see if the location is within this library system. It is if the key is less than or equal to 5
						if (substr($holdingKey, 0, 1) <= 5){
							$addToAvailableLocation = true;
						}
					}else{
						$addToAvailableLocation = true;
					}
				}
				//Check to see if the location is listed in the count of additional locations (can be any system).
				if (!$addToAvailableLocation && !in_array($holding['libraryDisplayName'], $availableLocations) && !in_array($holding['libraryDisplayName'], $additionalAvailableLocations)){
					$addToAdditionalAvailableLocation = true;
				}
				if ($addToAvailableLocation){
					$availableLocations[] = $holding['libraryDisplayName'];
				}elseif ($addToAdditionalAvailableLocation){
					$additionalAvailableLocations[] = $holding['libraryDisplayName'];
				}
			}else{
				if ($unavailableStatus == null){
					$unavailableStatus = $holding['status'];
				}
			}

			if (isset($holding['holdable']) && $holding['holdable'] == 1){
				$numHoldableCopies++;
			}
			$numCopies++;
			//Check to see if the holding has a download link and if so, set that info.
			if (isset($holding['link'])){
				foreach ($holding['link'] as $link){
					if ($link['isDownload']){
						$summaryInformation['status'] = "Available for Download";
						$summaryInformation['class'] = 'here';
						$summaryInformation['isDownloadable'] = true;
						$summaryInformation['downloadLink'] = $link['link'];
						$summaryInformation['downloadText'] = $link['linkText'];
					}
				}
			}
			//Only show a call number if the book is at the user's home library, one of their preferred libraries, or in the library they are in.
			$showItsHere = ($library == null) ? true : ($library->showItsHere == 1);
			if (in_array(substr($holdingKey, 0, 1), array('1', '2', '3', '4', '5')) && !isset($summaryInformation['callnumber'])){
				$summaryInformation['callnumber'] = $holding['callnumber'];
			}
			if ($showItsHere && substr($holdingKey, 0, 1) == '1' && $holding['availability'] == 1){
				//The item is available within the physical library.  Patron should go get it off the shelf
				$summaryInformation['status'] = "It's here";
				$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				$summaryInformation['class'] = 'here';
			}elseif ($showItsHere && !isset($summaryInformation['status']) &&
			substr($holdingKey, 0, 1) >= 2 && (substr($holdingKey, 0, 1) <= 4) &&
			$holding['availability'] == 1){
				//The item is at one of the patron's preferred branches.
				$summaryInformation['status'] = "It's at " . $holding['location'];
				$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				$summaryInformation['class'] = 'nearby';
			}elseif (!isset($summaryInformation['status']) &&
			((!$showItsHere && substr($holdingKey, 0, 1) <= 5) || substr($holdingKey, 0, 1) == 5 || !isset($library) ) &&
			$holding['availability'] == 1){
				//The item is at a location either in the same system or another system.
				$summaryInformation['status'] = "Available At";
				$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				$summaryInformation['class'] = 'available';
			}elseif (!isset($summaryInformation['status']) &&
			(substr($holdingKey, 0, 1) == 6 ) &&
			$holding['availability'] == 1){
				//The item is at a location either in the same system or another system.
				$summaryInformation['status'] = "Marmot";
				$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				$summaryInformation['class'] = 'available';
			}elseif (substr($holdingKey, 0, 1) == 7){
				$numCopiesOnOrder++;
				$numCopies--; //Don't increment number of copies for titles we don't have yet.
			}
		}

		//If all items are checked out the status will still be blank
		$summaryInformation['availableCopies'] = $numAvailableCopies;
		$summaryInformation['holdableCopies'] = $numHoldableCopies;
		if ($numHoldableCopies == 0){
			$summaryInformation['showPlaceHold'] = false;
		}

		$summaryInformation['numCopiesOnOrder'] = $numCopiesOnOrder;
		//Do some basic sanity checking to make sure that we show the total copies
		//With at least as many copies as the number of copies on order.
		if ($numCopies < $numCopiesOnOrder){
			$summaryInformation['numCopies'] = $numCopiesOnOrder;
		}else{
			$summaryInformation['numCopies'] = $numCopies;
		}

		if ($unavailableStatus != 'ONLINE'){
			$summaryInformation['unavailableStatus'] = $unavailableStatus;
		}

		//Status is not set, check to see if the item is downloadable
		if (!isset($summaryInformation['status'])){
			//Check to see if there is a download link in the 856 field
			//Make sure that the search engine has been setup.  It may not be if the
			//this is an AJAX request where the search engine is not needed otherwise.
			$searchObject = SearchObjectFactory::initSearchObject();
			global $configArray;
			$class = $configArray['Index']['engine'];
			$url = $configArray['Index']['url'];
			$this->db = new $class($url);
			if ($configArray['System']['debugSolr']) {
				$this->db->debug = true;
			}

			// Retrieve Full Marc Record
			$recordURL = null;
			if (!($record = $this->db->getRecord($id))) {
				//Must not be a MARC record. Ignore it for now.
			}else{
				// Process MARC Data
				require_once 'sys/MarcLoader.php';
				$marcRecord = MarcLoader::loadMarcRecordFromRecord($record);
				if ($marcRecord) {
					//Check the 856 tag to see if there is a URL
					if ($linkField = $marcRecord->getField('856')) {
						if ($linkURLField = $linkField->getSubfield('u')) {
							$linkURL = $linkURLField->getData();
						}
						if ($linkTextField = $linkField->getSubfield('y')) {
							$linkText = $linkTextField->getData();
						}else if ($linkTextField = $linkField->getSubfield('z')) {
							$linkText = $linkTextField->getData();
						}else if ($linkTextField = $linkField->getSubfield('3')) {
							$linkText = $linkTextField->getData();
						}
					}
				} else {
					//Can't process the marc record, ignore it.
				}
			}

			//If there is a link, add that status information.
			if (isset($linkURL) && !preg_match('/.*\.(?:gif|jpg|jpeg|tif|tiff)/', $linkURL)){
				$linkTestText = $linkText . ' ' . $linkURL;
				$isDownload = preg_match('/SpringerLink|NetLibrary|digital media|Online version\.|ebrary|gutenberg/i', $linkTestText);
				if ($linkTestText == 'digital media') $linkText = 'OverDrive';
				if (preg_match('/netlibrary/i', $linkURL)){
					$isDownload = true;
					$linkText = 'NetLibrary';
				}elseif(preg_match('/ebscohost/i', $linkURL)){
					$isDownload = true;
					$linkText = 'Ebsco';
				}elseif(preg_match('/overdrive/i', $linkURL)){
					$isDownload = true;
					$linkText = 'OverDrive';
				}elseif(preg_match('/ebrary/i', $linkURL)){
					$isDownload = true;
					$linkText = 'ebrary';
				}elseif(preg_match('/gutenberg/i', $linkURL)){
					$isDownload = true;
					$linkText = 'Gutenberg Project';
				}elseif(preg_match('/.*\.[pdf]/', $linkURL)){
					$isDownload = true;
				}
				if ($isDownload){
					$summaryInformation['status'] = "Available for Download";
					$summaryInformation['class'] = 'here';
					$summaryInformation['isDownloadable'] = true;
					$summaryInformation['downloadLink'] = $linkURL;
					$summaryInformation['downloadText'] = isset($linkText)? $linkText : 'Download';
				}
			}
		}

		if ($summaryInformation['status'] != "It's here"){
			//Replace all spaces in the name of a location with no break spaces
			foreach ($availableLocations as $key => $location){
				$availableLocations[$key] = str_replace(' ', ' ', $location);
			}
			$summaryInformation['availableAt'] = join(', ', $availableLocations);
			if ($summaryInformation['status'] == 'Marmot'){
				$summaryInformation['numAvailableOther'] = count($additionalAvailableLocations) + count($availableLocations);
			}else{
				$summaryInformation['numAvailableOther'] = count($additionalAvailableLocations);
			}
		}

		//If Status is still not set, apply some logic based on number of copies
		if (!isset($summaryInformation['status'])){
			if ($numCopies == 0){
				if ($numCopiesOnOrder > 0){
					//No copies are currently available, but we do have some that are on order.
					//show the status as on order and make it available.
					$summaryInformation['status'] = "On Order";
					$summaryInformation['class'] = 'available';
					$summaryInformation['showPlaceHold'] = $canShowHoldButton;
				}else{
					//Deal with weird cases where there are no items by saying it is unavailable
					$summaryInformation['status'] = "Unavailable";
					$summaryInformation['showPlaceHold'] = false;
					$summaryInformation['class'] = 'unavailable';
				}
			}else{
				if ($numHoldableCopies == 0 && $canShowHoldButton){
					$summaryInformation['status'] = "Not Available For Checkout";
					$summaryInformation['showPlaceHold'] = false;
					$summaryInformation['class'] = 'reserve';
				}else{
					$summaryInformation['status'] = "Checked Out";
					$summaryInformation['showPlaceHold'] = $canShowHoldButton;
					$summaryInformation['class'] = 'checkedOut';
				}
			}
		}

		//Reset status if the status for all items is consistent.
		//That way it will jive with the actual full record display.
		if ($allItemStatus != null && $allItemStatus != ''){
			//Only override this for statuses that don't have special meaning
			if ($summaryInformation['status'] != 'Marmot' && $summaryInformation['status'] != 'Available At'){
				$summaryInformation['status'] = $allItemStatus;
			}
		}
		if ($allItemStatus == 'In Library Use Only'){
			$summaryInformation['inLibraryUseOnly'] = true;
		}else{
			$summaryInformation['inLibraryUseOnly'] = false;
		}


		if ($summaryInformation['availableCopies'] == 0 && $summaryInformation['isDownloadable'] == true){
			$summaryInformation['showAvailabilityLine'] = false;
		}else{
			$summaryInformation['showAvailabilityLine'] = true;
		}

		//Clear unavailable status if it matches the status
		if (isset($summaryInformation['unavailableStatus']) && strcasecmp(trim($summaryInformation['unavailableStatus']), trim($summaryInformation['status'])) == 0){
			$summaryInformation['unavailableStatus'] = '';
		}

		return $summaryInformation;
	}

	/**
	 * Returns summary information for an array of ids.  This allows the search results
	 * to query all holdings at one time.
	 *
	 * @param array $ids an array ids to load summary information for.
	 * @return array an associative array containing a second array with summary information.
	 */
	public function getStatusSummaries($ids){
		$items = array();
		$count = 0;
		foreach ($ids as $id) {
			$items[$count] = $this->getStatusSummary($id);
			$count++;
		}
		return $items;
	}

	public function getHolding($id)
	{
		return $this->getStatus($id);
	}

	public function getPurchaseHistory($id)
	{
		return array();
	}

	/**
	 * Patron Login
	 *
	 * This is responsible for authenticating a patron against the catalog.
	 * Interface defined in CatalogConnection.php
	 *
	 * @param   string  $username   The patron username
	 * @param   string  $password   The patron password
	 * @return  mixed               A string of the user's ID number
	 *                              If an error occures, return a PEAR_Error
	 * @access  public
	 */
	public function patronLogin($username, $password)
	{
		global $timer;

		//Strip any non digit characters from the password
		$password = preg_replace('/[a-or-zA-OR-Z\W]/', '', $password);
		$id2= $password;

		//Load the raw information about the patron
		$patronDump = $this->_getPatronDump($id2);

		//TODO:  Verify this will work with all types of names including hypenation

		//Create a variety of possible name combinations for testing purposes.
		$Fullname = str_replace(","," ",$patronDump['PATRN_NAME']);
		$Fullname = str_replace(";"," ",$Fullname);
		$Fullname = str_replace(";","'",$Fullname);
		$allNameComponents = preg_split('^[\s-]^', strtolower($Fullname));
		$nameParts = explode(' ',$Fullname);
		$lastname = strtolower($nameParts[0]);
		$middlename = isset($nameParts[2]) ? strtolower($nameParts[2]) : '';
		$firstname = isset($nameParts[1]) ? strtolower($nameParts[1]) : $middlename;

		//Get the first name that the user supplies.
		//This expects the user to enter one or two names and only
		//Validates the first name that was entered.
		$enteredNames=preg_split('^[\s-]^', strtolower($username));
		$userValid = false;
		foreach ($enteredNames as $name){
			if (in_array($name, $allNameComponents, false)){
				$userValid = true;
				break;
			}
		}
		if ($userValid){
			$user = array(
                'id'        => $id2,
                'username'  => $patronDump['RECORD_#'],
                'firstname' => $firstname,
                'lastname'  => $lastname,
                'fullname'  => $Fullname,     //Added to array for possible display later. 
                'cat_username' => $username, //Should this be $Fullname or $patronDump['PATRN_NAME']
                'cat_password' => $password,

                'email' => isset($patronDump['EMAIL_ADDR']) ? $patronDump['EMAIL_ADDR'] : '',
                'major' => null,
                'college' => null);		
			$timer->logTime("patron logged in successfully");
			return $user;

		} else {
			$timer->logTime("patron login failed");
			return null;
		}

	}

	private $patronProfiles = array();
	/**
	 * Get Patron Profile
	 *
	 * This is responsible for retrieving the profile for a specific patron.
	 * Interface defined in CatalogConnection.php
	 *
	 * @param   array   $patron     The patron array
	 * @return  array               Array of the patron's profile data
	 *                              If an error occures, return a PEAR_Error
	 * @access  public
	 */
	public function getMyProfile($patron)
	{
		global $timer;

		if (is_object($patron)){
			$patron = get_object_vars($patron);
			$id2 = $patron['cat_password'];
		}else{
			$id2= $patron['id'];
		}

		if (array_key_exists($patron['id'], $this->patronProfiles)){
			$timer->logTime('Retrieved Cached Profile for Patron');
			return $this->patronProfiles[$patron['id']];
		}

		//Load the raw information about the patron
		$patronDump = $this->_getPatronDump($id2);

		$Fulladdress = $patronDump['ADDRESS'];
		$addressParts =explode('$',$Fulladdress);
		$Address1 = $addressParts[0];
		$City = isset($addressParts[1]) ? $addressParts[1] : '';
		$State = isset($addressParts[2]) ? $addressParts[2] : '';
		$Zip = isset($addressParts[3]) ? $addressParts[3] : '';

		if (preg_match('/(.*?),\\s+(.*)\\s+(\\d*(?:-\\d*)?)/', $City, $matches)) {
			$City = $matches[1];
			$State = $matches[2];
			$Zip = $matches[3];
		}
		$Fullname = $patronDump['PATRN_NAME'];

		$nameParts = explode(', ',$Fullname);
		$lastname = $nameParts[0];
		$secondname = isset($nameParts[1]) ? $nameParts[1] : '';
		if (strpos($secondname, ' ')){
			list($firstname, $middlename)=explode(' ', $secondname);
		}else{
			$firstname = $secondname;
			$middlename = '';
		}

		//Get additional information about the patron's home branch for display.
		$homeBranchCode = $patronDump['HOME_LIBR'];
		//Translate home branch to plain text
		global $user;
		$homeBranch = $homeBranchCode;

		$location = new Location();
		$location->whereAdd("code = '$homeBranchCode'");
		$location->find(1);

		if ($user) {
			if ($user->homeLocationId == 0) {
				$user->homeLocationId = $location->locationId;
				if ($location->nearbyLocation1 > 0){
					$user->myLocation1Id = $location->nearbyLocation1;
				}else{
					$user->myLocation1Id = $location->locationId;
				}
				if ($location->nearbyLocation2 > 0){
					$user->myLocation2Id = $location->nearbyLocation2;
				}else{
					$user->myLocation2Id = $location->locationId;
				}
				if ($user instanceof User) {
					//Update the database
					$user->update();
					//Update the serialized instance stored in the session
					$_SESSION['userinfo'] = serialize($user);
				}
			}

			//Get displayname for prefered location 1
			$myLocation1 = new Location();
			$myLocation1->whereAdd("locationId = '$user->myLocation1Id'");
			$myLocation1->find(1);

			//Get displayname for prefered location 1
			$myLocation2 = new Location();
			$myLocation2->whereAdd("locationId = '$user->myLocation2Id'");
			$myLocation2->find(1);
		}

		//see if expiration date is close
		list ($monthExp, $dayExp, $yearExp) = explode("-",$patronDump['EXP_DATE']);
		$timeExpire = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
		$timeNow = time();
		$timeToExpire = $timeExpire - $timeNow;
		if ($timeToExpire <= 30 * 24 * 60 * 60){
			$expireClose = 1;
		}else{
			$expireClose = 0;
		}

		$finesVal = floatval(preg_replace('/[^\\d.]/', '', $patronDump['MONEY_OWED']));
		
		$numHoldsAvailable = 0;
		$numHoldsRequested = 0;
		if (isset($patronDump['HOLD']) && count($patronDump['HOLD']) > 0){
			foreach ($patronDump['HOLD'] as $hold){
				if (preg_match('/ST=(105|98),/', $hold)){
					$numHoldsAvailable++;
				}else{
					$numHoldsRequested++;
				}
			}
		}
		$profile = array('lastname' => $lastname,
				'firstname' => $firstname,
				'fullname' => $Fullname,
				'address1' => $Address1,
				'address2' => $City . ', ' . $State,
				'city' => $City,
				'state' => $State,
				'zip'=> $Zip,
				'email' => isset($patronDump['EMAIL_ADDR']) ? $patronDump['EMAIL_ADDR'] : '',
				'phone' => $patronDump['TELEPHONE'],
				'fines' => $patronDump['MONEY_OWED'],
				'finesval' =>$finesVal,
				'expires' =>$patronDump['EXP_DATE'],
				'expireclose' =>$expireClose,
				'homeLocationCode' => trim($homeBranchCode),
				'homeLocationId' => $location->locationId,
				'homeLocation' => $location->displayName,
				'myLocation1Id' => ($user) ? $user->myLocation1Id : -1,
				'myLocation1' => isset($myLocation1) ? $myLocation1->displayName : '',
				'myLocation2Id' => ($user) ? $user->myLocation2Id : -1,
				'myLocation2' => isset($myLocation2) ? $myLocation2->displayName : '',
				'numCheckedOut' => $patronDump['CUR_CHKOUT'],
				'numHolds' => isset($patronDump['HOLD']) ? count($patronDump['HOLD']) : 0,
				'numHoldsAvailable' => $numHoldsAvailable,
				'numHoldsRequested' => $numHoldsRequested,
				'bypassAutoLogout' => ($user) ? $user->bypassAutoLogout : 0,
				'ptype' => $patronDump['P_TYPE'],
		);

		//Get eContent info as well
		require_once('Drivers/EContentDriver.php');
		$eContentDriver = new EContentDriver();
		$eContentAccountSummary = $eContentDriver->getAccountSummary();
		$profile = array_merge($profile, $eContentAccountSummary);

		//Get a count of the materials requests for the user
		$materialsRequest = new MaterialsRequest();
		$materialsRequest->createdBy = $user->id;
		$statusQuery = new MaterialsRequestStatus();
		$statusQuery->isOpen = 1;
		$materialsRequest->joinAdd($statusQuery);
		$materialsRequest->find();
		$profile['numMaterialsRequests'] = $materialsRequest->N;
		
		$timer->logTime("Got Patron Profile");
		$this->patronProfiles[$patron['id']] = $profile;
		return $profile;
	}

	/**
	 * Get a dump of information from Millenium that can be used in other
	 * routines.
	 *
	 * @param string $barcode the patron's barcode
	 */
	private function _getPatronDump($barcode)
	{
		global $configArray;
		global $memcache;
		global $timer;
		$patronDump = $memcache->get("patron_dump_$barcode");
		if (!$patronDump){
			$host=$configArray['OPAC']['patron_host'];
			//Special processing to allow MCVSD Students to login
			//with their student id.
			if (strlen($barcode)== 5){
				$barcode = "41000000" . $barcode;
			}
			
			// Load Record Page.  This page has a dump of all patron information
			//as a simple name value pair list within the body of the webpage.
			//Sample format of a row is as follows:
			//P TYPE[p47]=100<BR>
			$req =  $host . "/PATRONAPI/" . $barcode ."/dump" ;
			$req = new Proxy_Request($req);
			//$result = file_get_contents($req);
			if (PEAR::isError($req->sendRequest())) {
				return null;
			}
			$result = $req->getResponseBody();
			
			//Strip the acutal contents out of the body of the page.
			$r = substr($result, stripos($result, 'BODY'));
			$r = substr($r,strpos($r,">")+1);
			$r = substr($r,0,stripos($r,"</BODY"));
	
			//Remove the bracketted information from each row
			$r = preg_replace("/\[.+?]=/","=",$r);
		
			//Split the rows on each BR tag.
			//This could also be done with a regex similar to the following:
			//(.*)<BR\s*>
			//And then get all matches of group 1.
			//Or a regex similar to
			//(.*?)\[.*?\]=(.*?)<BR\s*>
			//Group1 would be the keys and group 2 the values.
			$rows = preg_replace("/<BR.*?>/","*",$r);
			$rows = explode("*",$rows);
			//Add the key and value from each row into an associative array.
			$ret = array();
			$patronDump = array();
			foreach ($rows as $row) {
				if (strlen(trim($row)) > 0){
					$ret = explode("=",$row, 2);
					//$patronDump[str_replace(" ", "_", trim($ret[0]))] = str_replace("$", " ",$ret[1]);
					$patronDumpKey = str_replace(" ", "_", trim($ret[0]));
					//Holds can be an array, treat them differently.
					if ($patronDumpKey == 'HOLD'){
						$patronDump[$patronDumpKey][] = isset($ret[1]) ? $ret[1] : '';
					}else{
						$patronDump[$patronDumpKey] = isset($ret[1]) ? $ret[1] : '';
					}
				}
			}
			$timer->logTime("Got patron information from Patron API");
			
			if (isset($configArray['ERRNUM'])){
				return null;
			}else{
				
				$memcache->set("patron_dump_$barcode", $patronDump, 0, $configArray['Caching']['patron_dump']);
				//Need to wait a little bit since getting the patron api locks the record in the DB
				usleep(250);
			}
		}
		return $patronDump;
	}

	private $curl_connection;
	/**
	 * Uses CURL to fetch a page from millenium and return the raw results
	 * for further processing.
	 *
	 * Performs minimal processing on it's own to remove HTML comments.
	 *
	 * @param array  $patronInfo information about a patron fetched from millenium
	 * @param string $page       The page to load within millenium
	 *
	 * @return string the result of the page load.
	 */
	private function _fetchPatronInfoPage($patronInfo, $page, $additionalGetInfo = array(), $additionalPostInfo = array(), $cookieJar = null, $admin = false, $startNewSession = true, $closeSession = true)
	{
		$deleteCookie = false;
		if (is_null($cookieJar)){
			$cookieJar = tempnam ("/tmp", "CURLCOOKIE");
			$deleteCookie = true;
		}
		$logger = new Logger();
		//$logger->log('PatronInfo cookie ' . $cookie, PEAR_LOG_INFO);
		global $configArray;
		$scope = $this->getDefaultScope();
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronInfo['RECORD_#'] ."/$page";
		$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);
		//echo "$curl_url";
		$this->curl_connection = curl_init($curl_url);

		curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($this->curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($this->curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
		curl_setopt($this->curl_connection, CURLOPT_COOKIESESSION, is_null($cookieJar) ? true : false);
		if ($admin){
			global $configArray;
			$post_data['name'] = $configArray['Catalog']['ils_admin_user'];
			$post_data['code'] = $configArray['Catalog']['ils_admin_pwd'];
		}else{
			$post_data['name'] = $patronInfo['PATRN_NAME'];
			$post_data['code'] = $patronInfo['P_BARCODE'];
		}
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($this->curl_connection);

		if (true){
			curl_close($this->curl_connection);
		}

		//For debugging purposes
		//echo "<h1>CURL Results</h1>For URL: $curl_url<br /> $sresult";
		if ($deleteCookie){
			unlink($cookieJar);
		}

		//Strip HTML comments
		$sresult = preg_replace("/<!--([^(-->)]*)-->/"," ",$sresult);
		return $sresult;
	}

	public function getMyTransactions($patron, $page = 1, $recordsPerPage = -1, $sortOption = 'dueDate')
	{
		$id2= $patron['id'];
		$patronDump = $this->_getPatronDump($id2);

		//Load the information from millenium using CURL
		$sresult = $this->_fetchPatronInfoPage($patronDump, 'items');

		$sresult = preg_replace("/<[^<]+?>\W<[^<]+?>\W\d* ITEM.? CHECKED OUT<[^<]+?>\W<[^<]+?>/", "", $sresult);

		$s = substr($sresult, stripos($sresult, 'patFunc'));
			
		$s = substr($s,strpos($s,">")+1);

		$s = substr($s,0,stripos($s,"</table"));

		$s = preg_replace ("/<br \/>/","", $s);

		$srows = preg_split("/<tr([^>]*)>/",$s);
		$scount = 0;
		$skeys = array_pad(array(),10,"");
		$checkedOutTitles = array();

		//Get patron's location to determine if renewals are allowed.
		global $locationSingleton;
		$patronLocation = $locationSingleton->getUserHomeLocation();
		if (isset($patronLocation)){
			$patronPtype = $this->getPType();
			$patronCanRenew = false;
			if ($patronLocation->ptypesToAllowRenewals == '*'){
				$patronCanRenew = true;
			}else if (preg_match("/^({$patronLocation->ptypesToAllowRenewal})$/", $patronPtype)){
				$patronCanRenew = true;
			}
		}else{
			$patronCanRenew = true;
		}

		foreach ($srows as $srow) {
			$scols = preg_split("/<t(h|d)([^>]*)>/",$srow);
			$curTitle = array();
			for ($i=0; $i < sizeof($scols); $i++) {
				$scols[$i] = str_replace("&nbsp;"," ",$scols[$i]);
				$scols[$i] = preg_replace ("/<br+?>/"," ", $scols[$i]);
				$scols[$i] = html_entity_decode(trim(substr($scols[$i],0,stripos($scols[$i],"</t"))));
				//print_r($scols[$i]);
				if ($scount == 1) {
					$skeys[$i] = $scols[$i];
				} else if ($scount > 1) {

					if (stripos($skeys[$i],"TITLE") > -1) {

						if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $scols[$i], $matches)) {
							$shortId = $matches[1];
							$bibid = '.' . $matches[1];
							$title = $matches[2];
						}else{
							$title = $scols[$i];
							$shortId = '';
							$bibid = '';
						}
						$curTitle['shortId'] = $shortId;
						$curTitle['id'] = $bibid;
						$curTitle['title'] = $title;
					}

					if (stripos($skeys[$i],"STATUS") > -1) {
						// $sret[$scount-2]['duedate'] = strip_tags($scols[$i]);
						$due = trim(str_replace("DUE", "", strip_tags($scols[$i])));
						$renewCount = 0;
						if (preg_match('/FINE\(up to now\) (\$\d+\.\d+)/i', $due, $matches)){
							$curTitle['fine'] = trim($matches[1]);
						}
						if (preg_match('/(.*)Renewed (\d+) time(?:s)?/i', $due, $matches)){
							$due = trim($matches[1]);
							$renewCount = $matches[2];
						}else if (preg_match('/(.*)\+\d+ HOLD.*/i', $due, $matches)){
							$due = trim($matches[1]);
						}
						if (preg_match('/(\d{2}-\d{2}-\d{2})/', $due, $dueMatches)){
							$dateDue = DateTime::createFromFormat('m-d-y', $dueMatches[1]);
							if ($dateDue){
								$dueTime = $dateDue->getTimestamp();
							}else{
								$dueTime = null;
							}
						}else{ 
							$dueTime = strtotime($due);
						}
						if ($dueTime != null){
							$daysUntilDue = ceil(($dueTime - time()) / (24 * 60 * 60));
							$overdue = $daysUntilDue < 0;
							$curTitle['duedate'] = $dueTime;
							$curTitle['overdue'] = $overdue;
							$curTitle['daysUntilDue'] = $daysUntilDue;
						}
						$curTitle['renewCount'] = $renewCount;
						
					}

					if (stripos($skeys[$i],"BARCODE") > -1) {
						$curTitle['barcode'] = strip_tags($scols[$i]);
					}


					if (stripos($skeys[$i],"RENEW") > -1) {
						$matches = array();
						if (preg_match('/<input\s*type="checkbox"\s*name="renew(\d+)"\s*id="renew(\d+)"\s*value="(.*?)"\s*\/>/', $scols[$i], $matches)){
							$curTitle['canrenew'] = $patronCanRenew;
							$curTitle['itemindex'] = $matches[1];
							$curTitle['itemid'] = $matches[3];
							$curTitle['renewIndicator'] = $curTitle['itemid'] . '|' . $curTitle['itemindex'];
						}else{
							$curTitle['canrenew'] = false;
						}

					}


					if (stripos($skeys[$i],"CALL NUMBER") > -1) {
						$curTitle['request'] = "null";
					}
				}

			}
			if ($scount > 1){
				//Get additional information from resources table
				if ($curTitle['shortId'] && strlen($curTitle['shortId']) > 0){
					$resource = new Resource();
					$resource->shortId = $curTitle['shortId'];
					if ($resource->find(true)){
						$curTitle = array_merge($curTitle, get_object_vars($resource));
						$curTitle['recordId'] = $resource->record_id;
						$curTitle['id'] = $resource->record_id;
					}else{
						//echo("Warning did not find resource for {$historyEntry['shortId']}");
					}
				}
				$sortTitle = isset($curTitle['title_sort']) ? $curTitle['title_sort'] : $curTitle['title'];
				$sortKey = $sortTitle;
				if ($sortOption == 'title'){
					$sortKey = $sortTitle;
				}elseif ($sortOption == 'author'){
					$sortKey = (isset($curTitle['author']) ? $curTitle['author'] : "Unknown") . '-' . $sortTitle;
				}elseif ($sortOption == 'dueDate'){
					if (isset($curTitle['duedate'])){
						if (preg_match('/.*?(\\d{1,2})[-\/](\\d{1,2})[-\/](\\d{2,4}).*/', $curTitle['duedate'], $matches)) {
							$sortKey = $matches[3] . '-' . $matches[1] . '-' . $matches[2] . '-' . $sortTitle;
						} else {
							$sortKey = $curTitle['duedate'] . '-' . $sortTitle;
						}
					}
				}elseif ($sortOption == 'format'){
					$sortKey = (isset($curTitle['format']) ? $curTitle['format'] : "Unknown") . '-' . $sortTitle;
				}elseif ($sortOption == 'renewed'){
					$sortKey = (isset($curTitle['renewCount']) ? $curTitle['renewCount'] : 0) . '-' . $sortTitle;
				}elseif ($sortOption == 'holdQueueLength'){
					$sortKey = (isset($curTitle['holdQueueLength']) ? $curTitle['holdQueueLength'] : 0) . '-' . $sortTitle;
				}
				$sortKey .= "_$scount";
				$checkedOutTitles[$sortKey] = $curTitle;
				
			}
			
			$scount++;
		}
		ksort($checkedOutTitles);
		
		$numTransactions = count($checkedOutTitles);
		//Process pagination
		if ($recordsPerPage != -1){
			$startRecord = ($page - 1) * $recordsPerPage;
			if ($startRecord > $numTransactions){
				$page = 0;
				$startRecord = 0;
			}
			$checkedOutTitles = array_slice($checkedOutTitles, $startRecord, $recordsPerPage);
		}
		
		return array(
			'transactions' => $checkedOutTitles,
			'numTransactions' => $numTransactions
		);
		
	}

	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		global $timer;
		$id2= $patron['id'];
		$patronDump = $this->_getPatronDump($id2);

		//Load the information from millenium using CURL
		$pageContents = $this->_fetchPatronInfoPage($patronDump, 'readinghistory');

		$sresult = preg_replace("/<[^<]+?><[^<]+?>Reading History.\(.\d*.\)<[^<]+?>\W<[^<]+?>/", "", $pageContents);

		$s = substr($sresult, stripos($sresult, 'patFunc'));
		$s = substr($s,strpos($s,">")+1);
		$s = substr($s,0,stripos($s,"</table"));

		$s = preg_replace ("/<br \/>/","", $s);

		$srows = preg_split("/<tr([^>]*)>/",$s);

		$scount = 0;
		$skeys = array_pad(array(),10,"");
		$readingHistoryTitles = array();
		foreach ($srows as $srow) {
			$scols = preg_split("/<t(h|d)([^>]*)>/",$srow);
			$historyEntry = array();
			for ($i=0; $i < sizeof($scols); $i++) {
				$scols[$i] = str_replace("&nbsp;"," ",$scols[$i]);
				$scols[$i] = preg_replace ("/<br+?>/"," ", $scols[$i]);
				$scols[$i] = html_entity_decode(trim(substr($scols[$i],0,stripos($scols[$i],"</t"))));
				//print_r($scols[$i]);
				if ($scount == 1) {
					$skeys[$i] = $scols[$i];
				} else if ($scount > 1) {
					if (stripos($skeys[$i],"Mark") > -1) {
						$historyEntry['deletable'] = "BOX";
					}

					if (stripos($skeys[$i],"Title") > -1) {
						if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $scols[$i], $matches)) {
							$shortId = $matches[1];
							$bibid = '.' . $matches[1];
							$title = $matches[2];
						}

						$historyEntry['id'] = $bibid;
						$historyEntry['shortId'] = $shortId;
						$historyEntry['title'] = $title;
					}

					if (stripos($skeys[$i],"Author") > -1) {
						$historyEntry['author'] = strip_tags($scols[$i]);
					}

					if (stripos($skeys[$i],"Checked Out") > -1) {
						$historyEntry['checkout'] = strip_tags($scols[$i]);
					}
					if (stripos($skeys[$i],"Details") > -1) {
						$historyEntry['details'] = strip_tags($scols[$i]);
					}

					$historyEntry['borrower_num'] = $patron['id'];
				} //Done processing column 
			} //Done processing row

			if ($scount > 1){
				//Get additional information from resources table
				if ($historyEntry['shortId'] && strlen($historyEntry['shortId']) > 0){
					$resource = new Resource();
					$resource->shortId = $historyEntry['shortId'];
					if ($resource->find(true)){
						$historyEntry = array_merge($historyEntry, get_object_vars($resource));
						$historyEntry['recordId'] = $resource->record_id;
					}else{
						//echo("Warning did not find resource for {$historyEntry['shortId']}");
					}
				}
				$titleKey = '';
				if ($sortOption == "title"){
					$titleKey = $historyEntry['title_sort'];
				}elseif ($sortOption == "author"){
					$titleKey = $historyEntry['author'] . "_" . $historyEntry['title_sort'];
				}elseif ($sortOption == "checkedOut" || $sortOption == "returned"){
					$checkoutTime = DateTime::createFromFormat('m-d-Y', $historyEntry['checkout']) ;
					$titleKey = $checkoutTime->getTimestamp() . "_" . $historyEntry['title_sort'];
				}elseif ($sortOption == "format"){
					$titleKey = $historyEntry['format'] . "_" . $historyEntry['title_sort'];
				}else{
					$titleKey = $historyEntry['title_sort'];
				}
				$titleKey .= '_' . $scount;
				$readingHistoryTitles[$titleKey] = $historyEntry;
			}
			$scount++;
		}//processed all rows in the table
		
		if ($sortOption == "checkedOut" || $sortOption == "returned"){
			krsort($readingHistoryTitles);
		}else{
			ksort($readingHistoryTitles);
		}
		$numTitles = count($readingHistoryTitles);
		//process pagination
		if ($recordsPerPage != -1){
			$startRecord = ($page - 1) * $recordsPerPage;
			$readingHistoryTitles = array_slice($readingHistoryTitles, $startRecord, $recordsPerPage);
		}
		
		//The history is active if there is an opt out link.
		$historyActive = (strpos($pageContents, 'OptOut') > 0);
		$timer->logTime("Loaded Reading history for patron");
		return array('historyActive'=>$historyActive, 'titles'=>$readingHistoryTitles, 'numTitles'=> $numTitles);
	}

	/**
	 * Do an update or edit of reading history information.  Current actions are:
	 * deleteMarked
	 * deleteAll
	 * exportList
	 * optOut
	 *
	 * @param   array   $patron         The patron array
	 * @param   string  $action         The action to perform
	 * @param   array   $selectedTitles The titles to do the action on if applicable
	 */
	function doReadingHistoryAction($patron, $action, $selectedTitles){
		global $configArray;
		$id2= $patron['id'];
		$patronDump = $this->_getPatronDump($id2);
		//Load the reading history page
		$scope = $this->getDefaultScope();
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/readinghistory";

		$cookie = tempnam ("/tmp", "CURLCOOKIE");
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data['name'] = $patronDump['PATRN_NAME'];
		$post_data['code'] = $patronDump['P_BARCODE'];
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		if ($action == 'deleteMarked'){
			//Load patron page readinghistory/rsh with selected titles marked
			if (!isset($selectedTitles) || count($selectedTitles) == 0){
				return;
			}
			$titles = array();
			foreach ($selectedTitles as $titleId){
				$titles[] = $titleId . '=1';
			}
			$title_string = implode ('&', $titles);
			//Issue a get request to delete the item from the reading history.
			//Note: Millennium really does issue a malformed url, and it is required
			//to make the history delete properly.
			$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/readinghistory/rsh&" . $title_string;
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			$sresult = curl_exec($curl_connection);
		}elseif ($action == 'deleteAll'){
			//load patron page readinghistory/rah
			$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/readinghistory/rah";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			$sresult = curl_exec($curl_connection);
		}elseif ($action == 'exportList'){
			//Leave this unimplemented for now.
		}elseif ($action == 'optOut'){
			//load patron page readinghistory/OptOut
			$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/readinghistory/OptOut";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			$sresult = curl_exec($curl_connection);
		}elseif ($action == 'optIn'){
			//load patron page readinghistory/OptIn
			$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/readinghistory/OptIn";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			$sresult = curl_exec($curl_connection);
		}
		curl_close($curl_connection);
		unlink($cookieJar);
	}

	public function getMyHolds($patron, $page = 1, $recordsPerPage = -1, $sortOption = 'title')
	{
		global $timer;
		global $configArray;
		$id2= $patron['id'];
		$patronDump = $this->_getPatronDump($id2);

		//Load the information from millenium using CURL
		$sresult = $this->_fetchPatronInfoPage($patronDump, 'holds');
		$timer->logTime("Got holds page from Millennium");

		$holds = $this->parseHoldsPage($sresult);
		$timer->logTime("Parsed Holds page");
		
		//Get a list of all record id so we can load supplemental information 
		$recordIds = array();
		foreach($holds as $section => $holdSections){
			foreach($holdSections as $hold){
				$recordIds[] = "'" . $hold['shortId'] . "'";
			}
		}
		//Get records from resource table
		$resourceInfo = new Resource();
		if (count($recordIds) > 0){
			$recordIdString = implode(",", $recordIds);
			mysql_select_db($configArray['Database']['database_vufind_dbname']);
			$resourceSql = "SELECT * FROM resource where source = 'VuFind' AND shortId in ({$recordIdString})";
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
							//Load title, author, and format information about the title
							$hold['title'] = isset($resourceInfo->title) ? $resourceInfo->title : 'Unknown';
							$hold['sortTitle'] = isset($resourceInfo->title_sort) ? $resourceInfo->title_sort : 'unknown';
							$hold['author'] = isset($resourceInfo->author) ? $resourceInfo->author : null;
							$hold['format'] = isset($resourceInfo->format) ?$resourceInfo->format : null;
							$hold['isbn'] = isset($resourceInfo->isbn) ? $resourceInfo->isbn : '';
							$hold['upc'] = isset($resourceInfo->upc) ? $resourceInfo->upc : '';
							$hold['format_category'] = isset($resourceInfo->format_category) ? $resourceInfo->format_category : '';
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
		//Sort the hold sections so vailable holds are first. 
		ksort($holds);

		$this->holds[$patron['id']] = $holds;
		$timer->logTime("Processed hold pagination and sorting");
		return array(
			'holds' => $holds,
			'numUnavailableHolds' => $numUnavailableHolds,
		);
	}

	public function parseHoldsPage($sresult){
		global $timer;
		$logger = new Logger();
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);

		$sresult = preg_replace("/<[^<]+?>\W<[^<]+?>\W\d* HOLD.?\W<[^<]+?>\W<[^<]+?>/", "", $sresult);
		//$logger->log('Hold information = ' . $sresult, PEAR_LOG_INFO);

		$s = substr($sresult, stripos($sresult, 'patFunc'));
			
		$s = substr($s,strpos($s,">")+1);

		$s = substr($s,0,stripos($s,"</table"));

		$s = preg_replace ("/<br \/>/","", $s);
		// echo $s;
		//echo $s . "<br />";

		$srows = preg_split("/<tr([^>]*)>/",$s);
		//echo "<pre>";
		//print_r($srows);
		//echo "</pre>";
		$scount = 0;
		$skeys = array_pad(array(),10,"");
		foreach ($srows as $srow) {
			$scols = preg_split("/<t(h|d)([^>]*)>/",$srow);
			//  echo "<pre>";
			//  print_r($scols);
			//  echo "</pre>";
			$curHold= array();
			$curHold['create'] = null;
			$curHold['reqnum'] = null;
			
			//Holds page occassionally has a header with number of items checked out.
			for ($i=0; $i < sizeof($scols); $i++) {
				$scols[$i] = str_replace("&nbsp;"," ",$scols[$i]);
				$scols[$i] = preg_replace ("/<br+?>/"," ", $scols[$i]);
				$scols[$i] = html_entity_decode(trim(substr($scols[$i],0,stripos($scols[$i],"</t"))));
				//print_r($scols[$i]);
				if ($scount <= 1) {
					$skeys[$i] = $scols[$i];
				} else if ($scount > 1) {
					if ($skeys[$i] == "CANCEL") { //Only check Cancel key, not Cancel if not filled by
						//Extract the id from the checkbox
						$matches = array();
						$numMatches = preg_match_all('/.*?cancel(.*?)x(\\d\\d).*/s', $scols[$i], $matches);
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

					if (stripos($skeys[$i],"TITLE") > -1) {
						if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $scols[$i], $matches)) {
							$shortId = $matches[1];
							$bibid = '.' . $matches[1]; //Technically, this isn't corrcect since the check digit is missing
							$title = $matches[2];
						}else{
							$bibid = '';
							$shortId = '';
							$title = trim($scols[$i]);
						}

						$curHold['id'] = $bibid;
						$curHold['shortId'] = $shortId;
						$curHold['title'] = $title;
					}
					if (stripos($skeys[$i],"Ratings") > -1) {
						$curHold['request'] = "STARS";
					}

					if (stripos($skeys[$i],"PICKUP LOCATION") > -1) {

						//Extract the current location for the hold if possible
						$matches = array();
						if (preg_match('/<select\\s+name=loc(.*?)x(\\d\\d).*?<option\\s+value="([a-z]{1,5})[+ ]*"\\s+selected="selected">.*/s', $scols[$i], $matches)){
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
							$curHold['locationSelect'] = $scols[$i];
						}else{
							$curHold['location'] = $scols[$i];
							//Trim the carrier code if any
							if (preg_match('/.*\s[\w\d]{4}/', $curHold['location'])){
								$curHold['location'] = substr($curHold['location'], 0, strlen($curHold['location']) - 5);
							}
							$curHold['currentPickupName'] = $curHold['location'];
							$curHold['locationUpdateable'] = false;
						}
					}

					if (stripos($skeys[$i],"STATUS") > -1) {
						$status = trim(strip_tags($scols[$i]));
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
								
							}elseif (preg_match('/READY FOR PICKUP/i', $status, $matches)){
								$curHold['status'] = 'Ready';
							}else{
								$curHold['status'] = $status;
							}
						}else{
							$curHold['status'] = "Pending $status";
						}
						$matches = array();
						$curHold['renewError'] = false;
						if (preg_match('/.*DUE\\s(\\d{2}-\\d{2}-\\d{2}).*(?:<font color="red">\\s*(.*)<\/font>).*/s', $scols[$i], $matches)){
							//Renew error
							$curHold['renewError'] = $matches[2];
							$curHold['statusMessage'] = $matches[2];
						}else{
							if (preg_match('/.*DUE\\s(\\d{2}-\\d{2}-\\d{2})\\s(.*)?/s', $scols[$i], $matches)){
								$curHold['statusMessage'] = $matches[2];
							}
						}
						$logger->log('Status for item ' . $curHold['id'] . '=' . $scols[$i], PEAR_LOG_INFO);
					}
					if (stripos($skeys[$i],"CANCEL IF NOT FILLED BY") > -1) {
						//$curHold['expire'] = strip_tags($scols[$i]);
					}
					if (stripos($skeys[$i],"FREEZE") > -1) {
						$matches = array();
						$curHold['frozen'] = false;
						if (preg_match('/<input.*name="freeze(.*?)"\\s*(\\w*)\\s*\/>/', $scols[$i], $matches)){
							$curHold['freezeable'] = true;
							if (strlen($matches[2]) > 0){
								$curHold['frozen'] = true;
								$curHold['status'] = 'Frozen';
							}
						}elseif (preg_match('/This hold can\s?not be frozen/i', $scols[$i], $matches)){
							//If we detect an error Freezing the hold, save it so we can report the error to the user later.
							$shortId = str_replace('.b', 'b', $curHold['id']);
							$_SESSION['freezeResult'][$shortId]['message'] = $scols[$i];
							$_SESSION['freezeResult'][$shortId]['result'] = false;
						}else{
							$curHold['freezeable'] = false;
						}
					}
				}
			} //End of columns
			
			if ($scount > 1) {
				if (!isset($curHold['status']) || strcasecmp($curHold['status'], "ready") != 0){
					$holds['unavailable'][] = $curHold;
				}else{
					$holds['available'][] = $curHold;
				}
			}

			$scount++;

		}//End of the row
		
		return $holds;
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds as well as placing recalls.
	 *
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $patronId   The id of the patron
	 * @param   string  $comment    Any comment regarding the hold or recall
	 * @param   string  $type       Whether to place a hold or recall
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occures, return a PEAR_Error
	 * @access  public
	 */
	public function placeHold($recordId, $patronId, $comment, $type){
		return $this->placeItemHold($recordId, null, $patronId, $comment, $type);
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
	 *                              If an error occures, return a PEAR_Error
	 * @access  public
	 */
	public function placeItemHold($recordId, $itemId, $patronId, $comment, $type){
		$id2= $patronId;
		$patronDump = $this->_getPatronDump($id2);

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
		global $configArray;
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$this->db->debug = true;
		}

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($bib1))) {
			$title = null;
		}else{
			if (isset($record['title_full'][0])){
				$title = $record['title_full'][0];
			}else{
				$title = $record['title'];
			}
		}

		//Cancel a hold
		if ($type == 'cancel' || $type == 'recall' || $type == 'update') {
			$result = $this->updateHold($recordId, $patronId, $type, $title);
			$result['title'] = $title;
			$result['bid'] = $bib1;
			return $result;

		} else {
			//User is logged in before they get here, always use the info from patrondump
			$username = $patronDump['PATRN_NAME'];

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

			list($Month, $Day, $Year)=explode("/", $date);

			//------------BEGIN CURL-----------------------------------------------------------------
			$Fullname = $patronDump['PATRN_NAME'];
			$nameParts = explode(', ',$Fullname);
			$lastname = $nameParts[0];
			if (isset($nameParts[1])){
				$secondname = $nameParts[1];
				$secondnameParts = explode(' ', $secondname);
				$firstname = $secondnameParts[0];
				if (isset($secondnameParts[1])){
					$middlename = $secondnameParts[1];
				}
			}

			list($first, $last)=explode(' ', $username);

			$header=array();
			$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
			$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
			$header[] = "Cache-Control: max-age=0";
			$header[] = "Connection: keep-alive";
			$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
			$header[] = "Accept-Language: en-us,en;q=0.5";
			$id=$patronDump['RECORD_#'];
			$cookie = tempnam ("/tmp", "CURLCOOKIE");
			$curl_url = $configArray['Catalog']['url'] . "/search/." . $bib . "/." . $bib ."/1,1,1,B/request~" . $bib;
			//echo "$curl_url";
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


			$post_data['name'] = $firstname . " " . $lastname;
			$post_data['code'] = $patronDump['P_BARCODE'];
			$post_data['needby_Month']= $Month;
			$post_data['needby_Day']= $Day;
			$post_data['needby_Year']=$Year;
			$post_data['submit.x']="35";
			$post_data['submit.y']="21";
			$post_data['submit']="submit";
			$post_data['locx00']= str_pad($campus, 5-strlen($campus), '+');
			if (!is_null($itemId) && $itemId != -1){
				$post_data['radio']=$itemId;
			}
			$post_data['x']="48";
			$post_data['y']="15";

			foreach ($post_data as $key => $value) {
				$post_items[] = $key . '=' . $value;
			}
			$post_string = implode ('&', $post_items);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			$sresult = curl_exec($curl_connection);

			$logger = new Logger();
			$logger->log("Placing hold $curl_url?$post_string", PEAR_LOG_INFO);

			$sresult = preg_replace("/<!--([^(-->)]*)-->/","",$sresult);

			curl_close($curl_connection);

			//Parse the response to get the status message
			//Get rid of header and footer information and just get the main content
			$matches = array();

			$numMatches = preg_match('/<td.*?class="pageMainArea">(.*)?<\/td>/s', $sresult, $matches);
			$hold_result = array(
                        'result' => true,
                        'title'  => $title,
                        'bid' => $bib1);
			if ($numMatches > 0){
				$logger->log('Place Hold Body Text\n' . $matches[1], PEAR_LOG_INFO);
				$cleanResponse = preg_replace("^\n|\r|&nbsp;^", "", $matches[1]);
				$cleanResponse = preg_replace("^<br\s*/>^", "\n", $cleanResponse);
				$cleanResponse = trim(strip_tags($cleanResponse));
					
				list($book,$reason)= explode("\n",$cleanResponse);
				if (preg_match('/success/', $cleanResponse)){
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
					$hold_result['message'] = 'Did not receive a response from the circulation system.  Please try again in a few minutes.';
				}else{
					//Got an error message back.
					$hold_result['result'] = false;
					$hold_result['message'] = $reason;
				}
			}else{
				if (preg_match('/Choose one item from the list below/', $sresult)){
					//Get information about the items that are available for holds
					preg_match_all('/<tr\\s+class="bibItemsEntry">.*?<input type="radio" name="radio" value="(.*?)".*?>.*?<td.*?>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>.*?<\/tr>/s', $sresult, $itemInfo, PREG_PATTERN_ORDER);
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

				$logger = new Logger();
				$logger->log('Place Hold Full HTML\n' . $sresult, PEAR_LOG_INFO);
			}
			return $hold_result;

		}

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
		return $this->updateHoldDetailed($requestId, $patronId, $type, $title, $xnum, $cancelId, $locationId, $freezeValue);
	}

	/**
	 * Update a hold that was previously placed in the system.
	 * Can cancel the hold or update pickup locations.
	 */
	public function updateHoldDetailed($requestId, $patronId, $type, $title, $xnum, $cancelId, $locationId, $freezeValue='off')
	{
		$logger = new Logger();
		global $configArray;

		$id2= $patronId;
		$patronDump = $this->_getPatronDump($id2);

		//Recall Holds
		$bib = $cancelId;

		if (!isset($xnum) ){
			$waitingHolds = isset($_REQUEST['waitingholdselected']) ? $_REQUEST['waitingholdselected'] : array();
			$availableHolds = isset($_REQUEST['availableholdselected']) ? $_REQUEST['availableholdselected'] : array();
			$xnum = array_merge($waitingHolds, $availableHolds);
		}
		$location = new Location();
		if (isset($locationId) && is_numeric($locationId)){
			$location->whereAdd("locationId = '$locationId'");
			$location->find();
			if ($location->N == 1) {
				$location->fetch();
				$paddedLocation = str_pad(trim($location->code), 5, '+');
			}
		}else{
			$paddedLocation = null;
		}
		$id=$patronDump['RECORD_#'];

		$cancelValue = ($type == 'cancel' || $type == 'recall') ? 'on' : 'off';
		
		if (is_array($xnum)){
			$extraGetInfo = array(
                'updateholdssome' => 'YES',
                'currentsortorder' => 'current_pickup',
			);
			foreach ($xnum as $tmpXnumInfo){
				list($tmpBib, $tmpXnum) = split("~", $tmpXnumInfo);
				$extraGetInfo['cancel' . $tmpBib . 'x' . $tmpXnum] = $cancelValue;
				if ($paddedLocation){
					$extraGetInfo['loc' . $tmpBib . 'x' . $tmpXnum] = $paddedLocation;
				}
				if (strlen($freezeValue) > 0){
					$extraGetInfo['freeze' . $tmpBib] = $freezeValue;
				}
			}
		}else{
			$extraGetInfo = array(
                'updateholdssome' => 'YES',
                'cancel' . $bib . $xnum => $cancelValue,
                'currentsortorder' => 'current_pickup',
			);
			if ($paddedLocation){
				$extraGetInfo['loc' . $bib . $xnum] = $paddedLocation;
			}
			if (strlen($freezeValue) > 0){
				$extraGetInfo['freeze' . $bib] = $freezeValue;
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
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, false);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data['name'] = $patronDump['PATRN_NAME'];
		$post_data['code'] = $patronDump['P_BARCODE'];
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		$scope = $this->getDefaultScope();
		//go to the holds page and get the number of holds on the account
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/holds";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$holds = $this->parseHoldsPage($sresult);
		$numHoldsStart = count($holds);

		//Issue a get request with the information about what to do with the holds
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/holds?" . $holdUpdateParams;
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$holds = $this->parseHoldsPage($sresult);
		//At this stage, we get messages if there were any errors freezing holds.

		//Go back to the hold page to check make sure our hold was cancelled
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/holds";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$holds = $this->parseHoldsPage($sresult);
		$numHoldsEnd = count($holds);

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
		global $memcache;
		$memcache->delete("patron_dump_$patronId");
		//Clear holds for the patron
		unset($this->holds[$patronId]);

		if ($type == 'cancel' || $type == 'recall'){
			if ($success){
				return array(
                    'title' => $title,
                    'result' => true,
                    'message' => 'Your hold was cancelled successfully.');
			}else{
				return array(
                    'title' => $title,
                    'result' => false,
                    'message' => 'Your hold could not be cancelled.  Please try again later or see your librarian.');
			}
		}else{
			return array(
                    'title' => $title,
                    'result' => true,
                    'message' => 'Your hold was updated successfully.');
		}
	}

	public function renewItem($patronId, $itemId, $itemIndex){
		$logger = new Logger();
		global $configArray;

		//Setup the call to Millennium
		$id2= $patronId;
		$patronDump = $this->_getPatronDump($id2);

		$extraGetInfo = array(
            'currentsortorder' => 'current_checkout',
            'renewsome' => 'YES',
            'renew' . $itemIndex => $itemId,
		);

		$get_items = array();
		foreach ($extraGetInfo as $key => $value) {
			$get_items[] = $key . '=' . urlencode($value);
		}
		$renewItemParams = implode ('&', $get_items);

		//Login to the patron's account
		$cookieJar = tempnam ("/tmp", "CURLCOOKIE");
		$success = false;

		$curl_url = $configArray['Catalog']['url'] . "/patroninfo";
		$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);

		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, false);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data['name'] = $patronDump['PATRN_NAME'];
		$post_data['code'] = $patronDump['P_BARCODE'];
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		//Go to the items page
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/items";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);

		//Post renewal information
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/items";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $renewItemParams);
		$sresult = curl_exec($curl_connection);
		if (preg_match('/<div id="renewfailmsg" style="display:none"  class="errormessage">(.*?)<\/div>.*?<font color="red">\\s*(.*?)<\/font>/si', $sresult, $matches)) {
			$success = false;
			$message = 'Unable to renew this item, ' . strtolower($matches[2]) . '.';
		}else if (preg_match('/<h2>\\s*You cannot renew items because:\\s*<\/h2><ul><li>(.*?)<\/ul>/si', $sresult, $matches)) {
			$success = false;
			$message = 'Unable to renew this item, ' . strtolower($matches[1]) . '.';
		}else{
			$success = true;
			$message = 'Your item was successfully renewed';
		}
		curl_close($curl_connection);
		unlink($cookieJar);

		return array(
                    'itemId' => $itemId,
                    'result'  => $success,
                    'message' => $message);
	}

	public function updatePatronInfo($patronId){
		global $user;
		global $configArray;

		//Setup the call to Millennium
		$id2= $patronId;
		$patronDump = $this->_getPatronDump($id2);

		//Validate that the input data is correct
		if (preg_match('/^\d{1,3}$/', $_POST['myLocation1']) == 0){
			PEAR::raiseError('The 1st location had an incorrect format.');
		}
		if (preg_match('/^\d{1,3}$/', $_POST['myLocation2']) == 0){
			PEAR::raiseError('The 2nd location had an incorrect format.');
		}
		if (isset($_REQUEST['bypassAutoLogout'])){
			if ($_REQUEST['bypassAutoLogout'] == 'yes'){
				$user->bypassAutoLogout = 1;
			}else{
				$user->bypassAutoLogout = 0;
			}
		}
		//Make sure the selected location codes are in the database.
		$location = new Location();
		$location->whereAdd("locationId = '{$_POST['myLocation1']}'");
		$location->find();
		if ($location->N != 1) {
			PEAR::raiseError('The 1st location couuld not be found in the database.');
		}
		$location->whereAdd();
		$location->whereAdd("locationId = '{$_POST['myLocation2']}'");
		$location->find();
		if ($location->N != 1) {
			PEAR::raiseError('The 2nd location couuld not be found in the database.');
		}
		$user->myLocation1Id = $_POST['myLocation1'];
		$user->myLocation2Id = $_POST['myLocation2'];
		$user->update();
		//Update the serialized instance stored in the session
		$_SESSION['userinfo'] = serialize($user);

		//Update profile information
		$extraPostInfo = array(
            'addr1a' => $_REQUEST['address1'],
            'addr1b' => $_REQUEST['city'] . ', ' . $_REQUEST['state'] . ' ' . $_REQUEST['zip'],
            'addr1c' => '',
            'addr1d' => '',
            'tele1'  => $_REQUEST['phone'],
            'email'  => $_REQUEST['email'],
		);

		//Login to the patron's account
		$cookieJar = tempnam ("/tmp", "CURLCOOKIE");
		$success = false;

		$curl_url = $configArray['Catalog']['url'] . "/patroninfo";

		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, false);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data['name'] = $patronDump['PATRN_NAME'];
		$post_data['code'] = $patronDump['P_BARCODE'];
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		//Issue a post request to update the patron information
		$post_items = array();
		foreach ($extraPostInfo as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$patronUpdateParams = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $patronUpdateParams);
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/modpinfo";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		$sresult = curl_exec($curl_connection);

		curl_close($curl_connection);
		unlink($cookieJar);
		
		//Make sure to clear any cached data
		global $memcache;
		$memcache->delete("patron_dump_$patronId");

		//Should get Patron Information Updated on success
		if (preg_match('/Patron information updated/', $sresult)){
			return true;
		}else{
			return false;
		}

	}

	var $ptype;
	/**
	 * returns the patron type identifier if a patron is logged in or if the patron
	 * is not logged in, it will return the default PType for the library domain.
	 * If a domain is not in use it will return -1.
	 *
	 * @return int
	 */
	public function getPType(){
		if (!(isset($ptype)) || $this->ptype == null){
			global $user;
			global $library;
			global $locationSingleton;
			$location = $locationSingleton->getActiveLocation();
			if (isset($user) && $user != false){
				$patronDump = $this->_getPatronDump($user->password);
				$this->ptype = $patronDump['P_TYPE'];
			}else if (isset($location) && $location->defaultPType != -1){
				$this->ptype = $location->defaultPType;
			}else if (isset($library)){
				$this->ptype = $library->defaultPType;
			}else{
				$this->ptype = -1;
			}
		}
		return $this->ptype;
	}

	/**
	 * Checks millenium to determine if there are issue summaries available.
	 * If there are issue summaries available, it will return them in an array.
	 * With holdings below them.
	 *
	 * If there are no issue summaries, null will be returned from the summary.
	 *
	 * @param string $id - The Id of the bib record to load summaries for.
	 * @return mixed - array or null
	 */
	public function getIssueSummaries($id, $millenniumInfo){
		//Issue summaries are loaded from the main record page.
		global $library;
		global $user;
		global $configArray;

		//Load circulation status information so we can use it later on to
		//determine what is holdable and what is not.
		self::loadCircStatusInfo();
		self::loadNonHoldableLocations();
		self::loadPtypeRestrictedLocations();

		if (preg_match('/class\\s*=\\s*\\"bibHoldings\\"/s', $millenniumInfo->framesetInfo)){
			//There are issue summaries available
			//Extract the table with the holdings
			$issueSummaries = array();
			$matches = array();
			if (preg_match('/<table\\s.*?class=\\"bibHoldings\\">(.*?)<\/table>/s', $millenniumInfo->framesetInfo, $matches)) {
				$issueSummaryTable = trim($matches[1]);
				//Each holdingSummary begins with a holdingsDivider statement
				$summaryMatches = explode('<tr><td colspan="2"><hr  class="holdingsDivider" /></td></tr>', $issueSummaryTable);
				if (count($summaryMatches) > 1){
					//Process each match independently
					foreach ($summaryMatches as $summaryData){
						$summaryData = trim($summaryData);
						if (strlen($summaryData) > 0){
							//Get each line within the summary
							$issueSummary = array();
							$issueSummary['type'] = 'issueSummary';
							$summaryLines = array();
							preg_match_all('/<tr\\s*>(.*?)<\/tr>/s', $summaryData, $summaryLines, PREG_SET_ORDER);
							for ($matchi = 0; $matchi < count($summaryLines); $matchi++) {
								$summaryLine = trim(str_replace('&nbsp;', ' ', $summaryLines[$matchi][1]));
								$summaryCols = array();
								if (preg_match('/<td.*?>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>/s', $summaryLine, $summaryCols)) {
									$label = trim($summaryCols[1]);
									$value = trim(strip_tags($summaryCols[2]));
									//Check to see if this has a link to a check-in grid.
									if (preg_match('/.*?<a href="(.*?)">.*/s', $label, $linkData)) {
										//Parse the check-in id
										$checkInLink = $linkData[1];
										if (preg_match('/\/search~S\\d+\\?\/.*?\/.*?\/.*?\/(.*?)&.*/', $checkInLink, $checkInGridInfo)) {
											$issueSummary['checkInGridId'] = $checkInGridInfo[1];
										}
										$issueSummary['checkInGridLink'] = 'http://www.millenium.marmot.org' . $checkInLink;
									}
									//Convert to camel case
									$label = (preg_replace('/[^\\w]/', '', strip_tags($label)));
									$label = strtolower(substr($label, 0, 1)) . substr($label, 1);
									if ($label == 'location'){
										//Try to trim the courier code if any
										if (preg_match('/(.*?)\\sC\\d{3}\\w{0,2}$/', $value, $locationParts)){
											$value = $locationParts[1];
										}
									}
									$issueSummary[$label] = $value;
								}
							}
							$issueSummaries[$issueSummary['location'] . count($issueSummaries)] = $issueSummary;
						}
					}
				}
			}
			return $issueSummaries;
		}else{
			return null;
		}
	}

	function getCheckInGrid($id, $checkInGridId){
		//Issue summaries are loaded from the main record page.
		global $library;
		global $user;
		global $configArray;

		// Strip ID
		$id_ = substr(str_replace('.b', '', $id), 0, -1);

		// Load Record Page
		if (substr($configArray['Catalog']['url'], -1) == '/') {
			$host = substr($configArray['Catalog']['url'], 0, -1);
		} else {
			$host = $configArray['Catalog']['url'];
		}

		$branchScope = $this->getMillenniumScope();
		$req =  $host . "/search~S{$branchScope}/.b" . $id_ . "/.b" . $id_ . "/1,1,1,B/$checkInGridId&FF=1,0,";
		$result = file_get_contents($req);

		//Extract the actual table
		$checkInData = array();
		if (preg_match('/<table  class="checkinCardTable">(.*?)<\/table>/s', $result, $matches)) {
			$checkInTable = trim($matches[1]);

			//Extract each item from the grid.
			preg_match_all('/.*?<td valign="top" class="(.*?)">(.*?)<\/td>/s', $checkInTable, $checkInCellMatch, PREG_SET_ORDER);
			for ($matchi = 0; $matchi < count($checkInCellMatch); $matchi++) {
				$checkInCell = array();
				$checkInCell['class'] = $checkInCellMatch[$matchi][1];
				$cellData = trim($checkInCellMatch[$matchi][2]);
				//Load issue date, status, date received, issue number, copies received
				if (preg_match('/(.*?)<br\\s*\/?>.*?<span class="(?:.*?)">(.*?)<\/span>.*?on (\\d{1,2}-\\d{1,2}-\\d{1,2})<br\\s*\/?>(.*?)(?:<!-- copies --> \\((\\d+) copy\\))?<br\\s*\/?>/s', $cellData, $matches)) {
					$checkInCell['issueDate'] = trim($matches[1]);
					$checkInCell['status'] = trim($matches[2]);
					$checkInCell['statusDate'] = trim($matches[3]);
					$checkInCell['issueNumber'] = trim($matches[4]);
					if (isset($matches[5])){
						$checkInCell['copies'] = trim($matches[5]);
					}
				}
				$checkInData[] = $checkInCell;
			}
		}
		return $checkInData;
	}

	function _getItemDetails($id, $holdings){
		$logger = new Logger();
		global $configArray;
		$scope = $this->getDefaultScope();

		$shortId = substr(str_replace('.b', 'b', $id), 0, -1);

		//Login to the site using vufind login.
		$cookie = tempnam ("/tmp", "CURLCOOKIE");
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo";
		$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);
		//echo "$curl_url";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		$post_data['name'] = $configArray['Catalog']['ils_admin_user'];
		$post_data['code'] = $configArray['Catalog']['ils_admin_pwd'];
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		foreach ($holdings as $itemNumber => $holding){
			//Get the staff page for the record
			//$curl_url = "https://www.millennium.marmot.org/search~S93?/Ypig&searchscope=93&SORT=D/Ypig&searchscope=93&SORT=D&SUBKEY=pig/1,383,383,B/staffi1~$shortId&FF=Ypig&2,2,";
			$curl_url = $configArray['Catalog']['url'] . "/search~S{$scope}?/Ypig&searchscope={$scope}&SORT=D/Ypig&searchscope={$scope}&SORT=D&SUBKEY=pig/1,383,383,B/staffi$itemNumber~$shortId&FF=Ypig&2,2,";
			$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);
			//echo "$curl_url";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
			curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie );
			curl_setopt($curl_connection, CURLOPT_COOKIESESSION, false);
			$sresult = curl_exec($curl_connection);

			//Extract Item infromation
			if (preg_match('/<!-- Fixfields -->.*?<table.*?>(.*?)<\/table>.*?<!-- Varfields -->.*?<table.*?>(.*?)<\/table>.*?<!-- Lnkfields -->.*?<table.*?>(.*?)<\/table>/s', $sresult, $matches)) {
				$fixFieldString = $matches[1];
				$varFieldString = $matches[2];
				$linkFieldString = $matches[3];
			}

			//Extract the fixFields into an array of name value pairs
			preg_match_all('/<td><font size="-1"><em>(.*?)<\/em><\/font>&nbsp;<strong>(.*?)<\/strong><\/td>/s', $fixFieldString, $fieldData, PREG_PATTERN_ORDER);
			$fixFields = array();
			for ($i = 0; $i < count($fieldData[0]); $i++) {
				$fixFields[$fieldData[1][$i]] = $fieldData[2][$i];
			}

			//Extract the fixFields into an array of name value pairs
			preg_match_all('/<td.*?><font size="-1"><em>(.*?)<\/em><\/font><\/td><td width="80%">(.*?)<\/td>/s', $varFieldString, $fieldData, PREG_PATTERN_ORDER);
			$varFields = array();
			for ($i = 0; $i < count($fieldData[0]); $i++) {
				$varFields[$fieldData[1][$i]] = $fieldData[2][$i];
			}

			//Add on the item information
			$holdings[$itemNumber] = array_merge($fixFields, $varFields, $holding);
		}
		curl_close($curl_connection);
	}

	function selfRegister(){
		$logger = new Logger();
		global $configArray;

		$firstName = $_GET['firstName'];
		$lastName = $_GET['lastName'];
		$address = $_GET['address'];
		$city = $_GET['city'];
		$state = $_GET['state'];
		$zip = $_GET['zip'];
		$email = $_GET['email'];

		$cookie = tempnam ("/tmp", "CURLCOOKIE");
		$curl_url = $configArray['Catalog']['url'] . "/selfreg~S" . $this->getMillenniumScope();
		$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);
		//echo "$curl_url";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);

		$post_data['nfirst'] = $firstName;
		$post_data['nlast'] = $lastName;
		$post_data['stre_aaddress'] = $address;
		$post_data['city_aaddress'] = $city;
		$post_data['stat_aaddress'] = $state;
		$post_data['post_aaddress'] = $zip;
		$post_data['zemailaddr'] = $email;
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		curl_close($curl_connection);

		//Parse the library card number from the response
		if (preg_match('/Your barcode is:.*?(\\d+)<\/(b|strong)>/s', $sresult, $matches)) {
			$barcode = $matches[0];
			return array('success' => true, 'barcode' => $barcode);
		} else {
			return array('success' => false, 'barcode' => $barcode);
		}

	}
}