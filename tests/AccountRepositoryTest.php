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

use PHPUnit\DbUnit\Database\Connection;
use PHPUnit\DbUnit\Database\DefaultConnection;
use PHPUnit\DbUnit\DataSet\IDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;
use SP\Storage\DatabaseConnectionData;
use SP\Storage\MySQLHandler;

/**
 * Class AccountRepositoryTest
 *
 * Tests unitarios para comprobar las consultas a la BBDD relativas a las cuentas
 *
 * @package SP\Tests
 */
class AccountRepositoryTest extends TestCase
{
    use TestCaseTrait;

    /**
     * @var \PDO
     */
    private static $pdo = null;

    /**
     * @var DefaultConnection
     */
    private $conn = null;

    public function testDelete()
    {

    }

    public function testEditRestore()
    {

    }

    public function testEditPassword()
    {

    }

    public function testGetPasswordForId()
    {

    }

    public function testCheckInUse()
    {

    }

    public function testGetById()
    {

    }

    public function testUpdate()
    {

    }

    public function testCheckDuplicatedOnAdd()
    {

    }

    public function testDeleteByIdBatch()
    {

    }

    public function testSearch()
    {

    }

    public function testGetLinked()
    {

    }

    public function testIncrementViewCounter()
    {

    }

    public function testGetAll()
    {

    }

    public function testUpdatePassword()
    {

    }

    public function testIncrementDecryptCounter()
    {

    }

    public function testGetTotalNumAccounts()
    {

    }

    public function testGetDataForLink()
    {

    }

    public function testGetForUser()
    {

    }

    public function testGetAccountsPassData()
    {

    }

    public function testCreate()
    {

    }

    public function testGetByIdBatch()
    {

    }

    public function testCheckDuplicatedOnUpdate()
    {

    }

    public function testGetPasswordHistoryForId()
    {

    }

    public function testGetByFilter()
    {

    }

    /**
     * Returns the test database connection.
     *
     * @return Connection
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo === null) {
                $data = new DatabaseConnectionData();
                $data->setDbHost('172.19.0.2');
                $data->setDbName('syspass');
                $data->setDbUser('root');
                $data->setDbPass('syspass');

                self::$pdo = (new MySQLHandler($data))->getConnection();
            }

            $this->conn = $this->createDefaultDBConnection(self::$pdo, 'syspass');
        }

        return $this->conn;
    }

    /**
     * Returns the test dataset.
     *
     * @return IDataSet
     */
    protected function getDataSet()
    {
        return $this->createMySQLXMLDataSet(RESOURCE_DIR . DIRECTORY_SEPARATOR . 'datasets' . DIRECTORY_SEPARATOR . 'syspass.xml');
    }
}
