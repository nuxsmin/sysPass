-- To 1.3.16020501;
CREATE TABLE `tags` (
  `tag_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tag_name` VARCHAR(45)  NOT NULL,
  `tag_hash` BINARY(20)   NOT NULL,
  PRIMARY KEY (`tag_id`),
  INDEX `IDX_name` (`tag_name` ASC),
  UNIQUE INDEX `tag_hash_UNIQUE` (`tag_hash` ASC)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
CREATE TABLE `accTags` (
  `acctag_accountId` INT UNSIGNED NOT NULL,
  `acctag_tagId`     INT UNSIGNED NOT NULL,
  INDEX `IDX_id` (`acctag_accountId` ASC),
  INDEX `fk_accTags_tags_id_idx` (`acctag_tagId` ASC)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;