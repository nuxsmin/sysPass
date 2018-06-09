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

namespace SP\Repositories\Category;

use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class CategoryRepository
 *
 * @package SP\Repositories\Category
 */
class CategoryRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param CategoryData $itemData
     *
     * @return int
     * @throws SPException
     * @throws DuplicatedItemException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new DuplicatedItemException(__u('Categoría duplicada'), DuplicatedItemException::WARNING);
        }

        $queryData = new QueryData();
        $queryData->setQuery('INSERT INTO Category SET `name` = ?, description = ?, `hash` = ?');
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getDescription(),
            $this->makeItemHash($itemData->getName(), $this->db->getDbHandler())
        ]);
        $queryData->setOnErrorMessage(__u('Error al crear la categoría'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param CategoryData $itemData
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id FROM Category WHERE `hash` = ? OR `name` = ?');
        $queryData->setParams([
            $this->makeItemHash($itemData->getName(), $this->db->getDbHandler()),
            $itemData->getName()
        ]);

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param CategoryData $itemData
     *
     * @return mixed
     * @throws SPException
     * @throws DuplicatedItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new DuplicatedItemException(__u('Nombre de categoría duplicado'), DuplicatedItemException::WARNING);
        }

        $query = /** @lang SQL */
            'UPDATE Category
              SET `name` = ?,
              description = ?,
              `hash` = ?
              WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getDescription(),
            $this->makeItemHash($itemData->getName(), $this->db->getDbHandler()),
            $itemData->getId()
        ]);
        $queryData->setOnErrorMessage(__u('Error al actualizar la categoría'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this;
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param CategoryData $itemData
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id FROM Category WHERE (`hash` = ? OR `name` = ?) AND id <> ?');
        $queryData->setParams([
            $this->makeItemHash($itemData->getName(), $this->db->getDbHandler()),
            $itemData->getName(),
            $itemData->getId()
        ]);

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows() > 0;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return CategoryData
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(CategoryData::class);
        $queryData->setQuery('SELECT id, `name`, description FROM Category WHERE id = ? LIMIT 1');
        $queryData->addParam($id);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Returns the item for given id
     *
     * @param string $name
     *
     * @return CategoryData
     */
    public function getByName($name)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(CategoryData::class);
        $queryData->setQuery('SELECT id, `name`, description FROM Category WHERE `name` = ? OR `hash` = ? LIMIT 1');
        $queryData->setParams([
            $name,
            $this->makeItemHash($name, $this->db->getDbHandler())
        ]);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return CategoryData[]
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(CategoryData::class);
        $queryData->setQuery('SELECT id, `name`, description, `hash` FROM Category ORDER BY `name`');

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT id, `name`, description FROM Category WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setMapClassName(CategoryData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Category WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error al eliminar la categorías'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM Category WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar la categoría'));

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows();
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
        throw new \RuntimeException('Not implemented');
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
        $queryData = new QueryData();
        $queryData->setSelect('id, name, description');
        $queryData->setFrom('Category');
        $queryData->setOrder('name');

        if ($SearchData->getSeachString() !== '') {
            $queryData->setWhere('name LIKE ? OR description LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit('?,?');
        $queryData->addParam($SearchData->getLimitStart());
        $queryData->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        /** @var array $queryRes */
        $queryRes = DbWrapper::getResultsArray($queryData, $this->db);

        $queryRes['count'] = $queryData->getQueryNumRows();

        return $queryRes;
    }
}