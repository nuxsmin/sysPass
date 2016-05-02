<?php

/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace SP\Core;

use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Exceptions\SPException;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Profiles\ProfileUtil;
use SP\Storage\DB;
use SP\Mgmt\Users\UserMigrate;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones actualización de la aplicación.
 */
class Upgrade
{
    private static $dbUpgrade = array(110, 1121, 1122, 1123, 11213, 11219, 11220, 12001, 12002, 1316011001, 1316020501);
    private static $cfgUpgrade = array(1124, 1316020501);

    /**
     * Inicia el proceso de actualización de la BBDD.
     *
     * @param int $version con la versión de la BBDD actual
     * @returns bool
     */
    public static function doUpgrade($version)
    {
        foreach (self::$dbUpgrade as $upgradeVersion) {
            if ($version < $upgradeVersion) {
                if (self::upgradeTo($upgradeVersion) === false) {
                    Init::initError(
                        _('Error al aplicar la actualización de la Base de Datos'),
                        _('Compruebe el registro de eventos para más detalles') . '. <a href="index.php?nodbupgrade=1">' . _('Acceder') . '</a>');
                }

                if (self::auxUpgrades($upgradeVersion) === false) {
                    Init::initError(
                        _('Error al aplicar la actualización auxiliar'),
                        _('Compruebe el registro de eventos para más detalles') . '. <a href="index.php?nodbupgrade=1">' . _('Acceder') . '</a>');
                }
            }
        }

        return true;
    }

    /**
     * Actualiza la BBDD según la versión.
     *
     * @param int $version con la versión a actualizar
     * @returns bool
     */
    private static function upgradeTo($version)
    {
        $Log = new Log(_('Actualizar BBDD'));

        switch ($version) {
            case 110:
                $queries[] = 'ALTER TABLE `accFiles` CHANGE COLUMN `accfile_name` `accfile_name` VARCHAR(100) NOT NULL';
                $queries[] = 'ALTER TABLE `accounts` ADD COLUMN `account_otherGroupEdit` BIT(1) NULL DEFAULT 0 AFTER `account_dateEdit`, ADD COLUMN `account_otherUserEdit` BIT(1) NULL DEFAULT 0 AFTER `account_otherGroupEdit`;';
                $queries[] = 'CREATE TABLE `accUsers` (`accuser_id` INT NOT NULL AUTO_INCREMENT,`accuser_accountId` INT(10) UNSIGNED NOT NULL,`accuser_userId` INT(10) UNSIGNED NOT NULL, PRIMARY KEY (`accuser_id`), INDEX `idx_account` (`accuser_accountId` ASC)) DEFAULT CHARSET=utf8;';
                $queries[] = 'ALTER TABLE `accHistory` ADD COLUMN `accHistory_otherUserEdit` BIT NULL AFTER `acchistory_mPassHash`, ADD COLUMN `accHistory_otherGroupEdit` VARCHAR(45) NULL AFTER `accHistory_otherUserEdit`;';
                $queries[] = 'ALTER TABLE `accFiles` CHANGE COLUMN `accfile_type` `accfile_type` VARCHAR(100) NOT NULL ;';
                break;
            case 1121:
                $queries[] = 'ALTER TABLE `categories` ADD COLUMN `category_description` VARCHAR(255) NULL AFTER `category_name`;';
                $queries[] = 'ALTER TABLE `usrProfiles` ADD COLUMN `userProfile_pAppMgmtMenu` BIT(1) NULL DEFAULT b\'0\' AFTER `userProfile_pUsersMenu`,CHANGE COLUMN `userProfile_pConfigCategories` `userProfile_pAppMgmtCategories` BIT(1) NULL DEFAULT b\'0\' AFTER `userProfile_pAppMgmtMenu`,ADD COLUMN `userProfile_pAppMgmtCustomers` BIT(1) NULL DEFAULT b\'0\' AFTER `userProfile_pAppMgmtCategories`;';
                break;
            case 1122:
                $queries[] = 'ALTER TABLE `usrData` CHANGE COLUMN `user_login` `user_login` VARCHAR(50) NOT NULL ,CHANGE COLUMN `user_email` `user_email` VARCHAR(80) NULL DEFAULT NULL ;';
                break;
            case 1123:
                $queries[] = 'CREATE TABLE `usrPassRecover` (`userpassr_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `userpassr_userId` SMALLINT UNSIGNED NOT NULL,`userpassr_hash` VARBINARY(40) NOT NULL,`userpassr_date` INT UNSIGNED NOT NULL,`userpassr_used` BIT(1) NOT NULL DEFAULT b\'0\', PRIMARY KEY (`userpassr_id`),INDEX `IDX_userId` (`userpassr_userId` ASC, `userpassr_date` ASC)) DEFAULT CHARSET=utf8;';
                $queries[] = 'ALTER TABLE `log` ADD COLUMN `log_ipAddress` VARCHAR(45) NOT NULL AFTER `log_userId`;';
                $queries[] = 'ALTER TABLE `usrData` ADD COLUMN `user_isChangePass` BIT(1) NULL DEFAULT b\'0\' AFTER `user_isMigrate`;';
                break;
            case 11213:
                $queries[] = 'ALTER TABLE `usrData` CHANGE COLUMN `user_mPass` `user_mPass` VARBINARY(32) NULL DEFAULT NULL ,CHANGE COLUMN `user_lastLogin` `user_lastLogin` DATETIME NULL DEFAULT NULL ,CHANGE COLUMN `user_lastUpdate` `user_lastUpdate` DATETIME NULL DEFAULT NULL, CHANGE COLUMN `user_mIV` `user_mIV` VARBINARY(32) NULL ;';
                $queries[] = 'ALTER TABLE `accounts` CHANGE COLUMN `account_login` `account_login` VARCHAR(50) NULL DEFAULT NULL ;';
                break;
            case 11219:
                $queries[] = 'ALTER TABLE `accounts` CHANGE COLUMN `account_pass` `account_pass` VARBINARY(255) NOT NULL ;';
                $queries[] = 'ALTER TABLE `accHistory` CHANGE COLUMN `acchistory_pass` `acchistory_pass` VARBINARY(255) NOT NULL ;';
                break;
            case 11220:
                $queries[] = 'ALTER TABLE `usrData` CHANGE COLUMN `user_pass` `user_pass` VARBINARY(255) NOT NULL,CHANGE COLUMN `user_mPass` `user_mPass` VARBINARY(255) DEFAULT NULL ;';
                break;
            case 12001:
                $queries[] = 'ALTER TABLE `accounts` CHANGE COLUMN `account_userEditId` `account_userEditId` TINYINT(3) UNSIGNED NULL DEFAULT NULL, CHANGE COLUMN `account_dateEdit` `account_dateEdit` DATETIME NULL DEFAULT NULL;';
                $queries[] = 'ALTER TABLE `accHistory` CHANGE COLUMN `acchistory_userEditId` `acchistory_userEditId` TINYINT(3) UNSIGNED NULL DEFAULT NULL, CHANGE COLUMN `acchistory_dateEdit` `acchistory_dateEdit` DATETIME NULL DEFAULT NULL;';
                $queries[] = 'ALTER TABLE `accHistory` CHANGE COLUMN `accHistory_otherGroupEdit` `accHistory_otherGroupEdit` BIT NULL DEFAULT b\'0\';';
                $queries[] = 'ALTER TABLE `usrProfiles` ADD COLUMN `userProfile_profile` BLOB NOT NULL;';
                $queries[] = 'ALTER TABLE `usrData` ADD `user_preferences` BLOB NULL;';
                $queries[] = 'CREATE TABLE usrToGroups (usertogroup_id INT UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,usertogroup_userId INT UNSIGNED NOT NULL,usertogroup_groupId INT UNSIGNED NOT NULL) DEFAULT CHARSET=utf8;';
                $queries[] = 'CREATE INDEX IDX_accountId ON usrToGroups (usertogroup_userId)';
                $queries[] = 'ALTER TABLE `accFiles` ADD `accFile_thumb` BLOB NULL;';
                $queries[] = 'CREATE TABLE `authTokens` (`authtoken_id` int(11) NOT NULL AUTO_INCREMENT,`authtoken_userId` int(11) NOT NULL,`authtoken_token` varbinary(100) NOT NULL,`authtoken_actionId` smallint(5) unsigned NOT NULL,`authtoken_createdBy` smallint(5) unsigned NOT NULL,`authtoken_startDate` int(10) unsigned NOT NULL,PRIMARY KEY (`authtoken_id`),UNIQUE KEY `unique_authtoken_id` (`authtoken_id`),KEY `IDX_checkToken` (`authtoken_userId`,`authtoken_actionId`,`authtoken_token`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
                $queries[] = 'CREATE TABLE `customFieldsDef` (`customfielddef_id` int(10) unsigned NOT NULL AUTO_INCREMENT, `customfielddef_module` smallint(5) unsigned NOT NULL, `customfielddef_field` blob NOT NULL, PRIMARY KEY (`customfielddef_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
                $queries[] = 'CREATE TABLE `customFieldsData` (`customfielddata_id` int(10) unsigned NOT NULL AUTO_INCREMENT,`customfielddata_moduleId` smallint(5) unsigned NOT NULL,`customfielddata_itemId` int(10) unsigned NOT NULL,`customfielddata_defId` int(10) unsigned NOT NULL,`customfielddata_data` longblob,`customfielddata_iv` varbinary(128) DEFAULT NULL, PRIMARY KEY (`customfielddata_id`), KEY `IDX_DEFID` (`customfielddata_defId`), KEY `IDX_DELETE` (`customfielddata_itemId`,`customfielddata_moduleId`), KEY `IDX_UPDATE` (`customfielddata_moduleId`,`customfielddata_itemId`,`customfielddata_defId`), KEY `IDX_ITEM` (`customfielddata_itemId`), KEY `IDX_MODULE` (`customfielddata_moduleId`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
                break;
            case 12002:
                $queries[] = 'ALTER TABLE config CHANGE config_value config_value VARCHAR(255);';
                $queries[] = 'ALTER TABLE usrData CHANGE user_pass user_pass VARBINARY(255);';
                $queries[] = 'ALTER TABLE usrData CHANGE user_hashSalt user_hashSalt VARBINARY(128);';
                $queries[] = 'ALTER TABLE accHistory CHANGE acchistory_mPassHash acchistory_mPassHash VARBINARY(255);';
                break;
            case 1316011001:
                $queries[] = 'ALTER TABLE `usrData` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `accFiles` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `accGroups` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `accHistory` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `accUsers` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `categories` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `config` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `customers` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `log` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `usrGroups` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `usrPassRecover` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `usrProfiles` ENGINE = InnoDB';
                $queries[] = 'ALTER TABLE `accounts` ENGINE = InnoDB , DROP INDEX `IDX_searchTxt` , ADD INDEX `IDX_searchTxt` (`account_name` ASC, `account_login` ASC, `account_url` ASC)';
                $queries[] = 'CREATE TABLE publicLinks (publicLink_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,publicLink_itemId INT,publicLink_hash VARBINARY(100) NOT NULL,publicLink_linkData LONGBLOB);';
                $queries[] = 'CREATE UNIQUE INDEX unique_publicLink_accountId ON publicLinks (publicLink_itemId)';
                $queries[] = 'CREATE UNIQUE INDEX unique_publicLink_hash ON publicLinks (publicLink_hash)';
                $queries[] = 'ALTER TABLE log ADD log_level VARCHAR(20) NOT NULL;';
                $queries[] = 'ALTER TABLE config CHANGE config_value config_value VARCHAR(2000);';
                $queries[] = 'CREATE TABLE `accFavorites` (`accfavorite_accountId` SMALLINT UNSIGNED NOT NULL,`accfavorite_userId` SMALLINT UNSIGNED NOT NULL,INDEX `fk_accFavorites_accounts_idx` (`accfavorite_accountId` ASC),INDEX `fk_accFavorites_users_idx` (`accfavorite_userId` ASC),INDEX `search_idx` (`accfavorite_accountId` ASC, `accfavorite_userId` ASC),CONSTRAINT `fk_accFavorites_accounts` FOREIGN KEY (`accfavorite_accountId`) REFERENCES `accounts` (`account_id`)  ON DELETE CASCADE ON UPDATE NO ACTION, CONSTRAINT `fk_accFavorites_users` FOREIGN KEY (`accfavorite_userId`) REFERENCES `usrData` (`user_id`) ON DELETE CASCADE ON UPDATE NO ACTION)ENGINE=InnoDB DEFAULT CHARSET=utf8';
                break;
            case 1316020501:
                $queries[] = 'CREATE TABLE `tags` (`tag_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,`tag_name` VARCHAR(45)  NOT NULL,`tag_hash` BINARY(20) NOT NULL,PRIMARY KEY (`tag_id`),INDEX `IDX_name` (`tag_name` ASC),UNIQUE INDEX `tag_hash_UNIQUE` (`tag_hash` ASC)) ENGINE = InnoDB DEFAULT CHARSET = utf8';
                $queries[] = 'CREATE TABLE `accTags` (`acctag_accountId` INT UNSIGNED NOT NULL,`acctag_tagId`     INT UNSIGNED NOT NULL, INDEX `IDX_id` (`acctag_accountId` ASC, `acctag_tagId` ASC)) ENGINE = InnoDB DEFAULT CHARSET = utf8';
                break;
            default :
                $Log->addDescription(_('No es necesario actualizar la Base de Datos.'));
                return true;
        }

        $Data = new QueryData();

        foreach ($queries as $query) {
            try {
                $Data->setQuery($query);
                DB::getQuery($Data);
            } catch (SPException $e) {
                $Log->setLogLevel(Log::ERROR);
                $Log->addDescription(_('Error al aplicar la actualización de la Base de Datos.') . ' (v' . $version . ')');
                $Log->addDetails('ERROR', sprintf('%s (%s)', $e->getMessage(), $e->getCode()));
                $Log->writeLog();

                Email::sendEmail($Log);
                return false;
            }
        }

        $Log->addDescription(_('Actualización de la Base de Datos realizada correctamente.') . ' (v' . $version . ')');
        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Aplicar actualizaciones auxiliares.
     *
     * @param $version int El número de versión
     * @return bool
     */
    private static function auxUpgrades($version)
    {
        switch ($version) {
            case 12001:
                return (ProfileUtil::migrateProfiles() && UserMigrate::migrateUsersGroup());
                break;
            case 12002:
                return (UserMigrate::setMigrateUsers());
                break;
            default:
                break;
        }
    }

    /**
     * Comprueba si es necesario actualizar la BBDD.
     *
     * @param int $version con el número de versión actual
     * @returns bool
     */
    public static function needDBUpgrade($version)
    {
        $upgrades = array_filter(self::$dbUpgrade, function ($uVersions) use ($version) {
            return ($uVersions > $version);
        });

        return (count($upgrades) > 0);
    }

    /**
     * Comprueba si es necesario actualizar la configuración.
     *
     * @param int $version con el número de versión actual
     * @returns bool
     */
    public static function needConfigUpgrade($version)
    {
        return (in_array($version, self::$cfgUpgrade));
    }

    /**
     * Migrar valores de configuración.
     *
     * @param int $version El número de versión
     * @return bool
     */
    public static function upgradeConfig($version)
    {
        $Config = new ConfigData();

        if (file_exists(CONFIG_FILE)) {
            // Include the file, save the data from $CONFIG
            include CONFIG_FILE;

            if (isset($CONFIG) && is_array($CONFIG)) {
                error_log('upgrade_old');

                foreach (self::getConfigParams() as $mapTo => $oldParam) {
                    $mapFrom = function () use ($oldParam) {
                        if (is_array($oldParam)) {
                            foreach ($oldParam as $param) {
                                if (isset($CONFIG[$param])) {
                                    return $param;
                                }
                            }

                            return '';
                        }

                        return $oldParam;
                    };

                    if (isset($CONFIG[$mapFrom()])
                        && method_exists($Config, $mapTo)
                    ) {
                        $Config->$mapTo($CONFIG[$mapFrom()]);
                    }
                }
            }
        }

        try {
            $Config->setConfigVersion($version);
            Config::saveConfig($Config, false);
            rename(CONFIG_FILE, CONFIG_FILE . '.old');
        } catch (\Exception $e){
            Log::writeNewLog(_('Actualizar Configuración'), _('Error al actualizar la configuración'), Log::ERROR);
            return false;
        }

        Log::writeNewLog(_('Actualizar Configuración'), _('Actualización de la Configuración realizada correctamente.') . ' (v' . $version . ')', Log::NOTICE);
        return true;
    }

    /**
     * Devuelve array de métodos y parámetros de configuración
     *
     * @return array
     */
    private static function getConfigParams()
    {
        return [
            'setAccountCount' => 'account_count',
            'setCheckUpdates' => 'checkupdates',
            'setDbHost' => 'dbhost',
            'setDbName' => 'dbname',
            'setDbPass' => 'dbpass',
            'setDbUser' => 'dbuser',
            'setDebug' => 'debug',
            'setDemoEnabled' => 'demo_enabled',
            'setGlobalSearch' => 'globalsearch',
            'setInstalled' => 'installed',
            'setMaintenance' => 'maintenance',
            'setPasswordSalt' => 'passwordsalt',
            'setSessionTimeout' => 'session_timeout',
            'setSiteLang' => 'sitelang',
            'setConfigVersion' => 'version',
            'setCheckNotices' => 'checknotices',
            'setConfigHash' => 'config_hash',
            'setProxyEnabled' => 'proxy_enabled',
            'setProxyPass' => 'proxy_pass',
            'setProxyPort' => 'proxy_port',
            'setProxyServer' => 'proxy_server',
            'setProxyUser' => 'proxy_user',
            'setResultsAsCards' => 'resultsascards',
            'setSiteTheme' => 'sitetheme',
            'setAccountPassToImage' => 'account_passtoimage',
            'setFilesAllowedExts' => 'allowed_exts',
            'setFilesAllowedSize' => 'allowed_size',
            'setFilesEnabled' => ['filesenabled', 'files_enabled'],
            'setLdapBase' => ['ldapbase', 'ldap_base'],
            'setLdapBindPass' => ['ldapbindpass', 'ldap_bindpass'],
            'setLdapBindUser' => ['ldapbinduser', 'ldap_binduser'],
            'setLdapEnabled' => ['ldapenabled', 'ldap_enabled'],
            'setLdapGroup' => ['ldapgroup', 'ldap_group'],
            'setLdapServer' => ['ldapserver', 'ldap_server'],
            'setLogEnabled' => ['logenabled', 'log_enabled'],
            'setMailEnabled' => ['mailenabled', 'mail_enabled'],
            'setMailFrom' => ['mailfrom', 'mail_from'],
            'setMailPass' => ['mailpass', 'mail_pass'],
            'setMailPort' => ['mailport', 'mail_port'],
            'setMailRequestsEnabled' => ['mailrequestsenabled', 'mail_requestsenabled'],
            'setMailSecurity' => ['mailsecurity', 'mail_security'],
            'setMailServer' => ['mailserver', 'mail_server'],
            'setMailUser' => ['mailuser', 'mail_user'],
            'setWikiEnabled' => ['wikienabled', 'wiki_enabled'],
            'setWikiFilter' => ['wikifilter', 'wiki_filter'],
            'setWikiPageUrl' => ['wikipageurl' . 'wiki_pageurl'],
            'setWikiSearchUrl' => ['wikisearchurl', 'wiki_searchurl']
        ];
    }
}