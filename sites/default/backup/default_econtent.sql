-- phpMyAdmin SQL Dump
-- version 3.3.10
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 11, 2012 at 10:06 PM
-- Server version: 5.5.14
-- PHP Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `default_econtent`
--

-- --------------------------------------------------------

--
-- Table structure for table `acs_log`
--

CREATE TABLE IF NOT EXISTS `acs_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acsTransactionId` varchar(50) DEFAULT NULL,
  `userAcsId` varchar(50) DEFAULT NULL,
  `fulfilled` tinyint(4) NOT NULL,
  `returned` tinyint(4) NOT NULL,
  `transactionDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A trasaction log for transactions sent by the ACS server.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `acs_log`
--


-- --------------------------------------------------------

--
-- Table structure for table `db_update`
--

CREATE TABLE IF NOT EXISTS `db_update` (
  `update_key` varchar(100) NOT NULL,
  `date_run` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`update_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `db_update`
--

INSERT INTO `db_update` (`update_key`, `date_run`) VALUES
('acsLog', '2011-12-13 09:04:23'),
('convertOldEContent', '2011-11-06 15:58:31'),
('eContentCheckout', '2011-11-10 16:57:56'),
('eContentCheckout_1', '2011-12-13 09:04:03'),
('eContentHistory', '2011-11-15 10:56:44'),
('eContentHolds', '2011-11-10 15:39:20'),
('eContentItem_1', '2011-12-04 15:13:19'),
('eContentRating', '2011-11-16 14:53:43'),
('eContentRecord_1', '2011-12-01 14:43:54'),
('eContentRecord_2', '2012-01-11 13:06:48'),
('eContentWishList', '2011-12-08 13:29:48'),
('econtent_attach', '2011-12-30 12:12:22'),
('econtent_marc_import', '2011-12-15 15:48:22'),
('initial_setup', '2011-11-15 15:29:11'),
('modifyColumnSizes_1', '2011-11-10 12:46:03'),
('notices_1', '2011-12-02 11:26:28'),
('overdrive_account_cache', '2012-01-02 15:16:10'),
('overdrive_record_cache', '2012-01-02 14:47:53');

-- --------------------------------------------------------

--
-- Table structure for table `econtent_attach`
--

CREATE TABLE IF NOT EXISTS `econtent_attach` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourcePath` varchar(255) DEFAULT NULL,
  `dateStarted` int(11) NOT NULL,
  `dateFinished` int(11) DEFAULT NULL,
  `status` enum('running','finished') NOT NULL,
  `recordsProcessed` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A trasaction log for eContent that has been added to records.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `econtent_attach`
--


-- --------------------------------------------------------

--
-- Table structure for table `econtent_checkout`
--

CREATE TABLE IF NOT EXISTS `econtent_checkout` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the eContent checkout',
  `recordId` int(11) NOT NULL COMMENT 'The id of the record being checked out',
  `dateCheckedOut` int(11) NOT NULL COMMENT 'When the item was checked out',
  `dateDue` int(11) NOT NULL COMMENT 'When the item needs to be returned',
  `dateReturned` int(11) DEFAULT NULL COMMENT 'When the item was returned',
  `userId` int(11) NOT NULL COMMENT 'The user who the hold is for',
  `status` enum('out','returned') DEFAULT NULL,
  `renewalCount` int(11) DEFAULT NULL COMMENT 'The number of times the item has been renewed.',
  `acsDownloadLink` varchar(512) DEFAULT NULL COMMENT 'The link to use when downloading an acs protected item',
  `dateFulfilled` int(11) DEFAULT NULL COMMENT 'When the item was fulfilled in the ACS server.',
  `recordExpirationNoticeSent` tinyint(4) NOT NULL DEFAULT '0',
  `returnReminderNoticeSent` tinyint(4) NOT NULL DEFAULT '0',
  `downloadedToReader` tinyint(4) NOT NULL DEFAULT '0',
  `acsTransactionId` varchar(50) DEFAULT NULL,
  `userAcsId` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='EContent files that can be viewed within VuFind.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `econtent_checkout`
--


-- --------------------------------------------------------

--
-- Table structure for table `econtent_history`
--

CREATE TABLE IF NOT EXISTS `econtent_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `recordId` int(11) NOT NULL COMMENT 'The record id of the item that was checked out',
  `openDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The date the record was opened',
  `action` varchar(30) NOT NULL DEFAULT 'Read Online',
  `accessType` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='The econtent reading history for patrons' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `econtent_history`
--


-- --------------------------------------------------------

--
-- Table structure for table `econtent_hold`
--

CREATE TABLE IF NOT EXISTS `econtent_hold` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the eContent hold',
  `recordId` int(11) NOT NULL COMMENT 'The id of the record being placed on hold',
  `datePlaced` int(11) NOT NULL COMMENT 'When the hold was placed',
  `dateUpdated` int(11) DEFAULT NULL COMMENT 'When the hold last changed status',
  `userId` int(11) NOT NULL COMMENT 'The user who the hold is for',
  `status` enum('active','suspended','cancelled','filled','available','abandoned') DEFAULT NULL,
  `reactivateDate` int(11) DEFAULT NULL COMMENT 'When the item should be reactivated.',
  `holdAvailableNoticeSent` tinyint(4) NOT NULL DEFAULT '0',
  `holdReminderNoticeSent` tinyint(4) NOT NULL DEFAULT '0',
  `holdAbandonedNoticeSent` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='EContent files that can be viewed within VuFind.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `econtent_hold`
--


-- --------------------------------------------------------

--
-- Table structure for table `econtent_item`
--

CREATE TABLE IF NOT EXISTS `econtent_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the eContent item',
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT 'The filename of the eContent item if any',
  `folder` varchar(100) NOT NULL DEFAULT '' COMMENT 'A folder containing a group of files for mp-3 files',
  `acsId` varchar(128) DEFAULT NULL COMMENT 'The uid of the book within the Adobe Content Server.',
  `recordId` int(11) NOT NULL COMMENT 'The id of the record to attach the item to.',
  `item_type` enum('epub','pdf','jpg','gif','mp3','plucker','kindle','externalLink','externalMP3','interactiveBook') NOT NULL,
  `notes` varchar(255) NOT NULL DEFAULT '',
  `addedBy` int(11) NOT NULL DEFAULT '-1' COMMENT 'The id of the user who added the item or -1 if it was added automatically',
  `date_added` mediumtext NOT NULL COMMENT 'The date the item was added',
  `date_updated` mediumtext NOT NULL COMMENT 'The last time the item was changed',
  `reviewdBy` int(11) NOT NULL DEFAULT '-1' COMMENT 'The id of the user who added the item or -1 if not reviewed',
  `reviewStatus` enum('Not Reviewed','Approved','Rejected') NOT NULL DEFAULT 'Not Reviewed',
  `reviewDate` mediumtext COMMENT 'When the review took place.',
  `reviewNotes` text COMMENT 'Notes about the review',
  `link` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='EContent files that can be viewed within VuFind.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `econtent_item`
--


-- --------------------------------------------------------

--
-- Table structure for table `econtent_marc_import`
--

CREATE TABLE IF NOT EXISTS `econtent_marc_import` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) DEFAULT NULL,
  `dateStarted` int(11) NOT NULL,
  `dateFinished` int(11) DEFAULT NULL,
  `status` enum('running','finished') NOT NULL,
  `recordsProcessed` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A trasaction log for marc files imported into the database.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `econtent_marc_import`
--


-- --------------------------------------------------------

--
-- Table structure for table `econtent_rating`
--

CREATE TABLE IF NOT EXISTS `econtent_rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `recordId` int(11) NOT NULL COMMENT 'The record id of the item that was checked out',
  `dateRated` int(11) NOT NULL COMMENT 'The date the record was opened',
  `rating` int(11) NOT NULL COMMENT 'The rating to aply to the record',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='The ratings for eContent records' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `econtent_rating`
--


-- --------------------------------------------------------

--
-- Table structure for table `econtent_record`
--

CREATE TABLE IF NOT EXISTS `econtent_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The id of the e-pub file',
  `cover` varchar(255) DEFAULT NULL COMMENT 'The filename of the cover art if any',
  `title` varchar(255) NOT NULL DEFAULT '',
  `subTitle` varchar(255) NOT NULL DEFAULT '',
  `accessType` enum('free','acs','singleUse') NOT NULL DEFAULT 'free' COMMENT 'Whether or not the Adobe Content Server should be checked before giving the user access to the file',
  `availableCopies` int(11) NOT NULL DEFAULT '1',
  `onOrderCopies` int(11) NOT NULL DEFAULT '0',
  `author` varchar(255) NOT NULL DEFAULT '',
  `author2` text,
  `description` text,
  `contents` text,
  `subject` text COMMENT 'A list of subjects separated by carriage returns',
  `language` varchar(255) NOT NULL DEFAULT '',
  `publisher` varchar(255) NOT NULL DEFAULT '',
  `edition` varchar(255) NOT NULL DEFAULT '',
  `isbn` varchar(255) NOT NULL DEFAULT '',
  `issn` varchar(255) NOT NULL DEFAULT '',
  `upc` varchar(255) NOT NULL DEFAULT '',
  `lccn` varchar(255) NOT NULL DEFAULT '',
  `series` varchar(255) NOT NULL DEFAULT '',
  `topic` text,
  `genre` text,
  `region` text,
  `era` varchar(255) NOT NULL DEFAULT '',
  `target_audience` varchar(255) NOT NULL DEFAULT '',
  `notes` text,
  `ilsId` varchar(255) DEFAULT '',
  `source` varchar(50) NOT NULL DEFAULT '' COMMENT 'Where the file was purchased or loaded from.',
  `sourceUrl` varchar(500) DEFAULT NULL COMMENT 'A link to the original file if known.',
  `purchaseUrl` varchar(500) DEFAULT NULL COMMENT 'A link to the url where a copy can be purchased if known.',
  `publishDate` varchar(100) DEFAULT NULL COMMENT 'The date the item was published',
  `addedBy` int(11) NOT NULL DEFAULT '-1' COMMENT 'The id of the user who added the item or -1 if it was added automatically',
  `date_added` int(11) NOT NULL COMMENT 'The date the item was added',
  `date_updated` int(11) DEFAULT NULL COMMENT 'The last time the item was changed',
  `reviewedBy` int(11) NOT NULL DEFAULT '-1' COMMENT 'The id of the user who added the item or -1 if not reviewed',
  `reviewStatus` enum('Not Reviewed','Approved','Rejected') NOT NULL DEFAULT 'Not Reviewed',
  `reviewDate` int(11) DEFAULT NULL COMMENT 'When the review took place.',
  `reviewNotes` text COMMENT 'Notes about the review',
  `marcControlField` varchar(100) DEFAULT NULL COMMENT 'The control field from the marc record to avoid importing duplicates.',
  `trialTitle` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not the title was purchased outright or on a trial basis.',
  `collection` varchar(30) DEFAULT NULL,
  `marcRecord` text,
  `literary_form_full` varchar(30) DEFAULT NULL,
  `status` enum('active','deleted','archived') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='EContent records for titles that exist in VuFind, bu not the ILS.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `econtent_record`
--


-- --------------------------------------------------------

--
-- Table structure for table `econtent_wishlist`
--

CREATE TABLE IF NOT EXISTS `econtent_wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `recordId` int(11) NOT NULL COMMENT 'The record id of the item that was checked out',
  `dateAdded` int(11) NOT NULL COMMENT 'The date the record was added to the wishlist',
  `status` enum('active','deleted','filled') NOT NULL COMMENT 'The status of the item in the wishlist',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='The ratings for eContent records' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `econtent_wishlist`
--


-- --------------------------------------------------------

--
-- Table structure for table `overdrive_account_cache`
--

CREATE TABLE IF NOT EXISTS `overdrive_account_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `holdPage` longtext,
  `holdPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  `bookshelfPage` longtext,
  `bookshelfPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  `wishlistPage` longtext,
  `wishlistPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A cache to store information about a user''s account within OverDrive.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `overdrive_account_cache`
--


-- --------------------------------------------------------

--
-- Table structure for table `overdrive_record_cache`
--

CREATE TABLE IF NOT EXISTS `overdrive_record_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourceUrl` varchar(512) DEFAULT NULL,
  `pageContents` longtext,
  `lastLoaded` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A cache to store information about records within OverDrive.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `overdrive_record_cache`
--

