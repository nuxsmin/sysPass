-- To 1.1
ALTER TABLE `accFiles` CHANGE COLUMN `accfile_name` `accfile_name` VARCHAR(100) NOT NULL
ALTER TABLE `accounts` ADD COLUMN `account_otherGroupEdit` BIT(1) NULL DEFAULT 0
AFTER `account_dateEdit`, ADD COLUMN `account_otherUserEdit` BIT(1) NULL DEFAULT 0
AFTER `account_otherGroupEdit`;
CREATE TABLE `accUsers` (
  `accuser_id`        INT              NOT NULL AUTO_INCREMENT,
  `accuser_accountId` INT(10) UNSIGNED NOT NULL,
  `accuser_userId`    INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`accuser_id`),
  INDEX `idx_account` (`accuser_accountId` ASC)
);
ALTER TABLE `accHistory` ADD COLUMN `accHistory_otherUserEdit` BIT NULL
AFTER `acchistory_mPassHash`, ADD COLUMN `accHistory_otherGroupEdit` VARCHAR(45) NULL
AFTER `accHistory_otherUserEdit`;
ALTER TABLE `accFiles` CHANGE COLUMN `accfile_type` `accfile_type` VARCHAR(100) NOT NULL;
-- To 1.1.2.1
ALTER TABLE `categories` ADD COLUMN `category_description` VARCHAR(255) NULL
AFTER `category_name`;
ALTER TABLE `usrProfiles` ADD COLUMN `userProfile_pAppMgmtMenu` BIT(1) NULL DEFAULT b'0'
AFTER `userProfile_pUsersMenu`, CHANGE COLUMN `userProfile_pConfigCategories` `userProfile_pAppMgmtCategories` BIT(1) NULL DEFAULT b'0'
AFTER `userProfile_pAppMgmtMenu`, ADD COLUMN `userProfile_pAppMgmtCustomers` BIT(1) NULL DEFAULT b'0'
AFTER `userProfile_pAppMgmtCategories`;
-- To 1.1.2.2
ALTER TABLE `usrData` CHANGE COLUMN `user_login` `user_login` VARCHAR(50) NOT NULL, CHANGE COLUMN `user_email` `user_email` VARCHAR(80) NULL DEFAULT NULL;
-- To 1.1.2.3
CREATE TABLE `usrPassRecover` (
  `userpassr_id`     INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `userpassr_userId` SMALLINT UNSIGNED NOT NULL,
  `userpassr_hash`   VARBINARY(40)     NOT NULL,
  `userpassr_date`   INT UNSIGNED      NOT NULL,
  `userpassr_used`   BIT(1)            NOT NULL DEFAULT b'0',
  PRIMARY KEY (`userpassr_id`),
  INDEX `IDX_userId` (`userpassr_userId` ASC, `userpassr_date` ASC)
)
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_general_ci;
ALTER TABLE `log` ADD COLUMN `log_ipAddress` VARCHAR(45) NOT NULL
AFTER `log_userId`;
ALTER TABLE `usrData` ADD COLUMN `user_isChangePass` BIT(1) NULL DEFAULT b'0'
AFTER `user_isMigrate`;
-- To 1.1.2.12
ALTER TABLE `usrData` CHANGE COLUMN `user_mPass` `user_mPass` VARBINARY(32) NULL DEFAULT NULL, CHANGE COLUMN `user_lastLogin` `user_lastLogin` DATETIME NULL DEFAULT NULL, CHANGE COLUMN `user_lastUpdate` `user_lastUpdate` DATETIME NULL DEFAULT NULL, CHANGE COLUMN `user_mIV` `user_mIV` VARBINARY(32) NULL;
ALTER TABLE `accounts` CHANGE COLUMN `account_login` `account_login` VARCHAR(50) NULL DEFAULT NULL;
-- To 1.1.2.13
ALTER TABLE `usrData` CHANGE COLUMN `user_mPass` `user_mPass` VARBINARY(32) NULL DEFAULT NULL, CHANGE COLUMN `user_lastLogin` `user_lastLogin` DATETIME NULL DEFAULT NULL, CHANGE COLUMN `user_lastUpdate` `user_lastUpdate` DATETIME NULL DEFAULT NULL, CHANGE COLUMN `user_mIV` `user_mIV` VARBINARY(32) NULL;
ALTER TABLE `accounts` CHANGE COLUMN `account_login` `account_login` VARCHAR(50) NULL DEFAULT NULL;
-- To 1.1.2.19
ALTER TABLE `accounts` CHANGE COLUMN `account_pass` `account_pass` VARBINARY(255) NOT NULL;
ALTER TABLE `accHistory` CHANGE COLUMN `acchistory_pass` `acchistory_pass` VARBINARY(255) NOT NULL;
-- To 1.1.2.20
ALTER TABLE `usrData` CHANGE COLUMN `user_pass` `user_pass` VARBINARY(255) NOT NULL, CHANGE COLUMN `user_mPass` `acchistory_pass` VARBINARY(255) DEFAULT NULL;
-- To 1.2.0.1
ALTER TABLE `accounts` CHANGE COLUMN `account_userEditId` `account_userEditId` TINYINT(3) UNSIGNED NULL DEFAULT NULL, CHANGE COLUMN `account_dateEdit` `account_dateEdit` DATETIME NULL DEFAULT NULL;
ALTER TABLE `accHistory` CHANGE COLUMN `acchistory_userEditId` `acchistory_userEditId` TINYINT(3) UNSIGNED NULL DEFAULT NULL, CHANGE COLUMN `acchistory_dateEdit` `acchistory_dateEdit` DATETIME NULL DEFAULT NULL;
ALTER TABLE `accHistory` CHANGE COLUMN `accHistory_otherGroupEdit` `accHistory_otherGroupEdit` BIT NULL DEFAULT b'0';
ALTER TABLE `usrProfiles` ADD COLUMN `userProfile_profile` BLOB NOT NULL;
ALTER TABLE `usrData` ADD `user_preferences` BLOB NULL;
CREATE TABLE usrToGroups (
  usertogroup_id      INT UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
  usertogroup_userId  INT UNSIGNED             NOT NULL,
  usertogroup_groupId INT UNSIGNED             NOT NULL
)
  DEFAULT CHARSET = utf8;
CREATE INDEX IDX_accountId ON usrToGroups (usertogroup_userId);
ALTER TABLE `accFiles` ADD `accFile_thumb` BLOB NULL;
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
-- To 1.2.0.2
ALTER TABLE `config` CHANGE config_value config_value VARCHAR(255);
ALTER TABLE `usrData` CHANGE user_pass user_pass VARBINARY(255);
ALTER TABLE `usrData` CHANGE user_hashSalt user_hashSalt VARBINARY(128);
ALTER TABLE `accHistory` CHANGE acchistory_mPassHash acchistory_mPassHash VARBINARY(255);