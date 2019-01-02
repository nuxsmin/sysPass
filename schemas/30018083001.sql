DELIMITER $$

CREATE TABLE `AccountDefaultPermission`
(
  `id`            int                           NOT NULL AUTO_INCREMENT,
  `userId`        smallint(5) unsigned,
  `userGroupId`   smallint(5) unsigned,
  `userProfileId` smallint(5) unsigned,
  `fixed`         tinyint(1) unsigned DEFAULT 0 NOT NULL,
  `priority`      tinyint(3) unsigned DEFAULT 0 NOT NULL,
  `permission`    blob,
  `hash`          varbinary(40) NOT NULL,
  UNIQUE INDEX `uk_AccountDefaultPermission_01` (`hash`),
  CONSTRAINT `fk_AccountDefaultPermission_userId`
  FOREIGN KEY (`userId`) REFERENCES `User` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_AccountDefaultPermission_userGroupId`
  FOREIGN KEY (`userGroupId`) REFERENCES `UserGroup` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_AccountDefaultPermission_userProfileId`
  FOREIGN KEY (`userProfileId`) REFERENCES `UserProfile` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8 $$