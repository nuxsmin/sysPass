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

use Exception;
use PDO;

use function SP\__u;

/**
 * Class PDOWrapper
 */
class PDOWrapper
{
    /**
     * Build a PDO object with the given connection data and options
     *
     * @throws DatabaseException
     */
    public function build(string $connectionUri, DatabaseConnectionData $connectionData, array $opts): PDO
    {
        try {
            return new PDO(
                $connectionUri,
                $connectionData->getDbUser(),
                $connectionData->getDbPass(),
                $opts
            );
        } catch (Exception $e) {
            throw DatabaseException::critical(
                __u('Unable to connect to DB'),
                sprintf('Error %s: %s', $e->getCode(), $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
}
