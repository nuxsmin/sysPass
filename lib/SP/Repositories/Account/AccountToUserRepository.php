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

namespace SP\Repositories\Account;

use SP\Account\AccountRequest;
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
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(AccountRequest $accountRequest)
    {
        $this->delete($accountRequest->id);
        $this->add($accountRequest);

        return false;
    }

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int $accountId con el Id de la cuenta
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function delete($accountId)
    {
        $Data = new QueryData();

        $query = /** @lang SQL */
            'DELETE FROM AccountToUser WHERE accountId = ?';

        $Data->addParam($accountId);

        $Data->setQuery($query);
        $Data->setOnErrorMessage(__u('Error al eliminar usuarios asociados a la cuenta'));

        return DbWrapper::getQuery($Data);
    }

    /**
     * Crear asociación de usuarios con cuentas.
     *
     * @param AccountRequest $accountRequest
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add(AccountRequest $accountRequest)
    {
        $query = /** @lang SQL */
            'INSERT INTO AccountToUser (accountId, userId) VALUES ' . $this->getParamsFromArray($accountRequest->users);

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setOnErrorMessage(__u('Error al actualizar los usuarios de la cuenta'));

        foreach ($accountRequest->users as $user) {
            $Data->addParam($accountRequest->id);
            $Data->addParam($user);
        }

        return DbWrapper::getQuery($Data);
    }

    /**
     * Obtiene el listado de usuarios de una cuenta.
     *
     * @param int $id con el id de la cuenta
     * @return array con los id de usuarios de la cuenta
     */
    public function getUsersByAccountId($id)
    {
        $query = /** @lang SQL */
            'SELECT U.id, U.name, U.login
            FROM AccountToUser AU
            INNER JOIN User U ON AU.userId = U.id
            WHERE AU.accountId = ?
            ORDER BY U.name';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResultsArray($Data);
    }
}