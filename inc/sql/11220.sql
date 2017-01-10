-- To 1.1.2.20;
ALTER TABLE `usrData`
  CHANGE COLUMN `user_pass` `user_pass` VARBINARY(255) NOT NULL,
  CHANGE COLUMN `user_mPass` `user_mPass` VARBINARY(255) DEFAULT NULL;