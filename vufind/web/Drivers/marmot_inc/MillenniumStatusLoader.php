<?php
class MillenniumStatusLoader{
	public static function getStatus($id, $driver){
		global $library;
		global $user;
		global $timer;
		global $configArray;
		global $logger;

		//Get information about holdings, order information, and issue information
		$millenniumInfo = $driver->getMillenniumRecordInfo($id);

		//Get the number of holds
		if ($millenniumInfo->framesetInfo){
			if (preg_match('/(\d+) hold(s?) on .*? of \d+ (copies|copy)/', $millenniumInfo->framesetInfo, $matches)){
				$holdQueueLength = $matches[1];
			}else{
				$holdQueueLength = 0;
			}
		}//

		// Load Record Page
		$r = substr($millenniumInfo->holdingsInfo, stripos($millenniumInfo->holdingsInfo, 'bibItems'));
		$r = substr($r,strpos($r,">")+1);
		$r = substr($r,0,stripos($r,"</table"));
		$rows = preg_split("/<tr([^>]*)>/",$r);
		$keys = array_pad(array(),10,"");

		// Load marc record
		$marcRecord = MarcLoader::loadMarcRecordByILSId($id);
		$itemFields = $marcRecord->getFields("989");
		$marcItemData = array();
		$pType = $driver->getPType();
		$scope = $driver->getMillenniumScope();

		//Load item information from marc record
		foreach ($itemFields as $itemField){
			$fullCallNumber = $itemField->getSubfield('s') != null ? ($itemField->getSubfield('s')->getData() . ' '): '';
			$fullCallNumber .= $itemField->getSubfield('a') != null ? $itemField->getSubfield('a')->getData() : '';
			$fullCallNumber .= $itemField->getSubfield('r') != null ? (' ' . $itemField->getSubfield('r')->getData()) : '';
			$itemData['callnumber'] = $fullCallNumber;
			$itemData['location'] = $itemField->getSubfield('d') != null ? $itemField->getSubfield('d')->getData() : ($itemField->getSubfield('p') != null ? $itemField->getSubfield('p')->getData() : '?????');
			$itemData['iType'] = $itemField->getSubfield('j') != null ? $itemField->getSubfield('j')->getData() : '0';
			$itemData['matched'] = false;
			$marcItemData[] = $itemData;
		}

		//Process each row in the callnumber table.
		$ret = MillenniumStatusLoader::parseHoldingRows($driver, $id, $rows, $keys);

		$timer->logTime('processed all holdings rows');

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
		$locationCodes = array();
		while ($location->fetch()){
			if (strlen($location->holdingBranchLabel) > 0 && $location->holdingBranchLabel != '???'){
				if ($library && $library->libraryId == $location->libraryId){
					$cleanLabel =  str_replace('/', '\/', $location->holdingBranchLabel);
					$libraryLocationLabels[] = str_replace('.', '\.', $cleanLabel);
				}

				$locationLabels[$location->holdingBranchLabel] = $location->displayName;
				$locationCodes[$location->code] = $location->holdingBranchLabel;
			}
		}
		if (count($libraryLocationLabels) > 0){
			$libraryLocationLabels = '/^(' . join('|', $libraryLocationLabels) . ').*/i';
		}else{
			$libraryLocationLabels = '';
		}

		//Get the current Ptype for later usage.
		$timer->logTime('setup for additional holdings processing.');

		//Now that we have the holdings, we need to filter and sort them according to scoping rules.
		$i = 0;
		foreach ($ret as $holdingKey => $holding){
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
			if (preg_match('/^(' . $driver->availableStatiRegex . ')$/', $holding['status'])){
				$holding['availability'] = 1;
			}else{
				$holding['availability'] = 0;
			}
			if (preg_match('/^(' . $driver->holdableStatiRegex . ')$/', $holding['status'])){
				$holding['holdable'] = 1;
			}else{
				$holding['holdable'] = 0;
				$holding['nonHoldableReason'] = "This item is not currently available for Patron Holds";
			}

			if (!isset($holding['libraryDisplayName'])){
				$holding['libraryDisplayName'] = $holding['location'];
			}

			//Get the location id for this holding
			$holding['locationCode'] = '?????';
			foreach ($locationCodes as $locationCode => $holdingLabel){
				if (strlen($locationCode) > 0 && preg_match("~$holdingLabel~i", $holding['location'])){
					$holding['locationCode'] = $locationCode;
				}
			}
			if ($holding['locationCode'] == '?????'){
				$logger->log("Did not find location code for " . $holding['location'] , PEAR_LOG_DEBUG);
			}

			//Now that we have the location code, try to match with the marc record
			$holding['iType'] = 0;
			foreach ($marcItemData as $itemKey => $itemData){
				if (!$itemData['matched']){
					$locationMatched = (strpos($itemData['location'], $holding['locationCode']) === 0);
					$callNumberMatched = false;
					if (strlen($itemData['callnumber']) == 0 || strlen($holding['callnumber']) == 0){
						$callNumberMatched = (strlen($holding['callnumber']) == strlen($holding['callnumber']));
					}else{
						$callNumberMatched = (strpos($itemData['callnumber'], $holding['callnumber']) >= 0);
					}
					if ($locationMatched && $callNumberMatched){
						$holding['iType'] = $itemData['iType'];
						$itemData['matched'] = true;
					}
				}
			}

			//Check to see if this item can be held by the current patron.  Only important when
			//we know what pType is in use and we are showing all items.
			if ($scope == 93 && $pType > 0){
				if (!$driver->isItemHoldableToPatron($holding['locationCode'], $holding['iType'], $pType)){
					//$logger->log("Removing item $holdingKey because it is not usable by the current patronType $pType, iType is {$holding['iType']}, location is {$holding['locationCode']}", PEAR_LOG_DEBUG);
					//echo("Removing item $holdingKey because it is not usable by the current patronType $pType, iType is {$holding['iType']}, location is {$holding['locationCode']}");
					unset($ret[$holdingKey]);
					continue;
				}
			}

			//Add the holding to the sorted array to determine
			$sortString = $holding['location'] . '-'. $i;
			//$sortString = $holding['location'] . $holding['callnumber']. $i;
			if (strlen($physicalBranch) > 0 && stripos($holding['location'], $physicalBranch) !== false){
				//If the user is in a branch, those holdings come first.
				$holding['section'] = 'In this library';
				$holding['sectionId'] = 1;
				$sorted_array['1' . $sortString] = $holding;
			} else if (strlen($homeBranch) > 0 && stripos($holding['location'], $homeBranch) !== false){
				//Next come the user's home branch if the user is logged in or has the home_branch cookie set.
				$holding['section'] = 'Your library';
				$holding['sectionId'] = 2;
				$sorted_array['2' . $sortString] = $holding;
			} else if ((strlen($nearbyBranch1) > 0 && stripos($holding['location'], $nearbyBranch1) !== false)){
				//Next come nearby locations for the user
				$holding['section'] = 'Nearby Libraries';
				$holding['sectionId'] = 3;
				$sorted_array['3' . $sortString] = $holding;
			} else if ((strlen($nearbyBranch2) > 0 && stripos($holding['location'], $nearbyBranch2) !== false)){
				//Next come nearby locations for the user
				$holding['section'] = 'Nearby Libraries';
				$holding['sectionId'] = 4;
				$sorted_array['4' . $sortString] = $holding;
			} else if (strlen($libraryLocationLabels) > 0 && preg_match($libraryLocationLabels, $holding['location'])){
				//Next come any locations within the same system we are in.
				$holding['section'] = $library->displayName;
				$holding['sectionId'] = 5;
				$sorted_array['5' . $sortString] = $holding;
			} else {
				//Finally, all other holdings are shown sorted alphabetically.
				$holding['section'] = 'Other Locations';
				$holding['sectionId'] = 6;
				$sorted_array['6' . $sortString] = $holding;
			}
			$i++;
		}
		$timer->logTime('finished processing holdings');

		//Check to see if the title is holdable
		$holdable = $driver->isRecordHoldable($marcRecord, $id);
		foreach ($sorted_array as $key => $holding){
			$holding['holdable'] = $holdable ? 1 : 0;
			$sorted_array[$key] = $holding;
		}

		//Load order records, these only show in the full page view, not the item display
		$orderMatches = array();
		if (preg_match_all('/<tr\\s+class="bibOrderEntry">.*?<td\\s*>(.*?)<\/td>/s', $millenniumInfo->framesetInfo, $orderMatches)){
			for ($i = 0; $i < count($orderMatches[1]); $i++) {
				$location = trim($orderMatches[1][$i]);
				$location = preg_replace('/\\sC\\d{3}[\\s\\.]/', '', $location);
				//Remove courier code if any
				$sorted_array['7' . $location . $i] = array(
                    'location' => $location,
                    'section' => 'On Order',
                    'sectionId' => 7,
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

		$issueSummaries = $driver->getIssueSummaries($id, $millenniumInfo);
		$timer->logTime('loaded issue summaries');
		if (!is_null($issueSummaries)){
			krsort($sorted_array);
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
					$issueSummaries[$issueSummaryKey]['holdings'][strtolower($key)] = $holding;
				}else{
					//Need to automatically add a summary so we don't lose data
					$issueSummaries[$holding['location']] = array(
                        'location' => $holding['location'],
                        'type' => 'issue',
                        'holdings' => array(strtolower($key) => $holding),
					);
				}
			}
			foreach ($issueSummaries as $key => $issueSummary){
				if (isset($issueSummary['holdings']) && is_array($issueSummary['holdings'])){
					krsort($issueSummary['holdings']);
					$issueSummaries[$key] = $issueSummary;
				}
			}
			ksort($issueSummaries);
			return $issueSummaries;
		}else{
			return $sorted_array;
		}
	}

	private static function parseHoldingRows($driver, $id, $rows, $keys){
		global $configArray;
		$loc_col_name      = $configArray['OPAC']['location_column'];
		$call_col_name     = $configArray['OPAC']['call_no_column'];
		$status_col_name   = $configArray['OPAC']['status_column'];
		$reserves_col_name = $configArray['OPAC']['location_column'];
		$reserves_key_name = $configArray['OPAC']['reserves_key_name'];
		$transit_key_name  = $configArray['OPAC']['transit_key_name'];
		$stat_avail        = $configArray['OPAC']['status_avail'];
		$stat_due          = $configArray['OPAC']['status_due'];
		$stat_libuse       = $configArray['OPAC']['status_libuse'];

		$ret = array();
		$count = 0;
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
							if (isset($driver->statusTranslations[$statfull])){
								$statfull = $driver->statusTranslations[$statfull];
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
				$curHolding['holdQueueLength'] = isset($holdQueueLength) ? $holdQueueLength : null;
				$ret[] = $curHolding;
			}
			$count++;
		} //End looping through rows
		return $ret;
	}
}
