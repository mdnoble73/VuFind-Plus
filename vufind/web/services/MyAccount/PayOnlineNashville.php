<?php

/**
 * Nashville online fines and fees payment processing
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * @author Niles Ingalls <ningalls@ena.com>
 * @author James Staub <james.staub@nashville.gov>
 * Date: 2/23/2016
 * Time: 3:30 PM
 */

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class PayOnlineNashville extends Action{

	protected $uri;
	protected $hostname;
	protected $username;
	protected $password;
	protected $port;
	protected $path;
	protected $cc_host;
	protected $appId;
	var $cc_ConvenienceFee;
	var $cc_fakeit;
	var $cc_fakeit_result;
	var $sqldb;
	var $nplwrapper;
	var $cc_number;
	var $cc_month;
	var $cc_year;
	var $cc_cvv;

	function __construct() {
		global $configArray;
		$this->uri = $configArray['NashvilleOnlinePayments']['uri'];
		$this->hostname = $configArray['NashvilleOnlinePayments']['hostname'];
		$this->username = $configArray['NashvilleOnlinePayments']['username'];
		$this->password = $configArray['NashvilleOnlinePayments']['password'];
		$this->port = $configArray['NashvilleOnlinePayments']['port'];
		$this->path = $configArray['NashvilleOnlinePayments']['path'];
		$this->cc_host = $configArray['NashvilleOnlinePayments']['cc_host'];
		$this->appId = $configArray['NashvilleOnlinePayments']['appId'];
		$this->cc_ConvenienceFee = $configArray['NashvilleOnlinePayments']['cc_ConvenienceFee'];
		$this->cc_fakeit = $configArray['NashvilleOnlinePayments']['cc_fakeit']; // fake our CC transaction (For testing)
		$this->cc_fakeit_result = $configArray['NashvilleOnlinePayments']['cc_fakeit_result']; // fake our CC transaction result (For testing)
		$this->sqldb = $configArray['NashvilleOnlinePayments']['sqldb'];
		$this->nplwrapper = $configArray['NashvilleOnlinePayments']['nplwrapper'];
	}

	function launch() {
		global $interface;
		global $user;
		global $configArray;

		$this->cc_number = $_POST['payment']['cc'];
		$this->cc_month = $_POST['payment']['cc_month'];
		$this->cc_year = $_POST['payment']['cc_year'];
		$this->cc_cvv = $_POST['payment']['cc_cvv'];
		$this->cc_zipcode = $_POST['payment']['zipcode'];
		$this->cc_fullname = $_POST['payment']['fullname'];

		//Do the actual processing here

		$this->librarycard = 'b' . $user->cat_username; 
		$search = $this->search();

		// if $search is empty, the record is busy.

// JAMES 20160218 said: there should now be $search->error if the patron record is busy - or any other error returned from Millennium Fines Payment API
// But how should those be processed here?

		$process = $this->process($search);

		if($process->ProcessPaymentResult->ResultCode == 'Approved') {
			if($this->credit($search)) {
				$interface->assign('paymentresult', 'Thank You, Payment has been applied.');
			} else {
				$interface->assign('paymentresult', 'Thank You, Payment will be credited within the next 24 hours.');
			}
		} else {
				$interface->assign('paymentresult', 'Payment not approved<br>' . $process->ProcessPaymentResult->ResponseMessage);
		}

		//Present a success or failure message
		$interface->setPageTitle('Payment Results');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('onlinePaymentResult.tpl');
		$interface->display('layout.tpl');
	}

	function convert_bill($response) {
		preg_match_all("/([^|= ]+)=([^|= ]+)/", $response, $r);
		$result = array_combine($r[1], str_replace("\"", "",$r[2]));
		if(isset($result) && is_array($result)) return $result;
	}

	function credit($patron) {
		$db = new SQLite3($this->sqldb);
		foreach($patron->bills AS $billkey=>$bill) {
			$json = json_encode(array('username' => $this->username,'password' => $this->password,'hostname' => $this->hostname,'port' => $this->port,'amount' => $bill['ITEM_CHARGE'],'type' => 1,'invoice' => $bill['INVOICE_ID'],'initials' => 'onlinecc','patronID' => $patron->patronID));
			$patron->bills[$billkey]['JSON'] = $json;
			$db->exec("INSERT INTO payments VALUES(NULL, NULL, " . $bill['INVOICE_ID'] . ", '$patron->patronID', '$json')");
		}
		foreach($patron->bills AS $bill) {
			// execute our API wrapper
			exec("$this->nplwrapper '" . $bill['JSON'] . "'" ,$wrap);
			// If $wrap is empty, the patron record is busy. tell the patron that the transaction will be credited at a later time.
			if(trim($wrap[0]) == $patron->patronID) {
				// update sqlite 
				echo "processing " . $bill['INVOICE_ID'] . "\n";
				$db->exec("UPDATE payments SET COMPLETE = 1 WHERE invoice = " . $bill['INVOICE_ID']); 
			} else {
				// patron not credited. determine an action - failure.
				$fail = TRUE;
			}
		}
		$db->close();
		if(!$fail) return TRUE;
	}

	function pending($PatronID) {
	// determine if there are pending invoices yet to be credited.  Run this before we look for any bills to
	// prevent patron frustration
		$db = new SQLite3($this->sqldb);
		$sql = sprintf("SELECT invoice FROM payments WHERE patronID = '%s' AND complete is NULL", $PatronID);
		$result = $db->query($sql);
		while ($row = $result->fetchArray()) {
			$invoices[] = $row['invoice'];
		}
		if(isset($invoices) && is_array($invoices)) return $invoices;
	}

	function process($patron) {
		// get payment stuff ready.
		$UserPart1 = $patron->patronid;
		$UserPart2 = $this->librarycard;
		if(is_array($patron->bills)) {
			foreach($patron->bills AS $each_bill) $invoices[] = $each_bill['INVOICE_ID']; // concat invoice #'s
			asort($invoices);
			$UserPart3 = implode("|", $invoices);
			// make sure bill amounts match.
			foreach($patron->bills AS $each_bill) $amount[] = $each_bill['ITEM_CHARGE'];
			$amounts = array_sum($amount);
			if(!($patron->bill*100) == $amounts) { // needs to be more graceful
				echo $patron->bill*100 . " $amounts\n";
				die("bills don't match\n");
		    	} else {
		      		$ConvenienceFee = ceil(($patron->bill * $this->cc_ConvenienceFee) * 100)/100;
				$TransactionAmount = $patron->bill + $ConvenienceFee;
	      			$client_param = array('appId' => $this->appId,
							'paymentTransaction' => array(
								'MerchantAmount' => $patron->bill,
								'ConvenienceFee' => $ConvenienceFee,
								'TransactionAmount' => $TransactionAmount,
								'AccountNumber' => $this->cc_number,
								'ExpirationMonth' => $this->cc_month,
								'ExpirationYear' => $this->cc_year,
								'CVV' => $this->cc_cvv,
								'UserPart1' => $UserPart1, // patronid
								'UserPart2' => $UserPart2, // patron barcode
       		   						'UserPart3' => $UserPart3, // invoice number(s)
								'BillingName' => $this->cc_fullname,
								'BillingZip' => $this->cc_zipcode,
								'BillingEmail' => $patron->email[0],
								'DuplicateOverride' => True
							)
				);

				if(!$this->cc_fakeit) {

$process  = new SoapClient($this->cc_host, array( 
	'cache_wsdl' => WSDL_CACHE_NONE,
	'exceptions' => true,
	'trace' => true,
));

try {
					$result = $process->ProcessPayment($client_param);
					print_r("\r\n\r\nRESULT\r\n");
					print_r($result);
					$request = $process->__getLastRequest();
					print_r("\r\n\r\nREQUEST\r\n");
					print_r($request);
					$response = $process->__getLastResponse();
					print_r("\r\n\r\nRESPONSE\r\n");
					print_r($response);
					
					return $result;
} catch (Exception $e) {
echo "\r\nException\r\n";
	var_dump($e);
echo "\r\nGet Last Request\r\n";
	var_dump($process->__getLastRequest());
echo "\r\n\r\nRequest Headers\r\n";
	var_dump($process->__getLastRequestHeaders());
}
echo "\r\nGet Last Request\r\n";
	var_dump($process->__getLastRequest());
echo "\r\n\r\nRequest Headers\r\n";
	var_dump($process->__getLastRequestHeaders());
echo "\r\nGet Last Response\r\n";
	var_dump($process->__getLastResponse());
echo "\r\n\r\nResponse Headers\r\n";
	var_dump($process->__getLastResponseHeaders());


				} else {
					// NOTE User3 response needs urldecode'd
					$fakeresponse = array(
						'ProcessPaymentResult' => array(
// fake response info here
						)
					);
					$result = new stdClass();
					$result = json_decode(json_encode($fakeresponse), FALSE);
					return $result;
	       			}
			}
		}
	}

	function recycle() {
		$db = new SQLite3($this->sqldb);
		$sql = "SELECT id, data FROM payments WHERE complete IS NULL";
		$result = $db->query($sql);

		while ($row = $result->fetchArray()) {
			$datavars = json_decode($row['data']);
			exec("$this->nplwrapper '" . $row['data'] . "'" ,$wrap); // credits users account
			if(trim($wrap[0]) == $datavars->patronID) {
				$recpay = $row['id'];
				$db->exec("UPDATE payments SET COMPLETE = 1 WHERE ID = '$recpay'"); // update sqlite
			} else {
				// bail out
				return;
			}
		}
	}

	function search() {
		$patron = new stdClass();
		try {
			$client = new SoapClient(null, array('location' => "$this->hostname:$this->port$this->path", 'uri' => $this->uri));
			$result = $client->searchPatrons($this->username, $this->password, $this->librarycard);
		} catch (SoapFault $fault) {
			// III Patron Web Services errors documented at http://techdocs.iii.com/patronws_api_errorhandling.shtml
			if (!isset($result)) {
				$result = new stdClass();
			}
			$result->error = "Online Payment is currently not available for this patron.";
			preg_match('/code = (\d+),/',$fault->faultstring,$failCode);
			$result->error .= " Error #$failCode[1]. ";
			preg_match('/desc = (.+)$/',$fault->faultstring,$failCode);
			$result->error .= $failCode[1];
			return $result;
		}
		$patronField = (array) $result;
		$patron->patronID = $patronField['patronID'];
		$GLOBALS['patronField'] = $patronField;
		foreach($patronField['patronFields'] AS $field) {
			if($field->fieldTag == 96) {
				// this contains the TOTAL bill, but we need each individual bill/invoice for payment purposes.
				$patron->bill = str_replace('$','',$field->value);
			}
			if($field->fieldTag == 81) {
				$patron->patronid = $field->value;
			}
			if($field->fieldTag == 9) {
				// create a bills array, convert the response to a key value array
				$patron->bills[] = $this->convert_bill($field->value);
				$GLOBALS['patronFine'][] = $this->convert_bill($field->value);
			}
			if($field->fieldTag == 'a') {
				$zipcode = preg_match("/\d{5}(-\d{4})?\b/", $field->value, $matches);
				$patron->zipcode = $matches[0];
			}
			if($field->fieldTag == 'n') {
				if(strpos($field->value, ',')) {
					$name = explode(',', $field->value);
				} else {
					$name = array($field->value);
				}
				$patron->fullname = sprintf("%s %s", $name[0], $name[1]);
			}
			if($field->fieldTag == 'z') {
				if(strpos($field->value, ',')) {
					$patron->email = explode(',', $field->value);
				} else {
					$patron->email = array($field->value);
				}
			}
		}
		if(!empty($patron)) return $patron;
	}
}
