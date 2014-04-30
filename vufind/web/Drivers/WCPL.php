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

require_once 'Interface.php';
require_once ROOT_DIR . '/sys/SIP2.php';
require_once ROOT_DIR . '/Drivers/Horizon.php';

class WCPL extends Horizon
{

	function __construct(){
		parent::__construct();
	}

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

		$firstName = strip_tags($_REQUEST['firstName']);
		$lastName = strip_tags($_REQUEST['lastName']);
		$streetAddress = strip_tags($_REQUEST['streetAddress']);
		$apartment = strip_tags($_REQUEST['apartment']);
		$citySt = strip_tags($_REQUEST['citySt']);
		$zip = strip_tags($_REQUEST['zip']);
		$email = strip_tags($_REQUEST['email']);
		$sendNoticeBy = strip_tags($_REQUEST['sendNoticeBy']);
		$pin = strip_tags($_REQUEST['pin']);
		$confirmPin = strip_tags($_REQUEST['confirmPin']);
		$phone = strip_tags($_REQUEST['phone']);

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
			$tempBarcode = '220460' . $tempBarcode;
			$success = true;
		}else{
			$success = false;
		}

		unlink($cookie);

		return array(
		  'tempBarcode' => $tempBarcode,
		  'result'  => $success
		);

	}

	public function patronLogin($username, $password){
		global $logger;
		if ($this->useDb){
			$logger->log("Starting patron login", PEAR_LOG_DEBUG);
			$borrowerNumberSql = "select borrower# from borrow_barcode where borrower_barcode='$username'";
			try {
				$borrowerNumberRS = $this->_query($borrowerNumberSql);
				if ($this->_num_rows($borrowerNumberRS) == 1){
					$borrowerNumberRow = $this->_fetch_assoc($borrowerNumberRS);
					$borrowerNumber = $borrowerNumberRow['borrower#'];
					$borrowerPinSql =  "select pin# from borrower where borrower#=$borrowerNumber";
					$borrowerPinRS = $this->_query($borrowerPinSql);
					if ($this->_num_rows($borrowerPinRS) == 1){
						$borrowerPinRow = $this->_fetch_assoc($borrowerPinRS);
						if ($borrowerPinRow['pin#'] == $password){
							$user = new User();
							$user->username = $username;

							//The user is valid, get information about the patron
							$basicInfoSql = "select name, pin#, btype, location,expiration_date from borrower where borrower# = $borrowerNumber";
							$basicInfoRS = $this->_query($basicInfoSql);
							$basicInfoRow = $this->_fetch_assoc($basicInfoRS);
							$fullName = $basicInfoRow['name'];
							$firstname = trim(substr($fullName, 1 + strripos($fullName, ',')));
							$lastname = trim(substr($fullName, 0, strripos($fullName, ',')));

							$contactInfoSql = "select address1, address2, address3, city_st, postal_code, email_address from borrower_address where borrower# = $borrowerNumber";
							$contactInfoRS = $this->_query($contactInfoSql);
							$contactInfoRow = $this->_fetch_assoc($contactInfoRS);

							$user->email = $contactInfoRow['email_address'];
							$user->major = 'null';
							$user->college = 'null';

							$phoneInfoSql = "select phone_no from borrower_phone where borrower#=$borrowerNumber";
							$phoneInfoRS = $this->_query($phoneInfoSql);
							if ($this->_num_rows($phoneInfoRS) == 1){
								$phoneInfoRow = $this->_fetch_assoc($phoneInfoRS);
								$user->phone = $phoneInfoRow['phone_no'];
							}

							$user->patronType = $basicInfoRow['btype'];

							//Update that the user authenticated
							$updateBorrowerAuthSql = "update borrower set last_authentication_date = datediff(dd, '1 jan 1970', getdate()) and borrower# = $borrowerNumber";
							$this->_query($updateBorrowerAuthSql);

							$userArray = array(
									'id'        => $borrowerNumber,
									'username'  => $username,
									'firstname' => $firstname,
									'lastname'  => $lastname,
									'fullname'  => $fullName,
									'cat_username' => $username,
									'cat_password' => $password,
									'displayName' => $fullName,
									'email' => $contactInfoRow['email_address'],
									'major' => null,
									'college' => null,
									'patronType' => $basicInfoRow['btype']
							);
							return $userArray;
						}else{
							$logger->log("Pin number was incorrect", PEAR_LOG_WARNING);
						}
					}else{
						$logger->log("Could not find pin for borrower $borrowerNumber", PEAR_LOG_WARNING);
					}
				}else{
					$logger->log("Could not find patron with username $username", PEAR_LOG_WARNING);
				}
				//User was not valid
				return null;
			} catch (PDOException $e) {
				return new PEAR_Error($e->getMessage());
			}
		}else{
			return parent::patronLogin($username, $password);
		}
	}

	private $patronProfiles = array();
	public function getMyProfile($patron) {
		global $timer;
		global $user;
		if ($this->useDb){
			//Get profile information from the database, borrower number is patron id
			if (is_object($patron)){
				$patron = get_object_vars($patron);
				$borrowerNumber = $patron['id'];
			}else{
				$borrowerNumber= $patron['id'];
			}
			if (array_key_exists($borrowerNumber, $this->patronProfiles) && !isset($_REQUEST['reload'])){
				$timer->logTime('Retrieved Cached Profile for Patron');
				return $this->patronProfiles[$borrowerNumber];
			}

			$basicInfoSql = "select name, pin#, btype, location,expiration_date from borrower where borrower# = $borrowerNumber";
			$basicInfoRS = $this->_query($basicInfoSql);
			$basicInfoRow = $this->_fetch_assoc($basicInfoRS);

			$contactInfoSql = "select address1, address2, address3, city_st, postal_code, email_address from borrower_address where borrower# = $borrowerNumber";
			$contactInfoRS = $this->_query($contactInfoSql);
			$contactInfoRow = $this->_fetch_assoc($contactInfoRS);

			//TODO: Split city and state
			$city = $contactInfoRow['city_st'];
			$state = $contactInfoRow['city_st'];

			//TODO: Load fines
			$fines = 0;

			//Load additional expiration info
			$timeExpire = $this->addDays('1970-01-01', $basicInfoRow['expiration_date']);
			$timeNow = time();
			$timeToExpire = $timeExpire - $timeNow;
			if ($timeToExpire <= 30 * 24 * 60 * 60){
				$expireClose = 1;
			}else{
				$expireClose = 0;
			}

			//Load location information
			$homeBranchCode = $basicInfoRow['location'];
			$homeBranchCode = str_replace('+', '', $homeBranchCode);
			//Translate home branch to plain text
			$location = new Location();
			$location->whereAdd("code = '$homeBranchCode'");
			$location->find(1);

			if ($user) {
				if ($user->homeLocationId == 0 && isset($location)) {
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
			}

			//Load transaction counts
			$availableHoldsSql = "select count(*) as numHolds from request where request_status= 1 and borrower#= $$borrowerNumber";
			$availableHoldsRS = $this->_query($availableHoldsSql);
			$availableHoldsRow = $this->_fetch_assoc($availableHoldsRS);
			$numHoldsAvailable = $availableHoldsRow['numHolds'];

			$unavailableHoldsSql = "select count(*) as numHolds from request where request_status in (0,2) and borrower#= $$borrowerNumber";
			$unavailableHoldsRS = $this->_query($unavailableHoldsSql);
			$unavailableHoldsRow = $this->_fetch_assoc($unavailableHoldsRS);
			$numHoldsRequested = $unavailableHoldsRow['numHolds'];

			$checkoutsSql = "select count(*) as numCheckouts from circ where borrower#= $$borrowerNumber";
			$checkoutsRS = $this->_query($checkoutsSql);
			$checkoutsRow = $this->_fetch_assoc($checkoutsRS);
			$numCheckedOut = $availableHoldsRow['numCheckouts'];

			$profile = array('lastname' => $patron['lastname'],
					'firstname' => $patron['firstname'],
					'fullname' => $patron['fullname'],
					'address1' => $contactInfoRow['address1'],
					'address2' => $contactInfoRow['address2'],
					'city' => $city,
					'state' => $state,
					'zip'=> $contactInfoRow['postal_code'],
					'email' => $patron['email'] ,
					'overdriveEmail' => ($user) ? $user->overdriveEmail : (isset($patronDump) && isset($patronDump['EMAIL_ADDR']) ? $patronDump['EMAIL_ADDR'] : ''),
					'promptForOverdriveEmail' => $user ? $user->promptForOverdriveEmail : 1,
					'phone' => $user->phone,
					'workPhone' => '',
					'mobileNumber' => '',
					'fines' => $fines,
					'finesval' => $fines,
					'expires' => date('M-j-Y'),
					'expireclose' => $expireClose,
					'homeLocationCode' => isset($homeBranchCode) ? trim($homeBranchCode) : '',
					'homeLocationId' => isset($location) ? $location->locationId : 0,
					'homeLocation' => isset($location) ? $location->displayName : '',
					'myLocation1Id' => ($user) ? $user->myLocation1Id : -1,
					'myLocation1' => isset($myLocation1) ? $myLocation1->displayName : '',
					'myLocation2Id' => ($user) ? $user->myLocation2Id : -1,
					'myLocation2' => isset($myLocation2) ? $myLocation2->displayName : '',
					'numCheckedOut' => $numCheckedOut,
					'numHolds' => $numHoldsAvailable + $numHoldsRequested,
					'numHoldsAvailable' => $numHoldsAvailable,
					'numHoldsRequested' => $numHoldsRequested,
					'bypassAutoLogout' => ($user) ? $user->bypassAutoLogout : 0,
					'ptype' => $patron['patronType'],
					'notices' => '',
					'web_note' => '',
			);

			//Get eContent info as well
			require_once(ROOT_DIR . '/Drivers/EContentDriver.php');
			$eContentDriver = new EContentDriver();
			$eContentAccountSummary = $eContentDriver->getAccountSummary();
			$profile = array_merge($profile, $eContentAccountSummary);

			require_once(ROOT_DIR . '/Drivers/OverDriveDriverFactory.php');
			$overDriveDriver = OverDriveDriverFactory::getDriver();
			if ($overDriveDriver->isUserValidForOverDrive($user)){
				$overDriveSummary = $overDriveDriver->getOverDriveSummary($user);
				$profile['numOverDriveCheckedOut'] = $overDriveSummary['numCheckedOut'];
				$profile['numOverDriveHoldsAvailable'] = $overDriveSummary['numAvailableHolds'];
				$profile['numOverDriveHoldsRequested'] = $overDriveSummary['numUnavailableHolds'];
				$profile['canUseOverDrive'] = true;
			}else{
				$profile['numOverDriveCheckedOut'] = 0;
				$profile['numOverDriveHoldsAvailable'] = 0;
				$profile['numOverDriveHoldsRequested'] = 0;
				$profile['canUseOverDrive'] = false;
			}

			$profile['numCheckedOutTotal'] = $profile['numCheckedOut'] + $profile['numOverDriveCheckedOut'] + $eContentAccountSummary['numEContentCheckedOut'];
			$profile['numHoldsAvailableTotal'] = $profile['numHoldsAvailable'] + $profile['numOverDriveHoldsAvailable'] + $eContentAccountSummary['numEContentAvailableHolds'];
			$profile['numHoldsRequestedTotal'] = $profile['numHoldsRequested'] + $profile['numOverDriveHoldsRequested'] + $eContentAccountSummary['numEContentUnavailableHolds'];
			$profile['numHoldsTotal'] = $profile['numHoldsAvailableTotal'] + $profile['numHoldsRequestedTotal'];

			//Get a count of the materials requests for the user
			if ($user){
				$materialsRequest = new MaterialsRequest();
				$materialsRequest->createdBy = $user->id;
				$homeLibrary = Library::getPatronHomeLibrary();
				$statusQuery = new MaterialsRequestStatus();
				$statusQuery->isOpen = 1;
				$statusQuery->libraryId = $homeLibrary->libraryId;
				$materialsRequest->joinAdd($statusQuery);
				$materialsRequest->find();
				$profile['numMaterialsRequests'] = $materialsRequest->N;
			}

			$timer->logTime("Got Patron Profile");
			$this->patronProfiles[$patron['id']] = $profile;
			return $profile;
		}else{
			return parent::getMyProfile($patron);
		}
	}
}
