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

		//Load the MODS data stream
		$this->modsData = $this->recordDriver->getModsData();
		$interface->assign('mods', $this->modsData);
		$this->modsModsData = $this->modsData->children('http://www.loc.gov/mods/v3');

		$this->formattedSubjects = $this->recordDriver->getAllSubjectsWithLinks();
		$interface->assign('subjects', $this->formattedSubjects);

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
					$interface->assign('transcription',
							array(
									'language' => (string)$marmotExtension->marmotLocal->hasTranscription->transcriptionLanguage,
									'text' => $transcriptionText
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

			$this->links = $this->recordDriver->getLinks();
			$interface->assign('externalLinks', $this->links);

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
					strlen($marmotExtension->marmotLocal->addressOtherRegion)){

				if (strlen((string)$marmotExtension->marmotLocal->latitude) > 0){
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
			}

			$notes = array();
			if (strlen($marmotExtension->marmotLocal->personNotes) > 0){
				$notes[] = (string)$marmotExtension->marmotLocal->personNotes;
			}
			if (strlen($marmotExtension->marmotLocal->citationNotes) > 0){
				$notes[] = (string)$marmotExtension->marmotLocal->citationNotes;
			}
			$interface->assign('notes', $notes);
		}

		//Load the RELS-EXT data stream
		/*$relsExtStream = $this->archiveObject->getDatastream('RELS-EXT');
		$temp = tempnam('/tmp', 'relext');
		$result = $relsExtStream->getContent($temp);
		$relsExtData = simplexml_load_string(file_get_contents($temp));
		if (count($relsExtData) == 0){
			$relsExtData = $relsExtData->children('http://www.w3.org/1999/02/22-rdf-syntax-ns#');
		}
		$this->relsExtData = $relsExtData;
		unlink($temp);*/

		$model = $this->archiveObject->models[0];
		$model = str_replace('islandora:', '', $model);

		$title = $this->archiveObject->label;
		$interface->assign('title', $title);
		$interface->setPageTitle($title);
		if ($this->modsData->abstract){
			$description = (string)$this->modsData->abstract;
			$interface->assign('description', $description);
		}else{
			$description = (string)$this->modsModsData->abstract;
			$interface->assign('description', $description);
		}

		$interface->assign('large_image', $fedoraUtils->getObjectImageUrl($this->archiveObject, 'large', $model));
		$interface->assign('medium_image', $fedoraUtils->getObjectImageUrl($this->archiveObject, 'medium', $model));

		$repositoryLink = $configArray['Islandora']['repositoryUrl'] . '/islandora/object/' . $this->pid;
		$interface->assign('repositoryLink', $repositoryLink);
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
		if (!isset($this->links)){
			return;
		}
		foreach ($this->links as $link){
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