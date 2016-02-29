/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE = @@TIME_ZONE */;
/*!40103 SET TIME_ZONE = '+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0 */;
/*!40101 SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES = @@SQL_NOTES, SQL_NOTES = 0 */;

CREATE TABLE `accFiles` (
  `accfile_id`        INT(11)              NOT NULL AUTO_INCREMENT,
  `accfile_accountId` SMALLINT(5) UNSIGNED NOT NULL,
  `accfile_name`      VARCHAR(100)         NOT NULL,
  `accfile_type`      VARCHAR(100)         NOT NULL,
  `accfile_size`      INT(11)              NOT NULL,
  `accfile_content`   MEDIUMBLOB           NOT NULL,
  `accfile_extension` VARCHAR(10)          NOT NULL,
  `accFile_thumb`     MEDIUMBLOB,
  PRIMARY KEY (`accfile_id`),
  KEY `IDX_accountId` (`accfile_accountId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `accGroups` (
  `accgroup_id`        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `accgroup_accountId` INT(10) UNSIGNED NOT NULL,
  `accgroup_groupId`   INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`accgroup_id`),
  KEY `IDX_accountId` (`accgroup_accountId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `accHistory` (
  `acchistory_id`             INT(11)              NOT NULL AUTO_INCREMENT,
  `acchistory_accountId`      SMALLINT(5) UNSIGNED NOT NULL,
  `acchistory_userGroupId`    TINYINT(3) UNSIGNED  NOT NULL,
  `acchistory_userId`         TINYINT(3) UNSIGNED  NOT NULL,
  `acchistory_userEditId`     TINYINT(3) UNSIGNED  NOT NULL,
  `acchistory_customerId`     TINYINT(3) UNSIGNED  NOT NULL,
  `acchistory_name`           VARCHAR(255)         NOT NULL,
  `acchistory_categoryId`     TINYINT(3) UNSIGNED  NOT NULL,
  `acchistory_login`          VARCHAR(50)          NOT NULL,
  `acchistory_url`            VARCHAR(255)                  DEFAULT NULL,
  `acchistory_pass`           VARBINARY(255)       NOT NULL,
  `acchistory_IV`             VARBINARY(32)        NOT NULL,
  `acchistory_notes`          TEXT                 NOT NULL,
  `acchistory_countView`      INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `acchistory_countDecrypt`   INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `acchistory_dateAdd`        DATETIME             NOT NULL,
  `acchistory_dateEdit`       DATETIME             NOT NULL,
  `acchistory_isModify`       BIT(1)                        DEFAULT NULL,
  `acchistory_isDeleted`      BIT(1)                        DEFAULT NULL,
  `acchistory_mPassHash`      VARBINARY(255)       NOT NULL,
  `accHistory_otherUserEdit`  BIT(1)                        DEFAULT b'0',
  `accHistory_otherGroupEdit` BIT(1)                        DEFAULT b'0',
  PRIMARY KEY (`acchistory_id`),
  KEY `IDX_accountId` (`acchistory_accountId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `accUsers` (
  `accuser_id`        INT(11)          NOT NULL AUTO_INCREMENT,
  `accuser_accountId` INT(10) UNSIGNED NOT NULL,
  `accuser_userId`    INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`accuser_id`),
  KEY `idx_account` (`accuser_accountId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `accViewLinks` (
  `accviewlinks_id`         INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `accviewlinks_accountId`  INT(10) UNSIGNED          DEFAULT NULL,
  `accviewlinks_expireTime` INT(10) UNSIGNED          DEFAULT NULL,
  `accviewlinks_expired`    BIT(1)                    DEFAULT b'0',
  `accviewlinks_userId`     INT(10) UNSIGNED          DEFAULT NULL,
  `accviewlinks_hash`       VARBINARY(100)            DEFAULT '',
  `accviewlinks_actionId`   SMALLINT(5) UNSIGNED      DEFAULT NULL,
  PRIMARY KEY (`accviewlinks_id`),
  UNIQUE KEY `unique_accviewlinks_id` (`accviewlinks_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

CREATE TABLE `accounts` (
  `account_id`             SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_userGroupId`    TINYINT(3) UNSIGNED  NOT NULL,
  `account_userId`         TINYINT(3) UNSIGNED  NOT NULL,
  `account_userEditId`     TINYINT(3) UNSIGNED  NOT NULL,
  `account_customerId`     INT(10) UNSIGNED     NOT NULL,
  `account_name`           VARCHAR(50)          NOT NULL,
  `account_categoryId`     TINYINT(3) UNSIGNED  NOT NULL,
  `account_login`          VARCHAR(50)                   DEFAULT NULL,
  `account_url`            VARCHAR(255)                  DEFAULT NULL,
  `account_pass`           VARBINARY(255)       NOT NULL,
  `account_IV`             VARBINARY(32)        NOT NULL,
  `account_notes`          TEXT,
  `account_countView`      INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `account_countDecrypt`   INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `account_dateAdd`        DATETIME             NOT NULL,
  `account_dateEdit`       DATETIME             NOT NULL,
  `account_otherGroupEdit` BIT(1)                        DEFAULT b'0',
  `account_otherUserEdit`  BIT(1)                        DEFAULT b'0',
  PRIMARY KEY (`account_id`),
  KEY `IDX_categoryId` (`account_categoryId`),
  KEY `IDX_userId` (`account_userGroupId`, `account_userId`),
  KEY `IDX_customerId` (`account_customerId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `authTokens` (
  `authtoken_id`        INT(11)              NOT NULL AUTO_INCREMENT,
  `authtoken_userId`    INT(11)              NOT NULL,
  `authtoken_token`     VARBINARY(100)       NOT NULL,
  `authtoken_actionId`  SMALLINT(5) UNSIGNED NOT NULL,
  `authtoken_createdBy` SMALLINT(5) UNSIGNED NOT NULL,
  `authtoken_startDate` INT(10) UNSIGNED     NOT NULL,
  PRIMARY KEY (`authtoken_id`),
  UNIQUE KEY `unique_authtoken_id` (`authtoken_id`),
  KEY `IDX_checkToken` (`authtoken_userId`, `authtoken_actionId`, `authtoken_token`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `categories` (
  `category_id`          SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name`        VARCHAR(50)          NOT NULL,
  `category_description` VARCHAR(255)                  DEFAULT NULL,
  PRIMARY KEY (`category_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `config` (
  `config_parameter` VARCHAR(50)   NOT NULL,
  `config_value`     VARCHAR(2000) NOT NULL,
  UNIQUE KEY `vacParameter` (`config_parameter`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `customers` (
  `customer_id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_name`        VARCHAR(100)     NOT NULL,
  `customer_hash`        VARBINARY(40)    NOT NULL,
  `customer_description` VARCHAR(255)              DEFAULT NULL,
  PRIMARY KEY (`customer_id`),
  KEY `IDX_name` (`customer_name`, `customer_hash`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `customFieldsDef` (
  `customfielddef_id`     INT(10) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `customfielddef_module` SMALLINT(5) UNSIGNED NOT NULL,
  `customfielddef_field`  BLOB                 NOT NULL,
  PRIMARY KEY (`customfielddef_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `customFieldsData` (
  `customfielddata_id`       INT(10) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `customfielddata_moduleId` SMALLINT(5) UNSIGNED NOT NULL,
  `customfielddata_itemId`   INT(10) UNSIGNED     NOT NULL,
  `customfielddata_defId`    INT(10) UNSIGNED     NOT NULL,
  `customfielddata_data`     LONGBLOB,
  `customfielddata_iv`       VARBINARY(128)                DEFAULT NULL,
  PRIMARY KEY (`customfielddata_id`),
  KEY `IDX_DEFID` (`customfielddata_defId`),
  KEY `IDX_DELETE` (`customfielddata_itemId`, `customfielddata_moduleId`),
  KEY `IDX_UPDATE` (`customfielddata_moduleId`, `customfielddata_itemId`, `customfielddata_defId`),
  KEY `IDX_ITEM` (`customfielddata_itemId`),
  KEY `IDX_MODULE` (`customfielddata_moduleId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `log` (
  `log_id`          INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `log_date`        INT(10) UNSIGNED    NOT NULL,
  `log_login`       VARCHAR(25)         NOT NULL,
  `log_userId`      TINYINT(3) UNSIGNED NOT NULL,
  `log_ipAddress`   VARCHAR(45)         NOT NULL,
  `log_action`      VARCHAR(50)         NOT NULL,
  `log_description` TEXT                NOT NULL,
  `log_level`       VARCHAR(20)         NOT NULL,
  PRIMARY KEY (`log_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `usrData` (
  `user_id`              SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_name`            VARCHAR(80)          NOT NULL,
  `user_groupId`         TINYINT(3) UNSIGNED  NOT NULL,
  `user_secGroupId`      TINYINT(3) UNSIGNED           DEFAULT NULL,
  `user_login`           VARCHAR(50)          NOT NULL,
  `user_pass`            VARBINARY(255)       NOT NULL,
  `user_mPass`           VARBINARY(255)                DEFAULT NULL,
  `user_mIV`             VARBINARY(32)        NOT NULL,
  `user_email`           VARCHAR(80)                   DEFAULT NULL,
  `user_notes`           TEXT,
  `user_count`           INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `user_profileId`       TINYINT(4)           NOT NULL,
  `user_lastLogin`       DATETIME                      DEFAULT NULL,
  `user_lastUpdate`      DATETIME                      DEFAULT NULL,
  `user_lastUpdateMPass` INT(11) UNSIGNED     NOT NULL DEFAULT '0',
  `user_isAdminApp`      BIT(1)               NOT NULL DEFAULT b'0',
  `user_isAdminAcc`      BIT(1)               NOT NULL DEFAULT b'0',
  `user_isLdap`          BIT(1)               NOT NULL DEFAULT b'0',
  `user_isDisabled`      BIT(1)               NOT NULL DEFAULT b'0',
  `user_hashSalt`        VARBINARY(128)       NOT NULL,
  `user_isMigrate`       BIT(1)                        DEFAULT b'0',
  `user_isChangePass`    BIT(1)                        DEFAULT b'0',
  `user_preferences`     BLOB,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `IDX_login` (`user_login`),
  KEY `IDX_pass` (`user_pass`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `usrGroups` (
  `usergroup_id`          SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usergroup_name`        VARCHAR(50)          NOT NULL,
  `usergroup_description` VARCHAR(255)                  DEFAULT NULL,
  PRIMARY KEY (`usergroup_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `usrPassRecover` (
  `userpassr_id`     INT(10) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `userpassr_userId` SMALLINT(5) UNSIGNED NOT NULL,
  `userpassr_hash`   VARBINARY(40)        NOT NULL,
  `userpassr_date`   INT(10) UNSIGNED     NOT NULL,
  `userpassr_used`   BIT(1)               NOT NULL,
  PRIMARY KEY (`userpassr_id`),
  KEY `IDX_userId` (`userpassr_userId`, `userpassr_date`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `usrProfiles` (
  `userprofile_id`      SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userprofile_name`    VARCHAR(45)          NOT NULL,
  `userProfile_profile` BLOB                 NOT NULL,
  PRIMARY KEY (`userprofile_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `usrToGroups` (
  `usertogroup_id`      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usertogroup_userId`  INT(10) UNSIGNED NOT NULL,
  `usertogroup_groupId` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`usertogroup_id`),
  KEY `IDX_usertogroup_userId` (`usertogroup_userId`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `publicLinks` (
  `publicLink_id`       INT            NOT NULL AUTO_INCREMENT,
  `publicLink_itemId`   INT,
  `publicLink_hash`     VARBINARY(100) NOT NULL,
  `publicLink_linkData` LONGBLOB,
  PRIMARY KEY (`publicLink_id`),
  KEY `IDX_itemId` (`publicLink_itemId`),
  UNIQUE KEY `IDX_hash` (`publicLink_hash`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `accFavorites` (
  `accfavorite_accountId` SMALLINT UNSIGNED NOT NULL,
  `accfavorite_userId`    SMALLINT UNSIGNED NOT NULL,
  INDEX `fk_accFavorites_accounts_idx` (`accfavorite_accountId` ASC),
  INDEX `fk_accFavorites_users_idx` (`accfavorite_userId` ASC),
  INDEX `search_idx` (`accfavorite_accountId` ASC, `accfavorite_userId` ASC),
  CONSTRAINT `fk_accFavorites_accounts`
  FOREIGN KEY (`accfavorite_accountId`)
  REFERENCES `accounts` (`account_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_accFavorites_users`
  FOREIGN KEY (`accfavorite_userId`)
  REFERENCES `usrData` (`user_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `tags` (
  `tag_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tag_name` VARCHAR(45)  NOT NULL,
  `tag_hash` BINARY(20) NOT NULL,
  PRIMARY KEY (`tag_id`),
  INDEX `IDX_name` (`tag_name` ASC),
  UNIQUE INDEX `tag_hash_UNIQUE` (`tag_hash` ASC)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
;

CREATE TABLE `accTags` (
  `acctag_accountId` INT UNSIGNED NOT NULL,
  `acctag_tagId`     INT UNSIGNED NOT NULL,
  INDEX `IDX_id` (`acctag_accountId` ASC, `acctag_tagId` ASC)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

/*!40103 SET TIME_ZONE = @OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE = @OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES = @OLD_SQL_NOTES */;