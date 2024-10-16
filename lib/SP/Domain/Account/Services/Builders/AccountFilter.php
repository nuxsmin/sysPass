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

namespace SP\Domain\Account\Services\Builders;

use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\QueryFactory;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountHistory as AccountHistoryModel;
use SP\Domain\Account\Ports\AccountFilterBuilder;
use SP\Domain\Account\Ports\AccountSearchConstants;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;

/**
 * Class AccountFilter
 */
final readonly class AccountFilter implements AccountFilterBuilder
{

    public function __construct(
        private Context             $context,
        private ConfigDataInterface $configData,
        private QueryFactory        $queryFactory
    ) {
    }

    /**
     * Devuelve el filtro para la consulta SQL de cuentas que un usuario puede acceder
     */
    public function buildFilterHistory(bool $useGlobalSearch = false, ?SelectInterface $query = null): SelectInterface
    {
        $userData = $this->context->getUserData();
        $userProfile = $this->context->getUserProfile();

        if ($query === null) {
            $query = $this->queryFactory->newSelect()->from(AccountHistoryModel::TABLE);
        }

        if ($this->isFilterWithoutGlobalSearch($userData, $useGlobalSearch, $userProfile)) {
            $where = [
                'AccountHistory.userId = :userId',
                'AccountHistory.userGroupId = :userGroupId',
                'AccountHistory.accountId IN (SELECT accountId AS accountId FROM AccountToUser WHERE accountId = AccountHistory.accountId AND userId = :userId UNION ALL SELECT accountId FROM AccountToUserGroup WHERE accountId = AccountHistory.accountId AND userGroupId = :userGroupId',
                'AccountHistory.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = AccountHistory.userGroupId AND userId = :userId)',
            ];

            if ($this->configData->isAccountFullGroupAccess()) {
                // Filtro de grupos secundarios en grupos que incluyen al usuario
                $where[] =
                    'AccountHistory.accountId = (SELECT accountId FROM AccountToUserGroup aug INNER JOIN UserToUserGroup uug ON uug.userGroupId = aug.userGroupId WHERE aug.accountId = AccountHistory.accountId AND uug.userId = :userId LIMIT 1)';
            }

            $query->where(sprintf('(%s)', join(sprintf(' %s ', AccountSearchConstants::FILTER_CHAIN_OR), $where)));
        }

        $query->where(
            '(AccountHistory.isPrivate IS NULL OR AccountHistory.isPrivate = 0 OR (AccountHistory.isPrivate = 1 AND AccountHistory.userId = :userId))'
        );
        $query->where(
            '(AccountHistory.isPrivateGroup IS NULL OR AccountHistory.isPrivateGroup = 0 OR (AccountHistory.isPrivateGroup = 1 AND AccountHistory.userGroupId = :userGroupId))'
        );

        $query->bindValues([
                               'userId' => $userData->id,
                               'userGroupId' => $userData->userGroupId,
                           ]);

        return $query;
    }

    /**
     * @param UserDto $userData
     * @param bool $useGlobalSearch
     * @param ProfileData|null $userProfile
     *
     * @return bool
     */
    private function isFilterWithoutGlobalSearch(
        UserDto $userData,
        bool         $useGlobalSearch,
        ?ProfileData $userProfile
    ): bool {
        return !$userData->isAdminApp
               && !$userData->isAdminAcc
               && !($this->configData->isGlobalSearch() && $useGlobalSearch && $userProfile->isAccGlobalSearch());
    }

    /**
     * Devuelve el filtro para la consulta SQL de cuentas que un usuario puede acceder
     */
    public function buildFilter(bool $useGlobalSearch = false, ?SelectInterface $query = null): SelectInterface
    {
        $userData = $this->context->getUserData();
        $userProfile = $this->context->getUserProfile();

        if ($query === null) {
            $query = $this->queryFactory->newSelect()->from(AccountModel::TABLE);
        }

        if ($this->isFilterWithoutGlobalSearch($userData, $useGlobalSearch, $userProfile)) {
            $where = [
                'Account.userId = :userId',
                'Account.userGroupId = :userGroupId',
                'Account.id IN (SELECT accountId AS accountId FROM AccountToUser WHERE accountId = Account.id AND userId = :userId UNION ALL SELECT accountId FROM AccountToUserGroup WHERE accountId = Account.id AND userGroupId = :userGroupId)',
                'Account.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = Account.userGroupId AND userId = :userId)',
            ];

            if ($this->configData->isAccountFullGroupAccess()) {
                // Filtro de grupos secundarios en grupos que incluyen al usuario
                $where[] =
                    'Account.id = (SELECT accountId FROM AccountToUserGroup aug INNER JOIN UserToUserGroup uug ON uug.userGroupId = aug.userGroupId WHERE aug.accountId = Account.id AND uug.userId = :userId LIMIT 1)';
            }

            $query->where(sprintf('(%s)', join(sprintf(' %s ', AccountSearchConstants::FILTER_CHAIN_OR), $where)));
        }

        $query->where(
            '(Account.isPrivate IS NULL OR Account.isPrivate = 0 OR (Account.isPrivate = 1 AND Account.userId = :userId))'
        );
        $query->where(
            '(Account.isPrivateGroup IS NULL OR Account.isPrivateGroup = 0 OR (Account.isPrivateGroup = 1 AND Account.userGroupId = :userGroupId))'
        );

        $query->bindValues([
                               'userId' => $userData->id,
                               'userGroupId' => $userData->userGroupId,
                           ]);

        return $query;
    }
}
