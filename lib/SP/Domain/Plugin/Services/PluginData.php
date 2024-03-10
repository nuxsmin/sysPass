<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Plugin\Services;

use SP\Core\Application;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Plugin\Models\PluginData as PluginDataModel;
use SP\Domain\Plugin\Ports\PluginDataRepository;
use SP\Domain\Plugin\Ports\PluginDataService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class PluginData
 *
 * @template T of PluginDataModel
 */
final class PluginData extends Service implements PluginDataService
{
    public function __construct(
        Application                           $application,
        private readonly PluginDataRepository $pluginDataRepository,
        private readonly CryptInterface       $crypt,
    ) {
        parent::__construct($application);
    }


    /**
     * Creates an item
     *
     * @param PluginDataModel $pluginData
     * @return QueryResult
     * @throws ConstraintException
     * @throws CryptException
     * @throws QueryException
     * @throws ServiceException
     */
    public function create(PluginDataModel $pluginData): QueryResult
    {
        return $this->pluginDataRepository->create(
            $pluginData->encrypt($this->getMasterKeyFromContext(), $this->crypt)
        );
    }

    /**
     * Updates an item
     *
     * @param PluginDataModel $pluginData
     * @return int
     * @throws ConstraintException
     * @throws CryptException
     * @throws QueryException
     * @throws ServiceException
     */
    public function update(PluginDataModel $pluginData): int
    {
        return $this->pluginDataRepository->update(
            $pluginData->encrypt($this->getMasterKeyFromContext(), $this->crypt)
        );
    }

    /**
     * Returns the item for given plugin and id
     *
     * @param string $name
     * @param int $itemId
     * @return PluginDataModel
     * @throws ConstraintException
     * @throws CryptException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws ServiceException
     */
    public function getByItemId(string $name, int $itemId): PluginDataModel
    {
        $result = $this->pluginDataRepository->getByItemId($name, $itemId);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::info(__u('Plugin\'s data not found'));
        }


        return $result->getData(PluginDataModel::class)
                      ->decrypt($this->getMasterKeyFromContext(), $this->crypt);
    }

    /**
     * Returns the item for given id
     *
     * @param string $name
     * @return array<T>
     * @throws CryptException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function getByName(string $name): array
    {
        $result = $this->pluginDataRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::info(__u('Plugin\'s data not found'));
        }

        return array_map(
            fn(PluginDataModel $pluginData) => $pluginData->decrypt($this->getMasterKeyFromContext(), $this->crypt),
            $result->getDataAsArray(PluginDataModel::class)
        );
    }

    /**
     * Returns all the items
     *
     * @return array<T>
     * @throws ConstraintException
     * @throws CryptException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    public function getAll(): array
    {
        return array_map(
            fn(PluginDataModel $pluginData) => $pluginData->decrypt($this->getMasterKeyFromContext(), $this->crypt),
            $this->pluginDataRepository->getAll()->getDataAsArray(PluginDataModel::class)
        );
    }

    /**
     * Deletes an item
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(string $name): void
    {
        if ($this->pluginDataRepository->delete($name)->getAffectedNumRows() === 0) {
            throw NoSuchItemException::info(__u('Plugin\'s data not found'));
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
        if ($this->pluginDataRepository->deleteByItemId($name, $itemId)->getAffectedNumRows() === 0) {
            throw NoSuchItemException::info(__u('Plugin\'s data not found'));
        }
    }
}
