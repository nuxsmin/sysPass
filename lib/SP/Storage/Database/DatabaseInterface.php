<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
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
     * @param QueryData $queryData Query data
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function doQuery(QueryData $queryData): QueryResult;

    /**
     * Don't fetch records and return prepared statement
     *
     * @param QueryData $queryData
     *
     * @return PDOStatement
     */
    public function doQueryRaw(QueryData $queryData): PDOStatement;

    /**
     * Returns the total number of records
     *
     * @param QueryData $queryData Query data
     *
     * @return int Records count
     */
    public function getFullRowCount(QueryData $queryData): int;

    /**
     * @return DBStorageInterface
     */
    public function getDbHandler(): DBStorageInterface;

    /**
     * @return int
     */
    public function getNumRows(): int;

    /**
     * @return int
     */
    public function getNumFields(): int;

    /**
     * @return array|null
     */
    public function getLastResult(): ?array;

    /**
     * @return int|null
     */
    public function getLastId(): ?int;

    /**
     * Iniciar una transacción
     *
     * @return bool
     */
    public function beginTransaction(): bool;

    /**
     * Finalizar una transacción
     *
     * @return bool
     */
    public function endTransaction(): bool;

    /**
     * Rollback de una transacción
     *
     * @return bool
     */
    public function rollbackTransaction(): bool;

    /**
     * @param string $table
     *
     * @return array
     */
    public function getColumnsForTable(string $table): array;
}