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

namespace SP\Tests\Infrastructure\Crypt;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PDO;
use Aura\SqlQuery\QueryFactory;
use SP\Domain\Account\Ports\AccountMasterPasswordService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\Crypt\Ports\MasterPasswordStorageGuard;
use SP\Domain\Crypt\Services\MasterPass;
use SP\Domain\CustomField\Ports\CustomFieldCryptService;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Infrastructure\Common\Repositories\SimpleRepository;
use SP\Infrastructure\Database\Database;
use SP\Tests\UnitaryTestCase;

#[Group('integration')]
final class MasterPasswordTransactionTest extends UnitaryTestCase
{
    #[Test]
    public function beginListenerFailureDoesNotLeaveTransactionOpen(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $events->method('notify')->willReturnCallback(static function (string $name): void {
            if ($name === 'database.transaction.begin') {
                throw new \RuntimeException('synthetic listener failure');
            }
        });
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        try {
            $repository->transactionAware(static function (): void {}, $this);
            self::fail('Expected begin notification failure');
        } catch (\RuntimeException) {
            self::assertFalse($pdo->inTransaction());
        }
    }
    #[\PHPUnit\Framework\Attributes\TestWith(['update.masterPassword.end'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['database.transaction.end'])]
    #[Test]
    public function testCompletionEventFailureReportsThatRotationCommitted(string $failingEvent): void
    {
        $pdo = new PDO('sqlite::memory:');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $events->method('notify')->willReturnCallback(static function (string $name) use ($failingEvent): void {
            if ($name === $failingEvent) {
                throw new \RuntimeException('audit failed');
            }
        });
        $pdo->exec('CREATE TABLE test_rotation (value TEXT)');
        $accounts = $this->createStub(AccountMasterPasswordService::class);
        $accounts->method('updateMasterPassword')->willReturnCallback(static fn() => $pdo->exec("INSERT INTO test_rotation VALUES ('committed')"));
        $config = $this->createStub(ConfigService::class);
        $config->method('save')->willReturn(true);
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        $service = new MasterPass($this->config, $events, $this->context, $config, $accounts,
            $this->createStub(CustomFieldCryptService::class), $repository, $this->createStub(MasterPasswordStorageGuard::class));
        try {
            $service->changeMasterPassword(new UpdateMasterPassRequest('old', 'new', 'hash'));
            self::fail('A completion event failure must be reported');
        } catch (\SP\Domain\Crypt\Exceptions\MasterPasswordChangeCommittedException) {
            self::assertSame('committed', $pdo->query('SELECT value FROM test_rotation')->fetchColumn());
        }
    }

    #[Test]
    public function testRotationSucceedsWhenTemporaryPasswordSettingsAreAbsent(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        $config = $this->createMock(ConfigService::class);
        $config->method('getByParam')->willReturnCallback(static function (string $key, $default = null) {
            if ($default === null) {
                throw \SP\Domain\Core\Exceptions\NoSuchItemException::error('missing');
            }
            return $default;
        });
        $config->expects(self::exactly(2))->method('save')->willReturn(true);
        $service = new MasterPass($this->config, $events, $this->context, $config,
            $this->createStub(AccountMasterPasswordService::class), $this->createStub(CustomFieldCryptService::class),
            $repository, $this->createStub(MasterPasswordStorageGuard::class));
        $service->changeMasterPassword(new UpdateMasterPassRequest('old synthetic', 'new synthetic', 'hash'));
        self::assertFalse($pdo->inTransaction());
    }

    #[Test]
    public function testRotationClearsResidualTemporaryPasswordFields(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        $values = ['tempmaster_pass' => '', 'tempmaster_maxtime' => '0', 'tempmaster_passkey' => 'residual'];
        $config = $this->createStub(ConfigService::class);
        $config->method('getByParam')->willReturnCallback(static fn(string $key, $default = null) => $values[$key] ?? $default);
        $config->method('save')->willReturnCallback(static function (string $key, string $value) use (&$values): bool {
            $values[$key] = $value;
            return true;
        });
        $service = new MasterPass($this->config, $events, $this->context, $config,
            $this->createStub(AccountMasterPasswordService::class), $this->createStub(CustomFieldCryptService::class),
            $repository, $this->createStub(MasterPasswordStorageGuard::class));
        $service->changeMasterPassword(new UpdateMasterPassRequest('old', 'new', 'hash'));
        self::assertSame('', $values['tempmaster_passkey']);
    }

    #[Test]
    public function testCurrentPasswordIsRecheckedUnderTransactionBeforeWrites(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        $guard = $this->createMock(MasterPasswordStorageGuard::class);
        $guard->expects(self::once())->method('lockAndVerifyCurrentPassword')->with('old')
            ->willReturnCallback(static function () use ($pdo): void { self::assertTrue($pdo->inTransaction()); });
        $config = $this->createStub(ConfigService::class);
        $config->method('save')->willReturn(true);
        $service = new MasterPass($this->config, $events, $this->context, $config,
            $this->createStub(AccountMasterPasswordService::class), $this->createStub(CustomFieldCryptService::class), $repository, $guard);
        $service->changeMasterPassword(new UpdateMasterPassRequest('old', 'new', 'hash'));
    }

    #[Test]
    public function testNestedTransactionRemainsAtomicWhenOuterOperationFails(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE changes_test (value TEXT)');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        try {
            $repository->transactionAware(function () use ($repository, $pdo) {
                $repository->transactionAware(function () use ($pdo) {
                    $pdo->exec("INSERT INTO changes_test VALUES ('inner')");
                }, $this);
                throw new \RuntimeException('outer failed');
            }, $this);
            self::fail('Outer failure must roll back inner changes');
        } catch (ServiceException) {
            self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM changes_test')->fetchColumn());
        }
    }

    #[Test]
    public function testNestedCompletionFailureDoesNotBreakOuterRollback(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE changes_test (value TEXT)');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $failed = false;
        $events->method('notify')->willReturnCallback(static function (string $name) use (&$failed): void {
            if ($name === 'database.transaction.end' && !$failed) {
                $failed = true;
                throw new \RuntimeException('synthetic notification failure');
            }
        });
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        $continuedInsideTransaction = false;
        try {
            $repository->transactionAware(function () use ($repository, $pdo, &$continuedInsideTransaction): void {
                $pdo->exec("INSERT INTO changes_test VALUES ('outer')");
                try {
                    $repository->transactionAware(function () use ($pdo): void {
                        $pdo->exec("INSERT INTO changes_test VALUES ('inner')");
                    }, $this);
                    self::fail('Expected inner completion failure');
                } catch (ServiceException) {
                    $continuedInsideTransaction = $pdo->inTransaction();
                    self::assertTrue($pdo->inTransaction());
                    self::assertSame(['outer', 'inner'], $pdo->query('SELECT value FROM changes_test')->fetchAll(PDO::FETCH_COLUMN));
                    $pdo->exec("INSERT INTO changes_test VALUES ('after failure')");
                }
                throw new \RuntimeException('outer failure');
            }, $this);
            self::fail('Expected outer failure');
        } catch (ServiceException) {
            self::assertSame([], $pdo->query('SELECT value FROM changes_test')->fetchAll(PDO::FETCH_COLUMN));
        }
        self::assertTrue($continuedInsideTransaction, 'The outer transaction must remain active after the nested completion failure');
    }

    #[Test]
    public function testNestedFailureRollsBackOnlyItsSavepoint(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        $pdo->exec('CREATE TABLE changes_test (value TEXT)');
        $repository->transactionAware(function () use ($repository, $pdo) {
            $pdo->exec("INSERT INTO changes_test VALUES ('outer')");
            try {
                $repository->transactionAware(function () use ($pdo) {
                    $pdo->exec("INSERT INTO changes_test VALUES ('inner')");
                    throw new \RuntimeException('inner failed');
                }, $this);
            } catch (ServiceException) {
                // The outer operation deliberately continues after the inner failure.
            }
        }, $this);
        self::assertSame(['outer'], $pdo->query('SELECT value FROM changes_test')->fetchAll(PDO::FETCH_COLUMN));
    }

    #[Test]
    public function testStorageGuardErrorRemainsActionable(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        $guard = $this->createStub(MasterPasswordStorageGuard::class);
        $guard->method('assertTransactional')->willThrowException(ServiceException::error('PluginData records exist'));
        $service = new MasterPass($this->config, $events, $this->context, $this->createStub(ConfigService::class),
            $this->createStub(AccountMasterPasswordService::class), $this->createStub(CustomFieldCryptService::class), $repository, $guard);
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('PluginData records exist');
        $service->changeMasterPassword(new UpdateMasterPassRequest('old', 'new', 'hash'));
    }

    #[Test]
    public function testConfigurationFailureRollsBackEarlierWrites(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE values_test (name TEXT PRIMARY KEY, value TEXT)");
        $pdo->exec("INSERT INTO values_test VALUES ('account', 'old'), ('hash', 'old')");
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        $accounts = $this->createStub(AccountMasterPasswordService::class);
        $accounts->method('updateMasterPassword')->willReturnCallback(
            static fn() => $pdo->exec("UPDATE values_test SET value = 'new' WHERE name = 'account'")
        );
        $config = $this->createStub(ConfigService::class);
        $config->method('save')->willReturnCallback(static function (string $key) use ($pdo): bool {
            if ($key === 'masterPwd') {
                $pdo->exec("UPDATE values_test SET value = 'new' WHERE name = 'hash'");
                return true;
            }
            return false;
        });
        $service = new MasterPass(
            $this->config,
            $events,
            $this->context,
            $config,
            $accounts,
            $this->createStub(CustomFieldCryptService::class),
            $repository,
            $this->createStub(MasterPasswordStorageGuard::class)
        );

        try {
            $service->changeMasterPassword(new UpdateMasterPassRequest('old', 'new', 'hash'));
            self::fail('The failed configuration write must abort the change');
        } catch (ServiceException) {
            self::assertSame(['old', 'old'], $pdo->query('SELECT value FROM values_test ORDER BY name')->fetchAll(PDO::FETCH_COLUMN));
            self::assertFalse($pdo->inTransaction());
        }
    }
}
