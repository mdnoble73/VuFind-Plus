<?php
require_once(ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php');
require_once ROOT_DIR . '/sys/Novelist/NovelistData.php';
class Novelist3{

	function doesGroupedWorkHaveCachedSeries($groupedRecordId){
		$novelistData = new NovelistData();
		if ($groupedRecordId != null && $groupedRecordId != ''){
			$novelistData->groupedRecordPermanentId = $groupedRecordId;
			if ($novelistData->find(true)){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	function loadBasicEnrichment($groupedRecordId, $isbns){
		global $timer;
		global $configArray;

		//First make sure that Novelist is enabled
		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}

		if ($groupedRecordId == null || $groupedRecordId == ''){
			return null;
		}

		//Check to see if we have cached data, first check MemCache.
		/** @var Memcache $memCache */
		global $memCache;
		$novelistData = $memCache->get("novelist_enrichment_basic_$groupedRecordId");
		if ($novelistData != false && !isset($_REQUEST['reload'])){
			return $novelistData;
		}

		$timer->logTime("Starting to load data from novelist for $groupedRecordId");
		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$doUpdate = true;
		$recordExists = false;
		if ($novelistData->find(true)){
			$recordExists = true;
			$doUpdate = false;
			//We already have data loaded, make sure the data is still "fresh"

			//First check to see if the record had isbns before we update
			if ($novelistData->groupedRecordHasISBN || count($isbns) > 0){
				//We do have at least one ISBN
				//If it's been more than 30 days since we updated, update 20% of the time
				//We do it randomly to spread out the updates.
				$now = time();
				if ($novelistData->lastUpdate < $now - (30 * 24 * 60 * 60)){
					$random = rand(1, 100);
					if ($random <= 20){
						$doUpdate = true;
					}
				}
			}//else, no ISBNs, don't update

		}

		$novelistData->groupedRecordHasISBN = count($isbns) > 0;

		//Check to see if a reload is being forced
		if (isset($_REQUEST['reload'])){
			$doUpdate = true;
		}

		//Check to see if we need to do an update
		if (!$recordExists || $doUpdate){
			if ($recordExists && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0 && !isset($_REQUEST['reload'])){
				//Just check the primary ISBN since we know that was good.
				$isbns = array($novelistData->primaryISBN);
			}

			//Update the last update time to optimize caching
			$novelistData->lastUpdate = time();

			if (count($isbns) == 0){
				//Whoops, no ISBNs, can't get enrichment for this
				$novelistData->hasNovelistData = false;
			}else{
				$novelistData->hasNovelistData = false;

				//Check each ISBN for enrichment data
				foreach ($isbns as $isbn){
					$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
					//echo($requestUrl);
					try{
						//Get the JSON from the service
						disableErrorHandler();
						$req = new Proxy_Request($requestUrl);
						//$result = file_get_contents($req);
						if (PEAR_Singleton::isError($req->sendRequest())) {
							enableErrorHandler();
							//No enrichment for this isbn, go to the next one
							continue;
						}
						enableErrorHandler();

						$response = $req->getResponseBody();
						$timer->logTime("Made call to Novelist to get basic enrichment info $isbn");

						//Parse the JSON
						$data = json_decode($response);
						//print_r($data);

						//Related ISBNs

						if (isset($data->FeatureContent) && $data->FeatureCount > 0){
							$novelistData->hasNovelistData = true;
							//We got data!
							$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

							//Series Information
							if (isset($data->FeatureContent->SeriesInfo)){
								$this->loadSeriesInfoFast($data->FeatureContent->SeriesInfo, $novelistData);
								$timer->logTime("loaded series data");
							}

							//We got good data, quit looking at ISBNs
							break;
						}
					}catch (Exception $e) {
						global $logger;
						$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
						if (isset($response)){
							$logger->log($response, PEAR_LOG_DEBUG);
						}
						$enrichment = null;
					}
				}//Loop on each ISBN
			}//Check for number of ISBNs
		}//Don't need to do an update

		if ($recordExists){
			if ($doUpdate){
				$ret = $novelistData->update();
			}
		}else{
			$ret = $novelistData->insert();
		}

		$memCache->set("novelist_enrichment_basic_$groupedRecordId", $novelistData, 0, $configArray['Caching']['novelist_enrichment']);
		return $novelistData;
	}

	/**
	 * Loads Novelist data from Novelist for a grouped record
	 *
	 * @param String    $groupedRecordId  The permanent id of the grouped record
	 * @param String[]  $isbns            a list of ISBNs for the record
	 * @return NovelistData
	 */
	function loadEnrichment($groupedRecordId, $isbns){
		global $timer;
		global $configArray;

		//First make sure that Novelist is enabled
		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}

		if ($groupedRecordId == null || $groupedRecordId == ''){
			return null;
		}

		//Check to see if we have cached data, first check MemCache.
		/** @var Memcache $memCache */
		global $memCache;
		$novelistData = $memCache->get("novelist_enrichment_$groupedRecordId");
		if ($novelistData != false && !isset($_REQUEST['reload'])){
			return $novelistData;
		}

		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$recordExists = false;
		if ($novelistData->find(true)){
			$recordExists = true;
			//We already have data loaded, make sure the data is still "fresh"

			//First check to see if the record had isbns before we update
			if ($novelistData->groupedRecordHasISBN || count($isbns) > 0){
				//We do have at least one ISBN
				//If it's been more than 30 days since we updated, update 20% of the time
				//We do it randomly to spread out the updates.
				$now = time();
				if ($novelistData->lastUpdate < $now - (30 * 24 * 60 * 60)){
					$random = rand(1, 100);
					if ($random <= 20){
						$doUpdate = true;
					}
				}
			}//else, no ISBNs, don't update

		}

		$novelistData->groupedRecordHasISBN = count($isbns) > 0;

		//When loading full data, we aways need to load the data since we can't cache due to terms of sevice
		if ($recordExists && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0){
			//Just check the primary ISBN since we know that was good.
			$isbns = array($novelistData->primaryISBN);
		}

		//Update the last update time to optimize caching
		$novelistData->lastUpdate = time();

		if (count($isbns) == 0){
			//Whoops, no ISBNs, can't get enrichment for this
			$novelistData->hasNovelistData = false;
		}else{
			$novelistData->hasNovelistData = false;

			//Check each ISBN for enrichment data
			foreach ($isbns as $isbn){
				$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
				//echo($requestUrl);
				try{
					//Get the JSON from the service
					disableErrorHandler();
					$req = new Proxy_Request($requestUrl);
					//$result = file_get_contents($req);
					if (PEAR_Singleton::isError($req->sendRequest())) {
						enableErrorHandler();
						//No enrichment for this isbn, go to the next one
						continue;
					}
					enableErrorHandler();

					$response = $req->getResponseBody();
					$timer->logTime("Made call to Novelist for enrichment information");

					//Parse the JSON
					$data = json_decode($response);
					//print_r($data);

					//Related ISBNs

					if (isset($data->FeatureContent) && $data->FeatureCount > 0){
						$novelistData->hasNovelistData = true;
						//We got data!
						$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

						//Series Information
						if (isset($data->FeatureContent->SeriesInfo)){
							$this->loadSeriesInfo($groupedRecordId, $data->FeatureContent->SeriesInfo, $novelistData);
						}

						//Similar Titles
						if (isset($data->FeatureContent->SimilarTitles)){
							$this->loadSimilarTitleInfo($groupedRecordId, $data->FeatureContent->SimilarTitles, $novelistData);
						}

						//Similar Authors
						if (isset($data->FeatureContent->SimilarAuthors)){
							$this->loadSimilarAuthorInfo($data->FeatureContent->SimilarAuthors, $novelistData);
						}

						//Similar Series
						if (isset($data->FeatureContent->SimilarSeries)){
							$this->loadSimilarSeries($data->FeatureContent->SimilarSeries, $novelistData);
						}

						//Related Content
						if (isset($data->FeatureContent->RelatedContent)){
							$this->loadRelatedContent($data->FeatureContent->RelatedContent, $novelistData);
						}

						//GoodReads Ratings
						if (isset($data->FeatureContent->GoodReads)){
							$this->loadGoodReads($data->FeatureContent->GoodReads, $novelistData);
						}

						//print_r($data);
						//We got good data, quit looking at ISBNs
						break;
					}
				}catch (Exception $e) {
					global $logger;
					$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
					if (isset($response)){
						$logger->log($response, PEAR_LOG_DEBUG);
					}
					$enrichment = null;
				}
			}//Loop on each ISBN
		}//Check for number of ISBNs

		if ($recordExists){
			$ret = $novelistData->update();
		}else{
			$ret = $novelistData->insert();
		}

		$memCache->set("novelist_enrichment_$groupedRecordId", $novelistData, 0, $configArray['Caching']['novelist_enrichment']);
		return $novelistData;
	}

	/**
	 * Loads Novelist data from Novelist for a grouped record
	 *
	 * @param String    $groupedRecordId  The permanent id of the grouped record
	 * @param String[]  $isbns            a list of ISBNs for the record
	 * @return NovelistData
	 */
	function getSimilarTitles($groupedRecordId, $isbns){
		global $timer;
		global $configArray;

		//First make sure that Novelist is enabled
		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}

		if ($groupedRecordId == null || $groupedRecordId == ''){
			return null;
		}

		//Check to see if we have cached data, first check MemCache.
		/** @var Memcache $memCache */
		global $memCache;
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$novelistData = $memCache->get("novelist_similar_titles_$groupedRecordId");
		if ($novelistData != false && !isset($_REQUEST['reload'])){
			return $novelistData;
		}

		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$recordExists = false;
		if ($novelistData->find(true)){
			$recordExists = true;
			//We already have data loaded, make sure the data is still "fresh"

			//First check to see if the record had isbns before we update
			if ($novelistData->groupedRecordHasISBN || count($isbns) > 0){
				//We do have at least one ISBN
				//If it's been more than 30 days since we updated, update 20% of the time
				//We do it randomly to spread out the updates.
				$now = time();
				if ($novelistData->lastUpdate < $now - (30 * 24 * 60 * 60)){
					$random = rand(1, 100);
					if ($random <= 20){
						$doUpdate = true;
					}
				}
			}//else, no ISBNs, don't update

		}

		$novelistData->groupedRecordHasISBN = count($isbns) > 0;

		//When loading full data, we aways need to load the data since we can't cache due to terms of sevice
		if ($recordExists && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0){
			//Just check the primary ISBN since we know that was good.
			$isbns = array($novelistData->primaryISBN);
		}

		//Update the last update time to optimize caching
		$novelistData->lastUpdate = time();

		if (count($isbns) == 0){
			//Whoops, no ISBNs, can't get enrichment for this
			$novelistData->hasNovelistData = false;
		}else{
			$novelistData->hasNovelistData = false;

			//Check each ISBN for enrichment data
			foreach ($isbns as $isbn){
				$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
				//echo($requestUrl);
				try{
					//Get the JSON from the service
					disableErrorHandler();
					$req = new Proxy_Request($requestUrl);
					//$result = file_get_contents($req);
					if (PEAR_Singleton::isError($req->sendRequest())) {
						enableErrorHandler();
						//No enrichment for this isbn, go to the next one
						continue;
					}
					enableErrorHandler();

					$response = $req->getResponseBody();
					$timer->logTime("Made call to Novelist for enrichment information");

					//Parse the JSON
					$data = json_decode($response);
					//print_r($data);

					//Related ISBNs

					if (isset($data->FeatureContent) && $data->FeatureCount > 0){
						$novelistData->hasNovelistData = true;
						//We got data!
						$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

						//Similar Titles
						if (isset($data->FeatureContent->SimilarTitles)){
							$this->loadSimilarTitleInfo($groupedRecordId, $data->FeatureContent->SimilarTitles, $novelistData);
						}

						//We got good data, quit looking at ISBNs
						break;
					}
				}catch (Exception $e) {
					global $logger;
					$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
					if (isset($response)){
						$logger->log($response, PEAR_LOG_DEBUG);
					}
					$enrichment = null;
				}
			}//Loop on each ISBN
		}//Check for number of ISBNs

		if ($recordExists){
			$ret = $novelistData->update();
		}else{
			$ret = $novelistData->insert();
		}

		$memCache->set("novelist_similar_titles_$groupedRecordId", $novelistData, 0, $configArray['Caching']['novelist_enrichment']);
		return $novelistData;
	}

	/**
	 * Loads Novelist data from Novelist for a grouped record
	 *
	 * @param String    $groupedRecordId  The permanent id of the grouped record
	 * @param String[]  $isbns            a list of ISBNs for the record
	 * @return NovelistData
	 */
	function getSimilarAuthors($groupedRecordId, $isbns){
		global $timer;
		global $configArray;

		//First make sure that Novelist is enabled
		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}

		if ($groupedRecordId == null || $groupedRecordId == ''){
			return null;
		}

		//Check to see if we have cached data, first check MemCache.
		/** @var Memcache $memCache */
		global $memCache;
		$novelistData = $memCache->get("novelist_similar_authors_$groupedRecordId");
		if ($novelistData != false && !isset($_REQUEST['reload'])){
			return $novelistData;
		}

		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$recordExists = false;
		if ($novelistData->find(true)){
			$recordExists = true;
		}

		$novelistData->groupedRecordHasISBN = count($isbns) > 0;

		//When loading full data, we aways need to load the data since we can't cache due to terms of sevice
		if ($recordExists && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0){
			//Just check the primary ISBN since we know that was good.
			$isbns = array($novelistData->primaryISBN);
		}

		//Update the last update time to optimize caching
		$novelistData->lastUpdate = time();

		if (count($isbns) == 0){
			//Whoops, no ISBNs, can't get enrichment for this
			$novelistData->hasNovelistData = false;
		}else{
			$novelistData->hasNovelistData = false;

			//Check each ISBN for enrichment data
			foreach ($isbns as $isbn){
				$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
				//echo($requestUrl);
				try{
					//Get the JSON from the service
					disableErrorHandler();
					$req = new Proxy_Request($requestUrl);
					//$result = file_get_contents($req);
					if (PEAR_Singleton::isError($req->sendRequest())) {
						enableErrorHandler();
						//No enrichment for this isbn, go to the next one
						continue;
					}
					enableErrorHandler();

					$response = $req->getResponseBody();
					$timer->logTime("Made call to Novelist for enrichment information");

					//Parse the JSON
					$data = json_decode($response);
					//print_r($data);

					//Related ISBNs

					if (isset($data->FeatureContent) && $data->FeatureCount > 0){
						$novelistData->hasNovelistData = true;
						//We got data!
						$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

						//Similar Authors
						if (isset($data->FeatureContent->SimilarAuthors)){
							$this->loadSimilarAuthorInfo($data->FeatureContent->SimilarAuthors, $novelistData);
						}

						//We got good data, quit looking at ISBNs
						break;
					}
				}catch (Exception $e) {
					global $logger;
					$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
					if (isset($response)){
						$logger->log($response, PEAR_LOG_DEBUG);
					}
					$enrichment = null;
				}
			}//Loop on each ISBN
		}//Check for number of ISBNs

		$memCache->set("novelist_similar_authors_$groupedRecordId", $novelistData, 0, $configArray['Caching']['novelist_enrichment']);
		return $novelistData;
	}

	private function loadSimilarAuthorInfo($feature, &$enrichment){
		$authors = array();
		$items = $feature->authors;
		foreach ($items as $item){
			$authors[] = array(
				'name' => $item->full_name,
				'reason' => $item->reason,
				'link' => '/Author/Home/?author='. urlencode($item->full_name),
			);
		}
		$enrichment->authors = $authors;
		$enrichment->similarAuthorCount = count($authors);
	}

	/**
	 * Loads Novelist data from Novelist for a grouped record
	 *
	 * @param String    $groupedRecordId  The permanent id of the grouped record
	 * @param String[]  $isbns            a list of ISBNs for the record
	 * @return NovelistData
	 */
	function getSeriesTitles($groupedRecordId, $isbns){
		global $timer;
		global $configArray;

		//First make sure that Novelist is enabled
		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}

		if ($groupedRecordId == null || $groupedRecordId == ''){
			return null;
		}

		//Check to see if we have cached data, first check MemCache.
		/** @var Memcache $memCache */
		global $memCache;
		$novelistData = $memCache->get("novelist_series_$groupedRecordId");
		if ($novelistData != false && !isset($_REQUEST['reload'])){
			return $novelistData;
		}

		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$recordExists = false;
		if ($novelistData->find(true)){
			$recordExists = true;
		}

		$novelistData->groupedRecordHasISBN = count($isbns) > 0;

		//When loading full data, we aways need to load the data since we can't cache due to terms of sevice
		if ($recordExists && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0){
			//Just check the primary ISBN since we know that was good.
			$isbns = array($novelistData->primaryISBN);
		}

		//Update the last update time to optimize caching
		$novelistData->lastUpdate = time();

		if (count($isbns) == 0){
			//Whoops, no ISBNs, can't get enrichment for this
			$novelistData->hasNovelistData = false;
		}else{
			$novelistData->hasNovelistData = false;

			//Check each ISBN for enrichment data
			foreach ($isbns as $isbn){
				$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
				//echo($requestUrl);
				try{
					//Get the JSON from the service
					disableErrorHandler();
					$req = new Proxy_Request($requestUrl);
					//$result = file_get_contents($req);
					if (PEAR_Singleton::isError($req->sendRequest())) {
						enableErrorHandler();
						//No enrichment for this isbn, go to the next one
						continue;
					}
					enableErrorHandler();

					$response = $req->getResponseBody();
					$timer->logTime("Made call to Novelist for enrichment information");

					//Parse the JSON
					$data = json_decode($response);
					//print_r($data);

					//Related ISBNs

					if (isset($data->FeatureContent) && $data->FeatureCount > 0){
						$novelistData->hasNovelistData = true;
						//We got data!
						$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

						//Series Information
						if (isset($data->FeatureContent->SeriesInfo)){
							$this->loadSeriesInfo($groupedRecordId, $data->FeatureContent->SeriesInfo, $novelistData);
						}

						//We got good data, quit looking at ISBNs
						break;
					}
				}catch (Exception $e) {
					global $logger;
					$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
					if (isset($response)){
						$logger->log($response, PEAR_LOG_DEBUG);
					}
					$enrichment = null;
				}
			}//Loop on each ISBN
		}//Check for number of ISBNs

		$memCache->set("novelist_series_$groupedRecordId", $novelistData, 0, $configArray['Caching']['novelist_enrichment']);
		return $novelistData;
	}

	/**
	 * @param SimpleXMLElement $seriesData
	 * @param NovelistData $novelistData
	 */
	private function loadSeriesInfoFast($seriesData, &$novelistData){
		$seriesName = $seriesData->full_title;
		$items = $seriesData->series_titles;
		foreach ($items as $item){
			if ($item->primary_isbn == $novelistData->primaryISBN){
				$novelistData->volume = $item->volume;
			}
		}
		$novelistData->seriesTitle = $seriesName;
		$novelistData->seriesNote = $seriesData->series_note;
	}

	private function loadSeriesInfo($currentId, $seriesData, &$novelistData){
		$seriesName = $seriesData->full_title;
		$seriesTitles = array();
		$items = $seriesData->series_titles;
		$titlesOwned = 0;
		foreach ($items as $item){
			$curTitle = $this->loadNoveListTitle($currentId, $item, $seriesTitles, $titlesOwned, $seriesName);
			if ($curTitle['isCurrent'] && isset($curTitle['volume']) && strlen($curTitle['volume']) > 0){
				$enrichment['volumeLabel'] = (isset($curTitle['volume']) ? ('volume ' . $curTitle['volume']) : '');
				$novelistData->volume = $curTitle['volume'];
			}
		}
		$novelistData->seriesTitles = $seriesTitles;
		$novelistData->seriesTitle = $seriesName;
		$novelistData->seriesNote = $seriesData->series_note;

		$novelistData->seriesCount = count($items);
		$novelistData->seriesCountOwned = $titlesOwned;
		$novelistData->seriesDefaultIndex = 1;
		$curIndex = 0;
		foreach ($seriesTitles as $title){

			if ($title['isCurrent']){
				$novelistData->seriesDefaultIndex = $curIndex;
			}
			$curIndex++;
		}
	}

	private function loadSimilarSeries($similarSeriesData, &$enrichment){
		$similarSeries = array();
		foreach ($similarSeriesData->series as $similarSeriesInfo){
			$similarSeries[] = array(
				'title' => $similarSeriesInfo->full_name,
				'author' => $similarSeriesInfo->author,
				'reason' => $similarSeriesInfo->reason,
				'link' => 'Union/Search/?lookfor='. $similarSeriesInfo->full_name . " AND " . $similarSeriesInfo->author,
			);
		}
		$enrichment->similarSeries = $similarSeries;
		$enrichment->similarSeriesCount = count($similarSeries);
	}

	private function loadSimilarTitleInfo($currentId, $similarTitles, &$enrichment){
		$items = $similarTitles->titles;
		$titlesOwned = 0;
		$similarTitlesReturn = array();
		foreach ($items as $item){
			$this->loadNoveListTitle($currentId, $item, $similarTitlesReturn, $titlesOwned);
		}
		$enrichment->similarTitles = $similarTitlesReturn;
		$enrichment->similarTitleCount = count($items);
		$enrichment->similarTitleCountOwned = $titlesOwned;
	}

	private function loadNoveListTitle($currentId, $item, &$titleList, &$titlesOwned, $seriesName = ''){
		global $user;
		global $timer;
		global $configArray;

		//Find the correct grouped work based on the isbns;
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkIdentifierRef.php';

		$timer->logTime("Start loadNoveListTitle");
		/** @var SimpleXMLElement $titleItem */
		$permanentId = null;
		$concatenatedIsbns = "'" . implode("','",$item->isbns) . "'";
		$groupedWorkIdentifier = new GroupedWorkIdentifier();
		$groupedWorkIdentifier->type = "isbn";
		$groupedWorkIdentifier->whereAdd("identifier in ($concatenatedIsbns)");
		if ($groupedWorkIdentifier->find()){
			while ($groupedWorkIdentifier->fetch()){
				$groupedWorkIdentifierRef = new GroupedWorkIdentifierRef();
				$groupedWorkIdentifierRef->identifier_id = $groupedWorkIdentifier->id;
				$groupedWorkIdentifierRef->find();
				if ($groupedWorkIdentifierRef->N == 1){
					$groupedWorkIdentifierRef->fetch();
					$groupedWork = new GroupedWork();
					$groupedWork->id = $groupedWorkIdentifierRef->grouped_work_id;
					if ($groupedWork->find(true)){
						$permanentId = $groupedWork->permanent_id;
						break;
					}
				}
			}
		}
		$timer->logTime("Load Novelist Title - Find Grouped Work based on identifier $permanentId");
		$isCurrent = $currentId == $permanentId;
		if (isset($seriesName)){
			$series = $seriesName;
		}else{
			$series = '';
		}
		$volume = '';
		if (isset($item->volume)){
			$volume = $item->volume;
		}

		//We didn't find a match in the database so we don't own it
		if ($permanentId == null){
			$isbn = reset($item->isbns);
			$isbn13 = strlen($isbn) == 13 ? $isbn : ISBNConverter::convertISBN10to13($isbn);
			$isbn10 = strlen($isbn) == 10 ? $isbn : ISBNConverter::convertISBN13to10($isbn);
			$curTitle = array(
				'title' => $item->full_title,
				'author' => $item->author,
				//'publicationDate' => (string)$item->PublicationDate,
				'isbn' => $isbn13,
				'isbn10' => $isbn10,
				'recordId' => -1,
				'libraryOwned' => false,
				'isCurrent' => $isCurrent,
				'series' => $series,
				'volume' => $volume,
				'reason' => isset($item->reason) ? $item->reason : '',
				'smallCover' => $cover = $configArray['Site']['coverUrl'] . "/bookcover.php?size=small&isn=" . $isbn13,
				'mediumCover' => $cover = $configArray['Site']['coverUrl'] . "/bookcover.php?size=medium&isn=" . $isbn13,
			);
		}else{
			//Get more information from Solr
			/** @var GroupedWorkDriver $recordDriver */
			$recordDriver = new GroupedWorkDriver($permanentId);
			$timer->logTime("Find grouped work in solr");

			if ($recordDriver->isValid){
				if (!isset($series)){
					if (isset($ownedRecord['series'])){
						$series = $ownedRecord['series'][0];
					}
				}
				//Load data about the record
				$ratingData = $recordDriver->getRatingData($user);
				$timer->logTime("Get Rating data");
				$fullRecordLink = $recordDriver->getLinkUrl();

				//See if we can get the series title from the record
				$curTitle = array(
					'title' => $recordDriver->getTitle(),
					'title_short' => $recordDriver->getTitle(),
					'author' => $recordDriver->getPrimaryAuthor(),
					//'publicationDate' => (string)$item->PublicationDate,
					'isbn' => $recordDriver->getCleanISBN(),
					'isbn10' => $recordDriver->getCleanISBN(),
					'upc' => $recordDriver->getCleanUPC(),
					'recordId' => $recordDriver->getPermanentId(),
					'recordtype' => 'grouped_work',
					'id' => $recordDriver->getPermanentId(), //This allows the record to be displayed in various locations.
					'libraryOwned' => true,
					'isCurrent' => $isCurrent,
					'shortId' => $recordDriver->getPermanentId(),
					'format_category' => $recordDriver->getFormatCategory(),
					'series' => $series,
					'volume' => $volume,
					'ratingData' => $ratingData,
					'fullRecordLink' => $fullRecordLink,
					'reason' => isset($item->reason) ? $item->reason : '',
					'recordDriver' => $recordDriver,
					'smallCover' => $recordDriver->getBookcoverUrl('small'),
					'mediumCover' => $recordDriver->getBookcoverUrl('medium'),
				);
				$timer->logTime("Load title information");
				$titlesOwned++;
			}else{
				$isbn = reset($item->isbns);
				$isbn13 = strlen($isbn) == 13 ? $isbn : ISBNConverter::convertISBN10to13($isbn);
				$isbn10 = strlen($isbn) == 10 ? $isbn : ISBNConverter::convertISBN13to10($isbn);
				$curTitle = array(
					'title' => $item->full_title,
					'author' => $item->author,
					//'publicationDate' => (string)$item->PublicationDate,
					'isbn' => $isbn13,
					'isbn10' => $isbn10,
					'recordId' => -1,
					'libraryOwned' => false,
					'isCurrent' => $isCurrent,
					'series' => $series,
					'volume' => $volume,
					'reason' => isset($item->reason) ? $item->reason : '',
					'smallCover' => $cover = $configArray['Site']['coverUrl'] . "/bookcover.php?size=small&isn=" . $isbn13,
					'mediumCover' => $cover = $configArray['Site']['coverUrl'] . "/bookcover.php?size=medium&isn=" . $isbn13,
				);
			}
		}

		$titleList[] = $curTitle;
		return $curTitle;
	}

	private function loadRelatedContent($relatedContent, &$enrichment) {
		$relatedContentReturn = array();
		foreach ($relatedContent->doc_types as $contentSection){
			$section = array(
				'title' => $contentSection->doc_type,
				'content' => array(),
			);
			foreach ($contentSection->content as $content){
				//print_r($content);
				$contentUrl = $content->links[0]->url;
				$section['content'][] = array(
					'author' => $content->feature_author,
					'title' => $content->title,
					'contentUrl' => $contentUrl,
				);
			}
			$relatedContentReturn[] = $section;
		}
		$enrichment->relatedContent = $relatedContentReturn;
	}

	private function loadGoodReads($goodReads, &$enrichment) {
		$goodReadsInfo = array(
			'inGoodReads' => $goodReads->is_in_goodreads,
			'averageRating' => $goodReads->average_rating,
			'numRatings' => $goodReads->ratings_count,
			'numReviews' => $goodReads->reviews_count,
			'sampleReviewsUrl' => $goodReads->links[0]->url,
		);
		$enrichment->goodReads = $goodReadsInfo;
	}
}