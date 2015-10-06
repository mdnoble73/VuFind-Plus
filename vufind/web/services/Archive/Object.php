<?php
/**
 * A superclass for Digital Archive Objects
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 9/9/2015
 * Time: 4:13 PM
 */

//Include code we need to use Tuque without Drupal
require_once(ROOT_DIR . '/sys/tuque/Cache.php');
require_once(ROOT_DIR . '/sys/tuque/FedoraApi.php');
require_once(ROOT_DIR . '/sys/tuque/FedoraApiSerializer.php');
require_once(ROOT_DIR . '/sys/tuque/Object.php');
require_once(ROOT_DIR . '/sys/tuque/HttpConnection.php');
require_once(ROOT_DIR . '/sys/tuque/Repository.php');
require_once(ROOT_DIR . '/sys/tuque/RepositoryConnection.php');

abstract class Archive_Object extends Action{
	protected $pid;
	protected $archiveObject;
	protected $dcData;
	protected $modsData;
	protected $relsExtData;

	function loadArchiveObjectData(){
		global $configArray;

		//Connect to Fedora via TUQUE
		// These components need to be instantiated to load the object.
		try{
			$serializer = new FedoraApiSerializer();
			$cache = new SimpleCache();
			$fedoraUrl = $configArray['Islandora']['fedoraUrl'];
			$fedoraPassword = $configArray['Islandora']['fedoraPassword'];
			$fedoraUser = $configArray['Islandora']['fedoraUsername'];
			$connection = new RepositoryConnection($fedoraUrl, $fedoraUser, $fedoraPassword);
			$api = new FedoraApi($connection, $serializer);
			$repository = new FedoraRepository($api, $cache);

			// Replace 'object:pid' with the PID of the object to be loaded.
			$this->pid = $_REQUEST['id'];
			$this->archiveObject = $repository->getObject($this->pid);

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

			//TODO: load content from someplace that isn't hardcoded!
			//$title = $object['objectProfile']['objectLabel'];
			global $interface;
			$title = (string)$this->modsData->titleInfo->title;
			$interface->assign('title', $title);
			$interface->setPageTitle($title);
			$description = (string)$this->modsData->abstract;
			$interface->assign('description', $description);


			if ($this->pid == 'mandala:2015'){
				//TODO: This will really be read from Islandora & should be sized appropriately
				$interface->assign('main_image', 'http://islandora.marmot.org/islandora/object/ssb:57/datastream/OBJ/view');
			}else{
				$interface->assign('main_image', "http://islandora.marmot.org/islandora/object/{$this->pid}/datastream/OBJ/view");
			}


			//print_r($object);
		}catch (Exception $e){
			global $logger;
			$logger->log("Error connecting to repository $e", PEAR_LOG_ERR);
		}
	}

	function loadExploreMoreContent(){
		global $interface;
		global $configArray;
		$relatedImages = array();
		$relatedImages[] = array(
			'thumbnail' => 'mandalaoc2_thumb.JPG',
			'image' => 'mandalaoc2.JPG',
			'title' => '',
			'shortTitle' => '',
		);
		$relatedImages[] = array(
			'thumbnail' => 'mandalaoc3_thumb.JPG',
			'image' => 'mandalaoc3.JPG',
			'title' => '',
			'shortTitle' => '',
		);
		$relatedImages[] = array(
			'thumbnail' => 'mandalaoc4_thumb.JPG',
			'image' => 'mandalaoc4.JPG',
			'title' => '',
			'shortTitle' => '',
		);
		$relatedImages[] = array(
			'thumbnail' => 'mandalaoc5_thumb.JPG',
			'image' => 'mandalaoc5.JPG',
			'title' => '',
			'shortTitle' => '',
		);
		$interface->assign('relatedImages', $relatedImages);

		// Additional Demo Variables
		$videoImage = ''; //TODO set
		$interface->assign('videoImage', $videoImage);
		$videoLink = "http://islandora.marmot.org/islandora/object/mandala:2024/datastream/OBJ/view"; //TODO set
		$interface->assign('videoLink', $videoLink);

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