<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Auth\Ports;


use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Auth\Services\LoginResponse;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;

/**
 * Class LoginService
 *
 * @package SP\Domain\Common\Services
 */
interface LoginServiceInterface
{
    /**
     * Ejecutar las acciones de login
     *
     * @return LoginResponse
     * @throws AuthException
     * @throws SPException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     * @uses LoginService::authBrowser()
     * @uses LoginService::authDatabase()
     * @uses LoginService::authLdap()
     *
     */
    public function doLogin(): LoginResponse;

    /**
     * @param  string|null  $from
     */
    public function setFrom(?string $from): void;
}
