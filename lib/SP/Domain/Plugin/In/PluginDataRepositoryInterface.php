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

namespace SP\Domain\Plugin\In;


use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Plugin\Repositories\PluginDataModel;

/**
 * Class PluginDataRepository
 *
 * @package SP\Infrastructure\Plugin\Repositories
 */
interface PluginDataRepositoryInterface
{
    /**
     * Creates an item
     *
     * @param  PluginDataModel  $itemData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(PluginDataModel $itemData): QueryResult;

    /**
     * Updates an item
     *
     * @param  PluginDataModel  $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PluginDataModel $itemData): int;

    /**
     * Deletes an item
     *
     * @param  string  $id
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete(string $id): int;

    /**
     * Deletes an item
     *
     * @param  string  $name
     * @param  int  $itemId
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByItemId(string $name, int $itemId): int;

    /**
     * Returns the item for given id
     *
     * @param  string  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(string $id): QueryResult;

    /**
     * Returns all the items
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult;

    /**
     * Returns all the items for given ids
     *
     * @param  string[]  $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param  string[]  $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param  string  $name
     * @param  int  $itemId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByItemId(string $name, int $itemId): QueryResult;
}