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

namespace SP\Domain\User;


use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserLoginData;
use SP\Domain\User\Services\UserPassResponse;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class UserPassService
 *
 * @package SP\Domain\User\Services
 */
interface UserPassServiceInterface
{
    /**
     * Actualizar la clave maestra con la clave anterior del usuario
     *
     * @throws SPException
     * @throws CryptoException
     */
    public function updateMasterPassFromOldPass(string $oldUserPass, UserLoginData $userLoginData): UserPassResponse;

    /**
     * Comprueba la clave maestra del usuario.
     *
     * @throws SPException
     */
    public function loadUserMPass(UserLoginData $userLoginData, ?string $userPass = null): UserPassResponse;

    /**
     * Obtener una clave de cifrado basada en la clave del usuario y un salt.
     *
     * @return string con la clave de cifrado
     */
    public function makeKeyForUser(string $userLogin, string $userPass): string;

    /**
     * Actualizar la clave maestra del usuario al realizar login
     *
     * @throws SPException
     * @throws CryptoException
     * @throws SPException
     */
    public function updateMasterPassOnLogin(string $userMPass, UserLoginData $userLoginData): UserPassResponse;

    /**
     * Actualizar la clave maestra del usuario en la BBDD.
     *
     * @throws CryptoException
     * @throws SPException
     */
    public function createMasterPass(string $masterPass, string $userLogin, string $userPass): UserPassResponse;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function migrateUserPassById(int $id, string $userPass): void;
}