<?php
/**
 * Handles integration with Prospector
 */

class Prospector{
	/**
	 * Load search results from Prospector using the encore interface.
	 * If $prospectorRecordDetails are provided, will sort the existing result to the
	 * top and tag it as being the record.
	 * $prospectorRecordDetails should be retrieved from getProspectorDetailsForLocalRecord
	 **/
	function getTopSearchResults($searchTerms, $maxResults, $prospectorRecordDetails = null){
		$prospectorUrl = $this->getSearchLink($searchTerms);
		//Load the HTML from Prospector
		$req = new Proxy_Request($prospectorUrl);
		if (PEAR::isError($req->sendRequest())) {
			return null;
		}
		$prospectorInfo = $req->getResponseBody();

		//Parse the information to get the titles from the page
		/*preg_match_all('/<table class="browseBibTable" cellspacing="2" border="0">.*?<div class="dpBibTitle">(.*?)<div class="dpBibAuthor">(.*?)<\/div>.*?<div class="dpImageExtras">(.*?)<br\\s?\/?>.*?<\/div>.*?<\/table>/s', $prospectorInfo, $titleInfo, PREG_SET_ORDER);*/
		preg_match_all('/<table class="browseBibTable" cellspacing="2" border="0">(.*?)<\/table>/s', $prospectorInfo, $titleInfo, PREG_SET_ORDER);
		$prospectorTitles = array();
		for ($matchi = 0; $matchi < count($titleInfo); $matchi++) {
			$curTitleInfo = array();
			//Extract the titld and bid from the titleTitleInfo
			$titleTitleInfo = $titleInfo[$matchi][1];

			if (preg_match('/<div class="dpBibTitle">.*?<a.*?href.*?%7CR(.*?)%7C.*?>\\s*(.*?)\\s*<\/a>.*?<\/div>/s', $titleTitleInfo, $titleMatches)) {
				$curTitleInfo['id'] = $titleMatches[1];
				//Create the link to the title in Encore
				$curTitleInfo['link'] = "http://encore.coalliance.org/iii/encore/record/C|R" . urlencode($curTitleInfo['id']) ."?lang=eng&amp;suite=def";
				$curTitleInfo['title'] = strip_tags($titleMatches[2]);
			} else {
				//Couldn't load information, skip to the next one.
				continue;
			}

			//Extract the author from the titleAuthorInfo
			$titleAuthorInfo = $titleInfo[$matchi][1];
			if (preg_match('/<div class="dpBibAuthor">(.*?)<\/div>/s', $titleAuthorInfo, $authorMatches)) {
				$authorInfo = trim(strip_tags($authorMatches[1]));
				if (strlen($authorInfo) > 0){
					$curTitleInfo['author'] = $authorInfo;
				}
			}

			//Extract the publication date from the titlePubDateInfo
			$titlePubDateInfo = $titleInfo[$matchi][1];
			if (preg_match('/<td align="right">.*?<div class="dpImageExtras">(.*?)<br \/>.*?<\/td>/s', $titlePubDateInfo, $pubMatches)) {
				//Make sure we are not getting scripts and copy counts
				if (!preg_match('/img/', $pubMatches[1]) && !preg_match('/script/', $pubMatches[1])){
					$publicationInfo = trim(strip_tags($pubMatches[1]));
					if (strlen($publicationInfo) > 0){
						$curTitleInfo['pubDate'] =$publicationInfo;
					}
				}
			}

			$prospectorTitles[] = $curTitleInfo;
		}

		if (!is_null($prospectorRecordDetails) && $prospectorRecordDetails['recordId'] != ''){
			//Try to find the record in the list of Prospector titles
			$foundCurrentTitle = false;
			foreach($prospectorTitles as $key => $title){
				if ($title['id'] == $prospectorRecordDetails['recordId']){
					unset($prospectorTitles[$key]);
					$title['isCurrent'] = true;
					array_unshift($prospectorTitles, $title);
					$foundCurrentTitle = true;
					break;
				}
			}
			//If we didn't get the titl in the search results, add it in.
			if (!$foundCurrentTitle){
				$title = array(
                  'id' => $prospectorRecordDetails['recordId'],
                  'title' => $prospectorRecordDetails['title'],
                  'author' => $prospectorRecordDetails['author'],
                  'link' => $prospectorRecordDetails['prospectorEncoreUrl'],
                  'isCurrent' => true,
				);
				array_unshift($prospectorTitles, $title);
			}
		}

		$prospectorTitles = array_slice($prospectorTitles, 0, $maxResults, true);
		return $prospectorTitles;
	}

	function getSearchLink($searchTerms){
		$search = "";
		foreach ($searchTerms as $term){
			if (strlen($search) > 0){
				$search .= ' ';
			}
			$search .= $term['lookfor'];
		}
		//Setup the link to Prospector (search classic)
		//$prospectorUrl = "http://prospector.coalliance.org/search/?searchtype=X&searcharg=" . urlencode($search) . "&Da=&Db=&SORT=R";
		$prospectorUrl = "http://encore.coalliance.org/iii/encore/search/C|S" . urlencode($search) ."|Orightresult|U1?lang=eng&amp;suite=def";
		return $prospectorUrl;
	}

	/**
	 * Retrieve details about a record within prospector
	 *
	 * @param $record - The full record information from Prospector
	 * @return Associative array with the record number in prospector and the list of libraries that own the title in prospector
	 */
	function getProspectorDetailsForLocalRecord($record){
		//Disable prospector details for now. 
		return false;
		
		$logger = new Logger();

		//Check to see if one of our libraries has a copy in Prospector.
		$shortId = substr($record['id'], 1, -1);
		$library = new Library();
		$institutions = '';
		foreach ($record['institution'] as $institution){
			if (strlen($institutions) > 0){
				$institutions .= ', ';
			}
			$institutions .= "'" . mysql_escape_string($institution) . "'";
		}
		$library->whereAdd("facetLabel IN ($institutions)");
		$library->whereAdd("prospectorCode != ''");
		$library->find();
		$results = array(
          'recordId' => '',
          'title' => $record['title'],
          'author' => isset($record['author']) ? $record['author'] : null,
          'numLibraries' => 0,
          'owningLibraries' => array(),
          'prospectorClassicUrl' => '',
          'prospectorEncoreUrl' => '',
		);

		if ($library->N > 0){
			while ($library->fetch()){
				$prospectorCode = $library->prospectorCode;

				//Call the url for the record using a local call number search
				$curl_url = "http://prospector.coalliance.org/search~S0?/z$prospectorCode+$shortId/z$prospectorCode+$shortId/1,1,1,B/detlframeset&FF=z$prospectorCode+$shortId";

				$cookieJar = tempnam ("/tmp", "CURLCOOKIE");

				$logger->log('Loading page ' . $curl_url, PEAR_LOG_INFO);

				$curl_connection = curl_init($curl_url);
				curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
				curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
				curl_setopt($curl_connection, CURLOPT_COOKIESESSION, false);
				$sresult = curl_exec($curl_connection);

				//We are getting bad results from Prospector from some local call number searches
				if (preg_match('/Prehistoric Europe, from stone age man to the early Greeks/', $sresult)){
					continue;
				}

				//Parse the page to extract the owning libraries and the record id
				//Record Id can be extracted from the save link
				if (preg_match('/save=(.*?)"/s', $sresult, $matches)) {
					$results['recordId'] = $matches[1];
				}

				//Owning libraries can be extracted with this regex
				$libraries = array();
				if (preg_match_all('/<tr class="holdings(.*?)"><td><a name="(.*?)"><\/a>(.*?)<\/td>/s', $sresult, $matches)) {
					foreach ($matches[1] as $index=>$libraryCode){
						$libraries[$libraryCode] = $matches[3][$index];
					}
				} else {
					$fineInfo = "";
				}
				$results['numLibraries'] = count($libraries);
				$results['owningLibraries'] = $libraries;
				$prospectorUrl = "http://prospector.coalliance.org/search~S0/.{$results['recordId']}/.{$results['recordId']}/1,1,1,B/frameset~.{$results['recordId']}";
				$results['prospectorClassicUrl'] = $prospectorUrl;
				$prospectorUrl = "http://encore.coalliance.org/iii/encore/record/C|R" . urlencode($results['recordId']) ."?lang=eng&amp;suite=def";
				$results['prospectorEncoreUrl'] = $prospectorUrl;
				$requestUrl = "http://encore.coalliance.org/iii/encore/InnreachRequestPage.external?lang=eng&sp=S" . urlencode($results['recordId']) ."&suite=def";
				$results['requestUrl'] = $requestUrl;
				break;
			}
		}
		return $results;
	}
}