<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 11/27/13
 * Time: 12:14 PM
 */
require_once ROOT_DIR  . '/Action.php';
class GroupedWork_Home extends Action{


	function launch() {
		global $interface;
		global $timer;
		global $logger;


		$id = $_REQUEST['id'];

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);
		if (!$recordDriver->isValid){
			$logger->log("Did not find a record for id {$id} in solr." , PEAR_LOG_DEBUG);
			$interface->setTemplate('invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}
		$interface->assign('recordDriver', $recordDriver);
		$timer->logTime('Initialized the Record Driver');

		// Retrieve User Search History
		$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ? $_SESSION['lastSearchURL'] : false);

		//Get Next/Previous Links
		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$searchObject->getNextPrevLinks();

		//Load more details options
		$moreDetailsOptions = array();
		$moreDetailsOptions['borrowerReviews'] = array(
			'label' => 'Borrower Reviews',
			'body' => "<div id='customerReviewPlaceholder'></div>",
		);
		$moreDetailsOptions['editorialReviews'] = array(
			'label' => 'Editorial Reviews',
			'body' => "<div id='editorialReviewsPlaceholder'></div>",
		);
		$moreDetailsOptions['syndicatedReviews'] = array(
			'label' => 'Syndicated Reviews',
			'body' => "<div id='syndicatedReviewsPlaceholder'></div>",
		);
		$moreDetailsOptions['goodreadsReviews'] = array(
			'label' => 'Reviews from GoodReads',
			'body' => '<iframe id="goodreads_iframe" class="goodReadsIFrame" src="https://www.goodreads.com/api/reviews_widget_iframe?did=DEVELOPER_ID&format=html&isbn={$recordDriver->getCleanISBN()}&links=660&review_back=fff&stars=000&text=000" width="100%" height="400px" frameborder="0"></iframe>',
		);
		$moreDetailsOptions['tableOfContents'] = array(
			'label' => 'Table of Contents',
			'body' => $interface->fetch('GroupedWork/tableOfContents.tpl'),
		);
		$moreDetailsOptions['excerpt'] = array(
			'label' => 'Excerpt',
			'body' => '<div id="excerptPlaceholder">Loading Excerpt...</div>',
		);
		$moreDetailsOptions['details'] = array(
			'label' => 'Details',
			'body' => $interface->fetch('GroupedWork/view-title-details.tpl'),
		);
		$moreDetailsOptions['similarTitles'] = array(
			'label' => 'Similar Titles From Novelist',
			'body' => '<div id="novelisttitlesPlaceholder"></div>',
		);
		$moreDetailsOptions['similarAuthors'] = array(
			'label' => 'Similar Authors From Novelist',
			'body' => '<div id="novelistauthorsPlaceholder"></div>',
		);
		$moreDetailsOptions['similarSeries'] = array(
			'label' => 'Similar Series From Novelist',
			'body' => '<div id="novelistseriesPlaceholder"></div>',
		);
		$moreDetailsOptions['staff'] = array(
			'label' => 'Staff View',
			'body' => $interface->fetch($recordDriver->getStaffView()),
		);

		$interface->assign('moreDetailsOptions', $moreDetailsOptions);

		$interface->assign('sidebar', 'GroupedWork/full-record-sidebar.tpl');
		$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
		$interface->setTemplate('full-record.tpl');

		// Display Page
		$interface->display('layout.tpl');
	}
}