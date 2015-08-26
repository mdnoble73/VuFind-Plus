<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Proxy_Request.php';
require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';

global $configArray;

class EContentRecord_AJAX extends Action {

	function AJAX() {
	}

	function launch() {
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];
		if (in_array($method, array('RateTitle', 'GetSeriesTitles', 'GetComments', 'DeleteItem', 'SaveComment', 'GetDownloadLink'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if (in_array($method, array('GetGoDeeperData', 'AddItem', 'EditItem', 'getDescription'))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if ($method == 'downloadMarc'){
			echo $this->$method();
		}else{
			header ('Content-type: text/xml');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

			$xmlResponse = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
			$xmlResponse .= "<AJAXResponse>\n";
			if (is_callable(array($this, $_GET['method']))) {
				$xmlResponse .= $this->$_GET['method']();
			} else {
				$xmlResponse .= '<Error>Invalid Method</Error>';
			}
			$xmlResponse .= '</AJAXResponse>';

			echo $xmlResponse;
		}
	}
	function downloadMarc(){
		$id = $_REQUEST['id'];
		$econtentRecord = new EContentRecord();
		$econtentRecord->id = $id;
		$econtentRecord->find(true);
		$marcData = MarcLoader::loadEContentMarcRecord($econtentRecord);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header("Content-Disposition: attachment; filename={$econtentRecord->ilsId}.mrc");
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');

		header('Content-Length: ' . strlen($marcData->toRaw()));
		ob_clean();
		flush();
		echo($marcData->toRaw());
	}
	function GetGoDeeperData(){
		require_once(ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php');
		$dataType = $_REQUEST['dataType'];
		$upc = $_REQUEST['upc'];
		$isbn = $_REQUEST['isbn'];

		$formattedData = GoDeeperData::getHtmlData($dataType, 'eContentRecord', $isbn, $upc);
		return $formattedData;
	}

	function GetSeriesTitles(){
		//Get other titles within a series for display within the title scroller
		require_once './Enrichment.php';
		$isbn = $_REQUEST['isbn'];
		$id = $_REQUEST['id'];
		$enrichmentData = EcontentRecord_Enrichment::loadEnrichment($isbn);
		global $interface;
		$interface->assign('id', $id);
		$interface->assign('enrichment', $enrichmentData);
	}

	function GetHoldingsInfo(){
		global $interface;
		global $configArray;
		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);
		//Load holdings information from the driver
		require_once (ROOT_DIR . '/Drivers/EContentDriver.php');
		require_once (ROOT_DIR . '/sys/eContent/EContentRecord.php');
		$driver = new EContentDriver();
		//Get any items that are stored for the record
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $id;
		$eContentRecord->find(true);

		/** @var EContentItem[] $holdings */
		$holdings = $driver->getHolding($id);
		$showEContentNotes = false;
		foreach ($holdings as $holding){
			if (strlen($holding->notes) > 0){
				$showEContentNotes = true;
			}
		}
		$interface->assign('record', $eContentRecord);
		$availability = $driver->getScopedAvailability($eContentRecord);
		$interface->assign('availability', $availability['mine']);
		$interface->assign('availabilityOther', $availability['other']);
		$showAvailability = true;
		$showAvailabilityOther = true;
		if ($eContentRecord->accessType == 'external' && strcasecmp($eContentRecord->source, 'OverDrive') != 0){
			$showAvailability = false;
			$showAvailabilityOther = false;
		}
		$interface->assign('showAvailability', $showAvailability);
		$interface->assign('showAvailabilityOther', $showAvailabilityOther);
		$interface->assign('source', $eContentRecord->source);
		$interface->assign('accessType', $eContentRecord->accessType);
		$interface->assign('showEContentNotes', $showEContentNotes);
		$showOverDriveConsole = false;
		$showAdobeDigitalEditions = false;
		foreach ($holdings as $item){
			if (strcasecmp($item->getSource(), 'overdrive') == 0){
				if (in_array($item->externalFormatId, array('ebook-epub-adobe', 'ebook-pdf-adobe'))){
					$showAdobeDigitalEditions = true;
				}else if (in_array($item->externalFormatId, array('video-wmv', 'music-wma', 'music-wma', 'audiobook-wma', 'audiobook-mp3'))){
					$showOverDriveConsole = true;
				}
			}else{
				if (in_array($item->item_type, array('epub', 'pdf'))){
					$showAdobeDigitalEditions = true;
				}
			}
		}
		$interface->assign('showOverDriveConsole', $showOverDriveConsole);
		$interface->assign('showAdobeDigitalEditions', $showAdobeDigitalEditions);

		$interface->assign('holdings', $holdings);
		//Load status summary
		$result = $driver->getStatusSummary($id, $holdings);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}
		$interface->assign('holdingsSummary', $result);
		return $interface->fetch('EcontentRecord/ajax-holdings.tpl');
	}

	function GetProspectorInfo(){
		require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
		global $configArray;
		global $interface;
		$id = 'econtentRecord' . $_REQUEST['id'];
		$interface->assign('id', $id);

		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		/** @var SearchObject_Solr $db */
		$db = new $class($url);

		// Retrieve Full record from Solr
		if (!($record = $db->getRecord($id))) {
			PEAR_Singleton::raiseError(new PEAR_Error('Record Does Not Exist'));
		}

		$prospector = new Prospector();
		//Check to see if the record exists within Prospector so we can get the prospector Id
		$prospectorDetails = $prospector->getProspectorDetailsForLocalRecord($record);
		$interface->assign('prospectorDetails', $prospectorDetails);

		$searchTerms = array(
			array(
				'lookfor' => $record['title'],
				'type' => 'title',
			),
		);
		if (isset($record['author'])){
			$searchTerms[] = array(
				'lookfor' => $record['author'],
				'type' => 'author',
			);
		}
		$prospectorResults = $prospector->getTopSearchResults($searchTerms, 10, $prospectorDetails);
		$interface->assign('prospectorResults', $prospectorResults);
		return $interface->fetch('Record/ajax-prospector.tpl');
	}

	function getDescription(){
		global $interface;
		require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';
		$eContentRecord = new EContentRecord();

		$id = $_REQUEST['id'];
		$eContentRecord->id = $id;
		$eContentRecord->find(true);

		require_once 'Description.php';
		$descriptionInfo = EcontentRecord_Description::loadDescription($eContentRecord);

		$interface->assign('description', $descriptionInfo['description']);
		$interface->assign('length', $eContentRecord->physicalDescription);
		$interface->assign('publisher', $eContentRecord->publisher);

		return $interface->fetch('Record/ajax-description-popup.tpl');
	}
	function AddItem(){
		require_once ROOT_DIR . '/sys/eContent/EContentItem.php';
		require_once ROOT_DIR . '/sys/DataObjectUtil.php';
		global $interface;
		global $configArray;
		$structure = EContentItem::getObjectStructure();
		$object = new EContentItem();
		$recordId = strip_tags($_REQUEST['id']);
		$object->recordId = $recordId;
		$interface->assign('object', $object);
		$interface->assign('title', 'Add a new eContent Item');
		$interface->assign('submitUrl', $configArray['Site']['path'] . "/EcontentRecord/SaveItem");
		$interface->assign('editForm', DataObjectUtil::getEditForm($structure));
		return $interface->fetch('EcontentRecord/ajax-editItem.tpl');
	}
	function EditItem(){
		require_once ROOT_DIR . '/sys/eContent/EContentItem.php';
		require_once ROOT_DIR . '/sys/DataObjectUtil.php';
		global $interface;
		global $configArray;
		$structure = EContentItem::getObjectStructure();
		$object = new EContentItem();
		$itemId = strip_tags($_REQUEST['itemId']);
		$object->id = $itemId;
		if ($object->find(true)){
			$interface->assign('object', $object);
			$interface->assign('title', 'Edit eContent Item');
			$interface->assign('submitUrl', $configArray['Site']['path'] . "/EcontentRecord/SaveItem");
			$interface->assign('editForm', DataObjectUtil::getEditForm($structure));
			return $interface->fetch('EcontentRecord/ajax-editItem.tpl');
		}else{
			return "Could not find a record for item $itemId";
		}
	}
	function DeleteItem(){
		global $user;
		require_once ROOT_DIR . '/sys/eContent/EContentItem.php';
		if ($user->hasRole('epubAdmin')){
			$itemId = strip_tags($_REQUEST['itemId']);
			$econtentItem = new EContentItem();
			$econtentItem->id = $itemId;
			if ($econtentItem->find(true)){
				$ret = $econtentItem->delete();
				if ($ret){
					$return = array('result' => true, 'message' => 'The item was deleted.');
				}else{
					$return = array('result' => false, 'message' => 'The item could not be deleted from the database and index.');
				}
			}else{
				$return = array('result' => false, 'message' => 'The specified item does not exist.');
			}
		}else{
			$return = array('result' => false, 'message' => 'You do not have permissions to delete this item.');
		}
		return json_encode($return);
	}
}