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

namespace SP\Repositories\CustomField;

use SP\Core\Exceptions\QueryException;
use SP\DataModel\CustomFieldData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class CustomFieldRepository
 *
 * @package SP\Services
 */
class CustomFieldRepository extends Repository implements RepositoryItemInterface
{
    /**
     * Updates an item
     *
     * @param CustomFieldData $itemData
     * @return bool
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
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

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getData());
        $Data->addParam($itemData->getKey());
        $Data->addParam($itemData->getModuleId());
        $Data->addParam($itemData->getId());
        $Data->addParam($itemData->getDefinitionId());

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Comprueba si el elemento tiene campos personalizados con datos
     *
     * @param CustomFieldData $itemData
     * @return bool
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function checkExists($itemData)
    {
        $query = /** @lang SQL */
            'SELECT id
            FROM CustomFieldData
            WHERE moduleId = ?
            AND itemId = ?
            AND definitionId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getModuleId());
        $Data->addParam($itemData->getId());
        $Data->addParam($itemData->getDefinitionId());

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() >= 1;
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Creates an item
     *
     * @param CustomFieldData $itemData
     * @return bool
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function create($itemData)
    {
        $query = /** @lang SQL */
            'INSERT INTO CustomFieldData SET itemId = ?, moduleId = ?, definitionId = ?, `data` = ?, `key` = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getId());
        $Data->addParam($itemData->getModuleId());
        $Data->addParam($itemData->getDefinitionId());
        $Data->addParam($itemData->getData());
        $Data->addParam($itemData->getKey());

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param int $id
     * @param int $moduleId
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function deleteCustomFieldData($id, $moduleId)
    {
        $query = /** @lang SQL */
            'DELETE FROM CustomFieldData
            WHERE itemId = ?
            AND moduleId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->addParam($moduleId);

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return void
     */
    public function getById($id)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Returns all the items
     *
     * @return CustomFieldData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT * FROM CustomFieldData';

        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldData::class);
        $queryData->setQuery($query);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return void
     */
    public function getByIdBatch(array $ids)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return void
     */
    public function deleteByIdBatch(array $ids)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return void
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Returns the module's item for given id
     *
     * @param $moduleId
     * @param $itemId
     * @return array
     */
    public function getForModuleById($moduleId, $itemId)
    {
        $query = /** @lang SQL */
            'SELECT CFD.name AS definitionName,
            CFD.id AS definitionId,
            CFD.moduleId,
            CFD.required,
            CFD.showInList,
            CFD.help,
            CFD2.data,
            CFD2.key,
            CFT.id AS typeId,
            CFT.name AS typeName,
            CFT.text AS typeText
            FROM CustomFieldDefinition CFD
            LEFT JOIN CustomFieldData CFD2 ON CFD2.definitionId = CFD.id
            INNER JOIN CustomFieldType CFT ON CFT.id = CFD.typeId
            WHERE CFD.moduleId = ?
            AND (CFD2.itemId = ? OR CFD2.definitionId IS NULL) 
            ORDER BY CFD.id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($moduleId);
        $Data->addParam($itemId);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     * @return void
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     * @return void
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new \RuntimeException('Unimplemented');
    }
}