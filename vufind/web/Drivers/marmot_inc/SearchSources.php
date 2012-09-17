<?php
class SearchSources{
	function getSearchSources(){
		$searchOptions = array();
		//Check to see if marmot catalog is a valid option
		global $library;
		global $interface;
		$repeatSearchSetting = '';
		$repeatInWorldCat = false;
		$repeatInProspector = true;
		$repeatInAmazon = true;
		$repeatInOverdrive = true;
		$systemsToRepeatIn = array();
		$searchGenealogy = true;
		$repeatCourseReserves = false;

		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		if ($location != null && $location->useScope && strlen($location->defaultLocationFacet) > 0){
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
		if (isset($location) && $location != null && $location->useScope && strlen($location->defaultLocationFacet) > 0){
			$searchOptions['local'] = array(
              'name' => $location->displayName,
              'description' => "The {$location->displayName} catalog.",
			);
		}elseif (isset($library)){
			$searchOptions['local'] = array(
              'name' => $library->displayName,
              'description' => "The {$library->displayName} catalog.",
			);
		}else{
			$marmotAdded = true;
			$searchOptions['local'] = array(
              'name' => 'Marmot Catalog',
              'description' => "The entire Marmot catalog.",
			);
		}

		if (($location != null) &&
		($repeatSearchSetting == 'marmot' || $repeatSearchSetting == 'librarySystem') &&
		($location->useScope && strlen($location->defaultLocationFacet) > 0)
		){
			$searchOptions[$library->subdomain] = array(
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

						$searchOptions[$repeatInLibrary->subdomain] = array(
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

							$searchOptions[$repeatInLocation->code] = array(
                              'name' => $repeatInLocation->displayName,
                              'description' => '',
							);
						}
					}
				}
			}
		}

		//Summon Search - later

		//eContent Search
		$searchOptions['econtent'] = array(
              'name' => 'Digital Collection',
              'description' => 'Digital Media available for use online and with portable devices',
		);

		//Marmot Global search
		if (isset($library) &&
		($repeatSearchSetting == 'marmot') &&
		(strlen($library->defaultLibraryFacet) > 0)
		&& $marmotAdded == false
		){

			$searchOptions['marmot'] = array(
              'name' => 'Marmot Catalog',
              'description' => 'A shared catalog of public, academic, and school libraries on the Western Slope.',
			);
		}

		//Genealogy Search
		if ($searchGenealogy && !$interface->isMobile()){
			$searchOptions['genealogy'] = array(
              'name' => 'Genealogy Records',
              'description' => 'Genealogy Records from Colorado',
			);
		}

		//Overdrive
		if ($repeatInOverdrive && !$interface->isMobile()){
			$searchOptions['overdrive'] = array(
              'name' => 'OverDrive Digital Catalog',
              'description' => 'Downloadable Books, Videos, Music, and eBooks with free use for library card holders.',
              'external' => true,
			);
		}

		if ($repeatInProspector && !$interface->isMobile()){
			$searchOptions['prospector'] = array(
              'name' => 'Prospector Catalog',
              'description' => 'A shared catalog of academic, public, and special libraries all over Colorado.',
              'external' => true,
			);
		}

		//Course reserves for colleges
		if ($repeatCourseReserves){
			//Mesa State
			$searchOptions['course-reserves-course-name'] = array(
              'name' => 'Course Reserves by Name or Number',
              'description' => 'Search course reserves by course name or number',
              'external' => true
			);
			$searchOptions['course-reserves-instructor'] = array(
              'name' => 'Course Reserves by Instructor',
              'description' => 'Search course reserves by professor, lecturer, or instructor name',
              'external' => true
			);
		}

		if ($repeatInWorldCat && !$interface->isMobile()){
			$searchOptions['worldcat'] = array(
              'name' => 'WorldCat',
              'description' => 'A shared catalog of libraries all over the world.',
              'external' => true,
			);
		}

		//Check to see if Gold Rush is a valid option
		if (isset($library) && strlen($library->goldRushCode) > 0 && !$interface->isMobile()){
			$searchOptions['goldrush'] = array(
			//'link' => "http://goldrush.coalliance.org/index.cfm?fuseaction=Search&amp;inst_code={$library->goldRushCode}&amp;search_type={$worldCatSearchType}&amp;search_term=".urlencode($lookfor),
              'name' => 'Gold Rush Magazine Finder',
              'description' => 'A catalog of online journals and full text articles.',
              'external' => true,
			);
		}

		if ($repeatInAmazon && !$interface->isMobile()){
			$searchOptions['amazon'] = array(
              'name' => 'Amazon',
              'description' => 'Online retailer selling a wide variety of books, movies, music, and more.',
              'external' => true,
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
		return;
	}

	public function getGoldRushSearchType($type){
		switch ($type){
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
			case 'AllFields':
			case 'Keyword':
			default:
				return 'Keyword';
				break;
		}
		return;
	}

	public function getExternalLink($searchSource, $type, $lookfor){
		global $library;
		if ($searchSource =='goldrush'){
			$goldRushType = $this->getGoldRushSearchType($type);
			return "http://goldrush.coalliance.org/index.cfm?fuseaction=Search&inst_code={$library->goldRushCode}&search_type={$goldRushType}&search_term=".urlencode($lookfor);
		}else if ($searchSource == 'worldcat'){
			$worldCatSearchType = $this->getWorldCatSearchType($type);
			$worldCatLink = "http://www.worldcat.org/search?q={$worldCatSearchType}%3A".urlencode($lookfor);
			if (isset($library) && strlen($library->worldCatUrl) > 0){
				$worldCatLink = $library->worldCatUrl;
				if (strpos($worldCatLink, '?') == false){
					$worldCatLink .= "?";
				}
				$worldCatLink .= "q={$worldCatSearchType}:".urlencode($lookfor);
				if (strlen($library->worldCatQt) > 0){
					$worldCatLink .= "&qt=" . $library->worldCatQt;
				}
			}
			return $worldCatLink;
		}else if ($searchSource == 'overdrive'){
			return "http://marmot.lib.overdrive.com/BangSearch.dll?Type=FullText&FullTextField=All&FullTextCriteria=" . urlencode($lookfor);
		}else if ($searchSource == 'prospector'){
			//$prospectorSearchType = $this->getProspectorSearchType($searchObject);
			return "http://encore.coalliance.org/iii/encore/search/C|S" . urlencode($lookfor) ."|Orightresult|U1?lang=eng&amp;suite=def";
		}else if ($searchSource == 'amazon'){
			return "http://www.amazon.com/s/ref=nb_sb_noss?url=search-alias%3Daps&field-keywords=" . urlencode($lookfor);
		}else if ($searchSource == 'course-reserves-course-name'){
			return "http://www.millennium.marmot.org/search~S{$library->scope}/r?SEARCH=" . urlencode($lookfor);
		}else if ($searchSource == 'course-reserves-instructor'){
			return "http://www.millennium.marmot.org/search~S{$library->scope}/p?SEARCH=" . urlencode($lookfor);
		}
	}
}