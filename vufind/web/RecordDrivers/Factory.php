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

/**
 * RecordDriverFactory Class
 *
 * This is a factory class to build record drivers for accessing metadata.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class RecordDriverFactory {
	/**
	 * initSearchObject
	 *
	 * This constructs a search object for the specified engine.
	 *
	 * @access  public
	 * @param   array   $record     The fields retrieved from the Solr index.
	 * @return  object              The record driver for handling the record.
	 */
	static function initRecordDriver($record)
	{
		global $configArray;

		// Determine driver path based on record type:
		$driver = ucwords($record['recordtype']) . 'Record';
		$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";
		// If we can't load the driver, fall back to the default, index-based one:
		if (!is_readable($path)) {
			//Try without appending Record
			$recordType = $record['recordtype'];
			$driverNameParts = explode('_', $recordType);
			$recordType = '';
			foreach ($driverNameParts as $driverPart){
				$recordType .= (ucfirst($driverPart));
			}

			$driver = $recordType . 'Driver' ;
			$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";

			// If we can't load the driver, fall back to the default, index-based one:
			if (!is_readable($path)) {

				$driver = 'IndexRecord';
				$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";
			}
		}

		// Build the object:
		require_once $path;
		if (class_exists($driver)) {
			disableErrorHandler();
			$obj = new $driver($record);
			if (PEAR_Singleton::isError($obj)){
				global $logger;
				$logger->log("Error loading record driver", PEAR_LOG_DEBUG);
			}
			enableErrorHandler();
			return $obj;
		}

		// If we got here, something went very wrong:
		return new PEAR_Error("Problem loading record driver: {$driver}");
	}

	/**
	 * @param $id
	 * @return ExternalEContentDriver|MarcRecord|null|OverDriveRecordDriver|PublicEContentDriver|RestrictedEContentDriver
	 */
	static function initRecordDriverById($id){
		$recordInfo = explode(':', $id);
		$recordType = $recordInfo[0];
		$recordId = $recordInfo[1];

		disableErrorHandler();
		if ($recordType == 'overdrive'){
			require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
			$recordDriver = new OverDriveRecordDriver($recordId);
		}elseif ($recordType == 'public_domain_econtent'){
			require_once ROOT_DIR . '/RecordDrivers/PublicEContentDriver.php';
			$recordDriver = new PublicEContentDriver($recordId);
		}elseif ($recordType == 'external_econtent'){
			require_once ROOT_DIR . '/RecordDrivers/ExternalEContentDriver.php';
			$recordDriver = new ExternalEContentDriver($recordId);
		}elseif ($recordType == 'restricted_econtent'){
			require_once ROOT_DIR . '/RecordDrivers/RestrictedEContentDriver.php';
			$recordDriver = new RestrictedEContentDriver($recordId);
		}elseif ($recordType == 'ils'){
			require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			$recordDriver = new MarcRecord($recordId);
			if (!$recordDriver->isValid()){
				echo("Unable to load record driver for $recordId");
				$recordDriver = null;
			}
		}else{
			echo("Unknown record type " . $recordType);
			$recordDriver = null;
		}
		enableErrorHandler();
		return $recordDriver;
	}
}
?>