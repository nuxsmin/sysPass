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

use SP\DataModel\ItemData;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\Query;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class AccountToUserRepository
 *
 * @package SP\Infrastructure\Account\Repositories
 */
final class AccountToUser extends Repository implements AccountToUserRepository
{
    use RepositoryItemTrait;

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int $id con el Id de la cuenta
     * @param bool $isEdit
     *
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteTypeByAccountId(int $id, bool $isEdit): void
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from('AccountToUser')
            ->where('accountId = :accountId')
            ->where('isEdit = :isEdit')
            ->bindValues([
                             'accountId' => $id,
                             'isEdit' => (int)$isEdit,
                         ]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the account\'s groups'));

        $this->db->doQuery($queryData);
    }

    /**
     * Crear asociación de usuarios con cuentas.
     *
     * @param int $accountId
     * @param array $items
     * @param bool $isEdit
     *
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     */
    public function addByType(int $accountId, array $items, bool $isEdit = false): void
    {
        $values = array_map(static function ($item) use ($accountId, $isEdit) {
            return [$accountId, (int)$item, (int)$isEdit];
        }, $items);

        $parameters = $this->buildParamsFromArray($values, '(?,?,?)');

        $query = /** @lang SQL */
            'INSERT INTO AccountToUser (accountId, userId, isEdit) 
              VALUES ' . $parameters . '
              ON DUPLICATE KEY UPDATE isEdit = ?';

        $queryData = QueryData::build(
            Query::buildForMySQL($query, array_merge_recursive($values))
        )->setOnErrorMessage(__u('Error while updating the account users'));

        $this->db->doQuery($queryData);
    }

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int $id con el Id de la cuenta
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountId(int $id): bool
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from('AccountToUser')
            ->where('accountId = :accountId')
            ->bindValues([
                             'accountId' => $id,
                         ]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the account users'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Obtiene el listado de usuarios de una cuenta.
     *
     * @param int $id con el id de la cuenta
     *
     * @return QueryResult
     */
    public function getUsersByAccountId(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                       'User.id',
                       'User.name',
                       'User.login',
                       'AccountToUser.isEdit',
                   ])
            ->from('AccountToUser')
            ->join('INNER', 'User', 'User.id == AccountToUser.userId')
            ->where('AccountToUser.accountId = :accountId')
            ->bindValues(['accountId' => $id])
            ->orderBy(['User.name ASC']);

        return $this->db->doSelect(QueryData::build($query)->setMapClassName(ItemData::class));
    }
}
