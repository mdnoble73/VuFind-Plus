<?php
/**
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once 'File/MARC.php';

require_once ROOT_DIR . '/RecordDrivers/IndexRecord.php';
require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';

/**
 * EContent Record Driver
 *
 * This class is designed to handle EContentRecord records.  Much of its functionality
 * is inherited from the default index-based driver.
 */
class EcontentRecordDriver extends IndexRecord
{
	/** @var EContentRecord */
	private $eContentRecord;
	/** @var File_MARC_Record|bool */
	private $marcRecord = false;
	public function __construct($record = null)
	{
		// Call the parent's constructor...
		parent::__construct($record);

		// Also process the MARC record:
		require_once ROOT_DIR . '/sys/MarcLoader.php';

	}

	public function setDataObject($eContentRecord){
		$this->eContentRecord = $eContentRecord;
		$this->marcRecord = MarcLoader::loadEContentMarcRecord($eContentRecord);
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getUniqueID()
	{
		$id = $this->fields['id'];
		//Strip off the econtent from the id
		$id = str_replace('econtentRecord', '', $id);
		return $id;
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult()
	{
		global $interface;
		global $user;
		global $logger;
		if (!isset($this->eContentRecord)){
			$this->eContentRecord = new EContentRecord();
			$this->eContentRecord->id = $this->getUniqueID();
			$this->eContentRecord->find(true);
		}
		$interface->assign('source', $this->eContentRecord->source);
		$interface->assign('eContentRecord', $this->eContentRecord);
		parent::getSearchResult();

		//Get Rating
		require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
		$econtentRating = new EContentRating();
		$econtentRating->recordId = $this->getUniqueID();
		$logger->log("Loading ratings for econtent record {$this->getUniqueID()}", PEAR_LOG_DEBUG);
		$interface->assign('summRating', $econtentRating->getRatingData($user, false));

		$interface->assign('summAjaxStatus', true);
		//Override fields as needed
		return 'RecordDrivers/Econtent/result.tpl';
	}

	public function getCitation($format){
		require_once ROOT_DIR . '/sys/CitationBuilder.php';

		// Build author list:
		$authors = array();
		$primary = $this->eContentRecord->author;
		if (!empty($primary)) {
			$authors[] = $primary;
		}
		$authors = array_unique(array_merge($authors, $this->eContentRecord->getPropertyArray('author2')));

		// Collect all details for citation builder:
		$publishers = array($this->eContentRecord->publisher);
		$pubDates = $this->eContentRecord->getPropertyArray('publishDate');
		$pubPlaces = array();
		$details = array(
            'authors' => $authors,
            'title' => $this->eContentRecord->title,
            'subtitle' => $this->eContentRecord->subTitle,
            'pubPlace' => count($pubPlaces) > 0 ? $pubPlaces[0] : null,
            'pubName' => count($publishers) > 0 ? $publishers[0] : null,
            'pubDate' => count($pubDates) > 0 ? $pubDates[0] : null,
            'edition' => $this->eContentRecord->getPropertyArray('edition'),
		        'source'  => $this->eContentRecord->source,
		        'format'  => $this->eContentRecord->format(),
		);

		// Build the citation:
		$citation = new CitationBuilder($details);
		switch($format) {
			case 'APA':
				return $citation->getAPA();
			case 'MLA':
				return $citation->getMLA();
			case 'AMA':
				return $citation->getAMA();
			case 'ChicagoAuthDate':
				return $citation->getChicagoAuthDate();
			case 'ChicagoHumanities':
				return $citation->getChicagoHumanities();
		}
		return '';
	}

	function getBookcoverUrl($id, $isbn, $upc, $formatCategory, $format){
		global $configArray;
		$bookCoverUrl = $configArray['Site']['coverUrl'] . "/bookcover.php?id={$id}&amp;econtent=true&amp;isn={$this->getCleanISBN()}&amp;size=small&amp;upc={$upc}&amp;category=" . urlencode($formatCategory) . "&amp;format=" . urlencode($format);
		return $bookCoverUrl;
	}

	public function getListEntry($user, $listId = null, $allowEdit = true)
	{
		global $interface;

		// Extract bibliographic metadata from the record:
		$id = $this->getUniqueID();
		$interface->assign('listId', $id);
		$shortId = trim($id, '.');
		$interface->assign('listShortId', $shortId);
		$interface->assign('listFormats', $this->getFormats());
		$interface->assign('listTitle', $this->getTitle());
		$interface->assign('listAuthor', $this->getPrimaryAuthor());
		$interface->assign('listISBN', $this->getCleanISBN());
		$interface->assign('listUPC', $this->getUPC());
		$interface->assign('listFormatCategory', $this->getFormatCategory());
		$interface->assign('listFormats', $this->getFormats());
		$interface->assign('listDate', $this->getPublicationDates());

		// Extract user metadata from the database:
		if ($user != false){
			$data = $user->getSavedData($id, $listId);
			foreach($data as $current) {
				if (!empty($current->notes)) {
					$notes[] = $current->notes;
				}
			}
			$notes = array();
			$interface->assign('listNotes', $notes);
			$interface->assign('listTags', $user->getTags($id, $listId));
		}

		// Pass some parameters along to the template to influence edit controls:
		$interface->assign('listSelected', $listId);
		$interface->assign('listEditAllowed', $allowEdit);

		//Get Rating
		require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
		$econtentRating = new EContentRating();
		$econtentRating->recordId = $id;
		$interface->assign('ratingData', $econtentRating->getRatingData($user, false));

		return 'RecordDrivers/Econtent/listentry.tpl';
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
	public function getExtendedMetadata()
	{
		global $interface;

		// Assign various values for display by the template; we'll prefix
		// everything with "extended" to avoid clashes with values assigned
		// elsewhere.
		$interface->assign('extendedSummary', $this->eContentRecord->description);
		$interface->assign('extendedAccess', $this->getAccessRestrictions());
		$interface->assign('extendedRelated', $this->getRelationshipNotes());
		$interface->assign('extendedNotes', $this->getGeneralNotes());
		$interface->assign('extendedDateSpan', $this->getDateSpan());
		$interface->assign('extendedISBNs', $this->eContentRecord->isbn);
		$interface->assign('extendedISSNs', $this->eContentRecord->issn);
		$interface->assign('extendedPhysical', $this->getPhysicalDescriptions());
		$interface->assign('extendedFrequency', $this->getPublicationFrequency());
		$interface->assign('extendedPlayTime', $this->getPlayingTimes());
		$interface->assign('extendedSystem', $this->getSystemDetails());
		$interface->assign('extendedAudience', $this->eContentRecord->target_audience);
		$interface->assign('extendedAwards', $this->getAwards());
		$interface->assign('extendedCredits', $this->getProductionCredits());
		$interface->assign('extendedBibliography', $this->getBibliographyNotes());
		$interface->assign('extendedFindingAids', $this->getFindingAids());

		return 'RecordDrivers/Index/extended.tpl';
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle()
	{
		if (isset($this->eContentRecord)){
			$title = $this->eContentRecord->title;
			if ($this->eContentRecord->subTitle != null && strlen($this->eContentRecord->subTitle) > 0){
				$title .= ': ' . $this->eContentRecord->subTitle;
			}
			return $title;
		}else{
			return parent::getTitle();
		}
	}

	protected function getShortTitle()
	{
		return isset($this->fields['title_short']) ? $this->fields['title_short'] : $this->eContentRecord->title;
	}

	/**
	 * Get an array of physical descriptions of the item.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPhysicalDescriptions()
	{
		if (isset($this->eContentRecord)){
			return array($this->eContentRecord->physicalDescription);
		}else{
			return parent::getPhysicalDescriptions();
		}
	}

	/**
	 * Get an array of publication detail lines combining information from
	 * getPublicationDates(), getPublishers() and getPlacesOfPublication().
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPublicationDetails()
	{
		if (isset($this->eContentRecord)){
			return array($this->eContentRecord->publishLocation . ' ' . $this->eContentRecord->publisher . ' ' . $this->eContentRecord->publishDate);
		}else{
			return parent::getPhysicalDescriptions();
		}
	}

	/**
	 * Get the item's place of publication.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPlacesOfPublication()
	{
		// Not currently stored in the Solr index
		if ($this->marcRecord){
			return $this->getFieldArray('260');
		}else if (isset($this->eContentRecord)){
			return array($this->eContentRecord->publishLocation);
		}else{
			return parent::getPlacesOfPublication();
		}
	}

/**
	 * Return an array of all values extracted from the specified field/subfield
	 * combination.  If multiple subfields are specified and $concat is true, they
	 * will be concatenated together in the order listed -- each entry in the array
	 * will correspond with a single MARC field.  If $concat is false, the return
	 * array will contain separate entries for separate subfields.
	 *
	 * @param   string      $field          The MARC field number to read
	 * @param   array       $subFields      The MARC subfield codes to read
	 * @param   bool        $concatenate         Should we concatenate subfields?
	 * @access  private
	 * @return  array
	 */
	private function getFieldArray($field, $subFields = null, $concatenate = true)
	{
		if (!$this->marcRecord){
			return array();
		}

		// Default to subField a if nothing is specified.
		if (!is_array($subFields)) {
			$subFields = array('a');
		}

		// Initialize return array
		$matches = array();

		// Try to look up the specified field, return empty array if it doesn't exist.
		$fields = $this->marcRecord->getFields($field);
		if (!is_array($fields)) {
			return $matches;
		}

		// Extract all the requested subfields, if applicable.
		foreach($fields as $currentField) {
			$next = $this->getSubfieldArray($currentField, $subFields, $concatenate);
			$matches = array_merge($matches, $next);
		}

		return $matches;
	}

	/**
	 * Return an array of non-empty subfield values found in the provided MARC
	 * field.  If $concat is true, the array will contain either zero or one
	 * entries (empty array if no subfields found, subfield values concatenated
	 * together in specified order if found).  If concat is false, the array
	 * will contain a separate entry for each subfield value found.
	 *
	 * @access  private
	 * @param   object      $currentField   Result from File_MARC::getFields.
	 * @param   array       $subFields      The MARC subfield codes to read
	 * @param   bool        $concatenate         Should we concatenate subfields?
	 * @return  array
	 */
	private function getSubfieldArray($currentField, $subFields, $concatenate = true)
	{
		// Start building a line of text for the current field
		$matches = array();
		$currentLine = '';

		// Loop through all specified subfields, collecting results:
		foreach($subFields as $subField) {
			$subFieldsResult = $currentField->getSubfields($subField);
			/** @var File_MARC_Subfield[] $subFieldsResult */
			if (is_array($subFieldsResult)) {
				foreach($subFieldsResult as $currentSubField) {
					// Grab the current subfield value and act on it if it is
					// non-empty:
					$data = trim($currentSubField->getData());
					if (!empty($data)) {
						// Are we concatenating fields or storing them separately?
						if ($concatenate) {
							$currentLine .= $data . ' ';
						} else {
							$matches[] = $data;
						}
					}
				}
			}
		}

		// If we're in concat mode and found data, it will be in $currentLine and
		// must be moved into the matches array.  If we're not in concat mode,
		// $currentLine will always be empty and this code will be ignored.
		if (!empty($currentLine)) {
			$matches[] = trim($currentLine);
		}

		// Send back our result array:
		return $matches;
	}

	public function getSupplementalSearchResult(){
		global $configArray;
		global $interface;
		global $user;
		global $logger;

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		if (substr($id, 0, 1) == '.'){
			$interface->assign('summShortId', substr($id, 1));
		}else{
			$interface->assign('summShortId', $id);
		}
		$formats = $this->getFormats();
		$interface->assign('summFormats', $formats);
		$formatCategories = $this->getFormatCategory();
		$interface->assign('summFormatCategory', $formatCategories);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summSubTitle', $this->getSubtitle());
		$interface->assign('summTitleStatement', $this->getTitleSection());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		$publishers = $this->getPublishers();
		$pubDates = $this->getPublicationDates();
		$pubPlaces = $this->getPlacesOfPublication();
		$interface->assign('summPublicationDates', $pubDates);
		$interface->assign('summPublishers', $publishers);
		$interface->assign('summPublicationPlaces',$pubPlaces);
		$interface->assign('summDate', $this->getPublicationDates());
		$interface->assign('summISBN', $this->getCleanISBN());
		$issn = $this->getCleanISSN();
		$interface->assign('summISSN', $issn);
		$upc = $this->getCleanUPC();
		$interface->assign('summUPC', $upc);
		if ($configArray['System']['debugSolr'] == 1){
			$interface->assign('summScore', $this->getScore());
			$interface->assign('summExplain', $this->getExplain());
		}
		$interface->assign('summPhysical', $this->getPhysicalDescriptions());
		$interface->assign('summEditions', $this->getEdition());

		// Obtain and assign snippet information:
		$snippet = $this->getHighlightedSnippet();
		$interface->assign('summSnippetCaption', $snippet ? $snippet['caption'] : false);
		$interface->assign('summSnippet', $snippet ? $snippet['snippet'] : false);

		//Get Rating
		require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
		$econtentRating = new EContentRating();
		$econtentRating->recordId = $id;
		$logger->log("Loading ratings for econtent record $id", PEAR_LOG_DEBUG);
		$interface->assign('summRating', $econtentRating->getRatingData($user, false));

		//Determine the cover to use
		$isbn = $this->getCleanISBN();
		$formatCategory = isset($formatCategories[0]) ? $formatCategories[0] : '';
		$format = isset($formats[0]) ? $formats[0] : '';

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl($id, $isbn, $upc, $formatCategory, $format));

		// By default, do not display AJAX status; we won't assume that all
		// records exist in the ILS.  Child classes can override this setting
		// to turn on AJAX as needed:
		$interface->assign('summAjaxStatus', false);


		return 'RecordDrivers/Econtent/supplementalResult.tpl';
	}

}