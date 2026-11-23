<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

declare(strict_types=1);

namespace SP\Tests\Modules\Cli\Commands;

use Aura\SqlQuery\QueryFactory;
use DI\DependencyException;
use DI\NotFoundException;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Log\Providers\DatabaseHandler;
use SP\Infrastructure\Common\Repositories\SimpleRepository;
use SP\Infrastructure\Database\Database;
use SP\Modules\Cli\Commands\Crypt\UpdateMasterPasswordCommand;
use SP\Modules\Cli\Init;
use SP\Tests\DatabaseTrait;
use SP\Tests\Modules\Cli\CliTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use function SP\Tests\recreateDir;

/**
 *
 */
#[Group('integration')]
class UpdateMasterPasswordCommandTest extends CliTestCase
{

    use DatabaseTrait;

    private const CURRENT_MASTERPASS = '12345678900';
    private const NEW_MASTERPASS     = '00123456789';

    /**
     * @var string
     */
    protected static string $currentConfig;
    /**
     * @var string[]
     */
    protected static array $commandInputData = [
        '--currentMasterPassword' => self::CURRENT_MASTERPASS,
        '--masterPassword' => self::NEW_MASTERPASS,
        '--acknowledge-backup' => null,
    ];

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testRealCliRotationWritesDatabaseAuditEvents(): void
    {
        $this->removeUnsupportedFixtureRecords();
        self::$conn->exec('DELETE FROM EventLog');
        $data = self::$dic->get(ConfigFileService::class)->getConfigData();
        $data->setInstalled(true)->setLogEnabled(true)->setMaintenance(true);
        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($data);
        self::$dic->set(ConfigFileService::class, $config);
        self::$dic->set(ConfigDataInterface::class, $data);
        $input = new ArrayInput(array_merge(['command' => 'sp:crypt:update-master-password'], self::$commandInputData, ['--update' => null]));
        $input->setInteractive(false);
        self::$dic->set(InputInterface::class, $input);
        $output = new BufferedOutput();
        self::$dic->set(OutputInterface::class, $output);
        self::$dic->get(Application::class)->setAutoExit(false);
        self::$dic->get(Init::class)->initialize('');
        self::assertStringContainsString('Master password updated', $output->fetch());
        self::assertTrue(self::$dic->get(EventDispatcherInterface::class)->has(
            self::$dic->get(DatabaseHandler::class)
        ));
        self::assertSame(['update.masterPassword.start', 'update.masterPassword.end'],
            self::$conn->query("SELECT action FROM EventLog WHERE action IN ('update.masterPassword.start', 'update.masterPassword.end') ORDER BY id")->fetchAll(PDO::FETCH_COLUMN));
        $hash = self::$conn->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn();
        self::assertTrue(Hash::checkMasterKey(self::NEW_MASTERPASS, $hash));
    }

    #[Test]
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
    #[Test]
    public function testUpdateIsSuccessful(): void
    {
        $this->removeUnsupportedFixtureRecords();
        $before = $this->readDecryptedFixtureData(self::CURRENT_MASTERPASS);
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
        self::assertSame($before, $this->readDecryptedFixtureData(self::NEW_MASTERPASS));
        $hash = self::$conn->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn();
        self::assertTrue(Hash::checkMasterKey(self::NEW_MASTERPASS, $hash));
        self::assertSame('0', self::$conn->query("SELECT value FROM Config WHERE parameter = 'tempmaster_maxtime'")->fetchColumn());
        foreach (self::$conn->query('SELECT mPassHash FROM AccountHistory WHERE BIT_LENGTH(pass) > 0')->fetchAll(PDO::FETCH_COLUMN) as $historyHash) {
            self::assertTrue(Hash::checkMasterKey(self::NEW_MASTERPASS, $historyHash));
        }

        // Recreate cache directory to avoid unwanted behavior
        recreateDir(CACHE_PATH);
    }

    #[Test]
    public function testUpdateReportsUnsupportedPluginData(): void
    {
        $inputData = array_merge(self::$commandInputData, ['--update' => null]);
        $tester = $this->executeCommandTest(UpdateMasterPasswordCommand::class, $inputData);
        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('PluginData', $tester->getDisplay());
    }

    #[Test]
    public function testHistoryMismatchRollsBackAccountAndConfiguration(): void
    {
        $this->removeUnsupportedFixtureRecords();
        $stmt = self::$conn->prepare('UPDATE AccountHistory SET mPassHash = ? WHERE id = 3');
        $stmt->execute([Hash::hashKey('different synthetic password')]);
        $accountBefore = self::$conn->query('SELECT pass, `key` FROM Account ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $configBefore = self::$conn->query("SELECT parameter, value FROM Config WHERE parameter IN ('masterPwd', 'lastupdatempass', 'tempmaster_maxtime') ORDER BY parameter")->fetchAll(PDO::FETCH_ASSOC);
        $tester = $this->executeCommandTest(UpdateMasterPasswordCommand::class, array_merge(self::$commandInputData, ['--update' => null]));
        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Master password mismatch for record ID 3', $tester->getDisplay());
        self::assertSame($accountBefore, self::$conn->query('SELECT pass, `key` FROM Account ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
        self::assertSame($configBefore, self::$conn->query("SELECT parameter, value FROM Config WHERE parameter IN ('masterPwd', 'lastupdatempass', 'tempmaster_maxtime') ORDER BY parameter")->fetchAll(PDO::FETCH_ASSOC));
    }

    #[Test]
    public function testNestedRepositoryFailureRollsBackOnMariaDb(): void
    {
        $storage = self::$dic->get(DbStorageHandler::class);
        $pdo = $storage->getConnection();
        $events = $this->createStub(EventDispatcherInterface::class);
        $repo = new SimpleRepository(new Database($storage, $events), self::$dic->get(Context::class), $events, new QueryFactory('mysql'));
        $before = $pdo->query("SELECT value FROM Config WHERE parameter = 'lastupdatempass'")->fetchColumn();
        try {
            $repo->transactionAware(function () use ($repo, $pdo) {
                $repo->transactionAware(function () use ($pdo) {
                    $pdo->exec("UPDATE Config SET value = '42' WHERE parameter = 'lastupdatempass'");
                }, $this);
                throw new RuntimeException('outer failure');
            }, $this);
            self::fail('Outer transaction must fail');
        } catch (ServiceException) {
            self::assertSame($before, $pdo->query("SELECT value FROM Config WHERE parameter = 'lastupdatempass'")->fetchColumn());
        }
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Test]
    public function testUpdateFromEnvironmentVarIsAbort(): void
    {
        $this->setEnvironmentVariables();

        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class,
            ['--acknowledge-backup' => null],
            false
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Master password update aborted', $output);
    }

    private function setEnvironmentVariables(): void
    {
        putenv(sprintf('%s=%s',
                UpdateMasterPasswordCommand::$envVarsMapping['currentMasterPassword'],
                self::CURRENT_MASTERPASS)
        );
        putenv(sprintf('%s=%s',
                UpdateMasterPasswordCommand::$envVarsMapping['masterPassword'],
                self::NEW_MASTERPASS)
        );
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Test]
    public function testUpdateFromEnvironmentVarBlankCurrentMasterPassword(): void
    {
        putenv(sprintf('%s=',
                UpdateMasterPasswordCommand::$envVarsMapping['masterPassword'])
        );

        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class,
            ['--acknowledge-backup' => null],
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
    #[Test]
    public function testUpdateFromEnvironmentVarBlankMasterPassword(): void
    {
        putenv(sprintf('%s=',
                UpdateMasterPasswordCommand::$envVarsMapping['currentMasterPassword'])
        );

        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class,
            ['--acknowledge-backup' => null],
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
    #[Test]
    public function testUpdateFromEnvironmentVarIsSuccessful(): void
    {
        $this->removeUnsupportedFixtureRecords();
        putenv(sprintf('%s=true',
                UpdateMasterPasswordCommand::$envVarsMapping['update'])
        );

        $this->setEnvironmentVariables();

        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class,
            ['--acknowledge-backup' => null],
            false
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Master password updated', $output);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Test]
    public function testSameMasterPassword(): void
    {
        $inputData = [
            '--currentMasterPassword' => self::CURRENT_MASTERPASS,
            '--masterPassword' => self::CURRENT_MASTERPASS,
            '--update' => null,
            '--acknowledge-backup' => null,
        ];

        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class,
            $inputData
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Passwords are the same', $output);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Test]
    public function testWrongMasterPassword(): void
    {
        $inputData = [
            '--currentMasterPassword' => uniqid('', true),
            '--masterPassword' => self::NEW_MASTERPASS,
            '--update' => null,
            '--acknowledge-backup' => null,
        ];

        $commandTester = $this->executeCommandTest(
            UpdateMasterPasswordCommand::class,
            $inputData
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('The current master password does not match', $output);
    }

    protected function setUp(): void
    {
        $this->unsetEnvironmentVariables();

        $this->setupDatabase();

        self::loadFixtures();

        parent::setUp();
    }

    private function removeUnsupportedFixtureRecords(): void
    {
        self::setConnection();
        self::$conn->exec('DELETE FROM PluginData');
        self::$conn->exec('DELETE FROM AuthToken');
    }

    private function readDecryptedFixtureData(string $masterPassword): array
    {
        $crypt = self::$dic->get(CryptInterface::class);
        $result = [];
        foreach ([
                     'Account' => 'SELECT id, pass AS data, `key` FROM Account WHERE BIT_LENGTH(pass) > 0 ORDER BY id',
                     'AccountHistory' => 'SELECT id, pass AS data, `key` FROM AccountHistory WHERE BIT_LENGTH(pass) > 0 ORDER BY id',
                     'CustomFieldData' => 'SELECT definitionId, moduleId, itemId, data, `key` FROM CustomFieldData WHERE `key` IS NOT NULL ORDER BY definitionId, moduleId, itemId',
                 ] as $type => $sql) {
            foreach (self::$conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $result[$type][] = $crypt->decrypt($row['data'], $row['key'], $masterPassword);
            }
        }
        self::assertNotEmpty($result['Account']);
        self::assertNotEmpty($result['AccountHistory']);
        self::assertNotEmpty($result['CustomFieldData']);
        return $result;
    }

    private function unsetEnvironmentVariables(): void
    {
        foreach (UpdateMasterPasswordCommand::$envVarsMapping as $envVar) {
            putenv($envVar);
        }
    }
}
