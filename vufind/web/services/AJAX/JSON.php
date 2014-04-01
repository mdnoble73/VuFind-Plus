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

class AJAX_JSON extends Action {

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

	function trackEvent(){
		global $analytics;
		if (!isset($_REQUEST['category']) || !isset($_REQUEST['eventAction'])){
			return 'Must provide a category and action to track an event';
		}
		$analytics->enableTracking();
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
		$tmpLocation->showInLocationsAndHoursList = 1;
		$tmpLocation->orderBy('displayName');
		$libraryLocations = array();
		$tmpLocation->find();
		if ($tmpLocation->N == 0){
			//Get all locations
			$tmpLocation = new Location();
			$tmpLocation->showInLocationsAndHoursList = 1;
			$tmpLocation->orderBy('displayName');
			$tmpLocation->find();
		}
		while ($tmpLocation->fetch()){
			$mapAddress = urlencode(preg_replace('/\r\n|\r|\n/', '+', $tmpLocation->address));
			$clonedLocation = clone $tmpLocation;
			$hours = $clonedLocation->getHours();
			foreach ($hours as $key => $hourObj){
				if (!$hourObj->closed){
					$hourString = $hourObj->open;
					list($hour, $minutes) = explode(':', $hourString);
					if ($hour < 12){
						$hourObj->open .= ' AM';
					}elseif ($hour == 12){
						$hourObj->open = 'Noon';
					}elseif ($hour == 24){
						$hourObj->open = 'Midnight';
					}else{
						$hour -= 12;
						$hourObj->open = "$hour:$minutes PM";
					}
					$hourString = $hourObj->close;
					list($hour, $minutes) = explode(':', $hourString);
					if ($hour < 12){
						$hourObj->close .= ' AM';
					}elseif ($hour == 12){
						$hourObj->close = 'Noon';
					}elseif ($hour == 24){
						$hourObj->close = 'Midnight';
					}else{
						$hour -= 12;
						$hourObj->close = "$hour:$minutes PM";
					}
				}
				$hours[$key] = $hourObj;
			}
			$libraryLocations[] = array(
				'id' => $tmpLocation->locationId,
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