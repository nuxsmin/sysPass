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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Ports\AppPaths;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Domain\Export\Ports\BackupFileService;
use SP\Modules\Cli\Commands\BackupCommand;
use SP\Modules\Cli\Commands\Crypt\UpdateMasterPasswordCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unitary/adapter characterization tests for CLI BackupCommand and UpdateMasterPasswordCommand:
 * lock contention exit codes, permission/destination handling, and abort/failure exit codes.
 */
#[Group('unitary')]
final class CliOperationsBoundaryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'syspass-cli-ops-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    /**
     * Lock contention must fail so automation does not mistake it for a completed change.
     */
    #[Test]
    public function updateMasterPasswordCommandFailsOnCrossProcessLockContention(): void
    {
        $autoload = REAL_APP_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        $readySignal = $this->tempDir . DIRECTORY_SEPARATOR . 'lock-held';
        $stopSignal = $this->tempDir . DIRECTORY_SEPARATOR . 'lock-stop';

        $holderScript = sprintf(
            <<<'PHP'
require %s;
$store = \Symfony\Component\Lock\Store\SemaphoreStore::isSupported()
    ? new \Symfony\Component\Lock\Store\SemaphoreStore()
    : new \Symfony\Component\Lock\Store\FlockStore();
$lock = (new \Symfony\Component\Lock\LockFactory($store))->createLock('sp:crypt:update-master-password');
if (!$lock->acquire()) {
    fwrite(STDERR, "Failed to acquire initial lock\n");
    exit(2);
}
file_put_contents(%s, 'locked');
while (!file_exists(%s)) {
    usleep(20000);
}
$lock->release();
PHP,
            var_export($autoload, true),
            var_export($readySignal, true),
            var_export($stopSignal, true)
        );

        $scriptFile = $this->tempDir . DIRECTORY_SEPARATOR . 'hold_lock.php';
        file_put_contents($scriptFile, "<?php\n" . $holderScript);

        $proc = proc_open(
            [PHP_BINARY, $scriptFile],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($proc);
        fclose($pipes[0]);

        try {
            $deadline = microtime(true) + 5.0;
            while (!file_exists($readySignal) && microtime(true) < $deadline) {
                usleep(20000);
            }
            self::assertFileExists($readySignal, 'Background lock holder did not acquire Symfony lock in time');

            $configData = $this->createStub(ConfigDataInterface::class);
            $config = $this->createStub(ConfigFileService::class);
            $config->method('getConfigData')->willReturn($configData);

            $masterPassService = $this->createMock(MasterPassService::class);
            $masterPassService->expects(self::never())->method('changeMasterPassword');

            $command = new UpdateMasterPasswordCommand(
                $masterPassService,
                $this->createStub(AccountService::class),
                $this->createStub(ConfigService::class),
                $this->createStub(LoggerInterface::class),
                $config
            );

            $tester = new CommandTester($command);
            $tester->execute(
                [
                    '--currentMasterPassword' => 'old',
                    '--masterPassword' => 'new',
                    '--update' => true,
                ],
                ['interactive' => false]
            );

            self::assertSame(Command::FAILURE, $tester->getStatusCode());
            self::assertStringContainsString(
                'The command is already running in another process',
                $tester->getDisplay()
            );
        } finally {
            file_put_contents($stopSignal, 'stop');
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);
        }
    }

    /**
     * Characterization: UpdateMasterPasswordCommand returns Command::FAILURE (1) without touching
     * MasterPassService::changeMasterPassword() when passwords match, current password fails check,
     * or confirmation is declined.
     */
    #[Test]
    public function updateMasterPasswordCommandReturnsNonZeroOnSameWrongOrAbortedInputs(): void
    {
        $configData = $this->createStub(ConfigDataInterface::class);
        $configData->method('isInstalled')->willReturn(true);
        $configData->method('isMaintenance')->willReturn(true);

        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($configData);

        $configService = $this->createStub(ConfigService::class);
        $configService->method('getByParam')->willReturn('stored-hash');

        $masterPassService = $this->createMock(MasterPassService::class);
        $masterPassService->expects(self::never())->method('changeMasterPassword');
        $masterPassService->method('checkMasterPassword')->willReturnCallback(
            static fn(string $pass): bool => $pass === 'correct-old'
        );

        $command = new UpdateMasterPasswordCommand(
            $masterPassService,
            $this->createStub(AccountService::class),
            $configService,
            $this->createStub(LoggerInterface::class),
            $config
        );

        // 1. Same password -> FAILURE (1)
        $sameTester = new CommandTester($command);
        $sameTester->execute([
            '--currentMasterPassword' => 'same-pass',
            '--masterPassword' => 'same-pass',
            '--update' => true,
            '--acknowledge-backup' => true,
        ], ['interactive' => false]);
        self::assertSame(Command::FAILURE, $sameTester->getStatusCode());
        self::assertStringContainsString('Passwords are the same', $sameTester->getDisplay());

        // 2. Wrong current master password -> FAILURE (1)
        $wrongTester = new CommandTester($command);
        $wrongTester->execute([
            '--currentMasterPassword' => 'wrong-old',
            '--masterPassword' => 'new-pass',
            '--update' => true,
            '--acknowledge-backup' => true,
        ], ['interactive' => false]);
        self::assertSame(Command::FAILURE, $wrongTester->getStatusCode());
        self::assertStringContainsString('The current master password does not match', $wrongTester->getDisplay());

        // 3. Aborted confirmation (no --update, non-interactive) -> FAILURE (1)
        $abortTester = new CommandTester($command);
        $abortTester->execute([
            '--currentMasterPassword' => 'correct-old',
            '--masterPassword' => 'new-pass',
            '--acknowledge-backup' => true,
        ], ['interactive' => false]);
        self::assertSame(Command::FAILURE, $abortTester->getStatusCode());
        self::assertStringContainsString('Master password update aborted', $abortTester->getDisplay());
    }

    /**
     * Characterization: BackupCommand creates missing destination dirs (0750), rejects non-writable
     * destinations and backup service errors with Command::FAILURE (1) while logging full trace,
     * and allows repeated runs without lock contention (0).
     */
    #[Test]
    public function backupCommandCharacterizesDestinationCreationReadOnlyErrorAndRepeatedSuccess(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            self::markTestSkipped('Read-only chmod permission check requires non-root UID');
        }

        $configData = $this->createStub(ConfigDataInterface::class);
        $configData->method('isInstalled')->willReturn(true);
        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($configData);

        $appPaths = $this->createStub(AppPaths::class);
        $appPaths->method('getBackupPath')->willReturn($this->tempDir);
        $appPaths->method('getApplicationPath')->willReturn(APP_PATH);

        $loggedErrors = [];
        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(static function (string $msg) use (&$loggedErrors): void {
            $loggedErrors[] = $msg;
        });

        // 1. Read-only directory -> exit 1
        $readOnlyDir = $this->tempDir . DIRECTORY_SEPARATOR . 'readonly';
        mkdir($readOnlyDir, 0500);

        $failBackupService = $this->createMock(BackupFileService::class);
        $failBackupService->expects(self::never())->method('doBackup');

        $roCommand = new BackupCommand($failBackupService, $logger, $config, $appPaths);
        $roTester = new CommandTester($roCommand);
        $roTester->execute(['--path' => $readOnlyDir], ['interactive' => false]);

        self::assertSame(Command::FAILURE, $roTester->getStatusCode());
        self::assertStringContainsString('Please, check the backup directory permissions', $roTester->getDisplay());
        chmod($readOnlyDir, 0755);

        // 2. ServiceException -> exit 1 and stack trace logged
        $exceptionService = $this->createMock(BackupFileService::class);
        $exceptionService->expects(self::once())
            ->method('doBackup')
            ->willThrowException(ServiceException::error('Disk full during backup'));

        $errCommand = new BackupCommand($exceptionService, $logger, $config, $appPaths);
        $errTester = new CommandTester($errCommand);
        $errTester->execute(['--path' => $this->tempDir], ['interactive' => false]);

        self::assertSame(Command::FAILURE, $errTester->getStatusCode());
        self::assertStringContainsString('Disk full during backup', $errTester->getDisplay());
        self::assertNotEmpty($loggedErrors);
        self::assertStringContainsString('#0 ', $loggedErrors[0]);

        // 3. New subdirectory under writable parent + repeated runs succeed (no lock contention)
        $targetSubdir = $this->tempDir . DIRECTORY_SEPARATOR . 'created-backup';
        $okBackupService = $this->createMock(BackupFileService::class);
        $okBackupService->expects(self::exactly(2))
            ->method('doBackup')
            ->with($targetSubdir, APP_PATH);

        $okCommand = new BackupCommand($okBackupService, $logger, $config, $appPaths);
        $firstRun = new CommandTester($okCommand);
        $firstRun->execute(['--path' => $targetSubdir], ['interactive' => false]);
        $secondRun = new CommandTester($okCommand);
        $secondRun->execute(['--path' => $targetSubdir], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $firstRun->getStatusCode());
        self::assertSame(Command::SUCCESS, $secondRun->getStatusCode());
        self::assertDirectoryExists($targetSubdir);
        self::assertSame('0750', sprintf('%04o', fileperms($targetSubdir) & 0777));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                @chmod($path, 0755);
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
