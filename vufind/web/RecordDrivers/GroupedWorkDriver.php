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
	public function __construct($indexFields)
	{
		if (is_string($indexFields)){
			global $configArray;

			$id = $indexFields;
			//Just got a record id, let's load the full record from Solr
			// Setup Search Engine Connection
			$class = $configArray['Index']['engine'];
			$url = $configArray['Index']['url'];
			/** @var Solr $db */
			$db = new $class($url);
			$db->disableScoping();
			if (function_exists('disableErrorHandler')){
				disableErrorHandler();
			}

			// Retrieve the record from Solr
			if (!($record = $db->getRecord($id))) {
				$this->isValid = false;
			}else{
				$this->fields = $record;
			}
			$db->enableScoping();
			if (function_exists('enableErrorHandler')){
				enableErrorHandler();
			}
		}else{
			$this->fields = $indexFields;
		}
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
		// TODO: Implement getBreadcrumb() method.
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
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$listEntry = new UserListEntry();
		$listEntry->groupedWorkPermanentId = $this->getUniqueID();
		$listEntry->listId = $listId;
		if ($listEntry->find(true)){
			$interface->assign('listEntryNotes', $listEntry->notes);
		}

		$interface->assign('listEditAllowed', $allowEdit);

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

	public function getLinkUrl(){
		global $configArray;
		return $configArray['Site']['url'] . '/GroupedWork/' . $this->getPermanentId() . '/Home';
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

		//Build the link URL.
		//If there is only one record for the work we will link straight to that.
		$relatedRecords = $this->getRelatedRecords();
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
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summSubTitle', $this->getSubtitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		$isbn = $this->getCleanISBN();
		$interface->assign('summISBN', $isbn);
		$interface->assign('summFormats', $this->getFormats());

		$interface->assign('numRelatedRecords', count($relatedRecords));

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
		$linkUrl = '/GroupedWork/' . $id . '/Home?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page='  . $interface->get_template_vars('page');
		$interface->assign('summUrl', $linkUrl);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summSubTitle', $this->getSubtitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());

		//Get Rating
		$interface->assign('ratingData', $this->getRatingData());

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

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
			$groupedWorkDetails['title'] = $groupedWork->title;
			$groupedWorkDetails['subtitle'] = $groupedWork->subtitle;
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
	public function getTitle()
	{
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
	protected function getSubtitle()
	{
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
	 * @return  string
	 */
	public function getPrimaryAuthor()
	{
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

	function getDescriptionFast(){
		if (isset($this->fields['display_description'])){
			return $this->fields['display_description'];
		}else{
			$relatedRecords = $this->getRelatedRecords();
			//Look for a description from a book in english
			foreach ($relatedRecords as $relatedRecord){
				if (($relatedRecord['format'] == 'Book' || $relatedRecord['format'] == 'eBook') && $relatedRecord['language'] == 'English'){
					$fastDescription = $relatedRecord['driver']->getDescriptionFast();
					if ($fastDescription != null && strlen($fastDescription) > 0){
						return $fastDescription;
					}
				}
			}
			//Didn't get a description, get the description from the first record
			foreach ($relatedRecords as $relatedRecord){
				$fastDescription = $relatedRecord['driver']->getDescriptionFast();
				if ($fastDescription != null && strlen($fastDescription) > 0){
					return $fastDescription;
				}
			}
			return '';
		}
	}

	function getDescription(){
		$description = "Description Not Provided";
		$cleanIsbn = $this->getCleanISBN();
		if ($cleanIsbn != null && strlen($cleanIsbn) > 0){
			require_once ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php';
			$summaryInfo = GoDeeperData::getSummary($cleanIsbn, $this->getCleanUPC());
			if (isset($summaryInfo['summary'])){
				$description = $summaryInfo['summary'];
			}
		}else{
			$description = $this->getDescriptionFast();
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
		if (!isset($_REQUEST['reload']) && $novelistData->find(true) && $novelistData->primaryISBN != null){
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
		if (isset($this->fields['related_record_ids'])){
			return count($this->fields['related_record_ids']);
		}else{
			return 0;
		}
	}

	private $relatedRecords = null;
	public function getRelatedRecords() {
		global $timer;
		if ($this->relatedRecords == null){
			$timer->logTime("Starting to load related records for {$this->getUniqueID()}");
			$relatedRecords = array();

			global $solrScope;

			$relatedRecordFieldName = 'related_record_ids';
			if ($solrScope){
				if (isset($this->fields["related_record_ids_$solrScope"])){
					$relatedRecordFieldName = "related_record_ids_$solrScope";
				}
			}

			//Get a list of related items
			$relatedItemsFieldName = 'related_record_items';
			if ($solrScope){
				if (isset($this->fields["related_items_$solrScope"])){
					$relatedItemsFieldName = "related_items_$solrScope";
				}
			}

			if (isset($this->fields[$relatedRecordFieldName])){
				$relatedRecordIds = $this->fields[$relatedRecordFieldName];
				if (!is_array($relatedRecordIds)){
					$relatedRecordIds = array($relatedRecordIds);
				}
				if (isset($this->fields[$relatedItemsFieldName])){
					$itemsFromIndex = array();
					$itemsFromIndexRaw = $this->fields[$relatedItemsFieldName];
					if (!is_array($itemsFromIndexRaw)){
						$itemsFromIndexRaw = array($itemsFromIndexRaw);
					}
					foreach ($itemsFromIndexRaw as $tmpItem){
						if (strpos($tmpItem, '|') !== FALSE){
							$itemsFromIndex[] = explode('|', $tmpItem);
						}else{
							$itemsFromIndex[] = array($tmpItem);
						}
					}
				}else{
					$itemsFromIndex = null;
				}
				foreach ($relatedRecordIds as $relatedRecordInfo){
					$hasDetailedRecordInfo = false;
					if (strpos($relatedRecordInfo, '|') !== FALSE){
						$relatedRecordInfo = explode('|', $relatedRecordInfo);
						$relatedRecordId = $relatedRecordInfo[0];
						$hasDetailedRecordInfo = true;
					}else{
						$relatedRecordId = $relatedRecordInfo;
					}
					require_once ROOT_DIR . '/RecordDrivers/Factory.php';
					$recordDriver = RecordDriverFactory::initRecordDriverById($relatedRecordId);
					if ($itemsFromIndex != null){
						$filteredItemsFromIndex = array();
						foreach ($itemsFromIndex as $item){
							if ($item[0] == $relatedRecordId){
								$filteredItemsFromIndex[] = $item;
							}
						}
						$recordDriver->setItemsFromIndex($filteredItemsFromIndex);
					}
					if ($hasDetailedRecordInfo){
						$recordDriver->setDetailedRecordInfoFromIndex($relatedRecordInfo);
					}
					$timer->logTime("Initialized Record Driver for $relatedRecordId");
					if ($recordDriver != null && $recordDriver->isValid()){
						$recordDriver->setScopingEnabled($this->scopingEnabled);
						$relatedRecordsForBib = $recordDriver->getRelatedRecords();
						foreach ($relatedRecordsForBib as $relatedRecord){
							$relatedRecord['driver'] = $recordDriver;
							$relatedRecords[] = $relatedRecord;
						}
					}
					$timer->logTime("Finished loading related records for $relatedRecordId");
				}
				//Sort the records based on format and then edition
				usort($relatedRecords, array("GroupedWorkDriver", "compareRelatedRecords"));
			}
			$this->relatedRecords = $relatedRecords;
		}
		$timer->logTime("Finished loading related records");
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
					'callNumber' => array(),
					'available' => false,
					'hasLocalItem' => false,
					'relatedRecords' => array(),
					'preferredEdition' => null,
					'statusMessage' => '',
					'shelfLocation' => array(),
					'availableLocally' => false,
					'availableOnline' => false,
					'availableHere' => false,
					'inLibraryUseOnly' => false,
					'allLibraryUseOnly' => true,
					'hideByDefault' => false,
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
				$manifestation['hideByDefault'] = true;
			}
			if ($selectedFormatCategory && $selectedFormatCategory != $manifestation['formatCategory']){
				$manifestation['hideByDefault'] = true;
			}
			if ($selectedAvailability == 'Available Now' && !($manifestation['availableLocally'] || $manifestation['availableOnline'])){
				$manifestation['hideByDefault'] = true;
			}
			$relatedManifestations[$key] = $manifestation;
		}
		$timer->logTime("Finished loading related manifestations");

		return $relatedManifestations;
	}

	static function compareRelatedRecords($a, $b){
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
			//Put english titles before spanish by default
			$languageComparison = GroupedWorkDriver::compareLanguagesForRecords($a, $b);
			if ($languageComparison == 0){
				//Compare editions if available
				$editionComparisonResult = GroupedWorkDriver::compareEditionsForRecords($a, $b);
				if ($editionComparisonResult == 0){
					//Put anything with a local copy higher
					$localItemComparisonResult = GroupedWorkDriver::compareLocalItemsForRecords($a, $b);
					if ($localItemComparisonResult == 0){
						//Anything that is available goes higher
						$availabilityComparisonResults = GroupedWorkDriver::compareAvailabilityForRecords($a, $b);
						if ($availabilityComparisonResults == 0){
							//All else being equal, sort by hold ratio
							if ($a['holdRatio'] == $b['holdRatio']){
								//Hold Ratio is the same, last thing to check is the number of copies
								if ($a['copies'] == $b['copies']){
									return 0;
								}elseif ($a['copies'] > $b['copies']){
									return -1;
								}else{
									return 1;
								}
							}elseif ($a['holdRatio'] > $b['holdRatio']){
								return -1;
							}else{
								return 1;
							}
						}else{
							return $availabilityComparisonResults;
						}
					}else{
						return $localItemComparisonResult;
					}
				}else{
					return $editionComparisonResult;
				}
			}else{
				return $languageComparison;
			}
		}else {
			return $formatComparison;
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

	static function compareEditionsForRecords($a, $b){
		$editionA = preg_replace('/\D/', '', $a['edition']);
		$editionB = preg_replace('/\D/', '', $b['edition']);
		if ($editionA == $editionB){
			return 0;
		}else if ($editionA > $editionB){
			return -1;
		}else{
			return 1;
		}
	}

	static function compareAvailabilityForRecords($a, $b){
		$availableLocallyA = isset($a['availableLocally']) && $a['availableLocally'];
		$availableLocallyB = isset($b['availableLocally']) && $b['availableLocally'];
		if ($availableLocallyA && $availableLocallyB){
			$availableA = isset($a['available']) && $a['available'];
			$availableB = isset($b['available']) && $b['available'];
			if ($availableA && $availableB){
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

	public function getSeries(){
		//Get a list of isbns from the record
		$relatedIsbns = $this->getISBNs();
		$novelist = NovelistFactory::getNovelist();
		$novelistData = $novelist->loadBasicEnrichment($this->getPermanentId(), $relatedIsbns);
		if ($novelistData != null && isset($novelistData->seriesTitle)){
			return array(
				'seriesTitle' => $novelistData->seriesTitle,
				'volume' => $novelistData->volume,
				'fromNovelist' => true,
			);
		}
		return null;
	}

	private function getFormats() {
		$formats = $this->fields['format'];
		if (is_array($formats)){
			natcasesort($formats);
			return implode(", ", $formats);
		}else{
			return $formats;
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
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$userReview = new UserWorkReview();
		$userReview->groupedRecordPermanentId = $this->getUniqueID();
		$joinUser = new User();
		$userReview->joinAdd($joinUser);
		$userReview->find();
		while ($userReview->fetch()){
			if (!$userReview->displayName){
				if (strlen(trim($userReview->firstname)) >= 1){
					$userReview->displayName = substr($userReview->firstname, 0, 1) . '. ' . $userReview->lastname;
				}else{
					$userReview->displayName = $userReview->lastname;
				}
			}
			//TODO: Clean the review text
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
}