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

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Plugin\PluginDataModel;
use SP\Services\Plugin\PluginDataService;
use SP\Services\ServiceException;

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
    private $pluginName;

    /**
     * PluginOperation constructor.
     *
     * @param PluginDataService $pluginDataService
     * @param string            $pluginName
     */
    public function __construct(PluginDataService $pluginDataService, string $pluginName)
    {
        $this->pluginDataService = $pluginDataService;
        $this->pluginName = $pluginName;
    }

    /**
     * @param int   $itemId
     * @param mixed $data
     *
     * @return int
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function create(int $itemId, $data)
    {
        $itemData = new PluginDataModel();
        $itemData->setName($this->pluginName);
        $itemData->setItemId($itemId);
        $itemData->setData(serialize($data));

        return $this->pluginDataService->create($itemData)->getLastId();
    }

    /**
     * @param int   $itemId
     * @param mixed $data
     *
     * @return int
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function update(int $itemId, $data)
    {
        $itemData = new PluginDataModel();
        $itemData->setName($this->pluginName);
        $itemData->setItemId($itemId);
        $itemData->setData(serialize($data));

        return $this->pluginDataService->update($itemData);
    }

    /**
     * @param int $itemId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $itemId)
    {
        $this->pluginDataService->deleteByItemId($this->pluginName, $itemId);
    }

    /**
     * @param int         $itemId
     * @param string|null $class
     *
     * @return mixed|null
     * @throws ConstraintException
     * @throws CryptoException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function get(int $itemId, string $class = null)
    {
        try {
            return $this->pluginDataService->getByItemId($this->pluginName, $itemId)->hydrate($class);
        } catch (NoSuchItemException $e) {
            return null;
        }
    }
}