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

use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\UserProfile as UserProfileModel;
use SP\Domain\User\Ports\UserProfileRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class UserProfile
 *
 * @template T of UserProfileModel
 */
final class UserProfile extends BaseRepository implements UserProfileRepository
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
            ->from(UserProfileModel::TABLE)
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while removing the profile'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(UserProfileModel::getCols())
            ->from(UserProfileModel::TABLE)
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        return $this->db->runQuery(QueryData::buildWithMapper($query, UserProfileModel::class));
    }

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(UserProfileModel::TABLE)
            ->cols(UserProfileModel::getCols())
            ->orderBy(['name']);

        return $this->db->runQuery(QueryData::buildWithMapper($query, UserProfileModel::class));
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
            ->from(UserProfileModel::TABLE)
            ->where('id IN (:ids)', ['ids' => $ids]);

        return $this->db->runQuery(QueryData::build($query));
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(UserProfileModel::TABLE)
            ->cols(UserProfileModel::getCols())
            ->orderBy(['name'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->where('name LIKE :name', ['name' => $search]);
        }

        $queryData = QueryData::build($query)->setMapClassName(UserProfileModel::class);

        return $this->db->runQuery($queryData, true);
    }

    /**
     * Creates an item
     *
     * @param UserProfileModel $userProfile
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create(UserProfileModel $userProfile): QueryResult
    {
        if ($this->checkDuplicatedOnAdd($userProfile)) {
            throw DuplicatedItemException::error(__u('Duplicated profile name'));
        }

        $query = $this->queryFactory
            ->newInsert()
            ->into(UserProfileModel::TABLE)
            ->cols($userProfile->toArray(null, ['id']));

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the profile'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param UserProfileModel $userProfile
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnAdd(UserProfileModel $userProfile): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(UserProfileModel::TABLE)
            ->where('UPPER(:name) = UPPER(name)', ['name' => $userProfile->getName()]);

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param UserProfileModel $userProfile
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update(UserProfileModel $userProfile): int
    {
        if ($this->checkDuplicatedOnUpdate($userProfile)) {
            throw DuplicatedItemException::error(__u('Duplicated profile name'));
        }

        $query = $this->queryFactory
            ->newUpdate()
            ->table(UserProfileModel::TABLE)
            ->cols($userProfile->toArray(null, ['id']))
            ->where('id = :id', ['id' => $userProfile->getId()])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the profile'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param UserProfileModel $userProfile
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnUpdate(UserProfileModel $userProfile): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(UserProfileModel::TABLE)
            ->where('id <> :id', ['id' => $userProfile->getId()])
            ->where('UPPER(:name) = UPPER(name)', ['name' => $userProfile->getName()]);

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }
}
