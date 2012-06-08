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
require_once 'Drivers/Millennium.php';

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
class EINetwork extends MillenniumDriver{
	/**
	 * Login with barcode and pin
	 * 
	 * @see Drivers/Millennium::patronLogin()
	 */
	public function patronLogin($barcode, $pin)
	{
		global $configArray;
		global $memcache;
		global $timer;

		//Load the raw information about the patron
		$patronDump = $this->_getPatronDump($barcode);

		//Check the pin number that was entered
		$pin = urlencode($pin);
		if (strlen($barcode) < 14) { $barcode = ".p" . $barcode; }
		$host=$configArray['OPAC']['patron_host'];
		$apiurl = $host . "/PATRONAPI/$barcode/$pin/pintest";
		
		$api_contents = file_get_contents($apiurl);
		$api_contents = trim(strip_tags($api_contents));
	
		$api_array_lines = explode("\n", $api_contents);
		foreach ($api_array_lines as $api_line) {
			$api_line_arr = explode("=", $api_line);
			$api_data[trim($api_line_arr[0])] = trim($api_line_arr[1]);
		}
	
		if (!isset($api_data['RETCOD'])){
			$userValid = false;
		}else if ($api_data['RETCOD'] == 1){
			$userValid = false;
		}else{
			$userValid = true;
		}

		//Create a variety of possible name combinations for testing purposes.
		$Fullname = str_replace(","," ",$patronDump['PATRN_NAME']);
		$Fullname = str_replace(";"," ",$Fullname);
		$Fullname = str_replace(";","'",$Fullname);
		$allNameComponents = preg_split('^[\s-]^', strtolower($Fullname));
		$nameParts = explode(' ',$Fullname);
		$lastname = strtolower($nameParts[0]);
		$middlename = isset($nameParts[2]) ? strtolower($nameParts[2]) : '';
		$firstname = isset($nameParts[1]) ? strtolower($nameParts[1]) : $middlename;

		if ($userValid){
			$user = array(
                'id'        => $barcode,
                'username'  => $patronDump['RECORD_#'],
                'firstname' => $firstname,
                'lastname'  => $lastname,
                'fullname'  => $Fullname,     //Added to array for possible display later. 
                'cat_username' => $barcode, //Should this be $Fullname or $patronDump['PATRN_NAME']
                'cat_password' => $pin,

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
	
	protected function _getLoginFormValues($patronInfo, $admin = false){
		global $user;
		$loginData = array();
		if ($admin){
			global $configArray;
			$loginData['name'] = $configArray['Catalog']['ils_admin_user'];
			$loginData['code'] = $configArray['Catalog']['ils_admin_pwd'];
		}else{
			$loginData['pin'] = $user->cat_password;
			$loginData['code'] = $user->cat_username;
		}
		$loginData['submit'] = 'submit';
		return $loginData;
	}
	
	protected function _getBarcode(){
		global $user;
		return $user->cat_username;
	}
	
	protected function _getHoldResult($holdResultPage){
		$hold_result = array();
		//Get rid of header and footer information and just get the main content
		$matches = array();

		if (preg_match('/success/', $holdResultPage)){
			//Hold was successful
			$hold_result['result'] = true;
			if (!isset($reason) || strlen($reason) == 0){
				$hold_result['message'] = 'Your hold was placed successfully';
			}else{
				$hold_result['message'] = $reason;
			}
		}else if (preg_match('/<font color="red" size="\+2">(.*?)<\/font>/is', $holdResultPage, $reason)){
			//Got an error message back.
			$hold_result['result'] = false;
			$hold_result['message'] = $reason[1];
		}else{
			//Didn't get a reason back.  This really shouldn't happen.
			$hold_result['result'] = false;
			$hold_result['message'] = 'Did not receive a response from the circulation system.  Please try again in a few minutes.';
		}

		return $hold_result;
	}
	
	public function updatePatronInfo($patronId){
		global $user;
		global $configArray;
		$logger = new Logger();

		//Setup the call to Millennium
		$id2= $patronId;
		$patronDump = $this->_getPatronDump($this->_getBarcode());
		$logger->log("1 Patron phone number = " . $patronDump['TELEPHONE'], PEAR_LOG_INFO);

		$this->_updateVuFindPatronInfo($patronId);
		
		//Update profile information
		$extraPostInfo = array();
		$extraPostInfo['tele1'] = $_REQUEST['phone'];
		$extraPostInfo['email'] = $_REQUEST['email'];

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
		$post_data = $this->_getLoginFormValues($patronDump);
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
		$memcache->delete("patron_dump_{$this->_getBarcode()}");
		usleep(500);
		$logger->log("Patron phone number = " . $patronDump['TELEPHONE']);

		//Should get Patron Information Updated on success
		if (preg_match('/Patron information updated/', $sresult)){
			$patronDump = $this->_getPatronDump($this->_getBarcode());
			$logger->log("2 Patron phone number = " . $patronDump['TELEPHONE'], PEAR_LOG_INFO);
			$memcache->delete("patron_dump_{$this->_getBarcode()}");
			usleep(500);
			$user->phone = $_REQUEST['phone'];
			$user->email = $_REQUEST['email'];
			//Update the serialized instance stored in the session
			$_SESSION['userinfo'] = serialize($user);
			return true;
		}else{
			return false;
		}

	}
}