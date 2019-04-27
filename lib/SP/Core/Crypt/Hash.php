<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

/**
 * Class Hash
 *
 * @package SP\Core\Crypt
 */
final class Hash
{
    /**
     * Longitud máxima aceptada para hashing
     */
    const MAX_KEY_LENGTH = 72;

    /**
     * Comprobar el hash de una clave.
     *
     * @param string $key  con la clave a comprobar
     * @param string $hash con el hash a comprobar
     *
     * @return bool
     */
    public static function checkHashKey($key, $hash)
    {
        return password_verify(self::getKey($key), $hash);
    }

    /**
     * Devolver la clave preparada. Se crea un hash si supera la longitud máxima.
     *
     * @param string $key
     * @param bool   $isCheck Indica si la operación es de comprobación o no
     *
     * @return string
     */
    private static function getKey(&$key, $isCheck = true)
    {
        if (mb_strlen($key) > self::MAX_KEY_LENGTH) {
            $key = hash('sha256', $key);

            if ($isCheck === false) {
                logger('[INFO] Password string shortened using SHA256 and then BCRYPT');
            }
        }

        return $key;
    }

    /**
     * Generar un hash de una clave criptográficamente segura
     *
     * @param string $key con la clave a 'hashear'
     *
     * @return string con el hash de la clave
     */
    public static function hashKey($key)
    {
        return password_hash(self::getKey($key, false), PASSWORD_BCRYPT);
    }

    /**
     * Checks a message with a given key against a hash
     *
     * @param $message
     * @param $key
     * @param $hash
     *
     * @return bool
     */
    public static function checkMessage($message, $key, $hash)
    {
        return hash_equals($hash, self::signMessage($message, $key));
    }

    /**
     * Signs a message with a given key
     *
     * @param $message
     * @param $key
     *
     * @return string
     */
    public static function signMessage($message, $key)
    {
        return hash_hmac('sha256', $message, $key);
    }
}