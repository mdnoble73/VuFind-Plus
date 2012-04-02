-- phpMyAdmin SQL Dump
-- version 3.3.10
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 02, 2012 at 02:52 PM
-- Server version: 5.5.14
-- PHP Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `dcl_vufind`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrators`
--

CREATE TABLE IF NOT EXISTS `administrators` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The unique Id for the administrator',
  `login` varchar(20) NOT NULL COMMENT 'A unique login name for the user',
  `password` varchar(32) NOT NULL COMMENT 'The MD5 has of the user''s password',
  `name` varchar(100) NOT NULL COMMENT 'A name to use when displaying the administrator',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Stores information about users who can administer the system' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `author_browse`
--

CREATE TABLE IF NOT EXISTS `author_browse` (
  `id` int(11) NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
  `value` varchar(255) NOT NULL COMMENT 'The original value',
  `numResults` int(11) NOT NULL COMMENT 'The number of results found in the table',
  PRIMARY KEY (`id`),
  KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bad_words`
--

CREATE TABLE IF NOT EXISTS `bad_words` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique Id for bad_word',
  `word` varchar(50) NOT NULL COMMENT 'The bad word that will be replaced',
  `replacement` varchar(50) NOT NULL COMMENT 'A replacement value for the word.',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Stores information about bad_words that should be removed fr' AUTO_INCREMENT=451 ;

-- --------------------------------------------------------

--
-- Table structure for table `callnumber_browse`
--

CREATE TABLE IF NOT EXISTS `callnumber_browse` (
  `id` int(11) NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
  `value` varchar(255) NOT NULL COMMENT 'The original value',
  `numResults` int(11) NOT NULL COMMENT 'The number of results found in the table',
  PRIMARY KEY (`id`),
  KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `circulationstatus`
--

CREATE TABLE IF NOT EXISTS `circulationstatus` (
  `circulationStatusId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique Id for the status',
  `millenniumName` varchar(25) NOT NULL COMMENT 'The name of the status as it displays in the Millennium holdings list',
  `displayName` varchar(40) NOT NULL COMMENT 'A name to translate the status into for display in vufind.  Leave plank to use the Millennium name.',
  `holdable` tinyint(4) NOT NULL COMMENT 'Whether or not patrons can place holds on items with this status',
  `available` tinyint(4) NOT NULL COMMENT 'Whether or not the item is available for immediate usage (if the patron is at that branch)',
  PRIMARY KEY (`circulationStatusId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Stores informattion about the circulation statuses in millen' AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `resource_id` int(11) NOT NULL DEFAULT '0',
  `comment` mediumtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `resource_id` (`resource_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=61 ;

-- --------------------------------------------------------

--
-- Table structure for table `db_update`
--

CREATE TABLE IF NOT EXISTS `db_update` (
  `update_key` varchar(100) NOT NULL,
  `date_run` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`update_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `editorial_reviews`
--

CREATE TABLE IF NOT EXISTS `editorial_reviews` (
  `editorialReviewId` int(11) NOT NULL AUTO_INCREMENT,
  `recordId` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `pubDate` bigint(20) NOT NULL,
  `review` text,
  `source` varchar(50) NOT NULL,
  PRIMARY KEY (`editorialReviewId`),
  KEY `RecordId` (`recordId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `epub_files`
--

CREATE TABLE IF NOT EXISTS `epub_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the e-pub file',
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT 'The filename of the e-pub file',
  `cover` varchar(255) DEFAULT NULL COMMENT 'The filename of the cover art if any',
  `hasDRM` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the Adobe Content Server should be checked before giving the user access to the file',
  `acsId` varchar(128) DEFAULT NULL COMMENT 'The uid of the book within the Adobe Content Server.',
  `relatedRecords` varchar(512) NOT NULL COMMENT 'A pipe delimited list of records to attach the file to',
  `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The date the record was opened',
  `type` varchar(25) NOT NULL DEFAULT 'epub',
  `source` varchar(50) NOT NULL DEFAULT '',
  `notes` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `author` varchar(50) NOT NULL DEFAULT '',
  `description` text,
  `availableCopies` int(11) NOT NULL DEFAULT '1',
  `folder` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='E-Pub files that can be viewed within VuFind.' AUTO_INCREMENT=2912 ;

-- --------------------------------------------------------

--
-- Table structure for table `epub_files_gale`
--

CREATE TABLE IF NOT EXISTS `epub_files_gale` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the e-pub file',
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT 'The filename of the e-pub file',
  `cover` varchar(255) DEFAULT NULL COMMENT 'The filename of the cover art if any',
  `hasDRM` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the Adobe Content Server should be checked before giving the user access to the file',
  `acsId` varchar(128) DEFAULT NULL COMMENT 'The uid of the book within the Adobe Content Server.',
  `relatedRecords` varchar(512) NOT NULL COMMENT 'A pipe delimited list of records to attach the file to',
  `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The date the record was opened',
  `type` varchar(25) NOT NULL DEFAULT 'epub',
  `source` varchar(50) NOT NULL DEFAULT '',
  `notes` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `author` varchar(50) NOT NULL DEFAULT '',
  `description` text,
  `availableCopies` int(11) NOT NULL DEFAULT '1',
  `folder` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='E-Pub files that can be viewed within VuFind.' AUTO_INCREMENT=2912 ;

-- --------------------------------------------------------

--
-- Table structure for table `epub_files_old`
--

CREATE TABLE IF NOT EXISTS `epub_files_old` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the e-pub file',
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT 'The filename of the e-pub file',
  `cover` varchar(255) DEFAULT NULL COMMENT 'The filename of the cover art if any',
  `hasDRM` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the Adobe Content Server should be checked before giving the user access to the file',
  `acsId` varchar(128) DEFAULT NULL COMMENT 'The uid of the book within the Adobe Content Server.',
  `relatedRecords` varchar(512) NOT NULL COMMENT 'A pipe delimited list of records to attach the file to',
  `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The date the record was opened',
  `type` varchar(25) NOT NULL DEFAULT 'EPUB',
  `source` varchar(50) NOT NULL DEFAULT '',
  `notes` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `author` varchar(50) NOT NULL DEFAULT '',
  `description` text,
  `availableCopies` int(11) NOT NULL DEFAULT '1',
  `folder` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='E-Pub files that can be viewed within VuFind.' AUTO_INCREMENT=522 ;

-- --------------------------------------------------------

--
-- Table structure for table `epub_transaction`
--

CREATE TABLE IF NOT EXISTS `epub_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `recordId` int(11) NOT NULL,
  `itemId` varchar(30) NOT NULL,
  `userAcsId` varchar(50) DEFAULT NULL,
  `transaction` varchar(50) DEFAULT NULL,
  `downloadUrl` varchar(500) DEFAULT NULL,
  `timeLinkGenerated` datetime DEFAULT NULL,
  `timeFulfilled` datetime DEFAULT NULL,
  `timeReturned` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=25 ;

-- --------------------------------------------------------

--
-- Table structure for table `externallinktracking`
--

CREATE TABLE IF NOT EXISTS `externallinktracking` (
  `externalLinkId` int(11) NOT NULL AUTO_INCREMENT,
  `ipAddress` varchar(30) DEFAULT NULL,
  `recordId` varchar(50) NOT NULL,
  `linkUrl` varchar(400) NOT NULL,
  `linkHost` varchar(200) NOT NULL,
  `trackingDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`externalLinkId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `ip_lookup`
--

CREATE TABLE IF NOT EXISTS `ip_lookup` (
  `id` int(25) NOT NULL AUTO_INCREMENT,
  `locationid` int(5) NOT NULL,
  `location` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;

-- --------------------------------------------------------

--
-- Table structure for table `library`
--

CREATE TABLE IF NOT EXISTS `library` (
  `libraryId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id to identify the library within the system',
  `subdomain` varchar(25) NOT NULL COMMENT 'The subdomain which can be used to access settings for the library',
  `displayName` varchar(50) NOT NULL COMMENT 'The name of the library which should be shown in titles.',
  `themeName` varchar(25) NOT NULL COMMENT 'The subdomain which can be used to access settings for the library',
  `facetFile` varchar(15) NOT NULL COMMENT 'The name of the facet file which should be used while searching',
  `defaultLibraryFacet` varchar(40) NOT NULL COMMENT 'A facet to apply during initial searches.  If left blank, no additional refinement will be done.',
  `showLibraryFacet` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether or not the user can see and use the library facet to change to another branch in their library system.',
  `showConsortiumFacet` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the user can see and use the consortium facet to change to other library systems. ',
  `allowInBranchHolds` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether or not the user can place holds for their branch.  If this isn''t shown, they won''t be able to place holds for books at the location they are in.  If set to false, they won''t be able to place any holds. ',
  `allowInLibraryHolds` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether or not the user can place holds for books at other locations in their library system',
  `allowConsortiumHolds` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the user can place holds for any book anywhere in the consortium.  ',
  `scope` smallint(6) NOT NULL COMMENT 'The scope for the system in Millennium to refine holdings for the user.',
  `useScope` tinyint(4) NOT NULL COMMENT 'Whether or not the scope should be used when displaying holdings.  ',
  `hideCommentsWithBadWords` tinyint(4) NOT NULL COMMENT 'If set to true (1), any comments with bad words are completely removed from the user interface for everyone except the original poster.',
  `showAmazonReviews` tinyint(4) NOT NULL COMMENT 'Whether or not reviews from Amazon are displayed on the full record page.',
  `linkToAmazon` tinyint(4) NOT NULL COMMENT 'Whether or not a purchase on Amazon link should be shown.  Should generally match showAmazonReviews setting',
  `showStandardReviews` tinyint(4) NOT NULL COMMENT 'Whether or not reviews from Content Cafe/Syndetics are displayed on the full record page.',
  `showHoldButton` tinyint(4) NOT NULL COMMENT 'Whether or not the hold button is displayed so patrons can place holds on items',
  `showLoginButton` tinyint(4) NOT NULL COMMENT 'Whether or not the login button is displayed so patrons can login to the site',
  `showTextThis` tinyint(4) NOT NULL COMMENT 'Whether or not the Text This link is shown',
  `showEmailThis` tinyint(4) NOT NULL COMMENT 'Whether or not the Email This link is shown',
  `showComments` tinyint(4) NOT NULL COMMENT 'Whether or not comments are shown (also disables adding comments)',
  `showTagging` tinyint(4) NOT NULL COMMENT 'Whether or not tags are shown (also disables adding tags)',
  `showFavorites` tinyint(4) NOT NULL COMMENT 'Whether or not uses can maintain favorites lists',
  `illLink` varchar(255) NOT NULL COMMENT 'A link to a library system specific ILL page',
  `askALibrarianLink` varchar(255) NOT NULL COMMENT 'A link to a library system specific Ask a Librarian page',
  `allow` tinyint(4) NOT NULL COMMENT 'A link to a library system specific Ask a Librarian page',
  `inSystemPickupsOnly` tinyint(4) NOT NULL COMMENT 'Restrict pickup locations to only locations within the library system which is active.',
  `defaultPType` int(11) NOT NULL,
  `facetLabel` varchar(50) NOT NULL,
  `suggestAPurchase` varchar(150) NOT NULL,
  `showEcommerceLink` tinyint(4) NOT NULL,
  `tabbedDetails` tinyint(4) NOT NULL,
  `goldRushCode` varchar(10) NOT NULL,
  `repeatSearchOption` enum('none','librarySystem','marmot','all') NOT NULL DEFAULT 'all' COMMENT 'Where to allow repeating search.  Valid options are: none, librarySystem, marmot, all',
  `repeatInProspector` tinyint(4) NOT NULL,
  `repeatInWorldCat` tinyint(4) NOT NULL,
  `systemsToRepeatIn` varchar(255) NOT NULL,
  `repeatInAmazon` tinyint(4) NOT NULL,
  `repeatInOverdrive` tinyint(4) NOT NULL DEFAULT '0',
  `homeLink` varchar(255) NOT NULL DEFAULT 'default',
  `showAdvancedSearchbox` tinyint(4) NOT NULL DEFAULT '1',
  `enableBookCart` tinyint(4) NOT NULL DEFAULT '0',
  `validPickupSystems` varchar(255) NOT NULL,
  `allowProfileUpdates` tinyint(4) NOT NULL DEFAULT '1',
  `allowRenewals` tinyint(4) NOT NULL DEFAULT '1',
  `allowFreezeHolds` tinyint(4) NOT NULL DEFAULT '0',
  `showSeriesAsTab` tinyint(4) NOT NULL DEFAULT '0',
  `showItsHere` tinyint(4) NOT NULL DEFAULT '1',
  `holdDisclaimer` mediumtext,
  `boopsieLink` varchar(150) NOT NULL,
  `enableAlphaBrowse` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`libraryId`),
  UNIQUE KEY `subdomain` (`subdomain`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `list_widgets`
--

CREATE TABLE IF NOT EXISTS `list_widgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `showTitleDescriptions` tinyint(4) DEFAULT '1',
  `onSelectCallback` varchar(255) DEFAULT '',
  `customCss` varchar(500) NOT NULL,
  `listDisplayType` enum('tabs','dropdown') NOT NULL DEFAULT 'tabs',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='A widget that can be displayed within VuFind or within other sites' AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `list_widget_lists`
--

CREATE TABLE IF NOT EXISTS `list_widget_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `listWidgetId` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `displayFor` enum('all','loggedIn','notLoggedIn') NOT NULL DEFAULT 'all',
  `name` varchar(50) NOT NULL,
  `source` varchar(500) NOT NULL,
  `fullListLink` varchar(500) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `ListWidgetId` (`listWidgetId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='The lists that should appear within the widget' AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE IF NOT EXISTS `location` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique Id for the branch or location within vuFind',
  `code` varchar(10) NOT NULL COMMENT 'The code for use when communicating with Millennium',
  `displayName` varchar(60) NOT NULL COMMENT 'The full name of the location for display to the user',
  `libraryId` int(11) NOT NULL COMMENT 'A link to the library which the location belongs to',
  `validHoldPickupBranch` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Determines if the location can be used as a pickup location if it is not the patrons home location or the location they are in.',
  `nearbyLocation1` int(11) DEFAULT NULL COMMENT 'A secondary location which is nearby and could be used for pickup of materials.',
  `nearbyLocation2` int(11) DEFAULT NULL COMMENT 'A tertiary location which is nearby and could be used for pickup of materials.',
  `holdingBranchLabel` varchar(40) NOT NULL COMMENT 'The label used within the Holdings table in Millenium.',
  `scope` smallint(6) NOT NULL COMMENT 'The scope for the system in Millennium to refine holdings to the branch.  If there is no scope defined for the branch, this can be set to 0.',
  `useScope` tinyint(4) NOT NULL COMMENT 'Whether or not the scope should be used when displaying holdings.  ',
  `defaultLocationFacet` varchar(40) NOT NULL COMMENT 'A facet to apply during initial searches.  If left blank, no additional refinement will be done.',
  `facetFile` varchar(15) NOT NULL DEFAULT 'default' COMMENT 'The name of the facet file which should be used while searching use default to not override the file',
  `showHoldButton` tinyint(4) NOT NULL COMMENT 'Whether or not the hold button is displayed so patrons can place holds on items',
  `showAmazonReviews` tinyint(4) NOT NULL COMMENT 'Whether or not reviews from Amazon are displayed on the full record page.',
  `showStandardReviews` tinyint(4) NOT NULL COMMENT 'Whether or not reviews from Content Cafe/Syndetics are displayed on the full record page.',
  `repeatSearchOption` enum('none','librarySystem','marmot','all') NOT NULL DEFAULT 'all' COMMENT 'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all',
  `facetLabel` varchar(50) NOT NULL COMMENT 'The Facet value used to identify this system.  If this value is changed, system_map.properties must be updated as well and the catalog must be reindexed.',
  `repeatInProspector` tinyint(4) NOT NULL,
  `repeatInWorldCat` tinyint(4) NOT NULL,
  `systemsToRepeatIn` varchar(255) NOT NULL,
  `repeatInOverdrive` tinyint(4) NOT NULL DEFAULT '0',
  `homeLink` varchar(255) NOT NULL DEFAULT 'default',
  PRIMARY KEY (`locationId`),
  UNIQUE KEY `code` (`code`),
  KEY `ValidHoldPickupBranch` (`validHoldPickupBranch`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Stores information about the various locations that are part' AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `marc_import`
--

CREATE TABLE IF NOT EXISTS `marc_import` (
  `id` varchar(50) NOT NULL DEFAULT '' COMMENT 'The id of the marc record in the ils',
  `checksum` int(11) NOT NULL COMMENT 'The timestamp when the reindex started',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `materials_request`
--

CREATE TABLE IF NOT EXISTS `materials_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `format` varchar(25) DEFAULT NULL,
  `ageLevel` varchar(25) DEFAULT NULL,
  `isbn` varchar(15) DEFAULT NULL,
  `oclcNumber` varchar(30) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publicationYear` varchar(4) DEFAULT NULL,
  `articleInfo` varchar(255) DEFAULT NULL,
  `abridged` tinyint(4) DEFAULT NULL,
  `about` text,
  `comments` text,
  `status` int(11) DEFAULT NULL,
  `dateCreated` int(11) DEFAULT NULL,
  `createdBy` int(11) DEFAULT NULL,
  `dateUpdated` int(11) DEFAULT NULL,
  `emailSent` tinyint(4) NOT NULL DEFAULT '0',
  `holdsCreated` tinyint(4) NOT NULL DEFAULT '0',
  `email` varchar(80) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `season` varchar(80) DEFAULT NULL,
  `magazineTitle` varchar(255) DEFAULT NULL,
  `upc` varchar(15) DEFAULT NULL,
  `issn` varchar(8) DEFAULT NULL,
  `bookType` varchar(20) DEFAULT NULL,
  `subFormat` varchar(20) DEFAULT NULL,
  `magazineDate` varchar(20) DEFAULT NULL,
  `magazineVolume` varchar(20) DEFAULT NULL,
  `magazinePageNumbers` varchar(20) DEFAULT NULL,
  `placeHoldWhenAvailable` tinyint(4) DEFAULT NULL,
  `holdPickupLocation` varchar(10) DEFAULT NULL,
  `bookmobileStop` varchar(50) DEFAULT NULL,
  `illItem` varchar(80) DEFAULT NULL,
  `magazineNumber` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21 ;

-- --------------------------------------------------------

--
-- Table structure for table `materials_request_status`
--

CREATE TABLE IF NOT EXISTS `materials_request_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(80) DEFAULT NULL,
  `isDefault` tinyint(4) DEFAULT '0',
  `sendEmailToPatron` tinyint(4) DEFAULT NULL,
  `emailTemplate` text,
  `isOpen` tinyint(4) DEFAULT NULL,
  `isPatronCancel` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `isDefault` (`isDefault`),
  KEY `isOpen` (`isOpen`),
  KEY `isPatronCancel` (`isPatronCancel`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21 ;

-- --------------------------------------------------------

--
-- Table structure for table `millennium_cache`
--

CREATE TABLE IF NOT EXISTS `millennium_cache` (
  `recordId` varchar(20) NOT NULL COMMENT 'The recordId being checked',
  `scope` int(16) NOT NULL COMMENT 'The scope that was loaded',
  `holdingsInfo` longtext NOT NULL COMMENT 'Raw HTML returned from Millennium for holdings',
  `framesetInfo` longtext NOT NULL COMMENT 'Raw HTML returned from Millennium on the frameset page',
  `cacheDate` int(16) NOT NULL COMMENT 'When the entry was recorded in the cache',
  PRIMARY KEY (`recordId`,`scope`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Caches information from Millennium so we do not have to cont';

-- --------------------------------------------------------

--
-- Table structure for table `nonholdablelocations`
--

CREATE TABLE IF NOT EXISTS `nonholdablelocations` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
  `millenniumCode` varchar(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
  `holdingDisplay` varchar(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium',
  `availableAtCircDesk` tinyint(4) NOT NULL COMMENT 'The item is available if the patron visits the circulation desk.',
  PRIMARY KEY (`locationId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ptyperestrictedlocations`
--

CREATE TABLE IF NOT EXISTS `ptyperestrictedlocations` (
  `locationId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'A unique id for the non holdable location',
  `millenniumCode` varchar(5) NOT NULL COMMENT 'The internal 5 letter code within Millennium',
  `holdingDisplay` varchar(30) NOT NULL COMMENT 'The text displayed in the holdings list within Millennium can use regular expression syntax to match multiple locations',
  `allowablePtypes` varchar(50) NOT NULL COMMENT 'A list of PTypes that are allowed to place holds on items with this location separated with pipes (|).',
  PRIMARY KEY (`locationId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `purchaselinktracking`
--

CREATE TABLE IF NOT EXISTS `purchaselinktracking` (
  `purchaseLinkId` int(11) NOT NULL AUTO_INCREMENT,
  `ipAddress` varchar(30) DEFAULT NULL,
  `recordId` varchar(50) NOT NULL,
  `store` varchar(255) NOT NULL,
  `trackingDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`purchaseLinkId`),
  KEY `purchaseLinkId` (`purchaseLinkId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=36 ;

-- --------------------------------------------------------

--
-- Table structure for table `reindex_log`
--

CREATE TABLE IF NOT EXISTS `reindex_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of reindex log',
  `startTime` int(11) NOT NULL COMMENT 'The timestamp when the reindex started',
  `endTime` int(11) DEFAULT NULL COMMENT 'The timestamp when the reindex process ended',
  `numRecordsAddedToSolr` int(11) DEFAULT NULL,
  `numRecordsRemovedFromSolr` int(11) DEFAULT NULL,
  `numUnchangedRecords` int(11) DEFAULT NULL,
  `notes` longtext COMMENT 'Detailed information about the reindex process.',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='The reading history for patrons' AUTO_INCREMENT=100 ;

-- --------------------------------------------------------

--
-- Table structure for table `resource`
--

CREATE TABLE IF NOT EXISTS `resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `source` varchar(50) NOT NULL DEFAULT 'VuFind',
  `author` varchar(255) DEFAULT NULL,
  `title_sort` varchar(255) DEFAULT NULL,
  `isbn` varchar(13) DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `format` varchar(50) DEFAULT NULL,
  `format_category` varchar(50) DEFAULT NULL,
  `marc_checksum` bigint(20) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `records_by_source` (`record_id`,`source`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=270496 ;

-- --------------------------------------------------------

--
-- Table structure for table `resource_callnumber`
--

CREATE TABLE IF NOT EXISTS `resource_callnumber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resourceId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  `callnumber` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `callnumber` (`callnumber`),
  KEY `resourceId` (`resourceId`),
  KEY `locationId` (`locationId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5423 ;

-- --------------------------------------------------------

--
-- Table structure for table `resource_subject`
--

CREATE TABLE IF NOT EXISTS `resource_subject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resourceId` int(11) NOT NULL,
  `subjectId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `resourceId` (`resourceId`),
  KEY `subjectId` (`subjectId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=68741 ;

-- --------------------------------------------------------

--
-- Table structure for table `resource_tags`
--

CREATE TABLE IF NOT EXISTS `resource_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL DEFAULT '0',
  `tag_id` int(11) NOT NULL DEFAULT '0',
  `list_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `resource_id` (`resource_id`),
  KEY `tag_id` (`tag_id`),
  KEY `list_id` (`list_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=24 ;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE IF NOT EXISTS `roles` (
  `roleId` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'The internal name of the role',
  `description` varchar(100) NOT NULL COMMENT 'A description of what the role allows',
  PRIMARY KEY (`roleId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='A role identifying what the user can do.' AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Table structure for table `search`
--

CREATE TABLE IF NOT EXISTS `search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `session_id` varchar(128) DEFAULT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `created` date NOT NULL DEFAULT '0000-00-00',
  `title` varchar(20) DEFAULT NULL,
  `saved` int(1) NOT NULL DEFAULT '0',
  `search_object` blob,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `folder_id` (`folder_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3197 ;

-- --------------------------------------------------------

--
-- Table structure for table `search_stats`
--

CREATE TABLE IF NOT EXISTS `search_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The unique id of the search statistic',
  `phrase` varchar(500) NOT NULL COMMENT 'The phrase being searched for',
  `type` varchar(50) NOT NULL COMMENT 'The type of search being done',
  `numResults` int(16) NOT NULL COMMENT 'The number of hits that were found.',
  `lastSearch` int(16) NOT NULL COMMENT 'The last time this search was done',
  `numSearches` int(16) NOT NULL COMMENT 'The number of times this search has been done.',
  `libraryId` int(16) NOT NULL COMMENT 'The library id that this search was scoped to or -1 for unscoped.',
  `locationId` int(16) NOT NULL COMMENT 'The location id that this search was scoped to or -1 for unscoped.',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `search_index` (`type`,`libraryId`,`locationId`,`phrase`(255),`numResults`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Statistical information about searches for use in reporting ' AUTO_INCREMENT=200364 ;

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE IF NOT EXISTS `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(128) DEFAULT NULL,
  `data` mediumtext,
  `last_used` int(12) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `last_used` (`last_used`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=116055 ;

-- --------------------------------------------------------

--
-- Table structure for table `spelling_words`
--

CREATE TABLE IF NOT EXISTS `spelling_words` (
  `word` varchar(30) NOT NULL COMMENT 'A word that is correctly spelled',
  `commonality` int(11) NOT NULL COMMENT 'How common the word is from 1-100 with 1 being the most common',
  `soundex` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`word`),
  KEY `Soundex` (`soundex`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Stores information about words that may be used as search su';

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE IF NOT EXISTS `subject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4939 ;

-- --------------------------------------------------------

--
-- Table structure for table `subject_browse`
--

CREATE TABLE IF NOT EXISTS `subject_browse` (
  `id` int(11) NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
  `value` varchar(255) NOT NULL COMMENT 'The original value',
  `numResults` int(11) NOT NULL COMMENT 'The number of results found in the table',
  PRIMARY KEY (`id`),
  KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(25) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

--
-- Table structure for table `title_browse`
--

CREATE TABLE IF NOT EXISTS `title_browse` (
  `id` int(11) NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
  `value` varchar(255) NOT NULL COMMENT 'The original value',
  `numResults` int(11) NOT NULL COMMENT 'The number of results found in the table',
  PRIMARY KEY (`id`),
  KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `usagetracking`
--

CREATE TABLE IF NOT EXISTS `usagetracking` (
  `usageId` int(11) NOT NULL AUTO_INCREMENT,
  `ipId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  `numPageViews` int(11) NOT NULL DEFAULT '0',
  `numHolds` int(11) NOT NULL DEFAULT '0',
  `numRenewals` int(11) NOT NULL DEFAULT '0',
  `trackingDate` bigint(20) NOT NULL,
  PRIMARY KEY (`usageId`),
  KEY `usageId` (`usageId`),
  KEY `IP_DATE` (`ipId`,`trackingDate`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=127 ;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `firstname` varchar(50) NOT NULL DEFAULT '',
  `lastname` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(250) NOT NULL DEFAULT '',
  `cat_username` varchar(50) DEFAULT NULL,
  `cat_password` varchar(50) DEFAULT NULL,
  `college` varchar(100) NOT NULL DEFAULT '',
  `major` varchar(100) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `homeLocationId` int(11) NOT NULL COMMENT 'A link to the locations table for the users home location (branch) defined in millennium',
  `myLocation1Id` int(11) NOT NULL COMMENT 'A link to the locations table representing an alternate branch the users frequents or that is close by',
  `myLocation2Id` int(11) NOT NULL COMMENT 'A link to the locations table representing an alternate branch the users frequents or that is close by',
  `trackReadingHistory` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not Reading History should be tracked within VuFind.',
  `bypassAutoLogout` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the user wants to bypass the automatic logout code on public workstations.',
  `displayName` varchar(30) NOT NULL DEFAULT '',
  `disableCoverArt` tinyint(4) NOT NULL DEFAULT '0',
  `disableRecommendations` tinyint(4) NOT NULL DEFAULT '0',
  `phone` varchar(30) NOT NULL DEFAULT '',
  `patronType` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30517 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_epub_history`
--

CREATE TABLE IF NOT EXISTS `user_epub_history` (
  `userHistoryId` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `resourceId` int(11) NOT NULL COMMENT 'The record id of the item that was checked out',
  `openDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The date the record was opened',
  `action` varchar(30) NOT NULL DEFAULT 'Read Online',
  `accessType` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`userHistoryId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='The epub reading history for patrons' AUTO_INCREMENT=674 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_epub_history_seq`
--

CREATE TABLE IF NOT EXISTS `user_epub_history_seq` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=443 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_list`
--

CREATE TABLE IF NOT EXISTS `user_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` mediumtext,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `public` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1689 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_rating`
--

CREATE TABLE IF NOT EXISTS `user_rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `resourceid` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqueness` (`userid`,`resourceid`),
  KEY `Resourceid` (`resourceid`),
  KEY `UserId` (`userid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=53 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_reading_history`
--

CREATE TABLE IF NOT EXISTS `user_reading_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `resourceId` int(11) NOT NULL COMMENT 'The record id of the item that was checked out',
  `lastCheckoutDate` date NOT NULL COMMENT 'The first day we detected that the item was checked out to the patron',
  `firstCheckoutDate` date NOT NULL COMMENT 'The last day we detected the item was checked out to the patron',
  `daysCheckedOut` int(11) NOT NULL COMMENT 'The total number of days the item was checked out even if it was checked out multiple times.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_resource` (`userId`,`resourceId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='The reading history for patrons' AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_reading_history_seq`
--

CREATE TABLE IF NOT EXISTS `user_reading_history_seq` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_resource`
--

CREATE TABLE IF NOT EXISTS `user_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `list_id` int(11) DEFAULT NULL,
  `notes` mediumtext,
  `saved` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  KEY `user_id` (`user_id`),
  KEY `list_id` (`list_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11474 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE IF NOT EXISTS `user_roles` (
  `userId` int(11) NOT NULL,
  `roleId` int(11) NOT NULL,
  PRIMARY KEY (`userId`,`roleId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Links users with roles so users can perform administration f';

-- --------------------------------------------------------

--
-- Table structure for table `user_suggestions`
--

CREATE TABLE IF NOT EXISTS `user_suggestions` (
  `suggestionId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The unique id of the suggestion',
  `name` varchar(50) NOT NULL COMMENT 'The name of the user who entered the suggestion',
  `email` varchar(100) NOT NULL COMMENT 'The email address of the user who entered the suggestion',
  `suggestion` longtext NOT NULL COMMENT 'The text of the suggestion',
  `enteredOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the suggestion was entered (for sorting)',
  `hide` tinyint(4) NOT NULL COMMENT 'Whether or not the suggestion should be hidden from the admin panel',
  `internalNotes` longtext NOT NULL COMMENT 'Internal notes by an administrator if needed.',
  PRIMARY KEY (`suggestionId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Stores suggestions from users of the catalog.' AUTO_INCREMENT=1 ;
