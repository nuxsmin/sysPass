-- To 1.3.16100601
ALTER TABLE `syspass-dev`.`accHistory`
CHANGE COLUMN `acchistory_userId` `acchistory_userId` SMALLINT(5) UNSIGNED NOT NULL ,
CHANGE COLUMN `acchistory_userEditId` `acchistory_userEditId` SMALLINT(5) UNSIGNED NOT NULL ,
CHANGE COLUMN `acchistory_customerId` `acchistory_customerId` INT(10) UNSIGNED NOT NULL ,
CHANGE COLUMN `acchistory_categoryId` `acchistory_categoryId` SMALLINT(5) UNSIGNED NOT NULL ,
CHANGE COLUMN `acchistory_dateEdit` `acchistory_dateEdit` DATETIME NULL DEFAULT NULL ,
ADD INDEX `fk_accHistory_users_id_idx` (`acchistory_userId` ASC, `acchistory_userEditId` ASC),
ADD INDEX `fk_accHistory_categories_id_idx` (`acchistory_categoryId` ASC),
ADD INDEX `fk_accHistory_customers_id_idx` (`acchistory_customerId` ASC);

ALTER TABLE `syspass-dev`.`accTags`
CHANGE COLUMN `acctag_accountId` `acctag_accountId` SMALLINT(10) UNSIGNED NOT NULL ,
DROP INDEX `IDX_id` ,
ADD INDEX `IDX_id` (`acctag_accountId` ASC),
ADD INDEX `fk_accTags_tags_id_idx` (`acctag_tagId` ASC);

ALTER TABLE `syspass-dev`.`accUsers`
DROP COLUMN `accuser_id`,
CHANGE COLUMN `accuser_accountId` `accuser_accountId` SMALLINT(5) UNSIGNED NOT NULL ,
CHANGE COLUMN `accuser_userId` `accuser_userId` SMALLINT(5) UNSIGNED NOT NULL ,
ADD INDEX `fk_accUsers_users_id_idx` (`accuser_userId` ASC),
DROP PRIMARY KEY;

ALTER TABLE `syspass-dev`.`accViewLinks`
CHANGE COLUMN `accviewlinks_accountId` `accviewlinks_accountId` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
CHANGE COLUMN `accviewlinks_userId` `accviewlinks_userId` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
ADD INDEX `fk_accViewLinks_account_idx` (`accviewlinks_accountId` ASC),
ADD INDEX `fk_accViewLinks_user_id_idx` (`accviewlinks_userId` ASC);

ALTER TABLE `syspass-dev`.`accounts`
CHANGE COLUMN `account_id` `account_id` SMALLINT(5) UNSIGNED NOT NULL ,
CHANGE COLUMN `account_userId` `account_userId` SMALLINT(5) UNSIGNED NOT NULL ,
CHANGE COLUMN `account_userEditId` `account_userEditId` SMALLINT(5) UNSIGNED NOT NULL ,
CHANGE COLUMN `account_categoryId` `account_categoryId` SMALLINT(3) UNSIGNED NOT NULL ,
CHANGE COLUMN `account_dateEdit` `account_dateEdit` DATETIME NULL DEFAULT NULL ,
ADD INDEX `fk_accounts_user_id_idx` (`account_userId` ASC, `account_userEditId` ASC);

ALTER TABLE `syspass-dev`.`authTokens`
CHANGE COLUMN `authtoken_userId` `authtoken_userId` SMALLINT(5) UNSIGNED NOT NULL ,
ADD INDEX `fk_authTokens_users_id_idx` (`authtoken_userId` ASC, `authtoken_createdBy` ASC);

ALTER TABLE `syspass-dev`.`log`
CHANGE COLUMN `log_userId` `log_userId` SMALLINT(5) UNSIGNED NOT NULL ,
CHANGE COLUMN `log_description` `log_description` TEXT NULL DEFAULT NULL ,
ADD INDEX `fk_log_users_id_idx` (`log_userId` ASC);

ALTER TABLE `syspass-dev`.`usrData`
CHANGE COLUMN `user_groupId` `user_groupId` SMALLINT(3) UNSIGNED NOT NULL ,
CHANGE COLUMN `user_secGroupId` `user_secGroupId` SMALLINT(3) UNSIGNED NULL DEFAULT NULL ,
CHANGE COLUMN `user_profileId` `user_profileId` SMALLINT(5) UNSIGNED NOT NULL ,
CHANGE COLUMN `user_isAdminApp` `user_isAdminApp` BIT(1) NULL DEFAULT b'0' ,
CHANGE COLUMN `user_isAdminAcc` `user_isAdminAcc` BIT(1) NULL DEFAULT b'0' ,
CHANGE COLUMN `user_isLdap` `user_isLdap` BIT(1) NULL DEFAULT b'0' ,
CHANGE COLUMN `user_isDisabled` `user_isDisabled` BIT(1) NULL DEFAULT b'0' ,
ADD INDEX `fk_usrData_groups_id_idx` (`user_groupId` ASC),
ADD INDEX `fk_usrData_profiles_id_idx` (`user_profileId` ASC);

ALTER TABLE `syspass-dev`.`usrPassRecover`
CHANGE COLUMN `userpassr_used` `userpassr_used` BIT(1) NULL DEFAULT b'0' ;

ALTER TABLE `syspass-dev`.`usrToGroups`
DROP COLUMN `usertogroup_id`,
CHANGE COLUMN `usertogroup_userId` `usertogroup_userId` SMALLINT(5) UNSIGNED NOT NULL ,
CHANGE COLUMN `usertogroup_groupId` `usertogroup_groupId` SMALLINT(5) UNSIGNED NOT NULL ,
ADD INDEX `fk_usrToGroups_groups_id_idx` (`usertogroup_groupId` ASC),
DROP PRIMARY KEY;

ALTER TABLE `syspass-dev`.`accFavorites`
ADD CONSTRAINT `fk_accFavorites_accounts_id`
  FOREIGN KEY (`accfavorite_accountId`)
  REFERENCES `syspass-dev`.`accounts` (`account_id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_accFavorites_users_id`
  FOREIGN KEY (`accfavorite_userId`)
  REFERENCES `syspass-dev`.`usrData` (`user_id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION;

ALTER TABLE `syspass-dev`.`accFiles`
ADD CONSTRAINT `fk_accFiles_accounts_id`
  FOREIGN KEY (`accfile_accountId`)
  REFERENCES `syspass-dev`.`accounts` (`account_id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

ALTER TABLE `syspass-dev`.`accGroups`
ADD CONSTRAINT `fk_accGroups_accounts_id`
  FOREIGN KEY (`accgroup_accountId`)
  REFERENCES `syspass-dev`.`accounts` (`account_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
ADD CONSTRAINT `fk_accGroups_groups_id`
  FOREIGN KEY (`accgroup_groupId`)
  REFERENCES `syspass-dev`.`usrGroups` (`usergroup_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `syspass-dev`.`accHistory`
ADD CONSTRAINT `fk_accHistory_users_id`
  FOREIGN KEY (`acchistory_userId` , `acchistory_userEditId`)
  REFERENCES `syspass-dev`.`usrData` (`user_id` , `user_id`)
  ON DELETE RESTRICT
  ON UPDATE RESTRICT,
ADD CONSTRAINT `fk_accHistory_categories_id`
  FOREIGN KEY (`acchistory_categoryId`)
  REFERENCES `syspass-dev`.`categories` (`category_id`)
  ON DELETE RESTRICT
  ON UPDATE RESTRICT,
ADD CONSTRAINT `fk_accHistory_customers_id`
  FOREIGN KEY (`acchistory_customerId`)
  REFERENCES `syspass-dev`.`customers` (`customer_id`)
  ON DELETE RESTRICT
  ON UPDATE RESTRICT;

ALTER TABLE `syspass-dev`.`accTags`
ADD CONSTRAINT `fk_accTags_accounts_id`
  FOREIGN KEY (`acctag_accountId`)
  REFERENCES `syspass-dev`.`accounts` (`account_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
ADD CONSTRAINT `fk_accTags_tags_id`
  FOREIGN KEY (`acctag_tagId`)
  REFERENCES `syspass-dev`.`tags` (`tag_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `syspass-dev`.`accUsers`
ADD CONSTRAINT `fk_accUsers_accounts_id`
  FOREIGN KEY (`accuser_accountId`)
  REFERENCES `syspass-dev`.`accounts` (`account_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
ADD CONSTRAINT `fk_accUsers_users_id`
  FOREIGN KEY (`accuser_userId`)
  REFERENCES `syspass-dev`.`usrData` (`user_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `syspass-dev`.`accViewLinks`
ADD CONSTRAINT `fk_accViewLinks_account_id`
  FOREIGN KEY (`accviewlinks_accountId`)
  REFERENCES `syspass-dev`.`accounts` (`account_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
ADD CONSTRAINT `fk_accViewLinks_user_id`
  FOREIGN KEY (`accviewlinks_userId`)
  REFERENCES `syspass-dev`.`usrData` (`user_id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

ALTER TABLE `syspass-dev`.`accounts`
ADD CONSTRAINT `fk_accounts_categories_id`
  FOREIGN KEY (`account_categoryId`)
  REFERENCES `syspass-dev`.`categories` (`category_id`)
  ON DELETE RESTRICT
  ON UPDATE CASCADE,
ADD CONSTRAINT `fk_accounts_user_id`
  FOREIGN KEY (`account_userId` , `account_userEditId`)
  REFERENCES `syspass-dev`.`usrData` (`user_id` , `user_id`)
  ON DELETE RESTRICT
  ON UPDATE RESTRICT,
ADD CONSTRAINT `fk_accounts_customer_id`
  FOREIGN KEY (`account_customerId`)
  REFERENCES `syspass-dev`.`customers` (`customer_id`)
  ON DELETE RESTRICT
  ON UPDATE RESTRICT;

ALTER TABLE `syspass-dev`.`authTokens`
ADD CONSTRAINT `fk_authTokens_users_id`
  FOREIGN KEY (`authtoken_userId` , `authtoken_createdBy`)
  REFERENCES `syspass-dev`.`usrData` (`user_id` , `user_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `syspass-dev`.`customFieldsData`
ADD CONSTRAINT `fk_customFieldsData_def_id`
  FOREIGN KEY (`customfielddata_defId`)
  REFERENCES `syspass-dev`.`customFieldsDef` (`customfielddef_id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

ALTER TABLE `syspass-dev`.`log`
ADD CONSTRAINT `fk_log_users_id`
  FOREIGN KEY (`log_userId`)
  REFERENCES `syspass-dev`.`usrData` (`user_id`)
  ON DELETE RESTRICT
  ON UPDATE RESTRICT;

ALTER TABLE `syspass-dev`.`usrData`
ADD CONSTRAINT `fk_usrData_groups_id`
  FOREIGN KEY (`user_groupId`)
  REFERENCES `syspass-dev`.`usrGroups` (`usergroup_id`)
  ON DELETE RESTRICT
  ON UPDATE RESTRICT,
ADD CONSTRAINT `fk_usrData_profiles_id`
  FOREIGN KEY (`user_profileId`)
  REFERENCES `syspass-dev`.`usrProfiles` (`userprofile_id`)
  ON DELETE RESTRICT
  ON UPDATE RESTRICT;

ALTER TABLE `syspass-dev`.`usrPassRecover`
ADD CONSTRAINT `fk_usrPassRecover_users`
  FOREIGN KEY (`userpassr_userId`)
  REFERENCES `syspass-dev`.`usrData` (`user_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `syspass-dev`.`usrToGroups`
ADD CONSTRAINT `fk_usrToGroups_users_id`
  FOREIGN KEY (`usertogroup_userId`)
  REFERENCES `syspass-dev`.`usrData` (`user_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
ADD CONSTRAINT `fk_usrToGroups_groups_id`
  FOREIGN KEY (`usertogroup_groupId`)
  REFERENCES `syspass-dev`.`usrGroups` (`usergroup_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;