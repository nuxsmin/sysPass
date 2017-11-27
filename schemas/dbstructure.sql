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

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `customer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) NOT NULL,
  `customer_hash` varbinary(40) NOT NULL,
  `customer_description` varchar(255) DEFAULT NULL,
  `customer_isGlobal` bit DEFAULT b'0',
  PRIMARY KEY (`customer_id`),
  KEY `IDX_name` (`customer_name`,`customer_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `category_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `category_hash` varbinary(40) NOT NULL,
  `category_description` varchar(255) DEFAULT NULL
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `usrGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usrGroups` (
  `usergroup_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `usergroup_name` varchar(50) NOT NULL,
  `usergroup_description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`usergroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `usrProfiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usrProfiles` (
  `userprofile_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `userprofile_name` varchar(45) NOT NULL,
  `userProfile_profile` blob NOT NULL,
  PRIMARY KEY (`userprofile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `usrData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usrData` (
  `user_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(80) NOT NULL,
  `user_groupId` smallint(3) unsigned NOT NULL,
  `user_secGroupId` smallint(3) unsigned DEFAULT NULL,
  `user_login` varchar(50) NOT NULL,
  `user_ssoLogin` varchar(100) null,
  `user_pass` varbinary(1000) NOT NULL,
  `user_mPass` varbinary(1000) DEFAULT NULL,
  `user_mKey` varbinary(1000) NOT NULL,
  `user_email` varchar(80) DEFAULT NULL,
  `user_notes` text,
  `user_count` int(10) unsigned NOT NULL DEFAULT '0',
  `user_profileId` smallint(5) unsigned NOT NULL,
  `user_lastLogin` datetime DEFAULT NULL,
  `user_lastUpdate` datetime DEFAULT NULL,
  `user_lastUpdateMPass` int(11) unsigned NOT NULL DEFAULT '0',
  `user_isAdminApp` bit(1) DEFAULT b'0',
  `user_isAdminAcc` bit(1) DEFAULT b'0',
  `user_isLdap` bit(1) DEFAULT b'0',
  `user_isDisabled` bit(1) DEFAULT b'0',
  `user_hashSalt` varbinary(128) NOT NULL,
  `user_isMigrate` bit(1) DEFAULT b'0',
  `user_isChangePass` bit(1) DEFAULT b'0',
  `user_isChangedPass` bit(1) DEFAULT b'0',
  `user_preferences` blob,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `IDX_login` (`user_login`, `user_ssoLogin`),
  KEY `IDX_pass` (`user_pass`),
  KEY `fk_usrData_groups_id_idx` (`user_groupId`),
  KEY `fk_usrData_profiles_id_idx` (`user_profileId`),
  CONSTRAINT `fk_usrData_groups_id` FOREIGN KEY (`user_groupId`) REFERENCES `usrGroups` (`usergroup_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_usrData_profiles_id` FOREIGN KEY (`user_profileId`) REFERENCES `usrProfiles` (`userprofile_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `account_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `account_userGroupId` smallint(5) unsigned NOT NULL,
  `account_userId` smallint(5) unsigned NOT NULL,
  `account_userEditId` smallint(5) unsigned NOT NULL,
  `account_customerId` int(10) unsigned NOT NULL,
  `account_name` varchar(50) NOT NULL,
  `account_categoryId` smallint(5) unsigned NOT NULL,
  `account_login` varchar(50) DEFAULT NULL,
  `account_url` varchar(255) DEFAULT NULL,
  `account_pass` varbinary(1000) NOT NULL,
  `account_key` varbinary(1000) NOT NULL,
  `account_notes` text,
  `account_countView` int(10) unsigned NOT NULL DEFAULT '0',
  `account_countDecrypt` int(10) unsigned NOT NULL DEFAULT '0',
  `account_dateAdd` datetime NOT NULL,
  `account_dateEdit` datetime DEFAULT NULL,
  `account_otherGroupEdit` bit(1) DEFAULT b'0',
  `account_otherUserEdit` bit(1) DEFAULT b'0',
  `account_isPrivate` bit(1) DEFAULT b'0',
  `account_isPrivateGroup` BIT(1) NULL DEFAULT b'0',
  `account_passDate` int(11) unsigned DEFAULT NULL,
  `account_passDateChange` int(11) unsigned DEFAULT NULL,
  `account_parentId` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`account_id`),
  KEY `IDX_categoryId` (`account_categoryId`),
  KEY `IDX_userId` (`account_userGroupId`,`account_userId`),
  KEY `IDX_customerId` (`account_customerId`),
  KEY `fk_accounts_user_id` (`account_userId`),
  KEY `fk_accounts_user_edit_id` (`account_userEditId`),
  CONSTRAINT `fk_accounts_user_id` FOREIGN KEY (`account_userId`) REFERENCES `usrData` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_accounts_category_id` FOREIGN KEY (`account_categoryId`) REFERENCES `categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_accounts_user_edit_id` FOREIGN KEY (`account_userEditId`) REFERENCES `usrData` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_accounts_customer_id` FOREIGN KEY (`account_customerId`) REFERENCES `customers` (`customer_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_accounts_userGroup_id` FOREIGN KEY (`account_userGroupId`) REFERENCES `usrGroups` (`usergroup_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `accFavorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accFavorites` (
  `accfavorite_accountId` smallint(5) unsigned NOT NULL,
  `accfavorite_userId` smallint(5) unsigned NOT NULL,
  KEY `fk_accFavorites_accounts_idx` (`accfavorite_accountId`),
  KEY `fk_accFavorites_users_idx` (`accfavorite_userId`),
  KEY `search_idx` (`accfavorite_accountId`,`accfavorite_userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `accFile_thumb` mediumblob,
  PRIMARY KEY (`accfile_id`),
  KEY `IDX_accountId` (`accfile_accountId`),
  CONSTRAINT `fk_accFiles_accounts_id` FOREIGN KEY (`accfile_accountId`) REFERENCES `accounts` (`account_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `accGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accGroups` (
  `accgroup_accountId` smallint(5) unsigned NOT NULL,
  `accgroup_groupId` smallint(5) unsigned NOT NULL,
  KEY `IDX_accountId` (`accgroup_accountId`),
  KEY `fk_accGroups_groups_id_idx` (`accgroup_groupId`),
  CONSTRAINT `fk_accGroups_accounts_id` FOREIGN KEY (`accgroup_accountId`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_accGroups_groups_id` FOREIGN KEY (`accgroup_groupId`) REFERENCES `usrGroups` (`usergroup_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `accHistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accHistory` (
  `acchistory_id` int(11) NOT NULL AUTO_INCREMENT,
  `acchistory_accountId` smallint(5) unsigned NOT NULL,
  `acchistory_userGroupId` smallint(5) unsigned NOT NULL,
  `acchistory_userId` smallint(5) unsigned NOT NULL,
  `acchistory_userEditId` smallint(5) unsigned NOT NULL,
  `acchistory_customerId` int(10) unsigned NOT NULL,
  `acchistory_name` varchar(255) NOT NULL,
  `acchistory_categoryId` smallint(5) unsigned NOT NULL,
  `acchistory_login` varchar(50) NOT NULL,
  `acchistory_url` varchar(255) DEFAULT NULL,
  `acchistory_pass` varbinary(1000) NOT NULL,
  `acchistory_key` varbinary(1000) NOT NULL,
  `acchistory_notes` text NOT NULL,
  `acchistory_countView` int(10) unsigned NOT NULL DEFAULT '0',
  `acchistory_countDecrypt` int(10) unsigned NOT NULL DEFAULT '0',
  `acchistory_dateAdd` datetime NOT NULL,
  `acchistory_dateEdit` datetime DEFAULT NULL,
  `acchistory_isModify` bit(1) DEFAULT NULL,
  `acchistory_isDeleted` bit(1) DEFAULT NULL,
  `acchistory_mPassHash` varbinary(255) NOT NULL,
  `accHistory_otherUserEdit` bit(1) DEFAULT b'0',
  `accHistory_otherGroupEdit` bit(1) DEFAULT b'0',
  `accHistory_passDate` int(10) unsigned DEFAULT NULL,
  `accHistory_passDateChange` int(10) unsigned DEFAULT NULL,
  `accHistory_parentId` smallint(5) unsigned DEFAULT NULL,
  `accHistory_isPrivate` BIT(1) NULL DEFAULT b'0',
  `accHistory_isPrivateGroup` BIT(1) NULL DEFAULT b'0',
  PRIMARY KEY (`acchistory_id`),
  KEY `IDX_accountId` (`acchistory_accountId`),
  KEY `fk_accHistory_users_edit_id_idx` (`acchistory_userEditId`),
  KEY `fk_accHistory_users_id` (`acchistory_userId`),
  KEY `fk_accHistory_categories_id` (`acchistory_categoryId`),
  KEY `fk_accHistory_customers_id` (`acchistory_customerId`),
  CONSTRAINT `fk_accHistory_users_id` FOREIGN KEY (`acchistory_userId`) REFERENCES `usrData` (`user_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `fk_accHistory_users_edit_id` FOREIGN KEY (`acchistory_userEditId`) REFERENCES `usrData` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_accHistory_category_id` FOREIGN KEY (`acchistory_categoryId`) REFERENCES `categories` (`category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_accHistory_customer_id` FOREIGN KEY (`acchistory_customerId`) REFERENCES `customers` (`customer_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_accHistory_userGroup_id` FOREIGN KEY (`acchistory_userGroupId`) REFERENCES `usrGroups` (`usergroup_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `tag_id` int unsigned NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(45) NOT NULL,
  `tag_hash` binary(40) NOT NULL,
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `tag_hash_UNIQUE` (`tag_hash`),
  KEY `IDX_name` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `accTags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accTags` (
  `acctag_accountId` smallint(10) unsigned NOT NULL,
  `acctag_tagId` int(10) unsigned NOT NULL,
  KEY `IDX_id` (`acctag_accountId`),
  KEY `fk_accTags_tags_id_idx` (`acctag_tagId`),
  CONSTRAINT `fk_accTags_accounts_id` FOREIGN KEY (`acctag_accountId`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_accTags_tags_id` FOREIGN KEY (`acctag_tagId`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `accUsers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accUsers` (
  `accuser_accountId` smallint(5) unsigned NOT NULL,
  `accuser_userId` smallint(5) unsigned NOT NULL,
  KEY `idx_account` (`accuser_accountId`),
  KEY `fk_accUsers_users_id_idx` (`accuser_userId`),
  CONSTRAINT `fk_accUsers_accounts_id` FOREIGN KEY (`accuser_accountId`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_accUsers_users_id` FOREIGN KEY (`accuser_userId`) REFERENCES `usrData` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `authTokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authTokens` (
  `authtoken_id` int(11) NOT NULL AUTO_INCREMENT,
  `authtoken_userId` smallint(5) unsigned NOT NULL,
  `authtoken_token` varbinary(100) NOT NULL,
  `authtoken_actionId` smallint(5) unsigned NOT NULL,
  `authtoken_createdBy` smallint(5) unsigned NOT NULL,
  `authtoken_startDate` int(10) unsigned NOT NULL,
  `authtoken_vault` varbinary(2000) NULL,
  `authtoken_hash` varbinary(1000) NULL,
  PRIMARY KEY (`authtoken_id`),
  UNIQUE KEY `unique_authtoken_id` (`authtoken_id`),
  KEY `IDX_checkToken` (`authtoken_userId`,`authtoken_actionId`,`authtoken_token`),
  KEY `fk_authTokens_users_id_idx` (`authtoken_userId`,`authtoken_createdBy`),
  KEY `fk_authTokens_users_createdby_id` (`authtoken_createdBy`),
  CONSTRAINT `fk_authTokens_user_id` FOREIGN KEY (`authtoken_userId`) REFERENCES `usrData` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_authTokens_createdBy_id` FOREIGN KEY (`authtoken_createdBy`) REFERENCES `usrData` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `config_parameter` varchar(50) NOT NULL,
  `config_value` varchar(2000) DEFAULT NULL,
  UNIQUE KEY `vacParameter` (`config_parameter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `customFieldsData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customFieldsData` (
  `customfielddata_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customfielddata_moduleId` smallint(5) unsigned NOT NULL,
  `customfielddata_itemId` int(10) unsigned NOT NULL,
  `customfielddata_defId` int(10) unsigned NOT NULL,
  `customfielddata_data` longblob,
  `customfielddata_key` varbinary(1000) DEFAULT NULL,
  PRIMARY KEY (`customfielddata_id`),
  KEY `IDX_DEFID` (`customfielddata_defId`),
  KEY `IDX_DELETE` (`customfielddata_itemId`,`customfielddata_moduleId`),
  KEY `IDX_UPDATE` (`customfielddata_moduleId`,`customfielddata_itemId`,`customfielddata_defId`),
  KEY `IDX_ITEM` (`customfielddata_itemId`),
  KEY `IDX_MODULE` (`customfielddata_moduleId`),
  CONSTRAINT `fk_customFieldsData_def_id` FOREIGN KEY (`customfielddata_defId`) REFERENCES `customFieldsDef` (`customfielddef_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `customFieldsDef`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customFieldsDef` (
  `customfielddef_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customfielddef_module` smallint(5) unsigned NOT NULL,
  `customfielddef_field` blob NOT NULL,
  PRIMARY KEY (`customfielddef_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `log_id` int unsigned NOT NULL AUTO_INCREMENT,
  `log_date` int(10) unsigned NOT NULL,
  `log_login` varchar(25) NOT NULL,
  `log_userId` smallint(5) unsigned NOT NULL,
  `log_ipAddress` varchar(45) NOT NULL,
  `log_action` varchar(50) NOT NULL,
  `log_description` text,
  `log_level` varchar(20) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `publicLinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `publicLinks` (
  `publicLink_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `publicLink_itemId` int UNSIGNED DEFAULT NULL,
  `publicLink_hash` varbinary(100) NOT NULL,
  `publicLink_linkData` longblob,
  PRIMARY KEY (`publicLink_id`),
  UNIQUE KEY `IDX_hash` (`publicLink_hash`),
  UNIQUE KEY `unique_publicLink_hash` (`publicLink_hash`),
  UNIQUE KEY `unique_publicLink_accountId` (`publicLink_itemId`),
  KEY `IDX_itemId` (`publicLink_itemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `usrPassRecover`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usrPassRecover` (
  `userpassr_id` int unsigned NOT NULL AUTO_INCREMENT,
  `userpassr_userId` smallint(5) unsigned NOT NULL,
  `userpassr_hash` varbinary(40) NOT NULL,
  `userpassr_date` int unsigned NOT NULL,
  `userpassr_used` bit(1) DEFAULT b'0',
  PRIMARY KEY (`userpassr_id`),
  KEY `IDX_userId` (`userpassr_userId`,`userpassr_date`),
  CONSTRAINT `fk_usrPassRecover_users` FOREIGN KEY (`userpassr_userId`) REFERENCES `usrData` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `usrToGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usrToGroups` (
  `usertogroup_userId` smallint(5) unsigned NOT NULL,
  `usertogroup_groupId` smallint(5) unsigned NOT NULL,
  KEY `IDX_usertogroup_userId` (`usertogroup_userId`),
  KEY `fk_usrToGroups_groups_id_idx` (`usertogroup_groupId`),
  CONSTRAINT `fk_usrToGroups_groups_id` FOREIGN KEY (`usertogroup_groupId`) REFERENCES `usrGroups` (`usergroup_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usrToGroups_users_id` FOREIGN KEY (`usertogroup_userId`) REFERENCES `usrData` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `plugins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugins` (
  `plugin_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plugin_name` VARCHAR(100) NOT NULL,
  `plugin_data` VARBINARY(5000) NULL,
  `plugin_enabled` BIT(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`plugin_id`),
  UNIQUE INDEX `plugin_name_UNIQUE` (`plugin_name` ASC)
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notices` (
  `notice_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `notice_type` VARCHAR(100) NULL,
  `notice_component` VARCHAR(100) NOT NULL,
  `notice_description` VARCHAR(500) NOT NULL,
  `notice_date` INT UNSIGNED NOT NULL,
  `notice_checked` BIT(1) NULL DEFAULT b'0',
  `notice_userId` SMALLINT(5) UNSIGNED NULL,
  `notice_sticky` BIT(1) NULL DEFAULT b'0',
  `notice_onlyAdmin` BIT(1) NULL DEFAULT b'0',
  PRIMARY KEY (`notice_id`),
  INDEX `IDX_userId` (`notice_userId` ASC, `notice_checked` ASC, `notice_date` ASC),
  INDEX `IDX_component` (`notice_component` ASC, `notice_date` ASC, `notice_checked` ASC, `notice_userId` ASC)
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `track`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `track` (
  `track_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `track_userId` SMALLINT(5) UNSIGNED NULL,
  `track_source` VARCHAR(100) NOT NULL,
  `track_time` INT UNSIGNED NOT NULL,
  `track_ipv4` BINARY(4) NOT NULL,
  `track_ipv6` BINARY(16) NULL,
  PRIMARY KEY (`track_id`),
  INDEX `IDX_userId` (`track_userId` ASC),
  INDEX `IDX_time-ip-source` (`track_time` ASC, `track_ipv4` ASC, `track_ipv6` ASC, `track_source` ASC)
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actions` (
  `action_id` SMALLINT(5) UNSIGNED NOT NULL,
  `action_name` VARCHAR(50) NOT NULL,
  `action_text` VARCHAR(100) NOT NULL,
  `action_route` VARCHAR(100),
  PRIMARY KEY (`action_id`, `action_name`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `account_data_v`;
CREATE OR REPLACE ALGORITHM = UNDEFINED DEFINER = CURRENT_USER SQL SECURITY DEFINER VIEW `account_data_v` AS
    SELECT
        `accounts`.`account_id` AS `account_id`,
        `accounts`.`account_name` AS `account_name`,
        `accounts`.`account_categoryId` AS `account_categoryId`,
        `accounts`.`account_userId` AS `account_userId`,
        `accounts`.`account_customerId` AS `account_customerId`,
        `accounts`.`account_userGroupId` AS `account_userGroupId`,
        `accounts`.`account_userEditId` AS `account_userEditId`,
        `accounts`.`account_login` AS `account_login`,
        `accounts`.`account_url` AS `account_url`,
        `accounts`.`account_notes` AS `account_notes`,
        `accounts`.`account_countView` AS `account_countView`,
        `accounts`.`account_countDecrypt` AS `account_countDecrypt`,
        `accounts`.`account_dateAdd` AS `account_dateAdd`,
        `accounts`.`account_dateEdit` AS `account_dateEdit`,
        CONV(`accounts`.`account_otherUserEdit`,
                10,
                2) AS `account_otherUserEdit`,
        CONV(`accounts`.`account_otherGroupEdit`,
                10,
                2) AS `account_otherGroupEdit`,
        CONV(`accounts`.`account_isPrivate`, 10, 2) AS `account_isPrivate`,
        CONV(`accounts`.`account_isPrivateGroup`, 10, 2) AS `account_isPrivateGroup`,
        `accounts`.`account_passDate` AS `account_passDate`,
        `accounts`.`account_passDateChange` AS `account_passDateChange`,
        `accounts`.`account_parentId` AS `account_parentId`,
        `categories`.`category_name` AS `category_name`,
        `customers`.`customer_name` AS `customer_name`,
        `ug`.`usergroup_name` AS `usergroup_name`,
        `u1`.`user_name` AS `user_name`,
        `u1`.`user_login` AS `user_login`,
        `u2`.`user_name` AS `user_editName`,
        `u2`.`user_login` AS `user_editLogin`,
        `publicLinks`.`publicLink_hash` AS `publicLink_hash`
    FROM
        ((((((`accounts`
        LEFT JOIN `categories` ON ((`accounts`.`account_categoryId` = `categories`.`category_id`)))
        LEFT JOIN `usrGroups` `ug` ON ((`accounts`.`account_userGroupId` = `ug`.`usergroup_id`)))
        LEFT JOIN `usrData` `u1` ON ((`accounts`.`account_userId` = `u1`.`user_id`)))
        LEFT JOIN `usrData` `u2` ON ((`accounts`.`account_userEditId` = `u2`.`user_id`)))
        LEFT JOIN `customers` ON ((`accounts`.`account_customerId` = `customers`.`customer_id`)))
        LEFT JOIN `publicLinks` ON ((`accounts`.`account_id` = `publicLinks`.`publicLink_itemId`)));

DROP TABLE IF EXISTS `account_search_v`;
CREATE OR REPLACE ALGORITHM = UNDEFINED DEFINER = CURRENT_USER SQL SECURITY DEFINER VIEW `account_search_v` AS
    SELECT DISTINCT
        `accounts`.`account_id` AS `account_id`,
        `accounts`.`account_customerId` AS `account_customerId`,
        `accounts`.`account_categoryId` AS `account_categoryId`,
        `accounts`.`account_name` AS `account_name`,
        `accounts`.`account_login` AS `account_login`,
        `accounts`.`account_url` AS `account_url`,
        `accounts`.`account_notes` AS `account_notes`,
        `accounts`.`account_userId` AS `account_userId`,
        `accounts`.`account_userGroupId` AS `account_userGroupId`,
        `accounts`.`account_otherUserEdit` AS `account_otherUserEdit`,
        `accounts`.`account_otherGroupEdit` AS `account_otherGroupEdit`,
        `accounts`.`account_isPrivate` AS `account_isPrivate`,
        `accounts`.`account_isPrivateGroup` AS `account_isPrivateGroup`,
        `accounts`.`account_passDate` AS `account_passDate`,
        `accounts`.`account_passDateChange` AS `account_passDateChange`,
        `accounts`.`account_parentId` AS `account_parentId`,
        `accounts`.`account_countView` AS `account_countView`,
        `ug`.`usergroup_name` AS `usergroup_name`,
        `categories`.`category_name` AS `category_name`,
        `customers`.`customer_name` AS `customer_name`,
        (SELECT
                COUNT(0)
            FROM
                `accFiles`
            WHERE
                (`accFiles`.`accfile_accountId` = `accounts`.`account_id`)) AS `num_files`
    FROM
        (((`accounts`
        LEFT JOIN `categories` ON ((`accounts`.`account_categoryId` = `categories`.`category_id`)))
        LEFT JOIN `usrGroups` `ug` ON ((`accounts`.`account_userGroupId` = `ug`.`usergroup_id`)))
        LEFT JOIN `customers` ON ((`customers`.`customer_id` = `accounts`.`account_customerId`)));

INSERT INTO actions VALUES (1, 'ACCOUNT_SEARCH', 'Buscar Cuentas', 'account/search');
INSERT INTO actions VALUES (10, 'ACCOUNT', 'Cuentas', 'account/index');
INSERT INTO actions VALUES (100, 'ACCOUNT_VIEW', 'Ver Cuenta', 'account/view');
INSERT INTO actions VALUES (101, 'ACCOUNT_CREATE', 'Nueva Cuenta', 'account/create');
INSERT INTO actions VALUES (102, 'ACCOUNT_EDIT', 'Editar Cuenta', 'account/edit');
INSERT INTO actions VALUES (103, 'ACCOUNT_DELETE', 'Eliminar Cuenta', 'account/delete');
INSERT INTO actions VALUES (104, 'ACCOUNT_VIEW_PASS', 'Ver Clave', 'account/viewPass');
INSERT INTO actions VALUES (105, 'ACCOUNT_VIEW_HISTORY', 'Ver Historial', 'account/viewHistory');
INSERT INTO actions VALUES (106, 'ACCOUNT_EDIT_PASS', 'Editar Clave de Cuenta', 'account/editPass');
INSERT INTO actions VALUES (107, 'ACCOUNT_EDIT_RESTORE', 'Restaurar Cuenta', 'account/restore');
INSERT INTO actions VALUES (108, 'ACCOUNT_COPY', 'Copiar Cuenta', 'account/copy');
INSERT INTO actions VALUES (109, 'ACCOUNT_COPY_PASS', 'Copiar Clave', 'account/copyPass');
INSERT INTO actions VALUES (11, 'ACCOUNT_FILE', 'Archivos', 'account/listFile');
INSERT INTO actions VALUES (111, 'ACCOUNT_FILE_VIEW', 'Ver Archivo', 'account/viewFile');
INSERT INTO actions VALUES (112, 'ACCOUNT_FILE_UPLOAD', 'Subir Archivo', 'account/uploadFile');
INSERT INTO actions VALUES (113, 'ACCOUNT_FILE_DOWNLOAD', 'Descargar Archivo', 'account/downloadFile');
INSERT INTO actions VALUES (114, 'ACCOUNT_FILE_DELETE', 'Eliminar Archivo', 'account/deleteFile');
INSERT INTO actions VALUES (12, 'ACCOUNT_REQUEST', 'Peticiones', 'account/request');
INSERT INTO actions VALUES (13, 'ACCOUNT_FAVORITE', 'Favoritos', 'favorite/index');
INSERT INTO actions VALUES (130, 'ACCOUNT_FAVORITE_VIEW', 'Ver Favoritos', 'favorite/view');
INSERT INTO actions VALUES (131, 'ACCOUNT_FAVORITE_ADD', 'Añadir Favorito', 'favorite/add');
INSERT INTO actions VALUES (133, 'ACCOUNT_FAVORITE_DELETE', 'Eliminar Favorito', 'favorite/delete');
INSERT INTO actions VALUES (20, 'WIKI', 'Wiki', 'wiki/index');
INSERT INTO actions VALUES (200, 'WIKI_VIEW', 'Ver Wiki', 'wiki/view');
INSERT INTO actions VALUES (201, 'WIKI_NEW', 'Añadir Wiki', 'wiki/create');
INSERT INTO actions VALUES (202, 'WIKI_EDIT', 'Editar Wiki', 'wiki/edit');
INSERT INTO actions VALUES (203, 'WIKI_DELETE', 'Eliminar Wiki', 'wiki/delete');
INSERT INTO actions VALUES (60, 'ITEMS_MANAGE', 'Elementos y Personalización', 'itemManager/index');
INSERT INTO actions VALUES (61, 'CATEGORY', 'Gestión Categorías', 'category/index');
INSERT INTO actions VALUES (610, 'CATEGORY_VIEW', 'Ver Categoría', 'category/view');
INSERT INTO actions VALUES (611, 'CATEGORY_CREATE', 'Nueva Categoría', 'category/create');
INSERT INTO actions VALUES (612, 'CATEGORY_EDIT', 'Editar Categoría', 'category/edit');
INSERT INTO actions VALUES (613, 'CATEGORY_DELETE', 'Eliminar Categoría', 'category/delete');
INSERT INTO actions VALUES (615, 'CATEGORY_SEARCH', 'Buscar Categoría', 'category/search');
INSERT INTO actions VALUES (62, 'CLIENT', 'Gestión Clientes', 'client/index');
INSERT INTO actions VALUES (620, 'CLIENT_VIEW', 'Ver Cliente', 'client/view');
INSERT INTO actions VALUES (621, 'CLIENT_CREATE', 'Nuevo CLiente', 'client/create');
INSERT INTO actions VALUES (622, 'CLIENT_EDIT', 'Editar Cliente', 'client/edit');
INSERT INTO actions VALUES (623, 'CLIENT_DELETE', 'Eliminar Cliente', 'client/delete');
INSERT INTO actions VALUES (625, 'CLIENT_SEARCH', 'Buscar Cliente', 'client/search');
INSERT INTO actions VALUES (63, 'APITOKEN', 'Gestión Autorizaciones API', 'apiToken/index');
INSERT INTO actions VALUES (630, 'APITOKEN_CREATE', 'Nuevo Token API', 'apiToken/create');
INSERT INTO actions VALUES (631, 'APITOKEN_VIEW', 'Ver Token API', 'apiToken/view');
INSERT INTO actions VALUES (632, 'APITOKEN_EDIT', 'Editar Token API', 'apiToken/edit');
INSERT INTO actions VALUES (633, 'APITOKEN_DELETE', 'Eliminar Token API', 'apiToken/delete');
INSERT INTO actions VALUES (635, 'APITOKEN_SEARCH', 'Buscar Token API', 'apiToken/search');
INSERT INTO actions VALUES (64, 'CUSTOMFIELD', 'Gestión Campos Personalizados', 'customField/index');
INSERT INTO actions VALUES (640, 'CUSTOMFIELD_CREATE', 'Nuevo Campo Personalizado', 'customField/create');
INSERT INTO actions VALUES (641, 'CUSTOMFIELD_VIEW', 'Ver Campo Personalizado', 'customField/view');
INSERT INTO actions VALUES (642, 'CUSTOMFIELD_EDIT', 'Editar Campo Personalizado', 'customField/edit');
INSERT INTO actions VALUES (643, 'CUSTOMFIELD_DELETE', 'Eliminar Campo Personalizado', 'customField/delete');
INSERT INTO actions VALUES (645, 'CUSTOMFIELD_SEARCH', 'Buscar Campo Personalizado', 'customField/search');
INSERT INTO actions VALUES (65, 'PUBLICLINK', 'Enlaces Públicos', 'publicLink/index');
INSERT INTO actions VALUES (650, 'PUBLICLINK_CREATE', 'Crear Enlace Público', 'publicLink/create');
INSERT INTO actions VALUES (651, 'PUBLICLINK_VIEW', 'Ver Enlace Público', 'publicLink/view');
INSERT INTO actions VALUES (653, 'PUBLICLINK_DELETE', 'Eliminar Enlace Público', 'publicLink/delete');
INSERT INTO actions VALUES (654, 'PUBLICLINK_REFRESH', 'Actualizar Enlace Público', 'publicLink/refresh');
INSERT INTO actions VALUES (655, 'PUBLICLINK_SEARCH', 'Buscar Enlace Público', 'publicLink/search');
INSERT INTO actions VALUES (66, 'FILE', 'Gestión de Archivos', 'file/index');
INSERT INTO actions VALUES (661, 'FILE_VIEW', 'Ver Archivo', 'file/view');
INSERT INTO actions VALUES (663, 'FILE_DELETE', 'Eliminar Archivo', 'file/delete');
INSERT INTO actions VALUES (665, 'FILE_SEARCH', 'Buscar Archivo', 'file/search');
INSERT INTO actions VALUES (67, 'ACCOUNTMGR', 'Gestión de Cuentas', 'accountManager/index');
INSERT INTO actions VALUES (6701, 'ACCOUNTMGR_HISTORY', 'Gestión de Cuenta (H)', 'accountHistoryManager/index');
INSERT INTO actions VALUES (671, 'ACCOUNTMGR_VIEW', 'Ver Cuenta', 'accountManager/view');
INSERT INTO actions VALUES (673, 'ACCOUNTMGR_DELETE', 'Eliminar Cuenta', 'accountManager/delete');
INSERT INTO actions VALUES (6731, 'ACCOUNTMGR_DELETE_HISTORY', 'Eliminar Cuenta', 'accountHistoryManager/delete');
INSERT INTO actions VALUES (675, 'ACCOUNTMGR_SEARCH', 'Buscar Cuenta', 'accountManager/search');
INSERT INTO actions VALUES (6751, 'ACCOUNTMGR_SEARCH_HISTORY', 'Buscar Cuenta', 'accountHistoryManager/search');
INSERT INTO actions VALUES (6771, 'ACCOUNTMGR_RESTORE', 'Restaurar Cuenta', 'accountManager/restore');
INSERT INTO actions VALUES (68, 'TAG', 'Gestión de Etiquetas', 'tag/index');
INSERT INTO actions VALUES (680, 'TAG_CREATE', 'Nueva Etiqueta', 'tag/create');
INSERT INTO actions VALUES (681, 'TAG_VIEW', 'Ver Etiqueta', 'tag/view');
INSERT INTO actions VALUES (682, 'TAG_EDIT', 'Editar Etiqueta', 'tag/edit');
INSERT INTO actions VALUES (683, 'TAG_DELETE', 'Eliminar Etiqueta', 'tag/delete');
INSERT INTO actions VALUES (685, 'TAG_SEARCH', 'Buscar Etiqueta', 'tag/search');
INSERT INTO actions VALUES (69, 'PLUGIN', 'Gestión Plugins', 'plugin/index');
INSERT INTO actions VALUES (690, 'PLUGIN_NEW', 'Nuevo Plugin', 'plugin/create');
INSERT INTO actions VALUES (691, 'PLUGIN_VIEW', 'Ver Plugin', 'plugin/view');
INSERT INTO actions VALUES (695, 'PLUGIN_SEARCH', 'Buscar Plugin', 'plugin/search');
INSERT INTO actions VALUES (696, 'PLUGIN_ENABLE', 'Habilitar Plugin', 'plugin/enable');
INSERT INTO actions VALUES (697, 'PLUGIN_DISABLE', 'Deshabilitar Plugin', 'plugin/disable');
INSERT INTO actions VALUES (698, 'PLUGIN_RESET', 'Restablecer Plugin', 'plugin/reset');
INSERT INTO actions VALUES (70, 'ACCESS_MANAGE', 'Usuarios y Accesos', 'accessManager/index');
INSERT INTO actions VALUES (71, 'USER', 'Gestión Usuarios', 'user/index');
INSERT INTO actions VALUES (710, 'USER_VIEW', 'Ver Usuario', 'user/view');
INSERT INTO actions VALUES (711, 'USER_CREATE', 'Nuevo Usuario', 'user/create');
INSERT INTO actions VALUES (712, 'USER_EDIT', 'Editar Usuario', 'user/edit');
INSERT INTO actions VALUES (713, 'USER_DELETE', 'Eliminar Usuario', 'user/delete');
INSERT INTO actions VALUES (714, 'USER_EDIT_PASS', 'Editar Clave Usuario', 'user/editPass');
INSERT INTO actions VALUES (715, 'USER_SEARCH', 'Buscar Usuario', 'user/search');
INSERT INTO actions VALUES (72, 'GROUP', 'Gestión Grupos', 'group/index');
INSERT INTO actions VALUES (720, 'GROUP_VIEW', 'Ver Grupo', 'group/view');
INSERT INTO actions VALUES (721, 'GROUP_CREATE', 'Nuevo Grupo', 'group/create');
INSERT INTO actions VALUES (722, 'GROUP_EDIT', 'Editar Grupo', 'group/edit');
INSERT INTO actions VALUES (723, 'GROUP_DELETE', 'Eliminar Grupo', 'group/delete');
INSERT INTO actions VALUES (725, 'GROUP_SEARCH', 'Buscar Grupo', 'group/search');
INSERT INTO actions VALUES (73, 'PROFILE', 'Gestión Perfiles', 'profile/index');
INSERT INTO actions VALUES (730, 'PROFILE_VIEW', 'Ver Perfil', 'profile/view');
INSERT INTO actions VALUES (731, 'PROFILE_CREATE', 'Nuevo Perfil', 'profile/create');
INSERT INTO actions VALUES (732, 'PROFILE_EDIT', 'Editar Perfil', 'profile/edit');
INSERT INTO actions VALUES (733, 'PROFILE_DELETE', 'Eliminar Perfil', 'profile/delete');
INSERT INTO actions VALUES (735, 'PROFILE_SEARCH', 'Buscar Perfil', 'profile/search');
INSERT INTO actions VALUES (740, 'PREFERENCE', 'Gestión Preferencias', 'preference/index');
INSERT INTO actions VALUES (741, 'PREFERENCE_GENERAL', 'Preferencias General', 'preference/general');
INSERT INTO actions VALUES (742, 'PREFERENCE_SECURITY', 'Preferencias Seguridad', 'preference/security');
INSERT INTO actions VALUES (760, 'NOTICE', 'Notificaciones', 'notice/index');
INSERT INTO actions VALUES (761, 'NOTICE_USER', 'Notificaciones Usuario', 'noticeUser/index');
INSERT INTO actions VALUES (7610, 'NOTICE_USER_VIEW', 'Ver Notificación', 'noticeUser/view');
INSERT INTO actions VALUES (7611, 'NOTICE_USER_CREATE', 'Crear Notificación', 'noticeUser/create');
INSERT INTO actions VALUES (7612, 'NOTICE_USER_EDIT', 'Editar Notificación', 'noticeUser/edit');
INSERT INTO actions VALUES (7613, 'NOTICE_USER_DELETE', 'Eliminar Notificación', 'noticeUser/delete');
INSERT INTO actions VALUES (7614, 'NOTICE_USER_CHECK', 'Marcar Notificación', 'noticeUser/check');
INSERT INTO actions VALUES (7615, 'NOTICE_USER_SEARCH', 'Buscar Notificación', 'noticeUser/search');
INSERT INTO actions VALUES (1000, 'CONFIG', 'Configuración', 'config/index');
INSERT INTO actions VALUES (1001, 'CONFIG_GENERAL', 'Configuración General', 'config/general');
INSERT INTO actions VALUES (1010, 'ACCOUNT_CONFIG', 'Configuración Cuentas', 'account/config');
INSERT INTO actions VALUES (1020, 'WIKI_CONFIG', 'Configuración Wiki', 'wiki/config');
INSERT INTO actions VALUES (1030, 'ENCRYPTION_CONFIG', 'Configuración Encriptación', 'encryption/config');
INSERT INTO actions VALUES (1031, 'ENCRYPTION_REFRESH', 'Actualizar Hash', 'encryption/updateHash');
INSERT INTO actions VALUES (1032, 'ENCRYPTION_TEMPPASS', 'Clave Maestra Temporal', 'encryption/createTempPass');
INSERT INTO actions VALUES (1040, 'BACKUP_CONFIG', 'Configuración Copia de Seguridad', 'backup/config');
INSERT INTO actions VALUES (1050, 'IMPORT_CONFIG', 'Configuración Importación', 'import/config');
INSERT INTO actions VALUES (1051, 'IMPORT_CSV', 'Importar CSV', 'import/csv');
INSERT INTO actions VALUES (1052, 'IMPORT_XML', 'Importar XML', 'import/xml');
INSERT INTO actions VALUES (1060, 'EXPORT_CONFIG', 'Configuración Exportación', 'export/config');
INSERT INTO actions VALUES (1061, 'EXPORT_XML', 'Exportar XML', 'export/xml');
INSERT INTO actions VALUES (1070, 'MAIL_CONFIG', 'Configuración Email', 'mail/config');
INSERT INTO actions VALUES (1080, 'LDAP_CONFIG', 'Configuración LDAP', 'ldap/config');
INSERT INTO actions VALUES (1081, 'LDAP_SYNC', 'Sincronización LDAP', 'ldap/sync');
INSERT INTO actions VALUES (90, 'EVENTLOG', 'Registro de Eventos', 'eventlog/index');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;