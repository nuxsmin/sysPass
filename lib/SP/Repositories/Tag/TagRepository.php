<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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
            throw new SPException(SPException::SP_INFO, __u('Etiqueta duplicada'));
        }

        $query = /** @lang SQL */
            'INSERT INTO Tag SET name = ?, `hash` = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getHash());
        $Data->setOnErrorMessage(__u('Error al crear etiqueta'));

        DbWrapper::getQuery($Data, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $query = /** @lang SQL */
            'SELECT id FROM Tag WHERE `hash` = ?';
        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getTagHash());

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
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
            throw new SPException(SPException::SP_INFO, __u('Etiqueta duplicada'));
        }

        $query = /** @lang SQL */
            'UPDATE Tag SET name = ?, `hash` = ? WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getHash());
        $Data->addParam($itemData->getId());
        $Data->setOnErrorMessage(__u('Error al actualizar etiqueta'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $query = /** @lang SQL */
            'SELECT hash FROM Tag WHERE `hash` = ? AND id <> ?';
        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getTagHash());
        $Data->addParam($itemData->getTagId());

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT id, name FROM Tag WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setMapClassName(TagData::class);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return TagData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT id, name, `hash` FROM Tag ORDER BY name';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName(TagData::class);

        return DbWrapper::getResultsArray($Data, $this->db);
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

        $Data = new QueryData();
        $Data->setMapClassName(TagData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return void
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids)
    {
        foreach ($ids as $id) {
            $this->delete($id);
        }
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
        $query = /** @lang SQL */
            'DELETE FROM Tag WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar etiqueta'));

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows();
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
        $query = /** @lang SQL */
            'SELECT tagId FROM AccountToTag WHERE tagId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setSelect('id, name');
        $Data->setFrom('Tag');
        $Data->setOrder('name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('name LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}