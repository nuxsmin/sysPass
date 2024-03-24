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

namespace SP\Providers\Auth\Browser;

use SP\Providers\Auth\AuthInterface;

/**
 * Class Browser
 *
 * Autentificación basada en credenciales del navegador
 *
 * @extends AuthInterface<BrowserAuthData>
 */
interface BrowserAuthInterface extends AuthInterface
{
    /**
     * Comprobar si el usuario es autentificado por el servidor web
     *
     * @param $login string El login del usuario a comprobar
     *
     * @return bool|null
     */
    public function checkServerAuthUser(string $login): ?bool;

    /**
     * Devolver el nombre del usuario autentificado por el servidor web
     *
     * @return string
     */
    public function getServerAuthUser(): ?string;
}
