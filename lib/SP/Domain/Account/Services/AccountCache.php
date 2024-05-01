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

namespace SP\Domain\Account\Services;

use SP\Core\Application;
use SP\Domain\Account\Dtos\AccountCacheDto;
use SP\Domain\Account\Ports\AccountCacheService;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;

/**
 * Class AccountCache
 */
final class AccountCache extends Service implements AccountCacheService
{
    public function __construct(
        Application                                   $application,
        private readonly AccountToUserRepository      $accountToUserRepository,
        private readonly AccountToUserGroupRepository $accountToUserGroupRepository
    ) {
        parent::__construct($application);
    }

    /**
     * Devolver los accesos desde la caché
     *
     * @param int $accountId The account's ID
     * @param int $dateEdit The account's date edit
     *
     * @return AccountCacheDto
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
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
