<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Proxy_Request.php';
require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';

global $configArray;

class OverDrive_AJAX extends Action {

	function launch() {
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];
		if (in_array($method, array('CheckoutOverDriveItem', 'PlaceOverDriveHold', 'CancelOverDriveHold', 'GetOverDriveHoldPrompts', 'ReturnOverDriveItem', 'SelectOverDriveDownloadFormat', 'GetDownloadLink'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else if (in_array($method, array('GetOverDriveLoanPeriod', 'getPurchaseOptions', 'getDescription', 'SelectOverDriveFormat'))){
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

	function GetHoldingsInfo(){
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once (ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php');
		$overDriveRecordDriver = new OverDriveRecordDriver($id);
		//Load holdings information from the driver
		require_once (ROOT_DIR . '/Drivers/OverDriveDriverFactory.php');
		$driver = OverDriveDriverFactory::getDriver();

		/** @var OverDriveAPIProductFormats[] $holdings */
		$holdings = $driver->getHoldings($overDriveRecordDriver);
		$scopedAvailability = $driver->getScopedAvailability($overDriveRecordDriver);
		$interface->assign('availability', $scopedAvailability['mine']);
		$interface->assign('availabilityOther', $scopedAvailability['other']);
		$showAvailability = true;
		$showAvailabilityOther = true;
		$interface->assign('showAvailability', $showAvailability);
		$interface->assign('showAvailabilityOther', $showAvailabilityOther);
		$showOverDriveConsole = false;
		$showAdobeDigitalEditions = false;
		foreach ($holdings as $item){
			if (in_array($item->textId, array('ebook-epub-adobe', 'ebook-pdf-adobe'))){
				$showAdobeDigitalEditions = true;
			}else if (in_array($item->textId, array('video-wmv', 'music-wma', 'music-wma', 'audiobook-wma', 'audiobook-mp3'))){
				$showOverDriveConsole = true;
			}
		}
		$interface->assign('showOverDriveConsole', $showOverDriveConsole);
		$interface->assign('showAdobeDigitalEditions', $showAdobeDigitalEditions);

		$interface->assign('holdings', $holdings);
		//Load status summary
		$result = $driver->getStatusSummary($id, $scopedAvailability, $holdings);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}
		$interface->assign('holdingsSummary', $result);
		return $interface->fetch('EcontentRecord/ajax-holdings.tpl');
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

	function PlaceOverDriveHold(){
		global $user;
		$overDriveId = $_REQUEST['overDriveId'];
		$format = $_REQUEST['formatId'];
		$overdriveEmail = isset($_REQUEST['overdriveEmail']) ? $_REQUEST['overdriveEmail'] : $user->overdriveEmail;
		if (isset($_REQUEST['overdriveEmail'])){
			if ($_REQUEST['overdriveEmail'] != $user->overdriveEmail){
				$user->overdriveEmail = $_REQUEST['overdriveEmail'];
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
		$format = $_REQUEST['formatId'];
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