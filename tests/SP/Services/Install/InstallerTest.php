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

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use PHPUnit\Framework\TestCase;
use SP\Config\Config;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\SPException;
use SP\Services\Crypt\MasterPassService;
use SP\Services\Install\InstallData;
use SP\Services\Install\Installer;
use SP\Storage\Database\DBStorageInterface;
use SP\Tests\DatabaseUtil;
use SP\Util\PasswordUtil;
use function SP\Tests\getResource;
use function SP\Tests\recreateDir;
use function SP\Tests\saveResource;
use function SP\Tests\setupContext;

/**
 * Class InstallerTest
 *
 * @package SP\Tests\Services\Install
 */
class InstallerTest extends TestCase
{
    const DB_NAME = 'syspass-test-install';

    private static $currentConfig;

    /**
     * @var Container
     */
    private static $dic;

    /**
     * @throws ContextException
     */
    public static function setUpBeforeClass()
    {
        self::$dic = setupContext();

        self::$currentConfig = getResource('config', 'config.xml');
    }

    /**
     * This method is called after the last test of this test class is run.
     */
    public static function tearDownAfterClass()
    {
        saveResource('config', 'config.xml', self::$currentConfig);
        recreateDir(CACHE_PATH);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testRun()
    {
        $params = new InstallData();
        $params->setDbAdminUser(getenv('DB_USER'));
        $params->setDbAdminPass(getenv('DB_PASS'));
        $params->setDbName(self::DB_NAME);
        $params->setDbHost(getenv('DB_SERVER'));
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);
        $installer->run($params);

        $configData = self::$dic->get(Config::class)->getConfigData();

        $this->assertEquals($params->getDbName(), $configData->getDbName());
        $this->assertEquals($params->getDbHost(), $configData->getDbHost());
        $this->assertEquals(3306, $configData->getDbPort());
        $this->assertTrue(preg_match('/sp_\w+/', $configData->getDbUser()) === 1);
        $this->assertNotEmpty($configData->getDbPass());
        $this->assertEquals($params->getSiteLang(), $configData->getSiteLang());

        $this->assertTrue(self::$dic->get(MasterPassService::class)->checkMasterPassword($params->getMasterPassword()));

        DatabaseUtil::dropDatabase(self::DB_NAME);

        DatabaseUtil::dropUser($configData->getDbUser(), $params->getDbAuthHost());

        if ($params->getDbAuthHostDns()) {
            DatabaseUtil::dropUser($configData->getDbUser(), $params->getDbAuthHostDns());
        }
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testFailDbHostName()
    {
        $params = new InstallData();
        $params->setDbAdminUser(getenv('DB_USER'));
        $params->setDbAdminPass(getenv('DB_PASS'));
        $params->setDbName(self::DB_NAME);
        $params->setDbHost('fail');
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);

        $this->expectException(SPException::class);
        $this->expectExceptionCode(2002);

        $installer->run($params);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testFailDbHostIp()
    {
        $params = new InstallData();
        $params->setDbAdminUser(getenv('DB_USER'));
        $params->setDbAdminPass(getenv('DB_PASS'));
        $params->setDbName(self::DB_NAME);
        $params->setDbHost('192.168.0.1');
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);

        $this->expectException(SPException::class);
        $this->expectExceptionCode(2002);

        $installer->run($params);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testFailDbHostPort()
    {
        $params = new InstallData();
        $params->setDbAdminUser(getenv('DB_USER'));
        $params->setDbAdminPass(getenv('DB_PASS'));
        $params->setDbName(self::DB_NAME);
        $params->setDbHost(getenv('DB_SERVER') . ':3307');
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);

        $this->expectException(SPException::class);
        $this->expectExceptionCode(2002);

        $installer->run($params);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testFailDbUser()
    {
        $params = new InstallData();
        $params->setDbAdminUser('toor');
        $params->setDbAdminPass(getenv('DB_PASS'));
        $params->setDbName(self::DB_NAME);
        $params->setDbHost(getenv('DB_SERVER'));
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);

        $this->expectException(SPException::class);
        $this->expectExceptionCode(1045);

        $installer->run($params);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testFailDbPass()
    {
        $params = new InstallData();
        $params->setDbAdminUser(getenv('DB_USER'));
        $params->setDbAdminPass('test');
        $params->setDbName(self::DB_NAME);
        $params->setDbHost(getenv('DB_SERVER'));
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);

        $this->expectException(SPException::class);
        $this->expectExceptionCode(1045);

        $installer->run($params);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testHostingMode()
    {
        $pass = PasswordUtil::randomPassword();
        $host = getenv('DB_SERVER');

        DatabaseUtil::dropDatabase(self::DB_NAME);
        DatabaseUtil::createDatabase(self::DB_NAME);
        DatabaseUtil::createUser('syspass_user', $pass, self::DB_NAME, $host);

        $params = new InstallData();
        $params->setDbAdminUser('syspass_user');
        $params->setDbAdminPass($pass);
        $params->setDbName(self::DB_NAME);
        $params->setDbHost($host);
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');
        $params->setHostingMode(true);

        $installer = self::$dic->get(Installer::class);
        $installer->run($params);

        $databaseUtil = new \SP\Storage\Database\DatabaseUtil(self::$dic->get(DBStorageInterface::class));

        $this->assertTrue($databaseUtil->checkDatabaseTables(self::DB_NAME));

        $configData = self::$dic->get(Config::class)->getConfigData();

        $this->assertEquals($params->getDbName(), $configData->getDbName());
        $this->assertEquals($params->getDbHost(), $configData->getDbHost());
        $this->assertEquals(3306, $configData->getDbPort());
        $this->assertNotEmpty($configData->getDbPass());
        $this->assertEquals($params->getSiteLang(), $configData->getSiteLang());

        $this->assertTrue(self::$dic->get(MasterPassService::class)->checkMasterPassword($params->getMasterPassword()));

        DatabaseUtil::dropDatabase(self::DB_NAME);
        DatabaseUtil::dropUser('syspass_user', SELF_IP_ADDRESS);
        DatabaseUtil::dropUser('syspass_user', SELF_HOSTNAME);
        DatabaseUtil::dropUser('syspass_user', $host);
    }

    protected function tearDown()
    {
        @unlink(CONFIG_FILE);
    }
}
