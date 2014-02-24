-- To 1.1
ALTER TABLE `accFiles` CHANGE COLUMN `accfile_name` `accfile_name` VARCHAR(100) NOT NULL
ALTER TABLE `accounts` ADD COLUMN `account_otherGroupEdit` BIT(1) NULL DEFAULT 0 AFTER `account_dateEdit`, ADD COLUMN `account_otherUserEdit` BIT(1) NULL DEFAULT 0 AFTER `account_otherGroupEdit`;
CREATE TABLE `accUsers` (`accuser_id` INT NOT NULL AUTO_INCREMENT,`accuser_accountId` INT(10) UNSIGNED NOT NULL,`accuser_userId` INT(10) UNSIGNED NOT NULL, PRIMARY KEY (`accuser_id`), INDEX `idx_account` (`accuser_accountId` ASC));
ALTER TABLE `accHistory` ADD COLUMN `accHistory_otherUserEdit` BIT NULL AFTER `acchistory_mPassHash`, ADD COLUMN `accHistory_otherGroupEdit` VARCHAR(45) NULL AFTER `accHistory_otherUserEdit`;
ALTER TABLE `accFiles` CHANGE COLUMN `accfile_type` `accfile_type` VARCHAR(100) NOT NULL ;
-- To 1.1.2.1
ALTER TABLE `categories` ADD COLUMN `category_description` VARCHAR(255) NULL AFTER `category_name`;
ALTER TABLE `usrProfiles` ADD COLUMN `userProfile_pAppMgmtMenu` BIT(1) NULL DEFAULT b'0' AFTER `userProfile_pUsersMenu`,CHANGE COLUMN `userProfile_pConfigCategories` `userProfile_pAppMgmtCategories` BIT(1) NULL DEFAULT b'0' AFTER `userProfile_pAppMgmtMenu`,ADD COLUMN `userProfile_pAppMgmtCustomers` BIT(1) NULL DEFAULT b'0' AFTER `userProfile_pAppMgmtCategories`;
-- To 1.1.2.2
ALTER TABLE `usrData` CHANGE COLUMN `user_login` `user_login` VARCHAR(50) NOT NULL ,CHANGE COLUMN `user_email` `user_email` VARCHAR(80) NULL DEFAULT NULL ;
-- To 1.1.2.3
CREATE TABLE `usrPassRecover` (`userpassr_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `userpassr_userId` SMALLINT UNSIGNED NOT NULL,`userpassr_hash` VARBINARY(40) NOT NULL,`userpassr_date` INT UNSIGNED NOT NULL,`userpassr_used` BIT(1) NOT NULL DEFAULT b\'0\', PRIMARY KEY (`userpassr_id`),INDEX `IDX_userId` (`userpassr_userId` ASC, `userpassr_date` ASC)) DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci;
ALTER TABLE `log` ADD COLUMN `log_ipAddress` VARCHAR(45) NOT NULL AFTER `log_userId`;
ALTER TABLE `usrData` ADD COLUMN `user_isChangePass` BIT(1) NULL DEFAULT b'0' AFTER `user_isMigrate`;

