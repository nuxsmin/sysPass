-- To 1.1.2.13;
ALTER TABLE `usrData`
  CHANGE COLUMN `user_mPass` `user_mPass` VARBINARY(32) NULL DEFAULT NULL,
  CHANGE COLUMN `user_lastLogin` `user_lastLogin` DATETIME NULL DEFAULT NULL,
  CHANGE COLUMN `user_lastUpdate` `user_lastUpdate` DATETIME NULL DEFAULT NULL,
  CHANGE COLUMN `user_mIV` `user_mIV` VARBINARY(32) NULL;
ALTER TABLE `accounts`
  CHANGE COLUMN `account_login` `account_login` VARCHAR(50) NULL DEFAULT NULL;