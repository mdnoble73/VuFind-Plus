<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/19/14
 * Time: 7:32 AM
 */

class EVokeDriver {
	public $version = 1;
	private $cookieJar;
	private $curl_connection;

	public function __construct(){
		$this->cookieJar = tempnam ("/tmp", "EVK");
		$this->curl_connection = curl_init();
	}
	public function __destruct(){
		unlink($this->cookieJar);
		curl_close($this->curl_connection);
	}
	public function getCheckedOutItems($user){
		global $configArray;
		$this->login($user);
		$url = $configArray['eVoke']['evokeApiUrl'] . '/LoanService/Get_Active_Loans';
		$checkedOutTitlesRaw = $this->_callUrl($url);
		$result = array('items' => array());
		if (isset($checkedOutTitlesRaw->response)){
			if (isset($checkedOutTitlesRaw->response->loan)){
				$loans = $checkedOutTitlesRaw->response->loan;
				//Check to see if we just have one loan
				if (isset($loans->type)){
					//There is just a single loan, not multiple, convert to an array
					$loans = array($loans);
				}
				foreach ($loans as $loan){
					$item = array(
						'checkoutSource' => 'eVoke',
						'recordId' => $loan->recordId,
						'loanId' => $loan->loanId,
						'expiresOn' => $loan->endDate,
						'format' => $loan->type
					);
					//TODO: Load additional information from record driver
					//recordId, coverUrl, recordUrl, title, author, linkUrl, ratingData
					$result['items'][] = $item;
				}
			}
		}
		return $result;
	}
	public function getHolds($user){
		global $configArray;
		$this->login($user);
		$url = $configArray['eVoke']['evokeApiUrl'] . '/LoanService/Get_Reserves';
		$holdsRaw = $this->_callUrl($url);
		$result = array(
			'holds' => array(
				'available' => array(),
				'unavailable' => array()
			)
		);
		if (isset($holdsRaw->response)){
			$holds = $holdsRaw->response->reserves;
			//Check to see if we just have one loan
			if (isset($holds->status)){
				//There is just a single hold, not multiple, convert to an array
				$holds = array($holds);
			}
			foreach ($holds as $hold){
				$item = array(
					'holdSource' => 'eVoke',
					'eVokeId' => $hold->recordId,
					'holdId' => $hold->reserveId,
					'status' => $hold->status
				);
				//TODO: Load additional information from record driver
				//recordId, coverUrl, recordUrl, title, author, linkUrl, ratingData
				if ($item['status'] == 'waiting'){
					$result['holds']['unavailable'][] = $item;
				}else{
					$result['holds']['available'][] = $item;
				}

			}
		}
		return $result;
	}

	public function getFormatsForTitle($evokeId){
		global $configArray;
		$url = $configArray['eVoke']['evokeApiUrl'] . '/RecordService/Get_Loanables?recordId=' . $evokeId;
		$results = $this->_callUrl($url);
		return $results;
	}

	public function checkoutTitle($evokeId, $formatId, $user){
		global $configArray;
		$this->login($user);
		$url = $configArray['eVoke']['evokeApiUrl'] . "/LoanService/New_Loan?recordId=$evokeId&loanableId=$formatId";
		$checkoutResponse = $this->_callUrl($url);
		return $checkoutResponse;
	}

	public function returnTitle($loanId, $user){
		global $configArray;
		$this->login($user);
		$url = $configArray['eVoke']['evokeApiUrl'] . "/LoanService/Return_Loan?loanId=$loanId";
		$returnResponse = $this->_callUrl($url);
		return $returnResponse;
	}

	public function placeHold($evokeId, $user){
		global $configArray;
		$this->login($user);
		$url = $configArray['eVoke']['evokeApiUrl'] . "/LoanService/New_Reserve?recordId=$evokeId";
		$placeHoldResponse = $this->_callUrl($url);
		return $placeHoldResponse;
	}

	public function cancelHold($holdId, $user){
		global $configArray;
		$this->login($user);
		$url = $configArray['eVoke']['evokeApiUrl'] . "/LoanService/Remove_Reserve?reserveId=$holdId";
		$cancelHoldResponse = $this->_callUrl($url);
		return $cancelHoldResponse;
	}

	public function login($user) {
		global $configArray;
		$curl_url = $configArray['eVoke']['evokeApiUrl'] . '/UserService/login?user=' . urlencode($user->cat_username) . '&pass=' . urlencode($user->cat_password);
		return $this->_callUrl($curl_url);
	}

	public function _callUrl($url, $postData = null){
		curl_setopt($this->curl_connection, CURLOPT_URL, $url);
		curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($this->curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($this->curl_connection, CURLOPT_COOKIEJAR, $this->cookieJar );
		curl_setopt($this->curl_connection, CURLOPT_COOKIESESSION, false);
		if ($postData == null){
			curl_setopt($this->curl_connection, CURLOPT_POST, false);
		}else{
			curl_setopt($this->curl_connection, CURLOPT_POST, true);
			curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $postData);
		}
		$return = curl_exec($this->curl_connection);
		$returnVal = json_decode($return);
		//print_r($returnVal);
		if ($returnVal != null){
			if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
				return $returnVal;
			}
		}
		return null;
	}
} 