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
class OverDriveRecordDriver implements RecordInterface {

	private $id;
	/** @var OverDriveAPIProduct  */
	private $overDriveProduct;
	/** @var  OverDriveAPIProductMetaData */
	private $overDriveMetaData;
	private $valid;
	private $isbns;
	private $items;
	protected $scopingEnabled = false;

	/**
	 * The Grouped Work that this record is connected to
	 * @var  GroupedWork */
	protected $groupedWork;

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
		return $this->groupedWork->permanent_id;
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
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult() {
		// TODO: Implement getSearchResult() method.
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
		return 'English';
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
			$availability->whereAdd('libraryId = -1 OR libraryId = ' . $this->getLibraryScopingId());
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
				'url' => $url . '/CheckOut'
			);
		}else{
			$relatedRecord['actions'][] = array(
				'title' => 'Place Hold',
				'url' => $url . '/PlaceHold'
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
	protected function getISBNs()
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
		return null;
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
		return $workAPI->getRatingData($this->groupedWork->permanent_id);
	}

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();

		//Load more details options
		$moreDetailsOptions = array();
		$moreDetailsOptions['formats'] = array(
			'label' => 'Formats',
			'body' => '<div id="formatsPlaceholder">Loading...</div>',
			'openByDefault' => true
		);
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
			'body' => $interface->fetch('OverDrive/view-title-details.tpl'),
		);
		$moreDetailsOptions['citations'] = array(
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		);
		$moreDetailsOptions['copies'] = array(
			'label' => 'Copy Details',
			'body' => '<div id="copiesPlaceholder">Loading...</div>',
		);
		$moreDetailsOptions['staff'] = array(
			'label' => 'Staff View',
			'body' => $interface->fetch($this->getStaffView()),
		);

		return $moreDetailsOptions;
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
}