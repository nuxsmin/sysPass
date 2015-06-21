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
     * @return string
     */
    public static function getUserLogin()
    {
        return isset($_SESSION['ulogin']) ? (string)$_SESSION['ulogin'] : '-';
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
     * @return object
     */
    public static function getUserProfile()
    {
        return (object)$_SESSION["usrprofile"];
    }

    /**
     * Establece el objeto de perfil de usuario en la sesión.
     *
     * @param \StdClass $profile
     */
    public static function setUserProfile(\StdClass $profile)
    {
        $_SESSION["usrprofile"] = $profile;
    }

    /**
     * @return \SP\AccountSearch
     */
    public static function getSearchFilters()
    {
        return $_SESSION["search"];
    }

    /**
     * @param \SP\AccountSearch $search
     */
    public static function setSearchFilters(\SP\AccountSearch $search)
    {
        $_SESSION["search"] = $search;
    }

    /**
     * Establece la cuenta primaria para el histórico
     *
     * @param $id int El id de la cuenta
     */
    public static function setAccountParentId($id){
        $_SESSION["accParentId"] = (int) $id;
    }

    /**
     * Devuelve la cuenta primaria para el histórico
     *
     * @return int
     */
    public static function getAccountParentId()
    {
        return $_SESSION["accParentId"];
    }
}