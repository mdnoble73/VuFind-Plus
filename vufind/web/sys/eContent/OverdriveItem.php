<?php
/**
 * An item that exists within Overdrive. 
 *  
 * The item information is loaded at runtime and is not persisisted to the database.
 *  
 * Copyright (C) Douglas County Libraries 2011.
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
 * @version 1.0
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Douglas County Libraries 2011.
 *
 */
class OverdriveItem extends DB_DataObject{
	public $__table = 'overdrive_item';
	public $id;
	public $overDriveId;
	public $source = 'OverDrive'; 
	public $format;
	public $formatId;
	public $size;
	public $available;
	public $notes;
	public $availableCopies;
	public $totalCopies;
	public $numHolds;
	public $lastLoaded;
	/**
	 * Whether or not the record is checked out to the user 
	 */
	public $checkedOut;
	public $onHold;
	public $holdPosition;
	public $libraryId;
	
	/**
	 * Dynamic information that can't be cached very long
	 */
	public $usageLink;
	

	function getUsageNotes(){
		$notes = '';
		if ($this->libraryId == -1){
			$notes = "Must be checked out to read.";
		}else{
			$library = new Library();
			$library->libraryId = $this->libraryId;
			if ($library->find(true)){
				$notes = "Available to <b>{$library->displayName} patrons</b> only.";
			}else{
				$notes = "Could not load library information.";
			}
		}
		return $notes;
	}
}