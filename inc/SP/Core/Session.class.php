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

use SP\Account;
use SP\Account\AccountSearch;
use SP\Config\ConfigData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserPreferencesData;
use SP\Mgmt;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Users\UserPreferences;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

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
     * Obtiene el id de usuario de la sesión.
     *
     * @return int
     */
    public static function getUserId()
    {
        return self::getSessionKey('uid', 0);
    }

    /**
     * Devolver una variable de sesión
     *
     * @param mixed $key
     * @param mixed $default
     * @return bool|int
     */
    public static function getSessionKey($key, $default = '')
    {
        if (isset($_SESSION[$key])) {
            if (is_numeric($default)) {
                return (int)$_SESSION[$key];
            }
            return $_SESSION[$key];
        }

        return $default;
    }

    /**
     * Establece el id de usuario en la sesión.
     *
     * @param $userId
     */
    public static function setUserId($userId)
    {
        self::setSessionKey('uid', $userId);
    }

    /**
     * Establecer una variable de sesión
     *
     * @param mixed $key   El nombre de la variable
     * @param mixed $value El valor de la variable
     */
    public static function setSessionKey($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Obtiene si el usuario es administrador de la aplicación de la sesión.
     *
     * @return bool
     */
    public static function getUserIsAdminApp()
    {
        return self::getSessionKey('uisadminapp', false);
    }

    /**
     * Establece si el usuario es administrador de la aplicación en la sesión.
     *
     * @param $bool
     */
    public static function setUserIsAdminApp($bool)
    {
        self::setSessionKey('uisadminapp', $bool);
    }

    /**
     * Obtiene si el usuario es administrador de cuentas de la sesión.
     *
     * @return bool
     */
    public static function getUserIsAdminAcc()
    {
        return self::getSessionKey('uisadminacc', false);
    }

    /**
     * Obtiene si el usuario es administrador de cuentas en la sesión.
     *
     * @param $bool
     */
    public static function setUserIsAdminAcc($bool)
    {
        self::setSessionKey('uisadminacc', $bool);
    }

    /**
     * Obtiene el id de perfil de usuario de la sesión.
     *
     * @return int
     */
    public static function getUserProfileId()
    {
        return self::getSessionKey('uprofile', 0);
    }

    /**
     * Establece el id de perfil de usuario en la sesión.
     *
     * @param int $profileId
     */
    public static function setUserProfileId($profileId)
    {
        self::setSessionKey('uprofile', $profileId);
    }

    /**
     * Obtiene el login de usuario de la sesión.
     *
     * @return mixed
     */
    public static function getUserLogin()
    {
        return self::getSessionKey('ulogin', false);
    }

    /**
     * Establece el login de usuario en la sesión.
     *
     * @param $userLogin
     */
    public static function setUserLogin($userLogin)
    {
        self::setSessionKey('ulogin', $userLogin);
    }

    /**
     * Obtiene el nombre de usuario de la sesión.
     *
     * @return string
     */
    public static function getUserName()
    {
        return self::getSessionKey('uname');
    }

    /**
     * Establece el nombre de usuario en la sesión.
     *
     * @param $userName
     */
    public static function setUserName($userName)
    {
        self::setSessionKey('uname', $userName);
    }

    /**
     * Obtiene el id de grupo de usuario de la sesión.
     *
     * @return int
     */
    public static function getUserGroupId()
    {
        return self::getSessionKey('ugroup', 0);
    }

    /**
     * Obtiene el id de grupo de usuario de la sesión.
     *
     * @param $groupId
     */
    public static function setUserGroupId($groupId)
    {
        self::setSessionKey('ugroup', $groupId);
    }

    /**
     * Obtiene el nombre del grupo de usuario de la sesión.
     *
     * @return string
     */
    public static function getUserGroupName()
    {
        return self::getSessionKey('ugroupn');
    }

    /**
     * Establece el nombre del grupo de usuario en la sesión.
     *
     * @param string $groupName
     */
    public static function setUserGroupName($groupName)
    {
        self::setSessionKey('ugroupn', $groupName);
    }

    /**
     * Obtiene el email de usuario de la sesión.
     *
     * @return string
     */
    public static function getUserEMail()
    {
        return self::getSessionKey('uemail');
    }

    /**
     * Establece el nombre del grupo de usuario en la sesión.
     *
     * @param $userEmail
     */
    public static function setUserEMail($userEmail)
    {
        self::setSessionKey('uemail', $userEmail);
    }

    /**
     * Obtiene si es un usuario de LDAP de la sesión.
     *
     * @return bool
     */
    public static function getUserIsLdap()
    {
        return self::getSessionKey('uisldap', false);
    }

    /**
     * Establece si es un usuario de LDAP en la sesión.
     *
     * @param $bool
     */
    public static function setUserIsLdap($bool)
    {
        self::setSessionKey('uisldap', $bool);
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
     * Establece la cuenta primaria para el histórico
     *
     * @param $id int El id de la cuenta
     */
    public static function setAccountParentId($id)
    {
        self::setSessionKey('accParentId', $id);
    }

    /**
     * Devuelve la cuenta primaria para el histórico
     *
     * @return int
     */
    public static function getAccountParentId()
    {
        return self::getSessionKey('accParentId', null);
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
     * Devuelve la clave maestra encriptada
     *
     * @return string
     */
    public static function getMPass()
    {
        return self::getSessionKey('mPass');
    }

    /**
     * Establecer la clave maestra encriptada
     *
     * @param $mpass string La clave maestra
     */
    public static function setMPass($mpass)
    {
        self::setSessionKey('mPass', $mpass);
    }

    /**
     * Devuelve la clave usada para encriptar la clave maestra
     *
     * @return string
     */
    public static function getMPassPwd()
    {
        return self::getSessionKey('mPassPwd');
    }

    /**
     * Establece la clave usada para encriptar la clave maestra
     *
     * @param $mPassPwd string La clave usada
     */
    public static function setMPassPwd($mPassPwd)
    {
        self::setSessionKey('mPassPwd', $mPassPwd);
    }

    /**
     * Devuelve el vector de inicialización de la clave maestra
     *
     * @return string
     */
    public static function getMPassIV()
    {
        return self::getSessionKey('mPassIV');
    }

    /**
     * Establece el vector de inicialización de la clave maestra
     *
     * @param $mPassIV string El vector de inicialización
     */
    public static function setMPassIV($mPassIV)
    {
        self::setSessionKey('mPassIV', $mPassIV);
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
     * Devuelve si el usuario ha pasado la autentificación en 2 pasos
     *
     * @return bool
     */
    public static function get2FApassed()
    {
        return self::getSessionKey('2fapass', false);
    }

    /**
     * Establece esi el usuario ha pasado la autentificación en 2 pasos
     *
     * @param $passed bool
     */
    public static function set2FApassed($passed)
    {
        self::setSessionKey('2fapass', $passed);
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
     * @param $key
     */
    public static function unsetSessionKey($key)
    {
        unset($_SESSION[$key]);
    }
}