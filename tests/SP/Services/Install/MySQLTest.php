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

use PHPUnit\Framework\TestCase;
use SP\Config\ConfigData;
use SP\Core\Exceptions\SPException;
use SP\Services\Install\InstallData;
use SP\Services\Install\MySQL;
use SP\Storage\Database\DatabaseException;
use SP\Storage\Database\MySQLHandler;
use SP\Tests\DatabaseUtil;
use SP\Util\PasswordUtil;

/**
 * Class MySQLTest
 *
 * @package SP\Tests\Services\Install
 */
class MySQLTest extends TestCase
{
    const DB_NAME = 'syspass-test-install';

    /**
     * @throws SPException
     */
    public function testCheckDatabaseNotExist()
    {
        DatabaseUtil::dropDatabase(self::DB_NAME);

        $mysql = new MySQL($this->getParams(), new ConfigData());

        $this->assertFalse($mysql->checkDatabaseExist());
    }

    /**
     * @return InstallData
     */
    private function getParams()
    {
        $params = new InstallData();
        $params->setDbAdminUser(getenv('DB_USER'));
        $params->setDbAdminPass(getenv('DB_PASS'));
        $params->setDbName(self::DB_NAME);
        $params->setDbHost(getenv('DB_SERVER'));
        $params->setDbAuthHost(SELF_IP_ADDRESS);

        // Long hostname returned on Travis CI
        if (getenv('TRAVIS') === false) {
            $params->setDbAuthHostDns(SELF_HOSTNAME);
        } else {
            $params->setDbAuthHostDns('localhost');
        }

        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        return $params;
    }

    /**
     * @throws DatabaseException
     * @throws SPException
     */
    public function testCheckDatabaseExist()
    {
        DatabaseUtil::createDatabase(self::DB_NAME);

        $mysql = new MySQL($this->getParams(), new ConfigData());

        $this->assertTrue($mysql->checkDatabaseExist());

        DatabaseUtil::dropDatabase(self::DB_NAME);
    }

    /**
     * @throws SPException
     */
    public function testSetupDbUser()
    {
        $configData = new ConfigData();

        $mysql = new MySQL($this->getParams(), $configData);
        $mysql->setupDbUser();

        $this->assertTrue(preg_match('/sp_\w+/', $configData->getDbUser()) === 1);
        $this->assertNotEmpty($configData->getDbPass());

        DatabaseUtil::dropUser($configData->getDbUser(), SELF_IP_ADDRESS);
        DatabaseUtil::dropUser($configData->getDbUser(), SELF_HOSTNAME);
    }

    /**
     * @throws SPException
     */
    public function testCreateDatabase()
    {
        $configData = new ConfigData();

        $mysql = new MySQL($this->getParams(), $configData);
        $mysql->setupDbUser();
        $mysql->createDatabase();

        $this->assertTrue($mysql->checkDatabaseExist());

        DatabaseUtil::dropDatabase(self::DB_NAME);
        DatabaseUtil::dropUser($configData->getDbUser(), SELF_IP_ADDRESS);
        DatabaseUtil::dropUser($configData->getDbUser(), SELF_HOSTNAME);
    }

    /**
     * @throws SPException
     */
    public function testCheckConnection()
    {
        $configData = new ConfigData();

        $mysql = new MySQL($this->getParams(), $configData);
        $mysql->setupDbUser();
        $mysql->createDatabase();
        $mysql->createDBStructure();
        $mysql->checkConnection();

        // Previous steps did not fail then true...
        $this->assertTrue(true);

        DatabaseUtil::dropDatabase(self::DB_NAME);
        DatabaseUtil::dropUser($configData->getDbUser(), SELF_IP_ADDRESS);
        DatabaseUtil::dropUser($configData->getDbUser(), SELF_HOSTNAME);
    }

    /**
     * @throws SPException
     */
    public function testConnectDatabase()
    {
        $mysql = new MySQL($this->getParams(), new ConfigData());
        $mysql->connectDatabase();

        $this->assertInstanceOf(MySQLHandler::class, $mysql->getDbHandler());
    }

    /**
     * @throws SPException
     */
    public function testCreateDBUser()
    {
        $mysql = new MySQL($this->getParams(), new ConfigData());
        $mysql->createDBUser('test', PasswordUtil::randomPassword());

        $num = (int)$mysql->getDbHandler()
            ->getConnectionSimple()
            ->query('SELECT COUNT(*) FROM mysql.user WHERE `User` = \'test\'')
            ->fetchColumn(0);

        $this->assertEquals(2, $num);

        DatabaseUtil::dropUser('test', SELF_IP_ADDRESS);
        DatabaseUtil::dropUser('test', SELF_HOSTNAME);
    }

    /**
     * @throws SPException
     */
    public function testRollback()
    {
        $mysql = new MySQL($this->getParams(), new ConfigData());
        $mysql->setupDbUser();
        $mysql->createDatabase();
        $mysql->createDBStructure();
        $mysql->rollback();

        $this->assertFalse($mysql->checkDatabaseExist());
    }

    /**
     * @throws SPException
     */
    public function testCreateDBStructure()
    {
        $mysql = new MySQL($this->getParams(), new ConfigData());
        $mysql->setupDbUser();
        $mysql->createDatabase();
        $mysql->createDBStructure();

        $this->assertTrue($mysql->checkDatabaseExist());

        $mysql->rollback();
    }
}
