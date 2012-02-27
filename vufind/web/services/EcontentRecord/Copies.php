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

require_once 'Drivers/EContentDriver.php';
require_once 'sys/eContent/EContentRecord.php';
require_once 'RecordDrivers/EcontentRecordDriver.php';

class Copies extends Action{
	private $eContentRecord;
	
	function launch()    {
		global $interface;
		
		$id = $_GET['id'];
		$interface->assign('id', $id);

		$this->eContentRecord = new EContentRecord();
		$this->eContentRecord->id = $_GET['id'];
		$this->eContentRecord->find(true);
		
		$recordDriver = new EcontentRecordDriver();
		$recordDriver->setDataObject($this->eContentRecord);
		$interface->assign('sourceUrl', $this->eContentRecord->sourceUrl);
		$interface->assign('source', $this->eContentRecord->source);
	
		$interface->setPageTitle(translate('Copies') . ': ' . $recordDriver->getBreadcrumb());
		
		$driver = new EContentDriver();
		$holdings = $driver->getHolding($id);
		$showEContentNotes = false;
		foreach ($holdings as $holding){
			if (strlen($holding->notes) > 0){
				$showEContentNotes = true;
			} 
		}
		$interface->assign('showEContentNotes', $showEContentNotes);
		$interface->assign('holdings', $holdings);
		//Load status summary
		$result = $driver->getStatusSummary($id, $holdings);
		if (PEAR::isError($result)) {
			PEAR::raiseError($result);
		}
		$holdingData->holdingsSummary = $result;
		
		$interface->assign('subTemplate', 'view-holdings.tpl');
		$interface->setTemplate('view-alt.tpl');
	
		// Display Page
		$interface->display('layout.tpl');
	}

	
}