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

use SP\Repositories\NoSuchItemException;
use SP\Repositories\Plugin\PluginDataModel;
use SP\Repositories\Plugin\PluginDataRepository;
use SP\Services\Service;

/**
 * Class PluginDataService
 *
 * @package SP\Services\Plugin
 */
final class PluginDataService extends Service
{
    /**
     * @var PluginDataRepository
     */
    protected $pluginRepository;

    /**
     * Creates an item
     *
     * @param PluginDataModel $itemData
     *
     * @return \SP\Storage\Database\QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(PluginDataModel $itemData)
    {
        $itemData->setData(serialize($itemData->getData()));

        return $this->pluginRepository->create($itemData);
    }

    /**
     * Updates an item
     *
     * @param PluginDataModel $itemData
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(PluginDataModel $itemData)
    {
        $itemData->setData(serialize($itemData->getData()));

        return $this->pluginRepository->update($itemData);
    }

    /**
     * Returns the item for given plugin and id
     *
     * @param string $name
     * @param int    $id
     *
     * @return PluginDataModel
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getByItemId(string $name, int $id)
    {
        $result = $this->pluginRepository->getByItemId($name, $id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Plugin\'s data not found'), NoSuchItemException::INFO);
        }

        return $result->getData();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return PluginDataModel[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws NoSuchItemException
     */
    public function getById($id)
    {
        $result = $this->pluginRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Plugin\'s data not found'), NoSuchItemException::INFO);
        }

        return $result->getDataAsArray();
    }

    /**
     * Returns all the items
     *
     * @return PluginDataModel[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAll()
    {
        return $this->pluginRepository->getAll()->getDataAsArray();
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        if ($this->pluginRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Plugin\'s data not found'), NoSuchItemException::INFO);
        }
    }

    /**
     * Deletes an item
     *
     * @param string $name
     * @param int    $itemId
     *
     * @return void
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByItemId(string $name, int $itemId)
    {
        if ($this->pluginRepository->deleteByItemId($name, $itemId) === 0) {
            throw new NoSuchItemException(__u('Plugin\'s data not found'), NoSuchItemException::INFO);
        }
    }

    protected function initialize()
    {
        $this->pluginRepository = $this->dic->get(PluginDataRepository::class);
    }
}