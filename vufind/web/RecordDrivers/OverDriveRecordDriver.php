<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/2/13
 * Time: 8:37 AM
 */

require_once ROOT_DIR . '/RecordDrivers/Interface.php';
class OverDriveRecordDriver extends RecordInterface {

	private $id;
	/** @var OverDriveAPIProduct  */
	private $overDriveProduct;
	/** @var  OverDriveAPIProductMetaData */
	private $overDriveMetaData;
	private $valid;
	private $isbns = null;
	private $upcs = null;
	private $asins = null;
	private $items;
	protected $scopingEnabled = false;

	/**
	 * The Grouped Work that this record is connected to
	 * @var  GroupedWork */
	protected $groupedWork;
	protected $groupedWorkDriver = null;

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param   string $recordId The id of the record within OverDrive.
	 * @access  public
	 */
	public function __construct($recordId) {
		if (is_string($recordId)){
			//The record is the identifier for the overdrive title
			$this->id = $recordId;
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
			$this->overDriveProduct = new OverDriveAPIProduct();
			$this->overDriveProduct->overdriveId = $recordId;
			if ($this->overDriveProduct->find(true)){
				$this->valid = true;
			}else{
				$this->valid = false;
			}
			$this->loadGroupedWork();
		}
	}

	protected $itemsFromIndex;
	public function setItemsFromIndex($itemsFromIndex){
		$this->itemsFromIndex = $itemsFromIndex;
	}

	protected $detailedRecordInfoFromIndex;
	public function setDetailedRecordInfoFromIndex($detailedRecordInfoFromIndex){
		$this->detailedRecordInfoFromIndex = $detailedRecordInfoFromIndex;
	}

	public function setScopingEnabled($enabled){
		$this->scopingEnabled = $enabled;
	}
	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork() {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='overdrive' AND identifier = '" . $this->getUniqueID() . "'";
		$groupedWork->query($query);

		if ($groupedWork->N == 1){
			$groupedWork->fetch();
			$this->groupedWork = clone $groupedWork;
		}
	}

	public function getPermanentId(){
		return $this->getGroupedWorkId();
	}
	public function getGroupedWorkId(){
		if (!isset($this->groupedWork)){
			$this->loadGroupedWork();
		}
		if ($this->groupedWork){
			return $this->groupedWork->permanent_id;
		}else{
			return null;
		}

	}

	public function isValid(){
		return $this->valid;
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
	 * @param   string  $format     Citation format to display.
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCitation($format)
	{
		require_once ROOT_DIR . '/sys/CitationBuilder.php';

		// Build author list:
		$authors = array();
		$primary = $this->getAuthor();
		if (!empty($primary)) {
			$authors[] = $primary;
		}
		$authors = array_unique(array_merge($authors, $this->getContributors()));

		// Collect all details for citation builder:
		$publishers = $this->getPublishers();
		$pubDates = $this->getPublicationDates();
		$pubPlaces = $this->getPlacesOfPublication();
		$details = array(
				'authors' => $authors,
				'title' => $this->getTitle(),
				'subtitle' => $this->getSubtitle(),
				'pubPlace' => count($pubPlaces) > 0 ? $pubPlaces[0] : null,
				'pubName' => count($publishers) > 0 ? $publishers[0] : null,
				'pubDate' => count($pubDates) > 0 ? $pubDates[0] : null,
				'edition' => $this->getEdition(),
				'format' => $this->getFormats()
		);

		// Build the citation:
		$citation = new CitationBuilder($details);
		switch($format) {
			case 'APA':
				return $citation->getAPA();
			case 'AMA':
				return $citation->getAMA();
			case 'ChicagoAuthDate':
				return $citation->getChicagoAuthDate();
			case 'ChicagoHumanities':
				return $citation->getChicagoHumanities();
			case 'MLA':
				return $citation->getMLA();
		}
		return '';
	}

	/**
	 * Get an array of strings representing citation formats supported
	 * by this record's data (empty if none).  Legal values: "APA", "MLA".
	 *
	 * @access  public
	 * @return  array               Strings representing citation formats.
	 */
	public function getCitationFormats()
	{
		return array('AMA', 'APA', 'ChicagoHumanities', 'ChicagoAuthDate', 'MLA');
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
		require_once (ROOT_DIR . '/Drivers/OverDriveDriverFactory.php');
		$driver = OverDriveDriverFactory::getDriver();

		/** @var OverDriveAPIProductFormats[] $holdings */
		return $driver->getHoldings($this);
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
	public function getSearchResult() {
		// TODO: Implement getSearchResult() method.
	}

	public function getSeries(){
		$seriesData = $this->getGroupedWorkDriver()->getSeries();
		if ($seriesData == null){
			$seriesName = isset($this->getOverDriveMetaData()->getDecodedRawData()->series) ? $this->getOverDriveMetaData()->getDecodedRawData()->series : null;
			if ($seriesName != null){
				$seriesData = array(
					'seriesTitle' => $seriesName,
					'fromNovelist' => false,
				);
			}
		}
		return $seriesData;
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
		global $interface;

		$overDriveAPIProduct = new OverDriveAPIProduct();
		$overDriveAPIProduct->overdriveId = strtolower($this->id);
		$overDriveAPIProductMetaData = new OverDriveAPIProductMetaData();
		$overDriveAPIProduct->joinAdd($overDriveAPIProductMetaData, 'INNER');
		$overDriveAPIProduct->selectAdd(null);
		$overDriveAPIProduct->selectAdd("overdrive_api_products.rawData as productRaw");
		$overDriveAPIProduct->selectAdd("overdrive_api_product_metadata.rawData as metaDataRaw");
		if ($overDriveAPIProduct->find(true)){
			$productRaw = json_decode($overDriveAPIProduct->productRaw);
			//Remove links to overdrive that could be used to get semi-sensitive data
			unset($productRaw->links);
			unset($productRaw->contentDetails->account);
			$interface->assign('overDriveProductRaw', $productRaw);
			$interface->assign('overDriveMetaDataRaw', json_decode($overDriveAPIProduct->metaDataRaw));
		}

		return 'RecordDrivers/Marc/staff.tpl';
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
		return $this->id;
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

	function getLanguage(){
		$metaData = $this->getOverDriveMetaData()->getDecodedRawData();
		$languages = array();
		if (isset($metaData->languages)){
			foreach ($metaData->languages as $language){
				$languages[] = $language->name;
			}
		}
		return $languages;
	}

	private $availability = null;

	/**
	 * @return OverDriveAPIProductAvailability[]
	 */
	function getAvailability(){
		if ($this->availability == null){
			$this->availability = array();
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductAvailability.php';
			$availability = new OverDriveAPIProductAvailability();
			$availability->productId = $this->overDriveProduct->id;
			//Only include shared collection if include digital collection is on
			$searchLibrary = Library::getSearchLibrary();
			$searchLocation = Location::getSearchLocation();
			$includeSharedTitles = true;
			if($searchLocation != null){
				$includeSharedTitles = $searchLocation->includeDigitalCollection != 0;
			}elseif ($searchLibrary != null){
				$includeSharedTitles = $searchLibrary->includeDigitalCollection != 0;
			}
			$libraryScopingId = $this->getLibraryScopingId();
			if ($includeSharedTitles){
				$availability->whereAdd('libraryId = -1 OR libraryId = ' . $libraryScopingId);
			}else{
				if ($libraryScopingId == -1){
					return $this->availability;
				}else{
					$availability->whereAdd('libraryId = ' . $libraryScopingId);
				}
			}
			$availability->find();
			while ($availability->fetch()){
				$this->availability[] = clone $availability;
			}
		}
		return $this->availability;
	}

	function getRelatedRecords(){
		global $configArray;

		$recordId = $this->getUniqueID();
		$availability = $this->getAvailability();
		$available = false;
		$availableCopies = 0;
		$totalCopies = 0;
		$numHolds = 0;
		$hasLocalItem = true;
		foreach ($availability as $curAvailability){
			if ($curAvailability->available){
				$available = true;
				$availableCopies += $curAvailability->copiesAvailable;
			}
			$totalCopies += $curAvailability->copiesOwned;
		}

		//If there are no copies, this isn't valid
		if ($totalCopies == 0 && $availableCopies == 0){
			return array();
		}

		$url = $configArray['Site']['path'] . '/OverDrive/' . $recordId;
		$this->getOverDriveMetaData();
		$relatedRecord = array(
			'id' => $recordId,
			'url' => $url,
			'format' => ($this->overDriveProduct->mediaType == 'Audiobook' ? 'eAudiobook' : $this->overDriveProduct->mediaType),
			'edition' => '',
			'language' => $this->getLanguage(),
			'title' => $this->overDriveProduct->title,
			'subtitle' => $this->overDriveProduct->subtitle,
			'publisher' => $this->overDriveMetaData->publisher,
			'publicationDate' => $this->overDriveMetaData->publishDate,
			'section' => '',
			'physical' => '',
			'callNumber' => '',
			'shelfLocation' => '',
			'available' => $available,
			'hasLocalItem' => $hasLocalItem,
			'copies' => $totalCopies,
			'numHolds' => $numHolds,
			'availableCopies' => $availableCopies,
			'holdRatio' => $totalCopies > 0 ? ($availableCopies + ($totalCopies - $numHolds) / $totalCopies) : 0,
			'locationLabel' => 'Online',
			'source' => 'OverDrive',
			'actions' => array()
		);
		if ($available){
			$relatedRecord['actions'][] = array(
				'title' => 'Check Out',
				'onclick' => "return VuFind.OverDrive.checkoutOverDriveItemOneClick('{$recordId}');",
				'requireLogin' => false,
			);
		}else{
			$relatedRecord['actions'][] = array(
				'title' => 'Place Hold',
				'onclick' => "return VuFind.OverDrive.placeOverDriveHold('{$recordId}');",
				'requireLogin' => false,
			);
		}
		return array($relatedRecord);
	}

	public function getLibraryScopingId(){
		//For econtent, we need to be more specific when restricting copies
		//since patrons can't use copies that are only available to other libraries.
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		$activeLibrary = Library::getActiveLibrary();
		$activeLocation = Location::getActiveLocation();
		$homeLibrary = Library::getPatronHomeLibrary();

		//Load the holding label for the branch where the user is physically.
		if (!is_null($homeLibrary)){
			return $homeLibrary->includeOutOfSystemExternalLinks ? -1 : $homeLibrary->libraryId;
		}else if (!is_null($activeLocation)){
			$activeLibrary = Library::getLibraryForLocation($activeLocation->locationId);
			return $activeLibrary->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		}else if (isset($activeLibrary)) {
			return $activeLibrary->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		}else if (!is_null($searchLocation)){
			$searchLibrary = Library::getLibraryForLocation($searchLibrary->locationId);
			return $searchLibrary->includeOutOfSystemExternalLinks ? -1 : $searchLocation->libraryId;
		}else if (isset($searchLibrary)) {
			return $searchLibrary->includeOutOfSystemExternalLinks ? -1 : $searchLibrary->libraryId;
		}else{
			return -1;
		}
	}

	public function getDescriptionFast(){
		$metaData =  $this->getOverDriveMetaData();
		return $metaData->fullDescription;
	}

	public function getDescription(){
		$metaData =  $this->getOverDriveMetaData();
		return $metaData->fullDescription;
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

		// Get all the ISBNs and initialize the return value:
		$isbns = $this->getISBNs();
		$isbn13 = false;

		// Loop through the ISBNs:
		foreach($isbns as $isbn) {
			// Strip off any unwanted notes:
			if ($pos = strpos($isbn, ' ')) {
				$isbn = substr($isbn, 0, $pos);
			}

			// If we find an ISBN-10, return it immediately; otherwise, if we find
			// an ISBN-13, save it if it is the first one encountered.
			$isbnObj = new ISBN($isbn);
			if ($isbn10 = $isbnObj->get10()) {
				return $isbn10;
			}
			if (!$isbn13) {
				$isbn13 = $isbnObj->get13();
			}
		}
		return $isbn13;
	}

	/**
	 * Get an array of all ISBNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getISBNs()
	{
		//Load ISBNs for the product
		if ($this->isbns == null){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductIdentifiers.php';
			$overDriveIdentifiers = new OverDriveAPIProductIdentifiers();
			$overDriveIdentifiers->type = 'ISBN';
			$overDriveIdentifiers->productId = $this->overDriveProduct->id;
			$this->isbns = array();
			$overDriveIdentifiers->find();
			while ($overDriveIdentifiers->fetch()){
				$this->isbns[] = $overDriveIdentifiers->value;
			}
		}
		return $this->isbns;
	}

	/**
	 * Get an array of all UPCs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getUPCs()
	{
		//Load UPCs for the product
		if ($this->upcs == null){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductIdentifiers.php';
			$overDriveIdentifiers = new OverDriveAPIProductIdentifiers();
			$overDriveIdentifiers->type = 'UPC';
			$overDriveIdentifiers->productId = $this->overDriveProduct->id;
			$this->upcs = array();
			$overDriveIdentifiers->find();
			while ($overDriveIdentifiers->fetch()){
				$this->upcs[] = $overDriveIdentifiers->value;
			}
		}
		return $this->upcs;
	}

	public function getAcceleratedReaderData(){
		return $this->getGroupedWorkDriver()->getAcceleratedReaderData();
	}
	public function getLexileCode(){
		return $this->getGroupedWorkDriver()->getLexileCode();
	}
	public function getLexileScore(){
		return $this->getGroupedWorkDriver()->getLexileScore();
	}
	public function getSubjects(){
		return $this->getOverDriveMetaData()->getDecodedRawData()->subjects;
	}

	/**
	 * Get an array of all ASINs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getASINs()
	{
		//Load UPCs for the product
		if ($this->asins == null){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductIdentifiers.php';
			$overDriveIdentifiers = new OverDriveAPIProductIdentifiers();
			$overDriveIdentifiers->type = 'ASIN';
			$overDriveIdentifiers->productId = $this->overDriveProduct->id;
			$this->asins = array();
			$overDriveIdentifiers->find();
			while ($overDriveIdentifiers->fetch()){
				$this->asins[] = $overDriveIdentifiers->value;
			}
		}
		return $this->asins;
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle()
	{
		return $this->overDriveProduct->title;
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getSubTitle()
	{
		return $this->overDriveProduct->subtitle;
	}

	/**
	 * Get an array of all the formats associated with the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getFormats()
	{
		$formats = array();
		foreach ($this->getItems() as $item){
			$formats[] = $item->name;
		}
		return $formats;
	}

	public function getItems(){
		if ($this->items == null){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductFormats.php';
			$overDriveFormats = new OverDriveAPIProductFormats();
			$overDriveFormats->productId = $this->overDriveProduct->id;
			$overDriveFormats->find();
			$this->items = array();
			while ($overDriveFormats->fetch()){
				$this->items[] = clone $overDriveFormats;
			}
		}
		return $this->items;
	}

	public function getAuthor(){
		return $this->overDriveProduct->primaryCreatorName;
	}

	public function getContributors(){
		return array();
	}

	public function getBookcoverUrl($size = 'small'){
		global $configArray;
		$coverUrl = $configArray['Site']['url'] . '/bookcover.php?size=' . $size;
		$coverUrl .= '&id=' . $this->id;
		$coverUrl .= '&type=overdrive';
		return $coverUrl;
	}

	public function getCoverUrl($size = 'small'){
		return $this->getBookcoverUrl($size);
	}

	private function getOverDriveMetaData() {
		if ($this->overDriveMetaData == null){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductMetaData.php';
			$this->overDriveMetaData = new OverDriveAPIProductMetaData();
			$this->overDriveMetaData->productId = $this->overDriveProduct->id;
			$this->overDriveMetaData->find(true);
		}
		return $this->overDriveMetaData;
	}

	public function getRatingData() {
		require_once ROOT_DIR . '/services/API/WorkAPI.php';
		$workAPI = new WorkAPI();
		$groupedWorkId = $this->getGroupedWorkId();
		if ($groupedWorkId == null){
			return null;
		}else{
			return $workAPI->getRatingData($this->getGroupedWorkId());
		}
	}

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();

		//Load holdings information from the driver
		require_once (ROOT_DIR . '/Drivers/OverDriveDriverFactory.php');
		$driver = OverDriveDriverFactory::getDriver();

		/** @var OverDriveAPIProductFormats[] $holdings */
		$holdings = $driver->getHoldings($this);
		$scopedAvailability = $driver->getScopedAvailability($this);
		$interface->assign('availability', $scopedAvailability['mine']);
		$interface->assign('availabilityOther', $scopedAvailability['other']);
		$showAvailability = true;
		$showAvailabilityOther = true;
		$interface->assign('showAvailability', $showAvailability);
		$interface->assign('showAvailabilityOther', $showAvailabilityOther);
		$showOverDriveConsole = false;
		$showAdobeDigitalEditions = false;
		foreach ($holdings as $item){
			if (in_array($item->textId, array('ebook-epub-adobe', 'ebook-pdf-adobe'))){
				$showAdobeDigitalEditions = true;
			}else if (in_array($item->textId, array('video-wmv', 'music-wma', 'music-wma', 'audiobook-wma', 'audiobook-mp3'))){
				$showOverDriveConsole = true;
			}
		}
		$interface->assign('showOverDriveConsole', $showOverDriveConsole);
		$interface->assign('showAdobeDigitalEditions', $showAdobeDigitalEditions);

		$interface->assign('holdings', $holdings);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);
		$moreDetailsOptions['formats'] = array(
			'label' => 'Formats',
			'body' => $interface->fetch('OverDrive/view-formats.tpl'),
			'openByDefault' => true
		);
		//Other editions if applicable (only if we aren't the only record!)
		$relatedRecords = $this->getGroupedWorkDriver()->getRelatedRecords();
		if (count($relatedRecords) > 1){
			$interface->assign('relatedManifestations', $this->getGroupedWorkDriver()->getRelatedManifestations());
			$moreDetailsOptions['otherEditions'] = array(
					'label' => 'Other Editions',
					'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
					'hideByDefault' => false
			);
		}

		$moreDetailsOptions['moreDetails'] = array(
			'label' => 'More Details',
			'body' => $interface->fetch('OverDrive/view-more-details.tpl'),
		);
		$moreDetailsOptions['citations'] = array(
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		);
		$moreDetailsOptions['copyDetails'] = array(
			'label' => 'Copy Details',
			'body' => $interface->fetch('OverDrive/view-copies.tpl'),
		);
		if ($interface->getVariable('showStaffView')){
			$moreDetailsOptions['staff'] = array(
				'label' => 'Staff View',
				'body' => $interface->fetch($this->getStaffView()),
			);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getLinkUrl($useUnscopedHoldingsSummary = false) {
		global $interface;
		$id = $this->getUniqueID();
		$linkUrl = '/OverDrive/' . $id . '/Home?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page='  . $interface->get_template_vars('page');
		if ($useUnscopedHoldingsSummary){
			$linkUrl .= '&amp;searchSource=marmot';
		}else{
			$linkUrl .= '&amp;searchSource=' . $interface->get_template_vars('searchSource');
		}
		return $linkUrl;
	}

	function getQRCodeUrl(){
		global $configArray;
		return $configArray['Site']['url'] . '/qrcode.php?type=OverDrive&id=' . $this->getPermanentId();
	}

	private function getPublishers() {
		$publishers = array();
		if (isset($this->overDriveMetaData->publisher)){
			$publishers[] = $this->overDriveMetaData->publisher;
		}
		return $publishers;
	}

	private function getPublicationDates() {
		$publicationDates = array();
		if (isset($this->getOverDriveMetaData()->getDecodedRawData()->publishDateText)){
			$publishDate = $this->getOverDriveMetaData()->getDecodedRawData()->publishDateText;
			$publishYear = substr($publishDate, -4);
			$publicationDates[] = $publishYear;
		}
		return $publicationDates;
	}

	private function getPlacesOfPublication() {
		return array();
	}

	/**
	 * Get an array of publication detail lines combining information from
	 * getPublicationDates(), getPublishers() and getPlacesOfPublication().
	 *
	 * @access  public
	 * @return  array
	 */
	function getPublicationDetails()
	{
		$places = $this->getPlacesOfPublication();
		$names = $this->getPublishers();
		$dates = $this->getPublicationDates();

		$i = 0;
		$returnVal = array();
		while (isset($places[$i]) || isset($names[$i]) || isset($dates[$i])) {
			// Put all the pieces together, and do a little processing to clean up
			// unwanted whitespace.
			$publicationInfo = (isset($places[$i]) ? $places[$i] . ' ' : '') .
					(isset($names[$i]) ? $names[$i] . ' ' : '') .
					(isset($dates[$i]) ? $dates[$i] : '');
			$returnVal[] = trim(str_replace('  ', ' ', $publicationInfo));
			$i++;
		}

		return $returnVal;
	}

	public function getEdition($returnFirst = false) {
		$edition = isset($this->overDriveMetaData->getDecodedRawData()->edition) ? $this->overDriveMetaData->getDecodedRawData()->edition : null;
		if ($returnFirst || is_null($edition)){
			return $edition;
		}else{
			return array($edition);
		}
	}

	public function getStreetDate(){
		return isset($this->overDriveMetaData->getDecodedRawData()->publishDateText) ? $this->overDriveMetaData->getDecodedRawData()->publishDateText : null;
	}

	private function getGroupedWorkDriver() {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		if ($this->groupedWorkDriver == null){
			$this->groupedWorkDriver = new GroupedWorkDriver($this->getPermanentId());
		}
		return $this->groupedWorkDriver;
	}
	public function getTags(){
		return $this->getGroupedWorkDriver()->getTags();
	}

}