<?php
/**
 *
 * Copyright (C) Villanova University 2009.
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

/**
 * ACS Class
 *
 * This class provides integration with the Adobe Content Server
 * for handing e-books
 *
 * @author      Mark Noble <mnoble@turningleaftech.com>
 * @access      public
 */
class AdobeContentServer
{

	private $acsConnection;

	const ADEPT_NS = "http://ns.adobe.com/adept";

	function __construct() {
		global $configArray;
		//Connect to the database
		/*$this->acsConnection = mysql_connect($configArray['EContent']['dbHost'] , $configArray['EContent']['dbUser'], $configArray['EContent']['dbPassword'], true);
		 if (!$this->acsConnection) {
		 die('Could not connect: (' . mysql_errno($this->acsConnection) . ') '. mysql_error($this->acsConnection));
		 }

		 $select = mysql_select_db('adept');*/
	}

	function __destruct() {
		mysql_close($this->acsConnection);
	}

	/**
	 * Returns all files that are currently stored on the content server
	 */
	public function getAllFiles(){

		$query = "SELECT resourceid, identifier, title, src FROM resourceItem where format = 'application/epub+zip'";
		$result = mysql_query($query, $this->acsConnection);

		$titles = array();
		if (!$result) {
			die('Invalid query: ' . mysql_error($this->acsConnection));
		}else{
			while ($row = mysql_fetch_assoc($result)){
				$titles[] = array(
          'resourceid'=>$row['resourceid'],
          'identifier'=>$row['identifier'],
          'title'=>$row['title'],
          'src'=>$row['src'],
				);
			}
		}

		return $titles;
	}

	function getTitleInfo($identifier){
		//Get copies that are checked out
		$query =  "SELECT DISTINCT `fulfillmentitem`.`resourceid`, `fulfillment`.`returned`, `fulfillmentitem`.`until`, fulfillment.loanuntil FROM fulfillmentitem INNER JOIN " .
              "fulfillment ON fulfillmentitem.fulfillmentid = fulfillment.fulfillmentid INNER JOIN " . 
              "resourceitem ON fulfillmentitem.resourceid = resourceitem.resourceid " . 
              "WHERE resourceitem.identifier like '" . mysql_real_escape_string ($identifier, $this->acsConnection) . "' and `returned` = 'F' AND `until` > NOW() " . 
              "ORDER BY loanuntil DESC";
		$copiesOut = array();
		$result = mysql_query($query, $this->acsConnection);
		if (!$result){
			die("Invalid query " . $query);
		}
		while ($row = mysql_fetch_assoc($result)){
			$copiesOut[] = array(
        'resourceid'=>$row['resourceid'],
        'until'=>$row['until'],
        'loanuntil'=>$row['loanuntil'],
			);
		}

		$titleInfo = array(
      'identifier' => $identifier,
      'copiesOut' => $copiesOut,
      'readPermissions' => ''
      );

      return $titleInfo;
	}

	static function mintDownloadLink($eContentItem, $eContentCheckout){
		global $configArray;
		global $user;

		if ($user == false){
			return '';
		}

		//First check to see if we have already minted a download link for this resource
		//And this user that hasn't been returned.
		if ($eContentCheckout->acsTransactionId == null || $eContentCheckout->acsDownloadLink == null){
			$transactionId = self::getUniqueID();
			$eContentCheckout->acsTransactionId = $transactionId;
			
			$dateval=time();
			$gbauthdate=gmdate('r', $dateval);
	
			$rights = "";
			$bookDownloadURL =
			    "action=enterloan". //Loan the title out
			    "&ordersource=".urlencode($configArray['EContent']['orderSource']).
			    "&orderid=".urlencode($transactionId).
			    "&resid=".urlencode($eContentItem->acsId).
			    $rights.
			    "&gbauthdate=".urlencode($gbauthdate).
			    "&dateval=".urlencode($dateval).
			    "&gblver=4";
	
			$linkURL = $configArray['EContent']['linkURL'];
			$sharedSecret = $configArray['EContent']['distributorSecret'];
			$sharedSecret = base64_decode($sharedSecret);
			$bookDownloadURL = $linkURL."?".$bookDownloadURL."&auth=".hash_hmac("sha1", $bookDownloadURL, $sharedSecret );
		
			$eContentCheckout->acsDownloadLink = $bookDownloadURL;
			$eContentCheckout->update();
			
			return $bookDownloadURL;
		}else{
			return $eContentCheckout->acsDownloadLink;
		}
	}

	static function getUniqueID(){
		$strOut = "";

		$r1 = self::get_random_digits();

		// TRUNCATE TO THE FIELD SIZE IF NEEDED
		$PRECISION = 30;
		while (strlen($r1) < $PRECISION){
			$r1 = $r1 . self::get_random_digits();
		}

		$r1 = "ACS4-".$r1;

		$iLen = strlen($r1);
		if ($iLen > $PRECISION)
		$r1 = substr($r1,0,$PRECISION);

		$strOut = $r1;

		if (!$strOut)
		ERROR_DIE("get_uniqueID() failed");

		return ($strOut);
	}

	/**
	 * Package a file to the ACS server and get back the ACS ID
	 */
	static function packageFile($filename, $existingResourceId = '', $numAvailable){
		$packageDoc = new DOMDocument('1.0', 'UTF-8');
		$packageDoc->formatOutput = true;
		$packageElem = $packageDoc->appendChild($packageDoc->createElementNS("http://ns.adobe.com/adept", "package"));
		//The ACS server generates errors when replacing an existing resource.
		//It wll be better to just delete the old resource after creating a new resource.
		if (true || $existingResourceId == ''){
			$packageElem->appendChild($packageDoc->createElement("action", "add"));
		}else{
			$packageElem->appendChild($packageDoc->createElement("action", "replace"));
			$packageElem->appendChild($packageDoc->createElement("resource", $existingResourceId));
		}
		$fileData = file_get_contents($filename);
		$packageElem->appendChild($packageDoc->createElement("data", base64_encode($fileData)));
		//<thumbnailData>**OPT Base64-encoded thumbnail bytes </thumbnailData>
		$packageElem->appendChild($packageDoc->createElement("expiration", date(DATE_W3C, time() + (15 * 60) ))); //Request expiration, default to 15 minutes
		$packageElem->appendChild($packageDoc->createElement('nonce', base64_encode(AdobeContentServer::makeNonce())));
		//Calculate hmac
		global $configArray;
		$serverPassword = hash("sha1",$configArray['EContent']['acsPassword'], true);

		AdobeContentServer::signNode($packageDoc, $packageElem, $serverPassword);

		$packagingURL = $configArray['EContent']['packagingURL'];
		//echo("Request:<br/>" . htmlentities($packageDoc->saveXML()) . "<br/>");
		$response = AdobeContentServer::sendRequest($packageDoc->saveXML(),$packagingURL);

		$responseData = simplexml_load_string($response);
		if (isset($responseData->error) || preg_match('/<error/', $response)){
			echo("Response:<br/>" . htmlentities($response) . "<br/>");
			return array('success' => false);
		}else{
			$acsId = (string)$responseData->resource;

			//Setup distribution rights
			$distributorId = $configArray['EContent']['distributorId'];
			$distributionResult = AdobeContentServer::addDistributionRights($acsId, $distributorId, $numAvailable);
			if ($distributionResult['success'] == false){
				return $distributionResult;
			}else{
				return array(
					'success' => true,
					'acsId' => $acsId, 
				);
			}
		}
	}

	static function addDistributionRights($acsId, $distributorId, $numAvailable){
		$distributionDoc = new DOMDocument('1.0', 'UTF-8');
		$distributionDoc->formatOutput = true;
		$distributionElem = $distributionDoc->appendChild($distributionDoc->createElementNS("http://ns.adobe.com/adept", "request"));
		$distributionElem->setAttribute("action", "create");
		$distributionElem->setAttribute("auth", "builtin");
		$distRightsElem = $distributionElem->appendChild($distributionDoc->createElement("distributionRights"));
		$distRightsElem->appendChild($distributionDoc->createElement("distributor", $distributorId));
		$distRightsElem->appendChild($distributionDoc->createElement("resource", $acsId));
		$distRightsElem->appendChild($distributionDoc->createElement("distributionType", "loan"));
		$distRightsElem->appendChild($distributionDoc->createElement("available", $numAvailable));
		$distRightsElem->appendChild($distributionDoc->createElement("returnable", "true"));
		$distRightsElem->appendChild($distributionDoc->createElement("userType", "user"));
		$permissionsElem = $distRightsElem->appendChild($distributionDoc->createElement("permissions"));
		$displayElem = $permissionsElem->appendChild($distributionDoc->createElement("display"));
		$displayElem->appendChild($distributionDoc->createElement("duration", "1814400")); //Allow reading for 21 days
		//Add nonce, expiration, and hmac
		$distributionElem->appendChild($distributionDoc->createElement("expiration", date(DATE_W3C, time() + (15 * 60) ))); //Request expiration, default to 15 minutes
		$distributionElem->appendChild($distributionDoc->createElement('nonce', base64_encode(AdobeContentServer::makeNonce())));
		//Calculate hmac
		global $configArray;
		$serverPassword = hash("sha1",$configArray['EContent']['acsPassword'], true);

		AdobeContentServer::signNode($distributionDoc, $distributionElem, $serverPassword);

		$distributionURL = $configArray['EContent']['operatorURL'] . '/ManageDistributionRights';
		//echo("Request:<br/>" . htmlentities($packageDoc->saveXML()) . "<br/>");
		$response = AdobeContentServer::sendRequest($distributionDoc->saveXML(),$distributionURL);

		//echo("Response:<br/>" . htmlentities($response) . "<br/>");
		$responseData = simplexml_load_string($response);

		if (isset($responseData->error)){
			return array('success' => 'false');
		}else{
			return array('success' => 'true');
		}
	}
	
	static function removeDistributionRights($acsId, $distributorId){
		$distributionDoc = new DOMDocument('1.0', 'UTF-8');
		$distributionDoc->formatOutput = true;
		$distributionElem = $distributionDoc->appendChild($distributionDoc->createElementNS("http://ns.adobe.com/adept", "request"));
		$distributionElem->setAttribute("action", "delete");
		$distributionElem->setAttribute("auth", "builtin");
		$distRightsElem = $distributionElem->appendChild($distributionDoc->createElement("distributionRights"));
		$distRightsElem->appendChild($distributionDoc->createElement("distributor", $distributorId));
		$distRightsElem->appendChild($distributionDoc->createElement("resource", $acsId));
		$distRightsElem->appendChild($distributionDoc->createElement("distributionType", "loan"));
		//Add nonce, expiration, and hmac
		$distributionElem->appendChild($distributionDoc->createElement("expiration", date(DATE_W3C, time() + (15 * 60) ))); //Request expiration, default to 15 minutes
		$distributionElem->appendChild($distributionDoc->createElement('nonce', base64_encode(AdobeContentServer::makeNonce())));
		//Calculate hmac
		global $configArray;
		$serverPassword = hash("sha1",$configArray['EContent']['acsPassword'], true);

		AdobeContentServer::signNode($distributionDoc, $distributionElem, $serverPassword);

		$distributionURL = $configArray['EContent']['operatorURL'] . '/ManageDistributionRights';
		$request = $distributionDoc->saveXML();
		$response = AdobeContentServer::sendRequest($distributionDoc->saveXML(),$distributionURL);

		//echo("Response:<br/>" . htmlentities($response) . "<br/>");
		$responseData = simplexml_load_string($response);

		if (isset($responseData->error)){
			return array('success' => 'false');
		}else{
			return array('success' => 'true');
		}
	}

	static function signNode( $xmlDoc, $xmlNodeToBeSigned, $secretKey ){
		require_once("/sys/XMLSigningSerializer.php");
		$serializer = new XMLSigningSerializer( false );
		$signingSerialization = $serializer->serialize($xmlNodeToBeSigned);
		$hmacData = base64_encode( hash_hmac("sha1", $signingSerialization, $secretKey, true ) );
		$hmacNode = $xmlDoc->createElement("hmac", $hmacData );
		$xmlNodeToBeSigned->appendChild( $hmacNode );
	}

	static function sendRequest($requestData, $requestURL){

		$c = curl_init($requestURL);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_POSTFIELDS, $requestData);
		curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-type: application/vnd.adobe.adept+xml'));

		// Request HTTP
		$result = curl_exec($c);
		curl_close($c);
		return $result;
	}

	// In real life, random generation might not be used.
	// you might want to use unique database IDs for every transaction.
	static function get_random_digits()
	{
		$r1 = mt_rand();
		$iDot = strpos($r1,".");
		return substr($r1,$iDot);

	}

	/**
	 * Creates a quasi-unique nonce based on the start time and an incremented
	 * counter
	 *
	 * @return a string containing the nonce
	 */
	static $counter = 1;
	static function makeNonce(){
		$nonce = base64_encode(mt_rand(10000000,mt_getrandmax()));
		return $nonce;
	}

	static function loanReturn($recordId, $userId){
		//First check to see if we have already minted a download link for this resource
		//And this user that hasn't been returned.
		require_once('sys/EPubTransaction.php');
		$trans = new EPubTransaction();
		$trans->userId = $userId;
		$trans->recordId = $recordId;
		$trans->itemId = $itemId;
		$trans->whereAdd('timeReturned = null');
		if ($trans->find(true)){
			if ($trans->userAcsId != null && strlen($trans->userAcsId) > 0){
				//Create the message to send to the ACS server
				$loanReturnDoc = new DOMDocument('1.0', 'UTF-8');
				$loanReturnElem = $loanReturnDoc->appendChild($loanReturnDoc->createElementNS("http://ns.adobe.com/adept", "loanReturn"));
				$loanReturnElem->appendChild($loanReturnDoc->createElement("user", $trans->userAcsId));
				$loanReturnElem->appendChild($loanReturnDoc->createElement("device", "urn:uuid:250f575b-99af-45fc-a0ca-239c98cebc37"));
				$loanReturnElem->appendChild($loanReturnDoc->createElement("expiration", date(DATE_W3C, time() + (15 * 60) ))); //Request expiration, default to 15 minutes
				$loanReturnElem->appendChild($loanReturnDoc->createElement('nonce', base64_encode(AdobeContentServer::makeNonce())));
				$loanReturnElem->appendChild($loanReturnDoc->createElement("loan", "1203b997-17ac-4845-913f-bd01a764f96b-00000003"));
				$loanReturnElem->appendChild($loanReturnDoc->createElement("signature", base64_encode($trans->userAcsId)));
				//Calculate hmac
				global $configArray;
				$serverPassword = hash("sha1",$configArray['EContent']['acsPassword'], true);

				//AdobeContentServer::signNode($loanReturnDoc, $loanReturnElem, $serverPassword);

				$linkURL = $configArray['EContent']['operatorURL'] . "/ManageLicense";

				//echo("Request:<br/>" . $loanReturnDoc->saveXML() . "<br/>");
				$response = AdobeContentServer::sendRequest($loanReturnDoc->saveXML(),$linkURL);

				//echo("Response:<br/>" . $response . "<br/>");
				$responseData = simplexml_load_string($response);

				if (strlen($response) == 0 || isset($responseData->error) || preg_match('/<error.*/', $response)){
					return array('result' => 'false');
				}else{
					return array('result' => 'true');
				}
			}
		}else{
			//Could not find the transaction for the record
			return array('result' => true, 'message' => 'The item was never checked out in the Adobe Content Server.');
		}

	}

	static function deleteResource($acsId){
		global $configArray;
		$distributorId = $configArray['EContent']['distributorId'];
		AdobeContentServer::removeDistributionRights($acsId, $distributorId);
		
		$deleteResourceDoc = new DOMDocument('1.0', 'UTF-8');
		$deleteResourceDoc->formatOutput = true;
		//Create the message to send to the ACS server
		$deleteResourceElem = $deleteResourceDoc->appendChild($deleteResourceDoc->createElementNS("http://ns.adobe.com/adept", "request"));
		$deleteResourceElem->setAttribute("action", "delete");
		$deleteResourceElem->setAttribute("auth", "builtin");
		$resourceKeyElem = $deleteResourceElem->appendChild($deleteResourceDoc->createElement("resourceKey"));
		$resourceKeyElem->appendChild($deleteResourceDoc->createElement("resource", $acsId));
		//Add nonce, expiration, and hmac
		$deleteResourceElem->appendChild($deleteResourceDoc->createElement("expiration", date(DATE_W3C, time() + (15 * 60) ))); //Request expiration, default to 15 minutes
		$deleteResourceElem->appendChild($deleteResourceDoc->createElement('nonce', base64_encode(AdobeContentServer::makeNonce())));
		//Calculate hmac
		global $configArray;
		$serverPassword = hash("sha1",$configArray['EContent']['acsPassword'], true);

		AdobeContentServer::signNode($deleteResourceDoc, $deleteResourceElem, $serverPassword);

		$distributionURL = $configArray['EContent']['operatorURL'] . '/ManageResourceKey';
		//echo("Request:<br/>" . htmlentities($deleteResourceDoc->saveXML()) . "<br/>");
		$response = AdobeContentServer::sendRequest($deleteResourceDoc->saveXML(),$distributionURL);

		//echo("Response:<br/>" . $response . "<br/>");
		$responseData = simplexml_load_string($response);

		if (strlen($response) == 0 || isset($responseData->error) || preg_match('/<error.*/', $response)){
			return array('result' => 'false');
		}else{
			return array('result' => 'true');
		}
	}
}