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

namespace SP\Infrastructure\CustomField\Repositories;

use RuntimeException;
use SP\DataModel\CustomFieldData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Ports\CustomFieldRepositoryInterface;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class CustomFieldRepository
 *
 * @package SP\Domain\Common\Services
 */
final class CustomFieldRepository extends Repository implements CustomFieldRepositoryInterface
{
    use RepositoryItemTrait;

    /**
     * Updates an item
     *
     * @param  CustomFieldData  $itemData
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function update($itemData): int
    {
        $query = /** @lang SQL */
            'UPDATE CustomFieldData SET
            `data` = ?,
            `key` = ?
            WHERE moduleId = ?
            AND itemId = ?
            AND definitionId = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getData(),
            $itemData->getKey(),
            $itemData->getModuleId(),
            $itemData->getItemId(),
            $itemData->getDefinitionId(),
        ]);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Comprueba si el elemento tiene campos personalizados con datos
     *
     * @param  CustomFieldData  $itemData
     *
     * @return bool
     * @throws QueryException
     * @throws ConstraintException
     */
    public function checkExists(CustomFieldData $itemData): bool
    {
        $query = /** @lang SQL */
            'SELECT id
            FROM CustomFieldData
            WHERE moduleId = ?
            AND itemId = ?
            AND definitionId = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getModuleId(),
            $itemData->getItemId(),
            $itemData->getDefinitionId(),
        ]);

        return $this->db->doSelect($queryData)->getNumRows() >= 1;
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return void
     */
    public function delete($id): void
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Creates an item
     *
     * @param  CustomFieldData  $itemData
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function create($itemData): int
    {
        $query = /** @lang SQL */
            'INSERT INTO CustomFieldData SET itemId = ?, moduleId = ?, definitionId = ?, `data` = ?, `key` = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getItemId(),
            $itemData->getModuleId(),
            $itemData->getDefinitionId(),
            $itemData->getData(),
            $itemData->getKey(),
        ]);

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param  int  $itemId
     * @param  int  $moduleId
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldData(int $itemId, int $moduleId): int
    {
        $query = /** @lang SQL */
            'DELETE FROM CustomFieldData
            WHERE itemId = ?
            AND moduleId = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$itemId, $moduleId]);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param  int  $id
     * @param  int  $moduleId
     * @param  int|null  $definitionId
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteCustomFieldDataForDefinition(
        int $id,
        int $moduleId,
        ?int $definitionId
    ): int {
        $query = /** @lang SQL */
            'DELETE FROM CustomFieldData
            WHERE itemId = ?
            AND moduleId = ?
            AND definitionId = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$id, $moduleId, $definitionId]);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param  int  $definitionId
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDefinitionData(int $definitionId): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM CustomFieldData WHERE definitionId = ?');
        $queryData->addParam($definitionId);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Eliminar los datos de los elementos de una definición
     *
     * @param  array  $definitionIds
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteCustomFieldDefinitionDataBatch(array $definitionIds): int
    {
        if (count($definitionIds) === 0) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery(
            'DELETE FROM CustomFieldData WHERE definitionId IN ('.$this->buildParamsFromArray($definitionIds).')'
        );
        $queryData->setParams($definitionIds);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param  int[]  $ids
     * @param  int  $moduleId
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDataBatch(array $ids, int $moduleId): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        $query = /** @lang SQL */
            'DELETE FROM CustomFieldData
            WHERE itemId IN ('.$this->buildParamsFromArray($ids).')
            AND moduleId = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams($ids);
        $queryData->addParam($moduleId);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return void
     */
    public function getById(int $id): void
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Returns all the items
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldData::class);
        $queryData->setQuery('SELECT * FROM CustomFieldData');

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items that were encryptes
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAllEncrypted(): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldData::class);
        $queryData->setQuery('SELECT * FROM CustomFieldData WHERE `key` IS NOT NULL ORDER BY definitionId');

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param  array  $ids
     *
     * @return void
     */
    public function getByIdBatch(array $ids): QueryResult
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Deletes all the items for given ids
     *
     * @param  array  $ids
     *
     * @return void
     */
    public function deleteByIdBatch(array $ids): int
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     *
     * @return void
     */
    public function checkInUse(int $id): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return void
     */
    public function search(ItemSearchData $itemSearchData): void
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Returns the module's item for given id
     *
     * @param  int  $moduleId
     * @param  int|null  $itemId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForModuleAndItemId(int $moduleId, ?int $itemId): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT CFD.name AS definitionName,
            CFD.id AS definitionId,
            CFD.moduleId,
            CFD.required,
            CFD.showInList,
            CFD.help,
            CFD.isEncrypted,
            CFD2.data,
            CFD2.key,
            CFT.id AS typeId,
            CFT.name AS typeName,
            CFT.text AS typeText
            FROM CustomFieldDefinition CFD
            LEFT JOIN CustomFieldData CFD2 ON CFD2.definitionId = CFD.id AND CFD2.itemId = ?
            INNER JOIN CustomFieldType CFT ON CFT.id = CFD.typeId
            WHERE CFD.moduleId = ?
            ORDER BY CFD.required DESC, CFT.text';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$itemId, $moduleId]);

        return $this->db->doSelect($queryData);
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param  mixed  $itemData
     *
     * @return void
     */
    public function checkDuplicatedOnUpdate($itemData): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param  mixed  $itemData
     *
     * @return void
     */
    public function checkDuplicatedOnAdd($itemData): bool
    {
        throw new RuntimeException('Not implemented');
    }
}
