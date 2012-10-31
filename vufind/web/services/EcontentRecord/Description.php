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

require_once 'sys/eContent/EContentRecord.php';
require_once 'RecordDrivers/EcontentRecordDriver.php';

class Description extends Action{
	private $eContentRecord;

	function launch()    {
		global $interface;

		$interface->assign('id', $_GET['id']);

		$this->eContentRecord = new EContentRecord();
		$this->eContentRecord->id = $_GET['id'];
		$this->eContentRecord->find(true);

		$recordDriver = new EcontentRecordDriver();
		$recordDriver->setDataObject($this->eContentRecord);

		$this->loadData();
		$interface->setPageTitle(translate('Description') . ': ' . $recordDriver->getBreadcrumb());
		$interface->assign('extendedMetadata', $recordDriver->getExtendedMetadata());
		$interface->assign('subTemplate', 'view-description.tpl');
		$interface->setTemplate('view-alt.tpl');

		// Display Page
		$interface->display('layout.tpl', $this->cacheId);
	}

	function loadData(){
		global $library;
		$allowExternalDescription = true;
		if (isset($library) && $library->preferSyndeticsSummary == 0){
			$allowExternalDescription = false;
		}
		return Description::loadDescription($this->eContentRecord);
	}

	static function loadDescription($eContentRecord){
		global $interface;
		global $configArray;
		global $library;
		global $timer;

		$marc = MarcLoader::loadEContentMarcRecord($eContentRecord);
		$descriptionArray = array();
		//Load the description
		if (strlen($eContentRecord->description) > 0) {
			$descriptionArray['description'] = Description::trimDescription($eContentRecord->description);
		}else{
			//TODO: Check syndetics for eContent
			$descriptionArray['description'] = "Description Not Provided";
		}

		//Load publisher
		$descriptionArray['publisher'] = $eContentRecord->publisher;

		if($descriptionArray){
			return $descriptionArray;
		}
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