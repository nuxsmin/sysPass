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

namespace SP\Repositories\Plugin;

use SP\Core\Exceptions\SPException;
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
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        $query = /** @lang SQL */
            'INSERT INTO Plugin SET name = ?, data = ?, enabled = ?, available = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getData());
        $Data->addParam($itemData->getEnabled());
        $Data->addParam($itemData->getAvailable());
        $Data->setOnErrorMessage(__u('Error al crear el plugin'));

        DbWrapper::getQuery($Data, $this->db);

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
              SET name = ?,
              data = ?,
              enabled = ?,
              available = ?
              WHERE name = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getData());
        $Data->addParam($itemData->getEnabled());
        $Data->addParam($itemData->getAvailable());
        $Data->addParam($itemData->getName());
        $Data->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($Data, $this->db);
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
            'SELECT id,
            name,
            data,
            enabled,
            available 
            FROM Plugin 
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(PluginData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResults($Data, $this->db);
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
            name,
            enabled,
            available 
            FROM Plugin 
            ORDER BY name';

        $Data = new QueryData();
        $Data->setMapClassName(PluginData::class);
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
            'SELECT id,
            name,
            enabled,
            available 
            FROM Plugin 
            WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(PluginData::class);
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
     * @return PluginRepository
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM Plugin WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar el plugin'));

        DbWrapper::getQuery($Data, $this->db);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __u('Plugin no encontrado'));
        }

        return $this;
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
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setSelect('id, name, enabled, available');
        $Data->setFrom('Plugin');
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

        /** @var array $queryRes */
        $queryRes = DbWrapper::getResultsArray($Data);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param $name int
     * @return mixed
     */
    public function getByName($name)
    {
        $query = /** @lang SQL */
            'SELECT id,
            name,
            data,
            enabled,
            available 
            FROM Plugin 
            WHERE name = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(PluginData::class);
        $Data->setQuery($query);
        $Data->addParam($name);

        return DbWrapper::getResults($Data, $this->db);
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
        $query = /** @lang SQL */
            'UPDATE Plugin
              SET enabled = ?
              WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($enabled);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($Data, $this->db);
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
        $query = /** @lang SQL */
            'UPDATE Plugin
              SET enabled = ?
              WHERE name = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($enabled);
        $Data->addParam($name);
        $Data->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($Data, $this->db);
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
        $query = /** @lang SQL */
            'UPDATE Plugin
              SET available = ?
              WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->addParam($available);
        $Data->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($Data, $this->db);
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
        $query = /** @lang SQL */
            'UPDATE Plugin
              SET available = ?
              WHERE name = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($available);
        $Data->addParam($name);
        $Data->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($Data, $this->db);
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
        $query = /** @lang SQL */
            'UPDATE Plugin
              SET data = NULL 
              WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al actualizar el plugin'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Devolver los plugins activados
     *
     * @return array
     */
    public function getEnabled()
    {
        $query = /** @lang SQL */
            'SELECT name FROM Plugin WHERE enabled = 1';

        $Data = new QueryData();
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }
}