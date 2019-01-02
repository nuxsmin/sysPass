DELIMITER $$

ALTER TABLE AccountDefaultPermission
  CHANGE permission data blob $$
DROP INDEX uk_AccountDefaultPermission_01
ON AccountDefaultPermission $$
ALTER TABLE AccountDefaultPermission
  DROP FOREIGN KEY fk_AccountDefaultPermission_userProfileId $$
ALTER TABLE AccountDefaultPermission
  DROP FOREIGN KEY fk_AccountDefaultPermission_userGroupId $$
ALTER TABLE AccountDefaultPermission
  DROP FOREIGN KEY fk_AccountDefaultPermission_userId $$
DROP INDEX fk_AccountDefaultPermission_userProfileId
ON AccountDefaultPermission $$
DROP INDEX fk_AccountDefaultPermission_userGroupId
ON AccountDefaultPermission $$
DROP INDEX fk_AccountDefaultPermission_userId
ON AccountDefaultPermission $$
ALTER TABLE AccountDefaultPermission
RENAME TO ItemPreset $$
ALTER TABLE ItemPreset
  ADD type varchar(25) NOT NULL $$
ALTER TABLE ItemPreset
  MODIFY COLUMN type varchar(25) NOT NULL
  AFTER id $$
CREATE UNIQUE INDEX uk_ItemPreset_01
  ON ItemPreset (hash) $$
CREATE INDEX fk_ItemPreset_userId
  ON ItemPreset (userId) $$
CREATE INDEX fk_ItemPreset_userGroupId
  ON ItemPreset (userGroupId) $$
CREATE INDEX fk_ItemPreset_userProfileId
  ON ItemPreset (userProfileId) $$
ALTER TABLE ItemPreset
  ADD CONSTRAINT fk_ItemPreset_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON DELETE CASCADE
  ON UPDATE CASCADE $$
ALTER TABLE ItemPreset
  ADD CONSTRAINT fk_ItemPreset_userGroupId
FOREIGN KEY (userGroupId) REFERENCES UserGroup (id)
  ON DELETE CASCADE
  ON UPDATE CASCADE $$
ALTER TABLE ItemPreset
  ADD CONSTRAINT fk_ItemPreset_userProfileId
FOREIGN KEY (userProfileId) REFERENCES UserProfile (id)
  ON DELETE CASCADE
  ON UPDATE CASCADE $$
UPDATE ItemPreset
SET type = 'account.permission'
WHERE type = '' $$
UPDATE ItemPreset
set hash = sha1(CONCAT(type, coalesce(userId, 0), coalesce(userGroupId, 0), coalesce(userProfileId, 0), priority)) $$