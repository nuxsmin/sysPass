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
use SP\Util\Util;

/**
 * Class SecureKeyCookie
 *
 * @package SP\Core\Crypt
 */
class SecureKeyCookie extends Cookie
{
    /**
     * Nombre de la cookie
     */
    const COOKIE_NAME = 'SYSPASS_SK';
    /**
     * Llave usada para encriptar los datos
     *
     * @var Key
     */
    protected $SecuredKey;

    /**
     * Obtener una llave de encriptación
     *
     * @param string $key
     * @return Key|false|string
     */
    public static function getKey($key = null)
    {
        $Cookie = new SecureKeyCookie();

        $key = $key === null ? $Cookie->getCypher() : $key;

        if (isset($_COOKIE[SecureKeyCookie::COOKIE_NAME])) {
            $data = $Cookie->getCookieData($_COOKIE[SecureKeyCookie::COOKIE_NAME], $key);

            if ($data === false) {
                debugLog('Cookie verification error.');

                return $Cookie->saveKey($key);
            }

            /** @var Vault $Vault */
            $Vault = unserialize($data);

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
        } elseif (($Cookie->getSecuredKey() instanceof Key) === true) {
            return $Cookie->getSecuredKey();
        } else {
            return $Cookie->saveKey($key);
        }

        return false;
    }

    /**
     * Devolver la llave de cifrado para los datos de la cookie
     *
     * @return string
     */
    public function getCypher()
    {
        return md5(Request::getRequestHeaders('User-Agent') . Util::getClientAddress());
    }

    /**
     * Guardar una llave de encriptación
     *
     * @param $key
     * @return Key|bool
     */
    public function saveKey($key)
    {
        if (empty($key)) {
            return false;
        }

        try {
            $this->SecuredKey = Key::createNewRandomKey();

            $Vault = new Vault();
            $Vault->saveData($this->SecuredKey->saveToAsciiSafeString(), $key);

//            $timeout = ini_get('session.gc_maxlifetime') ?: 3600;

            if (setcookie(SecureKeyCookie::COOKIE_NAME, $this->sign(serialize($Vault), $key), 0, Init::$WEBROOT)) {
                debugLog('Generating a new session key.');

                return $this->SecuredKey;
            } else {
                debugLog('Could not generate session key cookie.');

                unset($this->SecuredKey);
            }
        } catch (CryptoException $e) {
            debugLog($e->getMessage());
        }

        return false;
    }

    /**
     * @return Key
     */
    public function getSecuredKey()
    {
        return $this->SecuredKey;
    }
}