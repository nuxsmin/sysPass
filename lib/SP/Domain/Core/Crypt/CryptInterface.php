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

namespace SP\Domain\Core\Crypt;

use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;
use SP\Domain\Core\Exceptions\CryptException;

/**
 * Class Crypt
 */
interface CryptInterface
{
    /**
     * Securiza una clave de seguridad
     *
     * @param  string  $password
     * @param  bool  $useAscii
     *
     * @return string|KeyProtectedByPassword
     * @throws CryptException
     */
    public function makeSecuredKey(string $password, bool $useAscii = true): KeyProtectedByPassword|string;

    /**
     * Encriptar datos con una clave segura
     *
     * @param  string  $data
     * @param  string|Key  $securedKey
     * @param  string|null  $password
     *
     * @return string
     * @throws CryptException
     */
    public function encrypt(string $data, Key|string $securedKey, ?string $password = null): string;

    /**
     * Desencriptar datos con una clave segura
     *
     * @param  string  $data
     * @param  string|Key|KeyProtectedByPassword  $securedKey
     * @param  string|null  $password
     *
     * @return string
     * @throws CryptException
     */
    public function decrypt(
        string $data,
        Key|KeyProtectedByPassword|string $securedKey,
        ?string $password = null
    ): string;
}
