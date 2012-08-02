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

require_once 'RecordDrivers/IndexRecord.php';
require_once 'sys/eContent/EContentRecord.php';

/**
 * EContent Record Driver
 *
 * This class is designed to handle EContentRecord records.  Much of its functionality
 * is inherited from the default index-based driver.
 */
class EcontentRecordDriver extends IndexRecord
{
	private $eContentRecord;
	public function __construct($record = null)
	{
		// Call the parent's constructor...
		parent::__construct($record);
	}

	public function setDataObject($eContentRecord){
		$this->eContentRecord = $eContentRecord;
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
		if (!isset($this->eContentRecord)){
			$this->eContentRecord = new EContentRecord();
			$this->eContentRecord->id = $this->getUniqueID();
			$this->eContentRecord->find(true);
		}
		$interface->assign('source', $this->eContentRecord->source);
		$searchResultTemplate = parent::getSearchResult();
		//Override fields as needed
		return 'RecordDrivers/Econtent/result.tpl';
	}

	public function getCitation($format){
		require_once 'sys/CitationBuilder.php';

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
            'subtitle' => $this->eContentRecord->subTitle(),
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
	
	protected function getShortTitle()
	{
		return isset($this->fields['title_short']) ? $this->fields['title_short'] : $this->eContentRecord->title;
	}
	
}