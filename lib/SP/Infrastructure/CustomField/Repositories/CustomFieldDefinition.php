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

namespace SP\Infrastructure\CustomField\Repositories;

use Exception;
use SP\DataModel\ItemSearchData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Models\CustomFieldDefinition as CustomFieldDefinitionModel;
use SP\Domain\CustomField\Ports\CustomFieldDefinitionRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class CustomFieldDefinition
 *
 * @template T of CustomFieldDefinitionModel
 */
final class CustomFieldDefinition extends BaseRepository implements CustomFieldDefinitionRepository
{
    use RepositoryItemTrait;

    public const TABLE = 'CustomFieldDefinition';

    /**
     * Creates an item
     *
     * @param CustomFieldDefinitionModel $customFieldDefinition
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(CustomFieldDefinitionModel $customFieldDefinition): QueryResult
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into(self::TABLE)
            ->cols(CustomFieldDefinitionModel::getCols(['id']))
            ->bindValues([
                             'name' => $customFieldDefinition->getName(),
                             'moduleId' => $customFieldDefinition->getModuleId(),
                             'required' => $customFieldDefinition->getRequired(),
                             'help' => $customFieldDefinition->getHelp(),
                             'showInList' => $customFieldDefinition->getShowInList(),
                             'typeId' => $customFieldDefinition->getTypeId(),
                             'isEncrypted' => $customFieldDefinition->getIsEncrypted(),
                         ]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the custom field'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Updates an item
     *
     * @param CustomFieldDefinitionModel $customFieldDefinition
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(CustomFieldDefinitionModel $customFieldDefinition): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols($customFieldDefinition->toArray())
            ->where('id = :id')
            ->bindValues(
                [
                    'name' => $customFieldDefinition->getName(),
                    'moduleId' => $customFieldDefinition->getModuleId(),
                    'required' => $customFieldDefinition->getRequired(),
                    'help' => $customFieldDefinition->getHelp(),
                    'showInList' => $customFieldDefinition->getShowInList(),
                    'typeId' => $customFieldDefinition->getTypeId(),
                    'isEncrypted' => $customFieldDefinition->getIsEncrypted(),
                    'id' => $customFieldDefinition->getId()
                ]
            );

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the custom field'));
        return $this->db->doQuery($queryData)->getAffectedNumRows();
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
            ->from(self::TABLE)
            ->cols(CustomFieldDefinitionModel::getCols())
            ->where('id = :id')
            ->bindValues(['id' => $id])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, CustomFieldDefinitionModel::class);

        return $this->db->doSelect($queryData);
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
            ->from(self::TABLE)
            ->cols(CustomFieldDefinitionModel::getCols())
            ->orderBy(['moduleId ASC']);

        return $this->db->doSelect(QueryData::buildWithMapper($query, CustomFieldDefinitionModel::class));
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
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
            ->from(self::TABLE)
            ->where('id IN (:ids)')
            ->bindValues(['ids' => $ids]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while removing the custom fields'));

        return $this->db->doQuery($queryData);
    }

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
            ->from(self::TABLE)
            ->where('id = :id')
            ->bindValues(['id' => $id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while removing the custom field'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(sprintf('%s AS CF_Definition', self::TABLE))
            ->innerJoin('CustomFieldType AS CF_Type', 'CF_Type.id = CustomFieldDefinition.typeId')
            ->cols(CustomFieldDefinitionModel::getColsWithPreffix('CF_Definition'))
            ->orderBy(['CF_Definition.moduleId ASC'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('name LIKE :name OR description LIKE :description');

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues(['name' => $search, 'description' => $search]);
        }

        $queryData = QueryData::build($query)->setMapClassName(CustomFieldDefinitionModel::class);

        return $this->db->doSelect($queryData, true);
    }
}
