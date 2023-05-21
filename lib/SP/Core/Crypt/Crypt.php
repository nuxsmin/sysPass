<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Core\Crypt;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;
use SP\Core\Exceptions\CryptException;
use SP\Core\Exceptions\SPException;

/**
 * Class Crypt
 *
 * @package SP\Core\Crypt
 */
class Crypt implements CryptInterface
{
    /**
     * Securiza una clave de seguridad
     *
     * @param  string  $password
     * @param  bool  $useAscii
     *
     * @return string|KeyProtectedByPassword
     * @throws \SP\Core\Exceptions\CryptException
     * @TODO: Update callers to use instance
     */
    public function makeSecuredKey(string $password, bool $useAscii = true): KeyProtectedByPassword|string
    {
        try {
            if ($useAscii) {
                return KeyProtectedByPassword::createRandomPasswordProtectedKey($password)->saveToAsciiSafeString();
            }

            return KeyProtectedByPassword::createRandomPasswordProtectedKey($password);
        } catch (CryptoException $e) {
            throw new CryptException($e->getMessage(), SPException::ERROR, null, $e->getCode(), $e);
        }
    }

    /**
     * Encriptar datos con una clave segura
     *
     * @param  string  $data
     * @param  string|Key  $securedKey
     * @param  string|null  $password
     *
     * @return string
     * @throws \SP\Core\Exceptions\CryptException
     *
     * @TODO: Update callers to use instance
     */
    public function encrypt(string $data, Key|string $securedKey, ?string $password = null): string
    {
        try {
            if ($securedKey instanceof Key) {
                $key = $securedKey;
            } elseif (null !== $password) {
                $key = $this->unlockSecuredKey($securedKey, $password, false);
            } else {
                $key = Key::loadFromAsciiSafeString($securedKey);
            }

            return Crypto::encrypt($data, $key);
        } catch (CryptoException $e) {
            throw new CryptException($e->getMessage(), SPException::ERROR, null, $e->getCode(), $e);
        }
    }

    /**
     * @param  string  $key
     * @param  string  $password
     * @param  bool  $useAscii
     *
     * @return string|Key
     * @throws \SP\Core\Exceptions\CryptException
     */
    private function unlockSecuredKey(string $key, string $password, bool $useAscii = true): Key|string
    {
        try {
            if ($useAscii) {
                return KeyProtectedByPassword::loadFromAsciiSafeString($key)
                                             ->unlockKey($password)
                                             ->saveToAsciiSafeString();
            }

            return KeyProtectedByPassword::loadFromAsciiSafeString($key)->unlockKey($password);
        } catch (CryptoException $e) {
            throw new CryptException($e->getMessage(), SPException::ERROR, null, $e->getCode(), $e);
        }
    }

    /**
     * Desencriptar datos con una clave segura
     *
     * @param  string  $data
     * @param  string|Key|KeyProtectedByPassword  $securedKey
     * @param  string|null  $password
     *
     * @return string
     * @throws \SP\Core\Exceptions\CryptException
     * @TODO: Update callers to use instance
     */
    public function decrypt(
        string $data,
        Key|KeyProtectedByPassword|string $securedKey,
        ?string $password = null
    ): string {
        try {
            if ($securedKey instanceof Key) {
                return Crypto::decrypt($data, $securedKey);
            }

            if (null !== $password) {
                if ($securedKey instanceof KeyProtectedByPassword) {
                    return Crypto::decrypt($data, $securedKey->unlockKey($password));
                }

                return Crypto::decrypt($data, $this->unlockSecuredKey($securedKey, $password, false));
            }

            return Crypto::decrypt($data, Key::loadFromAsciiSafeString($securedKey));
        } catch (CryptoException $e) {
            throw new CryptException($e->getMessage(), SPException::ERROR, null, $e->getCode(), $e);
        }
    }
}
