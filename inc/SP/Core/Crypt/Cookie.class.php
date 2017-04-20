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
 * Class Cookie
 *
 * @package SP\Core\Crypt
 */
abstract class Cookie
{
    /**
     * Firmar la cookie para autentificación
     *
     * @param string $data
     * @param string $cypher
     * @return string
     */
    protected final function sign($data, $cypher)
    {
        $data = base64_encode($data);

        return hash_hmac('sha256', $data, $cypher) . ';' . $data;
    }

    /**
     * Comprobar la firma de la cookie y devolver los datos
     *
     * @param string $data
     * @param string $cypher
     * @return bool|string
     */
    protected final function getCookieData($data, $cypher)
    {
        list($signature, $data) = explode(';', $data, 2);

        if (!empty($signature) && !empty($data)) {
            return hash_equals($signature, hash_hmac('sha256', $data, $cypher)) ? base64_decode($data) : false;
        }

        return false;
    }
}