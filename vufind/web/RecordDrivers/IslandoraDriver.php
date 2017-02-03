<?php

/**
 * Record Driver for display of LargeImages from Islandora
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/9/2015
 * Time: 1:47 PM
 */
require_once ROOT_DIR . '/RecordDrivers/Interface.php';
require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
abstract class IslandoraDriver extends RecordInterface {
	protected $pid = null;
	protected $title = null;

	/** @var AbstractFedoraObject|null */
	protected $archiveObject = null;

	protected $modsData = null;
	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param   array|File_MARC_Record||string   $recordData     Data to construct the driver from
	 * @access  public
	 */
	public function __construct($recordData) {

		if ($recordData instanceof AbstractFedoraObject){
			$this->archiveObject = $recordData;
			$this->pid = $this->archiveObject->id;
			$this->title = $this->archiveObject->label;
		}elseif (is_array($recordData)){
			$this->pid = $recordData['PID'];
			$this->title = isset($recordData['fgs_label_s']) ? $recordData['fgs_label_s'] : (isset($recordData['dc.title']) ? $recordData['dc.title'] : "");
		}else{
			$this->pid = $recordData;
		}

		global $configArray;
		// Load highlighting/snippet preferences:
		$searchSettings = getExtraConfigArray('searches');
		$this->highlight = $configArray['Index']['enableHighlighting'];
		$this->snippet = $configArray['Index']['enableSnippets'];
		$this->snippetCaptions = isset($searchSettings['Snippet_Captions']) && is_array($searchSettings['Snippet_Captions']) ? $searchSettings['Snippet_Captions'] : array();
	}

	function getArchiveObject(){
		$fedoraUtils = FedoraUtils::getInstance();
		if ($this->archiveObject == null && $this->pid != null){
			$this->archiveObject = $fedoraUtils->getObject($this->pid);
		}
		return $this->archiveObject;
	}

	private $islandoraObjectCache = null;

	/**
	 * @return IslandoraObjectCache
	 */
	private function getCachedData(){
		if ($this->islandoraObjectCache == null) {
			require_once ROOT_DIR . '/sys/Islandora/IslandoraObjectCache.php';
			$this->islandoraObjectCache = new IslandoraObjectCache();
			$this->islandoraObjectCache->pid = $this->pid;
			if (!$this->islandoraObjectCache->find(true)){
				$this->islandoraObjectCache = new IslandoraObjectCache();
				$this->islandoraObjectCache->pid = $this->pid;
				$this->islandoraObjectCache->insert();
			}
		}
		return $this->islandoraObjectCache;
	}

	function getBookcoverUrl($size = 'small'){
		global $configArray;

		$cachedData = $this->getCachedData();
		if ($cachedData && !isset($_REQUEST['reload'])){
			if ($size == 'small' && $cachedData->smallCoverUrl != ''){
				return $cachedData->smallCoverUrl;
			}elseif ($size == 'medium' && $cachedData->mediumCoverUrl != ''){
				return $cachedData->mediumCoverUrl;
			}elseif ($size == 'large' && $cachedData->largeCoverUrl != ''){
				return $cachedData->largeCoverUrl;
			}
		}

		$objectUrl = $configArray['Islandora']['objectUrl'];
		if ($size == 'small'){
			if ($this->getArchiveObject()->getDatastream('SC') != null){
				$cachedData->smallCoverUrl = $objectUrl . '/' . $this->getUniqueID() . '/datastream/SC/view';
			}elseif ($this->getArchiveObject()->getDatastream('TN') != null){
				$cachedData->smallCoverUrl = $objectUrl . '/' . $this->getUniqueID() . '/datastream/TN/view';
			}else{
				//return a placeholder
				$cachedData->smallCoverUrl = $this->getPlaceholderImage();
			}
			$cachedData->update();
			return $cachedData->smallCoverUrl;

		}elseif ($size == 'medium'){
			if ($this->getArchiveObject()->getDatastream('MC') != null){
				$cachedData->mediumCoverUrl = $objectUrl . '/' . $this->getUniqueID() . '/datastream/MC/view';
			}elseif ($this->getArchiveObject()->getDatastream('PREVIEW') != null) {
				$cachedData->mediumCoverUrl = $objectUrl . '/' . $this->getUniqueID() . '/datastream/PREVIEW/view';
			}elseif ($this->getArchiveObject()->getDatastream('TN') != null){
				$cachedData->mediumCoverUrl = $objectUrl . '/' . $this->getUniqueID() . '/datastream/TN/view';
			}else{
				$cachedData->mediumCoverUrl = $this->getPlaceholderImage();
			}
			$cachedData->update();
			return $cachedData->mediumCoverUrl;
		}elseif ($size == 'large'){
			if ($this->getArchiveObject()->getDatastream('JPG') != null){
				$cachedData->largeCoverUrl = $objectUrl . '/' . $this->getUniqueID() . '/datastream/JPG/view';
			}elseif ($this->getArchiveObject()->getDatastream('LC') != null) {
				$cachedData->largeCoverUrl = $objectUrl . '/' . $this->getUniqueID() . '/datastream/LC/view';
			}elseif ($this->getArchiveObject()->getDatastream('PREVIEW') != null) {
				$cachedData->largeCoverUrl = $objectUrl . '/' . $this->getUniqueID() . '/datastream/PREVIEW/view';
			}elseif ($this->getArchiveObject()->getDatastream('OBJ') != null && ($this->archiveObject->getDatastream('OBJ')->mimetype == 'image/jpg' || $this->archiveObject->getDatastream('OBJ')->mimetype == 'image/jpeg')) {
				$cachedData->largeCoverUrl = $objectUrl . '/' . $this->getUniqueID() . '/datastream/OBJ/view';
			}else{
				$cachedData->largeCoverUrl = $this->getPlaceholderImage();
			}
			$cachedData->update();
			return $cachedData->largeCoverUrl;
		}elseif ($size == 'original'){
			if ($this->getArchiveObject()->getDatastream('OBJ') != null) {
				return $objectUrl . '/' . $this->getUniqueID() . '/datastream/OBJ/view';
			}
		}else{
			return $this->getPlaceholderImage();
		}
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

	public function getBrowseResult(){
		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);

		$url = $this->getLinkUrl();

		$interface->assign('summUrl', $url);
		$interface->assign('summTitle', $this->getTitle());
//		$interface->assign('summAuthor', null); // Commented out in the template for now. plb 8-25-2016

//		$interface->assign('summFormat', $this->getFormat()); // Not used in the template below. plb 8-25-2016

		//Get Book Covers
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		return 'RecordDrivers/Islandora/browse_result.tpl';
	}
	public function getListWidgetTitle(){
		$widgetTitleInfo = array(
			'id' =>          $this->getUniqueID(),
			'shortId' =>     $this->getUniqueID(),
			'recordtype' => 'archive', //TODO: meh, islandora?
			'image' =>       $this->getBookcoverUrl('medium'),
			'small_image' => $this->getBookcoverUrl('small'),
			'title' =>       $this->getTitle(),
		  'titleURL' =>    $this->getLinkUrl(true), // Include site URL
//			'author' =>      $this->getPrimaryAuthor(),
			'author' =>      $this->getFormat(), // Display the Format of Archive Object where the author would be otherwise displayed in the ListWidget
			'description' => $this->getDescription(),
			'length' =>      '', // TODO: do list widgets use this
			'publisher' =>   '', // TODO: do list widgets use this
			'ratingData' =>  null,
//			'ratingData' =>  $this->getRatingData(),
		);
		return $widgetTitleInfo;
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
//	public function getListEntry($user, $listId = null, $allowEdit = true) {
//		// TODO: Implement getListEntry() method.
//	}

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
		global $interface;

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$interface->assign('jquerySafeId', str_replace(':', '_', $id)); // make id safe for jquery & css calls
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('module', $this->getModule());
		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summDescription', $this->getDescription());
		$interface->assign('summFormat', $this->getFormat());

		// The below template variables are in the listentry.tpl but the driver doesn't currently
		// supply this information, so we are making sure they are set to a null value.
		$interface->assign('summShortId', null);
		$interface->assign('summTitleStatement', null);
		$interface->assign('summAuthor', null);
		$interface->assign('summPublisher', null);
		$interface->assign('summPubDate', null);
		$interface->assign('$summSnippets', null);


		//Determine the cover to use
//		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));


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

		return 'RecordDrivers/Islandora/listentry.tpl';
	}


	public function getModule() {
		return 'Archive';
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
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult($view = 'list') {
		if ($view == 'covers') { // Displaying Results as bookcover tiles
			return $this->getBrowseResult();
		}

		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('module', $this->getModule());

		$linkUrl = $this->getLinkUrl();
		$linkUrl .= '?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page='  . $interface->get_template_vars('page');

		$interface->assign('summUrl', $linkUrl);
//		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summDescription', $this->getDescription());
		$interface->assign('summFormat', $this->getFormat());

		//Determine the cover to use
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		return 'RecordDrivers/Islandora/result.tpl';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getStaffView() {
		// TODO: Implement getStaffView() method.
	}

	public function getTitle() {
		if (empty($this->title)){
			$this->title = $this->getArchiveObject()->label;
		}

		return $this->title;
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
		return $this->pid;
	}

	public function getType(){
		$id = $this->getUniqueID();
		list($type) = explode(':', $id, 2);
		return $type;
	}

	/**
	 * Does this record have audio content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasAudio() {
		return false;
	}

	/**
	 * Does this record have an excerpt available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasExcerpt() {
		return false;
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
		return false;
	}

	/**
	 * Does this record have image content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasImages() {
		return true;
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
		return false;
	}

	/**
	 * Does this record have a Table of Contents available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasTOC() {
		return false;
	}

	/**
	 * Does this record have video content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasVideo() {
		return false;
	}

	public function getDescription() {
		if (isset($this->fields['mods_abstract_s'])){
			return $this->fields['mods_abstract_s'];
		} else{
			$modsData = $this->getModsData();
			return $this->getModsValue('abstract', 'mods');
		}
	}

	public function getMoreDetailsOptions() {
		// TODO: Implement getMoreDetailsOptions() method.
	}

	public function getItemActions($itemInfo) {
		return array();
	}

	public function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null) {
		return array();
	}

//	public function getLinkUrl($unscoped = false) {
	public function getLinkUrl($absolutePath = false) {  // Signature is modeled after Grouped Work Driver to implement URLs for List Widgets
		$linkUrl = $this->getRecordUrl($absolutePath);
		return $linkUrl;
	}
	function getRecordUrl($absolutePath = false){
		global $configArray;
		$recordId = $this->getUniqueID();
		if ($absolutePath){
			return $configArray['Site']['url'] . '/Archive/' . urlencode($recordId) . '/' . $this->getViewAction();
		}else{
			return $configArray['Site']['path'] . '/Archive/' . urlencode($recordId) . '/' . $this->getViewAction();
		}
	}

	public abstract function getViewAction();

	protected function getPlaceholderImage() {
		global $configArray;
		return $configArray['Site']['path'] . '/interface/themes/responsive/images/History.png';
	}

	private $subjectHeadings = null;
	public function getAllSubjectHeadings() {
		if ($this->subjectHeadings == null) {
			require_once ROOT_DIR . '/sys/ArchiveSubject.php';
			$archiveSubjects = new ArchiveSubject();
			$subjectsToIgnore = array();
			$subjectsToRestrict = array();
			if ($archiveSubjects->find(true)){
				$subjectsToIgnore = array_flip(explode("\r\n", strtolower($archiveSubjects->subjectsToIgnore)));
				$subjectsToRestrict = array_flip(explode("\r\n", strtolower($archiveSubjects->subjectsToRestrict)));
			}

			$subjectsWithLinks = $this->getAllSubjectsWithLinks();
			$relatedSubjects = array();
			$title = $this->getTitle();
			if (strlen($title) > 0) {
				$relatedSubjects[$title] = '"' . $title . '"';
			}
			for ($i = 0; $i < 2; $i++){
				foreach ($subjectsWithLinks as $subject) {
					$searchSubject = preg_replace('/\(.*?\)/',"", $subject['label']);
					$searchSubject = trim(preg_replace('/[\/|:.,"]/',"", $searchSubject));
					$lowerSubject = strtolower($searchSubject);
					if (!array_key_exists($lowerSubject, $subjectsToIgnore)) {
						if ($i == 0){
							//First pass, just add primary subjects
							if (!array_key_exists($lowerSubject, $subjectsToRestrict)) {
								$relatedSubjects[$lowerSubject] = '"' . $searchSubject . '"';
							}
						}else{
							//Second pass, add restricted subjects, but only if we don't have 5 subjects already
							if (array_key_exists($lowerSubject, $subjectsToRestrict) && count($relatedSubjects) <= 5) {
								$relatedSubjects[$lowerSubject] = '"' . $searchSubject . '"';
							}
						}
					}
				}
			}
			$relatedSubjects = array_slice($relatedSubjects, 0, 5);

			//Extract Subjects
			$this->subjectHeadings = $relatedSubjects;
		}
		return $this->subjectHeadings;
	}

	private $subjectsWithLinks = null;
	public function getAllSubjectsWithLinks() {
		global $configArray;
		if ($this->subjectsWithLinks == null) {
			//Extract Subjects
			$this->subjectsWithLinks = array();
			$matches = $this->getModsValues('topic', 'mods');
			foreach ($matches as $subjectPart) {
				$subjectLink = $configArray['Site']['path'] . '/Archive/Results?lookfor=';
				if (strlen($subjectPart) > 0) {
					$subjectLink .= '&filter[]=mods_subject_topic_ms%3A' . urlencode('"' .(string)$subjectPart . '"');
					$this->subjectsWithLinks[] = array(
							'link' => $subjectLink,
							'label' => (string)$subjectPart
					);
				}
			}
		}
		return $this->subjectsWithLinks;
	}

	public function getModsAttribute($attribute, $snippet){
		return FedoraUtils::getInstance()->getModsAttribute($attribute, $snippet);
	}

	/**
	 * Gets a single valued field from the MODS data using regular expressions
	 *
	 * @param $tag
	 * @param $namespace
	 * @param $snippet - The snippet of XML to load from
	 *
	 * @return string
	 */
	public function getModsValue($tag, $namespace = null, $snippet = null){
		if ($snippet == null){
			$modsData = $this->getModsData();
		}else{
			$modsData = $snippet;
		}
		return FedoraUtils::getInstance()->getModsValue($tag, $namespace, $modsData);
	}

	/**
	 * Gets a multi valued field from the MODS data using regular expressions
	 *
	 * @param $tag
	 * @param $namespace
	 * @param $snippet - The snippet of XML to load from
	 * @param $includeTag - whether or not the surrounding tag should be included
	 *
	 * @return string[]
	 */
	public function getModsValues($tag, $namespace = null, $snippet = null, $includeTag = false){
		if ($snippet == null){
			$modsData = $this->getModsData();
		}else{
			$modsData = $snippet;
		}
		return FedoraUtils::getInstance()->getModsValues($tag, $namespace, $modsData, $includeTag);
	}

	public function getModsData(){
		global $timer;
		if ($this->modsData == null){
			$fedoraUtils = FedoraUtils::getInstance();
			$this->modsData = $fedoraUtils->getModsData($this->getArchiveObject());
			$timer->logTime('Loaded MODS data for ' . $this->getUniqueID());
		}
		return $this->modsData;
	}

	protected $subCollections = null;
	public function getSubCollections(){
		if ($this->subCollections == null){
			$this->subCollections = array();
			// Include Search Engine Class
			require_once ROOT_DIR . '/sys/Solr.php';

			// Initialise from the current search globals
			/** @var SearchObject_Islandora $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Islandora');
			$searchObject->init();
			$searchObject->setLimit(100);
			$searchObject->setSearchTerms(array(
				'lookfor' => 'RELS_EXT_isMemberOfCollection_uri_mt:"info:fedora/' . $this->getUniqueID() . '" AND RELS_EXT_hasModel_uri_mt:"info:fedora/islandora:collectionCModel"',
				'index' => 'IslandoraKeyword'
			));

			$searchObject->clearHiddenFilters();
			$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
			$searchObject->clearFilters();
			//$searchObject->setDebugging(true, true);
			//$searchObject->setPrimarySearch(true);
			$searchObject->setApplyStandardFilters(false);
			$response = $searchObject->processSearch(true, false, true);
			if ($response && $response['response']['numFound'] > 0) {
				foreach ($response['response']['docs'] as $doc) {
					$subCollectionPid = $doc['PID'];
					$this->subCollections[] = $subCollectionPid;
				}
			}
		}
		return $this->subCollections;
	}

	protected $relatedCollections = null;
	public function getRelatedCollections() {
		if ($this->relatedCollections == null){
			global $timer;
			$this->relatedCollections = array();
			if ($this->isEntity()){
				//Get collections related to objects related to this entity
				$directlyLinkedObjects = $this->getDirectlyRelatedArchiveObjects();
				foreach ($directlyLinkedObjects['objects'] as $tmpObject){
					$linkedCollections = $tmpObject['driver']->getRelatedCollections();
					$this->relatedCollections = array_merge($this->relatedCollections, $linkedCollections);
				}
			}
			//Get collections directly related to the object
			$collectionsRaw = $this->getArchiveObject()->relationships->get(FEDORA_RELS_EXT_URI, 'isMemberOfCollection');
			$fedoraUtils = FedoraUtils::getInstance();
			foreach ($collectionsRaw as $collectionInfo) {
				if ($fedoraUtils->isPidValidForPika($collectionInfo['object']['value'])){
					$collectionObject = $fedoraUtils->getObject($collectionInfo['object']['value']);
					$driver = RecordDriverFactory::initRecordDriver($collectionObject);
					$this->relatedCollections[$collectionInfo['object']['value']] = array(
							'pid' => $collectionInfo['object']['value'],
							'label' => $collectionObject->label,
							'link' => '/Archive/' . $collectionInfo['object']['value'] . '/Exhibit',
							'image' => $fedoraUtils->getObjectImageUrl($collectionObject, 'small'),
							'object' => $collectionObject,
							'driver' => $driver,
					);
				}
			}

			if (count($this->relatedCollections) == 0){
				foreach ($collectionsRaw as $collectionInfo) {
					if (!$fedoraUtils->isPidValidForPika($collectionInfo['object']['value'])){
						$parentObject = $fedoraUtils->getObject($collectionInfo['object']['value']);
						/** @var IslandoraDriver $parentDriver */
						$parentDriver = RecordDriverFactory::initRecordDriver($parentObject);
						$this->relatedCollections = $parentDriver->getRelatedCollections();
						if (count($this->relatedCollections) != 0){
							break;
						}
					}
				}
			}

			$timer->logTime('Loaded related collections for ' . $this->getUniqueID());
		}

		return $this->relatedCollections;
	}

	protected $relatedPeople = array();
	protected $productionTeam = array();
	protected $relatedPlaces = array();
	protected $relatedEvents = array();
	protected $relatedOrganizations = array();
	private $loadedRelatedEntities = false;
	private static $nonProductionTeamRoles = array('attendee', 'artist', 'child', 'correspondence recipient', 'employee', 'interviewee', 'member', 'parade marshal', 'parent', 'participant', 'president', 'rodeo royalty', 'described', 'author', 'sibling', 'spouse', 'pictured' );
	public function loadRelatedEntities(){
		if ($this->loadedRelatedEntities == false){
			$this->loadedRelatedEntities = true;
			$fedoraUtils = FedoraUtils::getInstance();
			$marmotExtension = $this->getMarmotExtension();
			if ($marmotExtension != null){
				$entities = $this->getModsValues('relatedEntity', 'marmot', null, true);
				foreach ($entities as $entity){
					$entityPid = $this->getModsValue('entityPid', 'marmot', $entity);
					if (strlen($entityPid) == 0){
						continue;
					}
					$entityTitle = $this->getModsValue('entityTitle', 'marmot', $entity);
					$entityType = $this->getModsAttribute('type', $entity);
					$relationshipNote = $this->getModsValue('relationshipNote', 'marmot', $entity);
					$this->addRelatedEntityToArrays($entityPid, $entityTitle, $entityType, $relationshipNote, '');

				}

				$transcriber = $this->getModsValue('transcriber', 'marmot');
				if ($transcriber){
					$transcriberPid = $this->getModsValue('entityPid', 'marmot', $transcriber);
					$transcriberTitle = $this->getModsValue('entityTitle', 'marmot', $transcriber);
					$this->addRelatedEntityToArrays($transcriberPid, $transcriberTitle, '', '', 'Transcriber');
				}

				$militaryConflict = $this->getModsValue('militaryConflict', 'marmot');
				if ($militaryConflict){
					$militaryConflictTitle = FedoraUtils::getInstance()->getObjectLabel($militaryConflict);
					$this->addRelatedEntityToArrays($militaryConflict, $militaryConflictTitle, '', '', '');
				}

				$creators = $this->getModsValues('hasCreator', 'marmot', null, true);
				foreach ($creators as $entity) {
					$entityPid = $this->getModsValue('entityPid', 'marmot', $entity);
					if (strlen($entityPid) == 0) {
						continue;
					}
					$entityTitle = $this->getModsValue('entityTitle', 'marmot', $entity);
					$relationshipNote = $this->getModsValue('relationshipNote', 'marmot', $entity);
					$entityType = $this->getModsAttribute('type', $entity);
					$entityRole = $this->getModsAttribute('role', $entity);
					$this->addRelatedEntityToArrays($entityPid, $entityTitle, $entityType, $relationshipNote, $entityRole);
				}

				$entities = $this->getModsValues('describedEntity', 'marmot', null, true);
				foreach ($entities as $entity) {
					$entityPid = $this->getModsValue('entityPid', 'marmot', $entity);
					if (strlen($entityPid) == 0) {
						continue;
					}
					$entityTitle = $this->getModsValue('entityTitle', 'marmot', $entity);
					$relationshipNote = $this->getModsValue('relationshipNote', 'marmot', $entity);
					$entityType = $this->getModsAttribute('type', $entity);
					$this->addRelatedEntityToArrays($entityPid, $entityTitle, $entityType, $relationshipNote, 'Described');
				}

				$entities = $this->getModsValues('picturedEntity', 'marmot', null, true);
				foreach ($entities as $entity) {
					$entityPid = $this->getModsValue('entityPid', 'marmot', $entity);
					if (strlen($entityPid) == 0) {
						continue;
					}
					$entityTitle = $this->getModsValue('entityTitle', 'marmot', $entity);
					$relationshipNote = $this->getModsValue('relationshipNote', 'marmot', $entity);
					$entityType = $this->getModsAttribute('type', $entity);
					$this->addRelatedEntityToArrays($entityPid, $entityTitle, $entityType, $relationshipNote, 'Pictured');
				}

				$entities = $this->getModsValues('relatedPersonOrg', 'marmot', null, true);
				foreach ($entities as $entity){
					$entityPid = $this->getModsValue('entityPid', 'marmot', $entity);
					if (strlen($entityPid) == 0) {
						continue;
					}
					$entityType = $this->getModsAttribute('type', $entity);
					if ($entityType == '' && strlen($entityPid)){
						//Get the type based on the pid
						list($entityType) = explode(':', $entityPid);
					}
					$entityTitle = $this->getModsValue('entityTitle', 'marmot', $entity);
					$relationshipNote = $this->getModsValue('relationshipNote', 'marmot', $entity);
					$entityRole = $this->getModsAttribute('role', $entity);
					if (strlen($entityRole) == 0){
						$entityRole = $this->getModsValue('role', 'marmot', $entity);
					}
					$entityInfo = array(
							'pid' => $entityPid,
							'label' => $entityTitle,
							'role' => $entityRole,
							'note' => $relationshipNote,

					);

					if ($entityType != 'person' && $entityType != 'organization'){
						//Need to check the actual content model
						$fedoraObject = $fedoraUtils->getObject($entityInfo['pid']);
						$recordDriver = RecordDriverFactory::initRecordDriver($fedoraObject);
						if ($recordDriver instanceof PersonDriver){
							$entityType = 'person';
						}elseif ($recordDriver instanceof OrganizationDriver){
							$entityType = 'organization';
						}
					}

					if ($entityType == 'person') {
						require_once ROOT_DIR . '/RecordDrivers/PersonDriver.php';
						$archiveDriver = new PersonDriver($entityPid);
					}else{
						require_once ROOT_DIR . '/RecordDrivers/OrganizationDriver.php';
						$archiveDriver = new OrganizationDriver($entityPid);
					}
					$entityInfo['image'] = $archiveDriver->getBookcoverUrl('medium');
					if ($entityType == 'person'){

						$isProductionTeam = strlen($entityRole) > 0 && !in_array(strtolower($entityRole), IslandoraDriver::$nonProductionTeamRoles);
						$entityInfo['link']= '/Archive/' . $entityPid . '/Person';
						if ($isProductionTeam){
							if (array_key_exists($entityPid, $this->productionTeam)){
								$this->productionTeam[$entityPid]['role'] .= ', ' . $entityInfo['role'];
							}else{
								$this->productionTeam[$entityPid] = $entityInfo;
							}
						}else{
							if (array_key_exists($entityPid, $this->relatedPeople)){
								$this->relatedPeople[$entityPid]['role'] .= ', ' .$entityInfo['role'];
							}else{
								$this->relatedPeople[$entityPid] = $entityInfo;
							}
						}

					}elseif ($entityType == 'organization'){
						$entityInfo['link']= '/Archive/' . $entityPid . '/Organization';
						if (array_key_exists($entityPid, $this->relatedOrganizations)){
							$this->relatedOrganizations[$entityPid]['role'] .= ', ' .$entityInfo['role'];
						}else{
							$this->relatedOrganizations[$entityPid] = $entityInfo;
						}
					}
				}

				$entities = $this->getModsValues('relatedEvent', 'marmot', null, true);
				foreach ($entities as $entity){
					$entityPid = $this->getModsValue('entityPid', 'marmot', $entity);
					if (strlen($entityPid) == 0) {
						continue;
					}
					$entityTitle = $this->getModsValue('entityTitle', 'marmot', $entity);
					$relationshipNote = $this->getModsValue('relationshipNote', 'marmot', $entity);
					$entityRole = $this->getModsAttribute('role', $entity);
					$entityInfo = array(
							'pid' => $entityPid,
							'label' => $entityTitle,
							'role' => $entityRole,
							'note' => $relationshipNote,

					);
					$entityInfo['link']= '/Archive/' . $entityPid . '/Event';
					if (array_key_exists($entityPid, $this->relatedEvents)){
						$this->relatedEvents[$entityPid]['role'] .= ', ' . $entityInfo['role'];
					}else{
						$this->relatedEvents[$entityPid] = $entityInfo;
					}
				}

				$entities = $this->getModsValues('relatedPlace', 'marmot', null, true);
				foreach ($entities as $entity){
					$entityPid = $this->getModsValue('entityPid', 'marmot', $entity);
					if (strlen($entityPid) == 0) {
						//TODO: If we don't get a PID we may still want to display address information?
						continue;
					}
					$entityTitle = $this->getModsValue('entityTitle', 'marmot', $entity);
					$entityInfo = array(
							'pid' => $entityPid,
							'label' => $entityTitle

					);
					$significance = $this->getModsValue('significance', 'marmot', $entity);
					if ($significance){
						$entityInfo['role'] = ucfirst($significance);
					}
					$entityInfo['link']= '/Archive/' . $entityPid . '/Place';
					$this->relatedPlaces[$entityInfo['pid']] = $entityInfo;
				}
			}

		}
	}

	public function getRelatedEvents(){
		if ($this->relatedEvents == null){
			$this->loadRelatedEntities();
		}
		return $this->relatedEvents;
	}

	public function getRelatedPeople(){
		if ($this->relatedPeople == null){
			$this->loadRelatedEntities();
		}
		return $this->relatedPeople;
	}

	public function getProductionTeam(){
		if ($this->productionTeam == null){
			$this->loadRelatedEntities();
		}
		return $this->productionTeam;
	}

	public function getRelatedPlaces(){
		if ($this->relatedPlaces == null){
			$this->loadRelatedEntities();
		}
		return $this->relatedPlaces;
	}

	public function getRelatedOrganizations(){
		if ($this->relatedOrganizations == null){
			$this->loadRelatedEntities();
		}
		return $this->relatedOrganizations;
	}

	public function isEntity(){
		return false;
	}

	/**
	 * @return string
	 */
	protected function getMarmotExtension(){
		return $this->getModsValue('extension', 'mods');
	}

	public function getVisibleLinks(){
		$allLinks = $this->getLinks();
		$visibleLinks = array();
		foreach ($allLinks as $link){
			if (!$link['hidden']){
				$visibleLinks[] = $link;
			}
		}
		return $visibleLinks;
	}
	protected $links = null;
	public function getLinks(){
		if ($this->links == null){
			global $timer;
			$this->links = array();
			$marmotExtension = $this->getMarmotExtension();
			if (strlen($marmotExtension) > 0){
				$linkData = $this->getModsValues('externalLink', 'marmot', $marmotExtension, true);
				foreach ($linkData as $linkInfo) {
					$linkType = $this->getModsAttribute('type', $linkInfo);
					$link = $this->getModsValue('link', 'marmot', $linkInfo);
					$linkText = $this->getModsValue('linkText', 'marmot', $linkInfo);
					if (strlen($linkText) == 0) {
						if (strlen($linkType) == 0) {
							$linkText = $link;
						} else {
							switch ($linkType) {
								case 'relatedPika':
									$linkText = 'Related title from the catalog';
									break;
								case 'marmotGenealogy':
									$linkText = 'Genealogy Record';
									break;
								case 'findAGrave':
									$linkText = 'Grave site information on Find a Grave';
									break;
								case 'fortLewisGeoPlaces':
									//Skip this one
									continue;
								case 'geoNames':
									$linkText = 'Geographic information from GeoNames.org';
									continue;
								case 'samePika':
									$linkText = 'This record within the catalog';
									continue;
								case 'whosOnFirst':
									$linkText = 'Geographic information from Who\'s on First';
									continue;
								case 'wikipedia':
									$linkText = 'Information from Wikipedia';
									continue;
								default:
									$linkText = $linkType;
							}
						}
					}
					if (strlen($link) > 0) {
						$isHidden = false;
						if ($linkType == 'wikipedia' || $linkType == 'geoNames' || $linkType == 'whosOnFirst' || $linkType == 'relatedPika') {
							$isHidden = true;
						}
						$this->links[] = array(
								'type' => $linkType,
								'link' => $link,
								'text' => $linkText,
								'hidden' => $isHidden
						);
					}
				}
			}
			$timer->logTime("Loaded links");
		}
		return $this->links;
	}

	protected $relatedPikaRecords;
	public function getRelatedPikaContent(){
		if ($this->relatedPikaRecords == null){
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';

			$this->relatedPikaRecords = array();

			//Look for things linked directly to this object
			$links = $this->getLinks();
			$relatedWorkIds = array();
			foreach ($links as $id => $link){
				if ($link['type'] == 'relatedPika'){
					if (preg_match('/^.*\/GroupedWork\/([a-f0-9-]{36})/', $link['link'], $matches)) {
						$workId = $matches[1];
						$relatedWorkIds[] = $workId;
					}else{
						//Didn't get a valid grouped work id
					}
				}
			}

			/** @var SearchObject_Solr $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init();
			$linkedWorkData = $searchObject->getRecords($relatedWorkIds);
			foreach ($linkedWorkData as $workData) {
				$workDriver = new GroupedWorkDriver($workData);
				if ($workDriver->isValid) {
					$this->relatedPikaRecords[] = array(
							'link' => $workDriver->getLinkUrl(),
							'label' => $workDriver->getTitle(),
							'image' => $workDriver->getBookcoverUrl('medium'),
							'id' => $workId
					);
					//$this->links[$id]['hidden'] = true;
				}
			}
			$searchObject = null;
			unset ($searchObject);

			//Look for links related to the collection(s) this object is linked to
			$collections = $this->getRelatedCollections();
			foreach ($collections as $collection){
				/** @var IslandoraDriver $collectionDriver */
				$collectionDriver = RecordDriverFactory::initRecordDriver($collection['object']);
				$relatedFromCollection = $collectionDriver->getRelatedPikaContent();
				if (count($relatedFromCollection)){
					$this->relatedPikaRecords = array_merge($this->relatedPikaRecords, $relatedFromCollection);
				}
			}
		}
		return $this->relatedPikaRecords;
	}

	protected $directlyRelatedObjects = null;

	/**
	 * Load objects that are related directly to this object
	 * Either based on a link from this object to another object
	 * Or based on a link from another object to this object
	 *
	 * @return array|null
	 */
	public function getDirectlyRelatedArchiveObjects(){
		if ($this->directlyRelatedObjects == null){
			global $timer;
			$fedoraUtils = FedoraUtils::getInstance();

			$timer->logTime("Starting getDirectlyLinkedArchiveObjects");
			$this->directlyRelatedObjects = array(
					'numFound' => 0,
					'objects' => array(),
			);

			$relatedObjects = $this->getModsValues('relatedObject', 'marmot');
			if (count($relatedObjects) > 0){
				$numObjects = 0;
				$relatedObjectPIDs = array();
				foreach ($relatedObjects as $relatedObjectSnippets){
					$objectPid = trim($this->getModsValue('objectPid', 'marmot', $relatedObjectSnippets));
					if (strlen($objectPid) > 0){
						$numObjects++;
						$relatedObjectPIDs[] = $objectPid;
					}
				}

				if (count($relatedObjectPIDs) > 0) {
					/** @var SearchObject_Islandora $searchObject */
					$searchObject = SearchObjectFactory::initSearchObject('Islandora');
					$searchObject->init();
					$searchObject->setSort('fgs_label_s');
					$searchObject->setLimit($numObjects);
					$searchObject->setQueryIDs($relatedObjectPIDs);
					$response = $searchObject->processSearch(true, false, true);
					if ($response && $response['response']['numFound'] > 0) {
						foreach ($response['response']['docs'] as $doc) {
							$entityDriver = RecordDriverFactory::initRecordDriver($doc);
							$objectInfo = array(
									'pid' => $entityDriver->getUniqueID(),
									'label' => $entityDriver->getTitle(),
									'description' => $entityDriver->getTitle(),
									'image' => $entityDriver->getBookcoverUrl('medium'),
									'link' => $entityDriver->getRecordUrl(),
									'driver' => $entityDriver
							);
							$this->directlyRelatedObjects['objects'][$objectInfo['pid']] = $objectInfo;
							$this->directlyRelatedObjects['numFound']++;
						}
					}
				}
				$searchObject = null;
				unset($searchObject);
			}
			// Include Search Engine Class
			require_once ROOT_DIR . '/sys/Solr.php';

			// Initialise from the current search globals
			/** @var SearchObject_Islandora $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Islandora');
			$searchObject->init();
			$searchObject->setSort('fgs_label_s');
			$searchObject->setLimit(100);
			$searchObject->setSearchTerms(array(
					'lookfor' => '"' . $this->getUniqueID() . '"',
					'index' => 'IslandoraRelationshipsById'
			));

			$searchObject->clearHiddenFilters();
			$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
			$searchObject->clearFilters();
			$searchObject->addFieldsToReturn(array(
					'mods_extension_marmotLocal_relatedPersonOrg_entityPid_ms',
					'mods_extension_marmotLocal_relatedPersonOrg_role_ms',
					'mods_extension_marmotLocal_relatedPersonOrg_entityTitle_ms'
			));
			//$searchObject->setDebugging(true, true);
			//$searchObject->setPrimarySearch(true);
			$response = $searchObject->processSearch(true, false);
			if ($response && $response['response']['numFound'] > 0) {
				foreach ($response['response']['docs'] as $doc) {
					$entityDriver = RecordDriverFactory::initRecordDriver($doc);

					//Try to find the relationship to the person
					$role = '';
					if (isset($doc['mods_extension_marmotLocal_relatedPersonOrg_entityPid_ms']) && isset($doc['mods_extension_marmotLocal_relatedPersonOrg_role_ms'])){
						//Check to see if we have the same number of entities and roles.  If not we will need to load the full related object to determine role.
						if (count($doc['mods_extension_marmotLocal_relatedPersonOrg_entityPid_ms']) != count($doc['mods_extension_marmotLocal_relatedPersonOrg_role_ms'])){
							/** @var IslandoraDriver $relatedEntityDriver */
							$relatedEntityDriver = RecordDriverFactory::initRecordDriver($doc);
							$relatedPeople = $relatedEntityDriver->getRelatedPeople();
							foreach ($relatedPeople as $person){
								if ($person['pid'] == $this->getUniqueID()){
									$role = $person['role'];
									break;
								}
							}

						}else{
							foreach ($doc['mods_extension_marmotLocal_relatedPersonOrg_entityPid_ms'] as $index => $value) {
								if ($value == $this->getUniqueID()) {
									if (isset($doc['mods_extension_marmotLocal_relatedPersonOrg_role_ms'][$index])){
										$role = $doc['mods_extension_marmotLocal_relatedPersonOrg_role_ms'][$index];
										//Reverse roles as appropriate
										if ($role == 'child'){
											$role = 'parent';
										}
									}
								}
							}
						}
					}

					if ($entityDriver instanceof EventDriver) {
						$this->addRelatedEntityToArrays($entityDriver->getUniqueID(), $entityDriver->getTitle(), 'event', '', $role);
					}elseif ($entityDriver instanceof PersonDriver){
						$this->addRelatedEntityToArrays($entityDriver->getUniqueID(), $entityDriver->getTitle(), 'person', '', $role);
					}elseif ($entityDriver instanceof OrganizationDriver){
						$this->addRelatedEntityToArrays($entityDriver->getUniqueID(), $entityDriver->getTitle(), 'organization', '', $role);
					}elseif ($entityDriver instanceof PlaceDriver){
						$this->addRelatedEntityToArrays($entityDriver->getUniqueID(), $entityDriver->getTitle(), 'place', '', $role);
					}else{
						$objectInfo = array(
								'pid' => $entityDriver->getUniqueID(),
								'label' => $entityDriver->getTitle(),
								'description' => $entityDriver->getTitle(),
								'image' => $entityDriver->getBookcoverUrl('medium'),
								'link' => $entityDriver->getRecordUrl(),
								'role' => $role,
								'driver' => $entityDriver
						);
						$this->directlyRelatedObjects['objects'][$objectInfo['pid']] = $objectInfo;
						$this->directlyRelatedObjects['numFound']++;
					}
				}
			}
			$timer->logTime("Finished getDirectlyLinkedArchiveObjects");
		}

		return $this->directlyRelatedObjects;
	}

	private function addRelatedEntityToArrays($pid, $entityName, $entityType, $note, $role) {
		if (strlen($pid) == 0 || strpos($pid, ':') === false){
			return;
		}
		$fedoraUtils = FedoraUtils::getInstance();
		if ($entityType == '' && strlen($pid)){
			//Get the type based on the pid
			list($entityType, $id) = explode(':', $pid);
		}

		require_once ROOT_DIR . '/sys/Islandora/IslandoraObjectCache.php';
		$islandoraCache = new IslandoraObjectCache();
		$islandoraCache->pid = $pid;
		if ($islandoraCache->find(true) && !empty($islandoraCache->mediumCoverUrl)){
			$imageUrl = $islandoraCache->mediumCoverUrl;
		}else{
			$imageUrl = $fedoraUtils->getObjectImageUrl($fedoraUtils->getObject($pid), 'medium', $entityType);
		}
		$entityInfo = array(
				'pid' => $pid,
				'label' => $entityName,
				'note' => $note,
				'role' => $role,
				'image' => $imageUrl,
		);
		if ($entityType == 'person'){
			$entityInfo['link']= '/Archive/' . $pid . '/Person';
			if (strlen($role) > 0 && !in_array(strtolower($role), IslandoraDriver::$nonProductionTeamRoles)){
				if (array_key_exists($pid, $this->productionTeam)){
					$this->productionTeam[$pid]['role'] .= ', ' . $entityInfo['role'];
				}else{
					$this->productionTeam[$pid] = $entityInfo;
				}
			}else{
				if (array_key_exists($pid, $this->relatedPeople)){
					$this->relatedPeople[$pid]['role'] .= ', ' . $entityInfo['role'];
				}else{
					$this->relatedPeople[$pid] = $entityInfo;
				}
			}

		}elseif ($entityType == 'place'){
			$entityInfo['link']= '/Archive/' . $pid . '/Place';
			if (array_key_exists($pid, $this->relatedPlaces)){
				$this->relatedPlaces[$pid]['role'] .= ', ' . $entityInfo['role'];
			}else{
				$this->relatedPlaces[$pid] = $entityInfo;
			}
		}elseif ($entityType == 'event'){
			$entityInfo['link']= '/Archive/' . $pid . '/Event';
			if (array_key_exists($pid, $this->relatedEvents)){
				$this->relatedEvents[$pid]['role'] .= ', ' . $entityInfo['role'];
			}else{
				$this->relatedEvents[$pid] = $entityInfo;
			}
		}elseif ($entityType == 'organization'){
			$entityInfo['link']= '/Archive/' . $pid . '/Organization';
			if (array_key_exists($pid, $this->relatedOrganizations)){
				$this->relatedOrganizations[$pid]['role'] .= ', ' . $entityInfo['role'];
			}else{
				$this->relatedOrganizations[$pid] = $entityInfo;
			}
		}else{
			//Need to check the actual content model
			$fedoraObject = $fedoraUtils->getObject($entityInfo['pid']);
			$recordDriver = RecordDriverFactory::initRecordDriver($fedoraObject);
			if ($recordDriver instanceof PersonDriver){
				$entityInfo['link']= '/Archive/' . $pid . '/Person';
				if (strlen($role) > 0 && !in_array(strtolower($role), IslandoraDriver::$nonProductionTeamRoles)){
					if (array_key_exists($pid, $this->productionTeam)){
						$this->productionTeam[$pid]['role'] .= ', ' . $entityInfo['role'];
					}else{
						$this->productionTeam[$pid] = $entityInfo;
					}
				}else{
					if (array_key_exists($pid, $this->relatedPeople)){
						$this->relatedPeople[$pid]['role'] .= ', ' . $entityInfo['role'];
					}else{
						$this->relatedPeople[$pid] = $entityInfo;
					}
				}

			}elseif ($recordDriver instanceof PlaceDriver){
				$entityInfo['link']= '/Archive/' . $pid . '/Place';
				if (array_key_exists($pid, $this->relatedPlaces)){
					$this->relatedPlaces[$pid]['role'] .= ', ' . $entityInfo['role'];
				}else{
					$this->relatedPlaces[$pid] = $entityInfo;
				}
			}elseif ($recordDriver instanceof EventDriver){
				$entityInfo['link']= '/Archive/' . $pid . '/Event';
				if (array_key_exists($pid, $this->relatedEvents)){
					$this->relatedEvents[$pid]['role'] .= ', ' . $entityInfo['role'];
				}else{
					$this->relatedEvents[$pid] = $entityInfo;
				}
			}elseif ($recordDriver instanceof OrganizationDriver){
				$entityInfo['link']= '/Archive/' . $pid . '/Organization';
				if (array_key_exists($pid, $this->relatedOrganizations)){
					$this->relatedOrganizations[$pid]['role'] .= ', ' . $entityInfo['role'];
				}else{
					$this->relatedOrganizations[$pid] = $entityInfo;
				}
			}
		}
	}

	public function getExtension($mimeType)
	{
		if(empty($mimeType)) return false;
		switch($mimeType)
		{
			case 'image/bmp': return '.bmp';
			case 'image/cis-cod': return '.cod';
			case 'image/gif': return '.gif';
			case 'image/ief': return '.ief';
			case 'image/jpeg': return '.jpg';
			case 'image/jpg': return '.jpg';
			case 'image/pipeg': return '.jfif';
			case 'image/tiff': return '.tif';
			case 'image/x-cmu-raster': return '.ras';
			case 'image/x-cmx': return '.cmx';
			case 'image/x-icon': return '.ico';
			case 'image/x-portable-anymap': return '.pnm';
			case 'image/x-portable-bitmap': return '.pbm';
			case 'image/x-portable-graymap': return '.pgm';
			case 'image/x-portable-pixmap': return '.ppm';
			case 'image/x-rgb': return '.rgb';
			case 'image/x-xbitmap': return '.xbm';
			case 'image/x-xpixmap': return '.xpm';
			case 'image/x-xwindowdump': return '.xwd';
			case 'image/png': return '.png';
			case 'image/x-jps': return '.jps';
			case 'image/x-freehand': return '.fh';
			case 'application/pdf': return '.pdf';
			default: return false;
		}
	}

	public function getDateCreated() {
		$dateCreated = $this->getModsValue('dateCreated', 'mods');
		if ($dateCreated == ''){
			$dateCreated = $this->getModsValue('dateIssued', 'mods');
			if ($dateCreated == ''){
				return 'Unknown';
			}else{
				return $dateCreated;
			}
		}else{
			return $dateCreated;
		}
	}

	public function getFormat(){
		$genre = $this->getModsValue('genre', 'mods');
		if ($genre != null){
			return ucwords($genre);
		}
		return null;
	}

	/**
	 * @return null|FedoraObject
	 */
	public function getParentObject(){
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$parentIdArray = $this->getArchiveObject()->relationships->get(FEDORA_RELS_EXT_URI, 'isMemberOf');
		if ($parentIdArray != null){
			$parentIdInfo = reset($parentIdArray);
			$parentId = $parentIdInfo['object']['value'];
			return $fedoraUtils->getObject($parentId);
		}else{
			$parentIdArray = $this->getArchiveObject()->relationships->get(FEDORA_RELS_EXT_URI, 'isConstituentOf');
			if ($parentIdArray != null){
				$parentIdInfo = reset($parentIdArray);
				$parentId = $parentIdInfo['object']['value'];
				return $fedoraUtils->getObject($parentId);
			}
		}
		return null;
	}

	public function loadMetadata(){
		global $interface;
		global $configArray;

		$this->loadRelatedEntities();

		$description = html_entity_decode($this->getDescription());
		$description = str_replace("\r\n", '<br/>', $description);
		$description = str_replace("&#xD;", '<br/>', $description);
		if (strlen($description)){
			$interface->assign('description', $description);
		}

		$contextNotes = $this->getModsValue('contextNotes', 'marmot');
		if (strlen($contextNotes)) {
			$interface->assign('contextNotes', $contextNotes);
		}

		$this->loadTranscription();
		$this->loadCorrespondenceInfo();
		$this->loadAcademicResearchData();
		$this->loadEducationInfo();
		$this->loadMilitaryServiceData();
		$this->loadNotes();
		$this->loadLinkedData();
		$this->loadRecordInfo();
		$this->loadRightsStatements();

		$visibleLinks = $this->getVisibleLinks();
		$interface->assignAppendToExisting('externalLinks', $visibleLinks);

		$this->formattedSubjects = $this->getAllSubjectsWithLinks();
		$interface->assignAppendToExisting('subjects', $this->formattedSubjects);

		$directlyRelatedObjects = $this->getDirectlyRelatedArchiveObjects();
		$existingValue = $interface->getVariable('directlyRelatedObjects');
		if ($existingValue != null){
			$directlyRelatedObjects['numFound'] += $existingValue['numFound'];
			$directlyRelatedObjects['objects'] = array_merge($existingValue['objects'], $directlyRelatedObjects['objects']);
		}
		$interface->assign('directlyRelatedObjects', $directlyRelatedObjects);

		$relatedEvents = $this->getRelatedEvents();
		$relatedPeople = $this->getRelatedPeople();
		$productionTeam = $this->getProductionTeam();
		$relatedOrganizations = $this->getRelatedOrganizations();
		$relatedPlaces = $this->getRelatedPlaces();

		//Sort all the related information
		usort($relatedEvents, 'ExploreMore::sortRelatedEntities');
		usort($relatedPeople, 'ExploreMore::sortRelatedEntities');
		usort($productionTeam, 'ExploreMore::sortRelatedEntities');
		usort($relatedOrganizations, 'ExploreMore::sortRelatedEntities');
		usort($relatedPlaces, 'ExploreMore::sortRelatedEntities');

		//Do final assignment
		$interface->assign('relatedEvents', $this->mergeEntities($interface->getVariable('relatedEvents'), $relatedEvents));
		$interface->assign('relatedPeople', $this->mergeEntities($interface->getVariable('relatedPeople'), $relatedPeople));
		$interface->assign('productionTeam', $this->mergeEntities($interface->getVariable('productionTeam'), $productionTeam));
		$interface->assign('relatedOrganizations', $this->mergeEntities($interface->getVariable('relatedOrganizations'), $relatedOrganizations));
		$interface->assign('relatedPlaces', $this->mergeEntities($interface->getVariable('relatedPlaces'), $relatedPlaces));

		$repositoryLink = $configArray['Islandora']['repositoryUrl'] . '/islandora/object/' . $this->getUniqueID();
		$interface->assign('repositoryLink', $repositoryLink);
	}

	private function mergeEntities($array1, $array2){
		if ($array1 == null){
			return $array2;
		}elseif ($array2 == null){
			return $array1;
		}else{
			foreach ($array2 as $entityInfo){
				$pid = $entityInfo['pid'];
				if (array_key_exists($pid, $array1)){
					$array1[$pid]['role'] .= ', ' . $entityInfo['role'];
				}else{
					$array1[$pid] = $entityInfo;
				}
			}
		}
	}
	private function loadTranscription() {
		global $interface;
		$transcriptions = $this->getModsValues('hasTranscription', 'marmot');
		if ($transcriptions){
			$transcriptionInfo = array();
			foreach ($transcriptions as $transcription){
				$transcriptionText = $this->getModsValue('transcriptionText', 'marmot', $transcription);
				$transcriptionText = str_replace("\r\n", '<br/>', $transcriptionText);
				$transcriptionText = str_replace("&#xD;", '<br/>', $transcriptionText);

				//Add links to timestamps
				$transcriptionTextWithLinks = $transcriptionText;
				if (preg_match_all('/\\(\\d{1,2}:\d{1,2}\\)/', $transcriptionText, $allMatches)){
					foreach ($allMatches[0] as $match){
						$offset = str_replace('(', '', $match);
						$offset = str_replace(')', '', $offset);
						list($minutes, $seconds) = explode(':', $offset);
						$offset = $minutes * 60 + $seconds;
						$replacement = '<a onclick="document.getElementById(\'player\').currentTime=\'' . $offset . '\';" style="cursor:pointer">' . $match . '</a>';
						$transcriptionTextWithLinks = str_replace($match, $replacement, $transcriptionTextWithLinks);
					}
				}elseif (preg_match_all('/\\[\\d{1,2}:\d{1,2}:\d{1,2}\\]/', $transcriptionText, $allMatches)){
					foreach ($allMatches[0] as $match){
						$offset = str_replace('(', '', $match);
						$offset = str_replace(')', '', $offset);
						list($hours, $minutes, $seconds) = explode(':', $offset);
						$offset = $hours * 3600 + $minutes * 60 + $seconds;
						$replacement = '<a onclick="document.getElementById(\'player\').currentTime=\'' . $offset . '\';" style="cursor:pointer">' . $match . '</a>';
						$transcriptionTextWithLinks = str_replace($match, $replacement, $transcriptionTextWithLinks);
					}
				}
				if (strlen($transcriptionTextWithLinks) > 0){
					$transcript = array(
							'language' => $this->getModsValue('transcriptionLanguage', 'marmot', $transcription),
							'text' => $transcriptionTextWithLinks,
							'location' => $this->getModsValue('transcriptionLocation', 'marmot', $transcription)
					);
					$transcriptionInfo[] = $transcript;
				}
			}

			if (count($transcriptionInfo) > 0){
				$interface->assign('transcription',$transcriptionInfo);
			}
		}
	}

	private function loadCorrespondenceInfo() {
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$correspondence = $this->getModsValue('correspondence', 'marmot');
		$hasCorrespondenceInfo = false;
		if ($correspondence){
			$includesStamp = $this->getModsValue('includesStamp', 'marmot', $correspondence);
			if ($includesStamp == 'yes'){
				$interface->assign('includesStamp', true);
				$hasCorrespondenceInfo = true;
			}
			$datePostmarked = $this->getModsValue('datePostmarked', 'marmot', $correspondence);
			if ($datePostmarked){
				$interface->assign('datePostmarked', $datePostmarked);
				$hasCorrespondenceInfo = true;
			}
			$relatedPlace = $this->getModsValue('entityPlace', 'marmot', $correspondence);
			if ($relatedPlace){
				$placePid = $this->getModsValue('entityPid', 'marmot', $relatedPlace);
				if ($placePid){
					$postMarkLocationObject = $fedoraUtils->getObject($placePid);
					if ($postMarkLocationObject){
						$postMarkLocationDriver = RecordDriverFactory::initRecordDriver($postMarkLocationObject);
						$interface->assign('postMarkLocation', array(
								'link' => $postMarkLocationDriver->getRecordUrl(),
								'label' => $postMarkLocationDriver->getTitle(),
								'role' => 'Postmark Location'
						));
						$hasCorrespondenceInfo = true;
					}
				}else{
					$placeTitle = $this->getModsValue('entityTitle', 'marmot', $relatedPlace);
					if ($placeTitle){
						$interface->assign('postMarkLocation', array(
								'label' => $placeTitle,
								'role' => 'Postmark Location'
						));
						$hasCorrespondenceInfo = true;
					}
				}
			}

			$relatedPerson = $this->getModsValue('relatedPersonOrg', 'marmot', $correspondence);
			if ($relatedPerson){
				$personPid = $this->getModsValue('entityPid', 'marmot', $relatedPerson);
				if ($personPid){
					$correspondenceRecipientObject = $fedoraUtils->getObject($personPid);
					if ($correspondenceRecipientObject){
						$correspondenceRecipientDriver = RecordDriverFactory::initRecordDriver($correspondenceRecipientObject);
						$interface->assign('correspondenceRecipient', array(
								'link' => $correspondenceRecipientDriver->getRecordUrl(),
								'label' => $correspondenceRecipientDriver->getTitle(),
								'role' => 'Correspondence Recipient'
						));
						$hasCorrespondenceInfo = true;
					}
				}else{
					$personTitle = $this->getModsValue('entityTitle', 'marmot', $relatedPerson);
					if ($personTitle){
						$interface->assign('correspondenceRecipient', array(
								'label' => $personTitle,
								'role' => 'Correspondence Recipient'
						));
						$hasCorrespondenceInfo = true;
					}
				}
			}
		}
		$interface->assign('hasCorrespondenceInfo', $hasCorrespondenceInfo);
	}

	private function loadAcademicResearchData() {
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$academicResearchSection = $this->getModsValue('academicResearch', 'marmot');
		$hasAcademicResearchData = false;
		if (!empty($academicResearchSection)){
			$researchType = FedoraUtils::cleanValue($this->getModsValue('academicResearchType', 'marmot', $academicResearchSection));
			if (strlen($researchType)){
				$hasAcademicResearchData = true;
				$interface->assign('researchType', $researchType);
			}

			$researchLevel = FedoraUtils::cleanValue($this->getModsValue('academicResearchLevel', 'marmot', $academicResearchSection));
			if (strlen($researchLevel)) {
				$hasAcademicResearchData = true;
				$interface->assign('researchLevel', ucwords($researchLevel));
			}

			$degreeName = FedoraUtils::cleanValue($this->getModsValue('degreeName', 'marmot', $academicResearchSection));
			if (strlen($degreeName)) {
				$hasAcademicResearchData = true;
				$interface->assign('degreeName', $degreeName);
			}

			$degreeDiscipline = FedoraUtils::cleanValue($this->getModsValue('degreeDiscipline', 'marmot', $academicResearchSection));
			if (strlen($degreeDiscipline)){
				$hasAcademicResearchData = true;
				$interface->assign('degreeDiscipline', $degreeDiscipline);
			}

			$peerReview = FedoraUtils::cleanValue($this->getModsValue('peerReview', 'marmot', $academicResearchSection));
			$interface->assign('peerReview', ucwords($peerReview));

			$defenceDate = FedoraUtils::cleanValue($this->getModsValue('defenceDate', 'marmot', $academicResearchSection));
			if (strlen($defenceDate)) {
				$hasAcademicResearchData = true;
				$interface->assign('defenceDate', $defenceDate);
			}

			$acceptedDate = FedoraUtils::cleanValue($this->getModsValue('acceptedDate', 'marmot', $academicResearchSection));
			if (strlen($acceptedDate)) {
				$hasAcademicResearchData = true;
				$interface->assign('acceptedDate', $acceptedDate);
			}

			$relatedAcademicPeople = $this->getModsValues('relatedPersonOrg', 'marmot', $academicResearchSection);
			if ($relatedAcademicPeople){
				$academicPeople = array();
				foreach ($relatedAcademicPeople as $relatedPerson){
					$personPid = $this->getModsValue('entityPid', 'marmot', $relatedPerson);
					$role = ucwords($this->getModsValue('role', 'marmot', $relatedPerson));
					if ($personPid){
						$academicPersonObject = $fedoraUtils->getObject($personPid);
						if ($academicPersonObject){
							$academicPersonDriver = RecordDriverFactory::initRecordDriver($academicPersonObject);
							$academicPeople[] = array(
									'link' => $academicPersonDriver->getRecordUrl(),
									'label' => $academicPersonDriver->getTitle(),
									'role' => $role
							);
						}
					}else{
						$personTitle = $this->getModsValue('entityTitle', 'marmot', $relatedPerson);
						if ($personTitle){
							$academicPeople[] = array(
									'label' => $personTitle,
									'role' => $role
							);
						}
					}
				}
				if (count($academicPeople) > 0){
					$interface->assign('academicPeople', $academicPeople);
					$hasAcademicResearchData = true;
				}
			}

		}
		$interface->assign('hasAcademicResearchData', $hasAcademicResearchData);
	}

	public function loadLinkedData(){
		global $interface;
		foreach ($this->getLinks() as $link){
			if ($link['type'] == 'wikipedia'){
				require_once ROOT_DIR . '/sys/WikipediaParser.php';
				$wikipediaParser = new WikipediaParser('en');

				//Transform from a regular wikipedia link to an api link
				$searchTerm = str_replace('https://en.wikipedia.org/wiki/', '', $link['link']);
				$url = "http://en.wikipedia.org/w/api.php" .
						'?action=query&prop=revisions&rvprop=content&format=json' .
						'&titles=' . urlencode(urldecode($searchTerm));
				$wikipediaData = $wikipediaParser->getWikipediaPage($url);
				$interface->assign('wikipediaData', $wikipediaData);
			}elseif($link['type'] == 'marmotGenealogy'){
				$matches = array();
				if (preg_match('/.*Person\/(\d+)/', $link['link'], $matches)){
					$personId = $matches[1];
					require_once ROOT_DIR . '/sys/Genealogy/Person.php';
					$person = new Person();
					$person->personId = $personId;
					if ($person->find(true)){
						$interface->assign('genealogyData', $person);

						$formattedBirthdate = $person->formatPartialDate($person->birthDateDay, $person->birthDateMonth, $person->birthDateYear);
						$interface->assign('birthDate', $formattedBirthdate);

						$formattedDeathdate = $person->formatPartialDate($person->deathDateDay, $person->deathDateMonth, $person->deathDateYear);
						$interface->assign('deathDate', $formattedDeathdate);

						$marriages = array();
						$personMarriages = $person->marriages;
						if (isset($personMarriages)){
							foreach ($personMarriages as $marriage){
								$marriageArray = (array)$marriage;
								$marriageArray['formattedMarriageDate'] = $person->formatPartialDate($marriage->marriageDateDay, $marriage->marriageDateMonth, $marriage->marriageDateYear);
								$marriages[] = $marriageArray;
							}
						}
						$interface->assign('marriages', $marriages);
						$obituaries = array();
						$personObituaries =$person->obituaries;
						if (isset($personObituaries)){
							foreach ($personObituaries as $obit){
								$obitArray = (array)$obit;
								$obitArray['formattedObitDate'] = $person->formatPartialDate($obit->dateDay, $obit->dateMonth, $obit->dateYear);
								$obituaries[] = $obitArray;
							}
						}
						$interface->assign('obituaries', $obituaries);
					}
				}
			}
		}
	}

	private function loadEducationInfo() {
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$interface->assign('hasEducationInfo', false);
		$academicRecord = $this->getModsValue('education', 'marmot');
		if (strlen($academicRecord) > 0){
			$degreeName = FedoraUtils::cleanValue($this->getModsValue('degreeName', 'marmot', $academicRecord));
			if ($degreeName){
				$interface->assign('degreeName', $degreeName);
				$hasEducationInfo = true;
			}

			$graduationDate = FedoraUtils::cleanValue($this->getModsValue('graduationDate', 'marmot', $academicRecord));
			if ($graduationDate){
				$interface->assign('graduationDate', $graduationDate);
				$hasEducationInfo = true;
			}

			$relatedEducationPeople = $this->getModsValues('relatedPersonOrg', 'marmot', $academicRecord);
			if ($relatedEducationPeople){
				$educationPeople = array();
				foreach ($relatedEducationPeople as $relatedPerson){
					$personPid = $this->getModsValue('entityPid', 'marmot', $relatedPerson);
					$role = ucwords($this->getModsValue('role', 'marmot', $relatedPerson));
					if ($personPid){
						$educationPersonObject = $fedoraUtils->getObject($personPid);
						if ($educationPersonObject){
							$educationPersonDriver = RecordDriverFactory::initRecordDriver($educationPersonObject);
							$educationPeople[] = array(
									'link' => $educationPersonDriver->getRecordUrl(),
									'label' => $educationPersonDriver->getTitle(),
									'role' => $role
							);
						}
					}else{
						$personTitle = $this->getModsValue('entityTitle', 'marmot', $relatedPerson);
						if ($personTitle){
							$educationPeople[] = array(
									'label' => $personTitle,
									'role' => $role
							);
						}
					}
					$hasEducationInfo = true;
				}
				if (count($educationPeople) > 0){
					$interface->assign('educationPeople', $educationPeople);
				}
			}

			$interface->assign('hasEducationInfo', $hasEducationInfo);
		}
	}

	private function loadMilitaryServiceData() {
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$interface->assign('hasMilitaryService', false);
		$militaryService = $this->getModsValue('militaryService', 'marmot');
		if (strlen($militaryService) > 0){
			/** @var SimpleXMLElement $record */
			$militaryRecord = $this->getModsValue('militaryRecord', 'marmot', $militaryService);
			$militaryBranch = $this->getModsValue('militaryBranch', 'marmot', $militaryRecord);
			$militaryConflict = $this->getModsValue('militaryConflict', 'marmot', $militaryRecord);
			if ($militaryBranch != 'none' || $militaryConflict != 'none'){
				$militaryRecord = array(
						'branch' => $fedoraUtils->getObjectLabel($militaryBranch),
						'branchLink' => '/Archive/' . $militaryBranch . '/Organization',
						'conflict' => $fedoraUtils->getObjectLabel($militaryConflict),
						'conflictLink' => '/Archive/' . $militaryConflict . '/Event',
				);
				$interface->assign('militaryRecord', $militaryRecord);
				$interface->assign('hasMilitaryService', true);
			}
		}
	}

	private function loadNotes() {
		global $interface;
		$notes = array();
		$personNotes = $this->getModsValue('personNotes', 'marmot');
		if (strlen($personNotes) > 0){
			$notes[] = $personNotes;
		}
		$citationNotes = $this->getModsValue('citationNotes', 'marmot');
		if (strlen($citationNotes) > 0){
			$notes[] = $citationNotes;
		}
		$interface->assignAppendToExisting('notes', $notes);
	}

	private function loadRecordInfo() {
		global $interface;
		$recordInfo = $this->getModsValue('identifier', 'recordInfo');
		if (strlen($recordInfo)){
			$interface->assign('hasRecordInfo', true);
			$recordOrigin = $this->getModsValue('recordOrigin', 'mods', $recordInfo);
			$interface->assign('recordOrigin', $recordOrigin);

			$recordCreationDate = $this->getModsValue('recordCreationDate', 'mods', $recordInfo);
			$interface->assign('recordCreationDate', $recordCreationDate);

			$recordChangeDate = $this->getModsValue('recordChangeDate', 'mods', $recordInfo);
			$interface->assign('recordChangeDate', $recordChangeDate);
		}

		$identifier = $this->getModsValues('identifier', 'mods');
		$interface->assignAppendToExisting('identifier', FedoraUtils::cleanValues($identifier));

		$originInfo = $this->getModsValue('originInfo', 'mods');
		if (strlen($originInfo)){
			$dateCreated = $this->getModsValue('dateCreated', 'mods', $originInfo);
			$interface->assign('dateCreated', $dateCreated);

			$dateIssued = $this->getModsValue('dateIssued', 'mods', $originInfo);
			$interface->assign('dateIssued', $dateIssued);
		}

		$language = $this->getModsValue('languageTerm', 'mods');
		$interface->assign('language', FedoraUtils::cleanValue($language));

		$physicalDescriptions = $this->getModsValues('physicalDescription', 'mods');
		$physicalExtents = array();
		foreach ($physicalDescriptions as $physicalDescription){
			$extent = $this->getModsValue('extent', 'mods', $physicalDescription);
			$form = $this->getModsValue('form', 'mods', $physicalDescription);
			if (empty($extent)){
				$extent = $form;
			}elseif (!empty($form)){
				$extent .= " ($form)";
			}
			$physicalExtents[] = $extent;

		}
		$interface->assign('physicalExtents', $physicalExtents);

		$physicalLocation = $this->getModsValues('physicalLocation', 'mods');
		$interface->assign('physicalLocation', $physicalLocation);

		$interface->assign('postcardPublisherNumber', $this->getModsValue('postcardPublisherNumber', 'marmot'));

		$shelfLocator = $this->getModsValues('shelfLocator', 'mods');
		$interface->assign('shelfLocator', FedoraUtils::cleanValues($shelfLocator));
	}

	private function loadRightsStatements() {
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$rightsStatements = $this->getModsValues('rightsStatement', 'marmot');
		foreach ($rightsStatements as $id => $rightsStatement){
			$rightsStatement = str_replace("\r\n", '<br/>', $rightsStatement);
			$rightsStatement = str_replace("&#xD;", '<br/>', $rightsStatement);
			$rightsStatements[$id] = $rightsStatement;
		}

		$interface->assignAppendUniqueToExisting('rightsStatements', $rightsStatements);

		$rightsHolder = $this->getModsValue('rightsHolder', 'marmot');
		if (!empty($rightsHolder)) {
			$rightsHolderPid = $this->getModsValue('entityPid', 'marmot', $rightsHolder);
			$rightsHolderTitle = $this->getModsValue('entityTitle', 'marmot', $rightsHolder);
			if ($rightsHolderPid) {
				$interface->assign('rightsHolderTitle', $rightsHolderTitle);
				$rightsHolderObj = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($rightsHolderPid));
				$interface->assign('rightsHolderLink', $rightsHolderObj->getRecordUrl());
			}
		}

		$rightsCreator = $this->getModsValue('rightsCreator', 'marmot');
		if (!empty($rightsCreator)) {
			$rightsCreatorPid = $this->getModsValue('entityPid', 'marmot', $rightsCreator);
			$rightsCreatorTitle = $this->getModsValue('entityTitle', 'marmot', $rightsCreator);
			if ($rightsCreatorPid) {
				$interface->assign('rightsCreatorTitle', $rightsCreatorTitle);
				$rightsCreatorObj = RecordDriverFactory::initRecordDriver($fedoraUtils->getObject($rightsCreatorPid));
				$interface->assign('rightsCreatorLink', $rightsCreatorObj->getRecordUrl());
			}
		}
	}

	protected $childObjects = null;
	public function getChildren() {
		if ($this->childObjects == null){
			$this->childObjects = array();
			// Include Search Engine Class
			require_once ROOT_DIR . '/sys/Solr.php';

			// Initialise from the current search globals
			/** @var SearchObject_Islandora $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Islandora');
			$searchObject->init();
			$searchObject->setLimit(100);
			$searchObject->setSort('fgs_label_s');
			$searchObject->setSearchTerms(array(
					'lookfor' => '"info:fedora/' . $this->getUniqueID() .'"',
					'index' => 'RELS_EXT_isMemberOfCollection_uri_mt'
			));
			$searchObject->addFieldsToReturn(array('RELS_EXT_isMemberOfCollection_uri_mt'));

			$searchObject->clearHiddenFilters();
			$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
			$searchObject->clearFilters();
			$searchObject->setApplyStandardFilters(false);
			$response = $searchObject->processSearch(true, false, true);
			if ($response && $response['response']['numFound'] > 0) {
				foreach ($response['response']['docs'] as $doc) {
					$subCollectionPid = $doc['PID'];
					$this->childObjects[] = $subCollectionPid;
				}
			}
		}
		return $this->childObjects;
	}

	public function getRandomObject() {
		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/Solr.php';

		// Initialise from the current search globals
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
		$searchObject->setLimit(1);
		$now = time();
		$searchObject->setSort("random_$now asc");
		$searchObject->setSearchTerms(array(
				'lookfor' => '"info:fedora/' . $this->getUniqueID() .'"',
				'index' => 'RELS_EXT_isMemberOfCollection_uri_mt'
		));

		$searchObject->clearHiddenFilters();
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		$searchObject->clearFilters();
		$searchObject->setApplyStandardFilters(false);
		$response = $searchObject->processSearch(true, false, true);
		if ($response && $response['response']['numFound'] > 0) {
			foreach ($response['response']['docs'] as $doc) {
				return $doc['PID'];
			}
		}
		return null;
	}

	public function getBrandingInformation($loadingCollectionData = false) {
		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		$fedoraUtils = FedoraUtils::getInstance();

		$brandingResults = array();

		$productionTeam = $this->getProductionTeam();
		foreach ($productionTeam as $person){
			if ($person['role'] == 'donor'){
				$brandingResults[] = array(
						'label' => 'Donated by ' . $person['label'],
						'image' => $person['image'],
						'link' => $person['link'],
						'sortIndex' => $loadingCollectionData ? 6 : 2,
						'pid' => $person['pid']
				);
			}elseif ($person['role'] == 'owner'){
				$brandingResults[] = array(
						'label' => 'Owned by ' . $person['label'],
						'image' => $person['image'],
						'link' => $person['link'],
						'sortIndex' => $loadingCollectionData ? 5 : 1,
						'pid' => $person['pid']
				);
			}elseif ($person['role'] == 'funder'){
				$brandingResults[] = array(
						'label' => 'Funded by ' . $person['label'],
						'image' => $person['image'],
						'link' => $person['link'],
						'sortIndex' => $loadingCollectionData ? 7 : 3,
						'pid' => $person['pid']
				);
			}elseif ($person['role'] == 'acknowledgement'){
				$brandingResults[] = array(
						'label' => '',
						'image' => $person['image'],
						'link' => $person['link'],
						'sortIndex' => $loadingCollectionData ? 8 : 4,
						'pid' => $person['pid']
				);
			}
		}
		$relatedOrganizations = $this->getRelatedOrganizations();
		foreach ($relatedOrganizations as $organization){
			if ($organization['role'] == 'donor'){
				$brandingResults[] = array(
						'label' => 'Donated by ' . $organization['label'],
						'image' => $organization['image'],
						'link' => $organization['link'],
						'sortIndex' => $loadingCollectionData ? 6 : 2,
						'pid' => $organization['pid']
				);
			}elseif ($organization['role'] == 'owner'){
				$brandingResults[] = array(
						'label' => 'Owned by ' . $organization['label'],
						'image' => $organization['image'],
						'link' => $organization['link'],
						'sortIndex' => $loadingCollectionData ? 5 : 1,
						'pid' => $organization['pid']
				);
			}elseif ($organization['role'] == 'funder'){
				$brandingResults[] = array(
						'label' => 'Funded by ' . $organization['label'],
						'image' => $organization['image'],
						'link' => $organization['link'],
						'sortIndex' => $loadingCollectionData ? 7 : 3,
						'pid' => $organization['pid']
				);
			}elseif ($organization['role'] == 'acknowledgement'){
				$brandingResults[] = array(
						'label' => '',
						'image' => $organization['image'],
						'link' => $organization['link'],
						'sortIndex' => $loadingCollectionData ? 8 : 4,
						'pid' => $organization['pid']
				);
			}
		}
		//Get the contributing institution
		list($namespace) = explode(':', $this->getUniqueID());
		$contributingLibrary = new Library();
		$contributingLibrary->archiveNamespace = $namespace;
		if (!$contributingLibrary->find(true)){
			$contributingLibrary = null;
		}else{
			if ($contributingLibrary->archivePid == ''){
				$contributingLibrary = null;
			}
		}


		if ($contributingLibrary){
			$contributingLibraryPid = $contributingLibrary->archivePid;
			require_once ROOT_DIR . '/sys/Islandora/IslandoraObjectCache.php';
			$islandoraCache = new IslandoraObjectCache();
			$islandoraCache->pid = $contributingLibraryPid;
			if ($islandoraCache->find(true) && !empty($islandoraCache->mediumCoverUrl)){
				$imageUrl = $islandoraCache->mediumCoverUrl;
			}else{
				$imageUrl = $fedoraUtils->getObjectImageUrl($fedoraUtils->getObject($contributingLibraryPid), 'medium');
			}
			$brandingResults[] = array(
					'label' => 'Contributed by ' . $contributingLibrary->displayName,
					'image' => $imageUrl,
					'link' => "/Archive/$contributingLibraryPid/Organization",
					'sortIndex' => 9,
					'pid' => $contributingLibraryPid
			);
		}

		return $brandingResults;
	}

	private $viewingRestrictions = null;

	/**
	 * @return array
	 */
	public function getViewingRestrictions() {
		if ($this->viewingRestrictions == null) {
			$this->viewingRestrictions = array();
			$accessLimits = $this->getModsValue('pikaAccessLimits', 'marmot');
			if ($accessLimits == 'all') {
				//No restrictions needed, don't check the parent collections
			}else if ($accessLimits == 'default' || $accessLimits == null) {
				$parentCollections = $this->getRelatedCollections();
				foreach ($parentCollections as $collection) {
					$collectionDriver = $collection['driver'];
					$accessLimits = $collectionDriver->getViewingRestrictions();
					if (count($accessLimits) > 0){
						$this->viewingRestrictions = array_merge($this->viewingRestrictions, $accessLimits);
					}
				}
			}else{
				$accessLimits = preg_split('/[\r\n,]/', $accessLimits);
				$this->viewingRestrictions = array_merge($this->viewingRestrictions, $accessLimits);
			}
		}
		return $this->viewingRestrictions;
	}

	private $showClaimAuthorship = null;

	/**
	 * @return boolean
	 */
	public function getShowClaimAuthorship() {
		if ($this->showClaimAuthorship == null){
			$showClaimAuthorship = $this->getModsValue('showClaimAuthorship', 'marmot');
			if ($showClaimAuthorship == null || strcasecmp($showClaimAuthorship, 'no') === 0){
				$this->showClaimAuthorship = false;
			}else{
				$this->showClaimAuthorship = true;
			}
		}
		return $this->showClaimAuthorship;
	}
}