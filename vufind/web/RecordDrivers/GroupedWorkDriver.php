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
class GroupedWorkDriver implements RecordInterface{

	protected $fields;
	protected $scopingEnabled = false;
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

			// Retrieve the record from Solr
			if (!($record = $db->getRecord($id))) {
				$this->isValid = false;
			}else{
				$this->fields = $record;
			}
			$db->enableScoping();
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
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display core metadata (the details shown in the
	 * top portion of the record view pages, above the tabs).
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCoreMetadata() {
		// TODO: Implement getCoreMetadata() method.
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
		// TODO: Implement getEmail() method.
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
		// TODO: Implement getListEntry() method.
	}

	/**
	 * Get the OpenURL parameters to represent this record (useful for the
	 * title attribute of a COinS span tag).
	 *
	 * @access  public
	 * @return  string              OpenURL parameters.
	 */
	public function getOpenURL() {
		// TODO: Implement getOpenURL() method.
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
		global $user;

		$interface->assign('useUnscopedHoldingsSummary', $useUnscopedHoldingsSummary);

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		if (substr($id, 0, 1) == '.'){
			$interface->assign('summShortId', substr($id, 1));
		}else{
			$interface->assign('summShortId', $id);
		}
		$linkUrl = '/GroupedWork/' . $id . '/Home?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page='  . $interface->get_template_vars('page');
		if ($useUnscopedHoldingsSummary){
			$linkUrl .= '&amp;searchSource=marmot';
		}else{
			$linkUrl .= '&amp;searchSource=' . $interface->get_template_vars('searchSource');
		}
		$interface->assign('summUrl', $linkUrl);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summSubTitle', $this->getSubtitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		$isbn = $this->getCleanISBN();
		$interface->assign('summISBN', $isbn);
		$interface->assign('summFormats', $this->getFormats());

		$interface->assign('numRelatedRecords', $this->getNumRelatedRecords());

		if ($configArray['System']['debugSolr']){
			$interface->assign('summScore', $this->getScore());
			$interface->assign('summExplain', $this->getExplain());
		}

		//Get Rating
		$interface->assign('summRating', $this->getRatingData());

		//Description
		$interface->assign('summDescription', $this->getDescriptionFast());
		$interface->assign('summSeries', $this->getSeries());

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		// By default, do not display AJAX status; we won't assume that all
		// records exist in the ILS.  Child classes can override this setting
		// to turn on AJAX as needed:
		$interface->assign('summAjaxStatus', false);

		$interface->assign('relatedManifestations', $this->getRelatedManifestations());

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
		// TODO: Implement getTOC() method.
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
		return 0;
	}

	public function getExplain(){
		if (isset($this->fields['explain'])){
			return nl2br(str_replace(' ', '&nbsp;', $this->fields['explain']));
		}
		return '';
	}

	function getDescriptionFast(){
		$relatedRecords = $this->getRelatedRecords();
		$bestDescription = '';
		foreach ($relatedRecords as $relatedRecord){
			$fastDescription = $relatedRecord['driver']->getDescriptionFast();
			if ($fastDescription != null && strlen($fastDescription) > 0){
				if (strlen($fastDescription) > $bestDescription){
					$bestDescription = $fastDescription;
				}
			}
		}
		return $bestDescription;
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
		$bookCoverUrl = $configArray['Site']['coverUrl'] . "/bookcover.php?id={$this->getUniqueID()}&amp;size={$size}&amp;type=grouped_work";
		$isbn = $this->getCleanISBN();
		if ($isbn){
			$bookCoverUrl .= "&amp;isn={$isbn}";
		}else{
			$upc = $this->getCleanUPC();
			if ($upc){
				$bookCoverUrl .= "&amp;upc={$upc}";
			}
		}
		if (isset($this->fields['format_category'])){
			if (is_array($this->fields['format_category'])){
				$bookCoverUrl .= "&category=" . reset($this->fields['format_category']);
			}else{
				$bookCoverUrl .= "&category=" . $this->fields['format_category'];
			}
		}

		return $bookCoverUrl;
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
		if ($novelistData->find(true) && $novelistData->primaryISBN != null){
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
		if ($this->relatedRecords == null){
			$relatedRecords = array();
			if (isset($this->fields['related_record_ids'])){
				$relatedRecordIds = $this->fields['related_record_ids'];
				if (!is_array($relatedRecordIds)){
					$relatedRecordIds = array($relatedRecordIds);
				}
				foreach ($relatedRecordIds as $relatedRecordId){
					$recordDriver = RecordDriverFactory::initRecordDriverById($relatedRecordId);
					$recordDriver->setScopingEnabled($this->scopingEnabled);
					if ($recordDriver != null && $recordDriver->isValid()){
						$relatedRecordsForBib = $recordDriver->getRelatedRecords();
						foreach ($relatedRecordsForBib as $relatedRecord){
							$relatedRecord['driver'] = $recordDriver;
							$relatedRecords[] = $relatedRecord;
						}
					}
				}
				//Sort the records based on format and then edition
				usort($relatedRecords, array("GroupedWorkDriver", "compareRelatedRecords"));
			}
			$this->relatedRecords = $relatedRecords;
		}
		return $this->relatedRecords;
	}

	public function getRelatedManifestations() {
		$relatedRecords = $this->getRelatedRecords();
		//Group the records based on format
		$relatedManifestations = array();
		foreach ($relatedRecords as $curRecord){
			if (!array_key_exists($curRecord['format'], $relatedManifestations)){
				$relatedManifestations[$curRecord['format']] = array(
					'format' => $curRecord['format'],
					'copies' => 0,
					'availableCopies' => 0,
					'callNumber' => $curRecord['callNumber'] ? $curRecord['callNumber'] : '',
					'available' => false,
					'hasLocalItem' => false,
					'relatedRecords' => array(),
					'preferredEdition' => null,
					'statusMessage' => '',
					'shelfLocation' => '',
				);
			}
			if (!$relatedManifestations[$curRecord['format']]['available'] && $curRecord['available']){
				$relatedManifestations[$curRecord['format']]['available'] = $curRecord['available'];
			}
			if (!$relatedManifestations[$curRecord['format']]['hasLocalItem'] && $curRecord['hasLocalItem']){
				$relatedManifestations[$curRecord['format']]['hasLocalItem'] = $curRecord['hasLocalItem'];
			}
			if (!$relatedManifestations[$curRecord['format']]['shelfLocation'] && $curRecord['shelfLocation']){
				$relatedManifestations[$curRecord['format']]['shelfLocation'] = $curRecord['shelfLocation'];
			}
			$relatedManifestations[$curRecord['format']]['relatedRecords'][$curRecord['holdRatio'] . '_' .  $curRecord['id']] = $curRecord;
			$relatedManifestations[$curRecord['format']]['copies'] += $curRecord['copies'];
			$relatedManifestations[$curRecord['format']]['availableCopies'] += $curRecord['availableCopies'];

		}

		//Check to see what we need to do for actions
		foreach ($relatedManifestations as $key => $manifestation){
			$manifestation['numRelatedRecords'] = count($manifestation['relatedRecords']);
			if (count($manifestation['relatedRecords']) == 1){
				$firstRecord = reset($manifestation['relatedRecords']);
				$manifestation['url'] = $firstRecord['url'];
				$manifestation['actions'] = $firstRecord['actions'];
			}else{
				//Figure out what the preferred record is to place a hold on
				$bestRecord = null;
				foreach ($manifestation['relatedRecords'] as $index => $record){
					if ($bestRecord == null){
						$bestRecord = $record;
					}else{
						//Check to see if this record is better than the current record.
						if ($bestRecord['available'] == true && $record['available'] == false){
							//The current record is not available, but the best record is so it is better.
						}else if ($bestRecord['available'] == false && $record['available'] == true){
							//The current record is available which makes it better automatically
							$bestRecord = $record;
						}else{
							//Check number of (copies - holds + available copies) / total copies
							//TODO: Do we need to account for the record being owned by the home library?  Possibly with an extra boost
							if ($record['holdRatio'] > $bestRecord['holdRatio']){
								$bestRecord = $record;
							}
						}
					}

				}

				$manifestation['actions'] = $bestRecord['actions'];
			}

			$relatedManifestations[$key] = $manifestation;
		}
		return $relatedManifestations;
	}

	static function compareRelatedRecords($a, $b){
		$formatComparison = strcasecmp($a['format'], $b['format']);
		if ($formatComparison == 0){
			//Same format, sort by number of copies, descending
			if ($a['copies'] == $b['copies']){
				return 0;
			}elseif ($a['copies'] > $b['copies']){
				return -1;
			}else{
				return 1;
			}
		}else{
			return $formatComparison;
		}
	}

	public function getIndexedSeries(){
		return $this->fields['series'];
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
				$userReview->displayName = substr($userReview->firstname, 0, 1) . '. ' . $userReview->lastname;
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

		//Load more details options
		$moreDetailsOptions = array();
		$moreDetailsOptions['tableOfContents'] = array(
			'label' => 'Table of Contents',
			'body' => $interface->fetch('GroupedWork/tableOfContents.tpl'),
			'hideByDefault' => true
		);
		$moreDetailsOptions['excerpt'] = array(
			'label' => 'Excerpt',
			'body' => '<div id="excerptPlaceholder">Loading Excerpt...</div>',
			'hideByDefault' => true
		);
		$moreDetailsOptions['borrowerReviews'] = array(
			'label' => 'Borrower Reviews',
			'body' => "<div id='customerReviewPlaceholder'></div>",
		);
		$moreDetailsOptions['editorialReviews'] = array(
			'label' => 'Editorial Reviews',
			'body' => "<div id='editorialReviewPlaceholder'></div>",
		);
		if ($isbn){
			$moreDetailsOptions['syndicatedReviews'] = array(
				'label' => 'Syndicated Reviews',
				'body' => "<div id='syndicatedReviewPlaceholder'></div>",
			);
		}
		//A few tabs require an ISBN
		if ($isbn){
			$moreDetailsOptions['goodreadsReviews'] = array(
				'label' => 'Reviews from GoodReads',
				'body' => '<iframe id="goodreads_iframe" class="goodReadsIFrame" src="https://www.goodreads.com/api/reviews_widget_iframe?did=DEVELOPER_ID&format=html&isbn=' . $isbn . '&links=660&review_back=fff&stars=000&text=000" width="100%" height="400px" frameborder="0"></iframe>',
			);
			$moreDetailsOptions['similarTitles'] = array(
				'label' => 'Similar Titles From Novelist',
				'body' => '<div id="novelisttitlesPlaceholder"></div>',
				'hideByDefault' => true
			);
			$moreDetailsOptions['similarAuthors'] = array(
				'label' => 'Similar Authors From Novelist',
				'body' => '<div id="novelistauthorsPlaceholder"></div>',
				'hideByDefault' => true
			);
			$moreDetailsOptions['similarSeries'] = array(
				'label' => 'Similar Series From Novelist',
				'body' => '<div id="novelistseriesPlaceholder"></div>',
				'hideByDefault' => true
			);
		}
		$moreDetailsOptions['details'] = array(
			'label' => 'Details',
			'body' => $interface->fetch('GroupedWork/view-title-details.tpl'),
		);
		$moreDetailsOptions['staff'] = array(
			'label' => 'Staff View',
			'body' => $interface->fetch($this->getStaffView()),
		);

		return $moreDetailsOptions;
	}
}