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
	//protected $relsExtData;

	/**
	 * @param string $mainContentTemplate  Name of the SMARTY template file for the main content of the Full Record View Pages
	 * @param string $pageTitle            What to display is the html title tag
	 * @param bool|string $sidebarTemplate      Sets the sidebar template, set to false or empty string for no sidebar
	 */
	function display($mainContentTemplate, $pageTitle=null, $sidebarTemplate='explore-more-sidebar.tpl') {
		global $interface;
		if (!empty($sidebarTemplate)) $interface->assign('sidebar', $sidebarTemplate);
		$interface->setTemplate($mainContentTemplate);
		$interface->setPageTitle($pageTitle == null ? $this->archiveObject->label : $pageTitle);
		$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
		$interface->display('layout.tpl');
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
		$modsStream = $this->archiveObject->getDatastream('MODS');
		$temp = tempnam('/tmp', 'mods');
		$modsStream->getContent($temp);
		$modsStreamContent = trim(file_get_contents($temp));
		if (strlen($modsStreamContent) > 0){
			$this->modsData = simplexml_load_string($modsStreamContent);
		}
		unlink($temp);
		$interface->assign('mods', $this->modsData);

		//Extract Subjects
		$formattedSubjects = array();
		foreach ($this->modsData->subject as $subjects){
			$subject = '';
			$subjectLink = $configArray['Site']['path'] . '/Archive/Results?lookfor=';
			foreach ($subjects->topic as $subjectPart){
				if (strlen($subject) > 0){
					$subject .= ' -- ';
				}
				$subject .= $subjectPart;
				$subjectLink .= '&filter[]=mods_subject_topic_ms:"' . $subjectPart . '"';
			}

			$formattedSubjects[] = array(
					'link' => $subjectLink,
					'label' => $subject
			);
		}
		$interface->assign('subjects', $formattedSubjects);

		$rightsStatements = array();
		foreach ($this->modsData->accessCondition as $condition){
			$marmotData = $condition->children('http://marmot.org/local_mods_extension');
			if (strlen($marmotData->rightsStatement)){
				$rightsStatements[] = (string)$marmotData->rightsStatement;
			}
		}
		$interface->assign('rightsStatements', $rightsStatements);

		$marmotExtension = $this->modsData->extension->children('http://marmot.org/local_mods_extension');
		$interface->assign('marmotExtension', $marmotExtension);

		$relatedPeople = array();
		$relatedPlaces = array();
		$relatedEvents = array();
		/** @var SimpleXMLElement $entity */
		foreach ($marmotExtension->relatedEntity as $entity){
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
				$relatedPeople[] = $entityInfo;
			}elseif ($entityType == 'place'){
				$relatedPlaces[] = $entityInfo;
			}elseif ($entityType == 'event'){
				$relatedEvents[] = $entityInfo;
			}
		}
		$interface->assign('relatedPeople', $relatedPeople);
		$interface->assign('relatedPlaces', $relatedPlaces);
		$interface->assign('relatedEvents', $relatedEvents);

		//Load the RELS-EXT data stream
		/*$relsExtStream = $this->archiveObject->getDatastream('RELS-EXT');
		$temp = tempnam('/tmp', 'relext');
		$result = $relsExtStream->getContent($temp);
		$this->relsExtData = trim(file_get_contents($temp));
		unlink($temp);*/

		$title = $this->archiveObject->label;
		$interface->assign('title', $title);
		$interface->setPageTitle($title);
		$description = (string)$this->modsData->abstract;
		$interface->assign('description', $description);

		$interface->assign('medium_image', $fedoraUtils->getObjectImageUrl($this->archiveObject, 'medium'));

		$repositoryLink = $configArray['Islandora']['repositoryUrl'] . '/islandora/object/' . $this->pid;
		$interface->assign('repositoryLink', $repositoryLink);
	}

	function loadExploreMoreContent(){
		global $interface;
		global $configArray;

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
		$exploreMoreCatalogUrl = $configArray['Site']['path'] . '/Search/AJAX?method=GetListTitles&id=search:22622';
		$interface->assign('exploreMoreCatalogUrl', $exploreMoreCatalogUrl);
	}
}