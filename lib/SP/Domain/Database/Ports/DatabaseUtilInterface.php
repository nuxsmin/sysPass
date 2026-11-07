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

namespace SP\Domain\Database\Ports;

/**
 * Read and escape helpers backed by the configured database connection.
 */
interface DatabaseUtilInterface
{
    /**
     * Checks that the expected sysPass tables exist in the given database.
     */
    public function checkDatabaseTables(string $dbName): bool;

    /**
     * Checks that the configured database connection is usable.
     */
    public function checkDatabaseConnection(): bool;

    /**
     * Returns the database server information.
     *
     * @return array<string, mixed>
     */
    public function getDBinfo(): array;

    /**
     * Escapes a string using the configured database connection.
     */
    public function escape(string $str): string;
}
