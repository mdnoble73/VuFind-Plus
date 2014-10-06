<?php
/**
 * Catalog Driver for Aspencat libraries based on Koha
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/3/14
 * Time: 5:51 PM
 */
require_once ROOT_DIR . '/Drivers/Interface.php';

class Aspencat implements DriverInterface{

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
		// TODO: Implement getItemsFast() method.
	}

	public function getStatus($id) {
		// TODO: Implement getStatus() method.
	}

	public function getStatuses($ids) {
		// TODO: Implement getStatuses() method.
	}

	public function getHolding($id) {
		// TODO: Implement getHolding() method.
	}

	public function getPurchaseHistory($id) {
		// TODO: Implement getPurchaseHistory() method.
	}

	public function getMyProfile($patron, $forceReload = false) {
		if (is_object($patron)){
			$patron = get_object_vars($patron);
			$userId = $patron['id'];
			$id2 = $patron['cat_password'];
		}else{
			global $user;
			$userId = $user->id;
			$id2= $patron['cat_password'];
		}

		$profile = array('lastname' => $patron['lastname'],
			'firstname' => $patron['firstname'],
			'fullname' => $patron['firstname'] . ' ' . $patron['lastname'],
			'address1' => '',
			'address2' => '',
			'city' => '',
			'state' => '',
			'zip'=> '',
			/*'email' => ($user && $user->email) ? $user->email : (isset($patronDump) && isset($patronDump['EMAIL_ADDR']) ? $patronDump['EMAIL_ADDR'] : '') ,
			'overdriveEmail' => ($user) ? $user->overdriveEmail : (isset($patronDump) && isset($patronDump['EMAIL_ADDR']) ? $patronDump['EMAIL_ADDR'] : ''),
			'promptForOverdriveEmail' => $user ? $user->promptForOverdriveEmail : 1,
			'phone' => (isset($patronDump) && isset($patronDump['TELEPHONE'])) ? $patronDump['TELEPHONE'] : (isset($patronDump['HOME_PHONE']) ? $patronDump['HOME_PHONE'] : ''),
			'workPhone' => (isset($patronDump) && isset($patronDump['G/WK_PHONE'])) ? $patronDump['G/WK_PHONE'] : '',
			'mobileNumber' => (isset($patronDump) && isset($patronDump['MOBILE_NO'])) ? $patronDump['MOBILE_NO'] : '',
			'fines' => isset($patronDump) ? $patronDump['MONEY_OWED'] : '0',
			'finesval' => $finesVal,
			'expires' => isset($patronDump) ? $patronDump['EXP_DATE'] : '',
			'expireclose' => $expireClose,
			'homeLocationCode' => isset($homeBranchCode) ? trim($homeBranchCode) : '',
			'homeLocationId' => isset($location) ? $location->locationId : 0,
			'homeLocation' => isset($location) ? $location->displayName : '',
			'myLocation1Id' => ($user) ? $user->myLocation1Id : -1,
			'myLocation1' => isset($myLocation1) ? $myLocation1->displayName : '',
			'myLocation2Id' => ($user) ? $user->myLocation2Id : -1,
			'myLocation2' => isset($myLocation2) ? $myLocation2->displayName : '',
			'numCheckedOut' => isset($patronDump) ? $patronDump['CUR_CHKOUT'] : '?',
			'numHolds' => isset($patronDump) ? (isset($patronDump['HOLD']) ? count($patronDump['HOLD']) : 0) : '?',
			'numHoldsAvailable' => $numHoldsAvailable,
			'numHoldsRequested' => $numHoldsRequested,
			'bypassAutoLogout' => ($user) ? $user->bypassAutoLogout : 0,
			'ptype' => ($user && $user->patronType) ? $user->patronType : (isset($patronDump) ? $patronDump['P_TYPE'] : 0),
			'notices' => isset($patronDump) ? $patronDump['NOTICE_PREF'] : '-',
			'web_note' => isset($patronDump) ? (isset($patronDump['WEB_NOTE']) ? $patronDump['WEB_NOTE'] : '') : '',*/
		);
		return $patron;
	}

	public function patronLogin($username, $password) {
		//TODO: Actual login with Koha
		//The catalog is offline, check the database to see if the user is valid
		global $timer;
		$user = new User();
		$user->cat_password = $password;
		if ($user->find(true)){
			$userValid = false;
			if ($user->cat_username){
				$userValid = true;
			}
			if ($userValid){
				$returnVal = array(
					'id'        => $password,
					'username'  => $user->username,
					'firstname' => $user->firstname,
					'lastname'  => $user->lastname,
					'fullname'  => $user->firstname . ' ' . $user->lastname,     //Added to array for possible display later.
					'cat_username' => $username, //Should this be $Fullname or $patronDump['PATRN_NAME']
					'cat_password' => $password,

					'email' => $user->email,
					'major' => null,
					'college' => null,
					'patronType' => $user->patronType,
					'web_note' => translate('The catalog is currently down.  You will have limited access to circulation information.'));
				$timer->logTime("patron logged in successfully");
				return $returnVal;
			} else {
				$timer->logTime("patron login failed");
				return null;
			}
		} else {
			$timer->logTime("patron login failed");
			return null;
		}
	}
}