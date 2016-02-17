<?php

/**
 * Description goes here
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/14/2016
 * Time: 5:42 PM
 */
require_once ROOT_DIR . '/sys/Pager.php';

class EDS_API {
	static $instance;

	private $edsBaseApi = 'http://eds-api.ebscohost.com/edsapi/rest';
	private $curl_connection;
	private $sessionId;
	private $authenticationToken;

	protected $queryStartTime = null;
	protected $queryEndTime = null;
	protected $queryTime = null;

	// Page number
	protected $page = 1;
	// Result limit
	protected $limit = 20;

	// STATS
	protected $resultsTotal = 0;

	protected $searchTerm;

	protected $lastSearchResults;

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
		if (!$this->authenticate()){
			return;
		}

		$this->startQueryTimer();
		$this->searchTerm = $searchTerm;
		$searchUrl = $this->edsBaseApi . '/search?query-1=AND,' . urlencode($searchTerm);
		curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
		curl_setopt($this->curl_connection, CURLOPT_HTTPHEADER, array(
			'x-authenticationToken: ' . $this->authenticationToken,
			'x-sessionToken: ' . $this->getSessionToken(),
		));
		curl_setopt($this->curl_connection, CURLOPT_URL, $searchUrl);
		$result = curl_exec($this->curl_connection);
		$searchData = new SimpleXMLElement($result);
		$this->stopQueryTimer();
		if ($searchData && !$searchData->ErrorNumber){
			$this->resultsTotal = $searchData->SearchResult->Statistics->TotalHits;
			$this->lastSearchResults = $searchData->SearchResult;
			return $searchData->SearchResult;
		}else{
			$curlInfo = curl_getinfo($this->curl_connection);
			$this->lastSearchResults = null;
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

	public function getQuerySpeed() {
		return $this->queryTime;
	}

	/**
	 * Start the timer to figure out how long a query takes.  Complements
	 * stopQueryTimer().
	 *
	 * @access protected
	 */
	protected function startQueryTimer()
	{
		// Get time before the query
		$time = explode(" ", microtime());
		$this->queryStartTime = $time[1] + $time[0];
	}

	/**
	 * End the timer to figure out how long a query takes.  Complements
	 * startQueryTimer().
	 *
	 * @access protected
	 */
	protected function stopQueryTimer()
	{
		$time = explode(" ", microtime());
		$this->queryEndTime = $time[1] + $time[0];
		$this->queryTime = $this->queryEndTime - $this->queryStartTime;
	}

	/**
	 * Return an array of data summarising the results of a search.
	 *
	 * @access  public
	 * @return  array   summary of results
	 */
	public function getResultSummary() {
		$summary = array();

		$summary['page']        = $this->page;
		$summary['perPage']     = $this->limit;
		$summary['resultTotal'] = $this->resultsTotal;
		// 1st record is easy, work out the start of this page
		$summary['startRecord'] = (($this->page - 1) * $this->limit) + 1;
		// Last record needs more care
		if ($this->resultsTotal < $this->limit) {
			// There are less records returned than one page, then use total results
			$summary['endRecord'] = $this->resultsTotal;
		} elseif (($this->page * $this->limit) > $this->resultsTotal) {
			// The end of the current page runs past the last record, use total results
			$summary['endRecord'] = $this->resultsTotal;
		} else {
			// Otherwise use the last record on this page
			$summary['endRecord'] = $this->page * $this->limit;
		}

		return $summary;
	}

	/**
	 * Return a url for use by pagination template
	 *
	 * @access  public
	 * @return  string   URL of a new search
	 */
	public function renderLinkPageTemplate() {
		// Stash our old data for a minute
		$oldPage = $this->page;
		// Add the page template
		$this->page = '%d';
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

	/**
	 * Build a url for the current search
	 *
	 * @access  public
	 * @return  string   URL of a search
	 */
	public function renderSearchUrl() {
		$searchUrl = '/EBSCO/Results?lookfor=' . $this->searchTerm;
		if ($this->page != 1){
			$searchUrl .= '&page=' . $this->page;
		}
		return $searchUrl;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getResultRecordHTML()
	{
		global $interface;
		$html = array();
		if (isset($this->lastSearchResults->Data->Records)) {
			for ($x = 0; $x < count($this->lastSearchResults->Data->Records->Record); $x++) {
				$current = &$this->lastSearchResults->Data->Records->Record[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));

				require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
				$record = new EbscoRecordDriver($current);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getSearchResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		}
		return $html;
	}
}