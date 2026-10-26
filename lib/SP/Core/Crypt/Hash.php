<?php
declare(strict_types=1);
/**
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

use function SP\logger;

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
    public const  MAX_KEY_LENGTH = 72;
    private const HASH_ALGO      = 'sha256';

    /**
     * Comprobar el hash de una clave.
     *
     * @param  string  $key  con la clave a comprobar
     * @param  string  $hash  con el hash a comprobar
     */
    public static function checkHashKey(string $key, string $hash): bool
    {
        return password_verify(self::getKey($key), $hash);
    }

    /**
     * Devolver la clave preparada. Se crea un hash si supera la longitud máxima.
     *
     * @param  string  $key
     * @param  bool  $isCheck  Indica si la operación es de comprobación o no
     *
     * @return string
     */
    private static function getKey(string &$key, bool $isCheck = true): string
    {
        if (mb_strlen($key) > self::MAX_KEY_LENGTH) {
            $key = hash(self::HASH_ALGO, $key);

            if ($isCheck === false) {
                logger('[INFO] Password string shortened using SHA256 and then BCRYPT');
            }
        }

        return $key;
    }

    /**
     * Generar un hash de una clave criptográficamente segura
     *
     * @param  string  $key  con la clave a 'hashear'
     *
     * @return string con el hash de la clave
     */
    public static function hashKey(string $key): string
    {
        return password_hash(self::getKey($key, false), PASSWORD_BCRYPT);
    }

    /**
     * Checks a message with a given key against a hash
     */
    public static function checkMessage(
        string $message,
        string $key,
        string $hash
    ): bool {
        return hash_equals($hash, self::signMessage($message, $key));
    }

    /**
     * Signs a message with a given key
     */
    public static function signMessage(string $message, string $key): string
    {
        return hash_hmac(self::HASH_ALGO, $message, $key);
    }
}
