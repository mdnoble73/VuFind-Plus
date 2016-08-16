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

require_once 'DriverInterface.php';
require_once ROOT_DIR . '/Drivers/HorizonAPI.php';

class WCPL extends HorizonAPI
{

	function translateFineMessageType($code){
		switch ($code){
			case "abs":       return "Automatic Bill Sent";
			case "acr":       return "Address Correction Requested";
			case "adjcr":     return "Adjustment credit, for changed";
			case "adjdbt":    return "Adjustment debit, for changed";
			case "balance":   return "Balancing Entry";
			case "bcbr":      return "Booking Cancelled by Borrower";
			case "bce":       return "Booking Cancelled - Expired";
			case "bcl":       return "Booking Cancelled by Library";
			case "bcsp":      return "Booking Cancelled by Suspension";
			case "bct":       return "Booking Cancelled - Tardy";
			case "bn":        return "Billing Notice";
			case "chgs":      return "Charges Misc. Fees";
			case "cr":        return "Claimed Return";
			case "credit":    return "Credit";
			case "damage":    return "Damaged";
			case "dc":        return "Debt Collection";
			case "dynbhm":    return "Dynix Being Held Mail";
			case "dynbhp":    return "Dynix Being Held Phone";
			case "dynfnl":    return "Dynix Final Overdue Notice";
			case "dynhc":     return "Dynix Hold Cancelled";
			case "dynhexp":   return "Dynix Hold Expired";
			case "dynhns":    return "Dynix Hold Notice Sent";
			case "dynnot1":   return "Dynix First Overdue Notice";
			case "dynnot2":   return "Dynix Second Overdue Notice";
			case "edc":       return "Exempt from Debt Collection";
			case "fdc":       return "Force to Debt Collection";
			case "fee":       return "ILL fees/Postage";
			case "final":     return "Final Overdue Notice";
			case "finalr":    return "Final Recall Notice";
			case "fine":      return "Fine";
			case "hcb":       return "Hold Cancelled by Borrower";
			case "hcl":       return "Hold Cancelled by Library";
			case "hclr":      return "Hold Cancelled & Reinserted in";
			case "he":        return "Hold Expired";
			case "hncko":     return "Hold Notification - Deliver";
			case "hncsa":     return "Hold - from closed stack";
			case "hnmail":    return "Hold Notification - Mail";
			case "hnphone":   return "Hold Notification - Phone";
			case "ill":       return "Interlibrary Loan Notification";
			case "in":        return "Invoice";
			case "infocil":   return "Checkin Location";
			case "infocki":   return "Checkin date";
			case "infocko":   return "Checkout date";
			case "infodue":   return "Due date";
			case "inforen":   return "Renewal date";
			case "l":         return "Lost";
			case "ld":        return "Lost on Dynix";
			case "lf":        return "Found";
			case "LostPro":   return "Lost Processing Fee";
			case "lr":        return "Lost Recall";
			case "msg":       return "Message to Borrower";
			case "nocko":     return "No Checkout";
			case "Note":      return "Comment";
			case "notice1":   return "First Overdue Notice";
			case "notice2":   return "Second Overdue Notice";
			case "notice3":   return "Third Overdue Notice";
			case "noticr1":   return "First Recall Notice";
			case "noticr2":   return "Second Recall Notice";
			case "noticr3":   return "Third Recall Notice";
			case "noticr4":   return "Fourth Recall Notice";
			case "noticr5":   return "Fifth Recall Notice";
			case "nsn":       return "Never Send Notices";
			case "od":        return "Overdue Still Out";
			case "odd":       return "Overdue Still Out on Dynix";
			case "odr":       return "Recalled and Overdue Still Out";
			case "onlin":     return "Online Registration";
			case "payment":   return "Fine Payment";
			case "pcr":       return "Phone Correction Requested";
			case "priv":      return "Privacy - Family permission";
			case "rd":        return "Request Deleted";
			case "re":        return "Request Expired";
			case "recall":    return "Item is recalled before due date";
			case "refund":    return "Refund of Payment";
			case "ri":        return "Reminder Invoice";
			case "rl":        return "Requested item lost";
			case "rn":        return "Reminder Billing Notice";
			case "spec":      return "Special Message";
			case "supv":      return "See Supervisor";
			case "suspend":   return "Suspension until ...";
			case "unpd":      return "Damaged Material Replacement";
			case "waiver":    return "Waiver of Fine";
			default:
				return $code;
		}
	}

	public function translateLocation($locationCode){
		$locationCode = strtoupper($locationCode);
		$locationMap = array(
        "ADR" =>	"Athens Drive Community Library",
        "BKM"	=>	"Bookmobile",
        "CAM"	=>	"Cameron Village Regional Library",
        "CRY"	=>	"Cary Community Library",
        "DUR"	=>	"Duraleigh Road Community Library",
        "ELF"	=>	"Express Library - Fayetteville St.",
        "ERL"	=>	"East Regional Library",
        "EVA"	=>	"Eva H. Perry Regional Library",
        "FUQ"	=>	"Fuquay-Varina Community Library",
        "GRE"	=>	"Green Road Community Library",
        "HSP"	=>	"Holly Springs Community Library",
        "LEE"	=>	"Leesville Community Library",
        "NOR"	=>	"North Regional Library",
        "ORL"	=>	"Olivia Raney Local History Library",
        "RBH"	=>	"Richard B. Harrison Community Library",
        "SER"	=>	"Southeast Regional Library",
        "SGA"	=>	"Southgate Community Library",
        "WAK"	=>	"Wake Forest Community Library",
		    "WCPL"=>  "Wake County Public Libraries",
        "WEN"	=>	"Wendell Community Library",
        "WRL"	=>	"West Regional Library",
        "ZEB"	=>	"Zebulon Community Library",
		);
		return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : "Unknown" ;
	}
	public function translateCollection($collectionCode){
		$collectionCode = strtoupper($collectionCode);
		$collectionMap = array(
        'AHS000' => 'Adult Non-Fiction',
        'AHS100' => 'Adult Non-Fiction',
        'AHS200' => 'Adult Non-Fiction',
        'AHS300' => 'Adult Non-Fiction',
        'AHS400' => 'Adult Non-Fiction',
        'AHS500' => 'Adult Non-Fiction',
        'AHS600' => 'Adult Non-Fiction',
        'AHS700' => 'Adult Non-Fiction',
        'AHS800' => 'Adult Non-Fiction',
        'AHS900' => 'Adult Non-fiction',
        'AHSBIO' => 'Biography',
        'AHSFICT' => 'Fiction',
        'AHSJBIO' => 'Juvenile Biography',
        'AHSJNFI' => 'Childrens Non-Fiction',
        'AHSMYST' => 'Mystery',
        'AHSNCNF' => 'North Carolina Non-Fiction',
        'AHSPER' => 'Periodicals',
        'AHSREFR' => 'Athens High Reference',
        'AHSSCFI' => 'Science Fiction',
        'AHSSCOL' => 'Story Collection',
        'AHSTRAV' => 'Travel',
        'AHSYAFI' => 'Young Adult Fiction',
        'AHSYANF' => 'Young Adult Non-Fiction',
        'AHSYASC' => 'YA Story Collection',
        'AHSYGRA' => 'YA Graphic Novels',
        'BKMABEA' => 'Audio Books - Children',
        'BKMADUL' => 'Adult collection',
        'BKMBBOO' => 'Board Books',
        'BKMEREA' => 'Beginning Readers',
        'BKMJFIC' => 'Childrens Fiction',
        'BKMJNF' => 'Childrens Non-fiction',
        'BKMPICT' => 'Picture books',
        'BKMPTRE' => 'Bkm Parent/teacher Resources',
        'CRYCOFF' => 'Cary Children\'s Librarian Office',
        'ERLRHOM' => 'Educator Reference Collection',
        'EVAJEDU' => 'Juvenile Education Resources',
        'FA' => 'Fast Add',
        'FA-BI' => 'Fast Add',
        'FA-I' => 'Fast Add',
        'FLIPVID' => 'Employees only',
        'ILLS' => 'Ill Items',
        'LAPTOP' => 'Employees only',
        'LCDPROJ' => 'Employees only',
        'NEWAFIC' => 'New Fiction',
        'NEWANFI' => 'New Nonfiction',
        'NEWBIOG' => 'New Nonfiction',
        'NEWBUSI' => 'New Nonfiction',
        'NEWCARE' => 'New Nonfiction',
        'NEWHORR' => 'New Fiction',
        'NEWINSP' => 'New Fiction',
        'NEWMYST' => 'New Fiction',
        'NEWPARE' => 'New Nonfiction',
        'NEWROMA' => 'New Fiction',
        'NEWSFIC' => 'New Fiction',
        'ORDER' => 'Item is on order',
        'ORLANF' => 'Closed Stacks',
        'ORLATLA' => 'Reading Room Atlas Case',
        'ORLAUDI' => 'Reading Room - Circulating',
        'ORLBHX' => 'Reading Room',
        'ORLBHXS' => 'Closed Stacks',
        'ORLBIOG' => 'Closed Stacks',
        'ORLCENM' => 'Microforms Room',
        'ORLCENP' => 'Reading Room',
        'ORLCIRC' => 'Circulating Collection',
        'ORLCIVW' => 'Closed Stacks',
        'ORLCWRF' => 'Reading Room',
        'ORLDIR' => 'Closed Stacks',
        'ORLFAMS' => 'Closed Stacks',
        'ORLFICH' => 'Microforms Room',
        'ORLFILM' => 'Microforms Room',
        'ORLGENC' => 'Main Desk',
        'ORLGENE' => 'Reading Room',
        'ORLGENS' => 'Closed Stacks',
        'ORLGOVM' => 'Microforms Room',
        'ORLGOVP' => 'Closed Stacks',
        'ORLGREF' => 'Reading Room',
        'ORLMAP' => 'Map Case',
        'ORLMSS' => 'Closed Stacks',
        'ORLNCFI' => 'Closed Stacks',
        'ORLNPAP' => 'Microforms Room',
        'ORLPERI' => 'Reading Room',
        'ORLPRO' => 'Closed Stacks',
        'ORLSERI' => 'Closed Stacks',
        'ORLVALT' => 'Orl Rare Book Vault',
        'ORLVFIL' => 'Closed Stacks',
        'POPJRAD' => 'Children\'s Readers\' Advisory',
        'POPYARA' => 'Young Adult Readers\' Advisory',
        'RBHLANF' => 'Lee Non-fict - Does Not Circ',
        'RBHLBIO' => 'Lee Biography - Does Not Circulate',
        'RBHLEAS' => 'Lee Easy Bk - Does Not Circ.',
        'RBHLFIC' => 'Lee Fiction - Does Not Circ.',
        'RBHLJBI' => 'Lee Juv Biog - Does Not Circ.',
        'RBHLJFI' => 'Lee Juv Fict - Does Not Circ.',
        'RBHLJNF' => 'Lee Juv Nf - Does Not Circ',
        'RBHLRBR' => 'Rare Book Room',
        'SYSABAD' => 'Audio Books - Adult Fiction',
        'SYSABAN' => 'Audio Books - Adult Nonfiction',
        'SYSABDN' => 'Audio Books - Downloadable',
        'SYSABEA' => 'Audio Books - Children',
        'SYSABJV' => 'Audio Books - Juvenile',
        'SYSABYA' => 'Audio Books - Young Adult',
        'SYSAFIC' => 'Adult Fiction',
        'SYSANFI' => 'Adult Non-fiction',
        'SYSATLA' => 'Atlas Stand',
        'SYSBBOO' => 'Board Books',
        'SYSBCKT' => 'Book Club Kit',
        'SYSBIOG' => 'Biography',
        'SYSBKNT' => 'Book Notes',
        'SYSBSRF' => 'Business Reference',
        'SYSBUSI' => 'Business',
        'SYSCARE' => 'Careers',
        'SYSCCRF' => 'College/Career Reference',
        'SYSCFLC' => 'Children\'s Foreign Language Collection',
        'SYSCOLC' => 'College/Career',
        'SYSCOMP' => 'Computers',
        'SYSCONR' => 'Consumer Reference Table',
        'SYSEASY' => 'Picture Books',
        'SYSEBKS' => 'eBooks',
        'SYSEDUC' => 'Educator\'s Resource Collection',
        'SYSEHOL' => 'Easy Holiday',
        'SYSEKIT' => 'Easy Book Club Kit',
        'SYSEREA' => 'Beginning Readers',
        'SYSFLCO' => 'Foreign Language Collection',
        'SYSGRAF' => 'Graphic Novels',
        'SYSINSP' => 'Inspirational Fiction',
        'SYSJBIO' => 'Childrens Biography',
        'SYSJFIC' => 'Childrens Fiction',
        'SYSJGRA' => 'Childrens Graphic Novels',
        'SYSJKIT' => 'Childrens Book Club Kit',
        'SYSJMAG' => 'Juvenile Magazines',
        'SYSJNFI' => 'Childrens Non-fiction',
        'SYSJREF' => 'Childrens Reference',
        'SYSJSPA' => 'Childrens Spanish Materials',
        'SYSLANG' => 'Language Instruction',
        'SYSLARP' => 'Large Print',
        'SYSLAWG' => 'Legal Reference Guides',
        'SYSLPNF' => 'Large Print Non Fiction',
        'SYSMDRF' => 'Medical Reference Table',
        'SYSMYST' => 'Mystery',
        'SYSNCRF' => 'Nc Reference',
        'SYSPARE' => 'Parenting',
        'SYSPERI' => 'Magazines',
        'SYSPROF' => 'Professional Collection',
        'SYSRADC' => 'Reader\'s Advisory Collection',
        'SYSRDSK' => 'Ask at Reference Desk',
        'SYSREFR' => 'Reference Section',
        'SYSROMA' => 'Romance',
        'SYSSFIC' => 'Science Fiction/Fantasy/Horror',
        'SYSSPAN' => 'Spanish Language Materials',
        'SYSTKIT' => 'Childrens Travel Kit',
        'SYSTRAV' => 'Travel',
        'SYSYAFI' => 'Young Adult',
        'SYSYANF' => 'Young Adult Non Fiction',
        'SYSYGRA' => 'YA Graphic Novels',
        'UNK' => 'Unknown collection for item creation',
        'ZEBGENE' => 'Genealogy',
		);
		return isset($collectionMap[$collectionCode]) ? $collectionMap[$collectionCode] : "Unknown $collectionCode";
	}
	public function translateStatus($statusCode){
		$statusCode = strtolower($statusCode);
		$statusMap = array(
        "a" =>	"Archived",
        "b" =>	"Bindery",
        "c" =>	"Credited as Returned",
        "csa" =>	"Closed Stack",
        "dc" =>	"Display",
        "dmg" =>	"Damaged",
        "e" =>	"Item hold expired",
        "ex" =>	"Exception",
				"fd" => "Featured Display",
        "fone" =>	"Phone pickup",
        "h" =>	"Item being held",
        "i" =>	"Checked In",
        "ill" =>	"ILL - Lending",
        "int" =>	"Internet",
        "l" =>	"Long Overdue",
        "lr" =>	"Lost Recall",
        "m" =>	"Item missing",
        "me" =>	"Mending",
        "mi" =>	"Missing Inventory",
        "n" =>	"In Processing",
        "o" =>	"Checked out",
        "os" =>	"On Shelf",
        "r" =>	"On Order",
        "rb" =>	"Reserve Bookroom",
        "recall" =>	"Recall",
        "ref" =>	"Does Not Circulate",
        "rs" =>	"On Reserve Shelf",
        "rw" =>	"Reserve withdrawal",
        "s" =>	"Shelving Cart",
        "shaw" =>	"Shaw University",
        "st" =>	"Storage",
        "t" =>	"In Cataloging",
        "tc" =>	"Transit Recall",
        "th" =>	"Transit Request",
        "tr" =>	"Transit",
        "trace" =>	"No Longer Avail.",
        "ufa" =>	"user fast added item",
        "weed" =>	"Items for deletion",
		);
		return isset($statusMap[$statusCode]) ? $statusMap[$statusCode] : 'Unknown (' . $statusCode . ')';
	}
	public function getLocationMapLink($locationCode){
		$locationCode = strtolower($locationCode);
		$locationMap = array();
		return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : '' ;
	}

	public function getLibraryHours($locationId, $timeToCheck){
		return null;
	}

	function selfRegister(){
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

		//Start at My Account Page
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$this->selfRegProfile}&menu=account";
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
		$logger->log("Loading Full Record $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR_Singleton::raiseError('Could not load session information from page.');
		}

		//Login by posting username and password
		$post_data = array(
      'aspect' => 'overview',
      'button' => 'New User',
      'login_prompt' => 'true',
      'menu' => 'account',
			'newuser_prompt' => 'true',
      'profile' => $this->selfRegProfile,
      'ri' => '', 
      'sec1' => '',
      'sec2' => '',
      'session' => $sessionId,
		);
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		$firstName = strip_tags($_REQUEST['firstname']);
		$lastName = strip_tags($_REQUEST['lastname']);
		$streetAddress = strip_tags($_REQUEST['address1']);
		$apartment = strip_tags($_REQUEST['address2']);
		$citySt = strip_tags($_REQUEST['city_st']);
		$zip = strip_tags($_REQUEST['postal_code']);
		$email = strip_tags($_REQUEST['email_address']);
		$sendNoticeBy = strip_tags($_REQUEST['send_notice_by']);
		$pin = strip_tags($_REQUEST['pin#']);
		$confirmPin = strip_tags($_REQUEST['confirmpin#']);
		$phone = strip_tags($_REQUEST['phone_no']);

		//Register the patron
		$post_data = array(
      'address1' => $streetAddress,
		  'address2' => $apartment,
			'aspect' => 'basic',
			'pin#' => $pin,
			'button' => 'I accept',
			'city_st' => $citySt,
			'confirmpin#' => $confirmPin,
			'email_address' => $email,
			'firstname' => $firstName,
			'ipp' => 20,
			'lastname' => $lastName,
			'menu' => 'account',
			'newuser_info' => 'true',
			'npp' => 30,
			'postal_code' => $zip,
      'phone_no' => $phone,
      'profile' => $this->selfRegProfile,
			'ri' => '',
			'send_notice_by' => $sendNoticeBy,
			'session' => $sessionId,
			'spp' => 20
		);

		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url . '#focus');
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		//Get the temporary barcode from the page
		if (preg_match('/Here is your temporary barcode\\. Use it for future authentication:&nbsp;([\\d-]+)/s', $sresult, $regs)) {
			$tempBarcode = $regs[1];
			//Append the library prefix to the card number
			$tempBarcode = '22046' . $tempBarcode;
			$success = true;
		}else{
			$success = false;
		}

		unlink($cookie);

		return array(
		  'barcode' => $tempBarcode,
		  'success'  => $success
		);

	}
	private function getBaseWebServiceUrl() {
		global $configArray;
		if (!empty($this->accountProfile->patronApiUrl)) {
			$webServiceURL = $this->accountProfile->patronApiUrl;
		} elseif (!empty($configArray['Catalog']['webServiceUrl'])) {
			$webServiceURL = $configArray['Catalog']['webServiceUrl'];
		} else {
			global $logger;
			$logger->log('No Web Service URL defined in Horizon API Driver', PEAR_LOG_CRIT);
			return null;
		}

		$urlParts = parse_url($webServiceURL);
		$baseWebServiceUrl = $urlParts['scheme']. '://'. $urlParts['host']. (!empty($urlParts['port']) ? ':'. $urlParts['port'] : '');

		return $baseWebServiceUrl;
	}

	function updatePin($user, $oldPin, $newPin, $confirmNewPin){
		global $configArray;

		//Log the user in
		list($userValid, $sessionToken) = $this->loginViaWebService($user->cat_username, $user->cat_password);
		if (!$userValid){
			return 'Sorry, it does not look like you are logged in currently.  Please login and try again';
		}

		$updatePinUrl = $this->getBaseWebServiceUrl() . '/hzws/v1/user/patron/changeMyPin';
		$jsonParameters = array(
			'currentPin' => $oldPin,
			'newPin' => $newPin,
		);
		$updatePinResponse = $this->getWebServiceResponseUpdated($updatePinUrl, $jsonParameters, $sessionToken);
		if (isset($updatePinResponse['messageList'])) {
			$errors = '';
			foreach ($updatePinResponse['messageList'] as $errorMessage) {
				$errors .= $errorMessage['message'] . ';';
			}
			global $logger;
			$logger->log('WCPL Driver error updating user\'s Pin :'.$errors, PEAR_LOG_ERR);
			return 'Sorry, we encountered an error while attempting to update your pin. Please contact your local library.';
		} elseif ($updatePinResponse['sessionToken'] == $sessionToken){
			// Success response isn't particularly clear, but returning the session Token seems to indicate the pin updated. plb 8-15-2016
			$user->cat_password = $newPin;
			$user->update();
			return "Your pin number was updated successfully.";
		}else{
			return "Sorry, we could not update your pin number. Please try again later.";
		}
	}


	function resetPin($user, $newPin, $resetToken=null){
		if (empty($resetToken)) {
			global $logger;
			$logger->log('No Reset Token passed to resetPin function', PEAR_LOG_ERR);
			return array(
				'error' => 'Sorry, we could not update your pin. The reset token is missing. Please try again later'
			);
		}

		$changeMyPinAPIUrl = $this->getBaseWebServiceUrl() . '/hzws/v1/user/patron/changeMyPin';
		$jsonParameters = array(
			'resetPinToken' => $resetToken,
			'newPin' => $newPin,
		);
		$changeMyPinResponse = $this->getWebServiceResponseUpdated($changeMyPinAPIUrl, $jsonParameters);
		if (isset($changeMyPinResponse['messageList'])) {
			$errors = '';
			foreach ($changeMyPinResponse['messageList'] as $errorMessage) {
				$errors .= $errorMessage['message'] . ';';
			}
			global $logger;
			$logger->log('WCPL Driver error updating user\'s Pin :'.$errors, PEAR_LOG_ERR);
			return array(
				'error' => 'Sorry, we encountered an error while attempting to update your pin. Please contact your local library.'
			);
		} elseif (!empty($changeMyPinResponse['sessionToken'])){
			if ($user->username == $changeMyPinResponse['patronKey']) { // Check that the ILS user matches the Pika user
				$user->cat_password = $newPin;
				$user->update();
			}
			return array(
				'success' => true,
			);
//			return "Your pin number was updated successfully.";
		}else{
			return array(
				'error' => "Sorry, we could not update your pin number. Please try again later."
			);
		}
	}



	// Newer Horizon API version
	public function emailPin($barcode)
	{
		if (empty($barcode)) {
			$barcode = $_REQUEST['barcode'];
		}

		$patron = new User;
		$patron->get('cat_username', $barcode);
		if (!empty($patron->id)) {
			global $configArray;
			$userID = $patron->id;

			//email the pin to the user
			$resetPinAPIUrl = $this->getBaseWebServiceUrl() . '/hzws/v1/user/patron/resetMyPin';
			$jsonPOST       = array(
				'login'       => $barcode,
				'resetPinUrl' => $configArray['Site']['url'] . '/MyAccount/ResetPin?resetToken=<RESET_PIN_TOKEN>&uid=' . $userID
			);

			$resetPinResponse = $this->getWebServiceResponseUpdated($resetPinAPIUrl, $jsonPOST);
			// Reset Pin Response is empty JSON on success.

			if ($resetPinResponse === array() && !isset($resetPinResponse['messageList'])) {
				return array(
					'success' => true,
				);
			} else {
				$result = array(
					'error' => "Sorry, we could not e-mail your pin to you.  Please visit the library to reset your pin."
				);
				if (isset($resetPinResponse['messageList'])) {
					$errors = '';
					foreach ($resetPinResponse['messageList'] as $errorMessage) {
						$errors .= $errorMessage['message'] . ';';
					}
					global $logger;
					$logger->log('WCPL Driver error updating user\'s Pin :' . $errors, PEAR_LOG_ERR);
				}
				return $result;
			}
		} else {
			return array(
				'error' => 'Sorry, we did not find the card number you entered.'
			);
		}
	}


	/**
	 *  Handles API calls to the newer Horizon APIs.
	 *
	 * @param $url
	 * @param array $post  POST variables get encoded as JSON
	 * @return bool|mixed|SimpleXMLElement
	 */
	public function getWebServiceResponseUpdated($url, $post = array(), $sessionToken = ''){
		global $configArray;
		$requestHeaders = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'SD-Originating-App-Id: Pika',
			'x-sirs-clientId: ' . $configArray['Catalog']['clientId'],
		);

		if (!empty($sessionToken)) {
			$requestHeaders[] = "x-sirs-sessionToken: $sessionToken";
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		if (!empty($post)) {
			$post = json_encode($post);  // Turn Post Fields into JSON Data
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLINFO_HEADER_OUT, true); // enables request headers for curl_getinfo()
		$curlResponse = curl_exec($ch);

		$info = curl_getinfo($ch);  // for debugging curl calls

		curl_close($ch);

		if ($curlResponse !== false && $curlResponse !== 'false'){
			$response = json_decode($curlResponse, true);
			if (json_last_error() == JSON_ERROR_NONE) {
				return $response;
			} else {
				global $logger;
				$logger->log('Error Parsing JSON response in WCPL Driver: ' . json_last_error_msg(), PEAR_LOG_ERR);
				return false;
			}


		}else{
			global $logger;
			$logger->log('Curl problem in getWebServiceResponseUpdated', PEAR_LOG_WARNING);
			return false;
		}
	}


}
