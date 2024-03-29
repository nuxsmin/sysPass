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

namespace SP\Infrastructure\Database;

use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;

/**
 * Interface DatabaseInterface
 *
 * @package SP\Storage
 */
interface DatabaseInterface
{
    /**
     * Perform any type of query
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function runQuery(QueryDataInterface $queryData, bool $fullCount = false): QueryResult;

    /**
     * Don't fetch records and return prepared statement
     */
    public function doFetchWithOptions(QueryDataInterface $queryData): iterable;

    public function beginTransaction(): bool;

    public function endTransaction(): bool;

    public function rollbackTransaction(): bool;

    /**
     * Execute a raw query
     *
     * @param string $query
     * @throws QueryException
     */
    public function runQueryRaw(string $query): void;
}
