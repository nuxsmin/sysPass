ALTER TABLE `customers`
  ADD `customer_isGlobal` TINYINT(1) DEFAULT '0' NULL;
ALTER TABLE `usrData`
  ADD `user_ssoLogin` VARCHAR(100) NULL
  AFTER `user_login`;

DROP INDEX IDX_login
ON `usrData`;
CREATE UNIQUE INDEX `IDX_login`
  ON `usrData` (`user_login`, `user_ssoLogin`);

ALTER TABLE plugins
  ADD `plugin_available` TINYINT(1) DEFAULT '0' NULL;

CREATE TABLE `Action` (
  `id`    SMALLINT(5) UNSIGNED NOT NULL,
  `name`  VARCHAR(50)          NOT NULL,
  `text`  VARCHAR(100)         NOT NULL,
  `route` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`, `name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

INSERT INTO Action (id, name, text, route)
VALUES (1, 'ACCOUNT_SEARCH', 'Buscar Cuentas', 'account/search');
INSERT INTO Action (id, name, text, route)
VALUES (10, 'ACCOUNT', 'Cuentas', 'account/index');
INSERT INTO Action (id, name, text, route)
VALUES (11, 'ACCOUNT_FILE', 'Archivos', 'account/listFile');
INSERT INTO Action (id, name, text, route)
VALUES (12, 'ACCOUNT_REQUEST', 'Peticiones', 'account/request');
INSERT INTO Action (id, name, text, route)
VALUES (13, 'ACCOUNT_FAVORITE', 'Favoritos', 'favorite/index');
INSERT INTO Action (id, name, text, route) VALUES (20, 'WIKI', 'Wiki', 'wiki/index');
INSERT INTO Action (id, name, text, route)
VALUES (60, 'ITEMS_MANAGE', 'Elementos y Personalización', 'itemManager/index');
INSERT INTO Action (id, name, text, route)
VALUES (61, 'CATEGORY', 'Gestión Categorías', 'category/index');
INSERT INTO Action (id, name, text, route)
VALUES (62, 'CLIENT', 'Gestión Clientes', 'client/index');
INSERT INTO Action (id, name, text, route)
VALUES (63, 'APITOKEN', 'Gestión Autorizaciones API', 'apiToken/index');
INSERT INTO Action (id, name, text, route)
VALUES (64, 'CUSTOMFIELD', 'Gestión Campos Personalizados', 'customField/index');
INSERT INTO Action (id, name, text, route)
VALUES (65, 'PUBLICLINK', 'Enlaces Públicos', 'publicLink/index');
INSERT INTO Action (id, name, text, route)
VALUES (66, 'FILE', 'Gestión de Archivos', 'file/index');
INSERT INTO Action (id, name, text, route)
VALUES (67, 'ACCOUNTMGR', 'Gestión de Cuentas', 'accountManager/index');
INSERT INTO Action (id, name, text, route)
VALUES (68, 'TAG', 'Gestión de Etiquetas', 'tag/index');
INSERT INTO Action (id, name, text, route)
VALUES (69, 'PLUGIN', 'Gestión Plugins', 'plugin/index');
INSERT INTO Action (id, name, text, route)
VALUES (70, 'ACCESS_MANAGE', 'Usuarios y Accesos', 'accessManager/index');
INSERT INTO Action (id, name, text, route)
VALUES (71, 'USER', 'Gestión Usuarios', 'user/index');
INSERT INTO Action (id, name, text, route)
VALUES (72, 'GROUP', 'Gestión Grupos', 'group/index');
INSERT INTO Action (id, name, text, route)
VALUES (73, 'PROFILE', 'Gestión Perfiles', 'profile/index');
INSERT INTO Action (id, name, text, route)
VALUES (90, 'EVENTLOG', 'Registro de Eventos', 'eventlog/index');
INSERT INTO Action (id, name, text, route)
VALUES (100, 'ACCOUNT_VIEW', 'Ver Cuenta', 'account/view');
INSERT INTO Action (id, name, text, route)
VALUES (101, 'ACCOUNT_CREATE', 'Nueva Cuenta', 'account/create');
INSERT INTO Action (id, name, text, route)
VALUES (102, 'ACCOUNT_EDIT', 'Editar Cuenta', 'account/edit');
INSERT INTO Action (id, name, text, route)
VALUES (103, 'ACCOUNT_DELETE', 'Eliminar Cuenta', 'account/delete');
INSERT INTO Action (id, name, text, route)
VALUES (104, 'ACCOUNT_VIEW_PASS', 'Ver Clave', 'account/viewPass');
INSERT INTO Action (id, name, text, route)
VALUES (105, 'ACCOUNT_VIEW_HISTORY', 'Ver Historial', 'account/viewHistory');
INSERT INTO Action (id, name, text, route)
VALUES (106, 'ACCOUNT_EDIT_PASS', 'Editar Clave de Cuenta', 'account/editPass');
INSERT INTO Action (id, name, text, route)
VALUES (107, 'ACCOUNT_EDIT_RESTORE', 'Restaurar Cuenta', 'account/restore');
INSERT INTO Action (id, name, text, route)
VALUES (108, 'ACCOUNT_COPY', 'Copiar Cuenta', 'account/copy');
INSERT INTO Action (id, name, text, route)
VALUES (109, 'ACCOUNT_COPY_PASS', 'Copiar Clave', 'account/copyPass');
INSERT INTO Action (id, name, text, route)
VALUES (111, 'ACCOUNT_FILE_VIEW', 'Ver Archivo', 'account/viewFile');
INSERT INTO Action (id, name, text, route)
VALUES (112, 'ACCOUNT_FILE_UPLOAD', 'Subir Archivo', 'account/uploadFile');
INSERT INTO Action (id, name, text, route)
VALUES (113, 'ACCOUNT_FILE_DOWNLOAD', 'Descargar Archivo', 'account/downloadFile');
INSERT INTO Action (id, name, text, route)
VALUES (114, 'ACCOUNT_FILE_DELETE', 'Eliminar Archivo', 'account/deleteFile');
INSERT INTO Action (id, name, text, route)
VALUES (130, 'ACCOUNT_FAVORITE_VIEW', 'Ver Favoritos', 'favorite/view');
INSERT INTO Action (id, name, text, route)
VALUES (131, 'ACCOUNT_FAVORITE_ADD', 'Añadir Favorito', 'favorite/add');
INSERT INTO Action (id, name, text, route)
VALUES (133, 'ACCOUNT_FAVORITE_DELETE', 'Eliminar Favorito', 'favorite/delete');
INSERT INTO Action (id, name, text, route)
VALUES (200, 'WIKI_VIEW', 'Ver Wiki', 'wiki/view');
INSERT INTO Action (id, name, text, route)
VALUES (201, 'WIKI_NEW', 'Añadir Wiki', 'wiki/create');
INSERT INTO Action (id, name, text, route)
VALUES (202, 'WIKI_EDIT', 'Editar Wiki', 'wiki/edit');
INSERT INTO Action (id, name, text, route)
VALUES (203, 'WIKI_DELETE', 'Eliminar Wiki', 'wiki/delete');
INSERT INTO Action (id, name, text, route)
VALUES (610, 'CATEGORY_VIEW', 'Ver Categoría', 'category/view');
INSERT INTO Action (id, name, text, route)
VALUES (611, 'CATEGORY_CREATE', 'Nueva Categoría', 'category/create');
INSERT INTO Action (id, name, text, route)
VALUES (612, 'CATEGORY_EDIT', 'Editar Categoría', 'category/edit');
INSERT INTO Action (id, name, text, route)
VALUES (613, 'CATEGORY_DELETE', 'Eliminar Categoría', 'category/delete');
INSERT INTO Action (id, name, text, route)
VALUES (615, 'CATEGORY_SEARCH', 'Buscar Categoría', 'category/search');
INSERT INTO Action (id, name, text, route)
VALUES (620, 'CLIENT_VIEW', 'Ver Cliente', 'client/view');
INSERT INTO Action (id, name, text, route)
VALUES (621, 'CLIENT_CREATE', 'Nuevo CLiente', 'client/create');
INSERT INTO Action (id, name, text, route)
VALUES (622, 'CLIENT_EDIT', 'Editar Cliente', 'client/edit');
INSERT INTO Action (id, name, text, route)
VALUES (623, 'CLIENT_DELETE', 'Eliminar Cliente', 'client/delete');
INSERT INTO Action (id, name, text, route)
VALUES (625, 'CLIENT_SEARCH', 'Buscar Cliente', 'client/search');
INSERT INTO Action (id, name, text, route)
VALUES (630, 'APITOKEN_CREATE', 'Nuevo Token API', 'apiToken/create');
INSERT INTO Action (id, name, text, route)
VALUES (631, 'APITOKEN_VIEW', 'Ver Token API', 'apiToken/view');
INSERT INTO Action (id, name, text, route)
VALUES (632, 'APITOKEN_EDIT', 'Editar Token API', 'apiToken/edit');
INSERT INTO Action (id, name, text, route)
VALUES (633, 'APITOKEN_DELETE', 'Eliminar Token API', 'apiToken/delete');
INSERT INTO Action (id, name, text, route)
VALUES (635, 'APITOKEN_SEARCH', 'Buscar Token API', 'apiToken/search');
INSERT INTO Action (id, name, text, route)
VALUES (640, 'CUSTOMFIELD_CREATE', 'Nuevo Campo Personalizado', 'customField/create');
INSERT INTO Action (id, name, text, route)
VALUES (641, 'CUSTOMFIELD_VIEW', 'Ver Campo Personalizado', 'customField/view');
INSERT INTO Action (id, name, text, route)
VALUES (642, 'CUSTOMFIELD_EDIT', 'Editar Campo Personalizado', 'customField/edit');
INSERT INTO Action (id, name, text, route)
VALUES (643, 'CUSTOMFIELD_DELETE', 'Eliminar Campo Personalizado', 'customField/delete');
INSERT INTO Action (id, name, text, route)
VALUES (645, 'CUSTOMFIELD_SEARCH', 'Buscar Campo Personalizado', 'customField/search');
INSERT INTO Action (id, name, text, route)
VALUES (650, 'PUBLICLINK_CREATE', 'Crear Enlace Público', 'publicLink/create');
INSERT INTO Action (id, name, text, route)
VALUES (651, 'PUBLICLINK_VIEW', 'Ver Enlace Público', 'publicLink/view');
INSERT INTO Action (id, name, text, route)
VALUES (653, 'PUBLICLINK_DELETE', 'Eliminar Enlace Público', 'publicLink/delete');
INSERT INTO Action (id, name, text, route)
VALUES (654, 'PUBLICLINK_REFRESH', 'Actualizar Enlace Público', 'publicLink/refresh');
INSERT INTO Action (id, name, text, route)
VALUES (655, 'PUBLICLINK_SEARCH', 'Buscar Enlace Público', 'publicLink/search');
INSERT INTO Action (id, name, text, route)
VALUES (661, 'FILE_VIEW', 'Ver Archivo', 'file/view');
INSERT INTO Action (id, name, text, route)
VALUES (663, 'FILE_DELETE', 'Eliminar Archivo', 'file/delete');
INSERT INTO Action (id, name, text, route)
VALUES (665, 'FILE_SEARCH', 'Buscar Archivo', 'file/search');
INSERT INTO Action (id, name, text, route)
VALUES (671, 'ACCOUNTMGR_VIEW', 'Ver Cuenta', 'accountManager/view');
INSERT INTO Action (id, name, text, route)
VALUES (673, 'ACCOUNTMGR_DELETE', 'Eliminar Cuenta', 'accountManager/delete');
INSERT INTO Action (id, name, text, route)
VALUES (675, 'ACCOUNTMGR_SEARCH', 'Buscar Cuenta', 'accountManager/search');
INSERT INTO Action (id, name, text, route)
VALUES (680, 'TAG_CREATE', 'Nueva Etiqueta', 'tag/create');
INSERT INTO Action (id, name, text, route)
VALUES (681, 'TAG_VIEW', 'Ver Etiqueta', 'tag/view');
INSERT INTO Action (id, name, text, route)
VALUES (682, 'TAG_EDIT', 'Editar Etiqueta', 'tag/edit');
INSERT INTO Action (id, name, text, route)
VALUES (683, 'TAG_DELETE', 'Eliminar Etiqueta', 'tag/delete');
INSERT INTO Action (id, name, text, route)
VALUES (685, 'TAG_SEARCH', 'Buscar Etiqueta', 'tag/search');
INSERT INTO Action (id, name, text, route)
VALUES (690, 'PLUGIN_NEW', 'Nuevo Plugin', 'plugin/create');
INSERT INTO Action (id, name, text, route)
VALUES (691, 'PLUGIN_VIEW', 'Ver Plugin', 'plugin/view');
INSERT INTO Action (id, name, text, route)
VALUES (695, 'PLUGIN_SEARCH', 'Buscar Plugin', 'plugin/search');
INSERT INTO Action (id, name, text, route)
VALUES (696, 'PLUGIN_ENABLE', 'Habilitar Plugin', 'plugin/enable');
INSERT INTO Action (id, name, text, route)
VALUES (697, 'PLUGIN_DISABLE', 'Deshabilitar Plugin', 'plugin/disable');
INSERT INTO Action (id, name, text, route)
VALUES (698, 'PLUGIN_RESET', 'Restablecer Plugin', 'plugin/reset');
INSERT INTO Action (id, name, text, route)
VALUES (710, 'USER_VIEW', 'Ver Usuario', 'user/view');
INSERT INTO Action (id, name, text, route)
VALUES (711, 'USER_CREATE', 'Nuevo Usuario', 'user/create');
INSERT INTO Action (id, name, text, route)
VALUES (712, 'USER_EDIT', 'Editar Usuario', 'user/edit');
INSERT INTO Action (id, name, text, route)
VALUES (713, 'USER_DELETE', 'Eliminar Usuario', 'user/delete');
INSERT INTO Action (id, name, text, route)
VALUES (714, 'USER_EDIT_PASS', 'Editar Clave Usuario', 'user/editPass');
INSERT INTO Action (id, name, text, route)
VALUES (715, 'USER_SEARCH', 'Buscar Usuario', 'user/search');
INSERT INTO Action (id, name, text, route)
VALUES (720, 'GROUP_VIEW', 'Ver Grupo', 'userGroup/view');
INSERT INTO Action (id, name, text, route)
VALUES (721, 'GROUP_CREATE', 'Nuevo Grupo', 'userGroup/create');
INSERT INTO Action (id, name, text, route)
VALUES (722, 'GROUP_EDIT', 'Editar Grupo', 'userGroup/edit');
INSERT INTO Action (id, name, text, route)
VALUES (723, 'GROUP_DELETE', 'Eliminar Grupo', 'userGroup/delete');
INSERT INTO Action (id, name, text, route)
VALUES (725, 'GROUP_SEARCH', 'Buscar Grupo', 'userGroup/search');
INSERT INTO Action (id, name, text, route)
VALUES (730, 'PROFILE_VIEW', 'Ver Perfil', 'userProfile/view');
INSERT INTO Action (id, name, text, route)
VALUES (731, 'PROFILE_CREATE', 'Nuevo Perfil', 'userProfile/create');
INSERT INTO Action (id, name, text, route)
VALUES (732, 'PROFILE_EDIT', 'Editar Perfil', 'userProfile/edit');
INSERT INTO Action (id, name, text, route)
VALUES (733, 'PROFILE_DELETE', 'Eliminar Perfil', 'userProfile/delete');
INSERT INTO Action (id, name, text, route)
VALUES (735, 'PROFILE_SEARCH', 'Buscar Perfil', 'userProfile/search');
INSERT INTO Action (id, name, text, route)
VALUES (740, 'PREFERENCE', 'Gestión Preferencias', 'userPreference/index');
INSERT INTO Action (id, name, text, route)
VALUES (741, 'PREFERENCE_GENERAL', 'Preferencias General', 'userPreference/general');
INSERT INTO Action (id, name, text, route)
VALUES (742, 'PREFERENCE_SECURITY', 'Preferencias Seguridad', 'userPreference/security');
INSERT INTO Action (id, name, text, route)
VALUES (760, 'NOTICE', 'Notificaciones', 'notice/index');
INSERT INTO Action (id, name, text, route)
VALUES (761, 'NOTICE_USER', 'Notificaciones Usuario', 'noticeUser/index');
INSERT INTO Action (id, name, text, route)
VALUES (1000, 'CONFIG', 'Configuración', 'config/index');
INSERT INTO Action (id, name, text, route)
VALUES (1001, 'CONFIG_GENERAL', 'Configuración General', 'config/general');
INSERT INTO Action (id, name, text, route)
VALUES (1010, 'ACCOUNT_CONFIG', 'Configuración Cuentas', 'account/config');
INSERT INTO Action (id, name, text, route)
VALUES (1020, 'WIKI_CONFIG', 'Configuración Wiki', 'wiki/config');
INSERT INTO Action (id, name, text, route)
VALUES (1030, 'ENCRYPTION_CONFIG', 'Configuración Encriptación', 'encryption/config');
INSERT INTO Action (id, name, text, route)
VALUES (1031, 'ENCRYPTION_REFRESH', 'Actualizar Hash', 'encryption/updateHash');
INSERT INTO Action (id, name, text, route)
VALUES (1032, 'ENCRYPTION_TEMPPASS', 'Clave Maestra Temporal', 'encryption/createTempPass');
INSERT INTO Action (id, name, text, route)
VALUES (1040, 'BACKUP_CONFIG', 'Configuración Copia de Seguridad', 'backup/config');
INSERT INTO Action (id, name, text, route)
VALUES (1050, 'IMPORT_CONFIG', 'Configuración Importación', 'import/config');
INSERT INTO Action (id, name, text, route)
VALUES (1051, 'IMPORT_CSV', 'Importar CSV', 'import/csv');
INSERT INTO Action (id, name, text, route)
VALUES (1052, 'IMPORT_XML', 'Importar XML', 'import/xml');
INSERT INTO Action (id, name, text, route)
VALUES (1070, 'MAIL_CONFIG', 'Configuración Email', 'mail/config');
INSERT INTO Action (id, name, text, route)
VALUES (1080, 'LDAP_CONFIG', 'Configuración LDAP', 'ldap/config');
INSERT INTO Action (id, name, text, route)
VALUES (1081, 'LDAP_SYNC', 'Sincronización LDAP', 'ldap/sync');
INSERT INTO Action (id, name, text, route)
VALUES (6701, 'ACCOUNTMGR_HISTORY', 'Gestión de Cuenta (H)', 'accountHistoryManager/index');
INSERT INTO Action (id, name, text, route)
VALUES (6731, 'ACCOUNTMGR_DELETE_HISTORY', 'Eliminar Cuenta', 'accountHistoryManager/delete');
INSERT INTO Action (id, name, text, route)
VALUES (6751, 'ACCOUNTMGR_SEARCH_HISTORY', 'Buscar Cuenta', 'accountHistoryManager/search');
INSERT INTO Action (id, name, text, route)
VALUES (6771, 'ACCOUNTMGR_RESTORE', 'Restaurar Cuenta', 'accountManager/restore');
INSERT INTO Action (id, name, text, route)
VALUES (7610, 'NOTICE_USER_VIEW', 'Ver Notificación', 'userNotice/view');
INSERT INTO Action (id, name, text, route)
VALUES (7611, 'NOTICE_USER_CREATE', 'Crear Notificación', 'userNotice/create');
INSERT INTO Action (id, name, text, route)
VALUES (7612, 'NOTICE_USER_EDIT', 'Editar Notificación', 'userNotice/edit');
INSERT INTO Action (id, name, text, route)
VALUES (7613, 'NOTICE_USER_DELETE', 'Eliminar Notificación', 'userNotice/delete');
INSERT INTO Action (id, name, text, route)
VALUES (7614, 'NOTICE_USER_CHECK', 'Marcar Notificación', 'userNotice/check');
INSERT INTO Action (id, name, text, route)
VALUES (7615, 'NOTICE_USER_SEARCH', 'Buscar Notificación', 'userNotice/search');

ALTER TABLE `customFieldsDef`
  CHANGE COLUMN `customfielddef_field` `customfielddef_field` BLOB NULL;
ALTER TABLE customFieldsDef
  ADD `required` TINYINT(1) UNSIGNED NULL;
ALTER TABLE customFieldsDef
  ADD `help` VARCHAR(255) NULL;
ALTER TABLE customFieldsDef
  ADD `showInList` TINYINT(1) UNSIGNED NULL;
ALTER TABLE customFieldsDef
  ADD `name` VARCHAR(100) NOT NULL
  AFTER customfielddef_id;
ALTER TABLE customFieldsDef
  ADD `typeId` TINYINT UNSIGNED NULL;
ALTER TABLE customFieldsDef
  CHANGE customfielddef_module moduleId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE customFieldsDef
  CHANGE customfielddef_field field BLOB NOT NULL;

ALTER TABLE customFieldsData
  DROP FOREIGN KEY fk_customFieldsData_def_id;

ALTER TABLE customFieldsData
  CHANGE customfielddata_defId definitionId INT(10) UNSIGNED NOT NULL;
ALTER TABLE customFieldsDef
  CHANGE customfielddef_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE customFieldsData
  CHANGE customfielddata_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE customFieldsData
  CHANGE customfielddata_moduleId moduleId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE customFieldsData
  CHANGE customfielddata_itemId itemId INT(10) UNSIGNED NOT NULL;
ALTER TABLE customFieldsData
  CHANGE customfielddata_data data LONGBLOB;
ALTER TABLE customFieldsData
  CHANGE customfielddata_key `key` VARBINARY(1000);

CREATE TABLE `CustomFieldType` (
  `id`   TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50)         NOT NULL,
  `text` VARCHAR(50)         NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

-- Extraer antes desde los datos
INSERT INTO CustomFieldType (id, name, text) VALUES (1, 'text', 'Texto');
INSERT INTO CustomFieldType (id, name, text) VALUES (2, 'password', 'Clave');
INSERT INTO CustomFieldType (id, name, text) VALUES (3, 'date', 'Fecha');
INSERT INTO CustomFieldType (id, name, text) VALUES (4, 'number', 'Número');
INSERT INTO CustomFieldType (id, name, text) VALUES (5, 'email', 'Email');
INSERT INTO CustomFieldType (id, name, text) VALUES (6, 'telephone', 'Teléfono');
INSERT INTO CustomFieldType (id, name, text) VALUES (7, 'url', 'URL');
INSERT INTO CustomFieldType (id, name, text) VALUES (8, 'color', 'Color');
INSERT INTO CustomFieldType (id, name, text) VALUES (9, 'wiki', 'Wiki');
INSERT INTO CustomFieldType (id, name, text) VALUES (10, 'textarea', 'Área de texto');

ALTER TABLE `publicLinks`
  ADD COLUMN `userId` SMALLINT(5) UNSIGNED NOT NULL,
  ADD COLUMN `typeId` INT(10) UNSIGNED NOT NULL
  AFTER `userId`,
  ADD COLUMN `notify` TINYINT(1) NULL DEFAULT 0
  AFTER `typeId`,
  ADD COLUMN `dateAdd` INT UNSIGNED NOT NULL
  AFTER `notify`,
  ADD COLUMN `dateExpire` INT UNSIGNED NOT NULL
  AFTER `dateAdd`,
  ADD COLUMN `dateUpdate` INT UNSIGNED NOT NULL
  AFTER `dateExpire`,
  ADD COLUMN `countViews` SMALLINT(5) UNSIGNED NULL DEFAULT 0
  AFTER `dateUpdate`,
  ADD COLUMN `totalCountViews` MEDIUMINT UNSIGNED NULL DEFAULT 0
  AFTER `countViews`,
  ADD COLUMN `maxCountViews` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0
  AFTER `totalCountViews`,
  ADD COLUMN `useinfo` BLOB NULL
  AFTER `maxCountViews`;

-- Foreign Keys
ALTER TABLE accHistory
  DROP FOREIGN KEY fk_accHistory_userGroup_id;
ALTER TABLE accHistory
  DROP FOREIGN KEY fk_accHistory_users_id;
ALTER TABLE accHistory
  DROP FOREIGN KEY fk_accHistory_users_edit_id;
ALTER TABLE accHistory
  DROP FOREIGN KEY fk_accHistory_customer_id;
ALTER TABLE accHistory
  DROP FOREIGN KEY fk_accHistory_category_id;

ALTER TABLE usrData
  DROP FOREIGN KEY fk_usrData_profiles_id;
ALTER TABLE usrData
  DROP FOREIGN KEY fk_usrData_groups_id;
DROP INDEX fk_usrData_groups_id_idx
ON usrData;
DROP INDEX fk_usrData_profiles_id_idx
ON usrData;

ALTER TABLE accounts
  DROP FOREIGN KEY fk_accounts_userGroup_id;
ALTER TABLE accounts
  DROP FOREIGN KEY fk_accounts_user_id;
ALTER TABLE accounts
  DROP FOREIGN KEY fk_accounts_user_edit_id;
ALTER TABLE accounts
  DROP FOREIGN KEY fk_accounts_customer_id;
ALTER TABLE accounts
  DROP FOREIGN KEY fk_accounts_category_id;
DROP INDEX fk_accounts_user_id
ON accounts;
DROP INDEX fk_accounts_user_edit_id
ON accounts;

ALTER TABLE accTags
  DROP FOREIGN KEY fk_accTags_accounts_id;
ALTER TABLE accTags
  DROP FOREIGN KEY fk_accTags_tags_id;
DROP INDEX IDX_id
ON accTags;
DROP INDEX fk_accTags_tags_id_idx
ON accTags;

ALTER TABLE accUsers
  DROP FOREIGN KEY fk_accUsers_accounts_id;
ALTER TABLE accUsers
  DROP FOREIGN KEY fk_accUsers_users_id;
DROP INDEX fk_accUsers_users_id_idx
ON accUsers;

ALTER TABLE accGroups
  DROP FOREIGN KEY fk_accGroups_accounts_id;
ALTER TABLE accGroups
  DROP FOREIGN KEY fk_accGroups_groups_id;
DROP INDEX fk_accGroups_groups_id_idx
ON accGroups;

DROP INDEX fk_accHistory_userGroup_id
ON accHistory;
DROP INDEX fk_accHistory_users_id
ON accHistory;
DROP INDEX fk_accHistory_users_edit_id_idx
ON accHistory;
DROP INDEX fk_accHistory_customers_id
ON accHistory;
DROP INDEX fk_accHistory_categories_id
ON accHistory;

ALTER TABLE accFiles
  DROP FOREIGN KEY fk_accFiles_accounts_id;

ALTER TABLE authTokens
  DROP FOREIGN KEY fk_authTokens_user_id;
ALTER TABLE authTokens
  DROP FOREIGN KEY fk_authTokens_createdBy_id;
DROP INDEX fk_authTokens_users_id_idx
ON authTokens;
DROP INDEX fk_authTokens_users_createdby_id
ON authTokens;

ALTER TABLE usrPassRecover
  DROP FOREIGN KEY fk_usrPassRecover_users;

ALTER TABLE usrToGroups
  DROP FOREIGN KEY fk_usrToGroups_groups_id;
ALTER TABLE usrToGroups
  DROP FOREIGN KEY fk_usrToGroups_users_id;
DROP INDEX fk_usrToGroups_groups_id_idx
ON usrToGroups;

-- CustomFieldData
ALTER TABLE customFieldsData
RENAME TO CustomFieldData;

-- CustomFieldDefinition
ALTER TABLE customFieldsDef
RENAME TO CustomFieldDefinition;

-- EventLog
ALTER TABLE log
  CHANGE log_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE log
  CHANGE log_date date INT(10) UNSIGNED NOT NULL;
ALTER TABLE log
  CHANGE log_login login VARCHAR(25) NOT NULL;
ALTER TABLE log
  CHANGE log_userId userId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE log
  CHANGE log_ipAddress ipAddress VARCHAR(45) NOT NULL;
ALTER TABLE log
  CHANGE log_action action VARCHAR(50) NOT NULL;
ALTER TABLE log
  CHANGE log_description description TEXT;
ALTER TABLE log
  CHANGE log_level level VARCHAR(20) NOT NULL;
ALTER TABLE log
RENAME TO EventLog;

-- Track
ALTER TABLE track
  CHANGE track_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE track
  CHANGE track_userId userId SMALLINT(5) UNSIGNED;
ALTER TABLE track
  CHANGE track_source source VARCHAR(100) NOT NULL;
ALTER TABLE track
  CHANGE track_time time INT(10) UNSIGNED NOT NULL;
ALTER TABLE track
  CHANGE track_ipv4 ipv4 BINARY(4) NOT NULL;
ALTER TABLE track
  CHANGE track_ipv6 ipv6 BINARY(16);
ALTER TABLE track
RENAME TO Track;

-- AccountFile
ALTER TABLE accFiles
  CHANGE accfile_accountId accountId MEDIUMINT(5) UNSIGNED NOT NULL;
ALTER TABLE accFiles
  CHANGE accfile_id id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE accFiles
  CHANGE accfile_name name VARCHAR(100) NOT NULL;
ALTER TABLE accFiles
  CHANGE accfile_type type VARCHAR(100) NOT NULL;
ALTER TABLE accFiles
  CHANGE accfile_size size INT(11) NOT NULL;
ALTER TABLE accFiles
  CHANGE accfile_content content MEDIUMBLOB NOT NULL;
ALTER TABLE accFiles
  CHANGE accfile_extension extension VARCHAR(10) NOT NULL;
ALTER TABLE accFiles
  CHANGE accFile_thumb thumb MEDIUMBLOB;
ALTER TABLE accFiles
RENAME TO AccountFile;

-- User
ALTER TABLE usrData
  DROP user_secGroupId;
ALTER TABLE usrData
  CHANGE user_id id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE usrData
  CHANGE user_name name VARCHAR(80) NOT NULL;
ALTER TABLE usrData
  CHANGE user_groupId userGroupId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE usrData
  CHANGE user_login login VARCHAR(50) NOT NULL;
ALTER TABLE usrData
  CHANGE user_ssoLogin ssoLogin VARCHAR(100);
ALTER TABLE usrData
  CHANGE user_pass pass VARBINARY(1000) NOT NULL;
ALTER TABLE usrData
  CHANGE user_mPass mPass VARBINARY(1000);
ALTER TABLE usrData
  CHANGE user_mKey mKey VARBINARY(1000) NOT NULL;
ALTER TABLE usrData
  CHANGE user_email email VARCHAR(80);
ALTER TABLE usrData
  CHANGE user_notes notes TEXT;
ALTER TABLE usrData
  CHANGE user_count loginCount INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE usrData
  CHANGE user_profileId userProfileId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE usrData
  CHANGE user_lastLogin lastLogin DATETIME;
ALTER TABLE usrData
  CHANGE user_lastUpdate lastUpdate DATETIME;
ALTER TABLE usrData
  CHANGE user_lastUpdateMPass lastUpdateMPass INT(11) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE usrData
  CHANGE user_isAdminApp isAdminApp TINYINT(1) DEFAULT 0;
ALTER TABLE usrData
  CHANGE user_isAdminAcc isAdminAcc TINYINT(1) DEFAULT 0;
ALTER TABLE usrData
  CHANGE user_isLdap isLdap TINYINT(1) DEFAULT 0;
ALTER TABLE usrData
  CHANGE user_isDisabled isDisabled TINYINT(1) DEFAULT 0;
ALTER TABLE usrData
  CHANGE user_hashSalt hashSalt VARBINARY(128) NOT NULL;
ALTER TABLE usrData
  CHANGE user_isMigrate isMigrate TINYINT(1) DEFAULT 0;
ALTER TABLE usrData
  CHANGE user_isChangePass isChangePass TINYINT(1) DEFAULT 0;
ALTER TABLE usrData
  CHANGE user_isChangedPass isChangedPass TINYINT(1) DEFAULT 0;
ALTER TABLE usrData
  CHANGE user_preferences preferences BLOB;
ALTER TABLE usrData
RENAME TO User;

-- UserProfile
ALTER TABLE usrProfiles
  CHANGE userprofile_id id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE usrProfiles
  CHANGE userprofile_name name VARCHAR(45) NOT NULL;
ALTER TABLE usrProfiles
  CHANGE userProfile_profile profile BLOB NOT NULL;
ALTER TABLE usrProfiles
RENAME TO UserProfile;

-- Notice
ALTER TABLE notices
  CHANGE notice_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE notices
  CHANGE notice_type type VARCHAR(100);
ALTER TABLE notices
  CHANGE notice_component component VARCHAR(100) NOT NULL;
ALTER TABLE notices
  CHANGE notice_description description VARCHAR(500) NOT NULL;
ALTER TABLE notices
  CHANGE notice_date date INT(10) UNSIGNED NOT NULL;
ALTER TABLE notices
  CHANGE notice_checked checked TINYINT(1) DEFAULT 0;
ALTER TABLE notices
  CHANGE notice_userId userId SMALLINT(5) UNSIGNED;
ALTER TABLE notices
  CHANGE notice_sticky sticky TINYINT(1) DEFAULT 0;
ALTER TABLE notices
  CHANGE notice_onlyAdmin onlyAdmin TINYINT(1) DEFAULT 0;
ALTER TABLE notices
RENAME TO Notice;

-- Plugin
ALTER TABLE plugins
  CHANGE plugin_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE plugins
  CHANGE plugin_name name VARCHAR(100) NOT NULL;
ALTER TABLE plugins
  CHANGE plugin_data data VARBINARY(5000);
ALTER TABLE plugins
  CHANGE plugin_enabled enabled TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE plugins
  CHANGE plugin_available available TINYINT(1) DEFAULT 0;
ALTER TABLE plugins
RENAME TO Plugin;

-- PublicLink
ALTER TABLE publicLinks
  CHANGE publicLink_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE publicLinks
  CHANGE publicLink_itemId itemId INT(10) UNSIGNED NOT NULL;
ALTER TABLE publicLinks
  CHANGE publicLink_hash hash VARBINARY(100) NOT NULL;
ALTER TABLE publicLinks
  CHANGE publicLink_linkData data LONGBLOB;
ALTER TABLE publicLinks
RENAME TO PublicLink;

-- Category
ALTER TABLE categories
  CHANGE category_id id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE categories
  CHANGE category_name name VARCHAR(50) NOT NULL;
ALTER TABLE categories
  CHANGE category_hash hash VARBINARY(40) NOT NULL;
ALTER TABLE categories
  CHANGE category_description description VARCHAR(255);
ALTER TABLE categories
RENAME TO Category;

-- Config
DROP INDEX vacParameter
ON config;
ALTER TABLE config
  CHANGE config_parameter parameter VARCHAR(50) NOT NULL;
ALTER TABLE config
  CHANGE config_value value VARCHAR(2000);
ALTER TABLE config
  ADD PRIMARY KEY (parameter);
ALTER TABLE config
RENAME TO Config;

-- Customer
ALTER TABLE customers
  CHANGE customer_id id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE customers
  CHANGE customer_name name VARCHAR(100) NOT NULL;
ALTER TABLE customers
  CHANGE customer_hash hash VARBINARY(40) NOT NULL;
ALTER TABLE customers
  CHANGE customer_description description VARCHAR(255);
ALTER TABLE customers
  CHANGE customer_isGlobal isGlobal TINYINT(1) DEFAULT 0;
ALTER TABLE customers
RENAME TO Client;

-- Account
ALTER TABLE accounts
  CHANGE account_id id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE accounts
  CHANGE account_userGroupId userGroupId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE accounts
  CHANGE account_userId userId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE accounts
  CHANGE account_userEditId userEditId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE accounts
  CHANGE account_customerId clientId MEDIUMINT(8) UNSIGNED NOT NULL;
ALTER TABLE accounts
  CHANGE account_name name VARCHAR(50) NOT NULL;
ALTER TABLE accounts
  CHANGE account_categoryId categoryId MEDIUMINT UNSIGNED NOT NULL;
ALTER TABLE accounts
  CHANGE account_login login VARCHAR(50);
ALTER TABLE accounts
  CHANGE account_url url VARCHAR(255);
ALTER TABLE accounts
  CHANGE account_pass pass VARBINARY(1000) NOT NULL;
ALTER TABLE accounts
  CHANGE account_key `key` VARBINARY(1000) NOT NULL;
ALTER TABLE accounts
  CHANGE account_notes notes TEXT;
ALTER TABLE accounts
  CHANGE account_countView countView INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE accounts
  CHANGE account_countDecrypt countDecrypt INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE accounts
  CHANGE account_dateAdd dateAdd DATETIME NOT NULL;
ALTER TABLE accounts
  CHANGE account_dateEdit dateEdit DATETIME;
ALTER TABLE accounts
  CHANGE account_otherGroupEdit otherUserGroupEdit TINYINT(1) DEFAULT 0;
ALTER TABLE accounts
  CHANGE account_otherUserEdit otherUserEdit TINYINT(1) DEFAULT 0;
ALTER TABLE accounts
  CHANGE account_isPrivate isPrivate TINYINT(1) DEFAULT 0;
ALTER TABLE accounts
  CHANGE account_isPrivateGroup isPrivateGroup TINYINT(1) DEFAULT 0;
ALTER TABLE accounts
  CHANGE account_passDate passDate INT(11) UNSIGNED;
ALTER TABLE accounts
  CHANGE account_passDateChange passDateChange INT(11) UNSIGNED;
ALTER TABLE accounts
  CHANGE account_parentId parentId MEDIUMINT UNSIGNED;
ALTER TABLE accounts
RENAME TO Account;

-- AccountToFavorite
DROP INDEX fk_accFavorites_users_idx
ON accFavorites;
DROP INDEX fk_accFavorites_accounts_idx
ON accFavorites;
ALTER TABLE accFavorites
  CHANGE accfavorite_accountId accountId MEDIUMINT UNSIGNED NOT NULL;
ALTER TABLE accFavorites
  CHANGE accfavorite_userId userId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE accFavorites
RENAME TO AccountToFavorite;

-- AccountHistory
ALTER TABLE accHistory
  CHANGE acchistory_id id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE accHistory
  CHANGE acchistory_accountId accountId MEDIUMINT UNSIGNED NOT NULL;
ALTER TABLE accHistory
  CHANGE acchistory_userGroupId userGroupId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE accHistory
  CHANGE acchistory_userId userId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE accHistory
  CHANGE acchistory_userEditId userEditId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE accHistory
  CHANGE acchistory_customerId clientId MEDIUMINT UNSIGNED NOT NULL;
ALTER TABLE accHistory
  CHANGE acchistory_name name VARCHAR(255) NOT NULL;
ALTER TABLE accHistory
  CHANGE acchistory_categoryId categoryId MEDIUMINT UNSIGNED NOT NULL;
ALTER TABLE accHistory
  CHANGE acchistory_login login VARCHAR(50);
ALTER TABLE accHistory
  CHANGE acchistory_url url VARCHAR(255);
ALTER TABLE accHistory
  CHANGE acchistory_pass pass VARBINARY(1000) NOT NULL;
ALTER TABLE accHistory
  CHANGE acchistory_key `key` VARBINARY(1000) NOT NULL;
ALTER TABLE accHistory
  CHANGE acchistory_notes notes TEXT NOT NULL;
ALTER TABLE accHistory
  CHANGE acchistory_countView countView INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE accHistory
  CHANGE acchistory_countDecrypt countDecrypt INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE accHistory
  CHANGE acchistory_dateAdd dateAdd DATETIME NOT NULL;
ALTER TABLE accHistory
  CHANGE acchistory_dateEdit dateEdit DATETIME;
ALTER TABLE accHistory
  CHANGE acchistory_isModify isModify TINYINT(1) DEFAULT 0;
ALTER TABLE accHistory
  CHANGE acchistory_isDeleted isDeleted TINYINT(1) DEFAULT 0;
ALTER TABLE accHistory
  CHANGE acchistory_mPassHash mPassHash VARBINARY(255) NOT NULL;
ALTER TABLE accHistory
  CHANGE accHistory_otherUserEdit otherUserEdit TINYINT(1) DEFAULT 0;
ALTER TABLE accHistory
  CHANGE accHistory_otherGroupEdit otherUserGroupEdit TINYINT(1) DEFAULT 0;
ALTER TABLE accHistory
  CHANGE accHistory_passDate passDate INT(10) UNSIGNED;
ALTER TABLE accHistory
  CHANGE accHistory_passDateChange passDateChange INT(10) UNSIGNED;
ALTER TABLE accHistory
  CHANGE accHistory_parentId parentId MEDIUMINT UNSIGNED;
ALTER TABLE accHistory
  CHANGE accHistory_isPrivate isPrivate TINYINT(1) DEFAULT 0;
ALTER TABLE accHistory
  CHANGE accHistory_isPrivateGroup isPrivateGroup TINYINT(1) DEFAULT 0;
ALTER TABLE accHistory
RENAME TO AccountHistory;

-- Tag
ALTER TABLE tags
  CHANGE tag_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE tags
  CHANGE tag_name name VARCHAR(45) NOT NULL;
ALTER TABLE tags
  CHANGE tag_hash hash BINARY(40) NOT NULL;
ALTER TABLE tags
RENAME TO Tag;

-- AccountToTag
ALTER TABLE accTags
  CHANGE acctag_accountId accountId MEDIUMINT UNSIGNED NOT NULL;
ALTER TABLE accTags
  CHANGE acctag_tagId tagId INT(10) UNSIGNED NOT NULL;
ALTER TABLE accTags
RENAME TO AccountToTag;

-- AccountToUserGroup
ALTER TABLE accGroups
  CHANGE accgroup_accountId accountId MEDIUMINT UNSIGNED NOT NULL;
ALTER TABLE accGroups
  CHANGE accgroup_groupId userGroupId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE accGroups
RENAME TO AccountToUserGroup;

-- AccountToUser
ALTER TABLE accUsers
  CHANGE accuser_accountId accountId MEDIUMINT UNSIGNED NOT NULL;
ALTER TABLE accUsers
  CHANGE accuser_userId userId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE accUsers
RENAME TO AccountToUser;

-- UserToUserGroup
ALTER TABLE usrToGroups
  CHANGE usertogroup_userId userId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE usrToGroups
  CHANGE usertogroup_groupId userGroupId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE usrToGroups
RENAME TO UserToUserGroup;

-- UserGroup
ALTER TABLE usrGroups
  CHANGE usergroup_id id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE usrGroups
  CHANGE usergroup_name name VARCHAR(50) NOT NULL;
ALTER TABLE usrGroups
  CHANGE usergroup_description description VARCHAR(255);
ALTER TABLE usrGroups
RENAME TO UserGroup;

-- AuthToken
ALTER TABLE authTokens
  CHANGE authtoken_id id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE authTokens
  CHANGE authtoken_userId userId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE authTokens
  CHANGE authtoken_token token VARBINARY(100) NOT NULL;
ALTER TABLE authTokens
  CHANGE authtoken_actionId actionId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE authTokens
  CHANGE authtoken_createdBy createdBy SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE authTokens
  CHANGE authtoken_startDate startDate INT(10) UNSIGNED NOT NULL;
ALTER TABLE authTokens
  CHANGE authtoken_vault vault VARBINARY(2000);
ALTER TABLE authTokens
  CHANGE authtoken_hash hash VARBINARY(1000);
ALTER TABLE authTokens
RENAME TO AuthToken;

-- UserPassRecover
ALTER TABLE usrPassRecover
  CHANGE userpassr_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE usrPassRecover
  CHANGE userpassr_userId userId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE usrPassRecover
  CHANGE userpassr_hash hash VARBINARY(128) NOT NULL;
ALTER TABLE usrPassRecover
  CHANGE userpassr_date date INT(10) UNSIGNED NOT NULL;
ALTER TABLE usrPassRecover
  CHANGE userpassr_used used TINYINT(1) DEFAULT 0;
ALTER TABLE usrPassRecover
RENAME TO UserPassRecover;

-- Views
CREATE OR REPLACE VIEW account_search_v AS
  SELECT DISTINCT
    `Account`.`id`                                       AS `id`,
    `Account`.`clientId`                                 AS `clientId`,
    `Account`.`categoryId`                               AS `categoryId`,
    `Account`.`name`                                     AS `name`,
    `Account`.`login`                                    AS `login`,
    `Account`.`url`                                      AS `url`,
    `Account`.`notes`                                    AS `notes`,
    `Account`.`userId`                                   AS `userId`,
    `Account`.`userGroupId`                              AS `userGroupId`,
    `Account`.`otherUserEdit`                            AS `otherUserEdit`,
    `Account`.`otherUserGroupEdit`                       AS `otherUserGroupEdit`,
    `Account`.`isPrivate`                                AS `isPrivate`,
    `Account`.`isPrivateGroup`                           AS `isPrivateGroup`,
    `Account`.`passDate`                                 AS `passDate`,
    `Account`.`passDateChange`                           AS `passDateChange`,
    `Account`.`parentId`                                 AS `parentId`,
    `Account`.`countView`                                AS `countView`,
    `Account`.`dateEdit`                                 AS `dateEdit`,
    `ug`.`name`                                          AS `userGroupName`,
    `Category`.`name`                                    AS `categoryName`,
    `Client`.`name`                                      AS `clientName`,
    (SELECT count(0)
     FROM `AccountFile`
     WHERE (`AccountFile`.`accountId` = `Account`.`id`)) AS `num_files`
  FROM (((`Account`
    LEFT JOIN `Category`
      ON ((`Account`.`categoryId` = `Category`.`id`))) LEFT JOIN
    `UserGroup` `ug` ON ((`Account`.`userGroupId` = `ug`.`id`))) LEFT JOIN
    `Client` ON ((`Client`.`id` = `Account`.`clientId`)));

CREATE OR REPLACE VIEW account_data_v AS
  SELECT
    `Account`.`id`                              AS `id`,
    `Account`.`name`                            AS `name`,
    `Account`.`categoryId`                      AS `categoryId`,
    `Account`.`userId`                          AS `userId`,
    `Account`.`clientId`                        AS `clientId`,
    `Account`.`userGroupId`                     AS `userGroupId`,
    `Account`.`userEditId`                      AS `userEditId`,
    `Account`.`login`                           AS `login`,
    `Account`.`url`                             AS `url`,
    `Account`.`notes`                           AS `notes`,
    `Account`.`countView`                       AS `countView`,
    `Account`.`countDecrypt`                    AS `countDecrypt`,
    `Account`.`dateAdd`                         AS `dateAdd`,
    `Account`.`dateEdit`                        AS `dateEdit`,
    conv(`Account`.`otherUserEdit`, 10, 2)      AS `otherUserEdit`,
    conv(`Account`.`otherUserGroupEdit`, 10, 2) AS `otherUserGroupEdit`,
    conv(`Account`.`isPrivate`, 10, 2)          AS `isPrivate`,
    conv(`Account`.`isPrivateGroup`, 10, 2)     AS `isPrivateGroup`,
    `Account`.`passDate`                        AS `passDate`,
    `Account`.`passDateChange`                  AS `passDateChange`,
    `Account`.`parentId`                        AS `parentId`,
    `Category`.`name`                           AS `categoryName`,
    `Client`.`name`                             AS `clientName`,
    `ug`.`name`                                 AS `userGroupName`,
    `u1`.`name`                                 AS `userName`,
    `u1`.`login`                                AS `userLogin`,
    `u2`.`name`                                 AS `userEditName`,
    `u2`.`login`                                AS `userEditLogin`,
    `PublicLink`.`hash`                         AS `publicLinkHash`
  FROM ((((((`Account`
    LEFT JOIN `Category`
      ON ((`Account`.`categoryId` = `Category`.`id`))) INNER JOIN
    `UserGroup` `ug` ON ((`Account`.`userGroupId` = `ug`.`id`))) INNER JOIN
    `User` `u1` ON ((`Account`.`userId` = `u1`.`id`))) INNER JOIN
    `User` `u2` ON ((`Account`.`userEditId` = `u2`.`id`))) LEFT JOIN
    `Client`
      ON ((`Account`.`clientId` = `Client`.`id`))) LEFT JOIN
    `PublicLink` ON ((`Account`.`id` = `PublicLink`.`itemId`)));

-- Foreign Keys

CREATE INDEX fk_Account_userId
  ON Account (userId);

CREATE INDEX fk_Account_userEditId
  ON Account (userEditId);

ALTER TABLE Account
  ADD CONSTRAINT fk_Account_userGroupId
FOREIGN KEY (userGroupId) REFERENCES UserGroup (id);

ALTER TABLE Account
  ADD CONSTRAINT fk_Account_userId
FOREIGN KEY (userId) REFERENCES User (id);

ALTER TABLE Account
  ADD CONSTRAINT fk_Account_userEditId
FOREIGN KEY (userEditId) REFERENCES User (id);

ALTER TABLE Account
  ADD CONSTRAINT fk_Account_clientId
FOREIGN KEY (clientId) REFERENCES Client (id);

ALTER TABLE Account
  ADD CONSTRAINT fk_Account_categoryId
FOREIGN KEY (categoryId) REFERENCES Category (id);

ALTER TABLE AccountFile
  ADD CONSTRAINT fk_AccountFile_accountId
FOREIGN KEY (accountId) REFERENCES Account (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

CREATE INDEX fk_AccountHistory_userGroupId
  ON AccountHistory (userGroupId);

CREATE INDEX fk_AccountHistory_userId
  ON AccountHistory (userId);

CREATE INDEX fk_AccountHistory_userEditId
  ON AccountHistory (userEditId);

CREATE INDEX fk_AccountHistory_clientId
  ON AccountHistory (clientId);

CREATE INDEX fk_AccountHistory_categoryId
  ON AccountHistory (categoryId);

ALTER TABLE AccountHistory
  ADD CONSTRAINT fk_AccountHistory_userGroupId
FOREIGN KEY (userGroupId) REFERENCES UserGroup (id);

ALTER TABLE AccountHistory
  ADD CONSTRAINT fk_AccountHistory_userId
FOREIGN KEY (userId) REFERENCES User (id);

ALTER TABLE AccountHistory
  ADD CONSTRAINT fk_AccountHistory_userEditId
FOREIGN KEY (userEditId) REFERENCES User (id);

ALTER TABLE AccountHistory
  ADD CONSTRAINT fk_AccountHistory_clientId
FOREIGN KEY (clientId) REFERENCES Client (id);

ALTER TABLE AccountHistory
  ADD CONSTRAINT fk_AccountHistory_categoryId
FOREIGN KEY (categoryId) REFERENCES Category (id);

CREATE INDEX fk_AccountToFavorite_userId
  ON AccountToFavorite (userId);

ALTER TABLE AccountToFavorite
  ADD CONSTRAINT fk_AccountToFavorite_accountId
FOREIGN KEY (accountId) REFERENCES Account (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

ALTER TABLE AccountToFavorite
  ADD CONSTRAINT fk_AccountToFavorite_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

ALTER TABLE AccountToFavorite
  ADD PRIMARY KEY (accountId, userId);

CREATE INDEX fk_AccountToGroup_userGroupId
  ON AccountToUserGroup (userGroupId);

ALTER TABLE AccountToUserGroup
  ADD CONSTRAINT fk_AccountToGroup_accountId
FOREIGN KEY (accountId) REFERENCES Account (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

ALTER TABLE AccountToUserGroup
  ADD CONSTRAINT fk_AccountToGroup_userGroupId
FOREIGN KEY (userGroupId) REFERENCES UserGroup (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

ALTER TABLE AccountToUserGroup
  ADD PRIMARY KEY (accountId, userGroupId);

CREATE INDEX fk_AccountToTag_accountId
  ON AccountToTag (accountId);

CREATE INDEX fk_AccountToTag_tagId
  ON AccountToTag (tagId);

ALTER TABLE AccountToTag
  ADD CONSTRAINT fk_AccountToTag_accountId
FOREIGN KEY (accountId) REFERENCES Account (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

ALTER TABLE AccountToTag
  ADD CONSTRAINT fk_AccountToTag_tagId
FOREIGN KEY (tagId) REFERENCES Tag (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

ALTER TABLE AccountToTag
  ADD PRIMARY KEY (accountId, tagId);

CREATE INDEX fk_AccountToUser_userId
  ON AccountToUser (userId);

ALTER TABLE AccountToUser
  ADD CONSTRAINT fk_AccountToUser_accountId
FOREIGN KEY (accountId) REFERENCES Account (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

ALTER TABLE AccountToUser
  ADD CONSTRAINT fk_AccountToUser_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

ALTER TABLE AccountToUser
  ADD PRIMARY KEY (accountId, userId);

CREATE INDEX fk_AuthToken_actionId
  ON AuthToken (actionId);

ALTER TABLE AuthToken
  ADD CONSTRAINT fk_AuthToken_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

ALTER TABLE AuthToken
  ADD CONSTRAINT fk_AuthToken_actionId
FOREIGN KEY (actionId) REFERENCES Action (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

ALTER TABLE CustomFieldData
  ADD CONSTRAINT fk_CustomFieldData_definitionId
FOREIGN KEY (definitionId) REFERENCES CustomFieldDefinition (id);

CREATE INDEX fk_CustomFieldDefinition_typeId
  ON CustomFieldDefinition (typeId);

ALTER TABLE CustomFieldDefinition
  ADD CONSTRAINT fk_CustomFieldDefinition_typeId
FOREIGN KEY (typeId) REFERENCES CustomFieldType (id)
  ON UPDATE CASCADE;

ALTER TABLE Notice
  ADD CONSTRAINT fk_Notice_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

CREATE INDEX fk_PublicLink_userId
  ON PublicLink (userId);

ALTER TABLE PublicLink
  ADD CONSTRAINT fk_PublicLink_userId
FOREIGN KEY (userId) REFERENCES User (id);

CREATE INDEX fk_User_userGroupId
  ON User (userGroupId);

CREATE INDEX fk_User_userProfileId
  ON User (userProfileId);

ALTER TABLE User
  ADD CONSTRAINT fk_User_userGroupId
FOREIGN KEY (userGroupId) REFERENCES UserGroup (id);

ALTER TABLE User
  ADD CONSTRAINT fk_User_userProfileId
FOREIGN KEY (userProfileId) REFERENCES UserProfile (id);

ALTER TABLE UserPassRecover
  ADD CONSTRAINT fk_UserPassRecover_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

CREATE INDEX fk_UserToGroup_userGroupId
  ON UserToUserGroup (userGroupId);

ALTER TABLE UserToUserGroup
  ADD CONSTRAINT fk_UserToGroup_userId
FOREIGN KEY (userId) REFERENCES User (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

ALTER TABLE UserToUserGroup
  ADD CONSTRAINT fk_UserToGroup_userGroupId
FOREIGN KEY (userGroupId) REFERENCES UserGroup (id)
  ON UPDATE CASCADE
  ON DELETE CASCADE;


