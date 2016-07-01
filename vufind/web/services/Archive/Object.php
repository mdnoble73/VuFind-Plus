<?php
/**
 * A superclass for Digital Archive Objects
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 9/9/2015
 * Time: 4:13 PM
 */

require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
abstract class Archive_Object extends Action{
	protected $pid;
	/** @var  FedoraObject $archiveObject */
	protected $archiveObject;
	/** @var IslandoraDriver $recordDriver */
	protected $recordDriver;
	//protected $dcData;
	protected $modsData;
	//Data with a namespace of mods
	protected $modsModsData;
	protected $relsExtData;

	protected $formattedSubjects;
	protected $links;

	/**
	 * @param string $mainContentTemplate  Name of the SMARTY template file for the main content of the Full Record View Pages
	 * @param string $pageTitle            What to display is the html title tag
	 */
	function display($mainContentTemplate, $pageTitle=null) {
		global $interface;

		$relatedEvents = $this->recordDriver->getRelatedEvents();
		$relatedPeople = $this->recordDriver->getRelatedPeople();
		$relatedOrganizations = $this->recordDriver->getRelatedOrganizations();
		$relatedPlaces = $this->recordDriver->getRelatedPlaces();

		//Sort all the related information
		usort($relatedEvents, 'ExploreMore::sortRelatedEntities');
		usort($relatedPeople, 'ExploreMore::sortRelatedEntities');
		usort($relatedOrganizations, 'ExploreMore::sortRelatedEntities');
		usort($relatedPlaces, 'ExploreMore::sortRelatedEntities');

		//Do final assignment
		$interface->assign('relatedEvents', $relatedEvents);
		$interface->assign('relatedPeople', $relatedPeople);
		$interface->assign('relatedOrganizations', $relatedOrganizations);
		$interface->assign('relatedPlaces', $relatedPlaces);

		$pageTitle = $pageTitle == null ? $this->archiveObject->label : $pageTitle;

		$interface->assign('breadcrumbText', $pageTitle);

		parent::display($mainContentTemplate, $pageTitle);
	}

	//TODO: This should eventually move onto a Record Driver
	function loadArchiveObjectData(){
		global $interface;
		global $configArray;
		$fedoraUtils = FedoraUtils::getInstance();

		// Replace 'object:pid' with the PID of the object to be loaded.
		$this->pid = urldecode($_REQUEST['id']);
		$interface->assign('pid', $this->pid);
		$this->archiveObject = $fedoraUtils->getObject($this->pid);
		$this->recordDriver = RecordDriverFactory::initRecordDriver($this->archiveObject);
		$interface->assign('recordDriver', $this->recordDriver);

		//Load the MODS data stream
		$this->modsData = $this->recordDriver->getModsData();
		$interface->assign('mods', $this->modsData);

		$originInfo = $this->recordDriver->getModsValue('originInfo', 'mods');
		if (strlen($originInfo)){
			$dateCreated = $this->recordDriver->getModsValue('dateCreated', 'mods', $originInfo);
			$interface->assign('dateCreated', $dateCreated);
		}
		
		$identifier = $this->recordDriver->getModsValue('identifier', 'mods');
		$interface->assign('identifier', $identifier);

		$physicalDescriptions = $this->recordDriver->getModsValues('physicalDescription', 'mods');
		$physicalExtents = array();
		foreach ($physicalDescriptions as $physicalDescription){
			$extent = $this->recordDriver->getModsValue('identifier', 'mods', $physicalDescription);
			$physicalExtents[] = $extent;
		}
		$interface->assign('physicalExtents', $physicalExtents);

		$physicalLocation = $this->recordDriver->getModsValues('physicalLocation', 'mods');
		$interface->assign('physicalLocation', $physicalLocation);
		
		$shelfLocator = $this->recordDriver->getModsValues('shelfLocator', 'mods');
		$interface->assign('shelfLocator', $shelfLocator);

		$recordInfo = $this->recordDriver->getModsValue('identifier', 'recordInfo');
		if (strlen($recordInfo)){
			$interface->assign('hasRecordInfo', true);
			$recordOrigin = $this->recordDriver->getModsValue('recordOrigin', 'mods', $recordInfo);
			$interface->assign('recordOrigin', $recordOrigin);

			$recordCreationDate = $this->recordDriver->getModsValue('recordCreationDate', 'mods', $recordInfo);
			$interface->assign('recordCreationDate', $recordCreationDate);

			$recordChangeDate = $this->recordDriver->getModsValue('recordChangeDate', 'mods', $recordInfo);
			$interface->assign('recordChangeDate', $recordChangeDate);
		}

		$this->formattedSubjects = $this->recordDriver->getAllSubjectsWithLinks();
		$interface->assign('subjects', $this->formattedSubjects);

		$location = $this->recordDriver->getModsValue('location', 'mods');
		if (strlen($location) > 0){
			$interface->assign('primaryUrl', $this->recordDriver->getModsValue('url', 'mods', $location));
		}

		$rightsStatements = $this->recordDriver->getModsValues('rightsStatement', 'marmot');
		$interface->assign('rightsStatements', $rightsStatements);

		$transcription = $this->recordDriver->getModsValue('hasTranscription', 'marmot');
		if (strlen($transcription)){
			$transcriptionText = $this->recordDriver->getModsValue('transcriptionText', 'marmot', $transcription);
			$transcriptionText = str_replace("\r\n", '<br/>', $transcriptionText);
			$transcriptionText = str_replace("&#xD;", '<br/>', $transcriptionText);

			//Add links to timestamps
			$transcriptionTextWithLinks = $transcriptionText;
			if (preg_match_all('/\\(\\d{1,2}:\d{1,2}\\)/', $transcriptionText, $allMatches)){
				foreach ($allMatches[0] as $match){
					$offset = str_replace('(', '', $match);
					$offset = str_replace(')', '', $offset);
					list($minutes, $seconds) = explode(':', $offset);
					$offset = $minutes * 60 + $seconds;
					$replacement = '<a onclick="document.getElementById(\'player\').currentTime=\'' . $offset . '\';" style="cursor:pointer">' . $match . '</a>';
					$transcriptionTextWithLinks = str_replace($match, $replacement, $transcriptionTextWithLinks);
				}
			}elseif (preg_match_all('/\\[\\d{1,2}:\d{1,2}:\d{1,2}\\]/', $transcriptionText, $allMatches)){
				foreach ($allMatches[0] as $match){
					$offset = str_replace('(', '', $match);
					$offset = str_replace(')', '', $offset);
					list($hours, $minutes, $seconds) = explode(':', $offset);
					$offset = $hours * 3600 + $minutes * 60 + $seconds;
					$replacement = '<a onclick="document.getElementById(\'player\').currentTime=\'' . $offset . '\';" style="cursor:pointer">' . $match . '</a>';
					$transcriptionTextWithLinks = str_replace($match, $replacement, $transcriptionTextWithLinks);
				}
			}

			$interface->assign('transcription',
					array(
							'language' => $this->recordDriver->getModsValue('transcriptionLanguage', 'marmotLocal', $transcription),
							'text' => $transcriptionTextWithLinks,
					)
			);
		}

		$alternateNames = $this->recordDriver->getModsValues('alternateName', 'marmotLocal');
		$interface->assign('alternateNames', $alternateNames);

		$this->recordDriver->loadRelatedEntities();

		$interface->assign('hasMilitaryService', false);
		$militaryService = $this->recordDriver->getModsValue('militaryService', 'marmotLocal');
		if (strlen($militaryService) > 0){
			/** @var SimpleXMLElement $record */
			$militaryRecord = $this->recordDriver->getModsValue('militaryRecord', 'marmotLocal', $militaryService);
			$militaryBranch = $this->recordDriver->getModsValue('militaryBranch', 'marmotLocal', $militaryRecord);
			$militaryConflict = $this->recordDriver->getModsValue('militaryConflict', 'marmotLocal', $militaryRecord);
			if ($militaryBranch != 'none' || $militaryConflict != 'none'){
				$militaryRecord = array(
						'branch' => $fedoraUtils->getObjectLabel($militaryBranch),
						'branchLink' => '/Archive/' . $militaryBranch . '/Organization',
						'conflict' => $fedoraUtils->getObjectLabel($militaryConflict),
						'conflictLink' => '/Archive/' . $militaryConflict . '/Event',
				);
				$interface->assign('militaryRecord', $militaryRecord);
				$interface->assign('hasMilitaryService', true);
			}
		}

		$addressInfo = array();
		$latitude = $this->recordDriver->getModsValue('latitude', 'marmot');
		$longitude = $this->recordDriver->getModsValue('longitude', 'marmot');
		$addressStreetNumber = $this->recordDriver->getModsValue('addressStreetNumber', 'marmot');
		$addressStreet = $this->recordDriver->getModsValue('addressStreet', 'marmot');
		$addressCity = $this->recordDriver->getModsValue('addressCity', 'marmot');
		$addressCounty = $this->recordDriver->getModsValue('addressCounty', 'marmot');
		$addressState = $this->recordDriver->getModsValue('addressState', 'marmot');
		$addressZipCode = $this->recordDriver->getModsValue('addressZipCode', 'marmot');
		$addressCountry = $this->recordDriver->getModsValue('addressCountry', 'marmot');
		$addressOtherRegion = $this->recordDriver->getModsValue('addressOtherRegion', 'marmot');
		if (strlen($latitude) ||
				strlen($longitude) ||
				strlen($addressStreetNumber) ||
				strlen($addressStreet) ||
				strlen($addressCity) ||
				strlen($addressCounty) ||
				strlen($addressState) ||
				strlen($addressZipCode) ||
				strlen($addressCountry) ||
				strlen($addressOtherRegion)) {

			if (strlen($latitude) > 0) {
				$addressInfo['latitude'] = $latitude;
			}
			if (strlen($longitude) > 0) {
				$addressInfo['longitude'] = $longitude;
			}

			if (strlen($addressStreetNumber) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressStreetNumber'] = $addressStreetNumber;
			}
			if (strlen($addressStreet) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressStreet'] = $addressStreet;
			}
			if (strlen($addressCity) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressCity'] = $addressCity;
			}
			if (strlen($addressState) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressCounty'] = $addressCounty;
			}
			if (strlen($addressState) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressState'] = $addressState;
			}
			if (strlen($addressZipCode) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressZipCode'] = $addressZipCode;
			}
			if (strlen($addressStreet) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressCountry'] = $addressCountry;
			}
			$interface->assign('addressInfo', $addressInfo);
		}//End verifying checking for address information

		$notes = array();
		$personNotes = $this->recordDriver->getModsValue('personNotes', 'marmot');
		if (strlen($personNotes) > 0){
			$notes[] = $personNotes;
		}
		$citationNotes = $this->recordDriver->getModsValue('citationNotes', 'marmot');
		if (strlen($citationNotes) > 0){
			$notes[] = $citationNotes;
		}
		$interface->assign('notes', $notes);

		//Load information about dates
		$startDate = $this->recordDriver->getModsValue('placeDateStart', 'marmot');
		if ($startDate){
			$interface->assign('startDate', $startDate);
		}else{
			$startDate = $this->recordDriver->getModsValue('eventStartDate', 'marmot');
			if ($startDate){
				$interface->assign('startDate', $startDate);
			}else{
				$startDate = $this->recordDriver->getModsValue('dateEstablished', 'marmot');
				if ($startDate){
					$interface->assign('startDate', $startDate);
				}
			}
		}
		$endDate = $this->recordDriver->getModsValue('placeDateEnd', 'marmot');
		if ($endDate){
			$interface->assign('endDate', $endDate);
		}else{
			$endDate = $this->recordDriver->getModsValue('eventEndDate', 'marmot');
			if ($endDate){
				$interface->assign('endDate', $endDate);
			}else{
				$endDate = $this->recordDriver->getModsValue('dateDisbanded', 'marmot');
				if ($endDate){
					$interface->assign('endDate', $endDate);
				}
			}
		}

		$contextNotes = $this->recordDriver->getModsValue('contextNotes', 'marmot');
		$interface->assign('contextNotes', $contextNotes);

		$title = $this->archiveObject->label;
		$interface->assign('title', $title);
		$interface->setPageTitle($title);
		$interface->assign('description', $this->recordDriver->getDescription());

		$interface->assign('original_image', $this->recordDriver->getBookcoverUrl('original'));
		$interface->assign('large_image', $this->recordDriver->getBookcoverUrl('large'));
		$interface->assign('medium_image', $this->recordDriver->getBookcoverUrl('medium'));

		$repositoryLink = $configArray['Islandora']['repositoryUrl'] . '/islandora/object/' . $this->pid;
		$interface->assign('repositoryLink', $repositoryLink);

		//Check for display restrictions
		if ($this->recordDriver instanceof BasicImageDriver || $this->recordDriver instanceof LargeImageDriver){
			/** @var CollectionDriver $collection */
			$anonymousMasterDownload = true;
			$verifiedMasterDownload = true;
			$anonymousLcDownload = true;
			$verifiedLcDownload = true;
			foreach ($this->recordDriver->getRelatedCollections() as $collection){
				$collectionDriver = RecordDriverFactory::initRecordDriver($collection['object']);
				if (!$collectionDriver->canAnonymousDownloadMaster()){
					$anonymousMasterDownload = false;
				}
				if (!$collectionDriver->canVerifiedDownloadMaster()){
					$verifiedMasterDownload = false;
				}
				if (!$collectionDriver->canAnonymousDownloadLC()){
					$anonymousLcDownload = false;
				}
				if (!$collectionDriver->canVerifiedDownloadLC()){
					$verifiedLcDownload = false;
				}
			}
			$interface->assign('anonymousMasterDownload', $anonymousMasterDownload);
			$interface->assign('verifiedMasterDownload', $verifiedMasterDownload);
			$interface->assign('anonymousLcDownload', $anonymousLcDownload);
			$interface->assign('verifiedLcDownload', $verifiedLcDownload);
		}
	}

	protected function loadLinkedData(){
		global $interface;
		foreach ($this->recordDriver->getLinks() as $link){
			if ($link['type'] == 'wikipedia'){
				require_once ROOT_DIR . '/sys/WikipediaParser.php';
				$wikipediaParser = new WikipediaParser('en');

				//Transform from a regular wikipedia link to an api link
				$searchTerm = str_replace('https://en.wikipedia.org/wiki/', '', $link['link']);
				$url = "http://en.wikipedia.org/w/api.php" .
						'?action=query&prop=revisions&rvprop=content&format=json' .
						'&titles=' . urlencode(urldecode($searchTerm));
				$wikipediaData = $wikipediaParser->getWikipediaPage($url);
				$interface->assign('wikipediaData', $wikipediaData);
			}elseif($link['type'] == 'marmotGenealogy'){
				$matches = array();
				if (preg_match('/.*Person\/(\d+)/', $link['link'], $matches)){
					$personId = $matches[1];
					require_once ROOT_DIR . '/sys/Genealogy/Person.php';
					$person = new Person();
					$person->personId = $personId;
					if ($person->find(true)){
						$interface->assign('genealogyData', $person);

						$formattedBirthdate = $person->formatPartialDate($person->birthDateDay, $person->birthDateMonth, $person->birthDateYear);
						$interface->assign('birthDate', $formattedBirthdate);

						$formattedDeathdate = $person->formatPartialDate($person->deathDateDay, $person->deathDateMonth, $person->deathDateYear);
						$interface->assign('deathDate', $formattedDeathdate);

						$marriages = array();
						$personMarriages = $person->marriages;
						if (isset($personMarriages)){
							foreach ($personMarriages as $marriage){
								$marriageArray = (array)$marriage;
								$marriageArray['formattedMarriageDate'] = $person->formatPartialDate($marriage->marriageDateDay, $marriage->marriageDateMonth, $marriage->marriageDateYear);
								$marriages[] = $marriageArray;
							}
						}
						$interface->assign('marriages', $marriages);
						$obituaries = array();
						$personObituaries =$person->obituaries;
						if (isset($personObituaries)){
							foreach ($personObituaries as $obit){
								$obitArray = (array)$obit;
								$obitArray['formattedObitDate'] = $person->formatPartialDate($obit->dateDay, $obit->dateMonth, $obit->dateYear);
								$obituaries[] = $obitArray;
							}
						}
						$interface->assign('obituaries', $obituaries);
					}
				}
			}
		}
	}

}