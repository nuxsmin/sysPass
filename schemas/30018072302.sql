DELIMITER $$

ALTER TABLE Account
  MODIFY pass varbinary(2000) NOT NULL,
  MODIFY `key` varbinary(2000) NOT NULL $$

ALTER TABLE AccountHistory
  MODIFY pass varbinary(2000) NOT NULL,
  MODIFY `key` varbinary(2000) NOT NULL $$

ALTER TABLE AuthToken
  MODIFY token varbinary(255) NOT NULL $$

ALTER TABLE AuthToken
  MODIFY hash varbinary(500) $$

ALTER TABLE Config
  MODIFY VALUE varbinary(4000) $$

ALTER TABLE CustomFieldData
  MODIFY `key` varbinary(2000) $$

ALTER TABLE Plugin
  MODIFY data mediumblob $$

ALTER TABLE PublicLink
  MODIFY data mediumblob $$

ALTER TABLE User
  MODIFY pass varbinary(500) NOT NULL,
  MODIFY mPass varbinary(2000),
  MODIFY mKey varbinary(2000),
  MODIFY hashSalt varbinary(255) NOT NULL $$

ALTER TABLE UserPassRecover
  MODIFY hash varbinary(255) NOT NULL $$

DELETE FROM Notification WHERE userId NOT IN (SELECT id FROM User) $$

ALTER TABLE Notification
  ADD CONSTRAINT fk_Notificationt_userId
FOREIGN KEY (userId) REFERENCES User (id) ON DELETE CASCADE ON UPDATE CASCADE $$