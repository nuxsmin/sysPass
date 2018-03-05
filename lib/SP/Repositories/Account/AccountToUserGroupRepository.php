<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
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

namespace SP\Repositories\Account;

use SP\Account\AccountRequest;
use SP\DataModel\ItemData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class AccountToUserGroupRepository
 *
 * @package SP\Repositories\Account
 */
class AccountToUserGroupRepository extends Repository
{
    use RepositoryItemTrait;

    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param int $id con el Id de la cuenta
     * @return ItemData[]
     */
    public function getUserGroupsByAccountId($id)
    {
        $query = /** @lang SQL */
            'SELECT G.id, G.name
            FROM AccountToUserGroup AUG
            INNER JOIN UserGroup G ON AUG.userGroupId = G.id
            WHERE AUG.accountId = ?
            ORDER BY G.name';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setMapClassName(ItemData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param $id
     * @return ItemData[]
     */
    public function getUserGroupsByUserGroupId($id)
    {
        $query = /** @lang SQL */
            'SELECT G.id, G.name
            FROM AccountToUserGroup AUG
            INNER JOIN UserGroup G ON AUG.userGroupId = G.id
            WHERE AUG.userGroupId = ?
            ORDER BY G.name';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setMapClassName(ItemData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * @param $id int
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByUserGroupId($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountToUserGroup WHERE userGroupId = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar grupos asociados a la cuenta'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * @param AccountRequest $accountRequest
     * @return $this
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(AccountRequest $accountRequest)
    {
        $this->deleteByAccountId($accountRequest->id);
        $this->add($accountRequest);

        return $this;
    }

    /**
     * @param $id int
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByAccountId($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountToUserGroup WHERE accountId = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar grupos asociados a la cuenta'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * @param AccountRequest $accountRequest
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add(AccountRequest $accountRequest)
    {
        $query = /** @lang SQL */
            'INSERT INTO AccountToUserGroup (accountId, userGroupId) VALUES ' . $this->getParamsFromArray($accountRequest->userGroups, '(?,?)');

        $queryData = new QueryData();
        $queryData->setQuery($query);

        foreach ($accountRequest->userGroups as $userGroup) {
            $queryData->addParam($accountRequest->id);
            $queryData->addParam($userGroup);
        }

        $queryData->setOnErrorMessage(__u('Error al actualizar los grupos secundarios'));

        return DbWrapper::getQuery($queryData, $this->db);
    }
}