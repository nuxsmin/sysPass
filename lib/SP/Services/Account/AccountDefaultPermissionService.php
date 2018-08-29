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

namespace SP\Services\Account;

use SP\DataModel\AccountDefaultPermissionData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Account\AccountDefaultPermissionRepository;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database\QueryResult;

/**
 * Class AccountDefaultPermissionService
 *
 * @package SP\Services\Account
 */
class AccountDefaultPermissionService extends Service
{
    /**
     * @var AccountDefaultPermissionRepository
     */
    private $accountDefaultPermissionRepository;

    /**
     * @param AccountDefaultPermissionData $accountDefaultPermissionData
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(AccountDefaultPermissionData $accountDefaultPermissionData)
    {
        return $this->accountDefaultPermissionRepository->create($accountDefaultPermissionData);
    }

    /**
     * @param AccountDefaultPermissionData $accountDefaultPermissionData
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(AccountDefaultPermissionData $accountDefaultPermissionData)
    {
        return $this->accountDefaultPermissionRepository->update($accountDefaultPermissionData);
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return AccountDefaultPermissionService
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        if ($this->accountDefaultPermissionRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Permiso no encontrada'));
        }

        return $this;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return AccountDefaultPermissionData
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getById($id)
    {
        $result = $this->accountDefaultPermissionRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Permiso no encontrada'));
        }

        /** @var AccountDefaultPermissionData $data */
        $data = $result->getData();

        return $data->hydrate();
    }

    /**
     * Returns all the items
     *
     * @return AccountDefaultPermissionData[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAll()
    {
        return $this->accountDefaultPermissionRepository->getAll()->getDataAsArray();
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->accountDefaultPermissionRepository->search($itemSearchData);
    }

    /**
     * @return AccountDefaultPermissionData
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getForCurrentUser()
    {
        $userData = $this->context->getUserData();

        return $this->getForUser($userData->getId(), $userData->getUserGroupId(), $userData->getUserProfileId());
    }

    /**
     * @param int $userId
     * @param int $userGroupId
     * @param int $userProfileId
     *
     * @return AccountDefaultPermissionData
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getForUser(int $userId, int $userGroupId, int $userProfileId)
    {
        $result = $this->accountDefaultPermissionRepository->getByFilter(
            $userId,
            $userGroupId,
            $userProfileId
        );

        if ($result->getNumRows() === 1) {
            return $result->getData()->hydrate();
        }

        return null;
    }

    /**
     * @param array $ids
     *
     * @return int
     * @throws ServiceException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->accountDefaultPermissionRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error al eliminar los permisos'), ServiceException::WARNING);
        }

        return $count;
    }

    protected function initialize()
    {
        $this->accountDefaultPermissionRepository = $this->dic->get(AccountDefaultPermissionRepository::class);
    }
}