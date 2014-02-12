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

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 *
 * @author Mark Noble
 *
 */
class DBMaintenance extends Admin_Admin {
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

						if (method_exists($this, $sql)){
							$this->$sql(&$update);
						}else{
							if (!$this->runSQLStatement(&$update, $sql)){
								break;
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
				'sql' => array(
					"INSERT INTO roles (name, description) VALUES ('epubAdmin', 'Allows administration of eContent.')",
				),
			),
			'library_1' => array(
				'title' => 'Library 1',
				'description' => 'Update Library table to include showSeriesAsTab column',
				'sql' => array(
					"ALTER TABLE library ADD COLUMN showSeriesAsTab TINYINT NOT NULL DEFAULT '0';",
					"UPDATE library SET showSeriesAsTab = '1' where subdomain in ('adams') ",
				),
			),
			'library_2' => array(
				'title' => 'Library 2',
				'description' => 'Update Library table to include showItsHere column',
				'sql' => array(
					"ALTER TABLE library ADD COLUMN showItsHere TINYINT NOT NULL DEFAULT '1';",
					"UPDATE library SET showItsHere = '0' where subdomain in ('adams', 'msc') ",
				),
			),
			'library_3' => array(
				'title' => 'Library 3',
				'description' => 'Update Library table to include holdDisclaimer column',
				'sql' => array(
					"ALTER TABLE library ADD COLUMN holdDisclaimer TEXT;",
					"UPDATE library SET holdDisclaimer = 'I understand that by requesting this item, information from my library patron record, including my contact information may be made available to the lending library.' where subdomain in ('msc') ",
				),
			),
			'library_5' => array(
				'title' => 'Library 5',
				'description' => 'Set up a link to boopsie in mobile',
				'sql' => array(
					"ALTER TABLE `library` ADD `boopsieLink` VARCHAR(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;",
				),
			),
			'library_6' => array(
				'title' => 'Library 6',
				'description' => 'Add fields orginally defined for Marmot',
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
				'sql' => array(
					"ALTER TABLE `library` ADD `enableMaterialsRequest` TINYINT DEFAULT '1';",
				),
			),
			'library_8' => array(
				'title' => 'Library 8',
				'description' => 'Add eContenLinkRules to determine how to load library specific link urls',
				'sql' => array(
					"ALTER TABLE `library` ADD `eContentLinkRules` VARCHAR(512) DEFAULT '';",
				),
			),
			'library_9' => array(
				'title' => 'Library 9',
				'description' => 'Add showOtherEditionsPopup to determine whether or not the Other Editions and Languages Popup is shown',
				'sql' => array(
					"ALTER TABLE `library` ADD `showOtherEditionsPopup` TINYINT DEFAULT '1';",
					"ALTER TABLE `library` ADD `showTableOfContentsTab` TINYINT DEFAULT '1';",
					"ALTER TABLE `library` ADD `notesTabName` VARCHAR(50) DEFAULT 'Notes';",
				),
			),
			'library_10' => array(
				'title' => 'Library 10',
				'description' => 'Add fields for showing copies in holdings summary, and hold button in results list',
				'sql' => array(
					"ALTER TABLE `library` ADD `showHoldButtonInSearchResults` TINYINT DEFAULT '1';",
					"ALTER TABLE `library` ADD `showCopiesLineInHoldingsSummary` TINYINT DEFAULT '1';",
				),
			),
			'library_11' => array(
				'title' => 'Library 11',
				'description' => 'Add fields for disabling some Novelist functionality and disabling boosting by number of holdings',
				'sql' => array(
					"ALTER TABLE `library` ADD `showSimilarAuthors` TINYINT DEFAULT '1';",
					"ALTER TABLE `library` ADD `showSimilarTitles` TINYINT DEFAULT '1';",
					"ALTER TABLE `library` ADD `showProspectorTitlesAsTab` TINYINT DEFAULT '1';",
					"ALTER TABLE `library` ADD `show856LinksAsTab` TINYINT DEFAULT '0';",
					"ALTER TABLE `library` ADD `applyNumberOfHoldingsBoost` TINYINT DEFAULT '1';",
					"ALTER TABLE `library` ADD `worldCatUrl` VARCHAR(100) DEFAULT '';",
					"ALTER TABLE `library` ADD `worldCatQt` VARCHAR(20) DEFAULT '';",
					"ALTER TABLE `library` ADD `preferSyndeticsSummary` TINYINT DEFAULT '1';",
				),
			),
			'library_12' => array(
				'title' => 'Library 12',
				'description' => 'Add abbreviation for library name for use in some cases where the full name is not desired.',
				'sql' => array(
					"ALTER TABLE `library` ADD `abbreviatedDisplayName` VARCHAR(20) DEFAULT '';",
					"UPDATE `library` SET `abbreviatedDisplayName` = LEFT(`displayName`, 20);",
				),
			),
			'library_13' => array(
				'title' => 'Library 13',
				'description' => 'Updates to World Cat integration for local libraries',
				'sql' => array(
					"ALTER TABLE `library` CHANGE `worldCatQt` `worldCatQt` VARCHAR(40) DEFAULT '';",
				),
			),
			'library_14' => array(
				'title' => 'Library 14',
				'description' => 'Allow Go Deeper to be disabled by Library',
				'sql' => array(
					"ALTER TABLE `library` ADD `showGoDeeper` TINYINT DEFAULT '1';",
				),
			),
			'library_15' => array(
				'title' => 'Library 15',
				'description' => 'Add showProspectorResultsAtEndOfSearch to library so prospector titles can be removed from search results without completely diasabling prospector',
				'sql' => array(
					"ALTER TABLE `library` ADD `showProspectorResultsAtEndOfSearch` TINYINT DEFAULT '1';",
				),
			),
			'library_16' => array(
				'title' => 'Library 16',
				'description' => 'Add overdriveAdvantage Information to library so we can determine who advantage title should belong to. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `overdriveAdvantageName` VARCHAR(128) DEFAULT '';",
					"ALTER TABLE `library` ADD `overdriveAdvantageProductsKey` VARCHAR(20) DEFAULT '';",
				),
			),
			'library_17' => array(
				'title' => 'Library 17',
				'description' => 'Add defaultNotNeededAfterDays and homePageWidgetId. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `defaultNotNeededAfterDays` INT DEFAULT '0';",
					"ALTER TABLE `library` ADD `homePageWidgetId` INT(11) DEFAULT '0';",
				),
			),
			'library_18' => array(
				'title' => 'Library 18',
				'description' => 'Add showCheckInGrid to determine how periodicals display. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `showCheckInGrid` INT DEFAULT '1';",
				),
			),
			'library_19' => array(
				'title' => 'Library 19',
				'description' => 'Add the ability to specify a list of records to blacklist. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `recordsToBlackList` MEDIUMTEXT;",
				),
			),
			'library_20' => array(
				'title' => 'Library 20',
				'description' => 'Add the show or hide marmot search results in scoped searches. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `showMarmotResultsAtEndOfSearch` INT(11) DEFAULT 1;",
				),
			),
			'library_21' => array(
				'title' => 'Library 21',
				'description' => 'Add the home link text so the breadcrumbs can be customized. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `homeLinkText` VARCHAR(50) DEFAULT 'Home';",
				),
			),
			'library_23' => array(
				'title' => 'Library 23',
				'description' => 'Add the ability to disable wikipedia and the Other format icon by library. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `showOtherFormatCategory` TINYINT(1) DEFAULT '1';",
					"ALTER TABLE `library` ADD `showWikipediaContent` TINYINT(1) DEFAULT '1';",
				),
			),
			'library_24' => array(
				'title' => 'Library 24',
				'description' => 'Add the ability to customize the link to pay fines. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `payFinesLink` VARCHAR(512) DEFAULT 'default';",
				),
			),
			'library_25' => array(
				'title' => 'Library 25',
				'description' => 'Add the ability to customize the link text to pay fines. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `payFinesLinkText` VARCHAR(512) DEFAULT 'Click to Pay Fines Online';",
				),
			),
			'library_26' => array(
				'title' => 'Library 26',
				'description' => 'Add a support e-mail address for eContent problems.',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `eContentSupportAddress` VARCHAR(256) DEFAULT 'askmarmot@marmot.org';",
				),
			),
			/*'library_27' => array(
				'title' => 'Library 27',
				'description' => 'Remove showOtherFormatCategory.',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` DROP `showOtherFormatCategory`;",
				),
			),*/
			'library_28' => array(
				'title' => 'Library 28',
				'description' => 'Add ilsCode.',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `ilsCode` VARCHAR(5) DEFAULT '';",
				),
			),
			'library_29' => array(
				'title' => 'Library 29',
				'description' => 'Add systemMessage.',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `systemMessage` VARCHAR(512) DEFAULT '';",
				),
			),
			'library_30' => array(
				'title' => 'Library 30',
				'description' => 'Add bettter controls for restricting what is searched',
				'sql' => array(
					"ALTER TABLE library ADD restrictSearchByLibrary TINYINT(1) DEFAULT '0'",
					"ALTER TABLE library ADD includeDigitalCollection TINYINT(1) DEFAULT '1'",
					"UPDATE library set restrictSearchByLibrary = 1 where defaultLibraryFacet <> ''"
				),
			),

			'library_31' => array(
				'title' => 'Library 31',
				'description' => 'Add includeOutOfSystemExternalLinks option to allow econtent links to be shown in global library search',
				'sql' => array(
					"ALTER TABLE library ADD includeOutOfSystemExternalLinks TINYINT(1) DEFAULT '0'",
				),
			),

			'library_32' => array(
				'title' => 'Library 32',
				'description' => 'Add restrictOwningBranchesAndSystems option to allow libraries to only show "their" systems and branches',
				'sql' => array(
					"ALTER TABLE library ADD restrictOwningBranchesAndSystems TINYINT(1) DEFAULT '1'",
				),
			),

			'library_33' => array(
				'title' => 'Library 33',
				'description' => 'Add additional configuration for Available At facet',
				'sql' => array(
					"ALTER TABLE library ADD showAvailableAtAnyLocation TINYINT(1) DEFAULT '1'",
				),
			),

			'library_34' => array(
				'title' => 'Library 34',
				'description' => 'Remove Facet File',
				'sql' => array(
					"ALTER TABLE library DROP COLUMN facetFile",
					"ALTER TABLE library DROP COLUMN defaultLibraryFacet",
				),
			),

			'library_35' => array(
				'title' => 'Library 35',
				'description' => 'Add Accounting Unit',
				'sql' => array(
					"ALTER TABLE library ADD accountingUnit INT(11) DEFAULT 10",
					"ALTER TABLE library ADD makeOrderRecordsAvailableToOtherLibraries TINYINT(1) DEFAULT 0",
				),
			),

			'library_css' => array(
				'title' => 'Library and Location CSS',
				'description' => 'Make changing the theme of common elements easier for libraries and locations',
				'sql' => array(
					"ALTER TABLE library ADD additionalCss MEDIUMTEXT",
					"ALTER TABLE location ADD additionalCss MEDIUMTEXT",
				),
			),

			'library_grouping' => array(
				'title' => 'Library Grouping Options',
				'description' => 'Whether or not records should shown as grouped in the user interface',
				'sql' => array(
					"ALTER TABLE library ADD searchGroupedRecords TINYINT DEFAULT 0",
				),
			),

			'library_materials_request_limits' => array(
				'title' => 'Library Materials Request Limits',
				'description' => 'Add configurable limits to the number of open requests and total requests per year that patrons can make. ',
				'dependencies' => array(),
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `library` ADD `maxRequestsPerYear` INT(11) DEFAULT 60;",
					"ALTER TABLE `library` ADD `maxOpenRequests` INT(11) DEFAULT 5;",
				),
			),

			'library_facets' => array(
				'title' => 'Library Facets',
				'description' => 'Create Library Facets table to allow library admins to customize their own facets. ',
				'continueOnError' => true,
				'sql' => array(
					"CREATE TABLE IF NOT EXISTS library_facet_setting (".
						"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
						"`libraryId` INT NOT NULL, " .
						"`displayName` VARCHAR(50) NOT NULL, " .
						"`facetName` VARCHAR(50) NOT NULL, " .
						"weight INT NOT NULL DEFAULT '0', " .
						"numEntriesToShowByDefault INT NOT NULL DEFAULT '5', " .
						"showAsDropDown TINYINT NOT NULL DEFAULT '0', " .
						"sortMode ENUM ('alphabetically', 'num_results') NOT NULL DEFAULT 'num_results', " .
						"showAboveResults TINYINT NOT NULL DEFAULT '0', " .
						"showInResults TINYINT NOT NULL DEFAULT '1', " .
						"showInAuthorResults TINYINT NOT NULL DEFAULT '1', " .
						"showInAdvancedSearch TINYINT NOT NULL DEFAULT '1' " .
					") ENGINE = MYISAM COMMENT = 'A widget that can be displayed within VuFind or within other sites' ",
					"ALTER TABLE `library_facet_setting` ADD UNIQUE `libraryFacet` (`libraryId`, `facetName`)",
				),
			),

			'library_facets_1' => array(
				'title' => 'Library Facets Update 1',
				'description' => 'Add index to library facets. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE library_facet_setting ADD INDEX (`libraryId`)",
				),
			),

			'library_facets_2' => array(
				'title' => 'Library Facets Update 2',
				'description' => 'Add collapsing of facets and more values popup. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE library_facet_setting ADD collapseByDefault TINYINT DEFAULT '0'",
					"ALTER TABLE library_facet_setting ADD useMoreFacetPopup TINYINT DEFAULT '1'",
				),
			),

			'location_facets' => array(
				'title' => 'Location Facets',
				'description' => 'Create Location Facets table to allow library admins to customize their own facets. ',
				'continueOnError' => true,
				'sql' => array(
					"CREATE TABLE IF NOT EXISTS location_facet_setting (".
						"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
						"`locationId` INT NOT NULL, " .
						"`displayName` VARCHAR(50) NOT NULL, " .
						"`facetName` VARCHAR(50) NOT NULL, " .
						"weight INT NOT NULL DEFAULT '0', " .
						"numEntriesToShowByDefault INT NOT NULL DEFAULT '5', " .
						"showAsDropDown TINYINT NOT NULL DEFAULT '0', " .
						"sortMode ENUM ('alphabetically', 'num_results') NOT NULL DEFAULT 'num_results', " .
						"showAboveResults TINYINT NOT NULL DEFAULT '0', " .
						"showInResults TINYINT NOT NULL DEFAULT '1', " .
						"showInAuthorResults TINYINT NOT NULL DEFAULT '1', " .
						"showInAdvancedSearch TINYINT NOT NULL DEFAULT '1', " .
						"INDEX (locationId) " .
					") ENGINE = MYISAM COMMENT = 'A widget that can be displayed within VuFind or within other sites' ",
					"ALTER TABLE `location_facet_setting` ADD UNIQUE `locationFacet` (`locationID`, `facetName`)",
				),
			),

			'location_facets_1' => array(
				'title' => 'Location Facets Update 1',
				'description' => 'Add collapsing of facets and more values popup. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE location_facet_setting ADD collapseByDefault TINYINT DEFAULT '0'",
					"ALTER TABLE location_facet_setting ADD useMoreFacetPopup TINYINT DEFAULT '1'",
				),
			),

			'location_1' => array(
				'title' => 'Location 1',
				'description' => 'Add fields orginally defined for Marmot',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `location` ADD `defaultPType` INT(11) NOT NULL DEFAULT '-1';",
					"ALTER TABLE `location` ADD `ptypesToAllowRenewals` VARCHAR(128) NOT NULL DEFAULT '*';"
				),
			),

			'location_2' => array(
				'title' => 'Location 2',
				'description' => 'Add the ability to customize footers per location',
				'sql' => array(
					"ALTER TABLE `location` ADD `footerTemplate` VARCHAR(40) NOT NULL DEFAULT 'default';",
				),
			),

			'location_3' => array(
				'title' => 'Location 3',
				'description' => 'Add the ability to set home page widget by location',
				'sql' => array(
					"ALTER TABLE `location` ADD `homePageWidgetId` INT(11) DEFAULT '0';",
				),
			),

			'location_4' => array(
				'title' => 'Location 4',
				'description' => 'Add the ability to specify a list of records to blacklist. ',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `location` ADD `recordsToBlackList` MEDIUMTEXT;",
				),
			),

			'location_5' => array(
				'title' => 'Location 5',
				'description' => 'Add ability to configure the automatic timeout length. ',
				'sql' => array(
					"ALTER TABLE `location` ADD `automaticTimeoutLength` INT(11) DEFAULT '90';",
				),
			),

			'location_6' => array(
				'title' => 'Location 6',
				'description' => 'Add ability to configure the automatic timeout length when logged out. ',
				'sql' => array(
					"ALTER TABLE `location` ADD `automaticTimeoutLengthLoggedOut` INT(11) DEFAULT '450';",
				),
			),

			'location_7' => array(
				'title' => 'Location 7',
				'description' => 'Add extraLocationCodesToInclude field for indexing of juvenile collections and other special collections, and add bettter controls for restricting what is searched',
				'sql' => array(
					"ALTER TABLE location ADD extraLocationCodesToInclude VARCHAR(255) DEFAULT ''",
					"ALTER TABLE location ADD restrictSearchByLocation TINYINT(1) DEFAULT '0'",
					"ALTER TABLE location ADD includeDigitalCollection TINYINT(1) DEFAULT '1'",
					"UPDATE location set restrictSearchByLocation = 1 where defaultLocationFacet <> ''"
				),
			),

			'location_8' => array(
				'title' => 'Location 8',
				'description' => 'Remove default location facet',
				'sql' => array(
					"ALTER TABLE location DROP defaultLocationFacet",
				),
			),

			'location_9' => array(
				'title' => 'Location 9',
				'description' => 'Allow suppressing all items from a location',
				'sql' => array(
					"ALTER TABLE location ADD suppressHoldings TINYINT(1) DEFAULT '0'",
				),
			),

			'location_address' => array(
				'title' => 'Location Address updates',
				'description' => 'Add fields related to address updates',
				'sql' => array(
					"ALTER TABLE location ADD address MEDIUMTEXT",
					"ALTER TABLE location ADD phone VARCHAR(15)  DEFAULT ''",
				),
			),

			'search_sources' => array(
				'title' => 'Search Sources',
				'description' => 'Setup Library and Location Search Source Table',
				'sql' => array(
					"CREATE TABLE library_search_source (
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						libraryId INT(11) NOT NULL DEFAULT -1,
						label VARCHAR(50) NOT NULL,
						weight INT NOT NULL DEFAULT 0,
						searchWhat ENUM('catalog', 'genealogy', 'overdrive', 'worldcat', 'prospector', 'goldrush', 'title_browse', 'author_browse', 'subject_browse', 'tags'),
						defaultFilter TEXT,
						defaultSort ENUM('relevance', 'popularity', 'newest_to_oldest', 'oldest_to_newest', 'author', 'title', 'user_rating'),
						INDEX (libraryId)
					)",
					"CREATE TABLE location_search_source (
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						locationId INT(11) NOT NULL DEFAULT -1,
						label VARCHAR(50) NOT NULL,
						weight INT NOT NULL DEFAULT 0,
						searchWhat ENUM('catalog', 'genealogy', 'overdrive', 'worldcat', 'prospector', 'goldrush', 'title_browse', 'author_browse', 'subject_browse', 'tags'),
						defaultFilter TEXT,
						defaultSort ENUM('relevance', 'popularity', 'newest_to_oldest', 'oldest_to_newest', 'author', 'title', 'user_rating'),
						INDEX (locationId)
					)"
				),
			),

			'search_sources_1' => array(
				'title' => 'Search Sources Update 1',
				'description' => 'Add scoping information to search scope',
				'sql' => array(
					"ALTER TABLE library_search_source ADD COLUMN catalogScoping ENUM('unscoped', 'library', 'location') DEFAULT 'unscoped'",
					"ALTER TABLE location_search_source ADD COLUMN catalogScoping ENUM('unscoped', 'library', 'location') DEFAULT 'unscoped'"
				),
			),

			'user_display_name' => array(
				'title' => 'User display name',
				'description' => 'Add displayName field to User table to allow users to have aliases',
				'sql' => array(
					"ALTER TABLE user ADD displayName VARCHAR( 30 ) NOT NULL DEFAULT ''",
				),
			),

		'user_phone' => array(
			'title' => 'User phone',
			'description' => 'Add phone field to User table to allow phone numbers to be displayed for Materials Requests',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD phone VARCHAR( 30 ) NOT NULL DEFAULT ''",
			),
		),

		'user_ilsType' => array(
			'title' => 'User Type',
			'description' => 'Add patronType field to User table to allow for functionality to be controlled based on the type of patron within the ils',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD patronType VARCHAR( 30 ) NOT NULL DEFAULT ''",
			),
		),

		'user_overdrive_email' => array(
			'title' => 'User OverDrive Email',
			'description' => 'Add overdriveEmail field to User table to allow for patrons to use a different email fo notifications when their books are ready',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD overdriveEmail VARCHAR( 250 ) NOT NULL DEFAULT ''",
				"ALTER TABLE user ADD promptForOverdriveEmail TINYINT DEFAULT 1",
				"UPDATE user set overdriveEmail = email"
			),
		),

		'user_preferred_library_interface' => array(
			'title' => 'User Preferred Library Interface',
			'description' => 'Add preferred library interface to ',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD preferredLibraryInterface INT(11) DEFAULT NULL",
			),
		),

		'list_widgets' => array(
			'title' => 'Setup Configurable List Widgets',
			'description' => 'Create list widgets tables',
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

		'list_widgets_update_1' => array(
			'title' => 'List Widget List Update 1',
			'description' => 'Add additional functionality to list widgets (auto rotate and single title view)',
			'sql' => array(
				"ALTER TABLE `list_widgets` ADD COLUMN `autoRotate` TINYINT NOT NULL DEFAULT '0'",
				"ALTER TABLE `list_widgets` ADD COLUMN `showMultipleTitles` TINYINT NOT NULL DEFAULT '1'",
			),
		),

		'list_widgets_update_2' => array(
			'title' => 'List Widget Update 2',
			'description' => 'Add library id to list widget',
			'sql' => array(
				"ALTER TABLE `list_widgets` ADD COLUMN `libraryId` INT(11) NOT NULL DEFAULT '-1'",
			),
		),

		'list_widgets_home' => array(
			'title' => 'List Widget Home',
			'description' => 'Create the default homepage widget',
			'sql' => array(
				"INSERT INTO list_widgets (name, description, showTitleDescriptions, onSelectCallback) VALUES ('home', 'Default example widget.', '1','')",
				"INSERT INTO list_widget_lists (listWidgetId, weight, source, name, displayFor) VALUES ('1', '1', 'highestRated', 'Highest Rated', 'all')",
				"INSERT INTO list_widget_lists (listWidgetId, weight, source, name, displayFor) VALUES ('1', '2', 'recentlyReviewed', 'Recently Reviewed', 'all')",
			),
		),

		'list_wdiget_list_update_1' => array(
			'title' => 'List Widget List Source Length Update',
			'description' => 'Update length of source field to accommodate search source type',
			'sql' => array(
				"ALTER TABLE `list_widget_lists` CHANGE `source` `source` VARCHAR( 500 ) NOT NULL "
			),
		),

		'index_search_stats' => array(
			'title' => 'Index search stats table',
			'description' => 'Add index to search stats table to improve autocomplete speed',
			'sql' => array(
				"ALTER TABLE `search_stats` ADD INDEX `search_index` ( `type` , `libraryId` , `locationId` , `phrase`, `numResults` )",
			),
		),
		'list_wdiget_update_1' => array(
			'title' => 'Update List Widget 1',
			'description' => 'Update List Widget to allow custom css files to be included and allow lists do be displayed in dropdown rather than tabs',
			'sql' => array(
				"ALTER TABLE `list_widgets` ADD COLUMN `customCss` VARCHAR( 500 ) NOT NULL ",
				"ALTER TABLE `list_widgets` ADD COLUMN `listDisplayType` ENUM('tabs', 'dropdown') NOT NULL DEFAULT 'tabs'"
			),
		),
			'list_widget_update_2' => array(
				'title' => 'Update List Widget 2',
				'description' => 'Update List Widget to add vertical widgets',
				'sql' => array(
					"ALTER TABLE `list_widgets` ADD COLUMN `style` ENUM('vertical', 'horizontal', 'single') NOT NULL DEFAULT 'horizontal'",
					"UPDATE `list_widgets` SET `style` = 'single' WHERE showMultipleTitles = 0",
				),
			),

			'list_widget_update_3' => array(
				'title' => 'List Widget Update 3',
				'description' => 'New functionality for widgets - ratings, cover size, new display option',
				'sql' => array(
					"ALTER TABLE `list_widgets` ADD COLUMN `coverSize` ENUM('small', 'medium') NOT NULL DEFAULT 'small'",
					"ALTER TABLE `list_widgets` ADD COLUMN `showRatings` TINYINT NOT NULL DEFAULT '0'",
					"ALTER TABLE `list_widgets` CHANGE `style` `style` ENUM('vertical', 'horizontal', 'single', 'single-with-next') NOT NULL DEFAULT 'horizontal'",
					"ALTER TABLE `list_widgets` ADD COLUMN `showTitle` TINYINT NOT NULL DEFAULT '1'",
					"ALTER TABLE `list_widgets` ADD COLUMN `showAuthor` TINYINT NOT NULL DEFAULT '1'",
				),
			),

			'library_4' => array(
				'title' => 'Library 4',
				'description' => 'Update Library table to include enableAlphaBrowse column',
				'sql' => array(
					"ALTER TABLE library ADD COLUMN enableAlphaBrowse TINYINT DEFAULT '1';",
				),
			),

			'genealogy' => array(
				'title' => 'Genealogy Setup',
				'description' => 'Initial setup of genealogy information',
				'continueOnError' => true,
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
					) ENGINE = MYISAM	COMMENT = 'Information about an obituary for a person';",
				),
			),

			'genealogy_1' => array(
				'title' => 'Genealogy Update 1',
				'description' => 'Update Genealogy 1 for Steamboat Springs to add cemetery information.',
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
				'sql' => array(
					"ALTER TABLE `user` ADD `disableRecommendations` TINYINT NOT NULL DEFAULT '0'",
						),
						),

			'editorial_review' => array(
				'title' => 'Create Editorial Review table',
				'description' => 'Create editorial review tables for external reviews, i.e. book-a-day blog',
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

			'editorial_review_1' => array(
				'title' => 'Add tabName to editorial reviews',
				'description' => 'Update editorial reviews to include a tab name',
				'sql' => array(
					"ALTER TABLE editorial_reviews add tabName VARCHAR(25) DEFAULT 'Reviews';",
				),
			),

			'editorial_review_2' => array(
				'title' => 'Add teaser to editorial reviews',
				'description' => 'Update editorial reviews to include a teaser',
				'sql' => array(
					"ALTER TABLE editorial_reviews add teaser VARCHAR(512);",
				),
			),

			'purchase_link_tracking' => array(
				'title' => 'Create Purchase Link Tracking Table',
				'description' => 'Create Purchase Links tables to track links that were clicked',
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
			/*'usage_tracking' => array(
				'title' => 'Create Usage Tracking Table',
				'description' => 'Create aggregate page view tracking table',
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
			),*/

			'resource_update_table' => array(
				'title' => 'Update resource table',
				'description' => 'Update resource tracking table to include additional information resources for sorting',
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
				'sql' => array(
					"ALTER TABLE `resource` ADD marc_checksum BIGINT",
					"ALTER TABLE `resource` ADD date_updated INT(11)",
				),
			),

			'resource_update4' => array(
				'title' => 'Update resource table 4',
				'description' => 'Update resource table to include a field for the actual marc record',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `resource` ADD marc BLOB",
				),
			),

			'resource_update5' => array(
				'title' => 'Update resource table 5',
				'description' => 'Add a short id column for use with certain ILS i.e. Millennium',
				'sql' => array(
					"ALTER TABLE `resource` ADD shortId VARCHAR(20)",
					"ALTER TABLE `resource` ADD INDEX (shortId)",
				),
			),

			'resource_update6' => array(
				'title' => 'Update resource table 6',
				'description' => 'Add a deleted column to determine if a resource has been removed from the catalog',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `resource` ADD deleted TINYINT DEFAULT '0'",
					"ALTER TABLE `resource` ADD INDEX (deleted)",
				),
			),

			'resource_update7' => array(
				'title' => 'Update resource table 7',
				'description' => 'Increase the size of the marc field to avoid indexing errors updating the resources table. ',
				'sql' => array(
					"ALTER TABLE `resource` CHANGE marc marc LONGBLOB",
				),
			),

			'resource_update8' => array(
				'title' => 'Update resource table 8',
				'description' => 'Updates resources to store marc records in text for easier debugging and UTF compatibility. ',
				'sql' => array(
					//"UPDATE resource set marc = null, marc_checksum = -1;",
					"ALTER TABLE `resource` CHANGE `marc` `marc` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;"
				),
			),

			/*'resource_update9' => array(
				'title' => 'Update resource 9',
				'description' => 'Updates resources to use MyISAM rather than INNODB for . ',
				'sql' => array(
					//"UPDATE resource set marc = null, marc_checksum = -1;",
					"ALTER TABLE resource_callnumber ENGINE = MYISAM",
					"ALTER TABLE resource_subject ENGINE = MYISAM",
					"ALTER TABLE `resource_callnumber` CHANGE `callnumber` `callnumber` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''",
				),
			),

			'resource_callnumber' => array(
				'title' => 'Resource call numbers',
				'description' => 'Build table to store call numbers for resources',
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
			),*/

			'resource_subject' => array(
				'title' => 'Resource subject',
				'description' => 'Build table to store subjects for resources',
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

			/*'resource_subject_1' => array(
				'title' => 'Resource subject update 1',
				'description' => 'Increase the length of the subject column',
				'sql' => array(
					'ALTER TABLE subject CHANGE subject subject VARCHAR(512) NOT NULL'
				),
			),*/

			'readingHistory' => array(
				'title' => 'Reading History Creation',
				'description' => 'Update reading History to include an id table',
				'sql' => array(
					"CREATE TABLE IF NOT EXISTS	user_reading_history(" .
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
				'sql' => array(
					"ALTER TABLE `user` ADD `disableCoverArt` TINYINT NOT NULL DEFAULT '0'",
				),
			),

			'externalLinkTracking' => array(
				'title' => 'Create External Link Tracking Table',
				'description' => 'Build table to track links to external sites from 856 tags or eContent',
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
				'sql' => array(
					'ALTER TABLE `user_reading_history` DROP PRIMARY KEY',
					'ALTER TABLE `user_reading_history` ADD UNIQUE `user_resource` ( `userId` , `resourceId` ) ',
					'ALTER TABLE `user_reading_history` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ',
				),
			),
			
			
			'notInterested' => array(
				'title' => 'Not Interested Table',
				'description' => 'Create a table for records the user is not interested in so they can be ommitted from search results' ,
				'sql' => array(
					"CREATE TABLE `user_not_interested` (
						id int(11) NOT NULL AUTO_INCREMENT,
						userId int(11) NOT NULL,
						resourceId varchar(20) NOT NULL,
						dateMarked int(11),
						PRIMARY KEY (id),
						UNIQUE INDEX (userId, resourceId),
						INDEX (userId)
					)",
				),
			),

			'userRatings1' => array(
				'title' => 'User Ratings Update 1',
				'description' => 'Add date rated for user ratings' ,
				'sql' => array(
					"ALTER TABLE user_rating ADD COLUMN dateRated int(11)",
				),
			),

			'materialsRequest' => array(
				'title' => 'Materials Request Table Creation',
				'description' => 'Update reading History to include an id table',
				'sql' => array(
					'CREATE TABLE IF NOT EXISTS materials_request (' .
					'id int(11) NOT NULL AUTO_INCREMENT, '.
					'title varchar(255), '.
					'author varchar(255), '.
					'format varchar(25), '.
					'ageLevel varchar(25), '.
					'isbn varchar(15), '.
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
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `emailSent` TINYINT NOT NULL DEFAULT 0',
					'ALTER TABLE `materials_request` ADD `holdsCreated` TINYINT NOT NULL DEFAULT 0',
				),
			),

			'materialsRequest_update2' => array(
				'title' => 'Materials Request Update 2',
				'description' => 'Material Request add fields phone and email so user can supply a different email address',
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `email` VARCHAR(80)',
					'ALTER TABLE `materials_request` ADD `phone` VARCHAR(15)',
				),
			),

			'materialsRequest_update3' => array(
				'title' => 'Materials Request Update 3',
				'description' => 'Material Request add fields season, magazineTitle, split isbn and upc',
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `season` VARCHAR(80)',
					'ALTER TABLE `materials_request` ADD `magazineTitle` VARCHAR(255)',
					//'ALTER TABLE `materials_request` CHANGE `isbn_upc` `isbn` VARCHAR( 15 )',
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
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `illItem` VARCHAR(80)',
				),
			),

			'materialsRequest_update5' => array(
				'title' => 'Materials Request Update 5',
				'description' => 'Material Request add magazine number',
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `magazineNumber` VARCHAR(80)',
				),
			),

			'materialsRequest_update6' => array(
				'title' => 'Materials Request Update 6',
				'description' => 'Updater Materials Requests to add indexes for improved performance',
				'sql' => array(
					'ALTER TABLE `materials_request` ADD INDEX(createdBy)',
					'ALTER TABLE `materials_request` ADD INDEX(dateUpdated)',
					'ALTER TABLE `materials_request` ADD INDEX(dateCreated)',
					'ALTER TABLE `materials_request` ADD INDEX(emailSent)',
					'ALTER TABLE `materials_request` ADD INDEX(holdsCreated)',
					'ALTER TABLE `materials_request` ADD INDEX(format)',
					'ALTER TABLE `materials_request` ADD INDEX(subFormat)',
				),
			),

			'materialsRequestStatus' => array(
				'title' => 'Materials Request Status Table Creation',
				'description' => 'Update reading History to include an id table',
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
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Already owned/On order', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.	Please check our online catalog periodically to put a hold for this item.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Item purchased', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - Adult', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - J/YA', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - AV', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('ILL Under Review', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Referred to ILL', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The library\\'s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.', 1)",
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

			'materialsRequestStatus_update1' => array(
				'title' => 'Materials Request Status Update 1',
				'description' => 'Material Request Status add library id',
				'sql' => array(
					"ALTER TABLE `materials_request_status` ADD `libraryId` INT(11) DEFAULT '-1'",
					'ALTER TABLE `materials_request_status` ADD INDEX (`libraryId`)',
				),
			),

			'catalogingRole' => array(
				'title' => 'Create cataloging role',
				'description' => 'Create cataloging role to handle materials requests, econtent loading, etc.',
				'sql' => array(
					"INSERT INTO `roles` (`name`, `description`) VALUES ('cataloging', 'Allows user to perform cataloging activities.')",
				),
			),

			'materialRequestsRole' => array(
				'title' => 'Create library material requests role',
				'description' => 'Create library materials request role to handle material requests for a specific library system.',
				'sql' => array(
					"INSERT INTO `roles` (`name`, `description`) VALUES ('library_material_requests', 'Allows user to manage material requests for a specific library.')",
				),
			),

			'libraryAdmin' => array(
				'title' => 'Create library admin role',
				'description' => 'Create library admin to allow .',
				'sql' => array(
					"INSERT INTO `roles` (`name`, `description`) VALUES ('libraryAdmin', 'Allows user to update library configuration for their library system only for their home location.')",
				),
			),

			'contentEditor' => array(
				'title' => 'Create Content Editor role',
				'description' => 'Create Content Editor Role to allow entering of editorial reviews and creation of widgets.',
				'sql' => array(
					"INSERT INTO `roles` (`name`, `description`) VALUES ('contentEditor', 'Allows entering of editorial reviews and creation of widgets.')",
				),
			),

			'ip_lookup_1' => array(
				'title' => 'IP Lookup Update 1',
				'description' => 'Add start and end ranges for IP Lookup table to improve performance.',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE ip_lookup ADD COLUMN startIpVal BIGINT",
					"ALTER TABLE ip_lookup ADD COLUMN endIpVal BIGINT",
					"ALTER TABLE `ip_lookup` ADD INDEX ( `startIpVal` )",
					"ALTER TABLE `ip_lookup` ADD INDEX ( `endIpVal` )",
					"createDefaultIpRanges"
				),
			),

			'ip_lookup_2' => array(
				'title' => 'IP Lookup Update 2',
				'description' => 'Change start and end ranges to be big integers.',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `ip_lookup` CHANGE `startIpVal` `startIpVal` BIGINT NULL DEFAULT NULL ",
					"ALTER TABLE `ip_lookup` CHANGE `endIpVal` `endIpVal` BIGINT NULL DEFAULT NULL ",
					"createDefaultIpRanges"
				),
			),

			/*'indexUsageTracking' => array(
				'title' => 'Index Usage Tracking',
				'description' => 'Update Usage Tracking to include index based on ip and tracking date',
				'continueOnError' => true,
				'sql' => array(
					"ALTER TABLE `usage_tracking` ADD INDEX `IP_DATE` ( `ipId` , `trackingDate` )",
				),
			),*/

			'merged_records' => array(
				'title' => 'Merged Records Table',
				'description' => 'Create Merged Records table to store ',
				'sql' => array(
					"CREATE TABLE `merged_records` (
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						`original_record` VARCHAR( 20 ) NOT NULL,
						`new_record` VARCHAR( 20 ) NOT NULL,
						UNIQUE INDEX (original_record),
						INDEX(new_record)
					)",
				),
			),

      'variables_table' => array(
				'title' => 'Variables Table',
				'description' => 'Create Variables Table for storing basic variables for use in programs (system writable config)',
				'sql' => array(
					"CREATE TABLE `variables` (
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						`name` VARCHAR( 128 ) NOT NULL,
						`value` VARCHAR( 255 ),
						INDEX(name)
					)",
				),
			),

			'utf8_update' => array(
			'title' => 'Update to UTF-8',
			'description' => 'Update database to use UTF-8 encoding',
			'continueOnError' => true,
			'sql' => array(
				"ALTER DATABASE " . $configArray['Database']['database_vufind_dbname'] . " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;",
				//"ALTER TABLE administrators CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
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
				//"ALTER TABLE nonHoldableLocations CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				//"ALTER TABLE ptype_restricted_locations CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE purchase_link_tracking CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE resource CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE resource_tags CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE roles CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE search CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE search_stats CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE session CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE spelling_words CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE tags CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				//"ALTER TABLE usage_tracking CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
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
			'sql' => array(
				//Update resource table indexes
				"ALTER TABLE `resource` ADD UNIQUE `records_by_source` (`record_id`, `source`)"
			),
		),

		/* This routine completely changed, removing alpha_browse_setup since alpha_browse_setup_1 complete redoes the tables */
		'alpha_browse_setup_2' => array(
			'title' => 'Setup Alphabetic Browse',
			'description' => 'Build tables to handle alphabetic browse functionality.',
			'sql' => array(
				"DROP TABLE IF EXISTS `title_browse`",
				"CREATE TABLE `title_browse` (
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`sortValue` VARCHAR( 255 ) NOT NULL COMMENT 'The value to sort by',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `sortValue` ),
				UNIQUE (`value`)
				) ENGINE = MYISAM;",
				/*"DROP TABLE IF EXISTS `title_browse_scoped_results`",
				"CREATE TABLE `title_browse_scoped_results`(
					`browseValueId` INT(11) NOT NULL,
					`scope` TINYINT NOT NULL,
					`scopeId` INT(11) NOT NULL,
					`record` VARCHAR( 50 ) NOT NULL,
				PRIMARY KEY ( `browseValueId`, `scope`, `scopeId`, `record` ),
				INDEX (`scopeId`)
				) ENGINE = MYISAM",*/

				"DROP TABLE IF EXISTS `author_browse`",
				"CREATE TABLE `author_browse` (
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`sortValue` VARCHAR( 255 ) NOT NULL COMMENT 'The value to sort by',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `sortValue` ),
				UNIQUE (`value`)
				) ENGINE = MYISAM;",
				/*"DROP TABLE IF EXISTS `author_browse_scoped_results`",
				"CREATE TABLE `author_browse_scoped_results`(
					`browseValueId` INT(11) NOT NULL,
					`scope` TINYINT NOT NULL,
					`scopeId` INT(11) NOT NULL,
					`record` VARCHAR( 50 ) NOT NULL,
				PRIMARY KEY ( `browseValueId`, `scope`, `scopeId`, `record` ),
				INDEX (`scopeId`)
				) ENGINE = MYISAM",*/

				"DROP TABLE IF EXISTS `callnumber_browse`",
				"CREATE TABLE `callnumber_browse` (
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`sortValue` VARCHAR( 255 ) NOT NULL COMMENT 'The value to sort by',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `sortValue` ),
				UNIQUE (`value`)
				) ENGINE = MYISAM;",
				/*"DROP TABLE IF EXISTS `callnumber_browse_scoped_results`",
				"CREATE TABLE `callnumber_browse_scoped_results`(
					`browseValueId` INT(11) NOT NULL,
					`scope` TINYINT NOT NULL,
					`scopeId` INT(11) NOT NULL,
					`record` VARCHAR( 50 ) NOT NULL,
				PRIMARY KEY ( `browseValueId`, `scope`, `scopeId`, `record` ),
				INDEX (`scopeId`)
				) ENGINE = MYISAM",*/

				"DROP TABLE IF EXISTS `subject_browse`",
				"CREATE TABLE `subject_browse` (
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`sortValue` VARCHAR( 255 ) NOT NULL COMMENT 'The value to sort by',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `sortValue` ),
				UNIQUE (`value`)
				) ENGINE = MYISAM;",
				/*"DROP TABLE IF EXISTS `subject_browse_scoped_results`",
				"CREATE TABLE `subject_browse_scoped_results`(
					`browseValueId` INT(11) NOT NULL,
					`scope` TINYINT NOT NULL,
					`scopeId` INT(11) NOT NULL,
					`record` VARCHAR( 50 ) NOT NULL,
				PRIMARY KEY ( `browseValueId`, `scope`, `scopeId`, `record` ),
				INDEX (`scopeId`)
				) ENGINE = MYISAM",*/
			),
		),

		'alpha_browse_setup_3' => array(
			'title' => 'Alphabetic Browse Performance',
			'description' => 'Create additional indexes and columns to improve performance of Alphabetic Browse.',
			'sql' => array(
				//Author browse
				//"ALTER TABLE `author_browse_scoped_results` ADD INDEX ( `browseValueId` )",
				//"ALTER TABLE `author_browse_scoped_results` ADD INDEX ( `scope` )",
				//"ALTER TABLE `author_browse_scoped_results` ADD INDEX ( `record` )",
				"ALTER TABLE `author_browse` ADD COLUMN `alphaRank` INT( 11 ) NOT NULL COMMENT 'A numerical ranking of the sort values from a-z'",
				"ALTER TABLE `author_browse` ADD INDEX ( `alphaRank` )",
				"set @r=0;",
				"UPDATE author_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;",

				//Call number browse
				//"ALTER TABLE `callnumber_browse_scoped_results` ADD INDEX ( `browseValueId` )",
				//"ALTER TABLE `callnumber_browse_scoped_results` ADD INDEX ( `scope` )",
				//"ALTER TABLE `callnumber_browse_scoped_results` ADD INDEX ( `record` )",
				"ALTER TABLE `callnumber_browse` ADD COLUMN `alphaRank` INT( 11 ) NOT NULL COMMENT 'A numerical ranking of the sort values from a-z'",
				"ALTER TABLE `callnumber_browse` ADD INDEX ( `alphaRank` )",
				"set @r=0;",
				"UPDATE callnumber_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;",

				//Subject Browse
				//"ALTER TABLE `subject_browse_scoped_results` ADD INDEX ( `browseValueId` )",
				//"ALTER TABLE `subject_browse_scoped_results` ADD INDEX ( `scope` )",
				//"ALTER TABLE `subject_browse_scoped_results` ADD INDEX ( `record` )",
				"ALTER TABLE `subject_browse` ADD COLUMN `alphaRank` INT( 11 ) NOT NULL COMMENT 'A numerical ranking of the sort values from a-z'",
				"ALTER TABLE `subject_browse` ADD INDEX ( `alphaRank` )",
				"set @r=0;",
				"UPDATE subject_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;",

				//Tile Browse
				//"ALTER TABLE `title_browse_scoped_results` ADD INDEX ( `browseValueId` )",
				//"ALTER TABLE `title_browse_scoped_results` ADD INDEX ( `scope` )",
				//"ALTER TABLE `title_browse_scoped_results` ADD INDEX ( `record` )",
				"ALTER TABLE `title_browse` ADD COLUMN `alphaRank` INT( 11 ) NOT NULL COMMENT 'A numerical ranking of the sort values from a-z'",
				"ALTER TABLE `title_browse` ADD INDEX ( `alphaRank` )",
				"set @r=0;",
				"UPDATE title_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;",
			),
		),

		'alpha_browse_setup_4' => array(
			'title' => 'Alphabetic Browse Metadata',
			'description' => 'Create metadata about alphabetic browsing improve performance of Alphabetic Browse.',
			'sql' => array(
				"CREATE TABLE author_browse_metadata (
					`scope` TINYINT( 4 ) NOT NULL ,
					`scopeId` INT( 11 ) NOT NULL ,
					`minAlphaRank` INT NOT NULL ,
					`maxAlphaRank` INT NOT NULL ,
					`numResults` INT NOT NULL
				) ENGINE = InnoDB;",
				//"INSERT INTO author_browse_metadata (SELECT scope, scopeId, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM author_browse inner join author_browse_scoped_results ON id = browseValueId GROUP BY scope, scopeId)",

				"CREATE TABLE callnumber_browse_metadata (
					`scope` TINYINT( 4 ) NOT NULL ,
					`scopeId` INT( 11 ) NOT NULL ,
					`minAlphaRank` INT NOT NULL ,
					`maxAlphaRank` INT NOT NULL ,
					`numResults` INT NOT NULL
				) ENGINE = InnoDB;",
				//"INSERT INTO callnumber_browse_metadata (SELECT scope, scopeId, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM callnumber_browse inner join callnumber_browse_scoped_results ON id = browseValueId GROUP BY scope, scopeId)",

				"CREATE TABLE title_browse_metadata (
					`scope` TINYINT( 4 ) NOT NULL ,
					`scopeId` INT( 11 ) NOT NULL ,
					`minAlphaRank` INT NOT NULL ,
					`maxAlphaRank` INT NOT NULL ,
					`numResults` INT NOT NULL
				) ENGINE = InnoDB;",
				//"INSERT INTO title_browse_metadata (SELECT scope, scopeId, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM title_browse inner join title_browse_scoped_results ON id = browseValueId GROUP BY scope, scopeId)",

				"CREATE TABLE subject_browse_metadata (
					`scope` TINYINT( 4 ) NOT NULL ,
					`scopeId` INT( 11 ) NOT NULL ,
					`minAlphaRank` INT NOT NULL ,
					`maxAlphaRank` INT NOT NULL ,
					`numResults` INT NOT NULL
				) ENGINE = InnoDB;",
				//"INSERT INTO subject_browse_metadata (SELECT scope, scopeId, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM subject_browse inner join subject_browse_scoped_results ON id = browseValueId GROUP BY scope, scopeId)",
			),
		),

		'alpha_browse_setup_5' => array(
			'title' => 'Alphabetic Browse scoped tables',
			'description' => 'Create Scoping tables for global and all libraries.',
			'continueOnError' => true,
			'sql' => array(
				//Add firstChar fields
				"ALTER TABLE `title_browse` ADD `firstChar` CHAR( 1 ) NOT NULL",
				"ALTER TABLE title_browse ADD INDEX ( `firstChar` )",
				'UPDATE title_browse set firstChar = SUBSTR(sortValue, 1, 1);',
				"ALTER TABLE `author_browse` ADD `firstChar` CHAR( 1 ) NOT NULL",
				"ALTER TABLE author_browse ADD INDEX ( `firstChar` )",
				'UPDATE author_browse set firstChar = SUBSTR(sortValue, 1, 1);',
				"ALTER TABLE `subject_browse` ADD `firstChar` CHAR( 1 ) NOT NULL",
				"ALTER TABLE subject_browse ADD INDEX ( `firstChar` )",
				'UPDATE subject_browse set firstChar = SUBSTR(sortValue, 1, 1);',
				"ALTER TABLE `callnumber_browse` ADD `firstChar` CHAR( 1 ) NOT NULL",
				"ALTER TABLE callnumber_browse ADD INDEX ( `firstChar` )",
				'UPDATE callnumber_browse set firstChar = SUBSTR(sortValue, 1, 1);',
				//Create global tables
				'CREATE TABLE `title_browse_scoped_results_global` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM',
				'CREATE TABLE `author_browse_scoped_results_global` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM',
				'CREATE TABLE `subject_browse_scoped_results_global` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM',
				'CREATE TABLE `callnumber_browse_scoped_results_global` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM',
				//Truncate old data
				"TRUNCATE TABLE `title_browse_scoped_results_global`",
				"TRUNCATE TABLE `author_browse_scoped_results_global`",
				"TRUNCATE TABLE `subject_browse_scoped_results_global`",
				"TRUNCATE TABLE `callnumber_browse_scoped_results_global`",
				//Load data from old method into tables
				/*'INSERT INTO title_browse_scoped_results_global (`browseValueId`, record)
					SELECT title_browse_scoped_results.browseValueId, title_browse_scoped_results.record
					FROM title_browse_scoped_results
					WHERE scope = 0;',
				'INSERT INTO author_browse_scoped_results_global (`browseValueId`, record)
					SELECT author_browse_scoped_results.browseValueId, author_browse_scoped_results.record
					FROM author_browse_scoped_results
					WHERE scope = 0;',
				'INSERT INTO subject_browse_scoped_results_global (`browseValueId`, record)
					SELECT subject_browse_scoped_results.browseValueId, subject_browse_scoped_results.record
					FROM subject_browse_scoped_results
					WHERE scope = 0;',
				'INSERT INTO callnumber_browse_scoped_results_global (`browseValueId`, record)
					SELECT callnumber_browse_scoped_results.browseValueId, callnumber_browse_scoped_results.record
					FROM callnumber_browse_scoped_results
					WHERE scope = 0;',*/
				'createScopingTables',
				/*'DROP TABLE title_browse_scoped_results',
				'DROP TABLE author_browse_scoped_results',
				'DROP TABLE subject_browse_scoped_results',
				'DROP TABLE callnumber_browse_scoped_results',*/

			),
		),

		'alpha_browse_setup_6' => array(
			'title' => 'Alphabetic Browse second letter',
			'description' => 'Add second char to the tables.',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE `title_browse` ADD `secondChar` CHAR( 1 ) NOT NULL",
				"ALTER TABLE title_browse ADD INDEX ( `secondChar` )",
				'UPDATE title_browse set secondChar = substr(sortValue, 2, 1);',
				"ALTER TABLE `author_browse` ADD `secondChar` CHAR( 1 ) NOT NULL",
				"ALTER TABLE author_browse ADD INDEX ( `secondChar` )",
				'UPDATE author_browse set secondChar = substr(sortValue, 2, 1);',
				"ALTER TABLE `subject_browse` ADD `secondChar` CHAR( 1 ) NOT NULL",
				"ALTER TABLE subject_browse ADD INDEX ( `secondChar` )",
				'UPDATE subject_browse set secondChar = substr(sortValue, 2, 1);',
				"ALTER TABLE `callnumber_browse` ADD `secondChar` CHAR( 1 ) NOT NULL",
				"ALTER TABLE callnumber_browse ADD INDEX ( `secondChar` )",
				'UPDATE callnumber_browse set secondChar = substr(sortValue, 2, 1);',
			),
		),

		'alpha_browse_setup_7' => array(
			'title' => 'Alphabetic Browse change scoping engine',
			'description' => 'Change DB Engine to INNODB for all scoping tables.',
			'continueOnError' => true,
			'sql' => array(
				"setScopingTableEngine",
			),
		),

		'alpha_browse_setup_8' => array(
			'title' => 'Alphabetic Browse change scoping engine',
			'description' => 'Change DB Engine to INNODB for all scoping tables.',
			'continueOnError' => true,
			'sql' => array(
				"setScopingTableEngine2",
			),
		),

		'alpha_browse_setup_9' => array(
			'title' => 'Alphabetic Browse remove record indices',
			'description' => 'Remove record indices since they are no longer needed and make the import slower, also use MyISAM engine since that is faster for import.',
			'continueOnError' => true,
			'sql' => array(
				"removeScopingTableIndex",
			),
		),

		'reindexLog' => array(
			'title' => 'Reindex Log table',
			'description' => 'Create Reindex Log table to track reindexing.',
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
					"`recordsProcessed` INT(11) NOT NULL COMMENT 'The number of records processed from marc files', "	.
					"`eContentRecordsProcessed` INT(11) NOT NULL COMMENT 'The number of econtent records processed from the database', "	.
					"`resourcesProcessed` INT(11) NOT NULL COMMENT 'The number of resources processed from the database', "	.
					"`numErrors` INT(11) NOT NULL COMMENT 'The number of errors that occurred during the process', "	.
					"`numAdded` INT(11) NOT NULL COMMENT 'The number of additions that occurred during the process', " .
					"`numUpdated` INT(11) NOT NULL COMMENT 'The number of items updated during the process', " .
					"`numDeleted` INT(11) NOT NULL COMMENT 'The number of items deleted during the process', " .
					"`numSkipped` INT(11) NOT NULL COMMENT 'The number of items skipped during the process', " .
					"`notes` TEXT COMMENT 'Additional information about the process', " .
					"PRIMARY KEY ( `id` ), INDEX ( `reindex_id` ), INDEX ( `processName` )" .
				") ENGINE = MYISAM;",

			),
		),

		'reindexLog_1' => array(
			'title' => 'Reindex Log table update 1',
			'description' => 'Update Reindex Log table to include notes and last update.',
			'sql' => array(
				"ALTER TABLE reindex_log ADD COLUMN `notes` TEXT COMMENT 'Notes related to the overall process'",
				"ALTER TABLE reindex_log ADD `lastUpdate` INT(11) COMMENT 'The last time the log was updated'",
			),
		),

		'reindexLog_2' => array(
			'title' => 'Reindex Log table update 2',
			'description' => 'Update Reindex Log table to include a count of non-marc records that have been processed.',
			'sql' => array(
				"ALTER TABLE reindex_process_log ADD COLUMN `overDriveNonMarcRecordsProcessed` INT(11) COMMENT 'The number of overdrive records processed that do not have a marc record associated with them.'",
			),
		),


		'cronLog' => array(
			'title' => 'Cron Log table',
			'description' => 'Create Cron Log table to track reindexing.',
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
					"`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the process started', "	.
					"`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the process last updated (to check for stuck processes)', " .
					"`endTime` INT(11) NULL COMMENT 'The timestamp when the process ended', "	.
					"`numErrors` INT(11) NOT NULL DEFAULT 0 COMMENT 'The number of errors that occurred during the process', "	.
					"`numUpdates` INT(11) NOT NULL DEFAULT 0 COMMENT 'The number of updates, additions, etc. that occurred', " .
					"`notes` TEXT COMMENT 'Additional information about the process', " .
					"PRIMARY KEY ( `id` ), INDEX ( `cronId` ), INDEX ( `processName` )" .
				") ENGINE = MYISAM;",

			),
		),

		'marcImport' => array(
			'title' => 'Marc Import table',
			'description' => 'Create a table to store information about marc records that are being imported.',
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
			'sql' => array(
				"ALTER TABLE marc_import CHANGE `checksum` `checksum` BIGINT NOT NULL COMMENT 'The checksum of the id as it currently exists in the active index.'",
			),
		),
		'marcImport_2' => array(
			'title' => 'Marc Import table Update 2',
			'description' => 'Increase the length of the checksum field for the marc import.',
			'sql' => array(
				"ALTER TABLE marc_import ADD COLUMN `backup_checksum` BIGINT COMMENT 'The checksum of the id in the backup index.'",
				"ALTER TABLE marc_import ADD COLUMN `eContent` TINYINT NOT NULL COMMENT 'Whether or not the record was detected as eContent in the active index.'",
				"ALTER TABLE marc_import ADD COLUMN `backup_eContent` TINYINT COMMENT 'Whether or not the record was detected as eContent in the backup index.'",
			),
		),
		'marcImport_3' => array(
			'title' => 'Marc Import table Update 3',
			'description' => 'Make backup fields optional.',
			'sql' => array(
				"ALTER TABLE marc_import CHANGE `backup_checksum` `backup_checksum` BIGINT COMMENT 'The checksum of the id in the backup index.'",
				"ALTER TABLE marc_import CHANGE `backup_eContent` `backup_eContent` TINYINT COMMENT 'Whether or not the record was detected as eContent in the backup index.'",
			),
		),
		'add_indexes' => array(
			'title' => 'Add indexes',
			'description' => 'Add indexes to tables that were not defined originally',
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
			'sql' => array(
				'ALTER TABLE `spelling_words` ADD `soundex` VARCHAR(20) ',
				'ALTER TABLE `spelling_words` ADD INDEX `Soundex` (`soundex`)',
				'UPDATE `spelling_words` SET soundex = SOUNDEX(word) '
			),
		),

		'boost_disabling' => array(
			'title' => 'Disabling Lib and Loc Boosting',
			'description' => 'Allow boosting of library and location boosting to be disabled',
			'sql' => array(
				"ALTER TABLE `library` ADD `boostByLibrary` TINYINT DEFAULT '1'",
				"ALTER TABLE `location` ADD `boostByLocation` TINYINT DEFAULT '1'",
			),
		),

		/*'cleanup_search' => array(
			'title' => 'Cleanup Search table',
			'description' => 'Cleanup Search table to remove unused tables and add needed indexes',
			'sql' => array(
				'ALTER TABLE search DROP folder_id',
				'ALTER TABLE search DROP title',
				'ALTER TABLE search ADD INDEX (`saved`)',
			),
		),*/


		/*'remove_old_tables' => array(
			'title' => 'Remove old tables',
			'description' => 'Remove tables that are no longer needed due to usage of memcache',
			'sql' => array(
				//Update resource table indexes
				'DROP TABLE IF EXISTS list_cache',
				'DROP TABLE IF EXISTS list_cache2',
				'DROP TABLE IF EXISTS novelist_cache',
				'DROP TABLE IF EXISTS reviews_cache',
				'DROP TABLE IF EXISTS sip2_item_cache',
			),
		),

		'remove_old_tables_2' => array(
			'title' => 'Remove old tables 2',
			'description' => 'Remove tables that are no longer needed due to changes in functionality',
			'sql' => array(
				'DROP TABLE IF EXISTS administrators',
				'DROP TABLE IF EXISTS administrators_to_roles',
				'DROP TABLE IF EXISTS resource_callnumber',
				'DROP TABLE IF EXISTS resource_subject',
			),
		),

		'remove_old_tables_3' => array(
			'title' => 'Remove usage tracking tables',
			'description' => 'Remove usage tracking tables (replaced with better analytics)',
			'sql' => array(
				'DROP TABLE IF EXISTS usagetracking',
				'DROP TABLE IF EXISTS usage_tracking',
			),
		),

		'remove_old_tables_4' => array(
			'title' => 'Remove subject tables',
			'description' => 'Remove subject table (replaced with browse tables)',
			'sql' => array(
				'DROP TABLE IF EXISTS subject',
			),
		),*/

		'rename_tables' => array(
			'title' => 'Rename tables',
			'description' => 'Rename tables for consistency and cross platform usage',
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
				'sql' => array('addTableListWidgetListsLinks'),
		),


		'millenniumTables' => array(
				'title' => 'Millennium table setup',
				'description' => 'Add new tables for millennium installations',
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
				) ENGINE=MYISAM",

				"CREATE TABLE IF NOT EXISTS `non_holdable_locations` (
					`locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
					`millenniumCode` varchar(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
					`holdingDisplay` varchar(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium',
					`availableAtCircDesk` tinyint(4) NOT NULL COMMENT 'The item is available if the patron visits the circulation desk.',
					PRIMARY KEY (`locationId`)
				) ENGINE=MYISAM"
			),
		),

		'loan_rule_determiners_1' => array(
			'title' => 'Loan Rule Determiners',
			'description' => 'Build tables to store loan rule determiners',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS loan_rules (" .
					"`id` INT NOT NULL AUTO_INCREMENT, " .
					"`loanRuleId` INT NOT NULL COMMENT 'The location id', " .
					"`name` varchar(50) NOT NULL COMMENT 'The location code the rule applies to', " .
					"`code` char(1) NOT NULL COMMENT '', ".
					"`normalLoanPeriod` INT(4) NOT NULL COMMENT 'Number of days the item checks out for', " .
					"`holdable` TINYINT NOT NULL DEFAULT '0', ".
					"`bookable` TINYINT NOT NULL DEFAULT '0', ".
					"`homePickup` TINYINT NOT NULL DEFAULT '0', ".
					"`shippable` TINYINT NOT NULL DEFAULT '0', ".
					"PRIMARY KEY ( `id` ), " .
					"INDEX ( `loanRuleId` ), " .
					"INDEX (`holdable`) " .
				") ENGINE=InnoDB",
				"CREATE TABLE IF NOT EXISTS loan_rule_determiners (" .
					"`id` INT NOT NULL AUTO_INCREMENT, " .
					"`rowNumber` INT NOT NULL COMMENT 'The row of the determiner.  Rules are processed in reverse order', " .
					"`location` varchar(10) NOT NULL COMMENT '', " .
					"`patronType` VARCHAR(50) NOT NULL COMMENT 'The patron types that this rule applies to', " .
					"`itemType` VARCHAR(255) NOT NULL DEFAULT '0' COMMENT 'The item types that this rule applies to', ".
					"`ageRange` varchar(10) NOT NULL COMMENT '', " .
					"`loanRuleId` varchar(10) NOT NULL COMMENT 'Close hour (24hr format) HH:MM', ".
					"`active` TINYINT NOT NULL DEFAULT '0', ".
					"PRIMARY KEY ( `id` ), " .
					"INDEX ( `rowNumber` ), " .
					"INDEX (`active`) " .
				") ENGINE=InnoDB",
			),
		),

		/*'remove_old_millennium_hold_logic' => array(
			'title' => 'Remove Old Millennium Hold Logic',
			'description' => 'Build tables to store loan rule determiners',
			'sql' => array(
				"DROP TABLE ptype_restricted_locations",
				"DROP TABLE non_holdable_locations",
			),
		),*/

		'location_hours' => array(
			'title' => 'Location Hours',
			'description' => 'Build table to store hours for a location',
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
			'description' => 'Build table to store holidays',
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

		'holiday_1' => array(
			'title' => 'Holidays 1',
			'description' => 'Update indexes for holidays',
			'sql' => array(
				"ALTER TABLE holiday DROP INDEX `date`",
				"ALTER TABLE holiday ADD INDEX Date (`date`) ",
				"ALTER TABLE holiday ADD INDEX Library (`libraryId`) ",
				"ALTER TABLE holiday ADD UNIQUE KEY LibraryDate(`date`, `libraryId`) ",
			),
		),
		'book_store' => array(
			'title' => 'Book store table',
			'description' => 'Create a table to store information about book stores.',
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
		'book_store_1' => array(
			'title' => 'Book store table update 1',
			'description' => 'Add a default column to determine if a book store should be used if a library does not override.',
			'sql' => array(
				"ALTER TABLE book_store ADD COLUMN `showByDefault` TINYINT NOT NULL DEFAULT 1 COMMENT 'Whether or not the book store should be used by default for al library systems.'",
				"ALTER TABLE book_store CHANGE `image` `image` VARCHAR(256) NULL COMMENT 'The URL to the icon/image to display'",
			),
		),
		'nearby_book_store' => array(
			'title' => 'Nearby book stores',
			'description' => 'Create a table to store book stores near a location.',
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

		'ptype' => array(
			'title' => 'P-Type',
			'description' => 'Build tables to store information related to P-Types.',
			'sql' => array(
				'CREATE TABLE IF NOT EXISTS ptype(
					id INT(11) NOT NULL AUTO_INCREMENT,
					pType INT(11) NOT NULL,
					maxHolds INT(11) NOT NULL DEFAULT 300,
					UNIQUE KEY (pType),
					PRIMARY KEY (id)
				)',
			),
		),

		'analytics' => array(
			'title' => 'Analytics',
			'description' => 'Build tables to store analytics information.',
			'continueOnError' => true,
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS analytics_session(" .
					"`id` INT(11) NOT NULL AUTO_INCREMENT, " .
					"`session_id` VARCHAR(128), " .
					"`sessionStartTime` INT(11) NOT NULL, " .
					"`lastRequestTime` INT(11) NOT NULL, " .
					"`country` VARCHAR(128) , " .
					"`city` VARCHAR(128), " .
					"`state` VARCHAR(128), " .
					"`latitude` FLOAT, " .
					"`longitude` FLOAT, " .
					"`ip` CHAR(16), " .
					"`theme` VARCHAR(128), " .
					"`mobile` TINYINT, " .
					"`device` VARCHAR(128), " .
					"`physicalLocation` VARCHAR(128), " .
					"`patronType` VARCHAR(50) NOT NULL DEFAULT 'logged out', " .
					"`homeLocationId` INT(11), " .
					"UNIQUE KEY ( `session_id` ), " .
					"PRIMARY KEY ( `id` )" .
				") ENGINE = InnoDB",
				"CREATE TABLE IF NOT EXISTS analytics_page_view(" .
					"`id` INT(11) NOT NULL AUTO_INCREMENT, " .
					"`sessionId` INT(11), " .
					"`pageStartTime` INT(11), " .
					"`pageEndTime` INT(11), "  .
					"`module` VARCHAR(128), " .
					"`action` VARCHAR(128), " .
					"`method` VARCHAR(128), " .
					"`objectId` VARCHAR(128), " .
					"`fullUrl` VARCHAR(1024), " .
					"`language` VARCHAR(128), " .
					"INDEX ( `sessionId` ), " .
					"PRIMARY KEY ( `id` )" .
				") ENGINE = InnoDB",
				"CREATE TABLE IF NOT EXISTS analytics_search(" .
					"`id` INT(11) NOT NULL AUTO_INCREMENT, " .
					"`sessionId` INT(11), " .
					"`searchType` VARCHAR(30), " .
					"`scope` VARCHAR(50), "  .
					"`lookfor` VARCHAR(256), " .
					"`isAdvanced` TINYINT, " .
					"`facetsApplied` TINYINT, " .
					"`numResults` INT(11), " .
					"INDEX ( `sessionId` ), " .
					"PRIMARY KEY ( `id` )" .
				") ENGINE = InnoDB",
				"CREATE TABLE IF NOT EXISTS analytics_event(" .
					"`id` INT(11) NOT NULL AUTO_INCREMENT, " .
					"`sessionId` INT(11), " .
					"`category` VARCHAR(100), " .
					"`action` VARCHAR(100), "  .
					"`data` VARCHAR(256), " .
					"INDEX ( `sessionId` ), " .
					"INDEX ( `category` ), " .
					"INDEX ( `action` ), " .
					"PRIMARY KEY ( `id` )" .
				") ENGINE = InnoDB",
			),
		),

		'analytics_1' => array(
			'title' => 'Analytics Update 1',
			'description' => 'Add times to searches and events.',
			'continueOnError' => true,
			'sql' => array(
				'ALTER TABLE analytics_event ADD COLUMN eventTime INT(11)',
				'ALTER TABLE analytics_search ADD COLUMN searchTime INT(11)'
			),
		),

		'analytics_2' => array(
			'title' => 'Analytics Update 2',
			'description' => 'Adjust length of searchType Field.',
			'sql' => array(
				'ALTER TABLE analytics_search CHANGE COLUMN searchType searchType VARCHAR(50)'
			),
		),

		'analytics_3' => array(
			'title' => 'Analytics Update 3',
			'description' => 'Index filter information to improve loading seed for reports.',
			'sql' => array(
				'ALTER TABLE `analytics_session` ADD INDEX ( `country`)',
				'ALTER TABLE `analytics_session` ADD INDEX ( `city`)',
				'ALTER TABLE `analytics_session` ADD INDEX ( `state`)',
				'ALTER TABLE `analytics_session` ADD INDEX ( `theme`)',
				'ALTER TABLE `analytics_session` ADD INDEX ( `mobile`)',
				'ALTER TABLE `analytics_session` ADD INDEX ( `device`)',
				'ALTER TABLE `analytics_session` ADD INDEX ( `physicalLocation`)',
				'ALTER TABLE `analytics_session` ADD INDEX ( `patronType`)',
				'ALTER TABLE `analytics_session` ADD INDEX ( `homeLocationId`)',
			),
		),

		'analytics_4' => array(
			'title' => 'Analytics Update 4',
			'description' => 'Add additional data fields for events.',
			'sql' => array(
				'ALTER TABLE `analytics_event` ADD COLUMN data2 VARCHAR(256)',
				'ALTER TABLE `analytics_event` ADD COLUMN data3 VARCHAR(256)',
				'ALTER TABLE `analytics_event` ADD INDEX ( `data`)',
				'ALTER TABLE `analytics_event` ADD INDEX ( `data2`)',
				'ALTER TABLE `analytics_event` ADD INDEX ( `data3`)',
			),
		),

		'analytics_5' => array(
			'title' => 'Analytics Update 5',
			'description' => 'Update analytics search to make display of reports faster.',
			'sql' => array(
				'ALTER TABLE analytics_search ADD INDEX(lookfor)',
				'ALTER TABLE analytics_search ADD INDEX(numResults)',
				'ALTER TABLE analytics_search ADD INDEX(searchType)',
				'ALTER TABLE analytics_search ADD INDEX(scope)',
				'ALTER TABLE analytics_search ADD INDEX(facetsApplied)',
				'ALTER TABLE analytics_search ADD INDEX(isAdvanced)',
			),
		),

		'analytics_6' => array(
			'title' => 'Analytics Update 6',
			'description' => 'Update analytics make display of dashboard and other reports faster.',
			'sql' => array(
				'ALTER TABLE analytics_event ADD INDEX(eventTime)',
				'ALTER TABLE analytics_page_view ADD INDEX(pageStartTime)',
				'ALTER TABLE analytics_page_view ADD INDEX(pageEndTime)',
				'ALTER TABLE analytics_page_view ADD INDEX(module)',
				'ALTER TABLE analytics_page_view ADD INDEX(action)',
				'ALTER TABLE analytics_page_view ADD INDEX(method)',
				'ALTER TABLE analytics_page_view ADD INDEX(objectId)',
				'ALTER TABLE analytics_page_view ADD INDEX(language)',
				'ALTER TABLE analytics_search ADD INDEX(searchTime)',
			),
		),

		'analytics_7' => array(
			'title' => 'Analytics Update 7',
			'description' => 'Normalize Analytics Session for better performance.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS analytics_country (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`value` VARCHAR(128),
					UNIQUE KEY (`value`),
					PRIMARY KEY ( `id` )
				) ENGINE = MYISAM",
				"CREATE TABLE IF NOT EXISTS analytics_city (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`value` VARCHAR(128),
					UNIQUE KEY (`value`),
					PRIMARY KEY ( `id` )
				) ENGINE = MYISAM",
				"CREATE TABLE IF NOT EXISTS analytics_state (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`value` VARCHAR(128),
					UNIQUE KEY (`value`),
					PRIMARY KEY ( `id` )
				) ENGINE = MYISAM",
				"CREATE TABLE IF NOT EXISTS analytics_theme (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`value` VARCHAR(128),
					UNIQUE KEY (`value`),
					PRIMARY KEY ( `id` )
				) ENGINE = MYISAM",
				"CREATE TABLE IF NOT EXISTS analytics_device (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`value` VARCHAR(128),
					UNIQUE KEY (`value`),
					PRIMARY KEY ( `id` )
				) ENGINE = MYISAM",
				"CREATE TABLE IF NOT EXISTS analytics_physical_location (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`value` VARCHAR(128),
					UNIQUE KEY (`value`),
					PRIMARY KEY ( `id` )
				) ENGINE = MYISAM",
				"CREATE TABLE IF NOT EXISTS analytics_patron_type (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`value` VARCHAR(128),
					UNIQUE KEY (`value`),
					PRIMARY KEY ( `id` )
				) ENGINE = MYISAM",
				"CREATE TABLE IF NOT EXISTS analytics_session_2(
						`id` INT(11) NOT NULL AUTO_INCREMENT,
						`session_id` VARCHAR(128),
						`sessionStartTime` INT(11) NOT NULL,
						`lastRequestTime` INT(11) NOT NULL,
						`countryId` INT(11) ,
						`cityId` INT(11),
						`stateId` INT(11),
						`latitude` FLOAT,
						`longitude` FLOAT,
						`ip` CHAR(16),
						`themeId` INT(11),
						`mobile` TINYINT,
						`deviceId` INT(11),
						`physicalLocationId` INT(11),
						`patronTypeId` INT(11),
						`homeLocationId` INT(11),
						UNIQUE KEY ( `session_id` ),
						INDEX (sessionStartTime),
						INDEX (lastRequestTime),
						INDEX (countryId),
						INDEX (cityId),
						INDEX (stateId),
						INDEX (latitude),
						INDEX (longitude),
						INDEX (ip),
						INDEX (themeId),
						INDEX (mobile),
						INDEX (deviceId),
						INDEX (physicalLocationId),
						INDEX (patronTypeId),
						INDEX (homeLocationId),
						PRIMARY KEY ( `id` )
				) ENGINE = InnoDB",
				'TRUNCATE TABLE analytics_country',
				'INSERT INTO analytics_country (value) SELECT DISTINCT country from analytics_session',
				'TRUNCATE TABLE analytics_city',
				'INSERT INTO analytics_city (value) SELECT DISTINCT city from analytics_session',
				'TRUNCATE TABLE analytics_state',
				'INSERT INTO analytics_state (value) SELECT DISTINCT state from analytics_session',
				'TRUNCATE TABLE analytics_theme',
				'INSERT INTO analytics_theme (value) SELECT DISTINCT theme from analytics_session',
				'TRUNCATE TABLE analytics_device',
				'INSERT INTO analytics_device (value) SELECT DISTINCT device from analytics_session',
				'TRUNCATE TABLE analytics_physical_location',
				'INSERT INTO analytics_physical_location (value) SELECT DISTINCT physicalLocation from analytics_session',
				'TRUNCATE TABLE analytics_patron_type',
				'INSERT INTO analytics_patron_type (value) SELECT DISTINCT patronType from analytics_session',
				'TRUNCATE TABLE analytics_session_2',
				"INSERT INTO analytics_session_2 (
						session_id, sessionStartTime, lastRequestTime, countryId, cityId, stateId, latitude, longitude, ip, themeId, mobile, deviceId, physicalLocationId, patronTypeId, homeLocationId
					)
					SELECT session_id, sessionStartTime, lastRequestTime, analytics_country.id, analytics_city.id, analytics_state.id, latitude, longitude, ip, analytics_theme.id, mobile, analytics_device.id, analytics_physical_location.id, analytics_patron_type.id, homeLocationId
					FROM analytics_session
					left join analytics_country on analytics_session.country = analytics_country.value
					left join analytics_city on analytics_session.city = analytics_city.value
					left join analytics_state on analytics_session.state = analytics_state.value
					left join analytics_theme on analytics_session.theme = analytics_theme.value
					left join analytics_device on analytics_session.device = analytics_device.value
					left join analytics_physical_location on analytics_session.physicalLocation= analytics_physical_location.value
					left join analytics_patron_type on analytics_session.patronType= analytics_patron_type.value",
				'RENAME TABLE analytics_session TO analytics_session_old',
				'RENAME TABLE analytics_session_2 TO analytics_session',
			),
		),

			'analytics_8' => array(
				'title' => 'Analytics Update 8',
				'description' => "Update analytics to store page load time so it doesn't have to be calculated.",
				'sql' => array(
					'ALTER TABLE analytics_page_view ADD COLUMN loadTime int',
					'ALTER TABLE analytics_page_view ADD INDEX(loadTime)',
					'UPDATE analytics_page_view set loadTime = pageEndTime - pageStartTime'
				),
			),

			'session_update_1' => array(
				'title' => 'Session Update 1',
				'description' => 'Add a field for whether or not the session was started with remember me on.',
				'sql' => array(
					"ALTER TABLE session ADD COLUMN `remember_me` TINYINT NOT NULL DEFAULT 0 COMMENT 'Whether or not the session was started with remember me on.'",
				),
			),

			'offline_holds' => array(
				'title' => 'Offline Holds',
				'description' => 'Stores information about holds that have been placed while the circulation system is offline',
				'sql' => array(
					"CREATE TABLE offline_hold (
						`id` INT(11) NOT NULL AUTO_INCREMENT,
						`timeEntered` INT(11) NOT NULL,
						`timeProcessed` INT(11) NULL,
						`bibId` VARCHAR(10) NOT NULL,
						`patronId` INT(11) NOT NULL,
						`patronBarcode` VARCHAR(20),
						`status` ENUM('Not Processed', 'Hold Succeeded', 'Hold Failed'),
						`notes` VARCHAR(512),
						INDEX(`timeEntered`),
						INDEX(`timeProcessed`),
						INDEX(`patronBarcode`),
						INDEX(`patronId`),
						INDEX(`bibId`),
						INDEX(`status`),
						PRIMARY KEY(`id`)
					) ENGINE = MYISAM"
				)
			),

			'offline_holds_update_1' => array(
				'title' => 'Offline Holds Update 1',
				'description' => 'Add the ability to store a name for patrons that have not logged in before.  Also used for conversions',
				'sql' => array(
					"ALTER TABLE `offline_hold` CHANGE `patronId` `patronId` INT( 11 ) NULL",
					"ALTER TABLE `offline_hold` ADD COLUMN `patronName` VARCHAR( 200 ) NULL",
				)
			),


			'offline_circulation' => array(
				'title' => 'Offline Circulation',
				'description' => 'Stores information about circulation activities done while the circulation system was offline',
				'sql' => array(
					"CREATE TABLE offline_circulation (
						`id` INT(11) NOT NULL AUTO_INCREMENT,
						`timeEntered` INT(11) NOT NULL,
						`timeProcessed` INT(11) NULL,
						`itemBarcode` VARCHAR(20) NOT NULL,
						`patronBarcode` VARCHAR(20),
						`patronId` INT(11) NULL,
						`login` VARCHAR(50),
						`loginPassword` VARCHAR(50),
						`initials` VARCHAR(50),
						`initialsPassword` VARCHAR(50),
						`type` ENUM('Check In', 'Check Out'),
						`status` ENUM('Not Processed', 'Processing Succeeded', 'Processing Failed'),
						`notes` VARCHAR(512),
						INDEX(`timeEntered`),
						INDEX(`patronBarcode`),
						INDEX(`patronId`),
						INDEX(`itemBarcode`),
						INDEX(`login`),
						INDEX(`initials`),
						INDEX(`type`),
						INDEX(`status`),
						PRIMARY KEY(`id`)
					) ENGINE = MYISAM"
				)
			),

			'novelist_data' => array(
				'title' => 'Novelist Data',
				'description' => 'Stores basic information from Novelist for efficiency purposes.  We can\'t cache everything due to contract.',
				'sql' => array(
					"CREATE table novelist_data (
						id INT(11) NOT NULL AUTO_INCREMENT,
						groupedRecordPermanentId VARCHAR(36),
						lastUpdate INT(11),
						hasNovelistData TINYINT(1),
						groupedRecordHasISBN TINYINT(1),
						primaryISBN VARCHAR(13),
						seriesTitle VARCHAR(255),
						seriesNote VARCHAR(255),
						volume VARCHAR(32),
						INDEX(`groupedRecordPermanentId`),
						PRIMARY KEY(`id`)
					) ENGINE = MYISAM",
				),
			),

			'syndetics_data' => array(
				'title' => 'Syndetics Data',
				'description' => 'Stores basic information from Syndetics for efficiency purposes.',
				'sql' => array(
					"CREATE table syndetics_data (
						id INT(11) NOT NULL AUTO_INCREMENT,
						groupedRecordPermanentId VARCHAR(36),
						lastUpdate INT(11),
						hasSyndeticsData TINYINT(1),
						primaryIsbn VARCHAR(13),
						primaryUpc VARCHAR(25),
						description MEDIUMTEXT,
						tableOfContents MEDIUMTEXT,
						excerpt MEDIUMTEXT,
						INDEX(`groupedRecordPermanentId`),
						PRIMARY KEY(`id`)
					) ENGINE = MYISAM",
				),
			),


			'grouped_works' => array(
				'title' => 'Setup Grouped Works',
				'description' =>'Sets up tables for grouped works so we can index and display them.',
				'sql' => array(
					"CREATE TABLE IF NOT EXISTS grouped_work (
					  id bigint(20) NOT NULL AUTO_INCREMENT,
					  permanent_id char(36) NOT NULL,
					  title varchar(100) NOT NULL,
					  author varchar(50) NOT NULL,
					  subtitle varchar(175) NOT NULL,
					  grouping_category varchar(25) NOT NULL,
					  PRIMARY KEY (id),
					  UNIQUE KEY permanent_id (permanent_id),
					  KEY title (title,author,grouping_category)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
					"CREATE TABLE IF NOT EXISTS grouped_work_identifiers (
					  id bigint(20) NOT NULL AUTO_INCREMENT,
					  grouped_work_id bigint(20) NOT NULL,
					  `type` varchar(15) NOT NULL,
					  identifier varchar(36) NOT NULL,
					  linksToDifferentTitles tinyint(4) NOT NULL DEFAULT '0',
					  PRIMARY KEY (id),
					  KEY `type` (`type`,identifier),
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
				),

			),

			'grouped_works_1' => array(
				'title' => 'Grouped Work update 1',
				'description' =>'Updates grouped works to normalize identifiers and add a reference table to link to .',
				'sql' => array(
					"CREATE TABLE IF NOT EXISTS grouped_work_identifiers_ref (
					  grouped_work_id bigint(20) NOT NULL,
					  identifier_id bigint(20) NOT NULL,
					  PRIMARY KEY (grouped_work_id, identifier_id)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
					"TRUNCATE TABLE grouped_work_identifiers",
					"ALTER TABLE `grouped_work_identifiers` CHANGE `type` `type` ENUM( 'asin', 'ils', 'isbn', 'issn', 'oclc', 'upc', 'order', 'external_econtent', 'acs', 'free', 'overdrive' )",
					"ALTER TABLE grouped_work_identifiers DROP COLUMN grouped_work_id",
					"ALTER TABLE grouped_work_identifiers DROP COLUMN linksToDifferentTitles",
					"ALTER TABLE grouped_work_identifiers ADD UNIQUE (`type`, `identifier`)",
				),
			),

			'grouped_works_2' => array(
				'title' => 'Grouped Work update 2',
				'description' =>'Updates grouped works to add a full title field.',
				'sql' => array(
					"ALTER TABLE `grouped_work` ADD `full_title` VARCHAR( 276 ) NOT NULL",
					"ALTER TABLE `grouped_work` ADD INDEX(`full_title`)",
				),
			),

			'grouped_works_primary_identifiers' => array(
				'title' => 'Grouped Work Primary Identifiers',
				'description' =>'Add primary identifiers table for works.',
				'sql' => array(
					"CREATE TABLE IF NOT EXISTS grouped_work_primary_identifiers (
					  id bigint(20) NOT NULL AUTO_INCREMENT,
					  grouped_work_id bigint(20) NOT NULL,
					  `type` ENUM('ils', 'external_econtent', 'acs', 'free', 'overdrive' ) NOT NULL,
					  identifier varchar(36) NOT NULL,
					  PRIMARY KEY (id),
					  UNIQUE KEY (`type`,identifier),
					  KEY grouped_record_id (grouped_work_id)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
				),
			),

			'grouped_works_primary_identifiers_1' => array(
				'title' => 'Grouped Work Primary Identifiers Update 1',
				'description' =>'Add additional types of identifiers.',
				'sql' => array(
					"ALTER TABLE grouped_work_primary_identifiers CHANGE `type` `type` ENUM('ils', 'external_econtent', 'drm', 'free', 'overdrive' ) NOT NULL",
				),
			),

			'ils_marc_checksums' => array(
				'title' => 'ILS MARC Checksums',
				'description' =>'Add a table to store checksums of MARC records stored in the ILS so we can determine if the record needs to be updated during grouping.',
				'sql' => array(
					"CREATE TABLE IF NOT EXISTS ils_marc_checksums (
						id INT(11) NOT NULL AUTO_INCREMENT,
					  ilsId varchar(20) NOT NULL,
					  checksum bigint(20) UNSIGNED NOT NULL,
					  PRIMARY KEY (id),
					  UNIQUE (ilsId)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
				),
			),

			'work_level_ratings' => array(
				'title' => 'Work Level Ratings',
				'description' => 'Stores user ratings at the work level rather than the individual record.',
				'sql' => array(
					"CREATE table user_work_review (
						id INT(11) NOT NULL AUTO_INCREMENT,
						groupedRecordPermanentId VARCHAR(36),
						userId INT(11),
						rating TINYINT(1),
						review MEDIUMTEXT,
						dateRated INT(11),
						INDEX(`groupedRecordPermanentId`),
						INDEX(`userId`),
						PRIMARY KEY(`id`)
					) ENGINE = MYISAM",
				),
			),

			'populate_work_level_ratings' => array(
				'title' => 'Populate Work Level Ratings',
				'description' => 'Converts from old record level ratings to the new ratings based on work level',
				'sql' => array(
					"populateWorkLevelRatings"
				),
			),

			'browse_categories' => array(
				'title' => 'Browse Categories',
				'description' => 'Setup Browse Category Table',
				'sql' => array(
					"CREATE TABLE browse_category (
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						textId VARCHAR(60) NOT NULL DEFAULT -1,
						userId INT(11),
						sharing ENUM('private', 'location', 'library', 'everyone') DEFAULT 'everyone',
						label VARCHAR(50) NOT NULL,
						description MEDIUMTEXT,
						catalogScoping ENUM('unscoped', 'library', 'location'),
						defaultFilter TEXT,
						defaultSort ENUM('relevance', 'popularity', 'newest_to_oldest', 'oldest_to_newest', 'author', 'title', 'user_rating'),
						UNIQUE (textId)
					) ENGINE = MYISAM",
				),
			),
		);
	}

	public function populateWorkLevelRatings(){
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';

		$sql = "TRUNCATE table user_work_rating";
		mysql_query($sql);
		$sql = "SELECT userid, rating, dateRated, record_id, source from user_rating inner join resource on resource.id = resourceid";
		$result = mysql_query($sql);
		while ($row = mysql_fetch_assoc($result)) {
			//We got a rating from the user, find the appropriate work based on the resource
			if ($row['source'] == 'VuFind'){
				//Should be an ils identifier for this
				$workIdentifier = new GroupedWorkIdentifier();
				$workIdentifier->identifier = $row['record_id'];
				$workIdentifier->type = 'ils';
				$workIdentifier->joinAdd(new GroupedWork());
				$workIdentifier->selectAdd('permanent_id');
				if ($workIdentifier->find(true)){
					$userWorkRating = new UserWorkReview();
					$userWorkRating->groupedRecordPermanentId = $workIdentifier->permanent_id;
					$userWorkRating->userid = $row['userid'];
					$userWorkRating->rating = $row['rating'];
					$userWorkRating->dateRated = $row['dateRated'];
					$userWorkRating->insert();
				}else{
					echo("Warning, did not find grouped work for {$row['record_id']}<br/>");
				}
			}else{
				//eContent
				echo("Warning, resource was marked as eContent, but should not have been {$row['record_id']}<br/>");
			}
		}

		//TODO: Load all econtent from econtent ratings


		//Merge ratings with comments to get reviews
		$sql = "SELECT user_id, comment, created, record_id, source from comments inner join resource on resource.id = resource_id";
		$result = mysql_query($sql);
		while ($row = mysql_fetch_assoc($result)) {
//We got a rating from the user, find the appropriate work based on the resource
			if ($row['source'] == 'VuFind'){
				//Should be an ils identifier for this
				$workIdentifier = new GroupedWorkIdentifier();
				$workIdentifier->identifier = $row['record_id'];
				$workIdentifier->type = 'ils';
				$workIdentifier->joinAdd(new GroupedWork());
				$workIdentifier->selectAdd('permanent_id');
				if ($workIdentifier->find(true)){
					$userWorkRating = new UserWorkReview();
					$userWorkRating->groupedRecordPermanentId = $workIdentifier->permanent_id;
					$userWorkRating->userid = $row['user_id'];
					//First check to see if we already have a rating
					$existingRating = false;
					if ($userWorkRating->find(true)){
						$existingRating = true;
					}else{
						$userWorkRating->rating = -1;
						$userWorkRating->dateRated = $row['created'];
					}
					$userWorkRating->review = $row['comment'];
					if ($existingRating){
						$userWorkRating->update();
					}else{
						$userWorkRating->insert();
					}
				}else{
					echo("Warning, did not find grouped work for {$row['record_id']}<br/>");
				}
			}else{
				//eContent
				//TODO: process econtent comments
			}
		}


		mysql_free_result($result);
	}

	public function addTableListWidgetListsLinks()
	{
		set_time_limit(120);
		$sql =	'CREATE TABLE IF NOT EXISTS `list_widget_lists_links`( '.
				'`id` int(11) NOT NULL AUTO_INCREMENT, '.
				'`listWidgetListsId` int(11) NOT NULL, '.
				'`name` varchar(50) NOT NULL, '.
				'`link` text NOT NULL, '.
				'`weight` int(3) NOT NULL DEFAULT \'0\','.
				'PRIMARY KEY (`id`) '.
				') ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
		mysql_query($sql);
		/*$result = mysql_query('SELECT id,fullListLink FROM `list_widget_lists` WHERE `fullListLink` != "" ');
		while($row = mysql_fetch_assoc($result))
		{
			$sqlInsert = 'INSERT INTO `list_widget_lists_links` (`id`,`listWidgetListsId`,`name`,`link`) VALUES (NULL,\''.$row['id'].'\',\'Full List Link\',\''.$row['fullListLink'].'\') ';
			mysql_query($sqlInsert);
		}
		mysql_free_result($result);
		mysql_query('ALTER TABLE `list_widget_lists` DROP `fullListLink`');*/
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

	function createScopingTables($update){
		//Create global scoping tables
		$library = new Library();
		$library->find();
		while ($library->fetch()){
			$this->runSQLStatement(&$update,
				"CREATE TABLE `title_browse_scoped_results_library_{$library->subdomain}` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM");
			$this->runSQLStatement(&$update,
				"CREATE TABLE `author_browse_scoped_results_library_{$library->subdomain}` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM");
			$this->runSQLStatement(&$update,
				"CREATE TABLE `subject_browse_scoped_results_library_{$library->subdomain}` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM");
			$this->runSQLStatement(&$update,
				"CREATE TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}` (
					`browseValueId` INT( 11 ) NOT NULL ,
					`record` VARCHAR( 50 ) NOT NULL ,
					PRIMARY KEY ( `browseValueId` , `record` ) ,
					INDEX ( `browseValueId` )
				) ENGINE = MYISAM");
			//Truncate old data
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `title_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `author_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `subject_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update,
				"INSERT INTO title_browse_scoped_results_library_" . $library->subdomain . " (`browseValueId`, record)
					SELECT title_browse_scoped_results.browseValueId, title_browse_scoped_results.record
					FROM title_browse_scoped_results
					WHERE scope = 1 and scopeId = {$library->libraryId};");
			$this->runSQLStatement(&$update,
				"INSERT INTO author_browse_scoped_results_library_" . $library->subdomain . " (`browseValueId`, record)
					SELECT author_browse_scoped_results.browseValueId, author_browse_scoped_results.record
					FROM author_browse_scoped_results
					WHERE scope = 1 and scopeId = {$library->libraryId};");
			$this->runSQLStatement(&$update,
				"INSERT INTO subject_browse_scoped_results_library_" . $library->subdomain . " (`browseValueId`, record)
					SELECT subject_browse_scoped_results.browseValueId, subject_browse_scoped_results.record
					FROM subject_browse_scoped_results
					WHERE scope = 1 and scopeId = {$library->libraryId};");
			$this->runSQLStatement(&$update,
				"INSERT INTO callnumber_browse_scoped_results_library_" . $library->subdomain . " (`browseValueId`, record)
					SELECT callnumber_browse_scoped_results.browseValueId, callnumber_browse_scoped_results.record
					FROM callnumber_browse_scoped_results
					WHERE scope = 1 and scopeId = {$library->libraryId};");
		}

		//TODO: Convert tables that do lots of indexing to INNODB

	}

	function setScopingTableEngine($update){
		//$this->runSQLStatement(&$update, "ALTER TABLE `title_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement(&$update, "ALTER TABLE `title_browse_scoped_results_global` ADD INDEX ( `record` )");
		//$this->runSQLStatement(&$update, "ALTER TABLE `author_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement(&$update, "ALTER TABLE `author_browse_scoped_results_global` ADD INDEX ( `record` )");
		//$this->runSQLStatement(&$update, "ALTER TABLE `subject_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement(&$update, "ALTER TABLE `subject_browse_scoped_results_global` ADD INDEX ( `record` )");
		//$this->runSQLStatement(&$update, "ALTER TABLE `callnumber_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement(&$update, "ALTER TABLE `callnumber_browse_scoped_results_global` ADD INDEX ( `record` )");

		$library = new Library();
		$library->find();
		while ($library->fetch()){
			//$this->runSQLStatement(&$update, "ALTER TABLE `title_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement(&$update, "ALTER TABLE `title_browse_scoped_results_library_" . $library->subdomain . "` ADD INDEX ( `record` )");
			//$this->runSQLStatement(&$update, "ALTER TABLE `author_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement(&$update, "ALTER TABLE `author_browse_scoped_results_library_" . $library->subdomain . "` ADD INDEX ( `record` )");
			//$this->runSQLStatement(&$update, "ALTER TABLE `subject_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement(&$update, "ALTER TABLE `subject_browse_scoped_results_library_" . $library->subdomain . "` ADD INDEX ( `record` )");
			//$this->runSQLStatement(&$update, "ALTER TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement(&$update, "ALTER TABLE `callnumber_browse_scoped_results_library_" . $library->subdomain . "` ADD INDEX ( `record` )");

		}
	}

	function setScopingTableEngine2($update){
		$this->runSQLStatement(&$update, "TRUNCATE TABLE title_browse_scoped_results_global");
		$this->runSQLStatement(&$update, "ALTER TABLE `title_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement(&$update, "TRUNCATE TABLE author_browse_scoped_results_global");
		$this->runSQLStatement(&$update, "ALTER TABLE `author_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement(&$update, "TRUNCATE TABLE subject_browse_scoped_results_global");
		$this->runSQLStatement(&$update, "ALTER TABLE `subject_browse_scoped_results_global` ENGINE = InnoDB");
		$this->runSQLStatement(&$update, "TRUNCATE TABLE callnumber_browse_scoped_results_global");
		$this->runSQLStatement(&$update, "ALTER TABLE `callnumber_browse_scoped_results_global` ENGINE = InnoDB");

		$library = new Library();
		$library->find();
		while ($library->fetch()){
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `title_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update, "ALTER TABLE `title_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `author_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update, "ALTER TABLE `author_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `subject_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update, "ALTER TABLE `subject_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update, "ALTER TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}` ENGINE = InnoDB");
		}
	}

	function removeScopingTableIndex($update){
		$this->runSQLStatement(&$update, "TRUNCATE TABLE title_browse_scoped_results_global");
		$this->runSQLStatement(&$update, "ALTER TABLE `title_browse_scoped_results_global` DROP INDEX `record`");
		$this->runSQLStatement(&$update, "ALTER TABLE `title_browse_scoped_results_global` ENGINE = MYISAM");
		$this->runSQLStatement(&$update, "TRUNCATE TABLE author_browse_scoped_results_global");
		$this->runSQLStatement(&$update, "ALTER TABLE `author_browse_scoped_results_global` DROP INDEX `record`");
		$this->runSQLStatement(&$update, "ALTER TABLE `author_browse_scoped_results_global` ENGINE = MYISAM");
		$this->runSQLStatement(&$update, "TRUNCATE TABLE subject_browse_scoped_results_global");
		$this->runSQLStatement(&$update, "ALTER TABLE `subject_browse_scoped_results_global` DROP INDEX `record`");
		$this->runSQLStatement(&$update, "ALTER TABLE `subject_browse_scoped_results_global` ENGINE = MYISAM");
		$this->runSQLStatement(&$update, "TRUNCATE TABLE callnumber_browse_scoped_results_global");
		$this->runSQLStatement(&$update, "ALTER TABLE `callnumber_browse_scoped_results_global` DROP INDEX `record`");
		$this->runSQLStatement(&$update, "ALTER TABLE `callnumber_browse_scoped_results_global` ENGINE = MYISAM");

		$library = new Library();
		$library->find();
		while ($library->fetch()){
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `title_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update, "ALTER TABLE `title_browse_scoped_results_library_{$library->subdomain}` DROP INDEX `record`");
			$this->runSQLStatement(&$update, "ALTER TABLE `title_browse_scoped_results_library_{$library->subdomain}` ENGINE = MYISAM");
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `author_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update, "ALTER TABLE `author_browse_scoped_results_library_{$library->subdomain}` DROP INDEX `record`");
			$this->runSQLStatement(&$update, "ALTER TABLE `author_browse_scoped_results_library_{$library->subdomain}` ENGINE = MYISAM");
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `subject_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update, "ALTER TABLE `subject_browse_scoped_results_library_{$library->subdomain}` DROP INDEX `record`");
			$this->runSQLStatement(&$update, "ALTER TABLE `subject_browse_scoped_results_library_{$library->subdomain}` ENGINE = MYISAM");
			$this->runSQLStatement(&$update, "TRUNCATE TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}`");
			$this->runSQLStatement(&$update, "ALTER TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}` DROP INDEX `record`");
			$this->runSQLStatement(&$update, "ALTER TABLE `callnumber_browse_scoped_results_library_{$library->subdomain}` ENGINE = MYISAM");
		}
	}

	function runSQLStatement($update, $sql){
		set_time_limit(500);
		$result = mysql_query($sql);
		$updateOk = true;
		if ($result == 0 || $result == false){
			if (isset($update['continueOnError']) && $update['continueOnError']){
				if (!isset($update['status'])) $update['status'] = '';
				$update['status'] .= 'Warning: ' . mysql_error() . "<br/>";
			}else{
				$update['status'] = 'Update failed ' . mysql_error();
				$updateOk = false;
			}
		}else{
			if (!isset($update['status'])){
				$update['status'] = 'Update succeeded';
			}
		}
		return $updateOk;
	}

	function createDefaultIpRanges(){
		require_once ROOT_DIR . '/Drivers/marmot_inc/ipcalc.php';
		require_once ROOT_DIR . '/Drivers/marmot_inc/subnet.php';
		$subnet = new subnet();
		$subnet->find();
		while ($subnet->fetch()){
			$subnet->update();
		}
	}
}