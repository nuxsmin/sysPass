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

namespace SPT\Domain\Install\Services;

use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Install\Adapters\InstallData;
use SP\Domain\Install\Ports\InstallerServiceInterface;
use SP\Domain\Install\Services\DatabaseSetupInterface;
use SP\Domain\Install\Services\InstallerService;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Domain\User\Services\UserService;
use SP\Infrastructure\Database\DatabaseConnectionData;
use SP\Util\VersionUtil;
use SPT\UnitaryTestCase;

/**
 * Class InstallerTest
 *
 */
#[Group('unitary')]
class InstallerTest extends UnitaryTestCase
{
    /**
     * @var MockObject|DatabaseSetupInterface
     */
    private $databaseSetup;
    /**
     * @var MockObject|UserService
     */
    private $userService;
    /**
     * @var Stub|RequestInterface
     */
    private $request;
    /**
     * @var MockObject|ConfigService
     */
    private $configService;
    /**
     * @var MockObject|UserGroupService
     */
    private $userGroupService;
    /**
     * @var MockObject|UserProfileService
     */
    private $userProfileService;

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testRunIsSuccessful(): void
    {
        $expectedDbSetup = [self::$faker->userName, self::$faker->password];

        $this->databaseSetup->expects($this->once())->method('connectDatabase');
        $this->databaseSetup->expects($this->once())->method('setupDbUser')->willReturn($expectedDbSetup);
        $this->databaseSetup->expects($this->once())->method('createDatabase');
        $this->databaseSetup->expects($this->once())->method('createDBStructure');
        $this->databaseSetup->expects($this->once())->method('checkConnection');
        $this->userService->expects($this->once())->method('createWithMasterPass')->willReturn(1);
        $this->configService->expects($this->exactly(3))->method('create');
        $this->userGroupService->expects($this->once())->method('create');
        $this->userProfileService->expects($this->once())->method('create');

        $params = $this->getInstallData();

        $installer = $this->getDefaultInstaller();

        $installer->run($params);

        $configData = $this->config->getConfigData();

        $this->assertEquals($params->getDbName(), $configData->getDbName());
        $this->assertEquals($params->getDbHost(), $configData->getDbHost());
        $this->assertEquals(3306, $configData->getDbPort());
        $this->assertEquals($expectedDbSetup[0], $configData->getDbUser());
        $this->assertEquals($expectedDbSetup[1], $configData->getDbPass());
        $this->assertEquals($params->getSiteLang(), $configData->getSiteLang());
        $this->assertEquals(VersionUtil::getVersionStringNormalized(), $configData->getConfigVersion());
        $this->assertEquals(VersionUtil::getVersionStringNormalized(), $configData->getDatabaseVersion());
        $this->assertEquals(SELF_IP_ADDRESS, $params->getDbAuthHost());
        $this->assertNull($configData->getUpgradeKey());
        $this->assertNull($configData->getDbSocket());
    }

    /**
     * @return InstallData
     */
    private function getInstallData(): InstallData
    {
        $params = new InstallData();
        $params->setDbAdminUser(self::$faker->userName);
        $params->setDbAdminPass(self::$faker->password);
        $params->setDbName(self::$faker->colorName);
        $params->setDbHost(self::$faker->domainName);
        $params->setAdminLogin(self::$faker->userName);
        $params->setAdminPass(self::$faker->password);
        $params->setMasterPassword(self::$faker->password(11));
        $params->setSiteLang(self::$faker->languageCode);

        return $params;
    }

    /**
     * @return InstallerServiceInterface
     */
    private function getDefaultInstaller(): InstallerServiceInterface
    {
        return new InstallerService(
            $this->request,
            $this->config,
            $this->userService,
            $this->userGroupService,
            $this->userProfileService,
            $this->configService,
            new DatabaseConnectionData(),
            $this->databaseSetup
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testSocketIsUsedForDBConnection(): void
    {
        $expectedDbSetup = [self::$faker->userName, self::$faker->password];
        $dbSocket = 'unix:/path/to/socket';

        $this->databaseSetup->expects($this->once())->method('setupDbUser')->willReturn($expectedDbSetup);
        $this->userService->expects($this->once())->method('createWithMasterPass')->willReturn(1);

        $params = $this->getInstallData();
        $params->setDbHost($dbSocket);

        $installer = $this->getDefaultInstaller();

        $installer->run($params);

        $configData = $this->config->getConfigData();

        $this->assertEquals(str_replace('unix:', '', $dbSocket), $configData->getDbSocket());
        $this->assertEquals($dbSocket, $configData->getDbHost());
        $this->assertEquals(0, $configData->getDbPort());
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testLocalhostIsUsedForDBConnection(): void
    {
        $expectedDbSetup = [self::$faker->userName, self::$faker->password];

        $this->databaseSetup->expects($this->once())->method('setupDbUser')->willReturn($expectedDbSetup);
        $this->userService->expects($this->once())->method('createWithMasterPass')->willReturn(1);

        $params = $this->getInstallData();
        $params->setDbHost('localhost');

        $installer = $this->getDefaultInstaller();

        $installer->run($params);

        $this->assertEquals($params->getDbHost(), $params->getDbAuthHost());
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testHostAndPortAreUsedForDBConnection(): void
    {
        $expectedDbSetup = [self::$faker->userName, self::$faker->password];

        $this->databaseSetup->expects($this->once())->method('setupDbUser')->willReturn($expectedDbSetup);
        $this->userService->expects($this->once())->method('createWithMasterPass')->willReturn(1);

        $params = $this->getInstallData();
        $params->setDbHost('host:3307');

        $installer = $this->getDefaultInstaller();

        $installer->run($params);

        $this->assertEquals(SELF_IP_ADDRESS, $params->getDbAuthHost());
        $this->assertEquals('host', $params->getDbHost());
        $this->assertEquals(3307, $params->getDbPort());
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testHostingModeIsUsed(): void
    {
        $this->databaseSetup->expects($this->never())->method('setupDbUser');
        $this->userService->expects($this->once())->method('createWithMasterPass')->willReturn(1);

        $params = $this->getInstallData();
        $params->setHostingMode(true);

        $installer = $this->getDefaultInstaller();

        $installer->run($params);

        $configData = $this->config->getConfigData();

        $this->assertEquals($params->getDbAdminUser(), $configData->getDbUser());
        $this->assertEquals($params->getDbAdminPass(), $configData->getDbPass());
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testAdminUserIsNotCreated(): void
    {
        $this->databaseSetup->expects($this->once())->method('rollback');
        $this->userService->expects($this->once())->method('createWithMasterPass')->willReturn(0);

        $params = $this->getInstallData();
        $params->setHostingMode(true);

        $installer = $this->getDefaultInstaller();

        $this->expectException(SPException::class);
        $this->expectExceptionMessage('Error while creating \'admin\' user');

        $installer->run($params);
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function testConfigIsNotSaved(): void
    {
        $this->configService->method('create')->willThrowException(new Exception('Create exception'));
        $this->databaseSetup->expects($this->once())->method('rollback');
        $this->userService->expects($this->never())->method('createWithMasterPass');

        $params = $this->getInstallData();
        $params->setHostingMode(true);

        $installer = $this->getDefaultInstaller();

        $this->expectException(SPException::class);
        $this->expectExceptionMessage('Create exception');

        $installer->run($params);
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     **/
    public function testAdminLoginIsNotBlank(): void
    {
        $params = $this->getInstallData();
        $params->setAdminLogin('');

        $installer = $this->getDefaultInstaller();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please, enter the admin username');

        $installer->run($params);
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     **/
    public function testAdminPassIsNotBlank(): void
    {
        $params = $this->getInstallData();
        $params->setAdminPass('');

        $installer = $this->getDefaultInstaller();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please, enter the admin\'s password');

        $installer->run($params);
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     **/
    public function testMasterPasswordIsNotBlank(): void
    {
        $params = $this->getInstallData();
        $params->setMasterPassword('');

        $installer = $this->getDefaultInstaller();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please, enter the Master Password');

        $installer->run($params);
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     **/
    public function testMasterPasswordLengthIsWrong(): void
    {
        $params = $this->getInstallData();
        $params->setMasterPassword(self::$faker->password(1, 10));

        $installer = $this->getDefaultInstaller();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Master password too short');

        $installer->run($params);
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     **/
    public function testDbAdminUserIsWrong(): void
    {
        $params = $this->getInstallData();
        $params->setDbAdminUser('');

        $installer = $this->getDefaultInstaller();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please, enter the database user');

        $installer->run($params);
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     **/
    public function testDbAdminPassIsWrong(): void
    {
        $params = $this->getInstallData();
        $params->setDbAdminPass('');

        $installer = $this->getDefaultInstaller();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please, enter the database password');

        $installer->run($params);
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     **/
    public function testDbNameIsBlank(): void
    {
        $params = $this->getInstallData();
        $params->setDbName('');

        $installer = $this->getDefaultInstaller();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please, enter the database name');

        $installer->run($params);
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     **/
    public function testDbNameIsWrong(): void
    {
        $params = $this->getInstallData();
        $params->setDbName('test.db');

        $installer = $this->getDefaultInstaller();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database name cannot contain "."');

        $installer->run($params);
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     **/
    public function testDbHostIsBlank(): void
    {
        $params = $this->getInstallData();
        $params->setDbHost('');

        $installer = $this->getDefaultInstaller();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please, enter the database server');

        $installer->run($params);
    }

    protected function setUp(): void
    {
        $this->databaseSetup = $this->createMock(DatabaseSetupInterface::class);
        $this->userService = $this->createMock(UserServiceInterface::class);
        $this->request = $this->createStub(RequestInterface::class);
        $this->configService = $this->createMock(ConfigService::class);
        $this->userGroupService = $this->createMock(UserGroupService::class);
        $this->userProfileService = $this->createMock(UserProfileService::class);

        parent::setUp();
    }
}
