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
-- Table structure for table `accFiles`
--

DROP TABLE IF EXISTS `accFiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accFiles` (
  `accfile_id` int(11) NOT NULL AUTO_INCREMENT,
  `accfile_accountId` smallint(5) unsigned NOT NULL,
  `accfile_name` varchar(100) NOT NULL,
  `accfile_type` varchar(100) NOT NULL,
  `accfile_size` int(11) NOT NULL,
  `accfile_content` mediumblob NOT NULL,
  `accfile_extension` varchar(10) NOT NULL,
  PRIMARY KEY (`accfile_id`),
  KEY `IDX_accountId` (`accfile_accountId`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accGroups`
--

DROP TABLE IF EXISTS `accGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accGroups` (
  `accgroup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `accgroup_accountId` int(10) unsigned NOT NULL,
  `accgroup_groupId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`accgroup_id`),
  KEY `IDX_accountId` (`accgroup_accountId`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accHistory`
--

DROP TABLE IF EXISTS `accHistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accHistory` (
  `acchistory_id` int(11) NOT NULL AUTO_INCREMENT,
  `acchistory_accountId` smallint(5) unsigned NOT NULL,
  `acchistory_userGroupId` tinyint(3) unsigned NOT NULL,
  `acchistory_userId` tinyint(3) unsigned NOT NULL,
  `acchistory_userEditId` tinyint(3) unsigned NOT NULL,
  `acchistory_customerId` tinyint(3) unsigned NOT NULL,
  `acchistory_name` varchar(255) NOT NULL,
  `acchistory_categoryId` tinyint(3) unsigned NOT NULL,
  `acchistory_login` varchar(50) NOT NULL,
  `acchistory_url` varchar(255) DEFAULT NULL,
  `acchistory_pass` varbinary(255) NOT NULL,
  `acchistory_IV` varbinary(32) NOT NULL,
  `acchistory_notes` text NOT NULL,
  `acchistory_countView` int(10) unsigned NOT NULL DEFAULT '0',
  `acchistory_countDecrypt` int(10) unsigned NOT NULL DEFAULT '0',
  `acchistory_dateAdd` datetime NOT NULL,
  `acchistory_dateEdit` datetime NOT NULL,
  `acchistory_isModify` bit(1) DEFAULT NULL,
  `acchistory_isDeleted` bit(1) DEFAULT NULL,
  `acchistory_mPassHash` varbinary(128) NOT NULL,
  `accHistory_otherUserEdit` bit(1) DEFAULT b'0',
  `accHistory_otherGroupEdit` bit(1) DEFAULT b'0',
  PRIMARY KEY (`acchistory_id`),
  KEY `IDX_accountId` (`acchistory_accountId`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accUsers`
--

DROP TABLE IF EXISTS `accUsers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accUsers` (
  `accuser_id` int(11) NOT NULL AUTO_INCREMENT,
  `accuser_accountId` int(10) unsigned NOT NULL,
  `accuser_userId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`accuser_id`),
  KEY `idx_account` (`accuser_accountId`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `account_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `account_userGroupId` tinyint(3) unsigned NOT NULL,
  `account_userId` tinyint(3) unsigned NOT NULL,
  `account_userEditId` tinyint(3) unsigned NOT NULL,
  `account_customerId` int(10) unsigned NOT NULL,
  `account_name` varchar(50) NOT NULL,
  `account_categoryId` tinyint(3) unsigned NOT NULL,
  `account_login` varchar(50) DEFAULT NULL,
  `account_url` varchar(255) DEFAULT NULL,
  `account_pass` varbinary(255) NOT NULL,
  `account_IV` varbinary(32) NOT NULL,
  `account_notes` text,
  `account_countView` int(10) unsigned NOT NULL DEFAULT '0',
  `account_countDecrypt` int(10) unsigned NOT NULL DEFAULT '0',
  `account_dateAdd` datetime NOT NULL,
  `account_dateEdit` datetime NOT NULL,
  `account_otherGroupEdit` bit(1) DEFAULT b'0',
  `account_otherUserEdit` bit(1) DEFAULT b'0',
  PRIMARY KEY (`account_id`),
  KEY `IDX_categoryId` (`account_categoryId`),
  KEY `IDX_userId` (`account_userGroupId`,`account_userId`),
  KEY `IDX_customerId` (`account_customerId`),
  FULLTEXT KEY `IDX_searchTxt` (`account_name`,`account_login`,`account_url`,`account_notes`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `category_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `category_description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `config_parameter` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `config_value` varchar(128) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  UNIQUE KEY `vacParameter` (`config_parameter`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `customer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) NOT NULL,
  `customer_hash` varbinary(40) NOT NULL,
  `customer_description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`customer_id`),
  KEY `IDX_name` (`customer_name`,`customer_hash`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_date` int(10) unsigned NOT NULL,
  `log_login` varchar(25) NOT NULL,
  `log_userId` tinyint(3) unsigned NOT NULL,
  `log_ipAddress` varchar(45) NOT NULL,
  `log_action` varchar(50) NOT NULL,
  `log_description` text NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usrData`
--

DROP TABLE IF EXISTS `usrData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usrData` (
  `user_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(80) NOT NULL,
  `user_groupId` tinyint(3) unsigned NOT NULL,
  `user_secGroupId` tinyint(3) unsigned DEFAULT NULL,
  `user_login` varchar(50) NOT NULL,
  `user_pass` varbinary(255) NOT NULL,
  `user_mPass` varbinary(255) DEFAULT NULL,
  `user_mIV` varbinary(32) NOT NULL,
  `user_email` varchar(80) DEFAULT NULL,
  `user_notes` text,
  `user_count` int(10) unsigned NOT NULL DEFAULT '0',
  `user_profileId` tinyint(4) NOT NULL,
  `user_lastLogin` datetime DEFAULT NULL,
  `user_lastUpdate` datetime DEFAULT NULL,
  `user_lastUpdateMPass` int(11) unsigned NOT NULL DEFAULT '0',
  `user_isAdminApp` bit(1) NOT NULL DEFAULT b'0',
  `user_isAdminAcc` bit(1) NOT NULL DEFAULT b'0',
  `user_isLdap` bit(1) NOT NULL DEFAULT b'0',
  `user_isDisabled` bit(1) NOT NULL DEFAULT b'0',
  `user_hashSalt` varbinary(40) NOT NULL,
  `user_isMigrate` bit(1) DEFAULT b'0',
  `user_isChangePass` bit(1) DEFAULT b'0',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `IDX_login` (`user_login`),
  KEY `IDX_pass` (`user_pass`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usrGroups`
--

DROP TABLE IF EXISTS `usrGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usrGroups` (
  `usergroup_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `usergroup_name` varchar(50) NOT NULL,
  `usergroup_description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`usergroup_id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usrPassRecover`
--

DROP TABLE IF EXISTS `usrPassRecover`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usrPassRecover` (
  `userpassr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userpassr_userId` smallint(5) unsigned NOT NULL,
  `userpassr_hash` varbinary(40) NOT NULL,
  `userpassr_date` int(10) unsigned NOT NULL,
  `userpassr_used` bit(1) NOT NULL,
  PRIMARY KEY (`userpassr_id`),
  KEY `IDX_userId` (`userpassr_userId`,`userpassr_date`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usrProfiles`
--

DROP TABLE IF EXISTS `usrProfiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usrProfiles` (
  `userprofile_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `userprofile_name` varchar(45) NOT NULL,
  `userProfile_profile` BLOB NOT NULL,
  PRIMARY KEY (`userprofile_id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
