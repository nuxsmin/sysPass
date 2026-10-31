<?php
declare(strict_types=1);
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

namespace SP\Tests\Modules\Cli\Commands;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Core\Bootstrap\PathsContext;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Modules\Cli\Commands\BackupCommand;
use SP\Core\Bootstrap\Path;
use SP\Tests\Modules\Cli\CliTestCase;

use function SP\Tests\recreateDir;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 *
 */
#[Group('integration')]
class BackupCommandTest extends CliTestCase
{
    /**
     * @var string
     */
    protected static string $currentConfig;
    /**
     * @var string[]
     */
    protected static array $commandInputData = [];

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Test]
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
    #[Test]
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
        $pathsContext = self::$dic->get(PathsContext::class);

        $hash = $configData->getBackupHash();
        $backupPath = $pathsContext[Path::BACKUP];

        $this->assertNotEmpty(glob(sprintf('%s/sysPass_app-%s*', $backupPath, $hash)) ?: []);
        $this->assertNotEmpty(glob(sprintf('%s/sysPass_db-%s*', $backupPath, $hash)) ?: []);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Test]
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
    #[Test]
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
                self::$dic->get(PathsContext::class)[Path::BACKUP])
        );
    }

    protected function setUp(): void
    {
        $this->unsetEnvironmentVariables();

        parent::setUp();

        self::buildContainer();
    }

    private function unsetEnvironmentVariables(): void
    {
        foreach (BackupCommand::$envVarsMapping as $envVar) {
            putenv($envVar);
        }
    }
}
