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
		$this->modsModsData = $this->modsData->children('http://www.loc.gov/mods/v3');

		$this->formattedSubjects = $this->recordDriver->getAllSubjectsWithLinks();
		$interface->assign('subjects', $this->formattedSubjects);

		if ($this->modsData->location->count()){
			$primaryUrl = $this->modsData->location->url;
			if (strlen($primaryUrl) > 0) {
				$interface->assign('primaryUrl', $primaryUrl);
			}
		}

		$rightsStatements = array();
		if ($this->modsData->accessCondition->count()){
			$accessConditions = $this->modsData->accessCondition->children('http://marmot.org/local_mods_extension');
			if (strlen($accessConditions->rightsStatement)){
				$rightsStatements[] = (string)$accessConditions->rightsStatement;
			}
		}
		$interface->assign('rightsStatements', $rightsStatements);

		/** @var SimpleXMLElement $marmotExtension */
		$marmotExtension = $this->modsData->extension->children('http://marmot.org/local_mods_extension');
		if (@count($marmotExtension) > 0){
			$interface->assign('marmotExtension', $marmotExtension);

			$marmotLocal = $marmotExtension->marmotLocal;
			if (count($marmotLocal) > 0){
				if ($marmotLocal->hasTranscription){
					$transcriptionText = (string)$marmotExtension->marmotLocal->hasTranscription->transcriptionText;
					$transcriptionText = str_replace("\r\n", '<br/>', $transcriptionText);

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
									'language' => (string)$marmotExtension->marmotLocal->hasTranscription->transcriptionLanguage,
									'text' => $transcriptionTextWithLinks,
							)
					);
				}

				if (count($marmotLocal->alternateName) > 0){
					$alternateNames = array();
					foreach ($marmotLocal->alternateName as $alternateName){
						if (strlen($alternateName->alternateName) > 0){
							$alternateNames[] = (string)$alternateName->alternateName;
						}
					}
					$interface->assign('alternateNames', $alternateNames);
				}

				$this->recordDriver->loadRelatedEntities();

				$interface->assign('hasMilitaryService', false);
				if (count($marmotExtension->marmotLocal->militaryService) > 0){
					/** @var SimpleXMLElement $record */
					$record = $marmotExtension->marmotLocal->militaryService->militaryRecord;
					if ($record->militaryBranch != 'none' || $record->militaryConflict != 'none'){
						$militaryRecord = array(
								'branch' => $fedoraUtils->getObjectLabel((string)$record->militaryBranch),
								'branchLink' => '/Archive/' . $record->militaryBranch . '/Organization',
								'conflict' => $fedoraUtils->getObjectLabel((string)$record->militaryConflict),
								'conflictLink' => '/Archive/' . $record->militaryConflict . '/Event',
						);
						$interface->assign('militaryRecord', $militaryRecord);
						$interface->assign('hasMilitaryService', true);
					}
				}

				$addressInfo = array();
				if (strlen($marmotExtension->marmotLocal->latitude) ||
						strlen($marmotExtension->marmotLocal->longitude) ||
						strlen($marmotExtension->marmotLocal->addressStreetNumber) ||
						strlen($marmotExtension->marmotLocal->addressStreet) ||
						strlen($marmotExtension->marmotLocal->addressCity) ||
						strlen($marmotExtension->marmotLocal->addressCounty) ||
						strlen($marmotExtension->marmotLocal->addressState) ||
						strlen($marmotExtension->marmotLocal->addressZipCode) ||
						strlen($marmotExtension->marmotLocal->addressCountry) ||
						strlen($marmotExtension->marmotLocal->addressOtherRegion)) {

					if (strlen((string)$marmotExtension->marmotLocal->latitude) > 0) {
						$addressInfo['latitude'] = (string)$marmotExtension->marmotLocal->latitude;
					}
					if (strlen((string)$marmotExtension->marmotLocal->longitude) > 0) {
						$addressInfo['longitude'] = (string)$marmotExtension->marmotLocal->longitude;
					}

					if (strlen((string)$marmotExtension->marmotLocal->addressStreetNumber) > 0) {
						$addressInfo['hasDetailedAddress'] = true;
						$addressInfo['addressStreetNumber'] = (string)$marmotExtension->marmotLocal->addressStreetNumber;
					}
					if (strlen((string)$marmotExtension->marmotLocal->addressStreet) > 0) {
						$addressInfo['hasDetailedAddress'] = true;
						$addressInfo['addressStreet'] = (string)$marmotExtension->marmotLocal->addressStreet;
					}
					if (strlen((string)$marmotExtension->marmotLocal->addressCity) > 0) {
						$addressInfo['hasDetailedAddress'] = true;
						$addressInfo['addressCity'] = (string)$marmotExtension->marmotLocal->addressCity;
					}
					if (strlen((string)$marmotExtension->marmotLocal->addressState) > 0) {
						$addressInfo['hasDetailedAddress'] = true;
						$addressInfo['addressCounty'] = (string)$marmotExtension->marmotLocal->addressCounty;
					}
					if (strlen((string)$marmotExtension->marmotLocal->addressState) > 0) {
						$addressInfo['hasDetailedAddress'] = true;
						$addressInfo['addressState'] = (string)$marmotExtension->marmotLocal->addressState;
					}
					if (strlen((string)$marmotExtension->marmotLocal->addressZipCode) > 0) {
						$addressInfo['hasDetailedAddress'] = true;
						$addressInfo['addressZipCode'] = (string)$marmotExtension->marmotLocal->addressZipCode;
					}
					if (strlen((string)$marmotExtension->marmotLocal->addressStreet) > 0) {
						$addressInfo['hasDetailedAddress'] = true;
						$addressInfo['addressCountry'] = (string)$marmotExtension->marmotLocal->addressCountry;
					}
					$interface->assign('addressInfo', $addressInfo);
				}//End verifying checking for address information

				$notes = array();
				if (strlen($marmotExtension->marmotLocal->personNotes) > 0){
					$notes[] = (string)$marmotExtension->marmotLocal->personNotes;
				}
				if (strlen($marmotExtension->marmotLocal->citationNotes) > 0){
					$notes[] = (string)$marmotExtension->marmotLocal->citationNotes;
				}
				$interface->assign('notes', $notes);

			}//End verifying marmot local is valid
		}//End verifying marmot extension is valid

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

	function loadExploreMoreContent(){
		require_once ROOT_DIR . '/sys/ExploreMore.php';
		global $interface;
		$exploreMore = new ExploreMore();
		$exploreMore->loadExploreMoreSidebar('archive', $this->recordDriver);


		$relatedSubjects = $this->recordDriver->getAllSubjectHeadings();

		$ebscoMatches = $exploreMore->loadEbscoOptions('archive', array(), implode($relatedSubjects, " or "));
		if (count($ebscoMatches) > 0){
			$interface->assign('relatedArticles', $ebscoMatches);
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