<?php
require_once ROOT_DIR . '/RecordDrivers/IndexRecord.php';

/**
 * List Record Driver
 *
 * This class is designed to handle List records.  Much of its functionality
 * is inherited from the default index-based driver.
 */
class ListRecord extends IndexRecord
{
	public function __construct($record)
	{
		// Call the parent's constructor...
		parent::__construct($record);
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult($view = 'list'){
		if ($view == 'covers') { // Displaying Results as bookcover tiles
			return $this->getCoverResult();
		}

		global $interface;

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$interface->assign('summShortId', substr($id, 4)); //Trim the list prefix for the short id
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		if (isset($this->fields['description'])){
			$interface->assign('summDescription', $this->fields['description']);
		}else{
			$interface->assign('summDescription', '');
		}
		if (isset($this->fields['num_titles'])){
			$interface->assign('summNumTitles', $this->fields['num_titles']);
		}else{
			$interface->assign('summNumTitles', 0);
		}

		return 'RecordDrivers/List/result.tpl';
	}

	public function getMoreDetailsOptions(){
		return array();
	}

	// initally taken From GroupedWorkDriver.php getBrowseResult();
	public function getCoverResult(){
		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$shortId = substr($id, 4);  // Trim the list prefix for the short id
//		$interface->assign('summShortId', $shortId);

		$url ='/MyAccount/MyList/'.$shortId;
//		$test = $this->getURLs();

		$interface->assign('summUrl', $url);
		$interface->assign('summTitle', $this->getTitle());
//		$interface->assign('summSubTitle', $this->getSubtitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());

		//Get Rating
//		$interface->assign('ratingData', $this->getRatingData());
		//TODO: list image. (list.png added in template
//		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));


		return 'RecordDrivers/List/cover_result.tpl';
//		return 'RecordDrivers/GroupedWork/browse_result.tpl';
	}

	function getFormat() {
		// overwrites class IndexRecord getFormat() so that getBookCoverURL() call will work without warning notices
		return array('list');
	}
}