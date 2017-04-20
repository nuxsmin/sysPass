-- To 1.2.0.0.1;
ALTER TABLE `accounts`
  CHANGE COLUMN `account_userEditId` `account_userEditId` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
  CHANGE COLUMN `account_dateEdit` `account_dateEdit` DATETIME NULL DEFAULT NULL;
ALTER TABLE `accHistory`
  CHANGE COLUMN `acchistory_userEditId` `acchistory_userEditId` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
  CHANGE COLUMN `acchistory_dateEdit` `acchistory_dateEdit` DATETIME NULL DEFAULT NULL;
ALTER TABLE `accHistory`
  CHANGE COLUMN `accHistory_otherGroupEdit` `accHistory_otherGroupEdit` BIT NULL DEFAULT b'0';
ALTER TABLE `usrProfiles`
  ADD COLUMN `userProfile_profile` BLOB NOT NULL;
ALTER TABLE `usrData`
  ADD `user_preferences` BLOB NULL;
CREATE TABLE usrToGroups (
  usertogroup_id      INT UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
  usertogroup_userId  INT UNSIGNED             NOT NULL,
  usertogroup_groupId INT UNSIGNED             NOT NULL
)
  DEFAULT CHARSET = utf8;
CREATE INDEX IDX_accountId
  ON usrToGroups (usertogroup_userId);
ALTER TABLE `accFiles`
  ADD `accFile_thumb` BLOB NULL;
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