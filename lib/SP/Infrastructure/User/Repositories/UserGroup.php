<?php
/*
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

namespace SP\Infrastructure\User\Repositories;

use Exception;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountToUserGroup as AccountToUserGroupModel;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\User as UserModel;
use SP\Domain\User\Models\UserGroup as UserGroupModel;
use SP\Domain\User\Models\UserToUserGroup as UserToUserGroupModel;
use SP\Domain\User\Ports\UserGroupRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class UserGroup
 *
 * @template T of UserGroupModel
 */
final class UserGroup extends BaseRepository implements UserGroupRepository
{
    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(UserGroupModel::TABLE)
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the group'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns the items that are using the given group id
     *
     * @param int $userGroupId
     * @return QueryResult
     */
    public function getUsage(int $userGroupId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(UserModel::TABLE)
            ->cols(['userGroupId AS id', '"User" AS ref'])
            ->where('userGroupId = :userGroupId')
            ->unionAll()
            ->from(UserToUserGroupModel::TABLE)
            ->cols(['userGroupId AS id', '"UserGroup" AS ref'])
            ->where('userGroupId = :userGroupId')
            ->unionAll()
            ->from(AccountToUserGroupModel::TABLE)
            ->cols(['userGroupId AS id', '"AccountToUserGroup" AS ref'])
            ->where('userGroupId = :userGroupId')
            ->unionAll()
            ->from(AccountModel::TABLE)
            ->cols(['userGroupId AS id', '"Account" AS ref'])
            ->where('userGroupId = :userGroupId')
            ->bindValues(['userGroupId' => $userGroupId]);

        return $this->db->runQuery(QueryData::build($query));
    }

    /**
     * Returns the users that are using the given group id
     *
     * @param int $userGroupId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function getUsageByUsers(int $userGroupId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(
                [
                    sprintf('%s.id AS id', UserModel::TABLE),
                    sprintf('%s.name as name', UserModel::TABLE),
                    sprintf('%s.login as login', UserModel::TABLE),
                    'ref'
                ]
            )
            ->fromSubSelect(
                $this->queryFactory
                    ->newSelect()
                    ->from(UserModel::TABLE)
                    ->cols(['id', '"User" AS ref'])
                    ->where('userGroupId = :userGroupId')
                    ->unionAll()
                    ->from(UserToUserGroupModel::TABLE)
                    ->cols(['userId AS id', '"UserGroup" AS ref'])
                    ->where('userGroupId = :userGroupId'),
                'Users'
            )
            ->innerJoin(UserModel::TABLE, sprintf('%s.id = %s.id', UserModel::TABLE, 'Users'))
            ->bindValues(['userGroupId' => $userGroupId],);

        return $this->db->runQuery(QueryData::build($query));
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult<T>
     */
    public function getById(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(UserGroupModel::getCols())
            ->from(UserGroupModel::TABLE)
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        return $this->db->runQuery(QueryData::buildWithMapper($query, UserGroupModel::class));
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     *
     * @return QueryResult<T>
     */
    public function getByName(string $name): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(UserGroupModel::getCols())
            ->from(UserGroupModel::TABLE)
            ->where('name = :name', ['name' => $name])
            ->limit(1);

        return $this->db->runQuery(QueryData::buildWithMapper($query, UserGroupModel::class));
    }

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     */
    public function getAll(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(UserGroupModel::TABLE)
            ->cols(UserGroupModel::getCols())
            ->orderBy(['name']);

        return $this->db->runQuery(QueryData::buildWithMapper($query, UserGroupModel::class));
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array<int> $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): QueryResult
    {
        if (count($ids) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(UserGroupModel::TABLE)
            ->where('id IN (:ids)', ['ids' => $ids]);

        return $this->db->runQuery(QueryData::build($query));
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult<T>
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(UserGroupModel::TABLE)
            ->cols(UserGroupModel::getCols())
            ->orderBy(['name'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('name LIKE :name OR description LIKE :description');

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues(['name' => $search, 'description' => $search]);
        }

        $queryData = QueryData::build($query)->setMapClassName(UserGroupModel::class);

        return $this->db->runQuery($queryData, true);
    }

    /**
     * Creates an item
     *
     * @param UserGroupModel $userGroup
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create(UserGroupModel $userGroup): QueryResult
    {
        if ($this->checkDuplicatedOnAdd($userGroup)) {
            throw DuplicatedItemException::error(__u('Duplicated group name'));
        }

        $query = $this->queryFactory
            ->newInsert()
            ->into(UserGroupModel::TABLE)
            ->cols($userGroup->toArray(null, ['id']));

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the group'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param UserGroupModel $userGroup
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnAdd(UserGroupModel $userGroup): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(UserGroupModel::TABLE)
            ->where('UPPER(:name) = UPPER(name)', ['name' => $userGroup->getName()]);

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param UserGroupModel $userGroup
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update(UserGroupModel $userGroup): int
    {
        if ($this->checkDuplicatedOnUpdate($userGroup)) {
            throw DuplicatedItemException::error(__u('Duplicated group name'));
        }

        $query = $this->queryFactory
            ->newUpdate()
            ->table(UserGroupModel::TABLE)
            ->cols($userGroup->toArray(null, ['id']))
            ->where('id = :id', ['id' => $userGroup->getId()])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the group'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param UserGroupModel $userGroup
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnUpdate(UserGroupModel $userGroup): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(UserGroupModel::TABLE)
            ->where('id <> :id', ['id' => $userGroup->getId()])
            ->where('UPPER(:name) = UPPER(name)', ['name' => $userGroup->getName()]);

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }
}
