-- To 1.1.23;
CREATE TABLE `usrPassRecover` (
  `userpassr_id`     INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `userpassr_userId` SMALLINT UNSIGNED NOT NULL,
  `userpassr_hash`   VARBINARY(40)     NOT NULL,
  `userpassr_date`   INT UNSIGNED      NOT NULL,
  `userpassr_used`   BIT(1)            NOT NULL DEFAULT b'0',
  PRIMARY KEY (`userpassr_id`),
  INDEX `IDX_userId` (`userpassr_userId` ASC, `userpassr_date` ASC)
)
  DEFAULT CHARSET = utf8;
ALTER TABLE `log`
  ADD COLUMN `log_ipAddress` VARCHAR(45) NOT NULL
  AFTER `log_userId`;
ALTER TABLE `usrData`
  ADD COLUMN `user_isChangePass` BIT(1) NULL DEFAULT b'0'
  AFTER `user_isMigrate`;