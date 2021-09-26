<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Modules\Cli\Commands;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Config\Config;
use SP\Modules\Cli\Commands\InstallCommand;
use SP\Storage\Database\DatabaseException;
use SP\Tests\DatabaseUtil;
use SP\Tests\Modules\Cli\CliTestCase;
use function SP\Tests\getResource;
use function SP\Tests\recreateDir;
use function SP\Tests\saveResource;

/**
 *
 */
class InstallCommandTest extends CliTestCase
{
    /**
     * @var string
     */
    protected static string $currentConfig;
    /**
     * @var string[]
     */
    protected static array $commandInputData = [
        'adminLogin' => 'Admin',
        'databaseHost' => 'localhost',
        'databaseName' => 'syspass-test-install',
        'databaseUser' => 'syspass_user',
        '--databasePassword' => 'test123',
        '--adminPassword' => 'admin123',
        '--masterPassword' => '12345678900',
        '--install' => null,
    ];

    /**
     * This method is called before the first test of this test class is run.
     *
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        // Backup current config file content in a variable
        self::$currentConfig = getResource('config', 'config.xml');

        parent::setUpBeforeClass();
    }

    /**
     * This method is called after the last test of this test class is run.
     */
    public static function tearDownAfterClass(): void
    {
        // Replace config file with previously saved data
        saveResource('config', 'config.xml', self::$currentConfig);
        // Recreate cache directory to avoid unwanted behavior
        recreateDir(CACHE_PATH);

        parent::tearDownAfterClass();
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testInstallationIsAborted(): void
    {
        $commandTester = $this->executeCommandTest(InstallCommand::class);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Installation aborted', $output);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testNoDatabaseConnection(): void
    {
        $inputData = array_merge(
            self::$commandInputData,
            ['--forceInstall' => null]
        );

        $commandTester = $this->executeCommandTest(
            InstallCommand::class,
            $inputData
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Unable to connect to DB', $output);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testEmptyAdminPassword(): void
    {
        $inputData = array_merge(
            self::$commandInputData,
            ['--adminPassword' => '']
        );

        $commandTester = $this->executeCommandTest(
            InstallCommand::class,
            $inputData
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Admin password cannot be blank', $output);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testEmptyMasterPassword(): void
    {
        $inputData = array_merge(
            self::$commandInputData,
            ['--masterPassword' => '']
        );

        $commandTester = $this->executeCommandTest(
            InstallCommand::class,
            $inputData
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Master password cannot be blank', $output);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DatabaseException
     */
    public function testInstallIsSuccessful(): void
    {
        $inputData = array_merge(
            self::$commandInputData,
            [
                'databaseHost' => getenv('DB_SERVER'),
                'databaseUser' => getenv('DB_USER'),
                '--databasePassword' => getenv('DB_PASS'),
                '--forceInstall' => null
            ]
        );

        $commandTester = $this->executeCommandTest(
            InstallCommand::class,
            $inputData
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Installation finished', $output);

        $configData = self::$dic->get(Config::class)->getConfigData();

        // Cleanup database
        DatabaseUtil::dropDatabase(self::$commandInputData['databaseName']);
        DatabaseUtil::dropUser($configData->getDbUser(), SELF_IP_ADDRESS);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DatabaseException
     */
    public function testInstallAndLanguageIsSet(): void
    {
        $inputData = array_merge(
            self::$commandInputData,
            [
                'databaseHost' => getenv('DB_SERVER'),
                'databaseUser' => getenv('DB_USER'),
                '--databasePassword' => getenv('DB_PASS'),
                '--language' => 'es_ES',
                '--forceInstall' => null
            ]
        );

        $commandTester = $this->executeCommandTest(
            InstallCommand::class,
            $inputData
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Installation finished', $output);

        $configData = self::$dic->get(Config::class)->getConfigData();

        $this->assertEquals($configData->getSiteLang(), $inputData['--language']);

        // Cleanup database
        DatabaseUtil::dropDatabase(self::$commandInputData['databaseName']);
        DatabaseUtil::dropUser($configData->getDbUser(), SELF_IP_ADDRESS);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DatabaseException
     */
    public function testInstallAndHostingModeIsUsed(): void
    {
        $databaseUser = 'syspass';
        $databasePassword = 'syspass123';

        DatabaseUtil::createDatabase(self::$commandInputData['databaseName']);
        DatabaseUtil::createUser(
            $databaseUser,
            $databasePassword,
            self::$commandInputData['databaseName'],
            getenv('DB_SERVER')
        );

        $inputData = array_merge(
            self::$commandInputData,
            [
                'databaseHost' => getenv('DB_SERVER'),
                'databaseUser' => $databaseUser,
                '--databasePassword' => $databasePassword,
                '--hostingMode' => null,
                '--forceInstall' => null
            ]
        );

        $commandTester = $this->executeCommandTest(
            InstallCommand::class,
            $inputData
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Installation finished', $output);

        $configData = self::$dic->get(Config::class)->getConfigData();

        $this->assertEquals($configData->getDbUser(), $databaseUser);
        $this->assertEquals($configData->getDbPass(), $databasePassword);

        // Cleanup database
        DatabaseUtil::dropDatabase(self::$commandInputData['databaseName']);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testInstallFromEnvironmentVarIsAbort(): void
    {
        $this->setEnvironmentVariables();

        $commandTester = $this->executeCommandTest(
            InstallCommand::class,
            null,
            false
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Installation aborted', $output);
    }

    private function setEnvironmentVariables(): void
    {
        putenv(sprintf('%s=%s',
                InstallCommand::$envVarsMapping['databaseHost'],
                getenv('DB_SERVER'))
        );
        putenv(sprintf('%s=%s',
                InstallCommand::$envVarsMapping['databaseUser'],
                getenv('DB_USER'))
        );
        putenv(sprintf('%s=%s',
                InstallCommand::$envVarsMapping['databasePassword'],
                getenv('DB_PASS'))
        );
        putenv(sprintf('%s=%s',
                InstallCommand::$envVarsMapping['databaseName'],
                self::$commandInputData['databaseName'])
        );
        putenv(sprintf('%s=%s',
                InstallCommand::$envVarsMapping['adminLogin'],
                self::$commandInputData['adminLogin'])
        );
        putenv(sprintf('%s=%s',
                InstallCommand::$envVarsMapping['adminPassword'],
                self::$commandInputData['--adminPassword'])
        );
        putenv(sprintf('%s=%s',
                InstallCommand::$envVarsMapping['masterPassword'],
                self::$commandInputData['--masterPassword'])
        );
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testInstallFromEnvironmentVarIsAbortedWithForce(): void
    {
        putenv(sprintf('%s=true',
                InstallCommand::$envVarsMapping['forceInstall'])
        );

        $this->setEnvironmentVariables();

        $commandTester = $this->executeCommandTest(
            InstallCommand::class,
            null,
            false
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Installation aborted', $output);
    }

    /**
     * @throws DatabaseException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testInstallFromEnvironmentVarIsSuccessful(): void
    {
        putenv(sprintf('%s=true',
                InstallCommand::$envVarsMapping['forceInstall'])
        );
        putenv(sprintf('%s=true',
                InstallCommand::$envVarsMapping['install'])
        );

        $this->setEnvironmentVariables();

        $commandTester = $this->executeCommandTest(
            InstallCommand::class,
            null,
            false
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Installation finished', $output);

        // Cleanup database
        DatabaseUtil::dropDatabase(self::$commandInputData['databaseName']);
    }
}
