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
DROP TABLE IF EXISTS `Account`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Account`
(
  `id`                 mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `userGroupId`        smallint(5) unsigned  NOT NULL,
  `userId`             smallint(5) unsigned  NOT NULL,
  `userEditId`         smallint(5) unsigned  NOT NULL,
  `clientId`           mediumint(8) unsigned NOT NULL,
  `name`               varchar(100)           NOT NULL,
  `categoryId`         mediumint(8) unsigned NOT NULL,
  `login`              varchar(50)                    DEFAULT NULL,
  `url`                varchar(255)                   DEFAULT NULL,
  `pass`               varbinary(2000)       NOT NULL,
  `key`                varbinary(2000)       NOT NULL,
  `notes`              text                           DEFAULT NULL,
  `countView`          int(10) unsigned      NOT NULL DEFAULT 0,
  `countDecrypt`       int(10) unsigned      NOT NULL DEFAULT 0,
  `dateAdd`            datetime              NOT NULL,
  `dateEdit`           datetime                       DEFAULT NULL,
  `otherUserGroupEdit` tinyint(1)                     DEFAULT 0,
  `otherUserEdit`      tinyint(1)                     DEFAULT 0,
  `isPrivate`          tinyint(1)                     DEFAULT 0,
  `isPrivateGroup`     tinyint(1)                     DEFAULT 0,
  `passDate`           int(11) unsigned               DEFAULT NULL,
  `passDateChange`     int(11) unsigned               DEFAULT NULL,
  `parentId`           mediumint(8) unsigned          DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_Account_01` (`categoryId`),
  KEY `idx_Account_02` (`userGroupId`, `userId`),
  KEY `idx_Account_03` (`clientId`),
  KEY `idx_Account_04` (`parentId`),
  KEY `fk_Account_userId` (`userId`),
  KEY `fk_Account_userEditId` (`userEditId`),
  CONSTRAINT `fk_Account_categoryId` FOREIGN KEY (`categoryId`) REFERENCES `Category` (`id`),
  CONSTRAINT `fk_Account_clientId` FOREIGN KEY (`clientId`) REFERENCES `Client` (`id`),
  CONSTRAINT `fk_Account_userEditId` FOREIGN KEY (`userEditId`) REFERENCES `User` (`id`),
  CONSTRAINT `fk_Account_userGroupId` FOREIGN KEY (`userGroupId`) REFERENCES `UserGroup` (`id`),
  CONSTRAINT `fk_Account_userId` FOREIGN KEY (`userId`) REFERENCES `User` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ItemPreset`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ItemPreset`
(
  `id`            int                           NOT NULL AUTO_INCREMENT,
  `type`          varchar(25)                   not null,
  `userId`        smallint(5) unsigned,
  `userGroupId`   smallint(5) unsigned,
  `userProfileId` smallint(5) unsigned,
  `fixed`         tinyint(1) unsigned DEFAULT 0 NOT NULL,
  `priority`      tinyint(3) unsigned DEFAULT 0 NOT NULL,
  `data`          blob,
  `hash`          varbinary(40)                 NOT NULL,
  UNIQUE INDEX `uk_ItemPreset_01` (`hash`),
  CONSTRAINT `fk_ItemPreset_userId`
    FOREIGN KEY (`userId`) REFERENCES `User` (`id`)
      ON DELETE CASCADE
      ON UPDATE CASCADE,
  CONSTRAINT `fk_ItemPreset_userGroupId`
    FOREIGN KEY (`userGroupId`) REFERENCES `UserGroup` (`id`)
      ON DELETE CASCADE
      ON UPDATE CASCADE,
  CONSTRAINT `fk_ItemPreset_userProfileId`
    FOREIGN KEY (`userProfileId`) REFERENCES `UserProfile` (`id`)
      ON DELETE CASCADE
      ON UPDATE CASCADE,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `AccountFile`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AccountFile`
(
  `id`        int(11)               NOT NULL AUTO_INCREMENT,
  `accountId` mediumint(5) unsigned NOT NULL,
  `name`      varchar(100)          NOT NULL,
  `type`      varchar(100)          NOT NULL,
  `size`      int(11)               NOT NULL,
  `content`   mediumblob            NOT NULL,
  `extension` varchar(10)           NOT NULL,
  `thumb`     mediumblob DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_AccountFile_01` (`accountId`),
  CONSTRAINT `fk_AccountFile_accountId` FOREIGN KEY (`accountId`) REFERENCES `Account` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `AccountHistory`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AccountHistory`
(
  `id`                 int(11)               NOT NULL AUTO_INCREMENT,
  `accountId`          mediumint(8) unsigned NOT NULL,
  `userGroupId`        smallint(5) unsigned  NOT NULL,
  `userId`             smallint(5) unsigned  NOT NULL,
  `userEditId`         smallint(5) unsigned  NOT NULL,
  `clientId`           mediumint(8) unsigned NOT NULL,
  `name`               varchar(255)          NOT NULL,
  `categoryId`         mediumint(8) unsigned NOT NULL,
  `login`              varchar(50)                    DEFAULT NULL,
  `url`                varchar(255)                   DEFAULT NULL,
  `pass`               varbinary(2000)       NOT NULL,
  `key`                varbinary(2000)       NOT NULL,
  `notes`              text                  NOT NULL,
  `countView`          int(10) unsigned      NOT NULL DEFAULT 0,
  `countDecrypt`       int(10) unsigned      NOT NULL DEFAULT 0,
  `dateAdd`            datetime              NOT NULL,
  `dateEdit`           datetime                       DEFAULT NULL,
  `isModify`           tinyint(1)                     DEFAULT 0,
  `isDeleted`          tinyint(1)                     DEFAULT 0,
  `mPassHash`          varbinary(255)        NOT NULL,
  `otherUserEdit`      tinyint(1)                     DEFAULT 0,
  `otherUserGroupEdit` tinyint(1)                     DEFAULT 0,
  `passDate`           int(10) unsigned               DEFAULT NULL,
  `passDateChange`     int(10) unsigned               DEFAULT NULL,
  `parentId`           mediumint(8) unsigned          DEFAULT NULL,
  `isPrivate`          tinyint(1)                     DEFAULT 0,
  `isPrivateGroup`     tinyint(1)                     DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_AccountHistory_01` (`accountId`),
  KEY `idx_AccountHistory_02` (`parentId`),
  KEY `fk_AccountHistory_userGroupId` (`userGroupId`),
  KEY `fk_AccountHistory_userId` (`userId`),
  KEY `fk_AccountHistory_userEditId` (`userEditId`),
  KEY `fk_AccountHistory_clientId` (`clientId`),
  KEY `fk_AccountHistory_categoryId` (`categoryId`),
  CONSTRAINT `fk_AccountHistory_categoryId` FOREIGN KEY (`categoryId`) REFERENCES `Category` (`id`),
  CONSTRAINT `fk_AccountHistory_clientId` FOREIGN KEY (`clientId`) REFERENCES `Client` (`id`),
  CONSTRAINT `fk_AccountHistory_userEditId` FOREIGN KEY (`userEditId`) REFERENCES `User` (`id`),
  CONSTRAINT `fk_AccountHistory_userGroupId` FOREIGN KEY (`userGroupId`) REFERENCES `UserGroup` (`id`),
  CONSTRAINT `fk_AccountHistory_userId` FOREIGN KEY (`userId`) REFERENCES `User` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `AccountToFavorite`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AccountToFavorite`
(
  `accountId` mediumint(8) unsigned NOT NULL,
  `userId`    smallint(5) unsigned  NOT NULL,
  PRIMARY KEY (`accountId`, `userId`),
  KEY `idx_AccountToFavorite_01` (`accountId`, `userId`),
  KEY `fk_AccountToFavorite_userId` (`userId`),
  CONSTRAINT `fk_AccountToFavorite_accountId` FOREIGN KEY (`accountId`) REFERENCES `Account` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_AccountToFavorite_userId` FOREIGN KEY (`userId`) REFERENCES `User` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `AccountToTag`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AccountToTag`
(
  `accountId` mediumint(8) unsigned NOT NULL,
  `tagId`     int(10) unsigned      NOT NULL,
  PRIMARY KEY (`accountId`, `tagId`),
  KEY `fk_AccountToTag_accountId` (`accountId`),
  KEY `fk_AccountToTag_tagId` (`tagId`),
  CONSTRAINT `fk_AccountToTag_accountId` FOREIGN KEY (`accountId`) REFERENCES `Account` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_AccountToTag_tagId` FOREIGN KEY (`tagId`) REFERENCES `Tag` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `AccountToUser`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AccountToUser`
(
  `accountId` mediumint(8) unsigned         NOT NULL,
  `userId`    smallint(5) unsigned          NOT NULL,
  `isEdit`    tinyint(1) unsigned DEFAULT 0 NULL,
  PRIMARY KEY (`accountId`, `userId`),
  KEY `idx_AccountToUser_01` (`accountId`),
  KEY `fk_AccountToUser_userId` (`userId`),
  CONSTRAINT `fk_AccountToUser_accountId` FOREIGN KEY (`accountId`) REFERENCES `Account` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_AccountToUser_userId` FOREIGN KEY (`userId`) REFERENCES `User` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `AccountToUserGroup`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AccountToUserGroup`
(
  `accountId`   mediumint(8) unsigned         NOT NULL,
  `userGroupId` smallint(5) unsigned          NOT NULL,
  `isEdit`      tinyint(1) unsigned DEFAULT 0 NULL,
  PRIMARY KEY (`accountId`, `userGroupId`),
  KEY `idx_AccountToUserGroup_01` (`accountId`),
  KEY `fk_AccountToUserGroup_userGroupId` (`userGroupId`),
  CONSTRAINT `fk_AccountToUserGroup_accountId` FOREIGN KEY (`accountId`) REFERENCES `Account` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_AccountToUserGroup_userGroupId` FOREIGN KEY (`userGroupId`) REFERENCES `UserGroup` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `AuthToken`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AuthToken`
(
  `id`        int(11)              NOT NULL AUTO_INCREMENT,
  `userId`    smallint(5) unsigned NOT NULL,
  `token`     varbinary(255)       NOT NULL,
  `actionId`  smallint(5) unsigned NOT NULL,
  `createdBy` smallint(5) unsigned NOT NULL,
  `startDate` int(10) unsigned     NOT NULL,
  `vault`     varbinary(2000) DEFAULT NULL,
  `hash`      varbinary(500)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_AuthToken_01` (`token`, `actionId`),
  KEY `idx_AuthToken_01` (`userId`, `actionId`, `token`),
  KEY `fk_AuthToken_actionId` (`actionId`),
  CONSTRAINT `fk_AuthToken_userId` FOREIGN KEY (`userId`) REFERENCES `User` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Category`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Category`
(
  `id`          mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name`        varchar(50)           NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `hash`        varbinary(40)         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_Category_01` (`hash`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Client`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Client`
(
  `id`          mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name`        varchar(100)          NOT NULL,
  `hash`        varbinary(40)         NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `isGlobal`    tinyint(1)   DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `uk_Client_01` (`hash`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Config`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Config`
(
  `parameter` varchar(50) NOT NULL,
  `value`     varchar(4000) DEFAULT NULL,
  PRIMARY KEY (`parameter`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `CustomFieldData`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CustomFieldData`
(
  `id`           int(10) unsigned     NOT NULL AUTO_INCREMENT,
  `moduleId`     smallint(5) unsigned NOT NULL,
  `itemId`       int(10) unsigned     NOT NULL,
  `definitionId` int(10) unsigned     NOT NULL,
  `data`         longblob        DEFAULT NULL,
  `key`          varbinary(2000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_CustomFieldData_01` (`definitionId`),
  KEY `idx_CustomFieldData_02` (`itemId`, `moduleId`),
  KEY `idx_CustomFieldData_03` (`moduleId`),
  KEY `uk_CustomFieldData_01` (`moduleId`, `itemId`, `definitionId`),
  CONSTRAINT `fk_CustomFieldData_definitionId` FOREIGN KEY (`definitionId`) REFERENCES `CustomFieldDefinition` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `CustomFieldDefinition`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CustomFieldDefinition`
(
  `id`          int(10) unsigned              NOT NULL AUTO_INCREMENT,
  `name`        varchar(100)                  NOT NULL,
  `moduleId`    smallint(5) unsigned          NOT NULL,
  `required`    tinyint(1) unsigned DEFAULT NULL,
  `help`        varchar(255)        DEFAULT NULL,
  `showInList`  tinyint(1) unsigned DEFAULT NULL,
  `typeId`      tinyint(3) unsigned           NOT NULL,
  `isEncrypted` tinyint(1) unsigned DEFAULT 1 NULL,
  PRIMARY KEY (`id`),
  KEY `fk_CustomFieldDefinition_typeId` (`typeId`),
  CONSTRAINT `fk_CustomFieldDefinition_typeId` FOREIGN KEY (`typeId`) REFERENCES `CustomFieldType` (`id`)
    ON UPDATE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `CustomFieldType`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CustomFieldType`
(
  `id`   tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50)         NOT NULL,
  `text` varchar(50)         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_CustomFieldType_01` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `EventLog`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EventLog`
(
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date`        int(10) unsigned NOT NULL,
  `login`       varchar(25)          DEFAULT NULL,
  `userId`      smallint(5) unsigned DEFAULT NULL,
  `ipAddress`   varchar(45)      NOT NULL,
  `action`      varchar(50)      NOT NULL,
  `description` text                 DEFAULT NULL,
  `level`       varchar(20)      NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO CustomFieldType (id, name, text)
VALUES (1, 'text', 'Text'),
       (2, 'password', 'Password'),
       (3, 'date', 'Date'),
       (4, 'number', 'Number'),
       (5, 'email', 'Email'),
       (6, 'telephone', 'Phone'),
       (7, 'url', 'URL'),
       (8, 'color', 'Color'),
       (9, 'wiki', 'Wiki'),
       (10, 'textarea', 'Text Area');
DROP TABLE IF EXISTS `Notification`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Notification`
(
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type`        varchar(100)         DEFAULT NULL,
  `component`   varchar(100)     NOT NULL,
  `description` text             NOT NULL,
  `date`        int(10) unsigned NOT NULL,
  `checked`     tinyint(1)           DEFAULT 0,
  `userId`      smallint(5) unsigned DEFAULT NULL,
  `sticky`      tinyint(1)           DEFAULT 0,
  `onlyAdmin`   tinyint(1)           DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_Notification_01` (`userId`, `checked`, `date`),
  KEY `idx_Notification_02` (`component`, `date`, `checked`, `userId`),
  KEY `fk_Notification_userId` (`userId`),
  CONSTRAINT `fk_Notification_userId` FOREIGN KEY (`userId`) REFERENCES `User` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Plugin`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Plugin`
(
  `id`           int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`         varchar(100)     NOT NULL,
  `data`         mediumblob                DEFAULT NULL,
  `enabled`      tinyint(1)       NOT NULL DEFAULT 0,
  `available`    tinyint(1)                DEFAULT 0,
  `versionLevel` varchar(15)      NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_Plugin_01` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `PublicLink`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PublicLink`
(
  `id`              int(10) unsigned     NOT NULL AUTO_INCREMENT,
  `itemId`          int(10) unsigned     NOT NULL,
  `hash`            varbinary(100)       NOT NULL,
  `data`            mediumblob                    DEFAULT NULL,
  `userId`          smallint(5) unsigned NOT NULL,
  `typeId`          int(10) unsigned     NOT NULL,
  `notify`          tinyint(1)                    DEFAULT 0,
  `dateAdd`         int(10) unsigned     NOT NULL,
  `dateExpire`      int(10) unsigned     NOT NULL,
  `dateUpdate`      int(10) unsigned              DEFAULT 0,
  `countViews`      smallint(5) unsigned          DEFAULT 0,
  `totalCountViews` mediumint(8) unsigned         DEFAULT 0,
  `maxCountViews`   smallint(5) unsigned NOT NULL DEFAULT 0,
  `useinfo`         blob                          DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_PublicLink_01` (`hash`),
  UNIQUE KEY `uk_PublicLink_02` (`itemId`),
  KEY `fk_PublicLink_userId` (`userId`),
  CONSTRAINT `fk_PublicLink_userId` FOREIGN KEY (`userId`) REFERENCES `User` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Tag`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Tag`
(
  `id`   int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45)      NOT NULL,
  `hash` varbinary(40)    NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_Tag_01` (`hash`),
  KEY `idx_Tag_01` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Track`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Track`
(
  `id`         int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userId`     smallint(5) unsigned DEFAULT NULL,
  `source`     varchar(100)     NOT NULL,
  `time`       int(10) unsigned NOT NULL,
  `timeUnlock` int(10) unsigned,
  `ipv4`       binary(4)            DEFAULT NULL,
  `ipv6`       binary(16)           DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_Track_01` (`userId`),
  KEY `idx_Track_02` (`time`, `ipv4`, `ipv6`, `source`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `User`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `User`
(
  `id`              smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name`            varchar(80)          NOT NULL,
  `userGroupId`     smallint(5) unsigned NOT NULL,
  `login`           varchar(50)          NOT NULL,
  `ssoLogin`        varchar(100)                  DEFAULT NULL,
  `pass`            varbinary(500)       NOT NULL,
  `mPass`           varbinary(2000)               DEFAULT NULL,
  `mKey`            varbinary(2000)               DEFAULT NULL,
  `email`           varchar(80)                   DEFAULT NULL,
  `notes`           text                          DEFAULT NULL,
  `loginCount`      int(10) unsigned     NOT NULL DEFAULT 0,
  `userProfileId`   smallint(5) unsigned NOT NULL,
  `lastLogin`       datetime                      DEFAULT NULL,
  `lastUpdate`      datetime                      DEFAULT NULL,
  `lastUpdateMPass` int(11) unsigned     NOT NULL DEFAULT 0,
  `isAdminApp`      tinyint(1)                    DEFAULT 0,
  `isAdminAcc`      tinyint(1)                    DEFAULT 0,
  `isLdap`          tinyint(1)                    DEFAULT 0,
  `isDisabled`      tinyint(1)                    DEFAULT 0,
  `hashSalt`        varbinary(255)       NOT NULL,
  `isMigrate`       tinyint(1)                    DEFAULT 0,
  `isChangePass`    tinyint(1)                    DEFAULT 0,
  `isChangedPass`   tinyint(1)                    DEFAULT 0,
  `preferences`     blob                          DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_User_01` (`login`, `ssoLogin`),
  KEY `idx_User_01` (`pass`),
  KEY `fk_User_userGroupId` (`userGroupId`),
  KEY `fk_User_userProfileId` (`userProfileId`),
  CONSTRAINT `fk_User_userGroupId` FOREIGN KEY (`userGroupId`) REFERENCES `UserGroup` (`id`),
  CONSTRAINT `fk_User_userProfileId` FOREIGN KEY (`userProfileId`) REFERENCES `UserProfile` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `UserGroup`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserGroup`
(
  `id`          smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name`        varchar(50)          NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `UserPassRecover`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserPassRecover`
(
  `id`     int(10) unsigned     NOT NULL AUTO_INCREMENT,
  `userId` smallint(5) unsigned NOT NULL,
  `hash`   varbinary(255)       NOT NULL,
  `date`   int(10) unsigned     NOT NULL,
  `used`   tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_UserPassRecover_01` (`userId`, `date`),
  CONSTRAINT `fk_UserPassRecover_userId` FOREIGN KEY (`userId`) REFERENCES `User` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `UserProfile`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserProfile`
(
  `id`      smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name`    varchar(45)          NOT NULL,
  `profile` blob                 NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `UserToUserGroup`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserToUserGroup`
(
  `userId`      smallint(5) unsigned NOT NULL,
  `userGroupId` smallint(5) unsigned NOT NULL,
  KEY `idx_UserToUserGroup_01` (`userId`),
  KEY `fk_UserToGroup_userGroupId` (`userGroupId`),
  UNIQUE KEY `uk_UserToUserGroup_01` (`userId`, `userGroupId`),
  CONSTRAINT `fk_UserToGroup_userGroupId` FOREIGN KEY (`userGroupId`) REFERENCES `UserGroup` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_UserToGroup_userId` FOREIGN KEY (`userId`) REFERENCES `User` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `PluginData`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
create table PluginData
(
  name   varchar(100)    not null,
  itemId int             not null,
  `data` blob            not null,
  `key`  varbinary(2000) not null,
  primary key (name, itemId),
  constraint fk_PluginData_name
    foreign key (name) references Plugin (name)
      on update cascade
      on delete cascade
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `account_data_v`;
/*!50001 DROP VIEW IF EXISTS `account_data_v`*/;
SET @saved_cs_client = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `account_data_v`
(
  `id`                 tinyint NOT NULL,
  `name`               tinyint NOT NULL,
  `categoryId`         tinyint NOT NULL,
  `userId`             tinyint NOT NULL,
  `clientId`           tinyint NOT NULL,
  `userGroupId`        tinyint NOT NULL,
  `userEditId`         tinyint NOT NULL,
  `login`              tinyint NOT NULL,
  `url`                tinyint NOT NULL,
  `notes`              tinyint NOT NULL,
  `countView`          tinyint NOT NULL,
  `countDecrypt`       tinyint NOT NULL,
  `dateAdd`            tinyint NOT NULL,
  `dateEdit`           tinyint NOT NULL,
  `otherUserEdit`      tinyint NOT NULL,
  `otherUserGroupEdit` tinyint NOT NULL,
  `isPrivate`          tinyint NOT NULL,
  `isPrivateGroup`     tinyint NOT NULL,
  `passDate`           tinyint NOT NULL,
  `passDateChange`     tinyint NOT NULL,
  `parentId`           tinyint NOT NULL,
  `categoryName`       tinyint NOT NULL,
  `clientName`         tinyint NOT NULL,
  `userGroupName`      tinyint NOT NULL,
  `userName`           tinyint NOT NULL,
  `userLogin`          tinyint NOT NULL,
  `userEditName`       tinyint NOT NULL,
  `userEditLogin`      tinyint NOT NULL,
  `publicLinkHash`     tinyint NOT NULL
)
  ENGINE = MyISAM */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `account_search_v`;
/*!50001 DROP VIEW IF EXISTS `account_search_v`*/;
SET @saved_cs_client = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `account_search_v`
(
  `id`                 tinyint NOT NULL,
  `clientId`           tinyint NOT NULL,
  `categoryId`         tinyint NOT NULL,
  `name`               tinyint NOT NULL,
  `login`              tinyint NOT NULL,
  `url`                tinyint NOT NULL,
  `notes`              tinyint NOT NULL,
  `userId`             tinyint NOT NULL,
  `userGroupId`        tinyint NOT NULL,
  `otherUserEdit`      tinyint NOT NULL,
  `otherUserGroupEdit` tinyint NOT NULL,
  `isPrivate`          tinyint NOT NULL,
  `isPrivateGroup`     tinyint NOT NULL,
  `passDate`           tinyint NOT NULL,
  `passDateChange`     tinyint NOT NULL,
  `parentId`           tinyint NOT NULL,
  `countView`          tinyint NOT NULL,
  `dateEdit`           tinyint NOT NULL,
  `userName`           tinyint NOT NULL,
  `userLogin`          tinyint NOT NULL,
  `userGroupName`      tinyint NOT NULL,
  `categoryName`       tinyint NOT NULL,
  `clientName`         tinyint NOT NULL,
  `num_files`          tinyint NOT NULL
)
  ENGINE = MyISAM */;
SET character_set_client = @saved_cs_client;
/*!50001 DROP TABLE IF EXISTS `account_data_v`*/;
/*!50001 DROP VIEW IF EXISTS `account_data_v`*/;
/*!50001 SET @saved_cs_client = @@character_set_client */;
/*!50001 SET @saved_cs_results = @@character_set_results */;
/*!50001 SET @saved_col_connection = @@collation_connection */;
/*!50001 SET character_set_client = utf8 */;
/*!50001 SET character_set_results = utf8 */;
/*!50001 SET collation_connection = utf8_general_ci */;
/*!50001 CREATE ALGORITHM = UNDEFINED */
  /*!50013 SQL SECURITY DEFINER */
  /*!50001 VIEW `account_data_v` AS
select `Account`.`id`                              AS `id`,
       `Account`.`name`                            AS `name`,
       `Account`.`categoryId`                      AS `categoryId`,
       `Account`.`userId`                          AS `userId`,
       `Account`.`clientId`                        AS `clientId`,
       `Account`.`userGroupId`                     AS `userGroupId`,
       `Account`.`userEditId`                      AS `userEditId`,
       `Account`.`login`                           AS `login`,
       `Account`.`url`                             AS `url`,
       `Account`.`notes`                           AS `notes`,
       `Account`.`countView`                       AS `countView`,
       `Account`.`countDecrypt`                    AS `countDecrypt`,
       `Account`.`dateAdd`                         AS `dateAdd`,
       `Account`.`dateEdit`                        AS `dateEdit`,
       conv(`Account`.`otherUserEdit`, 10, 2)      AS `otherUserEdit`,
       conv(`Account`.`otherUserGroupEdit`, 10, 2) AS `otherUserGroupEdit`,
       conv(`Account`.`isPrivate`, 10, 2)          AS `isPrivate`,
       conv(`Account`.`isPrivateGroup`, 10, 2)     AS `isPrivateGroup`,
       `Account`.`passDate`                        AS `passDate`,
       `Account`.`passDateChange`                  AS `passDateChange`,
       `Account`.`parentId`                        AS `parentId`,
       `Category`.`name`                           AS `categoryName`,
       `Client`.`name`                             AS `clientName`,
       `ug`.`name`                                 AS `userGroupName`,
       `u1`.`name`                                 AS `userName`,
       `u1`.`login`                                AS `userLogin`,
       `u2`.`name`                                 AS `userEditName`,
       `u2`.`login`                                AS `userEditLogin`,
       `PublicLink`.`hash`                         AS `publicLinkHash`
from ((((((`Account`
  left join `Category` on (`Account`.`categoryId` = `Category`.`id`)) join `UserGroup` `ug`
  on (`Account`.`userGroupId` = `ug`.`id`)) join `User` `u1` on (`Account`.`userId` = `u1`.`id`)) join `User` `u2`
  on (`Account`.`userEditId` = `u2`.`id`)) left join `Client` on (`Account`.`clientId` = `Client`.`id`))
       left join
     `PublicLink` on (`Account`.`id` = `PublicLink`.`itemId`)) */;
/*!50001 SET character_set_client = @saved_cs_client */;
/*!50001 SET character_set_results = @saved_cs_results */;
/*!50001 SET collation_connection = @saved_col_connection */;
/*!50001 DROP TABLE IF EXISTS `account_search_v`*/;
/*!50001 DROP VIEW IF EXISTS `account_search_v`*/;
/*!50001 SET @saved_cs_client = @@character_set_client */;
/*!50001 SET @saved_cs_results = @@character_set_results */;
/*!50001 SET @saved_col_connection = @@collation_connection */;
/*!50001 SET character_set_client = utf8 */;
/*!50001 SET character_set_results = utf8 */;
/*!50001 SET collation_connection = utf8_general_ci */;
/*!50001 CREATE ALGORITHM = UNDEFINED */
  /*!50013 SQL SECURITY DEFINER */
  /*!50001 VIEW `account_search_v` AS
SELECT `Account`.`id`                                       AS `id`,
       `Account`.`clientId`                                 AS `clientId`,
       `Account`.`categoryId`                               AS `categoryId`,
       `Account`.`name`                                     AS `name`,
       `Account`.`login`                                    AS `login`,
       `Account`.`url`                                      AS `url`,
       `Account`.`notes`                                    AS `notes`,
       `Account`.`userId`                                   AS `userId`,
       `Account`.`userGroupId`                              AS `userGroupId`,
       `Account`.`otherUserEdit`                            AS `otherUserEdit`,
       `Account`.`otherUserGroupEdit`                       AS `otherUserGroupEdit`,
       `Account`.`isPrivate`                                AS `isPrivate`,
       `Account`.`isPrivateGroup`                           AS `isPrivateGroup`,
       `Account`.`passDate`                                 AS `passDate`,
       `Account`.`passDateChange`                           AS `passDateChange`,
       `Account`.`parentId`                                 AS `parentId`,
       `Account`.`countView`                                AS `countView`,
       `Account`.`dateEdit`                                 AS `dateEdit`,
       `User`.`name`                                        AS `userName`,
       `User`.`login`                                       AS `userLogin`,
       `UserGroup`.`name`                                   AS `userGroupName`,
       `Category`.`name`                                    AS `categoryName`,
       `Client`.`name`                                      AS `clientName`,
       (SELECT count(0)
        FROM `AccountFile`
        WHERE (`AccountFile`.`accountId` = `Account`.`id`)) AS `num_files`,
       `PublicLink`.`hash`                                  AS `publicLinkHash`,
       `PublicLink`.`dateExpire`                            AS `publicLinkDateExpire`,
       `PublicLink`.`totalCountViews`                       AS `publicLinkTotalCountViews`
FROM `Account`
       INNER JOIN `Category` ON `Account`.`categoryId` = `Category`.`id`
       INNER JOIN `Client` ON `Client`.`id` = `Account`.`clientId`
       INNER JOIN `User` ON `Account`.`userId` = `User`.`id`
       INNER JOIN `UserGroup` ON `Account`.`userGroupId` = `UserGroup`.`id`
       LEFT JOIN `PublicLink` ON `Account`.`id` = `PublicLink`.`itemId` */;
/*!50001 SET character_set_client = @saved_cs_client */;
/*!50001 SET character_set_results = @saved_cs_results */;
/*!50001 SET collation_connection = @saved_col_connection */;
/*!40103 SET TIME_ZONE = @OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE = @OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES = @OLD_SQL_NOTES */;

