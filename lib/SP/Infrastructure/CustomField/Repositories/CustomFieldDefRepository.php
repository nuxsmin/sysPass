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
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldDefinitionData;
use SP\DataModel\ItemSearchData;
use SP\Domain\CustomField\Ports\CustomFieldDefRepositoryInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class CustomFieldDefRepository
 *
 * @package SP\Infrastructure\CustomField\Repositories
 */
final class CustomFieldDefRepository extends Repository implements CustomFieldDefRepositoryInterface
{
    use RepositoryItemTrait;

    private CustomFieldDefCollection $customFieldDefCollection;

    /**
     * Creates an item
     *
     * @param  CustomFieldDefinitionData  $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData): int
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
            $itemData->getisEncrypted(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while creating the custom field'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Updates an item
     *
     * @param  CustomFieldDefinitionData  $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update($itemData): int
    {
        if ($this->customFieldDefCollection->exists($itemData->getId())) {
            $this->customFieldDefCollection->remove($itemData->getId());
        }

        $query = /** @lang SQL */
            'UPDATE CustomFieldDefinition 
              SET `name` = ?, 
              moduleId = ?, 
              required = ?, 
              `help` = ?, 
              showInList = ?, 
              typeId = ?, 
              isEncrypted = ?
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
            $itemData->getId(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the custom field'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return CustomFieldDefinitionData
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): CustomFieldDefinitionData
    {
        if ($this->customFieldDefCollection->exists($id)) {
            return $this->customFieldDefCollection->get($id);
        }

        $query = /** @lang SQL */
            'SELECT id, 
              `name`, 
              moduleId, 
              required, 
              `help`, 
              showInList, 
              typeId, 
              isEncrypted
              FROM CustomFieldDefinition
              WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldDefinitionData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);

        $result = $this->db->doSelect($queryData);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Custom field not found'));
        }

        $this->customFieldDefCollection->set($id, $result->getData());

        return $result->getData();
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
        $query = /** @lang SQL */
            'SELECT id, `name`, moduleId, required, `help`, showInList, isEncrypted, typeId
              FROM CustomFieldDefinition
              ORDER BY moduleId';

        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldDefinitionData::class);
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param  array  $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids): QueryResult
    {
        if (count($ids) === 0) {
            return new QueryResult();
        }

        $query = /** @lang SQL */
            'SELECT id, `name`, moduleId, required, `help`, showInList, typeId, isEncrypted
              FROM CustomFieldDefinition
              WHERE id IN ('.$this->buildParamsFromArray($ids).') 
              ORDER BY id';

        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldDefinitionData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doSelect($queryData);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param  array  $ids
     *
     * @return int
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        $query = /** @lang SQL */
            'DELETE FROM CustomFieldDefinition WHERE id IN ('.$this->buildParamsFromArray($ids).')';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while removing the custom fields'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param  int  $id
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete(int $id): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM CustomFieldDefinition WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while removing the custom field'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     */
    public function checkInUse(int $id): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param  mixed  $itemData
     */
    public function checkDuplicatedOnUpdate($itemData): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param  mixed  $itemData
     */
    public function checkDuplicatedOnAdd($itemData): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldDefinitionData::class);
        $queryData->setSelect(
            'CFD.id, CFD.name, CFD.moduleId, CFD.required, CFD.help, CFD.showInList, CFD.typeId, CFD.isEncrypted, CFT.name AS typeName'
        );
        $queryData->setFrom('CustomFieldDefinition CFD INNER JOIN CustomFieldType CFT ON CFD.typeId = CFT.id');
        $queryData->setOrder('CFD.moduleId');

        if (!empty($itemSearchData->getSeachString())) {
            $queryData->setWhere('CFD.name LIKE ? OR CFT.name LIKE ?');

            $search = '%'.$itemSearchData->getSeachString().'%';
            $queryData->addParams([$search, $search]);
        }

        $queryData->setLimit(
            '?,?',
            [$itemSearchData->getLimitStart(), $itemSearchData->getLimitCount()]
        );

        return $this->db->doSelect($queryData, true);
    }

    /**
     * Resets the custom fields collection cache
     */
    public function resetCollection(): void
    {
        $this->customFieldDefCollection->clear();
    }

    protected function initialize(): void
    {
        $this->customFieldDefCollection = new CustomFieldDefCollection();
    }
}
