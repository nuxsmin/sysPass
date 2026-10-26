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

namespace SP\Domain\Security\Ports;

use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Security\Models\Track as TrackModel;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class TrackRepository
 *
 * @template T of TrackModel
 */
interface TrackRepository
{
    /**
     * @param TrackModel $track
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(TrackModel $track): QueryResult;

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
     * Devuelve los tracks de un cliente desde un tiempo y origen determinados
     *
     * @param TrackModel $track
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getTracksForClientFromTime(TrackModel $track): QueryResult;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     * @param int $time The time to decide whether the track has been tracked or not.
     * If the track time is equal or greater than $time, it's considered as tracked.
     * @return QueryResult<T>
     */
    public function search(ItemSearchDto $itemSearchData, int $time): QueryResult;
}
