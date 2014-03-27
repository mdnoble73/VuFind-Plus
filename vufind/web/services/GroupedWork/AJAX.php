<?php
/**
 * Handles loading asynchronous
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/2/13
 * Time: 3:52 PM
 */

class GroupedWork_AJAX {
	function launch() {
		global $timer;
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		echo $this->$method();
	}

	function clearUserRating(){
		global $user;
		$id = $_REQUEST['id'];
		$result = array('result' => false);
		if (!$user){
			$result['message'] = 'You must be logged in to delete ratings.';
		}else{
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
			$userWorkReview = new UserWorkReview();
			$userWorkReview->groupedRecordPermanentId = $id;
			$userWorkReview->userId = $user->id;
			if ($userWorkReview->find(true)){
				$userWorkReview->delete();
				$result = array('result' => true, 'message' => 'We successfully deleted that rating for you.');
			}else{
				$result['message'] = 'Sorry, we could not find a that review in the system.';
			}
		}

		return json_encode($result);
	}

	function getEnrichmentInfo(){
		global $configArray;
		global $interface;

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);

		$enrichmentResult = array();
		$enrichmentData = $recordDriver->loadEnrichment();

		//Process series data
		$titles = array();
		if (!isset($enrichmentData['novelist']->seriesTitles) || count($enrichmentData['novelist']->seriesTitles) == 0){
			$enrichmentResult['seriesInfo'] = array('titles'=>$titles, 'currentIndex'=>0);
		}else{
			foreach ($enrichmentData['novelist']->seriesTitles as $key => $record){
				$titles[] = $this->getScrollerTitle($record, $key, 'Series');
			}

			$seriesInfo = array('titles' => $titles, 'currentIndex' => $enrichmentData['novelist']->seriesDefaultIndex);
			$enrichmentResult['seriesInfo'] = $seriesInfo;
		}

		//Process other data from novelist
		if (isset($enrichmentData['novelist']) && isset($enrichmentData['novelist']->similarTitles)){
			$interface->assign('similarTitles', $enrichmentData['novelist']->similarTitles);
			$enrichmentResult['similarTitlesNovelist'] = $interface->fetch('GroupedWork/similarTitlesNovelist.tpl');
		}

		if (isset($enrichmentData['novelist']) && isset($enrichmentData['novelist']->authors)){
			$interface->assign('similarAuthors', $enrichmentData['novelist']->authors);
			$enrichmentResult['similarAuthorsNovelist'] = $interface->fetch('GroupedWork/similarAuthorsNovelist.tpl');
		}

		if (isset($enrichmentData['novelist']) && isset($enrichmentData['novelist']->similarSeries)){
			$interface->assign('similarSeries', $enrichmentData['novelist']->similarSeries);
			$enrichmentResult['similarSeriesNovelist'] = $interface->fetch('GroupedWork/similarSeriesNovelist.tpl');
		}

		//Load Similar titles (from Solr)
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		/** @var Solr $db */
		$db = new $class($url);
		$db->disableScoping();
		$similar = $db->getMoreLikeThis2($id);
		// Send the similar items to the template; if there is only one, we need
		// to force it to be an array or things will not display correctly.
		if (isset($similar) && count($similar['response']['docs']) > 0) {
			$similarTitles = array();
			foreach ($similar['response']['docs'] as $key => $similarTitle){
				$recordDriver = new GroupedWorkDriver($similarTitle);
				$similarTitles[] = $recordDriver->getScrollerTitle($key, 'MoreLikeThis');
			}
			$similarTitlesInfo = array('titles' => $similarTitles, 'currentIndex' => 0);
			$enrichmentResult['similarTitles'] = $similarTitlesInfo;
		}

		//Load go deeper options
		//TODO: Additional go deeper options
		if (isset($library) && $library->showGoDeeper == 0){
			$enrichmentResult['showGoDeeper'] = false;
		}else{
			require_once(ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php');
			$goDeeperOptions = GoDeeperData::getGoDeeperOptions($recordDriver->getCleanISBN(), $recordDriver->getCleanUPC());
			if (count($goDeeperOptions['options']) == 0){
				$enrichmentResult['showGoDeeper'] = false;
			}else{
				$enrichmentResult['showGoDeeper'] = true;
				$enrichmentResult['goDeeperOptions'] = $goDeeperOptions['options'];
			}
		}

		//Related data
		$enrichmentResult['relatedContent'] = $interface->fetch('Record\relatedContent.tpl');

		return json_encode($enrichmentResult);
	}

	function getScrollerTitle($record, $index, $scrollerName){
		$cover = $record['mediumCover'];
		$title = preg_replace("/\s*(\/|:)\s*$/","", $record['title']);
		if (isset($record['series']) && $record['series'] != null){
			if (is_array($record['series'])){
				foreach($record['series'] as $series){
					if (strcasecmp($series, 'none') !== 0){
						break;
					}else{
						$series = '';
					}
				}
			}else{
				$series = $record['series'];
			}
			if (isset($series)){
				$title .= ' (' . $series ;
				if (isset($record['volume'])){
					$title .= ' Volume ' . $record['volume'];
				}
				$title .= ')';
			}
		}

		if (isset($record['id'])){
			$formattedTitle = "<div id=\"scrollerTitle{$scrollerName}{$index}\" class=\"scrollerTitle\">" .
					'<a href="' . $record['fullRecordLink'] . '" id="descriptionTrigger' . $record['id'] . '">' .
					"<img src=\"{$cover}\" class=\"scrollerTitleCover\" alt=\"{$title} Cover\"/>" .
					"</a></div>" .
					"<div id='descriptionPlaceholder{$record['id']}' style='display:none'></div>";
		}else{
			$formattedTitle = "<div id=\"scrollerTitle{$scrollerName}{$index}\" class=\"scrollerTitle\">" .
					"<img src=\"{$cover}\" class=\"scrollerTitleCover\" alt=\"{$title} Cover\"/>" .
					"</div>";
		}

		return array(
			'id' => isset($record['id']) ? $record['id'] : '',
			'image' => $cover,
			'title' => $title,
			'author' => isset($record['author']) ? $record['author'] : '',
			'formattedTitle' => $formattedTitle
		);
	}

	function getGoDeeperData(){
		require_once(ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php');
		$dataType = $_REQUEST['dataType'];

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);
		$upc = $recordDriver->getCleanUPC();
		$isbn = $recordDriver->getCleanISBN();

		$formattedData = GoDeeperData::getHtmlData($dataType, 'GroupedWork', $isbn, $upc);
		$return = array(
			'formattedData' => $formattedData
		);
		return json_encode($return);

	}

	function getWorkInfo(){
		global $interface;
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);

		$interface->assign('recordDriver', $recordDriver);
		$results = array(
				'title' => $recordDriver->getTitle(),
				'modalBody' => $interface->fetch("GroupedWork/work-details.tpl"),
				'modalButtons' => "<a href='/GroupedWork/{$id}/Home'><span class='tool btn btn-primary'>More Info</span></a>"
		);
		return json_encode($results);

	}

	function RateTitle(){
		require_once(ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php');
		global $user;
		global $analytics;
		if (!isset($user) || $user == false){
			header('HTTP/1.0 500 Internal server error');
			return 'Please login to rate this title.';
		}
		$rating = $_REQUEST['rating'];
		//Save the rating
		$workReview = new UserWorkReview();
		$workReview->groupedRecordPermanentId = $_GET['id'];
		$workReview->userId = $user->id;
		$newReview = false;
		if (!$workReview->find(true)) {
			$newReview = true;
		}
		$workReview->rating = $rating;
		$workReview->dateRated = time();
		$workReview->review = '';
		if ($newReview){
			$workReview->insert();
		}else{
			$workReview->update();
		}

		$analytics->addEvent('User Enrichment', 'Rate Title', $_GET['id']);

		/** @var Memcache $memCache */
		global $memCache;
		$memCache->delete('rating_' . $_GET['id']);

		return $rating;
	}

	function getReviewInfo(){
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);
		$isbn = $recordDriver->getCleanISBN();

		//Load external (syndicated reviews)
		require_once ROOT_DIR . '/sys/Reviews.php';
		$externalReviews = new ExternalReviews($isbn);
		$reviews = $externalReviews->fetch();
		global $interface;
		$interface->assign('id', $id);
		$numSyndicatedReviews = 0;
		foreach ($reviews as $providerReviews){
			$numSyndicatedReviews += count($providerReviews);
		}
		$interface->assign('syndicatedReviews', $reviews);

		//Load editorial reviews
		require_once ROOT_DIR . '/sys/LocalEnrichment/EditorialReview.php';
		$editorialReviews = new EditorialReview();
		$editorialReviews->recordId = $id;
		$editorialReviews->find();
		$allEditorialReviews = array();
		while($editorialReviews->fetch()){
			$allEditorialReviews[] = clone($editorialReviews);
		}
		$interface->assign('editorialReviews', $allEditorialReviews);

		$userReviews = $recordDriver->getUserReviews();
		$interface->assign('userReviews', $userReviews);

		$results = array(
			'numSyndicatedReviews' => $numSyndicatedReviews,
			'syndicatedReviewsHtml' => $interface->fetch('GroupedWork/view-syndicated-reviews.tpl'),
			'numEditorialReviews' => count($allEditorialReviews),
			'editorialReviewsHtml' => $interface->fetch('GroupedWork/view-editorial-reviews.tpl'),
			'numCustomerReviews' => count($userReviews),
			'customerReviewsHtml' => $interface->fetch('GroupedWork/view-user-reviews.tpl'),
		);
		return json_encode($results);
	}

	function getReviewForm(){
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$results = array(
			'title' => 'Review',
			'modalBody' => $interface->fetch("GroupedWork/review-form-body.tpl"),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.GroupedWork.saveReview(\"{$id}\"); return false;'>Submit Review</span>"
		);
		return json_encode($results);
	}

	function saveReview()
	{
		$result = array();

		global $user;
		if ($user === false) {
			$result['result'] = false;
			$result['message'] = 'Please login before adding a review.';
		}else{
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
			$result['result'] = true;
			$id = $_REQUEST['id'];
			$rating = $_REQUEST['rating'];
			$comment = $_REQUEST['comment'];

			$groupedWorkReview = new UserWorkReview();
			$groupedWorkReview->userId = $user->id;
			$groupedWorkReview->groupedRecordPermanentId = $id;
			$newReview = true;
			if ($groupedWorkReview->find(true)){
				$newReview = false;
			}
			$result['newReview'] = $newReview;
			$groupedWorkReview->rating = $rating;
			$groupedWorkReview->review = $comment;
			if ($newReview){
				$groupedWorkReview->insert();
			}else{
				$groupedWorkReview->update();
			}
			$result['reviewId'] = $groupedWorkReview->id;
			global $interface;
			$interface->assign('review', $groupedWorkReview);
			$result['reviewHtml'] = $interface->fetch('GroupedWork/view-user-review.tpl');
		}

		return json_encode($result);
	}

	function getSMSForm(){
		global $interface;
		require_once ROOT_DIR . '/sys/Mailer.php';

		$sms = new SMSMailer();
		$interface->assign('carriers', $sms->getCarriers());
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);

		$relatedRecords = $recordDriver->getRelatedRecords();
		$interface->assign('relatedRecords', $relatedRecords);
		$results = array(
				'title' => 'Share via SMS Message',
				'modalBody' => $interface->fetch("GroupedWork/sms-form-body.tpl"),
				'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.GroupedWork.sendSMS(\"{$id}\"); return false;'>Send Text</span>"
		);
		return json_encode($results);
	}

	function getEmailForm(){
		global $interface;
		require_once ROOT_DIR . '/sys/Mailer.php';

		$sms = new SMSMailer();
		$interface->assign('carriers', $sms->getCarriers());
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);

		$relatedRecords = $recordDriver->getRelatedRecords();
		$interface->assign('relatedRecords', $relatedRecords);
		$results = array(
				'title' => 'Share via E-mail',
				'modalBody' => $interface->fetch("GroupedWork/email-form-body.tpl"),
				'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.GroupedWork.sendEmail(\"{$id}\"); return false;'>Send E-mail</span>"
		);
		return json_encode($results);
	}

	function sendEmail()
	{
		global $interface;
		global $configArray;

		$to = strip_tags($_REQUEST['to']);
		$from = strip_tags($_REQUEST['from']);
		$message = $_REQUEST['message'];

		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);
		$interface->assign('recordDriver', $recordDriver);

		if (isset($_REQUEST['related_record'])){
			$relatedRecord = $_REQUEST['related_record'];
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$recordDriver = new GroupedWorkDriver($id);

			$relatedRecords = $recordDriver->getRelatedRecords();

			foreach ($relatedRecords as $curRecord){
				if ($curRecord['id'] = $relatedRecord){
					if (isset($curRecord['callNumber'])){
						$interface->assign('callnumber', $curRecord['callNumber']);
					}
					if (isset($curRecord['shelfLocation'])){
						$interface->assign('shelfLocation', strip_tags($curRecord['shelfLocation']));
					}
				}
			}
		}

		$subject = translate("Library Catalog Record") . ": " . $recordDriver->getTitle();
		$interface->assign('from', $from);
		$interface->assign('emailDetails', $recordDriver->getEmail());
		$interface->assign('recordID', $recordDriver->getUniqueID());
		if (strpos($message, 'http') === false && strpos($message, 'mailto') === false && $message == strip_tags($message)){
			$interface->assign('message', $message);
			$body = $interface->fetch('Emails/grouped-work-email.tpl');

			require_once ROOT_DIR . '/sys/Mailer.php';
			$mail = new VuFindMailer();
			$emailResult = $mail->send($to, $configArray['Site']['email'], $subject, $body, $from);

			if ($emailResult === true){
				$result = array(
						'result' => true,
						'message' => 'Your e-mail was sent successfully.'
				);
			}elseif (PEAR_Singleton::isError($emailResult)){
				$result = array(
						'result' => false,
						'message' => "Your e-mail message could not be sent {$emailResult}."
				);
			}else{
				$result = array(
						'result' => false,
						'message' => 'Your e-mail message could not be sent due to an unknown error.'
				);
			}
		}else{
			$result = array(
					'result' => false,
					'message' => 'Sorry, we can&apos;t send e-mails with html or other data in it.'
			);
		}
		return json_encode($result);
	}

	function saveToList(){
		$result = array();

		global $user;
		if ($user === false) {
			$result['result'] = false;
			$result['message'] = 'Please login before adding a title to list.';
		}else{
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
			$result['result'] = true;
			$id = $_REQUEST['id'];
			$listId = $_REQUEST['listId'];
			$notes = $_REQUEST['notes'];

			//Check to see if we need to create a list
			$userList = new UserList();
			$listOk = true;
			if (empty($listId)){
				$userList->title = "My Favorites";
				$userList->user_id = $user->id;
				$userList->public = 0;
				$userList->description = '';
				$userList->insert();
			}else{
				$userList->id = $listId;
				if (!$userList->find(true)){
					$result['result'] = false;
					$result['message'] = 'Sorry, we could not find that list in the system.';
					$listOk = false;
				}
			}

			if ($listOk){
				$userListEntry = new UserListEntry();
				$userListEntry->listId = $userList->id;
				$userListEntry->groupedWorkPermanentId = $id;

				$existingEntry = false;
				if ($userListEntry->find(true)){
					$existingEntry = true;
				}
				$userListEntry->notes = $notes;
				$userListEntry->dateAdded = time();
				if ($existingEntry){
					$userListEntry->update();
				}else{
					$userListEntry->insert();
				}
			}

			$result['result'] = true;
			$result['message'] = 'This title was saved to your list successfully.';
		}

		return json_encode($result);
	}

	function getSaveToListForm(){
		global $interface;
		global $user;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';

		//Get a list of all lists for the user
		$containingLists = array();
		$nonContainingLists = array();

		$userLists = new UserList();
		$userLists->user_id = $user->id;
		$userLists->find();
		while ($userLists->fetch()){
			//Check to see if the user has already added the title to the list.
			$userListEntry = new UserListEntry();
			$userListEntry->listId = $userLists->id;
			$userListEntry->groupedWorkPermanentId = $id;
			if ($userListEntry->find(true)){
				$containingLists[] = array(
						'id' => $userLists->id,
						'title' => $userLists->title
				);
			}else{
				$nonContainingLists[] = array(
						'id' => $userLists->id,
						'title' => $userLists->title
				);
			}
		}

		$interface->assign('containingLists', $containingLists);
		$interface->assign('nonContainingLists', $nonContainingLists);

		$results = array(
				'title' => 'Add To List',
				'modalBody' => $interface->fetch("GroupedWork/save.tpl"),
				'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.GroupedWork.saveToList(\"{$id}\"); return false;'>Save To List</span>"
		);
		return json_encode($results);
	}

	function sendSMS(){
		global $configArray;
		global $interface;
		require_once ROOT_DIR . '/sys/Mailer.php';
		$sms = new SMSMailer();

		// Get Holdings
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);

		if (isset($_REQUEST['related_record'])){
			$relatedRecord = $_REQUEST['related_record'];
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$recordDriver = new GroupedWorkDriver($id);

			$relatedRecords = $recordDriver->getRelatedRecords();

			foreach ($relatedRecords as $curRecord){
				if ($curRecord['id'] = $relatedRecord){
					if (isset($curRecord['callNumber'])){
						$interface->assign('callnumber', $curRecord['callNumber']);
					}
					if (isset($curRecord['shelfLocation'])){
						$interface->assign('shelfLocation', strip_tags($curRecord['shelfLocation']));
					}
				}
			}
		}

		$interface->assign('title', $recordDriver->getTitle());
		$interface->assign('recordId', $_GET['id']);
		$message = $interface->fetch('Emails/grouped-work-sms.tpl');

		$smsResult = $sms->text($_REQUEST['provider'], $_REQUEST['sms_phone_number'], $configArray['Site']['email'], $message);

		if ($smsResult === true){
			$result = array(
					'result' => true,
					'message' => 'Your text message was sent successfully.'
			);
		}elseif (PEAR_Singleton::isError($smsResult)){
			$result = array(
					'result' => false,
					'message' => 'Your text message was count not be sent {$smsResult}.'
			);
		}else{
			$result = array(
					'result' => false,
					'message' => 'Your text message could not be sent due to an unknown error.'
			);
		}

		return json_encode($result);
	}

	function markNotInterested(){
		global $user;
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
		$notInterested = new NotInterested();
		$notInterested->userId = $user->id;
		$notInterested->groupedRecordPermanentId = $id;

		if (!$notInterested->find(true)){
			$notInterested->dateMarked = time();
			$notInterested->insert();
			$result = array(
				'result' => true,
				'message' => "You won't be shown this title in the future.",
			);
		}else{
			$result = array(
				'result' => false,
				'message' => "This record was already marked as something you aren't interested in.",
			);
		}
		return json_encode($result);
	}

	function clearNotInterested(){
		global $user;
		$idToClear = $_REQUEST['id'];
		require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
		$notInterested = new NotInterested();
		$notInterested->userId = $user->id;
		$notInterested->id = $idToClear;
		$result = array('result' => false);
		if ($notInterested->find(true)){
			$notInterested->delete();
			$result = array('result' => true);
		}
		return json_encode($result);
	}

	function getAddTagForm(){
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$results = array(
				'title' => 'Add Tag',
				'modalBody' => $interface->fetch("GroupedWork/addtag.tpl"),
				'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.GroupedWork.saveTag(\"{$id}\"); return false;'>Add Tags</span>"
		);
		return json_encode($results);
	}

	function saveTag()
	{
		global $user;
		if ($user === false) {
			return json_encode(array('success' => false, 'message' => 'Sorry, you must be logged in to add tags.'));
		}

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserTag.php';

		$id = $_REQUEST['id'];
		// Parse apart the tags and save them in association with the resource:
		preg_match_all('/"[^"]*"|[^,]+/', $_REQUEST['tag'], $words);
		foreach ($words[0] as $tag) {
			$tag = trim(strtolower(str_replace('"', '', $tag)));

			$userTag = new UserTag();
			$userTag->tag = $tag;
			$userTag->userId = $user->id;
			$userTag->groupedRecordPermanentId = $id;
			if (!$userTag->find(true)){
				//This is a new tag
				$userTag->dateTagged = time();
				$userTag->insert();
			}else{
				//This tag has already been added
			}
		}

		return json_encode(array('success' => true, 'message' => 'All tags have been added to the title.  Refresh to view updated tag list.'));
	}

	function removeTag(){
		global $user;
		if ($user === false) {
			return json_encode(array('success' => false, 'message' => 'Sorry, you must be logged in to remove tags.'));
		}

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserTag.php';

		$id = $_REQUEST['id'];
		$tag = $_REQUEST['tag'];
		$userTag = new UserTag();
		$userTag->tag = $tag;
		$userTag->userId = $user->id;
		$userTag->groupedRecordPermanentId = $id;
		if ($userTag->find(true)){
			//This is a new tag
			$userTag->delete();
			return json_encode(array('success' => true, 'message' => 'Removed your tag from the title.  Refresh to view updated tag list.'));
		}else{
			//This tag has already been added
			return json_encode(array('success' => true, 'message' => 'We could not find that tag for this record.'));
		}
	}

	function getProspectorInfo(){
		require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
		global $configArray;
		global $interface;
		$id = $_REQUEST['id'];
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
						'index' => 'Title'
				),
		);
		if (isset($record['author'])){
			$searchTerms[] = array(
					'lookfor' => $record['author'],
					'index' => 'Author'
			);
		}
		$prospectorResults = $prospector->getTopSearchResults($searchTerms, 10, $prospectorDetails);
		$interface->assign('prospectorResults', $prospectorResults);

		$result = array(
			'numTitles' => count($prospectorResults),
			'formattedData' => $interface->fetch('GroupedWork/ajax-prospector.tpl')
		);
		return json_encode($result);
	}

	function getNovelistData(){
		$url = $_REQUEST['novelistUrl'];
		$rawNovelistData = file_get_contents($url);
		//Trim off the wrapping data ();
		$rawNovelistData = substr($rawNovelistData, 1, -2);
		$jsonData = json_decode($rawNovelistData);
		$novelistData = $jsonData->body;
		echo($novelistData);
	}
} 