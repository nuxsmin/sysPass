<?php
declare(strict_types=1);
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

namespace SP\Domain\Crypt\Ports;

use Exception;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;

/**
 * Class MasterPassService
 *
 * @package SP\Domain\Crypt\Services
 */
interface MasterPassService
{
    /**
     * Check whether the user's master password was updated
     *
     * @param int $userMPassTime
     * @return bool false if it needs to be updated, false otherwise
     */
    public function checkUserUpdateMPass(int $userMPassTime): bool;

    /**
     * Check whether the provided master paswword matches with the current one
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
