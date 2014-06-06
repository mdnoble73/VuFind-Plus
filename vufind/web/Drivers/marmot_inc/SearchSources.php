<?php
class SearchSources{
	static function getSearchSources(){
		$activeLibrary = Library::getActiveLibrary();
		if ($activeLibrary == null || count($activeLibrary->searchSources) == 0 ){
			$searchSources = SearchSources::getSearchSourcesDefault();
		}else{
			$searchSources = SearchSources::getSearchSourcesLibrary($activeLibrary);
		}
		return $searchSources;
	}
	private static function getSearchSourcesLibrary($library){
		$searchOptions = array();
		foreach ($library->searchSources as $searchSource){
			$searchOptions['library' . $searchSource->id] = array(
				'name' => $searchSource->label,
				'description' => $searchSource->label,
			);
		}
		return $searchOptions;
	}
	private static function getSearchSourcesDefault(){
		$searchOptions = array();
		//Check to see if marmot catalog is a valid option
		global $library;
		global $interface;
		$repeatSearchSetting = '';
		$repeatInWorldCat = false;
		$repeatInProspector = true;
		$repeatInAmazon = true;
		$repeatInOverdrive = false;
		$systemsToRepeatIn = array();
		$searchGenealogy = true;
		$repeatCourseReserves = false;

		/** @var $locationSingleton Location */
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		if ($location != null && $location->useScope && $location->restrictSearchByLocation){
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
			$searchGenealogy = $library->enableGenealogy;
			$repeatCourseReserves = $library->enableCourseReserves == 1;
		}

		$marmotAdded = false;

		//Local search
		if (isset($location) && $location != null && $location->useScope && $location->restrictSearchByLocation){
			$searchOptions['local'] = array(
              'name' => $location->displayName,
              'description' => "The {$location->displayName} catalog.",
							'catalogType' => 'catalog'
			);
		}elseif (isset($library)){
			$searchOptions['local'] = array(
              'name' => strlen($library->abbreviatedDisplayName) > 0 ? $library->abbreviatedDisplayName :  $library->displayName,
              'description' => "The {$library->displayName} catalog.",
							'catalogType' => 'catalog'
			);
		}else{
			$marmotAdded = true;
			$searchOptions['local'] = array(
              'name' => 'Marmot Catalog',
              'description' => "The entire Marmot catalog.",
							'catalogType' => 'catalog'
			);
		}

		if (($location != null) &&
		($repeatSearchSetting == 'marmot' || $repeatSearchSetting == 'librarySystem') &&
		($location->useScope && $location->restrictSearchByLocation)
		){
			$searchOptions[$library->subdomain] = array(
        'name' => $library->displayName,
        'description' => "The entire {$library->displayName} catalog not limited to a particular branch.",
				'catalogType' => 'catalog'
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

						$searchOptions[$repeatInLibrary->subdomain] = array(
              'name' => $repeatInLibrary->displayName,
              'description' => '',
							'catalogType' => 'catalog'
						);
					}else{
						//See if this is a repeat within a location
						$repeatInLocation = new Location();
						$repeatInLocation->code = $system;
						$repeatInLocation->find();
						if ($repeatInLocation->N == 1){
							$repeatInLocation->fetch();

							$searchOptions[$repeatInLocation->code] = array(
                'name' => $repeatInLocation->displayName,
                'description' => '',
								'catalogType' => 'catalog'
							);
						}
					}
				}
			}
		}

		$includeOnlineOption = true;
		if ($location != null && $location->repeatInOnlineCollection == 0) {
			$includeOnlineOption = false;
		}elseif ($library != null && $library->repeatInOnlineCollection == 0) {
			$includeOnlineOption = false;
		}

		if ($includeOnlineOption){
			//eContent Search
			$searchOptions['econtent'] = array(
					'name' => 'Online Collection',
					'description' => 'Digital Media available for use online and with portable devices',
					'catalogType' => 'catalog'
			);
		}

		//Marmot Global search
		if (isset($library) &&
		($repeatSearchSetting == 'marmot') &&
		$library->restrictSearchByLibrary
		&& $marmotAdded == false
		){

			$searchOptions['marmot'] = array(
        'name' => 'Marmot Catalog',
        'description' => 'A shared catalog of public, academic, and school libraries on the Western Slope.',
				'catalogType' => 'catalog'
			);
		}

		//Genealogy Search
		if ($searchGenealogy && !$interface->isMobile()){
			$searchOptions['genealogy'] = array(
        'name' => 'Genealogy Records',
        'description' => 'Genealogy Records from Colorado',
				'catalogType' => 'genealogy'
			);
		}

		//Overdrive
		if ($repeatInOverdrive && !$interface->isMobile()){
			$searchOptions['overdrive'] = array(
        'name' => 'OverDrive Digital Catalog',
        'description' => 'Downloadable Books, Videos, Music, and eBooks with free use for library card holders.',
        'external' => true,
				'catalogType' => 'catalog'
			);
		}

		if ($repeatInProspector && !$interface->isMobile()){
			$searchOptions['prospector'] = array(
        'name' => 'Prospector Catalog',
        'description' => 'A shared catalog of academic, public, and special libraries all over Colorado.',
        'external' => true,
				'catalogType' => 'catalog'
			);
		}

		//Course reserves for colleges
		if ($repeatCourseReserves){
			//Mesa State
			$searchOptions['course-reserves-course-name'] = array(
        'name' => 'Course Reserves by Name or Number',
        'description' => 'Search course reserves by course name or number',
        'external' => true,
				'catalogType' => 'courseReserves'
			);
			$searchOptions['course-reserves-instructor'] = array(
        'name' => 'Course Reserves by Instructor',
        'description' => 'Search course reserves by professor, lecturer, or instructor name',
        'external' => true,
				'catalogType' => 'courseReserves'
			);
		}

		if ($repeatInWorldCat && !$interface->isMobile()){
			$searchOptions['worldcat'] = array(
        'name' => 'WorldCat',
        'description' => 'A shared catalog of libraries all over the world.',
        'external' => true,
				'catalogType' => 'catalog'
			);
		}

		//Check to see if Gold Rush is a valid option
		if (isset($library) && strlen($library->goldRushCode) > 0 && !$interface->isMobile()){
			$searchOptions['goldrush'] = array(
			//'link' => "http://goldrush.coalliance.org/index.cfm?fuseaction=Search&amp;inst_code={$library->goldRushCode}&amp;search_type={$worldCatSearchType}&amp;search_term=".urlencode($lookfor),
        'name' => 'Gold Rush Magazine Finder',
        'description' => 'A catalog of online journals and full text articles.',
        'external' => true,
				'catalogType' => 'catalog'
			);
		}

		if ($repeatInAmazon && !$interface->isMobile()){
			$searchOptions['amazon'] = array(
        'name' => 'Amazon',
        'description' => 'Online retailer selling a wide variety of books, movies, music, and more.',
        'external' => true,
				'catalogType' => 'catalog'
			);
		}

		return $searchOptions;
	}

	public function getWorldCatSearchType($type){
		switch ($type){
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
			case 'AllFields':
			case 'Keyword':
			default:
				return 'kw';
				break;
		}
	}

	public function getGoldRushSearchType($type){
		switch ($type){
			case 'Subject':
				return 'Subject';
				break;
			case 'Title':
				return 'Journal Title';
				break;
			case 'ISN':
				return 'ISSN';
				break;
			case 'Author': //Gold Rush does not support author searches directly
			case 'AllFields':
			case 'Keyword':
			default:
				return 'Keyword';
				break;
		}
	}

	public function getExternalLink($searchSource, $type, $lookFor){
		if (is_object($searchSource)){
			$searchSource = $searchSource->searchWhat;
		}
		global $library;
		if ($searchSource =='goldrush'){
			$goldRushType = $this->getGoldRushSearchType($type);
			return "http://goldrush.coalliance.org/index.cfm?fuseaction=Search&inst_code={$library->goldRushCode}&search_type={$goldRushType}&search_term=".urlencode($lookFor);
		}else if ($searchSource == 'worldcat'){
			$worldCatSearchType = $this->getWorldCatSearchType($type);
			$worldCatLink = "http://www.worldcat.org/search?q={$worldCatSearchType}%3A".urlencode($lookFor);
			if (isset($library) && strlen($library->worldCatUrl) > 0){
				$worldCatLink = $library->worldCatUrl;
				if (strpos($worldCatLink, '?') == false){
					$worldCatLink .= "?";
				}
				$worldCatLink .= "q={$worldCatSearchType}:".urlencode($lookFor);
				if (strlen($library->worldCatQt) > 0){
					$worldCatLink .= "&qt=" . $library->worldCatQt;
				}
			}
			return $worldCatLink;
		}else if ($searchSource == 'overdrive'){
			return "http://marmot.lib.overdrive.com/BangSearch.dll?Type=FullText&FullTextField=All&FullTextCriteria=" . urlencode($lookFor);
		}else if ($searchSource == 'prospector'){
			$prospectorSearchType = $this->getProspectorSearchType($type);
			$lookFor = str_replace('+', '%20', rawurlencode($lookFor));
			if ($prospectorSearchType != ' '){
				$lookFor = "$prospectorSearchType:(" . $lookFor . ")";
			}
			return "http://encore.coalliance.org/iii/encore/search/C|S" . $lookFor ."|Orightresult|U1?lang=eng&amp;suite=def";
		}else if ($searchSource == 'amazon'){
			return "http://www.amazon.com/s/ref=nb_sb_noss?url=search-alias%3Daps&field-keywords=" . urlencode($lookFor);
		}else if ($searchSource == 'course-reserves-course-name'){
			return "http://sierra.marmot.org/search~S{$library->scope}/r?SEARCH=" . urlencode($lookFor);
		}else if ($searchSource == 'course-reserves-instructor'){
			return "http://sierra.marmot.org/search~S{$library->scope}/p?SEARCH=" . urlencode($lookFor);
		}else{
			return "";
		}
	}

	public function getProspectorSearchType($type){
		switch ($type){
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
			case 'AllFields':
			case 'Keyword':
				return ' ';
				break;
		}
		return ' ';
	}
}