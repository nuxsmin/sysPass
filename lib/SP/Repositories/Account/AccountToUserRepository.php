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

namespace SP\Repositories\Account;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemTrait;
use SP\Services\Account\AccountRequest;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class AccountToUserRepository
 *
 * @package SP\Repositories\Account
 */
final class AccountToUserRepository extends Repository
{
    use RepositoryItemTrait;

    /**
     * Actualizar la asociación de grupos con cuentas.
     *
     * @param AccountRequest $accountRequest
     *
     * @param bool           $isEdit
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateByType(AccountRequest $accountRequest, bool $isEdit)
    {
        $this->deleteTypeByAccountId($accountRequest->id, $isEdit);
        $this->addByType($accountRequest, $isEdit);

        return false;
    }

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int  $id con el Id de la cuenta
     * @param bool $isEdit
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteTypeByAccountId($id, bool $isEdit)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountToUser WHERE accountId = ? AND isEdit = ?');
        $queryData->setParams([$id, (int)$isEdit]);
        $queryData->setOnErrorMessage(__u('Error while deleting the account users'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Crear asociación de usuarios con cuentas.
     *
     * @param AccountRequest $accountRequest
     * @param bool           $isEdit
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function addByType(AccountRequest $accountRequest, bool $isEdit)
    {
        $items = $isEdit ? $accountRequest->usersEdit : $accountRequest->usersView;
        $values = $this->getParamsFromArray($items, '(?,?,?)');

        $query = /** @lang SQL */
            'INSERT INTO AccountToUser (accountId, userId, isEdit) 
              VALUES ' . $values . '
              ON DUPLICATE KEY UPDATE isEdit = ' . (int)$isEdit;

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setOnErrorMessage(__u('Error while updating the account users'));

        $params = [];

        foreach ($items as $user) {
            $params[] = $accountRequest->id;
            $params[] = $user;
            $params[] = (int)$isEdit;
        }

        $queryData->setParams($params);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int $id con el Id de la cuenta
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountId($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountToUser WHERE accountId = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the account users'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Obtiene el listado de usuarios de una cuenta.
     *
     * @param int $id con el id de la cuenta
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsersByAccountId($id)
    {
        $query = /** @lang SQL */
            'SELECT `User`.id, `User`.name, `User`.login, AccountToUser.isEdit
            FROM AccountToUser
            INNER JOIN `User` ON AccountToUser.userId = `User`.id
            WHERE AccountToUser.accountId = ?
            ORDER BY `User`.name';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setMapClassName(ItemData::class);

        return $this->db->doSelect($queryData);
    }
}