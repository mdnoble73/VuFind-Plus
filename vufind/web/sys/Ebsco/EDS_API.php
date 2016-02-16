<?php

/**
 * Description goes here
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/14/2016
 * Time: 5:42 PM
 */
class EDS_API {
	static $instance;

	private $edsBaseApi = 'http://eds-api.ebscohost.com/edsapi/rest';
	private $curl_connection;
	private $sessionId;
	private $authenticationToken;

	/**
	 * @return EDS_API
	 */
	public static function getInstance(){
		if (EDS_API::$instance == null){
			EDS_API::$instance = new EDS_API();
		}
		return EDS_API::$instance;
	}

	public function authenticate(){
		global $library;
		if ($library->edsApiProfile){
			$this->curl_connection = curl_init("https://eds-api.ebscohost.com/authservice/rest/uidauth");
			$params =<<<BODY
<UIDAuthRequestMessage xmlns="http://www.ebscohost.com/services/public/AuthService/Response/2012/06/01">
    <UserId>{$library->edsApiUsername}</UserId>
    <Password>{$library->edsApiPassword}</Password>
    <InterfaceId>{$library->edsApiProfile}</InterfaceId>
</UIDAuthRequestMessage>
BODY;
			$headers = array(
				'Content-Type: application/xml',
				'Content-Length: ' . strlen($params)
			);

			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($this->curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl_connection, CURLOPT_TIMEOUT, 30);
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($this->curl_connection, CURLOPT_POST, true);
			curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $params);

			$return = curl_exec($this->curl_connection);
			$authenticationResponse = new SimpleXMLElement($return);
			if ($authenticationResponse && isset($authenticationResponse->AuthToken)){
				$this->authenticationToken = (string)$authenticationResponse->AuthToken;
				curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, array(
					'x-authenticationToken' => $this->authenticationToken,
				));
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	public function getSearchResults($searchTerm){
		$searchUrl = $this->edsBaseApi . '/search?query-1=AND,' . urlencode($searchTerm);
		curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
		curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, array(
			'x-authenticationToken: ' . $this->authenticationToken,
			'x-sessionToken: ' . $this->getSessionToken(),
		));
		curl_setopt($this->curl_connection, CURLOPT_URL, $searchUrl);
		$result = curl_exec($this->curl_connection);
		$searchData = new SimpleXMLElement($result);
		if ($searchData && !$searchData->ErrorNumber){
			return $searchData->SearchResult;
		}else{
			$curlInfo = curl_getinfo($this->curl_connection);
			return null;
		}
	}

	public function getSessionToken(){
		global $library;
		$params = array(
			'profile' => $library->edsApiProfile,
			'guest' => 'y',
			'org' => $library->displayName
		);
		curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
		curl_setopt($this->curl_connection, CURLOPT_URL, $this->edsBaseApi . '/createsession?' . http_build_query($params));
		$result = curl_exec($this->curl_connection);
		$createSessionResponse = new SimpleXMLElement($result);
		if ($createSessionResponse->SessionToken){
			$this->sessionId = (string)$createSessionResponse->SessionToken;
			return $this->sessionId;
		}
		return false;
	}

	public function endSession(){
		curl_setopt($this->curl_connection, CURLOPT_URL, $this->edsBaseApi . '/endsession?sessiontoken=' . $this->sessionId);
		$result = curl_exec($this->curl_connection);
	}

	public function __destruct(){
		$this->endSession();
		curl_close($this->curl_connection);
	}
}