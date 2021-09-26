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
use SP\Core\Exceptions\FileNotFoundException;
use SP\Modules\Cli\Commands\Crypt\UpdateMasterPasswordCommand;
use SP\Tests\DatabaseTrait;
use SP\Tests\Modules\Cli\CliTestCase;
use SP\Tests\Services\Account\AccountCryptServiceTest;
use function SP\Tests\recreateDir;

/**
 *
 */
class UpdateMasterPasswordCommandTest extends CliTestCase
{

    use DatabaseTrait;

    /**
     * @var string
     */
    protected static string $currentConfig;
    /**
     * @var string[]
     */
    protected static array $commandInputData = [
        '--currentMasterPassword' => AccountCryptServiceTest::CURRENT_MASTERPASS,
        '--masterPassword' => AccountCryptServiceTest::NEW_MASTERPASS
    ];

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testUpdateAborted(): void
    {
        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Master password update aborted', $output);
    }

    /**
     * @throws DependencyException
     * @throws FileNotFoundException
     * @throws NotFoundException
     */
    public function testUpdateIsSuccessful(): void
    {
        $inputData = array_merge(
            self::$commandInputData,
            [
                '--update' => null
            ]
        );

        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class,
            $inputData
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Master password updated', $output);

        // Recreate cache directory to avoid unwanted behavior
        recreateDir(CACHE_PATH);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testUpdateFromEnvironmentVarIsAbort(): void
    {
        $this->setEnvironmentVariables();

        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class,
            null,
            false
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Master password update aborted', $output);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testUpdateFromEnvironmentVarBlankCurrentMasterPassword(): void
    {
        putenv(sprintf('%s=',
                UpdateMasterPasswordCommand::$envVarsMapping['masterPassword'])
        );

        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class,
            null,
            false
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Master password cannot be blank', $output);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testUpdateFromEnvironmentVarBlankMasterPassword(): void
    {
        putenv(sprintf('%s=',
                UpdateMasterPasswordCommand::$envVarsMapping['currentMasterPassword'])
        );

        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class,
            null,
            false
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Master password cannot be blank', $output);
    }

    private function setEnvironmentVariables(): void
    {
        putenv(sprintf('%s=%s',
                UpdateMasterPasswordCommand::$envVarsMapping['currentMasterPassword'],
                AccountCryptServiceTest::CURRENT_MASTERPASS)
        );
        putenv(sprintf('%s=%s',
                UpdateMasterPasswordCommand::$envVarsMapping['masterPassword'],
                AccountCryptServiceTest::NEW_MASTERPASS)
        );
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testUpdateFromEnvironmentVarIsSuccessful(): void
    {
        putenv(sprintf('%s=true',
                UpdateMasterPasswordCommand::$envVarsMapping['update'])
        );

        $this->setEnvironmentVariables();

        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class,
            null,
            false
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Master password updated', $output);
    }

    protected function setUp(): void
    {
        $this->setupDatabase();

        self::loadFixtures();

        parent::setUp();
    }
}
