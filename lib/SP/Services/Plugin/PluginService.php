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

namespace SP\Services\Plugin;

use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PluginData;
use SP\Repositories\Plugin\PluginRepository;
use SP\Services\Service;
use SP\Services\ServiceException;

/**
 * Class PluginService
 * @package SP\Services\Plugin
 */
class PluginService extends Service
{
    /**
     * @var PluginRepository
     */
    protected $pluginRepository;

    /**
     * Creates an item
     *
     * @param PluginData $itemData
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(PluginData $itemData)
    {
        return $this->pluginRepository->create($itemData);
    }

    /**
     * Updates an item
     *
     * @param PluginData $itemData
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(PluginData $itemData)
    {
        return $this->pluginRepository->update($itemData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return PluginData
     */
    public function getById($id)
    {
        return $this->pluginRepository->getById($id);
    }

    /**
     * Returns all the items
     *
     * @return PluginData[]
     */
    public function getAll()
    {
        return $this->pluginRepository->getAll();
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return PluginData[]
     */
    public function getByIdBatch(array $ids)
    {
        return $this->pluginRepository->getByIdBatch($ids);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function deleteByIdBatch(array $ids)
    {
        $this->pluginRepository->deleteByIdBatch($ids);
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        if ($this->pluginRepository->delete($id) === 0) {
            throw new ServiceException(__u('Plugin no encontrado'), ServiceException::INFO);
        }
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     * @return mixed
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->pluginRepository->search($itemSearchData);
    }

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param string $name
     * @return PluginData
     */
    public function getByName($name)
    {
        return $this->pluginRepository->getByName($name);
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
        return $this->pluginRepository->toggleEnabled($id, $enabled);
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
        return $this->pluginRepository->toggleEnabledByName($name, $enabled);
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
        return $this->pluginRepository->toggleAvailable($id, $available);
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
        return $this->pluginRepository->toggleAvailableByName($name, $available);
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
        return $this->pluginRepository->resetById($id);
    }

    /**
     * Devolver los plugins activados
     *
     * @return ItemData[]
     */
    public function getEnabled()
    {
        return $this->pluginRepository->getEnabled();
    }

    protected function initialize()
    {
        $this->pluginRepository = $this->dic->get(PluginRepository::class);
    }
}