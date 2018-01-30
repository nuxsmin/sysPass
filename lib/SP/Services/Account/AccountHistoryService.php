<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Services\Account;

use SP\Core\Exceptions\SPException;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\AccountHistoryData;
use SP\Repositories\Account\AccountHistoryRepository;
use SP\Repositories\Account\AccountToUserGroupRepository;
use SP\Repositories\Account\AccountToUserRepository;

/**
 * Class AccountHistoryService
 *
 * @package SP\Services\Account
 */
class AccountHistoryService
{
    use InjectableTrait;

    /**
     * @var AccountHistoryRepository
     */
    protected $accountHistoryRepository;
    /**
     * @var AccountToUserGroupRepository
     */
    protected $accountToUserGroupRepository;
    /**
     * @var AccountToUserRepository
     */
    protected $accountToUserRepository;

    /**
     * AccountHistoryService constructor.
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct()
    {
        $this->injectDependencies();
    }

    /**
     * @param AccountHistoryRepository     $accountHistoryRepository
     * @param AccountToUserGroupRepository $accountToUserGroupRepository
     * @param AccountToUserRepository      $accountToUserRepository
     */
    public function inject(AccountHistoryRepository $accountHistoryRepository,
                           AccountToUserGroupRepository $accountToUserGroupRepository,
                           AccountToUserRepository $accountToUserRepository)
    {
        $this->accountHistoryRepository = $accountHistoryRepository;
        $this->accountToUserGroupRepository = $accountToUserGroupRepository;
        $this->accountToUserRepository = $accountToUserRepository;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return AccountHistoryData
     * @throws SPException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getById($id)
    {
        return $this->accountHistoryRepository->getById($id);
    }

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @param $id
     * @return array|false Con los registros con id como clave y fecha - usuario como valor
     */
    public function getHistoryForAccount($id)
    {
        return $this->accountHistoryRepository->getHistoryForAccount($id);
    }

    /**
     * @param $id
     * @return array
     */
    public function getUsersByAccountId($id)
    {
        return $this->accountToUserRepository->getUsersByAccountId($id);
    }

    /**
     * @param $id
     * @return array
     */
    public function getUserGroupsByAccountId($id)
    {
        return $this->accountToUserGroupRepository->getUserGroupsByAccountId($id);
    }
}