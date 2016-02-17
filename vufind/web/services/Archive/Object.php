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

	/**
	 * @param string $mainContentTemplate  Name of the SMARTY template file for the main content of the Full Record View Pages
	 * @param string $pageTitle            What to display is the html title tag
	 * @param bool|string $sidebarTemplate      Sets the sidebar template, set to false or empty string for no sidebar
	 */
	function display($mainContentTemplate, $pageTitle=null/*, $sidebarTemplate='explore-more-sidebar.tpl'*/) {
		$pageTitle == null ? $this->archiveObject->label : $pageTitle;
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
		$interface->assign('marmotExtension', $marmotExtension);

		$this->relatedPeople = array();
		$this->relatedPlaces = array();
		$this->relatedEvents = array();

		if (count($marmotExtension) > 0){
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
				$links = array();
				/** @var SimpleXMLElement $linkInfo */
				foreach ($marmotExtension->marmotLocal->externalLink as $linkInfo){
					$linkAttributes = $linkInfo->attributes();
					$links[] = array(
							'type' => (string)$linkAttributes['type'],
							'link' => (string)$linkInfo
					);
				}
				$interface->assign('externalLinks', $links);
			}
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
		global $interface;
		global $configArray;

		//Get parent collection(s) for the entity.
		$collectionsRaw = $this->archiveObject->relationships->get(FEDORA_RELS_EXT_URI, 'isMemberOfCollection');
		$collections = array();
		$fedoraUtils = FedoraUtils::getInstance();
		foreach ($collectionsRaw as $collectionInfo){
			$collectionObject = $fedoraUtils->getObject($collectionInfo['object']['value']);
			if ($collectionObject != null){
				$collections[] = array(
						'pid' => $collectionInfo['object']['value'],
						'label' => $collectionObject->label,
						'link' => '/Archive/' . $collectionInfo['object']['value'] . '/Exhibit',
						'image' => $fedoraUtils->getObjectImageUrl($collectionObject, 'small'),
				);
			}
		}
		$interface->assign('collections', $collections);

		// Additional Demo Variables
		$videoImage = ''; //TODO set
		$interface->assign('videoImage', $videoImage);
		//$videoLink = "http://islandora.marmot.org/islandora/object/mandala:2024/datastream/OBJ/view"; //TODO set
		//$interface->assign('videoLink', $videoLink);

		// Define The Section List for the explore more
		$AdditionalSections[0] = array(
			'title' => 'Images',
			'image' => 'mandalaoc2_thumb.JPG',
			'link' => '',
		);

		$AdditionalSections[1] = array(
			'title' => 'Videos',
			'image' => 'mandalaoc3_thumb.JPG',
			'link' => '',
		);

		$AdditionalSections[2] = array(
			'title' => 'Articles',
			'image' => 'mandalaoc4_thumb.JPG',
			'link' => '',
		);

		$interface->assign('sectionList', $AdditionalSections);

		//Load related content
		//TODO: load this from someplace real (like Islandora)
		$exploreMoreMainLinks = array();
		$exploreMoreMainLinks[] = array(
			'title' => 'Day by Day',
			'url' => $configArray['Site']['path'] . '/Archive/Exhibit'
		);
		$exploreMoreMainLinks[] = array(
			'title' => 'Community Mandala',
			'url' => $configArray['Site']['path'] . '/Archive/3/Exhibit'
		);
		$exploreMoreMainLinks[] = array(
			'title' => 'In the News',
			'url' => $configArray['Site']['path'] . '/Archive/4/Exhibit'
		);
		$exploreMoreMainLinks[] = array(
			'title' => '2010 Mandala',
			'url' => $configArray['Site']['path'] . '/Archive/5/Exhibit'
		);
		$interface->assign('exploreMoreMainLinks', $exploreMoreMainLinks);

		//Load related catalog content
		//Create a search object to get related content
		$exploreMoreCatalogUrl = $configArray['Site']['path'] . '/Search/AJAX?method=GetListTitles&id=search:22622';
		$interface->assign('exploreMoreCatalogUrl', $exploreMoreCatalogUrl);

		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchTerm = "(";
		foreach ($this->relatedPeople as $person){
			$searchTerm .= '"' . $person['label'] . '" OR ';
		}
		foreach ($this->formattedSubjects as $subject){
			$searchTerm .= '"' . $subject['label'] . '" OR ';
		}
		$searchTerm = substr($searchTerm, 0, -4);
		$searchTerm .= ")";
		$searchObject->init('local', $searchTerm);
		//$searchObject->setSearchType('Subject');
		$searchObject->addFilter('literary_form_full:Non Fiction');
		$searchObject->setLimit(5);
		$results = $searchObject->processSearch(true, false);

		if ($results && isset($results['response'])){
			$similarTitles = array(
					'numFound' => $results['response']['numFound'],
					'allResultsLink' => $searchObject->renderSearchUrl(),
					'topHits' => array()
			);
			foreach ($results['response']['docs'] as $doc){
				/** @var GroupedWorkDriver $driver */
				$driver = RecordDriverFactory::initRecordDriver($doc);
				$similarTitle = array(
						'title' => $driver->getTitle(),
						'link' => $driver->getLinkUrl(),
						'cover' => $driver->getBookcoverUrl('small')
				);
				$similarTitles['topHits'][] = $similarTitle;
			}
		}else{
			$similarTitles = array(
					'numFound' => 0,
					'topHits' => array()
			);
		}
		$interface->assign('related_titles', $similarTitles);

	}
}