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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase para manejar la variable de sesion
 */
class Session
{
    /**
     * Obtiene el id de usuario de la sesión.
     *
     * @return int
     */
    public static function getUserId()
    {
        return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
    }

    /**
     * Establece el id de usuario en la sesión.
     *
     * @param $userId
     */
    public static function setUserId($userId)
    {
        $_SESSION['uid'] = (int)$userId;
    }

    /**
     * Obtiene si el usuario es administrador de la aplicación de la sesión.
     *
     * @return bool
     */
    public static function getUserIsAdminApp()
    {
        return (bool)$_SESSION["uisadminapp"];
    }

    /**
     * Establece si el usuario es administrador de la aplicación en la sesión.
     *
     * @param $bool
     */
    public static function setUserIsAdminApp($bool)
    {
        $_SESSION["uisadminapp"] = (bool)$bool;
    }

    /**
     * Obtiene si el usuario es administrador de cuentas de la sesión.
     *
     * @return bool
     */
    public static function getUserIsAdminAcc()
    {
        return (bool)$_SESSION["uisadminacc"];
    }

    /**
     * Obtiene si el usuario es administrador de cuentas en la sesión.
     *
     * @param $bool
     */
    public static function setUserIsAdminAcc($bool)
    {
        $_SESSION["uisadminacc"] = (bool)$bool;
    }

    /**
     * Obtiene el id de perfil de usuario de la sesión.
     *
     * @return int
     */
    public static function getUserProfileId()
    {
        return (int)$_SESSION["uprofile"];
    }

    /**
     * Establece el id de perfil de usuario en la sesión.
     *
     * @param int $profileId
     */
    public static function setUserProfileId($profileId)
    {
        $_SESSION["uprofile"] = (int)$profileId;
    }

    /**
     * Obtiene el login de usuario de la sesión.
     *
     * @param bool $default valor devuelto en caso de no estar establecida la variable de sesión
     * @return string
     */
    public static function getUserLogin($default = false)
    {
        return isset($_SESSION['ulogin']) ? (string)$_SESSION['ulogin'] : $default;
    }

    /**
     * Establece el login de usuario en la sesión.
     *
     * @param $userLogin
     */
    public static function setUserLogin($userLogin)
    {
        $_SESSION['ulogin'] = (string)$userLogin;
    }

    /**
     * Obtiene el nombre de usuario de la sesión.
     *
     * @return string
     */
    public static function getUserName()
    {
        return (string)$_SESSION['uname'];
    }

    /**
     * Establece el nombre de usuario en la sesión.
     *
     * @param $userName
     */
    public static function setUserName($userName)
    {
        $_SESSION['uname'] = (string)$userName;
    }

    /**
     * Obtiene el id de grupo de usuario de la sesión.
     *
     * @return int
     */
    public static function getUserGroupId()
    {
        return (int)$_SESSION['ugroup'];
    }

    /**
     * Obtiene el id de grupo de usuario de la sesión.
     *
     * @param $groupId
     */
    public static function setUserGroupId($groupId)
    {
        $_SESSION['ugroup'] = (int)$groupId;
    }

    /**
     * Obtiene el nombre del grupo de usuario de la sesión.
     *
     * @return string
     */
    public static function getUserGroupName()
    {
        return (string)$_SESSION['ugroupn'];
    }

    /**
     * Establece el nombre del grupo de usuario en la sesión.
     *
     * @param string $groupName
     */
    public static function setUserGroupName($groupName)
    {
        $_SESSION['ugroupn'] = (string)$groupName;
    }

    /**
     * Obtiene el email de usuario de la sesión.
     *
     * @return string
     */
    public static function getUserEMail()
    {
        return (string)$_SESSION['uemail'];
    }

    /**
     * Establece el nombre del grupo de usuario en la sesión.
     *
     * @param $userEmail
     */
    public static function setUserEMail($userEmail)
    {
        $_SESSION['uemail'] = (string)$userEmail;
    }

    /**
     * Obtiene si es un usuario de LDAP de la sesión.
     *
     * @return bool
     */
    public static function getUserIsLdap()
    {
        return (bool)$_SESSION['uisldap'];
    }

    /**
     * Establece si es un usuario de LDAP en la sesión.
     *
     * @param $bool
     */
    public static function setUserIsLdap($bool)
    {
        $_SESSION['uisldap'] = (bool)$bool;
    }

    /**
     * Obtiene el objeto de perfil de usuario de la sesión.
     *
     * @return Profile
     */
    public static function getUserProfile()
    {
        return $_SESSION["usrprofile"];
    }

    /**
     * Establece el objeto de perfil de usuario en la sesión.
     *
     * @param \SP\Profile $profile
     */
    public static function setUserProfile(\SP\Profile $profile)
    {
        $_SESSION["usrprofile"] = $profile;
    }

    /**
     * @return \SP\AccountSearch
     */
    public static function getSearchFilters()
    {
        return (isset($_SESSION["searchFilters"])) ? $_SESSION["searchFilters"] : null;
    }

    /**
     * @param \SP\AccountSearch $searchFilters
     */
    public static function setSearchFilters(\SP\AccountSearch $searchFilters)
    {
        $_SESSION["searchFilters"] = $searchFilters;
    }

    /**
     * Establece la cuenta primaria para el histórico
     *
     * @param $id int El id de la cuenta
     */
    public static function setAccountParentId($id)
    {
        $_SESSION["accParentId"] = (int)$id;
    }

    /**
     * Devuelve la cuenta primaria para el histórico
     *
     * @return int
     */
    public static function getAccountParentId()
    {
        return (isset($_SESSION["accParentId"])) ? $_SESSION["accParentId"] : null;
    }

    /**
     * Establece si se ha comprobado si hay actualizaciones
     *
     * @param bool $bool
     */
    public static function setUpdated($bool = true)
    {
        $_SESSION["updated"] = $bool;
    }

    /**
     * Devuelve si se ha combrobado si hay actualizaciones
     *
     * @return bool
     */
    public static function getUpdated()
    {
        return (isset($_SESSION["updated"])) ? $_SESSION["updated"] : false;
    }

    /**
     * Devuelve el timeout de la sesión
     *
     * @return int|null El valor en segundos
     */
    public static function getSessionTimeout()
    {
        return (isset($_SESSION["sessionTimeout"])) ? $_SESSION["sessionTimeout"] : null;
    }

    /**
     * Establecer el timeout de la sesión
     *
     * @param int $timeout El valor en segundos
     */
    public static function setSessionTimeout($timeout)
    {
        $_SESSION["sessionTimeout"] = $timeout;
    }

    /**
     * Devuelve si es necesario recargar la aplicación
     *
     * @return bool|null
     */
    public static function getReload()
    {
        return (isset($_SESSION["reload"])) ? $_SESSION["reload"] : null;
    }

    /**
     * Establecer si es necesario recargar la aplicación
     *
     * @param bool $bool
     */
    public static function setReload($bool = false)
    {
        $_SESSION["reload"] = $bool;
    }

    /**
     * Devuelve la clave de seguridad para los formularios
     *
     * @return string|null
     */
    public static function getSecurityKey()
    {
        return (isset($_SESSION["sk"])) ? $_SESSION["sk"] : null;
    }

    /**
     * Establece la clave de seguridad para los formularios
     *
     * @param string $sk La clave de seguridad
     */
    public static function setSecurityKey($sk)
    {
        $_SESSION["sk"] = $sk;
    }

    /**
     * Devuelve la clave maestra encriptada
     *
     * @return string
     */
    public static function getMPass()
    {
        return $_SESSION["mPass"];
    }

    /**
     * Establecer la clave maestra encriptada
     *
     * @param $mpass string La clave maestra
     */
    public static function setMPass($mpass)
    {
        $_SESSION["mPass"] = $mpass;
    }

    /**
     * Devuelve la clave usada para encriptar la clave maestra
     *
     * @return string
     */
    public static function getMPassPwd()
    {
        return $_SESSION["mPassPwd"];
    }

    /**
     * Establece la clave usada para encriptar la clave maestra
     *
     * @param $mPassPwd string La clave usada
     */
    public static function setMPassPwd($mPassPwd)
    {
        $_SESSION["mPassPwd"] = $mPassPwd;
    }

    /**
     * Devuelve el vector de inicialización de la clave maestra
     *
     * @return string
     */
    public static function getMPassIV()
    {
        return $_SESSION["mPassIV"];
    }

    /**
     * Establece el vector de inicialización de la clave maestra
     *
     * @param $mPassIV string El vector de inicialización
     */
    public static function setMPassIV($mPassIV)
    {
        $_SESSION["mPassIV"] = $mPassIV;
    }

    /**
     * Devuelve la hora en la que el SID de sesión fue creado
     *
     * @return int
     */
    public static function getSidStartTime()
    {
        return (isset($_SESSION['sidStartTime'])) ? (int)$_SESSION['sidStartTime'] : 0;
    }

    /**
     * Establece la hora de creación del SID
     *
     * @param $time int La marca de hora
     */
    public static function setSidStartTime($time)
    {
        $_SESSION['sidStartTime'] = (int)$time;
    }

    /**
     * Devuelve la hora de inicio de actividad.
     *
     * @return int
     */
    public static function getStartActivity()
    {
        return (isset($_SESSION['startActivity'])) ? (int)$_SESSION['startActivity'] : 0;
    }

    /**
     * Establece la hora de inicio de actividad
     *
     * @param $time int La marca de hora
     */
    public static function setStartActivity($time)
    {
        $_SESSION['startActivity'] = $time;
    }

    /**
     * Devuelve la hora de la última actividad
     *
     * @return int
     */
    public static function getLastActivity()
    {
        return (isset($_SESSION['lastActivity'])) ? $_SESSION['lastActivity'] : 0;
    }

    /**
     * Establece la hora de la última actividad
     *
     * @param $time int La marca de hora
     */
    public static function setLastActivity($time)
    {
        $_SESSION['lastActivity'] = $time;
    }

    /**
     * Devuelve el id de la última cuenta vista
     *
     * @return int
     */
    public static function getLastAcountId()
    {
        return (isset($_SESSION['lastAccountId'])) ? $_SESSION['lastAccountId'] : 0;
    }

    /**
     * Establece el id de la última cuenta vista
     *
     * @param $id int La marca de hora
     */
    public static function setLastAcountId($id)
    {
        $_SESSION['lastAccountId'] = $id;
    }
}