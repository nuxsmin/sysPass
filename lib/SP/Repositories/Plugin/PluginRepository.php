<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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

namespace SP\Repositories\Plugin;

use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PluginData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class PluginRepository
 *
 * @package SP\Repositories\Plugin
 */
class PluginRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param PluginData $itemData
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        $query = /** @lang SQL */
            'INSERT INTO Plugin SET `name` = ?, `data` = ?, enabled = ?, available = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($itemData->getName());
        $queryData->addParam($itemData->getData());
        $queryData->addParam($itemData->getEnabled());
        $queryData->addParam($itemData->getAvailable());
        $queryData->setOnErrorMessage(__u('Error al crear el plugin'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Updates an item
     *
     * @param PluginData $itemData
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        $query = /** @lang SQL */
            'UPDATE Plugin
              SET `name` = ?,
              `data` = ?,
              enabled = ?,
              available = ?
              WHERE `name` = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($itemData->getName());
        $queryData->addParam($itemData->getData());
        $queryData->addParam($itemData->getEnabled());
        $queryData->addParam($itemData->getAvailable());
        $queryData->addParam($itemData->getName());
        $queryData->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return PluginData
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT id,
            `name`,
            `data`,
            enabled,
            available 
            FROM Plugin 
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setMapClassName(PluginData::class);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return PluginData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT id,
            `name`,
            enabled,
            available 
            FROM Plugin 
            ORDER BY `name`';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setMapClassName(PluginData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return PluginData[]
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT id,
            `name`,
            enabled,
            available 
            FROM Plugin 
            WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams($ids);
        $queryData->setMapClassName(PluginData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return void
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Plugin WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar el plugin'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return void
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     * @return void
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     * @return void
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new \RuntimeException('Not implemented');
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
        $queryData->setSelect('id, name, enabled, available');
        $queryData->setFrom('Plugin');
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

        /** @var array $queryRes */
        $queryRes = DbWrapper::getResultsArray($queryData, $this->db);

        $queryRes['count'] = $queryData->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param string $name
     * @return PluginData
     */
    public function getByName($name)
    {
        $query = /** @lang SQL */
            'SELECT id,
            `name`,
            `data`,
            enabled,
            available 
            FROM Plugin 
            WHERE `name` = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($name);
        $queryData->setMapClassName(PluginData::class);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param $id
     * @param $enabled
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function toggleEnabled($id, $enabled)
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Plugin SET enabled = ? WHERE id = ? LIMIT 1');
        $queryData->addParam($enabled);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param $name
     * @param $enabled
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function toggleEnabledByName($name, $enabled)
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Plugin SET enabled = ? WHERE name = ? LIMIT 1');
        $queryData->addParam($enabled);
        $queryData->addParam($name);
        $queryData->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param $id
     * @param $available
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function toggleAvailable($id, $available)
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Plugin SET available = ? WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->addParam($available);
        $queryData->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param $name
     * @param $available
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function toggleAvailableByName($name, $available)
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Plugin SET available = ? WHERE `name` = ? LIMIT 1');
        $queryData->addParam($available);
        $queryData->addParam($name);
        $queryData->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Restablecer los datos de un plugin
     *
     * @param int $id Id del plugin
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function resetById($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Plugin SET `data` = NULL WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Devolver los plugins activados
     *
     * @return ItemData[]
     */
    public function getEnabled()
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, name FROM Plugin WHERE enabled = 1');
        $queryData->setMapClassName(ItemData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }
}