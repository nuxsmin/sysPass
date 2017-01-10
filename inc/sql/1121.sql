-- To 1.1.21;
ALTER TABLE `categories`
  ADD COLUMN `category_description` VARCHAR(255) NULL
  AFTER `category_name`;
ALTER TABLE `usrProfiles`
  ADD COLUMN `userProfile_pAppMgmtMenu` BIT(1) NULL DEFAULT b'0'
  AFTER `userProfile_pUsersMenu`,
  CHANGE COLUMN `userProfile_pConfigCategories` `userProfile_pAppMgmtCategories` BIT(1) NULL DEFAULT b'0'
  AFTER `userProfile_pAppMgmtMenu`,
  ADD COLUMN `userProfile_pAppMgmtCustomers` BIT(1) NULL DEFAULT b'0'
  AFTER `userProfile_pAppMgmtCategories`;