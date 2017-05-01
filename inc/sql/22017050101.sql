ALTER TABLE `customers` ADD `customer_isGlobal` BIT(1) DEFAULT b'0' NULL;
ALTER TABLE `usrData` ADD `user_ssoLogin` VARCHAR(100) NULL AFTER `user_login`;

DROP INDEX IDX_login ON `usrData`;
CREATE UNIQUE INDEX `IDX_login` ON `usrData` (`user_login`, `user_ssoLogin`);