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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
 *
 */

require_once 'Action.php';
require_once 'services/Admin/Admin.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 *
 * @author Mark Noble
 *
 */
class DBMaintenanceEContent extends Admin {
	function launch() 	{
		global $configArray;
		global $interface;

		mysql_select_db($configArray['Database']['database_econtent_dbname']);

		//Create updates table if one doesn't exist already
		$this->createUpdatesTable();

		$availableUpdates = $this->getSQLUpdates();

		if (isset($_REQUEST['submit'])){
			$interface->assign('showStatus', true);

			//Process the updates
			foreach ($availableUpdates as $key => $update){
				if (isset($_REQUEST["selected"][$key])){
					$sqlStatements = $update['sql'];
					$updateOk = true;
					foreach ($sqlStatements as $sql){
						//Give enough time for long queries to run
						set_time_limit(120);
						if (method_exists($this, $sql)){
							$update['status'] = $this->$sql();
						}else{
							$result = mysql_query($sql);
							if ($result == 0 || $result == false){
								if (isset($update['continueOnError']) && $update['continueOnError']){
									if (!isset($update['status'])) $update['status'] = '';
									$update['status'] .= 'Warning: ' . mysql_error() . "<br/>";
								}else{
									$update['status'] = 'Update failed ' . mysql_error();
									$updateOk = false;
									break;
								}
							}else{
								if (!isset($update['status'])){
									$update['status'] = 'Update succeeded';
								}
							}

						}
					}
					if ($updateOk){
						$this->markUpdateAsRun($key);
					}
					$availableUpdates[$key] = $update;
				}
			}
		}

		//Check to see which updates have already been performed.
		$availableUpdates = $this->checkWhichUpdatesHaveRun($availableUpdates);

		$interface->assign('sqlUpdates', $availableUpdates);

		$interface->setTemplate('dbMaintenance.tpl');
		$interface->setPageTitle('Database Maintenance - EContent');
		$interface->display('layout.tpl');

	}

	private function getSQLUpdates() {
		global $configArray;
		return array(
			'initial_setup' => array(
				'title' => 'Setup eContent Database',
				'description' => 'Sets up eContent database',
				'dependencies' => array(),
				'sql' => array(
					"DROP TABLE IF EXISTS econtent_item",
					"DROP TABLE IF EXISTS econtent_record",
					"CREATE TABLE IF NOT EXISTS	econtent_item(" .
						"`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'The id of the eContent item', " .
						"`filename` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'The filename of the eContent item if any', " .
						"`folder` VARCHAR(100) NOT NULL default '' COMMENT 'A folder containing a group of files for mp-3 files', " .
						"`acsId` VARCHAR(128) NULL COMMENT 'The uid of the book within the Adobe Content Server.', " .
						"`recordId` int(11) NOT NULL COMMENT 'The id of the record to attach the item to.', " .
						"`type` ENUM('epub', 'pdf', 'jpg', 'gif', 'mp3', 'plucker', 'kindle'), " .
						"`notes` VARCHAR(255) NOT NULL default '', " .
						"`addedBy` INT(11) NOT NULL default -1 COMMENT 'The id of the user who added the item or -1 if it was added automatically', " .
						"`date_added` LONG NOT NULL COMMENT 'The date the item was added', " .
						"`date_updated` LONG NOT NULL COMMENT 'The last time the item was changed', " .
						"`reviewdBy` INT(11) NOT NULL default -1 COMMENT 'The id of the user who added the item or -1 if not reviewed', " .
						"`reviewStatus` ENUM('Not Reviewed', 'Approved', 'Rejected')	NOT NULL default 'Not Reviewed', " .
						"`reviewDate` LONG	NULL COMMENT 'When the review took place.', " .
						"`reviewNotes` TEXT COMMENT 'Notes about the review'" .
					") ENGINE = MYISAM COMMENT = 'EContent files that can be viewed within VuFind.'",
					"CREATE TABLE IF NOT EXISTS	econtent_record(" .
						"`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'The id of the e-pub file', " .
						"`cover` VARCHAR(255) NULL COMMENT 'The filename of the cover art if any', " .
						"`title` VARCHAR(255) NOT NULL default '', " .
						"`subTitle` VARCHAR(255) NOT NULL default '', " .
						"`accessType` ENUM('free', 'acs', 'singleUse') NOT NULL DEFAULT 'free' COMMENT 'Whether or not the Adobe Content Server should be checked before giving the user access to the file', " .
						"`availableCopies` INT NOT NULL default 1, " .
						"`onOrderCopies` INT NOT NULL default 0, " .
						"`author` VARCHAR(255) NULL, " .
						"`author2` TEXT NULL, " .
						"`description` TEXT, " .
						"`contents` TEXT, " .
						"`subject` TEXT NULL COMMENT 'A list of subjects separated by carriage returns', " .
						"`language` VARCHAR(255) NOT NULL default '', " .
						"`publisher` VARCHAR(255) NOT NULL default '', " .
						"`edition` VARCHAR(255) NOT NULL default '', " .
						"`isbn`	VARCHAR(255) NULL, " .
						"`issn`	VARCHAR(255) NULL, " .
						"`upc`	VARCHAR(255) NULL, " .
						"`lccn`	VARCHAR(255) NOT NULL default '', " .
						"`series`	VARCHAR(255) NOT NULL default '', " .
						"`topic` TEXT, " .
						"`genre` TEXT, " .
						"`region` TEXT, " .
						"`era`	VARCHAR(255) NOT NULL default '', " .
						"`target_audience` VARCHAR(255) NOT NULL default '', " .
						"`notes` TEXT, " .
						"`ilsId` VARCHAR(255) NULL, " .
						"`source` VARCHAR(50) NOT NULL default '' COMMENT 'Where the file was purchased or loaded from.', " .
						"`sourceUrl` VARCHAR(500) NULL COMMENT 'A link to the original file if known.', " .
						"`purchaseUrl` VARCHAR(500) NULL COMMENT 'A link to the url where a copy can be purchased if known.', " .
						"`publishDate` VARCHAR(100) NULL COMMENT 'The date the item was published', " .
						"`addedBy` INT(11) NOT NULL default -1 COMMENT 'The id of the user who added the item or -1 if it was added automatically', " .
						"`date_added` INT(11) NOT NULL COMMENT 'The date the item was added', " .
						"`date_updated` INT(11) COMMENT 'The last time the item was changed', " .
						"`reviewedBy` INT(11) NOT NULL default -1 COMMENT 'The id of the user who added the item or -1 if not reviewed', " .
						"`reviewStatus` ENUM('Not Reviewed', 'Approved', 'Rejected')	NOT NULL default 'Not Reviewed', " .
						"`reviewDate` INT(11) NULL COMMENT 'When the review took place.', " .
						"`reviewNotes` TEXT COMMENT 'Notes about the review', " .
						"`trialTitle` TINYINT NOT NULL default 0 COMMENT 'Whether or not the title was purchased outright or on a trial basis.', " .
						"`marcControlField` VARCHAR(100) NULL COMMENT 'The control field from the marc record to avoid importing duplicates.' " .
					") ENGINE = MYISAM COMMENT = 'EContent records for titles that exist in VuFind, but not the ILS.'",


		),

		),

			'eContentHolds'	=> array(
				'title' => 'eContent Holds table creation',
				'description' => 'Sets up tables for handling eContent holds',
				'dependencies' => array(),
				'sql' => array(
					"DROP TABLE IF EXISTS econtent_hold",
					"CREATE TABLE IF NOT EXISTS	econtent_hold(" .
						"`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'The id of the eContent hold', " .
						"`recordId` int(11) NOT NULL COMMENT 'The id of the record being placed on hold', " .
						"`datePlaced` LONG NOT NULL COMMENT 'When the hold was placed', " .
						"`dateUpdated` LONG NULL COMMENT 'When the hold last changed status', " .
						"`userId` int(11) NOT NULL COMMENT 'The user who the hold is for', " .
						"`status` ENUM('active', 'suspended', 'cancelled', 'filled', 'available', 'abandoned'), " .
						"`reactivateDate` int(11) NULL COMMENT 'When the item should be reactivated.',	" .
						"`noticeSent` TINYINT NOT NULL DEFAULT 0 COMMENT 'Whether or not a notice has been sent.' " .
					") ENGINE = MYISAM COMMENT = 'EContent files that can be viewed within VuFind.'",
		),
		),
			'eContentCheckout'	=> array(
				'title' => 'eContent Checkout table',
				'description' => 'Sets up tables for handling eContent checked out items',
				'dependencies' => array(),
				'sql' => array(
					"DROP TABLE IF EXISTS econtent_checkout",
					"CREATE TABLE IF NOT EXISTS	econtent_checkout(" .
						"`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'The id of the eContent checkout', " .
						"`recordId` int(11) NOT NULL COMMENT 'The id of the record being checked out', " .
						"`dateCheckedOut` int(11) NOT NULL COMMENT 'When the item was checked out', " .
						"`dateDue` int(11) NOT NULL COMMENT 'When the item needs to be returned', " .
						"`dateReturned` int(11) NULL COMMENT 'When the item was returned', " .
						"`userId` int(11) NOT NULL COMMENT 'The user who the hold is for', " .
						"`status` ENUM('out', 'returned'), " .
						"`renewalCount` int(11) COMMENT 'The number of times the item has been renewed.', " .
						"`acsDownloadLink` VARCHAR(512) COMMENT 'The link to use when downloading an acs protected item', " .
						"`dateFulfilled` int(11) NULL COMMENT 'When the item was fulfilled in the ACS server.' " .
					") ENGINE = MYISAM COMMENT = 'EContent files that can be viewed within VuFind.'",
		),
		),
		'eContentCheckout_1'	=> array(
			'title' => 'eContent Checkout Update 1',
			'description' => 'Updates to checkout to include additional information related to ACS downloads.',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE econtent_checkout ADD downloadedToReader TINYINT NOT NULL DEFAULT 0",
				"ALTER TABLE econtent_checkout ADD acsTransactionId VARCHAR(50) NULL",
				"ALTER TABLE econtent_checkout ADD userAcsId VARCHAR(50) NULL",
		),
		),

		'eContentHistory'	=> array(
			'title' => 'eContent History table',
			'description' => 'Sets up tables for handling history of eContent',
			'dependencies' => array(),
			'sql' => array(
					"DROP TABLE IF EXISTS econtent_history;",
					"CREATE TABLE IF NOT EXISTS	econtent_history(".
						"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
						"`userId` INT NOT NULL COMMENT 'The id of the user who checked out the item', ".
						"`recordId` INT NOT NULL COMMENT 'The record id of the item that was checked out', ".
						"`openDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The date the record was opened', ".
						"`action` VARCHAR(30) NOT NULL default 'Read Online', ".
						"`accessType` TINYINT NOT NULL default 0 ".
					") ENGINE = MYISAM COMMENT = 'The econtent reading history for patrons' ",
		),
		),
		'eContentRating'	=> array(
			'title' => 'eContent Rating',
			'description' => 'Sets up tables for handling rating of eContent',
			'dependencies' => array(),
			'sql' => array(
					"DROP TABLE IF EXISTS econtent_rating;",
					"CREATE TABLE IF NOT EXISTS	econtent_rating(".
						"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
						"`userId` INT NOT NULL COMMENT 'The id of the user who checked out the item', ".
						"`recordId` INT NOT NULL COMMENT 'The record id of the item that was checked out', ".
						"`dateRated` INT NOT NULL COMMENT 'The date the record was opened', ".
						"`rating` INT NOT NULL COMMENT 'The rating to aply to the record' ".
					") ENGINE = MYISAM COMMENT = 'The ratings for eContent records' ",
		),
		),

		'eContentRecord_1'	=> array(
			'title' => 'eContent Record Update 1',
			'description' => 'Adds fields for collection and formatted marc record',
			'dependencies' => array(),
			'database' => 'dclecontent',
			'sql' => array(
				"ALTER TABLE econtent_record ADD collection VARCHAR(30) NULL",
				"ALTER TABLE econtent_record ADD marcRecord TEXT NULL",
				"ALTER TABLE econtent_record ADD literary_form_full VARCHAR(30) NULL",
		),
		),

		'eContentRecord_2'	=> array(
			'title' => 'eContent Record Update 2',
			'description' => 'Adds status to allow ',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE econtent_record ADD status ENUM('active', 'deleted', 'archived') DEFAULT 'active'",
		),
		),

		'eContentRecord_3'	=> array(
			'title' => 'eContent Record Update 3',
			'description' => 'Increase length of isbn field ',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE econtent_record CHANGE `isbn` `isbn` VARCHAR(500) NULL",
		),
		),

		'eContentRecord_4'	=> array(
			'title' => 'eContent Record Update 4',
			'description' => 'Adds external accessType ',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE econtent_record CHANGE accessType accessType ENUM('free', 'acs', 'singleUse', 'external') DEFAULT 'acs'",
			),
		),

		'eContentRecord_5'	=> array(
			'title' => 'eContent Record Update 5',
			'description' => 'Adds externalId ',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE econtent_record ADD externalId VARCHAR(50) NULL",
			),
		),

		'eContentRecord_6'	=> array(
			'title' => 'eContent Record Update 6',
			'description' => 'Adds publication location and physical description ',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE econtent_record ADD publishLocation VARCHAR(100) NULL",
				"ALTER TABLE econtent_record ADD physicalDescription VARCHAR(100) NULL",
			),
		),

		'notices_1'	=> array(
			'title' => 'eContent Notices Update 1',
			'description' => 'Adds notices fields so each notice is tracked explicitly',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE econtent_hold DROP noticeSent",
				"ALTER TABLE econtent_hold ADD holdAvailableNoticeSent TINYINT NOT NULL DEFAULT 0",
				"ALTER TABLE econtent_hold ADD holdReminderNoticeSent TINYINT NOT NULL DEFAULT 0",
				"ALTER TABLE econtent_hold ADD holdAbandonedNoticeSent TINYINT NOT NULL DEFAULT 0",
				"ALTER TABLE econtent_checkout ADD returnReminderNoticeSent TINYINT NOT NULL DEFAULT 0",
				"ALTER TABLE econtent_checkout ADD recordExpirationNoticeSent TINYINT NOT NULL DEFAULT 0",
			),
		),

		'eContentItem_1'	=> array(
			'title' => 'eContent Item Update 1',
			'description' => 'Updates to allow external links to be added to the system',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE econtent_item ADD link VARCHAR(500) NULL",
				"ALTER TABLE `econtent_item` CHANGE `type` `item_type` ENUM( 'epub', 'pdf', 'jpg', 'gif', 'mp3', 'plucker', 'kindle', 'externalLink', 'externalMP3', 'interactiveBook' ) NOT NULL",
			),
		),

		'eContentItem_2'	=> array(
			'title' => 'eContent Item Update 2',
			'description' => 'Allow items to be restricted by library system',
			'dependencies' => array(),
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE econtent_item ADD libraryId INT(11) NOT NULL DEFAULT -1",
				"ALTER TABLE econtent_item ADD overDriveId INT(11) NOT NULL DEFAULT -1",
				"ALTER TABLE `econtent_item` CHANGE `item_type` `item_type` ENUM( 'epub', 'pdf', 'jpg', 'gif', 'mp3', 'plucker', 'kindle', 'externalLink', 'externalMP3', 'interactiveBook', 'overdrive' ) NOT NULL",
			),
		),

		'eContentItem_3'	=> array(
			'title' => 'eContent Item Update 3',
			'description' => 'Add Overdrive item capabilities',
			'dependencies' => array(),
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE econtent_item CHANGE overDriveId overDriveId VARCHAR(36) NULL",
				"ALTER TABLE `econtent_item` CHANGE `item_type` `item_type` ENUM( 'epub', 'pdf', 'jpg', 'gif', 'mp3', 'plucker', 'kindle', 'externalLink', 'externalMP3', 'interactiveBook', 'overdrive' ) NOT NULL",
			),
		),

		'overdriveItem' => array(
			'title' => 'Overdrive Item',
			'description' => 'Setup of Overdrive item to cache information about items from OverDrive for performance',
			'dependencies' => array(),
			'sql' => array(
				"DROP TABLE IF EXISTS overdrive_item",
				"CREATE TABLE IF NOT EXISTS	overdrive_item(" .
						"`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'The id of the eContent item', " .
						"`recordId` int(11) NOT NULL COMMENT 'The record id that this record belongs to', " .
						"`format` VARCHAR(100) NOT NULL default '' COMMENT 'A description of the format from overdrive', " .
						"`formatId` int(11) NULL COMMENT 'The id of the format ', " .
						"`size` VARCHAR(25) NOT NULL COMMENT 'A description of the size of the file(s) to be downloaded', " .
						"`available` TINYINT COMMENT 'Whether or not the format is available for immediate usage.', " .
						"`notes` VARCHAR(255) NOT NULL default '', " .
						"`lastLoaded` int(11) NOT NULL " .
					") ENGINE = MYISAM COMMENT = 'Cached information about overdrive items within VuFind'",
				'ALTER TABLE `overdrive_item` ADD INDEX `RecordId` ( `recordId` ) ',
			),
		),

		'overdriveItem_1' => array(
			'title' => 'Overdrive Item Update 1',
			'description' => 'Change Overdrive item to cache information about number of holds and waitlist',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE overdrive_item ADD COLUMN availableCopies int(11) DEFAULT 0;",
				"ALTER TABLE overdrive_item ADD COLUMN totalCopies int(11) DEFAULT 0;",
				"ALTER TABLE overdrive_item ADD COLUMN numHolds int(11) DEFAULT 0;",
			),
		),

		'overdriveItem_2' => array(
			'title' => 'Overdrive Item Update 2',
			'description' => 'Change Overdrive item to cache information based on overdriveId rather than record id since we may have more than 1 overdrive records on a record',
			'dependencies' => array(),
			'sql' => array(
				"TRUNCATE TABLE overdrive_item;",
				"ALTER TABLE overdrive_item DROP COLUMN recordId;",
				"ALTER TABLE overdrive_item ADD COLUMN overDriveId VARCHAR(36) NOT NULL;",
				"ALTER TABLE overdrive_item ADD INDEX `OverDriveId` (overDriveId);",
			),
		),

		'eContentWishList'	=> array(
			'title' => 'eContent Wishlist',
			'description' => 'Create table to allow econtent to be added to a user\'s wishlist if no items exits for the record.',
			'dependencies' => array(),
			'sql' => array(
				"DROP TABLE IF EXISTS econtent_wishlist;",
				"CREATE TABLE IF NOT EXISTS	econtent_wishlist(".
					"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
					"`userId` INT NOT NULL COMMENT 'The id of the user who checked out the item', ".
					"`recordId` INT NOT NULL COMMENT 'The record id of the item that was checked out', ".
					"`dateAdded` INT NOT NULL COMMENT 'The date the record was added to the wishlist', ".
					"`status` ENUM('active', 'deleted', 'filled') NOT NULL COMMENT 'The status of the item in the wishlist' ".
				") ENGINE = MYISAM COMMENT = 'The ratings for eContent records' ",
			),
		),

		'acsLog'	=> array(
			'title' => 'ACS Log',
			'description' => 'Create table to store	log of ACS transactions that have been returned by the server.',
			'dependencies' => array(),
			'sql' => array(
				"DROP TABLE IF EXISTS acs_log;",
				"CREATE TABLE IF NOT EXISTS	acs_log(".
					"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
					"`acsTransactionId` VARCHAR(50) NULL, ".
					"`userAcsId` VARCHAR(50) NULL, ".
					"`fulfilled` TINYINT NOT NULL, ".
					"`returned` TINYINT NOT NULL, ".
					"`transactionDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ".
				") ENGINE = MYISAM COMMENT = 'A trasaction log for transactions sent by the ACS server.' ",
			),
		),

		'econtent_marc_import'	=> array(
			'title' => 'EContent Marc Import Log',
			'description' => 'Create table to store log of Marc File Imports.',
			'dependencies' => array(),
			'sql' => array(
				"DROP TABLE IF EXISTS econtent_marc_import;",
				"CREATE TABLE IF NOT EXISTS	econtent_marc_import(".
					"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
					"`filename` VARCHAR(255) NULL, ".
					"`dateStarted` INT(11) NOT NULL, ".
					"`dateFinished` INT(11) NULL, ".
					"`status` ENUM('running', 'finished') NOT NULL, ".
					"`recordsProcessed` INT(11) NOT NULL DEFAULT 0 ".
				") ENGINE = MYISAM COMMENT = 'A trasaction log for marc files imported into the database.' ",
			),
		),

		'econtent_marc_import_1'	=> array(
			'title' => 'EContent Marc Import Update 1',
			'description' => 'Updates Log to include number of records that had errors with any error messages.',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE econtent_marc_import ADD COLUMN recordsWithErrors INT(11) NOT NULL DEFAULT 0",
				"ALTER TABLE econtent_marc_import ADD COLUMN errors LONGTEXT",
			),
		),

		'econtent_marc_import_2'	=> array(
			'title' => 'EContent Marc Import Update 2',
			'description' => 'Updates Log to include supplemental file and source.',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE econtent_marc_import ADD COLUMN supplementalFilename VARCHAR(255)",
				"ALTER TABLE econtent_marc_import ADD COLUMN source VARCHAR(100)",
				"ALTER TABLE econtent_marc_import ADD COLUMN accessType VARCHAR(100)",
			),
		),

		'econtent_attach'	=> array(
			'title' => 'EContent Attachment Log',
			'description' => 'Create table to store log of attaching eContent to records.',
			'dependencies' => array(),
			'sql' => array(
				"DROP TABLE IF EXISTS econtent_attach;",
				"CREATE TABLE IF NOT EXISTS	econtent_attach(".
					"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
					"`sourcePath` VARCHAR(255) NULL, ".
					"`dateStarted` INT(11) NOT NULL, ".
					"`dateFinished` INT(11) NULL, ".
					"`status` ENUM('running', 'finished') NOT NULL, ".
					"`recordsProcessed` INT(11) NOT NULL DEFAULT 0 ".
				") ENGINE = MYISAM COMMENT = 'A trasaction log for eContent that has been added to records.' ",
			),
		),

		'econtent_attach_update_1' => array(
			'title' => 'EContent Attachment Log',
			'description' => 'Create table to store log of attaching eContent to records.',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE econtent_attach ADD numErrors INT(11) DEFAULT 0;",
				"ALTER TABLE econtent_attach ADD notes TEXT ;",
		),
		),

		'overdrive_record_cache'	=> array(
			'title' => 'OverDrive Record Cache',
			'description' => 'Create table to cache page information from OverDrive.',
			'dependencies' => array(),
			'sql' => array(
				"DROP TABLE IF EXISTS overdrive_record_cache;",
				"CREATE TABLE IF NOT EXISTS	overdrive_record_cache(".
					"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
					"`sourceUrl` VARCHAR(512) NULL, ".
					"`pageContents` LONGTEXT, ".
					"`lastLoaded` INT(11) NOT NULL ".
				") ENGINE = MYISAM COMMENT = 'A cache to store information about records within OverDrive.' ",
		),
		),

		'overdrive_account_cache'	=> array(
			'title' => 'OverDrive Account Cache',
			'description' => 'Create table to cache account pages from OverDrive.',
			'dependencies' => array(),
			'sql' => array(
				"DROP TABLE IF EXISTS overdrive_account_cache;",
				"CREATE TABLE IF NOT EXISTS	overdrive_account_cache(".
					"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
					"`userId` INT(11) NULL, ".
					"`holdPage` LONGTEXT, ".
					"`holdPageLastLoaded` INT(11) NOT NULL DEFAULT 0, ".
					"`bookshelfPage` LONGTEXT, ".
					"`bookshelfPageLastLoaded` INT(11) NOT NULL DEFAULT 0, ".
					"`wishlistPage` LONGTEXT, ".
					"`wishlistPageLastLoaded` INT(11) NOT NULL DEFAULT 0 ".
				") ENGINE = MYISAM COMMENT = 'A cache to store information about a user\'s account within OverDrive.' ",
		),
		),

		'econtent_record_detection_settings' => array(
			'title' => 'EContent Record Detection Settings',
			'description' => 'Create table to store information about how to determine if a record in the marc export is print or eContent.',
			'dependencies' => array(),
			'sql' => array(
				"DROP TABLE IF EXISTS econtent_record_detection_settings;",
				"CREATE TABLE IF NOT EXISTS	econtent_record_detection_settings(".
					"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
					"`fieldSpec` VARCHAR(100), ".
					"`valueToMatch` VARCHAR(100), ".
					"`source` VARCHAR(100), ".
					"`accessType` VARCHAR(30), ".
					"`item_type` VARCHAR(30), ".
					"`add856FieldsAsExternalLinks` TINYINT NOT NULL DEFAULT 0, ".
					"INDEX(source) ".
				") ENGINE = MYISAM COMMENT = 'A cache to store information about a user\'s account within OverDrive.' ",
			),
		),

		'econtent_availability' => array(
			'title' => 'EContent Availability Update',
			'description' => 'Update eContent titles to separate availability from items',
			'dependencies' => array(),
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE econtent_availability(
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					recordId INT NOT NULL,
					copiesOwned INT NOT NULL DEFAULT 1,
					availableCopies INT NOT NULL DEFAULT 1,
					numberOfHolds INT NOT NULL DEFAULT 0,
					libraryId INT NOT NULL
				)",
				"ALTER TABLE econtent_record ADD COLUMN itemLevelOwnership TINYINT NOT NULL default 0 COMMENT 'If Item level ownership is set on, ownership will be determined at the item level with one copy owned per item.	Library who owns the title is set based on libraryId in items table.	Otherwise ownership is determined based on content_availability table'",
				"UPDATE econtent_record SET accessType = 'external' WHERE accessType = 'free' and source != 'Gutenberg'",
				"UPDATE econtent_record SET itemLevelOwnership = 1 WHERE (accessType = 'external') and source != 'OverDrive' and source != 'Gutenberg' ORDER BY `econtent_record`.`externalId`	DESC",
				"ALTER TABLE econtent_item CHANGE `item_type` `item_type` ENUM( 'epub', 'pdf', 'jpg', 'gif', 'mp3', 'plucker', 'kindle', 'externalLink', 'externalMP3', 'interactiveBook', 'overdrive', 'external_web', 'external_ebook', 'external_eaudio', 'external_emusic', 'external_evideo', 'text', 'gifs', 'itunes' ) NOT NULL",
				"ALTER TABLE econtent_item ADD COLUMN size INT NOT NULL default 0",
				"ALTER TABLE econtent_item ADD COLUMN externalFormat VARCHAR(50) NULL",
				"ALTER TABLE econtent_item ADD COLUMN externalFormatId VARCHAR(25) NULL",
				"ALTER TABLE econtent_item ADD COLUMN externalFormatNumeric INT NULL",
				"ALTER TABLE econtent_item ADD COLUMN identifier VARCHAR(50) NULL COMMENT 'The ISBN, ASIN, or UPC for the item' ",
				"ALTER TABLE econtent_item ADD COLUMN sampleName_1 VARCHAR(512) NULL",
				"ALTER TABLE econtent_item ADD COLUMN sampleUrl_1 VARCHAR(512) NULL",
				"ALTER TABLE econtent_item ADD COLUMN sampleName_2 VARCHAR(512) NULL",
				"ALTER TABLE econtent_item ADD COLUMN sampleUrl_2 VARCHAR(512) NULL",
				"ALTER TABLE econtent_item DROP COLUMN overDriveId",
				"DROP TABLE overdrive_item",
				"DELETE FROM `econtent_item` where item_type = 'overdrive' and externalFormat IS NULL"
			),
		),

		'remove_gale_pdfs'	=> array(
			'title' => 'Remove Gale PDF Files',
			'description' => 'Remove Gale PDF files from the catalog.',
			'dependencies' => array(),
			'database' => 'dclecontent',
			'sql' => array(
				"DELETE econtent_item.* FROM `econtent_item` inner join econtent_record on econtent_record.id = econtent_item.recordId where source = 'Gale Group' and item_type = 'pdf' ",
		),
		),

		'econtent_file_packaging_log'	=> array(
			'title' => 'Create eContent Packaging Log',
			'description' => 'Create eContent Packaging Log',
			'dependencies' => array(),
			'database' => 'dclecontent',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS	econtent_file_packaging_log(".
					"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
					"`filename` VARCHAR(255), ".
					"`libraryFilename` VARCHAR(255), ".
					"`publisher` VARCHAR(255), ".
					"`distributorId` VARCHAR(128), ".
					"`copies` INT, ".
					"`dateFound` INT(11), ".
					"`econtentRecordId` INT(11), ".
					"`econtentItemId` INT(11), ".
					"`dateSentToPackaging` INT(11), ".
					"`packagingId` INT(11), ".
					"`acsError` MEDIUMTEXT, ".
					"`acsId` VARCHAR(128), ".
					"`status` ENUM('detected', 'recordFound', 'copiedToLibrary', 'itemGenerated', 'sentToAcs', 'acsIdGenerated', 'acsError', 'processingComplete', 'skipped'), ".
					"INDEX(distributorId), ".
					"INDEX(publisher), ".
					"INDEX(econtentItemId), ".
					"INDEX(status) ".
				") ENGINE = MYISAM COMMENT = 'A table to store information about diles that are being sent for packaging in the ACS server.' ",
		),
		),

		'add_indexes' => array(
			'title' => 'Add eContent indexes',
			'description' => 'Add indexes to econtent tables that were not defined originally',
			'dependencies' => array(),
			'continueOnError' => true,
			'sql' => array(
				'ALTER TABLE `econtent_checkout` ADD INDEX `RecordId` ( `recordId` ) ',
				'ALTER TABLE `econtent_history` ADD INDEX `RecordId` ( `recordId` ) ',
				'ALTER TABLE `econtent_hold` ADD INDEX `RecordId` ( `recordId` ) ',
				'ALTER TABLE `econtent_item` ADD INDEX `RecordId` ( `recordId` ) ',
				'ALTER TABLE `econtent_wishlist` ADD INDEX `RecordId` ( `recordId` ) ',
			),
		),

		'add_indexes_2' => array(
			'title' => 'Add eContent indexes 2',
			'description' => 'Add additional indexes to econtent tables that were not defined originally',
			'dependencies' => array(),
			'continueOnError' => true,
			'sql' => array(
				'ALTER TABLE `econtent_rating` ADD INDEX `RecordId` ( `recordId` ) ',
				'ALTER TABLE `econtent_hold` ADD INDEX `UserStatus` ( `userId`, `status` ) ',
				'ALTER TABLE `econtent_checkout` ADD INDEX `UserStatus` ( `userId`, `status` ) ',
				'ALTER TABLE `econtent_wishlist` ADD INDEX `UserStatus` ( `userId`, `status` ) ',
			),
		),

		'add_indexes_3' => array(
			'title' => 'Add eContent indexes 3',
			'description' => 'Add additional indexes to econtent tables that were not defined originally',
			'dependencies' => array(),
			'sql' => array(
				'ALTER TABLE `econtent_record` ADD INDEX ( `accessType` ) ',
				'ALTER TABLE `econtent_record` ADD INDEX ( `source` ) ',
				'ALTER TABLE `econtent_hold` ADD INDEX ( `status` ) ',
				'ALTER TABLE `econtent_checkout` ADD INDEX ( `status` ) ',
				'ALTER TABLE `econtent_wishlist` ADD INDEX ( `status` ) ',
			),
		),

		'utf8_update' => array(
			'title' => 'Update to UTF-8',
			'description' => 'Update database to use UTF-8 encoding',
			'dependencies' => array(),
			'sql' => array(
				"ALTER DATABASE " . $configArray['Database']['database_econtent_dbname'] . " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE acs_log CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE db_update CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE econtent_checkout CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE econtent_history CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE econtent_hold CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE econtent_item CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE econtent_marc_import CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE econtent_rating CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE econtent_record CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE econtent_wishlist CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE overdrive_account_cache CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE overdrive_record_cache CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
			),
		),

		'cleanup_1' => array(
			'title' => 'Cleanup 1',
			'description' => 'Remove unused database tables',
			'dependencies' => array(),
			'sql' => array(
				"DROP TABLE IF EXISTS overdrive_account_cache",
				"DROP TABLE IF EXISTS overdrive_record_cache",
			),
		),
		'addIndexDateAddedEcontentRecordTable' => array(
			'title' => 'Add an Index',
			'description' => 'Add an index to econtent_record table',
			'dependencies' => array(),
			'sql' => array('addDateAddIndexToEContentRecord'),
		),

		);
	}

	public function addDateAddIndexToEContentRecord()
	{
		$query = "SHOW INDEX FROM econtent_record WHERE Key_name = 'ECDateAdded'";
		$result = mysql_query($query);
		$numRows = mysql_num_rows($result);
		if($numRows !== 1)
		{
			$sql = 'ALTER TABLE `econtent_record` ADD INDEX `ECDateAdded` ( `date_added` )';
			mysql_query($sql);
		}
	}

	private function checkWhichUpdatesHaveRun($availableUpdates){
		foreach ($availableUpdates as $key=>$update){
			$update['alreadyRun'] = false;
			$result = mysql_query("SELECT * from db_update where update_key = '" . mysql_escape_string($key) . "'");
			$numRows = mysql_num_rows($result);
			if ($numRows != false){
				$update['alreadyRun'] = true;
			}
			$availableUpdates[$key] = $update;
		}
		return $availableUpdates;
	}

	private function markUpdateAsRun($update_key){
		$result = mysql_query("SELECT * from db_update where update_key = '" . mysql_escape_string($update_key) . "'");
		if (mysql_num_rows($result) != false){
			//Update the existing value
			mysql_query("UPDATE db_update SET date_run = CURRENT_TIMESTAMP WHERE update_key = '" . mysql_escape_string($update_key) . "'");
		}else{
			mysql_query("INSERT INTO db_update (update_key) VALUES ('" . mysql_escape_string($update_key) . "')");
		}
	}

	function getAllowableRoles(){
		return array('userAdmin');
	}

	private function createUpdatesTable(){
		//Check to see if the updates table exists
		$result = mysql_query("SHOW TABLES");
		$tableFound = false;
		if ($result){
			while ($row = mysql_fetch_array($result, MYSQL_NUM)){
				if ($row[0] == 'db_update'){
					$tableFound = true;
					break;
				}
			}
		}
		if (!$tableFound){
			//Create the table to mark which updates have been run.
			mysql_query("CREATE TABLE db_update (" .
										"update_key VARCHAR( 100 ) NOT NULL PRIMARY KEY ," .
										"date_run TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" .
										") ENGINE = InnoDB");
		}
	}

	private function convertOldEContent(){
		require_once 'sys/eContent/EContentItem.php';
		require_once 'sys/eContent/EContentRecord.php';
		//Load eContent from the old database
		mysql_select_db($configArray['Database']['database_vufind_dbname']);
		$result = mysql_query("SELECT * FROM epub_files");
		$numRecordsProcessed = 0;
		mysql_select_db($configArray['Database']['database_econtent_dbname']);
		if ($result){
			while ($row = mysql_fetch_assoc($result)){
				//Add the Record
				$econtentRecord = new EContentRecord();
				$recordId = $row['relatedRecords'];
				if (strpos($recordId, '|') > 0){
					$recordIds = explode('|',$recordId);
					$recordId = $recordIds[0];
				}
				$econtentRecord->ilsId = $recordId;
				$econtentRecord->cover = $row['cover'];
				$econtentRecord->source = $row['source'];
				$econtentRecord->title = $row['title'];
				$econtentRecord->author = $row['author'];
				$econtentRecord->description = $row['description'];
				$econtentRecord->addedBy = -1;
				$econtentRecord->date_added = strtotime($row['createDate']);
				$econtentRecord->date_updated = strtotime($row['createDate']);
				if ($row['hasDRM'] == 0){
					$econtentRecord->accessType = 'free';
				}elseif ($row['hasDRM'] == 1){
					$econtentRecord->accessType = 'acs';
				}else{
					$econtentRecord->accessType = 'singleUse';
				}
				$econtentRecord->availableCopies = $row['availableCopies'];
				$econtentRecord->insert();

				//Add the item
				$econtentItem = new EContentItem();
				$econtentItem->filename = $row['filename'];
				$econtentItem->acsId = $row['acsId'];
				$econtentItem->folder = $row['folder'];
				$econtentItem->recordId = $econtentRecord->id;
				$econtentItem->item_type = strtolower($row['type']);
				$econtentItem->notes = $row['notes'];
				$econtentItem->addedBy = -1;
				$econtentItem->date_added = strtotime($row['createDate']);
				$econtentItem->date_updated = strtotime($row['createDate']);
				$econtentItem->insert();
				$numRecordsProcessed++;
				/*if ($numRecordsProcessed > 10){
					break;
					}*/
			}
		}
		return "Update succeeded, processed $numRecordsProcessed records.";
	}

}