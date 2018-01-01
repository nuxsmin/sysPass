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

namespace SP\Services\Category;


use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\DataModel\ItemSearchData;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class CategoryService
 *
 * @package SP\Services\Category
 */
class CategoryService extends Service implements ServiceItemInterface
{
    use ServiceItemTrait;

    /**
     * Creates an item
     *
     * @param CategoryData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(SPException::SP_WARNING, __u('Categoría duplicada'));
        }

        $query = /** @lang SQL */
            'INSERT INTO categories SET category_name = ?, category_description = ?, category_hash = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getCategoryName());
        $Data->addParam($itemData->getCategoryDescription());
        $Data->addParam($this->makeItemHash($itemData->getCategoryName()));
        $Data->setOnErrorMessage(__u('Error al crear la categoría'));

        DbWrapper::getQuery($Data, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param CategoryData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $query = /** @lang SQL */
            'SELECT category_id FROM categories WHERE category_hash = ? OR category_name = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($itemData->getCategoryName()));
        $Data->addParam($itemData->getCategoryName());
        $Data->addParam($itemData->getCategoryId());

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param CategoryData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(SPException::SP_WARNING, __u('Nombre de categoría duplicado'));
        }

        $query = /** @lang SQL */
            'UPDATE categories
              SET category_name = ?,
              category_description = ?,
              category_hash = ?
              WHERE category_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getCategoryName());
        $Data->addParam($itemData->getCategoryDescription());
        $Data->addParam($this->makeItemHash($itemData->getCategoryName()));
        $Data->addParam($itemData->getCategoryId());
        $Data->setOnErrorMessage(__u('Error al actualizar la categoría'));

        DbWrapper::getQuery($Data, $this->db);

        return $this;
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param CategoryData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $query = /** @lang SQL */
            'SELECT category_id FROM categories WHERE (category_hash = ? OR category_name = ?) AND category_id <> ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($itemData->getCategoryName()));
        $Data->addParam($itemData->getCategoryName());
        $Data->addParam($itemData->getCategoryId());

        DbWrapper::getQuery($Data, $this->db);

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
            'SELECT category_id, category_name, category_description FROM categories WHERE category_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setMapClassName(CategoryData::class);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return array
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT category_id, category_name, category_description, category_hash FROM categories ORDER BY category_name';

        $Data = new QueryData();
        $Data->setMapClassName(CategoryData::class);
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT category_id, category_name, category_description FROM categories WHERE category_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setParams($ids);
        $Data->setMapClassName(CategoryData::class);

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
     * @return CategoryService
     * @throws SPException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM categories WHERE category_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar la categoría'));

        DbWrapper::getQuery($Data, $this->db);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __u('Categoría no encontrada'));
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
            'SELECT account_id FROM accounts WHERE account_categoryId = ?';

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
        $Data->setSelect('category_id, category_name, category_description');
        $Data->setFrom('categories');
        $Data->setOrder('category_name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('category_name LIKE ? OR category_description LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        /** @var array $queryRes */
        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}