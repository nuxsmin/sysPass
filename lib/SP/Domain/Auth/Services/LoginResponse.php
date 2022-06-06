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

namespace SP\Domain\Auth\Services;

/**
 * Class LoginResponse
 *
 * @package SP\Domain\Auth\Services
 */
final class LoginResponse
{
    private int     $status;
    private ?string $redirect;

    /**
     * LoginResponse constructor.
     *
     * @param  int  $status
     * @param  string|null  $redirect
     */
    public function __construct(int $status, ?string $redirect = null)
    {
        $this->status = $status;
        $this->redirect = $redirect;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getRedirect(): ?string
    {
        return $this->redirect;
    }
}