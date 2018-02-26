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

namespace SP\Repositories\Tag;

use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\TagData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class TagRepository
 *
 * @package SP\Repositories\Tag
 */
class TagRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param TagData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(__u('Etiqueta duplicada'), SPException::INFO);
        }

        $queryData = new QueryData();
        $queryData->setQuery('INSERT INTO Tag SET name = ?, `hash` = ?');
        $queryData->addParam($itemData->getName());
        $queryData->addParam($this->makeItemHash($itemData->getName(), $this->db->getDbHandler()));
        $queryData->setOnErrorMessage(__u('Error al crear etiqueta'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param TagData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id FROM Tag WHERE `hash` = ?');
        $queryData->addParam($this->makeItemHash($itemData->getName(), $this->db->getDbHandler()));

        DbWrapper::getQuery($queryData);

        return $this->db->getNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param TagData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(__u('Etiqueta duplicada'), SPException::INFO);
        }

        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Tag SET name = ?, `hash` = ? WHERE id = ? LIMIT 1');
        $queryData->addParam($itemData->getName());
        $queryData->addParam($this->makeItemHash($itemData->getName(), $this->db->getDbHandler()));
        $queryData->addParam($itemData->getId());
        $queryData->setOnErrorMessage(__u('Error al actualizar etiqueta'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param TagData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT hash FROM Tag WHERE `hash` = ? AND id <> ?');
        $queryData->addParam($this->makeItemHash($itemData->getName(), $this->db->getDbHandler()));
        $queryData->addParam($itemData->getId());

        DbWrapper::getQuery($queryData);

        return $this->db->getNumRows() > 0;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, name FROM Tag WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setMapClassName(TagData::class);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return TagData[]
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, name, `hash` FROM Tag ORDER BY name');
        $queryData->setMapClassName(TagData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return TagData[]
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT id, name FROM Tag WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setMapClassName(TagData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return int
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Tag WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error al eliminar etiquetas'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Tag WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar etiqueta'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkInUse($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT tagId FROM AccountToTag WHERE tagId = ?');
        $queryData->addParam($id);

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows() > 0;
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     * @return mixed
     */
    public function search(ItemSearchData $itemSearchData)
    {
        $queryData = new QueryData();
        $queryData->setSelect('id, name');
        $queryData->setFrom('Tag');
        $queryData->setOrder('name');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('name LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $queryData->addParam($search);
        }

        $queryData->setLimit('?,?');
        $queryData->addParam($itemSearchData->getLimitStart());
        $queryData->addParam($itemSearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($queryData, $this->db);

        $queryRes['count'] = $this->db->getNumRows();

        return $queryRes;
    }
}