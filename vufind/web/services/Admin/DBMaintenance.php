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

require_once 'Action.php';
require_once 'services/Admin/Admin.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 *
 * @author Mark Noble
 *
 */
class DBMaintenance extends Admin {
	function launch() 	{
		global $configArray;
		global $interface;
		mysql_select_db($configArray['Database']['database_vufind_dbname']);
							
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
						$this->setTimeLimit(120);
						if (method_exists($this, $sql)){
							$this->$sql();
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
								$update['status'] = 'Update succeeded';
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
		$interface->setPageTitle('Database Maintenance');
		$interface->display('layout.tpl');

	}

	private function getSQLUpdates() {
		global $configArray;
		
		return array(
			'roles_1' => array(
				'title' => 'Roles 1',
				'description' => 'Add new role for epubAdmin',
				'dependencies' => array(),
				'sql' => array(
					"INSERT INTO roles (name, description) VALUES ('epubAdmin', 'Allows administration of eContent.')",
				),
			),
			'library_1' => array(
				'title' => 'Library 1',
				'description' => 'Update Library table to include showSeriesAsTab column',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE library ADD COLUMN showSeriesAsTab TINYINT NOT NULL DEFAULT '0';",
					"UPDATE library SET showSeriesAsTab = '1' where subdomain in ('adams') ",
				),
			),
			'library_2' => array(
				'title' => 'Library 2',
				'description' => 'Update Library table to include showItsHere column',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE library ADD COLUMN showItsHere TINYINT NOT NULL DEFAULT '1';",
					"UPDATE library SET showItsHere = '0' where subdomain in ('adams', 'msc') ",
				),
			),
			'library_3' => array(
				'title' => 'Library 3',
				'description' => 'Update Library table to include holdDisclaimer column',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE library ADD COLUMN holdDisclaimer TEXT;",
					"UPDATE library SET holdDisclaimer = 'I understand that by requesting this item, information from my library patron record, including my contact information may be made available to the lending library.' where subdomain in ('msc') ",
				),
			),
			'library_5' => array(
				'title' => 'Library 5',
				'description' => 'Set up a link to boopsie in mobile',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `library` ADD `boopsieLink` VARCHAR(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;",
				),
			),
			'library_6' => array(
				'title' => 'Library 6',
				'description' => 'Add fields orginally defined for Marmot',
				'dependencies' => array(),
				'continueOnError' => true,
				'sql' => array(
			
					"ALTER TABLE `library` ADD `showHoldCancelDate` TINYINT(4) NOT NULL DEFAULT '0';",
					"ALTER TABLE `library` ADD `enablePospectorIntegration` TINYINT(4) NOT NULL DEFAULT '0';",
					"ALTER TABLE `library` ADD `prospectorCode` VARCHAR(10) NOT NULL DEFAULT '';",
					"ALTER TABLE `library` ADD `showRatings` TINYINT(4) NOT NULL DEFAULT '1';",
					"ALTER TABLE `library` ADD `searchesFile` VARCHAR(15) NOT NULL DEFAULT 'default';",
					"ALTER TABLE `library` ADD `minimumFineAmount` FLOAT NOT NULL DEFAULT '0';",
					"UPDATE library set minimumFineAmount = '5' where showEcommerceLink = '1'",
					"ALTER TABLE `library` ADD `enableGenealogy` TINYINT(4) NOT NULL DEFAULT '0';",
					"ALTER TABLE `library` ADD `enableCourseReserves` TINYINT(1) NOT NULL DEFAULT '0';",
					"ALTER TABLE `library` ADD `exportOptions` VARCHAR(100) NOT NULL DEFAULT 'RefWorks|EndNote';",
					"ALTER TABLE `library` ADD `enableSelfRegistration` TINYINT NOT NULL DEFAULT '0';",
					"ALTER TABLE `library` ADD `useHomeLinkInBreadcrumbs` TINYINT(4) NOT NULL DEFAULT '0';",
				),
			),
			'library_7' => array(
				'title' => 'Library 7',
				'description' => 'Allow materials request to be enabled or disabled by library',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `library` ADD `enableMaterialsRequest` TINYINT DEFAULT '1';",
				),
			),
			'location_1' => array(
				'title' => 'Location 1',
				'description' => 'Add fields orginally defined for Marmot',
				'dependencies' => array(),
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `location` ADD `defaultPType` INT(11) NOT NULL DEFAULT '-1';",
					"ALTER TABLE `location` ADD `ptypesToAllowRenewals` VARCHAR(128) NOT NULL DEFAULT '*';"
				),
			),
		
      'user_display_name' => array(
        'title' => 'User display name',
        'description' => 'Add displayName field to User table to allow users to have aliases',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE user ADD displayName VARCHAR( 30 ) NOT NULL DEFAULT ''",
		),
		),
		
		'user_phone' => array(
        'title' => 'User phone',
        'description' => 'Add phone field to User table to allow phone numbers to be displayed for Materials Requests',
				'continueOnError' => true,
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE user ADD phone VARCHAR( 30 ) NOT NULL DEFAULT ''",
		),
		),
		
		'user_ilsType' => array(
        'title' => 'User Type',
        'description' => 'Add patronType field to User table to allow for functionality to be controlled based on the type of patron within the ils',
        'dependencies' => array(),
				'continueOnError' => true,
        'sql' => array(
          "ALTER TABLE user ADD patronType VARCHAR( 30 ) NOT NULL DEFAULT ''",
		),
		),
    	 
      'list_widgets' => array(
        'title' => 'Setup Configurable List Widgets',
        'description' => 'Create tables related to configurable list widgets',
        'dependencies' => array(),
        'sql' => array(
          "CREATE TABLE IF NOT EXISTS list_widgets (".
            "`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
            "`name` VARCHAR(50) NOT NULL, " . 
            "`description` TEXT, " .
            "`showTitleDescriptions` TINYINT DEFAULT 1, " .
            "`onSelectCallback` VARCHAR(255) DEFAULT '' " .
          ") ENGINE = MYISAM COMMENT = 'A widget that can be displayed within VuFind or within other sites' ",
          "CREATE TABLE IF NOT EXISTS list_widget_lists (".
            "`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
            "`listWidgetId` INT NOT NULL, " .
            "`weight` INT NOT NULL DEFAULT 0, " .
            "`displayFor` ENUM('all', 'loggedIn', 'notLoggedIn') NOT NULL DEFAULT 'all', " .
            "`name` VARCHAR(50) NOT NULL, " .
            "`source` VARCHAR(500) NOT NULL, " . 
            "`fullListLink` VARCHAR(500) DEFAULT '' " .
          ") ENGINE = MYISAM COMMENT = 'The lists that should appear within the widget' ",
    	     ),
    	     ),
      
      'list_widgets_home' => array(
        'title' => 'List Widget Home',
        'description' => 'Create the default homepage widget',
        'dependencies' => array(),
        'sql' => array(
					"INSERT INTO list_widgets (name, description, showTitleDescriptions, onSelectCallback) VALUES ('home', 'Default example widget.', '1','')",
					"INSERT INTO list_widget_lists (listWidgetId, weight, source, name, displayFor, fullListLink) VALUES ('1', '1', 'highestRated', 'Highest Rated', 'all', '')",
					"INSERT INTO list_widget_lists (listWidgetId, weight, source, name, displayFor, fullListLink) VALUES ('1', '2', 'recentlyReviewed', 'Recently Reviewed', 'all', '')",
				),
			),

      'list_wdiget_list_update_1' => array(
        'title' => 'List Widget List Source Length Update',
        'description' => 'Update length of source field to accommodate search source type',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE `list_widget_lists` CHANGE `source` `source` VARCHAR( 500 ) NOT NULL "
        ),
      ),
			
      'index_search_stats' => array(
        'title' => 'Index search stats table',
        'description' => 'Add index to search stats table to improve autocomplete speed',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE `search_stats` ADD INDEX `search_index` ( `type` , `libraryId` , `locationId` , `phrase`, `numResults` )",
        ),
      ),
      'list_wdiget_update_1' => array(
        'title' => 'Update List Widget 1',
        'description' => 'Update List Widget to allow custom css files to be included and allow lists do be displayed in dropdown rather than tabs',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE `list_widgets` ADD COLUMN `customCss` VARCHAR( 500 ) NOT NULL ",
          "ALTER TABLE `list_widgets` ADD COLUMN `listDisplayType` ENUM('tabs', 'dropdown') NOT NULL DEFAULT 'tabs'"
        ),
      ),
      'library_4' => array(
				'title' => 'Library 4',
				'description' => 'Update Library table to include enableAlphaBrowse column',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE library ADD COLUMN enableAlphaBrowse TINYINT DEFAULT '1';",
				),
			),
			
			'genealogy' => array(
				'title' => 'Genealogy Setup',
				'description' => 'Initial setup of genealogy information',
				'continueOnError' => true,
				'dependencies' => array(),
				'sql' => array(
					//-- setup tables related to the genealogy section
					//-- person table
					"CREATE TABLE `person` (
					`personId` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`firstName` VARCHAR( 100 ) NULL ,
					`middleName` VARCHAR( 100 ) NULL ,
					`lastName` VARCHAR( 100 ) NULL ,
					`maidenName` VARCHAR( 100 ) NULL ,
					`otherName` VARCHAR( 100 ) NULL ,
					`nickName` VARCHAR( 100 ) NULL ,
					`birthDate` DATE NULL ,
					`birthDateDay` INT NULL COMMENT 'The day of the month the person was born empty or null if not known',
					`birthDateMonth` INT NULL COMMENT 'The month the person was born, null or blank if not known',
					`birthDateYear` INT NULL COMMENT 'The year the person was born, null or blank if not known',
					`deathDate` DATE NULL ,
					`deathDateDay` INT NULL COMMENT 'The day of the month the person died empty or null if not known',
					`deathDateMonth` INT NULL COMMENT 'The month the person died, null or blank if not known',
					`deathDateYear` INT NULL COMMENT 'The year the person died, null or blank if not known',
					`ageAtDeath` TEXT NULL ,
					`cemeteryName` VARCHAR( 255 ) NULL ,
					`cemeteryLocation` VARCHAR( 255 ) NULL ,
					`mortuaryName` VARCHAR( 255 ) NULL ,
					`comments` MEDIUMTEXT NULL,
					`picture` VARCHAR( 255 ) NULL
					) ENGINE = MYISAM COMMENT = 'Stores information about a particular person for use in genealogy';",

					//-- marriage table
					"CREATE TABLE `marriage` (
					`marriageId` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`personId` INT NOT NULL COMMENT 'A link to one person in the marriage',
					`spouseName` VARCHAR( 200 ) NULL COMMENT 'The name of the other person in the marriage if they aren''t in the database',
					`spouseId` INT NULL COMMENT 'A link to the second person in the marriage if the person is in the database',
					`marriageDate` DATE NULL COMMENT 'The date of the marriage if known.',
					`marriageDateDay` INT NULL COMMENT 'The day of the month the marriage occurred empty or null if not known',
					`marriageDateMonth` INT NULL COMMENT 'The month the marriage occurred, null or blank if not known',
					`marriageDateYear` INT NULL COMMENT 'The year the marriage occurred, null or blank if not known',
					`comments` MEDIUMTEXT NULL
					) ENGINE = MYISAM COMMENT = 'Information about a marriage between two people';",


					//-- obituary table
					"CREATE TABLE `obituary` (
					`obituaryId` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`personId` INT NOT NULL COMMENT 'The person this obituary is for',
					`source` VARCHAR( 255 ) NULL ,
					`date` DATE NULL ,
					`dateDay` INT NULL COMMENT 'The day of the month the obituary came out empty or null if not known',
					`dateMonth` INT NULL COMMENT 'The month the obituary came out, null or blank if not known',
					`dateYear` INT NULL COMMENT 'The year the obituary came out, null or blank if not known',
					`sourcePage` VARCHAR( 25 ) NULL ,
					`contents` MEDIUMTEXT NULL ,
					`picture` VARCHAR( 255 ) NULL
					) ENGINE = MYISAM  COMMENT = 'Information about an obituary for a person';",
				),
			),
			
			'genealogy_1' => array(
				'title' => 'Genealogy Update 1',
				'description' => 'Update Genealogy 1 for Steamboat Springs to add cemetery information.',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE person ADD COLUMN veteranOf VARCHAR(100) NULL DEFAULT ''",
					"ALTER TABLE person ADD COLUMN addition VARCHAR(100) NULL DEFAULT ''",
					"ALTER TABLE person ADD COLUMN block VARCHAR(100) NULL DEFAULT ''",
					"ALTER TABLE person ADD COLUMN lot INT(11) NULL",
					"ALTER TABLE person ADD COLUMN grave INT(11) NULL",
					"ALTER TABLE person ADD COLUMN tombstoneInscription TEXT",
					"ALTER TABLE person ADD COLUMN addedBy INT(11) NOT NULL DEFAULT -1",
					"ALTER TABLE person ADD COLUMN dateAdded INT(11) NULL",
					"ALTER TABLE person ADD COLUMN modifiedBy INT(11) NOT NULL DEFAULT -1",
					"ALTER TABLE person ADD COLUMN lastModified INT(11) NULL",
					"ALTER TABLE person ADD COLUMN privateComments TEXT",
					"ALTER TABLE person ADD COLUMN importedFrom VARCHAR(50) NULL",
				),
			),
      
      'recommendations_optOut' => array(
        'title' => 'Recommendations Opt Out',
        'description' => 'Add tracking for whether the user wants to opt out of recommendations',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE `user` ADD `disableRecommendations` TINYINT NOT NULL DEFAULT '0'",
    	     ),
    	     ),

      'editorial_review' => array(
        'title' => 'Create Editorial Review table',
        'description' => 'Create table to store editorial reviews from external reviews, i.e. book-a-day blog',
        'dependencies' => array(),
        'sql' => array(
          "CREATE TABLE editorial_reviews (".
            "editorialReviewId int NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
            "recordId VARCHAR(50) NOT NULL, ".
            "title VARCHAR(255) NOT NULL, ".
            "pubDate BIGINT NOT NULL, ".
            "review TEXT, ".
            "source VARCHAR(50) NOT NULL".
          ")",
    	     ),
    	     ),
			'purchase_link_tracking' => array(
        'title' => 'Create Purchase Link Tracking Table',
        'description' => 'Create table to track data about the Purchase Links that were clicked',
        'dependencies' => array(),
        'sql' => array(
				  'CREATE TABLE IF NOT EXISTS purchase_link_tracking (' .
				  'purchaseLinkId int(11) NOT NULL AUTO_INCREMENT, '.
				  'ipAddress varchar(30) NULL, '.
          'recordId VARCHAR(50) NOT NULL, '.
          'store VARCHAR(255) NOT NULL, '.
				  'trackingDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, '.
				  'PRIMARY KEY (purchaseLinkId) '.
					') ENGINE=InnoDB',

           'ALTER TABLE purchase_link_tracking ADD INDEX ( `purchaseLinkId` )',
    	     ),
			),
      'usage_tracking' => array(
        'title' => 'Create Usage Tracking Table',
        'description' => 'Create table to track aggregate page view data',
        'dependencies' => array(),
        'sql' => array(
				  'CREATE TABLE IF NOT EXISTS usage_tracking (' .
				  'usageId int(11) NOT NULL AUTO_INCREMENT, '.
				  'ipId INT NOT NULL, ' .
					'locationId INT NOT NULL, ' .
					'numPageViews INT NOT NULL DEFAULT "0", ' .
					'numHolds INT NOT NULL DEFAULT "0", ' .
					'numRenewals INT NOT NULL DEFAULT "0", ' .
          "trackingDate BIGINT NOT NULL, ".
				  'PRIMARY KEY (usageId) '.
					') ENGINE=InnoDB',

           'ALTER TABLE usage_tracking ADD INDEX ( `usageId` )',
				),
			),		
			
			'resource_update_table' => array(
				'title' => 'Update resource table',
				'description' => 'Update resource tracking table to include additional information resources for sorting',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE resource ADD author VARCHAR(255)',
					'ALTER TABLE resource ADD title_sort VARCHAR(255)',
					'ALTER TABLE resource ADD isbn VARCHAR(13)',
					'ALTER TABLE resource ADD upc VARCHAR(13)', //Have to use 13 since some publishers use the ISBN as the UPC.
					'ALTER TABLE resource ADD format VARCHAR(50)',
					'ALTER TABLE resource ADD format_category VARCHAR(50)',
					'ALTER TABLE `resource` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci',
				),
			),
			
			'resource_update_table_2' => array(
				'title' => 'Update resource table 2',
				'description' => 'Update resource tracking table to make sure that title and author are utf8 encoded',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `resource` CHANGE `title` `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''",
					"ALTER TABLE `resource` CHANGE `source` `source` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'VuFind'",
					"ALTER TABLE `resource` CHANGE `author` `author` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci",
					"ALTER TABLE `resource` CHANGE `title_sort` `title_sort` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci",
				),
			),
			
			'resource_update3' => array(
				'title' => 'Update resource table 3',
				'description' => 'Update resource table to include the checksum of the marc record so we can skip updating records that haven\'t changed',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `resource` ADD marc_checksum BIGINT",
					"ALTER TABLE `resource` ADD date_updated INT(11)",
				),
			),
			
			'resource_update4' => array(
				'title' => 'Update resource table 4',
				'description' => 'Update resource table to include a field for the actual marc record',
				'dependencies' => array(),
				'continueOnError' => true,
        'sql' => array(
					"ALTER TABLE `resource` ADD marc BLOB",
				),
			),
			
			'resource_update5' => array(
				'title' => 'Update resource table 5',
				'description' => 'Add a short id column for use with certain ILS i.e. Millennium',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `resource` ADD shortId VARCHAR(20)",
					"ALTER TABLE `resource` ADD INDEX (shortId)", 
				),
			),
			
			'resource_update6' => array(
				'title' => 'Update resource table 6',
				'description' => 'Add a deleted column to determine if a resource has been removed from the catalog',
				'dependencies' => array(),
				'continueOnError' => true,
        'sql' => array(
					"ALTER TABLE `resource` ADD deleted TINYINT DEFAULT '0'",
					"ALTER TABLE `resource` ADD INDEX (deleted)", 
				),
			),
			
			'resource_update7' => array(
				'title' => 'Update resource table 7',
				'description' => 'Increase the size of the marc field to avoid indexing errors updating the resources table. ',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `resource` CHANGE marc marc LONGBLOB",
				),
			),
			
			'resource_callnumber' => array(
				'title' => 'Resource call numbers',
				'description' => 'Create table to store call numbers for resources',
				'dependencies' => array(),
				'sql' => array(
				  'CREATE TABLE IF NOT EXISTS resource_callnumber (' .
				  'id int(11) NOT NULL AUTO_INCREMENT, '.
				  'resourceId INT NOT NULL, ' .
					'locationId INT NOT NULL, ' .
					'callnumber VARCHAR(50) NOT NULL DEFAULT "", ' .
					'PRIMARY KEY (id), '.
					'INDEX (`callnumber`), ' .
					'INDEX (`resourceId`), ' .
					'INDEX (`locationId`)' .
					') ENGINE=InnoDB',
				),
			),
			
			'resource_subject' => array(
				'title' => 'Resource subject',
				'description' => 'Create table to store subjects for resources',
				'dependencies' => array(),
				'sql' => array(
					'CREATE TABLE IF NOT EXISTS subject (' .
				  'id int(11) NOT NULL AUTO_INCREMENT, '.
				  'subject VARCHAR(100) NOT NULL, ' .
					'PRIMARY KEY (id), '.
					'INDEX (`subject`)' .
					') ENGINE=InnoDB',
			
				  'CREATE TABLE IF NOT EXISTS resource_subject (' .
				  'id int(11) NOT NULL AUTO_INCREMENT, '.
				  'resourceId INT(11) NOT NULL, ' .
					'subjectId INT(11) NOT NULL, ' .
					'PRIMARY KEY (id), '.
					'INDEX (`resourceId`), ' .
					'INDEX (`subjectId`)' .
					') ENGINE=InnoDB',
				),
			),

			'readingHistory' => array(
        'title' => 'Reading History Creation',
        'description' => 'Update reading History to include an id table',
        'dependencies' => array(),
        'sql' => array(
			    "CREATE TABLE IF NOT EXISTS  user_reading_history(" .
				    "`userId` INT NOT NULL COMMENT 'The id of the user who checked out the item', " .
						"`resourceId` INT NOT NULL COMMENT 'The record id of the item that was checked out', " .
						"`lastCheckoutDate` DATE NOT NULL COMMENT 'The first day we detected that the item was checked out to the patron', " .
						"`firstCheckoutDate` DATE NOT NULL COMMENT 'The last day we detected the item was checked out to the patron', " .
						"`daysCheckedOut` INT NOT NULL COMMENT 'The total number of days the item was checked out even if it was checked out multiple times.', " .
						"PRIMARY KEY ( `userId` , `resourceId` )" .
					") ENGINE = MYISAM COMMENT = 'The reading history for patrons';",
	      ),
			),
			
      'coverArt_suppress' => array(
        'title' => 'Cover Art Suppress',
        'description' => 'Add tracking for whether the user wants to suppress cover art',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE `user` ADD `disableCoverArt` TINYINT NOT NULL DEFAULT '0'",
        ),
      ),
      
      'externalLinkTracking' => array(
        'title' => 'Create External Link Tracking Table',
        'description' => 'Create table to track links to external sites from 856 tags or eContent',
        'dependencies' => array(),
        'sql' => array(
				  'CREATE TABLE IF NOT EXISTS external_link_tracking (' .
				  'externalLinkId int(11) NOT NULL AUTO_INCREMENT, '.
				  'ipAddress varchar(30) NULL, '.
          'recordId varchar(50) NOT NULL, '.
          'linkUrl varchar(400) NOT NULL, '.
          'linkHost varchar(200) NOT NULL, '.
				  'trackingDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, '.
				  'PRIMARY KEY (externalLinkId) '.
					') ENGINE=InnoDB',
	      ),
			),
			
			'readingHistoryUpdate1' => array(
        'title' => 'Reading History Update 1',
        'description' => 'Update reading History to include an id table',
        'dependencies' => array(),
        'sql' => array(
			    'ALTER TABLE `user_reading_history` DROP PRIMARY KEY',
			    'ALTER TABLE `user_reading_history` ADD UNIQUE `user_resource` ( `userId` , `resourceId` ) ',
          'ALTER TABLE `user_reading_history` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ',
	      ),
			),
			
			'materialsRequest' => array(
        'title' => 'Materials Request Table Creation',
        'description' => 'Update reading History to include an id table',
        'dependencies' => array(),
        'sql' => array(
				  'CREATE TABLE IF NOT EXISTS materials_request (' .
				  'id int(11) NOT NULL AUTO_INCREMENT, '.
				  'title varchar(255), '.
          'author varchar(255), '.
          'format varchar(25), '.
          'ageLevel varchar(25), '.
				  'isbn_upc varchar(15), '.
				  'oclcNumber varchar(30), '.
				  'publisher varchar(255), '.
				  'publicationYear varchar(4), '.
				  'articleInfo varchar(255), '.
				  'abridged TINYINT, '.
				  'about TEXT, '.
				  'comments TEXT, '.
				  "status enum('pending', 'owned', 'purchased', 'referredToILL', 'ILLplaced', 'ILLreturned', 'notEnoughInfo', 'notAcquiredOutOfPrint', 'notAcquiredNotAvailable', 'notAcquiredFormatNotAvailable', 'notAcquiredPrice', 'notAcquiredPublicationDate', 'requestCancelled') DEFAULT 'pending', ".
				  'dateCreated int(11), '.
				  'createdBy int(11), ' .
				  'dateUpdated int(11), '.
				  'PRIMARY KEY (id) '.
					') ENGINE=InnoDB',
	      ),
			),
			
			'materialsRequest_update1' => array(
				'title' => 'Materials Request Update 1',
				'description' => 'Material Request add fields for sending emails and creating holds',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `emailSent` TINYINT NOT NULL DEFAULT 0',
					'ALTER TABLE `materials_request` ADD `holdsCreated` TINYINT NOT NULL DEFAULT 0',
				),
			),
			
			'materialsRequest_update2' => array(
				'title' => 'Materials Request Update 2',
				'description' => 'Material Request add fields phone and email so user can supply a different email address',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `email` VARCHAR(80)',
					'ALTER TABLE `materials_request` ADD `phone` VARCHAR(15)',
				),
			),
			
			'materialsRequest_update3' => array(
				'title' => 'Materials Request Update 3',
				'description' => 'Material Request add fields season, magazineTitle, split isbn and upc',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `season` VARCHAR(80)',
					'ALTER TABLE `materials_request` ADD `magazineTitle` VARCHAR(255)',
					'ALTER TABLE `materials_request` CHANGE `isbn_upc` `isbn` VARCHAR( 15 )',
					'ALTER TABLE `materials_request` ADD `upc` VARCHAR(15)',
					'ALTER TABLE `materials_request` ADD `issn` VARCHAR(8)',
					'ALTER TABLE `materials_request` ADD `bookType` VARCHAR(20)',
					'ALTER TABLE `materials_request` ADD `subFormat` VARCHAR(20)',
					'ALTER TABLE `materials_request` ADD `magazineDate` VARCHAR(20)',
					'ALTER TABLE `materials_request` ADD `magazineVolume` VARCHAR(20)',
					'ALTER TABLE `materials_request` ADD `magazinePageNumbers` VARCHAR(20)',
					'ALTER TABLE `materials_request` ADD `placeHoldWhenAvailable` TINYINT',
					'ALTER TABLE `materials_request` ADD `holdPickupLocation` VARCHAR(10)',
					'ALTER TABLE `materials_request` ADD `bookmobileStop` VARCHAR(50)',
				),
			),
			
			'materialsRequest_update4' => array(
				'title' => 'Materials Request Update 4',
				'description' => 'Material Request add illItem field and make status field not an enum so libraries can easily add statuses',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `illItem` VARCHAR(80)',
				),
			),
			
			'materialsRequest_update5' => array(
				'title' => 'Materials Request Update 5',
				'description' => 'Material Request add magazine number',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `magazineNumber` VARCHAR(80)',
				),
			),
			
			'materialsRequestStatus' => array(
        'title' => 'Materials Request Status Table Creation',
        'description' => 'Update reading History to include an id table',
        'dependencies' => array(),
        'sql' => array(
				  'CREATE TABLE IF NOT EXISTS materials_request_status (' .
				  'id int(11) NOT NULL AUTO_INCREMENT, '.
				  'description varchar(80), '.
          'isDefault TINYINT DEFAULT 0, '.
				  'sendEmailToPatron TINYINT, '.
          'emailTemplate TEXT, '.
				  'isOpen TINYINT, '.
				  'isPatronCancel TINYINT, '.
				  'PRIMARY KEY (id) '.
					') ENGINE=InnoDB',
			
					"INSERT INTO materials_request_status (description, isDefault, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Pending', 1, 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Already owned/On order', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.  Please check our online catalog periodically to put a hold for this item.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Item purchased', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - Adult', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - J/YA', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - AV', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('ILL Under Review', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Referred to ILL', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The library\'s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Filled by ILL', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Our Interlibrary Loan Department is set to borrow this item from another library.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Ineligible ILL', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Your library account is not eligible for interlibrary loan at this time.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Not enough info - please contact Collection Development to clarify', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We need more specific information in order to locate the exact item you need. Please re-submit your request with more details.', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - out of print', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is out of print.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not available in the US', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available in the US.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not available from vendor', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available from a preferred vendor.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not published', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested has not yet been published. Please check our catalog when the publication date draws near.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - price', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - publication date', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unavailable', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested cannot be purchased at this time from any of our regular suppliers and is not available from any of our lending libraries.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen, isPatronCancel) VALUES ('Cancelled by Patron', 0, '', 0, 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Cancelled - Duplicate Request', 0, '', 0)",
			
					"UPDATE materials_request SET status = (SELECT id FROM materials_request_status WHERE isDefault =1)",
			
					"ALTER TABLE materials_request CHANGE `status` `status` INT(11)"
			),
			),
			
			'catalogingRole' => array(
				'title' => 'Create cataloging role',
				'description' => 'Create cataloging role to handle materials requests, econtent loading, etc.',
				'dependencies' => array(),
				'sql' => array(
					"INSERT INTO `roles` (`name`, `description`) VALUES ('cataloging', 'Allows user to perform cataloging activities.')",
				),
			),
			
			'indexUsageTracking' => array(
				'title' => 'Index Usage Tracking',
				'description' => 'Update Usage Tracking to include index based on ip and tracking date',
				'dependencies' => array(),
				'continueOnError' => true,
        'sql' => array(
					"ALTER TABLE `usage_tracking` ADD INDEX `IP_DATE` ( `ipId` , `trackingDate` )",
				), 
			),
			
			'utf8_update' => array(
			'title' => 'Update to UTF-8',
			'description' => 'Update database to use UTF-8 encoding',
			'dependencies' => array(),
			'continueOnError' => true,
			'sql' => array(
				"ALTER DATABASE " . $configArray['Database']['database_vufind_dbname'] . " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE administrators CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE bad_words CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE circulation_status CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE comments CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE db_update CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE editorial_reviews CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE external_link_tracking CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE ip_lookup CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE library CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE list_widgets CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE list_widget_lists CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE location CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE nonHoldableLocations CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE ptype_restricted_locations CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE purchase_link_tracking CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE resource CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE resource_tags CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE roles CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE search CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE search_stats CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE session CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE spelling_words CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE tags CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE usage_tracking CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_list CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_rating CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_reading_history CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_resource CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_roles CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_suggestions CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
		),
		),
		
		'index_resources' => array(
			'title' => 'Index resources',
			'description' => 'Add a new index to resources table to make record id and source unique',
			'continueOnError' => true,
      'dependencies' => array(),
			'sql' => array(
				//Update resource table indexes
				"ALTER TABLE `resource` ADD UNIQUE `records_by_source` (`record_id`, `source`)" 
			),
		),
		
		'alpha_browse_setup' => array(
			'title' => 'Setup Alphabetic Browse',
			'description' => 'Create tables to handle alphabetic browse functionality.',
			'dependencies' => array(),
			'sql' => array(
				"CREATE TABLE `title_browse` ( 
					`id` INT NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`numResults` INT NOT NULL COMMENT 'The number of results found in the table',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `value` )
				) ENGINE = InnoDB;",
				"CREATE TABLE `author_browse` ( 
					`id` INT NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`numResults` INT NOT NULL COMMENT 'The number of results found in the table',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `value` )
				) ENGINE = InnoDB;",
				"CREATE TABLE `callnumber_browse` ( 
					`id` INT NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`numResults` INT NOT NULL COMMENT 'The number of results found in the table',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `value` )
				) ENGINE = InnoDB;",
				"CREATE TABLE `subject_browse` ( 
					`id` INT NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`numResults` INT NOT NULL COMMENT 'The number of results found in the table',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `value` )
				) ENGINE = InnoDB;",
			),
		),
		
		'reindexLog' => array(
      'title' => 'Reindex Log table',
      'description' => 'Create Reindex Log table to track reindexing.',
      'dependencies' => array(),
      'sql' => array(
		    "CREATE TABLE IF NOT EXISTS reindex_log(" .
					"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of reindex log', " .
					"`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the reindex started', " .
					"`endTime` INT(11) NULL COMMENT 'The timestamp when the reindex process ended', " .
					"PRIMARY KEY ( `id` )" .
				") ENGINE = MYISAM;",
				"CREATE TABLE IF NOT EXISTS reindex_process_log(" .
					"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of reindex process', " .
					"`reindex_id` INT(11) NOT NULL COMMENT 'The id of the reindex log this process ran during', " .
					"`processName` VARCHAR(50) NOT NULL COMMENT 'The name of the process being run', " .
					"`recordsProcessed` INT(11) NOT NULL COMMENT 'The number of records processed from marc files', "  . 
					"`eContentRecordsProcessed` INT(11) NOT NULL COMMENT 'The number of econtent records processed from the database', "  . 
					"`resourcesProcessed` INT(11) NOT NULL COMMENT 'The number of resources processed from the database', "  . 
					"`numErrors` INT(11) NOT NULL COMMENT 'The number of errors that occurred during the process', "  . 
					"`numAdded` INT(11) NOT NULL COMMENT 'The number of additions that occurred during the process', " .
					"`numUpdated` INT(11) NOT NULL COMMENT 'The number of items updated during the process', " .
					"`numDeleted` INT(11) NOT NULL COMMENT 'The number of items deleted during the process', " .
					"`numSkipped` INT(11) NOT NULL COMMENT 'The number of items skipped during the process', " .
					"`notes` TEXT COMMENT 'Additional information about the process', " .
					"PRIMARY KEY ( `id` ), INDEX ( `reindex_id` ), INDEX ( `processName` )" .
				") ENGINE = MYISAM;",
				
      ),
		),
		
		'cronLog' => array(
      'title' => 'Cron Log table',
      'description' => 'Create Cron Log table to track reindexing.',
      'dependencies' => array(),
      'sql' => array(
		    "CREATE TABLE IF NOT EXISTS cron_log(" .
					"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of the cron log', " .
					"`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the cron run started', " .
					"`endTime` INT(11) NULL COMMENT 'The timestamp when the cron run ended', " .
					"`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the cron run last updated (to check for stuck processes)', " .
					"`notes` TEXT COMMENT 'Additional information about the cron run', " .
					"PRIMARY KEY ( `id` )" .
				") ENGINE = MYISAM;",
				"CREATE TABLE IF NOT EXISTS cron_process_log(" .
					"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of cron process', " .
					"`cronId` INT(11) NOT NULL COMMENT 'The id of the cron run this process ran during', " .
					"`processName` VARCHAR(50) NOT NULL COMMENT 'The name of the process being run', " .
					"`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the process started', "  . 
					"`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the process last updated (to check for stuck processes)', " .
					"`endTime` INT(11) NULL COMMENT 'The timestamp when the process ended', "  . 
					"`numErrors` INT(11) NOT NULL DEFAULT 0 COMMENT 'The number of errors that occurred during the process', "  . 
					"`numUpdates` INT(11) NOT NULL DEFAULT 0 COMMENT 'The number of updates, additions, etc. that occurred', " .
					"`notes` TEXT COMMENT 'Additional information about the process', " .
					"PRIMARY KEY ( `id` ), INDEX ( `cronId` ), INDEX ( `processName` )" .
				") ENGINE = MYISAM;",
				
      ),
		),
		
		'marcImport' => array(
      'title' => 'Marc Import table',
      'description' => 'Create a table to store information about marc records that are being imported.',
      'dependencies' => array(),
      'sql' => array(
		    "CREATE TABLE IF NOT EXISTS marc_import(" .
			    "`id` VARCHAR(50) COMMENT 'The id of the marc record in the ils', " .
					"`checksum` INT(11) NOT NULL COMMENT 'The timestamp when the reindex started', " .
					"PRIMARY KEY ( `id` )" .
				") ENGINE = MYISAM;",
      ),
		),
		'marcImport_1' => array(
			'title' => 'Marc Import table Update 1',
			'description' => 'Increase the length of the checksum field for the marc import.',
			'dependencies' => array(),
			'sql' => array(
				"ALTER TABLE marc_import CHANGE `checksum` `checksum` BIGINT NOT NULL COMMENT 'The checksum of the id when it was last imported.'",
			),
		),
		'add_indexes' => array(
			'title' => 'Add indexes',
			'description' => 'Add indexes to tables that were not defined originally',
			'dependencies' => array(),
			'continueOnError' => true,
      'sql' => array(
				'ALTER TABLE `editorial_reviews` ADD INDEX `RecordId` ( `recordId` ) ',
				'ALTER TABLE `list_widget_lists` ADD INDEX `ListWidgetId` ( `listWidgetId` ) ',
				'ALTER TABLE `location` ADD INDEX `ValidHoldPickupBranch` ( `validHoldPickupBranch` ) ',
			),
		),
		
		'add_indexes2' => array(
			'title' => 'Add indexes 2',
			'description' => 'Add additional indexes to tables that were not defined originally',
			'dependencies' => array(),
			'continueOnError' => true,
      'sql' => array(
				'ALTER TABLE `user_rating` ADD INDEX `Resourceid` ( `resourceid` ) ',
				'ALTER TABLE `user_rating` ADD INDEX `UserId` ( `userid` ) ',
				'ALTER TABLE `materials_request_status` ADD INDEX ( `isDefault` )',
				'ALTER TABLE `materials_request_status` ADD INDEX ( `isOpen` )',
				'ALTER TABLE `materials_request_status` ADD INDEX ( `isPatronCancel` )',
				'ALTER TABLE `materials_request` ADD INDEX ( `status` )'
			),
		),
		
		'spelling_optimization' => array(
			'title' => 'Spelling Optimization',
			'description' => 'Optimizations to spelling to ensure indexes are used',
			'dependencies' => array(),
			'sql' => array(
				'ALTER TABLE `spelling_words` ADD `soundex` VARCHAR(20) ',
				'ALTER TABLE `spelling_words` ADD INDEX `Soundex` (`soundex`)',
				'UPDATE `spelling_words` SET soundex = SOUNDEX(word) '
			),
		),
		
		
		'remove_old_tables' => array(
			'title' => 'Remove old tables',
			'description' => 'Remove tables that are no longer needed due to usage of memcache',
			'dependencies' => array(),
			'sql' => array(
				//Update resource table indexes
				'DROP TABLE IF EXISTS list_cache',
				'DROP TABLE IF EXISTS list_cache2',
				'DROP TABLE IF EXISTS novelist_cache', 
				'DROP TABLE IF EXISTS reviews_cache',
				'DROP TABLE IF EXISTS sip2_item_cache',
			),
		),
		
		'rename_tables' => array(
			'title' => 'Rename tables',
			'description' => 'Rename tables for consistency and cross platform usage',
			'dependencies' => array(),
			'sql' => array(
				//Update resource table indexes
				'RENAME TABLE usageTracking TO usage_tracking',
				'RENAME TABLE nonHoldableLocations TO non_holdable_locations',
				'RENAME TABLE pTypeRestrictedLocations TO ptype_restricted_locations',
				'RENAME TABLE externalLinkTracking TO external_link_tracking',
				'RENAME TABLE circulationStatus TO circulation_status',
				'RENAME TABLE purchaseLinkTracking TO purchase_link_tracking'
			),
		),
		
		'addTablelistWidgetListsLinks' => array(
				'title' => 'Widget Lists',
				'description' => 'Add a new table: list_widget_lists_links',
				'dependencies' => array(),
				'sql' => array('addTableListWidgetListsLinks'),
		),
		
		
		'millenniumTables' => array(
				'title' => 'Millennium table setup',
				'description' => 'Add new tables for millennium installations',
				'dependencies' => array(),
				'continueOnError' => true,
				'sql' => array(
				"CREATE TABLE `millennium_cache` (
				    `recordId` VARCHAR( 20 ) NOT NULL COMMENT 'The recordId being checked',
				    `scope` int(16) NOT NULL COMMENT 'The scope that was loaded',
				    `holdingsInfo` MEDIUMTEXT NOT NULL COMMENT 'Raw HTML returned from Millennium for holdings',
				    `framesetInfo` MEDIUMTEXT NOT NULL COMMENT 'Raw HTML returned from Millennium on the frameset page',
				    `cacheDate` int(16) NOT NULL COMMENT 'When the entry was recorded in the cache'
				) ENGINE = MYISAM COMMENT = 'Caches information from Millennium so we do not have to continually load it.';",
				"ALTER TABLE `millennium_cache` ADD PRIMARY KEY ( `recordId` , `scope` ) ;",
		
				"CREATE TABLE IF NOT EXISTS `ptype_restricted_locations` (
				  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
				  `millenniumCode` varchar(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
				  `holdingDisplay` varchar(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium can use regular expression syntax to match multiple locations',
				  `allowablePtypes` varchar(50) NOT NULL COMMENT 'A list of PTypes that are allowed to place holds on items with this location separated with pipes (|).',
				  PRIMARY KEY (`locationId`)
				) ENGINE=MyISAM",
		
				"CREATE TABLE IF NOT EXISTS `non_holdable_locations` (
				  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
				  `millenniumCode` varchar(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
				  `holdingDisplay` varchar(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium',
				  `availableAtCircDesk` tinyint(4) NOT NULL COMMENT 'The item is available if the patron visits the circulation desk.',
				  PRIMARY KEY (`locationId`)
				) ENGINE=MyISAM"
		),

		),
		'location_hours' => array(
			'title' => 'Location Hours',
			'description' => 'Create table to store hours for a location',
			'dependencies' => array(),
			'sql' => array(				
				"CREATE TABLE IF NOT EXISTS location_hours (" .
					"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of hours entry', " .
					"`locationId` INT NOT NULL COMMENT 'The location id', " .
					"`day` INT NOT NULL COMMENT 'Day of the week 0 to 7 (Sun to Monday)', " .
					"`closed` TINYINT NOT NULL DEFAULT '0' COMMENT 'Whether or not the library is closed on this day', ".
					"`open` varchar(10) NOT NULL COMMENT 'Open hour (24hr format) HH:MM', " . 
					"`close` varchar(10) NOT NULL COMMENT 'Close hour (24hr format) HH:MM', ".
					"PRIMARY KEY ( `id` ), " .
					"UNIQUE KEY (`locationId`, `day`) " .
				") ENGINE=InnoDB",
			),
		),
		'holiday' => array(
			'title' => 'Holidays',
			'description' => 'Create table to store holidays',
			'dependencies' => array(),
			'sql' => array(				
				"CREATE TABLE IF NOT EXISTS holiday (" .
					"`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of holiday', " .
					"`libraryId` INT NOT NULL COMMENT 'The library system id', " .
					"`date` date NOT NULL COMMENT 'Date of holiday', " .
					"`name` varchar(100) NOT NULL COMMENT 'Name of holiday', " .
					"PRIMARY KEY ( `id` ), " .
					"UNIQUE KEY (`date`) " .
				") ENGINE=InnoDB",
			),
		),
		'book_store' => array(
			'title' => 'Book store table',
			'description' => 'Create a table to store information about book stores.',
			'dependencies' => array(),
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS book_store(" .
					"`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the book store', " .
					"`storeName` VARCHAR(100) NOT NULL COMMENT 'The name of the book store', " .
					"`link` VARCHAR(256) NOT NULL COMMENT 'The URL prefix for searching', " .
					"`linkText` VARCHAR(100) NOT NULL COMMENT 'The link text', " .
					"`image` VARCHAR(256) NOT NULL COMMENT 'The URL to the icon/image to display', " .
					"`resultRegEx` VARCHAR(100) NOT NULL COMMENT 'The regex used to check the search results', " .
					"PRIMARY KEY ( `id` )" .
				") ENGINE = InnoDB"
			),
		),
		'nearby_book_store' => array(
			'title' => 'Nearby book stores',
			'description' => 'Create a table to store book stores near a location.',
			'dependencies' => array(),
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS nearby_book_store(" .
					"`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of this association', " .
					"`libraryId` INT(11) NOT NULL COMMENT 'The id of the library', " .
					"`storeId` INT(11) NOT NULL COMMENT 'The id of the book store', " .
					"`weight` INT(11) NOT NULL DEFAULT 0 COMMENT 'The listing order of the book store', " .
					"KEY ( `libraryId`, `storeId` ), " .
					"PRIMARY KEY ( `id` )" .
				") ENGINE = InnoDB"
			),
		),
		);
	}
	
	private function setTimeLimit($time = 120)
	{
		set_time_limit(120);
	}
	private function freeMysqlResult($result)
	{
		mysql_free_result($result);
	}
	
	
	public function addTableListWidgetListsLinks()
	{
		$this->setTimeLimit(120);
		$sql =	'CREATE TABLE IF NOT EXISTS `list_widget_lists_links`( '.
				'`id` int(11) NOT NULL AUTO_INCREMENT, '.
				'`listWidgetListsId` int(11) NOT NULL, '.
				'`name` varchar(50) NOT NULL, '.
				'`link` text NOT NULL, '.
				'`weight` int(3) NOT NULL DEFAULT \'0\','.
				'PRIMARY KEY (`id`) '.
				') ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
		mysql_query($sql);
		$result = mysql_query('SELECT id,fullListLink FROM `list_widget_lists` WHERE `fullListLink` != "" ');
		while($row = mysql_fetch_assoc($result))
		{
			$sqlInsert = 'INSERT INTO `list_widget_lists_links` (`id`,`listWidgetListsId`,`name`,`link`) VALUES (NULL,\''.$row['id'].'\',\'Full List Link\',\''.$row['fullListLink'].'\') ';
			mysql_query($sqlInsert);
		}
		$this->freeMysqlResult($result);
		mysql_query('ALTER TABLE `list_widget_lists` DROP `fullListLink`');
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

}