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

			$entities = $marmotExtension->xpath('/marmotLocal/relatedEntity');
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
						'pid' => $entity->entityPid,
						'label' => $entity->entityTitle
				);
				if ($entityType == 'person'){
					$entityInfo['link']= '/Archive/' . $entity->entityPid . '/Person';
					$this->relatedPeople[] = $entityInfo;
				}elseif ($entityType == 'place'){
					$entityInfo['link']= '/Archive/' . $entity->entityPid . '/Place';
					$this->relatedPlaces[] = $entityInfo;
				}elseif ($entityType == 'event'){
					$entityInfo['link']= '/Archive/' . $entity->entityPid . '/Event';
					$this->relatedEvents[] = $entityInfo;
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
			$interface->assign('relatedPeople', $this->relatedPeople);
			$interface->assign('relatedPlaces', $this->relatedPlaces);
			$interface->assign('relatedEvents', $this->relatedEvents);

			if (count($marmotExtension->marmotLocal->militaryService) > 0){
				$interface->assign('hasMilitaryService', true);
				/** @var SimpleXMLElement $record */
				$record = $marmotExtension->marmotLocal->militaryService->militaryRecord;
				$militaryRecord = array(
						'branch' => $fedoraUtils->getObjectLabel((string)$record->militaryBranch),
						'conflict' => $fedoraUtils->getObjectLabel((string)$record->militaryConflict),
				);
				$interface->assign('militaryRecord', $militaryRecord);
			}

			if (count($marmotExtension->marmotLocal->externalLink) > 0){
				$this->links = array();
				/** @var SimpleXMLElement $linkInfo */
				foreach ($marmotExtension->marmotLocal->externalLink as $linkInfo){
					$linkAttributes = $linkInfo->attributes();
					$this->links[] = array(
							'type' => (string)$linkAttributes['type'],
							'link' => (string)$linkInfo->link
					);
				}
				$interface->assign('externalLinks', $this->links);
			}

			$addressInfo = array();
			if (count($marmotExtension->marmotLocal->latitude) > 0){
				$addressInfo['latitude'] = (string)$marmotExtension->marmotLocal->latitude;
			}
			if (count($marmotExtension->marmotLocal->longitude) > 0){
				$addressInfo['longitude'] = (string)$marmotExtension->marmotLocal->longitude;
			}
			$interface->assign('addressInfo', $addressInfo);
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
		$this->getRelatedCollections();
		$relatedSubjects = array();
		$numSubjectsAdded = 0;
		if (strlen($this->archiveObject->label) > 0) {
			$relatedSubjects[$this->archiveObject->label] = '"' . $this->archiveObject->label . '"';
		}
		for ($i = 0; $i < 2; $i++){
			foreach ($this->formattedSubjects as $subject) {
				$lowerSubject = strtolower($subject['label']);
				if (!array_key_exists($lowerSubject, $subjectsToIgnore)) {
					if ($i == 0){
						//First pass, just add primary subjects
						if (!array_key_exists($lowerSubject, $subjectsToRestrict)) {
							$relatedSubjects[$lowerSubject] = '"' . $subject['label'] . '"';
						}
					}else{
						//Second pass, add restricted subjects, but only if we don't have 5 subjects already
						if (array_key_exists($lowerSubject, $subjectsToRestrict) && count($relatedSubjects) <= 5) {
							$relatedSubjects[$lowerSubject] = '"' . $subject['label'] . '"';
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

		$exploreMore = new ExploreMore();
		$this->getRelatedWorks($relatedSubjects);
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
	}

	protected function loadLinkedData(){
		global $interface;
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
			}
		}
	}
}