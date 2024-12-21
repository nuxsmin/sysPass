<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Common\Models\Item;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\Query;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class AccountToUserGroup
 */
final class AccountToUserGroup extends BaseRepository implements AccountToUserGroupRepository
{
    use RepositoryItemTrait;

    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param int $id con el Id de la cuenta
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserGroupsByAccountId(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                       'UserGroup.id',
                       'UserGroup.name',
                       'AccountToUserGroup.isEdit',
                   ])
            ->from('AccountToUserGroup')
            ->join('INNER', 'UserGroup', 'UserGroup.id == AccountToUserGroup.userGroupId')
            ->where('AccountToUserGroup.accountId = :accountId')
            ->bindValues(['accountId' => $id])
            ->orderBy(['UserGroup.name ASC']);

        return $this->db->runQuery(QueryData::build($query)->setMapClassName(Item::class));
    }

    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserGroupsByUserGroupId(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                       'UserGroup.id',
                       'UserGroup.name',
                       'AccountToUserGroup.isEdit',
                   ])
            ->from('AccountToUserGroup')
            ->join('INNER', 'UserGroup', 'UserGroup.id == AccountToUserGroup.userGroupId')
            ->where('AccountToUserGroup.userGroupId = :userGroupId')
            ->bindValues(['userGroupId' => $id])
            ->orderBy(['UserGroup.name ASC']);

        return $this->db->runQuery(QueryData::build($query)->setMapClassName(Item::class));
    }

    /**
     * @param $id int
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByUserGroupId(int $id): bool
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from('AccountToUserGroup')
            ->where('userGroupId = :userGroupId')
            ->bindValues(['userGroupId' => $id,]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the account\'s groups'));

        return $this->db->runQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * @param int $id
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
            ->from('AccountToUserGroup')
            ->where('accountId = :accountId')
            ->where('isEdit = :isEdit')
            ->bindValues([
                             'accountId' => $id,
                             'isEdit' => (int)$isEdit,
                         ]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the account\'s groups'));

        $this->db->runQuery($queryData);
    }

    /**
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
            'INSERT INTO AccountToUserGroup (accountId, userGroupId, isEdit) 
              VALUES ' . $parameters . '
              ON DUPLICATE KEY UPDATE isEdit = ?';

        $queryData = QueryData::build(
            Query::buildForMySQL($query, array_merge_recursive($values))
        )->setOnErrorMessage(__u('Error while updating the secondary groups'));

        $this->db->runQuery($queryData);
    }

    /**
     * @param $id int
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountId(int $id): bool
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from('AccountToUserGroup')
            ->where('accountId = :accountId')
            ->bindValues(['accountId' => $id,]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the account\'s groups'));

        return $this->db->runQuery($queryData)->getAffectedNumRows() === 1;
    }
}
