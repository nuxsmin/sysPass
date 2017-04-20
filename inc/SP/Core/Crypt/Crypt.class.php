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
use Defuse\Crypto\Exception\CryptoException;
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
     * @param string     $data
     * @param string|Key $securedKey
     * @param string     $password
     * @return string
     * @throws CryptoException
     */
    public static function encrypt($data, $securedKey, $password = null)
    {
        try {
            if ($securedKey instanceof Key) {
                $key = $securedKey;
            } elseif (!empty($password)) {
                $key = self::unlockSecuredKey($securedKey, $password, false);
            } else {
                $key = Key::loadFromAsciiSafeString($securedKey);
            }

            return Crypto::encrypt($data, $key);
        } catch (CryptoException $e) {
            debugLog($e->getMessage());

            throw $e;
        }
    }

    /**
     * @param string $key
     * @param string $password
     * @param bool   $useAscii
     * @return string|Key
     * @throws CryptoException
     */
    public static function unlockSecuredKey($key, $password, $useAscii = true)
    {
        try {
            if ($useAscii) {
                return KeyProtectedByPassword::loadFromAsciiSafeString($key)->unlockKey($password)->saveToAsciiSafeString();
            }

            return KeyProtectedByPassword::loadFromAsciiSafeString($key)->unlockKey($password);
        } catch (CryptoException $e) {
            debugLog($e->getMessage());

            throw $e;
        }
    }

    /**
     * Desencriptar datos con una clave segura
     *
     * @param string     $data
     * @param string|Key $securedKey
     * @param string     $password
     * @return string
     * @throws CryptoException
     */
    public static function decrypt($data, $securedKey, $password = null)
    {
        try {
            if ($securedKey instanceof Key) {
                $key = $securedKey;
            } elseif (!empty($password) && $securedKey instanceof KeyProtectedByPassword) {
                $key = self::unlockSecuredKey($securedKey, $password);
            } else {
                $key = Key::loadFromAsciiSafeString($securedKey);
            }

            return Crypto::decrypt($data, $key);
        } catch (CryptoException $e) {
            debugLog($e->getMessage());

            throw $e;
        }

    }

    /**
     * Securiza una clave de seguridad
     *
     * @param string $password
     * @param bool   $useAscii
     * @return string|Key
     * @throws CryptoException
     */
    public static function makeSecuredKey($password, $useAscii = true)
    {
        try {
            if ($useAscii) {
                return KeyProtectedByPassword::createRandomPasswordProtectedKey($password)->saveToAsciiSafeString();
            }

            return KeyProtectedByPassword::createRandomPasswordProtectedKey($password);
        } catch (CryptoException $e) {
            debugLog($e->getMessage());

            throw $e;
        }
    }
}