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

namespace SP\Infrastructure\Account\Repositories;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemData;
use SP\Domain\Account\In\AccountToUserGroupRepositoryInterface;
use SP\Domain\Account\Services\AccountRequest;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountToUserGroupRepository
 *
 * @package SP\Infrastructure\Account\Repositories
 */
final class AccountToUserGroupRepository extends Repository implements AccountToUserGroupRepositoryInterface
{
    use RepositoryItemTrait;

    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param  int  $id  con el Id de la cuenta
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserGroupsByAccountId(int $id): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT UserGroup.id, UserGroup.name, AccountToUserGroup.isEdit
            FROM AccountToUserGroup
            INNER JOIN UserGroup ON AccountToUserGroup.userGroupId = UserGroup.id
            WHERE AccountToUserGroup.accountId = ?
            ORDER BY UserGroup.name';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setMapClassName(ItemData::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getUserGroupsByUserGroupId(int $id): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT UserGroup.id, UserGroup.name, AccountToUserGroup.isEdit
            FROM AccountToUserGroup
            INNER JOIN UserGroup ON AccountToUserGroup.userGroupId = UserGroup.id
            WHERE AccountToUserGroup.userGroupId = ?
            ORDER BY UserGroup.name';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setMapClassName(ItemData::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * @param $id int
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByUserGroupId(int $id): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountToUserGroup WHERE userGroupId = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the account\'s groups'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * @param  AccountRequest  $accountRequest
     * @param  bool  $isEdit
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateByType(AccountRequest $accountRequest, bool $isEdit): int
    {
        $this->deleteTypeByAccountId($accountRequest->id, $isEdit);

        return $this->addByType($accountRequest, $isEdit);
    }

    /**
     * @param  int  $id
     * @param  bool  $isEdit
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteTypeByAccountId(int $id, bool $isEdit): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountToUserGroup WHERE accountId = ? AND isEdit = ?');
        $queryData->setParams([$id, (int)$isEdit]);
        $queryData->setOnErrorMessage(__u('Error while deleting the account\'s groups'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * @param  AccountRequest  $accountRequest
     * @param  bool  $isEdit
     *
     * @return int Last ID inserted
     * @throws ConstraintException
     * @throws QueryException
     */
    public function addByType(AccountRequest $accountRequest, bool $isEdit): int
    {
        $items = $isEdit ? $accountRequest->userGroupsEdit : $accountRequest->userGroupsView;
        $values = $this->getParamsFromArray($items, '(?,?,?)');

        $query = /** @lang SQL */
            'INSERT INTO AccountToUserGroup (accountId, userGroupId, isEdit) 
              VALUES '.$values.'
              ON DUPLICATE KEY UPDATE isEdit = '.(int)$isEdit;

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setOnErrorMessage(__u('Error while updating the secondary groups'));

        $params = [];

        foreach ($items as $userGroup) {
            $params[] = $accountRequest->id;
            $params[] = $userGroup;
            $params[] = (int)$isEdit;
        }

        $queryData->setParams($params);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * @param $id int
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountId(int $id): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountToUserGroup WHERE accountId = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the account\'s groups'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }
}