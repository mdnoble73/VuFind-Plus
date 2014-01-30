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
		if (in_array($method, array('getRelatedRecords'))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else{
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}
	}

	function getRelatedRecords(){
		global $interface;
		if (isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init();

			// Retrieve Full record from Solr
			if (!($record = $searchObject->getRecord($id))) {
				PEAR_Singleton::raiseError(new PEAR_Error('Record Does Not Exist'));
			}

			$recordDriver = RecordDriverFactory::initRecordDriver($record);
			$interface->assign('relatedRecords', $recordDriver->getRelatedRecords());
			return $interface->fetch('GroupedWork/relatedRecordPopup.tpl');
		}else{
			return "Unable to load related records";
		}
	}

	function GetEnrichmentInfoJSON(){
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
			foreach ($enrichmentData['novelist']->seriesTitles as $record){
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
					$shortId = str_replace('.', '', $rawData['id']);
					$formattedTitle = "<div id=\"scrollerTitleSeries{$key}\" class=\"scrollerTitle\">" .
							'<a href="' . $configArray['Site']['path'] . "/GroupedWork/" . $rawData['id'] . '" id="descriptionTrigger' . $shortId . '">' .
							"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
							"</a></div>" .
							"<div id='descriptionPlaceholder{$shortId}' style='display:none'></div>";
				}else{
					$formattedTitle = "<div id=\"scrollerTitleSeries{$key}\" class=\"scrollerTitle\">" .
							"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
							"</div>";
				}
				$rawData['formattedTitle'] = $formattedTitle;
				$titles[$key] = $rawData;
			}
			$seriesInfo = array('titles' => $titles, 'currentIndex' => $enrichmentData['novelist']->seriesDefaultIndex);
			$enrichmentResult['seriesInfo'] = $seriesInfo;
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

	function GetGoDeeperData(){
		require_once(ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php');
		$dataType = $_REQUEST['dataType'];

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);
		$upc = $recordDriver->getCleanUPC();
		$isbn = $recordDriver->getCleanISBN();

		$formattedData = GoDeeperData::getHtmlData($dataType, 'GroupedWork', $isbn, $upc);
		return $formattedData;

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

	function GetReviewInfo(){
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

		$interface->assign('userReviews', $recordDriver->getUserReviews());

		$results = array(
			'syndicatedReviewsHtml' => $interface->fetch('GroupedWork/view-syndicated-reviews.tpl'),
			'editorialReviewsHtml' => $interface->fetch('GroupedWork/view-editorial-reviews.tpl'),
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
} 