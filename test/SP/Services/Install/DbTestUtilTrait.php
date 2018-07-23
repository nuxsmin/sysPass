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

namespace SP\Tests\Services\Install;

use SP\Storage\Database\DatabaseConnectionData;
use SP\Storage\Database\MySQLHandler;

/**
 * Trait DbTestUtilTrait
 *
 * @package SP\Tests\Services\Install
 */
trait DbTestUtilTrait
{
    /**
     * @param $database
     *
     * @throws \SP\Storage\Database\DatabaseException
     */
    private function createDatabase($database)
    {
        $this->getConnection()
            ->query(sprintf('CREATE DATABASE `%s`', $database));
    }

    /**
     * @return \PDO
     * @throws \SP\Storage\Database\DatabaseException
     */
    private function getConnection()
    {
        $data = (new DatabaseConnectionData())
            ->setDbHost('syspass-db')
            ->setDbUser('root')
            ->setDbPass('syspass');

        return (new MySQLHandler($data))->getConnectionSimple();
    }

    /**
     * @param $user
     * @param $pass
     * @param $database
     *
     * @throws \SP\Storage\Database\DatabaseException
     */
    private function createUser($user, $pass, $database)
    {
        $this->getConnection()
            ->query(sprintf('GRANT ALL PRIVILEGES ON `%s`.* TO \'%s\'@\'%s\' IDENTIFIED BY \'%s\'', $database, $user, SELF_IP_ADDRESS, $pass));
    }

    /**
     * @param $user
     * @param $host
     *
     * @throws \SP\Storage\Database\DatabaseException
     */
    private function dropUser($user, $host)
    {
        $this->getConnection()
            ->query(sprintf('DROP USER \'%s\'@\'%s\'', $user, $host));
    }

    /**
     * @param $database
     *
     * @throws \SP\Storage\Database\DatabaseException
     */
    private function dropDatabase($database)
    {
        $this->getConnection()
            ->query(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
    }
}