-- To 1.1.22;
ALTER TABLE `usrData`
  CHANGE COLUMN `user_login` `user_login` VARCHAR(50) NOT NULL,
  CHANGE COLUMN `user_email` `user_email` VARCHAR(80) NULL DEFAULT NULL;