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

require_once ROOT_DIR . '/Action.php';

class JSON extends Action {

	// define some status constants
	const STATUS_OK = 'OK';                  // good
	const STATUS_ERROR = 'ERROR';            // bad
	const STATUS_NEED_AUTH = 'NEED_AUTH';    // must login first

	function launch()
	{
		global $analytics;
		$analytics->disableTracking();

		//header('Content-type: application/json');
		header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$method = $_GET['method'];
		if (is_callable(array($this, $method))) {
			if ($method == 'getHoursAndLocations'){
				$output = $this->$method();
			}else{
				$output = json_encode(array('result'=>$this->$method()));
			}
		} else {
			$output = json_encode(array('error'=>'invalid_method'));
		}

		echo $output;
	}

	function isLoggedIn(){
		global $user;
		if ($user != false){
			return true;
		}else{
			return false;
		}
	}

	function getUserLists(){
		global $user;
		$lists = $user->getLists();
		$userLists = array();
		foreach($lists as $current) {
			$userLists[] = array('id' => $current->id,
                    'title' => $current->title);
		}
		return $userLists;
	}

	function saveToMyList(){
		require_once ROOT_DIR . '/services/MyResearch/lib/Resource.php';
		require_once ROOT_DIR . '/services/MyResearch/lib/User.php';

		$listId = $_REQUEST['list'];
		$tags = $_REQUEST['mytags'];
		$notes = $_REQUEST['notes'];
		$ids = $_REQUEST['id'];

		global $user;

		$list = new User_list();
		if ($_GET['list'] != '') {
			$list->id = $listId;
			$list->find(true);
		} else {
			$list->user_id = $user->id;
			$list->title = "My Favorites";
			$list->insert();
		}

		$ctr = 0;
		foreach ($ids as $id){
			$source = 'VuFind';
			$recordId = $id;
			if (strpos($recordId, 'econtentRecord') === 0){
				$source = 'eContent';
				$recordId = str_ireplace("econtentrecord", "", $recordId);
			}
			$ctr++;
			$resource = new Resource();
			$resource->record_id = $recordId;
			$resource->source = $source;
			if (!$resource->find(true)) {
				$resource->insert();
			}

			preg_match_all('/"[^"]*"|[^,]+/', $tags, $tagArray);
			//Make sure that Solr is only updated once for performance reasons.
			$user->addResource($resource, $list, $tagArray[0], $notes, $ctr == count($ids));
		}
		return array(
          'status' => 'OK'
          );
	}

	function loginUser(){
		//Login the user.  Must be called via Post parameters.
		global $user;
		$user = UserAccount::isLoggedIn();
		if (!$user || PEAR_Singleton::isError($user)){
			$user = UserAccount::login();
			if (!$user || PEAR_Singleton::isError($user)){
				return array(
					'success'=>false,
					'message'=>translate("Sorry that login information was not recognized, please try again.")
				);
			}
		}

		$patronHomeBranch = Location::getUserHomeLocation();
		//Check to see if materials request should be activated
		require_once ROOT_DIR . '/sys/MaterialsRequest.php';
		return array(
			'success'=>true,
			'name'=>ucwords($user->firstname . ' ' . $user->lastname),
			'phone'=>$user->phone,
			'email'=>$user->email,
			'homeLocation'=> isset($patronHomeBranch) ? $patronHomeBranch->code : '',
			'homeLocationId'=> isset($patronHomeBranch) ? $patronHomeBranch->locationId : '',
			'enableMaterialsRequest' => MaterialsRequest::enableMaterialsRequest(true),
		);
	}

	/**
	 * Get Item Statuses
	 *
	 * This is responsible for printing the holdings information for a
	 * collection of records in JSON format.
	 *
	 * @return void
	 * @access public
	 * @author Chris Delis <cedelis@uillinois.edu>
	 * @author Tuan Nguyen <tuan@yorku.ca>
	 */
	public function getItemStatuses(){
		global $interface;
		global $configArray;

		$catalog = ConnectionManager::connectToCatalog();
		if (!$catalog || !$catalog->status) {
			$this->output(translate('An error has occurred'), JSON::STATUS_ERROR);
		}
		$printIds = array();
		$econtentIds = array();
		$allIds = array();
		foreach ($_GET['id'] as $id){
			if (preg_match('/econtentRecord(\d+)$/i', $id, $matches)){
				$econtentIds[] = $matches[1];
			}else{
				$printIds[] = $id;
			}
		}
		$allIds = array_merge($printIds, $econtentIds);
		$results = array();
		if (count($printIds) > 0){
			$results = $catalog->getStatuses($printIds);
			if (PEAR_Singleton::isError($results)) {
				$this->output($results->getMessage(), JSON::STATUS_ERROR);
			} else if (!is_array($results)) {
				// If getStatuses returned garbage, let's turn it into an empty array
				// to avoid triggering a notice in the foreach loop below.
				$results = array();
			}
		}

		// In order to detect IDs missing from the status response, create an
		// array with a key for every requested ID.  We will clear keys as we
		// encounter IDs in the response -- anything left will be problems that
		// need special handling.
		$missingIds = array_flip($printIds);

		// Load messages for response:
		$messages = array(
            'available' => $interface->fetch('AJAX/status-available.tpl'),
            'unavailable' => $interface->fetch('AJAX/status-unavailable.tpl')
		);

		// Load callnumber and location settings:
		$callnumberSetting = isset($configArray['Item_Status']['multiple_call_nos'])
			? $configArray['Item_Status']['multiple_call_nos'] : 'msg';
		$locationSetting = isset($configArray['Item_Status']['multiple_locations'])
			? $configArray['Item_Status']['multiple_locations'] : 'msg';

		// Loop through all the status information that came back
		$statuses = array();
		foreach ($results as $record) {
			// Skip errors and empty records:
			if (!PEAR_Singleton::isError($record) && count($record)) {
				if ($locationSetting == "group") {
					$current = $this->_getItemStatusGroup($record, $messages, $callnumberSetting);
				} else {
					$current = $this->_getItemStatus($record, $messages, $locationSetting, $callnumberSetting);
				}
				$statuses[] = $current;

				// The current ID is not missing -- remove it from the missing list.
				unset($missingIds[$current['id']]);
			}
		}

		// If any IDs were missing, send back appropriate dummy data
		foreach ($missingIds as $missingId => $junk) {
			$statuses[] = array(
                'id'                   => $missingId,
                'availability'         => 'false',
                'availability_message' => $messages['unavailable'],
                'location'             => translate('Unknown'),
                'locationList'         => false,
                'reserve'              => 'false',
                'reserve_message'      => translate('Not On Reserve'),
                'callnumber'           => ''
                );
		}

		if (count($econtentIds) > 0){
			require_once ROOT_DIR . '/Drivers/EContentDriver.php';
			$econtentDriver = new EContentDriver();
			$econtentResults = $econtentDriver->getStatuses($econtentIds);
			foreach ($econtentResults as $result){
				$available = $result['available'];
				$interface->assign('status', $result['status']);
				if ($available){
					$message = $interface->fetch('AJAX/status-available.tpl');
				}else{
					$message = $interface->fetch('AJAX/status-unavailable.tpl');
				}
				$statuses[] = array(
                'id'                   => $result['id'],
                'shortId'                   => $result['id'],
                'availability' => ($available ? 'true' : 'false'),
                'availability_message' => $message, //$messages[$available ? 'available' : 'unavailable'],
                'location'             => translate('Unknown'),
                'locationList'         => false,
                'reserve'              => 'false',
                'reserve_message'      => translate('Not On Reserve'),
                'callnumber'           => ''
                );

			}
		}

		// Done
		$this->output($statuses, JSON::STATUS_OK);
	}

	/**
	 * Send output data and exit.
	 *
	 * @param mixed  $data   The response data
	 * @param string $status Status of the request
	 *
	 * @return void
	 * @access public
	 */
	protected function output($data, $status) {
		header('Content-type: application/javascript');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		$output = array('data'=>$data,'status'=>$status);
		echo json_encode($output);
		exit;
	}

	/**
	 * Support method for getItemStatuses() -- when presented with multiple values,
	 * pick which one(s) to send back via AJAX.
	 *
	 * @param array  $list Array of values to choose from.
	 * @param string $mode config.ini setting -- first, all or msg
	 * @param string $msg  Message to display if $mode == "msg"
	 *
	 * @return string
	 * @access private
	 */
	private function _pickValue($list, $mode, $msg)
	{
		if (!is_array($list)){
			if (is_string($list)){
				return $list;
			}else{
				return '';
			}
		}
		// Make sure array contains only unique values:
		$list = array_unique($list);

		// If there is only one value in the list, or if we're in "first" mode,
		// send back the first list value:
		if ($mode == 'first' || count($list) == 1) {
			return $list[0];
		} else if (count($list) == 0) {
			// Empty list?  Return a blank string:
			return '';
		} else if ($mode == 'all') {
			// All values mode?  Return comma-separated values:
			return implode(', ', $list);
		} else {
			// Message mode?  Return the specified message, translated to the
			// appropriate language.
			return translate($msg);
		}
	}

	/**
	 * Support method for getItemStatuses() -- process a single bibliographic record
	 * for location settings other than "group".
	 *
	 * @param array  $record            Information on items linked to a single bib
	 *                                  record
	 * @param array  $messages          Custom status HTML
	 *                                  (keys = available/unavailable)
	 * @param string $locationSetting   The location mode setting used for
	 *                                  _pickValue()
	 * @param string $callnumberSetting The callnumber mode setting used for
	 *                                  _pickValue()
	 *
	 * @return array                    Summarized availability information
	 * @access private
	 */
	private function _getItemStatus($record, $messages, $locationSetting, $callnumberSetting) {
		global $interface;
		// Summarize call number, location and availability info across all items:
		$callNumbers = $locations = array();
		$available = false;
		foreach ($record as $info) {
			// Find an available copy
			if ($info['availability']) {
				$available = true;
			}
			// Store call number/location info:
			if (isset($info['callnumber'])){
				$callNumbers[] = $info['callnumber'];
			}
			if (isset($info['location'])){
				$locations[] = $info['location'];
			}
		}

		// Determine call number string based on findings:
		$callNumber = $this->_pickValue(
		$callNumbers, $callnumberSetting, 'Multiple Call Numbers'
		);

		// Determine location string based on findings:
		$location = $this->_pickValue($locations, $locationSetting, 'Multiple Locations');

		// Send back the collected details:
		$firstRecord = reset($record);
		$id = (isset($firstRecord['id']) ? $firstRecord['id'] : '');
		$reserve = (isset($firstRecord['reserve']) ? $firstRecord['reserve'] : '');
		$interface->assign('status', $firstRecord['status']);
		if ($available){
			$message = $interface->fetch('AJAX/status-available.tpl');
		}else{
			$message = $interface->fetch('AJAX/status-unavailable.tpl');
		}
		return array(
            'id' => $id,
            'shortId' => trim($id, '.'),
            'availability' => ($available ? 'true' : 'false'),
            'availability_message' => $message, //$messages[$available ? 'available' : 'unavailable'],
            'location' => $location,
            'locationList' => false,
            'reserve' => ($reserve == 'Y' ? 'true' : 'false'),
            'reserve_message' => $reserve == 'Y' ? translate('on_reserve') : translate('Not On Reserve'),
            'callnumber' => $callNumber
		);
	}

	/**
	 * Email a list of items from the book carts.
	 *
	 * @return void
	 * @access public
	 */
	public function emailCartItems() {
		require_once ROOT_DIR . '/sys/Mailer.php';
		// Load the appropriate module based on the "type" parameter:
		global $configArray;
		$ids = $_REQUEST['id'];

		global $configArray;
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		$cartContents = array();
		if (count($ids) > 0){
			$searchObject->setQueryIDs($ids);
			$result = $searchObject->processSearch();
			$matchingRecords = $searchObject->getResultRecordSet();
			foreach ($matchingRecords as $record){
				$record['url'] = $configArray['Site']['url'] . "/Record/{$record['id']}/Home";
				$cartContents[] = $record;

			}
		}

		global $interface;

		$subject = translate("Your Book Cart Contents");
		$interface->assign('cartContents', $cartContents);
		$body = $interface->fetch('Emails/bookcart-contents.tpl');

		$mail = new VuFindMailer();
		$to = $_REQUEST['to'];
		$from = $configArray['Site']['email'];
		$result = $mail->send($to, $from, $subject, $body);

		$this->output(translate('email_success'), JSON::STATUS_OK);
	}

	function trackEvent(){
		global $analytics;
		if (!isset($_REQUEST['category']) || !isset($_REQUEST['eventAction'])){
			return 'Must provide a category and action to track an event';
		}
		$category = strip_tags($_REQUEST['category']);
		$action = strip_tags($_REQUEST['eventAction']);
		$data = isset($_REQUEST['data']) ? strip_tags($_REQUEST['data']) : '';
		$analytics->addEvent($category, $action, $data);
		return true;
	}

	function getHoursAndLocations(){
		//Get a list of locations for the current library
		global $library;
		$tmpLocation = new Location();
		$tmpLocation->libraryId = $library->libraryId;
		$tmpLocation->orderBy('displayName');
		$libraryLocations = array();
		$tmpLocation->find();
		if ($tmpLocation->N == 0){
			//Get all locations
			$tmpLocation = new Location();
			$tmpLocation->orderBy('libraryId, displayName');
			$tmpLocation->find();
		}
		while ($tmpLocation->fetch()){
			$mapAddress = urlencode(preg_replace('/\r\n|\r|\n/', '+', $tmpLocation->address));
			$clonedLocation = clone $tmpLocation;
			$hours = $clonedLocation->getHours();
			$libraryLocations[] = array(
				'name' => $tmpLocation->displayName,
				'address' => preg_replace('/\r\n|\r|\n/', '<br/>', $tmpLocation->address),
				'phone' => $tmpLocation->phone,
				'map_image' => "http://maps.googleapis.com/maps/api/staticmap?center=$mapAddress&zoom=15&size=200x200&sensor=false&markers=color:red%7C$mapAddress",
				'map_link' => "http://maps.google.com/maps?f=q&hl=en&geocode=&q=$mapAddress&ie=UTF8&z=15&iwloc=addr&om=1&t=m",
				'hours' => $hours
			);
		}

		global $interface;
		$interface->assign('libraryLocations', $libraryLocations);
		return $interface->fetch('AJAX/libraryHoursAndLocations.tpl');
	}
}