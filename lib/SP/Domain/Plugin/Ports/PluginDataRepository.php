<?php
declare(strict_types=1);
/**
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

use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Models\PluginData as PluginDataModel;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class PluginDataRepository
 *
 * @template T of PluginDataModel
 */
interface PluginDataRepository
{
    /**
     * Creates an item
     *
     * @param PluginDataModel $pluginData
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(PluginDataModel $pluginData): QueryResult;

    /**
     * Updates an item
     *
     * @param PluginDataModel $pluginData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PluginDataModel $pluginData): int;

    /**
     * Deletes an item
     *
     * @param string $name
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(string $name): QueryResult;

    /**
     * Deletes an item
     *
     * @param string $name
     * @param int $itemId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByItemId(string $name, int $itemId): QueryResult;

    /**
     * Returns the item for given name
     *
     * @param string $name
     * @return QueryResult<T>
     */
    public function getByName(string $name): QueryResult;

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult;

    /**
     * Returns all the items for given names
     *
     * @param string[] $names
     *
     * @return QueryResult<T>
     */
    public function getByNameBatch(array $names): QueryResult;

    /**
     * Deletes all the items for given names
     *
     * @param string[] $names
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByNameBatch(array $names): QueryResult;

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param string $name
     * @param int $itemId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByItemId(string $name, int $itemId): QueryResult;
}
