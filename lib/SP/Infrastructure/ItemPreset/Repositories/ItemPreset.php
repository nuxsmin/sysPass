<?php
declare(strict_types=1);
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

namespace SP\Infrastructure\ItemPreset\Repositories;

use Exception;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\ItemPreset\Models\ItemPreset as ItemPresetModel;
use SP\Domain\ItemPreset\Ports\ItemPresetRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class ItemPreset
 *
 * @template T of ItemPresetModel
 */
class ItemPreset extends BaseRepository implements ItemPresetRepository
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ItemPresetModel $itemPreset): QueryResult
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into(ItemPresetModel::TABLE)
            ->cols($itemPreset->toArray(null, ['id']));

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the permission'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Updates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(ItemPresetModel $itemPreset): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(ItemPresetModel::TABLE)
            ->cols($itemPreset->toArray(null, ['id']))
            ->where('id = :id')
            ->limit(1)
            ->bindValues(
                [
                    'id' => $itemPreset->getId()
                ]
            );

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the permission'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(ItemPresetModel::TABLE)
            ->where('id = :id')
            ->bindValues(['id' => $id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the permission'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $itemPresetId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $itemPresetId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(ItemPresetModel::TABLE)
            ->cols(ItemPresetModel::getCols())
            ->where('id = :id')
            ->bindValues(['id' => $itemPresetId])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, ItemPresetModel::class);

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param string $type
     * @param int $userId
     * @param int $userGroupId
     * @param int $userProfileId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByFilter(string $type, int $userId, int $userGroupId, int $userProfileId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(ItemPresetModel::TABLE)
            ->cols(ItemPresetModel::getCols())
            ->cols(
                [
                    'IF(userId IS NOT NULL, priority + 3, 
                    IF(userGroupId IS NOT NULL, priority + 2, 
                    IF(userProfileId IS NOT NULL, priority + 1, 0)))' => 'score'
                ]
            )
            ->where('type = :type')
            ->where(
                '(userId = :userId OR userGroupId = :userGroupId  OR userProfileId = :userProfileId
                        OR userGroupId IN (SELECT UserToUserGroup.userGroupId
                        FROM UserToUserGroup
                        WHERE UserToUserGroup.userId = :userId))'
            )
            ->bindValues([
                             'type' => $type,
                             'userId' => $userId,
                             'userGroupId' => $userGroupId,
                             'userProfileId' => $userProfileId
                         ])
            ->orderBy(['score DESC'])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, ItemPresetModel::class);

        return $this->db->runQuery($queryData);
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
            ->from(ItemPresetModel::TABLE)
            ->cols(ItemPresetModel::getCols());

        return $this->db->runQuery(QueryData::buildWithMapper($query, ItemPresetModel::class));
    }

    /**
     * Deletes all the items for given ids
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $itemPresetIds): QueryResult
    {
        if (count($itemPresetIds) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(ItemPresetModel::TABLE)
            ->where('id IN (:ids)', ['ids' => $itemPresetIds]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while removing the permissions'));

        return $this->db->runQuery($queryData);
    }


    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult<T>
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(ItemPresetModel::TABLE)
            ->cols(ItemPresetModel::getColsWithPreffix('ItemPreset'))
            ->cols([
                       'IF(userId IS NOT NULL, priority + 3, 
                        IF(userGroupId IS NOT NULL, priority + 2, 
                        IF(userProfileId IS NOT NULL, priority + 1, 0)))' => 'score',
                       'User.name' => 'userName',
                       'UserProfile.name' => 'userProfileName',
                       'UserGroup.name' => 'userGroupName'
                   ])
            ->leftJoin('User', 'ItemPreset.userId = User.id ')
            ->leftJoin('UserProfile', 'ItemPreset.userProfileId = UserProfile.id')
            ->leftJoin('UserGroup', 'ItemPreset.userGroupId = UserGroup.id')
            ->orderBy(['ItemPreset.type', 'score DESC'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where(
                'ItemPreset.type  LIKE :type 
                OR User.name LIKE :userName 
                OR UserProfile.name LIKE :userProfileName 
                OR UserGroup.name LIKE :userGroupName'
            );

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues(
                [
                    'type' => $search,
                    'userName' => $search,
                    'userProfileName' => $search,
                    'userGroupName' => $search
                ]
            );
        }

        $queryData = QueryData::build($query)->setMapClassName(ItemPresetModel::class);

        return $this->db->runQuery($queryData, true);
    }
}
