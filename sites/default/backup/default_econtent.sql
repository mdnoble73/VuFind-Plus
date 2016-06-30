-- MySQL dump 10.13  Distrib 5.7.12, for Win64 (x86_64)
--
-- Host: localhost    Database: econtent
-- ------------------------------------------------------
-- Server version	5.5.47-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `acs_log`
--

DROP TABLE IF EXISTS `acs_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acs_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acsTransactionId` varchar(50) DEFAULT NULL,
  `userAcsId` varchar(50) DEFAULT NULL,
  `fulfilled` tinyint(4) NOT NULL,
  `returned` tinyint(4) NOT NULL,
  `transactionDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A trasaction log for transactions sent by the ACS server.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acs_log`
--

LOCK TABLES `acs_log` WRITE;
/*!40000 ALTER TABLE `acs_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `acs_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `db_update`
--

DROP TABLE IF EXISTS `db_update`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `db_update` (
  `update_key` varchar(100) NOT NULL,
  `date_run` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`update_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `db_update`
--

LOCK TABLES `db_update` WRITE;
/*!40000 ALTER TABLE `db_update` DISABLE KEYS */;
INSERT INTO `db_update` VALUES ('acsLog','2011-12-13 16:04:23'),('convertOldEContent','2011-11-06 22:58:31'),('eContentCheckout','2011-11-10 23:57:56'),('eContentCheckout_1','2011-12-13 16:04:03'),('eContentHistory','2011-11-15 17:56:44'),('eContentHolds','2011-11-10 22:39:20'),('eContentItem_1','2011-12-04 22:13:19'),('eContentRating','2011-11-16 21:53:43'),('eContentRecord_1','2011-12-01 21:43:54'),('eContentRecord_2','2012-01-11 20:06:48'),('eContentWishList','2011-12-08 20:29:48'),('econtent_attach','2011-12-30 19:12:22'),('econtent_marc_import','2011-12-15 22:48:22'),('initial_setup','2011-11-15 22:29:11'),('modifyColumnSizes_1','2011-11-10 19:46:03'),('notices_1','2011-12-02 18:26:28'),('overdrive_account_cache','2012-01-02 22:16:10'),('overdrive_api_data','2016-06-30 17:11:12'),('overdrive_api_data_availability_type','2016-06-30 17:11:12'),('overdrive_api_data_update_1','2016-06-30 17:11:12'),('overdrive_api_data_update_2','2016-06-30 17:11:12'),('overdrive_record_cache','2012-01-02 21:47:53'),('utf8_update','2016-06-30 17:11:12');
/*!40000 ALTER TABLE `db_update` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `econtent_attach`
--

DROP TABLE IF EXISTS `econtent_attach`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `econtent_attach` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourcePath` varchar(255) DEFAULT NULL,
  `dateStarted` int(11) NOT NULL,
  `dateFinished` int(11) DEFAULT NULL,
  `status` enum('running','finished') NOT NULL,
  `recordsProcessed` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A trasaction log for eContent that has been added to records.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `econtent_attach`
--

LOCK TABLES `econtent_attach` WRITE;
/*!40000 ALTER TABLE `econtent_attach` DISABLE KEYS */;
/*!40000 ALTER TABLE `econtent_attach` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `econtent_checkout`
--

DROP TABLE IF EXISTS `econtent_checkout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `econtent_checkout` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='EContent files that can be viewed within VuFind.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `econtent_checkout`
--

LOCK TABLES `econtent_checkout` WRITE;
/*!40000 ALTER TABLE `econtent_checkout` DISABLE KEYS */;
/*!40000 ALTER TABLE `econtent_checkout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `econtent_history`
--

DROP TABLE IF EXISTS `econtent_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `econtent_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `recordId` int(11) NOT NULL COMMENT 'The record id of the item that was checked out',
  `openDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The date the record was opened',
  `action` varchar(30) NOT NULL DEFAULT 'Read Online',
  `accessType` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='The econtent reading history for patrons';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `econtent_history`
--

LOCK TABLES `econtent_history` WRITE;
/*!40000 ALTER TABLE `econtent_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `econtent_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `econtent_hold`
--

DROP TABLE IF EXISTS `econtent_hold`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `econtent_hold` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='EContent files that can be viewed within VuFind.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `econtent_hold`
--

LOCK TABLES `econtent_hold` WRITE;
/*!40000 ALTER TABLE `econtent_hold` DISABLE KEYS */;
/*!40000 ALTER TABLE `econtent_hold` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `econtent_item`
--

DROP TABLE IF EXISTS `econtent_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `econtent_item` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='EContent files that can be viewed within VuFind.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `econtent_item`
--

LOCK TABLES `econtent_item` WRITE;
/*!40000 ALTER TABLE `econtent_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `econtent_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `econtent_marc_import`
--

DROP TABLE IF EXISTS `econtent_marc_import`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `econtent_marc_import` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) DEFAULT NULL,
  `dateStarted` int(11) NOT NULL,
  `dateFinished` int(11) DEFAULT NULL,
  `status` enum('running','finished') NOT NULL,
  `recordsProcessed` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A trasaction log for marc files imported into the database.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `econtent_marc_import`
--

LOCK TABLES `econtent_marc_import` WRITE;
/*!40000 ALTER TABLE `econtent_marc_import` DISABLE KEYS */;
/*!40000 ALTER TABLE `econtent_marc_import` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `econtent_rating`
--

DROP TABLE IF EXISTS `econtent_rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `econtent_rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `recordId` int(11) NOT NULL COMMENT 'The record id of the item that was checked out',
  `dateRated` int(11) NOT NULL COMMENT 'The date the record was opened',
  `rating` int(11) NOT NULL COMMENT 'The rating to aply to the record',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='The ratings for eContent records';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `econtent_rating`
--

LOCK TABLES `econtent_rating` WRITE;
/*!40000 ALTER TABLE `econtent_rating` DISABLE KEYS */;
/*!40000 ALTER TABLE `econtent_rating` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `econtent_record`
--

DROP TABLE IF EXISTS `econtent_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `econtent_record` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='EContent records for titles that exist in VuFind, bu not the ILS.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `econtent_record`
--

LOCK TABLES `econtent_record` WRITE;
/*!40000 ALTER TABLE `econtent_record` DISABLE KEYS */;
/*!40000 ALTER TABLE `econtent_record` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `econtent_wishlist`
--

DROP TABLE IF EXISTS `econtent_wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `econtent_wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT 'The id of the user who checked out the item',
  `recordId` int(11) NOT NULL COMMENT 'The record id of the item that was checked out',
  `dateAdded` int(11) NOT NULL COMMENT 'The date the record was added to the wishlist',
  `status` enum('active','deleted','filled') NOT NULL COMMENT 'The status of the item in the wishlist',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='The ratings for eContent records';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `econtent_wishlist`
--

LOCK TABLES `econtent_wishlist` WRITE;
/*!40000 ALTER TABLE `econtent_wishlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `econtent_wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_account_cache`
--

DROP TABLE IF EXISTS `overdrive_account_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_account_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `holdPage` longtext,
  `holdPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  `bookshelfPage` longtext,
  `bookshelfPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  `wishlistPage` longtext,
  `wishlistPageLastLoaded` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A cache to store information about a user''s account within OverDrive.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_account_cache`
--

LOCK TABLES `overdrive_account_cache` WRITE;
/*!40000 ALTER TABLE `overdrive_account_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_account_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_availability`
--

DROP TABLE IF EXISTS `overdrive_api_product_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `libraryId` int(11) DEFAULT NULL,
  `available` tinyint(1) DEFAULT NULL,
  `copiesOwned` int(11) DEFAULT NULL,
  `copiesAvailable` int(11) DEFAULT NULL,
  `numberOfHolds` int(11) DEFAULT NULL,
  `availabilityType` varchar(35) DEFAULT 'Normal',
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId_2` (`productId`,`libraryId`),
  KEY `productId` (`productId`),
  KEY `libraryId` (`libraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_availability`
--

LOCK TABLES `overdrive_api_product_availability` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_creators`
--

DROP TABLE IF EXISTS `overdrive_api_product_creators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_creators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `name` varchar(215) DEFAULT NULL,
  `fileAs` varchar(215) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `productId` (`productId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_creators`
--

LOCK TABLES `overdrive_api_product_creators` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_creators` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_creators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_formats`
--

DROP TABLE IF EXISTS `overdrive_api_product_formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_formats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `textId` varchar(25) DEFAULT NULL,
  `numericId` int(11) DEFAULT NULL,
  `name` varchar(512) DEFAULT NULL,
  `fileName` varchar(215) DEFAULT NULL,
  `fileSize` int(11) DEFAULT NULL,
  `partCount` tinyint(4) DEFAULT NULL,
  `sampleSource_1` varchar(215) DEFAULT NULL,
  `sampleUrl_1` varchar(215) DEFAULT NULL,
  `sampleSource_2` varchar(215) DEFAULT NULL,
  `sampleUrl_2` varchar(215) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId_2` (`productId`,`textId`),
  KEY `productId` (`productId`),
  KEY `numericId` (`numericId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_formats`
--

LOCK TABLES `overdrive_api_product_formats` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_formats` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_formats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_identifiers`
--

DROP TABLE IF EXISTS `overdrive_api_product_identifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_identifiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `value` varchar(75) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `productId` (`productId`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_identifiers`
--

LOCK TABLES `overdrive_api_product_identifiers` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_identifiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_identifiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_languages`
--

DROP TABLE IF EXISTS `overdrive_api_product_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_languages`
--

LOCK TABLES `overdrive_api_product_languages` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_languages` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_languages_ref`
--

DROP TABLE IF EXISTS `overdrive_api_product_languages_ref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_languages_ref` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `languageId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId` (`productId`,`languageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_languages_ref`
--

LOCK TABLES `overdrive_api_product_languages_ref` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_languages_ref` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_languages_ref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_metadata`
--

DROP TABLE IF EXISTS `overdrive_api_product_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) DEFAULT NULL,
  `checksum` bigint(20) DEFAULT NULL,
  `sortTitle` varchar(512) DEFAULT NULL,
  `publisher` varchar(215) DEFAULT NULL,
  `publishDate` int(11) DEFAULT NULL,
  `isPublicDomain` tinyint(1) DEFAULT NULL,
  `isPublicPerformanceAllowed` tinyint(1) DEFAULT NULL,
  `shortDescription` text,
  `fullDescription` text,
  `starRating` float DEFAULT NULL,
  `popularity` int(11) DEFAULT NULL,
  `rawData` mediumtext,
  `thumbnail` varchar(255) DEFAULT NULL,
  `cover` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productId` (`productId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_metadata`
--

LOCK TABLES `overdrive_api_product_metadata` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_metadata` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_metadata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_subjects`
--

DROP TABLE IF EXISTS `overdrive_api_product_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_subjects`
--

LOCK TABLES `overdrive_api_product_subjects` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_product_subjects_ref`
--

DROP TABLE IF EXISTS `overdrive_api_product_subjects_ref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_product_subjects_ref` (
  `productId` int(11) DEFAULT NULL,
  `subjectId` int(11) DEFAULT NULL,
  UNIQUE KEY `productId` (`productId`,`subjectId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_product_subjects_ref`
--

LOCK TABLES `overdrive_api_product_subjects_ref` WRITE;
/*!40000 ALTER TABLE `overdrive_api_product_subjects_ref` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_product_subjects_ref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_api_products`
--

DROP TABLE IF EXISTS `overdrive_api_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_api_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `overdriveId` varchar(36) NOT NULL,
  `mediaType` varchar(50) NOT NULL,
  `title` varchar(512) NOT NULL,
  `series` varchar(215) DEFAULT NULL,
  `primaryCreatorRole` varchar(50) DEFAULT NULL,
  `primaryCreatorName` varchar(215) DEFAULT NULL,
  `cover` varchar(215) DEFAULT NULL,
  `dateAdded` int(11) DEFAULT NULL,
  `dateUpdated` int(11) DEFAULT NULL,
  `lastMetadataCheck` int(11) DEFAULT NULL,
  `lastMetadataChange` int(11) DEFAULT NULL,
  `lastAvailabilityCheck` int(11) DEFAULT NULL,
  `lastAvailabilityChange` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `dateDeleted` int(11) DEFAULT NULL,
  `rawData` mediumtext,
  `subtitle` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `overdriveId` (`overdriveId`),
  KEY `dateUpdated` (`dateUpdated`),
  KEY `lastMetadataCheck` (`lastMetadataCheck`),
  KEY `lastAvailabilityCheck` (`lastAvailabilityCheck`),
  KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_api_products`
--

LOCK TABLES `overdrive_api_products` WRITE;
/*!40000 ALTER TABLE `overdrive_api_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_api_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_extract_log`
--

DROP TABLE IF EXISTS `overdrive_extract_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_extract_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` int(11) DEFAULT NULL,
  `endTime` int(11) DEFAULT NULL,
  `lastUpdate` int(11) DEFAULT NULL,
  `numProducts` int(11) DEFAULT '0',
  `numErrors` int(11) DEFAULT '0',
  `numAdded` int(11) DEFAULT '0',
  `numDeleted` int(11) DEFAULT '0',
  `numUpdated` int(11) DEFAULT '0',
  `numSkipped` int(11) DEFAULT '0',
  `numAvailabilityChanges` int(11) DEFAULT '0',
  `numMetadataChanges` int(11) DEFAULT '0',
  `notes` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_extract_log`
--

LOCK TABLES `overdrive_extract_log` WRITE;
/*!40000 ALTER TABLE `overdrive_extract_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_extract_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdrive_record_cache`
--

DROP TABLE IF EXISTS `overdrive_record_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdrive_record_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourceUrl` varchar(512) DEFAULT NULL,
  `pageContents` longtext,
  `lastLoaded` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='A cache to store information about records within OverDrive.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdrive_record_cache`
--

LOCK TABLES `overdrive_record_cache` WRITE;
/*!40000 ALTER TABLE `overdrive_record_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `overdrive_record_cache` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-06-30 11:23:51
