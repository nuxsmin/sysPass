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
use SP\Domain\Config\Services\ConfigFileService;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Domain\Export\Services\BackupFiles;
use SP\Modules\Cli\Commands\BackupCommand;
use SP\Tests\Modules\Cli\CliTestCase;

use function SP\Tests\recreateDir;

/**
 *
 */
class BackupCommandTest extends CliTestCase
{
    /**
     * @var string
     */
    protected static string $currentConfig;
    /**
     * @var string[]
     */
    protected static array $commandInputData = [
        '--path' => TMP_PATH
    ];

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testBackupFails(): void
    {
        $inputData = ['--path' => '/non/existant/path'];

        $commandTester = $this->executeCommandTest(
            BackupCommand::class,
            $inputData
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Unable to create the backups directory', $output);
    }

    /**
     * @throws DependencyException
     * @throws FileNotFoundException
     * @throws NotFoundException
     */
    public function testBackupIsSuccessful(): void
    {
        $this->setupDatabase();

        $commandTester = $this->executeCommandTest(
            BackupCommand::class,
            self::$commandInputData
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Application and database backup completed successfully', $output);

        $this->checkBackupFilesAreCreated();

        // Recreate cache directory to avoid unwanted behavior
        recreateDir(CACHE_PATH);
    }

    private function checkBackupFilesAreCreated(): void
    {
        $configData = self::$dic->get(ConfigFileService::class)->getConfigData();

        $this->assertFileExists(
            BackupFiles::getAppBackupFilename(
                TMP_PATH,
                $configData->getBackupHash(),
                true
            )
        );
        $this->assertFileExists(
            BackupFiles::getDbBackupFilename(
                TMP_PATH,
                $configData->getBackupHash(),
                true
            )
        );
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testBackupFromEnvironmentVarIsAbort(): void
    {
        putenv(sprintf('%s=%s',
                BackupCommand::$envVarsMapping['path'],
                '/non/existant/path')
        );

        $commandTester = $this->executeCommandTest(
            BackupCommand::class,
            null,
            false
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Unable to create the backups directory', $output);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws FileNotFoundException
     */
    public function testBackupFromEnvironmentVarIsSuccessful(): void
    {
        $this->setEnvironmentVariables();

        $commandTester = $this->executeCommandTest(
            BackupCommand::class,
            null,
            false
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Application and database backup completed successfully', $output);

        $this->checkBackupFilesAreCreated();

        // Recreate cache directory to avoid unwanted behavior
        recreateDir(CACHE_PATH);
    }

    private function setEnvironmentVariables(): void
    {
        putenv(sprintf('%s=%s',
                BackupCommand::$envVarsMapping['path'],
                TMP_PATH)
        );
    }

    protected function setUp(): void
    {
        $this->unsetEnvironmentVariables();

        parent::setUp();
    }

    private function unsetEnvironmentVariables(): void
    {
        foreach (BackupCommand::$envVarsMapping as $envVar) {
            putenv($envVar);
        }
    }
}
