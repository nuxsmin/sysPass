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

namespace SP\Domain\Core\Crypt;

/**
 * Class UuidCookie
 */
interface UuidCookieInterface
{
    /**
     * Firmar la cookie para autentificación
     */
    public function sign(string $data, string $cypher): string;

    /**
     * Comprobar la firma de la cookie y devolver los datos
     *
     * @param string $data
     * @param string $cypher
     *
     * @return bool|string
     */
    public function getCookieData(string $data, string $cypher): bool|string;

    /**
     * Creates a cookie and sets its data
     *
     * @return string|false
     */
    public function create(string $signKey): bool|string;

    /**
     * Loads cookie data
     *
     * @return false|string
     */
    public function load(string $signKey): bool|string;
}
