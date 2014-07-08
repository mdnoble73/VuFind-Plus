<?php
require_once 'Interface.php';
require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';
require_once ROOT_DIR . '/sys/eContent/EContentItem.php';
require_once ROOT_DIR . '/sys/eContent/EContentHold.php';
require_once ROOT_DIR . '/sys/eContent/EContentCheckout.php';
require_once ROOT_DIR . '/sys/eContent/EContentWishList.php';
require_once ROOT_DIR . '/sys/Utils/ArrayUtils.php';

/**
 * Handles processing of account information related to eContent.
 *
 * Copyright (C) Douglas County Libraries 2011.
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
 * @version 1.0
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Douglas County Libraries 2011.
 */
class EContentDriver implements DriverInterface{
	//local variables for caching
	private static $holdings = array();

	/**
	 * Get Status
	 *
	 * This is responsible for retrieving the status information of a certain
	 * record.
	 *
	 * @param   string  $recordId   The record id to retrieve the holdings for
	 * @return  mixed               An associative array with the following keys:
	 *                              availability (boolean), status, location,
	 *                              reserve, callnumber
	 *                              If an error occures, return a PEAR_Error
	 * @access  public
	 */
	public function getStatus($recordId){
		$holdings = $this->getHolding($recordId);
		$statusSummary = $this->getStatusSummary($recordId, $holdings);
		$statusSummary['id'] = 'econtentRecord' . $recordId;
		$statusSummary['shortId'] = 'econtentRecord' . $recordId;

		return $statusSummary;
	}

	/**
	 * Get Statuses
	 *
	 * This is responsible for retrieving the status information for a
	 * collection of records.
	 *
	 * @param   array  $recordIds   The array of record ids to retrieve the
	 *                              status for
	 * @return  mixed               An associative array with the following keys:
	 *                              availability (boolean), status, location,
	 *                              reserve, callnumber
	 *                              If an error occures, return a PEAR_Error
	 * @access  public
	 */
	public function getStatuses($ids){
		$items = array();
		$count = 0;
		foreach ($ids as $id) {
			$items[$count] = $this->getStatus($id);
			$count++;
		}
		return $items;
	}

	/**
	 * Get Holding
	 *
	 * This is responsible for retrieving the holding information of a certain
	 * record.
	 *
	 * @param   string  $recordId   The record id to retrieve the holdings for
	 * @return  mixed               An associative array with the following keys:
	 *                              availability (boolean), status, location,
	 *                              reserve, callnumber, duedate, number,
	 *                              holding summary, holding notes
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	public function getHolding($id, $allowReindex = true){
		if (array_key_exists($id, EContentDriver::$holdings)){
			return EContentDriver::$holdings[$id];
		}
		global $user;
		global $configArray;

		$libraryScopeId = $this->getLibraryScopingId();
		//Get any items that are stored for the record
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $id;
		$eContentRecord->find(true);
		if ($eContentRecord->accessType != 'external'){
			//Check to see if the record is checked out or on hold within VuFind
			$checkedOutToUser = false;
			$onHoldForUser = false;
			$holdPosition = 0;
			if ($user){
				$eContentCheckout = new EContentCheckout();
				$eContentCheckout->userId = $user->id;
				$eContentCheckout->status = 'out';
				$eContentCheckout->recordId = $id;
				$eContentCheckout->find();
				if ($eContentCheckout->N > 0){
					//The item is checked out to the current user
					$eContentCheckout->fetch();
					$checkedOutToUser = true;
				}else{
					$eContentHold = new EContentHold();
					$eContentHold->userId = $user->id;
					$eContentHold->whereAdd("status in ('active', 'suspended', 'available')");
					$eContentHold->recordId = $id;
					$eContentHold->find();
					if ($eContentHold->N > 0){
						$onHoldForUser = true;
						$eContentHold->fetch();
						$holdPosition = $this->_getHoldPosition($eContentHold);
					}
				}
			}

			$eContentItem = new EContentItem();
			$eContentItem->recordId = $id;
			if ($libraryScopeId != -1){
				$eContentItem->whereAdd("libraryId = -1 or libraryId = $libraryScopeId");
			}
			$items = array();
			$eContentItem->find();
			while ($eContentItem->fetch()){
				$item = clone $eContentItem;
				$item->source = $eContentRecord->source;
				//Generate links for the items
				$links = array();
				if ($checkedOutToUser){
					$links = $this->_getCheckedOutEContentLinks($eContentRecord, $item, $eContentCheckout);
				}else if ($eContentRecord->accessType == 'free' && $item->isExternalItem()){
					$links = $this->_getFreeExternalLinks($eContentRecord, $item);
				}else if ($onHoldForUser){
					$links = $this->getOnHoldEContentLinks($eContentHold);
				}

				$item->checkedOut = $checkedOutToUser;
				$item->onHold = $onHoldForUser;
				$item->holdPosition = $holdPosition;
				$item->links = $links;
				$items[] = $item;
			}
		}else{
			$items = $eContentRecord->getItems();
			//We have econtent stored on an external server. Check to see if it is available there (if possible)
			if (strcasecmp($eContentRecord->source, 'OverDrive') == 0){
				//Add links as needed
				$availability = $eContentRecord->getAvailability();
				$addCheckoutLink = false;
				$addPlaceHoldLink = false;
				foreach($availability as $availableFrom){
					if ($availableFrom->libraryId == -1){
						if ($availableFrom->availableCopies > 0){
							$addCheckoutLink = true;
						}else{
							$addPlaceHoldLink = true;
						}
					}else{
						//Non shared item, check to see if we are in the correct scope to show it
						if ($libraryScopeId == -1 || $availableFrom->libraryId == $libraryScopeId){
							if ($availableFrom->availableCopies > 0){
								$addCheckoutLink = true;
							}else{
								$addPlaceHoldLink = true;
							}
						}
					}
				}
				foreach ($items as $key => $item){
					$item->links = array();
					if ($addCheckoutLink){
						if ($configArray['OverDrive']['interfaceVersion'] == 1){
							$checkoutLink = "return VuFind.OverDrive.checkoutOverDriveItem('{$eContentRecord->externalId}', '{$item->externalFormatNumeric}');";
						}else{
							$checkoutLink = "return VuFind.OverDrive.checkoutOverDriveItemOneClick('{$eContentRecord->externalId}', '{$item->externalFormatNumeric}');";
						}
						$item->links[] = array(
								'onclick' => $checkoutLink,
								'text' => 'Check Out',
								'overDriveId' => $eContentRecord->externalId,
								'formatId' => $item->externalFormatNumeric,
								'action' => 'CheckOut'
						);
					}else if ($addPlaceHoldLink){
						$item->links[] = array(
								'onclick' => "return VuFind.OverDrive.placeOverDriveHold('{$eContentRecord->externalId}', '{$item->externalFormatNumeric}');",
								'text' => 'Place Hold',
								'overDriveId' => $eContentRecord->externalId,
								'formatId' => $item->externalFormatNumeric,
								'action' => 'Hold'
						);
					}
					$items[$key] = $item;
				}
			}else{
				foreach ($items as $key => $item){
					$item->links = $this->_getFreeExternalLinks($eContentRecord, $item);
					$items[$key] = $item;
				}
			}
			if ($libraryScopeId != -1){
				foreach ($items as $key => $item){
					if ($item->libraryId != -1 && $item->libraryId != $libraryScopeId ){
						unset($items[$key]);
					}
				}
			}
		}

		EContentDriver::$holdings[$id] = $items;
		return $items;
	}

	public function getLibraryScopingId(){
		//For econtent, we need to be more specific when restricting copies
		//since patrons can't use copies that are only available to other libraries.
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		$activeLibrary = Library::getActiveLibrary();
		$activeLocation = Location::getActiveLocation();
		$homeLibrary = Library::getPatronHomeLibrary();

		//Load the holding label for the branch where the user is physically.
		if (!is_null($homeLibrary)){
			return $homeLibrary->includeOutOfSystemExternalLinks ? -1 : $homeLibrary->libraryId;
		}else if (!is_null($activeLocation)){
			$activeLibrary = Library::getLibraryForLocation($activeLocation->locationId);
			return $activeLibrary->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		}else if (isset($activeLibrary)) {
			return $activeLibrary->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		}else if (!is_null($searchLocation)){
			$searchLibrary = Library::getLibraryForLocation($searchLibrary->locationId);
			return $searchLibrary->includeOutOfSystemExternalLinks ? -1 : $searchLocation->libraryId;
		}else if (isset($searchLibrary)) {
			return $searchLibrary->includeOutOfSystemExternalLinks ? -1 : $searchLibrary->libraryId;
		}else{
			return -1;
		}
	}

	/**
	 * @param EContentRecord $eContentRecord
	 * @return array
	 */
	public function getScopedAvailability($eContentRecord){
		$availability = array();
		$availability['mine'] = $eContentRecord->getAvailability();
		$availability['other'] = array();
		$scopingId = $this->getLibraryScopingId();
		if ($scopingId != -1){
			foreach ($availability['mine'] as $key => $availabilityItem){
				if ($availabilityItem->libraryId != -1 && $availabilityItem->libraryId != $scopingId){
					$availability['other'][$key] = $availability['mine'][$key];
					unset($availability['mine'][$key]);
				}
			}
		}
		return $availability;
	}

	public function getStatusSummary($id, $holdings){
		global $user;
		//Get the eContent Record
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $id;
		$eContentRecord->find(true);

		$drmType = $eContentRecord->accessType;
		$checkedOut = false;
		$onHold = false;
		$addedToWishList = false;
		$holdPosition = 0;

		$availability = $this->getScopedAvailability($eContentRecord);
		$availableCopies = 0;
		$totalCopies = 0;
		$onOrderCopies = 0;
		$checkedOut = 0;
		$onHold = 0;
		$wishListSize = 0;
		$numHolds = 0;
		if (count($availability['mine']) > 0){
			foreach ($availability['mine'] as $curAvailability){
				$availableCopies += $curAvailability->availableCopies;
				$totalCopies += $curAvailability->copiesOwned;
				$onOrderCopies += $curAvailability->onOrderCopies;
				if ($curAvailability->numberOfHolds > $numHolds){
					$numHolds = $curAvailability->numberOfHolds;
				}
			}
		}elseif ($eContentRecord->itemLevelOwnership == 0) {
			$totalCopies = $eContentRecord->availableCopies;
			$onOrderCopies = $eContentRecord->onOrderCopies;
		}

		//Load status summary
		$statusSummary = array();
		$statusSummary['recordId'] = $id;
		$statusSummary['totalCopies'] = $totalCopies;
		$statusSummary['onOrderCopies'] = $onOrderCopies;
		$statusSummary['accessType'] = $eContentRecord->accessType;
		$statusSummary['isOverDrive'] = false;
		$statusSummary['alwaysAvailable'] = false;
		$statusSummary['class'] = 'checkedOut';
		$statusSummary['available'] = false;
		$statusSummary['status'] = 'Not Available';

		if ($eContentRecord->accessType == 'external' ){
			$statusSummary['availableCopies'] = $availableCopies;
			if( strcasecmp($eContentRecord->source, 'OverDrive') == 0){
				$statusSummary['isOverDrive'] = true;
				if ($totalCopies >= 999999){
					$statusSummary['alwaysAvailable'] = true;
				}
			}
			if ($availableCopies > 0){
				$statusSummary['status'] = "Available from {$eContentRecord->source}";
				$statusSummary['available'] = true;
				$statusSummary['class'] = 'available';
			}else if( strcasecmp($eContentRecord->source, 'OverDrive') == 0){
				$statusSummary['status'] = 'Checked Out';
				$statusSummary['available'] = false;
				$statusSummary['class'] = 'checkedOut';
				$statusSummary['isOverDrive'] = true;
			}
		}else{
			//Check to see if it is checked out
			$checkouts = new EContentCheckout();
			$checkouts->status = 'out';
			$checkouts->recordId = $id;
			$checkouts->find();
			$statusSummary['numCheckedOut'] = $checkouts->N;

			//Get a count of the holds on the record
			$holds = new EContentHold();
			$holds->recordId = $id;
			$holds->whereAdd("(status = 'active' or status = 'suspended')");
			$holds->find();
			$statusSummary['numHolds'] = $holds->N;

			//Get a count of the available holds on the record
			$holds = new EContentHold();
			$holds->recordId = $id;
			$holds->status = 'available';
			$holds->find();
			$statusSummary['numAvailableHolds'] = $holds->N;

			//Check to see if the record is on the user's wishlist
			if ($user){
				$eContentWishList = new EContentWishList();
				$eContentWishList->userId = $user->id;
				$eContentWishList->recordId = $id;
				$eContentWishList->status = 'active';
				$eContentWishList->find();
				if ($eContentWishList->N > 0){
					$addedToWishList = true;
				}
			}

			if (count($holdings) == 0){
				$statusSummary['availableCopies'] = 0;
			}else{
				$statusSummary['availableCopies'] = $statusSummary['totalCopies'] - $statusSummary['numCheckedOut'] - $statusSummary['numAvailableHolds'];
			}

			if ($checkedOut == true){
				$statusSummary['status'] = 'Checked Out to you';
				$statusSummary['available'] = false;
				$statusSummary['class'] = 'available';
			}elseif ($onHold == true){
				$statusSummary['status'] = 'On Hold for you';
				$statusSummary['available'] = false;
				$statusSummary['class'] = 'available';
			}elseif ($addedToWishList == true){
				$statusSummary['status'] = 'On your wishlist';
				$statusSummary['available'] = false;
				if ($statusSummary['numCheckedOut'] < $statusSummary['totalCopies']){
					$statusSummary['class'] = 'available';
				}else{
					$statusSummary['class'] = 'checkedOut';
				}
			}elseif (count($holdings) == 0){
				$statusSummary['status'] = 'Not available yet';
				$statusSummary['available'] = false;
				$statusSummary['class'] = 'unavailable';
			}elseif ($statusSummary['numCheckedOut'] < $statusSummary['totalCopies']){
				$statusSummary['status'] = 'Available Online';
				$statusSummary['available'] = true;
				$statusSummary['class'] = 'available';
			}else{
				$statusSummary['status'] = 'Checked Out';
				$statusSummary['available'] = false;
				$statusSummary['class'] = 'checkedOut';
			}

			$wishList = new EContentWishList();
			$wishList->recordId = $id;
			$wishList->status = 'active';
			$wishList->find();
			$wishListSize = $wishList->N;
		}

		//Determine which buttons to show
		$statusSummary['source'] = $eContentRecord->source;
		$isFreeExternalLink = false;
		if ($drmType == 'free'){
			$isFreeExternalLink = true;
			foreach ($holdings as $holding){
				if (!$holding->isExternalItem()){
					$isFreeExternalLink = false;
				}
			}
		}
		if ($eContentRecord->accessType == 'external'){
			if (strcasecmp($eContentRecord->source, 'OverDrive') ==0 ){
				$statusSummary['holdQueueLength'] = $numHolds;
				$statusSummary['showPlaceHold'] = $availableCopies == 0 && count($availability['mine']) > 0;
				$statusSummary['showCheckout'] = $availableCopies > 0 && count($availability['mine']) > 0;
				$statusSummary['showAddToWishlist'] = false;
				$statusSummary['showAccessOnline'] = false;
			}else{
				$statusSummary['showPlaceHold'] = false;
				$statusSummary['showCheckout'] = false;
				$statusSummary['showAddToWishlist'] = false;
				$statusSummary['showAccessOnline'] = count($holdings) >= 1;
				if (count($holdings) == 1){
					$firstHolding = reset($holdings);
					if (isset($firstHolding->links[0])){
						$firstLink = $firstHolding->links[0];
						$statusSummary['accessOnlineUrl'] = $firstLink['url'];
						$statusSummary['accessOnlineText'] = $firstLink['text'];
					}
				}
			}
		}elseif ($isFreeExternalLink){
			$statusSummary['showPlaceHold'] = false;
			$statusSummary['showCheckout'] = false;
			$statusSummary['showAddToWishlist'] = false;
			$statusSummary['showAccessOnline'] = true;
		}else{
			$statusSummary['showPlaceHold'] = (!$checkedOut && !$onHold) && $drmType != 'free' && ($statusSummary['availableCopies'] == 0) && count($holdings) > 0;
			$statusSummary['showCheckout'] = (!$checkedOut && !$onHold) && ($statusSummary['availableCopies'] > 0);
			$statusSummary['showAddToWishlist'] = (count($holdings) == 0 && !$addedToWishList);
			$statusSummary['showAccessOnline'] = ($checkedOut && count($holdings) > 0);
			$statusSummary['holdQueueLength'] = $this->getWaitList($id);
		}

		$statusSummary['onHold'] = $onHold;
		$statusSummary['checkedOut'] = $checkedOut;
		$statusSummary['holdPosition'] = $holdPosition;
		$statusSummary['numHoldings'] = count($holdings);
		$statusSummary['wishListSize'] = $wishListSize;

		return $statusSummary;
	}

	public function getStatusSummaries($ids){
		$summaries = array();
		if (is_array($ids) && count($ids) > 0){
			foreach ($ids as $id){
				$holdings = $this->getHolding($id);
				//Load status summary
				$result = $this->getStatusSummary($id, $holdings);
				if (PEAR_Singleton::isError($result)) {
					PEAR_Singleton::raiseError($result);
				}
				$summaries[$id] = $result;
			}
		}
		return $summaries;
	}
	public function getPurchaseHistory($id){

	}

	public function getMyHolds($user){
		$holds = array();
		$holds['holds'] = array();
		$holds['holds']['available'] = array();
		$holds['holds']['unavailable'] = array();

		$availableHolds = new EContentHold();
		$availableHolds->userId = $user->id;
		$availableHolds->status ='available';
		$availableHolds->find();
		while ($availableHolds->fetch()){
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $availableHolds->recordId;
			if ($eContentRecord->find(true)){
				$expirationDate = $availableHolds->dateUpdated + 5 * 24 * 60 * 60;
				$holds['holds']['available'][] = array(
					'id' => $eContentRecord->id,
					'recordId' => 'econtentRecord' . $eContentRecord->id,
					'source' => $eContentRecord->source,
					'title' => $eContentRecord->title,
					'author' => $eContentRecord->author,
					'available' => true,
					'create' => $availableHolds->datePlaced,
					'expire' => $expirationDate,
					'status' => $availableHolds->status,
					'links' => $this->getOnHoldEContentLinks($availableHolds),
					'holdSource' => 'eContent'
				);
			}
		}
		$unavailableHolds = new EContentHold();
		$unavailableHolds->userId = $user->id;
		$unavailableHolds->whereAdd("(status = 'active' or status = 'suspended')");
		$unavailableHolds->find();
		while ($unavailableHolds->fetch()){
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $unavailableHolds->recordId;
			if ($eContentRecord->find(true)){
				$holds['holds']['unavailable'][] = array(
					'id' => $eContentRecord->id,
					'recordId' => 'econtentRecord' . $eContentRecord->id,
					'source' => $eContentRecord->source,
					'title' => $eContentRecord->title,
					'author' => $eContentRecord->author,
					'available' => true,
					'createTime' => $unavailableHolds->datePlaced,
					'status' => $unavailableHolds->status,
					'position' => $this->_getHoldPosition($unavailableHolds),
					'links' => $this->getOnHoldEContentLinks($unavailableHolds),
					'frozen' => $unavailableHolds->status == 'suspended',
					'reactivateDate' => $unavailableHolds->reactivateDate,
					'holdSource' => 'eContent'
				);
			}
		}

		return $holds;
	}



	private function _getHoldPosition($existingHold){
		$eContentHold = new EContentHold();
		$eContentHold->recordId = $existingHold->recordId;
		$eContentHold->whereAdd("datePlaced < {$existingHold->datePlaced} AND (status = 'active')");
		$eContentHold->find();
		return $eContentHold->N + 1;
	}

	public function getMyTransactions($user){
		global $configArray;
		$return = array();
		$eContentCheckout = new EContentCheckout();
		$eContentCheckout->userId = $user->id;
		$eContentCheckout->status = 'out';
		$eContentCheckout->find();
		$return['transactions'] = array();
		$return['numTransactions'] = $eContentCheckout->find();
		while ($eContentCheckout->fetch()){
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $eContentCheckout->recordId;
			if ($eContentRecord->find(true)){
				$daysUntilDue = ceil(($eContentCheckout->dateDue - time()) / (24 * 60 * 60));
				$overdue = $daysUntilDue < 0;
				$waitList = $this->getWaitList($eContentRecord->id);
				$links = $this->_getCheckedOutEContentLinks($eContentRecord, null, $eContentCheckout);
				//Get Ratings
				require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
				$econtentRating = new EContentRating();
				$econtentRating->recordId = $eContentRecord->id;
				$ratingData = $econtentRating->getRatingData($user, false);
				$return['transactions'][] = array(
					'id' => $eContentRecord->id,
					'recordId' => 'econtentRecord' . $eContentRecord->id,
					'source' => $eContentRecord->source,
					'checkoutSource' => 'eContent',
					'title' => $eContentRecord->title,
					'author' => $eContentRecord->author,
					'duedate' => $eContentCheckout->dateDue,
					'checkoutdate' => $eContentCheckout->dateCheckedOut,
					'daysUntilDue' => $daysUntilDue,
					'holdQueueLength' => $waitList,
					'links' => $links,
					'ratingData' => $ratingData,
					'recordUrl' => $configArray['Site']['path'] . '/EcontentRecord/' . $eContentRecord->id . '/Home',
				);
			}
		}
		return $return;
	}

	private function _getFreeExternalLinks($eContentRecord, $eContentItem){
		global $configArray;
		global $user;
		$links = array();
		$addDefaultTypeLinks = false;
		if ($eContentItem != null){
			//Single usage or free
			//default links to read the title or download
			$links = array_merge($links, $this->getDefaultEContentLinks($eContentRecord, $eContentItem));
		}else{
			$eContentItems = $eContentRecord->getItems();
			foreach ($eContentItems as $item){
				//Single usage or free
				//default links to read the title or download
				$links = array_merge($links, $this->getDefaultEContentLinks($eContentRecord, $item));
				$links[ArrayUtils::getLastKey($links)]['item_type'] = $item->item_type;
			}
		}

		return $links;
	}

	private function _getCheckedOutEContentLinks($eContentRecord, $eContentItem, $eContentCheckout){
		global $configArray;
		global $user;
		$links = array();
		$addDefaultTypeLinks = false;
		if ($eContentItem != null){
			if ($eContentRecord->accessType == 'acs' && ($eContentItem->item_type == 'epub' || $eContentItem->item_type == 'pdf')){
				//Protected by ACS server
				//Links to read the title online or checkout from ACS server
				if ($eContentItem->item_type == 'pdf'){
					$links = array_merge($links, $this->_getACSPdfLinks($eContentItem, $eContentCheckout));
				}elseif ($eContentItem->item_type == 'epub'){
					$links = array_merge($links, $this->_getACSEpubLinks($eContentItem, $eContentCheckout));
				}
			}else{
				//Single usage or free
				//default links to read the title or download
				$links = array_merge($links, $this->getDefaultEContentLinks($eContentRecord, $eContentItem));
			}
		}else{
			$eContentItems = $eContentRecord->getItems();
			foreach ($eContentItems as $item){
				if ($eContentRecord->accessType == 'acs' && ($item->item_type == 'epub' || $item->item_type == 'pdf')){
					//Protected by ACS server
					//Links to read the title online or checkout from ACS server
					if ($item->item_type == 'pdf'){
						$links = array_merge($links, $this->_getACSPdfLinks($item, $eContentCheckout));
					}elseif ($item->item_type == 'epub'){
						$links = array_merge($links, $this->_getACSEpubLinks($item, $eContentCheckout));
					}
				}else{
					//Single usage or free
					//default links to read the title or download
					$links = array_merge($links, $this->getDefaultEContentLinks($eContentRecord, $item));
				}
				$links[ArrayUtils::getLastKey($links)]['item_type'] = $item->item_type;
			}
		}

		//Add a link to return the title
		if ($eContentCheckout->downloadedToReader == 0){
			$links[] = array(
							'text' => 'Return&nbsp;Now',
							'onclick' => "if (confirm('Are you sure you want to return this title?')){returnEpub('{$configArray['Site']['path']}/EcontentRecord/{$eContentRecord->id}/ReturnTitle')};return false;",
							'typeReturn' => 0
			);
		}else{
			$links[] = array(
				'text' => 'Return&nbsp;Now',
				'onclick' => "alert('Please return this title from your digital reader.');return false;",
				'typeReturn' => 1
			);
		}
		return $links;
	}

	public function placeHold($id, $user){
		$id = str_ireplace("econtentrecord", "", $id);
		$return = array();
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $id;
		if (!$eContentRecord->find(true)){
			$return['result'] = false;
			$return['message'] = "Could not find a record with an id of $id";
		}else{
			$return['title'] = $eContentRecord->title;

			//If the source is overdrive, process it as an overdrive title
			if (strcasecmp($eContentRecord->source, 'OverDrive') == 0){
				require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
				$overDriveDriver = OverDriveDriverFactory::getDriver();
				$overDriveResult = $overDriveDriver->placeOverDriveHold($eContentRecord->externalId, '', $user);
				$return['result'] = $overDriveResult['result'];
				$return['message'] = $overDriveResult['message'];
			}else{
				//Check to see if the user already has a hold placed
				$holds = new EContentHold();
				$holds->userId = $user->id;
				$holds->recordId = $id;
				$holds->whereAdd("(status = 'active' or status = 'suspended' or status ='available')");
				$holds->find();
				if ($holds->N > 0){
					$return['result'] = false;
					$return['message'] = "That record is already on hold for you, unable to place a second hold.";
				}else{
					//Check to see if the user already has the record checked out
					$checkouts = new EContentCheckout();
					$checkouts->userId = $user->id;
					$checkouts->status = 'out';
					$checkouts->recordId = $id;
					$checkouts->find();
					if ($checkouts->N > 0){
						$return['result'] = false;
						$return['message'] = "That record is already checked out to you, unable to place a hold.";
					}else{
						//Check to see if there are any available copies and then checkout the record rather than placing a hold
						$holdings = $this->getHolding($id);
						$holdingsSummary = $this->getStatusSummary($id, $holdings);
						if ($holdingsSummary['availableCopies'] > 0 || $eContentRecord->accessType == 'free'){
							//The record can be checked out directly
							$ret = $this->checkoutRecord($id, $user);
							return $ret;
						}else{
							//Place the hold for the user
							$hold = new EContentHold();
							$hold->userId = $user->id;
							$hold->recordId = $id;
							$hold->status = 'active';
							$hold->datePlaced = time();
							$hold->dateUpdated = time();
							if ($hold->insert()){
								$return['result'] = true;
								$holdPosition = $this->_getHoldPosition($hold);
								$return['message'] = "Your hold was successfully placed, you are number {$holdPosition} in the queue.";

								//Record that the record had a hold placed on it
								$this->recordEContentAction($id, "Place Hold", $eContentRecord->accessType);
							}
						}
					}
				}
			}
		}
		return $return;
	}

	public function cancelHold($id){
		global $user;
		//Check to see if there is an existing hold for the record
		$record = new EContentRecord();
		$record->id = $id;
		if ($record->find(true)){
			$title = $record->title;
			$hold = new EContentHold();
			$hold->recordId = $id;
			$hold->userId = $user->id;
			$hold->whereAdd("status in ('active', 'suspended')");
			$hold->find();
			if ($hold->N > 0){
				$hold->fetch();
				$hold->status = 'cancelled';
				$hold->dateUpdated = time();
				$ret = $hold->update();
				if ($ret == 1){
					$this->processHoldQueue($id);
					return array(
					      'title' => $title,
					      'result' => true,
					      'message' => 'Your hold was cancelled successfully.');
				}else{
					return array(
					      'title' => $title,
					      'result' => false,
					      'message' => 'Unable to update your hold.');
				}
			}else{
				return array(
				      'title' => $title,
				      'result' => true,
				      'message' => 'Sorry, but we could not find a hold for you for that title.');
			}
		}else{
			return array(
				      'title' => '',
				      'result' => false,
				      'message' => 'Could not find a record with that title.');
		}
	}

	public function reactivateHold($id){
		global $user;
		//Check to see if there is an existing hold for the record
		$record = new EContentRecord();
		$record->id = $id;
		if ($record->find(true)){
			$title = $record->title;
			$hold = new EContentHold();
			$hold->recordId = $id;
			$hold->userId = $user->id;
			$hold->status = 'suspended';
			$hold->find();
			if ($hold->N > 0){
				$hold->fetch();
				$hold->status = 'active';
				$hold->dateUpdated = time();
				$ret = $hold->update();
				if ($ret == 1){
					$this->processHoldQueue($id);
					return array(
					      'title' => $title,
					      'result' => true,
					      'message' => 'Your hold was activated successfully.');
				}else{
					return array(
					      'title' => $title,
					      'result' => true,
					      'message' => 'Unable to activate your hold.');
				}
			}else{
				return array(
				      'title' => $title,
				      'result' => true,
				      'message' => 'Sorry, but we could not find a hold for you for that title.');
			}
		}else{
			return array(
				      'title' => '',
				      'result' => false,
				      'message' => 'Could not find a record with that title.');
		}
	}

	public function suspendHolds($ids, $dateToReactivate){
		global $user;
		$result = array();
		foreach ($ids as $id){
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $id;
			if ($eContentRecord->find(true)){
				//Find the hold for the record
				$hold = new EContentHold();
				$hold->recordId = $id;
				$hold->userId = $user->id;
				$hold->status = 'active';
				if ($hold->find(true)){
					$hold->status = 'suspended';
					$hold->reactivateDate = $dateToReactivate;
					$hold->dateUpdated = time();
					$ret = $hold->update();
					if ($ret == 1){
						$result[$id] = array(
							'success' => true,
							'title' => $eContentRecord->title,
							'error' => "The hold was suspended."
						);
					}else{
						$result[$id] = array(
							'success' => false,
							'title' => $eContentRecord->title,
							'error' => "Could not suspend the hold."
						);
					}
				}else{
					$result[$id] = array(
						'success' => false,
						'title' => $eContentRecord->title,
						'error' => "Could not find an active hold to suspend."
					);
				}
			}else{
				$result[$id] = array(
					'success' => false,
					'error' => "Could not find a record with that id"
				);
			}
		}
		return $result;
	}

	public function checkoutRecord($id, $user){
		global $configArray;
		$return = array();
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $id;
		$eContentRecord->find(true);
		$return['title'] = $eContentRecord->title;
		//Check to see if the user already has the record checked out
		$checkouts = new EContentCheckout();
		$checkouts->userId = $user->id;
		$checkouts->status = 'out';
		$checkouts->recordId = $id;
		$checkouts->find();
		if ($checkouts->N > 0){
			$return['result'] = false;
			$return['message'] = "That record is already checked out to you, unable to check it out again.";
		}else{
			//Check to see if the record is on hold for the current user
			$holds = new EContentHold();
			$holds->userId = $user->id;
			$holds->recordId = $id;
			$holds->whereAdd("status != 'filled' AND status != 'cancelled' AND status != 'abandoned'");
			$checkoutRecord = true;
			if ($holds->find(true)){
				if ($holds->status == 'available'){
					$checkoutRecord = true;
					$holds->status = 'filled';
					$holds->dateUpdated = time();
					$ret = $holds->update();
					$checkoutRecord = $ret == 1 ;
				}else{
					$checkoutRecord = false;
					$return['result'] = false;
					$return['message'] = "This title is already on hold for you.";
				}
			}else{
				//Check to see if there are any available copies
				$holdings = $this->getHolding($id);
				$statusSummary = $this->getStatusSummary($id, $holdings);
				if ($statusSummary['availableCopies'] == 0){
					$return['result'] = false;
					$return['message'] = "There are no available copies of this title, please place a hold instead.";
				}else{
					$checkoutRecord = true;
				}
			}

			if ($checkoutRecord){
				//Checkout the record to the user
				$checkout = new EContentCheckout();
				$checkout->userId = $user->id;
				$checkout->recordId = $id;
				$checkout->status = 'out';
				$checkout->dateCheckedOut = time();
				$loanTerm = $configArray['EContent']['loanTerm'];
				$checkout->dateDue = time() + $loanTerm * 24 * 60 * 60; //Allow titles to be checked our for 3 weeks

				if ($checkout->insert()){
					$return['result'] = true;
					$return['message'] = "The title was checked out to you successfully.  You may read it from Checked Out eBooks and eAudio page within your account.";

					//Record that the record was checked out
					$this->recordEContentAction($id, "Checked Out", $eContentRecord->accessType);

					//Add the records to the reading history for the user
					if ($user->trackReadingHistory == 1){
						$this->addRecordToReadingHistory($eContentRecord, $user);
					}

					//If there are no more records available, reindex
					//Don't force a reindex to improve speed and deal with non xml characters
					//$eContentRecord->saveToSolr();
				}
			}
		}
		return $return;
	}

	public function addRecordToReadingHistory($eContentRecord, $user){
		//Get the resource for the record
		require_once(ROOT_DIR . '/services/MyResearch/lib/Resource.php');
		$resource = new Resource();
		$resource->record_id = $eContentRecord->id;
		$resource->source = 'eContent';
		if (!$resource->find(true)){
			$resource->title = $eContentRecord->title;
			$resource->author = $eContentRecord->author;
			$resource->format = 'EMedia';
			$resource->format_category = $eContentRecord->format_category();
			$ret = $resource->insert();
		}
		//Check to see if there is an existing entry
		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		$readingHistoryEntry = new ReadingHistoryEntry();
		$readingHistoryEntry->userId = $user->id;
		$readingHistoryEntry->resourceId = $resource->id;
		if (!$readingHistoryEntry->find(true)){
			$readingHistoryEntry->firstCheckoutDate = date('Y-m-d');
			$readingHistoryEntry->lastCheckoutDate = date('Y-m-d');
			$readingHistoryEntry->daysCheckedOut = 1;
			$ret = $readingHistoryEntry->insert();
		}else{
			$readingHistoryEntry->lastCheckoutDate = date('Y-m-d');
			$ret = $readingHistoryEntry->update();
		}
	}

	public function returnRecordInReadingHistory($eContentRecord, $user){
		//Get the resource for the record
		$resource = new Resource();
		$resource->record_id = $eContentRecord->id;
		$resource->source = 'eContent';
		if ($resource->find(true)){
			//Check to see if there is an existing entry
			require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
			$readingHistoryEntry = new ReadingHistoryEntry();
			$readingHistoryEntry->userId = $user->id;
			$readingHistoryEntry->resourceId = $resource->id;
			if ($readingHistoryEntry->find(true)){
				$readingHistoryEntry->lastCheckoutDate = date('Y-m-d');
				$ret = $readingHistoryEntry->update();
			}
		}
	}

	public function getMyEContent($user){
		global $user;
		global $configArray;
		$eContent = array();
		$myTransactions = $this->getMyTransactions($user);
		$eContent['checkedOut'] = $myTransactions['transactions'];

		$myHolds = $this->getMyHolds($user);
		$eContent['availableHolds'] = $myHolds['holds']['available'];
		$eContent['unavailableHolds'] = $myHolds['holds']['unavailable'];

		$myWishList = $this->getMyWishList($user);
		$eContent['wishlist'] = $myWishList['items'];

		/*require_once(ROOT_DIR . '/sys/eContent/EContentHistoryEntry.php');
		$user_epub_history = new EContentHistoryEntry();
		$user_epub_history->userId = $user->id;
		$user_epub_history->orderBy('openDate DESC, title ASC');
		$econtentRecord = new EContentRecord();
		$user_epub_history->joinAdd($econtentRecord, 'INNER');
		$user_epub_history->whereAdd("econtent_record.accessType = 'free'");
		$user_epub_history->find();
		$freeContent = array();
		while ($user_epub_history->fetch()){
			$freeItem = clone $user_epub_history;
			$freeItem->links[] = array(
				'url' => $configArray['Site']['path'] . '/EcontentRecord/' . $freeItem->recordId ,
				'text' => 'Read'
			);

			$freeContent[$freeItem->id] = $freeItem;
		}
		$eContent['free'] = $freeContent;*/

		return $eContent;
	}

	public function getMyWishList($user){
		global $configArray;
		//Get wishlist
		$wishListEntry = new EContentWishList();
		$wishListEntry->userId = $user->id;
		$wishListEntry->status = 'active';
		$wishListEntry->orderBy('title ASC');
		$econtentRecord = new EContentRecord();
		$wishListEntry->joinAdd($econtentRecord, 'INNER');
		$wishListEntry->find();
		$wishList = array();
		while ($wishListEntry->fetch()){
			$wishListItem = clone $wishListEntry;
			$wishListItem->links[] = array(
				'url' => $configArray['Site']['path'] . '/EcontentRecord/' . $wishListEntry->recordId . '/RemoveFromWishList' ,
				'text' => 'Remove&nbsp;From&nbsp;Wish&nbsp;List'
			);
			$wishListItem->recordId = 'econtentRecord' . $wishListItem->recordId;
			$wishList[$wishListItem->id] = $wishListItem;
		}
		$wishList['items'] = $wishList;

		return $wishList;
	}

	public function getWaitList($id){
		$eContentHold = new EContentHold();
		$eContentHold->recordId = $id;
		$eContentHold->status = 'active';
		$eContentHold->find();
		return $eContentHold->N;
	}

	private function getDefaultEContentLinks($eContentRecord, $eContentItem){
		global $configArray;
		global $user;

		$links = array();
		if (strcasecmp($eContentItem->item_type, 'epub') == 0){
			//Read in DCL Viewer
			$links[] = array(
							'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentItem->recordId}/Viewer?item={$eContentItem->id}",
							'text' => 'Read&nbsp;EPUB&nbsp;Online',
			);

			if ($eContentRecord->source != "Gale Group"){
				//Download link
				$links[] = array(
								'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentItem->recordId}/Download?item={$eContentItem->id}",
								'text' => 'Download&nbsp;EPUB',
				);
			}
		}elseif (strcasecmp($eContentItem->item_type, 'mp3') == 0){
			//Read online (will launch PDF viewer)
			$links[] = array(
							'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentItem->recordId}/Viewer?item={$eContentItem->id}",
							'text' => 'Listen&nbsp;Online',
			);
			$links[] = array(
							'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentItem->recordId}/Download?item={$eContentItem->id}",
							'text' => 'Download&nbsp;MP3',
			);
		}elseif (strcasecmp($eContentItem->item_type, 'pdf') == 0){
			//Read online (will launch PDF viewer)
			$links[] = array(
							'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentItem->recordId}/Download?item={$eContentItem->id}",
							'text' => 'Open&nbsp;PDF',
			);
		}elseif (strcasecmp($eContentItem->item_type, 'kindle') == 0){
			//Download book to device
			$links[] = array(
							'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentItem->recordId}/Download?item={$eContentItem->id}",
							'text' => 'Download&nbsp;Kindle&nbsp;Book',
			);
		}elseif (strcasecmp($eContentItem->item_type, 'plucker') == 0){
			//Download book to device
			$links[] = array(
							'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentItem->recordId}/Download?item={$eContentItem->id}",
							'text' => 'Download&nbsp;Plucker&nbsp;Book',
			);
		}elseif (strcasecmp($eContentItem->item_type, 'externalMP3') == 0){
			if ($eContentRecord->source == 'Freegal'){
				if ($user){
					$url = $eContentItem->link;
					$url = str_replace("{patronBarcode}", $user->cat_username, $url);
					$url = str_replace("{patronPin}", $user->cat_password, $url);
					//Link to Freegal
					$links[] = array(
									'url' => $url,
									'text' => 'Get&nbsp;MP3&nbsp;From&nbsp;Freegal',
					);
				}else{
					$links[] = array(
									'url' => $configArray['Site']['path'] . "/MyAccount/Login",
									'text' => 'Login&nbsp;to&nbsp;download&nbsp;from&nbsp;Freegal',
					);
				}
			}else{
				$links[] = array(
							'url' => $eContentItem->link,
							'text' => 'Access&nbsp;MP3',
				);
			}
		}elseif (strcasecmp($eContentItem->item_type, 'interactiveBook') == 0){
			$links[] = array(
							'url' =>  $configArray['Site']['path'] . "/EcontentRecord/{$eContentItem->recordId}/Link?itemId={$eContentItem->id}",
							'text' => 'Access&nbsp;eBook',
			);
		}elseif (strcasecmp($eContentItem->item_type, 'text') == 0){
			//Read online (will launch PDF viewer)
			$links[] = array(
							'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentItem->recordId}/Download?item={$eContentItem->id}",
							'text' => 'Open&nbsp;Plain&nbsp;Text',
			);
		}else{
			$links[] = array(
							'url' =>  $configArray['Site']['path'] . "/EcontentRecord/{$eContentItem->recordId}/Link?itemId={$eContentItem->id}",
							'text' => 'Access&nbsp;' . translate($eContentItem->item_type),
			);
		}
		return $links;
	}

	function getOnHoldEContentLinks($eContentHold){
		global $configArray;
		$links = array();
		//Link to cancel hold
		$links[] = array(
			'text' => 'Cancel&nbsp;Hold',
			'onclick' => "if (confirm('Are you sure you want to cancel this title?')){cancelEContentHold('{$configArray['Site']['path']}/EcontentRecord/{$eContentHold->recordId}/CancelHold')};return false;",

		);
		//Link to suspend hold
		/*if ($eContentHold->status == 'active'){
			$links[] = array(
				'text' => 'Suspend&nbsp;Hold',
				'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentHold->recordId}/SuspendHold",
			);
		}*/
		//Link to reactivate hold
		if ($eContentHold->status == 'suspended'){
			$links[] = array(
				'text' => 'Reactivate&nbsp;Hold',
				'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentHold->recordId}/ReactivateHold",
				'onclick' => "reactivateEContentHold('{$configArray['Site']['path']}/EcontentRecord/{$eContentHold->recordId}/ReactivateHold');return false;",
			);
		}
		//Link to check out (if available)
		if ($eContentHold->status == 'available'){
			$links[] = array(
				'text' => 'Checkout',
				'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentHold->recordId}/Checkout",

			);
		}

		return $links;
	}

	function returnRecord($id){
		global $user;
		global $logger;
		//Get the item information for the record
		require_once(ROOT_DIR . '/sys/eContent/EContentCheckout.php');
		$checkout = new EContentCheckout();
		$checkout->userId = $user->id;
		$checkout->recordId = $id;
		$checkout->status = 'out';

		$return = array();
		//$trans->whereAdd('timeReturned = null');
		if ($checkout->find(true)){
			$output = array();
			$checkout->dateReturned = time();
			$checkout->status = 'returned';
			$ret = $checkout->update();

			if ($ret != 0){
				$this->processHoldQueue($id);
				$eContentRecord = new EContentRecord();
				$eContentRecord->id = $id;
				$eContentRecord->find(true);
				//Record that the title was checked in
				$this->recordEContentAction($id, "Checked In", $eContentRecord->accessType);

				$eContentRecord->saveToSolr();

				$return = array('success' => true, 'message' => "The title was returned successfully.");
			}else{
				$return = array('success' => false, 'message' => "Could not return the item");
			}

			$output['database-response'] = $ret;
		}else{
			$logger->log("Could not find a checked out item for that title in the database.", PEAR_LOG_INFO);
			$return = array('success' => false, 'message' => "Could not find a checked out item for that title in the database.  It may have already been returned.");
		}
		return $return;
	}

	function processHoldQueue($id){
		//Check to see if there are any copies available for the next person
		$holdings = $this->getHolding($id);
		$holdingSummary = $this->getStatusSummary($id, $holdings);
		if ($holdingSummary['availableCopies'] >= 1){

			$eContentHold = new EContentHold();
			$eContentHold->recordId = $id;
			$eContentHold->status = 'active';
			$eContentHold->orderBy('datePlaced ASC');
			$eContentHold->limit(0, 1);
			if ($eContentHold->find(true)){
				//The next user in the list should get the hold
				$eContentHold->status = 'available';
				$eContentHold->dateUpdated = time();
				$eContentHold->update();
			}
		}
	}

	private function _getACSEpubLinks($eContentItem, $eContentCheckout){
		require_once ROOT_DIR . '/sys/AdobeContentServer.php';
		global $configArray;
		$links = array();
		//Read in DCL Viewer
		$links[] = array(
			'url' => $configArray['Site']['path'] . "/EcontentRecord/{$eContentItem->recordId}/Viewer?item={$eContentItem->id}",
			'text' => 'Read&nbsp;Online',
		);

		//Download link
		$downloadLink = AdobeContentServer::mintDownloadLink($eContentItem, $eContentCheckout);
		if ($downloadLink != null){
			$links[] = array(
				'url' => $downloadLink,
				'text' => 'Download',
			);
		}
		return $links;
	}

	private function _getACSPdfLinks($eContentItem, $eContentCheckout){
		require_once ROOT_DIR . '/sys/AdobeContentServer.php';
		global $configArray;
		$links = array();

		//Download link
		$downloadLink = AdobeContentServer::mintDownloadLink($eContentItem, $eContentCheckout);
		$links[] = array(
			'url' => $downloadLink,
			'text' => 'Download',
		);
		return $links;
	}

	public function isRecordCheckedOutToUser($id){
		require_once(ROOT_DIR . '/sys/eContent/EContentCheckout.php');
		global $user;
		$checkout = new EContentCheckout();
		$checkout->recordId = $id;
		$checkout->userId = $user->id;
		$checkout->status = 'out';
		if ($checkout->find(true)){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Track that an e-pub file was opened in the user's reading history.
	 */
	public function recordEContentAction($id, $action, $accessType){
		global $user;

		require_once(ROOT_DIR . '/sys/eContent/EContentHistoryEntry.php');

		$entry = new EContentHistoryEntry();
		$entry->userId = $user->id;
		$entry->recordId = $id;
		$entry->action = $action;
		$entry->accessType = $accessType;
		//Open date will be filled out automatically.
		$entry->insert();
	}

	public function getAccountSummary(){
		global $user;
		$accountSummary = array();
		if ($user){
			//Get a count of checked out items
			$eContentCheckout = new EContentCheckout();
			$eContentCheckout->status = 'out';
			$eContentCheckout->userId = $user->id;
			$eContentCheckout->find();
			$accountSummary['numEContentCheckedOut'] = $eContentCheckout->N;

			//Get a count of available holds
			$eContentHolds = new EContentHold();
			$eContentHolds->status = 'available';
			$eContentHolds->userId = $user->id;
			$eContentHolds->find();
			$accountSummary['numEContentAvailableHolds'] = $eContentHolds->N;

			//Get a count of unavailable holds
			$eContentHolds = new EContentHold();
			$eContentHolds->whereAdd("status IN ('active', 'suspended')");
			$eContentHolds->userId = $user->id;
			$eContentHolds->find();
			$accountSummary['numEContentUnavailableHolds'] = $eContentHolds->N;

			//Get a count of items on the wishlist
			$eContentWishList = new EContentWishList();
			$eContentWishList->status = 'active';
			$eContentWishList->userId = $user->id;
			$eContentWishList->find();
			$accountSummary['numEContentWishList'] = $eContentWishList->N;
		}else{
			return array(
				'numEContentCheckedOut' => 0,
				'numEContentAvailableHolds' => 0,
				'numEContentUnavailableHolds' => 0,
				'numEContentWishList' => 0,
			);
		}

		return $accountSummary;
	}

	function addToWishList($id, $user){
		$wishlistEntry = new EContentWishList();
		$wishlistEntry->userId = $user->id;
		$wishlistEntry->recordId = $id;
		$wishlistEntry->status = 'active';

		if ($wishlistEntry->find(true)){
			//The record was already added to the database
		}else{
			//Add to the database
			$wishlistEntry->dateAdded = time();
			$wishlistEntry->insert();
		}
		return true;
	}

	/**
	 * Loads items information as quickly as possible (no direct calls to the ILS)
	 *
	 * return is an array of items with the following information:
	 *  callnumber
	 *  available
	 *  holdable
	 *  lastStatusCheck (time)
	 *
	 * @param $id
	 * @param $scopingEnabled
	 * @return mixed
	 */
	public function getItemsFast($id, $scopingEnabled) {
		return $this->getStatus($id);
	}
}