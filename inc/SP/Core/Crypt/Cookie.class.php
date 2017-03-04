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

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Key;
use SP\Core\Init;
use SP\Http\Request;
use SP\Util\Checks;

/**
 * Class Cookie
 *
 * @package SP\Core\Crypt
 */
class Cookie
{
    /**
     * Nombre de la cookie
     */
    const COOKIE_NAME = 'SYSPASS_SK';
    /**
     * @var Key
     */
    public static $Key;

    /**
     * Obtener una llave de encriptación
     *
     * @param string $key
     * @return Key|false|string
     */
    public static function getKey($key = null)
    {
        $key = $key === null ? self::getCypher() : $key;

        if (isset($_COOKIE[Cookie::COOKIE_NAME])) {
            /** @var Vault $Vault */
            $Vault = unserialize($_COOKIE[Cookie::COOKIE_NAME]);

            if ($Vault !== false
                && ($Vault instanceof Vault) === true
            ) {
                try {
                    return Key::loadFromAsciiSafeString($Vault->getData($key));
                } catch (CryptoException $e) {
                    debugLog($e->getMessage());

                    return false;
                }
            }
        } elseif ((self::$Key instanceof Key) === true) {
            return self::$Key;
        } else {
            return self::saveKey($key);
        }

        return false;
    }

    /**
     * Devolver la llave de cifrado
     *
     * @return string
     */
    private static function getCypher()
    {
        return md5(Request::getRequestHeaders('User-Agent'));
    }

    /**
     * Guardar una llave de encriptación
     *
     * @param $key
     * @return Key|bool
     */
    public static function saveKey($key)
    {
        if (empty($key)) {
            return false;
        }

        try {
            self::$Key = Key::createNewRandomKey();

            $Vault = new Vault();
            $Vault->saveData(self::$Key->saveToAsciiSafeString(), $key);

//            $timeout = ini_get('session.gc_maxlifetime') ?: 3600;

            if (setcookie(Cookie::COOKIE_NAME, serialize($Vault), 0, Init::$WEBURI, Checks::httpsEnabled())) {
                return self::$Key;
            } else {
                self::$Key = null;
            }
        } catch (CryptoException $e) {
            debugLog($e->getMessage());
        }

        return false;
    }
}