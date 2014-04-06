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
		if (in_array($method, array('RateTitle', 'GetSeriesTitles', 'GetComments', 'DeleteItem', 'SaveComment', 'CheckoutOverDriveItem', 'PlaceOverDriveHold', 'CancelOverDriveHold', 'GetOverDriveHoldPrompts', 'ReturnOverDriveItem', 'SelectOverDriveDownloadFormat', 'GetDownloadLink'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if (in_array($method, array('GetGoDeeperData', 'AddItem', 'EditItem', 'GetOverDriveLoanPeriod', 'getPurchaseOptions', 'getDescription', 'SelectOverDriveFormat'))){
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

	function GetEnrichmentInfo(){
		require_once ROOT_DIR . '/services/EcontentRecord/Enrichment.php';
		global $configArray;
		global $library;
		$isbn = $_REQUEST['isbn'];
		$upc = $_REQUEST['upc'];
		$id = $_REQUEST['id'];
		$enrichmentData = EcontentRecord_Enrichment::loadEnrichment($isbn);
		global $interface;
		$interface->assign('id', $id);
		$interface->assign('enrichment', $enrichmentData);
		$interface->assign('enrichment', $enrichmentData);
		$showSimilarTitles = false;
		if (isset($enrichmentData['novelist']) && isset($enrichmentData['novelist']['similarTitles']) && is_array($enrichmentData['novelist']['similarTitles']) && count($enrichmentData['novelist']['similarTitles']) > 0){
			foreach ($enrichmentData['novelist']['similarTitles'] as $title){
				if ($title['recordId'] != -1){
					$showSimilarTitles = true;
					break;
				}
			}
		}
		if (isset($library) && $library->showSimilarTitles == 0){
			$interface->assign('showSimilarTitles', false);
		}else{
			$interface->assign('showSimilarTitles', $showSimilarTitles);
		}
		if (isset($library) && $library->showSimilarAuthors == 0){
			$interface->assign('showSimilarAuthors', false);
		}else{
			$interface->assign('showSimilarAuthors', true);
		}

		//Process series data
		$titles = array();
		if (!isset($enrichmentData['novelist']['series']) || count($enrichmentData['novelist']['series']) == 0){
			$interface->assign('seriesInfo', json_encode(array('titles'=>$titles, 'currentIndex'=>0)));
		}else{

			foreach ($enrichmentData['novelist']['series'] as $record){
				$isbn = $record['isbn'];
				if (strpos($isbn, ' ') > 0){
					$isbn = substr($isbn, 0, strpos($isbn, ' '));
				}
				$cover = $configArray['Site']['coverUrl'] . "/bookcover.php?size=medium&isn=" . $isbn;
				if (isset($record['id'])){
					$cover .= "&id=" . $record['id'];
				}
				if (isset($record['upc'])){
					$cover .= "&upc=" . $record['upc'];
				}
				if (isset($record['format_category'])){
					$cover .= "&category=" . $record['format_category'][0];
				}
				$titles[] = array(
							'id' => isset($record['id']) ? $record['id'] : '',
							'image' => $cover,
							'title' => $record['title'],
							'author' => $record['author']
				);
			}

			foreach ($titles as $key => $rawData){
				if ($rawData['id']){
					if (strpos($rawData['id'], 'econtentRecord') === 0){
						$shortId = str_replace('econtentRecord', '', $rawData['id']);
						$formattedTitle = "<div id=\"scrollerTitleSeries{$key}\" class=\"scrollerTitle\">" .
								'<a href="' . $configArray['Site']['path'] . "/EcontentRecord/" . $shortId . '" id="descriptionTrigger' . $shortId . '">' .
								"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
								"</a></div>" .
								"<div id='descriptionPlaceholder{$shortId}' style='display:none'></div>";
					}else{
						$shortId = str_replace('.', '', $rawData['id']);
						$formattedTitle = "<div id=\"scrollerTitleSeries{$key}\" class=\"scrollerTitle\">" .
							'<a href="' . $configArray['Site']['path'] . "/Record/" . $rawData['id'] . '" id="descriptionTrigger' . $shortId . '">' .
							"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
							"</a></div>" .
							"<div id='descriptionPlaceholder{$shortId}' style='display:none'></div>";
					}
				}else{
					$formattedTitle = "<div id=\"scrollerTitleSeries{$key}\" class=\"scrollerTitle\">" .
						"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
						"</div>";
				}
				$rawData['formattedTitle'] = $formattedTitle;
				$titles[$key] = $rawData;
			}
			$seriesInfo = array('titles' => $titles, 'currentIndex' => $enrichmentData['novelist']['seriesDefaultIndex']);
			$interface->assign('seriesInfo', json_encode($seriesInfo));
		}

		//Process similar titles for widget
		$titles = array();
		if (!isset($enrichmentData['novelist']['similarTitles']) || count($enrichmentData['novelist']['similarTitles']) == 0){
			$interface->assign('similarTitleInfo', json_encode(array('titles'=>$titles, 'currentIndex'=>0)));
		}else{
			foreach ($enrichmentData['novelist']['similarTitles'] as $record){
				$isbn = $record['isbn'];
				if (strpos($isbn, ' ') > 0){
					$isbn = substr($isbn, 0, strpos($isbn, ' '));
				}
				$cover = $configArray['Site']['coverUrl'] . "/bookcover.php?size=medium&isn=" . $isbn;
				if (isset($record['id'])){
					$cover .= "&id=" . $record['id'];
				}
				if (isset($record['upc'])){
					$cover .= "&upc=" . $record['upc'];
				}
				if (isset($record['issn'])){
					$cover .= "&issn=" . $record['issn'];
				}
				if (isset($record['format_category'])){
					$cover .= "&category=" . $record['format_category'][0];
				}
				$title = $record['title'];
				if (isset($record['series'])){
					$title .= ' (' . $record['series'] ;
					if (isset($record['volume'])){
						$title .= ' Volume ' . $record['volume'];
					}
					$title .= ')';
				}
				$titles[] = array(
					'id' => isset($record['id']) ? $record['id'] : '',
					'image' => $cover,
					'title' => $title,
					'author' => $record['author']
				);
			}

			foreach ($titles as $key => $rawData){
				if ($rawData['id']){
					if (strpos($rawData['id'], 'econtentRecord') === 0){
						$fullId = $rawData['id'];
						$shortId = str_replace('econtentRecord', '', $rawData['id']);
						$formattedTitle = "<div id=\"scrollerTitleSimilar{$key}\" class=\"scrollerTitle\">" .
								'<a href="' . $configArray['Site']['path'] . "/EcontentRecord/" . $shortId . '" id="descriptionTrigger' . $fullId . '">' .
								"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
								"</a></div>" .
								"<div id='descriptionPlaceholder{$fullId}' style='display:none'></div>";
					}else{
						$shortId = str_replace('.', '', $rawData['id']);
						$formattedTitle = "<div id=\"scrollerTitleSimilar{$key}\" class=\"scrollerTitle\">" .
								'<a href="' . $configArray['Site']['path'] . "/Record/" . $rawData['id'] . '" id="descriptionTrigger' . $shortId . '">' .
								"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
								"</a></div>" .
								"<div id='descriptionPlaceholder{$shortId}' style='display:none'></div>";
					}
				}else{
					$formattedTitle = "<div id=\"scrollerTitleSimilar{$key}\" class=\"scrollerTitle\">" .
							"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
							"</div>";
				}
				$rawData['formattedTitle'] = $formattedTitle;
				$titles[$key] = $rawData;
			}
			$seriesInfo = array('titles' => $titles, 'currentIndex' => 0);
			$interface->assign('similarTitleInfo', json_encode($seriesInfo));
		}

		//Load go deeper options
		if (isset($library) && $library->showGoDeeper == 0){
			$interface->assign('showGoDeeper', false);
		}else{
			require_once(ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php');
			$goDeeperOptions = GoDeeperData::getGoDeeperOptions($isbn, $upc);
			if (count($goDeeperOptions['options']) == 0){
				$interface->assign('showGoDeeper', false);
			}else{
				$interface->assign('showGoDeeper', true);
			}
		}

		return $interface->fetch('Record/ajax-enrichment.tpl');
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
		$interface->assign('showOtherEditionsPopup', $configArray['Content']['showOtherEditionsPopup']);
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
		if ($eContentRecord->getIsbn() == null || strlen($eContentRecord->getIsbn()) == 0){
			$interface->assign('showOtherEditionsPopup', false);
		}
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

		global $library;
		if (isset($library)){
			$interface->assign('showProspectorTitlesAsTab', $library->showProspectorTitlesAsTab);
		}else{
			$interface->assign('showProspectorTitlesAsTab', 1);
		}

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

	function PlaceOverDriveHold(){
		global $user;
		$overDriveId = $_REQUEST['overDriveId'];
		$format = $_REQUEST['formatId'];
		$overdriveEmail = isset($_REQUEST['overdriveEmail']) ? $_REQUEST['overdriveEmail'] : $user->overdriveEmail;
		if (isset($_REQUEST['overdriveEmail'])){
			if ($_REQUEST['overdriveEmail'] != $user->overdriveEmail){
				$user->overdriveEmail = $overdriveEmail;
				$user->update();
				//Update the serialized instance stored in the session
				$_SESSION['userinfo'] = serialize($user);
			}
		}
		if (isset($_REQUEST['promptForOverdriveEmail'])){
			$user->promptForOverdriveEmail = $_REQUEST['promptForOverdriveEmail'];
			$user->update();
			//Update the serialized instance stored in the session
			$_SESSION['userinfo'] = serialize($user);
		}
		if ($user && !PEAR_Singleton::isError($user)){
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$driver = OverDriveDriverFactory::getDriver();
			$holdMessage = $driver->placeOverDriveHold($overDriveId, $format, $user);
			return json_encode($holdMessage);
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to place a hold.'));
		}
	}

	function CheckoutOverDriveItem(){
		global $user;
		$overDriveId = $_REQUEST['overDriveId'];
		$format = isset($_REQUEST['formatId']) ? $_REQUEST['formatId'] : null;
		$lendingPeriod = isset($_REQUEST['lendingPeriod']) ? $_REQUEST['lendingPeriod'] : null;
		//global $logger;
		//$logger->log("Lending period = $lendingPeriod", PEAR_LOG_INFO);
		if ($user && !PEAR_Singleton::isError($user)){
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$driver = OverDriveDriverFactory::getDriver();
			$result = $driver->checkoutOverDriveItem($overDriveId, $format, $lendingPeriod, $user);
			//$logger->log("Checkout result = $result", PEAR_LOG_INFO);
			return json_encode($result);
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to checkout an item.'));
		}
	}

	function ReturnOverDriveItem(){
		global $user;
		$overDriveId = $_REQUEST['overDriveId'];
		$transactionId = $_REQUEST['transactionId'];
		if ($user && !PEAR_Singleton::isError($user)){
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$driver = OverDriveDriverFactory::getDriver();
			$result = $driver->returnOverDriveItem($overDriveId, $transactionId, $user);
			//$logger->log("Checkout result = $result", PEAR_LOG_INFO);
			return json_encode($result);
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to return an item.'));
		}
	}

	function SelectOverDriveDownloadFormat(){
		global $user;
		$overDriveId = $_REQUEST['overDriveId'];
		$formatId = $_REQUEST['formatId'];
		if ($user && !PEAR_Singleton::isError($user)){
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$driver = OverDriveDriverFactory::getDriver();
			$result = $driver->selectOverDriveDownloadFormat($overDriveId, $formatId, $user);
			//$logger->log("Checkout result = $result", PEAR_LOG_INFO);
			return json_encode($result);
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to download a title.'));
		}
	}

	function GetDownloadLink(){
		global $user;
		$overDriveId = $_REQUEST['overDriveId'];
		$formatId = $_REQUEST['formatId'];
		if ($user && !PEAR_Singleton::isError($user)){
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$driver = OverDriveDriverFactory::getDriver();
			$result = $driver->getDownloadLink($overDriveId, $formatId, $user);
			//$logger->log("Checkout result = $result", PEAR_LOG_INFO);
			return json_encode($result);
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to download a title.'));
		}
	}

	/**
	 * Return a form where the user can select the loan period when checking out a title
	 */
	function GetOverDriveLoanPeriod(){
		global $interface;
		global $configArray;
		$overDriveId = $_REQUEST['overDriveId'];
		$formatId = $_REQUEST['formatId'];
		$interface->assign('overDriveId', $overDriveId);
		$interface->assign('formatId', $formatId);
		require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
		$overDriveDriver = OverDriveDriverFactory::getDriver();
		$loanPeriods = $overDriveDriver->getLoanPeriodsForFormat($formatId);
		$interface->assign('loanPeriods', $loanPeriods);

		return $interface->fetch('EcontentRecord/ajax-loan-period.tpl');
	}

	function GetOverDriveHoldPrompts(){
		global $configArray;
		global $user;
		global $interface;
		$formatId = isset($_REQUEST['formatId']) ? $_REQUEST['formatId'] : null;
		$interface->assign('formatId', $formatId);
		$overDriveId = $_REQUEST['overDriveId'];
		$interface->assign('overDriveId', $overDriveId);
		if ($user->overdriveEmail == 'undefined'){
			$user->overdriveEmail = '';
		}
		$promptForEmail = false;
		if (strlen($user->overdriveEmail) == 0 || $user->promptForOverdriveEmail == 1){
			$promptForEmail = true;
		}

		$interface->assign('overdriveEmail', $user->overdriveEmail);
		$interface->assign('promptForEmail', $promptForEmail);
		$promptForFormat = false;
		if (!isset($configArray['OverDrive']) || $configArray['OverDrive']['interfaceVersion'] < 2){
			if (strlen($formatId) == 0){
				$promptForFormat = true;
			}
		}
		if ($promptForFormat){
			$eContentRecord = new EContentRecord();
			$eContentRecord->externalId = $overDriveId;
			if ($eContentRecord->find(true)){
				$items = $eContentRecord->getItems();
				$interface->assign('items', $items);
			}
		}
		$interface->assign('promptForFormat', $promptForFormat);
		if ($promptForFormat || $promptForEmail){
			if ($promptForFormat && $promptForEmail){
				$promptTitle = 'Additional information needed';
			}elseif($promptForFormat){
				$promptTitle = 'Select a format';
			}else{
				$promptTitle = 'Enter an e-mail';
			}
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('EcontentRecord/ajax-overdrive-hold-prompt.tpl'),
				)
			);
		}else{
			return json_encode(
				array(
					'promptNeeded' => false,
					'overdriveEmail' => $user->overdriveEmail,
					'promptForOverdriveEmail' => $promptForEmail,
				)
			);
		}

	}

	function SelectOverDriveFormat(){
		global $interface;
		global $configArray;
		$overDriveId = $_REQUEST['overDriveId'];
		$nextAction = $_REQUEST['nextAction'];
		$interface->assign('overDriveId', $overDriveId);
		$interface->assign('nextAction', $nextAction);
		$eContentRecord = new EContentRecord();
		$eContentRecord->externalId = $overDriveId;
		if ($eContentRecord->find(true)){
			$items = $eContentRecord->getItems();
			$interface->assign('items', $items);
		}

		return $interface->fetch('EcontentRecord/ajax-select-format.tpl');
	}

	function RemoveOverDriveRecordFromWishList(){
		global $user;
		$overDriveId = $_REQUEST['overDriveId'];
		if ($user && !PEAR_Singleton::isError($user)){
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$driver = OverDriveDriverFactory::getDriver();
			$result = $driver->removeOverDriveItemFromWishlist($overDriveId, $user);
			return json_encode($result);
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to add an item to your wish list.'));
		}
	}

	function CancelOverDriveHold(){
		global $user;
		$overDriveId = $_REQUEST['overDriveId'];
		if (isset($_REQUEST['formatId'])){
			$format = $_REQUEST['formatId'];
		}else{
			$format = null;
		}
		if ($user && !PEAR_Singleton::isError($user)){
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$driver = OverDriveDriverFactory::getDriver();
			$result = $driver->cancelOverDriveHold($overDriveId, $format, $user);
			return json_encode($result);
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to cancel holds.'));
		}
	}

	function getPurchaseOptions(){
		global $interface;
		if (isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
			$interface->assign('id', $id);
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $id;
			if ($eContentRecord->find(true)){
				$purchaseLinks = array();
				if ($eContentRecord->purchaseUrl != null){
					$purchaseLinks[] = array(
						'link' => $eContentRecord->purchaseUrl,
						'linkText' => 'Buy from ' . $eContentRecord->publisher,
						'storeName' => $eContentRecord->publisher,
						'field856Index' => 1,
					);
				}

				if (count($purchaseLinks) > 0){
					$interface->assign('purchaseLinks', $purchaseLinks);
				}else{
					$title = $eContentRecord->title;
					$author = $eContentRecord->author;
					require_once ROOT_DIR . '/services/Record/Purchase.php';
					$purchaseLinks = Record_Purchase::getStoresForTitle($title, $author);

					if (count($purchaseLinks) > 0){
						$interface->assign('purchaseLinks', $purchaseLinks);
					}else{
						$interface->assign('errors', array("Sorry we couldn't find any stores that offer this title."));
					}
				}
			}else{
				$errors = array("Could not load record for that id.");
				$interface->assign('errors', $errors);
			}
		}else{
			$errors = array("You must provide the id of the title to be purchased. ");
			$interface->assign('errors', $errors);
		}

		echo $interface->fetch('EcontentRecord/ajax-purchase-options.tpl');
	}
}