<?php
/**
 * GroupedWorkDriver Class
 *
 * This class handles the display of Grouped Works within VuFind.
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 11/26/13
 * Time: 1:51 PM
 */

require_once ROOT_DIR . '/RecordDrivers/Interface.php';
class GroupedWorkDriver extends RecordInterface{

	protected $fields;
	protected $scopingEnabled = true;
	public $isValid = true;
	/**
	 * These are captions corresponding with Solr fields for use when displaying
	 * snippets.
	 *
	 * @var    array
	 * @access protected
	 */
	protected $snippetCaptions = array(

	);

	/**
	 * Should we include snippets in search results?
	 *
	 * @var    bool
	 * @access protected
	 */
	protected $snippet = false;
	protected $highlight = false;
	/**
	 * These Solr fields should be used for snippets if available (listed in order
	 * of preference).
	 *
	 * @var    array
	 * @access protected
	 */
	protected $preferredSnippetFields = array('contents', 'topic');
	/**
	 * These Solr fields should NEVER be used for snippets.  (We exclude author
	 * and title because they are already covered by displayed fields; we exclude
	 * spelling because it contains lots of fields jammed together and may cause
	 * glitchy output; we exclude ID because random numbers are not helpful).
	 *
	 * @var    array
	 * @access protected
	 */
	protected $forbiddenSnippetFields = array(
		'author', 'author-letter', 'auth_author2', 'title', 'title_short', 'title_full',
		'title_auth', 'title_sub', 'title_display', 'spelling', 'id',
		'allfields', 'allfields_proper', 'fulltext_unstemmed', 'econtentText_unstemmed', 'keywords_proper',
		'spellingShingle', 'collection', 'title_proper',
		'contents_proper', 'genre_proper', 'geographic_proper', 'display_description'
	);
	public function __construct($indexFields)
	{
		if (is_string($indexFields)){
			$id = $indexFields;
			//Just got a record id, let's load the full record from Solr
			// Setup Search Engine Connection
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->disableScoping();
			if (function_exists('disableErrorHandler')){
				disableErrorHandler();
			}

			// Retrieve the record from Solr
			if (!($record = $searchObject->getRecord($id))) {
				$this->isValid = false;
			}else{
				$this->fields = $record;
			}
			$searchObject->enableScoping();
			if (function_exists('enableErrorHandler')){
				enableErrorHandler();
			}

		}else{
			$this->fields = $indexFields;
			// Load highlighting/snippet preferences:
			$searchSettings = getExtraConfigArray('searches');
			$this->highlight = !isset($searchSettings['General']['highlighting']) ? false : $searchSettings['General']['highlighting'];
			$this->snippet = !isset($searchSettings['General']['snippets']) ? false : $searchSettings['General']['snippets'];
			$this->snippetCaptions = isset($searchSettings['Snippet_Captions']) && is_array($searchSettings['Snippet_Captions']) ? $searchSettings['Snippet_Captions'] : array();
		}
	}

	public function getSolrField($fieldName){
		return isset($this->fields[$fieldName]) ? $this->fields[$fieldName] : null;
	}

	private static function normalizeEdition($edition) {
		$edition = strtolower($edition);
		$edition = str_replace('first', '1', $edition);
		$edition = str_replace('second', '2', $edition);
		$edition = str_replace('third', '3', $edition);
		$edition = str_replace('fourth', '4', $edition);
		$edition = preg_replace('/\D/', '', $edition);
		return $edition;
	}

	public function setScopingEnabled($enabled){
		$this->scopingEnabled = $enabled;
	}

	public function getContributors(){
		return $this->fields['auth_author2'];
	}

	public function getPermanentId(){
		return $this->fields['id'];
	}

	public function getMpaaRating(){
		return $this->fields['mpaaRating'];
	}

	/**
	 * Get text that can be displayed to represent this record in
	 * breadcrumbs.
	 *
	 * @access  public
	 * @return  string              Breadcrumb text to represent this record.
	 */
	public function getBreadcrumb() {
		return $this->getTitle();
	}

	/**
	 * Assign necessary Smarty variables and return a template name
	 * to load in order to display the requested citation format.
	 * For legal values, see getCitationFormats().  Returns null if
	 * format is not supported.
	 *
	 * @param   string $format Citation format to display.
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCitation($format) {
		// TODO: Implement getCitation() method.
	}

	/**
	 * Get an array of strings representing citation formats supported
	 * by this record's data (empty if none).  Legal values: "APA", "MLA".
	 *
	 * @access  public
	 * @return  array               Strings representing citation formats.
	 */
	public function getCitationFormats() {
		// TODO: Implement getCitationFormats() method.
	}

	/**
	 * Get an array of search results for other editions of the title
	 * represented by this record (empty if unavailable).  In most cases,
	 * this will use the XISSN/XISBN logic to find matches.
	 *
	 * @access  public
	 * @return  mixed               Editions in index engine result format.
	 *                              (or null if no hits, or PEAR_Error object).
	 */
	public function getEditions() {
		// TODO: Implement getEditions() method.
	}

	/**
	 * Get the text to represent this record in the body of an email.
	 *
	 * @access  public
	 * @return  string              Text for inclusion in email.
	 */
	public function getEmail() {
		// TODO: Remove getEmail() method.
	}

	/**
	 * Get any excerpts associated with this record.  For details of
	 * the return format, see sys/Excerpts.php.
	 *
	 * @access  public
	 * @return  array               Excerpt information.
	 */
	public function getExcerpts() {
		// TODO: Implement getExcerpts() method.
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to export the record in the requested format.  For
	 * legal values, see getExportFormats().  Returns null if format is
	 * not supported.
	 *
	 * @param   string $format Export format to display.
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getExport($format) {
		// TODO: Implement getExport() method.
	}

	/**
	 * Get an array of strings representing formats in which this record's
	 * data may be exported (empty if none).  Legal values: "RefWorks",
	 * "EndNote", "MARC", "RDF".
	 *
	 * @access  public
	 * @return  array               Strings representing export formats.
	 */
	public function getExportFormats() {
		// TODO: Implement getExportFormats() method.
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display extended metadata (more details beyond
	 * what is found in getCoreMetadata() -- used as the contents of the
	 * Description tab of the record view).
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getExtendedMetadata() {
		// TODO: Implement getExtendedMetadata() method.
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display holdings extracted from the base record
	 * (i.e. URLs in MARC 856 fields).  This is designed to supplement,
	 * not replace, holdings information extracted through the ILS driver
	 * and displayed in the Holdings tab of the record view page.  Returns
	 * null if no data is available.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getHoldings() {
		// TODO: Implement getHoldings() method.
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * user's favorites list.
	 *
	 * @access  public
	 * @param   object $user User object owning tag/note metadata.
	 * @param   int $listId ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param   bool $allowEdit Should we display edit controls?
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getListEntry($user, $listId = null, $allowEdit = true) {
		global $configArray;
		global $interface;
		global $timer;

		$id = $this->getUniqueID();
		$timer->logTime("Starting to load search result for grouped work $id");
		$interface->assign('summId', $id);
		if (substr($id, 0, 1) == '.'){
			$interface->assign('summShortId', substr($id, 1));
		}else{
			$interface->assign('summShortId', $id);
		}

		$relatedManifestations = $this->getRelatedManifestations();
		$interface->assign('relatedManifestations', $relatedManifestations);

		//Build the link URL.
		//If there is only one record for the work we will link straight to that.
		$relatedRecords = $this->getRelatedRecords();
		if (count($relatedRecords) == 1){
			$firstRecord = reset($relatedRecords);
			/** @var IndexRecord|OverDriveRecordDriver|BaseEContentDriver $driver */
			$driver = $firstRecord['driver'];
			$linkUrl = $driver->getLinkUrl();
		}else{
			$linkUrl = $this->getLinkUrl() . '?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page='  . $interface->get_template_vars('page');
		}

		$interface->assign('summUrl', $linkUrl);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summSubTitle', $this->getSubtitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		$isbn = $this->getCleanISBN();
		$interface->assign('summISBN', $isbn);
		$interface->assign('summFormats', $this->getFormats());

		$summPublisher = null;
		$summPubDate = null;
		$summPhysicalDesc = null;
		$summEdition = null;
		$summLanguage = null;
		$isFirst = true;
		foreach ($relatedRecords as $relatedRecord){
			if ($isFirst){
				$summPublisher = $relatedRecord['publisher'];
				$summPubDate = $relatedRecord['publicationDate'];
				$summPhysicalDesc = $relatedRecord['physical'];
				$summEdition = $relatedRecord['edition'];
				$summLanguage = $relatedRecord['language'];
			}else{
				if ($summPublisher != $relatedRecord['publisher']){
					$summPublisher = null;
				}
				if ($summPubDate != $relatedRecord['publicationDate']){
					$summPubDate = null;
				}
				if ($summPhysicalDesc != $relatedRecord['physical']){
					$summPhysicalDesc = null;
				}
				if ($summEdition != $relatedRecord['edition']){
					$summEdition = null;
				}
				if ($summLanguage != $relatedRecord['language']){
					$summLanguage = null;
				}
			}
			$isFirst = false;
		}
		$interface->assign('summPublisher', $summPublisher);
		$interface->assign('summPubDate', $summPubDate);
		$interface->assign('summPhysicalDesc', $summPhysicalDesc);
		$interface->assign('summEdition', $summEdition);
		$interface->assign('summLanguage', $summLanguage);

		$interface->assign('numRelatedRecords', $this->getNumRelatedRecords());

		if ($configArray['System']['debugSolr']){
			$interface->assign('summScore', $this->getScore());
			$interface->assign('summExplain', $this->getExplain());
		}

		//Get Rating
		$interface->assign('summRating', $this->getRatingData());

		//Description
		$interface->assign('summDescription', $this->getDescriptionFast());
		$timer->logTime('Finished Loading Description');
		if ($this->hasCachedSeries()){
			$interface->assign('ajaxSeries', false);
			$interface->assign('summSeries', $this->getSeries());
		}else{
			$interface->assign('ajaxSeries', true);
		}

		$timer->logTime('Finished Loading Series');

		//Get information from list entry
		if ($listId) {
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
			$listEntry                         = new UserListEntry();
			$listEntry->groupedWorkPermanentId = $this->getUniqueID();
			$listEntry->listId                 = $listId;
			if ($listEntry->find(true)) {
				$interface->assign('listEntryNotes', $listEntry->notes);
			}
			$interface->assign('listEditAllowed', $allowEdit);
		}
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		// By default, do not display AJAX status; we won't assume that all
		// records exist in the ILS.  Child classes can override this setting
		// to turn on AJAX as needed:
		$interface->assign('summAjaxStatus', false);

		$interface->assign('recordDriver', $this);

		return 'RecordDrivers/GroupedWork/listentry.tpl';
	}

	public function getSuggestionEntry(){
		global $interface;
		global $timer;

		$id = $this->getUniqueID();
		$timer->logTime("Starting to load search result for grouped work $id");
		$interface->assign('summId', $id);
		if (substr($id, 0, 1) == '.'){
			$interface->assign('summShortId', substr($id, 1));
		}else{
			$interface->assign('summShortId', $id);
		}

		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summSubTitle', $this->getSubtitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		$isbn = $this->getCleanISBN();
		$interface->assign('summISBN', $isbn);
		$interface->assign('summFormats', $this->getFormats());

		$interface->assign('numRelatedRecords', $this->getNumRelatedRecords());

		$relatedManifestations = $this->getRelatedManifestations();
		$interface->assign('relatedManifestations', $relatedManifestations);

		//Get Rating
		$interface->assign('summRating', $this->getRatingData());

		//Description
		$interface->assign('summDescription', $this->getDescriptionFast());
		$timer->logTime('Finished Loading Description');
		if ($this->hasCachedSeries()){
			$interface->assign('ajaxSeries', false);
			$interface->assign('summSeries', $this->getSeries());
		}else{
			$interface->assign('ajaxSeries', true);
		}
		$timer->logTime('Finished Loading Series');

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		$interface->assign('recordDriver', $this);

		return 'RecordDrivers/GroupedWork/suggestionEntry.tpl';
	}

	public function getScrollerTitle($index, $scrollerName){
		global $interface;
		$interface->assign('index', $index);
		$interface->assign('scrollerName', $scrollerName);
		$interface->assign('id', $this->getPermanentId());
		$interface->assign('title', $this->getTitle());
		$interface->assign('linkUrl', $this->getLinkUrl());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		$interface->assign('recordDriver', $this);

		return array(
				'id' => $this->getPermanentId(),
				'image' => $this->getBookcoverUrl('medium'),
				'title' => $this->getTitle(),
				'author' => $this->getPrimaryAuthor(),
				'formattedTitle' => $interface->fetch('RecordDrivers/GroupedWork/scroller-title.tpl')
		);
	}

	public function getLinkUrl($absolutePath = false){
		global $configArray;
		if ($absolutePath){
			return $configArray['Site']['url'] . '/GroupedWork/' . $this->getPermanentId() . '/Home';
		}else{
			return $configArray['Site']['path'] . '/GroupedWork/' . $this->getPermanentId() . '/Home';
		}

	}

	/**
	 * Get an XML RDF representation of the data in this record.
	 *
	 * @access  public
	 * @return  mixed               XML RDF data (false if unsupported or error).
	 */
	public function getRDFXML() {
		// TODO: Implement getRDFXML() method.
	}

	/**
	 * Get any reviews associated with this record.  For details of
	 * the return format, see sys/Reviews.php.
	 *
	 * @access  public
	 * @return  array               Review information.
	 */
	public function getReviews() {
		// TODO: Implement getReviews() method.
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public

	 * @param string $view The current view.
	 * @param boolean $useUnscopedHoldingsSummary Whether or not the result should show an unscoped holdings summary.
	 *
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult($view = 'list', $useUnscopedHoldingsSummary = false) {
		if ($view == 'covers') { // Displaying Results as bookcover tiles
			return $this->getBrowseResult();
		}

		// Displaying results as the default list
		global $configArray;
		global $interface;
		global $timer;

		$interface->assign('displayingSearchResults', true);
		$interface->assign('useUnscopedHoldingsSummary', $useUnscopedHoldingsSummary);

		$id = $this->getUniqueID();
		$timer->logTime("Starting to load search result for grouped work $id");
		$interface->assign('summId', $id);
		if (substr($id, 0, 1) == '.'){
			$interface->assign('summShortId', substr($id, 1));
		}else{
			$interface->assign('summShortId', $id);
		}
		$relatedManifestations = $this->getRelatedManifestations();
		$interface->assign('relatedManifestations', $relatedManifestations);
		$timer->logTime("Loaded related manifestations");

		//Build the link URL.
		//If there is only one record for the work we will link straight to that.
		$relatedRecords = $this->getRelatedRecords();
		$timer->logTime("Loaded related records");
		if (count($relatedRecords) == 1){
			$firstRecord = reset($relatedRecords);
			/** @var IndexRecord|OverDriveRecordDriver|BaseEContentDriver $driver */
			$driver = $firstRecord['driver'];
			$linkUrl = $driver->getLinkUrl();
		}else{
			$linkUrl = '/GroupedWork/' . $id . '/Home?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page='  . $interface->get_template_vars('page');
			if ($useUnscopedHoldingsSummary){
				$linkUrl .= '&amp;searchSource=marmot';
			}else{
				$linkUrl .= '&amp;searchSource=' . $interface->get_template_vars('searchSource');
			}
		}

		$interface->assign('summUrl', $linkUrl);
		$interface->assign('summTitle', $this->getTitle(true));
		$interface->assign('summSubTitle', $this->getSubtitle(true));
		$interface->assign('summAuthor', $this->getPrimaryAuthor(true));
		$isbn = $this->getCleanISBN();
		$interface->assign('summISBN', $isbn);
		$interface->assign('summFormats', $this->getFormats());
		$interface->assign('numRelatedRecords', count($relatedRecords));
		$timer->logTime("Finished assignment of main data");

		// Obtain and assign snippet (highlighting) information:
		$snippet = $this->getHighlightedSnippet();
		$interface->assign('summSnippetCaption', $snippet ? $snippet['caption'] : false);
		$interface->assign('summSnippet', $snippet ? $snippet['snippet'] : false);

		//Generate COinS URL for Zotero support
		$interface->assign('summCOinS', $this->getOpenURL());

		$summPublisher = null;
		$summPubDate = null;
		$summPhysicalDesc = null;
		$summEdition = null;
		$summLanguage = null;
		$isFirst = true;
		foreach ($relatedRecords as $relatedRecord){
			if ($isFirst){
				$summPublisher = $relatedRecord['publisher'];
				$summPubDate = $relatedRecord['publicationDate'];
				$summPhysicalDesc = $relatedRecord['physical'];
				$summEdition = $relatedRecord['edition'];
				$summLanguage = $relatedRecord['language'];
			}else{
				if ($summPublisher != $relatedRecord['publisher']){
					$summPublisher = null;
				}
				if ($summPubDate != $relatedRecord['publicationDate']){
					$summPubDate = null;
				}
				if ($summPhysicalDesc != $relatedRecord['physical']){
					$summPhysicalDesc = null;
				}
				if ($summEdition != $relatedRecord['edition']){
					$summEdition = null;
				}
				if ($summLanguage != $relatedRecord['language']){
					$summLanguage = null;
				}
			}
			$isFirst = false;
		}
		$interface->assign('summPublisher', $summPublisher);
		$interface->assign('summPubDate', $summPubDate);
		$interface->assign('summPhysicalDesc', $summPhysicalDesc);
		$interface->assign('summEdition', $summEdition);
		$interface->assign('summLanguage', $summLanguage);
		$timer->logTime("Finished assignment of data based on related records");

		if ($configArray['System']['debugSolr']){
			$interface->assign('summScore', $this->getScore());
			$interface->assign('summExplain', $this->getExplain());
		}
		$timer->logTime("Finished assignment of data based on solr debug info");

		//Get Rating
		$interface->assign('summRating', $this->getRatingData());
		$timer->logTime("Finished loading rating data");

		//Description
		$interface->assign('summDescription', $this->getDescriptionFast(true));
		$timer->logTime('Finished Loading Description');
		if ($this->hasCachedSeries()){
			$interface->assign('ajaxSeries', false);
			$interface->assign('summSeries', $this->getSeries(false));
		}else{
			$interface->assign('ajaxSeries', true);
			$interface->assign('summSeries', null);
		}
		$timer->logTime('Finished Loading Series');

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		// By default, do not display AJAX status; we won't assume that all
		// records exist in the ILS.  Child classes can override this setting
		// to turn on AJAX as needed:
		$interface->assign('summAjaxStatus', false);

		$interface->assign('recordDriver', $this);

		return 'RecordDrivers/GroupedWork/result.tpl';
	}

	public function getBrowseResult(){
		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);

		// if the grouped work consists of only 1 related item, return the record url, otherwise return the grouped-work url
		//Rather than loading all related records which can be slow, just get the count
		$numRelatedRecords = $this->getNumRelatedRecords();

		if ($numRelatedRecords == 1) {
			//Now that we know that we need more detailed information, load the related record.
			$relatedRecords = $this->getRelatedRecords(false);
			$onlyRecord = reset($relatedRecords);
			$url = $onlyRecord['url'];
		} else {
			$url = $this->getLinkUrl();
		}

		$interface->assign('summUrl', $url);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summSubTitle', $this->getSubtitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());

		//Get Rating
		$interface->assign('ratingData', $this->getRatingData());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
		// Rating Settings
		global $library, $location;
		$browseCategoryRatingsMode = null;
		if ($location) { // Try Location Setting
			$browseCategoryRatingsMode = $location->browseCategoryRatingsMode;
		}
		if (!$browseCategoryRatingsMode) { // Try Library Setting
			$browseCategoryRatingsMode = $library->browseCategoryRatingsMode;
		}
		if (!$browseCategoryRatingsMode) $browseCategoryRatingsMode = 'popup'; // default
		$interface->assign('browseCategoryRatingsMode', $browseCategoryRatingsMode);

		return 'RecordDrivers/GroupedWork/browse_result.tpl';
	}

	public function getListWidgetTitle(){
		$widgetTitleInfo = array(
				'id' => $this->getPermanentId(),
				'shortId' => $this->getPermanentId(),
				'recordtype' => 'grouped_work',
				'image' => $this->getBookcoverUrl('medium'),
				'small_image' => $this->getBookcoverUrl('small'),
				'title' => $this->getTitle(),
				'author' => $this->getPrimaryAuthor(),
				'description' => $this->getDescriptionFast(),
				'length' => '',
				'publisher' => '',
				'ratingData' => $this->getRatingData(),
		);
		return $widgetTitleInfo;
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getStaffView()
	{
		global $interface;

		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $this->getPermanentId();
		if ($groupedWork->find(true)){
			$groupedWorkDetails = array();
			$groupedWorkDetails['full_title'] = $groupedWork->full_title;
			$groupedWorkDetails['author'] = $groupedWork->author;
			$groupedWorkDetails['grouping_category'] = $groupedWork->grouping_category;
			$interface->assign('groupedWorkDetails', $groupedWorkDetails);
		}


		$fields = $this->fields;
		ksort($fields);
		$interface->assign('details', $fields);

		return 'RecordDrivers/GroupedWork/staff-view.tpl';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the Table of Contents extracted from the
	 * record.  Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getTOC() {
		$tableOfContents = array();
		foreach ($this->getRelatedRecords() as $record){
			$recordTOC = $record['driver']->getTOC();
			if (count($recordTOC) > 0){
				$editionDescription = "From the {$record['format']}";
				if ($record['edition']){
					$editionDescription .= " - {$record['edition']}";
				}
				$tableOfContents = array_merge($tableOfContents, array("<h4>From the $editionDescription</h4>"), $recordTOC);
			}
		}
		return $tableOfContents;
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getUniqueID() {
		return $this->fields['id'];
	}

	/**
	 * Does this record have audio content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasAudio() {
		// TODO: Implement hasAudio() method.
	}

	/**
	 * Does this record have an excerpt available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasExcerpt() {
		// TODO: Implement hasExcerpt() method.
	}

	/**
	 * Does this record have searchable full text in the index?
	 *
	 * Note: As of this writing, searchable full text is not a VuFind feature,
	 *       but this method will be useful if/when it is eventually added.
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasFullText() {
		// TODO: Implement hasFullText() method.
	}

	/**
	 * Does this record have image content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasImages() {
		// TODO: Implement hasImages() method.
	}

	/**
	 * Does this record support an RDF representation?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasRDF() {
		// TODO: Implement hasRDF() method.
	}

	/**
	 * Does this record have reviews available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasReviews() {
		// TODO: Implement hasReviews() method.
	}

	/**
	 * Does this record have a Table of Contents available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasTOC() {
		// TODO: Implement hasTOC() method.
	}

	/**
	 * Does this record have video content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasVideo() {
		// TODO: Implement hasVideo() method.
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle($useHighlighting = false) {
		// Don't check for highlighted values if highlighting is disabled:
		if ($this->highlight && $useHighlighting) {
			if (isset($this->fields['_highlighting']['title_display'][0])){
				return $this->fields['_highlighting']['title_display'][0];
			}else if (isset($this->fields['_highlighting']['title_full'][0])){
				return $this->fields['_highlighting']['title_full'][0];
			}
		}

		if (isset($this->fields['title_display'])){
			return $this->fields['title_display'];
		}else{
			if (isset($this->fields['title_full'])){
				if (is_array($this->fields['title_full'])){
					return reset($this->fields['title_full']);
				}else{
					return $this->fields['title_full'];
				}
			}else{
				return '';
			}
		}
	}

	/**
	 * Get the subtitle of the record.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function getSubtitle($useHighlighting = false)
	{
		// Don't check for highlighted values if highlighting is disabled:
		if ($useHighlighting) {
			if (isset($this->fields['_highlighting']['title_sub'][0])){
				return $this->fields['_highlighting']['title_sub'][0];
			}
		}
		return isset($this->fields['title_sub']) ?
				$this->fields['title_sub'] : '';
	}

	/**
	 * Get the authors of the work.
	 *
	 * @access  protected
	 * @return  string
	 */
	public function getAuthors()
	{
		return isset($this->fields['author']) ? $this->fields['author'] : null;
	}


	/**
	 * Get the main author of the record.
	 *
	 * @access  protected
	 *
	 * @return  string
	 */
	public function getPrimaryAuthor($useHighlighting = false)
	{
		// Don't check for highlighted values if highlighting is disabled:
		if ($this->highlight && $useHighlighting) {
			if (isset($this->fields['_highlighting']['author_display'][0])){
				return $this->fields['_highlighting']['author_display'][0];
			}else if (isset($this->fields['_highlighting']['author'][0])){
				return $this->fields['_highlighting']['author'][0];
			}
		}
		if (isset($this->fields['author_display'])){
			return $this->fields['author_display'];
		}else{
			return isset($this->fields['author']) ? $this->fields['author'] : '';
		}
	}

	public function getScore(){
		if (isset($this->fields['score'])){
			return $this->fields['score'];
		}
		return null;
	}

	public function getExplain(){
		if (isset($this->fields['explain'])){
			return nl2br(str_replace(' ', '&nbsp;', $this->fields['explain']));
		}
		return '';
	}

	private $fastDescription = null;
	function getDescriptionFast($useHighlighting = false){
		if ($this->fastDescription != null){
			return $this->fastDescription;
		}
		// Don't check for highlighted values if highlighting is disabled:
		if ($this->highlight && $useHighlighting) {
			if (isset($this->fields['_highlighting']['display_description'][0])){
				return $this->fields['_highlighting']['display_description'][0];
			}
		}
		if (isset($this->fields['display_description']) && strlen($this->fields['display_description']) > 0){
			$this->fastDescription = $this->fields['display_description'];
		}else{
			$relatedRecords = $this->getRelatedRecords(false);
			//Look for a description from a book in english
			foreach ($relatedRecords as $relatedRecord){
				$language = is_array($relatedRecord['language']) ? $relatedRecord['language'][0] : $relatedRecord['language'];
				if (($relatedRecord['format'] == 'Book' || $relatedRecord['format'] == 'eBook') && $language == 'English'){
					$fastDescription = $relatedRecord['driver']->getDescriptionFast();
					if ($fastDescription != null && strlen($fastDescription) > 0){
						$this->fastDescription = $fastDescription;
						return $this->fastDescription;
					}
				}
			}
			//Didn't get a description, get the description from the first record that isn't a book or ebook
			foreach ($relatedRecords as $relatedRecord){
				$language = is_array($relatedRecord['language']) ? $relatedRecord['language'][0] : $relatedRecord['language'];
				if (($relatedRecord['format'] != 'Book' && $relatedRecord['format'] != 'eBook') || !$language == 'English'){
					$fastDescription = $relatedRecord['driver']->getDescriptionFast();
					if ($fastDescription != null && strlen($fastDescription) > 0){
						$this->fastDescription =  $fastDescription;
					}
				}
			}
			$this->fastDescription =  '';
			return $this->fastDescription;
		}
		return $this->fastDescription;
	}

	function getDescription(){
		$description = null;
		$cleanIsbn = $this->getCleanISBN();
		if ($cleanIsbn != null && strlen($cleanIsbn) > 0){
			require_once ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php';
			$summaryInfo = GoDeeperData::getSummary($cleanIsbn, $this->getCleanUPC());
			if (isset($summaryInfo['summary'])){
				$description = $summaryInfo['summary'];
			}
		}
		if ($description == null){
			$description = $this->getDescriptionFast();
		}
		if ($description == null || strlen($description) == 0){
			$description = 'Description Not Provided';
		}
		return $description;
	}

	function getBookcoverUrl($size){
		global $configArray;
		$bookCoverUrl = $configArray['Site']['path'] . "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=grouped_work";

		if (isset($this->fields['format_category'])){
			if (is_array($this->fields['format_category'])){
				$bookCoverUrl .= "&category=" . reset($this->fields['format_category']);
			}else{
				$bookCoverUrl .= "&category=" . $this->fields['format_category'];
			}
		}

		return $bookCoverUrl;
	}

	function getQRCodeUrl(){
		global $configArray;
		return $configArray['Site']['url'] . '/qrcode.php?type=GroupedWork&id=' . $this->getPermanentId();
	}

	/**
	 * Get an array of all ISBNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getISBNs()
	{
		// If ISBN is in the index, it should automatically be an array... but if
		// it's not set at all, we should normalize the value to an empty array.
		if (isset($this->fields['isbn'])){
			if (is_array($this->fields['isbn'])){
				return $this->fields['isbn'];
			}else{
				return array($this->fields['isbn']);
			}
		}else{
			return array();
		}
	}

	/** Get all ISBNs that are unique to this work */
	public function getUniqueISBNs(){
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkIdentifierRef.php';

	}

	/**
	 * Return the first valid ISBN found in the record (favoring ISBN-10 over
	 * ISBN-13 when possible).
	 *
	 * @return  mixed
	 */
	public function getCleanISBN()
	{
		require_once ROOT_DIR . '/sys/ISBN.php';

		//Check to see if we already have NovelistData loaded with a primary ISBN
		require_once ROOT_DIR . '/sys/Novelist/NovelistData.php';
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $this->getPermanentId();
		if (!isset($_REQUEST['reload']) && $this->getPermanentId() != null && $this->getPermanentId() != '' && $novelistData->find(true) && $novelistData->primaryISBN != null){
			return $novelistData->primaryISBN;
		}else{
			// Get all the ISBNs and initialize the return value:
			$isbns = $this->getISBNs();
			$isbn10 = false;

			// Loop through the ISBNs:
			foreach($isbns as $isbn) {
				// If we find an ISBN-13, return it immediately; otherwise, if we find
				// an ISBN-10, save it if it is the first one encountered.
				$isbnObj = new ISBN($isbn);
				if ($isbnObj->isValid()){
					if ($isbn13  = $isbnObj->get13()) {
						return $isbn13;
					}
					if (!$isbn10) {
						$isbn10 = $isbnObj->get10();
					}
				}
			}
			return $isbn10;
		}
	}

	/**
	 * Get an array of all ISBNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getISSNs()
	{
		// If ISBN is in the index, it should automatically be an array... but if
		// it's not set at all, we should normalize the value to an empty array.
		if (isset($this->fields['issn'])){
			if (is_array($this->fields['issn'])){
				return $this->fields['issn'];
			}else{
				return array($this->fields['issn']);
			}
		}else{
			return array();
		}
	}

	/**
	 * Get the UPC associated with the record (may be empty).
	 *
	 * @return  array
	 */
	public function getUPCs()
	{
		// If UPCs is in the index, it should automatically be an array... but if
		// it's not set at all, we should normalize the value to an empty array.
		if (isset($this->fields['upc'])){
			if (is_array($this->fields['upc'])){
				return $this->fields['upc'];
			}else{
				return array($this->fields['upc']);
			}
		}else{
			return array();
		}
	}

	public function getCleanUPC(){
		$upcs = $this->getUPCs();
		if (empty($upcs)) {
			return false;
		}
		$upc = $upcs[0];
		if ($pos = strpos($upc, ' ')) {
			$upc = substr($upc, 0, $pos);
		}
		return $upc;
	}

	private function getNumRelatedRecords() {
		global $solrScope;

		$relatedRecordFieldName = 'related_record_ids';
		if ($solrScope){
			if (isset($this->fields["related_record_ids_$solrScope"])){
				$relatedRecordFieldName = "related_record_ids_$solrScope";
			}
		}
		if (isset($this->fields[$relatedRecordFieldName])){
			return count($this->fields[$relatedRecordFieldName]);
		}else{
			return 0;
		}
	}

	private $relatedRecords = null;

	/**
	 * The vast majority of record information is stored within the index.
	 * This routine parses the information from the index and restructures it for use within the user interface.
	 *
	 * @param bool $realTimeStatusNeeded
	 * @return array|null
	 */
	public function getRelatedRecords($realTimeStatusNeeded = true) {
		global $timer;
		if ($this->relatedRecords == null || isset($_REQUEST['reload'])){
			$timer->logTime("Starting to load related records for {$this->getUniqueID()}");

			global $solrScope;
			global $library;
			global $user;

			$searchLocation = Location::getSearchLocation();
			$activePTypes = array();
			if ($user){
				$activePTypes = array_merge($activePTypes, $user->getRelatedPTypes());
			}
			if ($searchLocation){
				$activePTypes[$searchLocation->defaultPType] = $searchLocation->defaultPType;
			}
			if ($library){
				$activePTypes[$library->defaultPType] = $library->defaultPType;
			}

			//First load scoping information from the index.  This is stored as multiple values
			//within the scoping details field for the scope.
			//Each field is
			$scopingInfoFieldName = 'scoping_details_' . $solrScope;
			$scopingInfo = array();
			$validRecordIds = array();
			$validItemIds = array();
			if (isset($this->fields[$scopingInfoFieldName])) {
				$scopingInfoRaw = $this->fields[$scopingInfoFieldName];
				if (!is_array($scopingInfoRaw)) {
					$scopingInfoRaw = array($scopingInfoRaw);
				}
				foreach ($scopingInfoRaw as $tmpItem){
					$scopingDetails = explode('|', $tmpItem);
					$scopeKey = $scopingDetails[0] . ':' . ($scopingDetails[1] == 'null' ? '' : $scopingDetails[1]);
					$scopingInfo[$scopeKey] = $scopingDetails;
					$validRecordIds[] = $scopingDetails[0];
					$validItemIds[] = $scopeKey;
				}
			}
			$timer->logTime("Loaded Scoping Details from the index");

			//Get related records from the index filtered according to
			$relatedRecordFieldName = "record_details";
			$recordsFromIndex = array();
			if (isset($this->fields[$relatedRecordFieldName])) {
				$relatedRecordIdsRaw = $this->fields[$relatedRecordFieldName];
				if (!is_array($relatedRecordIdsRaw)) {
					$relatedRecordIdsRaw = array($relatedRecordIdsRaw);
				}
				foreach ($relatedRecordIdsRaw as $tmpItem){
					$recordDetails = explode('|', $tmpItem);
					//Check to see if the record is valid
					if (in_array($recordDetails[0], $validRecordIds)){
						$recordsFromIndex[$recordDetails[0]] = $recordDetails;
					}
				}
			}
			$timer->logTime("Loaded Record Details from the index");

			//Get a list of related items filtered according to scoping
			$relatedItemsFieldName = 'item_details';
			$itemsFromIndex = array();
			if (isset($this->fields[$relatedItemsFieldName])) {
				$itemsFromIndexRaw = $this->fields[$relatedItemsFieldName];
				if (!is_array($itemsFromIndexRaw)) {
					$itemsFromIndexRaw = array($itemsFromIndexRaw);
				}
				foreach ($itemsFromIndexRaw as $tmpItem) {
					$itemDetails = explode('|', $tmpItem);
					$itemIdentifier = $itemDetails[0] . ':' . $itemDetails[1];
					if (in_array($itemIdentifier, $validItemIds)) {
						$itemsFromIndex[] = $itemDetails;
					}
				}
			}
			$timer->logTime("Loaded Item Details from the index");


			//Generate record information based on the information we have in the index
			$relatedRecords = array();
			foreach ($recordsFromIndex as $recordDetails){
				list($source, $id) = explode(':', $recordDetails[0], 2);
				require_once ROOT_DIR . '/RecordDrivers/Factory.php';
				$recordDriver = RecordDriverFactory::initRecordDriverById($recordDetails[0]);
				$timer->logTime("Loaded Record Driver for  $recordDetails[0]");

				//Setup the base record
				$relatedRecord = array(
					'id' => $recordDetails[0],
					'driver' => $recordDriver,
					'url' => $recordDriver->getRecordUrl(),
					'format' => $recordDetails[1],
					'formatCategory' => $recordDetails[2],
					'edition' => $recordDetails[3],
					'language' => $recordDetails[4],
					'publisher' => $recordDetails[5],
					'publicationDate' => $recordDetails[6],
					'physical' => $recordDetails[7],
					'callNumber' => '',
					'available' => false,
					'availableOnline' => false,
					'availableLocally' => false,
					'availableHere' => false,
					'inLibraryUseOnly' => true,
					'isEContent' => false,
					'availableCopies' => 0,
					'copies' => 0,
					'onOrderCopies' => 0,
					'localAvailableCopies' => 0,
					'localCopies' => 0,
					'numHolds' => 0,
					'hasLocalItem' => false,
					'holdRatio' => 0,
					'locationLabel' => '',
					'shelfLocation' => '',
					'bookable' => false,
					'holdable' => false,
					'itemSummary' => array(),
					'groupedStatus' => 'Currently Unavailable',
					'source' => $source,
					'actions' => array()
				);
				$timer->logTime("Setup base related record");

				//Process the items for the record and add additional information as needed
				$localShelfLocation = null;
				$libraryShelfLocation = null;
				$localCallNumber = null;
				$libraryCallNumber = null;
				$relatedUrls = array();

				$recordAvailable = false;
				$recordHoldable = false;
				$recordBookable = false;

				foreach ($itemsFromIndex as $curItem){
					if ($curItem[0] == $recordDetails[0]){
						$shelfLocation = $curItem[2];
						$callNumber = $curItem[3];
						$numCopies = $curItem[6];
						$isOrderItem = $curItem[7] == 'true';
						$isEcontent = $curItem[8] == 'true';
						if ($isEcontent){
							$relatedUrls[] = array(
								'source' => $curItem[9],
								'file' => $curItem[10],
								'url' => $curItem[11]
							);
							$relatedRecord['eContentSource'] = $curItem[9];
							$relatedRecord['isEContent'] = true;
						}
						//Get Scoping information for this record
						$scopeKey = $curItem[0] . ':' . ($curItem[1] == 'null' ? '' : $curItem[1]);
						$scopingDetails = $scopingInfo[$scopeKey];
						$groupedStatus = $scopingDetails[2];
						$status = $scopingDetails[3];
						$locallyOwned = $scopingDetails[4] == 'true';
						$available = $scopingDetails[5] == 'true';
						if ($available) $recordAvailable = true;
						$holdable = $scopingDetails[6] == 'true';
						$bookable = $scopingDetails[7] == 'true';
						$inLibraryUseOnly = $scopingDetails[8] == 'true';
						$libraryOwned = $scopingDetails[9] == 'true';
						$holdablePTypes = isset($scopingDetails[10]) ? $scopingDetails[10] : '';
						$bookablePTypes = isset($scopingDetails[11]) ? $scopingDetails[11] : '';
						if (strlen($holdablePTypes) > 0 && $holdablePTypes != '999'){
							$holdablePTypes = explode(',', $holdablePTypes);
							$matchingPTypes = array_intersect($holdablePTypes, $activePTypes);
							if (count($matchingPTypes) == 0){
								$holdable = false;
							}
						}
						if ($holdable) $recordHoldable = true;

						if (strlen($bookablePTypes) > 0 && $bookablePTypes != '999'){
							$bookablePTypes = explode(',', $bookablePTypes);
							$matchingPTypes = array_intersect($bookablePTypes, $activePTypes);
							if (count($matchingPTypes) == 0){
								$bookable = false;
							}
						}
						if ($bookable) $recordBookable = true;

						//Update the record with information from the item and from scoping.
						$displayByDefault = false;
						if ($available){
							if ($isEcontent){
								$relatedRecord['availableOnline'] = true;
							}else{
								$relatedRecord['available'] = true;
							}
							$relatedRecord['availableCopies'] += $numCopies;
							if ($searchLocation){
								$displayByDefault = $locallyOwned || $isEcontent;
							}elseif ($library){
								$displayByDefault = $libraryOwned || $isEcontent;
							}
						}
						if ($isOrderItem){
							$relatedRecord['onOrderCopies'] += $numCopies;
						}else{
							$relatedRecord['copies'] += $numCopies;
						}
						if (!$inLibraryUseOnly){
							$relatedRecord['inLibraryUseOnly'] = false;
						}
						if ($holdable){
							$relatedRecord['holdable'] = true;
						}
						if ($bookable){
							$relatedRecord['bookable'] = true;
						}
						$relatedRecord['groupedStatus'] = GroupedWorkDriver::keepBestGroupedStatus($relatedRecord['groupedStatus'], $groupedStatus);
						$description = $shelfLocation . ':' . $callNumber;
						if ($locallyOwned){
							if ($localShelfLocation == null) $localShelfLocation = $shelfLocation;
							if ($localCallNumber == null) $localCallNumber = $callNumber;
							if ($available && !$isEcontent){
								$relatedRecord['availableHere'] = true;
								$relatedRecord['availableLocally'] = true;
							}
							$relatedRecord['localCopies'] += $numCopies;
							$relatedRecord['hasLocalItem'] = true;
							$key = '1 ' . $description;
						}elseif ($libraryOwned){
							if ($libraryShelfLocation == null) $libraryShelfLocation = $shelfLocation;
							if ($libraryCallNumber == null) $libraryCallNumber = $callNumber;
							if ($available && !$isEcontent){
								$relatedRecord['availableLocally'] = true;
							}
							$relatedRecord['localCopies'] += $numCopies;
							if ($searchLocation == null || $isEcontent){
								$relatedRecord['hasLocalItem'] = true;
							}
							$key = '2 ' . $description;
						}else{
							$key = '3 ' . $description;
						}

						//Add the item to the item summary
						$itemSummaryInfo = array(
							'description' => $description,
							'shelfLocation' => $shelfLocation,
							'callNumber' => $callNumber,
							'totalCopies' => 1,
							'availableCopies' => ($available && !$isOrderItem) ? $numCopies : 0,
							'isLocalItem' => $locallyOwned,
							'isLibraryItem' => $libraryOwned,
							'inLibraryUseOnly' => $inLibraryUseOnly,
							'displayByDefault' => $displayByDefault,
							'onOrderCopies' => $isOrderItem ? $numCopies : 0,
							'status' => $groupedStatus,
							'statusFull' => $status,
							'available' => $available,
							'holdable' => $holdable,
						);
						if (isset($relatedRecord['itemSummary'][$key])){
							$relatedRecord['itemSummary'][$key]['totalCopies']++;
							$relatedRecord['itemSummary'][$key]['availableCopies']+=$itemSummaryInfo['availableCopies'];
							if ($itemSummaryInfo['displayByDefault']){
								$relatedRecord['itemSummary'][$key]['displayByDefault'] = true;
							}
							$relatedRecord['itemSummary'][$key]['onOrderCopies']+=$itemSummaryInfo['onOrderCopies'];
						}else{
							$relatedRecord['itemSummary'][$key] = $itemSummaryInfo;
						}
					}
				}
				if ($localShelfLocation != null){
					$relatedRecord['shelfLocation'] = $localShelfLocation;
				}elseif($libraryShelfLocation != null){
					$relatedRecord['shelfLocation'] = $libraryShelfLocation;
				}
				if ($localCallNumber != null){
					$relatedRecord['callNumber'] = $localCallNumber;
				}elseif($libraryCallNumber != null){
					$relatedRecord['callNumber'] = $libraryCallNumber;
				}
				ksort($relatedRecord['itemSummary']);
				$timer->logTime("Setup record items");

				$relatedRecord['actions'] = $recordDriver->getRecordActions($recordAvailable, $recordHoldable, $recordBookable, $relatedUrls);
				$timer->logTime("Loaded actions");
				$relatedRecords[] = $relatedRecord;
			}

				//Sort the records based on format and then edition
				usort($relatedRecords, array($this, "compareRelatedRecords"));

			$this->relatedRecords = $relatedRecords;
		}
		$timer->logTime("Finished loading related records {$this->getUniqueID()}");
		return $this->relatedRecords;
	}

	public function getRelatedManifestations() {
		global $timer;
		$timer->logTime("Starting to load related records in getRelatedManifestations");
		$relatedRecords = $this->getRelatedRecords();
		$timer->logTime("Finished loading related records in getRelatedManifestations");
		//Group the records based on format
		$relatedManifestations = array();
		foreach ($relatedRecords as $curRecord){
			if (!array_key_exists($curRecord['format'], $relatedManifestations)){
				$relatedManifestations[$curRecord['format']] = array(
					'format' => $curRecord['format'],
					'formatCategory' => $curRecord['formatCategory'],
					'copies' => 0,
					'availableCopies' => 0,
					'localCopies' => 0,
					'localAvailableCopies' => 0,
					'onOrderCopies' => 0,
					'numHolds' => 0,
					'available' => false,
					'hasLocalItem' => false,
					'isEContent' => false,
					'relatedRecords' => array(),
					'preferredEdition' => null,
					'statusMessage' => '',
					'itemLocations' => array(),
					'availableLocally' => false,
					'availableOnline' => false,
					'availableHere' => false,
					'inLibraryUseOnly' => false,
					'allLibraryUseOnly' => true,
					'hideByDefault' => false,
					'itemSummary' => array(),
					'itemSummaryLocal' => array(),
					'groupedStatus' => ''
				);
			}
			if (isset($curRecord['availableLocally']) && $curRecord['availableLocally'] == true){
				$relatedManifestations[$curRecord['format']]['availableLocally'] = true;
			}
			if (isset($curRecord['availableHere']) && $curRecord['availableHere'] == true){
				$relatedManifestations[$curRecord['format']]['availableHere'] = true;
			}
			if ($curRecord['available'] && $curRecord['locationLabel'] === 'Online'){
				$relatedManifestations[$curRecord['format']]['availableOnline'] = true;
			}
			if (isset($curRecord['availableOnline']) && $curRecord['availableOnline']){
				$relatedManifestations[$curRecord['format']]['availableOnline'] = true;
			}
			if (isset($curRecord['isEContent']) && $curRecord['isEContent']){
				$relatedManifestations[$curRecord['format']]['isEContent'] = true;
			}
			if (!$relatedManifestations[$curRecord['format']]['available'] && $curRecord['available']){
				$relatedManifestations[$curRecord['format']]['available'] = $curRecord['available'];
			}
			if (isset($curRecord['inLibraryUseOnly']) && $curRecord['inLibraryUseOnly']){
				$relatedManifestations[$curRecord['format']]['inLibraryUseOnly'] = $curRecord['inLibraryUseOnly'];
			}else{
				$relatedManifestations[$curRecord['format']]['allLibraryUseOnly'] = false;
			}
			if (!$relatedManifestations[$curRecord['format']]['hasLocalItem'] && $curRecord['hasLocalItem']){
				$relatedManifestations[$curRecord['format']]['hasLocalItem'] = $curRecord['hasLocalItem'];
			}
			if ($curRecord['shelfLocation']){
				$relatedManifestations[$curRecord['format']]['shelfLocation'][$curRecord['shelfLocation']] = $curRecord['shelfLocation'];
			}
			if ($curRecord['callNumber']){
				$relatedManifestations[$curRecord['format']]['callNumber'][$curRecord['callNumber']] = $curRecord['callNumber'];
			}
			$relatedManifestations[$curRecord['format']]['relatedRecords'][] = $curRecord;
			$relatedManifestations[$curRecord['format']]['copies'] += $curRecord['copies'];
			$relatedManifestations[$curRecord['format']]['availableCopies'] += $curRecord['availableCopies'];
			if ($curRecord['hasLocalItem']){
				$relatedManifestations[$curRecord['format']]['localCopies'] += (isset($curRecord['localCopies']) ? $curRecord['localCopies'] : 0);
				$relatedManifestations[$curRecord['format']]['localAvailableCopies'] += (isset($curRecord['localAvailableCopies']) ? $curRecord['localAvailableCopies'] : 0);
			}
			if (isset($curRecord['itemSummary'])){
				$relatedManifestations[$curRecord['format']]['itemSummary'] = $this->mergeItemSummary($relatedManifestations[$curRecord['format']]['itemSummary'], $curRecord['itemSummary']);
			}
			if ($curRecord['numHolds']){
				$relatedManifestations[$curRecord['format']]['numHolds'] += $curRecord['numHolds'];
			}
			if (isset($curRecord['onOrderCopies'])){
				$relatedManifestations[$curRecord['format']]['onOrderCopies'] += $curRecord['onOrderCopies'];
			}
			$statusRankings = array(
				'currently unavailable' => 1,
				'on order' => 2,
				'coming soon' => 3,
				'checked out' => 4,
				'library use only' => 5,
				'available online' => 6,
				'on shelf' => 7
			);
			if (isset($curRecord['groupedStatus']) && $curRecord['groupedStatus'] != ''){
				$groupedStatus = strtolower($relatedManifestations[$curRecord['format']]['groupedStatus']);

				//Check to see if we have a better status here
				if (array_key_exists(strtolower($curRecord['groupedStatus']), $statusRankings)){
					if ($groupedStatus == ''){
						$groupedStatus = $curRecord['groupedStatus'];
						//Check to see if we are getting a better status
					}elseif ($statusRankings[strtolower($curRecord['groupedStatus'])] > $statusRankings[$groupedStatus]){
						$groupedStatus = $curRecord['groupedStatus'];
					}
					$relatedManifestations[$curRecord['format']]['groupedStatus'] = $groupedStatus;
				}
			}
		}
		$timer->logTime("Finished initial processing of related records");

		//Check to see if we have applied a format or format category facet
		$selectedFormat = null;
		$selectedFormatCategory = null;
		$selectedAvailability = null;
		if (isset($_REQUEST['filter'])){
			foreach ($_REQUEST['filter'] as $filter){
				if (preg_match('/^format_category(?:\w*):"?(.+?)"?$/', $filter, $matches)){
					$selectedFormatCategory = urldecode($matches[1]);
				}elseif (preg_match('/^format(?:\w*):"?(.+?)"?$/', $filter, $matches)){
					$selectedFormat = urldecode($matches[1]);
				}elseif (preg_match('/^availability_toggle(?:\w*):"?(.+?)"?$/', $filter, $matches)){
					$selectedAvailability = urldecode($matches[1]);
				}elseif (preg_match('/^availability_by_format(?:[\w_]*):"?(.+?)"?$/', $filter, $matches)){
					$selectedAvailability = urldecode($matches[1]);
				}
			}
		}

		//Check to see what we need to do for actions, and determine if the record should be hidden by default
		foreach ($relatedManifestations as $key => $manifestation){
			$manifestation['numRelatedRecords'] = count($manifestation['relatedRecords']);
			if (count($manifestation['relatedRecords']) == 1){
				$firstRecord = reset($manifestation['relatedRecords']);
				$manifestation['url'] = $firstRecord['url'];
				$manifestation['actions'] = $firstRecord['actions'];
			}else{
				//Figure out what the preferred record is to place a hold on.  Since sorting has been done properly, this should always be the first
				$bestRecord = reset($manifestation['relatedRecords']);
				$manifestation['actions'] = $bestRecord['actions'];
			}
			if ($selectedFormat && $selectedFormat != $manifestation['format']){
				//Do a secondary check to see if we have a more detailed format in the facet
				$detailedFormat = mapValue('format_by_detailed_format', $selectedFormat);
				//Also check the reverse
				$detailedFormat2 = mapValue('format_by_detailed_format', $manifestation['format']);
				if ($manifestation['format'] != $detailedFormat && $detailedFormat2 != $selectedFormat){
					$manifestation['hideByDefault'] = true;
				}
			}
			if ($selectedFormatCategory && $selectedFormatCategory != $manifestation['formatCategory']){
				$manifestation['hideByDefault'] = true;
			}
			if ($selectedAvailability == 'Available Now' && !($manifestation['availableLocally'] || $manifestation['availableOnline'])){
				$manifestation['hideByDefault'] = true;
			}elseif($selectedAvailability == 'Entire Collection' && (!($manifestation['hasLocalItem']) && !$manifestation['isEContent'])){
				$manifestation['hideByDefault'] = true;
			}

			$relatedManifestations[$key] = $manifestation;
		}
		$timer->logTime("Finished loading related manifestations");

		return $relatedManifestations;
	}

	function compareRelatedRecords($a, $b){
		//Get literary form to determine if we should compare editions
		$literaryForm = '';
		if (isset($this->fields['literary_form'])){
			if (is_array($this->fields['literary_form'])){
				$literaryForm = reset($this->fields['literary_form']);
			}else{
				$literaryForm = $this->fields['literary_form'];
			}
		}
		//First sort by format
		$format1 = $a['format'];
		$format2 = $b['format'];
		$formatComparison = strcasecmp($format1, $format2);
		//Make sure that book is the very first format always
		if ($formatComparison != 0){
			if ($format1 == 'Book'){
				return -1;
			}elseif($format2 == 'Book'){
				return 1;
			}
		}
		if ($formatComparison == 0){
			//1) Put anything that is holdable first
			$holdabilityComparison = GroupedWorkDriver::compareHoldability($a, $b);
			if ($holdabilityComparison == 0) {
				//2) Compare by language to put english titles before spanish by default
				$languageComparison = GroupedWorkDriver::compareLanguagesForRecords($a, $b);
				if ($languageComparison == 0) {
					//3) Compare editions for non-fiction if available
					$editionComparisonResult = GroupedWorkDriver::compareEditionsForRecords($literaryForm, $a, $b);
					if ($editionComparisonResult == 0) {
						//4) Put anything with locally available items first
						$localAvailableItemComparisonResult = GroupedWorkDriver::compareLocalAvailableItemsForRecords($a, $b);
						if ($localAvailableItemComparisonResult == 0) {
							//5) Anything that is available elsewhere goes higher
							$availabilityComparisonResults = GroupedWorkDriver::compareAvailabilityForRecords($a, $b);
							if ($availabilityComparisonResults == 0) {
								//6) Put anything with a local copy higher
								$localItemComparisonResult = GroupedWorkDriver::compareLocalItemsForRecords($a, $b);
								if ($localItemComparisonResult == 0) {
									//7) All else being equal, sort by hold ratio
									if ($a['holdRatio'] == $b['holdRatio']) {
										//Hold Ratio is the same, last thing to check is the number of copies
										if ($a['copies'] == $b['copies']) {
											return 0;
										} elseif ($a['copies'] > $b['copies']) {
											return -1;
										} else {
											return 1;
										}
									} elseif ($a['holdRatio'] > $b['holdRatio']) {
										return -1;
									} else {
										return 1;
									}
								} else {
									return $localItemComparisonResult;
								}
							} else {
								return $availabilityComparisonResults;
							}
						} else {
							return $localAvailableItemComparisonResult;
						}
					} else {
						return $editionComparisonResult;
					}
				} else {
					return $languageComparison;
				}
			} else {
				return $holdabilityComparison;
			}
		}else {
			return $formatComparison;
		}
	}

	static function compareHoldability($a, $b){
		if ($a['holdable'] == $b['holdable']){
			return 0;
		}else if ($a['holdable']){
			return -1;
		}else{
			return 1;
		}
	}
	static function compareLanguagesForRecords($a, $b){
		$aHasEnglish = false;
		if (is_array($a['language'])){
			$languageA = strtolower(reset($a['language']));
			foreach ($a['language'] as $language){
				if (strcasecmp('english', $language) == 0){
					$aHasEnglish = true;
					break;
				}
			}
		}else{
			$languageA = strtolower($a['language']);
			if (strcasecmp('english', $languageA) == 0){
				$aHasEnglish = true;
			}
		}
		$bHasEnglish = false;
		if (is_array($b['language'])){
			$languageB = strtolower(reset($b['language']));
			foreach ($b['language'] as $language){
				if (strcasecmp('english', $language) == 0){
					$bHasEnglish = true;
					break;
				}
			}
		}else{
			$languageB = strtolower($b['language']);
			if (strcasecmp('english', $languageB) == 0){
				$bHasEnglish = true;
			}
		}
		if ($aHasEnglish && $bHasEnglish){
			return 0;
		}else{
			if ($aHasEnglish){
				return -1;
			}else if ($bHasEnglish){
				return 1;
			}else{
				return -strcmp($languageA, $languageB);
			}
		}
	}

	static function compareEditionsForRecords($literaryForm, $a, $b){
		//We only want to compare editions if the work is non-fiction
		if ($literaryForm == 'Non Fiction'){
			$editionA = GroupedWorkDriver::normalizeEdition($a['edition']);
			$editionB = GroupedWorkDriver::normalizeEdition($b['edition']);
			if ($editionA == $editionB){
				return 0;
			}else if ($editionA > $editionB){
				return -1;
			}else{
				return 1;
			}
		}
		return 0;
	}

	static function compareAvailabilityForRecords($a, $b){
		$availableLocallyA = isset($a['availableLocally']) && $a['availableLocally'];
		$availableLocallyB = isset($b['availableLocally']) && $b['availableLocally'];
		if (($availableLocallyA == $availableLocallyB)){
			$availableA = isset($a['available']) && $a['available'] && $a['holdable'];
			$availableB = isset($b['available']) && $b['available'] && $b['holdable'];
			if (($availableA == $availableB)){
				return 0;
			}elseif ($availableA){
				return -1;
			}else{
				return 1;
			}
		}else if ($availableLocallyA){
			return -1;
		}else{
			return 1;
		}
	}

	static function compareLocalAvailableItemsForRecords($a, $b){
		if (($a['availableLocally'] || $a['availableOnline']) && ($b['availableLocally'] || $b['availableOnline'])){
			return 0;
		}elseif ($a['availableLocally'] || $a['availableOnline']){
			return -1;
		}else{
			return 0;
		}
	}

	static function compareLocalItemsForRecords($a, $b){
		if ($a['hasLocalItem'] && $b['hasLocalItem']){
			return 0;
		}elseif ($a['hasLocalItem']){
			return -1;
		}else{
			return 0;
		}
	}

	public function getIndexedSeries(){
		return $this->fields['series'];
	}

	public function hasCachedSeries(){
		//Get a list of isbns from the record
		$novelist = NovelistFactory::getNovelist();
		return $novelist->doesGroupedWorkHaveCachedSeries($this->getPermanentId());
	}

	public function getSeries($allowReload = true){
		//Get a list of isbns from the record
		$relatedIsbns = $this->getISBNs();
		$novelist = NovelistFactory::getNovelist();
		$novelistData = $novelist->loadBasicEnrichment($this->getPermanentId(), $relatedIsbns, $allowReload);
		if ($novelistData != null && isset($novelistData->seriesTitle)){
			return array(
				'seriesTitle' => $novelistData->seriesTitle,
				'volume' => $novelistData->volume,
				'fromNovelist' => true,
			);
		}
		return null;
	}

	public function getFormats() {
		if (isset($this->fields['format'])){
			$formats = $this->fields['format'];
			if (is_array($formats)){
				natcasesort($formats);
				return implode(", ", $formats);
			}else{
				return $formats;
			}
		}else{
			return "Unknown";
		}
	}

	public function getFormatsArray() {
		if (isset($this->fields['format'])){
			$formats = $this->fields['format'];
			if (is_array($formats)){
				return $formats;
			}else{
				return array($formats);
			}
		}else{
			return array();
		}
	}

	public function getFormatCategory(){
		if (isset($this->fields['format_category'])){
			if (is_array($this->fields['format_category'])){
				return reset($this->fields['format_category']);
			}else{
				return $this->fields['format_category'];
			}
		}
		return "";
	}

	public function loadEnrichment() {
		$isbn = $this->getCleanISBN();
		$enrichment = array();
		if ($isbn == null || strlen($isbn) == 0){
			return $enrichment;
		}
		$novelist = NovelistFactory::getNovelist();;
		$enrichment['novelist'] = $novelist->loadEnrichment($this->getPermanentId(), $this->getISBNs());
		return $enrichment;
	}

	public function getUserReviews(){
		$reviews = array();

		// Determine if we should censor bad words or hide the comment completely.
		$censorWords = true;
		global $library;
		if (isset($library)) $censorWords = !$library->hideCommentsWithBadWords; // censor if not hiding
		require_once(ROOT_DIR . '/Drivers/marmot_inc/BadWord.php');
		$badWords = new BadWord();

		// Get the Reviews
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$userReview = new UserWorkReview();
		$userReview->groupedRecordPermanentId = $this->getUniqueID();
		$joinUser = new User();
		$userReview->joinAdd($joinUser);
		$userReview->find();
		while ($userReview->fetch()){
			// Set the display Name for the review
			if (!$userReview->displayName){
				if (strlen(trim($userReview->firstname)) >= 1){
					$userReview->displayName = substr($userReview->firstname, 0, 1) . '. ' . $userReview->lastname;
				}else{
					$userReview->displayName = $userReview->lastname;
				}
			}

			// Clean-up User Review Text
			if ($userReview->review) { // if the review has content to check
				if ($censorWords) { // replace bad words
					$userReview->review = $badWords->censorBadWords($userReview->review);
				} else { // skip reviews with bad words
					if ($badWords->hasBadWords($userReview->review)) continue;
				}
			}

			$reviews[] = clone $userReview;
		}
		return $reviews;
	}

	public function getRatingData() {
		require_once ROOT_DIR . '/services/API/WorkAPI.php';
		$workAPI = new WorkAPI();
		return $workAPI->getRatingData($this->getPermanentId());
	}

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();

		$tableOfContents = $this->getTOC();
		$interface->assign('tableOfContents', $tableOfContents);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);
		$moreDetailsOptions['details'] = array(
			'label' => 'Details',
			'body' => $interface->fetch('GroupedWork/view-title-details.tpl'),
		);
		$moreDetailsOptions['subjects'] = array(
				'label' => 'Subjects',
				'body' => $interface->fetch('GroupedWork/view-subjects.tpl'),
		);
		if ($interface->getVariable('showStaffView')){
			$moreDetailsOptions['staff'] = array(
				'label' => 'Staff View',
				'body' => $interface->fetch($this->getStaffView()),
			);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getTags(){
		global $user;
		/** @var UserTag[] $tags */
		$tags = array();
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserTag.php';
		$userTags = new UserTag();
		$userTags->groupedRecordPermanentId = $this->getPermanentId();
		$userTags->find();
		while ($userTags->fetch()){
			if (!isset($tags[$userTags->tag])){
				$tags[$userTags->tag] = clone $userTags;
				$tags[$userTags->tag]->userAddedThis = false;
			}
			$tags[$userTags->tag]->cnt++;
			if (!$user){
				return false;
			}else{
				if ($user->id == $tags[$userTags->tag]->userId){
					$tags[$userTags->tag]->userAddedThis = true;
				}
			}
		}
		return $tags;
	}

	public function getAcceleratedReaderData(){
		$hasArData = false;
		$arData = array();
		if ($this->fields['accelerated_reader_point_value'] > 0){
			$arData['pointValue'] = $this->fields['accelerated_reader_point_value'];
			$hasArData = true;
		}
		if ($this->fields['accelerated_reader_reading_level'] > 0){
			$arData['readingLevel'] = $this->fields['accelerated_reader_reading_level'];
			$hasArData = true;
		}
		if ($this->fields['accelerated_reader_interest_level'] > 0){
			$arData['interestLevel'] = $this->fields['accelerated_reader_interest_level'];
			$hasArData = true;
		}

		if ($hasArData){
			return $arData;
		}else{
			return null;
		}
	}

	public function getLexileCode(){
		return isset($this->fields['lexile_code']) ? $this->fields['lexile_code'] : null;
	}
	public function getLexileScore(){
		if (isset($this->fields['lexile_score'])){
			if ($this->fields['lexile_score'] > 0){
				return $this->fields['lexile_score'];
			}
		}
		return null;
	}
	public function getSubjects(){
		if (isset($this->fields['topic_facet'])){
			$subjects = $this->fields['topic_facet'];
			asort($subjects);
			return $subjects;
		}else{
			return null;
		}
	}

	private function mergeItemSummary($localCopies, $itemSummary) {
		foreach ($itemSummary as $key => $item){
			if (isset($localCopies[$key])){
				$localCopies[$key]['totalCopies'] += $item['totalCopies'];
				$localCopies[$key]['availableCopies'] += $item['availableCopies'];
				if ($item['displayByDefault']){
					$localCopies[$key]['displayByDefault'] = true;
				}
			}else{
				$localCopies[$key] = $item;
			}
		}
		ksort($localCopies);
		return $localCopies;
	}

	/**
	 * Get the OpenURL parameters to represent this record (useful for the
	 * title attribute of a COinS span tag).
	 *
	 * @access  public
	 * @return  string              OpenURL parameters.
	 */
	public function getOpenURL()
	{
		// Get the COinS ID -- it should be in the OpenURL section of config.ini,
		// but we'll also check the COinS section for compatibility with legacy
		// configurations (this moved between the RC2 and 1.0 releases).
		$coinsID = 'vufind+';

		// Start an array of OpenURL parameters:
		$params = array(
			'ctx_ver' => 'Z39.88-2004',
			'ctx_enc' => 'info:ofi/enc:UTF-8',
			'rfr_id' => "info:sid/{$coinsID}:generator",
			'rft.title' => $this->getTitle(),
		);

		// Get a representative publication date:
		$pubDate = $this->getPublicationDates();
		if (count($pubDate) == 1){
			$params['rft.date'] = $pubDate[0];
		}elseif (count($pubDate > 1)){
			$params['rft.date'] = $pubDate;
		}

		// Add additional parameters based on the format of the record:
		$formats = $this->getFormatsArray();

		// If we have multiple formats, Book and Journal are most important...
		if (in_array('Book', $formats)) {
			$format = 'Book';
		} else if (in_array('Journal', $formats)) {
			$format = 'Journal';
		} else {
			if (count($formats) > 0){
				$format = $formats[0];
			}else{
				$format = "";
			}
		}
		switch($format) {
			case 'Book':
				$params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
				$params['rft.genre'] = 'book';
				$params['rft.btitle'] = $params['rft.title'];
				if ($this->hasCachedSeries()){
					$series = $this->getSeries(false);
					if ($series != null) {
						// Handle both possible return formats of getSeries:
						$params['rft.series'] = $series['seriesTitle'];
					}
				}

				$params['rft.au'] = $this->getPrimaryAuthor();
				$publishers = $this->getPublishers();
				if (count($publishers) == 1) {
					$params['rft.pub'] = $publishers[0];
				}elseif (count($publishers) > 1) {
					$params['rft.pub'] = $publishers;
				}
				$params['rft.edition'] = $this->getEdition();
				$params['rft.isbn'] = $this->getCleanISBN();
				break;
			case 'Journal':
				/* This is probably the most technically correct way to represent
				 * a journal run as an OpenURL; however, it doesn't work well with
				 * Zotero, so it is currently commented out -- instead, we just add
				 * some extra fields and then drop through to the default case.
				 $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
				 $params['rft.genre'] = 'journal';
				 $params['rft.jtitle'] = $params['rft.title'];
				 $params['rft.issn'] = $this->getCleanISSN();
				 $params['rft.au'] = $this->getPrimaryAuthor();
				 break;
				 */
				$issns = $this->getISSNs();
				if (count($issns) > 0){
					$params['rft.issn'] = $issns[0];
				}

				// Including a date in a title-level Journal OpenURL may be too
				// limiting -- in some link resolvers, it may cause the exclusion
				// of databases if they do not cover the exact date provided!
				unset($params['rft.date']);
			default:
				$params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:dc';
				$params['rft.creator'] = $this->getPrimaryAuthor();
				$publishers = $this->getPublishers();
				if (count($publishers) > 0) {
					$params['rft.pub'] = $publishers[0];
				}
				$params['rft.format'] = $format;
				$langs = $this->getLanguages();
				if (count($langs) > 0) {
					$params['rft.language'] = $langs[0];
				}
				break;
		}

		// Assemble the URL:
		$parts = array();
		foreach($params as $key => $value) {
			if (is_array($value)){
				foreach($value as $arrVal){
					$parts[] = $key . '[]=' . urlencode($arrVal);
				}
			}else{
				$parts[] = $key . '=' . urlencode($value);
			}
		}
		return implode('&', $parts);
	}

	private function getPublicationDates() {
		return isset($this->fields['publishDate']) ? $this->fields['publishDate'] : array();
	}

	/**
	 * Get the publishers of the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPublishers()
	{
		return isset($this->fields['publisher']) ?
			$this->fields['publisher'] : array();
	}

	/**
	 * Get the edition of the current record.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function getEdition()
	{
		return isset($this->fields['edition']) ?
			$this->fields['edition'] : '';
	}

	/**
	 * Get an array of all the languages associated with the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getLanguages()
	{
		return isset($this->fields['language']) ?
			$this->fields['language'] : array();
	}

	private static $statusRankings = array(
		'Currently Unavailable' => 1,
		'On Order' => 2,
		'Coming Soon' => 3,
		'Checked Out' => 4,
		'Library Use Only' => 5,
		'Available Online' => 6,
		'On Shelf' => 7
	);
	public static function keepBestGroupedStatus($groupedStatus, $groupedStatus1) {
		$ranking1 = 1;
		if (isset(GroupedWorkDriver::$statusRankings[$groupedStatus])){
			$ranking1 = GroupedWorkDriver::$statusRankings[$groupedStatus];
		}
		$ranking2 = 1;
		if (isset(GroupedWorkDriver::$statusRankings[$groupedStatus1])){
			$ranking2 = GroupedWorkDriver::$statusRankings[$groupedStatus1];
		}
		if ($ranking1 > $ranking2){
			return $groupedStatus;
		}else{
			return $groupedStatus1;
		}
	}

	public function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null){
		return array();
	}

	/**
	 * Pick one line from the highlighted text (if any) to use as a snippet.
	 *
	 * @return mixed False if no snippet found, otherwise associative array
	 * with 'snippet' and 'caption' keys.
	 * @access protected
	 */
	protected function getHighlightedSnippet()
	{
		// Only process snippets if the setting is enabled:
		if ($this->snippet) {
			// First check for preferred fields:
			foreach ($this->preferredSnippetFields as $current) {
				if (isset($this->fields['_highlighting'][$current][0])) {
					return array(
						'snippet' => $this->fields['_highlighting'][$current][0],
						'caption' => $this->getSnippetCaption($current)
					);
				}
			}

			// No preferred field found, so try for a non-forbidden field:
			if (is_array($this->fields['_highlighting'])) {
				foreach ($this->fields['_highlighting'] as $key => $value) {
					if (!in_array($key, $this->forbiddenSnippetFields)) {
						return array(
							'snippet' => $value[0],
							'caption' => $this->getSnippetCaption($key)
						);
					}
				}
			}
		}

		// If we got this far, no snippet was found:
		return false;
	}

	/**
	 * Given a Solr field name, return an appropriate caption.
	 *
	 * @param string $field Solr field name
	 *
	 * @return mixed        Caption if found, false if none available.
	 * @access protected
	 */
	protected function getSnippetCaption($field)
	{
		if (isset($this->snippetCaptions[$field])){
			return $this->snippetCaptions[$field];
		}else{
			return ucwords(str_replace('_', ' ', $field));
		}
	}
}