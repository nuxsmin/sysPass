-- To 1.1.2.19;
ALTER TABLE `accounts`
  CHANGE COLUMN `account_pass` `account_pass` VARBINARY(255) NOT NULL;
ALTER TABLE `accHistory`
  CHANGE COLUMN `acchistory_pass` `acchistory_pass` VARBINARY(255) NOT NULL;