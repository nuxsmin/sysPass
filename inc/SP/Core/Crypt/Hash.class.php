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

/**
 * Class Hash
 *
 * @package SP\Core\Crypt
 */
class Hash
{
    /**
     * Comprobar el hash de una clave.
     *
     * @param string $key  con la clave a comprobar
     * @param string $hash con el hash a comprobar
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function checkHashKey($key, $hash)
    {
        return password_verify($key, $hash);
    }

    /**
     * Generar un hash de una clave criptográficamente segura
     *
     * @param string $key con la clave a 'hashear'
     * @return string con el hash de la clave
     */
    public static function hashKey($key)
    {
        return password_hash($key, PASSWORD_BCRYPT);
    }
}