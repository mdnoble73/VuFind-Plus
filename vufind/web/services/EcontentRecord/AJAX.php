<?php
require_once 'Action.php';
require_once 'sys/Proxy_Request.php';
require_once 'sys/eContent/EContentRecord.php';

global $configArray;

class AJAX extends Action {

	function AJAX() {
	}

	function launch() {
		$method = $_GET['method'];
		if (in_array($method, array('RateTitle', 'GetSeriesTitles', 'GetComments', 'DeleteItem', 'SaveComment', 'CheckoutOverDriveItem', 'PlaceOverDriveHold', 'AddOverDriveRecordToWishList', 'RemoveOverDriveRecordFromWishList', 'CancelOverDriveHold'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if (in_array($method, array('GetGoDeeperData', 'AddItem', 'EditItem', 'GetOverDriveLoanPeriod', 'getPurchaseOptions'))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
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

	function GetGoDeeperData(){
		require_once('Drivers/marmot_inc/GoDeeperData.php');
		$id = $_REQUEST['id'];
		$dataType = $_REQUEST['dataType'];
		$upc = $_REQUEST['upc'];
		$isbn = $_REQUEST['isbn'];

		$formattedData = GoDeeperData::getHtmlData($dataType, 'eContentRecord', $isbn, $upc);
		return $formattedData;
	}

	function GetEnrichmentInfo(){
		require_once 'Enrichment.php';
		global $configArray;
		global $library;
		$isbn = $_REQUEST['isbn'];
		$upc = $_REQUEST['upc'];
		$id = $_REQUEST['id'];
		$enrichmentData = Enrichment::loadEnrichment($isbn);
		global $interface;
		$interface->assign('id', $id);
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
				$formattedTitle = "<div id=\"scrollerTitleSeries{$key}\" class=\"scrollerTitle\">" .
	    			'<a href="' . $configArray['Site']['path'] . "/Record/" . $rawData['id'] . '" id="descriptionTrigger' . $rawData['id'] . '">' .
	    			"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
	    			"</a></div>" .
	    			"<div id='descriptionPlaceholder{$rawData['id']}' style='display:none'></div>";
				$rawData['formattedTitle'] = $formattedTitle;
				$titles[$key] = $rawData;
			}
			$seriesInfo = array('titles' => $titles, 'currentIndex' => $enrichmentData['novelist']['seriesDefaultIndex']);
			$interface->assign('seriesInfo', json_encode($seriesInfo));
		}

		//Load go deeper options
		if (isset($library) && $library->showGoDeeper == 0){
			$interface->assign('showGoDeeper', false);
		}else{
			require_once('Drivers/marmot_inc/GoDeeperData.php');
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
		require_once 'Enrichment.php';
		$isbn = $_REQUEST['isbn'];
		$upc = $_REQUEST['upc'];
		$id = $_REQUEST['id'];
		$enrichmentData = Enrichment::loadEnrichment($isbn);
		global $interface;
		global $configArray;
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
		require_once ('Drivers/EContentDriver.php');
		require_once ('sys/eContent/EContentRecord.php');
		$driver = new EContentDriver();
		//Get any items that are stored for the record
		$eContentRecord = new EContentRecord();
		$eContentRecord->id = $id;
		$eContentRecord->find(true);

		$holdings = $driver->getHolding($id);
		$showEContentNotes = false;
		$showSize = false;
		foreach ($holdings as $holding){
			if (strlen($holding->notes) > 0){
				$showEContentNotes = true;
			}
		}
		$interface->assign('record', $eContentRecord);
		$interface->assign('availability', $driver->getScopedAvailability($eContentRecord));
		$interface->assign('source', $eContentRecord->source);
		$interface->assign('showEContentNotes', $showEContentNotes);
		if ($eContentRecord->getIsbn() == null || strlen($eContentRecord->getIsbn()) == 0){
			$interface->assign('showOtherEditionsPopup', false);
		}
		$interface->assign('holdings', $holdings);
		//Load status summary
		$result = $driver->getStatusSummary($id, $holdings);
		if (PEAR::isError($result)) {
			PEAR::raiseError($result);
		}
		$holdingData->holdingsSummary = $result;
		$interface->assign('holdingsSummary', $result);
		return $interface->fetch('Record/ajax-holdings.tpl');
	}

	function GetProspectorInfo(){
		require_once 'Drivers/marmot_inc/Prospector.php';
		global $configArray;
		global $interface;
		$id = 'econtentRecord' . $_REQUEST['id'];
		$interface->assign('id', $id);

		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$db = new $class($url);
		if ($configArray['System']['debugSolr']) {
			$db->debug = true;
		}

		// Retrieve Full record from Solr
		if (!($record = $db->getRecord($id))) {
			PEAR::raiseError(new PEAR_Error('Record Does Not Exist'));
		}

		$prospector = new Prospector();
		//Check to see if the record exists within Prospector so we can get the prospector Id
		$prospectorDetails = $prospector->getProspectorDetailsForLocalRecord($record);
		$interface->assign('prospectorDetails', $prospectorDetails);

		$searchTerms = array(
		array('lookfor' => $record['title']),
		);
		if (isset($record['author'])){
			$searchTerms[] = array('lookfor' => $record['author']);
		}
		$prospectorResults = $prospector->getTopSearchResults($searchTerms, 10, $prospectorDetails);
		$interface->assign('prospectorResults', $prospectorResults);
		return $interface->fetch('Record/ajax-prospector.tpl');
	}

	// Email Record
	function SendEmail()
	{
		require_once 'services/EcontentRecord/Email.php';

		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		$emailService = new Email();
		$result = $emailService->sendEmail($_GET['to'], $_GET['from'], $_GET['message']);

		if (PEAR::isError($result)) {
			return '<result>Error</result><details>' .
			htmlspecialchars($result->getMessage()) . '</details>';
		} else {
			return '<result>Done</result>';
		}
	}

	// SMS Record
	function SendSMS()
	{
		require_once 'services/EcontentRecord/SMS.php';
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		$sms = new SMS();
		$result = $sms->sendSMS();

		if (PEAR::isError($result)) {
			return '<result>Error</result>';
		} else {
			if ($result === true){
				return '<result>Done</result>';
			}else{
				return '<result><![CDATA[' . $result . ']]></result>';
			}
		}
	}

	function SaveTag()
	{
		$user = UserAccount::isLoggedIn();
		if ($user === false) {
			return "<result>Unauthorized</result>";
		}

		require_once 'AddTag.php';
		AddTag::save('eContent');

		return '<result>Done</result>';
	}

	function SaveComment()
	{
		require_once 'services/MyResearch/lib/Resource.php';

		$user = UserAccount::isLoggedIn();
		if ($user === false) {
			return json_encode(array('result' => 'Unauthorized'));
		}

		$resource = new Resource();
		$resource->record_id = $_GET['id'];
		$resource->source = 'eContent';
		if (!$resource->find(true)) {
			$resource->insert();
		}
		$resource->addComment($_REQUEST['comment'], $user, 'eContent');

		return json_encode(array('result' => 'true'));
	}

	function DeleteComment()
	{
		require_once 'services/MyResearch/lib/Comments.php';
		global $user;
		global $configArray;

		// Process Delete Comment
		if (is_object($user)) {
			$comment = new Comments();
			$comment->id = $_GET['commentId'];
			$comment->source = 'eContent';
			if ($comment->find(true)) {
				if ($user->id == $comment->user_id) {
					$comment->delete();
				}
			}
		}
		return '<result>true</result>';
	}

	function GetComments()
	{
		global $interface;

		require_once 'services/MyResearch/lib/Resource.php';
		require_once 'services/MyResearch/lib/Comments.php';

		$interface->assign('id', $_GET['id']);

		$resource = new Resource();
		$resource->record_id = $_GET['id'];
		$resource->source = 'eContent';
		if ($resource->find(true)) {
			$commentList = $resource->getComments();
		}

		$interface->assign('commentList', $commentList['user']);
		$userComments = $interface->fetch('Record/view-comments-list.tpl');
		$interface->assign('staffCommentList', $commentList['staff']);
		$staffComments = $interface->fetch('Record/view-staff-reviews-list.tpl');

		return json_encode(array(
			'staffComments' => $staffComments,
			'userComments' => $userComments,
		));
	}

	function RateTitle(){
		require_once('sys/eContent/EContentRating.php');
		global $user;
		if (!isset($user) || $user == false){
			header('HTTP/1.0 500 Internal server error');
			return 'Please login to rate this title.';
		}
		$ratingValue = $_REQUEST['rating'];
		//Save the rating
		$rating = new EContentRating();
		$rating->recordId = $_REQUEST['id'];
		$rating->userId = $user->id;
		$existingRating = false;
		if ($rating->find(true) >= 1) {
			$existingRating = true;
		}
		$rating->rating = $ratingValue;
		$rating->dateRated = time();
		if ($existingRating){
			$rating->update();
		}else{
			$rating->insert();
		}
		//Update the title within Solr
		require_once 'sys/eContent/EContentRecord.php';
		$eContentRecord = new EContentRecord();
		$eContentRecord->recordId = $_REQUEST['id'];
		$eContentRecord->find(true);
		$eContentRecord->saveToSolr();

		return $ratingValue;
	}
	function getDescription(){
		global $interface;
		require_once 'sys/eContent/EContentRecord.php';
		$eContentRecord = new EContentRecord();

		$id = $_REQUEST['id'];
		$eContentRecord->id = $id;
		$eContentRecord->find(true);

		$output = "<result>\n";

		// Build an XML tag representing the current comment:
		$output .= "	<description><![CDATA[" . $eContentRecord->description . "]]></description>\n";
		$output .= "	<length><![CDATA[" . "" . "]]></length>\n";
		$output .= "	<publisher><![CDATA[" . $eContentRecord->publisher . "]]></publisher>\n";

		$output .= "</result>\n";

		return $output;
	}
	function AddItem(){
		require_once 'sys/eContent/EContentItem.php';
		require_once 'sys/DataObjectUtil.php';
		global $user;
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
		require_once 'sys/eContent/EContentItem.php';
		require_once 'sys/DataObjectUtil.php';
		global $user;
		global $interface;
		global $configArray;
		$structure = EContentItem::getObjectStructure();
		$object = new EContentItem();
		$recordId = strip_tags($_REQUEST['id']);
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
		require_once 'sys/eContent/EContentItem.php';
		if ($user->hasRole('epubAdmin')){
			$recordId = strip_tags($_REQUEST['id']);
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
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
			$holdMessage = $driver->placeOverDriveHold($overDriveId, $format, $user);
			return json_encode($holdMessage);
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to place a hold.'));
		}
	}

	function CheckoutOverDriveItem(){
		global $user;
		$overDriveId = $_REQUEST['overDriveId'];
		$format = $_REQUEST['formatId'];
		$lendingPeriod = $_REQUEST['lendingPeriod'];
		//global $logger;
		//$logger->log("Lending period = $lendingPeriod", PEAR_LOG_INFO);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
			$result = $driver->checkoutOverDriveItem($overDriveId, $format, $lendingPeriod, $user);
			//$logger->log("Checkout result = $result", PEAR_LOG_INFO);
			return json_encode($result);
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to checkout an item.'));
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
		require_once 'Drivers/OverDriveDriver.php';
		$overDriveDriver = new OverDriveDriver();
		$loanPeriods = $overDriveDriver->getLoanPeriodsForFormat($formatId);
		$interface->assign('loanPeriods', $loanPeriods);

		//Var for the IDCLREADER TEMPLATE
		$interface->assign('ButtonHome',true);
		$interface->assign('MobileTitle','{translate text="Loan Period"}');

		return $interface->fetch('EcontentRecord/ajax-loan-period.tpl');
	}

	function AddOverDriveRecordToWishList(){
		global $user;
		if (isset($_REQUEST['recordId'])){
			//TODO: get the overdrive id from the EContent REcord
			require_once 'sys/eContent/EContentRecord.php';
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $_REQUEST['recordId'];
			if ($eContentRecord->find(true)){
				$overDriveId = $eContentRecord->getOverDriveId();
			}
		}else{
			$overDriveId = $_REQUEST['overDriveId'];
		}
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
			$result = $driver->addItemToOverDriveWishList($overDriveId, $user);
			return json_encode($result);
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to add an item to your wish list.'));
		}
	}

	function RemoveOverDriveRecordFromWishList(){
		global $user;
		$overDriveId = $_REQUEST['overDriveId'];
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
			$result = $driver->removeOverDriveItemFromWishlist($overDriveId, $user);
			return json_encode($result);
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to add an item to your wish list.'));
		}
	}

	function CancelOverDriveHold(){
		global $user;
		$overDriveId = $_REQUEST['overDriveId'];
		$format = $_REQUEST['formatId'];
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
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
					$purchaseLinks[]  = array(
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
					require_once 'services/Record/Purchase.php';
					$purchaseLinks = Purchase::getStoresForTitle($title, $author);

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