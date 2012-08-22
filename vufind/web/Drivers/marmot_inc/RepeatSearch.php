<?php
class RepeatSearch{
	function getRepeatSearchOptions($searchObject){
		$repeatSearchOptions = array();
		//No repeat search options for DCL currently
		return $repeatSearchOptions;

		//Check to see if marmot catalog is a valid option
		global $library;
		$repeatSearchSetting = '';
		$repeatInWorldCat = true;
		$repeatInProspector = false;
		$repeatInAmazon = true;
		$repeatInOverdrive = true;
		$systemsToRepeatIn = array();

		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		if ($location != null){
			$repeatSearchSetting = $location->repeatSearchOption;
			$repeatInWorldCat = $location->repeatInWorldCat == 1;
			$repeatInProspector = $location->repeatInProspector == 1;
			$repeatInOverdrive = $location->repeatInOverdrive == 1;
			if (strlen($location->systemsToRepeatIn) > 0){
				$systemsToRepeatIn = explode('|', $location->systemsToRepeatIn);
			}else{
				$systemsToRepeatIn = explode('|', $library->systemsToRepeatIn);
			}
		}elseif (isset($library)){
			$repeatSearchSetting = $library->repeatSearchOption;
			$repeatInWorldCat = $library->repeatInWorldCat == 1;
			$repeatInProspector = $library->repeatInProspector == 1;
			$repeatInOverdrive = $library->repeatInOverdrive == 1;
			$systemsToRepeatIn = explode('|', $library->systemsToRepeatIn);
		}
		if (isset($library)){
			$repeatInAmazon = $library->repeatInAmazon;
		}

		if (($location != null) &&
		($repeatSearchSetting == 'marmot' || $repeatSearchSetting == 'librarySystem') &&
		($location->useScope && strlen($location->defaultLocationFacet) > 0)
		){
			$pageUrl = 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			if (strpos($pageUrl, '?') > 0){
				$pageUrl .= '&branch=all';
			}else{
				$pageUrl .= '?branch=all';
			}

			$repeatSearchOptions['system'] = array(
              'link' => $pageUrl,
              'name' => $library->displayName,
              'description' => "The entire {$library->displayName} catalog not limited to a particular branch.",
			);
		}

		//Process additional systems to repeat in
		if (count($systemsToRepeatIn) > 0){
			foreach ($systemsToRepeatIn as $system){
				if (strlen($system) > 0){
					$repeatInLibrary = new Library();
					$repeatInLibrary->subdomain = $system;
					$repeatInLibrary->find();
					if ($repeatInLibrary->N == 1){
						$repeatInLibrary->fetch();
						$currentServer = $_SERVER["SERVER_NAME"];
						$newServer = $repeatInLibrary->subdomain . '.' . substr($currentServer, strpos($currentServer, '.') + 1);
						$pageUrl = 'http://'.$newServer.$_SERVER["REQUEST_URI"];

						$repeatSearchOptions[$repeatInLibrary->subdomain] = array(
                          'link' => $pageUrl,
                          'name' => $repeatInLibrary->displayName,
                          'description' => '',
						);
					}else{
						//See if this is a repeat within a location
						$repeatInLocation = new Location();
						$repeatInLocation->code = $system;
						$repeatInLocation->find();
						if ($repeatInLocation->N == 1){
							$repeatInLocation->fetch();
							$currentServer = $_SERVER["SERVER_NAME"];
							$newServer = $repeatInLocation->code . '.' . substr($currentServer, strpos($currentServer, '.') + 1);
							$pageUrl = 'http://'.$newServer.$_SERVER["REQUEST_URI"];

							$repeatSearchOptions[$repeatInLocation->code] = array(
                              'link' => $pageUrl,
                              'name' => $repeatInLocation->displayName,
                              'description' => '',
							);
						}
					}
				}
			}
		}

		if (isset($library) &&
		($repeatSearchSetting == 'marmot') &&
		($library->useScope && strlen($library->defaultLibraryFacet) > 0)
		){
			$currentServer = $_SERVER["SERVER_NAME"];
			$newServer = substr($currentServer, strpos($currentServer, '.') + 1);
			$pageUrl = 'http://'.$newServer.$_SERVER["REQUEST_URI"];

			$repeatSearchOptions['marmot'] = array(
              'link' => $pageUrl,
              'name' => 'Marmot Catalog',
              'description' => 'A shared catalog of public, academic, and school libraries on the Western Slope.',
			);
		}

		if ($repeatInOverdrive){
			//Since Overdrive uses a POST in submitting their advanced search page, we can't
			//replicate an exact search type.  Just do a general full text search which should
			//get people close.
			$lookfor = $searchObject->displayQuery();
			$repeatSearchOptions['overdrive'] = array(
              'link' => "http://emediatogo.org/BangSearch.dll?Type=FullText&amp;FullTextField=All&amp;FullTextCriteria=" . urlencode($lookfor),
              'name' => 'OverDrive Digital Catalog',
              'description' => 'Download eAudio & eBooks to your PC, smartphone or eBook reader.',
			);
		}

		if ($repeatInProspector){
			//Check to see if prospector is a valid option
			$prospectorSearchType = $this->getProspectorSearchType($searchObject);
			if (isset($prospectorSearchType)){
				$lookfor = $searchObject->displayQuery();
				$pageUrl = 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
				$repeatSearchOptions['prospector'] = array(
                  'link' => "http://encore.coalliance.org/iii/encore/search/C|S" . urlencode($lookfor) ."|Orightresult|U1?lang=eng&amp;suite=def",
				//'link' => "http://prospector.coalliance.org/search/Y?searchtype={$prospectorSearchType}&searcharg=".urlencode($lookfor)."&backlink=" . urlencode($pageUrl),
                  'name' => 'Prospector Catalog',
                  'description' => 'A shared catalog of academic, public, and special libraries on the Front Range.',
				);
			}
		}

		if ($repeatInWorldCat){
			//Check to see if worldcat is a valid option
			$worldCatSearchType = $this->getWorldCatSearchType($searchObject);
			if (isset($worldCatSearchType)){
				$lookfor = $searchObject->displayQuery();
				$worldCatLink = "http://www.worldcat.org/search?q={$worldCatSearchType}%3A".urlencode($lookfor);
				if (isset($library) && strlen($library->worldCatUrl) > 0){
					$worldCatLink = $library->worldCatUrl;
					if (strpos($worldCatLink, '?') == false){
						$worldCatLink .= "?";
					}
					$worldCatLink .= "q={$worldCatSearchType}%3A".urlencode($lookfor);
					if (strlen($library->worldCatQt) > 0){
						$worldCatLink .= "qt=" . $library->worldCatQt;
					}
				}
				$pageUrl = 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
				$repeatSearchOptions['worldcat'] = array(
                  'link' => $worldCatLink,
                  'name' => 'WorldCat',
                  'description' => 'A shared catalog of libraries all over the world.',
				);
			}
		}

		//Check to see if Gold Rush is a valid option
		if (isset($library) && strlen($library->goldRushCode) > 0){
			$worldCatSearchType = $this->getGoldRushSearchType($searchObject);
			if (isset($worldCatSearchType)){
				$lookfor = $searchObject->displayQuery();
				$pageUrl = 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
				$repeatSearchOptions['amazon'] = array(
                  'link' => "http://goldrush.coalliance.org/index.cfm?fuseaction=Search&amp;inst_code={$library->goldRushCode}&amp;search_type={$worldCatSearchType}&amp;search_term=".urlencode($lookfor),
                  'name' => 'Gold Rush Magazine Finder',
                  'description' => 'A catalog of online journals and full text articles.',
				);
			}
		}

		if ($repeatInAmazon){
			$lookfor = $searchObject->displayQuery();
			$repeatSearchOptions['goldrush'] = array(
              'link' => "http://www.amazon.com/s/ref=nb_sb_noss?url=search-alias%3Daps&amp;field-keywords=" . urlencode($lookfor),
              'name' => 'Amazon',
              'description' => 'Online retailer selling a wide variety of books, movies, music, and more.',
			);
		}

		return $repeatSearchOptions;
	}

	public function getProspectorSearchType($searchObject){
		switch ($searchObject->getSearchIndex()){
			case 'AllFields':
			case 'Keyword':
				return 'X';
				break;
			case 'Subject':
				return 'd';
				break;
			case 'Author':
				return 'a';
				break;
			case 'Title':
				return 't';
				break;
			case 'ISN':
				return 'i';
				break;
		}
		return;
	}

	public function getWorldCatSearchType($searchObject){
		switch ($searchObject->getSearchIndex()){
			case 'AllFields':
			case 'Keyword':
				return 'kw';
				break;
			case 'Subject':
				return 'su';
				break;
			case 'Author':
				return 'au';
				break;
			case 'Title':
				return 'ti';
				break;
			case 'ISN':
				return 'bn';
				break;
		}
		return;
	}

	public function getGoldRushSearchType($searchObject){
		switch ($searchObject->getSearchIndex()){
			case 'AllFields':
			case 'Keyword':
				return 'Keyword';
				break;
			case 'Subject':
				return 'Subject';
				break;
			case 'Author':
				return; //Gold Rush does not support this directly
				break;
			case 'Title':
				return 'Journal Title';
				break;
			case 'ISN':
				return 'ISSN';
				break;
		}
		return;
	}
}