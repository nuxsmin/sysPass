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
use SP\DataModel\Item;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Plugin\Models\Plugin as PluginModel;
use SP\Domain\Plugin\Ports\PluginManagerInterface;
use SP\Domain\Plugin\Ports\PluginRepository;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class PluginManager
 *
 * @template T of PluginModel
 */
final class PluginManager extends Service implements PluginManagerInterface
{

    public function __construct(Application $application, private readonly PluginRepository $pluginRepository)
    {
        parent::__construct($application);
    }

    /**
     * Creates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(PluginModel $itemData): int
    {
        return $this->pluginRepository->create($itemData)->getLastId();
    }

    /**
     * Updates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PluginModel $itemData): int
    {
        return $this->pluginRepository->update($itemData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return PluginModel
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws SPException
     */
    public function getById(int $id): PluginModel
    {
        $result = $this->pluginRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), SPException::INFO);
        }

        return $result->getData();
    }

    /**
     * Returns all the items
     *
     * @return array<T>
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getAll(): array
    {
        return $this->pluginRepository->getAll()->getDataAsArray();
    }

    /**
     * Returns all the items for given ids
     *
     * @param int[] $ids
     *
     * @return array<T>
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getByIdBatch(array $ids): array
    {
        return $this->pluginRepository->getByIdBatch($ids)->getDataAsArray();
    }

    /**
     * Deletes all the items for given ids
     *
     * @param int[] $ids
     *
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids): void
    {
        if ($this->pluginRepository->deleteByIdBatch($ids) !== count($ids)) {
            throw new ServiceException(__u('Error while deleting the plugins'));
        }
    }

    /**
     * Deletes an item
     *
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): void
    {
        if ($this->pluginRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), SPException::INFO);
        }
    }

    /**
     * Searches for items by a given filter
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->pluginRepository->search($itemSearchData);
    }

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param string $name
     * @return PluginModel
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws SPException
     */
    public function getByName(string $name): PluginModel
    {
        $result = $this->pluginRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), SPException::INFO);
        }

        return $result->getData();
    }

    /**
     * Cambiar el estado del plugin
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function toggleEnabled(int $id, bool $enabled): void
    {
        if ($this->pluginRepository->toggleEnabled($id, $enabled) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), SPException::INFO);
        }
    }

    /**
     * Cambiar el estado del plugin
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function toggleEnabledByName(string $name, bool $enabled): void
    {
        if ($this->pluginRepository->toggleEnabledByName($name, $enabled) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), SPException::INFO);
        }
    }

    /**
     * Cambiar el estado del plugin
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function toggleAvailable(int $id, bool $available): void
    {
        if ($this->pluginRepository->toggleAvailable($id, $available) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), SPException::INFO);
        }
    }

    /**
     * Cambiar el estado del plugin
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function toggleAvailableByName(string $name, bool $available): void
    {
        if ($this->pluginRepository->toggleAvailableByName($name, $available) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), SPException::INFO);
        }
    }

    /**
     * Restablecer los datos de un plugin
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function resetById(int $id): bool
    {
        if (($count = $this->pluginRepository->resetById($id)) === 0) {
            throw new NoSuchItemException(__u('Plugin not found'), SPException::INFO);
        }

        return $count;
    }

    /**
     * Devolver los plugins activados
     *
     * @return Item[]
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getEnabled(): array
    {
        return $this->pluginRepository->getEnabled()->getDataAsArray();
    }
}
