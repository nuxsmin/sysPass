<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

declare(strict_types=1);

namespace SP\Domain\Crypt\Services;

/**
 * Class Hash
 *
 * @package SP\Domain\Crypt\Services
 */
final class Hash
{
    /**
     * Longitud máxima aceptada para hashing
     */
    public const  MAX_KEY_LENGTH = 72;
    private const HASH_ALGO      = 'sha256';
    private const LONG_BYTES_PREFIX = '$sp-sha256$';
    private const MASTER_PREFIX = '$sp-master-v1$';

    /**
     * Comprobar el hash de una clave.
     *
     * @param  string  $key  con la clave a comprobar
     * @param  string  $hash  con el hash a comprobar
     */
    public static function checkHashKey(string $key, string $hash): bool
    {
        if (str_starts_with($hash, self::LONG_BYTES_PREFIX)) {
            return password_verify(hash(self::HASH_ALGO, $key), substr($hash, strlen(self::LONG_BYTES_PREFIX)));
        }
        return password_verify(self::getKey($key), $hash);
    }

    /** Verify a master key without accepting ambiguous legacy bcrypt inputs. */
    public static function checkMasterKey(string $key, string $hash): bool
    {
        if (str_starts_with($hash, self::MASTER_PREFIX)) {
            return password_verify(hash(self::HASH_ALGO, $key), substr($hash, strlen(self::MASTER_PREFIX)));
        }
        if (str_starts_with($hash, self::LONG_BYTES_PREFIX)) {
            return self::checkHashKey($key, $hash);
        }
        if ((strlen($key) >= self::MAX_KEY_LENGTH && mb_strlen($key) <= self::MAX_KEY_LENGTH)
            || preg_match('/\A[0-9a-f]{64}\z/D', $key) === 1) {
            return false;
        }
        return self::checkHashKey($key, $hash);
    }

    public static function hashMasterKey(string $key): string
    {
        return self::MASTER_PREFIX . password_hash(hash(self::HASH_ALGO, $key), PASSWORD_BCRYPT);
    }

    /**
     * Devolver la clave preparada. Se crea un hash si supera la longitud máxima.
     *
     * @param  string  $key
     *
     * @return string
     */
    private static function getKey(string &$key): string
    {
        if (mb_strlen($key) > self::MAX_KEY_LENGTH) {
            $key = hash(self::HASH_ALGO, $key);
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
        return password_hash(self::getKey($key), PASSWORD_BCRYPT);
    }

    /** Rehash only when the current bcrypt work factor is weaker than the policy. */
    public static function needsRehash(string $hash): bool
    {
        foreach ([self::LONG_BYTES_PREFIX, self::MASTER_PREFIX] as $prefix) {
            if (str_starts_with($hash, $prefix)) {
                $hash = substr($hash, strlen($prefix));
                break;
            }
        }
        $info = password_get_info($hash);
        $defaultCost = defined('PASSWORD_BCRYPT_DEFAULT_COST') ? constant('PASSWORD_BCRYPT_DEFAULT_COST') : 10;

        return $info['algoName'] === 'bcrypt'
            && (int)($info['options']['cost'] ?? 0) < $defaultCost
            && password_needs_rehash($hash, PASSWORD_BCRYPT);
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
