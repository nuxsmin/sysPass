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

namespace SP\Core\Crypt;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;

/**
 * Class Crypt
 *
 * @package SP\Core\Crypt
 */
class Crypt
{
    /**
     * Encriptar datos con una clave segura
     *
     * @param $data
     * @param $securedKey
     * @return string
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     */
    public static function encrypt($data, $securedKey)
    {
        $key = Key::loadFromAsciiSafeString($securedKey);

        return Crypto::encrypt($data, $key);
    }

    /**
     * Desencriptar datos con una clave segura
     *
     * @param $data
     * @param $securedKey
     * @return string
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public static function decrypt($data, $securedKey)
    {
        $key = Key::loadFromAsciiSafeString($securedKey);

        try {
            return Crypto::decrypt($data, $key);
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            return false;
        }

    }

    /**
     * Securiza una clave de seguridad
     *
     * @param $password
     * @return string
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public static function makeSecuredKey($password)
    {
        return KeyProtectedByPassword::createRandomPasswordProtectedKey($password)->saveToAsciiSafeString();
    }

    /**
     * @param $key
     * @param $password
     * @return string
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     */
    public static function unlockSecuredKey($key, $password)
    {
        try {
            return KeyProtectedByPassword::loadFromAsciiSafeString($key)->unlockKey($password)->saveToAsciiSafeString();
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            return false;
        }
    }
}