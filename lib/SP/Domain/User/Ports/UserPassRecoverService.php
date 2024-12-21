<?php
declare(strict_types=1);
/**
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

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;

/**
 * Class UserPassRecoverService
 *
 * @package SP\Domain\Common\Services\UserPassRecover
 */
interface UserPassRecoverService
{
    /**
     * @throws SPException
     * @throws ServiceException
     */
    public function toggleUsedByHash(string $hash): void;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     * @throws EnvironmentIsBrokenException
     */
    public function requestForUserId(int $id): string;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(int $userId, string $hash): void;

    /**
     * Comprobar el hash de recuperación de clave.
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserIdForHash(string $hash): int;
}
