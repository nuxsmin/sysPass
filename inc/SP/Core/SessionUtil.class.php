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

use SP\Config\Config;
use SP\DataModel\UserData;
use SP\Mgmt\Profiles\Profile;
use SP\Core\Crypt\Session as CryptSession;

defined('APP_ROOT') || die();

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
        Session::setUserData($UserData);
        Session::setUserProfile(Profile::getItem()->getById($UserData->getUserProfileId()));
    }

    /**
     * Establecer la clave pública RSA en la sessión
     *
     * @throws \phpseclib\Exception\FileNotFoundException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function loadPublicKey()
    {
        $CryptPKI = new CryptPKI();
        Session::setPublicKey($CryptPKI->getPublicKey());
    }

    /**
     * Desencriptar la clave maestra de la sesión.
     *
     * @return string con la clave maestra
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     */
    public static function getSessionMPass()
    {
        return CryptSession::getSessionKey();
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
        // Generamos un nuevo hash si es necesario y lo guardamos en la sesión
        if ($new === true || null === Session::getSecurityKey()) {
            $hash = sha1(time() . Config::getConfig()->getPasswordSalt());

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
        return (null !== Session::getSecurityKey() && Session::getSecurityKey() === $key);
    }

    /**
     * Limpiar la sesión del usuario
     */
    public static function cleanSession()
    {
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }

//        Session::unsetSessionKey('userData');
//        Session::unsetSessionKey('usrprofile');
//        Session::unsetSessionKey('searchFilters');
//        Session::unsetSessionKey('updated');
//        Session::unsetSessionKey('sessionTimeout');
//        Session::unsetSessionKey('reload');
//        Session::unsetSessionKey('sk');
//        Session::unsetSessionKey('mPass');
//        Session::unsetSessionKey('mPassPwd');
//        Session::unsetSessionKey('mPassIV');
//        Session::unsetSessionKey('sidStartTime');
//        Session::unsetSessionKey('startActivity');
//        Session::unsetSessionKey('lastActivity');
//        Session::unsetSessionKey('lastAccountId');
//        Session::unsetSessionKey('theme');
//        Session::unsetSessionKey('2fapass');
//        Session::unsetSessionKey('pubkey');
//        Session::unsetSessionKey('locale');
//        Session::unsetSessionKey('userpreferences');
//        Session::unsetSessionKey('tempmasterpass');
//        Session::unsetSessionKey('accountcolor');
//        Session::unsetSessionKey('curlcookiesession');
//        Session::unsetSessionKey('dokuwikisession');
//        Session::unsetSessionKey('sessiontype');
//        Session::unsetSessionKey('config');
//        Session::unsetSessionKey('configTime');
    }

    /**
     * Regenerad el ID de sesión
     */
    public static function regenerate()
    {
        session_regenerate_id(true);
        Session::setSidStartTime(time());
    }

    /**
     * Destruir la sesión y reiniciar
     */
    public static function restart()
    {
        session_unset();
        session_destroy();
        session_start();
    }
}