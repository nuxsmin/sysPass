<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests;

use Exception;
use PDO;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Storage\Database\DatabaseException;

/**
 * Trait DatabaseUtil
 *
 * @package SP\Tests
 */
class DatabaseUtil
{
    /**
     * @param string $user
     * @param string $pass
     * @param string $database
     * @param string $host
     *
     * @throws DatabaseException
     */
    public static function createUser($user, $pass, $database, $host)
    {
        $query = 'GRANT ALL PRIVILEGES ON `%s`.* TO \'%s\'@\'%s\' IDENTIFIED BY \'%s\'';

        $conn = self::getConnection();
        $conn->query(sprintf($query, $database, $user, SELF_IP_ADDRESS, $pass));

        // Long hostname returned on Travis CI
        if (getenv('TRAVIS') === false) {
            $conn->query(sprintf($query, $database, $user, SELF_HOSTNAME, $pass));
        }

        $conn->query(sprintf($query, $database, $user, $host, $pass));
    }

    /**
     * @return PDO
     * @throws DatabaseException
     */
    public static function getConnection()
    {
        $data = (new DatabaseConnectionData())
            ->setDbHost(getenv('DB_SERVER'))
            ->setDbUser(getenv('DB_USER'))
            ->setDbPass(getenv('DB_PASS'));

        return getDbHandler($data)->getConnectionSimple();
    }

    /**
     * @param string $user
     * @param string $host
     */
    public static function dropUser($user, $host)
    {
        try {
            self::getConnection()
                ->query(sprintf('DROP USER \'%s\'@\'%s\'', $user, $host));
        } catch (Exception $e) {
            processException($e);
        }
    }

    /**
     * @param string $database
     *
     * @throws DatabaseException
     */
    public static function dropDatabase($database)
    {
        self::getConnection()
            ->query(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
    }

    /**
     * @param string $database
     *
     * @throws DatabaseException
     */
    public static function createDatabase($database)
    {
        self::getConnection()
            ->query(sprintf('CREATE DATABASE `%s`', $database));
    }
}