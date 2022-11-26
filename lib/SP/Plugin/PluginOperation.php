<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Plugin;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Plugin\Ports\PluginDataServiceInterface;
use SP\Domain\Plugin\Services\PluginDataService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Plugin\Repositories\PluginDataModel;

/**
 * Class PluginOperation
 *
 * @package SP\Plugin
 */
final class PluginOperation
{
    private PluginDataService $pluginDataService;
    private string $pluginName;

    /**
     * PluginOperation constructor.
     *
     * @param  PluginDataServiceInterface  $pluginDataService
     * @param  string  $pluginName
     */
    public function __construct(
        \SP\Domain\Plugin\Ports\PluginDataServiceInterface $pluginDataService,
        string            $pluginName
    )
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
    public function create(int $itemId, $data): int
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
    public function update(int $itemId, $data): int
    {
        $itemData = new \SP\Infrastructure\Plugin\Repositories\PluginDataModel();
        $itemData->setName($this->pluginName);
        $itemData->setItemId($itemId);
        $itemData->setData(serialize($data));

        return $this->pluginDataService->update($itemData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $itemId): void
    {
        $this->pluginDataService->deleteByItemId($this->pluginName, $itemId);
    }

    /**
     * @throws ConstraintException
     * @throws CryptoException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function get(int $itemId, ?string $class = null)
    {
        try {
            return $this->pluginDataService
                ->getByItemId($this->pluginName, $itemId)
                ->hydrate($class);
        } catch (NoSuchItemException $e) {
            return null;
        }
    }
}
