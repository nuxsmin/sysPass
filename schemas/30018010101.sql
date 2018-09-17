DELIMITER $$

SET FOREIGN_KEY_CHECKS = 0 $$

ALTER SCHEMA DEFAULT COLLATE utf8_unicode_ci $$

DROP PROCEDURE IF EXISTS drop_primary $$

CREATE PROCEDURE drop_primary(
  tName VARCHAR(64)
)
  BEGIN
    DECLARE cName VARCHAR(64);
    DECLARE done INT DEFAULT FALSE;
    DECLARE cur CURSOR FOR
      SELECT DISTINCT
        COLUMN_NAME
      FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
      WHERE TABLE_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = 'PRIMARY'
            AND TABLE_NAME = tName COLLATE utf8_unicode_ci;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
      FETCH cur
      INTO cName;
      IF done
      THEN
        LEAVE read_loop;
      END IF;
      SET @SQL = CONCAT('ALTER TABLE `', tName, '` DROP COLUMN `', cName, '`, DROP PRIMARY KEY');
      PREPARE stmt FROM @SQL;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END LOOP;

    CLOSE cur;
  END $$

DROP PROCEDURE IF EXISTS remove_constraints $$

CREATE PROCEDURE remove_constraints()
  BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE tName VARCHAR(64);
    DECLARE cName VARCHAR(64);
    DECLARE cur CURSOR FOR
      SELECT DISTINCT
        TABLE_NAME,
        CONSTRAINT_NAME
      FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
      WHERE TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
      FETCH cur
      INTO tName, cName;
      IF done
      THEN
        LEAVE read_loop;
      END IF;
      SET @SQL = CONCAT('ALTER TABLE `', tName, '` DROP FOREIGN KEY `', cName, '`');
      PREPARE stmt FROM @SQL;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END LOOP;

    CLOSE cur;
  END $$

DROP PROCEDURE IF EXISTS remove_indexes $$

CREATE PROCEDURE remove_indexes()
  BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE tName VARCHAR(64);
    DECLARE iName VARCHAR(64);
    DECLARE cur CURSOR FOR
      SELECT DISTINCT
        TABLE_NAME,
        INDEX_NAME
      FROM INFORMATION_SCHEMA.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE()
            AND NON_UNIQUE = 1;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
      FETCH cur
      INTO tName, iName;
      IF done
      THEN
        LEAVE read_loop;
      END IF;
      SET @SQL = CONCAT('ALTER TABLE `', tName, '` DROP INDEX `', iName, '`');
      PREPARE stmt FROM @SQL;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END LOOP;

    CLOSE cur;
  END $$

CALL remove_constraints() $$
CALL remove_indexes() $$

-- DROP PROCEDURE removeConstraints

CREATE TABLE `CustomFieldType` (
  `id`   TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50)         NOT NULL,
  `text` VARCHAR(50)         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_CustomFieldType_01` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8 $$

INSERT INTO CustomFieldType (id, name, text)
VALUES (1, 'text', 'Texto'), (2, 'password', 'Clave'), (3, 'date', 'Fecha'), (4, 'number', 'Número'),
  (5, 'email', 'Email'), (6, 'telephone', 'Teléfono'), (7, 'url', 'URL'), (8, 'color', 'Color'), (9, 'wiki', 'Wiki'),
  (10, 'textarea', 'Área de Texto') $$

-- CustomFieldData
ALTER TABLE customFieldsData
  CHANGE customfielddata_defId definitionId INT(10) UNSIGNED NOT NULL,
  CHANGE customfielddata_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE customfielddata_moduleId moduleId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE customfielddata_itemId itemId INT(10) UNSIGNED NOT NULL,
  CHANGE customfielddata_data data LONGBLOB,
  CHANGE customfielddata_key `key` VARBINARY(1000),
  ADD INDEX idx_CustomFieldData_01 (definitionId ASC),
  ADD INDEX idx_CustomFieldData_02 (itemId ASC, moduleId ASC),
  ADD INDEX idx_CustomFieldData_03 (moduleId ASC),
  COLLATE utf8_unicode_ci,
RENAME TO CustomFieldData $$

-- CustomFieldDefinition
ALTER TABLE customFieldsDef
  ADD required TINYINT(1) UNSIGNED NULL,
  ADD help VARCHAR(255) NULL,
  ADD showInList TINYINT(1) UNSIGNED NULL,
  ADD name VARCHAR(100) NOT NULL
  AFTER id,
  ADD typeId TINYINT UNSIGNED NOT NULL,
  ADD isEncrypted tinyint(1) unsigned DEFAULT 1 NULL,
  CHANGE customfielddef_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE customfielddef_module moduleId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE customfielddef_field field BLOB NULL,
  COLLATE utf8_unicode_ci,
RENAME TO CustomFieldDefinition $$

-- EventLog
ALTER TABLE log
  CHANGE log_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE log_date date INT(10) UNSIGNED NOT NULL,
  CHANGE log_login login VARCHAR(25),
  CHANGE log_userId userId SMALLINT(5) UNSIGNED,
  CHANGE log_ipAddress ipAddress VARCHAR(45) NOT NULL,
  CHANGE log_action action VARCHAR(50) NOT NULL,
  CHANGE log_description description TEXT,
  CHANGE log_level level VARCHAR(20) NOT NULL,
  COLLATE utf8_unicode_ci,
RENAME TO EventLog $$

-- Track
ALTER TABLE track
  CHANGE track_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE track_userId userId SMALLINT(5) UNSIGNED,
  CHANGE track_source source VARCHAR(100) NOT NULL,
  CHANGE track_time time INT(10) UNSIGNED NOT NULL,
  CHANGE track_ipv4 ipv4 BINARY(4) NOT NULL,
  CHANGE track_ipv6 ipv6 BINARY(16),
  ADD INDEX `idx_Track_01` (userId ASC),
  ADD INDEX `idx_Track_02` (time ASC, ipv4 ASC, ipv6 ASC, source ASC),
  COLLATE utf8_unicode_ci,
RENAME TO Track $$

-- AccountFile
ALTER TABLE accFiles
  CHANGE accfile_accountId accountId MEDIUMINT(5) UNSIGNED NOT NULL,
  CHANGE accfile_id id INT(11) NOT NULL AUTO_INCREMENT,
  CHANGE accfile_name name VARCHAR(100) NOT NULL,
  CHANGE accfile_type type VARCHAR(100) NOT NULL,
  CHANGE accfile_size size INT(11) NOT NULL,
  CHANGE accfile_content content MEDIUMBLOB NOT NULL,
  CHANGE accfile_extension extension VARCHAR(10) NOT NULL,
  CHANGE accFile_thumb thumb MEDIUMBLOB,
  ADD INDEX idx_AccountFile_01 (accountId ASC),
  COLLATE utf8_unicode_ci,
RENAME TO AccountFile $$

-- Fix NULL user's hash salt
UPDATE usrData SET user_hashSalt = '' WHERE user_hashSalt IS NULL $$

-- User
ALTER TABLE usrData
  DROP user_secGroupId,
  CHANGE user_id id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE user_name name VARCHAR(80) NOT NULL,
  CHANGE user_groupId userGroupId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE user_login login VARCHAR(50) NOT NULL,
  ADD ssoLogin VARCHAR(100) NULL
  AFTER login,
  CHANGE user_pass pass VARBINARY(1000) NOT NULL,
  CHANGE user_mPass mPass VARBINARY(1000) DEFAULT NULL,
  CHANGE user_mKey mKey VARBINARY(1000) DEFAULT NULL,
  CHANGE user_email email VARCHAR(80),
  CHANGE user_notes notes TEXT,
  CHANGE user_count loginCount INT(10) UNSIGNED NOT NULL DEFAULT '0',
  CHANGE user_profileId userProfileId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE user_lastLogin lastLogin DATETIME,
  CHANGE user_lastUpdate lastUpdate DATETIME,
  CHANGE user_lastUpdateMPass lastUpdateMPass INT(11) UNSIGNED NOT NULL DEFAULT '0',
  CHANGE user_isAdminApp isAdminApp TINYINT(1) DEFAULT 0,
  CHANGE user_isAdminAcc isAdminAcc TINYINT(1) DEFAULT 0,
  CHANGE user_isLdap isLdap TINYINT(1) DEFAULT 0,
  CHANGE user_isDisabled isDisabled TINYINT(1) DEFAULT 0,
  CHANGE user_hashSalt hashSalt VARBINARY(128) NOT NULL,
  CHANGE user_isMigrate isMigrate TINYINT(1) DEFAULT 0,
  CHANGE user_isChangePass isChangePass TINYINT(1) DEFAULT 0,
  CHANGE user_isChangedPass isChangedPass TINYINT(1) DEFAULT 0,
  CHANGE user_preferences preferences BLOB,
  ADD INDEX idx_User_01 (pass ASC),
  ADD UNIQUE INDEX `uk_User_01` (`login`, `ssoLogin`),
  COLLATE utf8_unicode_ci,
RENAME TO User $$

-- UserProfile
ALTER TABLE usrProfiles
  CHANGE userprofile_id id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE userprofile_name name VARCHAR(45) NOT NULL,
  CHANGE userProfile_profile profile BLOB NOT NULL,
  COLLATE utf8_unicode_ci,
RENAME TO UserProfile $$

-- Notice
ALTER TABLE notices
  CHANGE notice_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE notice_type type VARCHAR(100),
  CHANGE notice_component component VARCHAR(100) NOT NULL,
  CHANGE notice_description description VARCHAR(500) NOT NULL,
  CHANGE notice_date date INT(10) UNSIGNED NOT NULL,
  CHANGE notice_checked checked TINYINT(1) DEFAULT 0,
  CHANGE notice_userId userId SMALLINT(5) UNSIGNED,
  CHANGE notice_sticky sticky TINYINT(1) DEFAULT 0,
  CHANGE notice_onlyAdmin onlyAdmin TINYINT(1) DEFAULT 0,
  ADD INDEX idx_Notification_01 (userId ASC, checked ASC, date ASC),
  ADD INDEX idx_Notification_02 (component ASC, date ASC, checked ASC, userId ASC),
  COLLATE utf8_unicode_ci,
RENAME TO Notification $$

-- Plugin
ALTER TABLE `plugins`
  CHANGE plugin_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE plugin_name name VARCHAR(100) NOT NULL,
  CHANGE plugin_data data VARBINARY(5000),
  CHANGE plugin_enabled enabled TINYINT(1) NOT NULL DEFAULT 0,
  ADD available TINYINT(1) DEFAULT 0,
  ADD UNIQUE INDEX uk_Plugin_01 (name ASC),
  COLLATE utf8_unicode_ci,
RENAME TO Plugin $$

-- PublicLink
ALTER TABLE publicLinks
  ADD COLUMN `userId` SMALLINT(5) UNSIGNED NOT NULL,
  ADD COLUMN `typeId` INT(10) UNSIGNED NOT NULL
  AFTER `userId`,
  ADD COLUMN `notify` TINYINT(1) NULL DEFAULT 0
  AFTER `typeId`,
  ADD COLUMN `dateAdd` INT UNSIGNED NOT NULL
  AFTER `notify`,
  ADD COLUMN `dateExpire` INT UNSIGNED NOT NULL
  AFTER `dateAdd`,
  ADD COLUMN `dateUpdate` INT UNSIGNED DEFAULT 0
  AFTER `dateExpire`,
  ADD COLUMN `countViews` SMALLINT(5) UNSIGNED NULL DEFAULT 0
  AFTER `dateUpdate`,
  ADD COLUMN `totalCountViews` MEDIUMINT UNSIGNED NULL DEFAULT 0
  AFTER `countViews`,
  ADD COLUMN `maxCountViews` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0
  AFTER `totalCountViews`,
  ADD COLUMN `useinfo` BLOB NULL
  AFTER `maxCountViews`,
  CHANGE publicLink_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE publicLink_itemId itemId INT(10) UNSIGNED NOT NULL,
  CHANGE publicLink_hash `hash` VARBINARY(100) NOT NULL,
  CHANGE publicLink_linkData `data` LONGBLOB,
  ADD UNIQUE INDEX uk_PublicLink_01 (`hash` ASC),
  ADD UNIQUE INDEX uk_PublicLink_02 (itemId ASC),
  COLLATE utf8_unicode_ci,
RENAME TO PublicLink $$

-- Fix missing categories hash
UPDATE categories
SET category_hash = MD5(CONCAT(category_id, category_name))
WHERE category_hash IS NULL OR category_hash = '0' $$

-- Category
ALTER TABLE categories
  CHANGE category_id id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE category_name name VARCHAR(50) NOT NULL,
  CHANGE category_hash hash VARBINARY(40) NOT NULL,
  CHANGE category_description description VARCHAR(255),
  ADD UNIQUE INDEX uk_Category_01 (`hash` ASC),
  COLLATE utf8_unicode_ci,
RENAME TO Category $$

-- Config
ALTER TABLE config
  CHANGE config_parameter parameter VARCHAR(50) NOT NULL,
  CHANGE config_value VALUE VARCHAR(4000),
  ADD PRIMARY KEY (parameter),
  COLLATE utf8_unicode_ci,
RENAME TO Config $$

-- Fix missing customers hash
UPDATE customers
SET customer_hash = MD5(CONCAT(customer_id, customer_name))
WHERE customer_hash IS NULL OR customer_hash = '' $$

-- Customer
ALTER TABLE customers
  CHANGE customer_id id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE customer_name name VARCHAR(100) NOT NULL,
  CHANGE customer_hash hash VARBINARY(40) NOT NULL,
  CHANGE customer_description description VARCHAR(255),
  ADD `isGlobal` TINYINT(1) DEFAULT 0,
  ADD INDEX uk_Client_01 (`hash` ASC),
  COLLATE utf8_unicode_ci,
RENAME TO Client $$

-- Account
ALTER TABLE accounts
  CHANGE account_id id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE account_userGroupId userGroupId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE account_userId userId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE account_userEditId userEditId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE account_customerId clientId MEDIUMINT(8) UNSIGNED NOT NULL,
  CHANGE account_name name VARCHAR(50) NOT NULL,
  CHANGE account_categoryId categoryId MEDIUMINT UNSIGNED NOT NULL,
  CHANGE account_login login VARCHAR(50),
  CHANGE account_url url VARCHAR(255),
  CHANGE account_pass pass VARBINARY(1000) NOT NULL,
  CHANGE account_key `key` VARBINARY(1000) NOT NULL,
  CHANGE account_notes notes TEXT,
  CHANGE account_countView countView INT(10) UNSIGNED NOT NULL DEFAULT 0,
  CHANGE account_countDecrypt countDecrypt INT(10) UNSIGNED NOT NULL DEFAULT 0,
  CHANGE account_dateAdd dateAdd DATETIME NOT NULL,
  CHANGE account_dateEdit dateEdit DATETIME,
  CHANGE account_otherGroupEdit otherUserGroupEdit TINYINT(1) DEFAULT 0,
  CHANGE account_otherUserEdit otherUserEdit TINYINT(1) DEFAULT 0,
  CHANGE account_isPrivate isPrivate TINYINT(1) DEFAULT 0,
  CHANGE account_isPrivateGroup isPrivateGroup TINYINT(1) DEFAULT 0,
  CHANGE account_passDate passDate INT(11) UNSIGNED,
  CHANGE account_passDateChange passDateChange INT(11) UNSIGNED,
  CHANGE account_parentId parentId MEDIUMINT UNSIGNED,
  ADD INDEX idx_Account_01 (`categoryId` ASC),
  ADD INDEX idx_Account_02 (`userGroupId` ASC, `userId` ASC),
  ADD INDEX idx_Account_03 (`clientId` ASC),
  ADD INDEX idx_Account_04 (`parentId` ASC),
  COLLATE utf8_unicode_ci,
RENAME TO Account $$

-- AccountToFavorite
ALTER TABLE accFavorites
  CHANGE accfavorite_accountId accountId MEDIUMINT UNSIGNED NOT NULL,
  CHANGE accfavorite_userId userId SMALLINT(5) UNSIGNED NOT NULL,
  ADD INDEX idx_AccountToFavorite_01 (accountId ASC, userId ASC),
  COLLATE utf8_unicode_ci,
RENAME TO AccountToFavorite $$

-- AccountHistory
ALTER TABLE accHistory
  CHANGE acchistory_id id INT(11) NOT NULL AUTO_INCREMENT,
  CHANGE acchistory_accountId accountId MEDIUMINT UNSIGNED NOT NULL,
  CHANGE acchistory_userGroupId userGroupId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE acchistory_userId userId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE acchistory_userEditId userEditId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE acchistory_customerId clientId MEDIUMINT UNSIGNED NOT NULL,
  CHANGE acchistory_name name VARCHAR(255) NOT NULL,
  CHANGE acchistory_categoryId categoryId MEDIUMINT UNSIGNED NOT NULL,
  CHANGE acchistory_login login VARCHAR(50),
  CHANGE acchistory_url url VARCHAR(255),
  CHANGE acchistory_pass pass VARBINARY(1000) NOT NULL,
  CHANGE acchistory_key `key` VARBINARY(1000) NOT NULL,
  CHANGE acchistory_notes notes TEXT NOT NULL,
  CHANGE acchistory_countView countView INT(10) UNSIGNED NOT NULL DEFAULT 0,
  CHANGE acchistory_countDecrypt countDecrypt INT(10) UNSIGNED NOT NULL DEFAULT 0,
  CHANGE acchistory_dateAdd dateAdd DATETIME NOT NULL,
  CHANGE acchistory_dateEdit dateEdit DATETIME,
  CHANGE acchistory_isModify isModify TINYINT(1) DEFAULT 0,
  CHANGE acchistory_isDeleted isDeleted TINYINT(1) DEFAULT 0,
  CHANGE acchistory_mPassHash mPassHash VARBINARY(255) NOT NULL,
  CHANGE accHistory_otherUserEdit otherUserEdit TINYINT(1) DEFAULT 0,
  CHANGE accHistory_otherGroupEdit otherUserGroupEdit TINYINT(1) DEFAULT 0,
  CHANGE accHistory_passDate passDate INT(10) UNSIGNED,
  CHANGE accHistory_passDateChange passDateChange INT(10) UNSIGNED,
  CHANGE accHistory_parentId parentId MEDIUMINT UNSIGNED,
  CHANGE accHistory_isPrivate isPrivate TINYINT(1) DEFAULT 0,
  CHANGE accHistory_isPrivateGroup isPrivateGroup TINYINT(1) DEFAULT 0,
  ADD INDEX idx_AccountHistory_01 (accountId ASC),
  ADD INDEX idx_AccountHistory_02 (parentId ASC),
  COLLATE utf8_unicode_ci,
RENAME TO AccountHistory $$


-- Fix missing tags hash
UPDATE tags
SET tag_hash = MD5(CONCAT(tag_id, tag_name))
WHERE tag_hash IS NULL OR tag_hash = '' $$

-- Tag
ALTER TABLE tags
  CHANGE tag_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE tag_name name VARCHAR(45) NOT NULL,
  CHANGE tag_hash hash VARBINARY(40) NOT NULL,
  ADD UNIQUE INDEX uk_Tag_01 (`hash` ASC),
  ADD INDEX idx_Tag_01 (`name` ASC),
  COLLATE utf8_unicode_ci,
RENAME TO Tag $$

-- AccountToTag
ALTER TABLE accTags
  CHANGE acctag_accountId accountId MEDIUMINT UNSIGNED NOT NULL,
  CHANGE acctag_tagId tagId INT(10) UNSIGNED NOT NULL,
  COLLATE utf8_unicode_ci,
RENAME TO AccountToTag $$

-- AccountToUserGroup
ALTER TABLE accGroups
  ADD isEdit tinyint(1) unsigned DEFAULT 0 NULL,
  CHANGE accgroup_accountId accountId MEDIUMINT UNSIGNED NOT NULL,
  CHANGE accgroup_groupId userGroupId SMALLINT(5) UNSIGNED NOT NULL,
  ADD INDEX idx_AccountToUserGroup_01 (`accountId` ASC),
  COLLATE utf8_unicode_ci,
RENAME TO AccountToUserGroup $$

-- AccountToUser
ALTER TABLE accUsers
  ADD isEdit tinyint(1) unsigned DEFAULT 0 NULL,
  CHANGE accuser_accountId accountId MEDIUMINT UNSIGNED NOT NULL,
  CHANGE accuser_userId userId SMALLINT(5) UNSIGNED NOT NULL,
  ADD INDEX idx_AccountToUser_01 (accountId ASC),
  COLLATE utf8_unicode_ci,
RENAME TO AccountToUser $$

-- UserToUserGroup
ALTER TABLE usrToGroups
  CHANGE usertogroup_userId userId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE usertogroup_groupId userGroupId SMALLINT(5) UNSIGNED NOT NULL,
  ADD INDEX idx_UserToUserGroup_01 (userId ASC),
  COLLATE utf8_unicode_ci,
RENAME TO UserToUserGroup $$

-- UserGroup
ALTER TABLE usrGroups
  CHANGE usergroup_id id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE usergroup_name name VARCHAR(50) NOT NULL,
  CHANGE usergroup_description description VARCHAR(255),
  COLLATE utf8_unicode_ci,
RENAME TO UserGroup $$

-- AuthToken
ALTER TABLE authTokens
  CHANGE authtoken_id id INT(11) NOT NULL AUTO_INCREMENT,
  CHANGE authtoken_userId userId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE authtoken_token token VARBINARY(100) NOT NULL,
  CHANGE authtoken_actionId actionId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE authtoken_createdBy createdBy SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE authtoken_startDate startDate INT(10) UNSIGNED NOT NULL,
  CHANGE authtoken_vault vault VARBINARY(2000),
  CHANGE authtoken_hash hash VARBINARY(1000),
  ADD UNIQUE INDEX uk_AuthToken_01 (token ASC, actionId ASC),
  ADD INDEX idx_AuthToken_01 (userId ASC, actionId ASC, token ASC),
  COLLATE utf8_unicode_ci,
RENAME TO AuthToken $$

-- UserPassRecover
ALTER TABLE usrPassRecover
  CHANGE userpassr_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE userpassr_userId userId SMALLINT(5) UNSIGNED NOT NULL,
  CHANGE userpassr_hash hash VARBINARY(128) NOT NULL,
  CHANGE userpassr_date date INT(10) UNSIGNED NOT NULL,
  CHANGE userpassr_used used TINYINT(1) DEFAULT 0,
  ADD INDEX idx_UserPassRecover_01 (userId ASC, date ASC),
  COLLATE utf8_unicode_ci,
RENAME TO UserPassRecover $$

-- Views
CREATE OR REPLACE VIEW account_search_v AS
  SELECT
    `Account`.`id`                                       AS `id`,
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
    LEFT JOIN `PublicLink` ON `Account`.`id` = `PublicLink`.`itemId` $$

CREATE OR REPLACE VIEW account_data_v AS
  SELECT
    `Account`.`id`                              AS `id`,
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
  FROM ((((((`Account`
    LEFT JOIN `Category`
      ON ((`Account`.`categoryId` = `Category`.`id`))) INNER JOIN
    `UserGroup` `ug` ON ((`Account`.`userGroupId` = `ug`.`id`))) INNER JOIN
    `User` `u1` ON ((`Account`.`userId` = `u1`.`id`))) INNER JOIN
    `User` `u2` ON ((`Account`.`userEditId` = `u2`.`id`))) LEFT JOIN
    `Client`
      ON ((`Account`.`clientId` = `Client`.`id`))) LEFT JOIN
    `PublicLink` ON ((`Account`.`id` = `PublicLink`.`itemId`))) $$

-- Foreign Keys
CREATE INDEX fk_Account_userId
  ON Account (userId) $$

CREATE INDEX fk_Account_userEditId
  ON Account (userEditId) $$

ALTER TABLE Account
  ADD CONSTRAINT fk_Account_userGroupId
FOREIGN KEY (userGroupId) REFERENCES UserGroup (id) $$

ALTER TABLE Account
  ADD CONSTRAINT fk_Account_userId
FOREIGN KEY (userId) REFERENCES User (id) $$

ALTER TABLE Account
  ADD CONSTRAINT fk_Account_userEditId
FOREIGN KEY (userEditId) REFERENCES User (id) $$

ALTER TABLE Account
  ADD CONSTRAINT fk_Account_clientId
FOREIGN KEY (clientId) REFERENCES Client (id) $$

ALTER TABLE Account
  ADD CONSTRAINT fk_Account_categoryId
FOREIGN KEY (categoryId) REFERENCES Category (id) $$

ALTER TABLE AccountFile
  ADD CONSTRAINT fk_AccountFile_accountId
FOREIGN KEY (accountId) REFERENCES Account (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

CREATE INDEX fk_AccountHistory_userGroupId
  ON AccountHistory (userGroupId) $$

CREATE INDEX fk_AccountHistory_userId
  ON AccountHistory (userId) $$

CREATE INDEX fk_AccountHistory_userEditId
  ON AccountHistory (userEditId) $$

CREATE INDEX fk_AccountHistory_clientId
  ON AccountHistory (clientId) $$

CREATE INDEX fk_AccountHistory_categoryId
  ON AccountHistory (categoryId) $$

ALTER TABLE AccountHistory
  ADD CONSTRAINT fk_AccountHistory_userGroupId
FOREIGN KEY (userGroupId) REFERENCES UserGroup (id) $$

ALTER TABLE AccountHistory
  ADD CONSTRAINT fk_AccountHistory_userId
FOREIGN KEY (userId) REFERENCES User (id) $$

ALTER TABLE AccountHistory
  ADD CONSTRAINT fk_AccountHistory_userEditId
FOREIGN KEY (userEditId) REFERENCES User (id) $$

ALTER TABLE AccountHistory
  ADD CONSTRAINT fk_AccountHistory_clientId
FOREIGN KEY (clientId) REFERENCES Client (id) $$

ALTER TABLE AccountHistory
  ADD CONSTRAINT fk_AccountHistory_categoryId
FOREIGN KEY (categoryId) REFERENCES Category (id) $$

CREATE INDEX fk_AccountToFavorite_userId
  ON AccountToFavorite (userId) $$

ALTER TABLE AccountToFavorite
  ADD CONSTRAINT fk_AccountToFavorite_accountId
FOREIGN KEY (accountId) REFERENCES Account (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

ALTER TABLE AccountToFavorite
  ADD CONSTRAINT fk_AccountToFavorite_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

ALTER TABLE AccountToFavorite
  ADD PRIMARY KEY (accountId, userId) $$

CREATE INDEX fk_AccountToUserGroup_userGroupId
  ON AccountToUserGroup (userGroupId) $$

ALTER TABLE AccountToUserGroup
  ADD CONSTRAINT fk_AccountToUserGroup_accountId
FOREIGN KEY (accountId) REFERENCES Account (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

ALTER TABLE AccountToUserGroup
  ADD CONSTRAINT fk_AccountToUserGroup_userGroupId
FOREIGN KEY (userGroupId) REFERENCES UserGroup (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

CALL drop_primary('AccountToUserGroup') $$

ALTER TABLE AccountToUserGroup
  ADD PRIMARY KEY (accountId, userGroupId) $$

CREATE INDEX fk_AccountToTag_accountId
  ON AccountToTag (accountId) $$

CREATE INDEX fk_AccountToTag_tagId
  ON AccountToTag (tagId) $$

-- Fix duplicated tags
CREATE TEMPORARY TABLE IF NOT EXISTS tmp_tags AS (SELECT
                                                    AT.accountId,
                                                    AT.tagId
                                                  FROM AccountToTag AT
                                                  GROUP BY AT.accountId, AT.tagId
                                                  HAVING COUNT(*) > 1) $$

DELETE a FROM AccountToTag AS a
  INNER JOIN tmp_tags AS tmp ON tmp.accountId = a.accountId AND tmp.tagId = a.tagId $$

INSERT INTO AccountToTag SELECT
                           accountId,
                           tagId
                         FROM tmp_tags $$

DROP TEMPORARY TABLE tmp_tags $$

ALTER TABLE AccountToTag
  ADD CONSTRAINT fk_AccountToTag_accountId
FOREIGN KEY (accountId) REFERENCES Account (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

ALTER TABLE AccountToTag
  ADD CONSTRAINT fk_AccountToTag_tagId
FOREIGN KEY (tagId) REFERENCES Tag (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

ALTER TABLE AccountToTag
  ADD PRIMARY KEY (accountId, tagId) $$

CREATE INDEX fk_AccountToUser_userId
  ON AccountToUser (userId) $$

ALTER TABLE AccountToUser
  ADD CONSTRAINT fk_AccountToUser_accountId
FOREIGN KEY (accountId) REFERENCES Account (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

ALTER TABLE AccountToUser
  ADD CONSTRAINT fk_AccountToUser_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

ALTER TABLE AccountToUser
  ADD PRIMARY KEY (accountId, userId) $$

CREATE INDEX fk_AuthToken_actionId
  ON AuthToken (actionId) $$

-- Fix missing user's id
DELETE FROM AuthToken
WHERE userId NOT IN (SELECT id
                     FROM User) $$

ALTER TABLE AuthToken
  ADD CONSTRAINT fk_AuthToken_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

ALTER TABLE CustomFieldData
  ADD CONSTRAINT fk_CustomFieldData_definitionId
FOREIGN KEY (definitionId) REFERENCES CustomFieldDefinition (id) $$

CREATE INDEX fk_CustomFieldDefinition_typeId
  ON CustomFieldDefinition (typeId) $$

ALTER TABLE CustomFieldDefinition
  ADD CONSTRAINT fk_CustomFieldDefinition_typeId
FOREIGN KEY (typeId) REFERENCES CustomFieldType (id)
  ON UPDATE CASCADE $$

ALTER TABLE Notification
  ADD CONSTRAINT fk_Notification_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

CREATE INDEX fk_PublicLink_userId
  ON PublicLink (userId) $$

ALTER TABLE PublicLink
  ADD CONSTRAINT fk_PublicLink_userId
FOREIGN KEY (userId) REFERENCES User (id) $$

CREATE INDEX fk_User_userGroupId
  ON User (userGroupId) $$

CREATE INDEX fk_User_userProfileId
  ON User (userProfileId) $$

ALTER TABLE User
  ADD CONSTRAINT fk_User_userGroupId
FOREIGN KEY (userGroupId) REFERENCES UserGroup (id) $$

ALTER TABLE User
  ADD CONSTRAINT fk_User_userProfileId
FOREIGN KEY (userProfileId) REFERENCES UserProfile (id) $$

ALTER TABLE UserPassRecover
  ADD CONSTRAINT fk_UserPassRecover_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

CREATE INDEX fk_UserToGroup_userGroupId
  ON UserToUserGroup (userGroupId) $$

ALTER TABLE UserToUserGroup
  ADD CONSTRAINT fk_UserToGroup_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

ALTER TABLE UserToUserGroup
  ADD CONSTRAINT fk_UserToGroup_userGroupId
FOREIGN KEY (userGroupId) REFERENCES UserGroup (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE $$

-- Update AccountToUser permissions
UPDATE AccountToUser AU
  INNER JOIN
  Account A ON AU.accountId = A.id
SET
  AU.isEdit = 1
WHERE
  A.otherUserEdit = 1 $$

-- Update AccountToUserGroup permissions
UPDATE AccountToUserGroup AUG
  INNER JOIN
  Account A ON AUG.accountId = A.id
SET
  AUG.isEdit = 1
WHERE
  A.otherUserGroupEdit = 1 $$

SET FOREIGN_KEY_CHECKS = 1 $$
DELIMITER ;