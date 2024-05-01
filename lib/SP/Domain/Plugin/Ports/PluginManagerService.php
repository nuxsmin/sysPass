<?php
declare(strict_types=1);
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

namespace SP\Domain\Plugin\Ports;

use SP\Domain\Common\Models\Item;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Plugin\Models\Plugin;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Interface PluginManagerInterface
 */
interface PluginManagerService
{
    /**
     * Creates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(Plugin $plugin): int;

    /**
     * Updates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(Plugin $plugin): int;

    /**
     * Returns the item for given id
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): Plugin;

    /**
     * Returns all the items
     *
     * @return Plugin[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;

    /**
     * Returns all the items for given ids
     *
     * @param  int[]  $ids
     *
     * @return Plugin[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids): array;

    /**
     * Deletes all the items for given ids
     *
     * @param  int[]  $ids
     *
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids): void;

    /**
     * Deletes an item
     *
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): void;

    /**
     * Searches for items by a given filter
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName(string $name): Plugin;

    /**
     * Cambiar el estado del plugin
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function toggleEnabled(int $id, bool $enabled): void;

    /**
     * Cambiar el estado del plugin
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function toggleEnabledByName(string $name, bool $enabled): void;

    /**
     * Cambiar el estado del plugin
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function toggleAvailable(int $id, bool $available): void;

    /**
     * Cambiar el estado del plugin
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function toggleAvailableByName(string $name, bool $available): void;

    /**
     * Restablecer los datos de un plugin
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function resetById(int $id): void;

    /**
     * Devolver los plugins activados
     *
     * @return Item[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getEnabled(): array;
}
