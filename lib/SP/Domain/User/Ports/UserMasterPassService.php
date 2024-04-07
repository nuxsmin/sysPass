<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\User\Ports;

use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\User\Dtos\UserMasterPassDto;

/**
 * Class UserPassService
 *
 * @package SP\Domain\User\Services
 */
interface UserMasterPassService
{
    /**
     * Actualizar la clave maestra con la clave anterior del usuario
     *
     * @throws ServiceException
     */
    public function updateFromOldPass(string $oldUserPass, UserLoginDto $userLoginDto): UserMasterPassDto;

    /**
     * Comprueba la clave maestra del usuario.
     *
     * @throws ServiceException
     */
    public function load(UserLoginDto $userLoginDto, ?string $userPass = null): UserMasterPassDto;

    /**
     * Actualizar la clave maestra del usuario al realizar login
     *
     * @throws ServiceException
     */
    public function updateOnLogin(string $userMasterPass, UserLoginDto $userLoginDto): UserMasterPassDto;

    /**
     * Actualizar la clave maestra del usuario en la BBDD.
     *
     * @throws ServiceException
     */
    public function create(string $masterPass, string $userLogin, string $userPass): UserMasterPassDto;
}
