ALTER TABLE `accounts`
  ADD COLUMN `account_isPrivateGroup` BIT(1) NULL DEFAULT b'0' AFTER `account_isPrivate`;

ALTER TABLE `accHistory`
  ADD COLUMN `accHistory_isPrivate` BIT(1) NULL DEFAULT b'0' AFTER `accHistory_parentId`,
  ADD COLUMN `accHistory_isPrivateGroup` BIT(1) NULL DEFAULT b'0' AFTER `accHistory_isPrivate`;

CREATE OR REPLACE ALGORITHM = UNDEFINED DEFINER = CURRENT_USER SQL SECURITY DEFINER VIEW `account_data_v` AS
    SELECT
        `accounts`.`account_id` AS `account_id`,
        `accounts`.`account_name` AS `account_name`,
        `accounts`.`account_categoryId` AS `account_categoryId`,
        `accounts`.`account_userId` AS `account_userId`,
        `accounts`.`account_customerId` AS `account_customerId`,
        `accounts`.`account_userGroupId` AS `account_userGroupId`,
        `accounts`.`account_userEditId` AS `account_userEditId`,
        `accounts`.`account_login` AS `account_login`,
        `accounts`.`account_url` AS `account_url`,
        `accounts`.`account_notes` AS `account_notes`,
        `accounts`.`account_countView` AS `account_countView`,
        `accounts`.`account_countDecrypt` AS `account_countDecrypt`,
        `accounts`.`account_dateAdd` AS `account_dateAdd`,
        `accounts`.`account_dateEdit` AS `account_dateEdit`,
        CONV(`accounts`.`account_otherUserEdit`,10,2) AS `account_otherUserEdit`,
        CONV(`accounts`.`account_otherGroupEdit`,10,2) AS `account_otherGroupEdit`,
        CONV(`accounts`.`account_isPrivate`, 10, 2) AS `account_isPrivate`,
        CONV(`accounts`.`account_isPrivateGroup`, 10, 2) AS `account_isPrivateGroup`,
        `accounts`.`account_passDate` AS `account_passDate`,
        `accounts`.`account_passDateChange` AS `account_passDateChange`,
        `accounts`.`account_parentId` AS `account_parentId`,
        `categories`.`category_name` AS `category_name`,
        `customers`.`customer_name` AS `customer_name`,
        `ug`.`usergroup_name` AS `usergroup_name`,
        `u1`.`user_name` AS `user_name`,
        `u1`.`user_login` AS `user_login`,
        `u2`.`user_name` AS `user_editName`,
        `u2`.`user_login` AS `user_editLogin`,
        `publicLinks`.`publicLink_hash` AS `publicLink_hash`
    FROM
        ((((((`accounts`
        LEFT JOIN `categories` ON ((`accounts`.`account_categoryId` = `categories`.`category_id`)))
        LEFT JOIN `usrGroups` `ug` ON ((`accounts`.`account_userGroupId` = `ug`.`usergroup_id`)))
        LEFT JOIN `usrData` `u1` ON ((`accounts`.`account_userId` = `u1`.`user_id`)))
        LEFT JOIN `usrData` `u2` ON ((`accounts`.`account_userEditId` = `u2`.`user_id`)))
        LEFT JOIN `customers` ON ((`accounts`.`account_customerId` = `customers`.`customer_id`)))
        LEFT JOIN `publicLinks` ON ((`accounts`.`account_id` = `publicLinks`.`publicLink_itemId`)));

CREATE OR REPLACE ALGORITHM = UNDEFINED DEFINER = CURRENT_USER SQL SECURITY DEFINER VIEW `account_search_v` AS
    SELECT DISTINCT
        `accounts`.`account_id` AS `account_id`,
        `accounts`.`account_customerId` AS `account_customerId`,
        `accounts`.`account_categoryId` AS `account_categoryId`,
        `accounts`.`account_name` AS `account_name`,
        `accounts`.`account_login` AS `account_login`,
        `accounts`.`account_url` AS `account_url`,
        `accounts`.`account_notes` AS `account_notes`,
        `accounts`.`account_userId` AS `account_userId`,
        `accounts`.`account_userGroupId` AS `account_userGroupId`,
        `accounts`.`account_otherUserEdit` AS `account_otherUserEdit`,
        `accounts`.`account_otherGroupEdit` AS `account_otherGroupEdit`,
        `accounts`.`account_isPrivate` AS `account_isPrivate`,
        `accounts`.`account_isPrivateGroup` AS `account_isPrivateGroup`,
        `accounts`.`account_passDate` AS `account_passDate`,
        `accounts`.`account_passDateChange` AS `account_passDateChange`,
        `accounts`.`account_parentId` AS `account_parentId`,
        `accounts`.`account_countView` AS `account_countView`,
        `ug`.`usergroup_name` AS `usergroup_name`,
        `categories`.`category_name` AS `category_name`,
        `customers`.`customer_name` AS `customer_name`,
        (SELECT
                COUNT(0)
            FROM
                `accFiles`
            WHERE
                (`accFiles`.`accfile_accountId` = `accounts`.`account_id`)) AS `num_files`
    FROM
        (((`accounts`
        LEFT JOIN `categories` ON ((`accounts`.`account_categoryId` = `categories`.`category_id`)))
        LEFT JOIN `usrGroups` `ug` ON ((`accounts`.`account_userGroupId` = `ug`.`usergroup_id`)))
        LEFT JOIN `customers` ON ((`customers`.`customer_id` = `accounts`.`account_customerId`)));

