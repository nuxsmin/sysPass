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

namespace SP\Repositories\Account;

use SP\Account\AccountRequest;
use SP\DataModel\ItemData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class AccountToUserRepository
 *
 * @package SP\Repositories\Account
 */
class AccountToUserRepository extends Repository
{
    use RepositoryItemTrait;

    /**
     * Actualizar la asociación de grupos con cuentas.
     *
     * @param AccountRequest $accountRequest
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(AccountRequest $accountRequest)
    {
        $this->deleteByAccountId($accountRequest->id);
        $this->add($accountRequest);

        return false;
    }

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int $id con el Id de la cuenta
     *
     * @return int
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function deleteByAccountId($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountToUser WHERE accountId = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar usuarios asociados a la cuenta'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Crear asociación de usuarios con cuentas.
     *
     * @param AccountRequest $accountRequest
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add(AccountRequest $accountRequest)
    {
        $query = /** @lang SQL */
            'INSERT INTO AccountToUser (accountId, userId, isEdit) 
              VALUES ' . $this->getParamsFromArray($accountRequest->usersView, '(?,?,0)') . '
              ON DUPLICATE KEY UPDATE isEdit = 0';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setOnErrorMessage(__u('Error al actualizar los usuarios de la cuenta'));

        foreach ($accountRequest->usersView as $user) {
            $queryData->addParam($accountRequest->id);
            $queryData->addParam($user);
        }

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Actualizar la asociación de grupos con cuentas.
     *
     * @param AccountRequest $accountRequest
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function updateEdit(AccountRequest $accountRequest)
    {
        $this->deleteEditByAccountId($accountRequest->id);
        $this->addEdit($accountRequest);

        return false;
    }

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int $id con el Id de la cuenta
     *
     * @return int
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function deleteEditByAccountId($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountToUser WHERE accountId = ? AND isEdit = 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar usuarios asociados a la cuenta'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Crear asociación de usuarios con cuentas.
     *
     * @param AccountRequest $accountRequest
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function addEdit(AccountRequest $accountRequest)
    {
        $query = /** @lang SQL */
            'INSERT INTO AccountToUser (accountId, userId, isEdit) 
              VALUES ' . $this->getParamsFromArray($accountRequest->usersEdit, '(?,?,1)') . '
              ON DUPLICATE KEY UPDATE isEdit = 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setOnErrorMessage(__u('Error al actualizar los usuarios de la cuenta'));

        foreach ($accountRequest->usersEdit as $user) {
            $queryData->addParam($accountRequest->id);
            $queryData->addParam($user);
        }

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Obtiene el listado de usuarios de una cuenta.
     *
     * @param int $id con el id de la cuenta
     *
     * @return ItemData[] con los id de usuarios de la cuenta
     */
    public function getUsersByAccountId($id)
    {
        $query = /** @lang SQL */
            'SELECT U.id, U.name, U.login, AU.isEdit
            FROM AccountToUser AU
            INNER JOIN User U ON AU.userId = U.id
            WHERE AU.accountId = ?
            ORDER BY U.name';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setMapClassName(ItemData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }
}