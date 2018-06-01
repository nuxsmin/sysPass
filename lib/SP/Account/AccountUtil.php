<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Account;

use SP\Core\Context\ContextInterface;
use SP\Mvc\Model\QueryCondition;

defined('APP_ROOT') || die();

/**
 * Class AccountUtil con utilidades para la gestión de cuentas
 *
 * @package SP
 */
class AccountUtil
{
    /**
     * Devuelve el filtro para la consulta SQL de cuentas que un usuario puede acceder
     *
     * @param ContextInterface $context
     * @param bool             $useGlobalSearch
     * @return QueryCondition
     */
    public static function getAccountHistoryFilterUser(ContextInterface $context, $useGlobalSearch = false)
    {
        $queryFilter = new QueryCondition();

        $configData = $context->getConfig();
        $userData = $context->getUserData();

        if (!$userData->getIsAdminApp()
            && !$userData->getIsAdminAcc()
            && !($useGlobalSearch && $context->getUserProfile()->isAccGlobalSearch() && $configData->isGlobalSearch())
        ) {
            // Filtro usuario y grupo
            $filter = '(AccountHistory.userId = ? 
            OR AccountHistory.userGroupId = ? 
            OR AccountHistory.accountId IN (SELECT accountId AS accountId FROM AccountToUser WHERE accountId = AccountHistory.accountId AND userId = ? UNION ALL SELECT accountId FROM AccountToUserGroup WHERE accountId = AccountHistory.accountId AND userGroupId = ?)
            OR AccountHistory.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = AccountHistory.userGroupId AND userId = ?))';

            $params = [$userData->getId(), $userData->getUserGroupId(), $userData->getId(), $userData->getUserGroupId(), $userData->getId()];

            if ($configData->isAccountFullGroupAccess()) {
                // Filtro de grupos secundarios en grupos que incluyen al usuario
                $filter .= PHP_EOL . 'OR AccountHistory.accountId = (SELECT accountId FROM AccountToUserGroup aug INNER JOIN UserToUserGroup uug ON uug.userGroupId = aug.userGroupId WHERE aug.accountId = AccountHistory.accountId AND uug.userId = ? LIMIT 1)';
                $params[] = $userData->getId();
            }

            $queryFilter->addFilter($filter, $params);
        }

        $queryFilter->addFilter(
            '(AccountHistory.isPrivate IS NULL OR AccountHistory.isPrivate = 0 OR (AccountHistory.isPrivate = 1 AND AccountHistory.userId = ?)) AND (AccountHistory.isPrivateGroup IS NULL OR AccountHistory.isPrivateGroup = 0 OR (AccountHistory.isPrivateGroup = 1 AND AccountHistory.userGroupId = ?))',
            [$userData->getId(), $userData->getUserGroupId()]
        );

        return $queryFilter;
    }

    /**
     * Devuelve el filtro para la consulta SQL de cuentas que un usuario puede acceder
     *
     * @param ContextInterface $context
     * @param bool             $useGlobalSearch
     * @return QueryCondition
     */
    public static function getAccountFilterUser(ContextInterface $context, $useGlobalSearch = false)
    {
        $queryFilter = new QueryCondition();

        $configData = $context->getConfig();
        $userData = $context->getUserData();

        if (!$userData->getIsAdminApp()
            && !$userData->getIsAdminAcc()
            && !($useGlobalSearch && $context->getUserProfile()->isAccGlobalSearch() && $configData->isGlobalSearch())
        ) {
            // Filtro usuario y grupo
            $filter = '(Account.userId = ? 
            OR Account.userGroupId = ? 
            OR Account.id IN (SELECT accountId AS accountId FROM AccountToUser WHERE accountId = Account.id AND userId = ? UNION ALL SELECT accountId FROM AccountToUserGroup WHERE accountId = Account.id AND userGroupId = ?)
            OR Account.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = Account.userGroupId AND userId = ?))';

            $params = [$userData->getId(), $userData->getUserGroupId(), $userData->getId(), $userData->getUserGroupId(), $userData->getId()];

            if ($configData->isAccountFullGroupAccess()) {
                // Filtro de grupos secundarios en grupos que incluyen al usuario
                $filter .= PHP_EOL . 'OR Account.id = (SELECT accountId FROM AccountToUserGroup aug INNER JOIN UserToUserGroup uug ON uug.userGroupId = aug.userGroupId WHERE aug.accountId = Account.id AND uug.userId = ? LIMIT 1)';
                $params[] = $userData->getId();
            }

            $queryFilter->addFilter($filter, $params);
        }

        $queryFilter->addFilter(
            '(Account.isPrivate IS NULL OR Account.isPrivate = 0 OR (Account.isPrivate = 1 AND Account.userId = ?)) AND (Account.isPrivateGroup IS NULL OR Account.isPrivateGroup = 0 OR (Account.isPrivateGroup = 1 AND Account.userGroupId = ?))',
            [$userData->getId(), $userData->getUserGroupId()]
        );

        return $queryFilter;
    }
}