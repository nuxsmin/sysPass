<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Repositories\CustomField;

use RuntimeException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\CustomFieldData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class CustomFieldRepository
 *
 * @package SP\Services
 */
final class CustomFieldRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Updates an item
     *
     * @param CustomFieldData $itemData
     *
     * @return bool
     * @throws QueryException
     * @throws ConstraintException
     */
    public function update($itemData)
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
            $itemData->getDefinitionId()
        ]);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Comprueba si el elemento tiene campos personalizados con datos
     *
     * @param CustomFieldData $itemData
     *
     * @return bool
     * @throws QueryException
     * @throws ConstraintException
     */
    public function checkExists($itemData)
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
            $itemData->getDefinitionId()
        ]);

        return $this->db->doSelect($queryData)->getNumRows() >= 1;
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return mixed
     */
    public function delete($id)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Creates an item
     *
     * @param CustomFieldData $itemData
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function create($itemData)
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
            $itemData->getKey()
        ]);

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param int $itemId
     * @param int $moduleId
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldData($itemId, $moduleId)
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
     * @param int $id
     * @param int $moduleId
     * @param int $definitionId
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDataForDefinition($id, $moduleId, $definitionId)
    {
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
     * @param int $definitionId
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDefinitionData($definitionId)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM CustomFieldData WHERE definitionId = ?');
        $queryData->addParam($definitionId);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Eliminar los datos de los elementos de una definición
     *
     * @param array $definitionIds
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteCustomFieldDefinitionDataBatch(array $definitionIds)
    {
        if (empty($definitionIds)) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM CustomFieldData WHERE definitionId IN (' . $this->getParamsFromArray($definitionIds) . ')');
        $queryData->setParams($definitionIds);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param int[] $ids
     * @param int   $moduleId
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDataBatch(array $ids, $moduleId)
    {
        if (empty($ids)) {
            return 0;
        }

        $query = /** @lang SQL */
            'DELETE FROM CustomFieldData
            WHERE itemId IN (' . $this->getParamsFromArray($ids) . ')
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
     * @param int $id
     *
     * @return void
     */
    public function getById($id)
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
    public function getAll()
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
    public function getAllEncrypted()
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldData::class);
        $queryData->setQuery('SELECT * FROM CustomFieldData WHERE `key` IS NOT NULL ORDER BY definitionId');

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return void
     */
    public function getByIdBatch(array $ids)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return void
     */
    public function deleteByIdBatch(array $ids)
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
    public function checkInUse($id)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     *
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Returns the module's item for given id
     *
     * @param $moduleId
     * @param $itemId
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getForModuleAndItemId($moduleId, $itemId)
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
     * @param mixed $itemData
     *
     * @return void
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     *
     * @return void
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new RuntimeException('Not implemented');
    }
}