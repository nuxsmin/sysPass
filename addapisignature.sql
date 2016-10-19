ALTER TABLE `authTokens` ADD COLUMN
(
    `authtoken_secret` varchar(64) NOT NULL
) 
AFTER `authtoken_token`;
