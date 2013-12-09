<?php
require_once(ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php');
require_once ROOT_DIR . '/sys/Novelist/NovelistData.php';
class Novelist3{

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
								$this->loadSeriesInfoFast($data->FeatureContent->SeriesInfo, $novelistData);
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
		}//Don't need to do an update

		if ($recordExists){
			$ret = $novelistData->update();
		}else{
			$ret = $novelistData->insert();
		}

		$memCache->set("novelist_enrichment_$groupedRecordId", $novelistData, 0, $configArray['Caching']['novelist_enrichment']);
		return $novelistData;
	}

	function loadSimilarAuthorInfo($feature, &$enrichment){
		$authors = array();
		$items = $feature->authors;
		foreach ($items as $item){
			$authors[] = array(
				'name' => $item->full_name,
				'reason' => $item->reason,
				'link' => '/Union/Search/?basicType=Author&lookfor='. urlencode($item->full_name),
			);
		}
		$enrichment->authors = $authors;
		$enrichment->similarAuthorCount = count($authors);
	}

	function loadSeriesInfoFast($seriesData, &$novelistData){
		$seriesName = $seriesData->full_title;
		$items = $seriesData->series_titles;
		foreach ($items as $item){
			if ($item->primary_isbn == $novelistData->primaryIsbn){
				$novelistData->volume = $item->volume;
			}
		}
		$novelistData->seriesTitle = $seriesName;
		$novelistData->seriesNote = $seriesData->series_note;
	}

	function loadSeriesInfo($currentId, $seriesData, &$novelistData){
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

	function loadSimilarSeries($similarSeriesData, &$enrichment){
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

	function loadSimilarTitleInfo($currentId, $similarTitles, &$enrichment){
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

	function loadNoveListTitle($currentId, $item, &$titleList, &$titlesOwned, $seriesName = ''){
		global $user;

		//Find the correct grouped work based on the isbns;
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkIdentifier.php';

		/** @var SimpleXMLElement $titleItem */
		$permanentId = null;
		foreach($item->isbns as $isbn){
			if (strlen($isbn) == 10 || strlen($isbn) == 13){
				$groupedWorkIdentifier = new GroupedWorkIdentifier();
				$groupedWork = new GroupedWork();
				$groupedWorkIdentifier->type = "isbn";
				$groupedWorkIdentifier->identifier = $isbn;
				$groupedWorkIdentifier->joinAdd($groupedWork);
				if ($groupedWorkIdentifier->find(true)){
					$permanentId = $groupedWorkIdentifier->permanent_id;
					break;
				}
			}
		}
		$isCurrent = $currentId == $permanentId;
		if (isset($seriesName)){
			$series = $seriesName;
		}else{
			$series = null;
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
				'reason' => isset($item->reason) ? $item->reason : ''
			);
		}else{
			//Get more information from Solr
			//TODO:  cache this info since it can take a really long time to load
			$searchObj = SearchObjectFactory::initSearchObject();
			$searchObj->setBasicQuery("id:$permanentId");
			$searchObj->disableScoping();
			//Add a filter to only include books and DVDs
			$searchObj->processSearch(false, false);
			$matchingRecords = $searchObj->getResultRecordSet();

			if (count($matchingRecords) > 0){
				$ownedRecord = $matchingRecords[0];
				if (strpos($ownedRecord['isbn'][0], ' ') > 0){
					$isbnInfo = explode(' ', $ownedRecord['isbn'][0]);
					$isbn = $isbnInfo[0];
				}else{
					$isbn = $ownedRecord['isbn'][0];
				}
				$isbn13 = strlen($isbn) == 13 ? $isbn : ISBNConverter::convertISBN10to13($isbn);
				$isbn10 = strlen($isbn) == 10 ? $isbn : ISBNConverter::convertISBN13to10($isbn);
				if (!isset($series)){
					if (isset($ownedRecord['series'])){
						$series = $ownedRecord['series'][0];
					}
				}
				//Load rating data
				if ($ownedRecord['recordtype'] == 'marc'){
					$resource = new Resource();
					$resource->source = 'VuFind';
					$resource->record_id = $ownedRecord['id'];
					$resource->find(true);
					$ratingData = $resource->getRatingData($user);
					$fullRecordLink = '/Record/' . $ownedRecord['id'] . '/Home';
				}else{
					require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
					$shortId = str_replace('econtentRecord', '', $ownedRecord['id']);
					$econtentRating = new EContentRating();
					$econtentRating->recordId = $shortId;
					$ratingData = $econtentRating->getRatingData($user, false);
					$fullRecordLink = '/EcontentRecord/' . $shortId . '/Home';
				}


				//See if we can get the series title from the record
				$curTitle = array(
					'title' => $ownedRecord['title'],
					'title_short' => isset($ownedRecord['title_short']) ? $ownedRecord['title_short'] : $ownedRecord['title'],
					'author' => isset($ownedRecord['author']) ? $ownedRecord['author'] : '',
					//'publicationDate' => (string)$item->PublicationDate,
					'isbn' => $isbn13,
					'isbn10' => $isbn10,
					'upc' => isset($ownedRecord['upc'][0]) ? $ownedRecord['upc'][0] : '',
					'recordId' => $ownedRecord['id'],
					'recordtype' => $ownedRecord['recordtype'],
					'id' => $ownedRecord['id'], //This allows the record to be displayed in various locations.
					'libraryOwned' => true,
					'isCurrent' => $isCurrent,
					'shortId' => substr($ownedRecord['id'], 1),
					'format_category' => isset($ownedRecord['grouping_category']) ? $ownedRecord['grouping_category'] : '',
					'series' => $series,
					'volume' => $volume,
					'ratingData' => $ratingData,
					'fullRecordLink' => $fullRecordLink,
					'reason' => isset($item->reason) ? $item->reason : '',
				);
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
					'reason' => isset($item->reason) ? $item->reason : ''
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