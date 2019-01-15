DELIMITER $$

ALTER TABLE Track
  ADD timeUnlock int(10) unsigned NULL $$
ALTER TABLE Track
  MODIFY COLUMN timeUnlock int(10) unsigned AFTER time $$