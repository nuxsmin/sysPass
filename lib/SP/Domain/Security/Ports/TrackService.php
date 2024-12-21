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

use Exception;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class TrackService
 *
 * @package SP\Domain\Common\Services
 */
interface TrackService
{
    /**
     * @throws InvalidArgumentException
     */
    public function buildTrackRequest(string $source): TrackRequest;

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function unlock(int $id): void;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function clear(): bool;

    /**
     * Comprobar los intentos de login
     *
     * @return bool True if delay is performed, false otherwise
     * @throws Exception
     */
    public function checkTracking(TrackRequest $trackRequest): bool;

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(TrackRequest $trackRequest): int;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;
}
