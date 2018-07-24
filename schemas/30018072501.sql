DELIMITER $$

ALTER TABLE `UserToUserGroup` ADD CONSTRAINT `uk_UserToUserGroup_01` UNIQUE (`userId`, `userGroupId`) $$