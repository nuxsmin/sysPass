ALTER TABLE `accounts`
  CHANGE COLUMN `account_IV` `account_key` VARBINARY(1000) NOT NULL ;
ALTER TABLE `accHistory`
  CHANGE COLUMN `acchistory_IV` `acchistory_key` VARBINARY(1000) NOT NULL ;
ALTER TABLE `customFieldsData`
  CHANGE COLUMN `customfielddata_iv` `customfielddata_key` VARBINARY(1000) NOT NULL;
ALTER TABLE `usrData`
  CHANGE COLUMN `user_mPass` `user_mPass` VARBINARY(1000) NULL DEFAULT NULL,
  CHANGE COLUMN `user_mIV` `user_mKey` VARBINARY(1000) NULL DEFAULT NULL;

