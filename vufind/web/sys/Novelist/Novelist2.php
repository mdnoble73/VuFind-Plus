<?php
require_once(ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php');

class Novelist2{

	function loadEnrichment($isbn, $loadSeries = true, $loadSimilarTitles = true, $loadSimilarAuthors = true){
		global $timer;
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;

		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}

		if (!isset($isbn) || strlen($isbn) == 0){
			return null;
		}

		$enrichment = $memCache->get("novelist_enrichment_$isbn");
		if ($enrichment == false  || isset($_REQUEST['reload'])){
			$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
			//echo($requestUrl);
			try{
				//Get the JSON from the service
				disableErrorHandler();
				$req = new Proxy_Request($requestUrl);
				//$result = file_get_contents($req);
				if (PEAR_Singleton::isError($req->sendRequest())) {
					enableErrorHandler();
					return null;
				}
				enableErrorHandler();

				$response = $req->getResponseBody();
				$timer->logTime("Made call to Novelist for enrichment information");

				//Parse the JSON
				$data = json_decode($response);
				//print_r($data);

				//Related ISBNs

				if (isset($data->FeatureContent)){
					//Series Information
					if ($loadSeries && isset($data->FeatureContent->SeriesInfo)){
						$this->loadSeriesInfo($isbn, $data->FeatureContent->SeriesInfo, $enrichment);
					}

					//Similar Titles
					if ($loadSimilarTitles && isset($data->FeatureContent->SimilarTitles)){
						$this->loadSimilarTitleInfo($isbn, $data->FeatureContent->SimilarTitles, $enrichment);
					}

					//Similar Authors
					if ($loadSimilarAuthors && isset($data->FeatureContent->SimilarAuthors)){
						$this->loadSimilarAuthorInfo($data->FeatureContent->SimilarAuthors, $enrichment);
					}

					//Similar Series
					if ($loadSeries && isset($data->FeatureContent->SimilarSeries)){
						$this->loadSimilarSeries($data->FeatureContent->SimilarSeries, $enrichment);
					}

					//Related Content
					if (isset($data->FeatureContent->RelatedContent)){
						$this->loadRelatedContent($data->FeatureContent->RelatedContent, $enrichment);
					}

					//GoodReads Ratings
					if (isset($data->FeatureContent->GoodReads)){
						$this->loadGoodReads($data->FeatureContent->GoodReads, $enrichment);
					}

					//print_r($data);
				}
			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
				if (isset($response)){
					$logger->log($response, PEAR_LOG_DEBUG);
				}
				$enrichment = null;
			}

			$memCache->set("novelist_enrichment_$isbn", $enrichment, 0, $configArray['Caching']['novelist_enrichement']);
		}

		return $enrichment;
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
		$enrichment['authors'] = $authors;
		$enrichment['similarAuthorCount'] = count($authors);
	}

	function loadSeriesInfo($originalIsbn, $seriesData, &$enrichment){
		$seriesName = $seriesData->full_title;
		$seriesTitles = array();
		$items = $seriesData->series_titles;
		$titlesOwned = 0;
		foreach ($items as $item){
			$curTitle = $this->loadNoveListTitle($originalIsbn, $item, $seriesTitles, $titlesOwned, $seriesName);
			if ($curTitle['isCurrent'] && isset($curTitle['volume']) && strlen($curTitle['volume']) > 0){
				$enrichment['volumeLabel'] = (isset($curTitle['volume']) ? ('volume ' . $curTitle['volume']) : '');
			}
		}
		$enrichment['series'] = $seriesTitles;
		$enrichment['seriesTitle'] = $seriesName;
		$enrichment['seriesNote'] = $seriesData->series_note;
		$enrichment['seriesCount'] = count($items);
		$enrichment['seriesCountOwned'] = $titlesOwned;
		$enrichment['seriesDefaultIndex'] = 1;
		$curIndex = 0;
		foreach ($seriesTitles as $title){

			if ($title['isCurrent']){
				$enrichment['seriesDefaultIndex'] = $curIndex;
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
		$enrichment['similarSeries'] = $similarSeries;
		$enrichment['similarSeriesCount'] = count($similarSeries);
	}

	function loadSimilarTitleInfo($originalIsbn, $similarTitles, &$enrichment){
		$items = $similarTitles->titles;
		$titlesOwned = 0;
		$similarTitlesReturn = array();
		foreach ($items as $item){
			$this->loadNoveListTitle($originalIsbn, $item, $similarTitlesReturn, $titlesOwned);
		}
		$enrichment['similarTitles'] = $similarTitlesReturn;
		$enrichment['similarTitleCount'] = count($items);
		$enrichment['similarTitleCountOwned'] = $titlesOwned;
	}

	function loadNoveListTitle($originalIsbn, $item, &$titleList, &$titlesOwned, $seriesName = ''){
		global $user;
		$isbnList = array();
		/** @var SimpleXMLElement $titleItem */
		foreach($item->isbns as $isbn){
			if (strlen($isbn) == 10 || strlen($isbn) == 13){
				$isbnList[] = $isbn;
			}
		}
		//If there is no ISBN, don't bother loading the title
		if (count($isbnList) == 0){
			return;
		}
		//run a search to get the record id for the isbns.
		//TODO:  cache this info since it can take a really long time to load
		$searchObj = SearchObjectFactory::initSearchObject();
		$searchObj->setBasicQuery(implode(' OR ', $isbnList), 'ISN');
		$searchObj->disableScoping();
		//Add a filter to only include books and DVDs
		$searchObj->processSearch(false, false);
		$matchingRecords = $searchObj->getResultRecordSet();
		$isCurrent = in_array($originalIsbn, $isbnList);
		if (isset($seriesName)){
			$series = $seriesName;
		}else{
			$series = null;
		}
		$volume = '';
		if (isset($item->volume)){
			$volume = $item->volume;
		}
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
				'format_category' => $ownedRecord['format_category'],
				'format' => $ownedRecord['format'],
				'series' => $series,
				'volume' => $volume,
				'ratingData' => $ratingData,
				'fullRecordLink' => $fullRecordLink,
				'reason' => isset($item->reason) ? $item->reason : '',
				'grouping_term' => $ownedRecord['grouping_term'],
			);
			$titlesOwned++;
		}else{
			$isbn = $isbnList[0];
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
		$enrichment['relatedContent'] = $relatedContentReturn;
	}

	private function loadGoodReads($goodReads, &$enrichment) {
		$goodReadsInfo = array(
			'inGoodReads' => $goodReads->is_in_goodreads,
			'averageRating' => $goodReads->average_rating,
			'numRatings' => $goodReads->ratings_count,
			'numReviews' => $goodReads->reviews_count,
			'sampleReviewsUrl' => $goodReads->links[0]->url,
		);
		$enrichment['goodReads'] = $goodReadsInfo;
	}
}