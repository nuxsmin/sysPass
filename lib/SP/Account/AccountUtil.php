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
            $filter =
                /** @lang SQL */
                '(AH.userId = ? 
            OR AH.userGroupId = ? 
            OR AH.accountId IN (SELECT accountId AS accountId FROM AccountToUser WHERE accountId = AH.accountId AND userId = ? UNION ALL SELECT accountId FROM AccountToUserGroup WHERE accountId = AH.accountId AND userGroupId = ?)
            OR AH.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = AH.userGroupId AND userId = ?))';

            $params = [$userData->getId(), $userData->getUserGroupId(), $userData->getId(), $userData->getUserGroupId(), $userData->getId()];

            if ($configData->isAccountFullGroupAccess()) {
                // Filtro de grupos secundarios en grupos que incluyen al usuario
                $filter .= /** @lang SQL */
                    PHP_EOL . 'OR AH.accountId = (SELECT accountId FROM AccountToUserGroup aug INNER JOIN UserToUserGroup uug ON uug.userGroupId = aug.userGroupId WHERE aug.accountId = AH.accountId AND uug.userId = ? LIMIT 1)';
                $params[] = $userData->getId();
            }

            $queryFilter->addFilter($filter, $params);
        }

        $queryFilter->addFilter(/** @lang SQL */
            '(AH.isPrivate IS NULL OR AH.isPrivate = 0 OR (AH.isPrivate = 1 AND AH.userId = ?)) AND (AH.isPrivateGroup IS NULL OR AH.isPrivateGroup = 0 OR (AH.isPrivateGroup = 1 AND AH.userGroupId = ?))', [$userData->getId(), $userData->getUserGroupId()]);

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
            $filter =
                /** @lang SQL */
                '(A.userId = ? 
            OR A.userGroupId = ? 
            OR A.id IN (SELECT accountId AS accountId FROM AccountToUser WHERE accountId = A.id AND userId = ? UNION ALL SELECT accountId FROM AccountToUserGroup WHERE accountId = A.id AND userGroupId = ?)
            OR A.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = A.userGroupId AND userId = ?))';

            $params = [$userData->getId(), $userData->getUserGroupId(), $userData->getId(), $userData->getUserGroupId(), $userData->getId()];

            if ($configData->isAccountFullGroupAccess()) {
                // Filtro de grupos secundarios en grupos que incluyen al usuario
                $filter .= /** @lang SQL */
                    PHP_EOL . 'OR A.id = (SELECT accountId FROM AccountToUserGroup aug INNER JOIN UserToUserGroup uug ON uug.userGroupId = aug.userGroupId WHERE aug.accountId = A.id AND uug.userId = ? LIMIT 1)';
                $params[] = $userData->getId();
            }

            $queryFilter->addFilter($filter, $params);
        }

        $queryFilter->addFilter(/** @lang SQL */
            '(A.isPrivate IS NULL OR A.isPrivate = 0 OR (A.isPrivate = 1 AND A.userId = ?)) AND (A.isPrivateGroup IS NULL OR A.isPrivateGroup = 0 OR (A.isPrivateGroup = 1 AND A.userGroupId = ?))', [$userData->getId(), $userData->getUserGroupId()]);

        return $queryFilter;
    }
}