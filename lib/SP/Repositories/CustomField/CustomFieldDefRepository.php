<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldDefinitionData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\Database\QueryData;

/**
 * Class CustomFieldDefRepository
 *
 * @package SP\Repositories\CustomField
 */
class CustomFieldDefRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;
    /**
     * @var CustomFieldDefCollection
     */
    private $customFieldDefCollection;

    /**
     * Creates an item
     *
     * @param CustomFieldDefinitionData $itemData
     *
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        $query = /** @lang SQL */
            'INSERT INTO CustomFieldDefinition SET `name` = ?, moduleId = ?, required = ?, `help` = ?, showInList = ?, typeId = ?, isEncrypted = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getModuleId(),
            $itemData->getRequired(),
            $itemData->getHelp(),
            $itemData->getShowInList(),
            $itemData->getTypeId(),
            $itemData->getisEncrypted()
        ]);
        $queryData->setOnErrorMessage(__u('Error al crear el campo personalizado'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Updates an item
     *
     * @param CustomFieldDefinitionData $itemData
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        $query = /** @lang SQL */
            'UPDATE CustomFieldDefinition 
              SET `name` = ?, moduleId = ?, required = ?, `help` = ?, showInList = ?, typeId = ?, isEncrypted = ?, field = NULL 
              WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getModuleId(),
            $itemData->getRequired(),
            $itemData->getHelp(),
            $itemData->getShowInList(),
            $itemData->getTypeId(),
            $itemData->getisEncrypted(),
            $itemData->getId()
        ]);
        $queryData->setOnErrorMessage(__u('Error al actualizar el campo personalizado'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return CustomFieldDefinitionData
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getById($id)
    {
        if ($this->customFieldDefCollection->exists($id)) {
            return $this->customFieldDefCollection->get($id);
        }

        $query = /** @lang SQL */
            'SELECT id, `name`, moduleId, required, `help`, showInList, typeId, isEncrypted
              FROM CustomFieldDefinition
              WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldDefinitionData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);

        $result = $this->db->doSelect($queryData)->getData();
        $this->customFieldDefCollection->set($id, $result);

        return $result;
    }

    /**
     * Returns all the items
     *
     * @return CustomFieldDefinitionData[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT id, `name`, moduleId, required, `help`, showInList, isEncrypted
              FROM CustomFieldDefinition
              ORDER BY moduleId';

        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldDefinitionData::class);
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData)->getDataAsArray();
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return array
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT id, `name`, moduleId, required, `help`, showInList, typeId, isEncrypted
              FROM CustomFieldDefinition
              WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldDefinitionData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doSelect($queryData)->getData();
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'DELETE FROM CustomFieldDefinition WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($ids);
        $queryData->setOnErrorMessage(__u('Error al eliminar los campos personalizados'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM CustomFieldDefinition WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar el campo personalizado'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     *
     * @return \SP\Storage\Database\QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function search(ItemSearchData $SearchData)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldDefinitionData::class);
        $queryData->setSelect('CFD.id, CFD.name, CFD.moduleId, CFD.required, CFD.help, CFD.showInList, CFD.typeId, CFD.isEncrypted, CFT.name AS typeName');
        $queryData->setFrom('CustomFieldDefinition CFD INNER JOIN CustomFieldType CFT ON CFD.typeId = CFT.id');
        $queryData->setOrder('CFD.moduleId');

        $queryData->setLimit('?,?');
        $queryData->addParam($SearchData->getLimitStart());
        $queryData->addParam($SearchData->getLimitCount());

        return $this->db->doSelect($queryData, true);
    }

    protected function initialize()
    {
        $this->customFieldDefCollection = new CustomFieldDefCollection();
    }
}