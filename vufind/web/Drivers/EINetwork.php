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
			$loginData['code'] = $patronInfo['P_BARCODE'];
		}
		$loginData['submit'] = 'submit';
		return $loginData;
	}
	
	protected function _getBarcode(){
		global $user;
		return $user->cat_username;
	}
}