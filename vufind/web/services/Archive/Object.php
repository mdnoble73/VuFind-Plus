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
	//protected $dcData;
	protected $modsData;
	protected $relsExtData;
	protected $relatedPeople;
	protected $relatedPlaces;
	protected $relatedEvents;
	protected $formattedSubjects;
	protected $links;

	/**
	 * @param string $mainContentTemplate  Name of the SMARTY template file for the main content of the Full Record View Pages
	 * @param string $pageTitle            What to display is the html title tag
	 */
	function display($mainContentTemplate, $pageTitle=null) {
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

		//Load the dublin core data stream
		/*$dublinCoreStream = $this->archiveObject->getDatastream('DC');
		$temp = tempnam('/tmp', 'dc');
		$result = $dublinCoreStream->getContent($temp);
		$this->dcData = trim(file_get_contents($temp));
		unlink($temp);*/

		//Load the MODS data stream
		$this->modsData = $fedoraUtils->getModsData($this->archiveObject);
		$interface->assign('mods', $this->modsData);

		//Extract Subjects
		$formattedSubjects = array();
		foreach ($this->modsData->subject as $subjects){

			foreach ($subjects->topic as $subjectPart){
				$subjectLink = $configArray['Site']['path'] . '/Archive/Results?lookfor=';
				$subjectLink .= '&filter[]=mods_subject_topic_ms:"' . $subjectPart . '"';
				$formattedSubjects[] = array(
						'link' => $subjectLink,
						'label' => $subjectPart
				);
			}
		}
		$this->formattedSubjects = $formattedSubjects;
		$interface->assign('subjects', $formattedSubjects);

		$rightsStatements = array();
		foreach ($this->modsData->accessCondition as $condition){
			$marmotData = $condition->children('http://marmot.org/local_mods_extension');
			if (strlen($marmotData->rightsStatement)){
				$rightsStatements[] = (string)$marmotData->rightsStatement;
			}
		}
		$interface->assign('rightsStatements', $rightsStatements);

		/** @var SimpleXMLElement $marmotExtension */
		$marmotExtension = $this->modsData->extension->children('http://marmot.org/local_mods_extension');

		$this->relatedPeople = array();
		$this->relatedPlaces = array();
		$this->relatedEvents = array();

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
			}

			$entities = $marmotExtension->marmotLocal->relatedEntity;
			/** @var SimpleXMLElement $entity */
			foreach ($entities as $entity){
				$entityType = '';
				foreach ($entity->attributes() as $name => $value){
					if ($name == 'type'){
						$entityType = $value;
						break;
					}
				}
				$entityInfo = array(
						'pid' => (string)$entity->entityPid,
						'label' => (string)$entity->entityTitle
				);
				if ($entityType == 'person'){
					$entityInfo['link']= '/Archive/' . $entity->entityPid . '/Person';
					$this->relatedPeople[(string)$entity->entityPid] = $entityInfo;
				}elseif ($entityType == 'place'){
					$entityInfo['link']= '/Archive/' . $entity->entityPid . '/Place';
					$this->relatedPlaces[(string)$entity->entityPid] = $entityInfo;
				}elseif ($entityType == 'event'){
					$entityInfo['link']= '/Archive/' . $entity->entityPid . '/Event';
					$this->relatedEvents[(string)$entity->entityPid] = $entityInfo;
				}
			}
			if ($marmotExtension->marmotLocal->hasInterviewee){
				$interviewee = $marmotExtension->marmotLocal->hasInterviewee;
				$this->relatedPeople[] = array(
						'pid' => $interviewee->entityPid,
						'label' => $interviewee->entityTitle,
						'link' =>  '/Archive/' . $interviewee->entityPid . '/Person',
						'role' => 'Interviewee'
				);
			}

			foreach ($marmotExtension->marmotLocal->relatedPlace as $entity){
				if (count($entity->entityPlace) > 0 && strlen($entity->entityPlace->entityPid) > 0){
					$entityInfo = array(
							'pid' => (string)$entity->entityPlace->entityPid,
							'label' => (string)$entity->entityPlace->entityTitle

					);
					$entityInfo['link']= '/Archive/' . (string)$entity->entityPlace->entityPid . '/Place';
					$this->relatedPlaces[] = $entityInfo;
				}else {
					//Check to see if we have anything for this place
					if (strlen($entity->generalPlace->latitude) ||
							strlen($entity->generalPlace->longitude) ||
							strlen($entity->generalPlace->addressStreetNumber) ||
							strlen($entity->generalPlace->addressStreet) ||
							strlen($entity->generalPlace->addressCity) ||
							strlen($entity->generalPlace->addressCounty) ||
							strlen($entity->generalPlace->addressState) ||
							strlen($entity->generalPlace->addressZipCode) ||
							strlen($entity->generalPlace->addressCountry) ||
							strlen($entity->generalPlace->addressOtherRegion)){
					}
				}
			}

			$interface->assign('relatedPeople', $this->relatedPeople);
			$interface->assign('relatedPlaces', $this->relatedPlaces);
			$interface->assign('relatedEvents', $this->relatedEvents);



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

			if (count($marmotExtension->marmotLocal->externalLink) > 0){
				$this->links = array();
				/** @var SimpleXMLElement $linkInfo */
				foreach ($marmotExtension->marmotLocal->externalLink as $linkInfo){
					$linkAttributes = $linkInfo->attributes();
					if (strlen($linkInfo->linkText) == 0) {
						if (strlen((string)$linkAttributes['type']) == 0) {
							$linkText = $linkInfo->link;
						} else {
							$linkText = $linkAttributes['type'];
						}
					}else{
						$linkText = (string)$linkInfo->linkText;
					}
					$this->links[] = array(
							'type' => (string)$linkAttributes['type'],
							'link' => (string)$linkInfo->link,
							'text' => $linkText
					);
				}
				$interface->assign('externalLinks', $this->links);
			}

			$addressInfo = array();
			if (count($marmotExtension->marmotLocal->latitude) > 0){
				if (strlen((string)$marmotExtension->marmotLocal->latitude) > 0){
					$addressInfo['latitude'] = (string)$marmotExtension->marmotLocal->latitude;
				}
			}
			if (count($marmotExtension->marmotLocal->longitude) > 0){
				if (strlen((string)$marmotExtension->marmotLocal->longitude) > 0) {
					$addressInfo['longitude'] = (string)$marmotExtension->marmotLocal->longitude;
				}
			}
			$interface->assign('addressInfo', $addressInfo);

			$notes = array();
			if (strlen($marmotExtension->marmotLocal->personNotes) > 0){
				$notes[] = (string)$marmotExtension->marmotLocal->personNotes;
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
		$description = (string)$this->modsData->abstract;
		$interface->assign('description', $description);

		$interface->assign('large_image', $fedoraUtils->getObjectImageUrl($this->archiveObject, 'large', $model));
		$interface->assign('medium_image', $fedoraUtils->getObjectImageUrl($this->archiveObject, 'medium', $model));

		$repositoryLink = $configArray['Islandora']['repositoryUrl'] . '/islandora/object/' . $this->pid;
		$interface->assign('repositoryLink', $repositoryLink);
	}

	function loadExploreMoreContent(){
		require_once ROOT_DIR . '/sys/ArchiveSubject.php';
		global $interface;
		$archiveSubjects = new ArchiveSubject();
		$subjectsToIgnore = array();
		$subjectsToRestrict = array();
		if ($archiveSubjects->find(true)){
			$subjectsToIgnore = array_flip(explode("\r\n", strtolower($archiveSubjects->subjectsToIgnore)));
			$subjectsToRestrict = array_flip(explode("\r\n", strtolower($archiveSubjects->subjectsToRestrict)));
		}
		$relatedCollections = $this->getRelatedCollections();
		$relatedSubjects = array();
		$numSubjectsAdded = 0;
		if (strlen($this->archiveObject->label) > 0) {
			$relatedSubjects[$this->archiveObject->label] = '"' . $this->archiveObject->label . '"';
		}
		for ($i = 0; $i < 2; $i++){
			foreach ($this->formattedSubjects as $subject) {
				$searchSubject = preg_replace('/\(.*?\)/',"", $subject['label']);
				$searchSubject = trim(preg_replace('/[\/|:.,"]/',"", $searchSubject));
				$lowerSubject = strtolower($searchSubject);
				if (!array_key_exists($lowerSubject, $subjectsToIgnore)) {
					if ($i == 0){
						//First pass, just add primary subjects
						if (!array_key_exists($lowerSubject, $subjectsToRestrict)) {
							$relatedSubjects[$lowerSubject] = '"' . $searchSubject . '"';
						}
					}else{
						//Second pass, add restricted subjects, but only if we don't have 5 subjects already
						if (array_key_exists($lowerSubject, $subjectsToRestrict) && count($relatedSubjects) <= 5) {
							$relatedSubjects[$lowerSubject] = '"' . $searchSubject . '"';
						}
					}
				}
			}
		}
		$relatedSubjects = array_slice($relatedSubjects, 0, 5);
		foreach ($this->relatedPeople as $person) {
			$label = (string)$person['label'];
			$relatedSubjects[$label] = '"' . $label . '"';
			$numSubjectsAdded++;
		}
		$relatedSubjects = array_slice($relatedSubjects, 0, 8);

		//Get works that are directly related to this entity based on linked data
		$linkedWorks = $this->getLinkedWorks($relatedCollections);

		$exploreMore = new ExploreMore();
		$exploreMore->getRelatedWorks($relatedSubjects);
		$ebscoMatches = $exploreMore->loadEbscoOptions('archive', array(), implode($relatedSubjects, " or "));
		if (count($ebscoMatches) > 0){
			$interface->assign('relatedArticles', $ebscoMatches);
		}
		$searchTerm = implode(" OR ", $relatedSubjects);
		$exploreMore->getRelatedArchiveContent('archive', array(), $searchTerm);


	}

	protected function getRelatedCollections() {
		global $interface;

		//Get parent collection(s) for the entity.
		$collectionsRaw = $this->archiveObject->relationships->get(FEDORA_RELS_EXT_URI, 'isMemberOfCollection');
		$collections = array();
		$fedoraUtils = FedoraUtils::getInstance();
		foreach ($collectionsRaw as $collectionInfo) {
			$collectionObject = $fedoraUtils->getObject($collectionInfo['object']['value']);
			if ($collectionObject != null) {
				$okToAdd = true;
				$mods = FedoraUtils::getInstance()->getModsData($collectionObject);
				if ($mods != null) {
					if (count($mods->extension) > 0) {
						/** @var SimpleXMLElement $marmotExtension */
						$marmotExtension = $mods->extension->children('http://marmot.org/local_mods_extension');
						if (count($marmotExtension) > 0) {
							$marmotLocal = $marmotExtension->marmotLocal;
							if ($marmotLocal->count() > 0) {
								$pikaOptions = $marmotLocal->pikaOptions;
								if ($pikaOptions->count() > 0) {
									$okToAdd = $pikaOptions->includeInPika != 'no';
								}
							}
						}
					}
				} else {
					//If we don't get mods, exclude from the display
					$okToAdd = false;
				}

				if ($okToAdd) {
					$collections[] = array(
							'pid' => $collectionInfo['object']['value'],
							'label' => $collectionObject->label,
							'link' => '/Archive/' . $collectionInfo['object']['value'] . '/Exhibit',
							'image' => $fedoraUtils->getObjectImageUrl($collectionObject, 'small'),
					);
				}
			}
		}
		$interface->assign('collections', $collections);
		return $collections;
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
						'&titles=' . urlencode($searchTerm);
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

	private function getLinkedWorks($relatedCollections) {
		//Check for works that are directly related to this entity
		if (isset($this->links)) {
			foreach ($this->links as $link) {
				if ($link['type'] == 'relatedPika') {
					preg_match('/^.*\/GroupedWork\/([a-f0-9-]+)$/', $link['link'], $matches);
					$workId = $matches[1];
				}
			}
		}
	}
}