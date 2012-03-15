<?php
require_once('Drivers/marmot_inc/ISBNConverter.php') ;

class GoDeeperData{
	function getGoDeeperOptions($isbn, $upc, $getDefaultData){
		global $configArray;
		$validEnrichmentTypes = array();
		//Load the index page from syndetics
		if (!isset($isbn) && !isset($upc)){
			return $validEnrichmentTypes;
		}

		//Marmot is maybe planning on using Syndetics Go-Deeper Data right now.
		$useSyndetics = true;
		if ($useSyndetics){
			$clientKey = $configArray['Syndetics']['key'];
			$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/INDEX.XML&client=$clientKey&type=xw10&upc=$upc";

			try{
				//Get the XML from the service
				$ctx = stream_context_create(array(
				  'http' => array(
				    'timeout' => 2
				)
				));
				$response =file_get_contents($requestUrl, 0, $ctx);

				//Parse the XML
				if (preg_match('/<!DOCTYPE\\sHTML.*/', $response)) {
					//The ISBN was not found in syndetics (we got an error message)
				} else {
					//Got a valid response
					$data = new SimpleXMLElement($response);

					$validEnrichmentTypes = array();
					if (isset($data)){
						if ($configArray['Syndetics']['showSummary'] && isset($data->SUMMARY)){
							$validEnrichmentTypes['summary'] = 'Summary';
							if (!isset($defaultOption)) $defaultOption = 'summary';
						}
						if ($configArray['Syndetics']['showAvSummary'] && isset($data->AVSUMMARY)){
							$validEnrichmentTypes['avSummary'] = 'Summary';
							if (!isset($defaultOption)) $defaultOption = 'avSummary';
						}
						if ($configArray['Syndetics']['showAvProfile'] && isset($data->AVPROFILE)){
							//Profile has similar bands and tags for music.  Not sure how to best use this
						}
						if ($configArray['Syndetics']['showToc'] && isset($data->TOC)){
							$validEnrichmentTypes['tableOfContents'] = 'Table of Contents';
							if (!isset($defaultOption)) $defaultOption = 'tableOfContents';
						}
						if ($configArray['Syndetics']['showExcerpt'] && isset($data->DBCHAPTER)){
							$validEnrichmentTypes['excerpt'] = 'Excerpt';
							if (!isset($defaultOption)) $defaultOption = 'excerpt';
						}
						if ($configArray['Syndetics']['showFictionProfile'] && isset($data->FICTION)){
							$validEnrichmentTypes['fictionProfile'] = 'Character Information';
							if (!isset($defaultOption)) $defaultOption = 'fictionProfile';
						}
						if ($configArray['Syndetics']['showAuthorNotes'] && isset($data->ANOTES)){
							$validEnrichmentTypes['authorNotes'] = 'Author Notes';
							if (!isset($defaultOption)) $defaultOption = 'authorNotes';
						}
						if ($configArray['Syndetics']['showVideoClip'] && isset($data->VIDEOCLIP)){
							//Profile has similar bands and tags for music.  Not sure how to best use this
							$validEnrichmentTypes['videoClip'] = 'Video Clip';
							if (!isset($defaultOption)) $defaultOption = 'videoClip';
						}
					}
				}
			}catch (Exception $e) {
				$logger = new Logger();
				$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERROR);
				if (isset($response)){
					$logger->log($response, PEAR_LOG_INFO);
				}
			}
		}

		//Check To see if a Google Preview is available
		if (isset($isbn) && strlen($isbn) > 0){
			$id = self::getGoogleBookId($isbn);
			if ($id){
				$validEnrichmentTypes['googlePreview'] = 'Google Preview';
			}
		}

		$return = array('options' => $validEnrichmentTypes);
		if ($getDefaultData && count($validEnrichmentTypes) > 0){
			$return['defaultOption'] = $defaultOption;
		}

		return $return;
	}
	function getSummary($isbn, $upc){
		global $configArray;
		global $memcache;
		$summaryData = $memcache->get("syndetics_summary_{$isbn}_{$upc}");
			
		if (!$summaryData){
			try{
				$clientKey = $configArray['Syndetics']['key'];
				//Load the index page from syndetics
				$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/SUMMARY.XML&client=$clientKey&type=xw10&upc=$upc";
	
				//Get the XML from the service
				$ctx = stream_context_create(array(
						  'http' => array(
						  'timeout' => 2
				)
				));
	
				$response =file_get_contents($requestUrl, 0, $ctx);
				if (preg_match('/Error in Query Selection/', $response)){
					return array();
				}
	
				//Parse the XML
				$data = new SimpleXMLElement($response);
	
				$summaryData = array();
				if (isset($data)){
					if (isset($data->VarFlds->VarDFlds->Notes->Fld520->a)){
						$summaryData['summary'] = (string)$data->VarFlds->VarDFlds->Notes->Fld520->a;
					}
				}
			}catch (Exception $e) {
				$logger = new Logger();
				$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERROR);
				$logger->log("Request URL was $requestUrl");
				$summaryData = array();
			}
			$memcache->set("syndetics_summary_{$isbn}_{$upc}", $summaryData, MEMCACHE_COMPRESSED, $configArray['Caching']['syndetics_summary']);
		}
		return $summaryData;
	}
	function getTableOfContents($isbn, $upc){
		global $configArray;
		$clientKey = $configArray['Syndetics']['key'];
		//Load the index page from syndetics
		$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/TOC.XML&client=$clientKey&type=xw10&upc=$upc";

		try{
			//Get the XML from the service
			$ctx = stream_context_create(array(
				  'http' => array(
				  'timeout' => 2
			)
			));
			$response =file_get_contents($requestUrl, 0, $ctx);

			//Parse the XML
			$data = new SimpleXMLElement($response);

			$tocData = array();
			if (isset($data)){
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld970)){
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld970 as $field){
						$tocData[] = array(
                            'label' => (string)$field->l,
                            'title' => (string)$field->t,
                            'page' => (string)$field->p,
						);
					}
				}
			}

			return $tocData;
		}catch (Exception $e) {
			$logger = new Logger();
			$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERROR);
			return array();
		}
	}
	function getFictionProfile($isbn, $upc){
		//Load the index page from syndetics
		global $configArray;
		$clientKey = $configArray['Syndetics']['key'];
		$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/FICTION.XML&client=$clientKey&type=xw10&upc=$upc";

		try{
			//Get the XML from the service
			$ctx = stream_context_create(array(
				  'http' => array(
				  'timeout' => 2
			)
			));
			$response =file_get_contents($requestUrl, 0, $ctx);

			//Parse the XML
			$data = new SimpleXMLElement($response);

			$fictionData = array();
			if (isset($data)){
				//Load characters
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld920)){
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld920 as $field){
						$fictionData['characters'][] = array(
                            'name' => (string)$field->b,
                            'gender' => (string)$field->c,
                            'age' => (string)$field->d,
                            'description' => (string)$field->f,
                            'occupation' => (string)$field->g,
						);
					}

				}
				//Load subjects
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld950)){
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld950 as $field){
						$fictionData['topics'][] = (string)$field->a;
					}
				}
				//Load settings
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld951)){
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld951 as $field){
						if (isset($field->c)){
							$fictionData['settings'][] = (string)$field->a . ' -- ' . (string)$field->c;
						}else{
							$fictionData['settings'][] = (string)$field->a;
						}
					}
				}
				//Load additional settings
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld952)){
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld952 as $field){
						if (isset($field->c)){
							$fictionData['settings'][] = (string)$field->a . ' -- ' . (string)$field->c;
						}else{
							$fictionData['settings'][] = (string)$field->a;
						}
					}
				}
				//Load genres
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld955)){
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld955 as $field){
						$genre = (string)$field->a;
						$subGenres = array();
						if (isset($field->b)){
							foreach ($field->b as $subGenre){
								$subGenres[] = (string)$field->b;
							}
						}
						$fictionData['genres'][] = array(
                            'name'=>$genre,
                            'subGenres'=>$subGenres
						);
					}
				}
				//Load awards
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld985)){
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld985 as $field){
						$fictionData['awards'][] = array(
                            'name' => (string)$field->a,
                            'year' => (string)$field->y,
						);
					}

				}
			}

			return $fictionData;
		}catch (Exception $e) {
			$logger = new Logger();
			$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERROR);
			return array();
		}
	}
	function getAuthorNotes($isbn, $upc){
		global $configArray;
		$clientKey = $configArray['Syndetics']['key'];

		//Load the index page from syndetics
		$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/ANOTES.XML&client=$clientKey&type=xw10&upc=$upc";

		try{
			//Get the XML from the service
			$ctx = stream_context_create(array(
				  'http' => array(
				  'timeout' => 2
			)
			));
			$response =file_get_contents($requestUrl, 0, $ctx);

			//Parse the XML
			$data = new SimpleXMLElement($response);

			$summaryData = array();
			if (isset($data)){
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld980->a)){
					$summaryData['summary'] = (string)$data->VarFlds->VarDFlds->SSIFlds->Fld980->a;
				}
			}

			return $summaryData;
		}catch (Exception $e) {
			$logger = new Logger();
			$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERROR);
			return array();
		}
	}
	function getExcerpt($isbn, $upc){
		global $configArray;
		$clientKey = $configArray['Syndetics']['key'];

		//Load the index page from syndetics
		$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/DBCHAPTER.XML&client=$clientKey&type=xw10&upc=$upc";

		try{
			//Get the XML from the service
			$ctx = stream_context_create(array(
				  'http' => array(
				  'timeout' => 2
			)
			));
			$response =file_get_contents($requestUrl, 0, $ctx);

			//Parse the XML
			$data = new SimpleXMLElement($response);

			$excerptData = array();
			if (isset($data)){
				if (isset($data->VarFlds->VarDFlds->Notes->Fld520)){
					$excerptData['excerpt'] = (string)$data->VarFlds->VarDFlds->Notes->Fld520;
				}
			}

			return $excerptData;
		}catch (Exception $e) {
			$logger = new Logger();
			$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERROR);
			return array();
		}
	}
	function getVideoClip($isbn, $upc){
		global $configArray;
		$clientKey = $configArray['Syndetics']['key'];
		//Load the index page from syndetics
		$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/VIDEOCLIP.XML&client=$clientKey&type=xw10&upc=$upc";

		try{
			//Get the XML from the service
			$ctx = stream_context_create(array(
				  'http' => array(
				  'timeout' => 2
			)
			));
			$response =file_get_contents($requestUrl, 0, $ctx);

			//Parse the XML
			$data = new SimpleXMLElement($response);

			$summaryData = array();
			if (isset($data)){
				if (isset($data->VarFlds->VarDFlds->VideoLink)){
					$summaryData['videoClip'] = (string)$data->VarFlds->VarDFlds->VideoLink;
				}
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld997)){
					$summaryData['source'] = (string)$data->VarFlds->VarDFlds->SSIFlds->Fld997;
				}
			}

			return $summaryData;
		}catch (Exception $e) {
			$logger = new Logger();
			$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERROR);
			return array();
		}
	}
	function getAVSummary($isbn, $upc){
		global $configArray;
		$clientKey = $configArray['Syndetics']['key'];

		//Load the index page from syndetics
		$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/AVSUMMARY.XML&client=$clientKey&type=xw10&upc=$upc";

		try{
			//Get the XML from the service
			$ctx = stream_context_create(array(
				  'http' => array(
				  'timeout' => 2
			)
			));
			$response =file_get_contents($requestUrl, 0, $ctx);

			//Parse the XML
			$data = new SimpleXMLElement($response);

			$avSummaryData = array();
			if (isset($data)){
				if (isset($data->VarFlds->VarDFlds->Notes->Fld520->a)){
					$avSummaryData['summary'] = (string)$data->VarFlds->VarDFlds->Notes->Fld520->a;
				}
				if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld970)){
					foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld970 as $field){
						$avSummaryData['trackListing'][] = array(
                            'number' => (string)$field->l,
                            'name' => (string)$field->t,
						);
					}
				}
			}

			return $avSummaryData;
		}catch (Exception $e) {
			$logger = new Logger();
			$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERROR);
			return array();
		}
	}

	function getGoogleBookId($isbn){
		$requestUrl = "http://www.google.com/search?q=isbn:$isbn&btnG=Search+Books";

		try{
			//Get the XML from the service
			$ctx = stream_context_create(array(
				  'http' => array(
				  'timeout' => 2
			)
			));
			$response =file_get_contents($requestUrl, 0, $ctx);
			$matches = array();
			if (preg_match('/<a href=["\']http:\/\/books\\.google\\.com\/books\\?id=(.*?)&.*?["\']>/', $response, $matches)) {
				$googleBookId = $matches[1];
				return $googleBookId;
			} else {
				//This book does not have a preview
				return false;
			}
		}catch (Exception $e) {
			$logger = new Logger();
			$logger->log("Error checking if Google Preview is available $e", PEAR_LOG_ERROR);
			return false;
		}
	}
	function getGooglePreview($isbn){
		$googleBookId = self::getGoogleBookId($isbn);
		//Just return a URL to the preview which we will throw into an iFrame
		return array('link' => "http://books.google.com/books?id=$googleBookId&printsec=frontcover");
	}

	function getHtmlData($dataType, $isbn, $upc){
		global $interface;
		if ($dataType == 'summary'){
			$data = GoDeeperData::getSummary($isbn, $upc);
			$interface->assign('summaryData', $data);
			return $interface->fetch('Record/view-syndetics-summary.tpl');
		}else if ($dataType == 'tableOfContents'){
			$data = GoDeeperData::getTableOfContents($isbn, $upc);
			$interface->assign('tocData', $data);
			return $interface->fetch('Record/view-syndetics-toc.tpl');
		}else if ($dataType == 'fictionProfile'){
			$data = GoDeeperData::getFictionProfile($isbn, $upc);
			$interface->assign('fictionData', $data);
			return $interface->fetch('Record/view-syndetics-fiction.tpl');
		}else if ($dataType == 'authorNotes'){
			$data = GoDeeperData::getAuthorNotes($isbn, $upc);
			$interface->assign('authorData', $data);
			return $interface->fetch('Record/view-syndetics-author-notes.tpl');
		}else if ($dataType == 'excerpt'){
			$data = GoDeeperData::getExcerpt($isbn, $upc);
			$interface->assign('excerptData', $data);
			return $interface->fetch('Record/view-syndetics-excerpt.tpl');
		}else if ($dataType == 'avSummary'){
			$data = GoDeeperData::getAVSummary($isbn, $upc);
			$interface->assign('avSummaryData', $data);
			return $interface->fetch('Record/view-syndetics-av-summary.tpl');
		}else if ($dataType == 'videoClip'){
			$data = GoDeeperData::getVideoClip($isbn, $upc);
			$interface->assign('videoClipData', $data);
			return $interface->fetch('Record/view-syndetics-video-clip.tpl');
		}else if ($dataType == 'googlePreview'){
			$data = GoDeeperData::getGooglePreview($isbn);
			$interface->assign('googlePreviewData', $data);
			return $interface->fetch('Record/view-google-preview.tpl');
		}else{
			return "Loading data for $dataType still needs to be handled.";
		}
	}
}