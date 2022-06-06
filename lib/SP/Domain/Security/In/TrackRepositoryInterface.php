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

namespace SP\Domain\Security\In;


use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Security\Repositories\TrackRequest;

/**
 * Class TrackRepository
 *
 * @package SP\Infrastructure\Security\Repositories
 */
interface TrackRepositoryInterface
{
    /**
     * @param  TrackRequest  $trackRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(TrackRequest $trackRequest): int;

    /**
     * @param $id int
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function delete(int $id): int;

    /**
     * @param $id int
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function unlock(int $id): int;

    /**
     * Clears tracks
     *
     * @return bool con el resultado
     * @throws QueryException
     * @throws ConstraintException
     */
    public function clear(): bool;

    /**
     * @param $id int
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult;

    /**
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult;

    /**
     * Devuelve los tracks de un cliente desde un tiempo y origen determinados
     *
     * @param  TrackRequest  $trackRequest
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getTracksForClientFromTime(TrackRequest $trackRequest): QueryResult;

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;
}