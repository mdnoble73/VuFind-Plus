<?php
require_once('Drivers/marmot_inc/ISBNConverter.php') ;

class Novelist{

	function loadEnrichment($isbn, $loadSeries = true, $loadSimilarTitles = true, $loadSimilarAuthors = true){
		global $library;
		global $timer;
		global $configArray;
		global $memcache;

		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}
		
		$enrichment = array();
		if (!isset($isbn) || strlen($isbn) == 0){
			return null;
		}
		
		$enrichment = $memcache->get("novelist_enrichment_$isbn");
		if ($enrichment == false){
			
			//$requestUrl = "http://eit.ebscohost.com/Services/NovelistSelect.asmx/SeriesTitles?prof=$profile&pwd=$pwd&authType=&ipprof=&isbn={$this->isbn}";
			$requestUrl = "http://eit.ebscohost.com/Services/NovelistSelect.asmx/AllContent?prof=$profile&pwd=$pwd&authType=&ipprof=&isbn={$isbn}";
	
			try{
				//Get the XML from the service
				disableErrorHandler();
				$req = new Proxy_Request($requestUrl);
				//$result = file_get_contents($req);
				if (PEAR::isError($req->sendRequest())) {
					enableErrorHandler();
					return null;
				}
				enableErrorHandler();
				
				$response = $req->getResponseBody();
				$timer->logTime("Made call to Novelist for enrichment information");
	
				//Parse the XML
				$data = new SimpleXMLElement($response);
				//Convert the data into a structure suitable for display
				if (isset($data->Features->FeatureGroup)){
					foreach ($data->Features->FeatureGroup as $featureGroup){
						$groupType = (string)$featureGroup->attributes()->type;
						foreach ($featureGroup->Feature as $feature){
							$featureType = (string)$feature->attributes()->type;
							if ($featureType == 'SeriesTitles' && $loadSeries){
								$this->loadSeriesInfo($isbn, $feature, $enrichment);
								$timer->logTime("Loaded enrichment series info");
							}else if ($featureType == 'SimilarTitles' && $loadSimilarTitles){
								$this->loadSimilarTitleInfo($isbn, $feature, $enrichment);
								$timer->logTime("Loaded similar title info");
							}else if ($featureType == 'SimilarAuthors' && $loadSimilarAuthors){
								$this->loadSimilarAuthorInfo($isbn, $feature, $enrichment);
								$timer->logTime("Loaded similar title info");
							}
	
							//TODO: Load Related Content (Awards and Recommended Reading Lists)
							//      For now, don't worry about this since the data is not worth using
	
						}
					}
	
				}else{
					$enrichment = null;
				}
	
			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
				$enrichment = null;
			}
			
			$memcache->set("novelist_enrichment_$isbn", $enrichment, 0, $configArray['Caching']['novelist_enrichement']);
		}
		
		return $enrichment;
	}

	function loadSimilarAuthorInfo($originalIsbn, $feature, &$enrichment){
		$authors = array();
		$items = $feature->Item;
		foreach ($items as $item){
			$authors[] = (string)$item->Name;
		}
		if (count($authors) > 0){
			$authors = array_slice($authors, 0, 10);
		}
		$enrichment['authors'] = $authors;
		$enrichment['similarAuthorCount'] = count($authors);
	}

	function loadSeriesInfo($originalIsbn, $feature, &$enrichment){
		$seriesTitles = array();
		$items = $feature->Item;
		$titlesOwned = 0;
		foreach ($items as $item){
			$this->loadNoveListTitle($originalIsbn, $item, $seriesTitles, $titlesOwned);
		}
		$enrichment['series'] = $seriesTitles;
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

	function loadSimilarTitleInfo($originalIsbn, $feature, &$enrichment){
		$similarTitles = array();
		$items = $feature->Item;
		$titlesOwned = 0;
		foreach ($items as $item){
			$this->loadNoveListTitle($originalIsbn, $item, $similarTitles, $titlesOwned);
		}
		$enrichment['similarTitles'] = $similarTitles;
		$enrichment['similarTitleCount'] = count($items);
		$enrichment['similarTitleCountOwned'] = $titlesOwned;
	}

	function loadNoveListTitle($originalIsbn, $item, &$titleList, &$titlesOwned){

		$isbnList = array();
		foreach($item->TitleList->TitleItem as $titleItem){
			$tmpIsbn = (string)$titleItem->attributes()->value;
			if (strlen($tmpIsbn) == 10 || strlen($tmpIsbn) == 13){
				$isbnList[] = (string)$titleItem->attributes()->value;
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
		//Add a filter to only include books and DVDs
		$searchObj->processSearch(false, false);
		$matchingRecords = $searchObj->getResultRecordSet();
		$isCurrent = in_array($originalIsbn, $isbnList);
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
			//See if we can get the series title from the record
			if (isset($ownedRecord['series'])){
				$series = $ownedRecord['series'][0];
			}else{
				$series = '';
			}
			$titleList[] = array(
                'title' => $ownedRecord['title'],
                'title_short' => isset($ownedRecord['title_short']) ? $ownedRecord['title_short'] : $ownedRecord['title'],
                'author' => isset($ownedRecord['author']) ? $ownedRecord['author'] : '',
                'publicationDate' => (string)$item->PublicationDate,
                'isbn' => $isbn13,
                'isbn10' => $isbn10,
                'upc' => isset($ownedRecord['upc'][0]) ? $ownedRecord['upc'][0] : '',
                'recordId' => $ownedRecord['id'],
                'id' => $ownedRecord['id'], //This allows the record to be displayed in various locations.
                'libraryOwned' => true,
                'isCurrent' => $isCurrent,
                'shortId' => substr($ownedRecord['id'], 1),
                'format_category' => $ownedRecord['format_category'],
                'format' => $ownedRecord['format'],
                'series' => $series,
			);
			$titlesOwned++;
		}else{
			$isbn = $isbnList[0];
			$isbn13 = strlen($isbn) == 13 ? $isbn : ISBNConverter::convertISBN10to13($isbn);
			$isbn10 = strlen($isbn) == 10 ? $isbn : ISBNConverter::convertISBN13to10($isbn);
			$titleList[] = array(
                'title' => (string)$item->Name,
                'author' => (string)$item->Author,
                'publicationDate' => (string)$item->PublicationDate,
                'isbn' => $isbn13,
                'isbn10' => $isbn10,
                'recordId' => -1,
                'libraryOwned' => false,
                'isCurrent' => $isCurrent,
			);
		}
	}
}