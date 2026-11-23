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

namespace SP\Infrastructure\Database;

use PDO;

/**
 * Resolves the PDO MySQL driver attribute constants.
 *
 * PHP 8.4+ exposes them on the \Pdo\Mysql class and deprecates the legacy
 * PDO::MYSQL_ATTR_* constants. The class_exists() check stays lazy so only
 * the constant available on the running PHP version is evaluated.
 */
final class PdoMysqlAttributes
{
    public static function foundRows(): int
    {
        return class_exists(\Pdo\Mysql::class)
            ? \Pdo\Mysql::ATTR_FOUND_ROWS
            : PDO::MYSQL_ATTR_FOUND_ROWS;
    }

    public static function useBufferedQuery(): int
    {
        return class_exists(\Pdo\Mysql::class)
            ? \Pdo\Mysql::ATTR_USE_BUFFERED_QUERY
            : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
    }
}
