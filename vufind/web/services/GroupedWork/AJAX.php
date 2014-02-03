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
		$similarTitlesInfo = array();
		if (isset($similar) && count($similar['response']['docs']) > 0) {
			$similarTitles = array();
			foreach ($similar['response']['docs'] as $key => $similarTitle){
				$similarTitles[] = $this->getScrollerTitle($similarTitle, $key, 'MoreLikeThis');
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
		global $configArray;
		if (isset($record['isbn'])){
			$isbn = $record['isbn'];
			if (is_array($isbn)){
				$isbn = reset($isbn);
			}
			if (strpos($isbn, ' ') > 0){
				$isbn = substr($isbn, 0, strpos($isbn, ' '));
			}
		}else{
			$isbn = '';
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

		if (isset($record['id'])){
			$formattedTitle = "<div id=\"scrollerTitle{$scrollerName}{$index}\" class=\"scrollerTitle\">" .
					'<a href="' . $configArray['Site']['path'] . "/GroupedWork/" . $record['id'] . '" id="descriptionTrigger' . $record['id'] . '">' .
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
			'author' => $record['author'],
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