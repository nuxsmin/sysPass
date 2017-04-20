<?php

/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Core\Upgrade;

use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Config\ConfigDB;
use SP\Controller\MainActionController;
use SP\Core\Exceptions\SPException;
use SP\Core\Init;
use SP\Core\Session as CoreSession;
use SP\Core\TaskFactory;
use SP\Http\Request;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\CustomFields\CustomFieldsUtil;
use SP\Mgmt\Profiles\ProfileUtil;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserMigrate;
use SP\Mgmt\Users\UserPreferencesUtil;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Util;
use SP\Core\Upgrade\User as UserUpgrade;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de realizar las operaciones actualización de la aplicación.
 */
class Upgrade
{
    /**
     * @var array Versiones actualizables
     */
    private static $dbUpgrade = ['110', '112.1', '112.2', '112.3', '112.13', '112.19', '112.20', '120.01', '120.02', '130.16011001', '130.16100601', '200.17011302', '200.17011701', '210.17022601', '213.17031402'];
    private static $cfgUpgrade = ['112.4', '130.16020501', '200.17011202'];
    private static $auxUpgrade = ['120.01', '120.02', '200.17010901', '200.17011202'];
    private static $appUpgrade = ['210.17022601'];
    /**
     * @var string Versión de la BBDD
     */
    private static $currentDbVersion;

    /**
     * Inicia el proceso de actualización de la BBDD.
     *
     * @param int $version con la versión de la BBDD actual
     * @return bool
     * @throws SPException
     */
    public static function doUpgrade($version)
    {
        self::$currentDbVersion = self::normalizeVersion(ConfigDB::getValue('version'));

        foreach (self::$dbUpgrade as $dbVersion) {
            if (self::checkVersion($version, $dbVersion)) {
                if (self::auxPreDbUpgrade($dbVersion) === false) {
                    throw new SPException(SPException::SP_CRITICAL,
                        __('Error al aplicar la actualización auxiliar', false),
                        __('Compruebe el registro de eventos para más detalles', false));
                }

                if (self::upgradeDB($dbVersion) === false) {
                    throw new SPException(SPException::SP_CRITICAL,
                        __('Error al aplicar la actualización de la Base de Datos', false),
                        __('Compruebe el registro de eventos para más detalles', false));
                }
            }
        }

        foreach (self::$appUpgrade as $appVersion) {
            if (self::checkVersion($version, $appVersion)
                && self::appUpgrades($appVersion) === false
            ) {
                throw new SPException(SPException::SP_CRITICAL,
                    __('Error al aplicar la actualización de la aplicación', false),
                    __('Compruebe el registro de eventos para más detalles', false));
            }
        }

        foreach (self::$auxUpgrade as $auxVersion) {
            if (self::checkVersion($version, $auxVersion)
                && self::auxUpgrades($auxVersion) === false
            ) {
                throw new SPException(SPException::SP_CRITICAL,
                    __('Error al aplicar la actualización auxiliar', false),
                    __('Compruebe el registro de eventos para más detalles', false));
            }
        }

        return true;
    }

    /**
     * Normalizar un número de versión
     *
     * @param $version
     * @return string
     */
    private static function normalizeVersion($version)
    {
        if (strpos($version, '.') === false) {
            if (strlen($version) === 10) {
                return substr($version, 0, 2) . '0.' . substr($version, 2);
            }

            return substr($version, 0, 3) . '.' . substr($version, 3);
        }

        return $version;
    }

    /**
     * Comprobar si una versión necesita actualización
     *
     * @param string       $version
     * @param array|string $upgradeable
     * @return bool True si la versión es menor.
     */
    public static function checkVersion($version, $upgradeable)
    {
        if (is_array($upgradeable)) {
            $upgradeable = $upgradeable[count($upgradeable) - 1];
        }

        if (PHP_INT_SIZE > 4) {
            return version_compare($version, $upgradeable) === -1;
        }

        list($version, $build) = explode('.', $version, 2);
        list($upgradeVersion, $upgradeBuild) = explode('.', $upgradeable, 2);

        $versionRes = (int)$version <= (int)$upgradeVersion;

        if (($versionRes && (int)$upgradeBuild === 0)
            || ($versionRes && (int)$build < (int)$upgradeBuild)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Aplicar actualizaciones auxiliares antes de actualizar la BBDD
     *
     * @param $version
     * @return bool
     */
    private static function auxPreDbUpgrade($version)
    {
        switch ($version) {
            case '130.16011001':
                debugLog(__FUNCTION__ . ': ' . $version);

                return self::upgradeDB('130.00000000');
            case '130.16100601':
                debugLog(__FUNCTION__ . ': ' . $version);

                return
                    Account::fixAccountsId()
                    && UserUpgrade::fixUsersId(Request::analyze('userid', 0))
                    && Group::fixGroupId(Request::analyze('groupid', 0))
                    && Profile::fixProfilesId(Request::analyze('profileid', 0))
                    && Category::fixCategoriesId(Request::analyze('categoryid', 0))
                    && Customer::fixCustomerId(Request::analyze('customerid', 0));
        }

        return true;
    }

    /**
     * Actualiza la BBDD según la versión.
     *
     * @param int $version con la versión a actualizar
     * @returns bool
     */
    private static function upgradeDB($version)
    {
        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Actualizar BBDD', false));
        $LogMessage->addDetails(__('Versión', false), $version);

        $queries = self::getQueriesFromFile($version);

        if (count($queries) === 0 || self::checkVersion(self::$currentDbVersion, $version) === false) {
            $LogMessage->addDescription(__('No es necesario actualizar la Base de Datos.', false));

            debugLog($LogMessage->composeText());

            return true;
        }

        TaskFactory::$Message->setTask(__('Actualizar BBDD'));
        TaskFactory::$Message->setMessage(sprintf('%s : %s', __('Versión'), $version));
        TaskFactory::sendTaskMessage();

        debugLog(__FUNCTION__ . ': ' . $version);

        $Data = new QueryData();

        foreach ($queries as $query) {
            try {
                $Data->setQuery($query);
                DB::getQuery($Data);
            } catch (SPException $e) {
                $LogMessage->addDescription(__('Error al aplicar la actualización de la Base de Datos', false));
                $LogMessage->addDetails('ERROR', sprintf('%s (%s)', $e->getMessage(), $e->getCode()));
                $Log->setLogLevel(Log::ERROR);
                $Log->writeLog();

                Email::sendEmail($LogMessage);
                return false;
            }
        }

        ConfigDB::setValue('version', $version);

        self::$currentDbVersion = $version;

        $LogMessage->addDescription(__('Actualización de la Base de Datos realizada correctamente.', false));
        $Log->writeLog();

        Email::sendEmail($LogMessage);

        return true;
    }

    /**
     * Obtener las consultas de actualización desde un archivo
     *
     * @param $filename
     * @return array|bool
     */
    private static function getQueriesFromFile($filename)
    {
        $file = SQL_PATH . DIRECTORY_SEPARATOR . str_replace('.', '', $filename) . '.sql';

        $queries = [];

        if (file_exists($file) && $handle = fopen($file, 'rb')) {
            while (!feof($handle)) {
                $buffer = stream_get_line($handle, 1000000, ";\n");

                if (strlen(trim($buffer)) > 0) {
                    $queries[] = str_replace("\n", '', $buffer);
                }
            }
        }

        return $queries;
    }

    /**
     * Actualizaciones de la aplicación
     *
     * @param $version
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    private static function appUpgrades($version)
    {
        switch ($version) {
            case '210.17022601':
                $dbResult = true;

                if (self::checkVersion(self::$currentDbVersion, $version)) {
                    $dbResult = self::upgradeDB($version);
                }

                $masterPass = Request::analyzeEncrypted('masterkey');
                $UserData = User::getItem()->getByLogin(Request::analyze('userlogin'));

                if (!is_object($UserData)) {
                    throw new SPException(SPException::SP_ERROR, __('Error al obtener los datos del usuario', false));
                }

                CoreSession::setUserData($UserData);

                return $dbResult === true
                    && !empty($masterPass)
                    && Crypt::migrate($masterPass);
        }

        return false;
    }

    /**
     * Aplicar actualizaciones auxiliares.
     *
     * @param $version int El número de versión
     * @return bool
     */
    private static function auxUpgrades($version)
    {
        try {
            switch ($version) {
                case '120.01':
                    debugLog(__FUNCTION__ . ': ' . $version);

                    return (ProfileUtil::migrateProfiles() && UserMigrate::migrateUsersGroup());
                case '120.02':
                    debugLog(__FUNCTION__ . ': ' . $version);

                    return UserMigrate::setMigrateUsers();
                case '200.17010901':
                    debugLog(__FUNCTION__ . ': ' . $version);

                    return CustomFieldsUtil::migrateCustomFields() && UserPreferencesUtil::migrate();
                case '200.17011202':
                    debugLog(__FUNCTION__ . ': ' . $version);

                    return UserPreferencesUtil::migrate();
            }
        } catch (SPException $e) {
            return false;
        }

        return true;
    }

    /**
     * Comprueba si es necesario actualizar la configuración.
     *
     * @param int $version con el número de versión actual
     * @returns bool
     */
    public static function needConfigUpgrade($version)
    {
        return self::checkVersion($version, self::$cfgUpgrade);
    }

    /**
     * Migrar valores de configuración.
     *
     * @param int $version El número de versión
     * @return bool
     */
    public static function upgradeConfig($version)
    {
        $count = 0;
        $Config = Config::getConfig();

        foreach (self::$cfgUpgrade as $upgradeVersion) {
            if (self::checkVersion($version, $upgradeVersion)) {
                switch ($upgradeVersion) {
                    case '200.17011202':
                        debugLog(__FUNCTION__ . ': ' . $version);

                        $Config->setSiteTheme('material-blue');
                        $Config->setConfigVersion($upgradeVersion);
                        Config::saveConfig($Config, false);
                        $count++;
                        break;
                }
            }
        }

        return $count > 0;
    }

    /**
     * Actualizar el archivo de configuración a formato XML
     *
     * @param $version
     * @return bool
     */
    public static function upgradeOldConfigFile($version)
    {
        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Actualizar Configuración', false));

        $Config = new ConfigData();

        // Include the file, save the data from $CONFIG
        include CONFIG_FILE;

        if (isset($CONFIG) && is_array($CONFIG)) {
            foreach (self::getConfigParams() as $mapTo => $mapFrom) {
                if (method_exists($Config, $mapTo)) {
                    if (is_array($mapFrom)) {
                        foreach ($mapFrom as $param) {
                            if (isset($CONFIG[$param])) {
                                $LogMessage->addDetails(__('Parámetro', false), $param);
                                $Config->$mapTo($CONFIG[$param]);
                            }
                        }
                    } else {
                        if (isset($CONFIG[$mapFrom])) {
                            $LogMessage->addDetails(__('Parámetro', false), $mapFrom);
                            $Config->$mapTo($CONFIG[$mapFrom]);
                        }
                    }
                }
            }
        }

        try {
            $Config->setSiteTheme('material-blue');
            $Config->setConfigVersion($version);
            Config::saveConfig($Config, false);
            rename(CONFIG_FILE, CONFIG_FILE . '.old');

            $LogMessage->addDetails(__('Versión', false), $version);
            $Log->setLogLevel(Log::NOTICE);
            $Log->writeLog();

            return true;
        } catch (\Exception $e) {
            $LogMessage->addDescription(__('Error al actualizar la configuración', false));
            $LogMessage->addDetails(__('Archivo', false), CONFIG_FILE . '.old');
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();
        }

        // We are here...wrong
        return false;
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
            'setAccountLink' => 'account_link',
            'setCheckUpdates' => 'checkupdates',
            'setCheckNotices' => 'checknotices',
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
            'setConfigHash' => 'config_hash',
            'setProxyEnabled' => 'proxy_enabled',
            'setProxyPass' => 'proxy_pass',
            'setProxyPort' => 'proxy_port',
            'setProxyServer' => 'proxy_server',
            'setProxyUser' => 'proxy_user',
            'setResultsAsCards' => 'resultsascards',
            'setSiteTheme' => 'sitetheme',
            'setAccountPassToImage' => 'account_passtoimage',
            'setFilesAllowedExts' => ['allowed_exts', 'files_allowed_exts'],
            'setFilesAllowedSize' => ['allowed_size', 'files_allowed_size'],
            'setFilesEnabled' => ['filesenabled', 'files_enabled'],
            'setLdapBase' => ['ldapbase', 'ldap_base'],
            'setLdapBindPass' => ['ldapbindpass', 'ldap_bindpass'],
            'setLdapBindUser' => ['ldapbinduser', 'ldap_binduser'],
            'setLdapEnabled' => ['ldapenabled', 'ldap_enabled'],
            'setLdapGroup' => ['ldapgroup', 'ldap_group'],
            'setLdapServer' => ['ldapserver', 'ldap_server'],
            'setLdapAds' => 'ldap_ads',
            'setLdapDefaultGroup' => 'ldap_defaultgroup',
            'setLdapDefaultProfile' => 'ldap_defaultprofile',
            'setLogEnabled' => ['logenabled', 'log_enabled'],
            'setMailEnabled' => ['mailenabled', 'mail_enabled'],
            'setMailFrom' => ['mailfrom', 'mail_from'],
            'setMailPass' => ['mailpass', 'mail_pass'],
            'setMailPort' => ['mailport', 'mail_port'],
            'setMailRequestsEnabled' => ['mailrequestsenabled', 'mail_requestsenabled'],
            'setMailAuthenabled' => 'mail_authenabled',
            'setMailSecurity' => ['mailsecurity', 'mail_security'],
            'setMailServer' => ['mailserver', 'mail_server'],
            'setMailUser' => ['mailuser', 'mail_user'],
            'setWikiEnabled' => ['wikienabled', 'wiki_enabled'],
            'setWikiFilter' => ['wikifilter', 'wiki_filter'],
            'setWikiPageUrl' => ['wikipageurl' . 'wiki_pageurl'],
            'setWikiSearchUrl' => ['wikisearchurl', 'wiki_searchurl']
        ];
    }

    /**
     * Comrpueba y actualiza la versión de la BBDD.
     *
     * @return int|false
     */
    public static function checkDbVersion()
    {
        $appVersion = implode('.', Util::getVersion(true, true));
        $databaseVersion = self::normalizeVersion(ConfigDB::getValue('version'));

        if (self::checkVersion($databaseVersion, $appVersion)
            && Request::analyze('nodbupgrade', 0) === 0
            && self::checkVersion($databaseVersion, self::$dbUpgrade)
        ) {
            if (!Init::checkMaintenanceMode(true)) {
                self::setUpgradeKey('db');
            } else {
                $Controller = new MainActionController();
                $Controller->doAction($databaseVersion);
            }

            return true;
        }

        return false;
    }

    /**
     * Establecer la key de actualización
     *
     * @param string $type Tipo de actualización
     */
    private static function setUpgradeKey($type)
    {
        $upgradeKey = Config::getConfig()->getUpgradeKey();

        if (empty($upgradeKey)) {
            Config::getConfig()->setUpgradeKey(Util::generateRandomBytes(32));
        }

        Config::getConfig()->setMaintenance(true);
        Config::saveConfig(null, false);

        Init::initError(__('La aplicación necesita actualizarse'), sprintf(__('Si es un administrador pulse en el enlace: %s'), '<a href="index.php?a=upgrade&type=' . $type . '">' . __('Actualizar') . '</a>'));
    }

    /**
     * Comrpueba y actualiza la versión de la aplicación.
     *
     * @return int|false
     */
    public static function checkAppVersion()
    {
        $appVersion = self::normalizeVersion(Config::getConfig()->getConfigVersion());

        if (self::checkVersion($appVersion, self::$appUpgrade)) {
            if (!Init::checkMaintenanceMode(true)) {
                self::setUpgradeKey('app');
            } else {
                $Controller = new MainActionController();
                $Controller->doAction($appVersion);
            }

            return true;
        }

        return false;
    }
}