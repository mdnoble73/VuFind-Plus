<?php
/**
 * Handles Millennium Integration related to Reading History
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/20/13
 * Time: 11:51 AM
 */

class MillenniumReadingHistory {
	/**
	 * @var MillenniumDriver $driver;
	 */
	private $driver;
	public function __construct($driver){
		$this->driver = $driver;
	}

	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		global $timer;
		$patronDump = $this->driver->_getPatronDump($this->driver->_getBarcode());

		//Load the information from millennium using CURL
		$pageContents = $this->driver->_fetchPatronInfoPage($patronDump, 'readinghistory');

		//Check to see if there are multiple pages of reading history
		$hasPagination = preg_match('/<td[^>]*class="browsePager"/', $pageContents);
		$extraPagesToLoad = array();
		if ($hasPagination){
			//Load a list of extra pages to load.  The pagination links display multiple times, so load into an associative array to make them unique
			preg_match_all('/<a href="readinghistory&page=(\\d+)">/', $pageContents, $additionalPageMatches);
			foreach ($additionalPageMatches as $additionalPageMatch){
				$extraPagesToLoad[$additionalPageMatch[1]] = $additionalPageMatch[1];
			}
		}

		$readingHistoryTitles = $this->parseReadingHitoryPage($pageContents, $patron, $sortOption);
		foreach ($extraPagesToLoad as $pageNum){
			$pageContents = $this->driver->_fetchPatronInfoPage($patronDump, 'readinghistory&page=' . $pageNum);
			$additionalTitles = $this->parseReadingHitoryPage($pageContents, $patron, $sortOption);
			$readingHistoryTitles = array_merge($readingHistoryTitles, $additionalTitles);
		}

		if ($sortOption == "checkedOut" || $sortOption == "returned"){
			krsort($readingHistoryTitles);
		}else{
			ksort($readingHistoryTitles);
		}
		$numTitles = count($readingHistoryTitles);
		//process pagination
		if ($recordsPerPage != -1){
			$startRecord = ($page - 1) * $recordsPerPage;
			$readingHistoryTitles = array_slice($readingHistoryTitles, $startRecord, $recordsPerPage);
		}

		//The history is active if there is an opt out link.
		$historyActive = (strpos($pageContents, 'OptOut') > 0);
		$timer->logTime("Loaded Reading history for patron");
		return array('historyActive'=>$historyActive, 'titles'=>$readingHistoryTitles, 'numTitles'=> $numTitles);
	}

	/**
	 * Do an update or edit of reading history information.  Current actions are:
	 * deleteMarked
	 * deleteAll
	 * exportList
	 * optOut
	 *
	 * @param   string  $action         The action to perform
	 * @param   array   $selectedTitles The titles to do the action on if applicable
	 */
	function doReadingHistoryAction($action, $selectedTitles){
		global $configArray;
		global $analytics;
		$patronDump = $this->driver->_getPatronDump($this->driver->_getBarcode());
		//Load the reading history page
		$scope = $this->driver->getDefaultScope();
		$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/readinghistory";

		$cookie = tempnam ("/tmp", "CURLCOOKIE");
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = $this->driver->_getLoginFormValues();
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sResult = curl_exec($curl_connection);

		if ($action == 'deleteMarked'){
			//Load patron page readinghistory/rsh with selected titles marked
			if (!isset($selectedTitles) || count($selectedTitles) == 0){
				return;
			}
			$titles = array();
			foreach ($selectedTitles as $titleId){
				$titles[] = $titleId . '=1';
			}
			$title_string = implode ('&', $titles);
			//Issue a get request to delete the item from the reading history.
			//Note: Millennium really does issue a malformed url, and it is required
			//to make the history delete properly.
			$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/readinghistory/rsh&" . $title_string;
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			$sResult = curl_exec($curl_connection);
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Delete Marked Reading History Titles');
			}
		}elseif ($action == 'deleteAll'){
			//load patron page readinghistory/rah
			$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/readinghistory/rah";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			curl_exec($curl_connection);
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Delete All Reading History Titles');
			}
		}elseif ($action == 'exportList'){
			//Leave this unimplemented for now.
		}elseif ($action == 'optOut'){
			//load patron page readinghistory/OptOut
			$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/readinghistory/OptOut";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			curl_exec($curl_connection);
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Opt Out of Reading History');
			}
		}elseif ($action == 'optIn'){
			//load patron page readinghistory/OptIn
			$curl_url = $configArray['Catalog']['url'] . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/readinghistory/OptIn";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			curl_exec($curl_connection);
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Opt in to Reading History');
			}
		}
		curl_close($curl_connection);
		unlink($cookie);
	}

	private function parseReadingHitoryPage($pageContents, $patron, $sortOption) {
		$sResult = preg_replace("/<[^<]+?><[^<]+?>Reading History.\(.\d*.\)<[^<]+?>\W<[^<]+?>/", "", $pageContents);

		$s = substr($sResult, stripos($sResult, 'patFunc'));
		$s = substr($s,strpos($s,">")+1);
		$s = substr($s,0,stripos($s,"</table"));

		$s = preg_replace ("/<br \/>/","", $s);

		$srows = preg_split("/<tr([^>]*)>/",$s);

		$scount = 0;
		$skeys = array_pad(array(),10,"");
		$readingHistoryTitles = array();
		$itemindex = 0;
		foreach ($srows as $srow) {
			$tmpRow = preg_replace('/\r\n|\n|\r/', "", strip_tags($srow));
			if (strlen(trim($tmpRow)) == 0){
				continue;
			}elseif(preg_match('/Result Page/', $tmpRow)){
				continue;
			}
			$scols = preg_split("/<t(h|d)([^>]*)>/",$srow);
			$historyEntry = array();
			for ($i=0; $i < sizeof($scols); $i++) {
				$scols[$i] = str_replace("&nbsp;"," ",$scols[$i]);
				$scols[$i] = preg_replace ("/<br+?>/"," ", $scols[$i]);
				$scols[$i] = html_entity_decode(trim(substr($scols[$i],0,stripos($scols[$i],"</t"))));
				//print_r($scols[$i]);
				if ($scount == 1) {
					$skeys[$i] = $scols[$i];
				} else if ($scount > 1) {
					if (stripos($skeys[$i],"Mark") > -1) {
						if (preg_match('/id="rsh(\\d+)"/', $scols[$i], $matches)){
							$itemIndex = $matches[1];
							$historyEntry['itemindex'] = $itemIndex;
						}
						$historyEntry['deletable'] = "BOX";
					}

					if (stripos($skeys[$i],"Title") > -1) {
						if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $scols[$i], $matches)) {
							$shortId = $matches[1];
							$bibId = '.' . $matches[2];
							$title = $matches[2];

							$historyEntry['id'] = $bibId;
							$historyEntry['shortId'] = $shortId;
						}else{
							$title = strip_tags($scols[$i]);
						}

						$historyEntry['title'] = $title;
					}

					if (stripos($skeys[$i],"Author") > -1) {
						$historyEntry['author'] = strip_tags($scols[$i]);
					}

					if (stripos($skeys[$i],"Checked Out") > -1) {
						$historyEntry['checkout'] = strip_tags($scols[$i]);
					}
					if (stripos($skeys[$i],"Details") > -1) {
						$historyEntry['details'] = strip_tags($scols[$i]);
					}

					$historyEntry['borrower_num'] = $patron['id'];
				} //Done processing column
			} //Done processing row

			if ($scount > 1){
				$historyEntry['title_sort'] = strtolower($historyEntry['title']);

				//$historyEntry['itemindex'] = $itemindex++;
				//Get additional information from resources table
				if (isset($historyEntry['shortId']) && strlen($historyEntry['shortId']) > 0){
					/** @var Resource|Object $resource */
					$resource = new Resource();
					$resource->shortId = $historyEntry['shortId'];
					if ($resource->find(true)){
						$historyEntry = array_merge($historyEntry, get_object_vars($resource));
						$historyEntry['recordId'] = $resource->record_id;
						$historyEntry['shortId'] = str_replace('.b', 'b', $resource->record_id);
						$historyEntry['ratingData'] = $resource->getRatingData();
					}else{
						//echo("Warning did not find resource for {$historyEntry['shortId']}");
					}
				}
				if ($sortOption == "title"){
					$titleKey = $historyEntry['title_sort'];
				}elseif ($sortOption == "author"){
					$titleKey = $historyEntry['author'] . "_" . $historyEntry['title_sort'];
				}elseif ($sortOption == "checkedOut" || $sortOption == "returned"){
					$checkoutTime = DateTime::createFromFormat('m-d-Y', $historyEntry['checkout']) ;
					if ($checkoutTime){
						$titleKey = $checkoutTime->getTimestamp() . "_" . $historyEntry['title_sort'];
					}else{
						//print_r($historyEntry);
						$titleKey = $historyEntry['title_sort'];
					}
				}elseif ($sortOption == "format"){
					$titleKey = $historyEntry['format'] . "_" . $historyEntry['title_sort'];
				}else{
					$titleKey = $historyEntry['title_sort'];
				}
				$titleKey .= '_' . $scount;
				$readingHistoryTitles[$titleKey] = $historyEntry;
			}
			$scount++;
		}//processed all rows in the table
		return $readingHistoryTitles;
	}
}