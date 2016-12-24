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
 * Class CryptMasterPass para la gestión de la clave maestra
 *
 * @package SP
 */
class CryptMasterPass
{
    const MAX_ATTEMPTS = 100;

    /**
     * Crea una clave temporal para encriptar la clave maestra y guardarla.
     *
     * @param int $maxTime El tiempo máximo de validez de la clave
     * @return bool|string
     */
    public static function setTempMasterPass($maxTime = 14400)
    {
        // Encriptar la clave maestra con hash aleatorio generado
        $randomKey = Crypt::generateAesKey(Util::generate_random_bytes());
        $pass = Crypt::mkCustomMPassEncrypt($randomKey, SessionUtil::getSessionMPass());

        if (!is_array($pass)) {
            return false;
        }

        ConfigDB::setCacheConfigValue('tempmaster_pass', bin2hex($pass[0]));
        ConfigDB::setCacheConfigValue('tempmaster_passiv', bin2hex($pass[1]));
        ConfigDB::setCacheConfigValue('tempmaster_passhash', Crypt::mkHashPassword($randomKey));
        ConfigDB::setCacheConfigValue('tempmaster_passtime', time());
        ConfigDB::setCacheConfigValue('tempmaster_maxtime', time() + $maxTime);
        ConfigDB::setCacheConfigValue('tempmaster_attempts', 0);

        if (!ConfigDB::writeConfig(true)) {
            return false;
        }

        // Guardar la clave temporal hasta que finalice la sesión
        Session::setTemporaryMasterPass($randomKey);

        return $randomKey;
    }

    /**
     * Comprueba si la clave temporal es válida
     *
     * @param string $pass clave a comprobar
     * @return bool
     */
    public static function checkTempMasterPass($pass)
    {
        $passTime = ConfigDB::getValue('tempmaster_passtime');
        $passMaxTime = ConfigDB::getValue('tempmaster_maxtime');
        $attempts = ConfigDB::getValue('tempmaster_attempts');

        // Comprobar si el tiempo de validez se ha superado
        if ($passTime !== false
            && time() - $passTime > $passMaxTime
            || $attempts >= self::MAX_ATTEMPTS
        ) {
            ConfigDB::setCacheConfigValue('tempmaster_pass', '');
            ConfigDB::setCacheConfigValue('tempmaster_passiv', '');
            ConfigDB::setCacheConfigValue('tempmaster_passhash', '');
            ConfigDB::writeConfig();

            return false;
        }

        Crypt::checkHashPass($pass, ConfigDB::getValue('tempmaster_passhash'));

        $isValid = Crypt::checkHashPass($pass, ConfigDB::getValue('tempmaster_passhash'));

        if (!$isValid) {
            ConfigDB::setValue('tempmaster_attempts', $attempts + 1, false);
        }

        return $isValid;
    }

    /**
     * Devuelve la clave maestra que ha sido encriptada con la clave temporal
     *
     * @param $pass string con la clave utilizada para encriptar
     * @return string con la clave maestra desencriptada
     */
    public static function getTempMasterPass($pass)
    {
        $passLogin = hex2bin(ConfigDB::getValue('tempmaster_pass'));
        $passLoginIV = hex2bin(ConfigDB::getValue('tempmaster_passiv'));

        return Crypt::getDecrypt($passLogin, $passLoginIV, $pass);
    }
}