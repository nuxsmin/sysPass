<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Core\Crypt;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Key;
use SP\Http\Request;

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
    protected $securedKey;

    /**
     * @param Request $request
     *
     * @return SecureKeyCookie
     */
    public static function factory(Request $request) {
        return new self(self::COOKIE_NAME, $request);
    }

    /**
     * Obtener una llave de encriptación
     *
     * @return Key|false|string
     */
    public function getKey()
    {
        $key = $this->getCypher();

        if (($cookie = $this->getCookie())) {
            $data = $this->getCookieData($cookie, $key);

            if ($data === false) {
                debugLog('Cookie verification error.');

                return $this->saveKey($key);
            }

            /** @var Vault $vault */
            $vault = unserialize($data);

            if ($vault !== false
                && ($vault instanceof Vault) === true
            ) {
                try {
                    return Key::loadFromAsciiSafeString($vault->getData($key));
                } catch (CryptoException $e) {
                    debugLog($e->getMessage());

                    return false;
                }
            }
        } elseif (($this->getSecuredKey() instanceof Key) === true) {
            return $this->getSecuredKey();
        } else {
            return $this->saveKey($key);
        }

        return false;
    }

    /**
     * Devolver la llave de cifrado para los datos de la cookie
     *
     * @return string
     */
    private function getCypher()
    {
        return md5($this->request->getHeader('User-Agent') . $this->request->getClientAddress());
    }

    /**
     * Guardar una llave de encriptación
     *
     * @param $key
     *
     * @return Key|false
     */
    public function saveKey($key)
    {
        if (empty($key)) {
            return false;
        }

        try {
            $this->securedKey = Key::createNewRandomKey();

            $vault = new Vault();
            $vault->saveData($this->securedKey->saveToAsciiSafeString(), $key);

            if ($this->setCookie($this->sign(serialize($vault), $key))) {
                debugLog('Generating a new session key.');

                return $this->securedKey;
            } else {
                debugLog('Could not generate session key cookie.');

                unset($this->securedKey);
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
        return $this->securedKey;
    }
}