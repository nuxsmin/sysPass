<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use SP\Http\Request;

/**
 * Class SecureKeyCookie
 *
 * @package SP\Core\Crypt
 */
final class SecureKeyCookie extends Cookie
{
    /**
     * Nombre de la cookie
     */
    public const COOKIE_NAME = 'SYSPASS_SK';
    /**
     * Llave usada para encriptar los datos
     */
    private ?Key $securedKey = null;
    private ?string $cypher = null;

    public static function factory(Request $request): SecureKeyCookie
    {
        $self = new self(self::COOKIE_NAME, $request);
        $self->cypher = $self->getCypher();

        return $self;
    }

    /**
     * Devolver la llave de cifrado para los datos de la cookie
     */
    public function getCypher(): string
    {
        return sha1($this->request->getHeader('User-Agent') . $this->request->getClientAddress());
    }

    /**
     * Obtener una llave de encriptación
     *
     * @return false|Key
     */
    public function getKey()
    {
        $cookie = $this->getCookie();

        if ($cookie !== false) {
            $data = $this->getCookieData($cookie, $this->cypher);

            if ($data !== false) {
                /** @var Vault $vault */
                $vault = unserialize($data, ['allowed_classes' => Vault::class]);

                if (($vault instanceof Vault) === true
                ) {
                    try {
                        $this->securedKey = Key::loadFromAsciiSafeString($vault->getData($this->cypher));

                        return $this->securedKey;
                    } catch (CryptoException $e) {
                        logger($e->getMessage(), 'EXCEPTION');
                    }

                    return false;
                }
            } else {
                logger('Cookie verification error', 'ERROR');
            }
        } elseif (($this->securedKey instanceof Key) === true) {
            return $this->securedKey;
        }

        return $this->saveKey() ? $this->securedKey : false;
    }

    /**
     * Guardar una llave de encriptación
     */
    public function saveKey(): bool
    {
        try {
            if ($this->setCookie($this->sign($this->generateSecuredData()->getSerialized(), $this->cypher)) === false) {
                logger('Could not generate session\'s key cookie', 'ERROR');

                unset($this->securedKey);

                return false;
            }

            logger('Generating a new session\'s key cookie');

            return true;
        } catch (CryptoException $e) {
            logger($e->getMessage(), 'EXCEPTION');
        }

        return false;
    }

    /**
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     */
    public function generateSecuredData(): Vault
    {
        $this->securedKey = Key::createNewRandomKey();

        return (new Vault())
            ->saveData($this->securedKey->saveToAsciiSafeString(), $this->cypher);
    }

    public function getSecuredKey(): Key
    {
        return $this->securedKey;
    }
}