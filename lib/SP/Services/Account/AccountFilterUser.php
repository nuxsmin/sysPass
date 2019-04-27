<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Account;

use SP\Config\ConfigData;
use SP\Core\Context\ContextInterface;
use SP\DataModel\ProfileData;
use SP\Mvc\Model\QueryCondition;
use SP\Services\User\UserLoginResponse;

defined('APP_ROOT') || die();

/**
 * Class AccountUtil con utilidades para la gestión de cuentas
 *
 * @package SP
 */
final class AccountFilterUser
{
    /**
     * @var ProfileData
     */
    private $userProfile;
    /**
     * @var ConfigData
     */
    private $configData;
    /**
     * @var UserLoginResponse
     */
    private $userData;
    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * AccountFilterUser constructor.
     *
     * @param ContextInterface $context
     * @param ConfigData       $configData
     */
    public function __construct(ContextInterface $context, ConfigData $configData)
    {
        $this->context = $context;
        $this->configData = $configData;
    }

    /**
     * Devuelve el filtro para la consulta SQL de cuentas que un usuario puede acceder
     *
     * @param bool $useGlobalSearch
     *
     * @return QueryCondition
     */
    public function getFilterHistory($useGlobalSearch = false)
    {
        $this->setUp();

        $queryFilter = new QueryCondition();

        if (!$this->userData->getIsAdminApp()
            && !$this->userData->getIsAdminAcc()
            && !($this->configData->isGlobalSearch() && $useGlobalSearch && $this->userProfile->isAccGlobalSearch())
        ) {
            // Filtro usuario y grupo
            $filter = '(AccountHistory.userId = ? 
            OR AccountHistory.userGroupId = ? 
            OR AccountHistory.accountId IN (SELECT accountId AS accountId FROM AccountToUser WHERE accountId = AccountHistory.accountId AND userId = ? UNION ALL SELECT accountId FROM AccountToUserGroup WHERE accountId = AccountHistory.accountId AND userGroupId = ?)
            OR AccountHistory.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = AccountHistory.userGroupId AND userId = ?))';

            $params = [$this->userData->getId(),
                $this->userData->getUserGroupId(),
                $this->userData->getId(),
                $this->userData->getUserGroupId(),
                $this->userData->getId()];

            if ($this->configData->isAccountFullGroupAccess()) {
                // Filtro de grupos secundarios en grupos que incluyen al usuario
                $filter .= PHP_EOL . 'OR AccountHistory.accountId = (SELECT accountId FROM AccountToUserGroup aug INNER JOIN UserToUserGroup uug ON uug.userGroupId = aug.userGroupId WHERE aug.accountId = AccountHistory.accountId AND uug.userId = ? LIMIT 1)';
                $params[] = $this->userData->getId();
            }

            $queryFilter->addFilter($filter, $params);
        }

        $queryFilter->addFilter(
            '(AccountHistory.isPrivate IS NULL OR AccountHistory.isPrivate = 0 OR (AccountHistory.isPrivate = 1 AND AccountHistory.userId = ?)) AND (AccountHistory.isPrivateGroup IS NULL OR AccountHistory.isPrivateGroup = 0 OR (AccountHistory.isPrivateGroup = 1 AND AccountHistory.userGroupId = ?))',
            [$this->userData->getId(), $this->userData->getUserGroupId()]
        );

        return $queryFilter;
    }

    /**
     * setUp
     */
    private function setUp()
    {
        $this->userData = $this->context->getUserData();
        $this->userProfile = $this->context->getUserProfile();
    }

    /**
     * Devuelve el filtro para la consulta SQL de cuentas que un usuario puede acceder
     *
     * @param bool $useGlobalSearch
     *
     * @return QueryCondition
     */
    public function getFilter($useGlobalSearch = false)
    {
        $this->setUp();

        $queryFilter = new QueryCondition();

        if (!$this->userData->getIsAdminApp()
            && !$this->userData->getIsAdminAcc()
            && !($this->configData->isGlobalSearch() && $useGlobalSearch && $this->userProfile->isAccGlobalSearch())
        ) {
            // Filtro usuario y grupo
            $filter = '(Account.userId = ? 
            OR Account.userGroupId = ? 
            OR Account.id IN (SELECT accountId AS accountId FROM AccountToUser WHERE accountId = Account.id AND userId = ? UNION ALL SELECT accountId FROM AccountToUserGroup WHERE accountId = Account.id AND userGroupId = ?)
            OR Account.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = Account.userGroupId AND userId = ?))';

            $params = [$this->userData->getId(),
                $this->userData->getUserGroupId(),
                $this->userData->getId(),
                $this->userData->getUserGroupId(),
                $this->userData->getId()];

            if ($this->configData->isAccountFullGroupAccess()) {
                // Filtro de grupos secundarios en grupos que incluyen al usuario
                $filter .= PHP_EOL . 'OR Account.id = (SELECT accountId FROM AccountToUserGroup aug INNER JOIN UserToUserGroup uug ON uug.userGroupId = aug.userGroupId WHERE aug.accountId = Account.id AND uug.userId = ? LIMIT 1)';
                $params[] = $this->userData->getId();
            }

            $queryFilter->addFilter($filter, $params);
        }

        $queryFilter->addFilter(
            '(Account.isPrivate IS NULL OR Account.isPrivate = 0 OR (Account.isPrivate = 1 AND Account.userId = ?)) AND (Account.isPrivateGroup IS NULL OR Account.isPrivateGroup = 0 OR (Account.isPrivateGroup = 1 AND Account.userGroupId = ?))',
            [$this->userData->getId(), $this->userData->getUserGroupId()]
        );

        return $queryFilter;
    }
}