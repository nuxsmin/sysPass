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

use SP\DataModel\UserData;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Profiles\ProfileUtil;
use SP\Mgmt\Users\User;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class SessionUtil para las utilidades de la sesión
 *
 * @package SP
 */
class SessionUtil
{
    /**
     * Establece las variables de sesión del usuario.
     *
     * @param UserData $UserData
     */
    public static function loadUserSession(UserData $UserData)
    {
        Session::setUserId($UserData->getUserId());
        Session::setUserName($UserData->getUserName());
        Session::setUserLogin($UserData->getUserLogin());
        Session::setUserProfileId($UserData->getUserProfileId());
        Session::setUserGroupId($UserData->getUserGroupId());
        Session::setUserGroupName($UserData->getUsergroupName()); // FIXME
        Session::setUserEMail($UserData->getUserEmail());
        Session::setUserIsAdminApp($UserData->isUserIsAdminApp());
        Session::setUserIsAdminAcc($UserData->isUserIsAdminAcc());
        Session::setUserIsLdap($UserData->isUserIsLdap());
        Session::setUserProfile(Profile::getItem()->getById($UserData->getUserProfileId())->getItemData());
    }

    /**
     * Establecer la clave pública RSA en la sessión
     */
    public static function loadPublicKey()
    {
        $CryptPKI = new CryptPKI();
        Session::setPublicKey($CryptPKI->getPublicKey());
    }

    /**
     * Guardar la clave maestra encriptada en la sesión
     *
     * @param $masterPass
     * @return bool
     */
    public static function saveSessionMPass($masterPass)
    {
        $mPassPwd = Crypt::generateAesKey(session_id());
        $sessionMasterPass = Crypt::mkCustomMPassEncrypt($mPassPwd, $masterPass);

        Session::setMPass($sessionMasterPass[0]);
        Session::setMPassIV($sessionMasterPass[1]);

        return true;
    }

    /**
     * Desencriptar la clave maestra de la sesión.
     *
     * @return string con la clave maestra
     */
    public static function getSessionMPass()
    {
        $cryptPass = Crypt::generateAesKey(session_id());
        return Crypt::getDecrypt(Session::getMPass(), Session::getMPassIV(), $cryptPass);
    }

    /**
     * Devuelve un hash para verificación de formularios.
     * Esta función genera un hash que permite verificar la autenticidad de un formulario
     *
     * @param bool $new si es necesrio regenerar el hash
     * @return string con el hash de verificación
     */
    public static function getSessionKey($new = false)
    {
        $hash = sha1(time());

        // Generamos un nuevo hash si es necesario y lo guardamos en la sesión
        if (is_null(Session::getSecurityKey()) || $new === true) {
            Session::setSecurityKey($hash);
            return $hash;
        }

        return Session::getSecurityKey();
    }

    /**
     * Comprobar el hash de verificación de formularios.
     *
     * @param string $key con el hash a comprobar
     * @return bool|string si no es correcto el hash devuelve bool. Si lo es, devuelve el hash actual.
     */
    public static function checkSessionKey($key)
    {
        return (!is_null(Session::getSecurityKey()) && Session::getSecurityKey() == $key);
    }

    /**
     * Limpiar la sesión del usuario
     */
    public static function cleanSession()
    {
        Session::unsetSessionKey('uid');
        Session::unsetSessionKey('uisadminapp');
        Session::unsetSessionKey('uisadminacc');
        Session::unsetSessionKey('uprofile');
        Session::unsetSessionKey('ulogin');
        Session::unsetSessionKey('uname');
        Session::unsetSessionKey('ugroup');
        Session::unsetSessionKey('ugroupn');
        Session::unsetSessionKey('uemail');
        Session::unsetSessionKey('uisldap');
        Session::unsetSessionKey('usrprofile');
        Session::unsetSessionKey('searchFilters');
        Session::unsetSessionKey('accParentId');
        Session::unsetSessionKey('mPass');
        Session::unsetSessionKey('mPassPwd');
        Session::unsetSessionKey('mPassIV');
        Session::unsetSessionKey('sidStartTime');
        Session::unsetSessionKey('startActivity');
        Session::unsetSessionKey('lastActivity');
        Session::unsetSessionKey('lastAccountId');
        Session::unsetSessionKey('theme');
        Session::unsetSessionKey('2fapass');
        Session::unsetSessionKey('locale');
        Session::unsetSessionKey('userpreferences');
        Session::unsetSessionKey('tempmasterpass');
        Session::unsetSessionKey('accountcolor');
    }
}