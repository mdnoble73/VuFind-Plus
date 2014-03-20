<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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

require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';
require_once ROOT_DIR . '/RecordDrivers/PublicEContentDriver.php';

class PublicEContent_Home extends Action{
	/** @var  SearchObject_Solr $db */
	protected $db;
	private $id;
	private $isbn;
	private $issn;
	/** @var PublicEContentDriver */
	private $recordDriver;

	function launch(){
		global $interface;
		global $timer;
		global $configArray;

		//Enable and disable functionality based on library settings
		global $library;

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);

		if (isset($_REQUEST['searchId'])){
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		}else if (isset($_SESSION['searchId'])){
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		$this->id = strip_tags($_REQUEST['id']);
		$recordDriver = new PublicEContentDriver($this->id);

		$eContentRecord = new EContentRecord();
		$eContentRecord->ilsId = $this->id;
		if (!$recordDriver->isValid()){
			$interface->setTemplate('invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}else{
			$this->recordDriver = $recordDriver;
			$interface->assign('recordDriver', $recordDriver);

			if ($configArray['Catalog']['ils'] == 'Millennium' || $configArray['Catalog']['ils'] == 'Sierra'){
				if (isset($eContentRecord->ilsId) && strlen($eContentRecord->ilsId) > 0){
					$interface->assign('classicId', substr($eContentRecord->ilsId, 1, strlen($eContentRecord->ilsId) -2));
					$interface->assign('classicUrl', $configArray['Catalog']['linking_url']);
				}
			}

			$this->isbn = $recordDriver->getCleanISBN();
			$this->issn = $eContentRecord->getPropertyArray('issn');
			if (is_array($this->issn)){
				if (count($this->issn) > 0){
					$this->issn = $this->issn[0];
				}else{
					$this->issn = "";
				}
			}
			$interface->assign('additionalAuthorsList', $eContentRecord->getPropertyArray('author2'));

			$interface->assign('lccnList', $eContentRecord->getPropertyArray('lccn'));
			$interface->assign('isbnList', $eContentRecord->getPropertyArray('isbn'));
			$interface->assign('isbn', $recordDriver->getISBNs());
			$interface->assign('isbn10', $eContentRecord->getIsbn10());
			$interface->assign('issnList', $recordDriver->getISSNs());
			$interface->assign('upcList', $recordDriver->getUPCs());
			$interface->assign('seriesList', $eContentRecord->getPropertyArray('series'));
			$interface->assign('topicList', $eContentRecord->getPropertyArray('topic'));
			$interface->assign('genreList', $eContentRecord->getPropertyArray('genre'));
			$interface->assign('regionList', $eContentRecord->getPropertyArray('region'));
			$interface->assign('eraList', $eContentRecord->getPropertyArray('era'));

			$interface->assign('eContentRecord', $eContentRecord);
			$interface->assign('cleanDescription', strip_tags($eContentRecord->description, '<p><br><b><i><em><strong>'));

			$interface->assign('id', $eContentRecord->id);

			$interface->assign('ratingData', $recordDriver->getRatingData());

			//Determine the cover to use
			$interface->assign('bookCoverUrl', $recordDriver->getBookcoverUrl('large'));

			if (isset($_REQUEST['detail'])){
				$detail = strip_tags($_REQUEST['detail']);
				$interface->assign('defaultDetailsTab', $detail);
			}

			//Load the citations
			$this->loadCitations();

			// Retrieve User Search History
			$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ?
			$_SESSION['lastSearchURL'] : false);

			//Get Next/Previous Links
			$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init($searchSource);
			$searchObject->getNextPrevLinks();

			// Retrieve tags associated with the record
			$limit = 5;
			$resource = new Resource();
			$resource->record_id = $_GET['id'];
			$resource->source = 'eContent';
			$resource->find(true);
			$tags = $resource->getTags($limit);
			$interface->assign('tagList', $tags);
			$timer->logTime('Got tag list');

			//Load notes if any
			$marcRecord = MarcLoader::loadEContentMarcRecord($eContentRecord);
			if ($marcRecord){
				$tableOfContents = array();
				$marcFields505 = $marcRecord->getFields('505');
				if ($marcFields505){
					$tableOfContents = $this->processTableOfContentsFields($marcFields505);
				}

				$notes = array();
				/*$marcFields500 = $marcRecord->getFields('500');
				$marcFields504 = $marcRecord->getFields('504');
				$marcFields511 = $marcRecord->getFields('511');
				$marcFields518 = $marcRecord->getFields('518');
				$marcFields520 = $marcRecord->getFields('520');
				if ($marcFields500 || $marcFields504 || $marcFields505 || $marcFields511 || $marcFields518 || $marcFields520){
					$allFields = array_merge($marcFields500, $marcFields504, $marcFields511, $marcFields518, $marcFields520);
					$notes = $this->processNoteFields($allFields);
				}*/

				if ((isset($library) && $library->showTableOfContentsTab == 0) || count($tableOfContents) == 0) {
					$notes = array_merge($notes, $tableOfContents);
				}else{
					$interface->assign('tableOfContents', $tableOfContents);
				}
				if (isset($library) && strlen($library->notesTabName) > 0){
					$interface->assign('notesTabName', $library->notesTabName);
				}else{
					$interface->assign('notesTabName', 'Notes');
				}

        $additionalNotesFields = array(
	                '520' => 'Description',
	                '500' => 'General Note',
	                '504' => 'Bibliography',
	                '511' => 'Participants/Performers',
	                '518' => 'Date/Time and Place of Event',
                  '310' => 'Current Publication Frequency',
                  '321' => 'Former Publication Frequency',
                  '351' => 'Organization & arrangement of materials',
                  '362' => 'Dates of publication and/or sequential designation',
                  '501' => '"With"',
                  '502' => 'Dissertation',
                  '506' => 'Restrictions on Access',
                  '507' => 'Scale for Graphic Material',
                  '508' => 'Creation/Production Credits',
                  '510' => 'Citation/References',
                  '513' => 'Type of Report an Period Covered',
                  '515' => 'Numbering Peculiarities',
                  '521' => 'Target Audience',
                  '522' => 'Geographic Coverage',
                  '525' => 'Supplement',
                  '526' => 'Study Program Information',
                  '530' => 'Additional Physical Form',
                  '533' => 'Reproduction',
                  '534' => 'Original Version',
                  '536' => 'Funding Information',
                  '538' => 'System Details',
                  '545' => 'Biographical or Historical Data',
                  '546' => 'Language',
                  '547' => 'Former Title Complexity',
                  '550' => 'Issuing Body',
                  '555' => 'Cumulative Index/Finding Aids',
                  '556' => 'Information About Documentation',
                  '561' => 'Ownership and Custodial History',
                  '563' => 'Binding Information',
                  '580' => 'Linking Entry Complexity',
                  '581' => 'Publications About Described Materials',
                  '586' => 'Awards',
                  '590' => 'Local note',
                  '599' => 'Differentiable Local note',
        );

				foreach ($additionalNotesFields as $tag => $label){
					$marcFields = $marcRecord->getFields($tag);
					foreach ($marcFields as $marcField){
						$noteText = array();
						foreach ($marcField->getSubFields() as $subfield){
							$noteText[] = $subfield->getData();
						}
						$note = implode(',', $noteText);
						if (strlen($note) > 0){
							$notes[] = "<dt>$label</dt><dd>" . $note . '</dd>';
						}
					}
				}

				if (count($notes) > 0){
					$interface->assign('notes', $notes);
				}
			}

			//Load subjects
			if ($marcRecord){
				if (isset($configArray['Content']['subjectFieldsToShow'])){
					$subjectFieldsToShow = $configArray['Content']['subjectFieldsToShow'];
					$subjectFields = explode(',', $subjectFieldsToShow);

					$subjects = array();
					$standardSubjects = array();
					$bisacSubjects = array();
					$oclcFastSubjects = array();
					foreach ($subjectFields as $subjectField){
						/** @var File_MARC_Data_Field[] $marcFields */
						$marcFields = $marcRecord->getFields($subjectField);
						if ($marcFields){
							foreach ($marcFields as $marcField){
								$searchSubject = "";
								$subject = array();
								//Determine the type of the subject
								$type = 'standard';
								$subjectSource = $marcField->getSubfield('2');
								if ($subjectSource != null){
									if (preg_match('/bisac/i', $subjectSource->getData())){
										$type = 'bisac';
									}elseif (preg_match('/fast/i', $subjectSource->getData())){
										$type = 'fast';
									}
								}

								foreach ($marcField->getSubFields() as $subField){
									/** @var File_MARC_Subfield $subField */
									if ($subField->getCode() != '2' && $subField->getCode() != '0'){
										$subFieldData = $subField->getData();
										if ($type == 'bisac' && $subField->getCode() == 'a'){
											$subFieldData = ucwords(strtolower($subFieldData));
										}
										$searchSubject .= " " . $subFieldData;
										$subject[] = array(
											'search' => trim($searchSubject),
											'title'  => $subFieldData,
										);
									}
								}
								if ($type == 'bisac'){
									$bisacSubjects[] = $subject;
									$subjects[] = $subject;
								}elseif ($type == 'fast'){
									//Suppress fast subjects by default
									$oclcFastSubjects[] = $subject;
								}else{
									$subjects[] = $subject;
									$standardSubjects[] = $subject;
								}

							}
						}
						$interface->assign('subjects', $subjects);
						$interface->assign('standardSubjects', $standardSubjects);
						$interface->assign('bisacSubjects', $bisacSubjects);
						$interface->assign('oclcFastSubjects', $oclcFastSubjects);
					}
				}
			}else{
				$rawSubjects = $eContentRecord->getPropertyArray('subject');
				$subjects = array();
				foreach ($rawSubjects as $subject){
					$explodedSubjects = explode(' -- ', $subject);
					$searchSubject = "";
					$subject = array();
					foreach ($explodedSubjects as $tmpSubject){
						$searchSubject .= $tmpSubject . ' ';
						$subject[] = array(
							'search' => trim($searchSubject),
							'title'  => $tmpSubject,
						);
					}
					$subjects[] = $subject;
				}
				$interface->assign('subjects', $subjects);
			}

			if (isset($_REQUEST['subsection'])){
				$subsection = $_REQUEST['subsection'];
				if ($subsection == 'Description'){
					$interface->assign('extendedMetadata', $this->recordDriver->getExtendedMetadata());
					$interface->assign('subTemplate', 'view-description.tpl');
				}elseif ($subsection == 'Reviews'){
					$interface->assign('subTemplate', 'view-reviews.tpl');
				}
			}

			$interface->setPageTitle($this->recordDriver->getTitle());

			//Load Staff Details
			$interface->assign('staffDetails', $this->recordDriver->getStaffView($eContentRecord));
			$interface->assign('moreDetailsOptions', $this->recordDriver->getMoreDetailsOptions());

			// Display Page
			//Build the actual view
			$interface->assign('sidebar', 'PublicEContent/full-record-sidebar.tpl');
			$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
			$interface->setTemplate('view.tpl');
			$interface->display('layout.tpl');

		}
	}

	function loadCitations()
	{
		global $interface;

		$citationCount = 0;
		$formats = $this->recordDriver->getCitationFormats();
		foreach($formats as $current) {
			$interface->assign(strtolower($current), $this->recordDriver->getCitation($current));
			$citationCount++;
		}
		$interface->assign('citationCount', $citationCount);
	}

	function processNoteFields($allFields){
		$notes = array();
		foreach ($allFields as $marcField){
			foreach ($marcField->getSubFields() as $subfield){
				$note = $subfield->getData();
				if ($subfield->getCode() == 't'){
					$note = "&nbsp;&nbsp;&nbsp;" . $note;
				}
				$note = trim($note);
				if (strlen($note) > 0){
					$notes[] = $note;
				}
			}
		}
		return $notes;
	}

	function processTableOfContentsFields($allFields){
		$notes = array();
		foreach ($allFields as $marcField){
			$curNote = '';
			foreach ($marcField->getSubFields() as $subfield){
				$note = $subfield->getData();
				$curNote .= " " . $note;
				$curNote = trim($curNote);
				if (strlen($curNote) > 0 && in_array($subfield->getCode(), array('t', 'a'))){
					$notes[] = $curNote;
					$curNote = '';
				}
			}
		}
		return $notes;
	}
}