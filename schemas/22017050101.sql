ALTER TABLE `customers`
  ADD `customer_isGlobal` BIT(1) DEFAULT b'0' NULL;
ALTER TABLE `usrData`
  ADD `user_ssoLogin` VARCHAR(100) NULL
  AFTER `user_login`;

DROP INDEX IDX_login
ON `usrData`;
CREATE UNIQUE INDEX `IDX_login`
  ON `usrData` (`user_login`, `user_ssoLogin`);

ALTER TABLE plugins
  ADD `plugin_available` BIT(1) DEFAULT b'0' NULL;

CREATE TABLE `actions` (
  `action_id`    SMALLINT(5) UNSIGNED NOT NULL,
  `action_name`  VARCHAR(50)          NOT NULL,
  `action_text`  VARCHAR(100)         NOT NULL,
  `action_route` VARCHAR(100),
  PRIMARY KEY (`action_id`, `action_name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE actions
(
  action_id    INT(10) UNSIGNED PRIMARY KEY NOT NULL,
  action_name  VARCHAR(50)                  NOT NULL,
  action_text  VARCHAR(100)                 NOT NULL,
  action_route VARCHAR(100)
);

INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1, 'ACCOUNT_SEARCH', 'Buscar Cuentas', 'account/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (10, 'ACCOUNT', 'Cuentas', 'account/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (11, 'ACCOUNT_FILE', 'Archivos', 'account/listFile');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (12, 'ACCOUNT_REQUEST', 'Peticiones', 'account/request');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (13, 'ACCOUNT_FAVORITE', 'Favoritos', 'favorite/index');
INSERT INTO actions (action_id, action_name, action_text, action_route) VALUES (20, 'WIKI', 'Wiki', 'wiki/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (60, 'ITEMS_MANAGE', 'Elementos y Personalización', 'itemManager/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (61, 'CATEGORY', 'Gestión Categorías', 'category/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (62, 'CLIENT', 'Gestión Clientes', 'client/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (63, 'APITOKEN', 'Gestión Autorizaciones API', 'apiToken/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (64, 'CUSTOMFIELD', 'Gestión Campos Personalizados', 'customField/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (65, 'PUBLICLINK', 'Enlaces Públicos', 'publicLink/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (66, 'FILE', 'Gestión de Archivos', 'file/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (67, 'ACCOUNTMGR', 'Gestión de Cuentas', 'accountManager/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (68, 'TAG', 'Gestión de Etiquetas', 'tag/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (69, 'PLUGIN', 'Gestión Plugins', 'plugin/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (70, 'ACCESS_MANAGE', 'Usuarios y Accesos', 'accessManager/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (71, 'USER', 'Gestión Usuarios', 'user/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (72, 'GROUP', 'Gestión Grupos', 'group/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (73, 'PROFILE', 'Gestión Perfiles', 'profile/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (90, 'EVENTLOG', 'Registro de Eventos', 'eventlog/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (100, 'ACCOUNT_VIEW', 'Ver Cuenta', 'account/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (101, 'ACCOUNT_CREATE', 'Nueva Cuenta', 'account/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (102, 'ACCOUNT_EDIT', 'Editar Cuenta', 'account/edit');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (103, 'ACCOUNT_DELETE', 'Eliminar Cuenta', 'account/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (104, 'ACCOUNT_VIEW_PASS', 'Ver Clave', 'account/viewPass');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (105, 'ACCOUNT_VIEW_HISTORY', 'Ver Historial', 'account/viewHistory');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (106, 'ACCOUNT_EDIT_PASS', 'Editar Clave de Cuenta', 'account/editPass');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (107, 'ACCOUNT_EDIT_RESTORE', 'Restaurar Cuenta', 'account/restore');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (108, 'ACCOUNT_COPY', 'Copiar Cuenta', 'account/copy');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (109, 'ACCOUNT_COPY_PASS', 'Copiar Clave', 'account/copyPass');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (111, 'ACCOUNT_FILE_VIEW', 'Ver Archivo', 'account/viewFile');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (112, 'ACCOUNT_FILE_UPLOAD', 'Subir Archivo', 'account/uploadFile');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (113, 'ACCOUNT_FILE_DOWNLOAD', 'Descargar Archivo', 'account/downloadFile');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (114, 'ACCOUNT_FILE_DELETE', 'Eliminar Archivo', 'account/deleteFile');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (130, 'ACCOUNT_FAVORITE_VIEW', 'Ver Favoritos', 'favorite/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (131, 'ACCOUNT_FAVORITE_ADD', 'Añadir Favorito', 'favorite/add');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (133, 'ACCOUNT_FAVORITE_DELETE', 'Eliminar Favorito', 'favorite/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (200, 'WIKI_VIEW', 'Ver Wiki', 'wiki/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (201, 'WIKI_NEW', 'Añadir Wiki', 'wiki/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (202, 'WIKI_EDIT', 'Editar Wiki', 'wiki/edit');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (203, 'WIKI_DELETE', 'Eliminar Wiki', 'wiki/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (610, 'CATEGORY_VIEW', 'Ver Categoría', 'category/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (611, 'CATEGORY_CREATE', 'Nueva Categoría', 'category/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (612, 'CATEGORY_EDIT', 'Editar Categoría', 'category/edit');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (613, 'CATEGORY_DELETE', 'Eliminar Categoría', 'category/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (615, 'CATEGORY_SEARCH', 'Buscar Categoría', 'category/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (620, 'CLIENT_VIEW', 'Ver Cliente', 'client/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (621, 'CLIENT_CREATE', 'Nuevo CLiente', 'client/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (622, 'CLIENT_EDIT', 'Editar Cliente', 'client/edit');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (623, 'CLIENT_DELETE', 'Eliminar Cliente', 'client/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (625, 'CLIENT_SEARCH', 'Buscar Cliente', 'client/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (630, 'APITOKEN_CREATE', 'Nuevo Token API', 'apiToken/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (631, 'APITOKEN_VIEW', 'Ver Token API', 'apiToken/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (632, 'APITOKEN_EDIT', 'Editar Token API', 'apiToken/edit');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (633, 'APITOKEN_DELETE', 'Eliminar Token API', 'apiToken/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (635, 'APITOKEN_SEARCH', 'Buscar Token API', 'apiToken/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (640, 'CUSTOMFIELD_CREATE', 'Nuevo Campo Personalizado', 'customField/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (641, 'CUSTOMFIELD_VIEW', 'Ver Campo Personalizado', 'customField/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (642, 'CUSTOMFIELD_EDIT', 'Editar Campo Personalizado', 'customField/edit');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (643, 'CUSTOMFIELD_DELETE', 'Eliminar Campo Personalizado', 'customField/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (645, 'CUSTOMFIELD_SEARCH', 'Buscar Campo Personalizado', 'customField/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (650, 'PUBLICLINK_CREATE', 'Crear Enlace Público', 'publicLink/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (651, 'PUBLICLINK_VIEW', 'Ver Enlace Público', 'publicLink/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (653, 'PUBLICLINK_DELETE', 'Eliminar Enlace Público', 'publicLink/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (654, 'PUBLICLINK_REFRESH', 'Actualizar Enlace Público', 'publicLink/refresh');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (655, 'PUBLICLINK_SEARCH', 'Buscar Enlace Público', 'publicLink/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (661, 'FILE_VIEW', 'Ver Archivo', 'file/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (663, 'FILE_DELETE', 'Eliminar Archivo', 'file/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (665, 'FILE_SEARCH', 'Buscar Archivo', 'file/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (671, 'ACCOUNTMGR_VIEW', 'Ver Cuenta', 'accountManager/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (673, 'ACCOUNTMGR_DELETE', 'Eliminar Cuenta', 'accountManager/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (675, 'ACCOUNTMGR_SEARCH', 'Buscar Cuenta', 'accountManager/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (680, 'TAG_CREATE', 'Nueva Etiqueta', 'tag/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (681, 'TAG_VIEW', 'Ver Etiqueta', 'tag/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (682, 'TAG_EDIT', 'Editar Etiqueta', 'tag/edit');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (683, 'TAG_DELETE', 'Eliminar Etiqueta', 'tag/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (685, 'TAG_SEARCH', 'Buscar Etiqueta', 'tag/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (690, 'PLUGIN_NEW', 'Nuevo Plugin', 'plugin/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (691, 'PLUGIN_VIEW', 'Ver Plugin', 'plugin/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (695, 'PLUGIN_SEARCH', 'Buscar Plugin', 'plugin/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (696, 'PLUGIN_ENABLE', 'Habilitar Plugin', 'plugin/enable');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (697, 'PLUGIN_DISABLE', 'Deshabilitar Plugin', 'plugin/disable');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (698, 'PLUGIN_RESET', 'Restablecer Plugin', 'plugin/reset');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (710, 'USER_VIEW', 'Ver Usuario', 'user/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (711, 'USER_CREATE', 'Nuevo Usuario', 'user/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (712, 'USER_EDIT', 'Editar Usuario', 'user/edit');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (713, 'USER_DELETE', 'Eliminar Usuario', 'user/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (714, 'USER_EDIT_PASS', 'Editar Clave Usuario', 'user/editPass');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (715, 'USER_SEARCH', 'Buscar Usuario', 'user/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (720, 'GROUP_VIEW', 'Ver Grupo', 'userGroup/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (721, 'GROUP_CREATE', 'Nuevo Grupo', 'userGroup/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (722, 'GROUP_EDIT', 'Editar Grupo', 'userGroup/edit');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (723, 'GROUP_DELETE', 'Eliminar Grupo', 'userGroup/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (725, 'GROUP_SEARCH', 'Buscar Grupo', 'userGroup/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (730, 'PROFILE_VIEW', 'Ver Perfil', 'userProfile/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (731, 'PROFILE_CREATE', 'Nuevo Perfil', 'userProfile/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (732, 'PROFILE_EDIT', 'Editar Perfil', 'userProfile/edit');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (733, 'PROFILE_DELETE', 'Eliminar Perfil', 'userProfile/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (735, 'PROFILE_SEARCH', 'Buscar Perfil', 'userProfile/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (740, 'PREFERENCE', 'Gestión Preferencias', 'userPreference/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (741, 'PREFERENCE_GENERAL', 'Preferencias General', 'userPreference/general');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (742, 'PREFERENCE_SECURITY', 'Preferencias Seguridad', 'userPreference/security');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (760, 'NOTICE', 'Notificaciones', 'notice/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (761, 'NOTICE_USER', 'Notificaciones Usuario', 'noticeUser/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1000, 'CONFIG', 'Configuración', 'config/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1001, 'CONFIG_GENERAL', 'Configuración General', 'config/general');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1010, 'ACCOUNT_CONFIG', 'Configuración Cuentas', 'account/config');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1020, 'WIKI_CONFIG', 'Configuración Wiki', 'wiki/config');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1030, 'ENCRYPTION_CONFIG', 'Configuración Encriptación', 'encryption/config');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1031, 'ENCRYPTION_REFRESH', 'Actualizar Hash', 'encryption/updateHash');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1032, 'ENCRYPTION_TEMPPASS', 'Clave Maestra Temporal', 'encryption/createTempPass');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1040, 'BACKUP_CONFIG', 'Configuración Copia de Seguridad', 'backup/config');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1050, 'IMPORT_CONFIG', 'Configuración Importación', 'import/config');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1051, 'IMPORT_CSV', 'Importar CSV', 'import/csv');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1052, 'IMPORT_XML', 'Importar XML', 'import/xml');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1070, 'MAIL_CONFIG', 'Configuración Email', 'mail/config');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1080, 'LDAP_CONFIG', 'Configuración LDAP', 'ldap/config');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (1081, 'LDAP_SYNC', 'Sincronización LDAP', 'ldap/sync');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (6701, 'ACCOUNTMGR_HISTORY', 'Gestión de Cuenta (H)', 'accountHistoryManager/index');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (6731, 'ACCOUNTMGR_DELETE_HISTORY', 'Eliminar Cuenta', 'accountHistoryManager/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (6751, 'ACCOUNTMGR_SEARCH_HISTORY', 'Buscar Cuenta', 'accountHistoryManager/search');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (6771, 'ACCOUNTMGR_RESTORE', 'Restaurar Cuenta', 'accountManager/restore');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (7610, 'NOTICE_USER_VIEW', 'Ver Notificación', 'userNotice/view');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (7611, 'NOTICE_USER_CREATE', 'Crear Notificación', 'userNotice/create');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (7612, 'NOTICE_USER_EDIT', 'Editar Notificación', 'userNotice/edit');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (7613, 'NOTICE_USER_DELETE', 'Eliminar Notificación', 'userNotice/delete');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (7614, 'NOTICE_USER_CHECK', 'Marcar Notificación', 'userNotice/check');
INSERT INTO actions (action_id, action_name, action_text, action_route)
VALUES (7615, 'NOTICE_USER_SEARCH', 'Buscar Notificación', 'userNotice/search');

ALTER TABLE customFieldsDef
  MODIFY field BLOB;
ALTER TABLE customFieldsDef
  ADD required TINYINT(1) UNSIGNED NULL;
ALTER TABLE customFieldsDef
  ADD help VARCHAR(255) NULL;
ALTER TABLE customFieldsDef
  ADD showInList TINYINT(1) UNSIGNED NULL;
ALTER TABLE customFieldsDef
  ADD name VARCHAR(100) NOT NULL;
ALTER TABLE customFieldsDef
  MODIFY COLUMN name VARCHAR(100) NOT NULL
  AFTER id;
ALTER TABLE customFieldsDef
  CHANGE customfielddef_module moduleId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE customFieldsDef
  CHANGE customfielddef_field field BLOB NOT NULL;
ALTER TABLE customFieldsData
  DROP FOREIGN KEY fk_customFieldsDef_id;
ALTER TABLE customFieldsData
  CHANGE customfielddata_defId defId INT(10) UNSIGNED NOT NULL;
ALTER TABLE customFieldsDef
  CHANGE customfielddef_id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE customFieldsData
  ADD CONSTRAINT fk_customFieldsDef_id
FOREIGN KEY (definitionId) REFERENCES customFieldsDef (id);
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

CREATE TABLE customFieldsType
(
  id   TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  text VARCHAR(50) NOT NULL
);

ALTER TABLE customFieldsDef
  ADD typeId TINYINT UNSIGNED NULL;
ALTER TABLE customFieldsDef
  ADD CONSTRAINT fk_customFieldsType_id
FOREIGN KEY (typeId) REFERENCES customFieldsType (id)
  ON UPDATE CASCADE;

CREATE TABLE customFieldsType
(
  id   TINYINT(3) UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
  name VARCHAR(50)                     NOT NULL,
  text VARCHAR(50)                     NOT NULL
);

-- Extraer antes desde los datos
INSERT INTO customFieldsType (id, name, text) VALUES (1, 'text', 'Texto');
INSERT INTO customFieldsType (id, name, text) VALUES (2, 'password', 'Clave');
INSERT INTO customFieldsType (id, name, text) VALUES (3, 'date', 'Fecha');
INSERT INTO customFieldsType (id, name, text) VALUES (4, 'number', 'Número');
INSERT INTO customFieldsType (id, name, text) VALUES (5, 'email', 'Email');
INSERT INTO customFieldsType (id, name, text) VALUES (6, 'telephone', 'Teléfono');
INSERT INTO customFieldsType (id, name, text) VALUES (7, 'url', 'URL');
INSERT INTO customFieldsType (id, name, text) VALUES (8, 'color', 'Color');
INSERT INTO customFieldsType (id, name, text) VALUES (9, 'wiki', 'Wiki');
INSERT INTO customFieldsType (id, name, text) VALUES (10, 'textarea', 'Área de texto');

ALTER TABLE accFiles
  DROP FOREIGN KEY fk_accFiles_accounts_id;
ALTER TABLE accFiles
  CHANGE accfile_accountId accountId SMALLINT(5) UNSIGNED NOT NULL;
ALTER TABLE accFiles
  ADD CONSTRAINT fk_account_id
FOREIGN KEY (accountId) REFERENCES accounts (account_id);
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

CREATE OR REPLACE VIEW account_search_v AS
  SELECT DISTINCT
    `accounts`.`account_id`                                    AS `account_id`,
    `accounts`.`account_customerId`                            AS `account_customerId`,
    `accounts`.`account_categoryId`                            AS `account_categoryId`,
    `accounts`.`account_name`                                  AS `account_name`,
    `accounts`.`account_login`                                 AS `account_login`,
    `accounts`.`account_url`                                   AS `account_url`,
    `accounts`.`account_notes`                                 AS `account_notes`,
    `accounts`.`account_userId`                                AS `account_userId`,
    `accounts`.`account_userGroupId`                           AS `account_userGroupId`,
    `accounts`.`account_otherUserEdit`                         AS `account_otherUserEdit`,
    `accounts`.`account_otherGroupEdit`                        AS `account_otherGroupEdit`,
    `accounts`.`account_isPrivate`                             AS `account_isPrivate`,
    `accounts`.`account_isPrivateGroup`                        AS `account_isPrivateGroup`,
    `accounts`.`account_passDate`                              AS `account_passDate`,
    `accounts`.`account_passDateChange`                        AS `account_passDateChange`,
    `accounts`.`account_parentId`                              AS `account_parentId`,
    `accounts`.`account_countView`                             AS `account_countView`,
    `ug`.`usergroup_name`                                      AS `usergroup_name`,
    `categories`.`category_name`                               AS `category_name`,
    `customers`.`customer_name`                                AS `customer_name`,
    (SELECT count(0)
     FROM `accFiles`
     WHERE (`accFiles`.`accountId` = `accounts`.`account_id`)) AS `num_files`
  FROM (((`accounts`
    LEFT JOIN `categories`
      ON ((`accounts`.`account_categoryId` = `categories`.`category_id`))) LEFT JOIN
    `usrGroups` `ug` ON ((`accounts`.`account_userGroupId` = `ug`.`usergroup_id`))) LEFT JOIN
    `customers` ON ((`customers`.`customer_id` = `accounts`.`account_customerId`)));

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