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
	/** @var AbstractFedoraObject|null */
	protected $archiveObject;

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
		$fedoraUtils = FedoraUtils::getInstance();
		if ($recordData instanceof AbstractFedoraObject){
			$this->archiveObject = $recordData;
		}elseif (is_array($recordData)){
			$this->archiveObject = $fedoraUtils->getObject($recordData['PID']);
		}else{
			$this->archiveObject = $fedoraUtils->getObject($recordData);
		}

		global $configArray;
		// Load highlighting/snippet preferences:
		$searchSettings = getExtraConfigArray('searches');
		$this->highlight = $configArray['Index']['enableHighlighting'];
		$this->snippet = $configArray['Index']['enableSnippets'];
		$this->snippetCaptions = isset($searchSettings['Snippet_Captions']) && is_array($searchSettings['Snippet_Captions']) ? $searchSettings['Snippet_Captions'] : array();
	}

	function getBookcoverUrl($size = 'small'){
		global $configArray;
		$objectUrl = $configArray['Islandora']['objectUrl'];
		if ($size == 'small'){
			if ($this->archiveObject->getDatastream('SC') != null){
				return $objectUrl . '/' . $this->getUniqueID() . '/datastream/SC/view';
			}elseif ($this->archiveObject->getDatastream('TN') != null){
				return $objectUrl . '/' . $this->getUniqueID() . '/datastream/TN/view';
			}else{
				//return a placeholder
				return $this->getPlaceholderImage();
			}

		}elseif ($size == 'medium'){
			if ($this->archiveObject->getDatastream('MC') != null){
				return $objectUrl . '/' . $this->getUniqueID() . '/datastream/MC/view';
			}elseif ($this->archiveObject->getDatastream('TN') != null){
				return $objectUrl . '/' . $this->getUniqueID() . '/datastream/TN/view';
			}else{
				return $this->getPlaceholderImage();
			}
		}elseif ($size == 'large'){
			if ($this->archiveObject->getDatastream('JPG') != null){
				return $objectUrl . '/' . $this->getUniqueID() . '/datastream/JPG/view';
			}elseif ($this->archiveObject->getDatastream('LC') != null) {
				return $objectUrl . '/' . $this->getUniqueID() . '/datastream/LC/view';
			}elseif ($this->archiveObject->getDatastream('OBJ') != null && $this->archiveObject->getDatastream('OBJ')->mimetype == 'image/jpg') {
				return $objectUrl . '/' . $this->getUniqueID() . '/datastream/OBJ/view';
			}else{
				return $this->getPlaceholderImage();
			}
		}elseif ($size == 'original'){
			if ($this->archiveObject->getDatastream('OBJ') != null) {
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
		$interface->assign('summUrl', $this->getLinkUrl());
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
		$title = $this->archiveObject->label;
		if ($title == ''){
			//$title = $this->getModsData()->
		}
		return $title;
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
		return $this->archiveObject->id;
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
		// TODO: Implement getItemActions() method.
	}

	public function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null) {
		// TODO: Implement getRecordActions() method.
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
			if (strlen($this->archiveObject->label) > 0) {
				$relatedSubjects[$this->archiveObject->label] = '"' . $this->archiveObject->label . '"';
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
			$this->modsData = $fedoraUtils->getModsData($this->archiveObject);
			$timer->logTime('Loaded MODS data for ' . $this->getUniqueID());
		}
		return $this->modsData;
	}

	protected $relatedCollections = null;
	public function getRelatedCollections() {
		if ($this->relatedCollections === null){
			global $timer;
			$this->relatedCollections = array();
			if ($this->isEntity()){
				//Get collections related to objects related to this entity
				$directlyLinkedObjects = $this->getDirectlyRelatedArchiveObjects();
				foreach ($directlyLinkedObjects['objects'] as $tmpObject){
					$linkedCollections = $tmpObject['driver']->getRelatedCollections();
					$this->relatedCollections = array_merge($this->relatedCollections, $linkedCollections);
				}
			}else{
				//Get collections directly related to the object
				$collectionsRaw = $this->archiveObject->relationships->get(FEDORA_RELS_EXT_URI, 'isMemberOfCollection');
				$fedoraUtils = FedoraUtils::getInstance();
				foreach ($collectionsRaw as $collectionInfo) {
					if ($fedoraUtils->isPidValidForPika($collectionInfo['object']['value'])){
						$collectionObject = $fedoraUtils->getObject($collectionInfo['object']['value']);
						$this->relatedCollections[$collectionInfo['object']['value']] = array(
								'pid' => $collectionInfo['object']['value'],
								'label' => $collectionObject->label,
								'link' => '/Archive/' . $collectionInfo['object']['value'] . '/Exhibit',
								'image' => $fedoraUtils->getObjectImageUrl($collectionObject, 'small'),
								'object' => $collectionObject,
						);
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
	private static $nonProductionTeamRoles = array('interviewee', 'artist', 'described', 'contributor', 'author', 'child', 'parent', 'sibling', 'spouse');
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
					if ($entityType == 'person'){

						$isProductionTeam = strlen($entityRole) > 0 && !in_array(strtolower($entityRole), IslandoraDriver::$nonProductionTeamRoles);
						$personObject = $fedoraUtils->getObject($entityPid);
						$entityInfo['image'] = $fedoraUtils->getObjectImageUrl($personObject, 'medium', $entityType);
						$entityInfo['link']= '/Archive/' . $entityPid . '/Person';
						if ($isProductionTeam){
							$this->productionTeam[$entityPid] = $entityInfo;
						}else{
							$this->relatedPeople[$entityPid] = $entityInfo;
						}

					}elseif ($entityType == 'organization'){
						$entityInfo['link']= '/Archive/' . $entityPid . '/Organization';
						$this->relatedOrganizations[$entityPid] = $entityInfo;
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
					$this->relatedEvents[(string)$entityPid] = $entityInfo;
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
			foreach ($links as $id => $link){
				if ($link['type'] == 'relatedPika'){
					if (preg_match('/^.*\/GroupedWork\/([a-f0-9-]{36})/', $link['link'], $matches)) {
						$workId = $matches[1];
						$workDriver = new GroupedWorkDriver($workId);
						if ($workDriver->isValid) {
							$this->relatedPikaRecords[] = array(
									'link' => $workDriver->getLinkUrl(),
									'label' => $workDriver->getTitle(),
									'image' => $workDriver->getBookcoverUrl('medium'),
									'id' => $workId
							);
							$this->links[$id]['hidden'] = true;
						}
					}else{
						//Didn't get a valid grouped work id
					}
				}
			}

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
			foreach ($relatedObjects as $relatedObjectSnippets){
				$objectPid = $this->getModsValue('objectPid', 'marmot', $relatedObjectSnippets);
				if (strlen($objectPid) > 0){
					$archiveObject = $fedoraUtils->getObject($objectPid);
					if ($archiveObject != null){
						$entityDriver = RecordDriverFactory::initRecordDriver($archiveObject);
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


			// Include Search Engine Class
			require_once ROOT_DIR . '/sys/Solr.php';

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

					//TODO: Add the role of the user
					$objectInfo = array(
							'pid' => $entityDriver->getUniqueID(),
							'label' => $entityDriver->getTitle(),
							'description' => $entityDriver->getTitle(),
							'image' => $entityDriver->getBookcoverUrl('medium'),
							'link' => $entityDriver->getRecordUrl(),
							'role' => $role,
							'driver' => $entityDriver
					);
					if ($entityDriver instanceof EventDriver) {
						$this->relatedEvents[$objectInfo['pid']] = $objectInfo;
					}elseif ($entityDriver instanceof PersonDriver){
						$this->relatedPeople[$objectInfo['pid']] = $objectInfo;
					}elseif ($entityDriver instanceof OrganizationDriver){
						$this->relatedOrganizations[$objectInfo['pid']] = $objectInfo;
					}elseif ($entityDriver instanceof PlaceDriver){
						$this->relatedPlaces[$objectInfo['pid']] = $objectInfo;
					}else{
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
		$entityInfo = array(
				'pid' => $pid,
				'label' => $entityName,
				'note' => $note,
				'role' => $role,
				'image' => $fedoraUtils->getObjectImageUrl($fedoraUtils->getObject($pid), 'medium', $entityType),
		);
		if ($entityType == 'person'){
			$entityInfo['link']= '/Archive/' . $pid . '/Person';
			if (strlen($role) > 0 && !in_array(strtolower($role), IslandoraDriver::$nonProductionTeamRoles)){
				$this->productionTeam[$pid.$role] = $entityInfo;
			}else{
				$this->relatedPeople[$pid.$role] = $entityInfo;
			}

		}elseif ($entityType == 'place'){
			$entityInfo['link']= '/Archive/' . $pid . '/Place';
			$this->relatedPlaces[$pid.$role] = $entityInfo;
		}elseif ($entityType == 'event'){
			$entityInfo['link']= '/Archive/' . $pid . '/Event';
			$this->relatedEvents[$pid.$role] = $entityInfo;
		}elseif ($entityType == 'organization'){
			$entityInfo['link']= '/Archive/' . $pid . '/Organization';
			$this->relatedOrganizations[$pid.$role] = $entityInfo;
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
			default: return false;
		}
	}

	public function getDateCreated() {
		$dateCreated = $this->getModsValue('dateCreated', 'mods');
		if ($dateCreated == ''){
			return 'Unknown';
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
}