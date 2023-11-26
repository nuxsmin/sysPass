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

namespace SP\Domain\Crypt\Ports;

use Exception;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Crypt\Services\UpdateMasterPassRequest;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class MasterPassService
 *
 * @package SP\Domain\Crypt\Services
 */
interface MasterPassServiceInterface
{
    /**
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    public function checkUserUpdateMPass(int $userMPassTime): bool;

    /**
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    public function checkMasterPassword(string $masterPassword): bool;

    /**
     * @throws Exception
     */
    public function changeMasterPassword(UpdateMasterPassRequest $request): void;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateConfig($hash): void;
}
