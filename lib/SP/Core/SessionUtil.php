<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Bootstrap;
use SP\Config\ConfigData;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Session\Session;
use SP\DataModel\UserData;
use SP\Mgmt\Profiles\Profile;

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
     * @param Session  $session
     */
    public static function loadUserSession(UserData $UserData, Session $session)
    {
        $session->setUserData($UserData);
        $session->setUserProfile(Profile::getItem()->getById($UserData->getUserProfileId()));
    }

    /**
     * Establecer la clave pública RSA en la sessión
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws Dic\ContainerException
     */
    public static function loadPublicKey()
    {
        $CryptPKI = new CryptPKI();
        SessionFactory::setPublicKey($CryptPKI->getPublicKey());
    }

    /**
     * Desencriptar la clave maestra de la sesión.
     *
     * @return string con la clave maestra
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public static function getSessionMPass()
    {
        return CryptSession::getSessionKey();
    }

    /**
     * Devuelve un hash para verificación de formularios.
     * Esta función genera un hash que permite verificar la autenticidad de un formulario
     *
     * @param bool            $new si es necesrio regenerar el hash
     * @param ConfigData|null $configData
     * @return string con el hash de verificación
     * @deprecated
     */
    public static function getSessionKey($new = false, ConfigData $configData = null)
    {
        // FIXME
        if (null === $configData) {
            /** @var ConfigData $ConfigData */
            try {
                $configData = Bootstrap::getContainer()->get(ConfigData::class);
            } catch (NotFoundExceptionInterface $e) {
                return SessionFactory::getSecurityKey();
            } catch (ContainerExceptionInterface $e) {
                return SessionFactory::getSecurityKey();
            }
        }

        // Generamos un nuevo hash si es necesario y lo guardamos en la sesión
        if ($new === true || null === SessionFactory::getSecurityKey()) {
            $hash = sha1(time() . $configData->getPasswordSalt());

            SessionFactory::setSecurityKey($hash);

            return $hash;
        }

        return SessionFactory::getSecurityKey();
    }

    /**
     * Comprobar el hash de verificación de formularios.
     *
     * @param string $key con el hash a comprobar
     * @return bool|string si no es correcto el hash devuelve bool. Si lo es, devuelve el hash actual.
     */
    public static function checkSessionKey($key)
    {
        return (null !== SessionFactory::getSecurityKey() && SessionFactory::getSecurityKey() === $key);
    }

    /**
     * Limpiar la sesión del usuario
     */
    public static function cleanSession()
    {
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Regenerad el ID de sesión
     *
     * @param Session $session
     */
    public static function regenerate(Session $session)
    {
        debugLog(__METHOD__);

        session_regenerate_id(true);

        $session->setSidStartTime(time());
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