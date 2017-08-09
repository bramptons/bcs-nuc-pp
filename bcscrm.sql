-- MySQL dump 10.13  Distrib 5.6.23, for Win64 (x86_64)
--
-- Host: localhost    Database: bcscrm
-- ------------------------------------------------------
-- Server version	5.5.43

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
-- Table structure for table `tblaccesskey`
--

DROP TABLE IF EXISTS `tblaccesskey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblaccesskey` (
  `AccessKeyID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `AccessKey` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `Enabled` tinyint(1) NOT NULL DEFAULT '0',
  `IPv4Address` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `IPv4Mask` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ValidFrom` datetime DEFAULT NULL,
  `ValidUntil` datetime DEFAULT NULL,
  PRIMARY KEY (`AccessKeyID`),
  KEY `idxEnabled` (`Enabled`),
  KEY `idxFrom` (`ValidFrom`),
  KEY `idxKey` (`AccessKey`),
  KEY `idxUntil` (`ValidUntil`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblactiongroup`
--

DROP TABLE IF EXISTS `tblactiongroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblactiongroup` (
  `ActionGroupID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ActionGroupName` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `ActionGroupDesc` text COLLATE utf8_unicode_ci,
  `InApplication` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ActionGroupOrder` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ActionGroupID`),
  KEY `idxInApplication` (`InApplication`),
  KEY `idxName` (`ActionGroupName`),
  KEY `idxOrder` (`ActionGroupOrder`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblactiongroupitem`
--

DROP TABLE IF EXISTS `tblactiongroupitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblactiongroupitem` (
  `ActionGroupItemID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ActionGroupID` int(10) unsigned NOT NULL,
  `ItemCaption` varchar(96) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ItemToolTip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ActionGroupItemID`),
  KEY `idxActionGroup` (`ActionGroupID`),
  KEY `idxAlpha` (`ItemCaption`),
  CONSTRAINT `fkActionGroupItemToActionGroup` FOREIGN KEY (`ActionGroupID`) REFERENCES `tblactiongroup` (`ActionGroupID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbladdress`
--

DROP TABLE IF EXISTS `tbladdress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbladdress` (
  `AddressID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Lines` varchar(500) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Postcode` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Town` varchar(75) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `County` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Region` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ISO3166` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`AddressID`),
  KEY `idxTown` (`Town`),
  KEY `idxPostcode` (`Postcode`),
  KEY `fkAddressCountry_idx` (`ISO3166`),
  CONSTRAINT `fkAddressCountry` FOREIGN KEY (`ISO3166`) REFERENCES `tblcountry` (`ISO3166`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44903 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbladdresstoorganisation`
--

DROP TABLE IF EXISTS `tbladdresstoorganisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbladdresstoorganisation` (
  `AddressToOrganisationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `AddressID` int(10) unsigned NOT NULL,
  `OrganisationID` int(10) unsigned NOT NULL,
  `AddressType` enum('contact','finance','other') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'contact',
  PRIMARY KEY (`AddressToOrganisationID`),
  UNIQUE KEY `idxUnique` (`AddressID`,`OrganisationID`),
  KEY `idxAddress` (`AddressID`),
  KEY `fkAddressToOrganisationO_idx` (`OrganisationID`),
  CONSTRAINT `fkAddressToOrganisationA` FOREIGN KEY (`AddressID`) REFERENCES `tbladdress` (`AddressID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkAddressToOrganisationO` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbladdresstoperson`
--

DROP TABLE IF EXISTS `tbladdresstoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbladdresstoperson` (
  `AddressToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `AddressID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  `AddressType` enum('contact','finance','other') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'contact',
  PRIMARY KEY (`AddressToPersonID`),
  UNIQUE KEY `idxUnique` (`AddressID`,`PersonID`),
  KEY `idxAddress` (`AddressID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkAddressToPersonA` FOREIGN KEY (`AddressID`) REFERENCES `tbladdress` (`AddressID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkAddressToPersonP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=77669 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbladdresstopublication`
--

DROP TABLE IF EXISTS `tbladdresstopublication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbladdresstopublication` (
  `AddressToPublicationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `AddressID` int(10) unsigned NOT NULL,
  `PublicationToPersonID` int(10) unsigned NOT NULL,
  `PublicationToOrganisationID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`AddressToPublicationID`),
  KEY `idxAddress` (`AddressID`),
  KEY `idxPublicationOrg` (`PublicationToOrganisationID`),
  KEY `idxPublicationPerson` (`PublicationToPersonID`),
  CONSTRAINT `fkAddressToPublicationA` FOREIGN KEY (`AddressID`) REFERENCES `tbladdress` (`AddressID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkAddressToPublicationP` FOREIGN KEY (`PublicationToPersonID`) REFERENCES `tblpublicationtoperson` (`PublicationToPersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblapplication`
--

DROP TABLE IF EXISTS `tblapplication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblapplication` (
  `ApplicationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `WSCategoryID` int(10) unsigned NOT NULL,
  `ApplicationStageID` int(10) unsigned NOT NULL,
  `MSGradeID` smallint(5) unsigned DEFAULT NULL,
  `NOY` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `Created` datetime NOT NULL,
  `LastModified` datetime NOT NULL,
  `Flags` set('paid','fasttrack','free','directdebit') COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `IsOpen` int(1) unsigned NOT NULL DEFAULT '1',
  `Cancelled` datetime DEFAULT NULL,
  `ProposerName` varchar(96) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ProposerEmail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ProposerAffiliation` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ProposerMSNumber` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RefereeName` varchar(96) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RefereeEmail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RefereeAffiliation` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RefereeMSNumber` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DiscountID` int(10) unsigned DEFAULT NULL,
  `WhereDidYouHear` varchar(384) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RefereeTypeID` smallint(5) unsigned DEFAULT NULL,
  `ConfComponents` set('personal','address','contact','profile','study','job','expertise','proposer','referee','actiongroups','bylaws') COLLATE utf8_unicode_ci NOT NULL,
  `OtherComponents` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ApplicationID`),
  KEY `idxCreated` (`Created`),
  KEY `idxLastModified` (`LastModified`),
  KEY `idxOpen` (`IsOpen`),
  KEY `idxAppStage` (`ApplicationStageID`),
  KEY `idxWorkspace` (`WSCategoryID`),
  KEY `idxCancelled` (`Cancelled`),
  KEY `idxDiscount` (`DiscountID`),
  KEY `idxRefereeType` (`RefereeTypeID`),
  CONSTRAINT `fkApplicationAppCat` FOREIGN KEY (`WSCategoryID`) REFERENCES `tblwscategory` (`WSCategoryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkApplicationAppStage` FOREIGN KEY (`ApplicationStageID`) REFERENCES `tblapplicationstage` (`ApplicationStageID`) ON UPDATE CASCADE,
  CONSTRAINT `fkApplicationDiscount` FOREIGN KEY (`DiscountID`) REFERENCES `tbldiscount` (`DiscountID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkApplicationRefereeType` FOREIGN KEY (`RefereeTypeID`) REFERENCES `tblrefereetype` (`RefereeTypeID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=247 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblapplicationstage`
--

DROP TABLE IF EXISTS `tblapplicationstage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblapplicationstage` (
  `ApplicationStageID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `StageOrder` int(10) unsigned NOT NULL,
  `StageName` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `SubmissionStage` int(3) NOT NULL DEFAULT '1',
  `PaymentRequired` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `StageColour` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `IsCompletionStage` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `IsElectionStage` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CategorySelector` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'members',
  PRIMARY KEY (`ApplicationStageID`),
  KEY `idxOrder` (`StageOrder`),
  KEY `idxSubmStage` (`SubmissionStage`),
  KEY `idxPaymentRequired` (`PaymentRequired`),
  KEY `idxSelector` (`CategorySelector`),
  KEY `idxCompletion` (`IsCompletionStage`),
  KEY `idxElection` (`IsElectionStage`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblapplicationtoperson`
--

DROP TABLE IF EXISTS `tblapplicationtoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblapplicationtoperson` (
  `ApplicationToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ApplicationID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ApplicationToPersonID`),
  KEY `idxApplication` (`ApplicationID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkAppToPersonApp` FOREIGN KEY (`ApplicationID`) REFERENCES `tblapplication` (`ApplicationID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkAppToPersonPerson` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=247 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblattachment`
--

DROP TABLE IF EXISTS `tblattachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblattachment` (
  `AttachmentID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `EmailQueueID` int(10) unsigned NOT NULL,
  `Filename` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `MimeType` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Bucket` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Objectname` varchar(2048) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Data` longblob,
  PRIMARY KEY (`AttachmentID`),
  KEY `idxEmailQueue` (`EmailQueueID`),
  CONSTRAINT `fkAttachmentEmailQueueQ` FOREIGN KEY (`EmailQueueID`) REFERENCES `tblemailqueue` (`EmailQueueID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblauth`
--

DROP TABLE IF EXISTS `tblauth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblauth` (
  `Token` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  `Expires` datetime NOT NULL,
  PRIMARY KEY (`Token`),
  KEY `idxExpiry` (`Expires`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkAuthPersonID` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblauthquery`
--

DROP TABLE IF EXISTS `tblauthquery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblauthquery` (
  `AuthQueryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Token` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `Statement` text COLLATE utf8_unicode_ci NOT NULL,
  `Data` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`AuthQueryID`),
  KEY `idxToken` (`Token`),
  CONSTRAINT `fkAuthQyeryTokenToken` FOREIGN KEY (`Token`) REFERENCES `tblauth` (`Token`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblbulkemail`
--

DROP TABLE IF EXISTS `tblbulkemail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblbulkemail` (
  `BulkEmailID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Description` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `Request` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `SourceURL` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Created` datetime NOT NULL,
  `FromName` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FromEmail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Subject` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `Body` mediumtext COLLATE utf8_unicode_ci,
  `Priority` tinyint(3) unsigned NOT NULL DEFAULT '5',
  `Private` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`BulkEmailID`),
  KEY `idxCreated` (`Created`),
  KEY `idxExpiry` (`Expiry`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblcommittee`
--

DROP TABLE IF EXISTS `tblcommittee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblcommittee` (
  `CommitteeID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CommitteeName` varchar(92) COLLATE utf8_unicode_ci NOT NULL,
  `Description` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`CommitteeID`),
  KEY `idxAlpha` (`CommitteeName`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblcommitteerole`
--

DROP TABLE IF EXISTS `tblcommitteerole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblcommitteerole` (
  `CommitteeRoleID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Role` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `IsChair` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`CommitteeRoleID`),
  KEY `idxChair` (`IsChair`),
  KEY `idxRole` (`Role`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblcountry`
--

DROP TABLE IF EXISTS `tblcountry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblcountry` (
  `ISO3166` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `Country` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `MSFeeMultiplier` int(10) unsigned NOT NULL DEFAULT '100',
  `DDDiscount` int(10) unsigned NOT NULL DEFAULT '0',
  `PostcodeDisplay` enum('uk','beforetown','aftertown','linebeforetown','lineaftertown') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'beforetown',
  `ISO4217` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'USD',
  PRIMARY KEY (`ISO3166`),
  KEY `idxAlpha` (`Country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblcurrency`
--

DROP TABLE IF EXISTS `tblcurrency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblcurrency` (
  `ISO4217` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `Currency` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `Decimals` int(11) NOT NULL DEFAULT '2',
  `Symbol` char(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Available` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Analysis` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `VATAnalysis` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ISO4217`),
  KEY `idxAlpha` (`Currency`),
  KEY `idxAvailable` (`Available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldataprotection`
--

DROP TABLE IF EXISTS `tbldataprotection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldataprotection` (
  `DataProtectionID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Recorded` datetime NOT NULL,
  `Closed` datetime DEFAULT NULL,
  `SourcePersonID` int(10) unsigned DEFAULT NULL,
  `ActionType` enum('export','bulkemail','merge','directdebit','sensitiveinfo') COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Purpose` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Resolution` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ClosedPersonID` int(10) unsigned DEFAULT NULL,
  `ThirdPartyName` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`DataProtectionID`),
  KEY `idxClosed` (`Closed`),
  KEY `idxClosedBy` (`ClosedPersonID`),
  KEY `idxRecorded` (`Recorded`),
  KEY `idxSource` (`SourcePersonID`),
  KEY `idxThirdParty` (`ThirdPartyName`),
  CONSTRAINT `fkDataProtectionClosedBy` FOREIGN KEY (`ClosedPersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkDataProtectionSource` FOREIGN KEY (`SourcePersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldataprotectiontodirectdebit`
--

DROP TABLE IF EXISTS `tbldataprotectiontodirectdebit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldataprotectiontodirectdebit` (
  `DataProtectionToDirectDebitID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DataProtectionID` int(10) unsigned NOT NULL,
  `DirectDebitJobID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`DataProtectionToDirectDebitID`),
  KEY `idxDataProtection` (`DataProtectionID`),
  KEY `idxDirectDebit` (`DirectDebitJobID`),
  CONSTRAINT `fkDataProtectionDirectDebitDD` FOREIGN KEY (`DirectDebitJobID`) REFERENCES `tbldirectdebitjob` (`DirectDebitJobID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkDataProtectionDirectDebitDP` FOREIGN KEY (`DataProtectionID`) REFERENCES `tbldataprotection` (`DataProtectionID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldataprotectiontodocument`
--

DROP TABLE IF EXISTS `tbldataprotectiontodocument`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldataprotectiontodocument` (
  `DataProtectionToDocumentID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DataProtectionID` int(10) unsigned NOT NULL,
  `DocumentID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`DataProtectionToDocumentID`),
  KEY `idxDataProtection` (`DataProtectionID`),
  KEY `idxDocument` (`DocumentID`),
  CONSTRAINT `fkDataProtectionDocumentDo` FOREIGN KEY (`DocumentID`) REFERENCES `tbldocument` (`DocumentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkDataProtectionDocumentDP` FOREIGN KEY (`DataProtectionID`) REFERENCES `tbldataprotection` (`DataProtectionID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblddi`
--

DROP TABLE IF EXISTS `tblddi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblddi` (
  `DDIID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned DEFAULT NULL,
  `InstructionScope` set('members','publications') COLLATE utf8_unicode_ci NOT NULL,
  `Created` datetime NOT NULL,
  `ValidFrom` datetime NOT NULL,
  `InstructionStatus` enum('setup','active','cancelled') COLLATE utf8_unicode_ci NOT NULL,
  `InstructionType` enum('paper','auddismigrated','auddisimported','auddisonline','auddisoffline') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'paper',
  `AUDDIS` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AccountHolder` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `SortCode` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `AccountNo` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `DDReference` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BankName` varchar(120) COLLATE utf8_unicode_ci DEFAULT NULL,
  `TransactionCount` int(10) unsigned NOT NULL DEFAULT '0',
  `LastUsed` datetime DEFAULT NULL,
  PRIMARY KEY (`DDIID`),
  KEY `idxAUDDIS` (`AUDDIS`),
  KEY `idxCreated` (`Created`),
  KEY `idxLastUsed` (`LastUsed`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxReference` (`DDReference`),
  KEY `idxScope` (`InstructionScope`),
  KEY `idxStatus` (`InstructionStatus`),
  KEY `idxType` (`InstructionType`),
  KEY `idxValid` (`ValidFrom`),
  CONSTRAINT `fkddiperson` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3717 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldirectdebitjob`
--

DROP TABLE IF EXISTS `tbldirectdebitjob`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldirectdebitjob` (
  `DirectDebitJobID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned DEFAULT NULL,
  `Description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Created` datetime NOT NULL,
  `PlannedSubmission` datetime NOT NULL,
  `EmailNotifications` datetime DEFAULT NULL,
  `PDFNotifications` datetime DEFAULT NULL,
  `Submitted` datetime DEFAULT NULL,
  `ResultsProcessed` datetime DEFAULT NULL,
  `Locked` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`DirectDebitJobID`),
  KEY `idxCreated` (`Created`),
  KEY `idxEmailNotified` (`EmailNotifications`),
  KEY `idxPDFNotified` (`PDFNotifications`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxPlannedSubmission` (`PlannedSubmission`),
  KEY `idxResultsProcessed` (`ResultsProcessed`),
  KEY `idxSubmitted` (`Submitted`),
  CONSTRAINT `fkDirectDebitJobPerson` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldirectdebitjobitem`
--

DROP TABLE IF EXISTS `tbldirectdebitjobitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldirectdebitjobitem` (
  `DirectDebitJobItemID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DirectDebitJobID` int(10) unsigned NOT NULL,
  `DDIID` int(10) unsigned DEFAULT NULL,
  `InvoiceID` int(10) unsigned DEFAULT NULL,
  `SubmittedValue` int(11) NOT NULL,
  PRIMARY KEY (`DirectDebitJobItemID`),
  KEY `idxDDI` (`DDIID`),
  KEY `idxInvoice` (`InvoiceID`),
  KEY `idxJob` (`DirectDebitJobID`),
  CONSTRAINT `fkDirectDebitJobItemDDI` FOREIGN KEY (`DDIID`) REFERENCES `tblddi` (`DDIID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkDirectDebitJobItemInv` FOREIGN KEY (`InvoiceID`) REFERENCES `tblinvoice` (`InvoiceID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkDirectDebitJobItemJob` FOREIGN KEY (`DirectDebitJobID`) REFERENCES `tbldirectdebitjob` (`DirectDebitJobID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=691 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldiscount`
--

DROP TABLE IF EXISTS `tbldiscount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldiscount` (
  `DiscountID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DiscountCode` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `CategorySelector` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `InvoiceItemTypeID` int(10) unsigned DEFAULT NULL,
  `Discount` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `RefCount` smallint(5) unsigned NOT NULL DEFAULT '1',
  `ValidFrom` datetime NOT NULL,
  `ValidUntil` datetime DEFAULT NULL,
  PRIMARY KEY (`DiscountID`),
  UNIQUE KEY `idxCode` (`DiscountCode`),
  KEY `idxAlpha` (`Description`),
  KEY `idxCategory` (`CategorySelector`),
  KEY `idxInvItemType` (`InvoiceItemTypeID`),
  KEY `idxValidFrom` (`ValidFrom`),
  KEY `idxValidTo` (`ValidUntil`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldiscounttoorganisation`
--

DROP TABLE IF EXISTS `tbldiscounttoorganisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldiscounttoorganisation` (
  `DiscountToOrganisationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DiscountID` int(10) unsigned NOT NULL,
  `OrganisationID` int(10) unsigned NOT NULL,
  `RefCount` smallint(5) unsigned NOT NULL DEFAULT '1',
  `Expires` datetime DEFAULT NULL,
  PRIMARY KEY (`DiscountToOrganisationID`),
  KEY `idxDiscount` (`DiscountID`),
  KEY `idxExpires` (`Expires`),
  KEY `idxOrganisation` (`OrganisationID`),
  CONSTRAINT `fkDiscountToOrganisationD` FOREIGN KEY (`DiscountID`) REFERENCES `tbldiscount` (`DiscountID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkDiscountToOrganisationO` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldiscounttoperson`
--

DROP TABLE IF EXISTS `tbldiscounttoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldiscounttoperson` (
  `DiscountToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DiscountID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  `RefCount` smallint(5) unsigned NOT NULL DEFAULT '1',
  `Expires` datetime DEFAULT NULL,
  PRIMARY KEY (`DiscountToPersonID`),
  KEY `idxDiscount` (`DiscountID`),
  KEY `idxExpires` (`Expires`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkDiscountToPersonD` FOREIGN KEY (`DiscountID`) REFERENCES `tbldiscount` (`DiscountID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkDiscountToPersonP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldocument`
--

DROP TABLE IF EXISTS `tbldocument`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldocument` (
  `DocumentID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LastModified` datetime NOT NULL,
  `DocTitle` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Filename` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `MimeType` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Bucket` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Objectname` varchar(2048) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Data` longblob,
  `Confidential` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Expires` datetime DEFAULT NULL,
  PRIMARY KEY (`DocumentID`),
  KEY `idxModified` (`LastModified`),
  KEY `idxExpires` (`Expires`)
) ENGINE=InnoDB AUTO_INCREMENT=62354 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldocumentdownload`
--

DROP TABLE IF EXISTS `tbldocumentdownload`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldocumentdownload` (
  `DocumentDownloadID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DocumentID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned DEFAULT NULL,
  `Downloaded` datetime NOT NULL,
  PRIMARY KEY (`DocumentDownloadID`),
  KEY `idxDocument` (`DocumentID`),
  KEY `idxDownloaded` (`Downloaded`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkDocumentDownloadD` FOREIGN KEY (`DocumentID`) REFERENCES `tbldocument` (`DocumentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkDocumentDownloadP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=175 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldocumenttoddi`
--

DROP TABLE IF EXISTS `tbldocumenttoddi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldocumenttoddi` (
  `DocumentToDDIID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DocumentID` int(10) unsigned NOT NULL,
  `DDIID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`DocumentToDDIID`),
  KEY `idxDDI` (`DDIID`),
  KEY `idxDocument` (`DocumentID`),
  CONSTRAINT `fkDocumentToDDID` FOREIGN KEY (`DocumentID`) REFERENCES `tbldocument` (`DocumentID`) ON UPDATE CASCADE,
  CONSTRAINT `fkDocumentToDDII` FOREIGN KEY (`DDIID`) REFERENCES `tblddi` (`DDIID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldocumenttodirectdebitjob`
--

DROP TABLE IF EXISTS `tbldocumenttodirectdebitjob`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldocumenttodirectdebitjob` (
  `DocumentToDirectDebitJobID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DocumentID` int(10) unsigned NOT NULL,
  `DirectDebitJobID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`DocumentToDirectDebitJobID`),
  KEY `idxDocument` (`DocumentID`),
  KEY `idxJob` (`DirectDebitJobID`),
  CONSTRAINT `fkDocumentDirectDebitJobD` FOREIGN KEY (`DocumentID`) REFERENCES `tbldocument` (`DocumentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkDocumentDirectDebitJobJ` FOREIGN KEY (`DirectDebitJobID`) REFERENCES `tbldirectdebitjob` (`DirectDebitJobID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldocumenttoorganisation`
--

DROP TABLE IF EXISTS `tbldocumenttoorganisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldocumenttoorganisation` (
  `DocumentToOrganisationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DocumentID` int(10) unsigned NOT NULL,
  `OrganisationID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`DocumentToOrganisationID`),
  KEY `idxDocument` (`DocumentID`),
  KEY `idxOrganisation` (`OrganisationID`),
  CONSTRAINT `fkDocumentToOrganisationD` FOREIGN KEY (`DocumentID`) REFERENCES `tbldocument` (`DocumentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkDocumentToOrganisationO` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbldocumenttoperson`
--

DROP TABLE IF EXISTS `tbldocumenttoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbldocumenttoperson` (
  `DocumentToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DocumentID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`DocumentToPersonID`),
  KEY `idxDocument` (`DocumentID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkDocumentToPersonD` FOREIGN KEY (`DocumentID`) REFERENCES `tbldocument` (`DocumentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkDocumentToPersonP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62346 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblemail`
--

DROP TABLE IF EXISTS `tblemail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblemail` (
  `EmailID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `Email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`EmailID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxEmail` (`Email`),
  CONSTRAINT `fkEmailPersonID` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46973 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblemailqueue`
--

DROP TABLE IF EXISTS `tblemailqueue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblemailqueue` (
  `EmailQueueID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Queued` datetime NOT NULL,
  `FromName` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `FromEmail` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ReplyToEmail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `To` text COLLATE utf8_unicode_ci,
  `CC` text COLLATE utf8_unicode_ci,
  `BCC` text COLLATE utf8_unicode_ci,
  `Subject` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `Body` text COLLATE utf8_unicode_ci NOT NULL,
  `Priority` tinyint(3) unsigned NOT NULL DEFAULT '10',
  `Private` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `DocumentID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`EmailQueueID`),
  KEY `idxAge` (`Queued`),
  KEY `idxPriority` (`Priority`),
  KEY `idxDocument` (`DocumentID`)
) ENGINE=InnoDB AUTO_INCREMENT=381 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblemailqueuetoorganisation`
--

DROP TABLE IF EXISTS `tblemailqueuetoorganisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblemailqueuetoorganisation` (
  `EmailQueueToOrganisationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `EmailQueueID` int(10) unsigned NOT NULL,
  `OrganisationID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`EmailQueueToOrganisationID`),
  KEY `idxOrganisation` (`OrganisationID`),
  KEY `idxQueue` (`EmailQueueID`),
  CONSTRAINT `fkEmailQueueToOrgO` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkEmailQueueToOrgQ` FOREIGN KEY (`EmailQueueID`) REFERENCES `tblemailqueue` (`EmailQueueID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblemailqueuetoperson`
--

DROP TABLE IF EXISTS `tblemailqueuetoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblemailqueuetoperson` (
  `EmailQueueToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `EmailQueueID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`EmailQueueToPersonID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxQueue` (`EmailQueueID`),
  CONSTRAINT `fkEmailQueueToPersonP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkEmailQueueToPersonQ` FOREIGN KEY (`EmailQueueID`) REFERENCES `tblemailqueue` (`EmailQueueID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblemailstatus`
--

DROP TABLE IF EXISTS `tblemailstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblemailstatus` (
  `EmailStatusID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DocumentID` int(10) unsigned NOT NULL,
  `EmailMethod` char(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EmailStatus` enum('queued','sent','failed','dropped') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'queued',
  `RequestID` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MessageID` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Read` datetime DEFAULT NULL,
  PRIMARY KEY (`EmailStatusID`),
  KEY `idxDocument` (`DocumentID`),
  KEY `idxMessageID` (`MessageID`),
  CONSTRAINT `fkEmailStatusDocument` FOREIGN KEY (`DocumentID`) REFERENCES `tbldocument` (`DocumentID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62203 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblemailtemplate`
--

DROP TABLE IF EXISTS `tblemailtemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblemailtemplate` (
  `EmailTemplateID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Mnemonic` varchar(24) COLLATE utf8_unicode_ci NOT NULL,
  `Group` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `FromName` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FromEmail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Subject` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `Body` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `Priority` tinyint(3) unsigned NOT NULL DEFAULT '10',
  `Private` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `LastModified` datetime NOT NULL,
  `MSStatusID` smallint(5) unsigned DEFAULT NULL,
  `MSGradeID` smallint(5) unsigned DEFAULT NULL,
  `TransactionTypeID` int(10) unsigned DEFAULT NULL,
  `CategorySelector` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ISO4217` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Styles` text COLLATE utf8_unicode_ci,
  `Stylesheet` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`EmailTemplateID`),
  KEY `idxGrade` (`MSGradeID`),
  KEY `idxGroup` (`Group`),
  KEY `idxISO4217` (`ISO4217`),
  KEY `idxLastModified` (`LastModified`),
  KEY `idxMnemonic` (`Mnemonic`),
  KEY `idxSelector` (`CategorySelector`),
  KEY `idxStatus` (`MSStatusID`),
  KEY `idxTransactionType` (`TransactionTypeID`),
  CONSTRAINT `fkEmailTemplateISO4217` FOREIGN KEY (`ISO4217`) REFERENCES `tblcurrency` (`ISO4217`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkEmailTemplateMSGrade` FOREIGN KEY (`MSGradeID`) REFERENCES `tblmsgrade` (`MSGradeID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkEmailTemplateMSStatus` FOREIGN KEY (`MSStatusID`) REFERENCES `tblmsstatus` (`MSStatusID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkEmailTemplateSelector` FOREIGN KEY (`CategorySelector`) REFERENCES `tblwscategory` (`CategorySelector`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkEmailTemplateTransactionType` FOREIGN KEY (`TransactionTypeID`) REFERENCES `tbltransactiontype` (`TransactionTypeID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblexception`
--

DROP TABLE IF EXISTS `tblexception`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblexception` (
  `ExceptionID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `WSCategoryID` int(10) unsigned NOT NULL,
  `Severity` enum('info','warning','error') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'warning',
  `Recorded` datetime NOT NULL,
  `Expiry` datetime DEFAULT NULL,
  `Description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Data` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`ExceptionID`),
  KEY `idxCategory` (`WSCategoryID`),
  KEY `idxCreated` (`Recorded`),
  KEY `idxExpiry` (`Expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblexceptiontohistory`
--

DROP TABLE IF EXISTS `tblexceptiontohistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblexceptiontohistory` (
  `ExceptionToHistoryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ExceptionID` int(10) unsigned NOT NULL,
  `HistoryID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ExceptionToHistoryID`),
  KEY `idxException` (`ExceptionID`),
  KEY `idxHistory` (`HistoryID`),
  CONSTRAINT `fkExceptionToHistoryE` FOREIGN KEY (`ExceptionID`) REFERENCES `tblexception` (`ExceptionID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkExceptionToHistoryH` FOREIGN KEY (`HistoryID`) REFERENCES `tblhistory` (`HistoryID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblexceptiontoorganisation`
--

DROP TABLE IF EXISTS `tblexceptiontoorganisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblexceptiontoorganisation` (
  `ExceptionToOrganisationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ExceptionID` int(10) unsigned NOT NULL,
  `OrganisationID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ExceptionToOrganisationID`),
  KEY `idxException` (`ExceptionID`),
  KEY `idxOrganisation` (`OrganisationID`),
  CONSTRAINT `fkExceptionToOrganisationE` FOREIGN KEY (`ExceptionID`) REFERENCES `tblexception` (`ExceptionID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkExceptionToOrganisationO` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblexceptiontoperson`
--

DROP TABLE IF EXISTS `tblexceptiontoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblexceptiontoperson` (
  `ExceptionToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ExceptionID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ExceptionToPersonID`),
  KEY `idxException` (`ExceptionID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkExceptionToPersonE` FOREIGN KEY (`ExceptionID`) REFERENCES `tblexception` (`ExceptionID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkExceptionToPersonP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblfiletype`
--

DROP TABLE IF EXISTS `tblfiletype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblfiletype` (
  `Ext` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `MimeType` varchar(96) COLLATE utf8_unicode_ci NOT NULL,
  `Icon` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(48) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`Ext`),
  KEY `idxMimetype` (`MimeType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblfrom`
--

DROP TABLE IF EXISTS `tblfrom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblfrom` (
  `FromID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `FromEmail` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `FromName` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `WSCategoryID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`FromID`),
  KEY `idxAlpha` (`FromName`),
  KEY `idxEmail` (`FromEmail`),
  KEY `idxCategory` (`WSCategoryID`),
  CONSTRAINT `fkFromToWSCategory` FOREIGN KEY (`WSCategoryID`) REFERENCES `tblwscategory` (`WSCategoryID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblgrant`
--

DROP TABLE IF EXISTS `tblgrant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblgrant` (
  `GrantID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `Description` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`GrantID`),
  KEY `idxTitle` (`Title`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblhistory`
--

DROP TABLE IF EXISTS `tblhistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblhistory` (
  `HistoryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Recorded` datetime NOT NULL,
  `PersonID` int(10) unsigned DEFAULT NULL COMMENT 'This is the PersonID of the person creating the note. tblhistorytoperson provides the link(s) with which person the entry is for',
  `EntryType` enum('info','edit','delete','email','sms','call','letter','transaction','security') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'info',
  `Flags` set('system','success','warning','danger') COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Description` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `SourceIP` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Expires` datetime DEFAULT NULL,
  PRIMARY KEY (`HistoryID`),
  KEY `idxRecorded` (`Recorded`),
  KEY `idxAuthor` (`PersonID`),
  KEY `idxExpires` (`Expires`),
  CONSTRAINT `fkHistoryAuthor` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=226882 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblhistorytodirectdebit`
--

DROP TABLE IF EXISTS `tblhistorytodirectdebit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblhistorytodirectdebit` (
  `HistoryToDirectDebitID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `HistoryID` int(10) unsigned NOT NULL,
  `DirectDebitJobID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`HistoryToDirectDebitID`),
  KEY `idxDirectDebit` (`DirectDebitJobID`),
  KEY `idxHistory` (`HistoryID`),
  CONSTRAINT `fkHistoryToDirectDebitH` FOREIGN KEY (`HistoryID`) REFERENCES `tblhistory` (`HistoryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkHistoryToDirectDebitJ` FOREIGN KEY (`DirectDebitJobID`) REFERENCES `tbldirectdebitjob` (`DirectDebitJobID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblhistorytodocument`
--

DROP TABLE IF EXISTS `tblhistorytodocument`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblhistorytodocument` (
  `HistoryToDocumentID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `HistoryID` int(10) unsigned NOT NULL,
  `DocumentID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`HistoryToDocumentID`),
  KEY `idxDocument` (`DocumentID`),
  KEY `idxHistory` (`HistoryID`),
  CONSTRAINT `fkHistoryToDocumentD` FOREIGN KEY (`DocumentID`) REFERENCES `tbldocument` (`DocumentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkHistoryToDocumentH` FOREIGN KEY (`HistoryID`) REFERENCES `tblhistory` (`HistoryID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62189 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblhistorytoorganisation`
--

DROP TABLE IF EXISTS `tblhistorytoorganisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblhistorytoorganisation` (
  `HistoryToOrganisationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `HistoryID` int(10) unsigned NOT NULL,
  `OrganisationID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`HistoryToOrganisationID`),
  KEY `idxOrganisation` (`OrganisationID`),
  KEY `idxHistory` (`HistoryID`),
  CONSTRAINT `fkHistoryToOrganisationH` FOREIGN KEY (`HistoryID`) REFERENCES `tblhistory` (`HistoryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkHistoryToOrganisationO` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblhistorytoperson`
--

DROP TABLE IF EXISTS `tblhistorytoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblhistorytoperson` (
  `HistoryToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `HistoryID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`HistoryToPersonID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxHistory` (`HistoryID`),
  CONSTRAINT `fkHistoryToPersonH` FOREIGN KEY (`HistoryID`) REFERENCES `tblhistory` (`HistoryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkHistoryToPersonP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=239081 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblhistorytopersongroup`
--

DROP TABLE IF EXISTS `tblhistorytopersongroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblhistorytopersongroup` (
  `HistoryToPersonGroupID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `HistoryID` int(10) unsigned NOT NULL,
  `PersonGroupID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`HistoryToPersonGroupID`),
  KEY `idxHistory` (`HistoryID`),
  KEY `idxPersonGroup` (`PersonGroupID`),
  CONSTRAINT `fkHistoryToPersonGroupG` FOREIGN KEY (`PersonGroupID`) REFERENCES `tblpersongroup` (`PersonGroupID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkHistoryToPersonGroupH` FOREIGN KEY (`HistoryID`) REFERENCES `tblhistory` (`HistoryID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblhistorytopublication`
--

DROP TABLE IF EXISTS `tblhistorytopublication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblhistorytopublication` (
  `HistoryToPublicationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `HistoryID` int(10) unsigned NOT NULL,
  `PublicationID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`HistoryToPublicationID`),
  KEY `idxHistory` (`HistoryID`),
  KEY `idxPublication` (`PublicationID`),
  CONSTRAINT `fkHistoryToPublicationH` FOREIGN KEY (`HistoryID`) REFERENCES `tblhistory` (`HistoryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkHistoryToPublicationP` FOREIGN KEY (`PublicationID`) REFERENCES `tblpublication` (`PublicationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblinvoice`
--

DROP TABLE IF EXISTS `tblinvoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblinvoice` (
  `InvoiceID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `InvoiceType` enum('invoice','creditnote') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'invoice',
  `ISO4217` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'GBP',
  `InvoiceDate` datetime NOT NULL,
  `InvoiceDue` datetime NOT NULL,
  `InvoiceNo` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `InvoiceFrom` text COLLATE utf8_unicode_ci NOT NULL,
  `InvoiceTo` text COLLATE utf8_unicode_ci NOT NULL,
  `VATNumber` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ReminderCount` smallint(5) unsigned NOT NULL DEFAULT '0',
  `LastReminder` datetime DEFAULT NULL,
  `CustomerRef` varchar(96) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Terms` text COLLATE utf8_unicode_ci NOT NULL,
  `Payable` text COLLATE utf8_unicode_ci NOT NULL,
  `AddInfo` text COLLATE utf8_unicode_ci,
  `Analysis` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EDISent` datetime DEFAULT NULL,
  `EDIData` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`InvoiceID`),
  KEY `idxEDISent` (`EDISent`),
  KEY `idxInvoiceDate` (`InvoiceDate`),
  KEY `idxInvoiceDue` (`InvoiceDue`),
  KEY `idxInvoiceNo` (`InvoiceNo`),
  KEY `idxInvoiceType` (`InvoiceType`),
  KEY `idxISO4217` (`ISO4217`),
  KEY `idxLastReminder` (`LastReminder`),
  CONSTRAINT `fkInvoiceCurrency` FOREIGN KEY (`ISO4217`) REFERENCES `tblcurrency` (`ISO4217`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6187 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblinvoiceitem`
--

DROP TABLE IF EXISTS `tblinvoiceitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblinvoiceitem` (
  `InvoiceItemID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `InvoiceItemTypeID` int(10) unsigned DEFAULT NULL,
  `InvoiceID` int(10) unsigned NOT NULL,
  `LinkedID` int(10) unsigned DEFAULT NULL,
  `Description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ItemQty` mediumint(8) unsigned NOT NULL DEFAULT '1',
  `ItemUnitPrice` int(11) NOT NULL DEFAULT '0',
  `ItemVATRate` smallint(5) unsigned NOT NULL DEFAULT '2000',
  `ItemNet` int(11) NOT NULL DEFAULT '0',
  `ItemVAT` int(11) NOT NULL DEFAULT '0',
  `ItemDate` date DEFAULT NULL,
  `DiscountID` int(10) unsigned DEFAULT NULL,
  `Analysis` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `VATAnalysis` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Processed` datetime DEFAULT NULL,
  `Explain` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`InvoiceItemID`),
  KEY `idxDiscount` (`DiscountID`),
  KEY `idxInvoice` (`InvoiceID`),
  KEY `idxItemType` (`InvoiceItemTypeID`),
  KEY `idxLinked` (`LinkedID`),
  KEY `idxProcessed` (`Processed`),
  CONSTRAINT `fkInvoiceItemDiscount` FOREIGN KEY (`DiscountID`) REFERENCES `tbldiscount` (`DiscountID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkInvoiceItemInvoice` FOREIGN KEY (`InvoiceID`) REFERENCES `tblinvoice` (`InvoiceID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkInvoiceItemItemType` FOREIGN KEY (`InvoiceItemTypeID`) REFERENCES `tblinvoiceitemtype` (`InvoiceItemTypeID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6208 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblinvoiceitemtype`
--

DROP TABLE IF EXISTS `tblinvoiceitemtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblinvoiceitemtype` (
  `InvoiceItemTypeID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Mnemonic` varchar(24) COLLATE utf8_unicode_ci NOT NULL,
  `TypeName` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `CategorySelector` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `Analysis` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `VATAnalysis` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Writeoff` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `AllowedManual` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ReqUserIntervention` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`InvoiceItemTypeID`),
  UNIQUE KEY `Mnemonic_UNIQUE` (`Mnemonic`),
  KEY `idxAlpha` (`TypeName`),
  KEY `idxCategory` (`CategorySelector`),
  KEY `idxWriteoff` (`Writeoff`),
  KEY `idxManualAllowed` (`AllowedManual`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblinvoicetoorganisation`
--

DROP TABLE IF EXISTS `tblinvoicetoorganisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblinvoicetoorganisation` (
  `InvoiceToOrganisationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `InvoiceID` int(10) unsigned NOT NULL,
  `OrganisationID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`InvoiceToOrganisationID`),
  KEY `idxInvoice` (`InvoiceID`),
  KEY `idxOrganisation` (`OrganisationID`),
  CONSTRAINT `fkInvoiceToOrganisationI` FOREIGN KEY (`InvoiceID`) REFERENCES `tblinvoice` (`InvoiceID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkInvoiceToOrganisationO` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblinvoicetoperson`
--

DROP TABLE IF EXISTS `tblinvoicetoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblinvoicetoperson` (
  `InvoiceToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `InvoiceID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`InvoiceToPersonID`),
  KEY `idxInvoice` (`InvoiceID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkInvoiceToPersonI` FOREIGN KEY (`InvoiceID`) REFERENCES `tblinvoice` (`InvoiceID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkInvoiceToPersonP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6187 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbllogin`
--

DROP TABLE IF EXISTS `tbllogin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbllogin` (
  `LoginID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `Salt` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `PWHash` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `Expires` datetime DEFAULT NULL,
  `Method` enum('local','etarget') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'local',
  `Flags` set('noautolock') COLLATE utf8_unicode_ci NOT NULL,
  `FailCount` int(10) unsigned NOT NULL DEFAULT '0',
  `LastAttempt` datetime DEFAULT NULL,
  `SuccessCount` int(10) unsigned NOT NULL DEFAULT '0',
  `LastChanged` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`LoginID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxExpires` (`Expires`),
  KEY `idxMethod` (`Method`),
  KEY `idxFlags` (`Flags`),
  KEY `idxFailCount` (`FailCount`),
  KEY `idxLastAttempt` (`LastAttempt`),
  KEY `idxLastChanged` (`LastChanged`),
  CONSTRAINT `fkLogInPersonID` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbllogintofrom`
--

DROP TABLE IF EXISTS `tbllogintofrom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbllogintofrom` (
  `LoginToFromID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LoginID` int(10) unsigned NOT NULL,
  `FromID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`LoginToFromID`),
  KEY `idxFrom` (`FromID`),
  KEY `idxLogin` (`LoginID`),
  CONSTRAINT `fkLoginToFromF` FOREIGN KEY (`FromID`) REFERENCES `tblfrom` (`FromID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkLoginToFromL` FOREIGN KEY (`LoginID`) REFERENCES `tbllogin` (`LoginID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbllogintopermission`
--

DROP TABLE IF EXISTS `tbllogintopermission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbllogintopermission` (
  `LoginToPermissionID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LoginID` int(10) unsigned NOT NULL,
  `PermissionID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`LoginToPermissionID`),
  KEY `idxLogin` (`LoginID`),
  KEY `idxPermission` (`PermissionID`),
  CONSTRAINT `fkLoginToPermissionL` FOREIGN KEY (`LoginID`) REFERENCES `tbllogin` (`LoginID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkLoginToPermissionP` FOREIGN KEY (`PermissionID`) REFERENCES `tblpermission` (`PermissionID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbllogintopermissiongroup`
--

DROP TABLE IF EXISTS `tbllogintopermissiongroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbllogintopermissiongroup` (
  `LoginToPermissionGroupID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LoginID` int(10) unsigned NOT NULL,
  `PermissionGroupID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`LoginToPermissionGroupID`),
  KEY `idxLogin` (`LoginID`),
  KEY `idxPermissionGroup` (`PermissionGroupID`),
  CONSTRAINT `fkLoginToPermissionGroupG` FOREIGN KEY (`PermissionGroupID`) REFERENCES `tblpermissiongroup` (`PermissionGroupID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkLoginToPermissionGroupL` FOREIGN KEY (`LoginID`) REFERENCES `tbllogin` (`LoginID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblmoney`
--

DROP TABLE IF EXISTS `tblmoney`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblmoney` (
  `MoneyID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `TransactionTypeID` int(10) unsigned NOT NULL,
  `Received` datetime NOT NULL,
  `ReceivedAmount` int(11) NOT NULL DEFAULT '0',
  `ReceivedFrom` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `TransactionReference` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ISO4217` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'GBP',
  `AddInfo` text COLLATE utf8_unicode_ci,
  `Reversed` datetime DEFAULT NULL,
  `ReversalReason` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ReversalReference` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`MoneyID`),
  KEY `idxISO4217` (`ISO4217`),
  KEY `idxReceived` (`Received`),
  KEY `idxReference` (`TransactionReference`),
  KEY `idxReversed` (`Reversed`),
  KEY `idxType` (`TransactionTypeID`),
  CONSTRAINT `fkMoneyCurrency` FOREIGN KEY (`ISO4217`) REFERENCES `tblcurrency` (`ISO4217`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=358 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblmoneytoinvoice`
--

DROP TABLE IF EXISTS `tblmoneytoinvoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblmoneytoinvoice` (
  `MoneyToInvoiceID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `MoneyID` int(10) unsigned NOT NULL,
  `InvoiceID` int(10) unsigned NOT NULL,
  `AllocatedAmount` int(11) NOT NULL,
  PRIMARY KEY (`MoneyToInvoiceID`),
  KEY `idxInvoice` (`InvoiceID`),
  KEY `idxMoney` (`MoneyID`),
  CONSTRAINT `fkMoneyToInvoiceI` FOREIGN KEY (`InvoiceID`) REFERENCES `tblinvoice` (`InvoiceID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkMoneyToInvoiceM` FOREIGN KEY (`MoneyID`) REFERENCES `tblmoney` (`MoneyID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=335 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblmoneytoorganisation`
--

DROP TABLE IF EXISTS `tblmoneytoorganisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblmoneytoorganisation` (
  `MoneyToOrganisationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `MoneyID` int(10) unsigned NOT NULL,
  `OrganisationID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`MoneyToOrganisationID`),
  KEY `idxMoney` (`MoneyID`),
  KEY `idxOrganisation` (`OrganisationID`),
  CONSTRAINT `fkMoneyToOrganisationM` FOREIGN KEY (`MoneyID`) REFERENCES `tblmoney` (`MoneyID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkMoneyToOrganisationO` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblmoneytoperson`
--

DROP TABLE IF EXISTS `tblmoneytoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblmoneytoperson` (
  `MoneyToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `MoneyID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`MoneyToPersonID`),
  KEY `idxMoney` (`MoneyID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkMoneyToPersonM` FOREIGN KEY (`MoneyID`) REFERENCES `tblmoney` (`MoneyID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkMoneyToPersonP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=359 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblmsfee`
--

DROP TABLE IF EXISTS `tblmsfee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblmsfee` (
  `MSFeeID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `MSGradeID` smallint(5) unsigned NOT NULL,
  `ValidFrom` date NOT NULL,
  `ValidUntil` date DEFAULT NULL,
  PRIMARY KEY (`MSFeeID`),
  KEY `idxGrade` (`MSGradeID`),
  KEY `idxFrom` (`ValidFrom`),
  KEY `idxUntil` (`ValidUntil`),
  CONSTRAINT `fkMSFeeGrade` FOREIGN KEY (`MSGradeID`) REFERENCES `tblmsgrade` (`MSGradeID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblmsfeevalue`
--

DROP TABLE IF EXISTS `tblmsfeevalue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblmsfeevalue` (
  `MSFeeValueID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `MSFeeID` int(10) unsigned NOT NULL,
  `ISO4217` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `Value1Y` int(10) unsigned NOT NULL,
  `GroupValue1Y` int(10) unsigned DEFAULT NULL,
  `Value3Y` int(11) DEFAULT NULL,
  PRIMARY KEY (`MSFeeValueID`),
  UNIQUE KEY `idxFeeCurrency` (`MSFeeID`,`ISO4217`),
  KEY `idx4217` (`ISO4217`),
  KEY `idxFee` (`MSFeeID`),
  CONSTRAINT `fkMSFeeToFeeValueC` FOREIGN KEY (`ISO4217`) REFERENCES `tblcurrency` (`ISO4217`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkMSFeeToFeeValueF` FOREIGN KEY (`MSFeeID`) REFERENCES `tblmsfee` (`MSFeeID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblmsgrade`
--

DROP TABLE IF EXISTS `tblmsgrade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblmsgrade` (
  `MSGradeID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `GradeCaption` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Available` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `AutoElect` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ApplComponents` set('personal','address','contact','profile','study','job','expertise','proposer','referee','actiongroups','bylaws') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'personal,address',
  `DisplayOrder` smallint(5) unsigned NOT NULL DEFAULT '0',
  `IsRetired` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ApplyOnline` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `GraduationFrom` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `GraduationUntil` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`MSGradeID`),
  KEY `idxCaption` (`GradeCaption`),
  KEY `idxDisplayOrder` (`DisplayOrder`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblmsstatus`
--

DROP TABLE IF EXISTS `tblmsstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblmsstatus` (
  `MSStatusID` smallint(5) unsigned NOT NULL,
  `MSStatusCaption` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `MSStatusFlags` set('lapsed','msend','msbenefits','ismember','msstats','norenewal','overdue','anchor','deceased') COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`MSStatusID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblnationality`
--

DROP TABLE IF EXISTS `tblnationality`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblnationality` (
  `NationalityID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `Nationality` varchar(35) COLLATE utf8_unicode_ci NOT NULL,
  `ISO3166` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `IsDefault` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`NationalityID`),
  KEY `idxISO` (`ISO3166`),
  KEY `idxAlpha` (`Nationality`)
) ENGINE=InnoDB AUTO_INCREMENT=248 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblnote`
--

DROP TABLE IF EXISTS `tblnote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblnote` (
  `NoteID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned DEFAULT NULL COMMENT 'This is the PersonID of the person creating the note. tblnotetoperson provides the link(s) with who the note is for',
  `Created` datetime NOT NULL,
  `LastModified` datetime NOT NULL,
  `Expires` datetime DEFAULT NULL,
  `NoteText` text COLLATE utf8_unicode_ci NOT NULL,
  `Priority` enum('normal','high','critical') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal',
  PRIMARY KEY (`NoteID`),
  KEY `idxAuthor` (`PersonID`),
  KEY `idxLastModified` (`LastModified`),
  KEY `idxExpires` (`Expires`),
  KEY `idxPriority` (`Priority`),
  CONSTRAINT `fkNoteAuthor` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblnotetoorganisation`
--

DROP TABLE IF EXISTS `tblnotetoorganisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblnotetoorganisation` (
  `NoteToOrganisationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NoteID` int(10) unsigned NOT NULL,
  `OrganisationID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`NoteToOrganisationID`),
  KEY `idxNote` (`NoteID`),
  KEY `idxOrganisation` (`OrganisationID`),
  CONSTRAINT `fkNoteToOrganisationN` FOREIGN KEY (`NoteID`) REFERENCES `tblnote` (`NoteID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkNoteToOrganisationO` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblnotetoperson`
--

DROP TABLE IF EXISTS `tblnotetoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblnotetoperson` (
  `NoteToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NoteID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`NoteToPersonID`),
  KEY `idxNote` (`NoteID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkNoteToPersonN` FOREIGN KEY (`NoteID`) REFERENCES `tblnote` (`NoteID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkNoteToPersonP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblnotetowscategory`
--

DROP TABLE IF EXISTS `tblnotetowscategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblnotetowscategory` (
  `NoteToWSCategoryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NoteID` int(10) unsigned NOT NULL,
  `WSCategoryID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`NoteToWSCategoryID`),
  KEY `idxNote` (`NoteID`),
  KEY `idxCategory` (`WSCategoryID`),
  CONSTRAINT `fkNoteToCategoryC` FOREIGN KEY (`WSCategoryID`) REFERENCES `tblwscategory` (`WSCategoryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkNoteToCategoryN` FOREIGN KEY (`NoteID`) REFERENCES `tblnote` (`NoteID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblnotification`
--

DROP TABLE IF EXISTS `tblnotification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblnotification` (
  `NotificationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Token` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `Updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Type` enum('success','info','warning','danger','error') COLLATE utf8_unicode_ci NOT NULL,
  `Expires` timestamp NULL DEFAULT NULL,
  `SeenBefore` int(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`NotificationID`),
  KEY `idxToken` (`Token`),
  KEY `idxUpdated` (`Updated`),
  KEY `idxExpired` (`Expires`),
  CONSTRAINT `fkNotificationToken` FOREIGN KEY (`Token`) REFERENCES `tblauth` (`Token`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblnotificationitem`
--

DROP TABLE IF EXISTS `tblnotificationitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblnotificationitem` (
  `NotificationItemID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NotificationID` int(10) unsigned NOT NULL,
  `Caption` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `URL` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Script` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Target` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Icon` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`NotificationItemID`),
  KEY `idxNotification` (`NotificationID`),
  CONSTRAINT `fkNotificationItemN` FOREIGN KEY (`NotificationID`) REFERENCES `tblnotification` (`NotificationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblonline`
--

DROP TABLE IF EXISTS `tblonline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblonline` (
  `OnlineID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `CategoryIcon` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`OnlineID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblorganisation`
--

DROP TABLE IF EXISTS `tblorganisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblorganisation` (
  `OrganisationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Name` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `Dissolved` datetime DEFAULT NULL,
  `VATNumber` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CharityReg` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Ringgold` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ImportedFrom` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `UserLastModified` datetime DEFAULT NULL,
  `ISO4217` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'GBP',
  PRIMARY KEY (`OrganisationID`),
  KEY `idxAlpha` (`Name`),
  KEY `idxDissolved` (`Dissolved`),
  KEY `idxVAT` (`VATNumber`),
  KEY `idxCharity` (`CharityReg`),
  KEY `idxRinggold` (`Ringgold`),
  KEY `idxISO4217` (`ISO4217`),
  CONSTRAINT `fkOrganisationISO4217` FOREIGN KEY (`ISO4217`) REFERENCES `tblcurrency` (`ISO4217`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblorganisationtoonline`
--

DROP TABLE IF EXISTS `tblorganisationtoonline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblorganisationtoonline` (
  `OrganisationToOnlineID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `OrganisationID` int(10) unsigned NOT NULL,
  `OnlineID` int(10) unsigned NOT NULL,
  `URL` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`OrganisationToOnlineID`),
  KEY `idxOrganisation` (`OrganisationID`),
  KEY `idxOnline` (`OnlineID`),
  CONSTRAINT `fkOrganisationToOnlineO` FOREIGN KEY (`OnlineID`) REFERENCES `tblonline` (`OnlineID`) ON UPDATE CASCADE,
  CONSTRAINT `fkOrganisationToOnlineOrg` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblorganisationtophone`
--

DROP TABLE IF EXISTS `tblorganisationtophone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblorganisationtophone` (
  `OrganisationToPhoneID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PhoneTypeID` tinyint(3) unsigned NOT NULL,
  `OrganisationID` int(10) unsigned NOT NULL,
  `PhoneNo` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`OrganisationToPhoneID`),
  KEY `idxOrganisation` (`OrganisationID`),
  KEY `idxPhoneNo` (`PhoneNo`),
  KEY `idxDescription` (`Description`),
  KEY `fkOrganisationToPhonePh_idx` (`PhoneTypeID`),
  CONSTRAINT `fkOrganisationToPhoneO` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkOrganisationToPhonePh` FOREIGN KEY (`PhoneTypeID`) REFERENCES `tblphonetype` (`PhoneTypeID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpapertemplates`
--

DROP TABLE IF EXISTS `tblpapertemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpapertemplates` (
  `PaperTemplateID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Mnemonic` varchar(24) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Group` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `Title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `PageTemplate` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'default',
  `Content` mediumtext COLLATE utf8_unicode_ci,
  `LastModified` datetime NOT NULL,
  `MSStatusID` smallint(5) unsigned DEFAULT NULL,
  `MSGradeID` smallint(5) unsigned DEFAULT NULL,
  `CategorySelector` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `IsHTML` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`PaperTemplateID`),
  KEY `idxGrade` (`MSGradeID`),
  KEY `idxGroup` (`Group`),
  KEY `idxLastModified` (`LastModified`),
  KEY `idxMnemonic` (`Mnemonic`),
  KEY `idxSelector` (`CategorySelector`),
  KEY `idxStatus` (`MSStatusID`),
  KEY `idxTitle` (`Title`),
  CONSTRAINT `fkPaperTemplateMSGrade` FOREIGN KEY (`MSGradeID`) REFERENCES `tblmsgrade` (`MSGradeID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkPaperTemplateMSStatus` FOREIGN KEY (`MSStatusID`) REFERENCES `tblmsstatus` (`MSStatusID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkPaperTemplateSelector` FOREIGN KEY (`CategorySelector`) REFERENCES `tblwscategory` (`CategorySelector`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpermission`
--

DROP TABLE IF EXISTS `tblpermission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpermission` (
  `PermissionID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Mnemonic` varchar(24) COLLATE utf8_unicode_ci NOT NULL,
  `Caption` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `Description` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`PermissionID`),
  UNIQUE KEY `Mnemonic_UNIQUE` (`Mnemonic`),
  KEY `idxAlpha` (`Caption`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpermissiongroup`
--

DROP TABLE IF EXISTS `tblpermissiongroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpermissiongroup` (
  `PermissionGroupID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `IsDefault` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `IsRestricted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`PermissionGroupID`),
  KEY `idxDefault` (`IsDefault`),
  KEY `idxName` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpermissiongrouptofrom`
--

DROP TABLE IF EXISTS `tblpermissiongrouptofrom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpermissiongrouptofrom` (
  `PermissionGroupToFromID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PermissionGroupID` int(10) unsigned NOT NULL,
  `FromID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`PermissionGroupToFromID`),
  KEY `idxFrom` (`FromID`),
  KEY `idxGroup` (`PermissionGroupID`),
  CONSTRAINT `fkPermissionGroupToFromF` FOREIGN KEY (`FromID`) REFERENCES `tblfrom` (`FromID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPermissionGroupToFromG` FOREIGN KEY (`PermissionGroupID`) REFERENCES `tblpermissiongroup` (`PermissionGroupID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpermissiongrouptopermission`
--

DROP TABLE IF EXISTS `tblpermissiongrouptopermission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpermissiongrouptopermission` (
  `PermissionGroupToPermissionID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PermissionGroupID` int(10) unsigned NOT NULL,
  `PermissionID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`PermissionGroupToPermissionID`),
  KEY `idxPermission` (`PermissionID`),
  KEY `idxPermissionGroup` (`PermissionGroupID`),
  CONSTRAINT `fkPermissionGroupToPermissionG` FOREIGN KEY (`PermissionGroupID`) REFERENCES `tblpermissiongroup` (`PermissionGroupID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPermissionGroupToPermissionP` FOREIGN KEY (`PermissionID`) REFERENCES `tblpermission` (`PermissionID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblperson`
--

DROP TABLE IF EXISTS `tblperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblperson` (
  `PersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Firstname` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `Middlenames` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Lastname` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `Title` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Gender` enum('unknown','male','female','other','notsay') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'unknown',
  `DOB` date DEFAULT NULL,
  `ExtPostnominals` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `NationalityID` smallint(5) unsigned DEFAULT NULL,
  `Deceased` datetime DEFAULT NULL,
  `Graduation` date DEFAULT NULL,
  `PaidEmployment` date DEFAULT NULL,
  `DoNotContact` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `NoMarketing` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ISO3166` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `ISO4217` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'GBP',
  `MSNumber` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MSOldNumber` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MSMemberSince` date DEFAULT NULL,
  `MSNextRenewal` date DEFAULT NULL,
  `MSLastReminder` date DEFAULT NULL,
  `EmployerName` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `JobTitle` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Keywords` text COLLATE utf8_unicode_ci,
  `ImportedFrom` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MSFlags` set('norenewal','honorary') COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `UserLastModified` datetime DEFAULT NULL,
  `WorkRoleID` int(10) unsigned DEFAULT NULL,
  `PWHash` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  `PWFailCount` int(10) unsigned NOT NULL DEFAULT '0',
  `LastPWAttempt` datetime DEFAULT NULL,
  `LastPWChanged` datetime DEFAULT NULL,
  `PlaceOfStudyID` smallint(5) unsigned DEFAULT NULL,
  `PlaceOfWorkID` smallint(5) unsigned DEFAULT NULL,
  `StudyInstitution` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StudyDepartment` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`PersonID`),
  KEY `idxAlpha1` (`Firstname`,`Lastname`,`Middlenames`),
  KEY `idxAlpha2` (`Lastname`,`Firstname`,`Middlenames`),
  KEY `idxGender` (`Gender`),
  KEY `idxNationality` (`NationalityID`),
  KEY `idxDeceased` (`Deceased`),
  KEY `idxDOB` (`DOB`),
  KEY `idxMSNumber` (`MSNumber`),
  KEY `idxDoNotContact` (`DoNotContact`),
  KEY `idxISO3166` (`ISO3166`),
  KEY `idxMSLastReminder` (`MSLastReminder`),
  KEY `idxMSOldNumber` (`MSOldNumber`),
  KEY `idxMSRenewal` (`MSNextRenewal`),
  KEY `idxMSSince` (`MSMemberSince`),
  KEY `idxISO4217` (`ISO4217`),
  KEY `idxNoMarketing` (`NoMarketing`),
  KEY `fkPersonWorkRole_idx` (`WorkRoleID`),
  KEY `idxPlaceOfStudy` (`PlaceOfStudyID`),
  KEY `idxPlaceOfWork` (`PlaceOfWorkID`),
  CONSTRAINT `fkPersonISO3166` FOREIGN KEY (`ISO3166`) REFERENCES `tblcountry` (`ISO3166`) ON UPDATE CASCADE,
  CONSTRAINT `fkPersonISO4217` FOREIGN KEY (`ISO4217`) REFERENCES `tblcurrency` (`ISO4217`) ON UPDATE CASCADE,
  CONSTRAINT `fkPersonNationality` FOREIGN KEY (`NationalityID`) REFERENCES `tblnationality` (`NationalityID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkPersonPlaceOfStudy` FOREIGN KEY (`PlaceOfStudyID`) REFERENCES `tblplaceofstudy` (`PlaceOfStudyID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkPersonPlaceOfWork` FOREIGN KEY (`PlaceOfWorkID`) REFERENCES `tblplaceofwork` (`PlaceOfWorkID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkPersonWorkRole` FOREIGN KEY (`WorkRoleID`) REFERENCES `tblworkrole` (`WorkRoleID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20774 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersongroup`
--

DROP TABLE IF EXISTS `tblpersongroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersongroup` (
  `PersonGroupID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `GroupName` varchar(92) COLLATE utf8_unicode_ci NOT NULL,
  `Expires` datetime DEFAULT NULL,
  PRIMARY KEY (`PersonGroupID`),
  KEY `idxAlpha` (`GroupName`),
  KEY `idxExpires` (`Expires`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersongrouptomsgrade`
--

DROP TABLE IF EXISTS `tblpersongrouptomsgrade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersongrouptomsgrade` (
  `PersonGroupToMSGradeID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonGroupID` int(10) unsigned NOT NULL,
  `MSGradeID` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`PersonGroupToMSGradeID`),
  KEY `idxGrade` (`MSGradeID`),
  KEY `idxPersonGroup` (`PersonGroupID`),
  CONSTRAINT `fkPersonGroupToGradeGrade` FOREIGN KEY (`MSGradeID`) REFERENCES `tblmsgrade` (`MSGradeID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPersonGroupToGradeGroup` FOREIGN KEY (`PersonGroupID`) REFERENCES `tblpersongroup` (`PersonGroupID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersonms`
--

DROP TABLE IF EXISTS `tblpersonms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersonms` (
  `PersonMSID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `BeginDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  `MSStatusID` smallint(5) unsigned NOT NULL DEFAULT '0',
  `MSGradeID` smallint(5) unsigned DEFAULT NULL,
  `MSFlags` set('election','transfer','rejoin','free','norenewal') COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`PersonMSID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxBeginDate` (`BeginDate`),
  KEY `idxEndDate` (`EndDate`),
  KEY `idxStatus` (`MSStatusID`),
  KEY `idxMSGrade` (`MSGradeID`),
  CONSTRAINT `fkPersonMSG` FOREIGN KEY (`MSGradeID`) REFERENCES `tblmsgrade` (`MSGradeID`) ON UPDATE CASCADE,
  CONSTRAINT `fkPersonMSP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPersonMSS` FOREIGN KEY (`MSStatusID`) REFERENCES `tblmsstatus` (`MSStatusID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=132344 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersonmscopy`
--

DROP TABLE IF EXISTS `tblpersonmscopy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersonmscopy` (
  `PersonMSID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `BeginDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  `MSStatusID` smallint(5) unsigned NOT NULL DEFAULT '0',
  `MSGradeID` smallint(5) unsigned DEFAULT NULL,
  `MSFlags` set('election','transfer','rejoin','free','norenewal') COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`PersonMSID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxBeginDate` (`BeginDate`),
  KEY `idxEndDate` (`EndDate`),
  KEY `idxStatus` (`MSStatusID`),
  KEY `idxMSGrade` (`MSGradeID`)
) ENGINE=InnoDB AUTO_INCREMENT=125757 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersontoactiongroupitem`
--

DROP TABLE IF EXISTS `tblpersontoactiongroupitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersontoactiongroupitem` (
  `PersonToActionGroupItemID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ActionGroupItemID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`PersonToActionGroupItemID`),
  KEY `idxActionGroupItem` (`ActionGroupItemID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkPersonToAGIItem` FOREIGN KEY (`ActionGroupItemID`) REFERENCES `tblactiongroupitem` (`ActionGroupItemID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPersonToAGIPerson` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersontocommittee`
--

DROP TABLE IF EXISTS `tblpersontocommittee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersontocommittee` (
  `PersonToCommitteeID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CommitteeID` int(10) unsigned NOT NULL,
  `CommitteeRoleID` int(10) unsigned DEFAULT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  `Role` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StartDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  PRIMARY KEY (`PersonToCommitteeID`),
  KEY `idxCommittee` (`CommitteeID`),
  KEY `idxEnd` (`EndDate`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxRole` (`Role`),
  KEY `idxRoleID` (`CommitteeRoleID`),
  KEY `idxStart` (`StartDate`),
  CONSTRAINT `fkPersonToCommitteeC` FOREIGN KEY (`CommitteeID`) REFERENCES `tblcommittee` (`CommitteeID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPersonToCommitteeP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPersonToCommitteeR` FOREIGN KEY (`CommitteeRoleID`) REFERENCES `tblcommitteerole` (`CommitteeRoleID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersontodirectory`
--

DROP TABLE IF EXISTS `tblpersontodirectory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersontodirectory` (
  `PersonToDirectoryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `WSCategoryID` int(10) unsigned NOT NULL,
  `ShowElement` enum('name','membership','email','online','job','study','expertise') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`PersonToDirectoryID`),
  KEY `idxCategory` (`WSCategoryID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkPersonToDirectoryC` FOREIGN KEY (`WSCategoryID`) REFERENCES `tblwscategory` (`WSCategoryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPersonToDirectoryP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16566 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersontoemailtemplate`
--

DROP TABLE IF EXISTS `tblpersontoemailtemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersontoemailtemplate` (
  `PersonToEmailTemplateID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `Mnemonic` varchar(24) COLLATE utf8_unicode_ci NOT NULL,
  `Recorded` datetime NOT NULL,
  PRIMARY KEY (`PersonToEmailTemplateID`),
  KEY `idxMnemonic` (`Mnemonic`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxRecorded` (`Recorded`)
) ENGINE=InnoDB AUTO_INCREMENT=61867 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersontogrant`
--

DROP TABLE IF EXISTS `tblpersontogrant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersontogrant` (
  `PersonToGrantID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `GrantID` int(10) unsigned NOT NULL,
  `Awarded` datetime NOT NULL,
  `Comment` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`PersonToGrantID`),
  KEY `idxAwarded` (`Awarded`),
  KEY `idxGrant` (`GrantID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkPersonToGrantG` FOREIGN KEY (`GrantID`) REFERENCES `tblgrant` (`GrantID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPersonToGrantP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersontoonline`
--

DROP TABLE IF EXISTS `tblpersontoonline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersontoonline` (
  `PersonToOnlineID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `OnlineID` int(10) unsigned NOT NULL,
  `URL` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`PersonToOnlineID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxOnline` (`OnlineID`),
  CONSTRAINT `fkPersonToOnlineO` FOREIGN KEY (`OnlineID`) REFERENCES `tblonline` (`OnlineID`) ON UPDATE CASCADE,
  CONSTRAINT `fkPersonToOnlineP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersontopersongroup`
--

DROP TABLE IF EXISTS `tblpersontopersongroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersontopersongroup` (
  `PersonToPersonGroupID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `PersonGroupID` int(10) unsigned NOT NULL,
  `Comment` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`PersonToPersonGroupID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxPersonGroup` (`PersonGroupID`),
  CONSTRAINT `fkPersonToPGGroup` FOREIGN KEY (`PersonGroupID`) REFERENCES `tblpersongroup` (`PersonGroupID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPersonToPGPerson` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersontophone`
--

DROP TABLE IF EXISTS `tblpersontophone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersontophone` (
  `PersonToPhoneID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PhoneTypeID` tinyint(3) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  `PhoneNo` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`PersonToPhoneID`),
  KEY `idxType` (`PhoneTypeID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxPhoneNo` (`PhoneNo`),
  CONSTRAINT `fkPersonToPhonePe` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPersonToPhonePh` FOREIGN KEY (`PhoneTypeID`) REFERENCES `tblphonetype` (`PhoneTypeID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28260 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersontosector`
--

DROP TABLE IF EXISTS `tblpersontosector`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersontosector` (
  `PersonToSectorID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `SectorID` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`PersonToSectorID`),
  UNIQUE KEY `idxUnique` (`PersonID`,`SectorID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxSector` (`SectorID`),
  CONSTRAINT `fkPersonSectorP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPersonSectorS` FOREIGN KEY (`SectorID`) REFERENCES `tblsector` (`SectorID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpersontosubject`
--

DROP TABLE IF EXISTS `tblpersontosubject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpersontosubject` (
  `PersonToSubjectID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `SubjectID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`PersonToSubjectID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxSubject` (`SubjectID`),
  CONSTRAINT `fkPersonToSubjectP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPersonToSubjectS` FOREIGN KEY (`SubjectID`) REFERENCES `tblsubject` (`SubjectID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblphonetype`
--

DROP TABLE IF EXISTS `tblphonetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblphonetype` (
  `PhoneTypeID` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `PhoneType` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `IsMobile` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Icon` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`PhoneTypeID`),
  KEY `idxMobile` (`IsMobile`),
  KEY `idxType` (`PhoneType`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblplaceofstudy`
--

DROP TABLE IF EXISTS `tblplaceofstudy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblplaceofstudy` (
  `PlaceOfStudyID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `PlaceOfStudyDesc` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`PlaceOfStudyID`),
  KEY `idxDesc` (`PlaceOfStudyDesc`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblplaceofwork`
--

DROP TABLE IF EXISTS `tblplaceofwork`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblplaceofwork` (
  `PlaceOfWorkID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `PlaceOfWorkDesc` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `PlaceOfWorkParentID` smallint(5) unsigned DEFAULT NULL,
  `PlaceOfWorkOrder` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`PlaceOfWorkID`),
  KEY `idxDesc` (`PlaceOfWorkDesc`),
  KEY `idxOrder` (`PlaceOfWorkOrder`),
  KEY `idxParent` (`PlaceOfWorkParentID`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpublication`
--

DROP TABLE IF EXISTS `tblpublication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpublication` (
  `PublicationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PublicationType` enum('paper','online','email','sms') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'email',
  `PublicationScope` enum('public','members') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'public',
  `Title` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `Description` text COLLATE utf8_unicode_ci,
  `Flags` set('autosubscribe','nounsubscribe','optin','marketing') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`PublicationID`),
  KEY `idxType` (`PublicationType`),
  KEY `idxScope` (`PublicationScope`),
  KEY `idxTitle` (`Title`),
  KEY `idxFlags` (`Flags`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpublicationrule`
--

DROP TABLE IF EXISTS `tblpublicationrule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpublicationrule` (
  `PublicationRuleID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PublicationID` int(10) unsigned NOT NULL,
  `RuleScope` enum('indmember','indnonmember') COLLATE utf8_unicode_ci NOT NULL,
  `RuleFilter` enum('none','grade') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `FilterValueInt` int(10) unsigned DEFAULT NULL,
  `Net` int(10) unsigned NOT NULL DEFAULT '0',
  `VATRate` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`PublicationRuleID`),
  KEY `idxValue` (`FilterValueInt`),
  KEY `idxPublication` (`PublicationID`),
  KEY `idxFilter` (`RuleFilter`),
  KEY `idxScope` (`RuleScope`),
  CONSTRAINT `fkPublicationRuleP` FOREIGN KEY (`PublicationID`) REFERENCES `tblpublication` (`PublicationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblpublicationtoperson`
--

DROP TABLE IF EXISTS `tblpublicationtoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblpublicationtoperson` (
  `PublicationToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PublicationID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  `Complimentary` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CustomerReference` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Qty` smallint(5) unsigned NOT NULL DEFAULT '1',
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `LastReminder` datetime DEFAULT NULL,
  `Suspended` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`PublicationToPersonID`),
  KEY `idxPublication` (`PublicationID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxStart` (`StartDate`),
  KEY `idxEnd` (`EndDate`),
  KEY `idxLastReminder` (`LastReminder`),
  KEY `idxSuspended` (`Suspended`),
  CONSTRAINT `fkPublicationPersonPe` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkPublicationPersonPu` FOREIGN KEY (`PublicationID`) REFERENCES `tblpublication` (`PublicationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=75041 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblrecentfile`
--

DROP TABLE IF EXISTS `tblrecentfile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblrecentfile` (
  `RecentFileID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DocumentID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned DEFAULT NULL,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`RecentFileID`),
  KEY `idxCreated` (`Created`),
  KEY `idxDocument` (`DocumentID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkRecentFileDocument` FOREIGN KEY (`DocumentID`) REFERENCES `tbldocument` (`DocumentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkRecentFilePerson` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblrecentitem`
--

DROP TABLE IF EXISTS `tblrecentitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblrecentitem` (
  `RecentItemID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Token` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `URL` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `Caption` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `Recorded` datetime NOT NULL,
  PRIMARY KEY (`RecentItemID`),
  UNIQUE KEY `idxUnique` (`Caption`,`Token`),
  KEY `idxRecorded` (`Recorded`),
  KEY `idxToken` (`Token`),
  CONSTRAINT `fkRecentItemToken` FOREIGN KEY (`Token`) REFERENCES `tblauth` (`Token`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblrefereetype`
--

DROP TABLE IF EXISTS `tblrefereetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblrefereetype` (
  `RefereeTypeID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `RefereeTypeDesc` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`RefereeTypeID`),
  KEY `idxDesc` (`RefereeTypeDesc`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblrejoin`
--

DROP TABLE IF EXISTS `tblrejoin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblrejoin` (
  `RejoinID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `WSCategoryID` int(10) unsigned NOT NULL,
  `MSGradeID` smallint(5) unsigned NOT NULL,
  `Created` datetime NOT NULL,
  `LastModified` datetime NOT NULL,
  `RejoinDate` datetime NOT NULL,
  `MSNextRenewal` date NOT NULL,
  `Cancelled` datetime DEFAULT NULL,
  `IsOpen` int(1) unsigned NOT NULL DEFAULT '1',
  `ISO4217` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `ItemNet` int(11) NOT NULL DEFAULT '0',
  `ItemVATRate` smallint(5) NOT NULL DEFAULT '2000',
  `AnchorPersonMSID` int(10) unsigned DEFAULT NULL,
  `Comment` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DiscountID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`RejoinID`),
  KEY `fkRejoinISO4217_idx` (`ISO4217`),
  KEY `idxAnchorPersomMSID` (`AnchorPersonMSID`),
  KEY `idxCancelled` (`Cancelled`),
  KEY `idxCategory` (`WSCategoryID`),
  KEY `idxCreated` (`Created`),
  KEY `idxGrade` (`MSGradeID`),
  KEY `idxOpen` (`IsOpen`),
  KEY `idxRejoinDate` (`RejoinDate`),
  KEY `idxDiscount` (`DiscountID`),
  CONSTRAINT `fkRejoinAnchorPersonMSID` FOREIGN KEY (`AnchorPersonMSID`) REFERENCES `tblpersonms` (`PersonMSID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkRejoinCategory` FOREIGN KEY (`WSCategoryID`) REFERENCES `tblwscategory` (`WSCategoryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkRejoinDiscount` FOREIGN KEY (`DiscountID`) REFERENCES `tbldiscount` (`DiscountID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkRejoinISO4217` FOREIGN KEY (`ISO4217`) REFERENCES `tblcurrency` (`ISO4217`) ON UPDATE CASCADE,
  CONSTRAINT `fkRejoinMSGrade` FOREIGN KEY (`MSGradeID`) REFERENCES `tblmsgrade` (`MSGradeID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblrejointoperson`
--

DROP TABLE IF EXISTS `tblrejointoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblrejointoperson` (
  `RejoinToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `RejoinID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`RejoinToPersonID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxRejoin` (`RejoinID`),
  CONSTRAINT `fkRejoinToPersonP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkRejoinToPersonR` FOREIGN KEY (`RejoinID`) REFERENCES `tblrejoin` (`RejoinID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblrenewal`
--

DROP TABLE IF EXISTS `tblrenewal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblrenewal` (
  `RenewalID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned DEFAULT NULL,
  `OrganisationID` int(10) unsigned DEFAULT NULL,
  `WSCategoryID` int(10) unsigned NOT NULL,
  `MSGradeID` smallint(5) unsigned DEFAULT NULL,
  `Created` datetime NOT NULL,
  `LastModified` datetime NOT NULL,
  `RenewFor` smallint(5) unsigned NOT NULL DEFAULT '1',
  `RenewFlags` set('free') COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `DiscountID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`RenewalID`),
  KEY `idxCategory` (`WSCategoryID`),
  KEY `idxCreated` (`Created`),
  KEY `idxOrganisation` (`OrganisationID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxDiscount` (`DiscountID`),
  KEY `idxGrade` (`MSGradeID`),
  CONSTRAINT `fkRenewalCategory` FOREIGN KEY (`WSCategoryID`) REFERENCES `tblwscategory` (`WSCategoryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkRenewalDiscount` FOREIGN KEY (`DiscountID`) REFERENCES `tbldiscount` (`DiscountID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkRenewalGrade` FOREIGN KEY (`MSGradeID`) REFERENCES `tblmsgrade` (`MSGradeID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12299 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblsector`
--

DROP TABLE IF EXISTS `tblsector`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblsector` (
  `SectorID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `SectorName` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`SectorID`),
  KEY `idxAlpha` (`SectorName`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblsubject`
--

DROP TABLE IF EXISTS `tblsubject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblsubject` (
  `SubjectID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `SubjectCategoryID` int(10) unsigned NOT NULL,
  `Subject` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`SubjectID`),
  KEY `idxCategory` (`SubjectCategoryID`),
  KEY `idxSubject` (`Subject`),
  CONSTRAINT `fkSubjectToSubjectCategory` FOREIGN KEY (`SubjectCategoryID`) REFERENCES `tblsubjectcategory` (`SubjectCategoryID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblsubjectcategory`
--

DROP TABLE IF EXISTS `tblsubjectcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblsubjectcategory` (
  `SubjectCategoryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `SubjectCategory` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `SortOrder` int(10) unsigned NOT NULL,
  PRIMARY KEY (`SubjectCategoryID`),
  KEY `idxCategory` (`SubjectCategory`),
  KEY `idxSort` (`SortOrder`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblsyslog`
--

DROP TABLE IF EXISTS `tblsyslog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblsyslog` (
  `SysLogID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Recorded` datetime NOT NULL,
  `EntryKind` enum('info','success','warning','danger','error') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'info',
  `PersonID` int(10) unsigned DEFAULT NULL,
  `Caption` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `Data` text COLLATE utf8_unicode_ci,
  `Expiry` datetime DEFAULT NULL,
  `IsSystem` int(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`SysLogID`),
  KEY `idxRecorded` (`Recorded`),
  KEY `idxExpiry` (`Expiry`),
  KEY `fkSyslogPersonPersonID_idx` (`PersonID`),
  CONSTRAINT `fkSyslogPersonPersonID` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=59926 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbltransactiontype`
--

DROP TABLE IF EXISTS `tbltransactiontype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbltransactiontype` (
  `TransactionTypeID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `TransactionType` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Analysis` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `VATAnalysis` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`TransactionTypeID`),
  KEY `idxAlpha` (`TransactionType`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbltransfer`
--

DROP TABLE IF EXISTS `tbltransfer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbltransfer` (
  `TransferID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `WSCategoryID` int(10) unsigned NOT NULL,
  `MSGradeID` smallint(5) unsigned NOT NULL,
  `NewMSGradeID` smallint(5) unsigned NOT NULL,
  `Created` datetime NOT NULL,
  `LastModified` datetime NOT NULL,
  `Cancelled` datetime DEFAULT NULL,
  `IsOpen` int(1) unsigned NOT NULL DEFAULT '1',
  `ISO4217` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `ItemNet` int(11) NOT NULL DEFAULT '0',
  `ItemVATRate` smallint(5) NOT NULL DEFAULT '2000',
  `DiscountID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`TransferID`),
  KEY `fkTransferISO4217_idx` (`ISO4217`),
  KEY `idxCancelled` (`Cancelled`),
  KEY `idxCategory` (`WSCategoryID`),
  KEY `idxCreated` (`Created`),
  KEY `idxGrade` (`MSGradeID`),
  KEY `idxNewGrade` (`NewMSGradeID`),
  KEY `idxOpen` (`IsOpen`),
  KEY `idxDiscount` (`DiscountID`),
  CONSTRAINT `fkTransferCategory` FOREIGN KEY (`WSCategoryID`) REFERENCES `tblwscategory` (`WSCategoryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkTransferDiscount` FOREIGN KEY (`DiscountID`) REFERENCES `tbldiscount` (`DiscountID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fkTransferISO4217` FOREIGN KEY (`ISO4217`) REFERENCES `tblcurrency` (`ISO4217`) ON UPDATE CASCADE,
  CONSTRAINT `fkTransferMSGrade` FOREIGN KEY (`MSGradeID`) REFERENCES `tblmsgrade` (`MSGradeID`) ON UPDATE CASCADE,
  CONSTRAINT `fkTransferNewMSGrade` FOREIGN KEY (`NewMSGradeID`) REFERENCES `tblmsgrade` (`MSGradeID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbltransfertoperson`
--

DROP TABLE IF EXISTS `tbltransfertoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbltransfertoperson` (
  `TransferToPerson` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `TransferID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`TransferToPerson`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxTransfer` (`TransferID`),
  CONSTRAINT `fkTransferToPersonP` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkTransferToPersonT` FOREIGN KEY (`TransferID`) REFERENCES `tbltransfer` (`TransferID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblusersetting`
--

DROP TABLE IF EXISTS `tblusersetting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblusersetting` (
  `UserSettingID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` int(10) unsigned NOT NULL,
  `RecentItems` tinyint(2) unsigned NOT NULL DEFAULT '5',
  `NoDatepickerAutoFocus` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `SortByFirstname` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `OpenNewWindow` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `DefaultEmail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`UserSettingID`),
  KEY `idxPerson` (`PersonID`),
  CONSTRAINT `fkUserSettingPersonID` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblworkflowitem`
--

DROP TABLE IF EXISTS `tblworkflowitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblworkflowitem` (
  `WorkflowItemID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Priority` int(1) unsigned NOT NULL DEFAULT '0',
  `PersonID` int(10) unsigned DEFAULT NULL,
  `Created` datetime NOT NULL,
  `LastModified` datetime NOT NULL,
  `LastAssignment` datetime DEFAULT NULL,
  PRIMARY KEY (`WorkflowItemID`),
  KEY `idxPriority` (`Priority`),
  KEY `idxAssigned` (`PersonID`),
  KEY `idxLastModified` (`LastModified`),
  CONSTRAINT `fkWorkflowAssignedTo` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblworkflowitemtocategory`
--

DROP TABLE IF EXISTS `tblworkflowitemtocategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblworkflowitemtocategory` (
  `WorkflowItemToCategoryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `WorkflowItemID` int(10) unsigned NOT NULL,
  `WSCategoryID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`WorkflowItemToCategoryID`),
  KEY `idxWorkflowItem` (`WorkflowItemID`),
  KEY `idxWSCategory` (`WSCategoryID`),
  CONSTRAINT `fkWFItemToCatWFI` FOREIGN KEY (`WorkflowItemID`) REFERENCES `tblworkflowitem` (`WorkflowItemID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkWFItemToCatWSC` FOREIGN KEY (`WSCategoryID`) REFERENCES `tblwscategory` (`WSCategoryID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblworkflowitemtoorganisation`
--

DROP TABLE IF EXISTS `tblworkflowitemtoorganisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblworkflowitemtoorganisation` (
  `WorkflowItemToOrganisationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `WorkflowItemID` int(10) unsigned NOT NULL,
  `OrganisationID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`WorkflowItemToOrganisationID`),
  KEY `idxWorkflowItem` (`WorkflowItemID`),
  KEY `idxOrganisation` (`OrganisationID`),
  CONSTRAINT `fkWFItemToOrgOrg` FOREIGN KEY (`OrganisationID`) REFERENCES `tblorganisation` (`OrganisationID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkWFItemToOrgWFI` FOREIGN KEY (`WorkflowItemID`) REFERENCES `tblworkflowitem` (`WorkflowItemID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblworkflowitemtoperson`
--

DROP TABLE IF EXISTS `tblworkflowitemtoperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblworkflowitemtoperson` (
  `WorkflowItemToPersonID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `WorkflowItemID` int(10) unsigned NOT NULL,
  `PersonID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`WorkflowItemToPersonID`),
  KEY `idxPerson` (`PersonID`),
  KEY `idxWorkflowItem` (`WorkflowItemID`),
  CONSTRAINT `fkWFItemToPersonPerson` FOREIGN KEY (`PersonID`) REFERENCES `tblperson` (`PersonID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkWFItemToPersonWFI` FOREIGN KEY (`WorkflowItemID`) REFERENCES `tblworkflowitem` (`WorkflowItemID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblworkrole`
--

DROP TABLE IF EXISTS `tblworkrole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblworkrole` (
  `WorkRoleID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `WorkRole` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`WorkRoleID`),
  KEY `idxRole` (`WorkRole`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tblwscategory`
--

DROP TABLE IF EXISTS `tblwscategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tblwscategory` (
  `WSCategoryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CategorySelector` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `CategoryName` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `CategoryIcon` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `WorkflowDefault` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`WSCategoryID`),
  KEY `idxDefault` (`WorkflowDefault`),
  KEY `idxAlpha` (`CategoryName`),
  KEY `idxSelector` (`CategorySelector`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-08-09 13:12:54
