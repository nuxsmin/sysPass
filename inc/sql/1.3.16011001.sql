-- To 1.3.16011001
CREATE TABLE `publicLinks` (
  publicLink_id       INT UNSIGNEDPRIMARY KEY NOT NULL AUTO_INCREMENT,
  publicLink_itemId   INT UNSIGNED,
  publicLink_hash     VARBINARY(100)  NOT NULL,
  publicLink_linkData LONGBLOB
);
ALTER TABLE `usrData` ENGINE = InnoDB ;
ALTER TABLE `accFiles` ENGINE = InnoDB ;
ALTER TABLE `accGroups` ENGINE = InnoDB ;
ALTER TABLE `accHistory` ENGINE = InnoDB ;
ALTER TABLE `accUsers` ENGINE = InnoDB ;
ALTER TABLE `categories` ENGINE = InnoDB ;
ALTER TABLE `config` ENGINE = InnoDB ;
ALTER TABLE `customers` ENGINE = InnoDB ;
ALTER TABLE `log` ENGINE = InnoDB;
ALTER TABLE `usrGroups` ENGINE = InnoDB ;
ALTER TABLE `usrPassRecover` ENGINE = InnoDB ;
ALTER TABLE `usrProfiles` ENGINE = InnoDB ;
ALTER TABLE `accounts`
ENGINE = InnoDB ,
DROP INDEX `IDX_searchTxt` ,
ADD INDEX `IDX_searchTxt` (`account_name` ASC, `account_login` ASC, `account_url` ASC);
CREATE UNIQUE INDEX unique_publicLink_accountId ON publicLinks (publicLink_itemId);
CREATE UNIQUE INDEX unique_publicLink_hash ON publicLinks (publicLink_hash);
ALTER TABLE `log` ADD log_level VARCHAR(20) NOT NULL;
ALTER TABLE `config` CHANGE config_value config_value VARCHAR(2000);
CREATE TABLE `accFavorites` (
  `accfavorite_accountId` SMALLINT UNSIGNED NOT NULL,
  `accfavorite_userId`    SMALLINT UNSIGNED NOT NULL,
  INDEX `fk_accFavorites_accounts_idx` (`accfavorite_accountId` ASC),
  INDEX `fk_accFavorites_users_idx` (`accfavorite_userId` ASC),
  INDEX `search_idx` (`accfavorite_accountId` ASC, `accfavorite_userId` ASC),
  CONSTRAINT `fk_accFavorites_accounts` FOREIGN KEY (`accfavorite_accountId`) REFERENCES `accounts` (`account_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_accFavorites_users` FOREIGN KEY (`accfavorite_userId`) REFERENCES `usrData` (`user_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;