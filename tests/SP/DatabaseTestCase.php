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

use PDO;
use PHPUnit\DbUnit\Database\DefaultConnection;
use PHPUnit\DbUnit\DataSet\IDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;
use SP\Core\Exceptions\SPException;
use SP\Storage\Database\DatabaseConnectionData;

/**
 * Class DatabaseBaseTest
 *
 * Caso de test para tests que requieran consultas a la BBDD
 *
 * @package SP\Tests
 */
abstract class DatabaseTestCase extends TestCase
{
    use TestCaseTrait;

    /**
     * @var DatabaseConnectionData
     */
    protected static $databaseConnectionData;
    /**
     * @var string
     */
    protected static $dataset = 'syspass.xml';
    /**
     * @var PDO
     */
    private static $pdo;
    /**
     * @var DefaultConnection
     */
    protected $conn;

    /**
     * Returns the test database connection.
     *
     * @return DefaultConnection
     * @throws SPException
     */
    final public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo === null) {
                self::$pdo = getDbHandler()->getConnection();
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
        return $this->createMySQLXMLDataSet(RESOURCE_DIR . DIRECTORY_SEPARATOR . 'datasets' . DIRECTORY_SEPARATOR . self::$dataset);
    }
}