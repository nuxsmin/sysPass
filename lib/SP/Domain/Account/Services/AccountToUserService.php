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

namespace SP\Domain\Account\Services;

use SP\Core\Application;
use SP\DataModel\ItemData;
use SP\Domain\Account\Ports\AccountToUserRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserServiceInterface;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;

/**
 * Class AccountToUserService
 */
final class AccountToUserService extends Service implements AccountToUserServiceInterface
{
    public function __construct(
        Application $application,
        private AccountToUserRepositoryInterface $accountToUserRepository
    ) {
        parent::__construct($application);
    }

    /**
     * @param  int  $id
     *
     * @return ItemData[]
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getUsersByAccountId(int $id): array
    {
        return $this->accountToUserRepository->getUsersByAccountId($id)->getDataAsArray();
    }
}
