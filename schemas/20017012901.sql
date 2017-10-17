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
        CONV(`accounts`.`account_otherUserEdit`,
                10,
                2) AS `account_otherUserEdit`,
        CONV(`accounts`.`account_otherGroupEdit`,
                10,
                2) AS `account_otherGroupEdit`,
        CONV(`accounts`.`account_isPrivate`, 10, 2) AS `account_isPrivate`,
        CONV(`accounts`.`account_isPrivateGroup`,
                10,
                2) AS `account_isPrivateGroup`,
        `accounts`.`account_passDate` AS `account_passDate`,
        `accounts`.`account_passDateChange` AS `account_passDateChange`,
        `accounts`.`account_parentId` AS `account_parentId`,
        `accounts`.`account_countView` AS `account_countView`,
        `accounts`.`account_dateEdit` AS `account_dateEdit`,
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
