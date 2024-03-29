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
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Models\CustomFieldDefinition as CustomFieldDefinitionModel;
use SP\Domain\CustomField\Models\CustomFieldType as CustomFieldTypeModel;
use SP\Domain\CustomField\Ports\CustomFieldDataRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class CustomFieldData
 *
 * @template T of CustomFieldDataModel
 */
final class CustomFieldData extends BaseRepository implements CustomFieldDataRepository
{
    use RepositoryItemTrait;

    public const TABLE = 'CustomFieldData';

    /**
     * Updates an item
     *
     * @param CustomFieldDataModel $customFieldData
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function update(CustomFieldDataModel $customFieldData): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols($customFieldData->toArray(['data', 'key']))
            ->where('moduleId = :moduleId')
            ->where('itemId = :itemId')
            ->where('definitionId = :definitionId')
            ->bindValues(
                [
                    'moduleId' => $customFieldData->getModuleId(),
                    'itemId' => $customFieldData->getItemId(),
                    'definitionId' => $customFieldData->getDefinitionId(),
                ]
            );

        return $this->db->runQuery(QueryData::build($query))->getAffectedNumRows();
    }

    /**
     * Check whether the item has custom fields with data
     *
     * @param int $itemId
     * @param int $moduleId
     * @param int $definitionId
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkExists(int $itemId, int $moduleId, int $definitionId): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['itemId'])
            ->from(self::TABLE)
            ->where('moduleId = :moduleId')
            ->where('itemId = :itemId')
            ->where('definitionId = :definitionId')
            ->bindValues(
                [
                    'moduleId' => $moduleId,
                    'itemId' => $itemId,
                    'definitionId' => $definitionId,
                ]
            );

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Creates an item
     *
     * @param CustomFieldDataModel $customFieldData
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function create(CustomFieldDataModel $customFieldData): QueryResult
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into(self::TABLE)
            ->cols(CustomFieldDataModel::getCols())
            ->bindValues([
                             'itemId' => $customFieldData->getItemId(),
                             'moduleId' => $customFieldData->getModuleId(),
                             'definitionId' => $customFieldData->getDefinitionId(),
                             'data' => $customFieldData->getData(),
                             'key' => $customFieldData->getKey(),
                         ]);

        return $this->db->runQuery(QueryData::build($query));
    }

    /**
     * Delete the module's custom field data
     *
     * @param int[] $itemIds
     * @param int $moduleId
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteBatch(array $itemIds, int $moduleId): QueryResult
    {
        if (count($itemIds) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(self::TABLE)
            ->where('itemId IN (:itemIds)')
            ->where('moduleId = :moduleId')
            ->bindValues(['itemIds' => $itemIds, 'moduleId' => $moduleId]);

        return $this->db->runQuery(QueryData::build($query));
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
            ->cols(CustomFieldDataModel::getCols());

        return $this->db->runQuery(QueryData::buildWithMapper($query, CustomFieldDataModel::class));
    }

    /**
     * Returns all the items that were encrypted
     *
     * @return QueryResult<T>
     */
    public function getAllEncrypted(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(CustomFieldDataModel::getCols())
            ->where('key IS NOT NULL')
            ->orderBy(['definitionId ASC']);

        return $this->db->runQuery(QueryData::buildWithMapper($query, CustomFieldDataModel::class));
    }

    /**
     * Returns the module's item for given id
     *
     * @param int $moduleId
     * @param int|null $itemId
     *
     * @return QueryResult
     * @throws Exception
     */
    public function getForModuleAndItemId(int $moduleId, ?int $itemId): QueryResult
    {
        $cols = array_merge(
            CustomFieldDefinitionModel::getColsWithPreffix('CF_Definition', ['field', 'typeId']),
            CustomFieldDataModel::getColsWithPreffix(
                'CF_Data',
                ['id', 'moduleId', 'itemId', 'definitionId']
            ),
            CustomFieldTypeModel::getColsWithPreffix('CF_Type')
        );

        $query = $this->queryFactory
            ->newSelect()
            ->from('CustomFieldDefinition AS CF_Definition')
            ->cols($cols)
            ->leftJoin(
                'CustomFieldData AS CF_Data',
                'CF_Data.definitionId = CF_Definition.id AND CF_Data.itemId = :itemId'
            )
            ->innerJoin('CustomFieldType as CF_Type', 'CF_Type.id = CF_Definition.typeId')
            ->where('CF_Definition.moduleId = :moduleId')
            ->orderBy(['CF_Definition.required DESC', 'CF_Definition.text ASC'])
            ->bindValues([
                             'moduleId' => $moduleId,
                             'itemId' => $itemId
                         ]);

        return $this->db->runQuery(QueryData::build($query));
    }
}
