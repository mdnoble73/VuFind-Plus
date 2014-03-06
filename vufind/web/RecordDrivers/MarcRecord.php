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

/**
 * MARC Record Driver
 *
 * This class is designed to handle MARC records.  Much of its functionality
 * is inherited from the default index-based driver.
 */
class MarcRecord extends IndexRecord
{
	/** @var File_MARC_Record $marcRecord */
	protected $marcRecord;
	private $id;
	private $valid = true;

	/**
	 * @param array|File_MARC_Record|string $record
	 */
	public function __construct($record)
	{
		if ($record instanceof File_MARC_Record){
			$this->marcRecord = $record;
		}elseif (is_string($record)){
			require_once ROOT_DIR . '/sys/MarcLoader.php';
			$this->id = $record;
			$this->marcRecord = MarcLoader::loadMarcRecordByILSId($record);
			if (!$this->marcRecord) {
				$this->valid = false;
				global $errorHandlingEnabled;
				if ($errorHandlingEnabled){
					PEAR_Singleton::raiseError(new PEAR_Error('Cannot Process MARC Record for record ' . $record));
				}
			}
		}else{
			// Call the parent's constructor...
			parent::__construct($record);

			// Also process the MARC record:
			require_once ROOT_DIR . '/sys/MarcLoader.php';
			$this->marcRecord = MarcLoader::loadMarcRecordFromRecord($record);
			if (!$this->marcRecord) {
				$this->valid = false;
				global $errorHandlingEnabled;
				if ($errorHandlingEnabled){
					PEAR_Singleton::raiseError(new PEAR_Error('Cannot Process MARC Record for record ' . $record['id']));
				}
			}
		}
		if (!isset($this->id)){
			/** @var File_MARC_Data_Field $idField */
			$idField = $this->marcRecord->getField('907');
			if ($idField){
				$this->id = $idField->getSubfield('a')->getData();
			}
		}
		parent::loadGroupedWork();
	}

	public function isValid(){
		return $this->valid;
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
		if (isset($this->id)){
			return $this->id;
		}else{
			return $this->fields['id'];
		}
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to export the record in the requested format.  For
	 * legal values, see getExportFormats().  Returns null if format is
	 * not supported.
	 *
	 * @param   string  $format     Export format to display.
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getExport($format)
	{
		global $interface;

		switch(strtolower($format)) {
			case 'endnote':
				// This makes use of core metadata fields in addition to the
				// assignment below:
				header('Content-type: application/x-endnote-refer');
				$interface->assign('marc', $this->marcRecord);
				return 'RecordDrivers/Marc/export-endnote.tpl';
			case 'marc':
				$interface->assign('rawMarc', $this->marcRecord->toRaw());
				return 'RecordDrivers/Marc/export-marc.tpl';
			case 'rdf':
				header("Content-type: application/rdf+xml");
				$interface->assign('rdf', $this->getRDFXML());
				return 'RecordDrivers/Marc/export-rdf.tpl';
			case 'refworks':
				// To export to RefWorks, we actually have to redirect to
				// another page.  We'll do that here when the user requests a
				// RefWorks export, then we'll call back to this module from
				// inside RefWorks using the "refworks_data" special export format
				// to get the actual data.
				$this->redirectToRefWorks();
				break;
			case 'refworks_data':
				// This makes use of core metadata fields in addition to the
				// assignment below:
				header('Content-type: text/plain');
				$interface->assign('marc', $this->marcRecord);
				return 'RecordDrivers/Marc/export-refworks.tpl';
			default:
				return null;
		}
	}

	/**
	 * Get an array of strings representing formats in which this record's
	 * data may be exported (empty if none).  Legal values: "RefWorks",
	 * "EndNote", "MARC", "RDF".
	 *
	 * @access  public
	 * @return  array               Strings representing export formats.
	 */
	public function getExportFormats()
	{
		// Get an array of legal export formats (from config array, or use defaults
		// if nothing in config array).
		global $configArray;
		global $library;
		$active = isset($configArray['Export']) ?
		$configArray['Export'] : array('RefWorks' => true, 'EndNote' => true);

		// These are the formats we can possibly support if they are turned on in
		// config.ini:
		$possible = array('RefWorks', 'EndNote', 'MARC', 'RDF');

		// Check which formats are currently active:
		$formats = array();
		foreach($possible as $current) {
			if ($active[$current]) {
				if (!isset($library) || (strlen($library->exportOptions) > 0 &&  preg_match('/' . $library->exportOptions . '/i', $current))){
					//the library didn't filter out the export method
					$formats[] = $current;
				}
			}
		}

		// Send back the results:
		return $formats;
	}

	/**
	 * Get an XML RDF representation of the data in this record.
	 *
	 * @access  public
	 * @return  mixed               XML RDF data (false if unsupported or error).
	 */
	public function getRDFXML()
	{
		// Get Record as MARCXML
		$xml = trim($this->marcRecord->toXML());

		// Load Stylesheet
		$style = new DOMDocument;
		//$style->load('services/Record/xsl/MARC21slim2RDFDC.xsl');
		$style->load('services/Record/xsl/record-rdf-mods.xsl');

		// Setup XSLT
		$xsl = new XSLTProcessor();
		$xsl->importStyleSheet($style);

		// Transform MARCXML
		$doc = new DOMDocument;
		if ($doc->loadXML($xml)) {
			return $xsl->transformToXML($doc);
		}

		// If we got this far, something went wrong.
		return false;
	}

	/**
	 * Assign necessary Smarty variables and return a template name for the current
	 * view to load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @param string $view The current view.
	 * @param boolean $useUnscopedHoldingsSummary Whether or not the $result should show an unscoped holdings summary.
	 *
	 * @return string      Name of Smarty template file to display.
	 * @access public
	 */
	public function getSearchResult($view = 'list', $useUnscopedHoldingsSummary = false)
	{
		global $interface;

		// MARC results work just like index results, except that we want to
		// enable the AJAX status display since we assume that MARC records
		// come from the ILS:
		$template = parent::getSearchResult($view, $useUnscopedHoldingsSummary);
		$interface->assign('summAjaxStatus', true);
		return $template;
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

		// Get Record as MARCXML
		/*$xml = trim($this->marcRecord->toXML());

		// Transform MARCXML
		$style = new DOMDocument;
		$style->load('services/Record/xsl/record-marc.xsl');
		$xsl = new XSLTProcessor();
		$xsl->importStyleSheet($style);
		$doc = new DOMDocument;
		if ($doc->loadXML($xml)) {
			$html = $xsl->transformToXML($doc);
			$interface->assign('details', $html);
		}else{
			$interface->assign('details', 'MARC record could not be read.');
		}*/

		$interface->assign('marcRecord', $this->marcRecord);

		$solrRecord = $this->fields;
		if ($solrRecord){
			ksort($solrRecord);
		}
		$interface->assign('solrRecord', $solrRecord);
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
	public function getTOC()
	{
		global $interface;

		// Return null if we have no table of contents:
		$fields = $this->marcRecord->getFields('505');
		if (!$fields) {
			return null;
		}

		// If we got this far, we have a table -- collect it as a string:
		$toc = '';
		foreach($fields as $field) {
			$subfields = $field->getSubfields();
			foreach($subfields as $subfield) {
				$toc .= $subfield->getData();
			}
		}

		// Assign the appropriate variable and return the template name:
		$interface->assign('toc', $toc);
		return 'RecordDrivers/Marc/toc.tpl';
	}

	/**
	 * Does this record have a Table of Contents available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasTOC()
	{
		// Is there a table of contents in the MARC record?
		if ($this->marcRecord->getFields('505')) {
			return true;
		}
		return false;
	}

	/**
	 * Does this record support an RDF representation?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasRDF()
	{
		return true;
	}

	/**
	 * Get access restriction notes for the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getAccessRestrictions()
	{
		return $this->getFieldArray('506');
	}

	/**
	 * Get all subject headings associated with this record.  Each heading is
	 * returned as an array of chunks, increasing from least specific to most
	 * specific.
	 *
	 * @access  protected
	 * @return array
	 */
	protected function getAllSubjectHeadings()
	{
		// These are the fields that may contain subject headings:
		$fields = array('600', '610', '630', '650', '651', '655');

		// This is all the collected data:
		$retval = array();

		// Try each MARC field one at a time:
		foreach($fields as $field) {
			// Do we have any results for the current field?  If not, try the next.
			$results = $this->marcRecord->getFields($field);
			if (!$results) {
				continue;
			}

			// If we got here, we found results -- let's loop through them.
			foreach($results as $result) {
				// Start an array for holding the chunks of the current heading:
				$current = array();

				// Get all the chunks and collect them together:
				$subfields = $result->getSubfields();
				if ($subfields) {
					foreach($subfields as $subfield) {
						//Add unless this is 655 subfield 2
						if ($subfield->getCode() == 2){
							//Suppress this code
						}else{
							$current[] = $subfield->getData();
						}
					}
					// If we found at least one chunk, add a heading to our $result:
					if (!empty($current)) {
						$retval[] = $current;
					}
				}
			}
		}

		// Send back everything we collected:
		return $retval;
	}

	/**
	 * Get award notes for the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getAwards()
	{
		return $this->getFieldArray('586');
	}

	/**
	 * Get notes on bibliography content.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getBibliographyNotes()
	{
		return $this->getFieldArray('504');
	}

	/**
	 * Get the main corporate author (if any) for the record.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function getCorporateAuthor()
	{
		return $this->getFirstFieldValue('110', array('a', 'b'));
	}

	/**
	 * Return an array of all values extracted from the specified field/subfield
	 * combination.  If multiple subfields are specified and $concat is true, they
	 * will be concatenated together in the order listed -- each entry in the array
	 * will correspond with a single MARC field.  If $concat is false, the return
	 * array will contain separate entries for separate subfields.
	 *
	 * @param   string      $field          The MARC field number to read
	 * @param   array       $subfields      The MARC subfield codes to read
	 * @param   bool        $concat         Should we concatenate subfields?
	 * @access  private
	 * @return  array
	 */
	private function getFieldArray($field, $subfields = null, $concat = true)
	{
		// Default to subfield a if nothing is specified.
		if (!is_array($subfields)) {
			$subfields = array('a');
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
			$next = $this->getSubfieldArray($currentField, $subfields, $concat);
			$matches = array_merge($matches, $next);
		}

		return $matches;
	}

	/**
	 * Get the edition of the current record.
	 *
	 * @access  protected
	 * @param   boolean $returnFirst whether or not only the first value is desired
	 * @return  string
	 */
	protected function getEdition($returnFirst = false)
	{
		if ($returnFirst){
			return $this->getFirstFieldValue('250');
		}else{
			return $this->getFieldArray('250');
		}

	}

	/**
	 * Get notes on finding aids related to the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getFindingAids()
	{
		return $this->getFieldArray('555');
	}

	/**
	 * Get the first value matching the specified MARC field and subfields.
	 * If multiple subfields are specified, they will be concatenated together.
	 *
	 * @param   string      $field          The MARC field to read
	 * @param   array       $subfields      The MARC subfield codes to read
	 * @access  private
	 * @return  string
	 */
	private function getFirstFieldValue($field, $subfields = null)
	{
		$matches = $this->getFieldArray($field, $subfields);
		return (is_array($matches) && count($matches) > 0) ?
		$matches[0] : null;
	}

	/**
	 * Get general notes on the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getGeneralNotes()
	{
		return $this->getFieldArray('500');
	}

	/**
	 * Get the item's places of publication.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPlacesOfPublication()
	{
		$placesOfPublication = $this->getFieldArray('260', array('a'));
		$placesOfPublication2 = $this->getFieldArray('264', array('a'));
		return array_merge($placesOfPublication, $placesOfPublication2);
	}

	/**
	 * Get an array of playing times for the record (if applicable).
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPlayingTimes()
	{
		$times = $this->getFieldArray('306', array('a'), false);

		// Format the times to include colons ("HH:MM:SS" format).
		for ($x = 0; $x < count($times); $x++) {
			$times[$x] = substr($times[$x], 0, 2) . ':' .
			substr($times[$x], 2, 2) . ':' .
			substr($times[$x], 4, 2);
		}

		return $times;
	}

	/**
	 * Get credits of people involved in production of the item.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getProductionCredits()
	{
		return $this->getFieldArray('508');
	}

	/**
	 * Get an array of publication frequency information.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPublicationFrequency()
	{
		return $this->getFieldArray('310', array('a', 'b'));
	}

	/**
	 * Get an array of strings describing relationships to other items.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getRelationshipNotes()
	{
		return $this->getFieldArray('580');
	}

	/**
	 * Get an array of all series names containing the record.  Array entries may
	 * be either the name string, or an associative array with 'name' and 'number'
	 * keys.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getSeries()
	{
		$matches = array();

		// First check the 440, 800 and 830 fields for series information:
		$primaryFields = array(
            '440' => array('a', 'p'),
            '800' => array('a', 'b', 'c', 'd', 'f', 'p', 'q', 't'),
            '830' => array('a', 'p'));
		$matches = $this->getSeriesFromMARC($primaryFields);
		if (!empty($matches)) {
			return $matches;
		}

		// Now check 490 and display it only if 440/800/830 were empty:
		$secondaryFields = array('490' => array('a'));
		$matches = $this->getSeriesFromMARC($secondaryFields);
		if (!empty($matches)) {
			return $matches;
		}

		// Still no results found?  Resort to the Solr-based method just in case!
		return parent::getSeries();
	}

	/**
	 * Support method for getSeries() -- given a field specification, look for
	 * series information in the MARC record.
	 *
	 * @access  private
	 * @param   $fieldInfo  array           Associative array of field => subfield
	 *                                      information (used to find series name)
	 * @return  array                       Series data (may be empty)
	 */
	private function getSeriesFromMARC($fieldInfo)
	{
		$matches = array();

		// Loop through the field specification....
		foreach($fieldInfo as $field => $subfields) {
			// Did we find any matching fields?
			$series = $this->marcRecord->getFields($field);
			if (is_array($series)) {
				foreach($series as $currentField) {
					// Can we find a name using the specified subfield list?
					$name = $this->getSubfieldArray($currentField, $subfields);
					if (isset($name[0])) {
						$currentArray = array('name' => $name[0]);

						// Can we find a number in subfield v?  (Note that number is
						// always in subfield v regardless of whether we are dealing
						// with 440, 490, 800 or 830 -- hence the hard-coded array
						// rather than another parameter in $fieldInfo).
						$number = $this->getSubfieldArray($currentField, array('v'));
						if (isset($number[0])) {
							$currentArray['number'] = $number[0];
						}

						// Save the current match:
						$matches[] = $currentArray;
					}
				}
			}
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
	 * @param   object      $currentField   $result from File_MARC::getFields.
	 * @param   array       $subfields      The MARC subfield codes to read
	 * @param   bool        $concat         Should we concatenate subfields?
	 * @return  array
	 */
	private function getSubfieldArray($currentField, $subfields, $concat = true)
	{
		// Start building a line of text for the current field
		$matches = array();
		$currentLine = '';

		// Loop through all specified subfields, collecting results:
		foreach($subfields as $subfield) {
			$subfieldsResult = $currentField->getSubfields($subfield);
			if (is_array($subfieldsResult)) {
				foreach($subfieldsResult as $currentSubfield) {
					// Grab the current subfield value and act on it if it is
					// non-empty:
					$data = trim($currentSubfield->getData());
					if (!empty($data)) {
						// Are we concatenating fields or storing them separately?
						if ($concat) {
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

		// Send back our $result array:
		return $matches;
	}

	/**
	 * Get an array of summary strings for the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getSummary()
	{
		return $this->getFieldArray('520');
	}

	/**
	 * Get an array of technical details on the item represented by the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getSystemDetails()
	{
		return $this->getFieldArray('538');
	}

	/**
	 * Get an array of note about the record's target audience.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getTargetAudienceNotes()
	{
		return $this->getFieldArray('521');
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle()
	{
		return $this->getFirstFieldValue('245', array('a', 'b'));
	}

	/**
	 * Get the title of the record.
	 *
	 * @return  string
	 */
	public function getSubtitle()
	{
		return $this->getFirstFieldValue('245', array('b'));
	}

	/**
	 * Get the text of the part/section portion of the title.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function getTitleSection()
	{
		return $this->getFirstFieldValue('245', array('n', 'p'));
	}

	/**
	 * Get the statement of responsibility that goes with the title (i.e. "by John Smith").
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function getTitleStatement()
	{
		return $this->getFirstFieldValue('245', array('c'));
	}

	/**
	 * Return an associative array of URLs associated with this record (key = URL,
	 * value = description).
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getURLs()
	{
		$retVal = array();

		$urls = $this->marcRecord->getFields('856');
		if ($urls) {
			foreach($urls as $url) {
				// Is there an address in the current field?
				$address = $url->getSubfield('u');
				if ($address) {
					$address = $address->getData();

					// Is there a description?  If not, just use the URL itself.
					$desc = $url->getSubfield('z');
					if ($desc) {
						$desc = $desc->getData();
					} else {
						$desc = $address;
					}

					$retVal[$address] = $desc;
				}
			}
		}

		return $retVal;
	}

	/**
	 * Redirect to the RefWorks site and then die -- support method for getExport().
	 *
	 * @access  protected
	 */
	protected function redirectToRefWorks()
	{
		global $configArray;

		// Build the URL to pass data to RefWorks:
		$exportUrl = $configArray['Site']['url'] . '/Record/' .
		urlencode($this->getUniqueID()) . '/Export?style=refworks_data';

		// Build up the RefWorks URL:
		$url = $configArray['RefWorks']['url'] . '/express/expressimport.asp';
		$url .= '?vendor=' . urlencode($configArray['RefWorks']['vendor']);
		$url .= '&filter=RefWorks%20Tagged%20Format&url=' . urlencode($exportUrl);

		header("Location: {$url}");
		die();
	}

	public function getAuthor(){
		if (isset($this->fields['auth_author'])){
			return $this->fields['auth_author'];
		}else{
			$author = $this->getFirstFieldValue('100', array('a'));
			if (empty($author )){
				$author = $this->getFirstFieldValue('110', array('a'));
			}
			return $author;
		}
	}

	/**
	 * Get the text to represent this record in the body of an email.
	 *
	 * @access  public
	 * @return  string              Text for inclusion in email.
	 */
	public function getEmail()
	{
		global $configArray;
		global $interface;

		// Get Holdings
		try {
			$catalog = new CatalogConnection($configArray['Catalog']['driver']);
		} catch (PDOException $e) {
			return new PEAR_Error('Cannot connect to ILS');
		}
		$holdingsSummary = $catalog->getStatusSummary($_GET['id']);
		if (PEAR_Singleton::isError($holdingsSummary)) {
			return $holdingsSummary;
		}

		$email = "  " . $this->getTitle() . "\n";
		if (isset($holdingsSummary['callnumber'])){
			$email .= "  Call Number: " . $holdingsSummary['callnumber'] . "\n";
		}
		if (isset($holdingsSummary['availableAt'])){
			$email .= "  Available At: " . $holdingsSummary['availableAt'] . "\n";
		}
		if (isset($holdingsSummary['downloadLink'])){
			$email .= "  Download from: " . $holdingsSummary['downloadLink'] . "\n";
		}


		return $email;
	}

	function getDescriptionFast(){
		/** @var File_MARC_Data_Field $descriptionField */
		$descriptionField = $this->marcRecord->getField('520');
		if ($descriptionField != null){
			return $descriptionField->getSubfield('a')->getData();
		}
		return null;
	}

	function getDescription(){
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $interface;
		global $timer;
		$id = $this->getUniqueID();
		//Bypass loading solr, etc if we already have loaded the descriptive info before
		$descriptionArray = $memCache->get("record_description_{$id}");
		if (!$descriptionArray){
			require_once ROOT_DIR . '/services/Record/Description.php';

			$timer->logTime("Starting to load description for marc record");
			$descriptionArray = Record_Description::loadDescriptionFromMarc($this->marcRecord, false);
			$memCache->set("record_description_{$id}", $descriptionArray, 0, $configArray['Caching']['record_description']);
			$timer->logTime("Retrieved description for marc record");
		}
		$interface->assign('description', $descriptionArray['description']);
		$interface->assign('length', isset($descriptionArray['length']) ? $descriptionArray['length'] : '');
		$interface->assign('publisher', isset($descriptionArray['publisher']) ? $descriptionArray['publisher'] : '');

		return $interface->fetch('Record/ajax-description-popup.tpl');
	}

	function getLanguage(){
		/** @var File_MARC_Control_Field $field008 */
		$field008 = $this->marcRecord->getField('008');
		if ($field008 != null && strlen($field008->getData() >= 37)){
			$languageCode = substr($field008->getData(), 35, 3);
			if ($languageCode == 'eng'){
				$languageCode = "English";
			}elseif ($languageCode == 'spa'){
				$languageCode = "Spanish";
			}
			return $languageCode;
		}else{
			return 'English';
		}
	}

	function getFormat(){
		$result = array();

		$leader = $this->marcRecord->getLeader();
		/** @var File_MARC_Control_Field $fixedField */
		$fixedField = $this->marcRecord->getField("008");

		// check for music recordings quickly so we can figure out if it is music
		// for category (need to do here since checking what is on the Compact
		// Disc/Phonograph, etc is difficult).
		if (strlen($leader) >= 6) {
			$leaderBit = strtoupper($leader[6]);
			switch ($leaderBit) {
				case 'J':
					$result[] = "Music Recording";
					break;
			}
		}

		// check for playaway in 260|b
		/** @var File_MARC_Data_Field $sysDetailsNote */
		$sysDetailsNote = $this->marcRecord->getField("260");
		if ($sysDetailsNote != null) {
			if ($sysDetailsNote->getSubfield('b') != null) {
				$sysDetailsValue = strtolower($sysDetailsNote->getSubfield('b')->getData());
				if (strpos($sysDetailsValue, "playaway") !== FALSE) {
					$result[] = "Playaway";
				}
			}
		}

		// Check for formats in the 538 field
		$sysDetailsValue = strtolower($this->getFirstFieldValue("538"));
		if ($sysDetailsValue != null) {
			if (strpos($sysDetailsValue, "playaway") !== FALSE) {
				$result[] =  "Playaway";
			} else if (strpos($sysDetailsValue, "kinect sensor") !== FALSE) {
				$result[] =  "Xbox 360 Kinect";
			} else if (strpos($sysDetailsValue, "xbox") !== FALSE) {
				$result[] =  "Xbox 360";
			} else if (strpos($sysDetailsValue, "playstation 3") !== FALSE) {
				$result[] =  "PlayStation 3";
			} else if (strpos($sysDetailsValue, "playstation") !== FALSE) {
				$result[] =  "PlayStation";
			} else if (strpos($sysDetailsValue, "nintendo wii") !== FALSE) {
				$result[] =  "Nintendo Wii";
			} else if (strpos($sysDetailsValue, "directx") !== FALSE) {
				$result[] =  "Windows Game";
			} else if (strpos($sysDetailsValue, "bluray") !== FALSE
					|| strpos($sysDetailsValue, "blu-ray") !== FALSE) {
				$result[] =  "Blu-ray";
			} else if (strpos($sysDetailsValue, "dvd") !== FALSE) {
				$result[] =  "DVD";
			} else if (strpos($sysDetailsValue, "vertical file") !== FALSE) {
				$result[] =  "Vertical File";
			}
		}

		// Check for formats in the 500 tag
		/** @var File_MARC_Data_Field $sysDetailsNote2 */
		$noteValue = strtolower($this->getFirstFieldValue("500"));
		if ($noteValue) {
			if (strpos($noteValue, "vertical file") != FALSE) {
				$result[] =  "Vertical File";
			}
		}

		// Check for large print book (large format in 650, 300, or 250 fields)
		// Check for blu-ray in 300 fields
		$edition = strtolower($this->getFirstFieldValue("250"));
		if ($edition != null) {
			if (strpos($edition, "large type") !== FALSE) {
				$result[] =  "Large Print";
			}
		}

		$physicalDescriptions = $this->getFieldArray("300");
		foreach($physicalDescriptions as $physicalDescription){
			$physicalDescription = strtolower($physicalDescription);
			if (strpos($physicalDescription, "large type") !== FALSE) {
				$result[] =  "Large Print";
			} else if (strpos($physicalDescription, "bluray") !== FALSE
					|| strpos($physicalDescription, "blu-ray") !== FALSE) {
				$result[] =  "Blu-ray";
			} else if (strpos($physicalDescription, "computer optical disc") !== FALSE) {
				$result[] =  "Computer Software";
			} else if (strpos($physicalDescription, "sound cassettes") !== FALSE) {
				$result[] =  "Audio Cassette";
			} else if (strpos($physicalDescription, "sound discs") !== FALSE) {
				$result[] =  "Audio CD";
			}
		}

		$topicalTerms = $this->getFieldArray("650");
		foreach ($topicalTerms as $topicalTerm){
			$topicalTerm = strtolower($topicalTerm);
			if (strpos($topicalTerm, "large type") !== FALSE){
				$result[] =  "Large Print";
			}
		}

		$localTopicalTerms = $this->getFieldArray("690");
		foreach ($localTopicalTerms as $topicalTerm){
			$topicalTerm = strtolower($topicalTerm);
			if (strpos($topicalTerm, "seed library") !== FALSE){
				$result[] =  "Seed Packet";
			}
		}

		$addedAuthors = $this->getFieldArray("710");
		foreach ($addedAuthors as $addedAuthor){
			$addedAuthor = strtolower($addedAuthor);
			if (strpos($addedAuthor, "playaway digital audio") !== FALSE || strpos($addedAuthor, "findaway world") !== FALSE){
				$result[] =  "Playaway";
			}
		}

		// check the 007 - this is a repeating field
		$fields = $this->marcRecord->getFields("007");
		if ($fields != null) {
			/** @var File_MARC_Control_Field $formatField */
			foreach ($fields as $formatField) {
				if ($formatField->getData() == null || strlen($formatField->getData()) < 2) {
					continue;
				}
				// Check for blu-ray (s in position 4)
				// This logic does not appear correct.
				/*
				 * if (formatField.getData() != null && formatField.getData().length()
				 * >= 4){ if (formatField.getData().toUpperCase().charAt(4) == 'S'){
				 * $result[] =  "Blu-ray"; break; } }
				 */
				$formatCode = strtoupper($formatField->getData());
				$firstCharacter = substr($formatCode, 0, 1);
				$secondCharacter = substr($formatCode, 1, 1);
				switch ($firstCharacter) {
					case 'A':
						switch ($secondCharacter) {
							case 'D':
								$result[] =  "Atlas";
								break;
							default:
								$result[] =  "Map";
								break;
						}
						break;
					case 'C':
						switch ($secondCharacter) {
							case 'A':
								$result[] =  "Software";
								break;
							case 'B':
								$result[] =  "Software";
								break;
							case 'C':
								$result[] =  "Software";
								break;
							case 'F':
								$result[] =  "Tape Cassette";
								break;
							case 'H':
								$result[] =  "Tape Reel";
								break;
							case 'J':
								$result[] =  "Floppy Disk";
								break;
							case 'M':
							case 'O':
								$result[] =  "CD-ROM";
								break;
							case 'R':
								// Do not return - this will cause anything with an
								// 856 field to be labeled as "Electronic"
								break;
							default:
								$result[] =  "Software";
								break;
						}
						break;
					case 'D':
						$result[] =  "Globe";
						break;
					case 'F':
						$result[] =  "Braille";
						break;
					case 'G':
						switch ($secondCharacter) {
							case 'C':
							case 'D':
								$result[] =  "Filmstrip";
								break;
							case 'T':
								$result[] =  "Transparency";
								break;
							default:
								$result[] =  "Slide";
								break;
						}
						break;
					case 'H':
						$result[] =  "Microfilm";
						break;
					case 'K':
						switch ($secondCharacter) {
							case 'C':
								$result[] =  "Collage";
								break;
							case 'D':
								$result[] =  "Drawing";
								break;
							case 'E':
								$result[] =  "Painting";
								break;
							case 'F':
								$result[] =  "Print";
								break;
							case 'G':
								$result[] =  "Photo";
								break;
							case 'J':
								$result[] =  "Print";
								break;
							case 'L':
								$result[] =  "Drawing";
								break;
							case 'O':
								$result[] =  "Flash Card";
								break;
							case 'N':
								$result[] =  "Chart";
								break;
							default:
								$result[] =  "Photo";
								break;
						}
						break;
					case 'M':
						switch ($secondCharacter) {
							case 'F':
								$result[] =  "VHS";
								break;
							case 'R':
								$result[] =  "Video";
								break;
							default:
								$result[] =  "Video";
								break;
						}
						break;
					case 'O':
						$result[] =  "Kit";
						break;
					case 'Q':
						$result[] =  "Musical Score";
						break;
					case 'R':
						$result[] =  "Sensor Image";
						break;
					case 'S':
						switch ($secondCharacter) {
							case 'D':
								if (strlen($formatCode) >= 4) {
									$speed = substr($formatCode, 3, 1);
									if ($speed >= 'A' && $speed <= 'E') {
										$result[] =  "Phonograph";
									} else if ($speed == 'F') {
										$result[] =  "Audio CD";
									} else if ($speed >= 'K' && $speed <= 'R') {
										$result[] =  "Tape Recording";
									} else {
										$result[] =  "CD";
									}
								} else {
									$result[] =  "CD";
								}
								break;
							case 'S':
								$result[] =  "Audio Cassette";
								break;
							default:
								$result[] =  "Audio";
								break;
						}
						break;
					case 'T':
						switch ($secondCharacter) {
							case 'A':
								$result[] =  "Book";
								break;
							case 'B':
								$result[] =  "Large Print";
								break;
						}
						break;
					case 'V':
						switch ($secondCharacter) {
							case 'C':
								$result[] =  "Video";
								break;
							case 'D':
								$result[] =  "DVD";
								break;
							case 'F':
								$result[] =  "VHS";
								break;
							case 'R':
								$result[] =  "Video";
								break;
							default:
								$result[] =  "Video";
								break;
						}
						break;
				}
			}
		}

		// check the Leader at position 6
		if (strlen($leader) >= 6) {
			$leaderBit = strtoupper(substr($leader, 6, 1));
			switch ($leaderBit) {
				case 'C':
				case 'D':
					$result[] =  "Musical Score";
					break;
				case 'E':
				case 'F':
					$result[] =  "Map";
					break;
				case 'G':
					// We appear to have a number of items without 007 tags marked as G's.
					// These seem to be Videos rather than Slides.
					// $result[] =  "Slide";
					$result[] =  "Video";
					break;
				case 'I':
					$result[] =  "Sound Recording";
					break;
				case 'J':
					$result[] =  "Music Recording";
					break;
				case 'K':
					$result[] =  "Photo";
					break;
				case 'M':
					$result[] =  "Electronic";
					break;
				case 'O':
				case 'P':
					$result[] =  "Kit";
					break;
				case 'R':
					$result[] =  "Physical Object";
					break;
				case 'T':
					$result[] =  "Manuscript";
					break;
			}
		}

		if (strlen($leader) >= 7) {
			// check the Leader at position 7
			$leaderBit = strtoupper(substr($leader, 7, 1));
			switch ($leaderBit) {
				// Monograph
				case 'M':
					if (count($result) == 0) {
						$result[] =  "Book";
					}
					break;
				// Serial
				case 'S':
					// Look in 008 to determine what type of Continuing Resource
					$formatCode = substr(strtoupper($fixedField->getData()), 21, 1);
					switch ($formatCode) {
						case 'N':
							$result[] =  "Newspaper";
							break;
						case 'P':
							$result[] =  "Journal";
							break;
						default:
							$result[] =  "Serial";
							break;
					}
			}
		}

		// Nothing worked!
		if (count($result) == 0) {
			$result[] =  "Unknown";
		}

		return $result;
	}

	function getRecordUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		return $configArray['Site']['path'] . '/Record/' . $recordId;
	}
	function getRelatedRecords(){
		global $configArray;
		global $timer;
		$relatedRecords = array();
		$recordId = $this->getUniqueID();

		$url = $this->getRecordUrl();
		$holdUrl = $configArray['Site']['path'] . '/Record/' . $recordId . '/Hold';

		//Remove OverDrive records that are not formatted properly
		/** @var File_MARC_Data_Field $field856 */
		$field856 = $this->marcRecord->getField('856');
		if ($field856 != null){
			$subfieldU = $field856->getSubfield('u');
			if ($subfieldU != null && strpos($subfieldU->getData(), 'overdrive.com') !== FALSE){
				//Check items to make sure that we got something with |g
				$hasEContentSubfield = false;
				/** @var File_MARC_Data_Field[] $itemFields */
				$itemFields = $this->marcRecord->getField('989');
				foreach ($itemFields as $item){
					if ($item->getSubfield('w') != null){
						$hasEContentSubfield = true;
						break;
					}
				}
				if (!$hasEContentSubfield){
					return $relatedRecords;
				}
			}
		}
		$timer->logTime("Finished making sure the record is not eContent");

		$publishers = $this->getPublishers();
		$publisher = count($publishers) >= 1 ? $publishers[0] : '';
		$publicationDates = $this->getPublicationDates();
		$publicationDate = count($publicationDates) >= 1 ? $publicationDates[0] : '';
		$physicalDescriptions = $this->getPhysicalDescriptions();
		$physicalDescription = count($physicalDescriptions) >= 1 ? $physicalDescriptions[0] : '';
		$timer->logTime("Finished loading publication info");

		$totalCopies = $this->getNumCopies();
		//Don't add records the user can't get.
		if ($totalCopies == 0){
			return $relatedRecords;
		}
		$availableCopies = $this->getAvailableCopies(false);
		$hasLocalItem = $this->hasLocalItem();
		$numHolds = 0;
		$relatedRecord = array(
			'id' => $recordId,
			'url' => $url,
			'holdUrl' => $holdUrl,
			'format' => reset($this->getFormat()),
			'edition' => $this->getEdition(true),
			'language' => $this->getLanguage(),
			'title' => $this->getTitle(),
			'subtitle' => $this->getSubtitle(),
			'publisher' => $publisher,
			'publicationDate' => $publicationDate,
			'section' => $this->getTitleSection(),
			'physical' => $physicalDescription,
			'callNumber' => $this->getCallNumber(),
			'available' => $this->isAvailable(false),
			'availableLocally' => $this->isAvailableLocally(false),
			'availableCopies' => $availableCopies,
			'copies' => $totalCopies,
			'numHolds' => $numHolds,
			'hasLocalItem' => $hasLocalItem,
			'holdRatio' => $totalCopies > 0 ? ($availableCopies + ($totalCopies - $numHolds) / $totalCopies) : 0,
			'locationLabel' => $this->getLocationLabel(),
			'shelfLocation' => $this->getShelfLocation(),
			'source' => 'ils',
			'actions' => array()
		);
		if ($this->isHoldable()){
			$relatedRecord['actions'][] = array(
				'title' => 'Place Hold',
				'url' => $holdUrl
			);
		}

		$relatedRecords[] = $relatedRecord;
		return $relatedRecords;
	}

	private function isHoldable(){
		$items = $this->getItemsFast();
		$firstCallNumber = null;
		foreach ($items as $item){
			//Try to get an available non reserve call number
			if ($item['holdable'] == 1){
				return true;
			}
		}
		return $firstCallNumber;
	}

	private function getAvailableCopies($realTime){
		if ($realTime){
			$items = $this->getItems();
		}else{
			$items = $this->getItemsFast();
		}
		$numAvailableCopies = 0;
		foreach ($items as $item){
			//Try to get an available non reserve call number
			if ($item['availability'] == true){
				$numAvailableCopies++;
			}
		}
		return $numAvailableCopies;
	}

	private function getLocationLabel(){
		$items = $this->getItemsFast();
		$locationLabel = null;
		foreach ($items as $item){
			//Try to get an available non reserve call number
			if ($item['isLocalItem']){
				return $item['locationLabel'];
			}else if ($item['isLibraryItem']){
				if ($locationLabel == null){
					$locationLabel = $item['locationLabel'];
				}
			}
		}
		return $locationLabel;
	}

	private function getShelfLocation(){
		$items = $this->getItemsFast();
		$locationLabel = null;
		foreach ($items as $item){
			//Try to get an available non reserve call number
			if ($item['isLocalItem']){
				return $item['shelfLocation'];
			}else if ($item['isLibraryItem']){
				if ($locationLabel == null){
					$locationLabel = $item['shelfLocation'];
				}
			}
		}
		return $locationLabel;
	}
	public function isAvailable($realTime){
		if ($realTime){
			$items = $this->getItems();
		}else{
			$items = $this->getItemsFast();
		}
		foreach ($items as $item){
			//Try to get an available non reserve call number
			if ($item['availability'] === true){
				return true;
			}
		}
		return false;
	}

	public function isAvailableLocally($realTime){
		if ($realTime){
			$items = $this->getItems();
		}else{
			$items = $this->getItemsFast();
		}
		foreach ($items as $item){
			//Try to get an available non reserve call number
			if ($item['availability'] === true && ($item['isLocalItem'] || $item['isLibraryItem'])){
				return true;
			}
		}
		return false;
	}

	private function hasLocalItem() {
		$items = $this->getItemsFast();
		foreach ($items as $item){
			if ($item['isLocalItem'] || $item['isLibraryItem']){
				return true;
			}
		}
		return false;
	}

	private function getCallNumber(){
		$items = $this->getItemsFast();
		$firstCallNumber = null;
		$nonLibraryCallNumber = null;
		foreach ($items as $item){
			if ($item['isLocalItem'] == true){
				return $item['callnumber'];
			}else if ($item['isLibraryItem'] == true){
				//Try to get an available non reserve call number
				if ($item['availability'] && $item['holdable']){
					return $item['callnumber'];
				}else if (is_null($firstCallNumber)){
					$firstCallNumber = $item['callnumber'];
				}
			}elseif ($item['holdable'] == true && is_null($nonLibraryCallNumber)){
				//Not at this library (system)
				//$nonLibraryCallNumber = $item['callnumber'] . '(' . $item['location'] . ')';
			}
		}
		if ($firstCallNumber != null){
			return $firstCallNumber;
		}elseif ($nonLibraryCallNumber != null){
			return $nonLibraryCallNumber;
		}else{
			return '';
		}

	}

	private function getNumCopies() {
		return count($this->getItemsFast());
	}

	private $fastItems = null;
	public function getItemsFast(){
		if ($this->fastItems == null){
			$driver = MarcRecord::getCatalogDriver();
			$this->fastItems = $driver->getItemsFast($this->getUniqueID(), $this->scopingEnabled, $this->marcRecord);
		}
		return $this->fastItems;
	}

	private $items = null;
	private function getItems(){
		if ($this->items == null){
			$driver = MarcRecord::getCatalogDriver();
			$this->items = $driver->getStatus($this->getUniqueID(), true);
		}
		return $this->items;
	}

	static $catalogDriver = null;

	/**
	 * @return MillenniumDriver|Sierra|Marmot|DriverInterface
	 */
	private static function getCatalogDriver(){
		if (MarcRecord::$catalogDriver == null){
			global $configArray;
			try {
				MarcRecord::$catalogDriver = new CatalogConnection($configArray['Catalog']['driver']);
			} catch (PDOException $e) {
				// What should we do with this error?
				if ($configArray['System']['debug']) {
					echo '<pre>';
					echo 'DEBUG: ' . $e->getMessage();
					echo '</pre>';
				}
				return null;
			}
		}
		return MarcRecord::$catalogDriver;
	}

	/**
	 * Get an array of physical descriptions of the item.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPhysicalDescriptions()
	{
		$physicalDescription1 = $this->getFieldArray("300", array('a', 'b', 'c', 'e', 'f', 'g'));
		$physicalDescription2 = $this->getFieldArray("530", array('a', 'b', 'c', 'd'));
		return array_merge($physicalDescription1, $physicalDescription2);
	}

	/**
	 * Get the publication dates of the record.  See also getDateSpan().
	 *
	 * @access  public
	 * @return  array
	 */
	public function getPublicationDates()
	{
		$publicationDates = $this->getFieldArray('260', array('c'));
		/** @var File_MARC_Data_Field[] $rdaPublisherFields */
		$rdaPublisherFields = $this->marcRecord->getFields('264');
		foreach ($rdaPublisherFields as $rdaPublisherField){
			if ($rdaPublisherField->getIndicator(2) == 1 && $rdaPublisherField->getSubfield('c') != null){
				$publicationDates[] = $rdaPublisherField->getSubfield('c')->getData();
			}
		}
		foreach ($publicationDates as $key => $publicationDate){
			$publicationDates[$key] = preg_replace('/[.,]$/', '', $publicationDate);
		}
		return $publicationDates;
	}

	/**
	 * Get the publishers of the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPublishers()
	{
		$publishers = $this->getFieldArray('260', array('b'));
		/** @var File_MARC_Data_Field[] $rdaPublisherFields */
		$rdaPublisherFields = $this->marcRecord->getFields('264');
		foreach ($rdaPublisherFields as $rdaPublisherField){
			if ($rdaPublisherField->getIndicator(2) == 1 && $rdaPublisherField->getSubfield('b') != null){
				$publishers[] = $rdaPublisherField->getSubfield('b')->getData();
			}
		}
		foreach ($publishers as $key => $publisher){
			$publishers[$key] = preg_replace('/[.,]$/', '', $publisher);
		}
		return $publishers;
	}

	/**
	 * Get an array of all ISBNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getISBNs()
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
			$isbns = array();
			/** @var File_MARC_Data_Field[] $isbnFields */
			$isbnFields = $this->marcRecord->getFields('020');
			foreach($isbnFields as $isbnField){
				if ($isbnField->getSubfield('a') != null){
					$isbns[] = $isbnField->getSubfield('a')->getData();
				}
			}
			return $isbns;
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
			$upcs = array();
			/** @var File_MARC_Data_Field[] $upcFields */
			$upcFields = $this->marcRecord->getFields('024');
			foreach($upcFields as $upcField){
				if ($upcField->getSubfield('a') != null){
					$upcs[] = $upcField->getSubfield('a')->getData();
				}
			}
			return $upcs;
		}
	}

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();

		//Load more details options
		$moreDetailsOptions = array();
		$moreDetailsOptions['copies'] = array(
			'label' => 'Copies',
			'body' => '<div id="holdingsPlaceholder"></div>',
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
			'body' => $interface->fetch('EcontentRecord/view-title-details.tpl'),
		);
		$moreDetailsOptions['citations'] = array(
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		);
		$moreDetailsOptions['staff'] = array(
			'label' => 'Staff View',
			'body' => $interface->fetch($this->getStaffView()),
		);

		return $moreDetailsOptions;
	}

	protected function getRecordType(){
		return 'ils';
	}
}