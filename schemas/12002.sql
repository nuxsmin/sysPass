-- To 1.2.0.0.2;
ALTER TABLE config
  CHANGE config_value config_value VARCHAR(255);
ALTER TABLE usrData
  CHANGE user_pass user_pass VARBINARY(255);
ALTER TABLE usrData
  CHANGE user_hashSalt user_hashSalt VARBINARY(128);
ALTER TABLE accHistory
  CHANGE acchistory_mPassHash acchistory_mPassHash VARBINARY(255);