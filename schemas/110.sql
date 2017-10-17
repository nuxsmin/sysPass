-- To 1.1.0;
ALTER TABLE `accFiles`
  CHANGE COLUMN `accfile_name` `accfile_name` VARCHAR(100) NOT NULL;
ALTER TABLE `accounts`
  ADD COLUMN `account_otherGroupEdit` BIT(1) NULL DEFAULT 0
  AFTER `account_dateEdit`,
  ADD COLUMN `account_otherUserEdit` BIT(1) NULL DEFAULT 0
  AFTER `account_otherGroupEdit`;
CREATE TABLE `accUsers` (
  `accuser_id`        INT              NOT NULL AUTO_INCREMENT,
  `accuser_accountId` INT(10) UNSIGNED NOT NULL,
  `accuser_userId`    INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`accuser_id`),
  INDEX `idx_account` (`accuser_accountId` ASC)
)
  DEFAULT CHARSET = utf8;
ALTER TABLE `accHistory`
  ADD COLUMN `accHistory_otherUserEdit` BIT NULL
  AFTER `acchistory_mPassHash`,
  ADD COLUMN `accHistory_otherGroupEdit` VARCHAR(45) NULL
  AFTER `accHistory_otherUserEdit`;
ALTER TABLE `accFiles`
  CHANGE COLUMN `accfile_type` `accfile_type` VARCHAR(100) NOT NULL;