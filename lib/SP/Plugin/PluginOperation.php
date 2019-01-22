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

namespace SP\Plugin;

use SP\Repositories\Plugin\PluginDataModel;
use SP\Services\Plugin\PluginDataService;

/**
 * Class PluginOperation
 *
 * @package SP\Plugin
 */
final class PluginOperation
{
    /**
     * @var PluginDataService
     */
    private $pluginDataService;
    /**
     * @var string
     */
    private $name;

    /**
     * PluginOperation constructor.
     *
     * @param PluginDataService $pluginDataService
     * @param string            $name
     */
    public function __construct(PluginDataService $pluginDataService, string $name)
    {
        $this->pluginDataService = $pluginDataService;
        $this->name = $name;
    }

    /**
     * @param int    $itemId
     * @param string $data
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(int $itemId, string $data)
    {
        $itemData = new PluginDataModel();
        $itemData->setName($this->name);
        $itemData->setItemId($itemId);
        $itemData->setData($data);

        $this->pluginDataService->create($itemData);
    }

    /**
     * @param int    $itemId
     * @param string $data
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(int $itemId, string $data)
    {
        $itemData = new PluginDataModel();
        $itemData->setName($this->name);
        $itemData->setItemId($itemId);
        $itemData->setData($data);

        return $this->pluginDataService->update($itemData);
    }

    /**
     * @param int $itemId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function delete(int $itemId)
    {
        $this->pluginDataService->deleteByItemId($this->name, $itemId);
    }

    /**
     * @param int         $itemId
     * @param string|null $class
     *
     * @return mixed|null
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function get(int $itemId, string $class = null)
    {
        return $this->pluginDataService->getByItemId($this->name, $itemId)->hydrate($class);
    }
}