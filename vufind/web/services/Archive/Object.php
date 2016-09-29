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
		$productionTeam = $this->recordDriver->getProductionTeam();
		$relatedOrganizations = $this->recordDriver->getRelatedOrganizations();
		$relatedPlaces = $this->recordDriver->getRelatedPlaces();

		//Sort all the related information
		usort($relatedEvents, 'ExploreMore::sortRelatedEntities');
		usort($relatedPeople, 'ExploreMore::sortRelatedEntities');
		usort($productionTeam, 'ExploreMore::sortRelatedEntities');
		usort($relatedOrganizations, 'ExploreMore::sortRelatedEntities');
		usort($relatedPlaces, 'ExploreMore::sortRelatedEntities');

		//Do final assignment
		$interface->assign('relatedEvents', $relatedEvents);
		$interface->assign('relatedPeople', $relatedPeople);
		$interface->assign('productionTeam', $productionTeam);
		$interface->assign('relatedOrganizations', $relatedOrganizations);
		$interface->assign('relatedPlaces', $relatedPlaces);

		$directlyRelatedObjects = $this->recordDriver->getDirectlyRelatedArchiveObjects();
		$interface->assign('directlyRelatedObjects', $directlyRelatedObjects);

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

		list($namespace) = explode(':', $this->pid);
		//Find the owning library
		$owningLibrary = new Library();
		$owningLibrary->archiveNamespace = $namespace;
		if ($owningLibrary->find(true) && $owningLibrary->N == 1){
			$interface->assign ('allowRequestsForArchiveMaterials', $owningLibrary->allowRequestsForArchiveMaterials);
		} else {
			$interface->assign ('allowRequestsForArchiveMaterials', false);
		}

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

			$dateIssued = $this->recordDriver->getModsValue('dateIssued', 'mods', $originInfo);
			$interface->assign('dateIssued', $dateIssued);
		}
		
		$identifier = $this->recordDriver->getModsValues('identifier', 'mods');
		$interface->assign('identifier', FedoraUtils::cleanValues($identifier));

		$language = $this->recordDriver->getModsValue('languageTerm', 'mods');
		$interface->assign('language', FedoraUtils::cleanValue($language));

		$physicalDescriptions = $this->recordDriver->getModsValues('physicalDescription', 'mods');
		$physicalExtents = array();
		foreach ($physicalDescriptions as $physicalDescription){
			$extent = $this->recordDriver->getModsValue('extent', 'mods', $physicalDescription);
			$form = $this->recordDriver->getModsValue('form', 'mods', $physicalDescription);
			if (empty($extent)){
				$extent = $form;
			}elseif (!empty($form)){
				$extent .= " ($form)";
			}
			$physicalExtents[] = $extent;

		}
		$interface->assign('physicalExtents', $physicalExtents);

		$physicalLocation = $this->recordDriver->getModsValues('physicalLocation', 'mods');
		$interface->assign('physicalLocation', $physicalLocation);

		$interface->assign('postcardPublisherNumber', $this->recordDriver->getModsValue('postcardPublisherNumber', 'marmot'));

		$correspondence = $this->recordDriver->getModsValue('correspondence', 'marmot');
		$hasCorrespondenceInfo = false;
		if ($correspondence){
			$includesStamp = $this->recordDriver->getModsValue('includesStamp', 'marmot', $correspondence);
			if ($includesStamp == 'yes'){
				$interface->assign('includesStamp', true);
				$hasCorrespondenceInfo = true;
			}
			$datePostmarked = $this->recordDriver->getModsValue('datePostmarked', 'marmot', $correspondence);
			if ($datePostmarked){
				$interface->assign('datePostmarked', $datePostmarked);
				$hasCorrespondenceInfo = true;
			}
			$relatedPlace = $this->recordDriver->getModsValue('entityPlace', 'marmot', $correspondence);
			if ($relatedPlace){
				$placePid = $this->recordDriver->getModsValue('entityPid', 'marmot', $relatedPlace);
				if ($placePid){
					$postMarkLocationObject = $fedoraUtils->getObject($placePid);
					if ($postMarkLocationObject){
						$postMarkLocationDriver = RecordDriverFactory::initRecordDriver($postMarkLocationObject);
						$interface->assign('postMarkLocation', array(
								'link' => $postMarkLocationDriver->getRecordUrl(),
								'label' => $postMarkLocationDriver->getTitle(),
								'role' => 'Postmark Location'
						));
						$hasCorrespondenceInfo = true;
					}
				}else{
					$placeTitle = $this->recordDriver->getModsValue('entityTitle', 'marmot', $relatedPlace);
					if ($placeTitle){
						$interface->assign('postMarkLocation', array(
								'label' => $placeTitle,
								'role' => 'Postmark Location'
						));
						$hasCorrespondenceInfo = true;
					}
				}
			}

			$relatedPerson = $this->recordDriver->getModsValue('relatedPersonOrg', 'marmot', $correspondence);
			if ($relatedPerson){
				$personPid = $this->recordDriver->getModsValue('entityPid', 'marmot', $relatedPerson);
				if ($personPid){
					$correspondenceRecipientObject = $fedoraUtils->getObject($personPid);
					if ($correspondenceRecipientObject){
						$correspondenceRecipientDriver = RecordDriverFactory::initRecordDriver($correspondenceRecipientObject);
						$interface->assign('correspondenceRecipient', array(
								'link' => $correspondenceRecipientDriver->getRecordUrl(),
								'label' => $correspondenceRecipientDriver->getTitle(),
								'role' => 'Correspondence Recipient'
						));
						$hasCorrespondenceInfo = true;
					}
				}else{
					$personTitle = $this->recordDriver->getModsValue('entityTitle', 'marmot', $relatedPerson);
					if ($personTitle){
						$interface->assign('correspondenceRecipient', array(
								'label' => $personTitle,
								'role' => 'Correspondence Recipient'
						));
						$hasCorrespondenceInfo = true;
					}
				}
			}
		}
		$interface->assign('hasCorrespondenceInfo', $hasCorrespondenceInfo);

		$shelfLocator = $this->recordDriver->getModsValues('shelfLocator', 'mods');
		$interface->assign('shelfLocator', FedoraUtils::cleanValues($shelfLocator));

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

		$rightsHolder = $this->recordDriver->getModsValue('rightsHolder', 'marmot');
		if (!empty($rightsHolder)){
			$rightsHolderPid = $this->recordDriver->getModsValue('entityPid', 'marmot', $rightsHolder);
			$rightsHolderTitle = $this->recordDriver->getModsValue('entityTitle', 'marmot', $rightsHolder);
			if ($rightsHolderPid){
				$interface->assign('rightsHolderTitle', $rightsHolderTitle);
				$rightsHolderObj = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($rightsHolderPid));
				$interface->assign('rightsHolderLink', $rightsHolderObj->getRecordUrl());
			}
		}

		$rightsCreator = $this->recordDriver->getModsValue('rightsCreator', 'marmot');
		if (!empty($rightsCreator)){
			$rightsCreatorPid = $this->recordDriver->getModsValue('entityPid', 'marmot', $rightsCreator);
			$rightsCreatorTitle = $this->recordDriver->getModsValue('entityTitle', 'marmot', $rightsCreator);
			if ($rightsCreatorPid){
				$interface->assign('rightsCreatorTitle', $rightsCreatorTitle);
				$rightsCreatorObj = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($rightsCreatorPid));
				$interface->assign('rightsCreatorLink', $rightsCreatorObj->getRecordUrl());
			}
		}

		$academicResearchSection = $this->recordDriver->getModsValue('academicResearch', 'marmot');
		$hasAcademicResearchData = false;
		if (!empty($academicResearchSection)){
			$researchType = FedoraUtils::cleanValue($this->recordDriver->getModsValue('academicResearchType', 'marmot', $academicResearchSection));
			if (strlen($researchType)){
				$hasAcademicResearchData = true;
				$interface->assign('researchType', $researchType);
			}

			$researchLevel = FedoraUtils::cleanValue($this->recordDriver->getModsValue('academicResearchLevel', 'marmot', $academicResearchSection));
			if (strlen($researchLevel)) {
				$hasAcademicResearchData = true;
				$interface->assign('researchLevel', ucwords($researchLevel));
			}

			$degreeName = FedoraUtils::cleanValue($this->recordDriver->getModsValue('degreeName', 'marmot', $academicResearchSection));
			if (strlen($degreeName)) {
				$hasAcademicResearchData = true;
				$interface->assign('degreeName', $degreeName);
			}

			$degreeDiscipline = FedoraUtils::cleanValue($this->recordDriver->getModsValue('degreeDiscipline', 'marmot', $academicResearchSection));
			if (strlen($degreeDiscipline)){
				$hasAcademicResearchData = true;
				$interface->assign('degreeDiscipline', $degreeDiscipline);
			}

			$peerReview = FedoraUtils::cleanValue($this->recordDriver->getModsValue('peerReview', 'marmot', $academicResearchSection));
			$interface->assign('peerReview', ucwords($peerReview));

			$defenceDate = FedoraUtils::cleanValue($this->recordDriver->getModsValue('defenceDate', 'marmot', $academicResearchSection));
			if (strlen($defenceDate)) {
				$hasAcademicResearchData = true;
				$interface->assign('defenceDate', $defenceDate);
			}

			$acceptedDate = FedoraUtils::cleanValue($this->recordDriver->getModsValue('acceptedDate', 'marmot', $academicResearchSection));
			if (strlen($acceptedDate)) {
				$hasAcademicResearchData = true;
				$interface->assign('acceptedDate', $acceptedDate);
			}

			$relatedAcademicPeople = $this->recordDriver->getModsValues('relatedPersonOrg', 'marmot', $academicResearchSection);
			if ($relatedAcademicPeople){
				$academicPeople = array();
				foreach ($relatedAcademicPeople as $relatedPerson){
					$personPid = $this->recordDriver->getModsValue('entityPid', 'marmot', $relatedPerson);
					$role = ucwords($this->recordDriver->getModsValue('role', 'marmot', $relatedPerson));
					if ($personPid){
						$academicPersonObject = $fedoraUtils->getObject($personPid);
						if ($academicPersonObject){
							$academicPersonDriver = RecordDriverFactory::initRecordDriver($academicPersonObject);
							$academicPeople[] = array(
									'link' => $academicPersonDriver->getRecordUrl(),
									'label' => $academicPersonDriver->getTitle(),
									'role' => $role
							);
						}
					}else{
						$personTitle = $this->recordDriver->getModsValue('entityTitle', 'marmot', $relatedPerson);
						if ($personTitle){
							$academicPeople[] = array(
									'label' => $personTitle,
									'role' => $role
							);
						}
					}
				}
				if (count($academicPeople) > 0){
					$interface->assign('academicPeople', $academicPeople);
					$hasAcademicResearchData = true;
				}
			}

		}
		$interface->assign('hasAcademicResearchData', $hasAcademicResearchData);

		$transcriptions = $this->recordDriver->getModsValues('hasTranscription', 'marmot');
		if ($transcriptions){
			$transcriptionInfo = array();
			foreach ($transcriptions as $transcription){
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
				if (strlen($transcriptionTextWithLinks) > 0){
					$transcript = array(
							'language' => $this->recordDriver->getModsValue('transcriptionLanguage', 'marmot', $transcription),
							'text' => $transcriptionTextWithLinks,
							'location' => $this->recordDriver->getModsValue('transcriptionLocation', 'marmot', $transcription)
					);
					$transcriptionInfo[] = $transcript;
				}
			}

			if (count($transcriptionInfo) > 0){
				$interface->assign('transcription',$transcriptionInfo);
			}
		}


		$alternateNames = $this->recordDriver->getModsValues('alternateName', 'marmot');
		$interface->assign('alternateNames', FedoraUtils::cleanValues($alternateNames));

		$this->recordDriver->loadRelatedEntities();

		$interface->assign('hasEducationInfo', false);
		$academicRecord = $this->recordDriver->getModsValue('education', 'marmot');
		if (strlen($academicRecord) > 0){
			$degreeName = FedoraUtils::cleanValue($this->recordDriver->getModsValue('degreeName', 'marmot', $academicRecord));
			if ($degreeName){
				$interface->assign('degreeName', $degreeName);
				$hasEducationInfo = true;
			}

			$graduationDate = FedoraUtils::cleanValue($this->recordDriver->getModsValue('graduationDate', 'marmot', $academicRecord));
			if ($graduationDate){
				$interface->assign('graduationDate', $graduationDate);
				$hasEducationInfo = true;
			}

			$relatedEducationPeople = $this->recordDriver->getModsValues('relatedPersonOrg', 'marmot', $academicRecord);
			if ($relatedEducationPeople){
				$educationPeople = array();
				foreach ($relatedEducationPeople as $relatedPerson){
					$personPid = $this->recordDriver->getModsValue('entityPid', 'marmot', $relatedPerson);
					$role = ucwords($this->recordDriver->getModsValue('role', 'marmot', $relatedPerson));
					if ($personPid){
						$educationPersonObject = $fedoraUtils->getObject($personPid);
						if ($educationPersonObject){
							$educationPersonDriver = RecordDriverFactory::initRecordDriver($educationPersonObject);
							$educationPeople[] = array(
									'link' => $educationPersonDriver->getRecordUrl(),
									'label' => $educationPersonDriver->getTitle(),
									'role' => $role
							);
						}
					}else{
						$personTitle = $this->recordDriver->getModsValue('entityTitle', 'marmot', $relatedPerson);
						if ($personTitle){
							$educationPeople[] = array(
									'label' => $personTitle,
									'role' => $role
							);
						}
					}
					$hasEducationInfo = true;
				}
				if (count($educationPeople) > 0){
					$interface->assign('educationPeople', $educationPeople);
				}
			}

			$interface->assign('hasEducationInfo', $hasEducationInfo);
		}

		$interface->assign('hasMilitaryService', false);
		$militaryService = $this->recordDriver->getModsValue('militaryService', 'marmot');
		if (strlen($militaryService) > 0){
			/** @var SimpleXMLElement $record */
			$militaryRecord = $this->recordDriver->getModsValue('militaryRecord', 'marmot', $militaryService);
			$militaryBranch = $this->recordDriver->getModsValue('militaryBranch', 'marmot', $militaryRecord);
			$militaryConflict = $this->recordDriver->getModsValue('militaryConflict', 'marmot', $militaryRecord);
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
		$address2 = $this->recordDriver->getModsValue('address2', 'marmot');
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
				strlen($address2) ||
				strlen($addressCity) ||
				strlen($addressCounty) ||
				strlen($addressState) ||
				strlen($addressZipCode) ||
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
			if (strlen($address2) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['address2'] = $address2;
			}
			if (strlen($addressCity) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressCity'] = $addressCity;
			}
			if (strlen($addressState) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressState'] = $addressState;
			}
			if (strlen($addressCounty) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressCounty'] = $addressCounty;
			}
			if (strlen($addressZipCode) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressZipCode'] = $addressZipCode;
			}
			if (strlen($addressCountry) > 0) {
				$addressInfo['addressCountry'] = $addressCountry;
			}
			if (strlen($addressOtherRegion) > 0) {
				$addressInfo['addressOtherRegion'] = $addressOtherRegion;
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
		$description = html_entity_decode($this->recordDriver->getDescription());
		$description = str_replace("\r\n", '<br/>', $description);
		$description = str_replace("&#xD;", '<br/>', $description);
		$interface->assign('description', $description);

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