ALTER TABLE `usrData` ENGINE = InnoDB;
ALTER TABLE `accFiles` ENGINE = InnoDB;
ALTER TABLE `accGroups` ENGINE = InnoDB;
ALTER TABLE `accHistory` ENGINE = InnoDB;
ALTER TABLE `accUsers` ENGINE = InnoDB;
ALTER TABLE `categories` ENGINE = InnoDB;
ALTER TABLE `config` ENGINE = InnoDB;
ALTER TABLE `customers` ENGINE = InnoDB;
ALTER TABLE `log` ENGINE = InnoDB;
ALTER TABLE `usrGroups` ENGINE = InnoDB;
ALTER TABLE `usrPassRecover` ENGINE = InnoDB;
ALTER TABLE `usrProfiles` ENGINE = InnoDB;
ALTER TABLE `accounts` ENGINE = InnoDB;

ALTER TABLE `log` ADD log_level VARCHAR(20) NOT NULL;
ALTER TABLE `config` CHANGE config_value config_value VARCHAR(2000);

CREATE TABLE IF NOT EXISTS `publicLinks` (
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

CREATE TABLE IF NOT EXISTS `accFavorites` (
  `accfavorite_accountId` smallint(5) unsigned NOT NULL,
  `accfavorite_userId` smallint(5) unsigned NOT NULL,
  KEY `fk_accFavorites_accounts_idx` (`accfavorite_accountId`),
  KEY `fk_accFavorites_users_idx` (`accfavorite_userId`),
  KEY `search_idx` (`accfavorite_accountId`,`accfavorite_userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tags` (
  `tag_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tag_name` VARCHAR(45)  NOT NULL,
  `tag_hash` BINARY(40)   NOT NULL,
  PRIMARY KEY (`tag_id`),
  INDEX `IDX_name` (`tag_name` ASC),
  UNIQUE INDEX `tag_hash_UNIQUE` (`tag_hash` ASC)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `accTags` (
  `acctag_accountId` SMALLINT(10) UNSIGNED NOT NULL,
  `acctag_tagId`     INT UNSIGNED NOT NULL,
  INDEX `IDX_id` (`acctag_accountId` ASC, `acctag_tagId` ASC)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `plugins` (
  `plugin_id`      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `plugin_name`    VARCHAR(100)    NOT NULL,
  `plugin_data`    VARBINARY(5000) NULL,
  `plugin_enabled` BIT(1)          NOT NULL DEFAULT b'0',
  PRIMARY KEY (`plugin_id`),
  UNIQUE INDEX `plugin_name_UNIQUE` (`plugin_name` ASC)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `notices` (
  `notice_id`          INT UNSIGNED         NOT NULL AUTO_INCREMENT,
  `notice_type`        VARCHAR(100)         NULL,
  `notice_component`   VARCHAR(100)         NOT NULL,
  `notice_description` VARCHAR(500)         NOT NULL,
  `notice_date`        INT UNSIGNED         NOT NULL,
  `notice_checked`     BIT(1)               NULL     DEFAULT b'0',
  `notice_userId`      SMALLINT(5) UNSIGNED NULL,
  `notice_sticky`      BIT(1)               NULL     DEFAULT b'0',
  `notice_onlyAdmin`   BIT(1)               NULL     DEFAULT b'0',
  PRIMARY KEY (`notice_id`),
  INDEX `IDX_userId` (`notice_userId` ASC, `notice_checked` ASC, `notice_date` ASC),
  INDEX `IDX_component` (`notice_component` ASC, `notice_date` ASC, `notice_checked` ASC, `notice_userId` ASC)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;