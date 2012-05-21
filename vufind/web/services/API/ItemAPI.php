<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';
require_once 'sys/SolrStats.php';
require_once 'sys/Pager.php';
require_once 'sys/ISBN.php';
require_once 'services/Record/UserComments.php';
require_once 'services/Record/Holdings.php';
require_once 'CatalogConnection.php';

/**
 * API methods related to getting information about specific items. 
 * 
 * Copyright (C) Douglas County Libraries 2011.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * @version 1.0
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Douglas County Libraries 2011.
 */
class ItemAPI extends Action {
	protected $catalog;

	public $id;

	/**
	 * marc record in File_Marc object
	 */
	protected $recordDriver;
	public $marcRecord;

	public $record;

	public $isbn;
	public $upc;

	public $cacheId;

	public $db;

	function launch()
	{
		global $configArray;
		$method = $_REQUEST['method'];
		// Connect to Catalog
		if ($method != 'getBookcoverById' && $method != 'getBookCover'){
			$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);
			//header('Content-type: application/json');
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		}

		if (is_callable(array($this, $method))) {
			$output = json_encode(array('result'=>$this->$method()));
		} else {
			$output = json_encode(array('error'=>"invalid_method '$method'"));
		}

		echo $output;
	}
	
	function addFileToAcsServer(){
		global $configArray;
		require_once('sys/AdobeContentServer.php');
		if (!isset($_REQUEST['filename'])){
			return array('error' => 'Filename parameter was not provided.  Please provide the filename in the library to add to the ACS server.');
		}
		$filename = $_REQUEST['filename'];
		$fullFilename = $configArray['EContent']['library'] . '/' . $filename;
		if (!file_exists($fullFilename)){
			return array('error' => 'Filename does not exist in the library.  Unable to add to the ACS server.');
		}
		$ret = AdobeContentServer::packageFile($fullFilename, '', 1);
		return $ret;
	}

	/**
	 * Get information about a particular item and return it as JSON
	 */
	function getItem(){
		global $timer;
		global $configArray;
		$itemData = array();

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;
			
		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$this->db->debug = true;
		}

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			return array('error', 'Record does not exist');
		}
		$this->record = $record;
		if ($record['recordtype'] == 'econtentRecord'){
			require_once('sys/eContent/EContentRecord.php');
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = substr($record['id'], strlen('econtentRecord'));
			if (!$eContentRecord->find(true)){
				$itemData['error'] = 'Cannot load eContent Record for id ' . $record['id'];
			}else{
				$itemData['isbn'] = $eContentRecord->getIsbn();
				$itemData['upc'] = $eContentRecord->getUpc();
				$itemData['issn'] = '';
				$itemData['title'] = $record['title'];
				$itemData['author'] = $eContentRecord->author;
				$itemData['publisher'] = $eContentRecord->publisher;
				$itemData['allIsbn'] = $eContentRecord->getPropertyArray('isbn');
				$itemData['allUpc'] = $eContentRecord->getPropertyArray('upc');
				$itemData['allIssn'] = $eContentRecord->getPropertyArray('issn');
				$itemData['format'] = $eContentRecord->format();
				$itemData['formatCategory'] = $eContentRecord->format_category();
				$itemData['language'] = $eContentRecord->language;
				$itemData['cover'] = $configArray['Site']['coverUrl'] . "/bookcover.php?id={$itemData['id']}&isbn={$itemData['isbn']}&upc={$itemData['upc']}&category={$itemData['formatCategory']}&format={$itemData['format'][0]}&size=medium";
				$itemData['description'] = $eContentRecord->description;
				
				require_once('sys/eContent/EContentRating.php');
				$eContentRating = new EContentRating();
				$eContentRating->recordId = $eContentRecord->id;
				$itemData['ratingData'] = $eContentRating->getRatingData(false, false);
				
				// Retrieve tags associated with the record
				$limit = 5;
				$resource = new Resource();
				$resource->record_id = $eContentRecord->id;
				$resource->source = 'eContent';
				$tags = array();
				if ($tags = $resource->getTags()) {
					array_slice($tags, 0, $limit);
				}
				$itemData['tagList'] = $tags;
				$timer->logTime('Got tag list');
				
				$itemData['userComments'] = $resource->getComments();
				
				require_once 'Drivers/EContentDriver.php';
				$driver = new EContentDriver();
				$itemData['holdings'] = $driver->getHolding($eContentRecord->id);
			}
		}else{
			
			$this->recordDriver = RecordDriverFactory::initRecordDriver($record);
			$timer->logTime('Initialized the Record Driver');
	
			// Process MARC Data
			require_once 'sys/MarcLoader.php';
			$marcRecord = MarcLoader::loadMarcRecordFromRecord($record);
			if ($marcRecord) {
				$this->marcRecord = $marcRecord;
			} else {
				$itemData['error'] = 'Cannot Process MARC Record';
			}
			$timer->logTime('Processed the marc record');
	
			// Get ISBN for cover and review use
			if ($isbnFields = $this->marcRecord->getFields('020')) {
				//Use the first good ISBN we find.
				foreach ($isbnFields as $isbnField){
					if ($isbnField = $isbnField->getSubfield('a')) {
						$this->isbn = trim($isbnField->getData());
						if ($pos = strpos($this->isbn, ' ')) {
							$this->isbn = substr($this->isbn, 0, $pos);
						}
						if (strlen($this->isbn) < 10){
							$this->isbn = str_pad($this->isbn, 10, "0", STR_PAD_LEFT);
						}
						$itemData['isbn'] = $this->isbn;
						break;
					}
				}
			}
			if ($upcField = $this->marcRecord->getField('024')) {
				if ($upcField = $upcField->getSubfield('a')) {
					$this->upc = trim($upcField->getData());
					$itemData['upc'] = $this->upc;
				}
			}
			if ($issnField = $this->marcRecord->getField('022')) {
				if ($issnField = $issnField->getSubfield('a')) {
					$this->issn = trim($issnField->getData());
					if ($pos = strpos($this->issn, ' ')) {
						$this->issn = substr($this->issn, 0, $pos);
					}
					$itemData['issn'] = $this->issn;
				}
			}
			$timer->logTime('Got UPC, ISBN, and ISSN');
	
			//Generate basic information from the marc file to make display easier.
			$itemData['title'] = $record['title'];
			$itemData['author'] = $record['author'];
			$itemData['publisher'] = $record['publisher'];
			$itemData['allIsbn'] = $record['isbn'];
			$itemData['allUpc'] = $record['upc'];
			$itemData['allIssn'] = $record['issn'];
			$itemData['edition'] = $record['edition'];
			$itemData['callnumber'] = $record['callnumber'];
			$itemData['genre'] = $record['genre'];
			$itemData['series'] = $record['series'];
			$itemData['physical'] = $record['physical'];
			$itemData['lccn'] = $record['lccn'];
			$itemData['contents'] = $record['contents'];
	
			$itemData['format'] = $record['format'];
			$itemData['formatCategory'] = $record['format_category'][0];
			$itemData['language'] = $record['language'];
	
			//Retrieve description from MARC file
			$description = '';
			if ($descriptionField = $this->marcRecord->getField('520')) {
				if ($descriptionField = $descriptionField->getSubfield('a')) {
					$description = trim($descriptionField->getData());
				}
			}
			$itemData['description'] = $description;
	
			// Retrieve tags associated with the record
			$limit = 5;
			$resource = new Resource();
			$resource->record_id = $this->id;
			$resource->source = 'VuFind';
			$tags = array();
			if ($tags = $resource->getTags()) {
				array_slice($tags, 0, $limit);
			}
			$itemData['tagList'] = $tags;
			$timer->logTime('Got tag list');
	
			//setup 5 star ratings
			global $user;
			$ratingData = $resource->getRatingData($user);
			$itemData['ratingData'] = $ratingData;
			$timer->logTime('Got 5 star data');
	
			// Load User comments
			$itemData['userComments'] =  UserComments::getComments($this->id);
			$timer->logTime('Loaded Comments');
	
			//Load Holdings
			$itemData['holdings'] = Holdings::loadHoldings($this->id);
			$timer->logTime('Loaded Holdings');
	
			// Add Marc Record to the output
			if ($this->marcRecord){
				$itemData['marc'] = $this->marcRecord->toJSON();
			}
		}

		return $itemData;
	}

	function getBasicItemInfo(){
		global $timer;
		global $configArray;
		$itemData = array();

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;
			
		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$this->db->debug = true;
		}

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			PEAR::raiseError(new PEAR_Error('Record Does Not Exist'));
		}
		$this->record = $record;
		$this->recordDriver = RecordDriverFactory::initRecordDriver($record);
		$timer->logTime('Initialized the Record Driver');

		// Process MARC Data
		if ($record['recordtype'] == 'econtentRecord'){
			require_once('sys/eContent/EContentRecord.php');
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = substr($record['id'], strlen('econtentRecord'));
			if (!$eContentRecord->find(true)){
				$itemData['error'] = 'Cannot load eContent Record for id ' . $record['id'];
			}else{
				$itemData['isbn'] = $eContentRecord->getIsbn();
				$itemData['upc'] = $eContentRecord->getUpc();
				$itemData['issn'] = '';
				$itemData['title'] = $record['title'];
				$itemData['author'] = $eContentRecord->author;
				$itemData['publisher'] = $eContentRecord->publisher;
				$itemData['allIsbn'] = $eContentRecord->getPropertyArray('isbn');
				$itemData['allUpc'] = $eContentRecord->getPropertyArray('upc');
				$itemData['allIssn'] = $eContentRecord->getPropertyArray('issn');
				$itemData['format'] = $eContentRecord->format();
				$itemData['formatCategory'] = $eContentRecord->format_category();
				$itemData['language'] = $eContentRecord->language;
				$itemData['cover'] = $configArray['Site']['coverUrl'] . "/bookcover.php?id={$itemData['id']}&isbn={$itemData['isbn']}&upc={$itemData['upc']}&category={$itemData['formatCategory']}&format={$itemData['format'][0]}&size=medium";
				$itemData['description'] = $eContentRecord->description;
				
				require_once('sys/eContent/EContentRating.php');
				$eContentRating = new EContentRating();
				$eContentRating->recordId = $eContentRecord->id;
				$itemData['ratingData'] = $eContentRating->getRatingData($user, false);
			}
			 
		}else{
			require_once 'sys/MarcLoader.php';
			$marcRecord = MarcLoader::loadMarcRecordFromRecord($record);
			if ($marcRecord) {
				$this->marcRecord = $marcRecord;
			} else {
				$itemData['error'] = 'Cannot Process MARC Record';
			}
			$timer->logTime('Processed the marc record');
	
			// Get ISBN for cover and review use
			if ($isbnFields = $this->marcRecord->getFields('020')) {
				//Use the first good ISBN we find.
				foreach ($isbnFields as $isbnField){
					if ($isbnField = $isbnField->getSubfield('a')) {
						$this->isbn = trim($isbnField->getData());
						if ($pos = strpos($this->isbn, ' ')) {
							$this->isbn = substr($this->isbn, 0, $pos);
						}
						if (strlen($this->isbn) < 10){
							$this->isbn = str_pad($this->isbn, 10, "0", STR_PAD_LEFT);
						}
						$itemData['isbn'] = $this->isbn;
						break;
					}
				}
			}
			if ($upcField = $this->marcRecord->getField('024')) {
				if ($upcField = $upcField->getSubfield('a')) {
					$this->upc = trim($upcField->getData());
					$itemData['upc'] = $this->upc;
				}
			}
			if ($issnField = $this->marcRecord->getField('022')) {
				if ($issnField = $issnField->getSubfield('a')) {
					$this->issn = trim($issnField->getData());
					if ($pos = strpos($this->issn, ' ')) {
						$this->issn = substr($this->issn, 0, $pos);
					}
					$itemData['issn'] = $this->issn;
				}
			}
			$timer->logTime('Got UPC, ISBN, and ISSN');

			//Generate basic information from the marc file to make display easier.
			$itemData['title'] = $record['title'];
			$itemData['author'] = isset($record['author']) ? $record['author'] : (isset($record['author2']) ? $record['author2'][0] : '');
			$itemData['publisher'] = $record['publisher'];
			$itemData['allIsbn'] = $record['isbn'];
			$itemData['allUpc'] = $record['upc'];
			$itemData['allIssn'] = $record['issn'];
			$itemData['format'] = isset($record['format']) ? $record['format'][0] : '';
			$itemData['formatCategory'] = $record['format_category'][0];
			$itemData['language'] = $record['language'];
			$itemData['cover'] = $configArray['Site']['url'] . "/bookcover.php?id={$itemData['id']}&isbn={$itemData['isbn']}&upc={$itemData['upc']}&category={$itemData['formatCategory']}&format={$itemData['format'][0]}";
			
			//Retrieve description from MARC file
			$description = '';
			if ($descriptionField = $this->marcRecord->getField('520')) {
				if ($descriptionField = $descriptionField->getSubfield('a')) {
					$description = trim($descriptionField->getData());
				}
			}
			$itemData['description'] = $description;
			
			//setup 5 star ratings
			global $user;
			$resource = new Resource();
			$resource->record_id = $this->id;
			$ratingData = $resource->getRatingData($user);
			$itemData['ratingData'] = $ratingData;
			$timer->logTime('Got 5 star data');
		}

		return $itemData;
	}
	
	function getItemAvailability(){
		global $timer;
		global $configArray;
		$itemData = array();

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;
			
		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$this->db->debug = true;
		}

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			PEAR::raiseError(new PEAR_Error('Record Does Not Exist'));
		}
		$this->record = $record;
		if ($record['recordtype'] == 'econtentRecord'){
			require_once('sys/eContent/EContentRecord.php');
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = substr($record['id'], strlen('econtentRecord'));
			if (!$eContentRecord->find(true)){
				$itemData['error'] = 'Cannot load eContent Record for id ' . $record['id'];
			}else{
				require_once 'Drivers/EContentDriver.php';
				$driver = new EContentDriver();
				$itemData['holdings'] = $driver->getHolding($eContentRecord->id);
			}
		}else{
			$this->recordDriver = RecordDriverFactory::initRecordDriver($record);
			$timer->logTime('Initialized the Record Driver');
	
			//Load Holdings
			$itemData['holdings'] = Holdings::loadHoldings($this->id);
			$timer->logTime('Loaded Holdings');
		}

		return $itemData;
	}

	function getBookcoverById(){
		global $timer;
		global $configArray;
		$itemData = array();

		$record = $this->loadSolrRecord($_GET['id']);
		$isbn = isset($record['isbn']) ? ISBN::normalizeISBN($record['isbn'][0]) : null;
		$upc = isset($record['upc']) ? $record['upc'][0] : null;
		$formatCategory = isset($record['format_category']) ? $record['format_category'][0] : null;
		return $this->getBookCover($isbn, $upc, $formatCategory, $id);
	}

	function getBookCover($isbn = null, $upc = null, $formatCategory = null, $size = null, $id = null){
		if (is_null($isbn)) {$isbn = $_GET['isbn'];}
		$_GET['isn'] = ISBN::normalizeISBN($isbn);
		if (is_null($upc)) {$upc = $_GET['upc'];}
		$_GET['upc'] = $upc;
		if (is_null($formatCategory)) {$formatCategory = $_GET['formatCategory'];}
		$_GET['category'] = $formatCategory;
		if (is_null($size)) {$size = isset($_GET['size']) ? $_GET['size'] : 'small';}
		$_GET['size'] = $size;
		if (is_null($id)) {$id = $_GET['id'];}
		$_GET['id'] = $id;
		include_once('bookcover.php');
	}

	function clearBookCoverCacheById(){
		$id = strip_tags($_REQUEST['id']);
		$sizes = array('small', 'medium', 'large');
		$extensions = array('jpg', 'gif', 'png');
		$record = $this->loadSolrRecord($id);
		$filenamesToCheck = array();
		if (preg_match('/econtentrecord/i', $id)){
			$shortId = substr($id, 14);
			$filenamesToCheck[] = 'econtent' . $shortId;
		}
		$filenamesToCheck[] = $id;
		if (isset($record['isbn'])){
			$isbns = $record['isbn'];
			foreach ($isbns as $isbn){
				$filenamesToCheck[] = preg_replace('/[^0-9xX]/', '', $isbn);
			}
		}
		if (isset($record['upc'])){
			$upcs = $record['upc'];
			if (isset($upcs)){
				$filenamesToCheck = array_merge($filenamesToCheck, $upcs);
			}
		}
		$deletedFiles = array();
		global $configArray;
		$coverPath = $configArray['Site']['coverPath'];
		foreach ($filenamesToCheck as $filename){
			foreach ($extensions as $extension){
				foreach ($sizes as $size){
					$tmpFilename = "$coverPath/$size/$filename.$extension";
					if (file_exists($tmpFilename)){
						$deletedFiles[] = $tmpFilename;
						unlink($tmpFilename);
					}
				}
			}
		}
		
		return array('deletedFiles' => $deletedFiles);
	}

	function getEnrichmentData(){
		require_once 'services/Record/Enrichment.php';
		$record = $this->loadSolrRecord($_GET['id']);
		$isbn = isset($record['isbn']) ? ISBN::normalizeISBN($record['isbn'][0]) : null;
		//Need to trim the isbn to make sure there isn't descriptive text.
		$upc = isset($record['upc']) ? $record['upc'][0] : null;
		$enrichmentData = Enrichment::loadEnrichment($isbn);

		//Load go deeper options
		require_once('Drivers/marmot_inc/GoDeeperData.php');
		$goDeeperOptions = GoDeeperData::getGoDeeperOptions($isbn, $upc, false);

		return array('enrichment' => $enrichmentData, 'goDeeper' => $goDeeperOptions);
	}

	function getGoDeeperData(){
		require_once 'services/Record/Enrichment.php';
		$record = $this->loadSolrRecord($_GET['id']);
		$type = $_GET['type'];
		$isbn = isset($record['isbn']) ? ISBN::normalizeISBN($record['isbn'][0]) : null;
			
		//Load go deeper data
		require_once('Drivers/marmot_inc/GoDeeperData.php');
		$goDeeperOptions = GoDeeperData::getHtmlData($type, $isbn, $upc);

		return $goDeeperOptions;
	}

	private function loadSolrRecord($id){
		global $configArray;
		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;
			
		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$this->db->debug = true;
		}

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			PEAR::raiseError(new PEAR_Error('Record Does Not Exist'));
		}
		return $record;
	}

	/**
	 * Gets a list of all online items with expired holds so they can be returned.
	 */
	function getExpiredOnlineItemsWithHolds(){
		$expiredItemsWithOnlineHolds = $this->catalog->getExpiredOnlineItemsWithHolds();
		return $expiredItemsWithOnlineHolds;
	}
	
	function getOverdueOnlineItems(){
		$overdueOnlineItems = $this->catalog->getOverdueOnlineItems();
		return $overdueOnlineItems;
	}
	
	function getOnlineItemsToReturn(){
		$expiredItemsWithOnlineHolds = $this->catalog->getExpiredOnlineItemsWithHolds();
		$overdueOnlineItems = $this->catalog->getOverdueOnlineItems();
		return $expiredItemsWithOnlineHolds + $overdueOnlineItems;
	}

	/**
	 * Returns an item in the catalog.
	 */
	function checkInItem(){
		if (isset($_REQUEST['barcode'])){
			$barcode = $_REQUEST['barcode'];
			$result = $this->catalog->checkInItem($barcode);
			return $result;
		}else{
			return array('success' => false, 'message' => 'You must provide a barcode to check in.');
		}
	}
}