ALTER TABLE `accounts`
  CHANGE COLUMN `account_pass` `account_pass` VARBINARY(1000) NOT NULL,
  CHANGE COLUMN `account_IV` `account_key` VARBINARY(1000) NOT NULL;
ALTER TABLE `accHistory`
  CHANGE COLUMN `acchistory_pass` `acchistory_pass` VARBINARY(1000) NOT NULL,
  CHANGE COLUMN `acchistory_IV` `acchistory_key` VARBINARY(1000) NOT NULL;
ALTER TABLE `customFieldsData`
  CHANGE COLUMN `customfielddata_iv` `customfielddata_key` VARBINARY(1000) NOT NULL;
ALTER TABLE `usrData`
  CHANGE COLUMN `user_mPass` `user_mPass` VARBINARY(1000) NULL DEFAULT NULL,
  CHANGE COLUMN `user_mIV` `user_mKey` VARBINARY(1000) NULL DEFAULT NULL;
ALTER TABLE `authTokens`
  ADD COLUMN `authtoken_vault` VARBINARY(2000) NULL,
  ADD COLUMN `authtoken_hash` VARBINARY(100) NULL;
CREATE TABLE `track` (
  `track_id`     INT UNSIGNED         NOT NULL AUTO_INCREMENT,
  `track_userId` SMALLINT(5) UNSIGNED NULL,
  `track_source` VARCHAR(100)         NOT NULL,
  `track_time`   INT UNSIGNED         NOT NULL,
  `track_ipv4`   BINARY(4)            NOT NULL,
  `track_ipv6`   BINARY(16)           NULL,
  PRIMARY KEY (`track_id`),
  INDEX `IDX_userId` (`track_userId` ASC),
  INDEX `IDX_time-ip-source` (`track_time` ASC, `track_ipv4` ASC, `track_ipv6` ASC, `track_source` ASC)
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8;
ALTER TABLE `usrData`
  ADD COLUMN `user_isChangedPass` BIT(1) NULL DEFAULT b'0'
  AFTER `user_isChangePass`;

