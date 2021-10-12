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

namespace SP\Storage\Database;

use PDOStatement;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;

/**
 * Interface DatabaseInterface
 *
 * @package SP\Storage
 */
interface DatabaseInterface
{
    /**
     * Performs a DB query
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function doQuery(QueryData $queryData): QueryResult;

    /**
     * Don't fetch records and return prepared statement
     */
    public function doQueryRaw(QueryData $queryData): PDOStatement;

    /**
     * Returns the total number of records
     */
    public function getFullRowCount(QueryData $queryData): int;

    public function getDbHandler(): DBStorageInterface;

    public function getNumRows(): int;

    public function getNumFields(): int;

    public function getLastResult(): ?array;

    public function getLastId(): ?int;

    public function beginTransaction(): bool;

    public function endTransaction(): bool;

    public function rollbackTransaction(): bool;

    public function getColumnsForTable(string $table): array;
}