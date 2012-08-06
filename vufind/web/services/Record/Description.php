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

class Description extends Record{
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

	function loadData()    {
		global $library;
		$allowExternalDescription = true;
		if (isset($library) && $library->preferSyndeticsSummary == 0){
			$allowExternalDescription = false;
		}
		return Description::loadDescriptionFromMarc($this->marcRecord, $allowExternalDescription);

	}

	static function loadDescriptionFromMarc($marcRecord, $allowExternalDescription = true){
		global $interface;
		global $configArray;
		global $library;
		global $timer;
		global $memcache;
		
		// Get ISBN for cover and review use
		$isbn = null;
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
		if ($upcField = $marcRecord->getField('024')) {
			if ($upcField = $upcField->getSubfield('a')) {
				$upc = trim($upcField->getData());
			}
		}
		
		$descriptionArray = $memcache->get("record_description_{$isbn}_{$upc}_{$allowExternalDescription}");
		if (!$descriptionArray){
			$marcDescription = null;
			$description = '';
			if ($descriptionField = $marcRecord->getField('520')) {
				if ($descriptionSubfield = $descriptionField->getSubfield('a')) {
					$description = trim($descriptionSubfield->getData());
					$marcDescription = Description::trimDescription($description);
				}
			}
			
			//Load the description
			//Check to see if there is a description in Syndetics and use that instead if available
			$useMarcSummary = true;
			if ($allowExternalDescription){
				if (!is_null($isbn) || !is_null($upc)){
					require_once 'Drivers/marmot_inc/GoDeeperData.php';
					$summaryInfo = GoDeeperData::getSummary($isbn, $upc);
					if (isset($summaryInfo['summary'])){
						$descriptionArray['description'] = Description::trimDescription($summaryInfo['summary']);
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
			if ($length = $marcRecord->getField('300')){
				if ($length = $length->getSubfield('a')){
	
					$length = trim($length->getData());
					$length = preg_replace("/[\\/|;:]/","",$length);
					$length = preg_replace("/p\./","pages",$length);
					$descriptionArray['length'] = $length;
				}
	
			}
	
			//Load publisher
			if ($publisher = $marcRecord->getField('260')){
				if ($publisher = $publisher->getSubfield('b')){
					$publisher = trim($publisher->getData());
	
					$descriptionArray['publisher'] = $publisher;
				}
			}
			$memcache->set("record_description_{$isbn}_{$upc}_{$allowExternalDescription}", $descriptionArray, 0, $configArray['Caching']['record_description']);
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