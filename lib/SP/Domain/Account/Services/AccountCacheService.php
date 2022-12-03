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
use SP\Domain\Account\Dtos\AccountCacheDto;
use SP\Domain\Account\Ports\AccountCacheServiceInterface;
use SP\Domain\Account\Ports\AccountToUserGroupRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserRepositoryInterface;
use SP\Domain\Common\Services\Service;

/**
 * Class AccountCacheService
 */
final class AccountCacheService extends Service implements AccountCacheServiceInterface
{
    public function __construct(
        Application $application,
        private AccountToUserRepositoryInterface $accountToUserRepository,
        private AccountToUserGroupRepositoryInterface $accountToUserGroupRepository
    ) {
        parent::__construct($application);
    }

    /**
     * Devolver los accesos desde la caché
     *
     * @param  int  $accountId  The account's ID
     * @param  int  $dateEdit  The account's date edit
     *
     * @return \SP\Domain\Account\Dtos\AccountCacheDto
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getCacheForAccount(int $accountId, int $dateEdit): AccountCacheDto
    {
        $cache = $this->context->getAccountsCache();

        $cacheMiss = $cache === null
                     || !isset($cache[$accountId])
                     || $cache[$accountId]->getTime() < $dateEdit;

        if ($cacheMiss) {
            $cache[$accountId] = new AccountCacheDto(
                $accountId,
                $this->accountToUserRepository->getUsersByAccountId($accountId)->getDataAsArray(),
                $this->accountToUserGroupRepository->getUserGroupsByAccountId($accountId)->getDataAsArray()
            );

            $this->context->setAccountsCache($cache);
        }

        return $cache[$accountId];
    }
}
