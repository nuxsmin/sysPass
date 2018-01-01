<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Tag;


use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\TagData;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class TagService
 *
 * @package SP\Services\Tag
 */
class TagService extends Service implements ServiceItemInterface
{
    use ServiceItemTrait;

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
            'INSERT INTO tags SET tag_name = ?, tag_hash = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getTagName());
        $Data->addParam($itemData->getTagHash());
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
            'SELECT tag_id FROM tags WHERE tag_hash = ?';
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
            'UPDATE tags SET tag_name = ?, tag_hash = ? WHERE tag_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getTagName());
        $Data->addParam($itemData->getTagHash());
        $Data->addParam($itemData->getTagId());
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
            'SELECT tag_hash FROM tags WHERE tag_hash = ? AND tag_id <> ?';
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
            'SELECT tag_id, tag_name FROM tags WHERE tag_id = ? LIMIT 1';

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
            'SELECT tag_id, tag_name, tag_hash FROM tags ORDER BY tag_name';

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
            'SELECT tag_id, tag_name FROM tags WHERE tag_id IN (' . $this->getParamsFromArray($ids) . ')';

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
     * @return TagService
     * @throws SPException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM tags WHERE tag_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar etiqueta'));

        DbWrapper::getQuery($Data, $this->db);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __u('Etiqueta no encontrada'));
        }

        return $this;
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
            'SELECT acctag_tagId FROM accTags WHERE acctag_tagId = ?';

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
        $Data->setSelect('tag_id, tag_name');
        $Data->setFrom('tags');
        $Data->setOrder('tag_name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('tag_name LIKE ?');

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