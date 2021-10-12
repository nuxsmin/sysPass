<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Plugin;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Plugin\PluginDataModel;
use SP\Repositories\Plugin\PluginDataRepository;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database\QueryResult;

/**
 * Class PluginDataService
 *
 * @package SP\Services\Plugin
 */
final class PluginDataService extends Service
{
    protected ?PluginDataRepository $pluginRepository = null;

    /**
     * Creates an item
     *
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function create(PluginDataModel $itemData): QueryResult
    {
        return $this->pluginRepository
            ->create($itemData->encrypt($this->getMasterKeyFromContext()));
    }

    /**
     * Updates an item
     *
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function update(PluginDataModel $itemData): int
    {
        return $this->pluginRepository
            ->update($itemData->encrypt($this->getMasterKeyFromContext()));
    }

    /**
     * Returns the item for given plugin and id
     *
     * @throws NoSuchItemException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function getByItemId(string $name, int $id): PluginDataModel
    {
        $result = $this->pluginRepository->getByItemId($name, $id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(
                __u('Plugin\'s data not found'),
                SPException::INFO
            );
        }

        /** @var PluginDataModel $itemData */
        $itemData = $result->getData();

        return $itemData->decrypt($this->getMasterKeyFromContext());
    }

    /**
     * Returns the item for given id
     *
     * @return PluginDataModel[]
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     * @throws \SP\Services\ServiceException
     */
    public function getById(string $id): array
    {
        $result = $this->pluginRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(
                __u('Plugin\'s data not found'),
                SPException::INFO
            );
        }

        $data = $result->getDataAsArray();

        array_walk(
            $data,
            function ($itemData) {
                /** @var PluginDataModel $itemData */
                $itemData->decrypt($this->getMasterKeyFromContext());
            }
        );

        return $data;
    }

    /**
     * Returns all the items
     *
     * @return PluginDataModel[]
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Services\ServiceException
     */
    public function getAll(): array
    {
        $data = $this->pluginRepository->getAll()->getDataAsArray();

        array_walk(
            $data,
            function ($itemData) {
                /** @var PluginDataModel $itemData */
                $itemData->decrypt($this->getMasterKeyFromContext());
            }
        );

        return $data;
    }

    /**
     * Deletes an item
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function delete(string $id): void
    {
        if ($this->pluginRepository->delete($id) === 0) {
            throw new NoSuchItemException(
                __u('Plugin\'s data not found'),
                SPException::INFO
            );
        }
    }

    /**
     * Deletes an item
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByItemId(string $name, int $itemId): void
    {
        if ($this->pluginRepository->deleteByItemId($name, $itemId) === 0) {
            throw new NoSuchItemException(
                __u('Plugin\'s data not found'),
                SPException::INFO
            );
        }
    }

    protected function initialize(): void
    {
        $this->pluginRepository = $this->dic->get(PluginDataRepository::class);
    }
}