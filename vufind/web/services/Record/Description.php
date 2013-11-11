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

require_once 'Record.php';

class Record_Description extends Record_Record{
	function launch()    {
		global $interface;

		if (!$interface->is_cached($this->cacheId)) {
			$this->loadData();
			$interface->setPageTitle(translate('Description') . ': ' . $this->recordDriver->getBreadcrumb());
			$interface->assign('extendedMetadata', $this->recordDriver->getExtendedMetadata());
			$interface->assign('subTemplate', 'view-description.tpl');
			$interface->setTemplate('view.tpl');
		}

		// Display Page
		$interface->display('layout.tpl', $this->cacheId);
	}

	function loadData($forSummary = false){
		global $library;
		$allowExternalDescription = true;
		if (isset($library) && $library->preferSyndeticsSummary == 0){
			$allowExternalDescription = false;
		}
		if ($forSummary){
			$allowExternalDescription = false;
		}
		return Record_Description::loadDescriptionFromMarc($this->marcRecord, $allowExternalDescription);

	}

	/**
	 * @param File_MARC_Record $marcRecord
	 * @param bool $allowExternalDescription
	 * @return mixed
	 */
	static function loadDescriptionFromMarc($marcRecord, $allowExternalDescription = true){
		global $interface;
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;

		if (!$marcRecord){
			$descriptionArray = array();
			$description = "Description Not Provided";
			$descriptionArray['description'] = $description;
			return $descriptionArray;
		}

		// Get ISBN for cover and review use
		$isbn = null;
		/** @var File_MARC_Data_Field[] $isbnFields */
		if ($isbnFields = $marcRecord->getFields('020')) {
			//Use the first good ISBN we find.
			foreach ($isbnFields as $isbnField){
				if ($isbnSubfieldA = $isbnField->getSubfield('a')) {
					$tmpIsbn = trim($isbnSubfieldA->getData());
					if (strlen($tmpIsbn) > 0){

						$pos = strpos($tmpIsbn, ' ');
						if ($pos > 0) {
							$tmpIsbn = substr($tmpIsbn, 0, $pos);
						}
						$tmpIsbn = trim($tmpIsbn);
						if (strlen($tmpIsbn) > 0){
							if (strlen($tmpIsbn) < 10){
								$tmpIsbn = str_pad($tmpIsbn, 10, "0", STR_PAD_LEFT);
							}
							$isbn = $tmpIsbn;
							break;
						}
					}
				}
			}
		}

		$upc = null;
		/** @var File_MARC_Data_Field $upcField */
		if ($upcField = $marcRecord->getField('024')) {
			if ($upcSubfield = $upcField->getSubfield('a')) {
				$upc = trim($upcSubfield->getData());
			}
		}

		$descriptionArray = $memCache->get("record_description_{$isbn}_{$upc}_{$allowExternalDescription}");
		if (!$descriptionArray){
			$marcDescription = null;
			$description = '';
			/** @var File_MARC_Data_Field $descriptionField */
			if ($descriptionField = $marcRecord->getField('520')) {
				if ($descriptionSubfield = $descriptionField->getSubfield('a')) {
					$description = trim($descriptionSubfield->getData());
					$marcDescription = Record_Description::trimDescription($description);
				}
			}

			//Load the description
			//Check to see if there is a description in Syndetics and use that instead if available
			$useMarcSummary = true;
			if ($allowExternalDescription){
				if (!is_null($isbn) || !is_null($upc)){
					require_once ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php';
					$summaryInfo = GoDeeperData::getSummary($isbn, $upc);
					if (isset($summaryInfo['summary'])){
						$descriptionArray['description'] = Record_Description::trimDescription($summaryInfo['summary']);
						$useMarcSummary = false;
					}
				}
			}

			if ($useMarcSummary){
				if ($marcDescription != null){
					$descriptionArray['description'] = $marcDescription;
					$description = $marcDescription;
				}else{
					$description = "Description Not Provided";
					$descriptionArray['description'] = $description;
				}
			}
			$interface->assign('description', $description);


			//Load page count
			/** @var File_MARC_Data_Field $length */
			if ($length = $marcRecord->getField('300')){
				if ($lengthSubfield = $length->getSubfield('a')){

					$length = trim($lengthSubfield->getData());
					$length = preg_replace("/[\\/|;:]/","",$length);
					$length = preg_replace("/p\./","pages",$length);
					$descriptionArray['length'] = $length;
				}

			}

			//Load publisher
			/** @var File_MARC_Data_Field $publisher */
			if ($publisher = $marcRecord->getField('260')){
				if ($publisherSubfield = $publisher->getSubfield('b')){
					$publisher = trim($publisherSubfield->getData());

					$descriptionArray['publisher'] = $publisher;
				}
			}
			$memCache->set("record_description_{$isbn}_{$upc}_{$allowExternalDescription}", $descriptionArray, 0, $configArray['Caching']['record_description']);
		}

		return $descriptionArray;
	}

	private function trimDescription($description){
		$chars = 300;
		if (strlen($description)>$chars){
			$description = $description." ";
			$description = substr($description,0,$chars);
			$description = substr($description,0,strrpos($description,' '));
			$description = $description . "...";
		}
		return $description;
	}
}