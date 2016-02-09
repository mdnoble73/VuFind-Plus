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
	protected $dcData;
	protected $modsData;
	protected $relsExtData;

	/**
	 * @param string $mainContentTemplate  Name of the SMARTY template file for the main content of the Full Record View Pages
	 * @param string $pageTitle            What to display is the html title tag
	 * @param bool|string $sidebarTemplate      Sets the sidebar template, set to false or empty string for no sidebar
	 */
	function display($mainContentTemplate, $pageTitle=null, $sidebarTemplate='Search/home-sidebar.tpl') {
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
		$fedoraUtils = FedoraUtils::getInstance();

		// Replace 'object:pid' with the PID of the object to be loaded.
		$this->pid = urldecode($_REQUEST['id']);
		$interface->assign('pid', $this->pid);
		$this->archiveObject = $fedoraUtils->getObject($this->pid);

		//Load the dublin core data stream
		$dublinCoreStream = $this->archiveObject->getDatastream('DC');
		$temp = tempnam('/tmp', 'dc');
		$result = $dublinCoreStream->getContent($temp);
		$this->dcData = trim(file_get_contents($temp));
		/* $dublinCoreXML = simplexml_load_string('<?xml version="1.0"?>' . $dublinCoreContent); */
		unlink($temp);

		//Load the MODS data stream
		$modsStream = $this->archiveObject->getDatastream('MODS');
		$temp = tempnam('/tmp', 'mods');
		$result = $modsStream->getContent($temp);
		$modsStreamContent = trim(file_get_contents($temp));
		if (strlen($modsStreamContent) > 0){
			$this->modsData = simplexml_load_string($modsStreamContent);
		}
		unlink($temp);

		//Load the RELS-EXT data stream
		$relsExtStream = $this->archiveObject->getDatastream('RELS-EXT');
		$temp = tempnam('/tmp', 'relext');
		$result = $relsExtStream->getContent($temp);
		$this->relsExtData = trim(file_get_contents($temp));
		/*if (strlen($relsExtContent) > 0){
			$relsExtXML = simplexml_load_string('<?xml version="1.0"?>' . $relsExtContent);
		}*/
		unlink($temp);

		$title = $this->archiveObject->label;
		$interface->assign('title', $title);
		$interface->setPageTitle($title);
		$description = (string)$this->modsData->abstract;
		$interface->assign('description', $description);

		$interface->assign('medium_image', $fedoraUtils->getObjectImageUrl($this->archiveObject, 'medium'));
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