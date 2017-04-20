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

namespace SP\Core;

use SP\Account\AccountAcl;
use SP\Account\AccountSearch;
use SP\Config\ConfigData;
use SP\Core\Crypt\Vault;
use SP\DataModel\ProfileData;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Mgmt\Users\UserPreferences;

defined('APP_ROOT') || die();

/**
 * Clase para manejar la variable de sesion
 */
class Session
{
    /**
     * Tipos de sesión
     */
    const SESSION_INTERACTIVE = 1;
    const SESSION_API = 2;

    /**
     * Establece los datos del usuario en la sesión.
     *
     * @param UserData $UserData
     */
    public static function setUserData(UserData $UserData = null)
    {
        self::setSessionKey('userData', $UserData);
    }

    /**
     * Establecer una variable de sesión
     *
     * @param string $key   El nombre de la variable
     * @param mixed  $value El valor de la variable
     */
    public static function setSessionKey($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Establecer una variable de sesión para un plugin
     *
     * @param string $plugin Nombre del plugin
     * @param string $key    El nombre de la variable
     * @param mixed  $value  El valor de la variable
     */
    public static function setPluginKey($plugin, $key, $value)
    {
        $_SESSION[$plugin][$key] = $value;
    }

    /**
     * Devuelve los datos del usuario en la sesión.
     *
     * @return UserData
     */
    public static function getUserData()
    {
        return self::getSessionKey('userData', new UserData());
    }

    /**
     * Devolver una variable de sesión
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function getSessionKey($key, $default = '')
    {
        if (isset($_SESSION[$key])) {
            return is_numeric($default) ? (int)$_SESSION[$key] : $_SESSION[$key];
        }

        return $default;
    }

    /**
     * Devolver una variable de sesión
     *
     * @param string $plugin
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function getPluginKey($plugin, $key, $default = '')
    {
        if (isset($_SESSION[$plugin][$key])) {
            return is_numeric($default) ? (int)$_SESSION[$plugin][$key] : $_SESSION[$plugin][$key];
        }

        return $default;
    }

    /**
     * Obtiene el objeto de perfil de usuario de la sesión.
     *
     * @return ProfileData
     */
    public static function getUserProfile()
    {
        return self::getSessionKey('usrprofile');
    }

    /**
     * Establece el objeto de perfil de usuario en la sesión.
     *
     * @param ProfileData $ProfileData
     */
    public static function setUserProfile(ProfileData $ProfileData)
    {
        self::setSessionKey('usrprofile', $ProfileData);
    }

    /**
     * @return AccountSearch
     */
    public static function getSearchFilters()
    {
        return self::getSessionKey('searchFilters', new AccountSearch());
    }

    /**
     * @param AccountSearch $searchFilters
     */
    public static function setSearchFilters(AccountSearch $searchFilters)
    {
        self::setSessionKey('searchFilters', $searchFilters);
    }

    /**
     * Establece si se ha comprobado si hay actualizaciones
     *
     * @param bool $bool
     */
    public static function setUpdated($bool = true)
    {
        self::setSessionKey('updated', $bool);
    }

    /**
     * Devuelve si se ha combrobado si hay actualizaciones
     *
     * @return bool
     */
    public static function getUpdated()
    {
        return self::getSessionKey('updated', false);
    }

    /**
     * Devuelve el timeout de la sesión
     *
     * @return int|null El valor en segundos
     */
    public static function getSessionTimeout()
    {
        return self::getSessionKey('sessionTimeout', null);
    }

    /**
     * Establecer el timeout de la sesión
     *
     * @param int $timeout El valor en segundos
     */
    public static function setSessionTimeout($timeout)
    {
        self::setSessionKey('sessionTimeout', $timeout);
    }

    /**
     * Devuelve si es necesario recargar la aplicación
     *
     * @return bool
     */
    public static function getReload()
    {
        return self::getSessionKey('reload', false);
    }

    /**
     * Establecer si es necesario recargar la aplicación
     *
     * @param bool $bool
     */
    public static function setReload($bool)
    {
        self::setSessionKey('reload', $bool);
    }

    /**
     * Devuelve la clave de seguridad para los formularios
     *
     * @return string|null
     */
    public static function getSecurityKey()
    {
        return self::getSessionKey('sk', null);
    }

    /**
     * Establece la clave de seguridad para los formularios
     *
     * @param string $sk La clave de seguridad
     */
    public static function setSecurityKey($sk)
    {
        self::setSessionKey('sk', $sk);
    }

    /**
     * Devuelve la hora en la que el SID de sesión fue creado
     *
     * @return int
     */
    public static function getSidStartTime()
    {
        return self::getSessionKey('sidStartTime', 0);
    }

    /**
     * Establece la hora de creación del SID
     *
     * @param $time int La marca de hora
     */
    public static function setSidStartTime($time)
    {
        self::setSessionKey('sidStartTime', $time);
    }

    /**
     * Devuelve la hora de inicio de actividad.
     *
     * @return int
     */
    public static function getStartActivity()
    {
        return self::getSessionKey('startActivity', 0);
    }

    /**
     * Establece la hora de inicio de actividad
     *
     * @param $time int La marca de hora
     */
    public static function setStartActivity($time)
    {
        self::setSessionKey('startActivity', $time);
    }

    /**
     * Devuelve la hora de la última actividad
     *
     * @return int
     */
    public static function getLastActivity()
    {
        return self::getSessionKey('lastActivity', 0);
    }

    /**
     * Establece la hora de la última actividad
     *
     * @param $time int La marca de hora
     */
    public static function setLastActivity($time)
    {
        self::setSessionKey('lastActivity', $time);
    }

    /**
     * Devuelve el id de la última cuenta vista
     *
     * @return int
     */
    public static function getLastAcountId()
    {
        return self::getSessionKey('lastAccountId', 0);
    }

    /**
     * Establece el id de la última cuenta vista
     *
     * @param $id int La marca de hora
     */
    public static function setLastAcountId($id)
    {
        self::setSessionKey('lastAccountId', $id);
    }

    /**
     * Devuelve el tema visual utilizado en sysPass
     *
     * @return string
     */
    public static function getTheme()
    {
        return self::getSessionKey('theme');
    }

    /**
     * Establece el tema visual utilizado en sysPass
     *
     * @param $theme string El tema visual a utilizar
     */
    public static function setTheme($theme)
    {
        self::setSessionKey('theme', $theme);
    }

    /**
     * Devolver la clave pública
     *
     * @return mixed
     */
    public static function getPublicKey()
    {
        return self::getSessionKey('pubkey');
    }

    /**
     * Establecer la clave pública
     *
     * @param $key
     */
    public static function setPublicKey($key)
    {
        self::setSessionKey('pubkey', $key);
    }

    /**
     * Establecer el lenguaje de la sesión
     *
     * @param $locale
     */
    public static function setLocale($locale)
    {
        self::setSessionKey('locale', $locale);
    }

    /**
     * Devuelve el lenguaje de la sesión
     *
     * @return string
     */
    public static function getLocale()
    {
        return self::getSessionKey('locale');
    }

    /**
     * Obtiene el objeto de preferencias de usuario de la sesión.
     *
     * @return UserPreferencesData
     */
    public static function getUserPreferences()
    {
        return self::getSessionKey('userpreferences');
    }

    /**
     * Establece el objeto de preferencias de usuario en la sesión.
     *
     * @param UserPreferencesData|UserPreferences $preferences
     */
    public static function setUserPreferences(UserPreferencesData $preferences)
    {
        self::setSessionKey('userpreferences', $preferences);
    }

    /**
     * Devolver la clave maestra temporal
     *
     * @return string
     */
    public static function getTemporaryMasterPass()
    {
        return self::getSessionKey('tempmasterpass');
    }

    /**
     * Establece la clave maestra temporal
     *
     * @param string $password
     */
    public static function setTemporaryMasterPass($password)
    {
        self::setSessionKey('tempmasterpass', $password);
    }

    /**
     * Devolver el color asociado a una cuenta
     *
     * @return string
     */
    public static function getAccountColor()
    {
        return self::getSessionKey('accountcolor');
    }

    /**
     * Establece el color asociado a una cuenta
     *
     * @param array $color
     */
    public static function setAccountColor(array $color)
    {
        self::setSessionKey('accountcolor', $color);
    }

    /**
     * Devolver si hay una cookie de sesión para CURL
     *
     * @return string
     */
    public static function getCurlCookieSession()
    {
        return self::getSessionKey('curlcookiesession', false);
    }

    /**
     * Establecer si hay una cookie de sesión para CURL
     *
     * @param bool $session
     */
    public static function setCurlCookieSession($session)
    {
        self::setSessionKey('curlcookiesession', $session);
    }

    /**
     * Devolver si hay una sesión a la API de DokuWiki
     *
     * @return string
     */
    public static function getDokuWikiSession()
    {
        return self::getSessionKey('dokuwikisession', false);
    }

    /**
     * Establecer si hay una sesión a la API de DokuWiki
     *
     * @param bool $session
     */
    public static function setDokuWikiSession($session)
    {
        self::setSessionKey('dokuwikisession', $session);
    }

    /**
     * Devolver el tipo de sesion
     *
     * @return int
     */
    public static function getSessionType()
    {
        return self::getSessionKey('sessiontype', 0);
    }

    /**
     * Establecer el tipo de sesion
     *
     * @param int $type
     */
    public static function setSessionType($type)
    {
        self::setSessionKey('sessiontype', $type);
    }

    /**
     * Devolver la configuración
     *
     * @return ConfigData
     */
    public static function getConfig()
    {
        return self::getSessionKey('config');
    }

    /**
     * Establecer la configuración
     *
     * @param ConfigData $config
     */
    public static function setConfig(ConfigData $config)
    {
        self::setSessionKey('config', $config);
    }

    /**
     * Establecer la hora de carga de la configuración
     *
     * @param $time
     */
    public static function setConfigTime($time)
    {
        self::setSessionKey('configTime', $time);
    }

    /**
     * Devolver la hora de carga de la configuración
     *
     * @return int
     */
    public static function getConfigTime()
    {
        return self::getSessionKey('configTime');
    }

    /**
     * Establecer los plugins cargados
     *
     * @param array $plugins
     */
    public static function setPluginsLoaded(array $plugins)
    {
        self::setSessionKey('plugins_loaded', $plugins);
    }

    /**
     * Devolver los plugins cargados
     */
    public static function getPluginsLoaded()
    {
        return self::getSessionKey('plugins_loaded', []);
    }

    /**
     * Establecer los plugins deshabilitados
     *
     * @param array $plugins
     */
    public static function setPluginsDisabled(array $plugins)
    {
        self::setSessionKey('plugins_disabled', $plugins);
    }

    /**
     * Devolver los plugins deshabilitados
     */
    public static function getPluginsDisabled()
    {
        return self::getSessionKey('plugins_disabled', []);
    }

    /**
     * @param $key
     */
    public static function unsetSessionKey($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Establecer si el usuario está completamente autorizado
     *
     * @param $bool
     */
    public static function setAuthCompleted($bool)
    {
        self::setSessionKey('authCompleted', (bool)$bool);
    }

    /**
     * Devolver si el usuario está completamente logeado
     */
    public static function getAuthCompleted()
    {
        return self::getSessionKey('authCompleted', false);
    }

    /**
     * Establecer la ACL de una cuenta
     *
     * @param AccountAcl $AccountAcl
     */
    public static function setAccountAcl(AccountAcl $AccountAcl)
    {
        $_SESSION['accountAcl'][$AccountAcl->getAccountId()] = $AccountAcl;
    }

    /**
     * Devolver la ACL de una cuenta
     *
     * @param $accountId
     *
     * @return null|AccountAcl
     */
    public static function getAccountAcl($accountId)
    {
        if (isset($_SESSION['accountAcl'][$accountId])) {
            return $_SESSION['accountAcl'][$accountId];
        }

        return null;
    }

    /**
     * Establece si se ha actulizado la aplicación
     *
     * @param bool $bool
     */
    public static function setAppUpdated($bool = true)
    {
        self::setSessionKey('appupdated', $bool);
    }

    /**
     * Devuelve si se ha actulizado la aplicación
     *
     * @return bool
     */
    public static function getAppUpdated()
    {
        return self::getSessionKey('appupdated', false);
    }

    /**
     * Devuelve la clave maestra encriptada
     *
     * @return Vault
     */
    public static function getVault()
    {
        return self::getSessionKey('vault');
    }

    /**
     * Establecer la clave maestra encriptada
     *
     * @param Vault $vault
     */
    public static function setVault(Vault $vault)
    {
        self::setSessionKey('vault', $vault);
    }

    /**
     * Devuelve si es necesario comprobar la versión de la aplicación
     * para actualizar
     *
     * @return bool
     */
    public static function getUpgradeChecked()
    {
        return self::getSessionKey('upgradechecked', true);
    }

    /**
     * Establecer si es necesario comprobar la versión de la aplicación
     * para actualizar
     *
     * @param bool $upgradechecked
     */
    public static function setUpgradeChecked($upgradechecked = false)
    {
        self::setSessionKey('upgradechecked', $upgradechecked);
    }


}