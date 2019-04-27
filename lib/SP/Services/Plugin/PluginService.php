<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Plugin;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Plugin\PluginModel;
use SP\Repositories\Plugin\PluginRepository;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database\QueryResult;

/**
 * Class PluginService
 *
 * @package SP\Services\Plugin
 */
final class PluginService extends Service
{
    /**
     * @var PluginRepository
     */
    protected $pluginRepository;

    /**
     * Creates an item
     *
     * @param PluginModel $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(PluginModel $itemData)
    {
        return $this->pluginRepository->create($itemData)->getLastId();
    }

    /**
     * Updates an item
     *
     * @param PluginModel $itemData
     *
     * @return mixed
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PluginModel $itemData)
    {
        return $this->pluginRepository->update($itemData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return PluginModel
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById($id)
    {
        $result = $this->pluginRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), NoSuchItemException::INFO);
        }

        return $result->getData();
    }

    /**
     * Returns all the items
     *
     * @return PluginModel[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll()
    {
        return $this->pluginRepository->getAll()->getDataAsArray();
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return PluginModel[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids)
    {
        return $this->pluginRepository->getByIdBatch($ids)->getDataAsArray();
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids)
    {
        if ($this->pluginRepository->deleteByIdBatch($ids) !== count($ids)) {
            throw new ServiceException(__u('Error while deleting the plugins'));
        }
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($id)
    {
        if ($this->pluginRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), NoSuchItemException::INFO);
        }
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->pluginRepository->search($itemSearchData);
    }

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param string $name
     *
     * @return PluginModel
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName($name)
    {
        $result = $this->pluginRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), NoSuchItemException::INFO);
        }

        return $result->getData();
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param $id
     * @param $enabled
     *
     * @return void
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleEnabled($id, $enabled)
    {
        if ($this->pluginRepository->toggleEnabled($id, $enabled) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), NoSuchItemException::INFO);
        }
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param $name
     * @param $enabled
     *
     * @return void
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleEnabledByName($name, $enabled)
    {
        if ($this->pluginRepository->toggleEnabledByName($name, $enabled) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), NoSuchItemException::INFO);
        }
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param $id
     * @param $available
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleAvailable($id, $available)
    {
        if ($this->pluginRepository->toggleAvailable($id, $available) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), NoSuchItemException::INFO);
        }
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param string $name
     * @param int    $available
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleAvailableByName($name, $available)
    {
        if ($this->pluginRepository->toggleAvailableByName($name, $available) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), NoSuchItemException::INFO);
        }
    }

    /**
     * Restablecer los datos de un plugin
     *
     * @param int $id Id del plugin
     *
     * @return bool
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function resetById($id)
    {
        if (($count = $this->pluginRepository->resetById($id)) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), NoSuchItemException::INFO);
        }

        return $count;
    }

    /**
     * Devolver los plugins activados
     *
     * @return ItemData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getEnabled()
    {
        return $this->pluginRepository->getEnabled()->getDataAsArray();
    }

    protected function initialize()
    {
        $this->pluginRepository = $this->dic->get(PluginRepository::class);
    }
}